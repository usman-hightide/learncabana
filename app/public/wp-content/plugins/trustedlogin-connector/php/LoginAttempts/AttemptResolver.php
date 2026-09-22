<?php
/**
 * Login attempt resolver for REST API integration.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\LoginAttempts;

use TrustedLogin\Vendor\ApiHandler;
use TrustedLogin\Vendor\TeamSettings;
use WP_Error;

/**
 * Stateless wrapper over ApiHandler::call() for the SaaS
 * /api/v1/logs/login-attempts/* endpoints.
 *
 * The team's stored `private_key` ships in the X-TL-Api-Key header,
 * paired with X-TL-Auth-Version for the SaaS CheckApiKey middleware.
 *
 * @since 2.0.0
 */
final class AttemptResolver {

	const PATH_PREFIX         = 'logs';
	const PATH_LOGIN_ATTEMPTS = 'login-attempts';
	const QUERY_KEY_PER_PAGE  = 'per_page';
	const QUERY_KEY_PAGE      = 'page';

	/**
	 * API handler instance.
	 *
	 * @var ApiHandler|object Injectable for tests.
	 */
	private $api;

	/**
	 * Team settings object.
	 *
	 * @var TeamSettings
	 */
	private $team;

	/**
	 * Inject the API handler and team settings.
	 *
	 * @param ApiHandler|object $api  Anything with a `call()` method;
	 *                                Mockery doubles work here in tests.
	 * @param TeamSettings      $team Team whose `private_key` and
	 *                                `account_id` populate the request.
	 */
	public function __construct( $api, TeamSettings $team ) {
		$this->api  = $api;
		$this->team = $team;
	}

	/**
	 * Fetch one attempt by id. Validates the lpat_<UUID> shape locally
	 * before any HTTP call, so a malformed bookmark never leaves the
	 * Connector.
	 *
	 * @param string $attempt_id The login attempt ID to fetch.
	 * @return array|WP_Error
	 */
	public function getOne( string $attempt_id ) {
		if ( ! preg_match( Constants::ATTEMPT_ID_REGEX, $attempt_id ) ) {
			return new WP_Error( 'invalid_attempt_id', esc_html__( 'Malformed attempt id.', 'trustedlogin-connector' ) );
		}

		$endpoint = ApiHandler::buildEndpoint(
			array( self::PATH_PREFIX, self::PATH_LOGIN_ATTEMPTS, $this->team_id(), $attempt_id )
		);

		$this->stamp_v2_auth_headers();

		$result = $this->api->call( $endpoint, array(), 'GET' );

		return $this->normalize_single( $result );
	}

	/**
	 * Paginated list. Per_page is forwarded as-is; SaaS side enforces
	 * its own clamp so we don't double-clamp.
	 *
	 * @param array $args Recognised keys: per_page, page.
	 *
	 * @return array|WP_Error  ['data' => array<int, array>, 'meta' => array]
	 */
	public function getList( array $args = array() ) {
		$query = array();

		if ( isset( $args[ self::QUERY_KEY_PER_PAGE ] ) ) {
			$query[ self::QUERY_KEY_PER_PAGE ] = (int) $args[ self::QUERY_KEY_PER_PAGE ];
		}
		if ( isset( $args[ self::QUERY_KEY_PAGE ] ) ) {
			$query[ self::QUERY_KEY_PAGE ] = (int) $args[ self::QUERY_KEY_PAGE ];
		}

		$endpoint = ApiHandler::buildEndpoint(
			array( self::PATH_PREFIX, self::PATH_LOGIN_ATTEMPTS, $this->team_id() ),
			$query
		);

		$this->stamp_v2_auth_headers();

		$result = $this->api->call( $endpoint, array(), 'GET' );

		return $this->normalize_list( $result );
	}

	/**
	 * Stringified team id for the SaaS path. The SaaS treats the
	 * value as opaque, so casting to string here keeps both numeric
	 * and prefixed ids working without an extra encode step.
	 *
	 * @return string
	 */
	private function team_id(): string {
		return (string) $this->team->get( 'account_id' );
	}

	/**
	 * Stamps {@see ApiHandler::HEADER_API_KEY} and
	 * {@see ApiHandler::HEADER_AUTH_VERSION} on the injected handler
	 * for the next outbound call. No-op when no private_key is stored.
	 *
	 * ## Lifecycle contract
	 *
	 * This method MUTATES `$this->api`'s additional_headers map in
	 * place; each call overwrites the prior values. Callers MUST NOT
	 * share an AttemptResolver instance across teams within the same
	 * request — every getOne() / list() invocation re-stamps the same
	 * team's credentials, so a cross-team reuse would silently leak
	 * one team's api-key onto another team's request. The Activity
	 * endpoint constructs a fresh resolver per request, which is the
	 * supported pattern.
	 *
	 * @return void
	 */
	private function stamp_v2_auth_headers() {
		$api_key = (string) $this->team->get( 'private_key' );
		if ( '' === $api_key ) {
			return;
		}

		$this->api->setAdditionalHeader( ApiHandler::HEADER_API_KEY, $api_key );
		$this->api->setAdditionalHeader( ApiHandler::HEADER_AUTH_VERSION, ApiHandler::AUTH_VERSION_V2 );
	}

	/**
	 * Normalize a single-attempt response. ApiHandler::call() returns
	 * json_decode($body) — stdClass for objects, array for arrays.
	 * Use a wp_json_encode → json_decode(assoc=true) round-trip
	 * (matches Endpoints/Activity::get_logins pattern) to land on a
	 * consistent associative-array shape.
	 *
	 * @param mixed $result The response to normalize.
	 *
	 * @return array|WP_Error
	 */
	private function normalize_single( $result ) {
		if ( is_wp_error( $result ) ) {
			// ApiHandler emits the literal code 'not_found' on HTTP 404
			// (php/ApiHandler.php). Match it directly. The substring
			// fallback covers transport-level WP_Errors (e.g. a code
			// that happens to embed '404' from a future ApiHandler
			// branch) without re-introducing the brittle-only path.
			$code = (string) $result->get_error_code();
			if ( 'not_found' === $code || false !== strpos( $code, '404' ) ) {
				return new WP_Error( 'not_found', 'Attempt not found.' );
			}

			return new WP_Error( 'saas_error', $result->get_error_message() );
		}

		if ( ! is_array( $result ) && ! is_object( $result ) ) {
			return new WP_Error( 'malformed_response', 'Unexpected response shape.' );
		}

		$encoded = wp_json_encode( $result );
		if ( false === $encoded ) {
			return new WP_Error( 'malformed_response', 'Could not re-encode response.' );
		}

		$assoc = json_decode( $encoded, true );
		if ( ! is_array( $assoc ) || empty( $assoc['id'] ) ) {
			return new WP_Error( 'malformed_response', 'Response missing id.' );
		}

		return $assoc;
	}

	/**
	 * Normalize a paginated list response.
	 *
	 * @param mixed $result The response to normalize.
	 *
	 * @return array|WP_Error
	 */
	private function normalize_list( $result ) {
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'saas_error', $result->get_error_message() );
		}

		if ( ! is_array( $result ) && ! is_object( $result ) ) {
			return new WP_Error( 'malformed_response', 'Unexpected response shape.' );
		}

		$encoded = wp_json_encode( $result );
		if ( false === $encoded ) {
			return new WP_Error( 'malformed_response', 'Could not re-encode response.' );
		}

		$assoc = json_decode( $encoded, true );

		if ( ! is_array( $assoc ) || ! isset( $assoc['data'], $assoc['meta'] ) ) {
			return new WP_Error( 'malformed_response', 'Response missing data/meta.' );
		}

		return array(
			'data' => array_values( (array) $assoc['data'] ),
			'meta' => (array) $assoc['meta'],
		);
	}
}

<?php
/**
 * ApiHandler implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Contracts\SendsApiRequests as ApiSender;
use TrustedLogin\Vendor\Traits\Logger;
use WP_Error;
use Exception;

/**
 * Class: TrustedLogin API Handler
 *
 * @version 0.1.0
 */
class ApiHandler {

	use Logger;

	/**
	 * Current API version.
	 *
	 * @var string
	 */
	const API_VERSION = 'v1';

	/**
	 * Header carrying the team api_key for SaaS routes that authenticate
	 * via `CheckApiKey` middleware (logs/logins/*, logs/login-attempts/*).
	 *
	 * Replaces the legacy `?api_key=` query parameter, which leaked the
	 * credential into access logs and any URL-redacting client. The
	 * matching constant on the SaaS side is `CheckApiKey::HEADER`.
	 *
	 * @since 2.0.0
	 */
	const HEADER_API_KEY = 'X-TL-Api-Key';

	/**
	 * Capability header advertised by Connectors that opt in to the
	 * header-only / request-bound auth surface (v2). When this header is
	 * set, the SaaS refuses to fall back to the legacy `?api_key=` query
	 * parameter or to a static SHA-256 bearer — a silent downgrade is no
	 * longer possible.
	 *
	 * Shared between the CheckApiKey path (logs routes) and the
	 * CheckPrivateKey path (bearer-token routes) so both surfaces fail
	 * loudly on misconfigured intermediaries.
	 *
	 * @since 2.0.0
	 */
	const HEADER_AUTH_VERSION = 'X-TL-Auth-Version';

	/**
	 * Value sent in {@see HEADER_AUTH_VERSION} when a request is built
	 * with the v2 conventions: header-only api_key, request-bound HMAC
	 * bearer (METHOD|URI|sha256(body)|ts|nonce), and the X-TL-Timestamp
	 * / X-TL-Nonce companions on bearer routes.
	 *
	 * @since 2.0.0
	 */
	const AUTH_VERSION_V2 = '2';

	/**
	 * The URL for the API being queried.
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * The API private key for authenticating API calls.
	 *
	 * @var string
	 */
	private $private_key;

	/**
	 * The TrustedLogin API Key used in generating the X-TL-TOKEN header.
	 *
	 * @var string
	 */
	private $public_key;

	/**
	 * Whether an Auth token is required.
	 *
	 * @var bool
	 */
	private $auth_required = true;

	/**
	 * The type of Header to use for sending the token.
	 *
	 * @var string
	 */
	private $auth_header_type = 'Authorization';

	/**
	 * Additional headers added to the API handler instance.
	 *
	 * @var array
	 */
	private $additional_headers = array();

	/**
	 * Whether debug logging is enabled.
	 *
	 * @var bool
	 */
	private $debug_mode = false;

	/**
	 * The API sender instance for making requests.
	 *
	 * @var ApiSender
	 */
	private $apiSender;

	/**
	 * Initialize the API handler.
	 *
	 * @param array     $data Configuration data with keys: private_key, public_key, debug_mode, api_url, auth_required.
	 * @param ApiSender $apiSender The API request sender instance.
	 */
	public function __construct( $data, ApiSender $apiSender ) {
		$this->apiSender = $apiSender;
		$defaults        = array(
			'private_key'   => null,
			'public_key'    => null,
			'debug_mode'    => false,
			'api_url'       => TRUSTEDLOGIN_API_URL,
			'auth_required' => true,
		);

		$atts = wp_parse_args( $data, $defaults );

		foreach ( array_keys( $defaults ) as $key ) {
			$this->{$key} = $atts[ $key ];
		}
	}

	/**
	 * Get the full versioned API URL with trailing slash.
	 *
	 * @internal
	 *
	 * @return string
	 */
	public function getApiUrl() {
		return $this->api_url;
	}

	/**
	 * Get the authorization header type.
	 *
	 * @return string
	 */
	public function getAuthHeaderType() {
		return $this->auth_header_type;
	}

	/**
	 * Legacy v1 bearer: a plain `sha256(private_key)`. Static — not
	 * bound to the request method/URL/body — and therefore replayable
	 * by anything that observes a single legitimate Authorization
	 * header (proxy logs, exception trackers that capture headers, a
	 * past compromised host, etc.).
	 *
	 * Kept for compatibility with SaaS deployments running the v1
	 * `CheckPrivateKey` path. New deployments opt into the v2 surface
	 * via {@see HEADER_AUTH_VERSION}, where {@see computeV2Bearer()}
	 * binds the bearer to METHOD|URI|sha256(body)|ts|nonce.
	 *
	 * @return string
	 */
	private function getAuthBearerToken() {
		return hash( 'sha256', $this->private_key );
	}

	/**
	 * Header carrying the Unix-seconds timestamp the Connector used when
	 * computing the v2 HMAC bearer. SaaS-side counterpart:
	 * `GetTeamByPrivateKey::TIMESTAMP_HEADER`.
	 *
	 * @since 2.0.0
	 */
	const HEADER_TIMESTAMP = 'X-TL-Timestamp';

	/**
	 * Header carrying the per-request nonce used in the v2 HMAC bearer.
	 * SaaS-side counterpart: `GetTeamByPrivateKey::NONCE_HEADER`.
	 *
	 * @since 2.0.0
	 */
	const HEADER_NONCE = 'X-TL-Nonce';

	/**
	 * SaaS-specific HTTP statuses {@see self::handleResponse()} branches on.
	 * Named so a `switch` case reading `423` is grep-able to its intent and a
	 * future SaaS upgrade that retires one of these returns a parse-time
	 * clue (vs. a silently dead branch).
	 *
	 * @since 2.0.0
	 */
	const HTTP_GONE              = 410;
	const HTTP_LOCKED            = 423;   // SaaS pause-mode indicator.
	const HTTP_FAILED_DEPENDENCY = 424;   // SaaS signature-key fetch failed.

	/**
	 * Computes the v2 request-bound HMAC bearer.
	 *
	 * Inputs are exactly the canonical message the SaaS recomputes for
	 * `hash_equals` in `GetTeamByPrivateKey::matchesTeamV2()`:
	 *
	 *     METHOD \n REQUEST_URI \n sha256(body) \n ts \n nonce
	 *
	 * `$request_uri` MUST be the path + query string the SaaS will see
	 * (matching Laravel's `Request::getRequestUri()`). For an outbound
	 * URL like `https://api.trustedlogin.com/v1/accounts/42/sites/`, the
	 * value is `/v1/accounts/42/sites/`.
	 *
	 * `$body_bytes` MUST be the exact bytes the HTTP client will send as
	 * the request body — same `wp_json_encode()` form ApiSend uses, or
	 * the empty string when there is no body.
	 *
	 * @since 2.0.0
	 *
	 * @param string $api_key      Team api_key (Connector's stored `private_key`).
	 * @param string $method       Uppercase HTTP method (GET, POST, PUT, DELETE).
	 * @param string $request_uri  Path + query, leading slash, matching Laravel.
	 * @param string $body_bytes   Exact body bytes that will be sent (may be '').
	 * @param int    $timestamp    Unix seconds (`time()`).
	 * @param string $nonce        Per-request random nonce.
	 *
	 * @return string Base64-encoded HMAC-SHA256 — directly usable as the
	 *                Authorization bearer value.
	 */
	public static function computeV2Bearer( $api_key, $method, $request_uri, $body_bytes, $timestamp, $nonce ) {
		$message = strtoupper( $method ) . "\n"
			. $request_uri . "\n"
			. hash( 'sha256', $body_bytes ) . "\n"
			. (int) $timestamp . "\n"
			. $nonce;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HMAC signature, not obfuscation.
		return base64_encode( hash_hmac( 'sha256', $message, $api_key, true ) );
	}

	/**
	 * Computes the request URI the SaaS will see, given a full outbound
	 * URL. Mirrors Laravel's `Request::getRequestUri()` — path + `?query`
	 * starting with a leading slash, no scheme/host. Used as the second
	 * input to {@see computeV2Bearer()}.
	 *
	 * @since 2.0.0
	 *
	 * @param string $url Parameter.
	 *
	 * @return string
	 */
	public static function requestUriFromUrl( $url ) {
		$parsed = wp_parse_url( $url );
		if ( ! is_array( $parsed ) ) {
			return '/';
		}
		$path = isset( $parsed['path'] ) ? (string) $parsed['path'] : '';
		// A well-formed URI path must start with `/`. Anything else
		// (empty, `://no-scheme`-style junk parsed into the path) is
		// fallback-to-root so the HMAC canonical message is sane.
		if ( '' === $path || '/' !== $path[0] ) {
			return '/';
		}
		$query = isset( $parsed['query'] ) && '' !== $parsed['query'] ? '?' . $parsed['query'] : '';

		return $path . $query;
	}

	/**
	 * Generates a per-request nonce suitable for the v2 HMAC bearer.
	 *
	 * Uses `random_bytes()` directly (cryptographic primitive, never
	 * filtered) and a 32-hex-char output so a malicious local plugin
	 * can't constrain the alphabet or push the value to a colliding
	 * one via `wp_generate_password()`-style filter overrides.
	 *
	 * @since 2.0.0
	 *
	 * @return string 32 hex characters (16 bytes of entropy).
	 */
	public static function generateNonce() {
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Returns the hash used for the X-TL-TOKEN header.
	 *
	 * @since 1.0
	 *
	 * @return string|WP_Error $saas_token Additional SaaS Token for authenticating API queries. WP_Error on error.
	 */
	public function getXTlToken() {

		if ( ! $this->public_key ) {
			return new WP_Error( 'missing_public_key' );
		}

		if ( ! $this->private_key ) {
			return new WP_Error( 'missing_private_key' );
		}

		return hash( 'sha256', $this->public_key . $this->private_key );
	}

	/**
	 * Get public key
	 *
	 * @return string
	 */
	public function getApiKey() {
		return $this->public_key;
	}

	/**
	 * Returns the per-instance additional headers, plus the legacy
	 * X-TL-TOKEN (still required by the SaaS `getEnvelope` route — see
	 * `app/Http/Controllers/SiteController.php`). The Authorization
	 * bearer is NOT set here anymore — it's stamped per-call by
	 * {@see stampBearerAuthHeaders()} so the v2 bearer can include
	 * the request method/URL/body bytes in its HMAC input.
	 *
	 * Callers that need only the static-shape headers (e.g.
	 * `verify()`'s eager log line) can still rely on this method;
	 * callers that send a request should use the headers returned
	 * by `stampBearerAuthHeaders()` instead.
	 *
	 * @return array
	 */
	public function getAdditionalHeader() {
		if ( ! empty( $this->private_key ) && ! empty( $this->public_key ) ) {
			$this->additional_headers['X-TL-TOKEN'] = $this->getXTlToken();
		}
		return $this->additional_headers;
	}

	/**
	 * Sets the Header authorization type
	 *
	 * @since 0.8.0
	 *
	 * @param string          $key The Header key to add.
	 * @param string|WP_Error $value The Header value to add.
	 *
	 * @return array|false
	 */
	public function setAdditionalHeader( $key, $value ) {

		if ( empty( $key ) || empty( $value ) || is_wp_error( $value ) ) {
			return false;
		}

		$this->additional_headers[ $key ] = $value;

		return $this->additional_headers;
	}


	/**
	 * Builds an API endpoint string with URL-safe path segments and a
	 * sanitized query string. Callers should prefer this over manual
	 * concatenation so path segments and query args are properly encoded.
	 *
	 * @since 2.0.0
	 *
	 * @param array<int, string|int> $path_segments Unencoded path segments; each is rawurlencoded.
	 * @param array<string, mixed>   $query_args    Associative query args; keys/values are encoded by add_query_arg.
	 * @param bool                   $trailing_slash Append a trailing slash to the path. Default false.
	 *
	 * @return string Endpoint relative to the API base (no leading slash).
	 */
	public static function buildEndpoint( array $path_segments, array $query_args = array(), $trailing_slash = false ) {
		$encoded = array();
		foreach ( $path_segments as $segment ) {
			if ( '' === (string) $segment ) {
				continue;
			}
			$encoded[] = rawurlencode( (string) $segment );
		}

		$path = implode( '/', $encoded );

		// Some upstream routes require a trailing slash (distinct from
		// "collection with no id"). Handle it here so call sites never need
		// to concatenate a bare `/` onto the result.
		if ( $trailing_slash && '' !== $path ) {
			$path .= '/';
		}

		if ( empty( $query_args ) ) {
			return $path;
		}

		return add_query_arg( $query_args, $path );
	}

	/**
	 * Make an API call to the SaaS backend.
	 *
	 * @param string $endpoint The API endpoint path.
	 * @param mixed  $data The request body data.
	 * @param string $method The HTTP method (GET, POST, PUT, DELETE).
	 *
	 * @return mixed The parsed response or false on failure.
	 */
	public function call( $endpoint, $data, $method ) {

		$additional_headers = $this->getAdditionalHeader();

		$url = $this->getApiUrl() . $endpoint;

		if ( ! empty( $this->private_key ) ) {
			$this->stampBearerAuthHeaders( $additional_headers, $url, $data, $method );
		}

		if ( $this->auth_required && empty( $additional_headers ) ) {
			$this->log( 'Auth required for API call', __METHOD__, 'error' );

			return false;
		}

		// Replace bearer-equivalent query-arg values with `REDACTED` before
		// the URL is written to the debug log. `/logs/logins/*`
		// authenticates via the `api_key` query param (CheckApiKey
		// middleware); leaving it verbatim in the logged URL puts a
		// team-admin credential in plaintext on disk under
		// wp-content/uploads.
		$log_url = Utils::redact_url_for_logging( $url );
		$this->log( "Sending $method API call to $log_url", __METHOD__, 'debug' );

		$api_response = $this->apiSend( $url, $data, $method, $additional_headers );

		return $this->handleResponse( $api_response );
	}

	/**
	 * Stamps the Authorization header — and, on the v2 path, the
	 * timestamp / nonce / auth-version headers — on a per-request
	 * headers array.
	 *
	 * V2 is opt-in via the `trustedlogin/connector/auth/use-v2-bearer`
	 * filter. When enabled, the bearer is bound to METHOD|URI|sha256(body)
	 * |ts|nonce so observation of one request can't replay against a
	 * different one. Defaults to TRUE because the SaaS accepts both v1
	 * and v2 on the same routes — a Connector built from this commit
	 * forward should never need v1.
	 *
	 * @param array  $additional_headers Headers array, mutated in place.
	 * @param string $url                Full outbound URL.
	 * @param mixed  $data               Request body (may be encoded later
	 *                                   by ApiSend); must match the bytes
	 *                                   that ApiSend actually transmits or
	 *                                   the HMAC won't verify.
	 * @param string $method             HTTP method.
	 *
	 * @return void
	 */
	private function stampBearerAuthHeaders( array &$additional_headers, $url, $data, $method ) {
		/**
		 * Filters whether to send the v2 request-bound HMAC bearer instead
		 * of the legacy static SHA-256 bearer. Filter is provided as a
		 * release-gate / rollback knob — operators can flip back to v1
		 * without redeploying if a SaaS regression is observed. The
		 * underlying SaaS still accepts both for the foreseeable future.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $use_v2 Default true.
		 */
		$use_v2 = (bool) apply_filters( 'trustedlogin/connector/auth/use-v2-bearer', true );

		if ( ! $use_v2 ) {
			// Make the downgrade observable. Without this signal a
			// hostile or buggy filter could pin every request to the
			// legacy static bearer silently, defeating the
			// request-binding hardening. The log goes through the
			// Logger trait so it lands in the connector debug log when
			// error logging is enabled; the option timestamp is queryable
			// from wp-cli even when logs are off.
			update_site_option( 'tlc_v2_bearer_downgrade_last_seen', time() );
			$this->log(
				'v2 bearer is suppressed by the trustedlogin/connector/auth/use-v2-bearer filter — sending legacy static bearer.',
				__METHOD__,
				'warning'
			);
			$additional_headers[ $this->auth_header_type ] = 'Bearer ' . $this->getAuthBearerToken();

			return;
		}

		$body_bytes = $this->encodeBodyForHmac( $data );
		$timestamp  = time();
		$nonce      = self::generateNonce();

		$bearer = self::computeV2Bearer(
			$this->private_key,
			$method,
			self::requestUriFromUrl( $url ),
			$body_bytes,
			$timestamp,
			$nonce
		);

		$additional_headers[ $this->auth_header_type ]   = 'Bearer ' . $bearer;
		$additional_headers[ self::HEADER_AUTH_VERSION ] = self::AUTH_VERSION_V2;
		$additional_headers[ self::HEADER_TIMESTAMP ]    = (string) $timestamp;
		$additional_headers[ self::HEADER_NONCE ]        = $nonce;
	}

	/**
	 * Encodes the request body to the exact bytes that ApiSend will
	 * transmit, so the HMAC computed here matches what the SaaS hashes.
	 *
	 * The contract mirrors ApiSend::send(): truthy data → wp_json_encode
	 * with default flags; falsy data → empty string. Any divergence would
	 * silently break v2 auth, so this method is the single source of
	 * truth for body bytes.
	 *
	 * @param mixed $data Parameter.
	 *
	 * @return string
	 */
	private function encodeBodyForHmac( $data ) {
		if ( ! $data ) {
			return '';
		}

		$encoded = wp_json_encode( $data );

		return false === $encoded ? '' : $encoded;
	}

	/**
	 * Verifies the provided credentials.
	 *
	 * @since 0.9.1
	 *
	 * @param string $account_id The account ID to verify.
	 *
	 * @return \stdClass|WP_Error If valid status received, returns object with a few details, otherwise a WP_Error for the status code provided.
	 */
	public function verify( $account_id = '' ) {

		$account_id = (int) $account_id;

		if ( 0 === $account_id ) {
			return new WP_Error(
				'verify-failed',
				__( 'No account ID provided.', 'trustedlogin-connector' )
			);
		}

		$url     = $this->getApiUrl() . 'accounts/' . $account_id;
		$method  = 'POST';
		$body    = array(
			'api_endpoint' => get_rest_url(),
		);
		$headers = $this->getAdditionalHeader();

		if ( ! empty( $this->private_key ) ) {
			$this->stampBearerAuthHeaders( $headers, $url, $body, $method );
		}

		// Route through the same redactor used by ApiHandler::call() even
		// though `/accounts/{id}` takes no sensitive query args today —
		// keeps the "URL in log context" pattern safe against a future
		// refactor that appends one.
		$this->log(
			'Sending verification request',
			__METHOD__,
			'debug',
			array(
				'url'         => Utils::redact_url_for_logging( $url ),
				'method'      => $method,
				'body'        => $body,
				'has_headers' => ! empty( $headers ),
			)
		);

		$verification = $this->apiSend( $url, $body, $method, $headers );

		$this->log( 'Verification results:', __METHOD__ . ':' . __LINE__, 'debug', array( '$verification' => $verification ) );

		if ( is_wp_error( $verification ) ) {
			$this->failure(
				'saas_unreachable',
				FailureLog::SEVERITY_WARNING,
				'Verification API call returned WP_Error',
				__METHOD__,
				array(
					'error_code'    => $verification->get_error_code(),
					// File-log-only; dropped by FailureLog allowlist.
					'error_message' => $verification->get_error_message(),
				)
			);

			return new WP_Error(
				$verification->get_error_code(),
				__( 'We could not verify your TrustedLogin credentials, please try save settings again.', 'trustedlogin-connector' ),
				$verification->get_error_message()
			);
		}

		if ( ! $verification ) {
			$this->failure(
				'saas_unreachable',
				FailureLog::SEVERITY_WARNING,
				'Verification API call returned empty response',
				__METHOD__,
				array( 'error_code' => 'empty_response' )
			);

			return new WP_Error(
				'verify-failed',
				__( 'We could not verify your TrustedLogin credentials, please try save settings again.', 'trustedlogin-connector' )
			);
		}

		$status = wp_remote_retrieve_response_code( $verification );
		$body   = wp_remote_retrieve_body( $verification );

		$this->log(
			'Verification response details',
			__METHOD__,
			'debug',
			array(
				'status_code' => $status,
				'raw_body'    => $body,
			)
		);

		$body = json_decode( $body );

		if ( $status > 399 ) {
			// 402 means subscription expired; everything else in
			// 4xx/5xx is connectivity / config drift on the SaaS
			// side. Distinct event type so the panel can offer a
			// "Renew subscription" CTA on 402 specifically.
			$failure_type = ( 402 === (int) $status )
				? 'saas_subscription_expired'
				: 'saas_verify_failed';

			$this->failure(
				$failure_type,
				FailureLog::SEVERITY_WARNING,
				'Verification failed with HTTP error',
				__METHOD__,
				array(
					'http_code' => (int) $status,
					// File-log-only context.
					'body'      => $body,
				)
			);
			switch ( $status ) {
				case 402:
					return new WP_Error(
						'verify-failed-402',
						__( 'You do not have a valid TrustedLogin subscription.', 'trustedlogin-connector' )
					);
				case 400:
				case 403:
					return new WP_Error(
						'verify-failed-' . $status,
						__( 'Could not verify the team. Please confirm the Public Key and Private Key settings are correct.', 'trustedlogin-connector' )
					);
				case 404:
					return new WP_Error(
						'verify-failed-404',
						__( 'Account not found, please check the ID provided.', 'trustedlogin-connector' )
					);
				case 405:
					return new WP_Error(
						'verify-failed-405',
						sprintf(
						// translators: %1$s is the HTTP method used, %2$s is the URL.
							__( 'Incorrect method (%1$s) used for %2$s', 'trustedlogin-connector' ),
							/* %1$s */ $method,
							/* %2$s */ $url
						)
					);
				case 500:
					return new WP_Error(
						'verify-failed-500',
						// translators: %d is the HTTP status code.
						sprintf( __( 'Status %d returned', 'trustedlogin-connector' ), $status )
					);
				default:
					// $body is stdClass (json_decode without assoc), so
					// reach for `message` via object access. Length-cap
					// the upstream string so a verbose SaaS response
					// can't crowd the admin notice.
					$upstream_msg = is_object( $body ) && isset( $body->message ) && is_scalar( $body->message )
						? mb_substr( (string) $body->message, 0, 200 )
						: (string) $status;
					return new WP_Error(
						'verify-failed-' . $status,
						// translators: %s is the Response message if available otherwise the HTTP status code.
						sprintf( __( "The TrustedLogin Service Responded with:\n%s\nIf the problem continues please contact support.", 'trustedlogin-connector' ), $upstream_msg )
					);
			}
		}

		$this->log( 'Verification response on line ' . __LINE__ . ':', __METHOD__, 'debug', $body );

		if ( ! $body ) {
			$this->log( 'Verification failed - empty response body', __METHOD__, 'error' );

			return new WP_Error(
				'verify-failed',
				__( 'Your TrustedLogin account is not active, please login to activate your account.', 'trustedlogin-connector' )
			);
		}

		if ( isset( $body->status ) && 'active' !== $body->status ) {
			$this->log(
				'Verification failed - account not active',
				__METHOD__,
				'error',
				array(
					'account_status' => $body->status,
				)
			);

			return new WP_Error(
				'verify-failed-inactive',
				__( 'Your TrustedLogin account is not active, please login to activate your account.', 'trustedlogin-connector' )
			);
		}

		if ( isset( $body->error ) && $body->error ) {
			$this->log(
				'Verification failed - error in response body',
				__METHOD__,
				'error',
				array(
					'error'  => $body->error,
					'status' => $status,
				)
			);

			return new WP_Error(
				'verify-failed-other',
				// translators: %d is the HTTP status code.
				sprintf( __( 'Please contact support (Error Status #%d)', 'trustedlogin-connector' ), $status )
			);
		}

		$this->log(
			'Verification successful!',
			__METHOD__,
			'info',
			array(
				'account_id' => $account_id,
				'status'     => $body->status ?? 'unknown',
				'name'       => $body->name ?? 'unknown',
			)
		);

		return $body;
	}

	/**
	 * Handles the response for API calls
	 *
	 * @since 0.4.1
	 *
	 * @param array|false|WP_Error $api_response The result from `$this->apiSend()`.
	 *
	 * @return object|true|WP_Error  Either `json_decode()` of the result's body, or true if status === 204 (successful response, but no sites found) or WP_Error if empty body or error.
	 */
	public function handleResponse( $api_response ) {

		if ( is_wp_error( $api_response ) ) {
			return $api_response; // Logging intentionally left out; already logged in apiSend().
		}

		if ( empty( $api_response ) || ! is_array( $api_response ) ) {
			$this->log(
				'Malformed api_response received:',
				__METHOD__,
				'error',
				array(
					'response' => $api_response,
				)
			);

			return new WP_Error( 'malformed_response', esc_html__( 'Malformed API response received.', 'trustedlogin-connector' ) );
		}

		// first check the HTTP Response code.
		$response_code = wp_remote_retrieve_response_code( $api_response );

		// successful response, but no sites found. does not return any body content, so can bounce out successfully here.
		if ( 204 === $response_code ) {
			return true;
		}

		$body = wp_remote_retrieve_body( $api_response );

		$body = json_decode( $body );

		// Accept both object-shaped and array-shaped JSON bodies. Most
		// SaaS endpoints return objects, but the /logs/logins aggregator
		// returns a top-level array of bucket objects — rejecting arrays
		// wholesale would force every array-returning endpoint to
		// hand-roll its own decode path.
		if ( ! is_object( $body ) && ! is_array( $body ) ) {
			$this->log( 'No body received:', __METHOD__, 'error', array( 'body' => $body ) );

			return new WP_Error( 'empty_body', esc_html__( 'No body received.', 'trustedlogin-connector' ) );
		}

		$body_message = isset( $body->message ) ? $body->message : null;

		switch ( $response_code ) {
			case self::HTTP_LOCKED:
				$this->log(
					'Pause mode is activated on TrustedLogin.com:',
					__METHOD__,
					'error',
					array(
						'response' => $api_response,
					)
				);
				return new WP_Error( 'pause_mode_error', esc_html__( 'Cannot log in: Pause Mode is activated for this account on TrustedLogin.com.', 'trustedlogin-connector' ) );
			case self::HTTP_FAILED_DEPENDENCY:
				$this->log(
					'Error Getting Signature Key from Vendor: ',
					__METHOD__,
					'error',
					array(
						'response' => $api_response,
					)
				);
				return new WP_Error( 'signature_key_error', $body_message );
			case self::HTTP_GONE:
				$this->log(
					'Error Getting Signature Key from Vendor: ',
					__METHOD__,
					'error',
					array(
						'response' => $api_response,
					)
				);
				return new WP_Error( 'gone', 'This support request is gone. Please create a new request. (SecretNotFoundInVaultException)' );
			case 403:
				// Intentional fall-through: 403 surfaces as not_found until a distinct token-error handler lands.
			case 404:
				return new WP_Error( 'not_found', esc_html__( 'Not found.', 'trustedlogin-connector' ) );
			default:
		}

		// The API may return either error or errors, so we need to check both.
		if ( isset( $body->errors ) || isset( $body->error ) ) {
			$errors = isset( $body->errors ) ? $body->errors : $body->error;
			$errors = implode( ', ', (array) $errors );

			$this->log( "Error from API: {$errors}", __METHOD__, 'error' );

			// translators: %s is the error message from the API.
			return new WP_Error( 'api_errors', sprintf( esc_html__( 'Errors returned from API: %s', 'trustedlogin-connector' ), $errors ) );
		}

		return $body;
	}

	/**
	 * API Function: send the API request
	 *
	 * @since 0.4.0
	 *
	 * @param string $url The complete url for the REST API request.
	 * @param mixed  $data Data to send as JSON-encoded request body.
	 * @param string $method HTTP request method (must be 'POST', 'PUT', 'GET', or 'DELETE').
	 * @param array  $additional_headers Any additional headers to send in request (required for auth/etc).
	 *
	 * @return array|false|WP_Error - wp_remote_post response, false if invalid HTTP method, WP_Error if request errors
	 */
	public function apiSend( $url, $data, $method, $additional_headers ) {

		return $this->apiSender->send( $url, $data, $method, $additional_headers );
	}
}

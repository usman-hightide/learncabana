<?php
/**
 * REST endpoint for listing and retrieving login attempts.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\Capabilities;
use TrustedLogin\Vendor\LoginAttempts\AttemptPresenter;
use TrustedLogin\Vendor\LoginAttempts\AttemptResolver;
use TrustedLogin\Vendor\LoginAttempts\Constants;
use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Traits\SlidingWindowRateLimit;
use WP_Error;
use WP_REST_Request;

/**
 * REST endpoint exposed to the Connector's React app:
 *
 *   GET /trustedlogin/v1/login-attempts/{teamId}              — list
 *   GET /trustedlogin/v1/login-attempts/{teamId}/{attemptId}  — single
 */
final class LoginAttempts extends Endpoint {

	use SlidingWindowRateLimit;

	const ROUTE_LIST_REGEX   = 'login-attempts/(?P<team_id>\d+)';
	const ROUTE_SINGLE_REGEX = 'login-attempts/(?P<team_id>\d+)/(?P<attempt_id>[^/]+)';

	/**
	 * Per-admin rate limit. 60 requests per 60s — same shape as
	 * Activity::RATE_LIMIT_* so both proxy surfaces share a budget
	 * for SaaS-side throttle protection.
	 */
	const RATE_LIMIT_REQUESTS_PER_WINDOW = 60;
	const RATE_LIMIT_WINDOW_SECONDS      = 60;
	const RATE_LIMIT_KEY_PREFIX          = 'tl_login_attempts_rl_';

	/**
	 * Register both routes. The base Endpoint::register signature
	 * takes ($editable, $readable) flags but those are for the standard
	 * CRUD pattern Activity bypasses; we do the same.
	 *
	 * @param bool $editable Unused.
	 * @param bool $readable Unused.
	 */
	public function register( $editable = true, $readable = true ) {

		$view_activity = function () {
			return Capabilities::current_user_can( Capabilities::VIEW_ACTIVITY );
		};

		register_rest_route(
			self::NAMESPACE,
			self::ROUTE_LIST_REGEX,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'index' ),
				'permission_callback' => $view_activity,
				'args'                => array(
					'team_id'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			self::ROUTE_SINGLE_REGEX,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'show' ),
				'permission_callback' => $view_activity,
				'args'                => array(
					'team_id'    => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'attempt_id' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * GET /trustedlogin/v1/login-attempts/{teamId}/{attemptId}
	 *
	 * Validates the attempt-id format locally before any HTTP call so a
	 * malformed bookmark never leaves the Connector. The resolver runs
	 * the SaaS round-trip and the presenter shapes the response.
	 *
	 * @param WP_REST_Request $request Required params: team_id, attempt_id.
	 *
	 * @return \WP_REST_Response|WP_Error JSON shape on success; WP_Error
	 *                                    on validation, team-not-found,
	 *                                    or upstream failure.
	 */
	public function show( WP_REST_Request $request ) {
		if ( $this->rate_limit_exceeded() ) {
			return new WP_Error(
				'rate_limited',
				esc_html__( 'Too many requests.', 'trustedlogin-connector' ),
				array( 'status' => Constants::STATUS_TOO_MANY_REQUESTS )
			);
		}

		$attempt_id = (string) $request->get_param( 'attempt_id' );
		if ( ! preg_match( Constants::ATTEMPT_ID_REGEX, $attempt_id ) ) {
			return new WP_Error(
				'invalid_attempt_id',
				esc_html__( 'Malformed attempt id.', 'trustedlogin-connector' ),
				array( 'status' => Constants::STATUS_BAD_REQUEST )
			);
		}

		$team_id  = (int) $request->get_param( 'team_id' );
		$resolver = $this->resolver_for_team( $team_id );
		if ( null === $resolver ) {
			return new WP_Error(
				'team_not_found',
				esc_html__( 'Team not found.', 'trustedlogin-connector' ),
				array( 'status' => Constants::STATUS_NOT_FOUND )
			);
		}

		$result = $resolver->getOne( $attempt_id );
		if ( is_wp_error( $result ) ) {
			$status = 'not_found' === $result->get_error_code()
				? Constants::STATUS_NOT_FOUND
				: Constants::STATUS_BAD_GATEWAY;

			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array( 'status' => $status )
			);
		}

		$presenter = $this->build_presenter();

		return rest_ensure_response( $presenter->present( $result ) );
	}

	/**
	 * GET /trustedlogin/v1/login-attempts/{teamId}
	 *
	 * Returns a paginated list. `per_page` is clamped to the
	 * Connector-side bounds (Constants::MIN_PER_PAGE..MAX_PER_PAGE)
	 * before forwarding to the SaaS — defense-in-depth so a hostile
	 * client can't drag a 999999-row page through if the SaaS
	 * clamp ever breaks. `page` is forwarded as-is.
	 *
	 * @param WP_REST_Request $request Required: team_id. Optional:
	 *                                 per_page, page.
	 *
	 * @return \WP_REST_Response|WP_Error JSON `{ data, meta }` on
	 *                                    success; WP_Error on
	 *                                    team-not-found or upstream
	 *                                    failure.
	 */
	public function index( WP_REST_Request $request ) {
		if ( $this->rate_limit_exceeded() ) {
			return new WP_Error(
				'rate_limited',
				esc_html__( 'Too many requests.', 'trustedlogin-connector' ),
				array( 'status' => Constants::STATUS_TOO_MANY_REQUESTS )
			);
		}

		$team_id  = (int) $request->get_param( 'team_id' );
		$resolver = $this->resolver_for_team( $team_id );
		if ( null === $resolver ) {
			return new WP_Error(
				'team_not_found',
				esc_html__( 'Team not found.', 'trustedlogin-connector' ),
				array( 'status' => Constants::STATUS_NOT_FOUND )
			);
		}

		$args     = array();
		$per_page = $request->get_param( 'per_page' );
		if ( is_numeric( $per_page ) ) {
			$args['per_page'] = max(
				Constants::MIN_PER_PAGE,
				min( Constants::MAX_PER_PAGE, (int) $per_page )
			);
		}
		$page = $request->get_param( 'page' );
		if ( is_numeric( $page ) ) {
			$args['page'] = (int) $page;
		}

		$result = $resolver->getList( $args );
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array( 'status' => Constants::STATUS_BAD_GATEWAY )
			);
		}

		$presenter = $this->build_presenter();
		$data      = array();
		foreach ( $result['data'] as $row ) {
			$data[] = $presenter->present( (array) $row );
		}

		// Whitelist pagination keys so any string field SaaS may add
		// later doesn't bypass the presenter scrub on `data`.
		$meta_in  = is_array( $result['meta'] ?? null ) ? $result['meta'] : array();
		$meta_out = array();
		foreach ( array( 'page', 'per_page', 'total', 'total_pages' ) as $k ) {
			if ( array_key_exists( $k, $meta_in ) ) {
				$meta_out[ $k ] = is_scalar( $meta_in[ $k ] ) ? (int) $meta_in[ $k ] : 0;
			}
		}

		return rest_ensure_response(
			array(
				'data' => $data,
				'meta' => $meta_out,
			)
		);
	}

	/**
	 * Build the AttemptResolver for a given team. Tests can inject a
	 * double via the `trustedlogin/connector/login-attempts/resolver-for-team`
	 * filter.
	 *
	 * @param int $team_id The numeric team account ID.
	 * @return AttemptResolver|null
	 */
	private function resolver_for_team( int $team_id ): ?AttemptResolver {
		// Per-team scoping runs FIRST, before the test-injection
		// filter, so authorization is unbypassable. VIEW_ACTIVITY
		// alone is not enough — the requesting user must also belong
		// to a role listed in this team's approved_roles. Mirrors
		// the gate in Endpoints/Activity::get_logins. Returning null
		// here surfaces as `team_not_found` (404) to the caller,
		// identical to the "team genuinely missing" branch below —
		// no existence oracle.
		if ( ! Activity::user_can_access_team( $team_id ) ) {
			return null;
		}

		$override = apply_filters( 'trustedlogin/connector/login-attempts/resolver-for-team', null, $team_id );
		if ( null !== $override ) {
			return $override;
		}

		$team = SettingsApi::fromSaved()->getByAccountId( (string) $team_id );
		if ( ! $team ) {
			return null;
		}

		// Defensive: trustedlogin_connector() can return null if the
		// plugin singleton failed to bootstrap (e.g., bad config), and
		// getApiHandler() can return false when the team is missing
		// keys. Either case → 404 to the React app, not a fatal.
		$connector = trustedlogin_connector();
		if ( ! $connector ) {
			return null;
		}

		$api = $connector->getApiHandler( $team_id, '', $team );
		if ( ! $api ) {
			return null;
		}

		return new AttemptResolver( $api, $team );
	}

	/**
	 * Build a presenter bound to the agent currently driving the
	 * request. Re-built per request so the heading branches reflect
	 * whether the caller is the agent who issued the original access
	 * key.
	 *
	 * @return AttemptPresenter
	 */
	private function build_presenter(): AttemptPresenter {
		return new AttemptPresenter( get_current_user_id() );
	}

	// ---------------------------------------------------------------
	// Endpoint base-class abstract hooks. This class registers its
	// own routes in register() above, so these stubs only exist to
	// satisfy the parent contract — they're never invoked.
	// ---------------------------------------------------------------

	/**
	 * Satisfies the abstract route() declaration. The actual routes
	 * are registered in self::register().
	 *
	 * @return string
	 */
	protected function route() {
		return self::ROUTE_LIST_REGEX;
	}

	/**
	 * Args for the standard CRUD path; unused because register() wires
	 * its own per-route args.
	 *
	 * @return array
	 */
	protected function getArgs() {
		return array();
	}

	/**
	 * Args for the standard CRUD update path; unused — see getArgs().
	 *
	 * @return array
	 */
	protected function updateArgs() {
		return array();
	}

	/**
	 * Satisfies the abstract get() declaration. Delegates to index()
	 * for parity if anything ever instantiates this class via the
	 * standard CRUD wiring; the registered routes call index()
	 * directly.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function get( $request ) {
		return $this->index( $request );
	}

	/**
	 * Per-user sliding-window rate limit. Mirrors Activity's budget so
	 * SaaS-side throttle protection is consistent across both proxies.
	 *
	 * @return bool True when the caller should be refused with 429.
	 */
	private function rate_limit_exceeded(): bool {
		$user_id = (int) get_current_user_id();
		if ( $user_id <= 0 ) {
			return true;
		}

		return $this->enforce_rate_limit(
			self::RATE_LIMIT_KEY_PREFIX . $user_id,
			self::RATE_LIMIT_REQUESTS_PER_WINDOW,
			self::RATE_LIMIT_WINDOW_SECONDS
		);
	}
}

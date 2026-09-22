<?php
/**
 * REST API endpoints for the Activity page.
 *
 * Proxy-only surface: every row shown to the admin originates from the
 * TrustedLogin SaaS. The Connector holds no local copy of login activity
 * and does not cache responses. See
 * docs/superpowers/specs/2026-04-21-activity-page-saas-rebuild-design.md
 * for the full threat model and rationale.
 *
 * @package TrustedLogin\Vendor\Endpoints
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\Capabilities;
use TrustedLogin\Vendor\SecretAudit;
use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Traits\SlidingWindowRateLimit;

/**
 * Activity endpoint for REST API.
 *
 * @since 2.0.0
 */
class Activity extends Endpoint {

	use Logger;
	use SlidingWindowRateLimit;

	/**
	 * Whitelist of accepted `range` values for the /activity/logins proxy.
	 *
	 * Enforced both by the REST arg `sanitize_callback` and as a
	 * defence-in-depth re-check inside {@see Activity::get_logins()}.
	 */
	const ALLOWED_LOGINS_RANGES = array( 'weekly', 'monthly' );

	/**
	 * Hard ceiling on /activity/secrets `limit`. Defence against a misbehaving
	 * client requesting `limit=999999` and turning the SecretAudit SQL into
	 * an effectively-unbounded scan.
	 *
	 * @since 2.0.0
	 */
	const SECRETS_MAX_LIMIT = 200;

	/**
	 * Default /activity/secrets `limit` when the client doesn't pass one.
	 *
	 * @since 2.0.0
	 */
	const SECRETS_DEFAULT_LIMIT = 50;

	/**
	 * Maximum number of intervals returned by /activity/logins.
	 *
	 * One year of weekly buckets or ~4 years of monthly buckets — more than
	 * enough for any reporting UI, low enough that a misbehaving client
	 * can't DoS the upstream ES query via `length=999999`.
	 */
	const MAX_LENGTH = 52;

	/**
	 * Per-admin rate limit on /activity/logins: 60 requests per 60s.
	 *
	 * Defence in depth on top of the SaaS-side `throttle:60,1` limiter.
	 * Keyed on WP user id so a compromised admin session can't burn
	 * another admin's budget. Short-circuits before the outbound HTTP
	 * call, saving both SaaS resources and local transient churn.
	 *
	 * Both values are currently `60`, but they mean different things — the
	 * REQUESTS_PER_WINDOW is a count and WINDOW_SECONDS is a duration;
	 * naming them apart prevents a future tuning pass from touching one and
	 * forgetting the other.
	 */
	const RATE_LIMIT_REQUESTS_PER_WINDOW = 60;
	const RATE_LIMIT_WINDOW_SECONDS      = 60;

	/**
	 * REST arg `sanitize_callback` that accepts only values present in
	 * {@see Activity::ALLOWED_LOGINS_RANGES}, falling back to `weekly`.
	 *
	 * @param mixed $value Raw param value.
	 *
	 * @return string
	 */
	public static function sanitize_logins_range( $value ) {
		$value = is_scalar( $value ) ? sanitize_key( (string) $value ) : '';
		return in_array( $value, self::ALLOWED_LOGINS_RANGES, true ) ? $value : 'weekly';
	}

	/**
	 * REST arg `sanitize_callback` that clamps /activity/secrets `limit`
	 * into [1, SECRETS_MAX_LIMIT]. Defends against negative or unbounded
	 * values reaching SecretAudit::get_recent()'s SQL LIMIT.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Raw param value.
	 *
	 * @return int Clamped limit.
	 */
	public static function clamp_secrets_limit( $value ) {
		// (int) preserves sign so negatives are treated as bad input and
		// snap to the default; absint() would silently turn -42 into 42
		// and pass through as a "valid" small number.
		$value = is_numeric( $value ) ? (int) $value : 0;
		if ( $value < 1 ) {
			return self::SECRETS_DEFAULT_LIMIT;
		}
		return min( $value, self::SECRETS_MAX_LIMIT );
	}

	/**
	 * Validates a user-supplied start date. Accepts `YYYY-MM-DD` only;
	 * anything else returns an empty string (caller treats that as "use
	 * today"). Strict parsing prevents cross-injection into the SaaS URL
	 * via e.g. `?start=2026-01-01%2F..%2Fpoke`.
	 *
	 * @param mixed $value Raw parameter value to validate.
	 *
	 * @return string Either `YYYY-MM-DD` or ``.
	 */
	public static function sanitize_start_date( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return '';
		}
		$dt = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		if ( false === $dt ) {
			return '';
		}
		// Round-trip: reject e.g. 2026-02-31 that PHP's lax mode might
		// silently normalize.
		if ( $dt->format( 'Y-m-d' ) !== $value ) {
			return '';
		}
		return $value;
	}

	/**
	 * Returns the list of team account_ids the current user is permitted to
	 * access via the Activity endpoints.
	 *
	 * Scoping rules:
	 *   - `manage_options` holders see every configured team (admin-level
	 *     users need full visibility for team config + diagnostics).
	 *   - Every other VIEW_ACTIVITY holder sees only teams where at
	 *     least one of their WP roles appears in the team's
	 *     `approved_roles`. This matches the per-team membership model
	 *     used by AccessKeyLogin (login gate) — a support agent's
	 *     activity visibility tracks the teams they can actually
	 *     support, no more.
	 *
	 * Surfaces a filter so integrators (Teams plugin, MU-pool
	 * segregation, etc.) can install a narrower policy on top.
	 *
	 * @return array<int> List of permitted account_ids as integers.
	 */
	public static function get_permitted_account_ids() {
		$account_ids = array();

		try {
			foreach ( SettingsApi::fromSaved()->allTeams() as $team ) {
				if ( self::current_user_is_approved_for_team( $team ) ) {
					$account_ids[] = (int) $team->get( 'account_id' );
				}
			}
		} catch ( \Exception $e ) {
			$account_ids = array();
		}

		$user_id = get_current_user_id();

		/**
		 * Filter the list of team account_ids the current user is allowed to
		 * access via /wp-json/trustedlogin/v1/activity/* endpoints.
		 *
		 * The filter may only NARROW the set — return values are
		 * intersected with the unfiltered list, so a hook that returns
		 * extra IDs can't broaden access.
		 *
		 * @since 2.0.0
		 *
		 * @param int[] $account_ids   Default: all configured team account_ids.
		 * @param int   $user_id       Current user ID (0 if unauthenticated).
		 */
		$filtered = apply_filters( 'trustedlogin/connector/permitted_team_account_ids', $account_ids, $user_id );

		// Keep the legacy `trustedlogin/vendor/*` hook firing with a
		// deprecation notice so integrators reading the new 1.4 docs
		// ("every hook on trustedlogin/connector/*") stop at the right
		// callback, while sites still relying on the old name continue
		// working until they migrate.
		$filtered = apply_filters_deprecated(
			'trustedlogin/vendor/permitted_team_account_ids',
			array( $filtered, $user_id ),
			'1.4.1',
			'trustedlogin/connector/permitted_team_account_ids'
		);

		$filtered = array_map( 'intval', (array) $filtered );

		// Intersect against the unfiltered set so the filter can only
		// remove entries, never add new ones. Authorization is computed
		// server-side; hooks are an opt-in narrowing channel.
		$filtered = array_intersect( $filtered, $account_ids );

		return array_values( array_unique( $filtered ) );
	}

	/**
	 * Whether the current user's WP roles overlap with a team's
	 * `approved_roles` list, OR they hold `manage_options` (site admin
	 * escape hatch).
	 *
	 * Used by {@see self::get_permitted_account_ids()} to scope the
	 * Activity team list per-user. Extracted so the Connector-side
	 * React bootstrap can reuse the same decision when trimming the
	 * team dropdown options.
	 *
	 * @since 2.0.0
	 *
	 * @param \TrustedLogin\Vendor\TeamSettings $team Team settings to check against.
	 *
	 * @return bool
	 */
	public static function current_user_is_approved_for_team( $team ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$approved = $team->get( 'approved_roles' );
		if ( ! is_array( $approved ) || empty( $approved ) ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( ! $user || 0 === (int) $user->ID ) {
			return false;
		}

		return (bool) array_intersect( (array) $user->roles, $approved );
	}

	/**
	 * Checks whether the current user is permitted to act on a given team.
	 *
	 * This is a WP-admin UX gate, not a security boundary — the SaaS
	 * repeats every authorization decision that matters. See threat model
	 * in the design doc.
	 *
	 * @param string|int $team_account_id Target team.
	 *
	 * @return bool
	 */
	public static function user_can_access_team( $team_account_id ) {
		$team_account_id = (int) $team_account_id;
		if ( 0 === $team_account_id ) {
			return false;
		}
		return in_array( $team_account_id, self::get_permitted_account_ids(), true );
	}

	/**
	 * Get the activity route.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'activity';
	}

	/**
	 * Register all activity-related REST routes.
	 *
	 * @param bool $editable Unused.
	 * @param bool $readable Unused.
	 */
	public function register( $editable = true, $readable = true ) {

		$view_activity = function () {
			return Capabilities::current_user_can( Capabilities::VIEW_ACTIVITY );
		};

		// GET /activity/secrets — recent secret audit events for the current
		// admin only. No `user_id` arg — passing one used to let any user
		// with VIEW_ACTIVITY enumerate every admin's audit rows.
		register_rest_route(
			self::NAMESPACE,
			'activity/secrets',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_secrets' ),
				'permission_callback' => $view_activity,
				'args'                => array(
					'limit'  => array(
						'type'              => 'integer',
						'default'           => self::SECRETS_DEFAULT_LIMIT,
						'sanitize_callback' => array( __CLASS__, 'clamp_secrets_limit' ),
					),
					'offset' => array(
						'type'              => 'integer',
						'default'           => 0,
						// Clamp to 0 — `absint(-7)` would silently surface 7,
						// which masks a programming error in the client.
						'sanitize_callback' => static function ( $value ) {
							return max( 0, is_numeric( $value ) ? (int) $value : 0 );
						},
					),
				),
			)
		);

		// GET /activity/logins — login data from the SaaS (proxied).
		register_rest_route(
			self::NAMESPACE,
			'activity/logins',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_logins' ),
				'permission_callback' => $view_activity,
				'args'                => array(
					'team_account_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'range'           => array(
						'type'              => 'string',
						'default'           => 'weekly',
						'enum'              => self::ALLOWED_LOGINS_RANGES,
						// validate BEFORE sanitize so an unknown value is
						// rejected with 400, not silently coerced to the
						// default. Old behaviour made typos invisible —
						// a caller passing `range=hourly` would get back
						// weekly data and never know.
						'validate_callback' => 'rest_validate_request_arg',
						'sanitize_callback' => array( __CLASS__, 'sanitize_logins_range' ),
					),
					'length'          => array(
						'type'              => 'integer',
						'default'           => 12,
						'sanitize_callback' => 'absint',
					),
					'start'           => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => array( __CLASS__, 'sanitize_start_date' ),
					),
					'site_id'         => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'user_id'         => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'with_data'       => array(
						'type'    => 'integer',
						'default' => 1,
						'enum'    => array( 0, 1 ),
					),
				),
			)
		);

		// GET /activity/failures — recent vendor-side failure events
		// from the FailureLog ring buffer (connector-local; no SaaS
		// round-trip). Capped at MAX_ENTRIES (50) by FailureLog.
		//
		// Gated on manage_options (NOT VIEW_ACTIVITY) because the ring
		// buffer is connector-global with no per-team partitioning —
		// surfacing it to a per-team VIEW_ACTIVITY holder would leak
		// failure context from every other team configured on the site.
		register_rest_route(
			self::NAMESPACE,
			'activity/failures',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_failures' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'limit'        => array(
						'type'              => 'integer',
						'default'           => 25,
						'sanitize_callback' => 'absint',
					),
					'min_severity' => array(
						'type'    => 'string',
						'default' => '',
						'enum'    => array( '', \TrustedLogin\Vendor\FailureLog::SEVERITY_NOTICE, \TrustedLogin\Vendor\FailureLog::SEVERITY_WARNING, \TrustedLogin\Vendor\FailureLog::SEVERITY_ERROR ),
					),
				),
			)
		);
	}

	/**
	 * GET /activity/failures — recent FailureLog rows.
	 *
	 * Returns the connector-local ring buffer of failure events.
	 * Severity floor is applied server-side (the ring already filters
	 * on read), so the client only sees what it should render.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_failures( \WP_REST_Request $request ) {
		$limit        = (int) $request->get_param( 'limit' );
		$min_severity = (string) $request->get_param( 'min_severity' );

		$rows = \TrustedLogin\Vendor\FailureLog::recent( $limit, '' !== $min_severity ? $min_severity : null );

		return new \WP_REST_Response(
			array(
				'data' => $rows,
				'meta' => array(
					'count'    => count( $rows ),
					'capacity' => \TrustedLogin\Vendor\FailureLog::MAX_ENTRIES,
				),
			),
			200
		);
	}

	/**
	 * Get activity endpoints.
	 *
	 * @inheritdoc
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function get( \WP_REST_Request $request ) {
		return new \WP_REST_Response(
			array( 'endpoints' => array( 'secrets', 'logins', 'failures' ) ),
			200
		);
	}

	/**
	 * Returns recent secret audit events, scoped to the current admin.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_secrets( \WP_REST_Request $request ) {

		$secrets = SecretAudit::get_recent(
			get_current_user_id(),
			(int) $request->get_param( 'limit' ),
			(int) $request->get_param( 'offset' )
		);

		// wpdb->get_results returns stdClass[] by default and null on
		// a DB failure — normalise to an iterable array of objects so
		// the foreach below can't trip on null and the lockout flag
		// always lands on the response shape.
		if ( ! is_array( $secrets ) ) {
			$secrets = array();
		}

		// Surface lockout state as an explicit boolean. The underlying
		// signal is the `memo='permanently_locked'` value the
		// passphrase-verify endpoint writes on a final failed attempt,
		// but the client should not couple to that literal string.
		foreach ( $secrets as $row ) {
			if ( ! is_object( $row ) ) {
				continue;
			}
			$event          = isset( $row->event ) ? (string) $row->event : '';
			$memo           = isset( $row->memo ) ? (string) $row->memo : '';
			$row->is_locked = ( 'passphrase_failed' === $event && 'permanently_locked' === $memo );
		}

		return new \WP_REST_Response( $secrets, 200 );
	}

	/**
	 * Proxies login data from the TrustedLogin SaaS.
	 *
	 * Connector is a stateless read-through proxy. Every authorization
	 * decision that matters is re-enforced on the SaaS side (the
	 * `CheckApiKey` middleware rewrites the `teamId` path parameter from
	 * the api_key's bound team, so even if this method's `user_can_access_team`
	 * gate is bypassed, the SaaS cannot be coerced into leaking another
	 * team's data).
	 *
	 * Response-body passthrough is **shape-validated** but not re-serialized;
	 * the SaaS response is the contract. Errors from the upstream surface as
	 * structured logs locally — the client sees `{error: <code>}` only, never
	 * raw upstream text.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_logins( \WP_REST_Request $request ) {

		$account_id = absint( $request->get_param( 'team_account_id' ) );

		if ( 0 === $account_id ) {
			return $this->error_response( 'invalid_param', 400, array( 'param' => 'team_account_id' ) );
		}

		if ( ! self::user_can_access_team( $account_id ) ) {
			return $this->error_response( 'forbidden_team', 403 );
		}

		// Rate limit BEFORE the outbound SaaS call. Transient-backed
		// fixed-window counter — cheap, good-enough granularity, and
		// survives object-cache eviction via Utils::*_transient.
		if ( $this->rate_limit_exceeded() ) {
			return $this->error_response( 'rate_limited', 429 );
		}

		try {
			$team = SettingsApi::fromSaved()->getByAccountId( $account_id );
		} catch ( \Exception $e ) {
			return $this->error_response( 'team_not_found', 404 );
		}

		// Re-apply allowlist/clamp here as defence-in-depth. Even if the
		// REST sanitize_callback is stripped or bypassed by a future
		// refactor, these values never reach the outbound URL verbatim.
		$range     = self::sanitize_logins_range( $request->get_param( 'range' ) );
		$length    = max( 1, min( (int) $request->get_param( 'length' ), self::MAX_LENGTH ) );
		$start     = self::sanitize_start_date( $request->get_param( 'start' ) );
		$site_id   = absint( $request->get_param( 'site_id' ) );
		$user_id   = absint( $request->get_param( 'user_id' ) );
		$with_data = (int) $request->get_param( 'with_data' ) ? 1 : 0;

		$query_args = array(
			'range'  => $range,
			'length' => $length,
			'data'   => $with_data,
		);

		if ( '' !== $start ) {
			$query_args['start'] = $start;
		}
		if ( $user_id > 0 ) {
			$query_args['user_id'] = $user_id;
		}

		$path_segments = array( 'logs', 'logins', $account_id );
		if ( $site_id > 0 ) {
			$path_segments[] = 'site';
			$path_segments[] = $site_id;
		}

		$endpoint = \TrustedLogin\Vendor\ApiHandler::buildEndpoint( $path_segments, $query_args );

		// SaaS `/logs/logins/*` authenticates via the team api_key
		// (CheckApiKey middleware). Carry it in the X-TL-Api-Key header
		// instead of the legacy `?api_key=` query arg so the credential
		// never lands in access logs or URL-redacting clients. Pair with
		// X-TL-Auth-Version so the SaaS refuses a silent downgrade.
		$api     = \trustedlogin_connector()->getApiHandler( $account_id, '', $team );
		$api_key = $team->get( 'private_key' );
		if ( ! empty( $api_key ) ) {
			$api->setAdditionalHeader( \TrustedLogin\Vendor\ApiHandler::HEADER_API_KEY, $api_key );
			$api->setAdditionalHeader( \TrustedLogin\Vendor\ApiHandler::HEADER_AUTH_VERSION, \TrustedLogin\Vendor\ApiHandler::AUTH_VERSION_V2 );
		}
		$result = $api->call( $endpoint, array(), 'GET' );

		if ( is_wp_error( $result ) ) {
			$this->log(
				sprintf(
					'Activity /logins upstream error for team %d (code=%s)',
					$account_id,
					$result->get_error_code()
				),
				__METHOD__,
				'error'
			);
			return $this->error_response( 'saas_error', 502 );
		}

		// ApiHandler returns `json_decode($body)` (no assoc flag), so a
		// top-level-array body comes back as an array of stdClass objects.
		// Normalize to a nested associative array for the validator. The
		// round-trip through wp_json_encode is safer than hand-crafted
		// casts (a deeply-nested mix of arrays and objects is common) and
		// also guards against non-UTF-8 bytes by refusing to proceed.
		if ( ! is_array( $result ) && ! is_object( $result ) ) {
			$this->log(
				sprintf( 'Activity /logins malformed upstream for team %d', $account_id ),
				__METHOD__,
				'error'
			);
			return $this->error_response( 'saas_error', 502 );
		}

		$encoded = wp_json_encode( $result );
		if ( false === $encoded ) {
			$this->log(
				sprintf( 'Activity /logins non-UTF-8 upstream body for team %d', $account_id ),
				__METHOD__,
				'error'
			);
			return $this->error_response( 'saas_error', 502 );
		}

		$as_array = json_decode( $encoded, true );
		if ( ! is_array( $as_array ) ) {
			return $this->error_response( 'saas_error', 502 );
		}

		$validated = $this->validate_logins_response( $as_array, $account_id );

		$response = new \WP_REST_Response(
			array( 'ranges' => $validated ),
			200
		);
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * Validates and normalizes the SaaS `/logs/logins` response.
	 *
	 * Passes through only the documented fields. Drops unknown keys so a
	 * future SaaS-side addition can't leak data through the proxy
	 * unintentionally.
	 *
	 * @param array $raw              Decoded SaaS response.
	 * @param int   $expected_team_id The team the caller is authorized for.
	 *                                Rows whose `teamId` doesn't match are
	 *                                dropped. The SaaS today uses per-team
	 *                                ES indexes so a mismatched row should
	 *                                be impossible — this filter hard-codes
	 *                                the invariant in the proxy so a SaaS
	 *                                regression can't silently surface
	 *                                cross-team rows in the admin UI.
	 *
	 * @return array<int, array> Validated bucket objects.
	 */
	private function validate_logins_response( array $raw, $expected_team_id ) {
		$expected_team_id = (int) $expected_team_id;
		$buckets          = array();

		foreach ( $raw as $bucket ) {
			if ( ! is_array( $bucket ) ) {
				continue;
			}

			$normalized = array(
				'count' => isset( $bucket['count'] ) ? (int) $bucket['count'] : 0,
				'start' => isset( $bucket['start'] ) && is_scalar( $bucket['start'] )
					? sanitize_text_field( (string) $bucket['start'] )
					: '',
				'end'   => isset( $bucket['end'] ) && is_scalar( $bucket['end'] )
					? sanitize_text_field( (string) $bucket['end'] )
					: '',
			);

			if ( isset( $bucket['data'] ) && is_array( $bucket['data'] ) ) {
				$rows = array();
				foreach ( $bucket['data'] as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					// Defence-in-depth: if a row's teamId doesn't match
					// the caller's authorized team, drop it. See the
					// parameter docblock for the threat model.
					if ( isset( $row['teamId'] ) && (int) $row['teamId'] !== $expected_team_id ) {
						continue;
					}
					$rows[] = array(
						'time'      => isset( $row['time'] ) && is_scalar( $row['time'] )
							? sanitize_text_field( (string) $row['time'] )
							: '',
						'userId'    => isset( $row['userId'] ) ? (int) $row['userId'] : 0,
						'userName'  => isset( $row['userName'] ) && is_scalar( $row['userName'] )
							? sanitize_text_field( (string) $row['userName'] )
							: '',
						'siteId'    => isset( $row['siteId'] ) ? (int) $row['siteId'] : 0,
						// Restrict to http/https explicitly — esc_url_raw's
						// default allowlist includes tel/sms/xmpp/webcal,
						// none of which are meaningful for a customer
						// website and any of which would surface as
						// clickable links in the admin UI.
						'siteUrl'   => isset( $row['siteUrl'] ) && is_scalar( $row['siteUrl'] )
							? esc_url_raw( (string) $row['siteUrl'], array( 'http', 'https' ) )
							: '',
						'teamId'    => isset( $row['teamId'] ) ? (int) $row['teamId'] : 0,
						'eventType' => isset( $row['eventType'] ) && is_scalar( $row['eventType'] )
							? sanitize_key( (string) $row['eventType'] )
							: '',
					);
				}
				$normalized['data'] = $rows;
			}

			$buckets[] = $normalized;
		}

		return $buckets;
	}

	/**
	 * Per-user sliding-window rate limit for /activity/logins.
	 *
	 * Keyed on WP user id rather than IP because a WP-admin session is
	 * authenticated — IP can be shared (NAT / reverse-proxy) and would
	 * unfairly block co-admins on the same egress.
	 *
	 * @return bool True if the caller should be rate-limited.
	 */
	private function rate_limit_exceeded() {
		$user_id = (int) get_current_user_id();
		if ( $user_id <= 0 ) {
			return true;
		}

		return $this->enforce_rate_limit(
			'tl_activity_logins_rl_' . $user_id,
			self::RATE_LIMIT_REQUESTS_PER_WINDOW,
			self::RATE_LIMIT_WINDOW_SECONDS
		);
	}

	/**
	 * Builds a sanitized error response envelope. Keeps the error codes a
	 * small fixed set so the client can branch on them without parsing
	 * free-text messages.
	 *
	 * @param string $code   One of: invalid_param, forbidden_team, forbidden_cap,
	 *                       team_not_found, saas_error, saas_timeout.
	 * @param int    $status HTTP status code.
	 * @param array  $extra  Optional additional context (safe to expose).
	 *
	 * @return \WP_REST_Response
	 */
	private function error_response( $code, $status, array $extra = array() ) {
		$body = array_merge( array( 'error' => $code ), $extra );
		return new \WP_REST_Response( $body, (int) $status );
	}
}

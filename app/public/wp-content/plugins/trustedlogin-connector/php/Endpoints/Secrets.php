<?php
/**
 * REST API endpoints for one-time secret sharing.
 *
 * @package TrustedLogin\Vendor\Endpoints
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\Capabilities;
use TrustedLogin\Vendor\SecretAudit;
use TrustedLogin\Vendor\SecretManager;
use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Traits\SlidingWindowRateLimit;
use TrustedLogin\Vendor\Utils;

/**
 * Registers and handles REST routes for creating, fetching, burning,
 * and passphrase-verifying one-time secrets.
 *
 * @since 2.0.0
 */
class Secrets extends Endpoint {

	use Logger;
	use SlidingWindowRateLimit;

	/**
	 * Hard ceiling on the `plaintext` body of a /secrets create call,
	 * in bytes. 64 KiB is the working budget — any credential a
	 * support agent might share fits well under this, and a multi-MB
	 * body never reaches the encrypt + transient-write path.
	 *
	 * @since 2.0.0
	 */
	const MAX_PLAINTEXT_LENGTH = 65536;

	/**
	 * Per-IP cap on passphrase verify attempts before 429.
	 */
	const VERIFY_RATE_MAX_HITS = 10;

	/**
	 * Verify rate-limit window in seconds.
	 */
	const VERIFY_RATE_WINDOW = 300;

	/**
	 * Option-prefix for the per-IP rate-limit transient keys.
	 *
	 * Stored via Utils::set_transient so the counter survives object-cache
	 * eviction. Format: `tl_rl_<action>_<ip-hash>`.
	 */
	const RATE_LIMIT_KEY_PREFIX = 'tl_rl_';

	/**
	 * Register all secret-related REST routes.
	 *
	 * Overrides the parent to register four routes with different auth requirements:
	 * - POST   /secrets                         (admin only)
	 * - GET    /secrets/(?P<token>[a-f0-9]{32})  (public)
	 * - DELETE /secrets/(?P<token>[a-f0-9]{32})  (public)
	 * - POST   /secrets/(?P<token>[a-f0-9]{32})/verify (public)
	 *
	 * @since 2.0.0
	 *
	 * @param bool $editable Unused. Kept for signature compatibility.
	 * @param bool $readable Unused. Kept for signature compatibility.
	 *
	 * @return void
	 */
	public function register( $editable = true, $readable = true ) {

		$manage_secrets = static function () {
			return Capabilities::current_user_can( Capabilities::MANAGE_SECRETS );
		};
		$create_secret  = static function () {
			return Capabilities::current_user_can( Capabilities::CREATE_SECRET );
		};

		// List recent secrets.
		register_rest_route(
			self::NAMESPACE,
			'secrets',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get' ),
				'permission_callback' => $manage_secrets,
			)
		);

		// Full audit history for a single secret (admin-only, scoped to creator).
		// Uses token_hash rather than the raw token because admins never
		// possess the raw token — only the recipient does.
		register_rest_route(
			self::NAMESPACE,
			'secrets/hash/(?P<token_hash>[a-f0-9]{64})/history',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_history' ),
				'permission_callback' => $manage_secrets,
				'args'                => array(
					'token_hash' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $value ) {
							return is_string( $value ) && 1 === preg_match( '/^[a-f0-9]{64}$/', $value );
						},
					),
				),
			)
		);

		// Create secret.
		register_rest_route(
			self::NAMESPACE,
			'secrets',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create' ),
				'permission_callback' => $create_secret,
				'args'                => array(
					'plaintext'          => array(
						'required'  => true,
						'type'      => 'string',
						// 64 KiB ceiling — generous for any credential
						// share an agent could need, narrow enough that
						// a multi-MB body can't inflate wp_options.
						'maxLength' => self::MAX_PLAINTEXT_LENGTH,
						// No sanitize_callback: the plaintext is immediately encrypted
						// and never rendered as HTML on the server. Sanitization would
						// silently strip HTML/XML that the agent may be sharing.
					),
					'ttl'                => array(
						'type'    => 'integer',
						'default' => 86400,
					),
					'passphrase'         => array(
						'type'      => 'string',
						'default'   => '',
						'maxLength' => SecretManager::MAX_PASSPHRASE_LENGTH,
					),
					'memo'               => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'team_account_id'    => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'burn_after_reading' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'viewer_can_destroy' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Whether recipients of a reusable link see a "Destroy this secret" button on the reveal page. Ignored when burn_after_reading is true (single-use links self-destroy regardless).', 'trustedlogin-connector' ),
					),
				),
			)
		);

		// Fetch ciphertext (public — the recipient is unauthenticated).
		register_rest_route(
			self::NAMESPACE,
			'secrets/(?P<token>[a-f0-9]{' . SecretManager::TOKEN_LENGTH . '})',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'fetch' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token' => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => array( $this, 'validate_token' ),
					),
					'nonce' => array(
						'type' => 'string',
					),
				),
			)
		);

		// Burn / reveal (public).
		register_rest_route(
			self::NAMESPACE,
			'secrets/(?P<token>[a-f0-9]{' . SecretManager::TOKEN_LENGTH . '})',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'burn' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token' => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => array( $this, 'validate_token' ),
					),
				),
			)
		);

		// Passphrase verification (public).
		register_rest_route(
			self::NAMESPACE,
			'secrets/(?P<token>[a-f0-9]{' . SecretManager::TOKEN_LENGTH . '})/verify',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'verify_passphrase' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token'      => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => array( $this, 'validate_token' ),
					),
					'passphrase' => array(
						'required'  => true,
						'type'      => 'string',
						'maxLength' => SecretManager::MAX_PASSPHRASE_LENGTH,
					),
				),
			)
		);

		// Prepare: generates a single-use reveal nonce. Called by JS on button
		// click — not embedded in the HTML, so crawlers can't extract it.
		// Requires the recipient to prove they hold the URL-fragment key
		// (by sending its sha256) — token-only leaks can't stamp a nonce.
		register_rest_route(
			self::NAMESPACE,
			'secrets/(?P<token>[a-f0-9]{' . SecretManager::TOKEN_LENGTH . '})/prepare',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'prepare' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token'    => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => array( $this, 'validate_token' ),
					),
					'key_hash' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Get the route URI.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected function route() {
		return 'secrets';
	}

	/**
	 * Returns a list of the current admin's recent secrets.
	 *
	 * Inherits admin authorization from the parent class.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get( \WP_REST_Request $request ) {

		$user_id = get_current_user_id();
		$recent  = SecretAudit::get_recent( $user_id );

		// SecretAudit::get_recent is scoped to tokens created by $user_id, so
		// the creator for every returned row is the current user. Populate
		// display name here so the React list can render "Created By" without
		// a second roundtrip.
		$user    = $user_id ? get_userdata( $user_id ) : null;
		$creator = $user ? $user->display_name : '';
		foreach ( (array) $recent as $row ) {
			$row->created_by_id      = $user_id;
			$row->created_by         = $creator;
			$row->burn_after_reading = null === $row->burn_after_reading ? null : (bool) $row->burn_after_reading;
			$row->ttl_seconds        = null === $row->ttl_seconds ? null : (int) $row->ttl_seconds;
			// Per-token view counts. SQL emits these as strings on some
			// MySQL builds (the SUM-of-boolean idiom), so cast eagerly.
			// viewed     — `previewed` audit rows (page loads that fetched ciphertext).
			// succeeded  — `passphrase_succeeded` audit rows (correct passphrase entries; only fires for passphrase-protected secrets).
			// failed     — `passphrase_failed` audit rows.
			$row->viewed_count    = isset( $row->viewed_count ) ? (int) $row->viewed_count : 0;
			$row->succeeded_count = isset( $row->succeeded_count ) ? (int) $row->succeeded_count : 0;
			$row->failed_count    = isset( $row->failed_count ) ? (int) $row->failed_count : 0;
			// Compute expires_at so React doesn't need to know about timezones
			// or datetime formats. gmdate+strtotime roundtrips the UTC stamp.
			if ( ! empty( $row->created_at ) && $row->ttl_seconds ) {
				$created_ts      = strtotime( $row->created_at . ' UTC' );
				$row->expires_at = $created_ts ? gmdate( 'Y-m-d H:i:s', $created_ts + $row->ttl_seconds ) : null;
			} else {
				$row->expires_at = null;
			}
		}

		return new \WP_REST_Response( $recent, 200 );
	}

	/**
	 * Returns the full audit history for a single secret.
	 *
	 * Admin is only allowed to see history for tokens they created —
	 * scope is enforced by checking the `created` row's actor_user_id
	 * against the current user. Each returned row is scrubbed of the
	 * user agent (stored for forensics but not useful in the UI) and
	 * enriched with a display_name so the React detail panel can
	 * render "Created by Jane Doe" / "Viewed from 1.2.3.4" inline.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_history( \WP_REST_Request $request ) {

		$token_hash = (string) $request->get_param( 'token_hash' );
		$history    = SecretAudit::get_history( $token_hash, 100 );

		if ( empty( $history ) ) {
			return new \WP_REST_Response( array(), 200 );
		}

		$creator_id = null;
		foreach ( $history as $row ) {
			if ( 'created' === $row->event ) {
				$creator_id = (int) $row->actor_user_id;
				break;
			}
		}

		if ( (int) get_current_user_id() !== $creator_id ) {
			// Don't leak existence of another admin's secret.
			return new \WP_REST_Response( array(), 200 );
		}

		// actor_ip on previewed / revealed / passphrase_* events is the
		// recipient's IP, not the creator's. Only managers of secrets
		// across the site should see those.
		$show_actor_ip = current_user_can( Capabilities::MANAGE_SECRETS );

		$display_cache = array();
		$output        = array();
		foreach ( $history as $row ) {
			$actor_id = (int) $row->actor_user_id;
			if ( $actor_id && ! isset( $display_cache[ $actor_id ] ) ) {
				$user                       = get_userdata( $actor_id );
				$display_cache[ $actor_id ] = $user ? $user->display_name : '';
			}
			$entry = array(
				'event'      => $row->event,
				'event_at'   => $row->event_at,
				'actor_id'   => $actor_id ? $actor_id : null,
				'actor_name' => $actor_id ? ( $display_cache[ $actor_id ] ?? '' ) : '',
				'memo'       => isset( $row->memo ) ? $row->memo : '',
			);
			if ( $show_actor_ip ) {
				$entry['actor_ip'] = $row->actor_ip;
			}
			$output[] = $entry;
		}

		return new \WP_REST_Response( $output, 200 );
	}

	/**
	 * Creates a new one-time secret and returns the share URL.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create( \WP_REST_Request $request ) {

		// Defense-in-depth size cap. The schema maxLength catches
		// well-formed clients; this guards against schema bypass and
		// keeps a multi-MB body from reaching the encrypt + transient
		// write.
		$plaintext = (string) $request->get_param( 'plaintext' );
		if ( strlen( $plaintext ) > self::MAX_PLAINTEXT_LENGTH ) {
			return new \WP_REST_Response(
				array(
					'error'   => 'plaintext_too_large',
					'message' => sprintf(
						/* translators: %d is the byte ceiling. */
						__( 'Secret plaintext exceeds the %d-byte limit.', 'trustedlogin-connector' ),
						self::MAX_PLAINTEXT_LENGTH
					),
				),
				413
			);
		}

		// Team-bound secrets require VIEW_ACTIVITY on the bound team
		// (CREATE_SECRET alone is site-wide; the team_account_id
		// binding selects the audit + visibility scope).
		$team_account_id = (int) $request->get_param( 'team_account_id' );
		if ( $team_account_id > 0 && ! Activity::user_can_access_team( $team_account_id ) ) {
			return new \WP_REST_Response(
				array(
					'error'   => 'forbidden_team',
					'message' => __( 'You cannot create a secret bound to that team.', 'trustedlogin-connector' ),
				),
				403
			);
		}

		$manager = new SecretManager();
		$result  = $manager->create( $request->get_params() );

		if ( null === $result ) {
			return new \WP_REST_Response(
				array(
					'error'   => 'creation_failed',
					'message' => 'Unable to create the secret.',
				),
				500
			);
		}

		SecretAudit::log(
			'created',
			$result['token'],
			array(
				'team_account_id'    => $request->get_param( 'team_account_id' ),
				'memo'               => $request->get_param( 'memo' ),
				'ttl_seconds'        => (int) $request->get_param( 'ttl' ),
				'burn_after_reading' => (bool) $request->get_param( 'burn_after_reading' ),
				'viewer_can_destroy' => (bool) $request->get_param( 'viewer_can_destroy' ),
			)
		);

		$key_bin = hex2bin( $result['key'] );

		if ( false === $key_bin ) {
			return new \WP_REST_Response(
				array(
					'error'   => 'creation_failed',
					'message' => 'Invalid key generated.',
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'url'        => $this->build_secret_url( $result['token'], $key_bin ),
				'token'      => $result['token'],
				'expires_at' => $result['expires_at'],
			),
			201
		);
	}

	/**
	 * Builds the recipient-facing URL: `trustedlogin/s/<token>#<key>`.
	 *
	 * The key rides in the fragment so it never reaches the server logs.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token   Hex token identifying the stored record.
	 * @param string $key_bin Raw bytes of the symmetric key.
	 *
	 * @return string The recipient URL with the key in the fragment.
	 */
	private function build_secret_url( string $token, string $key_bin ): string {
		return home_url( 'trustedlogin/s/' . $token ) . '#' . $this->base64url_encode( $key_bin );
	}

	/**
	 * Generates a single-use reveal nonce for a secret.
	 *
	 * Called by the JS on button click — NOT embedded in the page HTML so
	 * link-preview crawlers can't pre-fetch it.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare( \WP_REST_Request $request ) {

		$token    = $request->get_param( 'token' );
		$key_hash = (string) $request->get_param( 'key_hash' );
		$manager  = new SecretManager();
		$record   = $manager->get_record( $token );

		if ( null === $record || ! empty( $record['in_flight'] ) || null !== $record['revealed_at'] ) {
			return $this->not_available();
		}

		// Fragment-key proof: only clients that hold the URL fragment can
		// derive the expected sha256. Token-only observers (access logs,
		// proxy logs) fail this check and cannot destroy the secret.
		if ( empty( $record['key_hash'] ) || ! preg_match( '/^[a-f0-9]{64}$/', $key_hash )
			|| ! hash_equals( $record['key_hash'], $key_hash ) ) {
			return $this->not_available();
		}

		// Rate limit: same bucket as fetch.
		if ( $this->is_rate_limited( 'fetch' ) ) {
			return new \WP_REST_Response( array( 'error' => 'rate_limited' ), 429 );
		}

		// Idempotency: if a reveal nonce was already issued for this
		// record, return it rather than minting a new one. Concurrent
		// /prepare calls otherwise race on write_record() and the
		// loser's nonce becomes unfetchable.
		//
		// Caveat: two requests landing here within the same millisecond
		// can both pass this guard (record was fetched before either
		// wrote) and both mint a fresh nonce; the second write wins and
		// the first nonce silently becomes orphaned. The secret data
		// itself is unaffected — only the loser's nonce is wasted, and
		// the recipient retries with a fresh /prepare anyway. We do
		// not gate this with a DB transaction because the cost (per-
		// option-row locking) outweighs the impact (a wasted bin2hex).
		if ( ! empty( $record['reveal_nonce'] ) && is_string( $record['reveal_nonce'] ) ) {
			return new \WP_REST_Response( array( 'nonce' => $record['reveal_nonce'] ), 200 );
		}

		// Generate and store the reveal nonce.
		$nonce                  = bin2hex( random_bytes( SecretManager::NONCE_BYTES ) );
		$record['reveal_nonce'] = $nonce;
		$manager->write_record( $token, $record );

		return new \WP_REST_Response( array( 'nonce' => $nonce ), 200 );
	}

	/**
	 * Fetches and locks ciphertext for reveal (first-GET-wins).
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function fetch( \WP_REST_Request $request ) {

		$token = $request->get_param( 'token' );

		// Reveal-nonce check runs BEFORE the IP rate-limit bucket so
		// only callers that provably rendered the pre-reveal page can
		// consume bucket capacity.
		$nonce = $request->get_header( 'X-TL-Secret-Nonce' );

		$manager = new SecretManager();

		if ( $nonce ) {
			$peek = $manager->get_record( $token );
			if ( ! $peek || empty( $peek['reveal_nonce'] ) || ! hash_equals( $peek['reveal_nonce'], $nonce ) ) {
				return $this->not_available();
			}
		} else {
			return $this->not_available();
		}

		// IP-based rate limit: max 10 fetches per 5-minute window per IP.
		// Defense-in-depth against XSS-replay loops on a single client.
		if ( $this->is_rate_limited( 'fetch' ) ) {
			return new \WP_REST_Response( array( 'error' => 'rate_limited' ), 429 );
		}

		$record = $manager->fetch_and_lock( $token );

		if ( null === $record ) {
			return $this->not_available();
		}

		SecretAudit::log( 'previewed', $token );

		return new \WP_REST_Response(
			array(
				'nonce'               => $record['nonce'],
				'ciphertext'          => $record['ciphertext'],
				'passphrase_required' => $record['passphrase_required'],
				'burn_after_reading'  => $record['burn_after_reading'] ?? true,
				'expires_at'          => $record['expires_at'],
			),
			200
		);
	}

	/**
	 * Burns (reveals and destroys) a secret.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function burn( \WP_REST_Request $request ) {

		$token   = $request->get_param( 'token' );
		$manager = new SecretManager();

		// Require the reveal nonce for ALL deletes — proves the caller rendered
		// the page and clicked the button (JS-only step that crawlers can't do).
		$nonce  = $request->get_header( 'X-TL-Secret-Nonce' );
		$record = $manager->get_record( $token );

		if ( null === $record ) {
			return $this->not_available();
		}

		// Verify reveal nonce — must match the one stamped by /prepare.
		if ( ! $nonce || empty( $record['reveal_nonce'] ) || ! hash_equals( $record['reveal_nonce'], $nonce ) ) {
			return $this->not_available();
		}

		$is_multi_view = empty( $record['burn_after_reading'] );

		if ( ! $is_multi_view && empty( $record['in_flight'] ) ) {
			// Single-view record that hasn't been fetched yet — block pre-fetch burns.
			return $this->not_available();
		}

		// Multi-view secrets honor the issuer's viewer_can_destroy
		// flag — when false, recipients can read the secret but
		// can't DELETE it. Single-view secrets self-destroy on
		// reveal, so the flag only applies in the multi-view branch.
		if ( $is_multi_view && empty( $record['viewer_can_destroy'] ) ) {
			return $this->not_available();
		}

		// Two distinct DELETE callers land here:
		// - single-use: the speculative auto-DELETE the JS fires
		// immediately after a successful reveal. The cause IS the
		// reveal — log as 'revealed' so the audit row pairs with
		// the reveal event.
		// - multi-view: the recipient explicitly clicked "Destroy
		// this secret" on the reveal page. The cause is an
		// explicit destroy action, not a reveal. Log as 'burned'
		// so the SecretsPage status filter ($event === 'burned')
		// actually matches and the row moves out of "Active".
		$reason = $is_multi_view ? 'burned' : 'revealed';

		// Hand destroy() the record we already hold so it doesn't re-SELECT.
		$manager->destroy( $token, $reason, $record );

		SecretAudit::log( $reason, $token );

		return new \WP_REST_Response(
			array( 'success' => true ),
			200
		);
	}

	/**
	 * Verifies a passphrase against the stored hash.
	 *
	 * On lockout (0 attempts remaining), the secret is auto-destroyed
	 * and an audit event is logged.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function verify_passphrase( \WP_REST_Request $request ) {

		// Rate limit passphrase attempts per IP.
		if ( $this->is_rate_limited( 'verify', self::VERIFY_RATE_MAX_HITS, self::VERIFY_RATE_WINDOW ) ) {
			return new \WP_REST_Response( array( 'error' => 'rate_limited' ), 429 );
		}

		$token   = $request->get_param( 'token' );
		$manager = new SecretManager();
		$result  = $manager->verify_passphrase( $token, $request->get_param( 'passphrase' ) );

		if ( null === $result ) {
			return $this->not_available();
		}

		if ( $result['valid'] ) {
			// Pair-of with `passphrase_failed` so the listing UI can
			// derive "successful access" without inferring it from the
			// gap between previewed and failed counts. Also surfaces
			// a passphrase-protected secret in the audit panel even
			// before the recipient hits the destroy/burn step.
			SecretAudit::log( 'passphrase_succeeded', $token );
		} else {
			$memo = ! empty( $result['locked'] ) ? 'permanently_locked' : '';
			SecretAudit::log( 'passphrase_failed', $token, array( 'memo' => $memo ) );
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Checks if the current request is rate-limited by IP.
	 *
	 * Delegates the bucket mechanics to the shared
	 * {@see SlidingWindowRateLimit} trait so this endpoint uses the same
	 * sliding-window primitive as Activity / LoginAttempts / HelpdeskSecret.
	 * The local filter hook stays at the call site — it's specific to the
	 * Secrets endpoints (load-test / e2e escape hatch) and shouldn't
	 * leak into the generic trait.
	 *
	 * Bucket-shape note: the trait stores arrays of timestamps; the
	 * previous Secrets implementation stored a bare integer counter
	 * under the same key prefix. After deploy, an in-flight transient
	 * holding an integer is observed as `! is_array()` and the trait
	 * silently re-initialises it to an empty bucket — a clean cutover
	 * with no live-traffic breakage.
	 *
	 * @since 2.0.0
	 *
	 * @param string $action    The action being rate-limited (e.g. 'fetch', 'verify').
	 * @param int    $max_hits  Maximum requests allowed in the window. Default 10.
	 * @param int    $window    Time window in seconds. Default 300 (5 minutes).
	 *
	 * @return bool True if the caller has exceeded the rate limit.
	 */
	private function is_rate_limited( string $action, int $max_hits = 10, int $window = 300 ): bool {

		$ip      = Utils::get_ip();
		$ip_hash = substr( hash( 'sha256', $ip ), 0, SecretManager::TOKEN_LENGTH / 2 );

		/**
		 * Filters whether to apply rate limiting to secrets endpoints.
		 *
		 * Returning false disables limiting for the current request. Intended
		 * for controlled environments (e2e stacks, load tests) where legitimate
		 * traffic from a single IP would otherwise trip the limit. MUST stay
		 * true in production.
		 *
		 * @since 2.0.0
		 *
		 * @param bool   $enabled Whether rate limiting is applied. Default true.
		 * @param string $action  Endpoint action: 'fetch' or 'verify'.
		 * @param string $ip      The requester's IP address.
		 */
		if ( ! apply_filters( 'trustedlogin/connector/secrets/rate-limit/enabled', true, $action, $ip ) ) {
			return false;
		}

		return $this->enforce_rate_limit(
			self::RATE_LIMIT_KEY_PREFIX . $action . '_' . $ip_hash,
			$max_hits,
			$window
		);
	}

	/**
	 * Validates that a token is exactly 32 lowercase hexadecimal characters.
	 *
	 * @since 2.0.0
	 *
	 * @param string $value The token value to validate.
	 *
	 * @return true|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_token( $value ) {

		if ( preg_match( '/^[a-f0-9]{' . SecretManager::TOKEN_LENGTH . '}$/', $value ) ) {
			return true;
		}

		return new \WP_Error(
			'invalid_token',
			sprintf( 'Token must be exactly %d lowercase hexadecimal characters.', SecretManager::TOKEN_LENGTH ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Returns the universal "not available" response for secrets that
	 * have already been viewed, burned, or expired.
	 *
	 * @since 2.0.0
	 *
	 * @return \WP_REST_Response
	 */
	private function not_available() {

		return new \WP_REST_Response(
			array(
				'error'   => 'not_available',
				'message' => 'This secret has been viewed or expired.',
			),
			404
		);
	}

	/**
	 * URL-safe base64 encoding.
	 *
	 * Replaces + with -, / with _, and strips trailing = padding.
	 *
	 * @since 2.0.0
	 *
	 * @param string $data Raw binary data to encode.
	 *
	 * @return string URL-safe base64-encoded string.
	 */
	private function base64url_encode( $data ) {

		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}
}

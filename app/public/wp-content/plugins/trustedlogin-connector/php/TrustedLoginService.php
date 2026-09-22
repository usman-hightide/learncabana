<?php
/**
 * High-level service layer for TrustedLogin SaaS API interactions.
 *
 * @package TrustedLogin\TrustedLoginService
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Plugin;
use TrustedLogin\Vendor\Traits\VerifyUser;

/**
 * High-level API for SaaS interactions
 *
 * Methods include validation, logging and API calls
 */
class TrustedLoginService {


	use Logger;
	use VerifyUser;

	// Constants came fromhttps://github.com/trustedlogin/trustedlogin-vendor/blob/f8c451d6648a6aa4e1844c4df0952c1bdce87985/includes/class-trustedlogin-endpoint.php#L31-L41
	// Not all are needed.

	const HEALTH_CHECK_SUCCESS_STATUS = 204;

	const HEALTH_CHECK_ERROR_STATUS = 424;

	const PUBLIC_KEY_SUCCESS_STATUS = 200;

	const PUBLIC_KEY_ERROR_STATUS = 501;

	const REDIRECT_SUCCESS_STATUS = 302;

	const REDIRECT_ERROR_STATUS = 303;


	/**
	 * Plugin instance used for API calls and logging.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Inject the plugin container.
	 *
	 * @param Plugin $plugin Main plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Ingests an array of secret IDs and returns an array of only valid IDs with extra data.
	 *
	 * @since 0.12.0
	 *
	 * @param string[] $secret_ids Parameter.
	 * @param int      $account_id The account ID of the TrustedLogin account.
	 *
	 * @return array{ id:string, url_parts:array, envelope:array }
	 */
	public function get_valid_secrets( array $secret_ids, $account_id ) {

		$valid_ids = array();

		foreach ( $secret_ids as $secret_id ) {
			$envelope = $this->api_get_envelope( $secret_id, $account_id );

			$envelope = $this->verify_envelope( $envelope );

			if ( is_wp_error( $envelope ) ) {
				$this->log( 'Error: ' . $envelope->get_error_message(), __METHOD__, 'error' );
				continue;
			}

			// Shape-only debug — never log the full envelope. The
			// envelope carries an encrypted identifier, plaintext
			// siteUrl, client publicKey, and nonce; anything more
			// than shape metadata is half a replay primitive on disk.
			$this->log(
				'Envelope verified.',
				__METHOD__,
				'debug',
				array(
					'has_identifier' => is_array( $envelope ) && isset( $envelope['identifier'] ),
					'has_siteUrl'    => is_array( $envelope ) && isset( $envelope['siteUrl'] ),
				)
			);

			$url_parts = $this->envelope_to_url( $envelope, true );

			if ( is_wp_error( $url_parts ) ) {
				$this->log(
					'Error: ',
					__METHOD__,
					'error',
					array(
						'error_messages' => $url_parts->get_error_message(),
					)
				);
				continue;
			}

			if ( empty( $url_parts ) ) {
				continue;
			}

			$valid_ids[] = array(
				'id'        => $secret_id,
				'url_parts' => $url_parts,
				'envelope'  => $envelope,
			);
		}

		return $valid_ids;
	}

	/**
	 * Gets the secretId's associated with an access or license key.
	 *
	 * @since  1.0.0
	 *
	 * @param string $access_key The key we're checking for connected sites.
	 * @param int    $account_id The account ID for access key.
	 * @return array|\WP_Error  Array of siteIds or \WP_Error  on issue.
	 */
	public function api_get_secret_ids( $access_key, $account_id ) {

		if ( empty( $access_key ) ) {
			$this->log( 'Error: access_key cannot be empty.', __METHOD__, 'error' );

			return new \WP_Error( 'data-error', esc_html__( 'Access Key cannot be empty', 'trustedlogin-connector' ) );
		}

		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'auth-error', esc_html__( 'User not logged in.', 'trustedlogin-connector' ) );
		}

		$saas_api = $this->plugin->getApiHandler( $account_id );
		$response = $saas_api->call(
			ApiHandler::buildEndpoint( array( 'accounts', (int) $account_id, 'sites' ), array(), true ),
			array(
				'searchKeys' => array( $access_key ),
			),
			'POST'
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$this->log( 'Response: ', __METHOD__, 'debug', array( 'response' => $response ) );

		// 204 response: no sites found.
		if ( true === $response ) {
			return array();
		}

		$access_keys = array();

		if ( ! empty( $response ) ) {
			foreach ( $response as $key => $secrets ) {
				foreach ( (array) $secrets as $secret ) {
					$access_keys[] = $secret;
				}
			}
		}

		return array_reverse( $access_keys );
	}

	/**
	 * API Wrapper: Get the envelope for a specified site ID
	 *
	 * @since 0.2.0
	 *
	 * @param string $secret_id Unique secret_id of a site.
	 * @param int    $account_id The ID for the TrustedLogin account.
	 *
	 * @return array|false|\WP_Error
	 */
	public function api_get_envelope( $secret_id, $account_id ) {

		if ( empty( $secret_id ) ) {
			$this->log( 'Error: secret_id cannot be empty.', __METHOD__, 'error' );

			return new \WP_Error( 'data-error', esc_html__( 'Site ID cannot be empty', 'trustedlogin-connector' ) );
		}

		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'auth-error', esc_html__( 'User not logged in.', 'trustedlogin-connector' ) );
		}

		// The data array that will be sent to TrustedLogin to request a site's envelope.
		$data = array();

		// Let's grab the user details. Logged in status already confirmed in maybeRedirectSupport().
		$current_user = wp_get_current_user();

		$data['user'] = array(
			'id'   => $current_user->ID,
			'name' => $current_user->display_name,
		);

		// Then let's get the identity verification pair to confirm the site is the one sending the request.
		$trustedlogin_encryption = $this->plugin->getEncryption();
		$auth_nonce              = $trustedlogin_encryption->createIdentityNonce();

		if ( is_wp_error( $auth_nonce ) ) {
			return $auth_nonce;
		}

		$data['nonce']       = $auth_nonce['nonce'];
		$data['signedNonce'] = $auth_nonce['signed'];

		$account_id = (int) $account_id;

		$endpoint = ApiHandler::buildEndpoint(
			array( 'sites', $account_id, $secret_id, 'get-envelope' )
		);

		$saas_api   = $this->plugin->getApiHandler( $account_id );
		$x_tl_token = $saas_api->getXTlToken();

		if ( is_wp_error( $x_tl_token ) ) {
			$error = esc_html__( 'Error getting X-TL-TOKEN header', 'trustedlogin-connector' );
			$this->log( $error, __METHOD__, 'error' );
			return new \WP_Error( 'x-tl-token-error', $error );
		}

		$token_added = $saas_api->setAdditionalHeader( 'X-TL-TOKEN', $x_tl_token );

		if ( ! $token_added ) {
			$error = esc_html__( 'Error setting X-TL-TOKEN header', 'trustedlogin-connector' );
			$this->log( $error, __METHOD__, 'error' );
			return new \WP_Error( 'x-tl-token-error', $error );
		}

		// Return as-is (array|false|WP_Error) so the caller decides
		// how to log — false here is a normal network failure, not an
		// exception path.
		return $saas_api->call( $endpoint, $data, 'POST' );
	}

	/**
	 * Helper function: verify the structure of an envelope is valid.
	 *
	 * @since 2.0.0
	 *
	 * @param array|mixed $envelope Envelope to validate.
	 *
	 * @return array|\WP_Error Valid envelope or error if invalid.
	 */
	public function verify_envelope( $envelope ) {

		if ( empty( $envelope ) ) {
			$this->log( '$envelope is empty', __METHOD__, 'error' );
			return new \WP_Error( 'empty_envelope', 'The envelope is empty.' );
		}

		if ( is_object( $envelope ) ) {
			$envelope = (array) $envelope;
		}

		if ( ! is_array( $envelope ) ) {
			$this->log(
				'Error: envelope not an array. e:',
				__METHOD__,
				'error',
				array(
					'envelope' => $envelope,
				)
			);

			return new \WP_Error( 'malformed_envelope', 'The data received is not formatted correctly' );
		}

		// Verify the SaaS-issued signature BEFORE shape / content checks so a
		// tampered envelope is rejected before any of its fields are trusted.
		// Verification is a no-op when the integrator has not configured a
		// SaaS public key (legacy installs keep working unchanged).
		$signature_check = $this->verify_envelope_signature( $envelope );
		if ( is_wp_error( $signature_check ) ) {
			return $signature_check;
		}

		$required_keys = array( 'identifier', 'siteUrl', 'publicKey', 'nonce' );

		foreach ( $required_keys as $required_key ) {
			if ( ! array_key_exists( $required_key, $envelope ) ) {
				// Shape-only log; envelope content stays out of the log file.
				$this->failure(
					'envelope_malformed',
					FailureLog::SEVERITY_ERROR,
					'Error: malformed envelope.',
					__METHOD__,
					array(
						// 'keys' is for the file log only — FailureLog
						// drops it via the allowlist.
						'keys'       => array_keys( $envelope ),
						'error_code' => 'missing_' . $required_key,
					)
				);

				return new \WP_Error( 'malformed_envelope', 'The data received is not formatted correctly or there was a server error.' );
			}
		}

		return $envelope;
	}

	/**
	 * Verify the SaaS-signed envelope signature.
	 *
	 * Resolves the SaaS public key (filter → option fallback) and whether
	 * hard mode is engaged (filter, default false / soft) and delegates to
	 * the `EnvelopeVerifier`. Returns `true` when signing is disabled entirely
	 * on this install so back-compat is preserved.
	 *
	 * @since 2.0.0
	 *
	 * @param array $envelope Envelope to verify.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_envelope_signature( array $envelope ) {
		/**
		 * Filters the hex-encoded sodium Ed25519 public key used to verify
		 * SaaS-signed envelopes.
		 *
		 * Resolution order: this filter wins, then the
		 * `trustedlogin_vendor_saas_envelope_public_key` option. Use the
		 * filter when the key is stored outside the database (e.g. in an
		 * environment variable alongside other secrets); use the option for
		 * the simple case where you want to set it once via WP-CLI.
		 *
		 * Return `null` or empty string to disable verification entirely.
		 * This puts the connector into legacy-compat mode where any
		 * envelope from the SaaS is accepted on trust — the threat model
		 * explicitly rates a compromised SaaS as in-scope, so leaving
		 * this empty is a meaningful security trade. A persistent admin
		 * notice on TrustedLogin admin pages flags this state.
		 *
		 * Expected value: 64 hex characters (32 bytes after hex2bin).
		 * Anything else makes EnvelopeVerifier::is_enabled() return false
		 * and the connector silently falls back to legacy-compat mode.
		 *
		 * @since 2.0.0
		 *
		 * @param string|null $public_key_hex 64-char hex Ed25519 public key,
		 *                                    or null/empty to disable.
		 *                                    Defaults to the value of option
		 *                                    `trustedlogin_vendor_saas_envelope_public_key`.
		 */
		$public_key_hex = apply_filters(
			'trustedlogin/connector/envelope/saas-public-key',
			get_option( 'trustedlogin_vendor_saas_envelope_public_key', null )
		);

		// TOFU pin on first use. Threat model accepts the brief activation
		// window; integrators who want manual pinning disable it via the
		// auto-fetch filter and configure the key out-of-band.
		if ( ( ! is_string( $public_key_hex ) || '' === trim( (string) $public_key_hex ) ) && self::auto_fetch_enabled() ) {
			$fetched = $this->fetch_saas_envelope_public_key();
			if ( null !== $fetched ) {
				update_option( 'trustedlogin_vendor_saas_envelope_public_key', $fetched, false );
				$public_key_hex = $fetched;

				$fingerprint_prefix = substr( hash( 'sha256', $fetched ), 0, 8 );

				$this->log(
					'TOFU: pinned SaaS envelope public key.',
					__METHOD__,
					'info',
					array( 'fingerprint_sha256_prefix' => $fingerprint_prefix )
				);
			}
		}

		// HARD mode when a key is configured (signatures enforced);
		// SOFT only when no key exists (verifier::is_enabled() is false
		// and a persistent admin notice surfaces it). Integrators
		// staging an unsigned SaaS can opt back to SOFT via the filter
		// below.
		$verifier = new EnvelopeVerifier( $public_key_hex );

		/**
		 * Filters whether envelopes must carry a valid signature before
		 * the connector will decrypt them ("hard mode").
		 *
		 * Default depends on whether a verification key is configured:
		 *   - Key configured (EnvelopeVerifier::is_enabled() === true)
		 *     → HARD by default. The integrator deliberately wired
		 *     verification, so the safe default is to actually enforce it.
		 *     An envelope from a compromised SaaS that simply omitted the
		 *     `signature` field would otherwise be accepted with only a
		 *     warning log.
		 *   - No key configured → SOFT by default (you cannot enforce
		 *     what you cannot verify). The connector falls back to
		 *     legacy-compat mode and the persistent admin notice flags
		 *     the gap.
		 *
		 * The escape hatch: integrators staging a SaaS that does not
		 * sign yet can opt into soft mode by returning false here. Use
		 * this only during the rollout window — remove the filter as
		 * soon as the SaaS side is signing.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $enforce Defaults to true when a verification key
		 *                      is configured, false otherwise.
		 */
		$enforce = (bool) apply_filters(
			'trustedlogin/connector/envelope/require-signature',
			$verifier->is_enabled()
		);

		// Surface the "no verification key configured" state to the
		// admin panel as a notice (not an error — there's nothing
		// broken yet, but the integrator should configure
		// verification before going live). No file-log line here —
		// EnvelopeVerifier already emits a once-per-request notice.
		if ( ! $verifier->is_enabled() ) {
			FailureLog::record(
				'saas_pubkey_unpinned',
				FailureLog::SEVERITY_NOTICE,
				array()
			);
		}

		$result = $verifier->verify( $envelope, $enforce );
		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		// JIT rotation check: ask the SaaS for its current key. Same
		// key as cached → real signature failure, return as-is. New key
		// → pin it and retry verification once. Skipped when no cached
		// key exists or the integrator opted out of auto-fetch.
		if ( ! is_string( $public_key_hex ) || '' === trim( (string) $public_key_hex ) ) {
			return $result;
		}
		if ( ! self::auto_fetch_enabled() ) {
			return $result;
		}

		$refetched = $this->fetch_saas_envelope_public_key();
		if ( null === $refetched || hash_equals( (string) $public_key_hex, $refetched ) ) {
			// Same key, or fetch failed — the failure is real.
			// Surface it as a forgery-suspected event (matching
			// fingerprints) so the panel can render the P0
			// escalation card.
			$cached_fp = substr( hash( 'sha256', (string) $public_key_hex ), 0, 8 );
			FailureLog::record(
				'envelope_sig_failed',
				FailureLog::SEVERITY_ERROR,
				array(
					'fingerprint_old' => $cached_fp,
					'fingerprint_new' => $cached_fp,
					'error_code'      => $result->get_error_code(),
				)
			);
			return $result;
		}

		// Different key. Auto-rotate, audit the swap, retry once.
		update_option( 'trustedlogin_vendor_saas_envelope_public_key', $refetched, false );

		$old_fp = substr( hash( 'sha256', (string) $public_key_hex ), 0, 8 );
		$new_fp = substr( hash( 'sha256', $refetched ), 0, 8 );

		$this->failure(
			'saas_pubkey_rotated',
			FailureLog::SEVERITY_WARNING,
			'JIT: SaaS envelope public key rotated; pinned new key and retrying verification.',
			__METHOD__,
			array(
				'fingerprint_old' => $old_fp,
				'fingerprint_new' => $new_fp,
			)
		);

		// Persistent admin acknowledgement of the rotation: the option
		// gates the admin notice; the one-time email fires only on
		// transition from "no pending" → "pending" so repeat rotations
		// in the same dismissal window don't spam the inbox.
		$pending = get_option( 'trustedlogin_connector_saas_pubkey_rotation_pending', false );
		if ( ! is_array( $pending ) ) {
			$payload = array(
				'fingerprint_old' => $old_fp,
				'fingerprint_new' => $new_fp,
				'rotated_at'      => time(),
			);
			update_option( 'trustedlogin_connector_saas_pubkey_rotation_pending', $payload, false );

			$admin_email = get_option( 'admin_email' );
			if ( is_string( $admin_email ) && '' !== $admin_email ) {
				$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
				$subject   = sprintf(
					/* translators: %s: site name. */
					__( '[%s] TrustedLogin: SaaS envelope signing key was rotated', 'trustedlogin-connector' ),
					$site_name
				);
				// phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralText -- Multi-paragraph admin email; literal-concat keeps source readable.
				$body = sprintf(
					/* translators: 1: old key fingerprint, 2: new key fingerprint, 3: admin URL. */
					__(
						"TrustedLogin Connector auto-pinned a new SaaS envelope signing key during a signature retry.\n\n" .
						"Old key fingerprint: %1\$s\nNew key fingerprint: %2\$s\n\n" .
						"If this rotation was expected (announced by TrustedLogin), no action is needed — review and dismiss the notice from the TrustedLogin Settings screen:\n%3\$s\n\n" .
						"If this rotation was NOT expected, this could indicate a network-level interception of the connector's HTTPS call to TrustedLogin. Investigate before dismissing the notice.\n",
						'trustedlogin-connector'
					),
					$old_fp,
					$new_fp,
					admin_url( 'admin.php?page=trustedlogin-settings' )
				);
				// phpcs:enable WordPress.WP.I18n.NonSingularStringLiteralText
				wp_mail( $admin_email, $subject, $body );
			}
		}

		$verifier = new EnvelopeVerifier( $refetched );
		$retry    = $verifier->verify( $envelope, $enforce );
		if ( ! is_wp_error( $retry ) ) {
			return $retry;
		}

		// Retry still failed. Rotation-suspected envelope_sig_failed
		// — different fingerprints distinguish this from the
		// forgery-suspected matching-fingerprint case above.
		FailureLog::record(
			'envelope_sig_failed',
			FailureLog::SEVERITY_ERROR,
			array(
				'fingerprint_old' => $old_fp,
				'fingerprint_new' => $new_fp,
				'error_code'      => $retry->get_error_code(),
			)
		);

		// The signing key has changed but the new key doesn't
		// verify this envelope either — the audit log entry above
		// gives ops the rotation context. Surface a short user-
		// facing message and let them retry; persistent failure is
		// an admin escalation.
		return new \WP_Error(
			'envelope_signature_invalid_after_rotation',
			esc_html__( 'Support session could not be verified. Please try again. If the issue persists, contact your administrator.', 'trustedlogin-connector' )
		);
	}

	/**
	 * Fetches the SaaS's currently-published Ed25519 envelope-signing
	 * public key. Returns the 64-char hex string on success, null on
	 * any failure (network, 404, malformed response, wrong shape).
	 *
	 * Used by both TOFU (lazy first-use pinning) and JIT (rotation
	 * detection on a verification failure). Both call sites are
	 * responsible for persisting the result if they want to.
	 *
	 * @since 2.0.0
	 *
	 * @return string|null
	 */
	protected function fetch_saas_envelope_public_key() {
		$base = (string) apply_filters( 'trustedlogin/api-url/saas', TRUSTEDLOGIN_API_URL );
		$url  = rtrim( $base, '/' ) . '/envelope-signing-public-key';

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 5,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			// SaaS unreachable — surface to admin so they know
			// connectivity is the issue, not their config.
			FailureLog::record(
				'saas_unreachable',
				FailureLog::SEVERITY_WARNING,
				array( 'error_code' => $response->get_error_code() )
			);
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			// 404 from this endpoint specifically means "SaaS is not
			// signing envelopes yet" — that's a different signal
			// (recorded as saas_pubkey_unpinned at the verify site).
			// 4xx-not-404 / 5xx are real connectivity / auth issues.
			if ( 404 !== $code ) {
				FailureLog::record(
					'saas_unreachable',
					FailureLog::SEVERITY_WARNING,
					array( 'http_code' => $code )
				);
			}
			return null;
		}

		$body    = (string) wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) || empty( $decoded['publicKey'] ) || ! is_string( $decoded['publicKey'] ) ) {
			return null;
		}

		$key = trim( $decoded['publicKey'] );

		// Same shape check EnvelopeVerifier::is_enabled() applies.
		if ( 64 !== strlen( $key ) || ! ctype_xdigit( $key ) ) {
			return null;
		}

		return $key;
	}

	/**
	 * Whether the connector is allowed to fetch the SaaS envelope-
	 * signing public key automatically (TOFU on first use, JIT on
	 * verification failure). Defaults to true; integrators who want
	 * strict out-of-band trust establishment can disable both auto-
	 * fetch paths by returning false from the filter.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	private static function auto_fetch_enabled() {
		/**
		 * Controls whether the connector may fetch the SaaS envelope-
		 * signing public key automatically.
		 *
		 * Default true. Both TOFU on first use and JIT on a
		 * verification failure are gated on this filter. Returning
		 * false leaves all key delivery to the integrator (option set
		 * via wp-cli, or the saas-public-key filter).
		 *
		 * @since 2.0.0
		 *
		 * @param bool $enabled Defaults to true.
		 */
		return (bool) apply_filters( 'trustedlogin/connector/envelope/auto-fetch-key', true );
	}

	/**
	 * Validates that an envelope's `siteUrl` host is on the integrator's
	 * allowlist.
	 *
	 * Empty allowlist (the default) means "any host" for backwards
	 * compatibility; integrators that have a fixed set of customer sites
	 * should populate the filter with hostnames they actually support.
	 *
	 * @since 2.0.0
	 *
	 * @param string $site_url Raw siteUrl from the envelope.
	 *
	 * @return string|\WP_Error Normalized URL string on success, WP_Error on rejection.
	 */
	public function validate_return_site_url( $site_url ) {
		$site_url = trim( (string) $site_url );

		if ( '' === $site_url ) {
			return new \WP_Error( 'empty_site_url', esc_html__( 'Envelope is missing a client site URL.', 'trustedlogin-connector' ) );
		}

		$parsed = wp_parse_url( $site_url );

		if ( empty( $parsed['host'] ) || empty( $parsed['scheme'] ) ) {
			return new \WP_Error( 'invalid_site_url', esc_html__( 'Envelope site URL is malformed.', 'trustedlogin-connector' ) );
		}

		if ( ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
			return new \WP_Error( 'invalid_site_url', esc_html__( 'Envelope site URL must use http or https.', 'trustedlogin-connector' ) );
		}

		/**
		 * Filter the list of hostnames that are allowed to appear in a
		 * decrypted envelope's `siteUrl` field. Return an empty array to
		 * allow any host (default); return a populated array to restrict
		 * redirects to a pre-declared set.
		 *
		 * Integrators with a fixed set of customer domains should populate
		 * this filter with the hostnames they support.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $allowed_hosts Hostnames to accept. Defaults to empty (any).
		 */
		$allowed_hosts = (array) apply_filters( 'trustedlogin/connector/envelope/allowed-return-hosts', array() );

		if ( empty( $allowed_hosts ) ) {
			return $site_url;
		}

		$allowed_hosts = array_map( 'strtolower', array_map( 'strval', $allowed_hosts ) );
		$host          = strtolower( $parsed['host'] );

		if ( ! in_array( $host, $allowed_hosts, true ) ) {
			$this->failure(
				'site_url_not_allowed',
				FailureLog::SEVERITY_WARNING,
				'Envelope siteUrl host not in allowlist — refusing redirect.',
				__METHOD__,
				array(
					'site_host'     => $host,
					// 'allowed_count' is file-log-only; FailureLog
					// drops it via the allowlist.
					'allowed_count' => count( $allowed_hosts ),
				)
			);
			return new \WP_Error(
				'site_url_not_allowed',
				esc_html__( 'The client site URL is not on the allowed-hosts list for this connector.', 'trustedlogin-connector' )
			);
		}

		return $site_url;
	}

	/**
	 * Helper function: Extract redirect url from encrypted envelope.
	 *
	 * @since 0.1.0
	 *
	 * @param array $envelope {
	 * Received from encrypted TrustedLogin storage.
	 * @type string $siteUrl Encrypted site URL
	 * @type string $identifier Encrypted site identifier, used to generate endpoint
	 * @type string $publicKey Hex-encoded client public key (sealed-envelope sender).
	 * @type string $nonce Nonce from Client {@see \TrustedLogin\Envelope::generate_nonce()} converted to string using \sodium_bin2hex().
	 * }
	 *
	 * @param bool  $return_parts Optional. Whether to return an array of parts. Default: false.
	 *
	 * @return string|array|\WP_Error  If $return_parts is false, returns login URL. If true, returns array with login parts. If error, returns \WP_Error .
	 */
	public function envelope_to_url( $envelope, $return_parts = false ) {

		if ( is_wp_error( $this->verify_envelope( $envelope ) ) ) {
			// Log envelope shape only, never content — a captured envelope
			// plus a private_key leak is half of a replay primitive.
			$this->log(
				'Error: envelope not an array.',
				__METHOD__,
				'error',
				array(
					'keys' => is_array( $envelope ) ? array_keys( $envelope ) : gettype( $envelope ),
				)
			);

			return new \WP_Error( 'malformed_envelope', 'The data received is not formatted correctly' );
		}

		$trustedlogin_encryption = $this->plugin->getEncryption();

		try {
			// Don't log the envelope — see rationale above. Enough
			// metadata to trace the attempt without exposing the
			// ciphertext or the target siteUrl.
			$this->log(
				'Starting to decrypt envelope.',
				__METHOD__,
				'debug',
				array(
					'has_identifier' => isset( $envelope['identifier'] ),
					'has_nonce'      => isset( $envelope['nonce'] ),
					'has_publicKey'  => isset( $envelope['publicKey'] ),
				)
			);

			$decrypted_identifier = $trustedlogin_encryption->decryptCryptoBox( $envelope['identifier'], $envelope['nonce'], $envelope['publicKey'] );

			// Client SDKs cache our public key for 10 min; a rotation
			// mid-session leaves them sealing against a retired key. Walk
			// Encryption::getKeypairHistory's retention ring for a match.
			if ( is_wp_error( $decrypted_identifier ) && 'decryption_failed' === $decrypted_identifier->get_error_code() ) {
				$historical_keyring = $trustedlogin_encryption->getKeypairHistory();
				// DEBUG (not INFO) so a post-rotation burst of stale-cache
				// clients doesn't flood the file log. Operators reading
				// after the fact can still see the entry attempts when
				// triaging a suspected replay-with-retired-key incident.
				$this->log(
					'Current keypair could not decrypt envelope; walking historical keyring.',
					__METHOD__,
					'debug',
					array( 'historical_entries' => count( $historical_keyring ) )
				);
				foreach ( $historical_keyring as $historical ) {
					if ( empty( $historical['private_key'] ) ) {
						continue;
					}
					$candidate = $trustedlogin_encryption->decryptCryptoBoxWithKeypair(
						$envelope['identifier'],
						$envelope['nonce'],
						$envelope['publicKey'],
						$historical['private_key']
					);
					if ( ! is_wp_error( $candidate ) ) {
						$this->log( 'Decrypted envelope with a retired keypair from the historical keyring.', __METHOD__, 'info' );
						$decrypted_identifier = $candidate;
						break;
					}
				}
			}

			if ( is_wp_error( $decrypted_identifier ) ) {
				$this->failure(
					'envelope_decrypt_failed',
					FailureLog::SEVERITY_ERROR,
					'There was an error decrypting the envelope:',
					__METHOD__,
					array(
						'error_code'    => $decrypted_identifier->get_error_code(),
						// 'error_message' is file-log-only;
						// FailureLog drops it via the allowlist.
						'error_message' => $decrypted_identifier->get_error_message(),
					)
				);

				return $decrypted_identifier;
			}

			// Log a short hash prefix of the identifier — correlates
			// log lines for one session without writing the raw bearer
			// token to disk.
			$this->log(
				'Envelope decrypted.',
				__METHOD__,
				'debug',
				array(
					'identifier_sha256_prefix' => is_string( $decrypted_identifier )
						? substr( hash( 'sha256', $decrypted_identifier ), 0, 8 )
						: '',
				)
			);

			// Validate the envelope's siteUrl against the integrator's
			// configured return-host allowlist before using it for redirects.
			$site_url_check = $this->validate_return_site_url( (string) $envelope['siteUrl'] );

			if ( is_wp_error( $site_url_check ) ) {
				return $site_url_check;
			}

			$parts = array(
				'siteurl'    => $site_url_check,
				'identifier' => $decrypted_identifier,
			);
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		$endpoint = $trustedlogin_encryption::hash( $parts['siteurl'] . $parts['identifier'] );

		if ( is_wp_error( $endpoint ) ) {
			return $endpoint;
		}

		$loginurl = $parts['siteurl'] . '/' . $endpoint . '/' . $parts['identifier'];

		if ( $return_parts ) {
			return array(
				'siteurl'    => $parts['siteurl'],
				'loginurl'   => $loginurl,
				'endpoint'   => $endpoint,
				'identifier' => $parts['identifier'],
			);
		}

		return $loginurl;
	}
}

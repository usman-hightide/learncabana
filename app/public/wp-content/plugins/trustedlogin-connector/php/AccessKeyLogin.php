<?php
/**
 * Access-key login handler.
 *
 * Validates inbound access-key requests (length, account-id lookup, role/cap
 * gates, secret resolution) and returns the URL parts the React form needs to
 * complete the support-agent handoff. Owns the SLUG_* error constants used by
 * MaybeRedirect to map internal slugs onto the externally-exposed URL slugs.
 *
 * @package trustedlogin-vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\VerifyUser;
use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Webhooks\Factory;

/**
 * Handler for access key login.
 */
class AccessKeyLogin {

	use Logger;
	use VerifyUser;

	/**
	 * WordPress admin slug for access key login
	 */
	const PAGE_SLUG = 'trustedlogin_access_key_login';

	/**
	 * Name of form nonce
	 */
	const NONCE_NAME = '_tl_ak_nonce';

	/**
	 * Name of form nonce action
	 */
	const NONCE_ACTION = 'ak-redirect';

	/**
	 * Query param for redirect URL, to indicate it is a key login
	 */
	const ACCESS_KEY_ACTION_NAME = 'tl_access_key_login';

	/**
	 * Query param for redirect URL, to indicate account ID
	 */
	const ACCOUNT_ID_INPUT_NAME = 'ak_account_id';

	const ACCESS_KEY_INPUT_NAME = 'ak';

	const ACCESS_KEY_STRING_LENGTH = 64;

	const REDIRECT_ENDPOINT = 'trustedlogin';

	/** HTTP status when the current user isn't allowed to provide support. */
	const ERROR_INVALID_ROLE = 403;

	/** HTTP status when no TL account matches the specified account ID. */
	const ERROR_NO_ACCOUNT_ID = 404;

	/** HTTP status when no secret ids found. */
	const ERROR_NO_SECRET_IDS_FOUND = 406;

	/** HTTP status when the secret keys provided have an invalid format. */
	const ERROR_INVALID_SECRET = 422;

	/** HTTP status when no envelope was found. */
	const ERROR_NO_ENVELOPE = 510;

	/*
	 * Slug constants used as WP_Error::$code values. These are the canonical
	 * machine identifiers — callers should branch on these strings via
	 * `get_error_code()`. The matching HTTP status integers live in
	 * WP_Error::$data['status'] (set via the ERROR_* constants above) so that
	 * WP REST consumers see the correct response status without parsing slugs.
	 */
	const SLUG_INVALID_SECRET           = 'invalid_secret';
	const SLUG_INVALID_ACCOUNT_ID       = 'invalid_account_id';
	const SLUG_INVALID_USER_ROLE        = 'invalid_user_role';
	const SLUG_MISSING_LOGIN_CAPABILITY = 'missing_login_capability';
	const SLUG_INVALID_SECRET_KEYS      = 'invalid_secret_keys';
	const SLUG_NO_SECRET_IDS            = 'no_secret_ids';
	const SLUG_NO_VALID_SECRET_IDS      = 'no_valid_secret_ids';


	/**
	 * The URL for access key login.
	 *
	 * @param string $account_id The account ID.
	 * @param string $provider   The provider name.
	 * @param string $access_key (Optional) The key for the access being requested.
	 *
	 * @return string
	 */
	public static function url( $account_id, $provider, $access_key = '' ) {
		return Factory::actionUrl(
			self::ACCESS_KEY_ACTION_NAME,
			$account_id,
			$provider,
			$access_key
		);
	}

	/**
	 * Generates a fresh hex-encoded secret for use as an HMAC signing key.
	 *
	 * 256 bits — HMAC signing-key floor. Existing shorter secrets keep
	 * validating because HMAC is key-length agnostic.
	 *
	 * @return string 64-char hex-encoded secret.
	 */
	public static function makeSecret() {
		return bin2hex( random_bytes( 32 ) );
	}


	/**
	 * Processes the access-key login request.
	 *
	 * Runs {@see verifyGrantAccessRequest()} unless the caller passes
	 * `$trusted = true` to attest that CSRF was verified upstream.
	 *
	 * @param array $args    Optional. Source of `ak` / `ak_account_id`;
	 *                       absent keys fall back to `$_REQUEST`.
	 * @param bool  $trusted Optional. Skip the nonce check when true.
	 *                       Default false.
	 *
	 * @return array|\WP_Error
	 */
	public function handle( array $args = array(), bool $trusted = false ) {
		if ( ! $trusted ) {
			$verified = $this->verifyGrantAccessRequest();

			if ( is_wp_error( $verified ) ) {
				return $verified;
			}
		}

		if ( isset( $args[ self::ACCESS_KEY_INPUT_NAME ] ) && isset( $args[ self::ACCOUNT_ID_INPUT_NAME ] ) ) {
			$access_key = sanitize_text_field( (string) $args[ self::ACCESS_KEY_INPUT_NAME ] );
			$account_id = sanitize_text_field( (string) $args[ self::ACCOUNT_ID_INPUT_NAME ] );
		} else {
			$access_key = Helpers::get_post_or_get( self::ACCESS_KEY_INPUT_NAME, 'sanitize_text_field' );
			$account_id = Helpers::get_post_or_get( self::ACCOUNT_ID_INPUT_NAME, 'sanitize_text_field' );
		}

		if ( self::ACCESS_KEY_STRING_LENGTH !== strlen( $access_key ) ) {
			return new \WP_Error(
				self::SLUG_INVALID_SECRET,
				esc_html__( 'The provided key is not the correct length. Make sure you copied it correctly and it is an Access Key.', 'trustedlogin-connector' ),
				array( 'status' => self::ERROR_INVALID_SECRET )
			);
		}

		// Get saved settings and then team settings.
		$settings   = SettingsApi::fromSaved();
		$account_id = (int) $account_id;

		try {
			$teamSettings = $settings->getByAccountId( $account_id );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				self::SLUG_INVALID_ACCOUNT_ID,
				esc_html__( 'No team was found for that Account ID.', 'trustedlogin-connector' ),
				array( 'status' => self::ERROR_NO_ACCOUNT_ID )
			);
		}

		if ( ! $this->verifyUserRole( $teamSettings ) ) {
			return new \WP_Error(
				self::SLUG_INVALID_USER_ROLE,
				esc_html__( 'You do not have a role that is allowed to provide support for this team.', 'trustedlogin-connector' ),
				array( 'status' => self::ERROR_INVALID_ROLE )
			);
		}

		// Two-tier access: team-level `approved_roles` (above) restricts
		// which roles can support THIS team; site-level ACCESS_KEY_LOGIN
		// (here) restricts which roles can use the access-key flow at
		// all. Both must pass.
		if ( ! Capabilities::current_user_can( Capabilities::ACCESS_KEY_LOGIN ) ) {
			return new \WP_Error(
				self::SLUG_MISSING_LOGIN_CAPABILITY,
				esc_html__( 'You do not have permission to complete access-key login. Contact an administrator.', 'trustedlogin-connector' ),
				array( 'status' => self::ERROR_INVALID_ROLE )
			);
		}

		$trustedlogin_service = new TrustedLoginService(
			trustedlogin_connector()
		);

		$secret_ids = $trustedlogin_service->api_get_secret_ids( $access_key, $account_id );

		if ( is_wp_error( $secret_ids ) ) {
			return new \WP_Error(
				self::SLUG_INVALID_SECRET_KEYS,
				$secret_ids->get_error_message(),
				array( 'status' => 400 )
			);
		}

		if ( empty( $secret_ids ) ) {
			return new \WP_Error(
				self::SLUG_NO_SECRET_IDS,
				esc_html__( 'There were no sites found that match that Access Key. Access may have been revoked.', 'trustedlogin-connector' ),
				array( 'status' => self::ERROR_NO_SECRET_IDS_FOUND )
			);
		}

		$valid_secrets = $trustedlogin_service->get_valid_secrets( $secret_ids, $account_id );

		// Shape-only debug — never serialize the full $valid_secrets
		// array. Each entry contains the decrypted bearer identifier,
		// the constructed loginurl, and the raw envelope (ciphertext +
		// nonce + client publicKey). With debug-logging on, anyone with
		// read access to the log file (backup grab, file-disclosure,
		// shared-host log reader) picks up working bearer URLs to the
		// customer's WP admin for the full client-side TTL. Same leak
		// class as commits 9a00df2 and 2b209c1; this site was missed.
		$this->log(
			'Valid secrets returned.',
			__METHOD__,
			'debug',
			array(
				'count' => count( $valid_secrets ),
			)
		);

		if ( empty( $valid_secrets ) ) {
			return new \WP_Error(
				self::SLUG_NO_VALID_SECRET_IDS,
				esc_html__( 'There were secrets found, but they were invalid.', 'trustedlogin-connector' ),
				array( 'status' => self::ERROR_NO_SECRET_IDS_FOUND )
			);
		}

		/**
		 * Return all url parts, not just 0
		 *
		 * @see https://github.com/trustedlogin/vendor/issues/109
		 */
		return wp_list_pluck( $valid_secrets, 'url_parts' );
	}

	/**
	 * Verifies the $_POST request by the Access Key login form.
	 *
	 * Checks `ak`, `ak_account_id`, and the form nonce. Callers that
	 * have already authenticated the request upstream should pass
	 * `$trusted = true` to {@see handle()} rather than calling this
	 * method.
	 *
	 * @return bool|\WP_Error
	 */
	public function verifyGrantAccessRequest() {

		if ( ! Helpers::get_post_or_get( self::ACCESS_KEY_INPUT_NAME ) ) {
			$this->log( 'No access key sent.', __METHOD__, 'error' );
			return new \WP_Error( 'no_access_key', esc_html__( 'No access key was sent with the request.', 'trustedlogin-connector' ) );
		}

		if ( ! Helpers::get_post_or_get( self::ACCOUNT_ID_INPUT_NAME ) ) {
			$this->log( 'No account id sent.', __METHOD__, 'error' );
			return new \WP_Error( 'no_account_id', esc_html__( 'No account id was sent with the request.', 'trustedlogin-connector' ) );
		}

		$nonce = Helpers::get_post_or_get( self::NONCE_NAME, 'sanitize_text_field' );

		if ( ! $nonce ) {
			$this->log( 'No nonce set. Insecure request.', __METHOD__, 'error' );
			return new \WP_Error( 'no_nonce', esc_html__( 'No nonce was sent with the request.', 'trustedlogin-connector' ) );
		}

		$valid = wp_verify_nonce( $nonce, self::NONCE_ACTION );

		if ( ! $valid ) {
			$this->log( 'Nonce is invalid; could be insecure request. Refresh the page and try again.', __METHOD__, 'error' );
			return new \WP_Error( 'bad_nonce', esc_html__( 'The nonce sent with the request was invalid or expired. Refresh the page and try again.', 'trustedlogin-connector' ) );
		}

		return true;
	}

	/**
	 * Get access key or account ID from requests
	 *
	 * @param bool $return_access_key Optional. If true, access key returned. If false, account ID.
	 * @return string
	 */
	public static function fromRequest( bool $return_access_key = true ) {

		if ( $return_access_key ) {
			return (string) Helpers::get_post_or_get( self::ACCESS_KEY_INPUT_NAME, 'sanitize_text_field' );
		}

		return (string) Helpers::get_post_or_get( self::ACCOUNT_ID_INPUT_NAME, 'sanitize_text_field' );
	}
}

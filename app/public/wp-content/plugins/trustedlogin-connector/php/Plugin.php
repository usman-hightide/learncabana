<?php
/**
 * Plugin implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Contracts\SendsApiRequests as ApiSend;
use TrustedLogin\Vendor\Endpoints\SignatureKey;
use TrustedLogin\Vendor\Forms\GravityForms\TrustedLoginGFField;
use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\TeamSettings;
/**
 * Plugin implementation.
 */
class Plugin {

	use Logger;

	/**
	 * Instance of Encryption.
	 *
	 * @var Encryption
	 */
	protected $encryption;

	/**
	 * Instance of ApiSend.
	 *
	 * @var ApiSend
	 */
	protected $apiSender;

	/**
	 * Instance of SettingsApi.
	 *
	 * @var SettingsApi
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param Encryption $encryption The encryption instance.
	 */
	public function __construct( Encryption $encryption ) {
		$this->encryption = $encryption;
		$this->apiSender  = new \TrustedLogin\Vendor\ApiSend();
		$this->settings   = SettingsApi::fromSaved();

		$this->register_gravity_forms_field();
	}

	/**
	 * Registers the access-key field with Gravity Forms when available.
	 *
	 * No-op if GF isn't loaded.
	 */
	private function register_gravity_forms_field() {
		if ( ! class_exists( '\GF_Fields' ) ) {
			return;
		}
		try {
			\GF_Fields::register( new TrustedLoginGFField() );
		} catch ( \Exception $e ) {
			$this->log( $e->getMessage(), __METHOD__, 'error' );
		}
	}



	/**
	 * Add REST API endpoints
	 *
	 * @uses "rest_api_init" action
	 */
	public function restApiInit() {
		( new \TrustedLogin\Vendor\Endpoints\Settings() )
			->register( true );
		( new \TrustedLogin\Vendor\Endpoints\GlobalSettings() )
			->register( true );
		( new \TrustedLogin\Vendor\Endpoints\ResetTeam() )
			->register( true, false );
		( new \TrustedLogin\Vendor\Endpoints\PublicKey() )
			->register( false );
		( new SignatureKey() )
			->register( false );
		( new \TrustedLogin\Vendor\Endpoints\ResetEncryption() )
			->register( true, false );
		( new \TrustedLogin\Vendor\Endpoints\AccessKey() )
			->register( true, false );
		( new \TrustedLogin\Vendor\Endpoints\Logging() )
			->register( true, true );
		( new \TrustedLogin\Vendor\Endpoints\Secrets() )
			->register();
		( new \TrustedLogin\Vendor\Endpoints\Activity() )
			->register();
		( new \TrustedLogin\Vendor\Endpoints\LoginAttempts() )
			->register();
		( new \TrustedLogin\Vendor\Endpoints\Permissions() )
			->register( true, true );
		( new \TrustedLogin\Vendor\Endpoints\HelpdeskSecret() )
			->register( false, true );
	}

	/**
	 * Get the settings API object
	 *
	 * @return SettingsApi
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * Get the encryption API
	 *
	 * @return Encryption
	 */
	public function getEncryption() {
		return $this->encryption;
	}


	/**
	 * Get the encyption public key
	 *
	 * @return string|\WP_Error
	 */
	public function getPublicKey() {
		return $this->encryption
			->getPublicKey();
	}

	/**
	 * Get the encyption signature key
	 *
	 * @return string|\WP_Error
	 */
	public function getSignatureKey() {
		return $this->encryption
			->getPublicKey( 'sign_public_key' );
	}


	/**
	 * Get API Handler by account id
	 *
	 * @param int               $accountId Account ID, which must be saved in settings, to get handler for.
	 * @param string            $apiUrl Optional. URL override for TrustedLogin API.
	 * @param null|TeamSettings $team Optional. TeamSettings  to use.
	 *
	 * @return ApiHandler
	 */
	public function getApiHandler( $accountId, $apiUrl = '', $team = null ) {
		if ( ! $team ) {
			$team = SettingsApi::fromSaved()->getByAccountId( $accountId );
		}

		return new ApiHandler(
			array(
				'private_key' => $team->get( 'private_key' ),
				'public_key'  => $team->get( 'public_key' ),
				'debug_mode'  => $team->get( 'debug_enabled' ),
				'api_url'     => $apiUrl ? $apiUrl : $this->getApiUrl(),
			),
			$this->apiSender
		);
	}

	/**
	 * Verify team credentials
	 *
	 * @param TeamSettings $team The team settings to verify.
	 * @return bool
	 */
	public function verifyAccount( TeamSettings $team ) {
		$account_id = $team->get( 'account_id' );

		$this->log(
			'Starting account verification',
			__METHOD__,
			'debug',
			array(
				'account_id'      => $account_id,
				'has_private_key' => ! empty( $team->get( 'private_key' ) ),
				'has_public_key'  => ! empty( $team->get( 'public_key' ) ),
				'api_url'         => $this->getApiUrl(),
			)
		);

		$handler = new ApiHandler(
			array(
				'private_key' => $team->get( 'private_key' ),
				'public_key'  => $team->get( 'public_key' ),
				'debug_mode'  => $team->get( 'debug_enabled' ),
				'api_url'     => $this->getApiUrl(),
			),
			$this->apiSender
		);

		$result = $handler->verify( $account_id );

		if ( is_wp_error( $result ) ) {
			$this->log(
				'Account verification failed',
				__METHOD__,
				'error',
				array(
					'account_id'    => $account_id,
					'error_code'    => $result->get_error_code(),
					'error_message' => $result->get_error_message(),
				)
			);
			return false;
		}

		// Don't log $result directly — it may contain tokens or other sensitive
		// data from the SaaS API. Log only that verification succeeded.
		$this->log(
			'Account verification successful',
			__METHOD__,
			'info',
			array(
				'account_id' => $account_id,
				'verified'   => true,
			)
		);

		return true;
	}

	/**
	 * Returns the API URL after passing it through a filter.
	 *
	 * @return string
	 */
	public function getApiUrl() {
		return (string) apply_filters( 'trustedlogin/api-url/saas', TRUSTEDLOGIN_API_URL );
	}

	/**
	 * Set the apiSender instance
	 *
	 * @param ApiSend $apiSender Parameter.
	 * @return $this
	 */
	public function setApiSender( ApiSend $apiSender ) {
		$this->apiSender = $apiSender;
		return $this;
	}
}

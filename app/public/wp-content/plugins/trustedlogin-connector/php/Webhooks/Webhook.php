<?php
/**
 * Base webhook class for TrustedLogin Vendor integrations.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Webhooks;

use TrustedLogin\Vendor\AccessKeyLogin;
use TrustedLogin\Vendor\Traits\Licensing;
use TrustedLogin\Vendor\Traits\Logger;

/**
 * Base class for all webhooks to integrated with
 *
 * The webhookEndpoint() method must be defined in a concrete class.
 * It must return an array with a key "html"
 * This should be the output of the webhook for the widget.
 *
 * The getProviderName() method must be defined in a concrete class.
 * It must return a string, with the name of the provider and should be URL safe.
 * This name is used as the value of the "provider" query arg in the webhook URL.
 */
abstract class Webhook {


	use Licensing;
	use Logger;

	/**
	 * Value of "action" query arg for webhooks
	 */
	const WEBHOOK_ACTION = 'trustedlogin_webhook';

	/**
	 * Shared secret for the webhook
	 *
	 * @var string
	 */
	protected $secret;

	/**
	 * Initialise the webhook with a shared secret.
	 *
	 * @param string $secret Shared secret used to authenticate webhook requests.
	 */
	public function __construct( string $secret ) {
		$this->secret = $secret;
	}

	/**
	 * Get provider name for this webhook.
	 *
	 * @return string
	 */
	abstract protected static function getProviderName();

	/**
	 * Generates the HTML output for the webhook.
	 *
	 * @param array|null $data The data sent to the webhook. If null, php://input is used.
	 *
	 * @return array
	 */
	abstract public function webhookEndpoint( $data = null );

	/**
	 * Calculate signature from request data
	 *
	 * @param string $data JSON encoded data.
	 * @return string
	 */
	public function makeSignature( string $data ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HMAC signature, not obfuscation.
		return base64_encode( hash_hmac( 'sha1', $data, $this->secret, true ) );
	}

	/**
	 * Builds a URL for helpdesk request and redirect actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id What account ID link is for.
	 *
	 * @return string|\WP_Error The url with GET variables.
	 */
	public static function actionUrl( $account_id ) {
		return Factory::actionUrl(
			self::WEBHOOK_ACTION,
			$account_id,
			static::getProviderName()
		);
	}

	/**
	 * Returns license keys associated with customer email addresses.
	 *
	 * @param array $customer_emails Array of email addresses Help Scout associates with the customer.
	 *
	 * @return array Array of license keys associated with the passed emails.  @param array $licenses List of licenses. {
	 * @type object $license {
	 * @type string $key License key.
	 * @type string $status License status.
	 *   }
	 *  }
	 */
	protected function getLicensesByEmails( array $customer_emails ) {

		$licenses = array();
		if ( $this->isEDDStore() && $this->eddHasLicensing() ) {
			foreach ( $customer_emails as $customer_email ) {
				$email               = sanitize_email( $customer_email );
				$cache_key           = 'trustedlogin_licenses_edd' . md5( $email );
				$cache_group         = 'trustedlogin_edd';
				$_licenses_for_email = wp_cache_get( $cache_key, $cache_group );

				if ( false === $_licenses_for_email ) {
					$_licenses_for_email = $this->eddGetLicenses( $email );
				}

				if ( ! empty( $_licenses_for_email ) ) {
					wp_cache_set( $cache_key, $_licenses_for_email, $cache_group, DAY_IN_SECONDS );

					$licenses = array_merge( $licenses, $_licenses_for_email );
				}
			}
		}

		/**
		 * Filter: allow for other addons to generate the licenses array
		 *
		 * @since 1.1.1
		 *
		 * @param \EDD_SL_License[]|false $licenses
		 * @param array $customer_emails Array of email addresses associated with a customer, passed by the support desk widget.
		 *
		 * @return array
		 */
		$licenses = apply_filters( 'trustedlogin/connector/customers/licenses', $licenses, $customer_emails );

		/**
		 * Deprecated licenses filter alias.
		 *
		 * @since 0.6.0
		 * @depecated 1.1.1
		 */
		$licenses = apply_filters_deprecated( 'trustedlogin/vendor/customers/licenses', array( $licenses, $customer_emails ), '1.1.1', 'trustedlogin/connector/customers/licenses' );

		return $licenses;
	}
}

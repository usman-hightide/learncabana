<?php
/**
 * Webhook factory for creating helpdesk webhook instances.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Webhooks;

use TrustedLogin\Vendor\TeamSettings;
use TrustedLogin\Vendor\AccessKeyLogin;
use TrustedLogin\Vendor\Webhooks\Helpscout;

/**
 * Webhook factory for creating helpdesk webhook instances.
 *
 * @since 2.0.0
 */
class Factory {



	const PROVIDER_KEY = 'provider';

	/**
	 * Create a webhook instance for a team.
	 *
	 * @param TeamSettings $teamSettings The team settings object.
	 *
	 * @return Webhook
	 * @throws \Exception When the team has no helpdesk configured or the helpdesk type is unsupported.
	 */
	public static function webhook( TeamSettings $teamSettings ) {

		$helpdesks = $teamSettings->getHelpdesks();
		if ( empty( $helpdesks ) ) {
			throw new \Exception( esc_html__( 'Team has no helpdesk configured; cannot build webhook.', 'trustedlogin-connector' ) );
		}

		$type = $helpdesks[0];
		switch ( $type ) {
			case TeamSettings::HELPDESK_HELPSCOUT:
				return new HelpScout( $teamSettings->getHelpdeskData( $type )['secret'] );
			case TeamSettings::HELPDESK_FREESCOUT:
				return new Freescout( $teamSettings->getHelpdeskData( $type )['secret'] );
			default:
				throw new \Exception( esc_html__( 'Unknown webhook type.', 'trustedlogin-connector' ) );
		}
	}

	/**
	 * Get list of supported webhook providers.
	 *
	 * @return array
	 */
	public static function getProviders() {
		return array(
			TeamSettings::HELPDESK_HELPSCOUT,
			TeamSettings::HELPDESK_FREESCOUT,
		);
	}

	/**
	 * Returns the provider class FQCN for a given provider slug.
	 *
	 * Callers that need to invoke a static helper (e.g. build_error_message)
	 * without a fully-constructed webhook instance can dispatch through
	 * this lookup so the correct provider class is used.
	 *
	 * @since 2.0.0
	 *
	 * @param string $provider Provider slug from getProviders().
	 *
	 * @return string FQCN of the provider class. Falls back to Helpscout for unknown slugs.
	 */
	public static function providerClass( $provider ) {
		switch ( $provider ) {
			case TeamSettings::HELPDESK_FREESCOUT:
				return Freescout::class;
			case TeamSettings::HELPDESK_HELPSCOUT:
			default:
				return Helpscout::class;
		}
	}

	/**
	 * Builds a URL for helpdesk request and redirect actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action What action the link should do. eg 'support_redirect'.
	 * @param string $account_id What account ID link is for.
	 * @param string $provider Slug of helpdesk.
	 * @param string $access_key (Optional) The key for the access being requested.
	 *
	 * @return string|\WP_Error The url with GET variables.
	 */
	public static function actionUrl( $action, $account_id, $provider, $access_key = '' ) {

		if ( empty( $action ) ) {
			return new \WP_Error( 'variable-missing', esc_html__( 'Cannot build helpdesk action URL without a specified action.', 'trustedlogin-connector' ) );
		}

		$args = array(
			AccessKeyLogin::REDIRECT_ENDPOINT     => true,
			'action'                              => $action,
			self::PROVIDER_KEY                    => $provider,
			AccessKeyLogin::ACCOUNT_ID_INPUT_NAME => $account_id,
			AccessKeyLogin::NONCE_NAME            => wp_create_nonce( AccessKeyLogin::NONCE_ACTION ),
		);

		if ( $access_key ) {
			$args[ AccessKeyLogin::ACCESS_KEY_INPUT_NAME ] = $access_key;
		}

		// `add_query_arg()` percent-encodes values internally. The previous
		// pre-loop `urlencode()` over $args produced double-encoded output
		// (e.g. a space → `%2520` instead of `%20`); the receiving endpoint
		// urldecodes once and the value arrives in $_GET still containing
		// `%XX` literals, breaking any HMAC / nonce / equality check.
		$url = add_query_arg( $args, get_home_url() );
		return $url;
	}
}

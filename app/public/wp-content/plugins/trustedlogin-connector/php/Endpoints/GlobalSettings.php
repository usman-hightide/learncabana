<?php
/**
 * GlobalSettings implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\MenuPage;
use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Webhooks\Factory;
/**
 * GlobalSettings implementation.
 */
class GlobalSettings extends Settings {



	/**
	 * Return the REST route URI for global settings.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'settings/global';
	}

	/**
	 * Return the accepted arguments for POST requests to this route.
	 *
	 * @inheritdoc
	 */
	protected function updateArgs() {
		return array(
			'integrations'         => array(
				'type'     => 'object',
				'required' => false,
			),
			'default_landing_page' => array(
				'type'              => 'string',
				'required'          => false,
				'enum'              => array_keys( MenuPage::DEFAULT_LANDING_PAGES ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Handler for requests to POST settings updates
	 *
	 * @param \WP_REST_Request $request Parameter.
	 * @return \WP_REST_Response
	 */
	public function update( \WP_REST_Request $request ) {
		$settingsApi = SettingsApi::fromSaved();
		$updates     = array();

		$integrations = $request->get_param( 'integrations' );
		if ( is_array( $integrations ) || is_object( $integrations ) ) {
			$sanitized = self::sanitize_integrations( (array) $integrations );
			if ( ! empty( $sanitized ) ) {
				$updates['integrations'] = $sanitized;
			}
		}

		$default_landing_page = $request->get_param( 'default_landing_page' );
		if ( null !== $default_landing_page && isset( MenuPage::DEFAULT_LANDING_PAGES[ $default_landing_page ] ) ) {
			$updates['default_landing_page'] = $default_landing_page;
		}

		if ( ! empty( $updates ) ) {
			$settingsApi = $settingsApi->setGlobalSettings( $updates );
			$settingsApi->save();
		}

		return $this->createResponse( $settingsApi );
	}

	/**
	 * Reduces the inbound `integrations` payload to the allowed shape.
	 *
	 * Allowlist:
	 *   - keys: provider slugs from {@see Factory::getProviders()}
	 *   - per-provider fields: `enabled` (bool)
	 *
	 * Anything else is silently dropped.
	 *
	 * @param array $integrations Parameter.
	 *
	 * @return array
	 */
	private static function sanitize_integrations( array $integrations ) {
		$providers = Factory::getProviders();
		$out       = array();
		foreach ( $providers as $slug ) {
			if ( ! isset( $integrations[ $slug ] ) ) {
				continue;
			}
			$entry        = (array) $integrations[ $slug ];
			$out[ $slug ] = array(
				'enabled' => ! empty( $entry['enabled'] ),
			);
		}
		return $out;
	}
}

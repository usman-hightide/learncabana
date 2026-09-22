<?php
/**
 * Logging endpoint for REST API.
 *
 * Endpoint that gets/sets logging settings.
 *
 * Right now, this is just the error logging setting
 *  - https://github.com/trustedlogin/vendor/issues/127
 * Will also be used for activity logging
 *  - https://github.com/trustedlogin/vendor/issues/99
 *
 * @package TrustedLogin\Vendor\Endpoints
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\SettingsApi;

/**
 * Logging endpoint for REST API.
 *
 * @since 2.0.0
 */
class Logging extends Settings {


	/**
	 * Get the logging route.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'settings/logging';
	}

	/**
	 * Get REST endpoint args for the update method.
	 *
	 * @inheritdoc
	 */
	protected function updateArgs() {
		return array(
			'error' => array(
				'type'     => 'boolean',
				'required' => false,
				'default'  => false,
			),

		);
	}

	/**
	 * Get current logging settings.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get( \WP_REST_Request $request ) {
		return $this->createResponse(
			SettingsApi::fromSaved()
		);
	}

	/**
	 * Enable or disable logging.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function update( \WP_REST_Request $request ) {
		$error_logging_setting = (bool) $request->get_param( 'error' );

		$settingsApi = SettingsApi::fromSaved();
		$settingsApi->setGlobalSettings(
			array_merge(
				$settingsApi->getGlobalSettings(),
				array(
					'error_logging' => $error_logging_setting,
				)
			)
		);

		// Persist the toggle before touching the log file so a.
		// save() exception aborts cleanly without losing forensics.
		$settingsApi->save();

		if ( ! $error_logging_setting ) {
			trustedlogin_connector()->deleteLog();
		}

		return $this->createResponse( $settingsApi );
	}

	/**
	 * Create response from settings.
	 *
	 * @inheritdoc
	 * @param SettingsApi $settingsApi The settings API instance to serialise.
	 */
	protected function createResponse( SettingsApi $settingsApi ) {
		return rest_ensure_response(
			array(
				'error_logging' => $settingsApi->isErrorLogggingEnabled(),
			)
		);
	}
}

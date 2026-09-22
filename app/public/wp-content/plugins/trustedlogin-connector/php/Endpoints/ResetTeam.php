<?php
/**
 * ResetTeam implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Traits\Logger;
/**
 * ResetTeam implementation.
 */
class ResetTeam extends Settings {

	use Logger;

	/**
	 * Return the REST route URI for team reset.
	 *
	 * @inheritdoc
	 */
	protected function route() {

		return 'settings/team/reset';
	}

	/**
	 * Return the accepted arguments for POST requests to this route.
	 *
	 * @inheritdoc
	 */
	protected function updateArgs() {

		return array(
			'accountId'   => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
			'integration' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/**
	 * Handle GET requests and return current settings.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function get( \WP_REST_Request $request ) {
		$settingsApi = SettingsApi::fromSaved();
		return $this->createResponse( $settingsApi );
	}

	/**
	 * Handler for requests to POST settings updates
	 *
	 * @param \WP_REST_Request $request Parameter.
	 * @return \WP_REST_Response
	 */
	public function update( \WP_REST_Request $request ) {
		$account_id  = $request->get_param( 'accountId' );
		$integration = $request->get_param( 'integration' );
		$settingsApi = SettingsApi::fromSaved();

		try {
			$settingsApi->resetHelpdeskSettings( $account_id, $integration );
		} catch ( \Exception $e ) {
			// SettingsApi::resetHelpdeskSettings() throws \Exception.
			// for missing accounts. Surface 404 and log so a misfiled.
			// integration value (or a renamed account) is observable.
			$this->log(
				sprintf( 'Reset team helpdesk failed: account_id=%s integration=%s message=%s', $account_id, $integration, $e->getMessage() ),
				__METHOD__,
				'warning'
			);
			return new \WP_REST_Response(
				array( 'error' => 'Account not found or could not be reset' ),
				404
			);
		}

		return $this->createResponse( SettingsApi::fromSaved() );
	}
}

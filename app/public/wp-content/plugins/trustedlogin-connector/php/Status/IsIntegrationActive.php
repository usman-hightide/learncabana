<?php
/**
 * Integration active status checker.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Status;

/**
 * Checks if an integration is globally active.
 */
class IsIntegrationActive {

	/**
	 * Check if integration is globally active.
	 *
	 * @param string $integrationName Name of the integration to check.
	 * @return bool True if integration is enabled, false otherwise.
	 */
	public static function check( string $integrationName ) {
		$settings = \trustedlogin_connector()->getSettings()->getIntegrationSettings();
		return isset( $settings[ $integrationName ] ) && $settings[ $integrationName ]['enabled'];
	}
}

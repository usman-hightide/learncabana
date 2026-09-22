<?php
/**
 * Reset implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Status\Onboarding;
use TrustedLogin\Vendor\Traits\Logger;

/**
 * Handles reset operations for clearing plugin state.
 */
class Reset {

	use Logger;


	const ACTION_NAME = 'tl_reset';

	const NONCE_ACTION = 'tl_reset_nonce';

	/**
	 * Clear ALL data added by Trusted Login
	 *
	 * @param Plugin $plugin Parameter.
	 * @return Plugin
	 */
	public function resetAll( Plugin $plugin ) {
		$plugin->getSettings()->reset()->save();
		Onboarding::reset();
		$plugin->getEncryption()->deleteKeys();

		$logFile = $this->getLogFileName();
		if ( file_exists( $logFile ) ) {
			wp_delete_file( $logFile );
		}

		return $plugin;
	}

	/**
	 * Get URL for resetting all data
	 *
	 * @return string
	 */
	public static function actionUrl() {
		return add_query_arg(
			array(
				'action'   => self::ACTION_NAME,
				'_wpnonce' => wp_create_nonce( self::NONCE_ACTION ),
				'page'     => MenuPage::PARENT_MENU_SLUG,
			),
			admin_url( 'admin.php' )
		);
	}
}

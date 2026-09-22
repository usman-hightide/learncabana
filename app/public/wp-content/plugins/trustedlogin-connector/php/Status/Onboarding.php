<?php
/**
 * Onboarding status tracker.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Status;

/**
 * Tracks and manages onboarding status.
 */
class Onboarding {

	/**
	 * The name of the option we store onboarding status in.
	 */
	const ONBOARDED = 'trustedlogin_has_onboarded';

	/**
	 * Reset onboarding status.
	 *
	 * @return void
	 */
	public static function reset() {
		delete_option( self::ONBOARDED );
	}
	/**
	 * Check whether the install has already been onboarded.
	 *
	 * @return bool
	 */
	public static function hasOnboarded() {
		return '1' === (string) get_option( self::ONBOARDED, 0 );
	}

	/**
	 * Set onboarding status to has onboarded.
	 *
	 * @return void
	 */
	public static function setHasOnboarded() {
		update_option( self::ONBOARDED, 1 );
	}
}

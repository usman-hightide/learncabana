<?php
/**
 * User verification trait for TrustedLogin.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Traits;

use TrustedLogin\Vendor\TeamSettings;

/**
 * Helper trait for verifying users against team settings.
 *
 * @see https://github.com/trustedlogin/trustedlogin-vendor/blob/develop/includes/class-trustedlogin-endpoint.php#L789-L817
 */
trait VerifyUser {

	/**
	 * Helper: Check if the current user can be redirected to the client site
	 *
	 * @param TeamSettings $settings Team settings to check if is approved role.
	 * @return bool
	 */
	public function verifyUserRole( TeamSettings $settings ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$_usr       = get_userdata( get_current_user_id() );
		$user_roles = $_usr->roles;

		if ( ! is_array( $user_roles ) ) {
			return false;
		}

		$required_roles = $settings->get( 'approved_roles' );

		$intersect = array_intersect( $required_roles, $user_roles );

		if ( 0 < count( $intersect ) ) {
			return true;
		}

		return false;
	}
}

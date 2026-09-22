<?php
/**
 * Licensing trait for TrustedLogin Vendor integrations.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Traits;

trait Licensing {

	/**
	 * Map of licensing platform slugs to fully-qualified class names.
	 *
	 * @var array
	 */
	protected $licensing_platforms = array(
		'edd' => 'TrustedLogin\Vendor\Licensing\Platform\Edd',
	);

	/**
	 * Return all currently active licensing platform instances.
	 *
	 * @return array Active platform instances.
	 */
	public function getActivePlatforms() {
		$platforms = array();
		// $licensing_platforms is a map of slug => fully-qualified class name.
		// (strings), not instances. Instantiate each before calling isActive().
		foreach ( $this->licensing_platforms as $platform_class_name ) {
			if ( ! class_exists( $platform_class_name ) ) {
				continue;
			}
			$platform_instance = new $platform_class_name();
			if ( $platform_instance->isActive() ) {
				$platforms[] = $platform_instance;
			}
		}
		return $platforms;
	}

	/**
	 * Whether EDD Software Licensing is available.
	 *
	 * @return bool True if EDD Software Licensing function exists.
	 */
	public function eddHasLicensing() {
		return function_exists( 'edd_software_licensing' );
	}

	/**
	 * Retrieve all EDD license objects for a given customer email.
	 *
	 * @param string $email Customer email address.
	 *
	 * @return array<int, \EDD_SL_License> Array of EDD_SL_License objects, empty if the user is not found.
	 */
	public function eddGetLicenses( $email ) {

		$licenses = array();
		$_u       = get_user_by( 'email', $email );

		if ( $_u ) {
			$licenses = edd_software_licensing()->get_license_keys_of_user( $_u->ID, 0, 'all', true );

			foreach ( $licenses as $license ) {
				$children = edd_software_licensing()->get_child_licenses( $license->ID );
				if ( $children ) {
					foreach ( $children as $child ) {
						$licenses[] = edd_software_licensing()->get_license( $child->ID );
					}
				}

				$licenses[] = edd_software_licensing()->get_license( $license->ID );
			}
		}

		return ( ! empty( $licenses ) ) ? $licenses : false;
	}

	/**
	 * Helper function: Check if the current site is an EDD store
	 *
	 * @since 0.2.0
	 * @return Boolean
	 */
	public function isEddStore() {
		return class_exists( 'Easy_Digital_Downloads' );
	}

	/**
	 * Helper function: Check if the current site is Woocommerce store
	 *
	 * @since 0.8.0
	 * @return Boolean
	 */
	public function isWooStore() {
		return class_exists( 'woocommerce' );
	}
}

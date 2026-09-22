<?php

class WP_AICH_Utils {
    public static function isProActivated() {
		if (class_exists('SureCart\Licensing\Client')) {
			$activation_key = get_option('wpaico-pilotpro_license_options');

			if( $activation_key && count($activation_key) > 0 && isset($activation_key['sc_license_key']) && $activation_key['sc_license_key'] !== '') {
				return true;
			}
		}else {
			global $aich_pro_license;
			if ($aich_pro_license) {
				return $aich_pro_license->is_valid();
			}
		}

		return false;
    }
}
<?php
/**
 * Plugin Name: Learn Cabana Group Province
 * Description: Group-level Province + User Report District/Area Manager filters via supervisor hierarchy (update-safe MU-plugin). Does not touch user profiles. Does not auto-backfill group data.
 * Version: 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LC_GROUP_PROVINCE_DIR', __DIR__ . '/learcabana-group-province' );
define( 'LC_GROUP_PROVINCE_URL', content_url( '/mu-plugins/learcabana-group-province' ) );
define( 'LC_GROUP_PROVINCE_VERSION', '1.2.3' );

require_once LC_GROUP_PROVINCE_DIR . '/province-helpers.php';
require_once LC_GROUP_PROVINCE_DIR . '/manager-helpers.php';
require_once LC_GROUP_PROVINCE_DIR . '/province-reporting.php';
require_once LC_GROUP_PROVINCE_DIR . '/province-backfill.php';

<?php
/**
 * Plugin Name: Better Admin Bar
 * Plugin URI: https://betteradminbar.com/
 * Description: Hide WordPress admin bar, replace it with a better swift control.
 * Version: 4.1.4
 * Author: David Vongries
 * Author URI: https://davidvongries.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: better-admin-bar
 *
 * @package Better_Admin_Bar
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

// Helper constants.
define( 'SWIFT_CONTROL_PLUGIN_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'SWIFT_CONTROL_PLUGIN_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );
define( 'SWIFT_CONTROL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SWIFT_CONTROL_PLUGIN_VERSION', '4.1.4' );

require __DIR__ . '/autoload.php';

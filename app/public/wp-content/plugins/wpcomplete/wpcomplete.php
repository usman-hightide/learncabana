<?php

/**
 * @link              https://wpcomplete.co
 * @since             1.0.0
 * @package           WPComplete
 *
 * @wordpress-plugin
 * Plugin Name:       WPComplete (FREE)
 * Description:       A WordPress plugin that helps your students keep track of their progress through your course or membership site.
 * Version:           2.9.5.6
 * Author:            iThemes
 * Author URI:        https://ithemes.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcomplete
 * Domain Path:       /languages
 * iThemes Package:   wpcomplete
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

// Fix for 5.3. This variable wasn't added until 5.4.
if ( ! defined( 'JSON_UNESCAPED_UNICODE' ) ) {
  define( 'JSON_UNESCAPED_UNICODE', 256 );
}

// Define some variables we will use throughout the plugin:
define( 'WPCOMPLETE_STORE_URL', 'https://wpcomplete.co' );
define( 'WPCOMPLETE_PRODUCT_NAME', 'WPComplete' );
define( 'WPCOMPLETE_PREFIX', 'wpcomplete' );
define( 'WPCOMPLETE_VERSION', '2.9.5.6' );
define( 'WPCOMPLETE_IS_ACTIVATED', false );

function wpcomplete_is_production() {
  return true;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpcomplete-activator.php
 */
function activate_wpcomplete() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpcomplete-activator.php';
  WPComplete_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpcomplete-deactivator.php
 */
function deactivate_wpcomplete() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpcomplete-deactivator.php';
  WPComplete_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpcomplete' );
register_deactivation_hook( __FILE__, 'deactivate_wpcomplete' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpcomplete.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpcomplete() {

  $plugin = new WPComplete();
  $plugin->run();

}
run_wpcomplete();

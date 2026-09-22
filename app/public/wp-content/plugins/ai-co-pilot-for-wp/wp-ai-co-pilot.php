<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpmessiah.com
 * @since             1.0.0
 * @package           Wp_Ai_CoPilot
 *
 * @wordpress-plugin
 * Plugin Name:       WP AI CoPilot - AI content writer plugin, ChatGPT WordPress, GPT-3/4 , Ai assistance
 * Plugin URI:        https://wpaicopilot.com/
 * Description:       AI Content Writing Assistant – A one-click solution that generates high-quality, unique content by utilizing AI (GPT4 , OpenAI).
 * Version:           1.2.8
 * Author:            WP Messiah
 * Author URI:        https://wpmessiah.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-ai-co-pilot
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}


/**
 * Plugin global information..
 */
define( 'WP_AHC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_AHC_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_AHC_SLUG', 'wp-ai-copilot' );
define( 'WP_AHC_SHORT_NAME', 'WP AI CoPilot' );
define( 'WP_AHC_FULL_NAME', 'WP AI CoPilot' );
define( 'WP_AHC_BASE_NAME', plugin_basename( __FILE__ ) );

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AI_CONTENT_HELPER_VERSION', '1.2.8' );


require __DIR__ . '/vendor/autoload.php';



/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_ai_co_pilot_for_wp() {

    if ( ! class_exists( 'Appsero\Client' ) ) {
      require_once __DIR__ . '/appsero/src/Client.php';
    }

    $client = new Appsero\Client( 'fd1359e9-52f6-4ba0-98de-fdd2f2ae2315', 'AI Co-Pilot For WP', __FILE__ );

    // Active insights
    $client->insights()->init();

}

appsero_init_tracker_ai_co_pilot_for_wp();



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ai-content-helper-activator.php
 */
function activate_ai_content_helper() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-ai-content-helper-activator.php';
    Ai_Content_Helper_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ai-content-helper-deactivator.php
 */
function deactivate_ai_content_helper() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-ai-content-helper-deactivator.php';
    Ai_Content_Helper_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ai_content_helper' );
register_deactivation_hook( __FILE__, 'deactivate_ai_content_helper' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ai-content-helper.php';
do_action( 'wp_ai_co_pilot/loaded' );
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

if( ! function_exists( 'validate_api_key' ) ) {
    function validate_api_key( $value ) {
        $api_key = $value;
        $client = new GuzzleHttp\Client(['verify' => false ]);
        try {
            $res = $client->request('GET', 'https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
             return esc_html__( 'This open ai api key is not valid!', 'csf' );
        }

        if ( $res->getStatusCode() !== 200 ) {
             return esc_html__( 'This open ai api key is not valid!', 'csf' );
        }    

        $body = json_decode( $res->getBody(), true );

        if ( ! isset( $body['data'] ) ) {
             return esc_html__( 'This open ai api key is not valid!', 'csf' );
        }
    }
}

function run_ai_content_helper() {
    $plugin = new Ai_Content_Helper();
    $plugin->run();
}

add_action( 'plugins_loaded', 'run_ai_content_helper', 2 );
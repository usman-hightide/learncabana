<?php
/**
 * Plugin Name: Chart Block
 * Description: Represent your data by symbols, such as bars, lines, or pie charts.
 * Version: 1.1.7
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: chart-block
 */


// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }


if ( function_exists( 'chart_block_fs' ) ) {
    chart_block_fs()->set_basename( true, __FILE__ );
} 
else {
    // Constant
    // নতুন কনস্ট্যান্ট (New Prefix)
    define( 'CHART_BLOCK_VERSION',  ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : '1.1.7' ); 
    define( 'CHART_BLOCK_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'CHART_BLOCK_DIR_PATH', plugin_dir_path( __FILE__ ) );

    // পুরনো ব্যবহারকারীদের জন্য ব্যাকআপ (Fallback)
    if ( ! defined( 'BCHART_VERSION' ) ) {
        define( 'BCHART_VERSION', CHART_BLOCK_VERSION );
    }
    if ( ! defined( 'BCHART_DIR_URL' ) ) {
        define( 'BCHART_DIR_URL', CHART_BLOCK_DIR_URL );
    }
    if ( ! defined( 'BCHART_DIR_PATH' ) ) {
        define( 'BCHART_DIR_PATH', CHART_BLOCK_DIR_PATH );
    }

    if ( ! function_exists( 'chart_block_fs' ) ) {
        // Create a helper function for easy SDK access.
        function chart_block_fs() {
           global $chart_block_fs;
 

            if ( ! isset( $chart_block_fs ) ) {
                // Include Freemius SDK.
                require_once BCHART_DIR_PATH . 'vendor/freemius-lite/start.php';

                $chart_block_fs =fs_lite_dynamic_init([
                    'id'                  => '17726',
                    'slug'                => 'chart-block',
                    'premium_slug'        => 'chart-block-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_e8ee503e1b981e8777e10621d3f8a',
                    'is_premium'          => false,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'menu'                => array(
                        'slug'           => 'chart-block',
                        'first-path'     => 'edit.php?post_type=chart_block&page=block-dashboard#/welcome',
                        'support'        => false,
                    ),
                ]);
            }

            return $chart_block_fs;
        }

        // Init Freemius.
        chart_block_fs();
        // Signal that SDK was initiated.
        do_action( 'chart_block_fs_loaded' );
    }
    // ... Your plugin's main file logic ...
    require_once BCHART_DIR_PATH . 'includes/class-chart-block-dependencies.php';
    new ChartBlock_Dependencies();
}


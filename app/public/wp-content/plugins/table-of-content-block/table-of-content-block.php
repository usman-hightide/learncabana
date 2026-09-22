<?php
/**
 * Plugin Name: Table Of Content Block
 * Description: The Table of Contents block automatically generates a table of contents for your WordPress post or page.
 * Version: 1.0.9
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * Requires at least:	6.5
*  Requires PHP: 7.2
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: table-of-content-block
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'tbcnb_fs' ) ) {
	tbcnb_fs()->set_basename( true, __FILE__ );
}else {
	// Constant
	define( 'TBCNB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.9' );
	define( 'TBCNB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'TBCNB_DIR_PATH', plugin_dir_path( __FILE__ ) );

	// Create a helper function for easy SDK access.
	function tbcnb_fs() {
		global $tbcnb_fs;

		if ( ! isset( $tbcnb_fs ) ) {
			require_once dirname( __FILE__ ) . '/vendor/freemius-lite/start.php';
			
			$tbcnb_fs = fs_lite_dynamic_init( array(
				'id'                  => '20172',
				'slug'                => 'table-of-content-block',
				'type'                => 'plugin',
				'public_key'          => 'pk_76ee353e51da337d61c9d64277075',
				'is_premium'          => false,
				'premium_suffix'      => 'Pro',
				// If your plugin is a serviceware, set this option to false.
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'menu'                => array(
					'first-path'     => 'plugins.php',
					'contact'        => false,
					'support'        => false,
				),
			) );
		}

		return $tbcnb_fs;
	}

	tbcnb_fs();
	do_action( 'tbcnb_fs_loaded');

	if( !class_exists( 'TBCNB_Table_Of_Contetn_Block' ) ){
		class TBCNB_Table_Of_Contetn_Block{

			function __construct(){
				add_action( 'init', [ $this, 'onInit' ] );
			}

			function onInit(){
				register_block_type( __DIR__ . '/build' );
			}

		}
		new TBCNB_Table_Of_Contetn_Block();
	}

}
<?php

defined( 'ABSPATH' ) || exit;

class grassblade_user_report_block {

	function __construct() {
		add_action( 'init', array($this, "register_block") );
		add_action( 'enqueue_block_editor_assets', array($this, "block") );
	}

	function block() {

		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		wp_enqueue_script(
		    'grassblade/user-report',
		    plugins_url('/block.js', __FILE__),
		    array('jquery', 'wp-blocks','wp-editor','wp-element', 'wp-i18n'),
		    GRASSBLADE_VERSION
		);

		wp_set_script_translations('grassblade/user-report', 'grassblade', dirname(GRASSBLADE_ADDON_DIR) . '/languages');
	} // end of admin_report_block

	function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}
		register_block_type(__DIR__ . '/block.json', array(
			'render_callback' => array($this, "render"),
		));
	} // end of register_block

	function render( $attributes = array(), $content = "") {
		$className 	= isset($attributes["className"]) ? $attributes["className"] : '';
		$bg_color 	= isset($attributes["bg_color"]) ? $attributes["bg_color"] : '';
		$filter 	= isset($attributes["filter"]) ? $attributes["filter"] : '';

		$grassblade_user_report = new grassblade_user_report();
		$profile = $grassblade_user_report->user_report(array("bg_color" => $bg_color, "filter" => $filter, "class" => $className));

		return $profile;
	} // end of admin_report_block_render_callback
}
new grassblade_user_report_block();
<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wpmessiah.com
 * @since      1.0.0
 *
 * @package    Ai_Content_Helper
 * @subpackage Ai_Content_Helper/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Ai_Content_Helper
 * @subpackage Ai_Content_Helper/includes
 * @author     WP Messiah <contact@wpmessiah.com>
 */
class Ai_Content_Helper_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ai-content-helper',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}
}

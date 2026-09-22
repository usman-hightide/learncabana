<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ChartBlock_Options' ) ) {
	/**
	 * Options class
	 * Handles plugin options and related AJAX requests.
	 *
	 * @package chart-block
	 */
	class ChartBlock_Options {
		/**
		 * Constructor.
		 * Registers options-related hooks.
		 */
		public function __construct() {
			add_action( 'wp_ajax_chart_block_save_uninstall_option', [ $this, 'chart_block_save_uninstall_option' ] );
		}

		/**
		 * Retrieves all plugin options, merged with defaults.
		 * Strictly follows Rule 2 naming consistency using chart_block_ prefix.
		 *
		 * @return array The plugin options.
		 */
		public static function chart_block_get_options() {
			$defaults = [
				'delete_data_on_uninstall' => false,
			];

			$options = get_option( 'chart_block_settings', [] );

			return wp_parse_args( $options, $defaults );
		}

		/**
		 * Updates plugin options.
		 * Strictly follows Rule 2 naming consistency using chart_block_ prefix.
		 *
		 * @param array $new_options The options to update.
		 * @return bool True on success, false on failure.
		 */
		public static function chart_block_update_options( $new_options ) {
			$options = self::chart_block_get_options();
			$updated_options = array_merge( $options, $new_options );
			return update_option( 'chart_block_settings', $updated_options );
		}

		/**
		 * Saves the "delete data on uninstall" option via AJAX.
		 *
		 * @return void
		 */
		public function chart_block_save_uninstall_option() {
			check_ajax_referer( 'chart_block_uninstall_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Permission denied.', 'chart-block' ) );
			}

			$enabled = isset( $_POST['enabled'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );

			// Save to the settings array option
			self::chart_block_update_options( [ 'delete_data_on_uninstall' => $enabled ] );

			wp_send_json_success( [
				'enabled' => $enabled,
				'message' => $enabled
					? __( 'All plugin data will be deleted when uninstalled.', 'chart-block' )
					: __( 'Plugin data will be preserved when uninstalled.', 'chart-block' )
			] );
		}

	}
	new ChartBlock_Options();
}


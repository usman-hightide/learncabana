<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ChartBlock_Admin_Menu' ) ) {

	class ChartBlock_Admin_Menu {

		public function __construct() {
			add_action( 'admin_menu', [ $this, 'chart_block_register_admin_menu' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'chart_block_enqueue_admin_scripts' ] );
		}

		/**
		 * Registers the admin menu.
		 */
		public function chart_block_register_admin_menu() {
             add_submenu_page(
            'edit.php?post_type=chart_block',
            'Help And Demo',
            'Help And Demo',
            'manage_options',
            'block-dashboard',
            [$this, 'chart_block_render_dashboard_page'],
            100
          );
		}

		/**
		 * Dashboard page render.
		 */
		public function chart_block_render_dashboard_page() {
			?>
			<div id='ChartBlockDashboard'
				data-info='<?php echo esc_attr( wp_json_encode( [
            'version' => CHART_BLOCK_VERSION,
            'isPremium' => false,
            'hasPro' => false,
            'adminUrl' => admin_url(), 
            'deleteDataOnUninstall' => ChartBlock_Options::chart_block_get_options()['delete_data_on_uninstall'],
            'uninstallNonce' => wp_create_nonce('chart_block_uninstall_nonce')
				] ) ); ?>'
			>
			</div>
			<?php
		}


		public function chart_block_enqueue_admin_scripts( $screen ) {
			if ( $screen === 'chart_block_page_block-dashboard' ) {
				wp_enqueue_style( 'chart-block-admin-dashboard-style', CHART_BLOCK_DIR_URL . 'build/admin/dashboard.css', [], CHART_BLOCK_VERSION );
				wp_enqueue_script( 'chart-block-admin-dashboard-script', CHART_BLOCK_DIR_URL . 'build/admin/dashboard.js', [ 'wp-element', 'wp-i18n', 'wp-components', 'wp-util' ], CHART_BLOCK_VERSION, true );
			}
		}
	} 
	new ChartBlock_Admin_Menu();
}

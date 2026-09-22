<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ChartBlock_Dependencies' ) ) {
	class ChartBlock_Dependencies {

		public function __construct() {
			$this->chart_block_load_dependencies();
		}

		public function chart_block_load_dependencies() {
			if ( file_exists( CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-options.php' ) ) {
				require_once CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-options.php';
			}
			if ( file_exists( CHART_BLOCK_DIR_PATH . 'chart-block.php' ) ) {
				require_once CHART_BLOCK_DIR_PATH . 'chart-block.php';
			}
			if ( file_exists( CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-admin-menu.php' ) ) {
				require_once CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-admin-menu.php';
			}
			if ( file_exists( CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-cpt.php' ) ) {
				require_once CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-cpt.php';
			}
			if ( file_exists( CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-shortcode.php' ) ) {
				require_once CHART_BLOCK_DIR_PATH . 'includes/class-chart-block-shortcode.php';
			}
		}


	}
	new ChartBlock_Dependencies();
}

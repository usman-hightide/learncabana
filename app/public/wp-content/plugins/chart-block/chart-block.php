<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

if( !class_exists( 'ChartBlock_Plugin' ) ){
	class ChartBlock_Plugin {
		function __construct(){
			add_action( 'init', [$this, 'chart_block_onInit'] );
			add_action( 'enqueue_block_assets', [$this, 'chart_block_enqueueBlockAssets'] );
			add_action( 'enqueue_block_editor_assets', [$this, 'chart_block_enqueueBlockEditorAssets'] );
		}
	
		function chart_block_enqueueBlockAssets(){
			wp_register_script( 'chartJS', CHART_BLOCK_DIR_URL . 'assets/js/chart.min.js', [], '3.5.1', true );
		}
		function chart_block_enqueueBlockEditorAssets(){
			wp_add_inline_script( 'wp-data', sprintf(
					'const chart_block_pricing_url = %s;',
					wp_json_encode( admin_url( 'edit.php?post_type=chart_block&page=block-dashboard#/pricing' ) )
			), 'before' );
		}


		function chart_block_onInit() {
			register_block_type( __DIR__ . '/build' );
		}
	}
	new ChartBlock_Plugin();
}
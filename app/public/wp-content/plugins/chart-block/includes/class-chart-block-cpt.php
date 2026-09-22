<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ChartBlock_CPT' ) ) {

	class ChartBlock_CPT {
		public function __construct() {
			add_action( 'init', [ $this, 'chart_block_register_cpt' ] );
			add_filter( 'manage_chart_block_posts_columns', [ $this, 'chart_block_manage_columns' ], 10 );
			add_action( 'manage_chart_block_posts_custom_column', [ $this, 'chart_block_manage_custom_columns' ], 10, 2 );
			add_action( 'admin_head', [ $this, 'chart_block_hide_view_links' ] );
		}

		/**
		 * Registers the custom post type.
		 */
		public function chart_block_register_cpt() {
			register_post_type( 'chart_block', [
				'labels'             => [
					'name'               => __( 'Chart ', 'chart-block' ),
					'singular_name'      => __( 'Chart ', 'chart-block' ),
					'add_new'            => __( 'Add New ', 'chart-block' ),
					'add_new_item'       => __( 'Add New Chart', 'chart-block' ),
					'edit_item'          => __( 'Edit Chart', 'chart-block' ),
					'not_found'          => __( 'Sorry, we couldn\'t find the Charts you are looking for.', 'chart-block' ),
					'search_items'       => __( 'Search Charts', 'chart-block' ),
					'new_item'           => __( 'New Chart', 'chart-block' ),
					'menu_name'          => __( 'Chart', 'chart-block' ),
					'all_items'          => __( 'All Charts', 'chart-block' ),
					'not_found_in_trash' => __( 'No Chart found in trash', 'chart-block' ),
					'item_updated'       => __( 'Chart updated', 'chart-block' ),
				],
				'public'             => true,
				'publicly_queryable' => false,
				'has_archive'        => false,
				'show_in_rest'       => true,
				'template_lock'      => 'all',
				'menu_icon'          => 'dashicons-chart-line',
				'template'           => [ [ 'b-chart/chart' ] ],
				'show_in_menu'       => true,
			] );
		}

		/**
		 * Manages columns for the CPT list table.
		 *
		 * @param array $defaults The default columns.
		 * @return array
		 */
		public function chart_block_manage_columns( $defaults ) {
			unset( $defaults['date'] );
			$defaults['shortcode'] = __( 'Shortcode', 'chart-block' );
			$defaults['date']      = __( 'Date', 'chart-block' );
			return $defaults;
		}


		public function chart_block_manage_custom_columns( $column_name, $post_id ) {
			if ( 'shortcode' === $column_name ) {
				echo '<div class="chartBlockAdminShortcode" id="chartBlockAdminShortcode-' . esc_attr( $post_id ) . '">
						<input value="[chart_block id=' . esc_attr( $post_id ) . ']"
						onclick="copyChartBlockAdminShortcode(\'' . esc_attr( $post_id ) . '\')" readonly>
						<span class="tooltip">' . esc_html__( 'Copy To Clipboard', 'chart-block' ) . '</span>
					 </div>';
			}
		}

		/**
		 * Hides all "View" links for this CPT.
		 */
		public function chart_block_hide_view_links() {
			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();

			// Only apply on the chart_block post type screen to prevent CSS leakage
			if ( $screen && 'chart_block' === $screen->post_type ) {
				?>
				<style id="chart-block-hide-view-links-css">
					/* Hide "View" link in List Table */
					.post-type-chart_block .row-actions .view,
					/* Hide Preview/View in Gutenberg Header */
					.post-type-chart_block .editor-post-preview,
					.post-type-chart_block .editor-post-view,
					.post-type-chart_block .edit-post-header__settings a[href*="post="],
					/* Hide View link in snackbar notices */
					.post-type-chart_block .components-snackbar__content a,
					/* Hide View submenu item */
					#adminmenu .wp-submenu li a[href*="post_type=chart_block"] + li {
						display: none !important;
					}
				</style>
				<?php
			}
		}
	}
	new ChartBlock_CPT();
}

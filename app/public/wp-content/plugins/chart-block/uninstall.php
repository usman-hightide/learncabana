<?php
/**
 * Uninstall handler for chart_block.
 *
 * Cleans up plugin data when the plugin is deleted from the admin.
 * Only runs if the user has opted in via the "Delete data on uninstall" setting.
 *
 * @package chart_block
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$chartBlockSettings	= get_option( 'chart_block_settings', [] );
$isDeleteData	= isset( $chartBlockSettings['delete_data_on_uninstall'] ) ? $chartBlockSettings['delete_data_on_uninstall'] : false;

if ( ! $isDeleteData ) {
	return;
}

global $wpdb;

// 1. Delete all 'chart_block' custom post type posts and their meta/revisions efficiently.
$chart_block_post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'chart_block' ) );

if ( ! empty( $chart_block_post_ids ) ) {
	foreach ( $chart_block_post_ids as $post_id ) {
		wp_delete_post( $post_id, true ); // Force delete (bypass trash).
	}
}

// 2. Delete post view tracking meta from all posts.
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => 'chart_block_views_count' ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

// 3. Delete plugin options.
delete_option( 'chart_block_settings' );
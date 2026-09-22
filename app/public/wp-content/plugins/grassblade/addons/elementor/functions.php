<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function() {
	if(defined("ELEMENTOR_VERSION")) {
		add_action( 'elementor/widgets/register', function( $widgets_manager ) {
			require_once(dirname(__FILE__).'/widget.php');

			if(method_exists($widgets_manager, "register")) /* Since Elementor v3.5.0 */
			$widgets_manager->register(new \Elementor\xAPI_widget());
			else
			$widgets_manager->register_widget_type(new \Elementor\xAPI_widget());
		});
		add_filter("grassblade_get_content_post_meta_keys", function($keys) {
			if(!in_array('show_xapi_content_elementor', $keys))
			$keys[] = 'show_xapi_content_elementor';
			return $keys;
		}, 10, 1);
	}
} );

add_action('save_post', function($post_id) {
	$post_meta = get_post_meta($post_id);
	if(empty($post_meta) || count($_REQUEST) < 3)
		return;

	if(defined("ELEMENTOR_VERSION")) {
		$instance_documents = Elementor\Plugin::$instance->documents;
		if( !empty( $instance_documents ) ) {
			$document = $instance_documents->get( $post_id );

			if( $document )
			$is_built_with_elementor = $document->is_built_with_elementor();
		}
	}

	if(!defined("ELEMENTOR_VERSION") || empty($is_built_with_elementor) ) {
		delete_post_meta($post_id, "show_xapi_content_elementor");
		delete_post_meta($post_id, "all_xapi_contents_elementor");
	}
} );


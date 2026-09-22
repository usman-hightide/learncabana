<?php

use function FakerPress\register;

if ( ! defined( 'ABSPATH' ) ) exit;

	/*
	*
	* GrassBlade Block Creation Codes - Right Now There are there blocks
	* 1. To add xAPI content - Function - xapi_content_block & JS - xapi-block.js
	* 2. To add LeaderBoard - Function - leaderboard_block & JS - gb-leaderboard.js
	* 3. To add User Score - Function - userscore_block & JS - gb-user-score.js
	*
	*/

class grassblade_gutenberg {

	function __construct() {
		// Register Blocks.
		add_action('init', array($this, 'register_editor_blocks') );
		// Register Assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_editor_assets' ) );

		global $wp_version;
		$block_categories_hook_name = version_compare( $wp_version, '5.8-beta0', '<' )? "block_categories":"block_categories_all";
		add_filter( $block_categories_hook_name , array($this,'block_category'), 10, 2);

		add_action('save_post', array($this, 'update_xapi_content_blocks_meta' ) );

		add_filter("grassblade_get_content_post_meta_keys", array($this, 'add_content_post_meta_key'), 10, 1);
	}

	/**
	 * Register Editor Blocks.
	 *
	 */
	function register_editor_blocks() {
		// Blocks.
		register_block_type(__DIR__ . '/xapi-content');
		register_block_type(__DIR__ . '/leaderboard');
		register_block_type(__DIR__ . '/userscore');
	}

	/**
	 * Register Editor Assets.
	 *
	 */
	function register_editor_assets() {
		$gb_block_data = $this->get_block_data();

		wp_enqueue_script('grassblade-gutenberg-voc', plugins_url('/voc/voc.js', __FILE__), ['wp-edit-post']);

		$gb_block_data = apply_filters("gb_block_data", $gb_block_data);
		wp_localize_script('wp-blocks','gb_block_data', $gb_block_data);

		$include_files = [
			'grassblade/xapi-content' => 'xapi-content/block.js',
			'grassblade/leaderboard' => 'leaderboard/block.js',
			'grassblade/userscore' => 'userscore/block.js',
		];

		foreach ($include_files as $handle => $file) {
			wp_enqueue_script(
				$handle,
				plugins_url($file, __FILE__),
				array('jquery', 'wp-blocks','wp-editor','wp-element', 'wp-i18n'),
				GRASSBLADE_VERSION
			);
			wp_set_script_translations($handle, 'grassblade', dirname(GRASSBLADE_ADDON_DIR) . '/languages');
		}
	}

	function add_content_post_meta_key($keys) {
		if(!in_array('show_xapi_content_blocks', $keys))
		$keys[] = 'show_xapi_content_blocks';
		return $keys;
	}
	function get_block_data(){
		global $wpdb;
		$post_content = array();
		$xapi_contents = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish' ORDER BY post_title ASC");
		//$xapi_contents = get_posts("post_type=gb_xapi_content&orderby=post_title&posts_per_page=-1");

		foreach ($xapi_contents as $xapi_content) {

			$completion_tracking = grassblade_xapi_content::is_completion_tracking_enabled($xapi_content->ID);
			$temp = array(
							'id' => $xapi_content->ID,
						  	'post_title' => $xapi_content->post_title,
						  	'completion_tracking' => $completion_tracking,
						 );

			array_push($post_content,$temp);

		} // end of for each

		global $wp_roles;

	    $all_roles = $wp_roles->roles;
	    $roles = apply_filters('grassblade_block_roles', $all_roles);

		$arrayOfValues = array(
		    'admin_url'     	=> admin_url(),
		    'post_content' 		=> $post_content,
		    'roles'  			=> $roles,
		    'extra_message' 	=> "",
		);

		return $arrayOfValues;
	} // end of get_block_data

	function block_category( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug' => 'grassblade-blocks',
					'title' => __( 'GrassBlade xAPI Companion Blocks', 'grassblade' ),
				),
			)
		);
	} // end of block_category function

	function update_xapi_content_blocks_meta($post_id) {
		$content_post = get_post($post_id);
		delete_post_meta($post_id,  "show_xapi_content_blocks");

		if ( has_blocks( $content_post->post_content ) ) {
		    $blocks = parse_blocks( $content_post->post_content );

		    $this->save_blocks($blocks,$post_id);
		} // end of if
	} // end of update_post_meta_xapicontent

	function save_blocks($blocks,$post_id)
	{
		$new_block = array();
		foreach($blocks as $key => $value)
		{
			if (!empty($value["innerBlocks"]))
			{
				unset($blocks[$key]);
				$new_block[$key] = $this->save_blocks($value["innerBlocks"],$post_id);
			}
			else
			{
				$content_id = (!empty($value["attrs"]) && !empty($value["attrs"]["content_id"]))? $value["attrs"]["content_id"]:"";
				if ($value["blockName"] == 'grassblade/xapi-content') {
					add_post_meta($post_id, 'show_xapi_content_blocks', $content_id);
				} // end of if
				unset($blocks[$key]);
			}
		}

	    return $new_block;
	}
}

if(function_exists( 'register_block_type' ))
new grassblade_gutenberg();
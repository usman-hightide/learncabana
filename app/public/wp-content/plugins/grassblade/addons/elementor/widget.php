<?php

namespace Elementor;
if ( ! defined( 'ABSPATH' ) ) exit;

class xAPI_widget extends \Elementor\Widget_Base {

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );

		add_action( 'elementor/editor/after_save', array($this, "after_save"), 10, 2);
	}

	public function get_name() {
		return 'xAPI Content';
	}

	public function get_title() {
		return 'xAPI Content';
	}

	public function get_icon() {
		return 'dashicon dashicons dashicons-welcome-learn-more';
	}

	public function get_categories() {
		return [ 'basic' ];
	}
	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'video', 'player', 'embed', 'youtube', 'vimeo', 'xapi', 'tincan', 'scorm', 'audio', 'grassblade' ];
	}
	protected function register_controls() {
		global $wpdb;
		$xapi_contents = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish' ORDER BY post_title ASC");
		//$xapi_contents = get_posts("post_type=gb_xapi_content&orderby=post_title&posts_per_page=-1");
		$contents_list = array();

		foreach ($xapi_contents as $xapi_content) {
			$contents_list[$xapi_content->ID] = $xapi_content->post_title;
		}

		//$settings = $this->get_settings();
		//$shortcode = !empty($settings['content_id']) ? "[grassblade id=".$settings['content_id']."]" : '';

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'grassblade' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(

			'content_id',
			[
				'label' => __( 'Select xAPI Content', 'grassblade' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 0,
				'options' => $contents_list,
				'description' => "<a href='#' onClick='grassblade_elementor_open_edit_page(this); return false;'>Click here</a> to edit selected content.",//.$settings['content_id']

			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$shortcode = !empty($settings['content_id']) ? "[grassblade id=".$settings['content_id']."]" : '';
		echo do_shortcode($shortcode);
	}
/*
	protected function _content_template() {
		?>
		<div> [grassblade id={{{ settings.content_id }}}]</div>
		<?php
	}
*/

	function after_save($post_id, $editor_data) {
		global $post;
		$contents = $this->get_contents_on_page($editor_data);
		update_post_meta($post->ID, "all_xapi_contents_elementor", $contents);

		$current_contents = get_post_meta($post->ID, "show_xapi_content_elementor");

		if(is_array($current_contents))
		foreach ($current_contents as $k => $v) {
			if(is_array($v))
			$current_contents[$k] = $v[0];
		}
		else
		$current_contents = array();

		$diff = array_diff($contents, $current_contents);

		if(!empty($diff)) {
			delete_post_meta($post->ID, "show_xapi_content_elementor");
			foreach ($contents as $key => $content_id) {
				add_post_meta($post->ID, "show_xapi_content_elementor", $content_id);
			}
		}
	}

	function get_contents_on_page($editor_data) {
		$contents = array();
		foreach ($editor_data as $element) {
			if( !empty($element["widgetType"]) && !empty($element["settings"]) && !empty($element["settings"]["content_id"]) && $element["widgetType"] == $this->get_name() ) {
				$contents[$element["id"]] = $element["settings"]["content_id"];
			}
			if(!empty($element["elements"]) && count($element["elements"]) > 0)
			{
				$contents = $contents + $this->get_contents_on_page($element["elements"]);
			}
		}
		return $contents;
	}
}

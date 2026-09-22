<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!class_exists('ChartBlock_ShortCode')) {
  class ChartBlock_ShortCode{
    public function __construct(){
      add_shortcode('chart_block', [$this, 'chart_block_shortcode']);
      add_action('admin_enqueue_scripts', [$this, 'chart_block_adminEnqueueScripts']);
    }
    public function chart_block_shortcode($atts) {
      if (isset($atts['id'])) {
          $post = get_post($atts['id']);

          if ($post) {
              $blocks = parse_blocks($post->post_content);
              foreach ($blocks as $block) {
                  if ($block['blockName'] === 'b-chart/chart') { 
                    return render_block($block);
                  }
              }
          } else {
              return 'Post not found or invalid post type.';
          }
      }
    }
    public function chart_block_adminEnqueueScripts( $hook ){
      // global $pagenow;
      if( 'edit.php' === $hook || 'post.php' === $hook ){
        wp_enqueue_style( 'chart-block-admin-post', CHART_BLOCK_DIR_URL . 'build/admin-post.css', [], CHART_BLOCK_VERSION );
        wp_enqueue_script( 'chart-block-admin-post', CHART_BLOCK_DIR_URL . 'build/admin-post.js', [], CHART_BLOCK_VERSION, true );
        wp_set_script_translations( 'chart-block-admin-post', 'chart-block', CHART_BLOCK_DIR_PATH . 'languages' );
      }
	  }
  }
  new ChartBlock_ShortCode();
}
<?php

class AICH_Classic_Editor{
    public function __construct() {
        add_action( 'init', [$this, 'setup_tinymce_plugin'] );
        add_filter('mce_css', [$this, 'aich_classic_mce_css']);
    }

    public function setup_tinymce_plugin() {

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        if ( get_user_option( 'rich_editing' ) !== 'true' ) {
            return;
        }

        add_filter( 'mce_external_plugins', [$this, 'add_tinymce_plugin'] );
        add_filter( 'mce_buttons', [$this, 'add_tinymce_toolbar_button']);
    }

    public function add_tinymce_plugin( $plugin_array ) {

        $plugin_array['aich_classic_plugin'] = plugin_dir_url( __FILE__ ) . '../admin/js/classic.min.js';
        return $plugin_array;

    }

    public function add_tinymce_toolbar_button( $buttons ) {

        array_push( $buttons, '|', 'aich_classic_plugin_text', '|', 'wp_ai_co_pilot_pro_classic_plugin' );
        return $buttons;
    }

    function aich_classic_mce_css($mce_css) {
        if (! empty($mce_css)) {
            $mce_css .= ',';
        }
        $mce_css .= plugins_url('../admin/css/ai-content-helper-admin.css', __FILE__);

        return $mce_css;
    }

}

new AICH_Classic_Editor();
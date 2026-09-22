<?php

require_once(__DIR__ . '/class-aich-openai.php');
require_once(__DIR__ . '/class-aich-settings.php');
require_once(__DIR__ . '/class-ai-content-helper-utils.php');

class AICH_Load_Block_Assets
{
    public function __construct()
    {
        add_action('enqueue_block_assets', [$this, 'block_assets']);
        add_action('admin_enqueue_scripts', [$this, 'block_assets']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'aich_elementor_text_editor_mec_plugin_support']);
    }

    public function block_assets()
    {
        global $pagenow;
        if ($pagenow === 'post-new.php' || $pagenow === 'post.php') {
            require_once(__DIR__ . '/class-ach-promptmanager.php');

            $settings = Wp_AHC_Settings::get_settings();
            
            // Fix for Sensitive Information Exposure
            // Remove sensitive API keys before sending settings to the frontend
            $sensitive_keys = [
                'api_key', 
                'pixabay_api_key', 
                'stability_ai_api_key', 
                'pexels_api_key', 
                'unsplash_api_key'
            ];
            
            $filtered_settings = $settings;
            foreach ($sensitive_keys as $key) {
                if (isset($filtered_settings[$key])) {
                    unset($filtered_settings[$key]);
                }
            }

            $new_prompt = [];

            $prompts_obj = new AICH_Prompt_Manager_Controller();
            $prompts = $prompts_obj->get_prompt();

            $promptsList = [];
            foreach ($prompts as $key => $single_prompt) {
                $promptsList = array_merge($promptsList, $single_prompt['new_prompt']);
            }

            $prompts_lang = $settings['ai_language'];
            $dependencies = require __DIR__ . '/../src/gblock/build/index.asset.php';

            $open_ai_api_is_valid = AICH_Openai::check_api_key_is_valid();
            wp_enqueue_script('wp_ai_sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js', array('jquery'), $dependencies['version'], true);
            wp_register_style('wp_ai_sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css', false, $dependencies['version']);

            wp_enqueue_script('aich_index',  plugin_dir_url(__FILE__) . '../src/gblock/build/index.js', array('jquery'), $dependencies['version'], true);
            wp_register_style('aich_index_css', plugin_dir_url(__FILE__) . '../src/gblock/build/style-index.css', false, $dependencies['version']);
            wp_enqueue_style('aich_index_css');
            $nonce = wp_create_nonce('wp_rest');
            wp_localize_script(
                'aich_index',
                'aich_ajax',
                array(
                    'nonce'  =>  $nonce,
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'prompts' => $prompts,
                    'pro_activated' => WP_AICH_Utils::isProActivated(),
                    'lang' => $prompts_lang,
                    'plugin_url' => plugin_dir_url(__DIR__),
                    'api_key_is_valid' => $open_ai_api_is_valid,
                    'all_prompts' => $promptsList,
                    'aich_settings' => $filtered_settings,
                )
            );
        }
    }

    function aich_elementor_text_editor_mec_plugin_support()
    {
        wp_enqueue_script('wp_ai_sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js', '', '', true);
        wp_register_style('wp_ai_sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css', array(), time(), 'all');

        wp_register_script('elementor_classic_editor_support', plugin_dir_url(__FILE__) . '../admin/js/elementor-classic.min.js', '', '', true);
        wp_enqueue_style('wp_ai_co_pilot_elementor-editor', plugin_dir_url(__FILE__) . '../admin/js/elementor-editor.css', array(), time(), 'all');
        wp_enqueue_script('elementor_classic_editor_support');
        wp_enqueue_script('wp_ai_co_pilot_elementor-editor');

        require_once(__DIR__ . '/class-ach-promptmanager.php');

        $settings = Wp_AHC_Settings::get_settings();

        $prompts_obj = new AICH_Prompt_Manager_Controller();
        $prompts = $prompts_obj->get_prompt();

        $promptsList = [];
        foreach ($prompts as $key => $single_prompt) {
            $promptsList = array_merge($promptsList, $single_prompt['new_prompt']);
        }

        $prompts_lang = $settings['ai_language'];

        $open_ai_api_is_valid = AICH_Openai::check_api_key_is_valid();

        $nonce = wp_create_nonce('wp_rest');

        wp_localize_script(
            'elementor_classic_editor_support',
            'aich_ajax',
            array(
                'nonce'  =>  $nonce,
                'ajaxurl' => admin_url('admin-ajax.php'),
                'prompts' => $prompts,
                'pro_activated' => WP_AICH_Utils::isProActivated(),
                'lang' => $prompts_lang,
                'plugin_url' => plugin_dir_url(__DIR__),
                'api_key_is_valid' => $open_ai_api_is_valid,
                'all_prompts' => $promptsList
            )
        );
    }
}
new AICH_Load_Block_Assets();

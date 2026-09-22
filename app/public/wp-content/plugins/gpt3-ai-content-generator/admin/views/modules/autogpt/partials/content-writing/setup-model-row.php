<?php
/**
 * Content Writing setup panel wrapper.
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_autogpt_setup_config = [
    'scope' => 'cw',
    'name_prefix' => '',
    'model_helper' => __('More varied writing.', 'gpt3-ai-content-generator'),
    'reasoning_helper' => __('More effort for hard tasks.', 'gpt3-ai-content-generator'),
    'has_length' => true,
    'default_length' => 'medium',
    'prompt_label' => __('Prompts', 'gpt3-ai-content-generator'),
    'prompt_mode' => 'popover',
    'prompt_target' => 'aipkit_autogpt_cw_prompt_settings_popover',
    'prompt_include' => __DIR__ . '/prompts-settings.php',
    'prompt_popover_title' => __('Prompts', 'gpt3-ai-content-generator'),
    'prompt_show_back_button' => true,
    'prompt_track_title' => true,
    'prompt_root_attrs' => [
        'data-aipkit-popover-default-view' => 'root',
        'data-aipkit-popover-active-view' => 'root',
    ],
    'extra_rows_include' => __DIR__ . '/smart-seo-settings.php',
];

include dirname(__DIR__) . '/shared/setup-panel.php';

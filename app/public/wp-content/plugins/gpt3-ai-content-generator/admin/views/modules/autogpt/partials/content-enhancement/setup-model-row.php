<?php
/**
 * Content enhancement setup panel wrapper.
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_autogpt_setup_config = [
    'scope' => 'ce',
    'name_prefix' => 'ce_',
    'model_helper' => __('More controlled or varied rewrites.', 'gpt3-ai-content-generator'),
    'max_tokens_helper' => __('Limit rewrite output size.', 'gpt3-ai-content-generator'),
    'reasoning_helper' => __('More effort for harder rewrites.', 'gpt3-ai-content-generator'),
    'has_max_tokens' => true,
    'default_max_tokens' => isset($cw_default_max_tokens) ? $cw_default_max_tokens : '4000',
    'prompt_label' => __('Prompts', 'gpt3-ai-content-generator'),
    'prompt_mode' => 'popover',
    'prompt_target' => 'aipkit_autogpt_ce_prompt_settings_popover',
    'prompt_include' => __DIR__ . '/prompts-settings.php',
    'prompt_popover_title' => __('Prompts', 'gpt3-ai-content-generator'),
    'prompt_stage_id' => 'aipkit_task_config_enhancement_ai_and_prompts_main',
    'prompt_show_back_button' => true,
    'prompt_track_title' => true,
    'prompt_root_attrs' => [
        'data-aipkit-popover-default-view' => 'root',
        'data-aipkit-popover-active-view' => 'root',
    ],
];

include dirname(__DIR__) . '/shared/setup-panel.php';

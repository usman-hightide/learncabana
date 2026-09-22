<?php
/**
 * Comment replies setup panel wrapper.
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_autogpt_setup_config = [
    'scope' => 'cc',
    'name_prefix' => 'cc_',
    'model_helper' => __('More controlled or varied replies.', 'gpt3-ai-content-generator'),
    'max_tokens_helper' => __('Limit reply length.', 'gpt3-ai-content-generator'),
    'reasoning_helper' => __('More effort for more nuanced replies.', 'gpt3-ai-content-generator'),
    'has_max_tokens' => true,
    'default_max_tokens' => isset($cw_default_max_tokens) ? $cw_default_max_tokens : '4000',
    'prompt_label' => __('Prompt', 'gpt3-ai-content-generator'),
    'prompt_mode' => 'flyout',
    'prompt_target' => 'aipkit_task_cc_reply_prompt_flyout',
    'prompt_include' => __DIR__ . '/prompts-settings.php',
];

include dirname(__DIR__) . '/shared/setup-panel.php';

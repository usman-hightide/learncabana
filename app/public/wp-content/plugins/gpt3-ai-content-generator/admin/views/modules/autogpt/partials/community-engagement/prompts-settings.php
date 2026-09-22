<?php
/**
 * Partial: Community Engagement Automated Task - Prompt Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_prompt_items = \WPAICG\AutoGPT\Helpers\AIPKit_AutoGPT_Prompt_Definitions::get_comment_reply_prompt_items();

$aipkit_prompts_render_list = false;

include __DIR__ . '/../shared/prompts-popover-body.php';

// Prevent this flag from leaking into subsequent includes.
unset($aipkit_prompts_render_list);

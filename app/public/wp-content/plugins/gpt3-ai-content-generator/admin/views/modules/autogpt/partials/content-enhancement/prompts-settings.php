<?php
/**
 * Partial: Content Enhancement Automated Task - Prompt Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_prompt_items = \WPAICG\AutoGPT\Helpers\AIPKit_AutoGPT_Prompt_Definitions::get_content_enhancement_prompt_items();

include WPAICG_PLUGIN_DIR . 'admin/views/modules/autogpt/partials/shared/prompts-popover-body.php';

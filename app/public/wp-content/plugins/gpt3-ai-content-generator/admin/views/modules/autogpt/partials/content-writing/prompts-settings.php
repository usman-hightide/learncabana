<?php
/**
 * Partial: Content Writing Automated Task - Prompt Settings (Redesigned)
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;

$aipkit_prompt_items = AIPKit_Content_Writer_Prompts::get_autogpt_content_writing_prompt_items();

include __DIR__ . '/../shared/prompts-popover-body.php';

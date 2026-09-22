<?php

/**
 * Partial: Community Engagement Automated Task - Source Settings
 * This is included in the main "Setup" step of the wizard.
 *
 * @since 2.2.0
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.
// Variables from parent: $all_selectable_post_types

$reply_actions = [
    'approve' => __('Approve Immediately', 'gpt3-ai-content-generator'),
    'hold' => __('Hold for Moderation', 'gpt3-ai-content-generator'),
];
?>
<div id="aipkit_task_cc_source_settings" class="aipkit_cc_source_panel">
    <div class="aipkit_cw_source_mode_header aipkit_cc_source_header">
        <h3 class="aipkit_cw_source_mode_title"><?php esc_html_e('Comment Replies', 'gpt3-ai-content-generator'); ?></h3>
        <p class="aipkit_cw_source_mode_desc"><?php esc_html_e('Choose where this task should monitor comments and prepare replies.', 'gpt3-ai-content-generator'); ?></p>
    </div>

    <div class="aipkit_cc_source_grid">
        <section class="aipkit_cc_card aipkit_cc_card--sources">
            <div class="aipkit_cc_card_header">
                <h4 class="aipkit_cc_card_title"><?php esc_html_e('Content Types', 'gpt3-ai-content-generator'); ?></h4>
                <p class="aipkit_cc_card_desc"><?php esc_html_e('Only comments on these post types will be considered for automated replies.', 'gpt3-ai-content-generator'); ?></p>
            </div>
            <label class="screen-reader-text" for="aipkit_task_comment_reply_post_types"><?php esc_html_e('Post Types to Monitor', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_comment_reply_post_types" name="post_types_for_comments[]" class="aipkit_form-input aipkit_cc_multi_select" multiple size="5">
                <?php foreach ($all_selectable_post_types as $slug => $pt_obj): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($pt_obj->label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="aipkit_cc_card_help"><?php esc_html_e('Use Ctrl/Cmd + click to choose multiple entries.', 'gpt3-ai-content-generator'); ?></p>
        </section>

        <section class="aipkit_cc_card aipkit_cc_card--behavior">
            <div class="aipkit_cc_card_header">
                <h4 class="aipkit_cc_card_title"><?php esc_html_e('Reply Behavior', 'gpt3-ai-content-generator'); ?></h4>
                <p class="aipkit_cc_card_desc"><?php esc_html_e('Choose how generated replies should be submitted.', 'gpt3-ai-content-generator'); ?></p>
            </div>

            <div class="aipkit_cc_field_group">
                <label class="aipkit_cc_field_label" for="aipkit_task_comment_reply_action"><?php esc_html_e('Action on Reply', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipkit_task_comment_reply_action" name="reply_action" class="aipkit_form-input aipkit_cc_select">
                    <?php foreach ($reply_actions as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, 'approve'); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="aipkit_cc_card_help"><?php esc_html_e('Use moderation when you want human review before replies are published.', 'gpt3-ai-content-generator'); ?></p>
            </div>

            <div class="aipkit_cc_toggle_card">
                <label class="aipkit_cc_toggle" for="aipkit_task_comment_reply_no_replies">
                    <input type="checkbox" id="aipkit_task_comment_reply_no_replies" name="no_reply_to_replies" value="1" checked>
                    <span class="aipkit_cc_toggle_copy">
                        <span class="aipkit_cc_toggle_title"><?php esc_html_e('Do not reply to other replies', 'gpt3-ai-content-generator'); ?></span>
                        <span class="aipkit_cc_toggle_desc"><?php esc_html_e('Limit automation to top-level comments and skip conversations that are already threaded.', 'gpt3-ai-content-generator'); ?></span>
                    </span>
                </label>
            </div>
        </section>
    </div>
</div>

<?php

/**
 * Partial: Community Engagement Automated Task - Comment Reply Settings
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="aipkit_task_config_comment_reply_settings">
    <section class="aipkit_cc_card aipkit_cc_card--filters">
        <div class="aipkit_cc_card_header">
            <h4 class="aipkit_cc_card_title"><?php esc_html_e('Comment Filters', 'gpt3-ai-content-generator'); ?></h4>
            <p class="aipkit_cc_card_desc"><?php esc_html_e('Use keyword rules to narrow which comments the task should answer.', 'gpt3-ai-content-generator'); ?></p>
        </div>

        <div class="aipkit_cc_filter_grid">
            <div class="aipkit_cc_field_group">
                <label class="aipkit_cc_field_label" for="aipkit_task_comment_reply_include_keywords"><?php esc_html_e('Only reply if comment contains', 'gpt3-ai-content-generator'); ?></label>
                <textarea id="aipkit_task_comment_reply_include_keywords" name="include_keywords" class="aipkit_form-input aipkit_cc_textarea" rows="3" placeholder="<?php esc_attr_e('e.g., question, help, how to', 'gpt3-ai-content-generator'); ?>"></textarea>
                <p class="aipkit_cc_card_help"><?php esc_html_e('Comma-separated. The task replies only if a comment contains at least one of these words or phrases.', 'gpt3-ai-content-generator'); ?></p>
            </div>

            <div class="aipkit_cc_field_group">
                <label class="aipkit_cc_field_label" for="aipkit_task_comment_reply_exclude_keywords"><?php esc_html_e('Do not reply if comment contains', 'gpt3-ai-content-generator'); ?></label>
                <textarea id="aipkit_task_comment_reply_exclude_keywords" name="exclude_keywords" class="aipkit_form-input aipkit_cc_textarea" rows="3" placeholder="<?php esc_attr_e('e.g., spam, offer, http', 'gpt3-ai-content-generator'); ?>"></textarea>
                <p class="aipkit_cc_card_help"><?php esc_html_e('Comma-separated. The task skips comments containing any of these words or phrases.', 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </section>
</div>

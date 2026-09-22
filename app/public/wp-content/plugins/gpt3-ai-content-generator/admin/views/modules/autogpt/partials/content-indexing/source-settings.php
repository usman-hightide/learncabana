<?php

/**
 * Partial: Content Indexing Automated Task - Source Settings
 * @since 2.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.
// Variables from parent: $all_selectable_post_types
?>
<div id="aipkit_task_ci_source_settings" class="aipkit_ci_source_panel">
    <div class="aipkit_cw_source_mode_header aipkit_ci_source_header">
        <h3 class="aipkit_cw_source_mode_title"><?php esc_html_e('Content Indexing', 'gpt3-ai-content-generator'); ?></h3>
        <p class="aipkit_cw_source_mode_desc"><?php esc_html_e('Choose what content should be indexed and where those embeddings should be stored.', 'gpt3-ai-content-generator'); ?></p>
    </div>

    <div class="aipkit_ci_source_grid">
        <section class="aipkit_ci_card aipkit_ci_card--destination">
            <div class="aipkit_ci_card_header">
                <h4 class="aipkit_ci_card_title"><?php esc_html_e('Destination', 'gpt3-ai-content-generator'); ?></h4>
                <p class="aipkit_ci_card_desc"><?php esc_html_e('Pick the target vector index for indexed content.', 'gpt3-ai-content-generator'); ?></p>
            </div>
            <?php include __DIR__ . '/knowledge-base-settings.php'; ?>
        </section>

        <section class="aipkit_ci_card aipkit_ci_card--scope">
            <div class="aipkit_ci_card_header">
                <h4 class="aipkit_ci_card_title"><?php esc_html_e('Content Types', 'gpt3-ai-content-generator'); ?></h4>
                <p class="aipkit_ci_card_desc"><?php esc_html_e('Select post types to index.', 'gpt3-ai-content-generator'); ?></p>
            </div>
            <label class="screen-reader-text" for="aipkit_task_content_indexing_post_types"><?php esc_html_e('Post Types to Index', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_content_indexing_post_types" name="post_types[]" class="aipkit_form-input aipkit_ci_multi_select" multiple size="5">
                <?php foreach ($all_selectable_post_types as $slug => $pt_obj): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($pt_obj->label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="aipkit_ci_card_help"><?php esc_html_e('Use Ctrl/Cmd + click to choose multiple.', 'gpt3-ai-content-generator'); ?></p>
        </section>
    </div>

    <section class="aipkit_ci_card aipkit_ci_card--behavior">
        <div class="aipkit_ci_card_header">
            <h4 class="aipkit_ci_card_title"><?php esc_html_e('Indexing Behavior', 'gpt3-ai-content-generator'); ?></h4>
            <p class="aipkit_ci_card_desc"><?php esc_html_e('Control whether this task builds the initial index, keeps it fresh, or does both.', 'gpt3-ai-content-generator'); ?></p>
        </div>

        <div class="aipkit_ci_option_list">
            <div class="aipkit_ci_option_card">
                <label class="aipkit_ci_option_toggle" for="aipkit_task_content_indexing_index_existing">
                    <input type="checkbox" name="index_existing_now_flag" id="aipkit_task_content_indexing_index_existing" value="1" checked>
                    <span class="aipkit_ci_option_copy">
                        <span class="aipkit_ci_option_title"><?php esc_html_e('Queue all existing content now', 'gpt3-ai-content-generator'); ?></span>
                        <span class="aipkit_ci_option_desc"><?php esc_html_e('Use this one-time pass to build the initial knowledge base from existing content.', 'gpt3-ai-content-generator'); ?></span>
                    </span>
                </label>
                <p class="aipkit_ci_option_help"><?php esc_html_e('After the initial run finishes, this option is automatically disabled for the task.', 'gpt3-ai-content-generator'); ?></p>
            </div>

            <div class="aipkit_ci_option_card">
                <label class="aipkit_ci_option_toggle" for="aipkit_task_content_indexing_only_new_updated">
                    <input type="checkbox" name="only_new_updated_flag" id="aipkit_task_content_indexing_only_new_updated" value="1">
                    <span class="aipkit_ci_option_copy">
                        <span class="aipkit_ci_option_title"><?php esc_html_e('Auto-index new and updated content', 'gpt3-ai-content-generator'); ?></span>
                        <span class="aipkit_ci_option_desc"><?php esc_html_e('Keeps the destination in sync as content is published or updated later.', 'gpt3-ai-content-generator'); ?></span>
                    </span>
                </label>
                <p class="aipkit_ci_option_help"><?php esc_html_e('Use this with the schedule on the right when you want indexing to run continuously.', 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </section>
</div>

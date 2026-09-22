<?php
/**
 * Partial: Content Writing Automated Task - Manual Entry
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$aipkit_render_autogpt_bulk_row = static function () use ($cw_available_post_types, $cw_users_for_author, $cw_wp_categories) {
    ?>
    <div class="aipkit_cw_bulk_row" data-aipkit-bulk-row>
        <div class="aipkit_cw_bulk_row_main">
            <label class="aipkit_cw_bulk_field aipkit_cw_bulk_field--topic">
                <input
                    type="text"
                    class="aipkit_form-input aipkit_cw_bulk_input aipkit_cw_bulk_input--topic"
                    data-bulk-field="topic"
                    placeholder="<?php esc_attr_e('Enter topic...', 'gpt3-ai-content-generator'); ?>"
                    aria-label="<?php esc_attr_e('Topic', 'gpt3-ai-content-generator'); ?>"
                >
            </label>
            <label class="aipkit_cw_bulk_field aipkit_cw_bulk_field--keywords-inline">
                <input
                    type="text"
                    class="aipkit_form-input aipkit_cw_bulk_input aipkit_cw_bulk_input--keywords-inline"
                    data-bulk-field="keywords"
                    placeholder="<?php esc_attr_e('Enter keywords', 'gpt3-ai-content-generator'); ?>"
                    aria-label="<?php esc_attr_e('Keywords', 'gpt3-ai-content-generator'); ?>"
                >
            </label>
            <div class="aipkit_cw_bulk_row_actions">
                <button
                    type="button"
                    class="aipkit_cw_bulk_toggle_row_details"
                    aria-expanded="false"
                    aria-label="<?php esc_attr_e('Advanced fields', 'gpt3-ai-content-generator'); ?>"
                    title="<?php esc_attr_e('Advanced fields', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                </button>
                <button
                    type="button"
                    class="aipkit_cw_bulk_remove_row"
                    aria-label="<?php esc_attr_e('Remove row', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="aipkit_cw_bulk_row_details">
            <label class="aipkit_cw_bulk_detail_field">
                <span class="aipkit_cw_bulk_detail_label"><?php esc_html_e('Category', 'gpt3-ai-content-generator'); ?></span>
                <select
                    class="aipkit_form-input aipkit_cw_bulk_input aipkit_cw_bulk_detail"
                    data-bulk-field="category"
                    aria-label="<?php esc_attr_e('Category', 'gpt3-ai-content-generator'); ?>"
                >
                    <option value=""><?php esc_html_e('Category', 'gpt3-ai-content-generator'); ?></option>
                    <?php foreach ($cw_wp_categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="aipkit_cw_bulk_detail_field">
                <span class="aipkit_cw_bulk_detail_label"><?php esc_html_e('Author', 'gpt3-ai-content-generator'); ?></span>
                <select
                    class="aipkit_form-input aipkit_cw_bulk_input aipkit_cw_bulk_detail"
                    data-bulk-field="author"
                    aria-label="<?php esc_attr_e('Author', 'gpt3-ai-content-generator'); ?>"
                >
                    <option value=""><?php esc_html_e('Author', 'gpt3-ai-content-generator'); ?></option>
                    <?php foreach ($cw_users_for_author as $user) : ?>
                        <option value="<?php echo esc_attr($user->user_login ?? ''); ?>" data-user-id="<?php echo esc_attr($user->ID); ?>">
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="aipkit_cw_bulk_detail_field">
                <span class="aipkit_cw_bulk_detail_label"><?php esc_html_e('Post Type', 'gpt3-ai-content-generator'); ?></span>
                <select
                    class="aipkit_form-input aipkit_cw_bulk_input aipkit_cw_bulk_detail"
                    data-bulk-field="type"
                    aria-label="<?php esc_attr_e('Post Type', 'gpt3-ai-content-generator'); ?>"
                >
                    <option value=""><?php esc_html_e('Post', 'gpt3-ai-content-generator'); ?></option>
                    <?php foreach ($cw_available_post_types as $pt_slug => $pt_obj) : ?>
                        <option value="<?php echo esc_attr($pt_slug); ?>"><?php echo esc_html($pt_obj->label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="aipkit_cw_bulk_detail_field">
                <span class="aipkit_cw_bulk_detail_label"><?php esc_html_e('Schedule', 'gpt3-ai-content-generator'); ?></span>
                <input
                    type="datetime-local"
                    class="aipkit_form-input aipkit_cw_bulk_input aipkit_cw_bulk_detail"
                    data-bulk-field="schedule"
                    aria-label="<?php esc_attr_e('Schedule', 'gpt3-ai-content-generator'); ?>"
                >
            </label>
        </div>
    </div>
    <?php
};
?>
<div id="aipkit_task_cw_input_mode_bulk" class="aipkit_task_cw_input_mode_section">
    <div class="aipkit_cw_task_entry_shell" data-task-entry-view="batch">
        <div class="aipkit_cw_task_entry_header">
            <div class="aipkit_cw_task_entry_top">
                <h3 class="aipkit_cw_task_entry_title"><?php esc_html_e('Task Input', 'gpt3-ai-content-generator'); ?></h3>
                <div class="aipkit_cw_task_entry_switch" role="group" aria-label="<?php esc_attr_e('Manual entry layout', 'gpt3-ai-content-generator'); ?>">
                    <button type="button" class="aipkit_cw_task_entry_switch_btn is-active" data-aipkit-task-entry-tab="batch" aria-pressed="true">
                        <?php esc_html_e('Batch Editor', 'gpt3-ai-content-generator'); ?>
                        <span class="aipkit_cw_task_entry_switch_count" data-aipkit-task-entry-batch-count hidden>0</span>
                    </button>
                    <button type="button" class="aipkit_cw_task_entry_switch_btn" data-aipkit-task-entry-tab="paste" aria-pressed="false">
                        <?php esc_html_e('Quick Paste', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>
            </div>
            <p class="aipkit_cw_task_entry_desc" data-aipkit-task-entry-mode-desc>
                <?php esc_html_e('Build a queue row by row and fine-tune each item before the task runs.', 'gpt3-ai-content-generator'); ?>
            </p>
        </div>

        <div class="aipkit_cw_task_entry_panel aipkit_cw_task_entry_panel--batch" data-aipkit-task-entry-panel="batch">
            <div class="aipkit_cw_bulk_editor" data-aipkit-bulk-editor>
                <div class="aipkit_cw_bulk_rows" data-aipkit-bulk-rows>
                    <?php for ($i = 0; $i < 3; $i++) : ?>
                        <?php $aipkit_render_autogpt_bulk_row(); ?>
                    <?php endfor; ?>
                </div>
                <template id="aipkit_task_cw_bulk_row_template">
                    <?php $aipkit_render_autogpt_bulk_row(); ?>
                </template>
            </div>
        </div>

        <div class="aipkit_cw_task_entry_panel aipkit_cw_task_entry_panel--paste" data-aipkit-task-entry-panel="paste" hidden>
            <div class="aipkit_cw_paste_panel">
                <div class="aipkit_cw_paste_field aipkit_cw_single_compose_field">
                    <label class="aipkit_cw_single_compose_label" for="aipkit_task_cw_content_title_bulk"><?php esc_html_e('Topics', 'gpt3-ai-content-generator'); ?></label>
                    <textarea
                        id="aipkit_task_cw_content_title_bulk"
                        name="content_title_bulk"
                        class="aipkit_form-input aipkit_cw_paste_textarea"
                        rows="6"
                        placeholder="<?php esc_attr_e('How to frost cupcakes | frosting, dessert', 'gpt3-ai-content-generator'); ?>"
                    ></textarea>
                </div>
                <div class="aipkit_cw_paste_footer">
                    <div class="aipkit_csv_help_content">
                        <div class="aipkit_csv_columns_row">
                            <div class="aipkit_csv_column_chip">
                                <span class="aipkit_csv_column_label"><?php esc_html_e('Topic', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <span class="aipkit_csv_column_divider">→</span>
                            <div class="aipkit_csv_column_chip">
                                <span class="aipkit_csv_column_label"><?php esc_html_e('Keywords', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <span class="aipkit_csv_column_divider">→</span>
                            <div class="aipkit_csv_column_chip">
                                <span class="aipkit_csv_column_label"><?php esc_html_e('Category ID', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <span class="aipkit_csv_column_divider">→</span>
                            <div class="aipkit_csv_column_chip">
                                <span class="aipkit_csv_column_label"><?php esc_html_e('Author Login', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <span class="aipkit_csv_column_divider">→</span>
                            <div class="aipkit_csv_column_chip">
                                <span class="aipkit_csv_column_label"><?php esc_html_e('Post Type', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <span class="aipkit_csv_column_divider">→</span>
                            <div class="aipkit_csv_column_chip">
                                <span class="aipkit_csv_column_label"><?php esc_html_e('Schedule', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

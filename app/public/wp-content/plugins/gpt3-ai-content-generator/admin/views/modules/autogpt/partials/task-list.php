<?php
/**
 * Partial: Automated Task List
 * Displays the table of existing automated tasks.
 * REDESIGNED: Simplified 6-column layout following philosophy principles
 * - Reduced choice overload by consolidating timing columns
 * - Better chunking with cleaner visual hierarchy
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="aipkit_automated_task_list_wrapper">
    <div class="aipkit_container-header">
        <div class="aipkit_container-header-left">
            <div class="aipkit_autogpt_header_copy">
                <div class="aipkit_autogpt_header_title_row">
                    <div class="aipkit_container-title"><?php esc_html_e('Tasks', 'gpt3-ai-content-generator'); ?></div>
                </div>
            </div>
        </div>
        <div class="aipkit_container-actions aipkit_autogpt_overview_header_actions">
            <button id="aipkit_add_new_task_btn" class="aipkit_btn aipkit_btn-primary">
                <?php esc_html_e('New Task', 'gpt3-ai-content-generator'); ?>
            </button>
        </div>
    </div>

    <div class="aipkit_autogpt_overview_body">
        <div class="aipkit_data-table aipkit_autogpt_tasks_table">
            <table>
                <colgroup class="aipkit_autogpt_tasks_columns">
                    <col class="aipkit_autogpt_tasks_col aipkit_autogpt_tasks_col--name">
                    <col class="aipkit_autogpt_tasks_col aipkit_autogpt_tasks_col--type">
                    <col class="aipkit_autogpt_tasks_col aipkit_autogpt_tasks_col--frequency">
                    <col class="aipkit_autogpt_tasks_col aipkit_autogpt_tasks_col--status">
                    <col class="aipkit_autogpt_tasks_col aipkit_autogpt_tasks_col--schedule">
                    <col class="aipkit_autogpt_tasks_col aipkit_autogpt_tasks_col--actions">
                </colgroup>
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Type', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Frequency', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Schedule', 'gpt3-ai-content-generator'); ?></th>
                        <th class="aipkit_actions_cell_header"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                    </tr>
                </thead>
                <tbody id="aipkit_automated_tasks_tbody">
                    <tr><td colspan="6" class="aipkit_text-center"><?php esc_html_e('Loading...', 'gpt3-ai-content-generator'); ?></td></tr>
                </tbody>
            </table>
        </div>
        <div id="aipkit_automated_task_list_pagination" class="aipkit_pagination"></div>
    </div>
</div>

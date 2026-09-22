<?php
/**
 * Partial: Automated Task Queue Viewer
 * Displays items currently in the task queue.
 * REDESIGNED: Simplified 5-column layout following philosophy principles
 * - Removed Attempts column (edge case info, visible in status if failed)
 * - Removed Type column (low-signal, already implied by task)
 * - Combined timing info for better chunking
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_cron_state = !empty($aipkit_autogpt_cron_summary['state']) ? (string) $aipkit_autogpt_cron_summary['state'] : 'enabled';
$aipkit_cron_chip_status = __('OK', 'gpt3-ai-content-generator');

if ($aipkit_cron_state === 'disabled') {
    $aipkit_cron_chip_status = __('Off', 'gpt3-ai-content-generator');
} elseif ($aipkit_cron_state === 'overdue') {
    $aipkit_cron_chip_status = __('Delayed', 'gpt3-ai-content-generator');
}
?>
<div id="aipkit_automated_task_queue_wrapper">
        <div class="aipkit_container-header">
        <div class="aipkit_container-header-left">
            <div class="aipkit_autogpt_header_copy">
                <div class="aipkit_autogpt_header_title_row">
                    <div class="aipkit_container-title"><?php esc_html_e('Queue', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <div id="aipkit_autogpt_queue_header_summary" class="aipkit_queue_header_summary" aria-live="polite"></div>
            </div>
        </div>
        <div class="aipkit_filter_group aipkit_sources_toolbar_group aipkit_autogpt_overview_actions">
            <details
                class="aipkit_autogpt_cron_menu"
                id="aipkit_autogpt_cron_info"
                data-cron-state="<?php echo esc_attr($aipkit_cron_state); ?>"
            >
                <summary
                    class="aipkit_autogpt_header_tool aipkit_autogpt_header_tool--cron aipkit_autogpt_cron_chip"
                    id="aipkit_autogpt_cron_info_trigger"
                    aria-controls="aipkit_autogpt_cron_status"
                    aria-label="<?php esc_attr_e('Cron Status', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="aipkit_autogpt_cron_chip_indicator" aria-hidden="true"></span>
                    <span class="aipkit_autogpt_cron_chip_label">
                        <span class="aipkit_autogpt_cron_chip_text"><?php esc_html_e('Cron', 'gpt3-ai-content-generator'); ?></span>
                    </span>
                    <span class="aipkit_autogpt_cron_chip_status"><?php echo esc_html($aipkit_cron_chip_status); ?></span>
                </summary>
                <div id="aipkit_autogpt_cron_status" class="aipkit_autogpt_cron_status">
                    <?php include __DIR__ . '/settings-popover.php'; ?>
                </div>
            </details>
            <button
                id="aipkit_refresh_task_queue_btn"
                class="aipkit_btn aipkit_btn-secondary aipkit_autogpt_header_tool aipkit_autogpt_header_tool--icon"
                type="button"
                title="<?php esc_attr_e('Refresh Queue', 'gpt3-ai-content-generator'); ?>"
                aria-label="<?php esc_attr_e('Refresh Queue', 'gpt3-ai-content-generator'); ?>"
            >
                <span class="dashicons dashicons-update-alt" aria-hidden="true"></span>
                <span class="aipkit_spinner" style="display:none;"></span>
            </button>
            <details class="aipkit_autogpt_queue_tools_menu" id="aipkit_autogpt_queue_tools_menu">
                <summary
                    class="aipkit_autogpt_header_tool aipkit_autogpt_header_tool--icon aipkit_autogpt_queue_tools_toggle"
                    aria-label="<?php esc_attr_e('Queue Tools', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="dashicons dashicons-ellipsis" aria-hidden="true"></span>
                </summary>
                <div class="aipkit_autogpt_queue_tools_panel">
                    <div class="aipkit_autogpt_queue_tools_section">
                        <label class="aipkit_autogpt_queue_tools_label" for="aipkit_task_queue_search_input"><?php esc_html_e('Search', 'gpt3-ai-content-generator'); ?></label>
                        <input type="search" id="aipkit_task_queue_search_input" class="aipkit_sources_search_input" placeholder="<?php esc_attr_e('Search queue...', 'gpt3-ai-content-generator'); ?>">
                    </div>
                    <div class="aipkit_autogpt_queue_tools_section">
                        <label class="aipkit_autogpt_queue_tools_label" for="aipkit_task_queue_status_filter"><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></label>
                        <select id="aipkit_task_queue_status_filter" class="aipkit_sources_filter_select">
                            <option value="all"><?php esc_html_e('All Statuses', 'gpt3-ai-content-generator'); ?></option>
                            <option value="pending"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></option>
                            <option value="processing"><?php esc_html_e('Processing', 'gpt3-ai-content-generator'); ?></option>
                            <option value="completed"><?php esc_html_e('Completed', 'gpt3-ai-content-generator'); ?></option>
                            <option value="failed"><?php esc_html_e('Failed', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <div class="aipkit_autogpt_queue_tools_actions">
                        <button id="aipkit_delete_queue_by_status_btn" class="aipkit_btn aipkit_btn-danger" title="<?php esc_attr_e('Delete filtered items', 'gpt3-ai-content-generator'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <span class="aipkit_autogpt_queue_tools_btn_text"><?php esc_html_e('Delete', 'gpt3-ai-content-generator'); ?></span>
                            <span class="aipkit_spinner" style="display:none;"></span>
                        </button>
                    </div>
                </div>
            </details>
        </div>
    </div>
    <div class="aipkit_autogpt_overview_body">
        <div id="aipkit_automated_task_queue_viewer_area" class="aipkit_data-table aipkit_autogpt_queue_table">
            <table>
                <thead>
                    <tr>
                        <th class="aipkit-sortable-col" data-sort-by="q.target_identifier"><?php esc_html_e('Item', 'gpt3-ai-content-generator'); ?></th>
                        <th class="aipkit-sortable-col" data-sort-by="t.task_name"><?php esc_html_e('Task', 'gpt3-ai-content-generator'); ?></th>
                        <th class="aipkit-sortable-col" data-sort-by="q.added_at"><?php esc_html_e('Added', 'gpt3-ai-content-generator'); ?></th>
                        <th class="aipkit-sortable-col" data-sort-by="q.status"><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                        <th class="aipkit_actions_cell_header"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                    </tr>
                </thead>
                <tbody id="aipkit_automated_task_queue_tbody">
                    <tr><td colspan="5" class="aipkit_text-center"><?php esc_html_e('Loading queue...', 'gpt3-ai-content-generator'); ?></td></tr>
                </tbody>
            </table>
        </div>
        <div id="aipkit_automated_task_queue_pagination" class="aipkit_pagination"></div>
    </div>
</div>

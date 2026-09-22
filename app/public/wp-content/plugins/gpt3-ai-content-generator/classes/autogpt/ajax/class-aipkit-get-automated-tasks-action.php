<?php


namespace WPAICG\AutoGPT\Ajax;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX request for getting all automated tasks.
 */
class AIPKit_Get_Automated_Tasks_Action extends AIPKit_Automated_Task_Base_Ajax_Action
{
    public function handle_request()
    {
        $permission_check = $this->check_module_access_permissions('autogpt', self::NONCE_ACTION);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $current_page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $items_per_page = 10;
        $offset = ($current_page - 1) * $items_per_page;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Admin pagination count over a plugin-owned custom table.
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tasks_table_name}");

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Admin pagination query over a plugin-owned custom table.
        $tasks = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->tasks_table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d", $items_per_page, $offset), ARRAY_A);


        wp_send_json_success([
            'tasks' => $tasks ?: [],
            'pagination' => [
                'total_items' => (int) $total_items,
                'total_pages' => ceil($total_items / $items_per_page),
                'current_page' => $current_page,
                'per_page' => $items_per_page,
            ]
        ]);
    }
}

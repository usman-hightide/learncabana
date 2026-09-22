<?php

/**
 * AIPKit AutoGPT Module - Main View
 * UPDATED: Re-architected into a three-column layout with a central tabbed input panel and action bar.
 * MODIFIED: Moved template controls to the left column and status indicators to the right column.
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

use WPAICG\AIPKit_Providers; // For AI models
use WPAICG\AIPKIT_AI_Settings; // For AI parameters
use WPAICG\aipkit_dashboard; // For addon status

// --- Variable Definitions for Partials ---
$post_types_args = ['public' => true];
$all_post_types = get_post_types($post_types_args, 'objects');
$all_selectable_post_types = array_filter($all_post_types, function ($pt_obj) {
    return $pt_obj->name !== 'attachment';
});

$vector_store_localization = [
    'openai_vector_stores' => [],
    'pinecone_indexes' => [],
    'qdrant_collections' => [],
    'chroma_collections' => [],
];
if (class_exists(AIPKit_Providers::class)) {
    $vector_store_localization = AIPKit_Providers::get_vector_store_localization_payload('autogpt_ui');
}
$openai_vector_stores = isset($vector_store_localization['openai_vector_stores']) && is_array($vector_store_localization['openai_vector_stores'])
    ? $vector_store_localization['openai_vector_stores']
    : [];
$pinecone_indexes = isset($vector_store_localization['pinecone_indexes']) && is_array($vector_store_localization['pinecone_indexes'])
    ? $vector_store_localization['pinecone_indexes']
    : [];
$qdrant_collections = isset($vector_store_localization['qdrant_collections']) && is_array($vector_store_localization['qdrant_collections'])
    ? $vector_store_localization['qdrant_collections']
    : [];
$chroma_collections = isset($vector_store_localization['chroma_collections']) && is_array($vector_store_localization['chroma_collections'])
    ? $vector_store_localization['chroma_collections']
    : [];


$task_categories = [
    '' => __('-- Select Category --', 'gpt3-ai-content-generator'),
    'content_creation' => __('Create New Content', 'gpt3-ai-content-generator'),
    'content_enhancement' => __('Rewrite Content', 'gpt3-ai-content-generator'),
    'knowledge_base' => __('Content Indexing', 'gpt3-ai-content-generator'),
    'community_engagement' => __('Engagement', 'gpt3-ai-content-generator'),
];
$frequencies = [
    'one-time' => __('One-time', 'gpt3-ai-content-generator'),
    'aipkit_five_minutes' => __('Every 5 Minutes', 'gpt3-ai-content-generator'),
    'aipkit_fifteen_minutes' => __('Every 15 Minutes', 'gpt3-ai-content-generator'),
    'aipkit_thirty_minutes' => __('Every 30 Minutes', 'gpt3-ai-content-generator'),
    'hourly' => __('Hourly', 'gpt3-ai-content-generator'),
    'twicedaily' => __('Twice Daily', 'gpt3-ai-content-generator'),
    'daily' => __('Daily', 'gpt3-ai-content-generator'),
    'weekly' => __('Weekly', 'gpt3-ai-content-generator'),
];

$is_pro = aipkit_dashboard::is_pro_plan(); // Define is_pro for partials

// For Content Writing Task Type
$cw_providers_for_select = ['OpenAI', 'Google', 'Claude', 'OpenRouter', 'Azure', 'Ollama', 'DeepSeek', 'xAI'];
$cw_ai_parameters = AIPKIT_AI_Settings::get_ai_parameters();
$cw_default_temperature = $cw_ai_parameters['temperature'] ?? 1.0;
$cw_available_post_types = get_post_types(['public' => true], 'objects');
unset($cw_available_post_types['attachment']);
$cw_current_user_id = get_current_user_id();
// Minimal safeguard: avoid loading thousands of users into selects.
// Load up to a small, reasonable cap and ensure current user is always present.
$__aipkit_user_list_cap = 200;
$cw_users_for_author = get_users([
    'orderby' => 'display_name',
    'order'   => 'ASC',
    'fields'  => ['ID', 'display_name', 'user_login'],
    'number'  => $__aipkit_user_list_cap,
]);
if ($cw_current_user_id) {
    $has_current_user = false;
    foreach ($cw_users_for_author as $u) {
        if ((int) $u->ID === (int) $cw_current_user_id) { $has_current_user = true; break; }
    }
    if (!$has_current_user) {
        $u = get_user_by('id', $cw_current_user_id);
        if ($u && isset($u->ID)) {
            $cw_users_for_author[] = (object) [
                'ID' => (int) $u->ID,
                'display_name' => (string) $u->display_name,
                'user_login' => (string) $u->user_login,
            ];
        }
    }
}
$cw_post_statuses = [
    'draft' => __('Draft', 'gpt3-ai-content-generator'),
    'publish' => __('Publish', 'gpt3-ai-content-generator'),
    'pending' => __('Pending Review', 'gpt3-ai-content-generator'),
    'private' => __('Private', 'gpt3-ai-content-generator'),
];
$cw_wp_categories = get_categories(['hide_empty' => false]);

$aipkit_task_statuses_for_select = [ // This was used for the task status dropdown
    'active' => __('Active', 'gpt3-ai-content-generator'),
    'paused' => __('Paused', 'gpt3-ai-content-generator'),
];

// --- AutoGPT Cron Summary (Global Card) ---
$aipkit_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
$aipkit_cron_alternate = defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON;
$aipkit_cron_task_hook_prefix = 'aipkit_automated_task_';
$aipkit_cron_task_hooks = [];
$aipkit_cron_next_timestamp = null;

if (function_exists('_get_cron_array')) {
    $aipkit_cron_events = _get_cron_array();
    if (is_array($aipkit_cron_events)) {
        foreach ($aipkit_cron_events as $timestamp => $events) {
            if (!is_array($events)) {
                continue;
            }
            foreach ($events as $hook => $hook_events) {
                if (strpos($hook, $aipkit_cron_task_hook_prefix) === 0) {
                    if (!isset($aipkit_cron_task_hooks[$hook]) || $timestamp < $aipkit_cron_task_hooks[$hook]) {
                        $aipkit_cron_task_hooks[$hook] = $timestamp;
                    }
                }
            }
        }
    }
}

$aipkit_cron_task_count = count($aipkit_cron_task_hooks);
if ($aipkit_cron_task_count > 0) {
    $aipkit_cron_next_timestamp = min($aipkit_cron_task_hooks);
}

$aipkit_cron_status_label = $aipkit_cron_disabled
    ? __('Disabled', 'gpt3-ai-content-generator')
    : __('Enabled', 'gpt3-ai-content-generator');
if (!$aipkit_cron_disabled && $aipkit_cron_alternate) {
    $aipkit_cron_status_label = __('Enabled (Alternate)', 'gpt3-ai-content-generator');
}

$aipkit_cron_state = $aipkit_cron_disabled ? 'disabled' : 'enabled';
$aipkit_cron_overdue = false;
if (!$aipkit_cron_disabled && $aipkit_cron_next_timestamp) {
    $aipkit_cron_overdue = $aipkit_cron_next_timestamp < (time() - (15 * MINUTE_IN_SECONDS));
    if ($aipkit_cron_overdue) {
        $aipkit_cron_state = 'overdue';
    }
}

$aipkit_cron_next_label = $aipkit_cron_next_timestamp
    ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $aipkit_cron_next_timestamp)
    : __('Not scheduled', 'gpt3-ai-content-generator');

$aipkit_cron_tip = '';
if ($aipkit_cron_disabled) {
    $aipkit_cron_tip = sprintf(
        /* translators: %s: URL to documentation about enabling WP-Cron. */
        __('WP-Cron is disabled. Enable WP-Cron to run automated tasks. <a href="%s" target="_blank" rel="noopener noreferrer">Learn how to enable WP-Cron</a>.', 'gpt3-ai-content-generator'),
        esc_url('https://www.siteground.com/kb/enable-wordpress-cron/')
    );
} elseif ($aipkit_cron_overdue) {
    $aipkit_cron_tip = __('Next run is overdue. WP-Cron runs on page loads.', 'gpt3-ai-content-generator');
}

$aipkit_autogpt_cron_summary = [
    'state' => $aipkit_cron_state,
    'status_label' => $aipkit_cron_status_label,
    'next_label' => $aipkit_cron_next_label,
    'next_timestamp' => $aipkit_cron_next_timestamp ? (int) $aipkit_cron_next_timestamp : 0,
    'task_count' => $aipkit_cron_task_count,
    'tip' => $aipkit_cron_tip,
];
// --- End Variable Definitions ---

?>
<?php
$aipkit_notice_id = 'aipkit_provider_notice_autogpt';
include WPAICG_PLUGIN_DIR . 'admin/views/shared/provider-key-notice.php';
include WPAICG_PLUGIN_DIR . 'admin/views/shared/seo-plugin-conflict-notice.php';
?>
<?php
$aipkit_autogpt_cron_warning = '';
$aipkit_autogpt_cron_warning_key = '';
if (!empty($aipkit_autogpt_cron_summary)) {
    if (($aipkit_autogpt_cron_summary['state'] ?? '') === 'disabled') {
        $aipkit_autogpt_cron_warning_key = 'autogpt-wp-cron-disabled-v1';
        $aipkit_autogpt_cron_warning = sprintf(
            /* translators: %s: URL to documentation about enabling WP-Cron. */
            __('WP-Cron is disabled. Automated tasks will not run. <a href="%s" target="_blank" rel="noopener noreferrer">Learn how to enable WP-Cron</a>.', 'gpt3-ai-content-generator'),
            esc_url('https://www.siteground.com/kb/enable-wordpress-cron/')
        );
    } elseif (($aipkit_autogpt_cron_summary['state'] ?? '') === 'overdue') {
        $aipkit_autogpt_cron_warning_key = 'autogpt-wp-cron-overdue-v1';
        $aipkit_autogpt_cron_warning = __('WP-Cron appears delayed. Automated tasks run on page loads, so low traffic can delay runs.', 'gpt3-ai-content-generator');
    }
}
?>
<?php if (!empty($aipkit_autogpt_cron_warning)) : ?>
<div class="aipkit_notification_bar aipkit_notification_bar--warning" data-aipkit-dismissible-notice="<?php echo esc_attr($aipkit_autogpt_cron_warning_key); ?>">
    <div class="aipkit_notification_bar__icon" aria-hidden="true">
        <span class="dashicons dashicons-clock"></span>
    </div>
    <div class="aipkit_notification_bar__content">
        <p><?php echo wp_kses_post($aipkit_autogpt_cron_warning); ?></p>
    </div>
    <button type="button" class="aipkit_notification_bar__close" data-aipkit-dismiss-notice aria-label="<?php esc_attr_e('Dismiss notice', 'gpt3-ai-content-generator'); ?>">
        &times;
    </button>
</div>
<?php endif; ?>
<div class="aipkit_container aipkit_module_autogpt" id="aipkit_autogpt_container">
    <div class="aipkit_container-header">
        <div class="aipkit_container-header-left">
            <div class="aipkit_autogpt_header_copy">
                <div class="aipkit_autogpt_header_title_row">
                    <div class="aipkit_container-title" id="aipkit_autogpt_header_title_default"><?php esc_html_e('Automations', 'gpt3-ai-content-generator'); ?></div>
                    <div class="aipkit_autogpt_header_title_editor" id="aipkit_autogpt_header_title_editor" style="display: none;">
                        <button
                            type="button"
                            class="aipkit_autogpt_title_display"
                            id="aipkit_autogpt_title_display"
                            aria-label="<?php esc_attr_e('Edit task name', 'gpt3-ai-content-generator'); ?>"
                        >
                            <span class="aipkit_autogpt_title_text" id="aipkit_autogpt_title_text" data-default-label="<?php esc_attr_e('New Task', 'gpt3-ai-content-generator'); ?>">
                                <?php esc_html_e('New Task', 'gpt3-ai-content-generator'); ?>
                            </span>
                            <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                        </button>
                        <input
                            type="text"
                            id="aipkit_autogpt_task_title_input"
                            class="aipkit_form-input aipkit_autogpt_title_input"
                            placeholder="<?php esc_attr_e('New Task', 'gpt3-ai-content-generator'); ?>"
                            style="display: none;"
                        >
                    </div>
                    <span
                        id="aipkit_automated_task_form_status"
                        class="aipkit_training_status aipkit_global_status_area"
                        aria-live="polite"
                    ></span>
                </div>
                <p class="aipkit_autogpt_header_hint"><?php esc_html_e('Create, schedule, and monitor recurring AI automations.', 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/task-automation-ui.php'; // Include the main UI partial?>
</div>

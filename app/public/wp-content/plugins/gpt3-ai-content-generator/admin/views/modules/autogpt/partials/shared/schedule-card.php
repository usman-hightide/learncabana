<?php
/**
 * Partial: AutoGPT Task Schedule Card
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$cw_post_statuses = isset($cw_post_statuses) && is_array($cw_post_statuses)
    ? $cw_post_statuses
    : [];
?>
<input type="hidden" id="aipkit_autogpt_task_status_input" name="task_status" value="active">

<div class="aipkit_cw_publishing_panel aipkit_post_settings_redesigned">
    <div class="aipkit_cw_publishing_row aipkit_task_schedule_row">
        <label class="aipkit_cw_panel_label" for="aipkit_autogpt_task_status_toggle">
            <?php esc_html_e('Schedule', 'gpt3-ai-content-generator'); ?>
        </label>
        <div class="aipkit_cw_publishing_row_actions aipkit_task_schedule_toggle_actions">
            <span class="aipkit_task_status_value" id="aipkit_autogpt_task_status_label"><?php esc_html_e('Active', 'gpt3-ai-content-generator'); ?></span>
            <label class="aipkit_switch">
                <input
                    type="checkbox"
                    id="aipkit_autogpt_task_status_toggle"
                    class="aipkit_toggle_switch"
                    checked
                >
                <span class="aipkit_switch_slider"></span>
            </label>
        </div>
    </div>

    <?php include __DIR__ . '/task-frequency.php'; ?>

    <div class="aipkit_post_settings_chunk aipkit_post_settings_chunk--publishing" data-aipkit-task-publishing>
        <div class="aipkit_post_settings_chunk_body">
            <div class="aipkit_post_status_row aipkit_cw_publishing_row">
                <label class="aipkit_cw_panel_label" for="aipkit_task_cw_post_status">
                    <?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_cw_publishing_row_actions">
                    <select id="aipkit_task_cw_post_status" name="post_status" class="aipkit_post_settings_select aipkit_form-input aipkit_cw_publishing_select aipkit_cw_blended_chevron_select">
                        <?php foreach ($cw_post_statuses as $status_val => $status_label): ?>
                            <option value="<?php echo esc_attr($status_val); ?>" <?php selected($status_val, 'publish'); ?>><?php echo esc_html($status_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button
                        type="button"
                        class="aipkit_cw_settings_icon_trigger"
                        id="aipkit_autogpt_cw_post_settings_trigger"
                        data-aipkit-popover-target="aipkit_autogpt_cw_post_settings_popover"
                        data-aipkit-popover-placement="left"
                        aria-controls="aipkit_autogpt_cw_post_settings_popover"
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e('Post settings', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Post settings', 'gpt3-ai-content-generator'); ?>"
                        data-aipkit-autogpt-schedule-section="content_writing"
                        hidden
                    >
                        <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                    </button>
                </div>
            </div>

            <div id="aipkit_task_cw_schedule_options_wrapper" class="aipkit_post_smart_schedule_container aipkit_task_schedule_options" hidden>
                <div class="aipkit_post_smart_schedule_header">
                    <span class="aipkit_cw_panel_label">
                        <?php esc_html_e('Publishing Schedule', 'gpt3-ai-content-generator'); ?>
                    </span>
                </div>
                <div class="aipkit_post_smart_schedule_options">
                    <label class="aipkit_post_schedule_radio">
                        <input type="radio" name="schedule_mode" value="immediate" checked>
                        <span class="aipkit_post_schedule_radio_text"><?php esc_html_e('Publish Immediately', 'gpt3-ai-content-generator'); ?></span>
                    </label>
                    <label class="aipkit_post_schedule_radio">
                        <input type="radio" name="schedule_mode" value="smart">
                        <span class="aipkit_post_schedule_radio_text"><?php esc_html_e('Smart Schedule', 'gpt3-ai-content-generator'); ?></span>
                    </label>
                    <label class="aipkit_post_schedule_radio aipkit_schedule_from_input_option aipkit_task_schedule_from_input_option">
                        <input type="radio" name="schedule_mode" value="from_input">
                        <span class="aipkit_post_schedule_radio_text"><?php esc_html_e('Use Dates from Input', 'gpt3-ai-content-generator'); ?></span>
                        <span
                            class="aipkit_popover_warning is-visible"
                            data-tooltip="<?php echo esc_attr__("Use when your input includes a publish date (Bulk/CSV or Google Sheets).\n\nAccepted formats: YYYY-MM-DD HH:MM[:SS], YYYY/MM/DD HH:MM, MM/DD/YYYY HH:MM, DD/MM/YYYY HH:MM, or ISO 8601.\n\nTimes use the site timezone unless an offset/Z is provided.", 'gpt3-ai-content-generator'); ?>"
                            tabindex="0"
                            aria-label="<?php echo esc_attr__('Show date format help', 'gpt3-ai-content-generator'); ?>"
                        >
                            <span class="dashicons dashicons-info" aria-hidden="true"></span>
                        </span>
                    </label>
                </div>

                <div id="aipkit_task_cw_smart_schedule_fields" class="aipkit_post_smart_schedule_fields" hidden>
                    <div class="aipkit_post_smart_schedule_field">
                        <label class="aipkit_cw_panel_label" for="aipkit_task_cw_smart_schedule_start_datetime">
                            <?php esc_html_e('Start Date/Time', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <input type="datetime-local" id="aipkit_task_cw_smart_schedule_start_datetime" name="smart_schedule_start_datetime" class="aipkit_post_settings_input aipkit_form-input aipkit_cw_publishing_input">
                    </div>
                    <div class="aipkit_post_smart_schedule_field">
                        <label class="aipkit_cw_panel_label" for="aipkit_task_cw_smart_schedule_interval_value">
                            <?php esc_html_e('Publish one post every', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <div class="aipkit_post_smart_schedule_interval">
                            <input type="number" id="aipkit_task_cw_smart_schedule_interval_value" name="smart_schedule_interval_value" value="1" min="1" class="aipkit_post_settings_input aipkit_post_settings_input--number aipkit_form-input aipkit_cw_publishing_input aipkit_cw_publishing_input--number">
                            <select id="aipkit_task_cw_smart_schedule_interval_unit" name="smart_schedule_interval_unit" class="aipkit_post_settings_select aipkit_form-input aipkit_cw_publishing_select aipkit_cw_blended_chevron_select">
                                <option value="hours"><?php esc_html_e('Hours', 'gpt3-ai-content-generator'); ?></option>
                                <option value="days"><?php esc_html_e('Days', 'gpt3-ai-content-generator'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <p class="aipkit_post_schedule_hint aipkit_schedule_from_input_help" hidden></p>
            </div>
        </div>
    </div>
</div>

<div class="aipkit_model_settings_popover aipkit_cw_settings_popover" id="aipkit_autogpt_cw_post_settings_popover" aria-hidden="true">
    <div class="aipkit_model_settings_popover_panel aipkit_cw_settings_popover_panel aipkit_cw_post_settings_popover_panel" role="dialog" aria-label="<?php esc_attr_e('Post settings', 'gpt3-ai-content-generator'); ?>">
        <?php include dirname(__DIR__) . '/content-writing/post-settings.php'; ?>
    </div>
</div>

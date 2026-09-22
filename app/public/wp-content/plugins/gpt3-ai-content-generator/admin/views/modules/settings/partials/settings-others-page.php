<?php
/**
 * Partial: Other Settings Page
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_backup_row">
    <label class="aipkit_form-label" for="aipkit_settings_export_button">
        <?php esc_html_e('Settings Backup', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Export or import settings JSON.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_action_buttons">
        <button
            type="button"
            id="aipkit_settings_export_button"
            class="button button-secondary aipkit_btn"
            data-aipkit-settings-action="export-backup"
        >
            <?php esc_html_e('Export JSON', 'gpt3-ai-content-generator'); ?>
        </button>
        <button
            type="button"
            id="aipkit_settings_import_trigger"
            class="button button-primary aipkit_btn aipkit_btn-primary"
            data-aipkit-settings-action="import-trigger"
        >
            <?php esc_html_e('Import JSON', 'gpt3-ai-content-generator'); ?>
        </button>
        <input
            type="file"
            id="aipkit_settings_import_file"
            class="aipkit_settings_hidden_file_input"
            accept=".json,application/json"
        />
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_restore_point_row">
    <label class="aipkit_form-label" for="aipkit_settings_create_restore_point">
        <?php esc_html_e('Restore Point', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Save a restore point.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_action_buttons">
        <button
            type="button"
            id="aipkit_settings_create_restore_point"
            class="button button-primary aipkit_btn aipkit_btn-primary"
            data-aipkit-settings-action="create-restore-point"
        >
            <?php esc_html_e('Create Restore Point', 'gpt3-ai-content-generator'); ?>
        </button>
        <button
            type="button"
            id="aipkit_settings_restore_restore_point"
            class="button aipkit_btn aipkit_btn-danger"
            data-aipkit-settings-action="restore-restore-point"
        >
            <?php esc_html_e('Restore Last Point', 'gpt3-ai-content-generator'); ?>
        </button>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_model_cache_row">
    <label class="aipkit_form-label" for="aipkit_settings_clear_model_cache">
        <?php esc_html_e('Model Cache', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Clear cached model lists.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_action_buttons">
        <button
            type="button"
            id="aipkit_settings_clear_model_cache"
            class="button button-secondary aipkit_btn"
            data-aipkit-settings-action="clear-model-cache"
        >
            <?php esc_html_e('Clear Model Cache', 'gpt3-ai-content-generator'); ?>
        </button>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_transients_row">
    <label class="aipkit_form-label" for="aipkit_settings_clear_transients">
        <?php esc_html_e('Transient Cache', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Clear transients and object cache.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_action_buttons">
        <button
            type="button"
            id="aipkit_settings_clear_transients"
            class="button aipkit_btn aipkit_btn-danger"
            data-aipkit-settings-action="clear-transients"
        >
            <?php esc_html_e('Clear Transients', 'gpt3-ai-content-generator'); ?>
        </button>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_sync_all_row">
    <label class="aipkit_form-label" for="aipkit_settings_sync_all_models">
        <?php esc_html_e('Sync All Models', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Sync models for all providers.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_action_buttons">
        <button
            type="button"
            id="aipkit_settings_sync_all_models"
            class="button button-primary aipkit_btn aipkit_btn-primary"
            data-aipkit-settings-action="sync-all-models"
        >
            <?php esc_html_e('Sync All', 'gpt3-ai-content-generator'); ?>
        </button>
    </div>
</div>

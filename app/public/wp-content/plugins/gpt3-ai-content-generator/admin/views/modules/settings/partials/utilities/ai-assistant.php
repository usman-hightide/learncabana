<?php
/**
 * Partial: Utility Assistant Settings
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

// Variables from parent: $enhancer_editor_integration_enabled, $enhancer_list_button_enabled
$enhancer_editor_integration_enabled = isset($enhancer_editor_integration_enabled) && (string) $enhancer_editor_integration_enabled === '1'
    ? '1'
    : '0';
$enhancer_list_button_enabled = isset($enhancer_list_button_enabled) && (string) $enhancer_list_button_enabled === '1'
    ? '1'
    : '0';
?>

<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_utilities_content_assistant_row">
    <label class="aipkit_form-label" for="aipkit_enhancer_list_button">
        <?php esc_html_e('Content Assistant', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper">
            <?php esc_html_e('Show Content Assistant on content lists.', 'gpt3-ai-content-generator'); ?>
        </span>
    </label>
    <label class="aipkit_settings_big_checkbox" for="aipkit_enhancer_list_button">
        <input
            type="checkbox"
            id="aipkit_enhancer_list_button"
            name="enhancer_list_button"
            class="aipkit_autosave_trigger"
            value="1"
            <?php checked($enhancer_list_button_enabled, '1'); ?>
        />
        <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
            <span class="dashicons dashicons-saved"></span>
        </span>
        <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
    </label>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row">
    <label class="aipkit_form-label" for="aipkit_enhancer_editor_integration">
        <?php esc_html_e('Editor assistant', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper">
            <?php esc_html_e('Show Assistant in Classic and Block editors.', 'gpt3-ai-content-generator'); ?>
        </span>
    </label>
    <label class="aipkit_settings_big_checkbox" for="aipkit_enhancer_editor_integration">
        <input
            type="checkbox"
            id="aipkit_enhancer_editor_integration"
            name="enhancer_editor_integration"
            class="aipkit_autosave_trigger"
            value="1"
            <?php checked($enhancer_editor_integration_enabled, '1'); ?>
        />
        <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
            <span class="dashicons dashicons-saved"></span>
        </span>
        <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
    </label>
</div>

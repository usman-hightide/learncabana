<?php
/**
 * Partial: Utilities Settings Page
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$utilities_options = get_option('aipkit_options', []);
$utilities_options = is_array($utilities_options) ? $utilities_options : [];
$utilities_enhancer_settings = isset($utilities_options['enhancer_settings']) && is_array($utilities_options['enhancer_settings'])
    ? $utilities_options['enhancer_settings']
    : [];
$enhancer_editor_integration_enabled = $utilities_enhancer_settings['editor_integration'] ?? '1';
$enhancer_list_button_enabled = $utilities_enhancer_settings['show_list_button'] ?? '1';

$utilities_training_general_settings = get_option('aipkit_training_general_settings', [
    'show_index_button' => true,
]);
$utilities_training_general_settings = is_array($utilities_training_general_settings)
    ? $utilities_training_general_settings
    : [];
$utilities_show_index_button_enabled = !array_key_exists('show_index_button', $utilities_training_general_settings)
    || (bool) $utilities_training_general_settings['show_index_button'];
$utilities_indexing_nonce = wp_create_nonce('aipkit_ai_training_settings_nonce');
?>

<div
    id="aipkit_settings_utilities_page"
    class="aipkit_settings_utilities_page"
    data-indexing-nonce="<?php echo esc_attr($utilities_indexing_nonce); ?>"
>
    <div
        class="aipkit_form-group aipkit_settings_simple_row"
        id="aipkit_settings_utilities_show_index_button_row"
        data-aipkit-settings-autosave-exclude="true"
    >
        <label class="aipkit_form-label" for="aipkit_utilities_show_index_button">
            <?php esc_html_e('Index button', 'gpt3-ai-content-generator'); ?>
            <span class="aipkit_form-label-helper">
                <?php esc_html_e('Show Index button on content lists.', 'gpt3-ai-content-generator'); ?>
            </span>
        </label>
        <label class="aipkit_settings_big_checkbox" for="aipkit_utilities_show_index_button">
            <input
                type="checkbox"
                id="aipkit_utilities_show_index_button"
                name="show_index_button"
                class="aipkit_utilities_indexing_select"
                value="1"
                data-saved-value="<?php echo esc_attr($utilities_show_index_button_enabled ? '1' : '0'); ?>"
                <?php checked($utilities_show_index_button_enabled, true); ?>
            />
            <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                <span class="dashicons dashicons-saved"></span>
            </span>
            <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
        </label>
    </div>

    <?php include __DIR__ . '/utilities/ai-assistant.php'; ?>
</div>

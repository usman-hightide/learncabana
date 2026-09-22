<?php
/**
 * Partial: REST API Settings Page
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_api_rest_key_row">
    <label class="aipkit_form-label" for="aipkit_public_api_key">
        <?php esc_html_e('REST API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Use this key for external requests.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_api-key-wrapper">
        <input
            type="password"
            id="aipkit_public_api_key"
            name="public_api_key"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($public_api_key); ?>"
            placeholder="<?php esc_attr_e('Leave blank to disable REST API access', 'gpt3-ai-content-generator'); ?>"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
            data-lpignore="true"
            data-1p-ignore="true"
            data-form-type="other"
        />
        <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
    </div>
</div>

<?php include __DIR__ . '/settings-wp-ai-client.php'; ?>

<?php include __DIR__ . '/settings-event-webhooks.php'; ?>

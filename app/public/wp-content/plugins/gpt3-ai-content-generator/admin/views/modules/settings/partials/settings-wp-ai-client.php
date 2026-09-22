<?php
/**
 * Partial: WordPress AI Client connector management.
 */
if (!defined('ABSPATH')) {
    exit;
}

$aipkit_wpai_settings_class = '\\WPAICG\\WP_AI_Client\\AIPKit_WP_AI_Client_Settings';

if (!class_exists($aipkit_wpai_settings_class) || !$aipkit_wpai_settings_class::is_supported()) {
    return;
}

$aipkit_wpai_managed = $aipkit_wpai_settings_class::is_effectively_managed();
?>
<div
    class="aipkit_form-group aipkit_settings_simple_row"
    id="aipkit_settings_wp_ai_client_row"
    data-aipkit-wpai-control
    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    data-nonce="<?php echo esc_attr(wp_create_nonce('aipkit_wp_ai_client_set_mode')); ?>"
    data-mode="<?php echo esc_attr($aipkit_wpai_managed ? 'managed' : 'observe'); ?>"
>
    <label class="aipkit_form-label" for="aipkit_settings_wp_ai_client_toggle">
        <?php esc_html_e('WordPress AI Connectors', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Let AI Puffer manage WP AI Client.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <label class="aipkit_settings_big_checkbox" for="aipkit_settings_wp_ai_client_toggle">
        <input
            type="checkbox"
            id="aipkit_settings_wp_ai_client_toggle"
            data-aipkit-wpai-toggle
            <?php checked($aipkit_wpai_managed, true); ?>
        />
        <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
            <span class="dashicons dashicons-saved"></span>
        </span>
        <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
    </label>
</div>

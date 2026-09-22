<?php
/**
 * Shared Partial: Provider API Key Notice
 *
 * Expected variables:
 * - $aipkit_notice_id or $notice_id (string) Unique HTML id for the notice element.
 * - $aipkit_notice_class or $notice_class (string, optional) Additional CSS classes.
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$notice_id = isset($notice_id) ? (string) $notice_id : '';
$notice_class = isset($notice_class) ? (string) $notice_class : '';
$aipkit_notice_id = isset($aipkit_notice_id) ? (string) $aipkit_notice_id : $notice_id;
$aipkit_notice_class = isset($aipkit_notice_class) ? (string) $aipkit_notice_class : $notice_class;

if ($aipkit_notice_id === '') {
    return;
}

$aipkit_settings_url = admin_url('admin.php?page=wpaicg');
?>
<div
    id="<?php echo esc_attr($aipkit_notice_id); ?>"
    class="aipkit_notification_bar aipkit_notification_bar--warning aipkit_provider_key_notice aipkit_provider_notice--hidden <?php echo esc_attr($aipkit_notice_class); ?>"
    data-aipkit-provider-notice="1"
    data-message-openai="<?php echo esc_attr__('You selected OpenAI as an AI provider, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-openrouter="<?php echo esc_attr__('You selected OpenRouter as an AI provider, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-google="<?php echo esc_attr__('You selected Google as an AI provider, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-azure="<?php echo esc_attr__('You selected Azure as an AI provider, but it is not configured yet. Add its API key and endpoint in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-claude="<?php echo esc_attr__('You selected Anthropic as an AI provider, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-deepseek="<?php echo esc_attr__('You selected DeepSeek as an AI provider, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-xai="<?php echo esc_attr__('You selected xAI as an AI provider, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-ollama="<?php echo esc_attr__('You selected Ollama as an AI provider, but it is not configured yet. Add its connection URL in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-replicate="<?php echo esc_attr__('You selected Replicate as an AI provider, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
    data-message-default="<?php echo esc_attr__('The selected AI provider is not configured yet. Add required credentials in Settings.', 'gpt3-ai-content-generator'); ?>"
>
    <div class="aipkit_notification_bar__icon" aria-hidden="true">
        <span class="dashicons dashicons-warning"></span>
    </div>
    <div class="aipkit_notification_bar__content">
        <p>
            <span class="aipkit_provider_notice_message">
                <?php esc_html_e('The selected AI provider is not configured yet. Add required credentials in Settings.', 'gpt3-ai-content-generator'); ?>
            </span>
        </p>
    </div>
    <div class="aipkit_notification_bar__actions">
        <a
            href="<?php echo esc_url($aipkit_settings_url); ?>"
            class="aipkit_btn aipkit_provider_notice_settings_link"
            data-aipkit-load-module="settings"
        >
            <?php esc_html_e('Open Settings', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

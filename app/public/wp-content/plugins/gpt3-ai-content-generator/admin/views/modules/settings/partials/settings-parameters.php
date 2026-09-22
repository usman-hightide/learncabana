<?php
/**
 * Partial: AI Parameters & Advanced Settings
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

if (!defined('ABSPATH')) exit;

// Variables required: $current_provider, $temperature, $top_p
// Also, variables required by the included partials settings-advanced-provider.php and settings-safety-google.php
// must be available here or passed down.
// For settings-advanced-provider.php: $openai_data, $openrouter_data, $google_data, $azure_data, $claude_data, $deepseek_data, $openai_defaults, etc.
// For settings-safety-google.php: $category_thresholds, $safety_thresholds
// $is_pro (from settings/index.php)

use WPAICG\Core\Providers\Google\GoogleSettingsHandler; // For settings-safety-google.php
use WPAICG\Core\Moderation\AIPKit_Global_Security_Settings;

$openai_security_settings = class_exists(AIPKit_Global_Security_Settings::class)
    ? AIPKit_Global_Security_Settings::get_settings()
    : [
        'openai_moderation_enabled' => '0',
        'openai_moderation_message' => __('Your message was flagged by the moderation system and could not be sent.', 'gpt3-ai-content-generator'),
    ];
$openai_moderation_enabled = isset($openai_security_settings['openai_moderation_enabled']) && (string) $openai_security_settings['openai_moderation_enabled'] === '1'
    ? '1'
    : '0';
$openai_moderation_message = (string) ($openai_security_settings['openai_moderation_message']
    ?? __('Your message was flagged by the moderation system and could not be sent.', 'gpt3-ai-content-generator'));

$sync_button_configs = [
    'OpenAI' => [
        'id' => 'aipkit_sync_openai_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
    'OpenRouter' => [
        'id' => 'aipkit_sync_openrouter_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
    'Google' => [
        'id' => 'aipkit_sync_google_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
    'Azure' => [
        'id' => 'aipkit_sync_azure_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
    'Claude' => [
        'id' => 'aipkit_sync_claude_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
    'DeepSeek' => [
        'id' => 'aipkit_sync_deepseek_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
    'xAI' => [
        'id' => 'aipkit_sync_xai_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
    'Ollama' => [
        'id' => 'aipkit_sync_ollama_models',
        'label' => __('Model List', 'gpt3-ai-content-generator'),
        'button_text' => __('Sync Models', 'gpt3-ai-content-generator'),
    ],
];

$render_sync_row = static function ($provider) use ($sync_button_configs) {
    if (!isset($sync_button_configs[$provider])) {
        return;
    }

    $config = $sync_button_configs[$provider];
    ?>
    <button
        type="button"
        id="<?php echo esc_attr($config['id']); ?>"
        class="button button-secondary aipkit_btn aipkit_sync_btn"
        data-provider="<?php echo esc_attr($provider); ?>"
    >
        <span class="dashicons dashicons-update"></span>
        <span class="aipkit_btn_label"><?php echo esc_html($config['button_text']); ?></span>
    </button>
    <?php
};

$render_endpoint_provider_group = static function ($provider, $slug, $data, $defaults) use ($current_provider, $render_sync_row) {
    $base_url_id = 'aipkit_' . $slug . '_base_url';
    $base_url_name = $slug . '_base_url';
    $api_version_id = 'aipkit_' . $slug . '_api_version';
    $api_version_name = $slug . '_api_version';
    ?>
    <div
        class="aipkit_popover_option_group aipkit_settings_advanced_group aipkit_advanced_settings_provider"
        data-provider-setting="<?php echo esc_attr($provider); ?>"
        style="display: <?php echo ($current_provider === $provider) ? 'block' : 'none'; ?>;"
    >
        <div class="aipkit_settings_advanced_row aipkit_settings_advanced_row--with-action">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="<?php echo esc_attr($base_url_id); ?>"><?php esc_html_e('Base URL', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Custom API endpoint.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <div class="aipkit_popover_option_actions">
                    <div class="aipkit_input-with-icon-wrapper">
                        <input type="text" id="<?php echo esc_attr($base_url_id); ?>" name="<?php echo esc_attr($base_url_name); ?>" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--vector-wide aipkit_autosave_trigger" value="<?php echo esc_attr($data['base_url']); ?>" />
                        <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($defaults['base_url']); ?>" data-target-input="<?php echo esc_attr($base_url_id); ?>">
                            <span class="dashicons dashicons-undo"></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="aipkit_settings_advanced_action">
                <?php $render_sync_row($provider); ?>
            </div>
        </div>
        <div class="aipkit_settings_advanced_row">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="<?php echo esc_attr($api_version_id); ?>"><?php esc_html_e('API Version', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Endpoint version.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <div class="aipkit_popover_option_actions">
                    <div class="aipkit_input-with-icon-wrapper">
                        <input type="text" id="<?php echo esc_attr($api_version_id); ?>" name="<?php echo esc_attr($api_version_name); ?>" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger" value="<?php echo esc_attr($data['api_version']); ?>" />
                        <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($defaults['api_version']); ?>" data-target-input="<?php echo esc_attr($api_version_id); ?>">
                            <span class="dashicons dashicons-undo"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};

?>
    <div class="aipkit_popover_options_list">
    <div class="aipkit_popover_option_group aipkit_settings_advanced_group aipkit_settings_advanced_group--common">
        <div class="aipkit_settings_advanced_row">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_temperature"><?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Creativity level.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <input
                    type="number"
                    id="aipkit_temperature"
                    name="temperature"
                    class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger"
                    min="0"
                    max="2"
                    step="0.1"
                    value="<?php echo esc_attr($temperature); ?>"
                />
            </div>
        </div>
        <div class="aipkit_settings_advanced_row">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_top_p"><?php esc_html_e('Top P', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Sampling diversity.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <input
                    type="number"
                    id="aipkit_top_p"
                    name="top_p"
                    class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger"
                    min="0"
                    max="1"
                    step="0.01"
                    value="<?php echo esc_attr($top_p); ?>"
                />
            </div>
        </div>
    </div>

    <div
        class="aipkit_popover_option_group aipkit_settings_advanced_group aipkit_advanced_settings_provider aipkit_settings_advanced_group--provider-openai"
        data-provider-setting="OpenAI"
        style="display: <?php echo ($current_provider === 'OpenAI') ? 'block' : 'none'; ?>;"
    >
        <div class="aipkit_settings_advanced_row aipkit_settings_advanced_row--with-action">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_openai_base_url"><?php esc_html_e('Base URL', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Custom API endpoint.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <div class="aipkit_popover_option_actions">
                    <div class="aipkit_input-with-icon-wrapper">
                        <input type="text" id="aipkit_openai_base_url" name="openai_base_url" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--vector-wide aipkit_autosave_trigger" value="<?php echo esc_attr($openai_data['base_url']); ?>" />
                        <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($openai_defaults['base_url']); ?>" data-target-input="aipkit_openai_base_url">
                            <span class="dashicons dashicons-undo"></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="aipkit_settings_advanced_action">
                <?php $render_sync_row('OpenAI'); ?>
            </div>
        </div>
        <div class="aipkit_settings_advanced_row">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_openai_api_version"><?php esc_html_e('API Version', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Endpoint version.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <div class="aipkit_popover_option_actions">
                    <div class="aipkit_input-with-icon-wrapper">
                        <input type="text" id="aipkit_openai_api_version" name="openai_api_version" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger" value="<?php echo esc_attr($openai_data['api_version']); ?>" />
                        <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($openai_defaults['api_version']); ?>" data-target-input="aipkit_openai_api_version">
                            <span class="dashicons dashicons-undo"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($is_pro): ?>
        <div class="aipkit_settings_advanced_row aipkit_openai_expiration_policy_field">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_openai_expiration_policy"><?php esc_html_e('File Expiration (Days)', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Vector store retention period.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <input
                    type="number"
                    id="aipkit_openai_expiration_policy"
                    name="openai_expiration_policy"
                    class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger"
                    value="<?php echo esc_attr(isset($openai_data['expiration_policy']) ? $openai_data['expiration_policy'] : 7); ?>"
                    min="1"
                    max="365"
                    step="1"
                />
            </div>
        </div>
        <?php endif; ?>
        <div class="aipkit_settings_advanced_row aipkit_openai_store_field">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_openai_store"><?php esc_html_e('Store Conversation', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Save chat history on OpenAI server.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <label class="aipkit_settings_big_checkbox" for="aipkit_openai_store">
                    <input
                        type="checkbox"
                        id="aipkit_openai_store"
                        name="openai_store_conversation"
                        class="aipkit_autosave_trigger"
                        value="1"
                        <?php checked((string) $openai_store_conversation, '1'); ?>
                    />
                    <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                        <span class="dashicons dashicons-saved"></span>
                    </span>
                    <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
                </label>
            </div>
        </div>
        <div class="aipkit_settings_advanced_row">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_settings_openai_moderation_enabled"><?php esc_html_e('Moderation', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Moderate user input.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <label class="aipkit_settings_big_checkbox" for="aipkit_settings_openai_moderation_enabled">
                    <input
                        type="checkbox"
                        id="aipkit_settings_openai_moderation_enabled"
                        name="security[openai_moderation_enabled]"
                        class="aipkit_autosave_trigger"
                        value="1"
                        <?php checked($openai_moderation_enabled, '1'); ?>
                    />
                    <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                        <span class="dashicons dashicons-saved"></span>
                    </span>
                    <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
                </label>
            </div>
        </div>
        <div
            class="aipkit_settings_advanced_row"
            id="aipkit_settings_openai_moderation_message_row"
            <?php echo ($openai_moderation_enabled === '1') ? '' : 'hidden'; ?>
        >
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_settings_openai_moderation_message"><?php esc_html_e('Moderation Message', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Shown when a message is blocked.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <input
                    type="text"
                    id="aipkit_settings_openai_moderation_message"
                    name="security[openai_moderation_message]"
                    class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_autosave_trigger"
                    value="<?php echo esc_attr($openai_moderation_message); ?>"
                    placeholder="<?php esc_attr_e('Your message was flagged by the moderation system and could not be sent.', 'gpt3-ai-content-generator'); ?>"
                    autocomplete="off"
                    data-lpignore="true"
                    data-1p-ignore="true"
                    data-form-type="other"
                />
            </div>
        </div>
    </div>

    <?php
    $render_endpoint_provider_group('OpenRouter', 'openrouter', $openrouter_data, $openrouter_defaults);
    $render_endpoint_provider_group('Google', 'google', $google_data, $google_defaults);
    ?>

    <div
        class="aipkit_popover_option_group aipkit_settings_advanced_group aipkit_advanced_settings_provider"
        data-provider-setting="Azure"
        style="display: <?php echo ($current_provider === 'Azure') ? 'block' : 'none'; ?>;"
    >
        <div class="aipkit_settings_advanced_row aipkit_settings_advanced_row--with-action">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_azure_authoring_version"><?php echo esc_html__('Authoring API Version', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Deployment management version.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <div class="aipkit_popover_option_actions">
                    <div class="aipkit_input-with-icon-wrapper">
                        <input type="text" id="aipkit_azure_authoring_version" name="azure_authoring_version" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger" value="<?php echo esc_attr($azure_data['api_version_authoring']); ?>" />
                        <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($azure_defaults['api_version_authoring']); ?>" data-target-input="aipkit_azure_authoring_version">
                            <span class="dashicons dashicons-undo"></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="aipkit_settings_advanced_action">
                <?php $render_sync_row('Azure'); ?>
            </div>
        </div>
        <div class="aipkit_settings_advanced_row">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="aipkit_azure_inference_version"><?php echo esc_html__('Inference API Version', 'gpt3-ai-content-generator'); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Response generation version.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <div class="aipkit_popover_option_actions">
                    <div class="aipkit_input-with-icon-wrapper">
                        <input type="text" id="aipkit_azure_inference_version" name="azure_inference_version" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger" value="<?php echo esc_attr($azure_data['api_version_inference']); ?>" />
                        <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default value', 'gpt3-ai-content-generator'); ?>" data-default-value="<?php echo esc_attr($azure_defaults['api_version_inference']); ?>" data-target-input="aipkit_azure_inference_version">
                            <span class="dashicons dashicons-undo"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $render_endpoint_provider_group('Claude', 'claude', $claude_data, $claude_defaults);
    $render_endpoint_provider_group('DeepSeek', 'deepseek', $deepseek_data, $deepseek_defaults);
    $render_endpoint_provider_group('xAI', 'xai', $xai_data, $xai_defaults);
    ?>

    <?php
    if (class_exists(GoogleSettingsHandler::class)) {
        include __DIR__ . '/settings-safety-google.php';
    }
    ?>
</div>

<?php

/**
 * Partial: API Key Input Fields for different providers.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

if (!defined('ABSPATH')) exit;

// Variables required: $current_provider, $openai_data, $openrouter_data, $google_data, $azure_data, $claude_data, $deepseek_data, $xai_data, $azure_defaults

$provider_api_key_urls = [
    'OpenAI' => 'https://platform.openai.com/api-keys',
    'OpenRouter' => 'https://openrouter.ai/keys',
    'Google' => 'https://makersuite.google.com/app/apikey',
    'Azure' => 'https://ai.azure.com/', 
    'Claude' => 'https://console.anthropic.com/settings/keys',
    'DeepSeek' => 'https://platform.deepseek.com/api_keys',
    'xAI' => 'https://console.x.ai/team/default/api-keys',
];

?>
<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_openai_api_key_group"
    data-provider="OpenAI"
    style="display: <?php echo ($current_provider === 'OpenAI') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_openai_api_key">
        <?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Paste the provider API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input type="password" id="aipkit_openai_api_key" name="openai_api_key" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($openai_data['api_key']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="<?php echo esc_url($provider_api_key_urls['OpenAI']); ?>" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get your API key', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_openrouter_api_key_group"
    data-provider="OpenRouter"
     style="display: <?php echo ($current_provider === 'OpenRouter') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_openrouter_api_key">
        <?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Paste the provider API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input type="password" id="aipkit_openrouter_api_key" name="openrouter_api_key" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($openrouter_data['api_key']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="<?php echo esc_url($provider_api_key_urls['OpenRouter']); ?>" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get your API key', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_google_api_key_group"
    data-provider="Google"
     style="display: <?php echo ($current_provider === 'Google') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_google_api_key">
        <?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Paste the provider API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input type="password" id="aipkit_google_api_key" name="google_api_key" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($google_data['api_key']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="<?php echo esc_url($provider_api_key_urls['Google']); ?>" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get your API key', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_claude_api_key_group"
    data-provider="Claude"
     style="display: <?php echo ($current_provider === 'Claude') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_claude_api_key">
        <?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Paste the provider API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input type="password" id="aipkit_claude_api_key" name="claude_api_key" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($claude_data['api_key']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="<?php echo esc_url($provider_api_key_urls['Claude']); ?>" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get your API key', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_azure_api_key_group"
    data-provider="Azure"
     style="display: <?php echo ($current_provider === 'Azure') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_azure_api_key">
        <?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Paste the provider API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input type="password" id="aipkit_azure_api_key" name="azure_api_key" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($azure_data['api_key']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="<?php echo esc_url($provider_api_key_urls['Azure']); ?>" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get your API key', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_azure_endpoint_group"
    data-provider="Azure"
    style="display: <?php echo ($current_provider === 'Azure') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_azure_endpoint">
        <?php esc_html_e('Endpoint URL', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter Azure resource endpoint URL.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button"> <?php // Wrap with aipkit_input-with-button for consistent flex behavior ?>
        <div class="aipkit_input-with-icon-wrapper">
             <input type="url" id="aipkit_azure_endpoint" name="azure_endpoint" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($azure_data['endpoint']); ?>" placeholder="<?php esc_attr_e('e.g., https://your-resource-name.openai.azure.com/', 'gpt3-ai-content-generator'); ?>" />
             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Clear field', 'gpt3-ai-content-generator'); ?>" data-default-value="" data-target-input="aipkit_azure_endpoint">
                <span class="dashicons dashicons-undo"></span>
            </span>
        </div>
        <span class="aipkit_input-button-spacer"></span> <?php // Spacer keeps this field aligned with API key rows that include a helper link ?>
    </div>
</div>


<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_deepseek_api_key_group"
    data-provider="DeepSeek"
     style="display: <?php echo ($current_provider === 'DeepSeek') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_deepseek_api_key">
        <?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Paste the provider API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input type="password" id="aipkit_deepseek_api_key" name="deepseek_api_key" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($deepseek_data['api_key']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="<?php echo esc_url($provider_api_key_urls['DeepSeek']); ?>" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get your API key', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_xai_api_key_group"
    data-provider="xAI"
     style="display: <?php echo ($current_provider === 'xAI') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_xai_api_key">
        <?php esc_html_e('API Key', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Paste the provider API key.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_api-key-wrapper">
            <input type="password" id="aipkit_xai_api_key" name="xai_api_key" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($xai_data['api_key']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
            <span class="aipkit_api-key-toggle"><span class="dashicons dashicons-visibility"></span></span>
        </div>
        <a href="<?php echo esc_url($provider_api_key_urls['xAI']); ?>" target="_blank" rel="noopener noreferrer" class="aipkit_get_key_btn aipkit_settings_get_key_link">
            <?php esc_html_e('Get your API key', 'gpt3-ai-content-generator'); ?>
        </a>
    </div>
</div>

<div
    class="aipkit_form-group aipkit_api_key_field aipkit_settings_simple_row"
    id="aipkit_ollama_base_url_group"
    data-provider="Ollama"
    style="display: <?php echo ($current_provider === 'Ollama') ? 'grid' : 'none'; ?>;"
>
    <label class="aipkit_form-label" for="aipkit_ollama_base_url">
        <?php esc_html_e('Base URL', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Enter the Ollama server URL.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_input-with-button">
        <div class="aipkit_input-with-icon-wrapper">
             <input type="url" id="aipkit_ollama_base_url" name="ollama_base_url" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($ollama_data['base_url']); ?>" placeholder="<?php esc_attr_e('e.g., http://localhost:11434', 'gpt3-ai-content-generator'); ?>" />
             <span class="aipkit_restore-default-icon" title="<?php esc_attr_e('Restore default', 'gpt3-ai-content-generator'); ?>" data-default-value="http://localhost:11434" data-target-input="aipkit_ollama_base_url">
                <span class="dashicons dashicons-undo"></span>
            </span>
        </div>
        <button
            type="button"
            id="aipkit_sync_ollama_models"
            class="button button-secondary aipkit_btn aipkit_sync_btn"
            data-provider="Ollama"
        >
            <span class="dashicons dashicons-update"></span>
            <span class="aipkit_btn_label"><?php esc_html_e('Sync Models', 'gpt3-ai-content-generator'); ?></span>
        </button>
    </div>
</div>

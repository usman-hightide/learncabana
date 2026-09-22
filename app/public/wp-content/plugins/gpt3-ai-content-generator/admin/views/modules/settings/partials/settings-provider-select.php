<?php
/**
 * Partial: AI Provider Selection Dropdown
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

if (!defined('ABSPATH')) exit;

// Variables required: $current_provider, $providers, $is_pro, $provider_select_options
$provider_select_options = is_array($provider_select_options ?? null)
    ? $provider_select_options
    : [];

if (empty($provider_select_options) && class_exists('\\WPAICG\\AIPKit_Provider_Model_List_Builder')) {
    $provider_select_options = \WPAICG\AIPKit_Provider_Model_List_Builder::get_provider_options((array) $providers, (bool) $is_pro);
}

if (empty($provider_select_options)) {
    foreach ((array) $providers as $provider_key) {
        $provider_key = (string) $provider_key;
        if ($provider_key === '') {
            continue;
        }

        $provider_disabled = ($provider_key === 'Ollama' && empty($is_pro));
        $provider_label = class_exists('\\WPAICG\\AIPKit_Providers')
            ? \WPAICG\AIPKit_Providers::get_provider_display_name($provider_key)
            : ($provider_key === 'Claude' ? __('Anthropic', 'gpt3-ai-content-generator') : $provider_key);
        $provider_select_options[] = [
            'value' => $provider_key,
            'label' => $provider_disabled ? __('Ollama (Pro)', 'gpt3-ai-content-generator') : $provider_label,
            'disabled' => $provider_disabled,
        ];
    }
}
?>
<div class="aipkit_form-group aipkit_settings_simple_row aipkit_settings_simple_row--provider">
    <label
        class="aipkit_form-label"
        for="aipkit_provider"
    >
        <?php echo esc_html__('Engine', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper">
            <?php echo esc_html__('Choose the default AI provider.', 'gpt3-ai-content-generator'); ?>
        </span>
    </label>
    <div class="aipkit_settings_provider_row_content">
        <select
            id="aipkit_provider"
            name="provider"
            class="aipkit_form-input aipkit_autosave_trigger"
        >
            <?php foreach ($provider_select_options as $provider_option) :
                if (!is_array($provider_option)) {
                    continue;
                }
                $provider_value = (string) ($provider_option['value'] ?? '');
                if ($provider_value === '') {
                    continue;
                }
                $provider_label = (string) ($provider_option['label'] ?? $provider_value);
                $provider_disabled = !empty($provider_option['disabled']);
            ?>
            <option value="<?php echo esc_attr($provider_value); ?>" <?php selected($current_provider, $provider_value); ?> <?php echo $provider_disabled ? 'disabled' : ''; ?>>
                <?php echo esc_html($provider_label); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php

/**
 * Partial: Model Selection Fields
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

// Variables required: $current_provider, $openai_data, $openrouter_data, $google_data,
// $azure_data, $claude_data, $deepseek_data, $xai_data, $ollama_data

$model_field_configs = [
    'OpenAI' => [
        'group_id' => 'aipkit_openai_model_group',
        'field_id' => 'aipkit_openai_model',
        'field_name' => 'openai_model',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
    'OpenRouter' => [
        'group_id' => 'aipkit_openrouter_model_group',
        'field_id' => 'aipkit_openrouter_model',
        'field_name' => 'openrouter_model',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
    'Google' => [
        'group_id' => 'aipkit_google_model_group',
        'field_id' => 'aipkit_google_model',
        'field_name' => 'google_model',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
    'Claude' => [
        'group_id' => 'aipkit_claude_model_group',
        'field_id' => 'aipkit_claude_model',
        'field_name' => 'claude_model',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
    'Azure' => [
        'group_id' => 'aipkit_azure_deployment_group',
        'field_id' => 'aipkit_azure_deployment',
        'field_name' => 'azure_deployment',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
    'DeepSeek' => [
        'group_id' => 'aipkit_deepseek_model_group',
        'field_id' => 'aipkit_deepseek_model',
        'field_name' => 'deepseek_model',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
    'xAI' => [
        'group_id' => 'aipkit_xai_model_group',
        'field_id' => 'aipkit_xai_model',
        'field_name' => 'xai_model',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
    'Ollama' => [
        'group_id' => 'aipkit_ollama_model_group',
        'field_id' => 'aipkit_ollama_model',
        'field_name' => 'ollama_model',
        'label' => __('Model', 'gpt3-ai-content-generator'),
        'helper' => __('Choose the default model.', 'gpt3-ai-content-generator'),
    ],
];

$current_models_by_provider = [
    'OpenAI' => (string) ($openai_data['model'] ?? ''),
    'OpenRouter' => (string) ($openrouter_data['model'] ?? ''),
    'Google' => (string) ($google_data['model'] ?? ''),
    'Claude' => (string) ($claude_data['model'] ?? ''),
    'Azure' => (string) ($azure_data['model'] ?? ''),
    'DeepSeek' => (string) ($deepseek_data['model'] ?? ''),
    'xAI' => (string) ($xai_data['model'] ?? ''),
    'Ollama' => (string) ($ollama_data['model'] ?? ''),
];

foreach ($model_field_configs as $provider_key => $field_config) :
    $current_model = $current_models_by_provider[$provider_key] ?? '';

    $model_options_payload = [
        'groups' => [],
        'manual_option' => null,
        'has_selectable_options' => false,
        'empty_option_label' => __('(Sync to load models)', 'gpt3-ai-content-generator'),
    ];

    if (class_exists('\WPAICG\AIPKit_Provider_Model_List_Builder')) {
        $model_options_payload = \WPAICG\AIPKit_Provider_Model_List_Builder::get_model_options(
            (string) $provider_key,
            (string) $current_model
        );
    } elseif ($current_model !== '') {
        $model_options_payload['manual_option'] = [
            'value' => $current_model,
            'label' => $current_model,
        ];
    }

    $option_groups = is_array($model_options_payload['groups'] ?? null)
        ? $model_options_payload['groups']
        : [];

    $manual_option = is_array($model_options_payload['manual_option'] ?? null)
        ? $model_options_payload['manual_option']
        : null;

    $has_selectable_options = !empty($model_options_payload['has_selectable_options']);

    $empty_option_label = (string) ($model_options_payload['empty_option_label'] ?? '');
    if ($empty_option_label === '') {
        $empty_option_label = __('(Sync to load models)', 'gpt3-ai-content-generator');
    }
    ?>
    <div
        class="aipkit_form-group aipkit_model_field aipkit_settings_simple_row"
        id="<?php echo esc_attr($field_config['group_id']); ?>"
        data-provider="<?php echo esc_attr($provider_key); ?>"
        style="display: <?php echo ($current_provider === $provider_key) ? 'grid' : 'none'; ?>;"
    >
        <label class="aipkit_form-label" for="<?php echo esc_attr($field_config['field_id']); ?>">
            <?php echo esc_html($field_config['label']); ?>
            <span class="aipkit_form-label-helper"><?php echo esc_html($field_config['helper']); ?></span>
        </label>
        <div class="aipkit_settings_model_row_content">
            <select
                id="<?php echo esc_attr($field_config['field_id']); ?>"
                name="<?php echo esc_attr($field_config['field_name']); ?>"
                class="aipkit_form-input aipkit_autosave_trigger"
            >
                <?php foreach ($option_groups as $group) :
                    if (!is_array($group)) {
                        continue;
                    }
                    $group_label = (string) ($group['label'] ?? '');
                    $group_options = is_array($group['options'] ?? null) ? $group['options'] : [];
                    if (empty($group_options)) {
                        continue;
                    }

                    if ($group_label !== '') {
                        echo '<optgroup label="' . esc_attr($group_label) . '">';
                    }

                    foreach ($group_options as $group_option) {
                        if (!is_array($group_option)) {
                            continue;
                        }
                        $option_value = (string) ($group_option['value'] ?? '');
                        if ($option_value === '') {
                            continue;
                        }
                        $option_label = (string) ($group_option['label'] ?? $option_value);
                        $option_selected = !empty($group_option['selected']);
                        echo '<option value="' . esc_attr($option_value) . '" ' . selected($option_selected, true, false) . '>' . esc_html($option_label) . '</option>';
                    }

                    if ($group_label !== '') {
                        echo '</optgroup>';
                    }
                endforeach; ?>

                <?php if ($manual_option) :
                    $manual_value = (string) ($manual_option['value'] ?? '');
                    $manual_label = (string) ($manual_option['label'] ?? $manual_value);
                    if ($manual_value !== '') :
                        ?>
                        <option value="<?php echo esc_attr($manual_value); ?>" selected>
                            <?php echo esc_html($manual_label); ?>
                        </option>
                    <?php
                    endif;
                endif; ?>

                <?php if (!$has_selectable_options && !$manual_option) : ?>
                    <option value=""><?php echo esc_html($empty_option_label); ?></option>
                <?php endif; ?>
            </select>
            <button
                type="button"
                class="aipkit_settings_advanced_toggle_link"
                aria-controls="aipkit_settings_advanced_group"
                aria-expanded="false"
            >
                <?php esc_html_e('Advanced', 'gpt3-ai-content-generator'); ?>
                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
            </button>
        </div>
    </div>
<?php endforeach; ?>

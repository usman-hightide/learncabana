<?php
/**
 * Shared AutoGPT setup panel renderer.
 *
 * Expected variables:
 * - $aipkit_autogpt_setup_config (array)
 * - $cw_providers_for_select (array)
 * - $cw_default_temperature (mixed)
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_autogpt_setup_config = isset($aipkit_autogpt_setup_config) && is_array($aipkit_autogpt_setup_config)
    ? $aipkit_autogpt_setup_config
    : [];

$aipkit_autogpt_setup_scope = isset($aipkit_autogpt_setup_config['scope'])
    ? (string) $aipkit_autogpt_setup_config['scope']
    : 'cw';
$aipkit_autogpt_setup_name_prefix = isset($aipkit_autogpt_setup_config['name_prefix'])
    ? (string) $aipkit_autogpt_setup_config['name_prefix']
    : '';
$aipkit_autogpt_setup_model_helper = isset($aipkit_autogpt_setup_config['model_helper'])
    ? (string) $aipkit_autogpt_setup_config['model_helper']
    : __('More varied writing.', 'gpt3-ai-content-generator');
$aipkit_autogpt_setup_max_tokens_helper = isset($aipkit_autogpt_setup_config['max_tokens_helper'])
    ? (string) $aipkit_autogpt_setup_config['max_tokens_helper']
    : __('Limit output size.', 'gpt3-ai-content-generator');
$aipkit_autogpt_setup_reasoning_helper = isset($aipkit_autogpt_setup_config['reasoning_helper'])
    ? (string) $aipkit_autogpt_setup_config['reasoning_helper']
    : __('More effort for hard tasks.', 'gpt3-ai-content-generator');
$aipkit_autogpt_setup_prompt_label = isset($aipkit_autogpt_setup_config['prompt_label'])
    ? (string) $aipkit_autogpt_setup_config['prompt_label']
    : __('Prompts', 'gpt3-ai-content-generator');
$aipkit_autogpt_setup_prompt_mode = isset($aipkit_autogpt_setup_config['prompt_mode'])
    ? (string) $aipkit_autogpt_setup_config['prompt_mode']
    : 'popover';
$aipkit_autogpt_setup_prompt_target = isset($aipkit_autogpt_setup_config['prompt_target'])
    ? (string) $aipkit_autogpt_setup_config['prompt_target']
    : '';
$aipkit_autogpt_setup_prompt_include = isset($aipkit_autogpt_setup_config['prompt_include'])
    ? (string) $aipkit_autogpt_setup_config['prompt_include']
    : '';
$aipkit_autogpt_setup_prompt_popover_title = isset($aipkit_autogpt_setup_config['prompt_popover_title'])
    ? (string) $aipkit_autogpt_setup_config['prompt_popover_title']
    : __('Prompts', 'gpt3-ai-content-generator');
$aipkit_autogpt_setup_prompt_stage_id = isset($aipkit_autogpt_setup_config['prompt_stage_id'])
    ? (string) $aipkit_autogpt_setup_config['prompt_stage_id']
    : '';
$aipkit_autogpt_setup_prompt_show_back_button = !empty($aipkit_autogpt_setup_config['prompt_show_back_button']);
$aipkit_autogpt_setup_prompt_track_title = !empty($aipkit_autogpt_setup_config['prompt_track_title']);
$aipkit_autogpt_setup_prompt_root_attrs = isset($aipkit_autogpt_setup_config['prompt_root_attrs']) && is_array($aipkit_autogpt_setup_config['prompt_root_attrs'])
    ? $aipkit_autogpt_setup_config['prompt_root_attrs']
    : [];
$aipkit_autogpt_setup_extra_rows_include = isset($aipkit_autogpt_setup_config['extra_rows_include'])
    ? (string) $aipkit_autogpt_setup_config['extra_rows_include']
    : '';
$aipkit_autogpt_setup_has_length = !empty($aipkit_autogpt_setup_config['has_length']);
$aipkit_autogpt_setup_has_max_tokens = !empty($aipkit_autogpt_setup_config['has_max_tokens']);
$aipkit_autogpt_setup_default_max_tokens = isset($aipkit_autogpt_setup_config['default_max_tokens'])
    ? $aipkit_autogpt_setup_config['default_max_tokens']
    : '4000';
$aipkit_autogpt_setup_content_length_options = isset($aipkit_autogpt_setup_config['content_length_options']) && is_array($aipkit_autogpt_setup_config['content_length_options'])
    ? $aipkit_autogpt_setup_config['content_length_options']
    : [
        'short' => __('Short', 'gpt3-ai-content-generator'),
        'medium' => __('Medium', 'gpt3-ai-content-generator'),
        'long' => __('Long', 'gpt3-ai-content-generator'),
    ];
$aipkit_autogpt_setup_default_length = isset($aipkit_autogpt_setup_config['default_length'])
    ? (string) $aipkit_autogpt_setup_config['default_length']
    : 'medium';

$aipkit_autogpt_setup_providers = isset($cw_providers_for_select) && is_array($cw_providers_for_select)
    ? $cw_providers_for_select
    : [];
$aipkit_autogpt_setup_default_provider = class_exists('\WPAICG\AIPKit_Providers')
    ? strtolower(\WPAICG\AIPKit_Providers::get_current_provider())
    : 'openai';
$aipkit_autogpt_setup_is_pro = class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan();
$aipkit_autogpt_setup_default_temperature = isset($cw_default_temperature) ? $cw_default_temperature : '1';
$aipkit_autogpt_setup_reasoning_options = [
    'none' => __('None', 'gpt3-ai-content-generator'),
    'low' => __('Low', 'gpt3-ai-content-generator'),
    'medium' => __('Medium', 'gpt3-ai-content-generator'),
    'high' => __('High', 'gpt3-ai-content-generator'),
    'xhigh' => __('XHigh', 'gpt3-ai-content-generator'),
];

$aipkit_autogpt_setup_base = 'aipkit_task_' . $aipkit_autogpt_setup_scope;
$aipkit_autogpt_setup_provider_id = $aipkit_autogpt_setup_base . '_ai_provider';
$aipkit_autogpt_setup_model_id = $aipkit_autogpt_setup_base . '_ai_model';
$aipkit_autogpt_setup_selection_id = $aipkit_autogpt_setup_base . '_ai_selection';
$aipkit_autogpt_setup_temperature_id = $aipkit_autogpt_setup_base . '_ai_temperature';
$aipkit_autogpt_setup_reasoning_row_class = $aipkit_autogpt_setup_base . '_reasoning_effort_field';
$aipkit_autogpt_setup_reasoning_id = $aipkit_autogpt_setup_base . '_reasoning_effort';
$aipkit_autogpt_setup_advanced_trigger_id = 'aipkit_autogpt_' . $aipkit_autogpt_setup_scope . '_ai_advanced_trigger';
$aipkit_autogpt_setup_advanced_popover_id = 'aipkit_autogpt_' . $aipkit_autogpt_setup_scope . '_ai_advanced_popover';
$aipkit_autogpt_setup_prompt_trigger_id = 'aipkit_task_' . $aipkit_autogpt_setup_scope . '_prompt_trigger';
$aipkit_autogpt_setup_prompt_popover_id = 'aipkit_autogpt_' . $aipkit_autogpt_setup_scope . '_prompt_settings_popover';
$aipkit_autogpt_setup_content_length_id = $aipkit_autogpt_setup_base . '_content_length';
$aipkit_autogpt_setup_content_max_tokens_id = $aipkit_autogpt_setup_base . '_content_max_tokens';

?>
<div class="aipkit_content_writer_inputs aipkit_autogpt_setup_fields">
    <select
        id="<?php echo esc_attr($aipkit_autogpt_setup_provider_id); ?>"
        name="<?php echo esc_attr($aipkit_autogpt_setup_name_prefix . 'ai_provider'); ?>"
        class="aipkit_autosave_trigger"
        data-aipkit-provider-notice-target="aipkit_provider_notice_autogpt"
        data-aipkit-provider-notice-defer="1"
        hidden
        aria-hidden="true"
        tabindex="-1"
    >
        <?php foreach ($aipkit_autogpt_setup_providers as $aipkit_autogpt_setup_provider_name) : ?>
            <?php
            $aipkit_autogpt_setup_provider_value = strtolower((string) $aipkit_autogpt_setup_provider_name);
            $aipkit_autogpt_setup_provider_disabled = ($aipkit_autogpt_setup_provider_name === 'Ollama' && !$aipkit_autogpt_setup_is_pro);
            $aipkit_autogpt_setup_provider_display_name = class_exists('\\WPAICG\\AIPKit_Providers')
                ? \WPAICG\AIPKit_Providers::get_provider_display_name((string) $aipkit_autogpt_setup_provider_name)
                : ((string) $aipkit_autogpt_setup_provider_name === 'Claude' ? __('Anthropic', 'gpt3-ai-content-generator') : (string) $aipkit_autogpt_setup_provider_name);
            $aipkit_autogpt_setup_provider_label = $aipkit_autogpt_setup_provider_disabled
                ? __('Ollama (Pro)', 'gpt3-ai-content-generator')
                : $aipkit_autogpt_setup_provider_display_name;
            ?>
            <option
                value="<?php echo esc_attr($aipkit_autogpt_setup_provider_value); ?>"
                <?php selected($aipkit_autogpt_setup_default_provider, $aipkit_autogpt_setup_provider_value); ?>
                <?php echo $aipkit_autogpt_setup_provider_disabled ? 'disabled' : ''; ?>
            >
                <?php echo esc_html($aipkit_autogpt_setup_provider_label); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select
        id="<?php echo esc_attr($aipkit_autogpt_setup_model_id); ?>"
        name="<?php echo esc_attr($aipkit_autogpt_setup_name_prefix . 'ai_model'); ?>"
        class="aipkit_autosave_trigger screen-reader-text"
        hidden
        aria-hidden="true"
        tabindex="-1"
    ></select>

    <div class="aipkit_cw_ai_row">
        <div class="aipkit_cw_panel_label_wrap">
            <label class="aipkit_cw_panel_label" for="<?php echo esc_attr($aipkit_autogpt_setup_selection_id); ?>">
                <?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?>
            </label>
        </div>
        <div class="aipkit_cw_ai_control aipkit_cw_ai_control--model">
            <select
                id="<?php echo esc_attr($aipkit_autogpt_setup_selection_id); ?>"
                class="aipkit_form-input"
                data-aipkit-picker-title="<?php esc_attr_e('Model', 'gpt3-ai-content-generator'); ?>"
            >
                <option value=""><?php esc_html_e('Loading models...', 'gpt3-ai-content-generator'); ?></option>
            </select>
            <button
                type="button"
                class="aipkit_cw_settings_icon_trigger"
                id="<?php echo esc_attr($aipkit_autogpt_setup_advanced_trigger_id); ?>"
                data-aipkit-popover-target="<?php echo esc_attr($aipkit_autogpt_setup_advanced_popover_id); ?>"
                data-aipkit-popover-placement="left"
                aria-controls="<?php echo esc_attr($aipkit_autogpt_setup_advanced_popover_id); ?>"
                aria-expanded="false"
                aria-label="<?php esc_attr_e('Model settings', 'gpt3-ai-content-generator'); ?>"
                title="<?php esc_attr_e('Model settings', 'gpt3-ai-content-generator'); ?>"
            >
                <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <?php if ($aipkit_autogpt_setup_has_length) : ?>
        <div class="aipkit_cw_ai_row">
            <div class="aipkit_cw_panel_label_wrap">
                <label class="aipkit_cw_panel_label" for="<?php echo esc_attr($aipkit_autogpt_setup_content_length_id); ?>">
                    <?php esc_html_e('Length', 'gpt3-ai-content-generator'); ?>
                </label>
            </div>
            <div class="aipkit_cw_ai_control aipkit_cw_ai_control--compact">
                <select
                    id="<?php echo esc_attr($aipkit_autogpt_setup_content_length_id); ?>"
                    name="<?php echo esc_attr($aipkit_autogpt_setup_name_prefix . 'content_length'); ?>"
                    class="aipkit_autosave_trigger aipkit_form-input aipkit_cw_blended_chevron_select"
                >
                    <?php foreach ($aipkit_autogpt_setup_content_length_options as $aipkit_autogpt_setup_length_value => $aipkit_autogpt_setup_length_label) : ?>
                        <option value="<?php echo esc_attr($aipkit_autogpt_setup_length_value); ?>" <?php selected($aipkit_autogpt_setup_length_value, $aipkit_autogpt_setup_default_length); ?>>
                            <?php echo esc_html($aipkit_autogpt_setup_length_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    <?php endif; ?>

    <div class="aipkit_cw_ai_row">
        <div class="aipkit_cw_panel_label_wrap">
            <div class="aipkit_cw_panel_label">
                <?php echo esc_html($aipkit_autogpt_setup_prompt_label); ?>
            </div>
        </div>
        <div class="aipkit_cw_ai_control aipkit_cw_ai_control--compact">
            <button
                type="button"
                id="<?php echo esc_attr($aipkit_autogpt_setup_prompt_trigger_id); ?>"
                class="button button-secondary aipkit_btn aipkit_btn-secondary aipkit_cw_button_match<?php echo $aipkit_autogpt_setup_prompt_mode === 'popover' ? ' aipkit_cw_popover_trigger' : ''; ?>"
                <?php if ($aipkit_autogpt_setup_prompt_mode === 'popover') : ?>
                    data-aipkit-popover-target="<?php echo esc_attr($aipkit_autogpt_setup_prompt_target); ?>"
                    data-aipkit-popover-placement="left"
                <?php else : ?>
                    data-aipkit-flyout-target="<?php echo esc_attr($aipkit_autogpt_setup_prompt_target); ?>"
                <?php endif; ?>
                aria-controls="<?php echo esc_attr($aipkit_autogpt_setup_prompt_target); ?>"
                aria-expanded="false"
            >
                <?php esc_html_e('Customize', 'gpt3-ai-content-generator'); ?>
            </button>
        </div>
    </div>

    <?php if ($aipkit_autogpt_setup_extra_rows_include !== '' && file_exists($aipkit_autogpt_setup_extra_rows_include)) : ?>
        <?php include $aipkit_autogpt_setup_extra_rows_include; ?>
    <?php endif; ?>
</div>

<div
    class="aipkit_model_settings_popover aipkit_cw_settings_popover"
    id="<?php echo esc_attr($aipkit_autogpt_setup_advanced_popover_id); ?>"
    aria-hidden="true"
>
    <div
        class="aipkit_model_settings_popover_panel aipkit_cw_settings_popover_panel aipkit_cw_ai_advanced_popover_panel"
        role="dialog"
        aria-label="<?php esc_attr_e('Model settings', 'gpt3-ai-content-generator'); ?>"
    >
        <div class="aipkit_model_settings_popover_header aipkit_cw_settings_sheet_header">
            <span class="aipkit_model_settings_popover_title"><?php esc_html_e('Model settings', 'gpt3-ai-content-generator'); ?></span>
        </div>
        <div class="aipkit_model_settings_popover_body aipkit_cw_settings_popover_body aipkit_cw_settings_sheet_body">
            <div class="aipkit_popover_options_list">
                <div class="aipkit_popover_option_row">
                    <div class="aipkit_popover_option_main">
                        <div class="aipkit_cw_settings_option_text">
                            <label class="aipkit_popover_option_label" for="<?php echo esc_attr($aipkit_autogpt_setup_temperature_id); ?>">
                                <?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?>
                            </label>
                            <span class="aipkit_popover_option_helper">
                                <?php echo esc_html($aipkit_autogpt_setup_model_helper); ?>
                            </span>
                        </div>
                        <input
                            type="number"
                            id="<?php echo esc_attr($aipkit_autogpt_setup_temperature_id); ?>"
                            name="<?php echo esc_attr($aipkit_autogpt_setup_name_prefix . 'ai_temperature'); ?>"
                            class="aipkit_form-input aipkit_autosave_trigger aipkit_popover_option_input aipkit_popover_option_input--compact aipkit_cw_ai_advanced_number"
                            min="0"
                            max="2"
                            step="0.1"
                            value="<?php echo esc_attr($aipkit_autogpt_setup_default_temperature); ?>"
                            inputmode="decimal"
                        />
                    </div>
                </div>

                <?php if ($aipkit_autogpt_setup_has_max_tokens) : ?>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <div class="aipkit_cw_settings_option_text">
                                <label class="aipkit_popover_option_label" for="<?php echo esc_attr($aipkit_autogpt_setup_content_max_tokens_id); ?>">
                                    <?php esc_html_e('Max Tokens', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <span class="aipkit_popover_option_helper">
                                    <?php echo esc_html($aipkit_autogpt_setup_max_tokens_helper); ?>
                                </span>
                            </div>
                            <input
                                type="number"
                                id="<?php echo esc_attr($aipkit_autogpt_setup_content_max_tokens_id); ?>"
                                name="<?php echo esc_attr($aipkit_autogpt_setup_name_prefix . 'content_max_tokens'); ?>"
                                class="aipkit_form-input aipkit_autosave_trigger aipkit_popover_option_input aipkit_popover_option_input--compact aipkit_cw_ai_advanced_number"
                                min="1"
                                step="1"
                                value="<?php echo esc_attr($aipkit_autogpt_setup_default_max_tokens); ?>"
                                inputmode="numeric"
                            />
                        </div>
                    </div>
                <?php endif; ?>

                <div class="aipkit_popover_option_row <?php echo esc_attr($aipkit_autogpt_setup_reasoning_row_class); ?>" hidden>
                    <div class="aipkit_popover_option_main">
                        <div class="aipkit_cw_settings_option_text">
                            <label class="aipkit_popover_option_label" for="<?php echo esc_attr($aipkit_autogpt_setup_reasoning_id); ?>">
                                <?php esc_html_e('Reasoning', 'gpt3-ai-content-generator'); ?>
                            </label>
                            <span class="aipkit_popover_option_helper">
                                <?php echo esc_html($aipkit_autogpt_setup_reasoning_helper); ?>
                            </span>
                        </div>
                        <select
                            id="<?php echo esc_attr($aipkit_autogpt_setup_reasoning_id); ?>"
                            name="<?php echo esc_attr($aipkit_autogpt_setup_name_prefix . 'reasoning_effort'); ?>"
                            class="aipkit_autosave_trigger aipkit_popover_option_select aipkit_popover_option_select--fit aipkit_cw_blended_chevron_select"
                        >
                            <?php foreach ($aipkit_autogpt_setup_reasoning_options as $aipkit_autogpt_setup_reasoning_value => $aipkit_autogpt_setup_reasoning_label) : ?>
                                <option value="<?php echo esc_attr($aipkit_autogpt_setup_reasoning_value); ?>" <?php selected($aipkit_autogpt_setup_reasoning_value, 'none'); ?>>
                                    <?php echo esc_html($aipkit_autogpt_setup_reasoning_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($aipkit_autogpt_setup_prompt_mode === 'popover' && $aipkit_autogpt_setup_prompt_include !== '' && file_exists($aipkit_autogpt_setup_prompt_include)) : ?>
    <div
        class="aipkit_model_settings_popover aipkit_cw_settings_popover aipkit_cw_prompt_popover"
        id="<?php echo esc_attr($aipkit_autogpt_setup_prompt_popover_id); ?>"
        aria-hidden="true"<?php foreach ($aipkit_autogpt_setup_prompt_root_attrs as $aipkit_autogpt_setup_attr => $aipkit_autogpt_setup_value) : ?>
            <?php if ($aipkit_autogpt_setup_value === null || $aipkit_autogpt_setup_value === false) : ?>
                <?php continue; ?>
            <?php endif; ?>
            <?php if ($aipkit_autogpt_setup_value === true) : ?>
                <?php echo ' ' . esc_attr((string) $aipkit_autogpt_setup_attr); ?>
            <?php else : ?>
                <?php printf(' %1$s="%2$s"', esc_attr((string) $aipkit_autogpt_setup_attr), esc_attr((string) $aipkit_autogpt_setup_value)); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    >
        <div
            class="aipkit_model_settings_popover_panel aipkit_cw_settings_popover_panel aipkit_cw_prompts_panel aipkit_cw_prompt_popover_panel"
            role="dialog"
            aria-label="<?php echo esc_attr($aipkit_autogpt_setup_prompt_popover_title); ?>"
        >
            <div class="aipkit_model_settings_popover_header aipkit_cw_settings_sheet_header<?php echo $aipkit_autogpt_setup_prompt_show_back_button ? ' aipkit_cw_prompt_sheet_header' : ''; ?>">
                <?php if ($aipkit_autogpt_setup_prompt_show_back_button) : ?>
                    <button
                        type="button"
                        class="aipkit_cw_prompt_nav_back is-hidden"
                        data-aipkit-popover-view-back
                        aria-label="<?php esc_attr_e('Back to prompts', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Back', 'gpt3-ai-content-generator'); ?>"
                        aria-hidden="true"
                        tabindex="-1"
                    >
                        <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                    </button>
                <?php endif; ?>
                <span class="aipkit_model_settings_popover_title"<?php echo $aipkit_autogpt_setup_prompt_track_title ? ' data-aipkit-popover-title' : ''; ?>>
                    <?php echo esc_html($aipkit_autogpt_setup_prompt_popover_title); ?>
                </span>
            </div>
            <div class="aipkit_model_settings_popover_body aipkit_cw_settings_popover_body aipkit_cw_prompt_popover_body">
                <div class="aipkit_cw_prompt_popover_stage"<?php echo $aipkit_autogpt_setup_prompt_stage_id !== '' ? ' id="' . esc_attr($aipkit_autogpt_setup_prompt_stage_id) . '"' : ''; ?>>
                    <?php include $aipkit_autogpt_setup_prompt_include; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($aipkit_autogpt_setup_prompt_include !== '' && file_exists($aipkit_autogpt_setup_prompt_include)) : ?>
    <?php include $aipkit_autogpt_setup_prompt_include; ?>
<?php endif; ?>

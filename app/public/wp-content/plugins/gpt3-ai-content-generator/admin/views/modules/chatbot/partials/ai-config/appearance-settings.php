<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bot_id = $initial_active_bot_id;
$bot_settings = $active_bot_settings;
$saved_footer_text = $bot_settings['footer_text'] ?? '';
$saved_placeholder = $bot_settings['input_placeholder'] ?? __('Type your message...', 'gpt3-ai-content-generator');
$custom_typing_text = $bot_settings['custom_typing_text'] ?? '';
$retrieving_context_text = $bot_settings['retrieving_context_text'] ?? '';
$enable_fullscreen = $bot_settings['enable_fullscreen']
    ?? \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_FULLSCREEN;
$enable_download = $bot_settings['enable_download']
    ?? \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_DOWNLOAD;
$enable_copy_button = $bot_settings['enable_copy_button']
    ?? \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON;
$enable_conversation_starters = $bot_settings['enable_conversation_starters']
    ?? \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_STARTERS;
$enable_conversation_sidebar = $bot_settings['enable_conversation_sidebar']
    ?? \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR;
$enable_feedback = $bot_settings['enable_feedback']
    ?? \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_FEEDBACK;
$enable_consent_compliance = $bot_settings['enable_consent_compliance']
    ?? \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_CONSENT_COMPLIANCE;
$enable_consent_compliance = in_array($enable_consent_compliance, ['0', '1'], true)
    ? $enable_consent_compliance
    : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_CONSENT_COMPLIANCE;
$consent_title = $bot_settings['consent_title'] ?? __('Consent Required', 'gpt3-ai-content-generator');
$consent_message = $bot_settings['consent_message'] ?? __('Before starting the conversation, please agree to our Terms of Service and Privacy Policy.', 'gpt3-ai-content-generator');
$consent_button = $bot_settings['consent_button'] ?? __('I Agree', 'gpt3-ai-content-generator');
$consent_toggle_id = 'aipkit_bot_' . $bot_id . '_enable_consent_compliance';
$consent_toggle_display_id = $consent_toggle_id . '_display';
$consent_toggle_value = ($consent_feature_available && $enable_consent_compliance === '1') ? '1' : '0';
?>
<div class="aipkit_popover_options_list aipkit_interface_options">
    <div class="aipkit_builder_field aipkit_builder_field--theme-row">
        <div class="aipkit_interface_theme_rows">
            <div class="aipkit_interface_theme_top_row aipkit_interface_theme_top_row--primary">
                <div class="aipkit_interface_theme_top_cell aipkit_interface_theme_top_cell--theme">
                    <?php
                    $theme_dropdown_label = __('Select theme', 'gpt3-ai-content-generator');
                    $saved_theme_key = isset($saved_theme) ? (string) $saved_theme : '';
                    if ($saved_theme_key === 'custom' && !empty($selected_theme_preset_label)) {
                        $theme_dropdown_label = (string) $selected_theme_preset_label;
                    } elseif ($saved_theme_key !== '' && isset($available_themes[$saved_theme_key])) {
                        $theme_dropdown_label = (string) $available_themes[$saved_theme_key];
                    } elseif ($saved_theme_key !== '') {
                        $theme_dropdown_label = ucwords(str_replace(['-', '_'], ' ', $saved_theme_key));
                    }
                    ?>
                    <div class="aipkit_interface_theme_label_row">
                        <label
                            class="aipkit_popover_option_label"
                            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme_dropdown_btn"
                        >
                            <?php esc_html_e('Theme', 'gpt3-ai-content-generator'); ?>
                        </label>
                    </div>
                    <div
                        class="aipkit_popover_multiselect aipkit_interface_theme_dropdown"
                        data-aipkit-theme-dropdown
                        data-placeholder="<?php echo esc_attr__('Select theme', 'gpt3-ai-content-generator'); ?>"
                    >
                        <button
                            type="button"
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme_dropdown_btn"
                            class="aipkit_popover_multiselect_btn"
                            aria-expanded="false"
                            aria-controls="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme_dropdown_panel"
                        >
                            <span class="aipkit_popover_multiselect_label">
                                <?php echo esc_html($theme_dropdown_label); ?>
                            </span>
                        </button>
                        <div
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme_dropdown_panel"
                            class="aipkit_popover_multiselect_panel aipkit_interface_theme_panel"
                            role="menu"
                            hidden
                        >
                            <div class="aipkit_popover_multiselect_options aipkit_popover_multiselect_options--unbounded aipkit_interface_theme_options">
                                <?php foreach ($available_themes as $theme_key => $theme_name) : ?>
                                    <?php
                                    if ($theme_key === 'custom') {
                                        continue;
                                    }
                                    $theme_is_selected = ($saved_theme_key === (string) $theme_key);
                                    ?>
                                    <label class="aipkit_popover_multiselect_item aipkit_interface_theme_item">
                                        <span class="aipkit_interface_theme_item_label">
                                            <input
                                                type="radio"
                                                class="aipkit_interface_theme_radio"
                                                name="aipkit_theme_choice_<?php echo esc_attr($bot_id); ?>"
                                                value="<?php echo esc_attr($theme_key); ?>"
                                                data-theme-value="<?php echo esc_attr($theme_key); ?>"
                                                data-preset-key=""
                                                <?php checked($theme_is_selected, true); ?>
                                            />
                                            <span class="aipkit_popover_multiselect_text"><?php echo esc_html($theme_name); ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>

                                <?php if (isset($available_themes['custom']) && !empty($custom_theme_presets)) : ?>
                                    <?php foreach ($custom_theme_presets as $preset) : ?>
                                        <?php
                                        if (!is_array($preset)) {
                                            continue;
                                        }
                                        $preset_key = isset($preset['key']) ? sanitize_key((string) $preset['key']) : '';
                                        $preset_label = isset($preset['label']) ? (string) $preset['label'] : '';
                                        if ($preset_key === '' || $preset_label === '') {
                                            continue;
                                        }
                                        $preset_is_selected = ($saved_theme_key === 'custom' && $selected_theme_preset_key === $preset_key);
                                        ?>
                                        <label class="aipkit_popover_multiselect_item aipkit_interface_theme_item">
                                            <span class="aipkit_interface_theme_item_label">
                                                <input
                                                    type="radio"
                                                    class="aipkit_interface_theme_radio"
                                                    name="aipkit_theme_choice_<?php echo esc_attr($bot_id); ?>"
                                                    value="custom"
                                                    data-theme-value="custom"
                                                    data-preset-key="<?php echo esc_attr($preset_key); ?>"
                                                    <?php checked($preset_is_selected, true); ?>
                                                    <?php disabled($aipkit_hide_custom_theme); ?>
                                                />
                                                <span class="aipkit_popover_multiselect_text"><?php echo esc_html($preset_label); ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (isset($available_themes['custom'])) : ?>
                                    <?php $custom_theme_selected = ($saved_theme_key === 'custom' && $selected_theme_preset_key === ''); ?>
                                    <div class="aipkit_popover_multiselect_item aipkit_interface_theme_item aipkit_interface_theme_item--custom">
                                        <label class="aipkit_interface_theme_item_label">
                                            <input
                                                type="radio"
                                                class="aipkit_interface_theme_radio"
                                                name="aipkit_theme_choice_<?php echo esc_attr($bot_id); ?>"
                                                value="custom"
                                                data-theme-value="custom"
                                                data-preset-key=""
                                                <?php checked($custom_theme_selected, true); ?>
                                                <?php disabled($aipkit_hide_custom_theme); ?>
                                            />
                                            <span class="aipkit_popover_multiselect_text"><?php echo esc_html($available_themes['custom']); ?></span>
                                        </label>
                                        <button
                                            type="button"
                                            class="aipkit_popover_option_btn aipkit_theme_config_btn aipkit_theme_config_btn--inline"
                                            aria-expanded="false"
                                            aria-controls="aipkit_custom_theme_flyout"
                                            data-aipkit-theme-custom-edit
                                            <?php echo $aipkit_hide_custom_theme ? 'hidden' : ''; ?>
                                            <?php disabled($aipkit_hide_custom_theme); ?>
                                        >
                                            <?php esc_html_e('Edit', 'gpt3-ai-content-generator'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <select
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme"
                            name="theme"
                            class="aipkit_popover_option_select aipkit_theme_hidden_select"
                        >
                            <?php foreach ($available_themes as $theme_key => $theme_name) : ?>
                                <?php
                                if ($theme_key === 'custom') {
                                    continue;
                                }
                                $theme_is_selected = ((string) $saved_theme === (string) $theme_key);
                                ?>
                                <option
                                    value="<?php echo esc_attr($theme_key); ?>"
                                    <?php selected($theme_is_selected, true); ?>
                                >
                                    <?php echo esc_html($theme_name); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (isset($available_themes['custom']) && !empty($custom_theme_presets)) : ?>
                                <?php foreach ($custom_theme_presets as $preset) : ?>
                                    <?php
                                    if (!is_array($preset)) {
                                        continue;
                                    }
                                    $preset_key = isset($preset['key']) ? sanitize_key((string) $preset['key']) : '';
                                    $preset_label = isset($preset['label']) ? (string) $preset['label'] : '';
                                    $preset_primary = isset($preset['primary']) ? (string) $preset['primary'] : '';
                                    $preset_secondary = isset($preset['secondary']) ? (string) $preset['secondary'] : '';
                                    if ($preset_key === '' || $preset_label === '') {
                                        continue;
                                    }
                                    $preset_is_selected = ($saved_theme === 'custom' && $selected_theme_preset_key === $preset_key);
                                    ?>
                                    <option
                                        value="custom"
                                        data-preset-key="<?php echo esc_attr($preset_key); ?>"
                                        data-primary="<?php echo esc_attr($preset_primary); ?>"
                                        data-secondary="<?php echo esc_attr($preset_secondary); ?>"
                                        <?php selected($preset_is_selected, true); ?>
                                        <?php disabled($aipkit_hide_custom_theme); ?>
                                    >
                                        <?php echo esc_html($preset_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (isset($available_themes['custom'])) : ?>
                                <?php $custom_theme_selected = ($saved_theme === 'custom' && $selected_theme_preset_key === ''); ?>
                                <option
                                    value="custom"
                                    <?php selected($custom_theme_selected, true); ?>
                                    <?php disabled($aipkit_hide_custom_theme); ?>
                                >
                                    <?php echo esc_html($available_themes['custom']); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                        <input
                            type="hidden"
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme_preset_key"
                            name="theme_preset_key"
                            value="<?php echo esc_attr($selected_theme_preset_key); ?>"
                        />
                    </div>
                </div>
                <div class="aipkit_interface_theme_top_cell aipkit_interface_theme_top_cell--controls aipkit_interface_cell--controls">
                    <label
                        class="aipkit_popover_option_label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_controls_select"
                    >
                        <?php esc_html_e('UI features', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <div
                        class="aipkit_popover_multiselect aipkit_interface_controls_multiselect"
                        data-aipkit-interface-controls-dropdown
                        data-placeholder="<?php echo esc_attr__('Select controls', 'gpt3-ai-content-generator'); ?>"
                    >
                        <button
                            type="button"
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_controls_select"
                            class="aipkit_popover_multiselect_btn"
                            aria-expanded="false"
                            aria-controls="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_controls_panel"
                        >
                            <span class="aipkit_popover_multiselect_label">
                                <?php esc_html_e('Select controls', 'gpt3-ai-content-generator'); ?>
                            </span>
                        </button>
                        <div
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_controls_panel"
                            class="aipkit_popover_multiselect_panel"
                            role="menu"
                            hidden
                        >
                            <div class="aipkit_popover_multiselect_options aipkit_popover_multiselect_options--unbounded">
                                <label class="aipkit_popover_multiselect_item aipkit_interface_control_item">
                                    <input
                                        type="checkbox"
                                        class="aipkit_interface_control_option"
                                        value="enable_download"
                                        <?php checked($enable_download, '1'); ?>
                                    />
                                    <span class="aipkit_popover_multiselect_text"><?php esc_html_e('Download', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <label class="aipkit_popover_multiselect_item aipkit_interface_control_item">
                                    <input
                                        type="checkbox"
                                        class="aipkit_interface_control_option"
                                        value="enable_copy_button"
                                        <?php checked($enable_copy_button, '1'); ?>
                                    />
                                    <span class="aipkit_popover_multiselect_text"><?php esc_html_e('Copy', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <label class="aipkit_popover_multiselect_item aipkit_interface_control_item">
                                    <input
                                        type="checkbox"
                                        class="aipkit_interface_control_option"
                                        value="enable_feedback"
                                        <?php checked($enable_feedback, '1'); ?>
                                    />
                                    <span class="aipkit_popover_multiselect_text"><?php esc_html_e('Feedback', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <label class="aipkit_popover_multiselect_item aipkit_interface_control_item">
                                    <input
                                        type="checkbox"
                                        class="aipkit_interface_control_option"
                                        value="enable_fullscreen"
                                        <?php checked($enable_fullscreen, '1'); ?>
                                    />
                                    <span class="aipkit_popover_multiselect_text"><?php esc_html_e('Fullscreen', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <label
                                    class="aipkit_popover_multiselect_item aipkit_interface_control_item aipkit_interface_control_item--sidebar<?php echo ($popup_enabled === '1') ? ' is-disabled' : ''; ?>"
                                >
                                    <input
                                        type="checkbox"
                                        class="aipkit_interface_control_option aipkit_interface_control_option--sidebar"
                                        value="enable_conversation_sidebar"
                                        <?php checked($enable_conversation_sidebar, '1'); ?>
                                        <?php disabled($popup_enabled === '1'); ?>
                                    />
                                    <span class="aipkit_popover_multiselect_text"><?php esc_html_e('Sidebar', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <div class="aipkit_popover_multiselect_item aipkit_interface_control_item aipkit_interface_control_item--starters">
                                    <label class="aipkit_interface_control_item_main">
                                        <input
                                            type="checkbox"
                                            class="aipkit_interface_control_option"
                                            value="enable_conversation_starters"
                                            <?php checked($enable_conversation_starters, '1'); ?>
                                        />
                                        <span class="aipkit_popover_multiselect_text"><?php esc_html_e('Starters', 'gpt3-ai-content-generator'); ?></span>
                                    </label>
                                    <button
                                        type="button"
                                        class="aipkit_popover_option_btn aipkit_starters_config_btn aipkit_starters_config_btn--inline"
                                        data-feature="conversation_starters"
                                        aria-expanded="false"
                                        aria-controls="aipkit_starters_flyout"
                                        <?php echo ((string) $enable_conversation_starters === '1') ? '' : 'hidden'; ?>
                                    >
                                        <?php esc_html_e('Edit', 'gpt3-ai-content-generator'); ?>
                                    </button>
                                </div>
                                <div class="aipkit_popover_multiselect_item aipkit_interface_control_item aipkit_interface_control_item--consent<?php echo !$consent_feature_available ? ' is-disabled' : ''; ?>">
                                    <label class="aipkit_interface_control_item_main" for="<?php echo esc_attr($consent_toggle_display_id); ?>">
                                        <input
                                            type="checkbox"
                                            id="<?php echo esc_attr($consent_toggle_display_id); ?>"
                                            class="aipkit_interface_control_option"
                                            value="enable_consent_compliance"
                                            <?php checked($consent_toggle_value, '1'); ?>
                                            <?php disabled(!$consent_feature_available); ?>
                                        />
                                        <span class="aipkit_popover_multiselect_text"><?php esc_html_e('Consent', 'gpt3-ai-content-generator'); ?></span>
                                    </label>
                                    <?php if ($consent_feature_available) : ?>
                                        <button
                                            type="button"
                                            class="aipkit_popover_option_btn aipkit_consent_config_btn aipkit_consent_config_btn--inline"
                                            data-feature="consent_notice"
                                            aria-expanded="false"
                                            aria-controls="aipkit_consent_flyout"
                                            <?php echo ($enable_consent_compliance === '1') ? '' : 'hidden'; ?>
                                        >
                                            <?php esc_html_e('Edit', 'gpt3-ai-content-generator'); ?>
                                        </button>
                                    <?php else : ?>
                                        <a
                                            class="aipkit_tools_enabled_item_upgrade aipkit_popover_upgrade_link"
                                            href="<?php echo esc_url($pricing_url); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="aipkit_interface_control_hidden_fields aipkit_sidebar_toggle_group"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_sidebar_group"

                        aria-hidden="true"
                    >
                        <span class="aipkit_interface_toggle_label screen-reader-text">
                            <?php esc_html_e('Sidebar', 'gpt3-ai-content-generator'); ?>
                        </span>
                        <select
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_download"
                            name="enable_download"
                            class="aipkit_form-input aipkit_popover_option_select aipkit_toggle_switch_select aipkit_interface_control_hidden_select"
                        >
                            <option value="1" <?php selected($enable_download, '1'); ?>><?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?></option>
                            <option value="0" <?php selected($enable_download, '0'); ?>><?php esc_html_e('No', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                        <select
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_copy_button"
                            name="enable_copy_button"
                            class="aipkit_form-input aipkit_popover_option_select aipkit_toggle_switch_select aipkit_interface_control_hidden_select"
                        >
                            <option value="1" <?php selected($enable_copy_button, '1'); ?>><?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?></option>
                            <option value="0" <?php selected($enable_copy_button, '0'); ?>><?php esc_html_e('No', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                        <select
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_feedback"
                            name="enable_feedback"
                            class="aipkit_form-input aipkit_popover_option_select aipkit_toggle_switch_select aipkit_interface_control_hidden_select"
                        >
                            <option value="1" <?php selected($enable_feedback, '1'); ?>><?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?></option>
                            <option value="0" <?php selected($enable_feedback, '0'); ?>><?php esc_html_e('No', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                        <select
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_fullscreen"
                            name="enable_fullscreen"
                            class="aipkit_form-input aipkit_popover_option_select aipkit_toggle_switch_select aipkit_interface_control_hidden_select"
                        >
                            <option value="1" <?php selected($enable_fullscreen, '1'); ?>><?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?></option>
                            <option value="0" <?php selected($enable_fullscreen, '0'); ?>><?php esc_html_e('No', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                        <select
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_conversation_sidebar"
                            name="enable_conversation_sidebar"
                            class="aipkit_form-input aipkit_popover_option_select aipkit_toggle_switch_select aipkit_sidebar_toggle_switch aipkit_interface_control_hidden_select"
                            <?php disabled($popup_enabled === '1'); ?>
                        >
                            <option value="1" <?php selected($enable_conversation_sidebar, '1'); ?>><?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?></option>
                            <option value="0" <?php selected($enable_conversation_sidebar, '0'); ?>><?php esc_html_e('No', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                        <select
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_conversation_starters"
                            name="enable_conversation_starters"
                            class="aipkit_form-input aipkit_popover_option_select aipkit_toggle_switch_select aipkit_starters_toggle_switch aipkit_interface_control_hidden_select"
                        >
                            <option value="1" <?php selected($enable_conversation_starters, '1'); ?>><?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?></option>
                            <option value="0" <?php selected($enable_conversation_starters, '0'); ?>><?php esc_html_e('No', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                        <select
                            id="<?php echo esc_attr($consent_toggle_id); ?>"
                            name="enable_consent_compliance"
                            class="aipkit_form-input aipkit_popover_option_select aipkit_toggle_switch_select aipkit_consent_toggle_switch aipkit_interface_control_hidden_select"
                            <?php disabled(!$consent_feature_available); ?>
                        >
                            <option value="1" <?php selected($consent_toggle_value, '1'); ?>><?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?></option>
                            <option value="0" <?php selected($consent_toggle_value, '0'); ?>><?php esc_html_e('No', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="aipkit_interface_theme_top_row aipkit_interface_theme_top_row--primary">
                <div class="aipkit_interface_theme_top_cell">
                    <div class="aipkit_interface_identity_stack_item">
                        <label
                            class="aipkit_popover_option_label"
                            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_greeting"
                        >
                            <?php esc_html_e('Greeting', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <input
                            type="text"
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_greeting"
                            name="greeting"
                            class="aipkit_popover_option_input aipkit_popover_option_input--wide aipkit_popover_option_input--framed"
                            value="<?php echo esc_attr($saved_greeting); ?>"
                            placeholder="<?php esc_attr_e('Hello there!', 'gpt3-ai-content-generator'); ?>"
                            autocomplete="off"
                            data-lpignore="true"
                            data-1p-ignore="true"
                            data-form-type="other"
                        />
                    </div>
                </div>
                <div class="aipkit_interface_theme_top_cell">
                    <div class="aipkit_interface_identity_stack_item">
                        <label
                            class="aipkit_popover_option_label"
                            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_subgreeting"
                        >
                            <?php esc_html_e('Subgreeting', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <input
                            type="text"
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_subgreeting"
                            name="subgreeting"
                            class="aipkit_popover_option_input aipkit_popover_option_input--wide aipkit_popover_option_input--framed"
                            value="<?php echo esc_attr($saved_subgreeting); ?>"
                            placeholder="<?php esc_attr_e('How can I help you today?', 'gpt3-ai-content-generator'); ?>"
                            autocomplete="off"
                            data-lpignore="true"
                            data-1p-ignore="true"
                            data-form-type="other"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="aipkit_interface_section aipkit_interface_section--text">
        <div class="aipkit_interface_grid aipkit_interface_grid--text">
            <div class="aipkit_popover_option_row aipkit_interface_cell aipkit_interface_cell--text">
                <div class="aipkit_popover_option_main">
                    <label
                        class="aipkit_popover_option_label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_placeholder"
                    >
                        <?php esc_html_e('Placeholder', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_placeholder"
                        name="input_placeholder"
                        class="aipkit_popover_option_input aipkit_popover_option_input--wide aipkit_popover_option_input--framed"
                        value="<?php echo esc_attr($saved_placeholder); ?>"
                        placeholder="<?php esc_attr_e('Type your message...', 'gpt3-ai-content-generator'); ?>"
                        autocomplete="off"
                        data-lpignore="true"
                        data-1p-ignore="true"
                        data-form-type="other"
                    />
                </div>
            </div>
            <div class="aipkit_popover_option_row aipkit_interface_cell aipkit_interface_cell--text">
                <div class="aipkit_popover_option_main">
                    <label
                        class="aipkit_popover_option_label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_footer_text"
                    >
                        <?php esc_html_e('Footer', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_footer_text"
                        name="footer_text"
                        class="aipkit_popover_option_input aipkit_popover_option_input--wide aipkit_popover_option_input--framed"
                        value="<?php echo esc_attr($saved_footer_text); ?>"
                        placeholder="<?php esc_attr_e('Powered by AI', 'gpt3-ai-content-generator'); ?>"
                        autocomplete="off"
                        data-lpignore="true"
                        data-1p-ignore="true"
                        data-form-type="other"
                    />
                </div>
            </div>
            <div class="aipkit_popover_option_row aipkit_interface_cell aipkit_interface_cell--text">
                <div class="aipkit_popover_option_main">
                    <label
                        class="aipkit_popover_option_label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_custom_typing_text"
                    >
                        <?php esc_html_e('Typing text', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_custom_typing_text"
                        name="custom_typing_text"
                        class="aipkit_popover_option_input aipkit_popover_option_input--wide aipkit_popover_option_input--framed"
                        value="<?php echo esc_attr($custom_typing_text); ?>"
                        placeholder="<?php esc_attr_e('Thinking', 'gpt3-ai-content-generator'); ?>"
                        autocomplete="off"
                        data-lpignore="true"
                        data-1p-ignore="true"
                        data-form-type="other"
                    />
                </div>
            </div>
            <div
                class="aipkit_popover_option_row aipkit_interface_cell aipkit_interface_cell--text"
            >
                <div class="aipkit_popover_option_main">
                    <label
                        class="aipkit_popover_option_label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_retrieving_context_text"
                    >
                        <?php esc_html_e('Status text', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_retrieving_context_text"
                        name="retrieving_context_text"
                        class="aipkit_popover_option_input aipkit_popover_option_input--wide aipkit_popover_option_input--framed"
                        value="<?php echo esc_attr($retrieving_context_text); ?>"
                        placeholder="<?php esc_attr_e('Retrieving context...', 'gpt3-ai-content-generator'); ?>"
                        autocomplete="off"
                        data-lpignore="true"
                        data-1p-ignore="true"
                        data-form-type="other"
                    />
                </div>
            </div>
        </div>
    </div>
</div>

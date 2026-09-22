<?php

/**
 * Partial: AI Form Editor - Main Settings
 * Renders the right-hand editor column for model, prompt, connected apps,
 * and advanced generation settings.
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

// Variables passed from parent (form-editor.php):
// $providers, $default_temp, $default_max_tokens, $default_top_p, $default_frequency_penalty, $default_presence_penalty
// Variables passed down from ai-forms/index.php:
// $openai_vector_stores, $pinecone_indexes, $qdrant_collections, $chroma_collections, $openai_embedding_models, $google_embedding_models
?>
<div class="aipkit_ai_form_editor_sidebar">
    <select
        id="aipkit_ai_form_ai_provider"
        name="ai_provider"
        class="aipkit_form-input aipkit_ai_form_hidden_ai_field"
        data-aipkit-provider-notice-target="aipkit_provider_notice_ai_forms"
        data-aipkit-provider-notice-defer="1"
        hidden
        aria-hidden="true"
        tabindex="-1"
    >
        <?php foreach ($providers as $p_value) :
            $disabled = false;
            $label = class_exists('\\WPAICG\\AIPKit_Providers')
                ? \WPAICG\AIPKit_Providers::get_provider_display_name((string) $p_value)
                : ((string) $p_value === 'Claude' ? __('Anthropic', 'gpt3-ai-content-generator') : (string) $p_value);
            if ($p_value === 'Ollama' && (empty($is_pro) || !$is_pro)) {
                $disabled = true;
                $label = __('Ollama (Pro)', 'gpt3-ai-content-generator');
            }
            ?>
            <option value="<?php echo esc_attr($p_value); ?>" <?php echo $disabled ? 'disabled' : ''; ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
    <input
        type="hidden"
        id="aipkit_ai_form_ai_model"
        name="ai_model"
        class="aipkit_ai_form_hidden_ai_field"
        value=""
    >

    <section class="aipkit_ai_form_inspector_card aipkit_ai_form_inspector_card--model">
        <div class="aipkit_ai_form_inspector_card_header">
            <div class="aipkit_ai_form_inspector_card_header_copy">
                <h3 class="aipkit_ai_form_inspector_card_title"><?php esc_html_e('AI', 'gpt3-ai-content-generator'); ?></h3>
            </div>
        </div>
        <div class="aipkit_ai_form_inspector_card_body">
            <div class="aipkit_ai_form_model_field">
                <label class="aipkit_ai_form_model_label" for="aipkit_ai_form_ai_selection">
                    <?php esc_html_e('Model', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_ai_form_model_control">
                    <select
                        id="aipkit_ai_form_ai_selection"
                        class="aipkit_form-input"
                        data-aipkit-picker-title="<?php esc_attr_e('Model', 'gpt3-ai-content-generator'); ?>"
                    >
                        <option value=""><?php esc_html_e('Loading models...', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                    <button
                        type="button"
                        class="aipkit_ai_form_model_settings_trigger aipkit_ai_form_model_settings_icon"
                        id="aipkit_ai_form_model_settings_trigger"
                        data-aipkit-popover-target="aipkit_ai_form_model_settings_popover"
                        data-aipkit-popover-placement="left"
                        aria-controls="aipkit_ai_form_model_settings_popover"
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e('Model settings', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Model settings', 'gpt3-ai-content-generator'); ?>"
                    >
                        <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                    </button>

                    <div class="aipkit_model_settings_popover aipkit_cw_settings_popover" id="aipkit_ai_form_model_settings_popover" aria-hidden="true">
                        <div class="aipkit_model_settings_popover_panel aipkit_cw_settings_popover_panel aipkit_cw_ai_advanced_popover_panel aipkit_ai_form_model_settings_popover_panel" role="dialog" aria-label="<?php esc_attr_e('Model settings', 'gpt3-ai-content-generator'); ?>">
                            <div class="aipkit_model_settings_popover_header aipkit_cw_settings_sheet_header">
                                <span class="aipkit_model_settings_popover_title"><?php esc_html_e('Model settings', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <div class="aipkit_model_settings_popover_body aipkit_cw_settings_popover_body aipkit_cw_settings_sheet_body">
                                <div class="aipkit_popover_options_list">
                                    <div class="aipkit_popover_option_row">
                                        <div class="aipkit_popover_option_main">
                                            <div class="aipkit_cw_settings_option_text">
                                                <label class="aipkit_popover_option_label" for="aipkit_ai_form_temperature"><?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?></label>
                                                <span class="aipkit_popover_option_helper"><?php esc_html_e('More varied output.', 'gpt3-ai-content-generator'); ?></span>
                                            </div>
                                            <input type="number" id="aipkit_ai_form_temperature" name="temperature" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--compact aipkit_ai_form_model_number" min="0" max="2" step="0.1" value="<?php echo esc_attr($default_temp); ?>" inputmode="decimal" />
                                        </div>
                                    </div>
                                    <div class="aipkit_popover_option_row">
                                        <div class="aipkit_popover_option_main">
                                            <div class="aipkit_cw_settings_option_text">
                                                <label class="aipkit_popover_option_label" for="aipkit_ai_form_max_tokens"><?php esc_html_e('Max Tokens', 'gpt3-ai-content-generator'); ?></label>
                                                <span class="aipkit_popover_option_helper"><?php esc_html_e('Response length limit.', 'gpt3-ai-content-generator'); ?></span>
                                            </div>
                                            <input type="number" id="aipkit_ai_form_max_tokens" name="max_tokens" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--compact aipkit_ai_form_model_number" min="1" max="128000" step="1" value="<?php echo esc_attr($default_max_tokens); ?>" inputmode="numeric" />
                                        </div>
                                    </div>
                                    <div class="aipkit_popover_option_row">
                                        <div class="aipkit_popover_option_main">
                                            <div class="aipkit_cw_settings_option_text">
                                                <label class="aipkit_popover_option_label" for="aipkit_ai_form_top_p"><?php esc_html_e('Top P', 'gpt3-ai-content-generator'); ?></label>
                                                <span class="aipkit_popover_option_helper"><?php esc_html_e('Sampling diversity.', 'gpt3-ai-content-generator'); ?></span>
                                            </div>
                                            <input type="number" id="aipkit_ai_form_top_p" name="top_p" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--compact aipkit_ai_form_model_number" min="0" max="1" step="0.01" value="<?php echo esc_attr($default_top_p); ?>" inputmode="decimal" />
                                        </div>
                                    </div>
                                    <div class="aipkit_popover_option_row">
                                        <div class="aipkit_popover_option_main">
                                            <div class="aipkit_cw_settings_option_text">
                                                <label class="aipkit_popover_option_label" for="aipkit_ai_form_frequency_penalty"><?php esc_html_e('Frequency Penalty', 'gpt3-ai-content-generator'); ?></label>
                                                <span class="aipkit_popover_option_helper"><?php esc_html_e('Reduce repeated wording.', 'gpt3-ai-content-generator'); ?></span>
                                            </div>
                                            <input type="number" id="aipkit_ai_form_frequency_penalty" name="frequency_penalty" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--compact aipkit_ai_form_model_number" min="0" max="2" step="0.1" value="<?php echo esc_attr($default_frequency_penalty); ?>" inputmode="decimal" />
                                        </div>
                                    </div>
                                    <div class="aipkit_popover_option_row">
                                        <div class="aipkit_popover_option_main">
                                            <div class="aipkit_cw_settings_option_text">
                                                <label class="aipkit_popover_option_label" for="aipkit_ai_form_presence_penalty"><?php esc_html_e('Presence Penalty', 'gpt3-ai-content-generator'); ?></label>
                                                <span class="aipkit_popover_option_helper"><?php esc_html_e('Encourage new topics.', 'gpt3-ai-content-generator'); ?></span>
                                            </div>
                                            <input type="number" id="aipkit_ai_form_presence_penalty" name="presence_penalty" class="aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--compact aipkit_ai_form_model_number" min="0" max="2" step="0.1" value="<?php echo esc_attr($default_presence_penalty); ?>" inputmode="decimal" />
                                        </div>
                                    </div>
                                    <div class="aipkit_popover_option_row aipkit_ai_form_reasoning_effort_field" hidden>
                                        <div class="aipkit_popover_option_main">
                                            <div class="aipkit_cw_settings_option_text">
                                                <label class="aipkit_popover_option_label" for="aipkit_ai_form_reasoning_effort"><?php esc_html_e('Reasoning', 'gpt3-ai-content-generator'); ?></label>
                                                <span class="aipkit_popover_option_helper"><?php esc_html_e('More effort for hard tasks.', 'gpt3-ai-content-generator'); ?></span>
                                            </div>
                                            <select id="aipkit_ai_form_reasoning_effort" name="reasoning_effort" class="aipkit_popover_option_select">
                                                <option value="none"><?php esc_html_e('None', 'gpt3-ai-content-generator'); ?></option>
                                                <option value="low"><?php esc_html_e('Low', 'gpt3-ai-content-generator'); ?></option>
                                                <option value="medium"><?php esc_html_e('Medium', 'gpt3-ai-content-generator'); ?></option>
                                                <option value="high"><?php esc_html_e('High', 'gpt3-ai-content-generator'); ?></option>
                                                <option value="xhigh"><?php esc_html_e('XHigh', 'gpt3-ai-content-generator'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="aipkit_ai_form_prompt_field">
                <label class="aipkit_ai_form_model_label aipkit_ai_form_prompt_label" for="aipkit_ai_form_prompt_template">
                    <?php esc_html_e('Prompt', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_builder_textarea_wrap aipkit_ai_form_prompt_wrap">
                    <textarea id="aipkit_ai_form_prompt_template" name="prompt_template" class="aipkit_builder_textarea aipkit_form-input" rows="12" placeholder="<?php esc_attr_e('e.g., Generate a meta description for: {your_field_name}', 'gpt3-ai-content-generator'); ?>"></textarea>
                    <button
                        type="button"
                        class="aipkit_builder_icon_btn aipkit_builder_textarea_expand aipkit_ai_form_prompt_expand"
                        aria-label="<?php esc_attr_e('Expand prompt editor', 'gpt3-ai-content-generator'); ?>"
                    >
                        <span class="dashicons dashicons-editor-expand"></span>
                    </button>
                </div>
                <div class="aipkit_prompt_snippets_container" id="aipkit_prompt_snippets_container">
                </div>
                <div class="aipkit_prompt_validation_area">
                    <button type="button" id="aipkit_validate_prompt_btn" class="aipkit_btn aipkit_btn-secondary aipkit_ai_form_prompt_validate_btn">
                        <span class="dashicons dashicons-editor-spellcheck"></span>
                        <span class="aipkit_btn-text"><?php esc_html_e('Validate', 'gpt3-ai-content-generator'); ?></span>
                    </button>
                    <div id="aipkit_prompt_validation_results" class="aipkit_form-help"></div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="aipkit_ai_form_inspector_card aipkit_ai_form_inspector_card--advanced"
        id="aipkit_ai_form_settings_panel"
    >
        <div class="aipkit_ai_form_inspector_card_header">
            <div class="aipkit_ai_form_inspector_card_header_copy">
                <h3 class="aipkit_ai_form_inspector_card_title"><?php esc_html_e('Context', 'gpt3-ai-content-generator'); ?></h3>
            </div>
        </div>
        <div class="aipkit_ai_form_inspector_card_body aipkit_ai_form_context_body">
            <div class="aipkit_ai_form_model_field aipkit_ai_form_context_field">
                <label class="aipkit_ai_form_model_label" for="aipkit_ai_form_enable_vector_store_display">
                    <?php esc_html_e('Knowledge base', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_ai_form_model_control aipkit_ai_form_context_control">
                    <label class="aipkit_switch aipkit_ai_form_context_switch">
                        <input
                            type="checkbox"
                            id="aipkit_ai_form_enable_vector_store_display"
                            class="aipkit_ai_form_context_enable_select"
                            value="1"
                            aria-label="<?php esc_attr_e('Enable knowledge base', 'gpt3-ai-content-generator'); ?>"
                        >
                        <span class="aipkit_switch_slider"></span>
                    </label>
                    <button
                        type="button"
                        class="aipkit_ai_form_model_settings_icon aipkit_ai_form_context_settings_icon"
                        id="aipkit_ai_form_context_settings_trigger"
                        data-aipkit-popover-target="aipkit_ai_form_context_settings_popover"
                        data-aipkit-popover-placement="top"
                        aria-controls="aipkit_ai_form_context_settings_popover"
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e('Knowledge base settings', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Knowledge base settings', 'gpt3-ai-content-generator'); ?>"
                    >
                        <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="aipkit_model_settings_popover aipkit_cw_settings_popover" id="aipkit_ai_form_context_settings_popover" aria-hidden="true">
                    <div class="aipkit_model_settings_popover_panel aipkit_cw_settings_popover_panel aipkit_cw_ai_advanced_popover_panel aipkit_ai_form_model_settings_popover_panel aipkit_ai_form_context_settings_popover_panel" role="dialog" aria-label="<?php esc_attr_e('Knowledge base settings', 'gpt3-ai-content-generator'); ?>">
                        <div class="aipkit_model_settings_popover_header aipkit_cw_settings_sheet_header">
                            <span class="aipkit_model_settings_popover_title"><?php esc_html_e('Knowledge base', 'gpt3-ai-content-generator'); ?></span>
                            <span class="aipkit_popover_status_inline aipkit_model_sync_status" aria-live="polite"></span>
                        </div>
                        <div class="aipkit_model_settings_popover_body aipkit_cw_settings_popover_body aipkit_cw_settings_sheet_body">
                            <div class="aipkit_model_settings_panel" data-aipkit-settings-panel="context">
                                <?php include __DIR__ . '/vector-config.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="aipkit_ai_form_model_field aipkit_ai_form_context_field">
                <label class="aipkit_ai_form_model_label" for="aipkit_ai_form_web_search_enabled_display">
                    <?php esc_html_e('Web Search', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_ai_form_model_control aipkit_ai_form_context_control">
                    <label class="aipkit_switch aipkit_ai_form_context_switch">
                        <input
                            type="checkbox"
                            id="aipkit_ai_form_web_search_enabled_display"
                            class="aipkit_ai_form_web_search_enable_select"
                            value="1"
                            aria-label="<?php esc_attr_e('Enable web search', 'gpt3-ai-content-generator'); ?>"
                        >
                        <span class="aipkit_switch_slider"></span>
                    </label>
                    <button
                        type="button"
                        class="aipkit_ai_form_model_settings_icon aipkit_ai_form_context_settings_icon"
                        id="aipkit_ai_form_web_search_settings_trigger"
                        data-aipkit-popover-target="aipkit_ai_form_web_search_settings_popover"
                        data-aipkit-popover-placement="top"
                        aria-controls="aipkit_ai_form_web_search_settings_popover"
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e('Web search settings', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Web search settings', 'gpt3-ai-content-generator'); ?>"
                    >
                        <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="aipkit_model_settings_popover aipkit_cw_settings_popover" id="aipkit_ai_form_web_search_settings_popover" aria-hidden="true">
                    <div class="aipkit_model_settings_popover_panel aipkit_cw_settings_popover_panel aipkit_cw_ai_advanced_popover_panel aipkit_ai_form_model_settings_popover_panel aipkit_ai_form_web_search_settings_popover_panel" role="dialog" aria-label="<?php esc_attr_e('Web search settings', 'gpt3-ai-content-generator'); ?>">
                        <div class="aipkit_model_settings_popover_header aipkit_cw_settings_sheet_header">
                            <span class="aipkit_model_settings_popover_title"><?php esc_html_e('Web Search', 'gpt3-ai-content-generator'); ?></span>
                        </div>
                        <div class="aipkit_model_settings_popover_body aipkit_cw_settings_popover_body aipkit_cw_settings_sheet_body">
                            <div class="aipkit_model_settings_panel" data-aipkit-settings-panel="tools">
                                <?php include __DIR__ . '/tools-config.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="aipkit_ai_form_inspector_card aipkit_ai_form_connected_apps_card"
        data-aipkit-ai-form-connected-apps
        data-manage-url="<?php echo esc_url($connected_apps_manage_url); ?>"
    >
        <div class="aipkit_ai_form_inspector_card_header">
            <div class="aipkit_ai_form_inspector_card_header_copy">
                <div class="aipkit_ai_form_inspector_card_title_row">
                    <h3 class="aipkit_ai_form_inspector_card_title"><?php esc_html_e('Connected Apps', 'gpt3-ai-content-generator'); ?></h3>
                    <?php if (!$is_pro): ?>
                        <a
                            href="<?php echo esc_url($upgrade_url); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="aipkit_pro_tag"
                        ><?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?></a>
                    <?php endif; ?>
                </div>
                <?php if ($is_pro): ?>
                    <span
                        class="aipkit_ai_form_connected_apps_summary"
                        data-aipkit-ai-form-connected-apps-summary
                        data-default-summary=""
                    ></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($is_pro): ?>
            <div class="aipkit_chatbot_connected_apps_actions aipkit_ai_form_connected_apps_actions">
                <a
                    href="<?php echo esc_url($connected_apps_manage_url); ?>"
                    class="button button-primary aipkit_btn aipkit_btn-primary"
                    data-aipkit-ai-form-connected-apps-manage
                >
                    <?php esc_html_e('Manage', 'gpt3-ai-content-generator'); ?>
                </a>
            </div>
            <details class="aipkit_ai_form_inline_details aipkit_ai_form_connected_apps_details">
                <summary class="aipkit_ai_form_inline_details_summary">
                    <span><?php esc_html_e('Recipes', 'gpt3-ai-content-generator'); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                </summary>
                <div class="aipkit_ai_form_inline_details_body">
                    <div class="aipkit_chatbot_connected_apps_list" data-aipkit-ai-form-connected-apps-list>
                        <?php $render_ai_form_connected_apps_cards($initial_ai_form_connected_apps); ?>
                    </div>
                    <p class="aipkit_chatbot_connected_apps_empty" data-aipkit-ai-form-connected-apps-empty>
                        <?php esc_html_e('Save this form to connect apps.', 'gpt3-ai-content-generator'); ?>
                    </p>
                </div>
            </details>
        <?php else : ?>
            <div class="aipkit_chatbot_connected_apps_upsell aipkit_ai_form_connected_apps_upsell">
                <p class="aipkit_chatbot_connected_apps_intro_text">
                    <?php esc_html_e('Send form submissions to Slack, HubSpot, Notion, Pipedrive, Zapier, Make, and n8n.', 'gpt3-ai-content-generator'); ?>
                </p>
                <div class="aipkit_chatbot_connected_apps_logo_grid" aria-label="<?php esc_attr_e('Supported app destinations', 'gpt3-ai-content-generator'); ?>">
                    <?php foreach ($connected_apps_supported_destinations as $connected_app_destination) : ?>
                        <span
                            class="aipkit_chatbot_connected_apps_logo_item"
                            title="<?php echo esc_attr((string) $connected_app_destination['name']); ?>"
                        >
                            <img
                                class="aipkit_chatbot_connected_apps_logo"
                                src="<?php echo esc_url((string) $connected_app_destination['logo_url']); ?>"
                                alt="<?php echo esc_attr((string) $connected_app_destination['name']); ?>"
                                loading="lazy"
                                decoding="async"
                            />
                        </span>
                    <?php endforeach; ?>
                    <a
                        href="<?php echo esc_url($upgrade_url); ?>"
                        class="aipkit_btn aipkit_btn-primary aipkit_ai_form_connected_apps_upgrade aipkit_chatbot_connected_apps_grid_cta"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="aipkit_btn-text"><?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?></span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

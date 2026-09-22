<?php

/**
 * Partial: Automated Task Form - Media Settings
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$image_provider_settings_url = admin_url('admin.php?page=wpaicg');
?>

<div class="aipkit_image_settings_redesigned">
    <div class="aipkit_cw_image_section">
        <div class="aipkit_cw_image_hidden_fields" hidden aria-hidden="true">
            <input
                type="checkbox"
                id="aipkit_task_cw_generate_images_enabled"
                name="generate_images_enabled"
                class="aipkit_toggle_switch aipkit_task_cw_image_enable_toggle"
                value="1"
                tabindex="-1"
            >
            <input
                type="checkbox"
                id="aipkit_task_cw_generate_featured_image"
                name="generate_featured_image"
                class="aipkit_toggle_switch"
                value="1"
                tabindex="-1"
            >
            <select id="aipkit_task_cw_image_provider" name="image_provider" tabindex="-1">
                <optgroup label="<?php echo esc_attr__('AI Providers', 'gpt3-ai-content-generator'); ?>">
                    <option value="openai" selected>OpenAI</option>
                    <option value="google">Google</option>
                    <option value="openrouter">OpenRouter</option>
                    <option value="azure">Azure</option>
                    <option value="xai">xAI</option>
                    <option value="replicate"><?php esc_html_e('Replicate', 'gpt3-ai-content-generator'); ?></option>
                </optgroup>
                <optgroup label="<?php echo esc_attr__('Stock Photos', 'gpt3-ai-content-generator'); ?>">
                    <option value="pexels"><?php esc_html_e('Pexels', 'gpt3-ai-content-generator'); ?></option>
                    <option value="pixabay"><?php esc_html_e('Pixabay', 'gpt3-ai-content-generator'); ?></option>
                </optgroup>
            </select>
            <select id="aipkit_task_cw_image_model" name="image_model" tabindex="-1">
                <?php // Populated by JS ?>
            </select>
            <input
                type="hidden"
                id="aipkit_task_cw_image_provider_options"
                name="image_provider_options"
                value="{}"
            >
        </div>

        <div class="aipkit_cw_image_row aipkit_cw_image_row--mode">
            <div class="aipkit_cw_panel_label_wrap">
                <label class="aipkit_cw_panel_label" for="aipkit_task_cw_image_mode_control">
                    <?php esc_html_e('Images', 'gpt3-ai-content-generator'); ?>
                </label>
            </div>
            <div class="aipkit_cw_image_control">
                <select
                    id="aipkit_task_cw_image_mode_control"
                    class="aipkit_form-input aipkit_cw_blended_chevron_select"
                >
                    <option value="off"><?php esc_html_e('Off', 'gpt3-ai-content-generator'); ?></option>
                    <option value="content"><?php esc_html_e('Content', 'gpt3-ai-content-generator'); ?></option>
                    <option value="featured"><?php esc_html_e('Featured', 'gpt3-ai-content-generator'); ?></option>
                    <option value="both"><?php esc_html_e('Content + Featured', 'gpt3-ai-content-generator'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="aipkit_task_cw_image_settings_container" hidden>
        <div class="aipkit_cw_image_section aipkit_task_cw_image_source_section">
            <div class="aipkit_cw_image_row aipkit_cw_image_row--source">
                <div class="aipkit_cw_panel_label_wrap">
                    <label class="aipkit_cw_panel_label" for="aipkit_task_cw_image_selection">
                        <?php esc_html_e('Image source', 'gpt3-ai-content-generator'); ?>
                    </label>
                </div>
                <div class="aipkit_cw_image_control aipkit_cw_image_control--selection">
                    <div class="aipkit_cw_image_selection_inline">
                        <select
                            id="aipkit_task_cw_image_selection"
                            class="aipkit_form-input"
                            data-aipkit-picker-title="<?php echo esc_attr__('Image source', 'gpt3-ai-content-generator'); ?>"
                        >
                            <?php // Populated by JS ?>
                        </select>
                        <?php $aipkit_task_cw_image_display_settings_render_mode = 'trigger'; ?>
                        <?php include __DIR__ . '/image-display-settings.php'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="aipkit_task_cw_image_provider_notice"
            class="aipkit_notification_bar aipkit_notification_bar--warning aipkit_task_cw_image_provider_notice"
            data-message-replicate="<?php echo esc_attr__('Replicate is selected for image generation, but it is not configured yet. Add its API key in Settings > Integrations.', 'gpt3-ai-content-generator'); ?>"
            data-message-xai="<?php echo esc_attr__('xAI is selected for image generation, but it is not configured yet. Add its API key in Settings.', 'gpt3-ai-content-generator'); ?>"
            hidden
        >
            <div class="aipkit_notification_bar__icon" aria-hidden="true">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="aipkit_notification_bar__content">
                <p class="aipkit_task_cw_image_provider_notice_message">
                    <?php esc_html_e('Replicate is selected for image generation, but it is not configured yet. Add its API key in Settings > Integrations.', 'gpt3-ai-content-generator'); ?>
                </p>
            </div>
            <div class="aipkit_notification_bar__actions">
                <a
                    href="<?php echo esc_url($image_provider_settings_url); ?>"
                    class="aipkit_btn aipkit_provider_notice_settings_link"
                    data-aipkit-load-module="settings"
                >
                    <?php esc_html_e('Open Settings', 'gpt3-ai-content-generator'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php $aipkit_task_cw_image_display_settings_render_mode = 'popover'; ?>
    <?php include __DIR__ . '/image-display-settings.php'; ?>
</div>

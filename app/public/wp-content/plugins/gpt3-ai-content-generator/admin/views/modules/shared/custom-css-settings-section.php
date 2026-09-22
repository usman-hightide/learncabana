<?php
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Shared view partial configured by parent templates.

$aipkit_custom_css_section_id_prefix = isset($aipkit_custom_css_section_id_prefix)
    ? preg_replace('/[^A-Za-z0-9_]/', '', (string) $aipkit_custom_css_section_id_prefix)
    : '';
$aipkit_custom_css_field_id = isset($aipkit_custom_css_field_id)
    ? preg_replace('/[^A-Za-z0-9_]/', '', (string) $aipkit_custom_css_field_id)
    : '';
$aipkit_custom_css_header_helper = isset($aipkit_custom_css_header_helper)
    ? (string) $aipkit_custom_css_header_helper
    : __('Theme overrides for Custom theme.', 'gpt3-ai-content-generator');
$aipkit_custom_css_label_helper = isset($aipkit_custom_css_label_helper)
    ? (string) $aipkit_custom_css_label_helper
    : __('Applies to the Custom theme.', 'gpt3-ai-content-generator');
?>
<section
    class="aipkit_ai_forms_settings_block aipkit_settings_module_tab_panel"
    id="<?php echo esc_attr($aipkit_custom_css_section_id_prefix . '_custom_css'); ?>"
    role="tabpanel"
    aria-labelledby="<?php echo esc_attr($aipkit_custom_css_section_id_prefix . '_tab_custom_css'); ?>"
    data-aipkit-settings-module-tab-panel="custom-css"
    hidden
>
    <div class="aipkit_ai_forms_settings_block_header">
        <div>
            <h3 class="aipkit_ai_forms_settings_block_title"><?php esc_html_e('Custom CSS', 'gpt3-ai-content-generator'); ?></h3>
            <p class="aipkit_ai_forms_settings_block_helper"><?php echo esc_html($aipkit_custom_css_header_helper); ?></p>
        </div>
    </div>
    <div class="aipkit_ai_forms_settings_block_body">
        <div class="aipkit_ai_forms_settings_row aipkit_ai_forms_settings_row--wide">
            <label class="aipkit_form-label" for="<?php echo esc_attr($aipkit_custom_css_field_id); ?>">
                <?php esc_html_e('Custom CSS', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php echo esc_html($aipkit_custom_css_label_helper); ?></span>
            </label>
            <textarea
                id="<?php echo esc_attr($aipkit_custom_css_field_id); ?>"
                name="custom_css"
                class="aipkit_form-input aipkit_ai_forms_settings_textarea aipkit_ai_forms_settings_textarea--code aipkit_autosave_trigger"
                rows="12"
                placeholder="<?php echo esc_attr($default_css_template); ?>"
            ><?php echo esc_textarea($custom_css ?: $default_css_template); ?></textarea>
        </div>
    </div>
</section>

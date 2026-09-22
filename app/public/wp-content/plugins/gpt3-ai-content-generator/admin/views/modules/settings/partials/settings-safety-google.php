<?php

/**
 * Partial: Google Safety Settings
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

if (!defined('ABSPATH')) exit;

// Use the new GoogleSettingsHandler
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;

// Variables required: $current_provider (from settings/index.php)
// Fetch settings using the handler
$safety_settings = [];
$category_thresholds = [];
if (class_exists(GoogleSettingsHandler::class)) {
    $safety_settings = GoogleSettingsHandler::get_safety_settings();
    foreach ($safety_settings as $setting) {
        if (isset($setting['category'], $setting['threshold'])) {
            $category_thresholds[$setting['category']] = $setting['threshold'];
        }
    }
} else {
    // Error logged by parent settings/index.php, prevent further rendering if handler is missing
    return;
}

$safety_thresholds_map = array(
    'BLOCK_NONE'             => 'Block None',
    'BLOCK_LOW_AND_ABOVE'    => 'Block Few',
    'BLOCK_MEDIUM_AND_ABOVE' => 'Block Some',
    'BLOCK_ONLY_HIGH'        => 'Block Most',
);

// Define the order of safety categories for consistent layout
$safety_categories_ordered = [
    'HARM_CATEGORY_HARASSMENT' => __('Harassment', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_HATE_SPEECH' => __('Hate Speech', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_SEXUALLY_EXPLICIT' => __('Sexually Explicit', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_DANGEROUS_CONTENT' => __('Dangerous Content', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_CIVIC_INTEGRITY' => __('Civic Integrity', 'gpt3-ai-content-generator'),
];

?>
<div
    class="aipkit_popover_option_group aipkit_settings_advanced_group aipkit_advanced_settings_provider"
    id="aipkit_safety_settings_accordion"
    style="display: <?php echo ($current_provider === 'Google') ? 'block' : 'none'; ?>;"
    data-provider-setting="Google"
>
    <?php foreach ($safety_categories_ordered as $category_key => $category_label): ?>
        <?php
        $current_threshold = $category_thresholds[$category_key] ?? 'BLOCK_NONE';
        $input_name_short = strtolower(str_replace('HARM_CATEGORY_', '', $category_key));
        $input_id = 'aipkit_safety_' . $input_name_short;
        ?>
        <div class="aipkit_settings_advanced_row">
            <div class="aipkit_settings_advanced_label_wrap">
                <label class="aipkit_settings_advanced_label" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($category_label); ?></label>
                <span class="aipkit_settings_advanced_helper"><?php esc_html_e('Safety threshold.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_advanced_control">
                <div class="aipkit_popover_option_actions">
                    <select id="<?php echo esc_attr($input_id); ?>" name="safety_<?php echo esc_attr($input_name_short); ?>" class="aipkit_form-input aipkit_popover_option_select aipkit_popover_option_input--framed aipkit_popover_option_input--compact aipkit_autosave_trigger">
                        <?php foreach ($safety_thresholds_map as $tKey => $tLabel): ?>
                            <option value="<?php echo esc_attr($tKey); ?>" <?php selected($current_threshold, $tKey); ?>>
                                <?php echo esc_html($tLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

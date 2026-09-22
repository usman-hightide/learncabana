<?php
/**
 * Partial: AutoGPT Prompts Popover Body (Reusable)
 *
 * Expected variables:
 * - $aipkit_prompt_items (array) Required
 *
 * Each prompt item supports:
 * - key (string) Required, used for data-prompt-key.
 * - section_id (string) Optional, applied to the row wrapper (used for toggling).
 * - label (string) Required
 * - description (string) Optional
 * - section_style (string) Optional, inline style applied to the row wrapper
 * - toggle (array) Optional: ['id' => '', 'name' => '', 'checked' => bool]
 * - flyout_id (string) Required
 * - flyout_title (string) Required
 * - textarea (array) Required: ['id' => '', 'name' => '', 'value' => '', 'placeholder' => '']
 * - library (array) Optional:
 *   ['select_id' => '', 'options' => array, 'default_prompt' => '', 'default_label' => 'Default']
 * - placeholders (array) Optional: list of tokens, e.g. ['{topic}']
 * - placeholders_prompt_type (string) Optional, added as data-prompt-type for JS updates
 * - placeholders_id (string) Optional, added as id for the placeholders container
 * - placeholders_label (string) Optional, defaults to "Variables:"
 * - placeholders_extra (array) Optional: list of tokens shown conditionally
 * - placeholders_extra_label (string) Optional, e.g. "For products:"
 * - placeholders_extra_class (string) Optional, defaults to "aipkit-product-placeholders"
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$aipkit_prompt_items = isset($aipkit_prompt_items) && is_array($aipkit_prompt_items) ? $aipkit_prompt_items : [];
$aipkit_prompts_render_list = isset($aipkit_prompts_render_list)
    ? (bool) $aipkit_prompts_render_list
    : true;
if (!$aipkit_prompt_items) {
    return;
}

$aipkit_placeholder_label_default = __('Variables:', 'gpt3-ai-content-generator');
$aipkit_placeholder_copy_title = __('Click to copy', 'gpt3-ai-content-generator');

$aipkit_render_library_options = static function(array $options): void {
    foreach ($options as $option) {
        if (empty($option['label']) || empty($option['prompt'])) {
            continue;
        }
        printf(
            '<option value="%s">%s</option>',
            esc_attr($option['prompt']),
            esc_html($option['label'])
        );
    }
};

$aipkit_render_placeholder_codes = static function(array $placeholders, string $title): void {
    foreach ($placeholders as $placeholder) {
        if ($placeholder === '') {
            continue;
        }
        printf(
            '<code class="aipkit-placeholder" title="%s">%s</code>',
            esc_attr($title),
            esc_html($placeholder)
        );
    }
};
?>

<?php if ($aipkit_prompts_render_list) : ?>
    <div class="aipkit_prompts_redesigned">
        <?php foreach ($aipkit_prompt_items as $item) : ?>
            <?php
            $key = isset($item['key']) ? (string) $item['key'] : '';
            $section_id = isset($item['section_id']) ? (string) $item['section_id'] : '';
            $section_style = isset($item['section_style']) ? (string) $item['section_style'] : '';
            $label = isset($item['label']) ? (string) $item['label'] : '';
            $description = isset($item['description']) ? (string) $item['description'] : '';
            $toggle = isset($item['toggle']) && is_array($item['toggle']) ? $item['toggle'] : [];
            $flyout_id = isset($item['flyout_id']) ? (string) $item['flyout_id'] : '';
            $flyout_title = isset($item['flyout_title']) ? (string) $item['flyout_title'] : '';
            $textarea = isset($item['textarea']) && is_array($item['textarea']) ? $item['textarea'] : [];
            $textarea_id = isset($textarea['id']) ? (string) $textarea['id'] : '';
            $textarea_name = isset($textarea['name']) ? (string) $textarea['name'] : '';
            $textarea_value = isset($textarea['value']) ? (string) $textarea['value'] : '';
            $textarea_placeholder = isset($textarea['placeholder']) ? (string) $textarea['placeholder'] : '';
            if ($key === '' || $label === '' || $flyout_id === '' || $flyout_title === '' || $textarea_id === '' || $textarea_name === '') {
                continue;
            }
            ?>
            <div class="aipkit_prompt_toggle_card" data-prompt-key="<?php echo esc_attr($key); ?>"<?php echo $section_id !== '' ? ' id="' . esc_attr($section_id) . '"' : ''; ?><?php echo $section_style !== '' ? ' style="' . esc_attr($section_style) . '"' : ''; ?>>
                <div class="aipkit_prompt_toggle_row">
                    <div class="aipkit_prompt_toggle_info">
                        <span class="aipkit_prompt_toggle_label"><?php echo esc_html($label); ?></span>
                        <?php if ($description !== '') : ?>
                            <span class="aipkit_prompt_toggle_desc"><?php echo esc_html($description); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="aipkit_prompt_toggle_controls">
                        <?php if (!empty($toggle['id']) && !empty($toggle['name'])) : ?>
                            <label class="aipkit_switch">
                                <input
                                    type="checkbox"
                                    id="<?php echo esc_attr($toggle['id']); ?>"
                                    name="<?php echo esc_attr($toggle['name']); ?>"
                                    value="1"
                                    <?php checked(!empty($toggle['checked'])); ?>
                                >
                                <span class="aipkit_switch_slider"></span>
                            </label>
                        <?php endif; ?>
                        <button
                            type="button"
                            class="aipkit_prompt_edit_btn"
                            data-aipkit-flyout-target="<?php echo esc_attr($flyout_id); ?>"
                            aria-controls="<?php echo esc_attr($flyout_id); ?>"
                            aria-expanded="false"
                            title="<?php esc_attr_e('Edit prompt', 'gpt3-ai-content-generator'); ?>"
                        >
                            <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php foreach ($aipkit_prompt_items as $item) : ?>
    <?php
    $flyout_id = isset($item['flyout_id']) ? (string) $item['flyout_id'] : '';
    $flyout_title = isset($item['flyout_title']) ? (string) $item['flyout_title'] : '';
    $textarea = isset($item['textarea']) && is_array($item['textarea']) ? $item['textarea'] : [];
    $textarea_id = isset($textarea['id']) ? (string) $textarea['id'] : '';
    $textarea_name = isset($textarea['name']) ? (string) $textarea['name'] : '';
    $textarea_value = isset($textarea['value']) ? (string) $textarea['value'] : '';
    $textarea_placeholder = isset($textarea['placeholder']) ? (string) $textarea['placeholder'] : '';
    if ($flyout_id === '' || $flyout_title === '' || $textarea_id === '' || $textarea_name === '') {
        continue;
    }
    $library = isset($item['library']) && is_array($item['library']) ? $item['library'] : [];
    $library_select_id = isset($library['select_id']) ? (string) $library['select_id'] : '';
    $library_options = isset($library['options']) && is_array($library['options']) ? $library['options'] : [];
    $library_default_prompt = isset($library['default_prompt']) ? (string) $library['default_prompt'] : '';
    $library_default_label = isset($library['default_label']) ? (string) $library['default_label'] : __('Default', 'gpt3-ai-content-generator');
    $placeholders = isset($item['placeholders']) && is_array($item['placeholders']) ? $item['placeholders'] : [];
    $placeholders_label = isset($item['placeholders_label']) ? (string) $item['placeholders_label'] : $aipkit_placeholder_label_default;
    $placeholders_extra = isset($item['placeholders_extra']) && is_array($item['placeholders_extra']) ? $item['placeholders_extra'] : [];
    $placeholders_extra_label = isset($item['placeholders_extra_label']) ? (string) $item['placeholders_extra_label'] : '';
    $placeholders_extra_class = isset($item['placeholders_extra_class']) ? (string) $item['placeholders_extra_class'] : 'aipkit-product-placeholders';
    $placeholders_prompt_type = isset($item['placeholders_prompt_type']) ? (string) $item['placeholders_prompt_type'] : '';
    $placeholders_id = isset($item['placeholders_id']) ? (string) $item['placeholders_id'] : '';
    ?>
    <div class="aipkit_cw_prompt_flyout" id="<?php echo esc_attr($flyout_id); ?>" aria-hidden="true">
        <div class="aipkit_cw_prompt_panel aipkit_cw_prompt_panel--tall" role="dialog" aria-label="<?php echo esc_attr($flyout_title); ?>">
            <div class="aipkit_cw_prompt_editor">
                <div class="aipkit_cw_prompt_editor_toolbar">
                    <span class="aipkit_cw_prompt_editor_title"><?php echo esc_html($flyout_title); ?></span>
                    <?php if ($library_select_id !== '') : ?>
                        <select
                            id="<?php echo esc_attr($library_select_id); ?>"
                            class="aipkit_cw_prompt_template_select aipkit_cw_prompt_library_select"
                            data-aipkit-prompt-target="<?php echo esc_attr($textarea_id); ?>"
                            title="<?php esc_attr_e('Load template', 'gpt3-ai-content-generator'); ?>"
                        >
                            <option value="<?php echo esc_attr($library_default_prompt); ?>"><?php echo esc_html($library_default_label); ?></option>
                            <?php $aipkit_render_library_options($library_options); ?>
                        </select>
                    <?php endif; ?>
                </div>
                <textarea
                    id="<?php echo esc_attr($textarea_id); ?>"
                    name="<?php echo esc_attr($textarea_name); ?>"
                    class="aipkit_cw_prompt_editor_textarea aipkit_autosave_trigger"
                    placeholder="<?php echo esc_attr($textarea_placeholder); ?>"
                ><?php echo esc_textarea($textarea_value); ?></textarea>
                <?php if (!empty($placeholders) || !empty($placeholders_extra)) : ?>
                    <div class="aipkit_cw_prompt_editor_footer">
                        <span
                            class="aipkit_cw_prompt_editor_placeholders"
                            data-label="<?php echo esc_attr($placeholders_label); ?>"
                            data-copy-title="<?php echo esc_attr($aipkit_placeholder_copy_title); ?>"
                            <?php echo $placeholders_prompt_type !== '' ? ' data-prompt-type="' . esc_attr($placeholders_prompt_type) . '"' : ''; ?>
                            <?php echo $placeholders_id !== '' ? ' id="' . esc_attr($placeholders_id) . '"' : ''; ?>
                        >
                            <?php echo esc_html($placeholders_label); ?>
                            <?php $aipkit_render_placeholder_codes($placeholders, $aipkit_placeholder_copy_title); ?>
                            <?php if (!empty($placeholders_extra)) : ?>
                                <span class="<?php echo esc_attr($placeholders_extra_class); ?>" style="display:none;">
                                    <?php
                                    if ($placeholders_extra_label !== '') {
                                        echo ' ' . esc_html($placeholders_extra_label);
                                    }
                                    $aipkit_render_placeholder_codes($placeholders_extra, $aipkit_placeholder_copy_title);
                                    ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

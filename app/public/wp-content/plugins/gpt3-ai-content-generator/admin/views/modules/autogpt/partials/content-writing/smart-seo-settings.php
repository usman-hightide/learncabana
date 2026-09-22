<?php
/**
 * Partial: Content Writing Automated Task - Smart SEO Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

$aipkit_task_cw_smart_seo_is_pro = class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan();
$aipkit_task_cw_seo_profile = class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')
    ? \WPAICG\SEO\AIPKit_SEO_Helper::get_active_plugin_profile()
    : [
        'profile' => 'aipkit',
        'label' => __('AIPKit SEO', 'gpt3-ai-content-generator'),
        'logo_url' => '',
        'logo_initials' => 'AI',
    ];
$aipkit_task_cw_seo_profile_label = isset($aipkit_task_cw_seo_profile['label']) ? (string) $aipkit_task_cw_seo_profile['label'] : __('AIPKit SEO', 'gpt3-ai-content-generator');
$aipkit_task_cw_seo_profile_key = isset($aipkit_task_cw_seo_profile['profile']) ? (string) $aipkit_task_cw_seo_profile['profile'] : 'aipkit';
$aipkit_task_cw_seo_profile_logo_url = isset($aipkit_task_cw_seo_profile['logo_url']) ? (string) $aipkit_task_cw_seo_profile['logo_url'] : '';
$aipkit_task_cw_seo_profile_logo_initials = isset($aipkit_task_cw_seo_profile['logo_initials']) ? (string) $aipkit_task_cw_seo_profile['logo_initials'] : 'SEO';
$aipkit_task_cw_seo_rules_class = '\\WPAICG\\ContentWriter\\SEO\\AIPKit_Content_Writer_Smart_SEO_Rules';
if (!class_exists($aipkit_task_cw_seo_rules_class) && defined('WPAICG_LIB_DIR')) {
    $aipkit_task_cw_seo_rules_path = WPAICG_LIB_DIR . 'content-writer/seo/class-aipkit-content-writer-smart-seo-rules.php';
    if (file_exists($aipkit_task_cw_seo_rules_path)) {
        require_once $aipkit_task_cw_seo_rules_path;
    }
}
$aipkit_task_cw_seo_rules_available = class_exists($aipkit_task_cw_seo_rules_class) && !empty($aipkit_task_cw_seo_rules_class::rule_catalog());
$aipkit_task_cw_seo_default_disabled_rules = class_exists('\\WPAICG\\ContentWriter\\SEO\\AIPKit_Content_Writer_SEO_Config')
    ? \WPAICG\ContentWriter\SEO\AIPKit_Content_Writer_SEO_Config::default_disabled_rules()
    : '[]';
$aipkit_task_cw_smart_seo_upgrade_url = function_exists('wpaicg_gacg_fs')
    ? wpaicg_gacg_fs()->get_upgrade_url()
    : admin_url('admin.php?page=wpaicg-pricing');
?>

<div
    class="aipkit_cw_ai_row aipkit_cw_seo_settings_row aipkit_cw_smart_seo_feature_card aipkit_task_cw_smart_seo_settings_row<?php echo $aipkit_task_cw_smart_seo_is_pro ? '' : ' is-pro-locked'; ?>"
    data-aipkit-task-smart-seo-settings-row
    data-aipkit-seo-active-profile="<?php echo esc_attr($aipkit_task_cw_seo_profile_key); ?>"
    data-aipkit-seo-active-profile-label="<?php echo esc_attr($aipkit_task_cw_seo_profile_label); ?>"
    data-aipkit-seo-active-profile-logo="<?php echo esc_url($aipkit_task_cw_seo_profile_logo_url); ?>"
    data-aipkit-seo-active-profile-initials="<?php echo esc_attr($aipkit_task_cw_seo_profile_logo_initials); ?>"
>
    <div class="aipkit_cw_panel_label_wrap">
        <label class="aipkit_cw_panel_label"<?php echo $aipkit_task_cw_smart_seo_is_pro ? ' for="aipkit_task_cw_seo_score_improvement_enabled"' : ''; ?>>
            <span class="aipkit_seo_settings_label">
                <span><?php esc_html_e('Smart SEO', 'gpt3-ai-content-generator'); ?></span>
                <span
                    class="aipkit_seo_profile_logo aipkit_seo_settings_logo"
                    title="<?php echo esc_attr($aipkit_task_cw_seo_profile_label); ?>"
                    aria-label="<?php echo esc_attr($aipkit_task_cw_seo_profile_label); ?>"
                    role="img"
                >
                    <?php if ($aipkit_task_cw_seo_profile_logo_url !== '') : ?>
                        <img src="<?php echo esc_url($aipkit_task_cw_seo_profile_logo_url); ?>" alt="">
                    <?php else : ?>
                        <span><?php echo esc_html($aipkit_task_cw_seo_profile_logo_initials); ?></span>
                    <?php endif; ?>
                </span>
            </span>
        </label>
        <span class="aipkit_cw_smart_seo_feature_helper">
            <?php esc_html_e('Automatically refines content until it achieves a higher SEO score.', 'gpt3-ai-content-generator'); ?>
        </span>
    </div>
    <div class="aipkit_cw_ai_control aipkit_cw_ai_control--compact">
        <div class="aipkit_cw_seo_inline_actions">
            <?php if ($aipkit_task_cw_smart_seo_is_pro) : ?>
                <input type="hidden" name="seo_score_improvement_enabled" value="0" data-aipkit-task-smart-seo-control>
                <label class="aipkit_switch aipkit_cw_seo_inline_switch" title="<?php esc_attr_e('Smart SEO auto-improvement', 'gpt3-ai-content-generator'); ?>">
                    <input
                        type="checkbox"
                        id="aipkit_task_cw_seo_score_improvement_enabled"
                        name="seo_score_improvement_enabled"
                        class="aipkit_toggle_switch aipkit_autosave_trigger"
                        value="1"
                        data-aipkit-task-smart-seo-control
                        data-aipkit-task-smart-seo-main-toggle
                    >
                    <span class="aipkit_switch_slider"></span>
                </label>
                <?php if ($aipkit_task_cw_seo_rules_available) : ?>
                    <button
                        type="button"
                        class="aipkit_cw_settings_icon_trigger aipkit_cw_seo_rules_trigger"
                        id="aipkit_task_cw_smart_seo_rules_trigger"
                        data-aipkit-popover-target="aipkit_task_cw_smart_seo_rules_popover"
                        data-aipkit-popover-placement="left"
                        aria-controls="aipkit_task_cw_smart_seo_rules_popover"
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e('Smart SEO rules', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Smart SEO rules', 'gpt3-ai-content-generator'); ?>"
                    >
                        <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                    </button>
                <?php endif; ?>
            <?php else : ?>
                <a
                    class="aipkit_cw_seo_upgrade_btn"
                    href="<?php echo esc_url($aipkit_task_cw_smart_seo_upgrade_url); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?>
                </a>
                <input type="hidden" name="seo_score_improvement_enabled" value="0" data-aipkit-task-smart-seo-control>
            <?php endif; ?>
            <input type="hidden" name="seo_score_continue_until_target" value="1" data-aipkit-task-smart-seo-control>
            <input type="hidden" name="seo_score_target" value="100" data-aipkit-task-smart-seo-control>
            <input type="hidden" name="seo_score_max_passes" value="3" data-aipkit-task-smart-seo-control>
            <input type="hidden" name="seo_score_profile" value="auto" data-aipkit-task-smart-seo-control>
            <input type="hidden" name="seo_score_disabled_rules" value="<?php echo esc_attr($aipkit_task_cw_seo_default_disabled_rules); ?>" data-aipkit-task-smart-seo-control data-aipkit-smart-seo-disabled-rules>
        </div>
    </div>
</div>

<?php
$aipkit_smart_seo_rules_popover_id = 'aipkit_task_cw_smart_seo_rules_popover';
$aipkit_smart_seo_rules_profile_key = $aipkit_task_cw_seo_profile_key;
$aipkit_smart_seo_rules_profile_label = $aipkit_task_cw_seo_profile_label;
$aipkit_smart_seo_rules_popover_path = defined('WPAICG_LIB_DIR') ? WPAICG_LIB_DIR . 'views/modules/shared/smart-seo-rules-popover.php' : '';
if ($aipkit_task_cw_smart_seo_is_pro && $aipkit_task_cw_seo_rules_available && $aipkit_smart_seo_rules_popover_path !== '' && file_exists($aipkit_smart_seo_rules_popover_path)) {
    include $aipkit_smart_seo_rules_popover_path;
}
?>

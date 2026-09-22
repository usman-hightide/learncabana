<?php

// Silence direct access
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

use WPAICG\aipkit_dashboard;
use WPAICG\AIPKit_Role_Manager; // <-- Import Role Manager

$module_settings = aipkit_dashboard::get_module_settings();
$can_access_dashboard = AIPKit_Role_Manager::user_can_access_dashboard_shell();
$can_access_settings = AIPKit_Role_Manager::user_can_access_module('settings');

$nav_modules = array(
    'chat_bot' => array(
        'label'       => __('Chatbots', 'gpt3-ai-content-generator'),
        'icon'        => 'format-chat',
        'data_module' => 'chatbot',
    ),
    'content_writer' => array(
        'label'       => __('Content Writer', 'gpt3-ai-content-generator'),
        'icon'        => 'edit',
        'data_module' => 'content-writer',
    ),
    'autogpt' => array(
        'label'       => __('Automations', 'gpt3-ai-content-generator'),
        'icon'        => 'airplane',
        'data_module' => 'autogpt',
    ),
    'ai_forms' => array(
        'label'       => __('AI Forms', 'gpt3-ai-content-generator'),
        'icon'        => 'feedback',
        'data_module' => 'ai-forms',
    ),
    'image_generator' => array(
        'label'       => __('Images', 'gpt3-ai-content-generator'),
        'icon'        => 'format-image',
        'data_module' => 'image-generator',
    ),
    'sources' => array(
        'label'       => __('Knowledge Base', 'gpt3-ai-content-generator'),
        'icon'        => 'media-document',
        'data_module' => 'sources',
    ),
    'stats_viewer' => array(
        'label'       => __('Usage', 'gpt3-ai-content-generator'),
        'icon'        => 'chart-bar',
        'data_module' => 'stats',
    ),
);

?>
<div class="wrap aipkit_wrap">
    <div class="aipkit_module-tabs">
        <nav class="aipkit_module-tabs_list" role="tablist" aria-label="<?php esc_attr_e('Main navigation', 'gpt3-ai-content-generator'); ?>">
            <?php if ($can_access_dashboard): ?>
                <a
                    href="#"
                    class="aipkit_module-tab aipkit_module-tab--home aipkit_module-link"
                    data-module="dashboard-home"
                    data-aipkit-open-module="dashboard-home"
                    aria-label="<?php esc_attr_e('Dashboard', 'gpt3-ai-content-generator'); ?>"
                    title="<?php esc_attr_e('Dashboard', 'gpt3-ai-content-generator'); ?>"
                    role="tab"
                >
                    <span class="aipkit_module-tab_home-badge" aria-hidden="true">
                        <img
                            src="<?php echo esc_url(WPAICG_PLUGIN_URL . 'public/images/icon.svg'); ?>"
                            alt=""
                            class="aipkit_module-tab_home-logo"
                        />
                    </span>
                    <span class="aipkit_module-tab_label"><?php esc_html_e('Home', 'gpt3-ai-content-generator'); ?></span>
                </a>

                <?php foreach ($nav_modules as $option_key => $module): ?>
                    <?php
                    $module_slug = $module['data_module'];
                    $is_enabled = !isset($module_settings[$option_key]) || !empty($module_settings[$option_key]);
                    if (!AIPKit_Role_Manager::user_can_access_module($module_slug)) {
                        continue;
                    }
                    ?>
                    <a
                        href="#"
                        class="aipkit_module-tab aipkit_module-link aipkit_module-tab--module<?php echo $is_enabled ? '' : ' aipkit_module-tab--is-hidden'; ?>"
                        data-module="<?php echo esc_attr($module_slug); ?>"
                        data-option-key="<?php echo esc_attr($option_key); ?>"
                        data-aipkit-open-module="<?php echo esc_attr($module_slug); ?>"
                        role="tab"
                        title="<?php echo esc_attr($module['label']); ?>"
                        <?php if (!$is_enabled): ?>
                            hidden
                            aria-hidden="true"
                            tabindex="-1"
                        <?php endif; ?>
                    >
                        <span class="dashicons dashicons-<?php echo esc_attr($module['icon']); ?>" aria-hidden="true"></span>
                        <span class="aipkit_module-tab_label"><?php echo esc_html($module['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <?php if ($can_access_dashboard): ?>
            <div class="aipkit_module-tabs_actions">
                <?php if ($can_access_settings): ?>
                <a
                    href="#"
                    class="aipkit_module-tab aipkit_module-tab--settings aipkit_module-link"
                    data-module="settings"
                    data-aipkit-open-module="settings"
                    role="tab"
                    title="<?php esc_attr_e('Settings', 'gpt3-ai-content-generator'); ?>"
                >
                    <svg class="aipkit_settings-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="m19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span class="aipkit_module-tab_label"><?php esc_html_e('Settings', 'gpt3-ai-content-generator'); ?></span>
                </a>
                <?php endif; ?>

                <?php 
                // Show upgrade button only for non-pro users
                $is_pro_plan = class_exists('\\WPAICG\\aipkit_dashboard') ? \WPAICG\aipkit_dashboard::is_pro_plan() : false;
                if (!$is_pro_plan):
                ?>
                <button 
                    type="button" 
                    class="aipkit_module-tab aipkit_module-tab--settings aipkit_upgrade_btn" 
                    id="aipkit_upgradeBtn"
                    title="<?php echo esc_attr__('Upgrade', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="aipkit_upgrade_btn_icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </span>
                    <span class="aipkit_module-tab_label aipkit_upgrade_btn_label"><?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?></span>
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="aipkit_main-content" id="aipkit_module-container">
    </div>
</div>

<?php 
// Upgrade to Pro Modal - Only show for non-pro users
$is_pro_plan_for_modal = class_exists('\\WPAICG\\aipkit_dashboard') ? \WPAICG\aipkit_dashboard::is_pro_plan() : false;
if (!$is_pro_plan_for_modal && AIPKit_Role_Manager::user_can_manage_settings()):
    $upgrade_url = function_exists('wpaicg_gacg_fs') ? wpaicg_gacg_fs()->get_upgrade_url() : admin_url('admin.php?page=wpaicg-pricing');
?>
<div class="aipkit_upgrade_modal" id="aipkit_upgradeModal">
    <div class="aipkit_modal_backdrop" data-close-modal></div>
    <div class="aipkit_modal aipkit_upgrade_modal_content">
        <div class="aipkit_modal_header">
            <h2 class="aipkit_modal_title">
                <span class="aipkit_upgrade_modal_icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </span>
                <?php esc_html_e('Unlock Pro Features', 'gpt3-ai-content-generator'); ?>
            </h2>
            <button type="button" class="aipkit_modal_close" data-close-modal aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aipkit_modal_body">
            <div class="aipkit_upgrade_plans">
                <div class="aipkit_upgrade_plan aipkit_upgrade_plan--free">
                    <div class="aipkit_plan_header">
                        <h3 class="aipkit_plan_name"><?php esc_html_e('Free', 'gpt3-ai-content-generator'); ?></h3>
                        <span class="aipkit_plan_badge aipkit_plan_badge--current"><?php esc_html_e('Current', 'gpt3-ai-content-generator'); ?></span>
                    </div>
                    <ul class="aipkit_plan_features">
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Chatbot', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Content Writer', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Image Generator', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('AI Forms', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('WooCommerce Writer', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Knowledge Base', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('OpenAI, Google, Azure, and DeepSeek', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Semantic Search & Vector Stores', 'gpt3-ai-content-generator'); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Basic Automation Tasks', 'gpt3-ai-content-generator'); ?></li>
                    </ul>
                </div>

                <div class="aipkit_upgrade_plan aipkit_upgrade_plan--pro">
                    <div class="aipkit_plan_header">
                        <h3 class="aipkit_plan_name"><?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?></h3>
                        <span class="aipkit_plan_badge aipkit_plan_badge--recommended"><?php esc_html_e('Most Popular', 'gpt3-ai-content-generator'); ?></span>
                    </div>
                    <div class="aipkit_plan_price_callout">
                        <span class="aipkit_plan_price_callout_amount">$7.99</span>
                        <span class="aipkit_plan_price_callout_term"><?php esc_html_e('/ month', 'gpt3-ai-content-generator'); ?></span>
                    </div>
                    <a href="<?php echo esc_url($upgrade_url); ?>" class="aipkit_btn aipkit_btn--primary aipkit_btn--upgrade aipkit_plan_cta">
                        <span class="aipkit_plan_cta_label"><?php esc_html_e('Purchase now', 'gpt3-ai-content-generator'); ?></span>
                    </a>
                    <ul class="aipkit_plan_features">
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Priority Email Support', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Slack, HubSpot, Notion, Pipedrive, Zapier, Make, n8n integrations', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Ollama Integration', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Chatbot Triggers & Automation', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Realtime Voice Agent', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('File Upload for Context (PDF, TXT)', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Embed Chatbot External Sites', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('PDF Download of Transcripts', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Consent Compliance', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('AI Forms Conditional Steps', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Index Custom Post Types', 'gpt3-ai-content-generator'); ?></li>
                        <li class="aipkit_plan_feature--highlight"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Content Generation via RSS, URL, and Google Sheets', 'gpt3-ai-content-generator'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <p class="aipkit_upgrade_modal_footer_note">
            <?php esc_html_e('Purchasing our plugin does not provide any API credits. It only unlocks the Pro features.', 'gpt3-ai-content-generator'); ?>
        </p>
    </div>
</div>
<?php endif; ?>

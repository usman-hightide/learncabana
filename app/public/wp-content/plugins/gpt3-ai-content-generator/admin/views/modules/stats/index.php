<?php

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$stats_default_days = 7;
$log_settings = get_option('aipkit_log_settings', [
    'enable_pruning' => false,
    'retention_period_days' => 90,
]);
$log_settings_enabled = !empty($log_settings['enable_pruning']);
$log_settings_retention = isset($log_settings['retention_period_days'])
    ? (int) $log_settings['retention_period_days']
    : 90;
$user_credits_nonce = wp_create_nonce('aipkit_user_credits_nonce');
$retention_options = class_exists('\\WPAICG\\Chat\\Utils\\LogConfig')
    ? \WPAICG\Chat\Utils\LogConfig::get_retention_periods()
    : [
        7 => __('7 Days', 'gpt3-ai-content-generator'),
        15 => __('15 Days', 'gpt3-ai-content-generator'),
        30 => __('30 Days', 'gpt3-ai-content-generator'),
        60 => __('60 Days', 'gpt3-ai-content-generator'),
        90 => __('90 Days', 'gpt3-ai-content-generator'),
    ];
$is_pro = class_exists('\\WPAICG\\aipkit_dashboard')
    ? \WPAICG\aipkit_dashboard::is_pro_plan()
    : false;
$upgrade_url = admin_url('admin.php?page=wpaicg-pricing');
$cron_hook = class_exists('\\WPAICG\\Chat\\Storage\\LogCronManager')
    ? \WPAICG\Chat\Storage\LogCronManager::HOOK_NAME
    : 'aipkit_prune_logs_cron';
$next_scheduled = wp_next_scheduled($cron_hook);
$is_cron_active = $next_scheduled !== false;
$last_run_option = get_option('aipkit_log_pruning_last_run', '');
$last_run_label = $last_run_option
    ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_run_option))
    : __('Never', 'gpt3-ai-content-generator');
$cron_state = 'disabled';
$cron_text = __('Disabled', 'gpt3-ai-content-generator');
if ($log_settings_enabled) {
    if ($is_cron_active) {
        $cron_state = 'scheduled';
        $cron_text = __('Scheduled', 'gpt3-ai-content-generator');
    } else {
        $cron_state = 'not-scheduled';
        $cron_text = __('Not Scheduled', 'gpt3-ai-content-generator');
    }
}

$chatbot_posts = [];
if (class_exists('\\WPAICG\\Chat\\Admin\\AdminSetup')) {
    $chatbot_posts = get_posts([
        'post_type' => \WPAICG\Chat\Admin\AdminSetup::POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
}

$module_labels = [
    'chatbot' => __('Chatbot', 'gpt3-ai-content-generator'),
    'content_writer' => __('Content Writer', 'gpt3-ai-content-generator'),
    'content_writer_automation' => __('Automation', 'gpt3-ai-content-generator'),
    'image_generator' => __('Image Generator', 'gpt3-ai-content-generator'),
    'ai_forms' => __('AI Forms', 'gpt3-ai-content-generator'),
    'autogpt' => __('Automations', 'gpt3-ai-content-generator'),
    'ai_post_enhancer' => __('Content Assistant', 'gpt3-ai-content-generator'),
    'wp_ai_client' => __('WP AI Client', 'gpt3-ai-content-generator'),
    'unknown' => __('Unknown', 'gpt3-ai-content-generator'),
];

$module_options = [];
if (class_exists('\\WPAICG\\Stats\\AIPKit_Stats')) {
    $stats_calculator = new \WPAICG\Stats\AIPKit_Stats();
    $quick_stats = $stats_calculator->get_quick_interaction_stats($stats_default_days);
    if (!is_wp_error($quick_stats) && !empty($quick_stats['module_counts'])) {
        $module_options = array_keys($quick_stats['module_counts']);
    }
}
if (!in_array('chatbot', $module_options, true)) {
    $module_options[] = 'chatbot';
}
$module_options = array_values(array_unique(array_filter($module_options)));
sort($module_options);

$woo_active = class_exists('WooCommerce') && post_type_exists('product') && function_exists('wc_get_product');
$woo_create_product_url = admin_url('post-new.php?post_type=product');
$woo_products_url = admin_url('edit.php?post_type=product');
$can_manage_woo_products = current_user_can('edit_products') || current_user_can('manage_woocommerce');
$customer_dashboard_page_url = (string) get_option('aipkit_token_dashboard_page_url', '');
$customer_dashboard_buycredits_saved_url = (string) get_option('aipkit_token_shop_page_url', '');
$customer_dashboard_buycredits_default_url = $customer_dashboard_buycredits_saved_url;
if ($customer_dashboard_buycredits_default_url === '' && function_exists('wc_get_page_id')) {
    $customer_dashboard_buycredits_default_url = (string) get_permalink(wc_get_page_id('shop'));
}
$woo_credit_products = [];

if ($woo_active) {
    $woo_credit_query = new \WP_Query([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 8,
        'orderby' => 'modified',
        'order' => 'DESC',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small admin-only WooCommerce lookup for token packages.
        'meta_key' => '_aipkit_is_token_package',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Small admin-only WooCommerce lookup for token packages.
        'meta_value' => 'yes',
        'no_found_rows' => true,
    ]);

    if ($woo_credit_query->have_posts()) {
        foreach ($woo_credit_query->posts as $woo_product_post) {
            $woo_product = wc_get_product($woo_product_post->ID);
            $woo_price = $woo_product ? $woo_product->get_price() : '';
            $woo_status = get_post_status($woo_product_post);
            $woo_status_object = $woo_status ? get_post_status_object($woo_status) : null;
            $woo_edit_url = $can_manage_woo_products ? get_edit_post_link($woo_product_post->ID, '') : '';

            $woo_credit_products[] = [
                'id' => $woo_product_post->ID,
                'title' => get_the_title($woo_product_post),
                'credits' => absint(get_post_meta($woo_product_post->ID, '_aipkit_tokens_amount', true)),
                'price' => ($woo_price !== '' && $woo_price !== null) ? wc_price((float) $woo_price) : '&mdash;',
                'status' => $woo_status_object ? $woo_status_object->label : __('Unknown', 'gpt3-ai-content-generator'),
                'edit_url' => is_string($woo_edit_url) ? $woo_edit_url : '',
            ];
        }
    }

    wp_reset_postdata();
}
?>

<div
    class="aipkit_container aipkit_stats_container"
    id="aipkit_stats_container"
    data-default-days="<?php echo esc_attr($stats_default_days); ?>"
    data-is-pro="<?php echo esc_attr($is_pro ? '1' : '0'); ?>"
    data-module-labels="<?php echo esc_attr(wp_json_encode($module_labels)); ?>"
    data-user-credits-nonce="<?php echo esc_attr($user_credits_nonce); ?>"
>
    <div class="aipkit_container-header">
        <div class="aipkit_container-header-left">
            <div class="aipkit_stats_header_copy">
                <div class="aipkit_stats_header_title_row">
                    <div class="aipkit_container-title"><?php esc_html_e('Usage', 'gpt3-ai-content-generator'); ?></div>
                    <span id="aipkit_stats_status" class="aipkit_training_status aipkit_global_status_area" aria-live="polite"></span>
                </div>
                <p class="aipkit_stats_header_hint"><?php esc_html_e('Inspect saved conversations, billing activity, and user balances across your AI modules.', 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div>
    <div class="aipkit_container-body">
        <div class="aipkit_stats_workspace">
            <div class="aipkit_stats_tabs" role="tablist" aria-label="<?php esc_attr_e('Usage Sections', 'gpt3-ai-content-generator'); ?>">
                <button
                    type="button"
                    class="aipkit_stats_tab aipkit_active"
                    id="aipkit_stats_tab_logs"
                    role="tab"
                    aria-selected="true"
                    aria-controls="aipkit_stats_logs_panel"
                    data-aipkit-stats-tab="logs"
                >
                    <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                    <?php esc_html_e('Logs', 'gpt3-ai-content-generator'); ?>
                </button>
                <button
                    type="button"
                    class="aipkit_stats_tab"
                    id="aipkit_stats_tab_billing"
                    role="tab"
                    aria-selected="false"
                    aria-controls="aipkit_stats_billing_panel"
                    data-aipkit-stats-tab="billing"
                    tabindex="-1"
                >
                    <span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
                    <?php esc_html_e('Billing', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>

            <section
                class="aipkit_stats_tab_panel"
                id="aipkit_stats_logs_panel"
                role="tabpanel"
                aria-labelledby="aipkit_stats_tab_logs"
                data-aipkit-stats-panel="logs"
            >
                <div class="aipkit_stats_layout">
                    <div class="aipkit_stats_logs_shell">
                        <div class="aipkit_stats_panel_header aipkit_stats_logs_header">
                            <div class="aipkit_stats_panel_intro">
                                <div class="aipkit_sub_container_title"><?php esc_html_e('Conversation Logs', 'gpt3-ai-content-generator'); ?></div>
                                <p class="aipkit_stats_management_hint">
                                    <?php esc_html_e('Filter and inspect saved conversations.', 'gpt3-ai-content-generator'); ?>
                                    <a href="#" id="aipkit_stats_retention_open" class="aipkit_stats_inline_link">
                                        <?php esc_html_e('Set retention', 'gpt3-ai-content-generator'); ?>
                                    </a>
                                    <?php esc_html_e('to control log growth and keep your db size under control.', 'gpt3-ai-content-generator'); ?>
                                </p>
                            </div>
                            <div class="aipkit_stats_table_menu_wrapper">
                                <button
                                    type="button"
                                    class="aipkit_stats_table_menu_trigger"
                                    id="aipkit_stats_table_menu_trigger"
                                    aria-expanded="false"
                                    aria-controls="aipkit_stats_table_menu"
                                    aria-haspopup="dialog"
                                >
                                    <span class="aipkit_stats_table_menu_trigger_dots" aria-hidden="true">
                                        <span class="aipkit_stats_table_menu_trigger_dot"></span>
                                        <span class="aipkit_stats_table_menu_trigger_dot"></span>
                                        <span class="aipkit_stats_table_menu_trigger_dot"></span>
                                    </span>
                                    <span class="screen-reader-text"><?php esc_html_e('Log filters and actions', 'gpt3-ai-content-generator'); ?></span>
                                </button>
                                <div
                                    class="aipkit_stats_table_menu"
                                    id="aipkit_stats_table_menu"
                                    role="dialog"
                                    aria-label="<?php esc_attr_e('Log filters and actions', 'gpt3-ai-content-generator'); ?>"
                                    hidden
                                >
                                    <div class="aipkit_stats_table_menu_section">
                                        <div class="aipkit_stats_table_menu_section_header">
                                            <div class="aipkit_stats_table_menu_section_title"><?php esc_html_e('Filters', 'gpt3-ai-content-generator'); ?></div>
                                            <button type="button" class="aipkit_stats_table_menu_reset" id="aipkit_stats_filters_reset_btn">
                                                <?php esc_html_e('Reset', 'gpt3-ai-content-generator'); ?>
                                            </button>
                                        </div>
                                        <div class="aipkit_stats_table_menu_fields">
                                            <div class="aipkit_stats_table_menu_field aipkit_stats_table_menu_field--full">
                                                <label class="aipkit_stats_table_menu_label" for="aipkit_stats_search_input"><?php esc_html_e('Search logs', 'gpt3-ai-content-generator'); ?></label>
                                                <div class="aipkit_stats_search_control aipkit_stats_search_control--menu">
                                                    <input
                                                        type="search"
                                                        id="aipkit_stats_search_input"
                                                        class="aipkit_stats_search_input"
                                                        placeholder="<?php esc_attr_e('Search logs', 'gpt3-ai-content-generator'); ?>"
                                                        autocomplete="off"
                                                    />
                                                </div>
                                            </div>

                                            <div class="aipkit_stats_table_menu_field">
                                                <label class="aipkit_stats_table_menu_label" for="aipkit_stats_bot_filter"><?php esc_html_e('Chatbot', 'gpt3-ai-content-generator'); ?></label>
                                                <select id="aipkit_stats_bot_filter" class="aipkit_popover_select">
                                                    <option value=""><?php esc_html_e('All bots', 'gpt3-ai-content-generator'); ?></option>
                                                    <option value="0"><?php esc_html_e('No bot', 'gpt3-ai-content-generator'); ?></option>
                                                    <?php foreach ($chatbot_posts as $chatbot_post): ?>
                                                        <option value="<?php echo esc_attr($chatbot_post->ID); ?>">
                                                            <?php echo esc_html($chatbot_post->post_title); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="aipkit_stats_table_menu_field">
                                                <label class="aipkit_stats_table_menu_label" for="aipkit_stats_module_filter"><?php esc_html_e('Module', 'gpt3-ai-content-generator'); ?></label>
                                                <select id="aipkit_stats_module_filter" class="aipkit_popover_select">
                                                    <option value=""><?php esc_html_e('All modules', 'gpt3-ai-content-generator'); ?></option>
                                                    <?php foreach ($module_options as $module_key): ?>
                                                        <option value="<?php echo esc_attr($module_key); ?>">
                                                            <?php echo esc_html($module_labels[$module_key] ?? ucfirst(str_replace('_', ' ', $module_key))); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="aipkit_stats_table_menu_field aipkit_stats_table_menu_field--full">
                                                <label class="aipkit_stats_table_menu_label" for="aipkit_stats_logs_days_filter"><?php esc_html_e('Date range', 'gpt3-ai-content-generator'); ?></label>
                                                <select id="aipkit_stats_logs_days_filter" class="aipkit_popover_select" data-aipkit-stats-days-filter>
                                                    <option value="7" <?php selected($stats_default_days, 7); ?>><?php esc_html_e('Last 7 days', 'gpt3-ai-content-generator'); ?></option>
                                                    <option value="30" <?php selected($stats_default_days, 30); ?>><?php esc_html_e('Last 30 days', 'gpt3-ai-content-generator'); ?></option>
                                                    <option value="90" <?php selected($stats_default_days, 90); ?>><?php esc_html_e('Last 90 days', 'gpt3-ai-content-generator'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="aipkit_stats_table_menu_divider" aria-hidden="true"></div>

                                    <div class="aipkit_stats_table_menu_section">
                                        <div class="aipkit_stats_table_menu_section_title"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></div>
                                        <div class="aipkit_stats_table_menu_actions">
                                            <button type="button" class="aipkit_stats_table_menu_item" id="aipkit_stats_export_btn">
                                                <span class="aipkit_stats_table_menu_item_icon" aria-hidden="true">
                                                    <span class="dashicons dashicons-download"></span>
                                                </span>
                                                <span class="aipkit_stats_table_menu_item_label"><?php esc_html_e('Export logs', 'gpt3-ai-content-generator'); ?></span>
                                            </button>
                                            <button type="button" class="aipkit_stats_table_menu_item aipkit_stats_table_menu_item--danger" id="aipkit_stats_delete_all_btn">
                                                <span class="aipkit_stats_table_menu_item_icon" aria-hidden="true">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </span>
                                                <span class="aipkit_stats_table_menu_item_label"><?php esc_html_e('Delete logs', 'gpt3-ai-content-generator'); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="aipkit_data-table aipkit_stats_table">
                            <table class="aipkit_data-table__table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Date', 'gpt3-ai-content-generator'); ?></th>
                                        <th><?php esc_html_e('User', 'gpt3-ai-content-generator'); ?></th>
                                        <th><?php esc_html_e('Source', 'gpt3-ai-content-generator'); ?></th>
                                        <th><?php esc_html_e('Messages', 'gpt3-ai-content-generator'); ?></th>
                                        <th><?php esc_html_e('Tokens', 'gpt3-ai-content-generator'); ?></th>
                                        <th><?php esc_html_e('Message', 'gpt3-ai-content-generator'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="aipkit_stats_table_body">
                                    <tr>
                                        <td colspan="6" class="aipkit_stats_table_placeholder">
                                            <?php esc_html_e('Loading logs...', 'gpt3-ai-content-generator'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="aipkit_stats_pagination" class="aipkit_logs_pagination_container"></div>
                    </div>

                    <div class="aipkit_stats_detail_shell">
                        <div class="aipkit_stats_panel_header aipkit_stats_detail_shell_header">
                            <div class="aipkit_stats_panel_intro">
                                <div class="aipkit_sub_container_title"><?php esc_html_e('Conversation Details', 'gpt3-ai-content-generator'); ?></div>
                                <p class="aipkit_stats_management_hint">
                                    <?php esc_html_e('Open a log row to inspect the full exchange and metadata.', 'gpt3-ai-content-generator'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="aipkit_stats_detail_panel" id="aipkit_stats_detail_panel">
                            <div class="aipkit_stats_detail_empty">
                                <?php esc_html_e('Select a conversation to view details.', 'gpt3-ai-content-generator'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="aipkit_stats_tab_panel"
                id="aipkit_stats_billing_panel"
                role="tabpanel"
                aria-labelledby="aipkit_stats_tab_billing"
                data-aipkit-stats-panel="billing"
                hidden
            >
                <div class="aipkit_stats_inner_tabs" role="tablist" aria-label="<?php esc_attr_e('Billing Sections', 'gpt3-ai-content-generator'); ?>">
                    <button
                        type="button"
                        class="aipkit_stats_inner_tab aipkit_active"
                        id="aipkit_stats_billing_tab_pricing"
                        role="tab"
                        aria-selected="true"
                        aria-controls="aipkit_stats_billing_pricing_panel"
                        data-aipkit-billing-tab="pricing"
                    >
                        <?php esc_html_e('Pricing', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button
                        type="button"
                        class="aipkit_stats_inner_tab"
                        id="aipkit_stats_billing_tab_activity"
                        role="tab"
                        aria-selected="false"
                        aria-controls="aipkit_stats_billing_activity_panel"
                        data-aipkit-billing-tab="activity"
                        tabindex="-1"
                    >
                        <?php esc_html_e('Activity', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button
                        type="button"
                        class="aipkit_stats_inner_tab"
                        id="aipkit_stats_billing_tab_balances"
                        role="tab"
                        aria-selected="false"
                        aria-controls="aipkit_stats_billing_balances_panel"
                        data-aipkit-billing-tab="balances"
                        tabindex="-1"
                    >
                        <?php esc_html_e('Balances', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button
                        type="button"
                        class="aipkit_stats_inner_tab"
                        id="aipkit_stats_billing_tab_shortcode"
                        role="tab"
                        aria-selected="false"
                        aria-controls="aipkit_stats_billing_shortcode_panel"
                        data-aipkit-billing-tab="shortcode"
                        tabindex="-1"
                    >
                        <?php esc_html_e('WooCommerce', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>

                <div
                    class="aipkit_stats_inner_panel"
                    id="aipkit_stats_billing_pricing_panel"
                    role="tabpanel"
                    aria-labelledby="aipkit_stats_billing_tab_pricing"
                    data-aipkit-billing-panel="pricing"
                >
                    <div class="aipkit_sub_container aipkit_stats_management_card aipkit_stats_shell">
                        <div class="aipkit_sub_container_header aipkit_stats_panel_header">
                            <div class="aipkit_stats_panel_intro">
                                <div class="aipkit_sub_container_title"><?php esc_html_e('Pricing Rules', 'gpt3-ai-content-generator'); ?></div>
                                <p class="aipkit_stats_management_hint">
                                    <?php esc_html_e('Define module-level model pricing used for credit estimates and billing.', 'gpt3-ai-content-generator'); ?>
                                </p>
                            </div>
                            <div class="aipkit_stats_section_actions">
                                <button type="button" class="aipkit_btn aipkit_btn-primary" id="aipkit_stats_pricing_open">
                                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                                    <?php esc_html_e('New Pricing', 'gpt3-ai-content-generator'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="aipkit_stats_management_body aipkit_stats_shell_body">
                            <div class="aipkit_data-table aipkit_stats_pricing_table">
                                <table class="aipkit_data-table__table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Module', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Provider / Model', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Pricing', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="aipkit_stats_pricing_rules_body">
                                        <tr>
                                            <td colspan="5" class="aipkit_stats_table_placeholder">
                                                <?php esc_html_e('Loading pricing rules...', 'gpt3-ai-content-generator'); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="aipkit-modal-overlay aipkit_stats_pricing_modal" id="aipkit_stats_pricing_modal" aria-hidden="true">
                    <div class="aipkit-modal-content aipkit-modal-shell aipkit-modal-shell--compact aipkit-modal-shell--overflow-visible aipkit_stats_pricing_modal_content" role="dialog" aria-modal="true" aria-labelledby="aipkit_stats_pricing_modal_title" aria-describedby="aipkit_stats_pricing_modal_description">
                        <div class="aipkit-modal-header aipkit-modal-shell-header">
                            <div class="aipkit-modal-shell-intro">
                                <h2 class="aipkit-modal-shell-title" id="aipkit_stats_pricing_modal_title"><?php esc_html_e('New Rule', 'gpt3-ai-content-generator'); ?></h2>
                                <p class="aipkit-modal-shell-copy" id="aipkit_stats_pricing_modal_description">
                                    <?php esc_html_e('Set pricing for a usage type, model, and billing method.', 'gpt3-ai-content-generator'); ?>
                                </p>
                            </div>
                            <button class="aipkit-modal-close-btn aipkit-modal-shell-close" type="button" id="aipkit_stats_pricing_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <div class="aipkit-modal-body aipkit-modal-shell-body aipkit_stats_modal_body">
                            <form id="aipkit_stats_pricing_form" class="aipkit_stats_pricing_form">
                                <input type="hidden" id="aipkit_stats_pricing_rule_id" value="" />
                                <input type="hidden" id="aipkit_stats_pricing_enabled" value="1" />
                                <div class="aipkit_stats_pricing_form_grid">
                                    <label class="aipkit_form-field">
                                        <span class="aipkit_form-label"><?php esc_html_e('Usage type', 'gpt3-ai-content-generator'); ?></span>
                                        <select id="aipkit_stats_pricing_usage_type" class="aipkit_popover_select"></select>
                                        <select id="aipkit_stats_pricing_module" class="aipkit_popover_select" hidden tabindex="-1" aria-hidden="true">
                                            <option value="chat"><?php esc_html_e('Chatbot', 'gpt3-ai-content-generator'); ?></option>
                                            <option value="ai_forms"><?php esc_html_e('AI Forms', 'gpt3-ai-content-generator'); ?></option>
                                            <option value="image_generator"><?php esc_html_e('Image Generator', 'gpt3-ai-content-generator'); ?></option>
                                        </select>
                                    </label>
                                    <label class="aipkit_form-field">
                                        <span class="aipkit_form-label"><?php esc_html_e('Provider & Model', 'gpt3-ai-content-generator'); ?></span>
                                        <input type="hidden" id="aipkit_stats_pricing_provider" value="" />
                                        <input type="hidden" id="aipkit_stats_pricing_model" value="" />
                                        <select
                                            id="aipkit_stats_pricing_ai_selection"
                                            class="aipkit_form-input"
                                            data-aipkit-picker-title="<?php esc_attr_e('Provider & Model', 'gpt3-ai-content-generator'); ?>"
                                        >
                                            <option value=""><?php esc_html_e('Loading models...', 'gpt3-ai-content-generator'); ?></option>
                                        </select>
                                    </label>
                                    <label class="aipkit_form-field">
                                        <span class="aipkit_form-label"><?php esc_html_e('Billing method', 'gpt3-ai-content-generator'); ?></span>
                                        <select id="aipkit_stats_pricing_billing_method" class="aipkit_popover_select"></select>
                                        <select id="aipkit_stats_pricing_operation" class="aipkit_popover_select" hidden tabindex="-1" aria-hidden="true"></select>
                                    </label>
                                </div>
                                <div class="aipkit_stats_pricing_rate_grid">
                                    <label class="aipkit_form-field" data-rate-field="input_rate">
                                        <span class="aipkit_form-label"><?php esc_html_e('Input rate', 'gpt3-ai-content-generator'); ?></span>
                                        <input type="number" id="aipkit_stats_pricing_input_rate" class="aipkit_form-input" min="0" step="0.000001" placeholder="<?php esc_attr_e('e.g. 1', 'gpt3-ai-content-generator'); ?>" />
                                    </label>
                                    <label class="aipkit_form-field" data-rate-field="output_rate">
                                        <span class="aipkit_form-label"><?php esc_html_e('Output rate', 'gpt3-ai-content-generator'); ?></span>
                                        <input type="number" id="aipkit_stats_pricing_output_rate" class="aipkit_form-input" min="0" step="0.000001" placeholder="<?php esc_attr_e('e.g. 2', 'gpt3-ai-content-generator'); ?>" />
                                    </label>
                                    <label class="aipkit_form-field" data-rate-field="unit_rate">
                                        <span class="aipkit_form-label"><?php esc_html_e('Unit rate', 'gpt3-ai-content-generator'); ?></span>
                                        <input type="number" id="aipkit_stats_pricing_unit_rate" class="aipkit_form-input" min="0" step="0.000001" placeholder="<?php esc_attr_e('e.g. 1', 'gpt3-ai-content-generator'); ?>" />
                                    </label>
                                </div>
                                <div class="aipkit_stats_pricing_actions">
                                    <button type="button" class="aipkit_btn aipkit_btn-secondary" id="aipkit_stats_pricing_reset">
                                        <?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?>
                                    </button>
                                    <button type="submit" class="aipkit_btn aipkit_btn-primary" id="aipkit_stats_pricing_save">
                                        <?php esc_html_e('Save Rule', 'gpt3-ai-content-generator'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    class="aipkit_stats_inner_panel"
                    id="aipkit_stats_billing_activity_panel"
                    role="tabpanel"
                    aria-labelledby="aipkit_stats_billing_tab_activity"
                    data-aipkit-billing-panel="activity"
                    hidden
                >
                    <div class="aipkit_sub_container aipkit_stats_management_card aipkit_stats_shell">
                        <div class="aipkit_sub_container_header aipkit_stats_panel_header">
                            <div class="aipkit_stats_panel_intro">
                                <div class="aipkit_sub_container_title"><?php esc_html_e('Ledger Activity', 'gpt3-ai-content-generator'); ?></div>
                                <p class="aipkit_stats_management_hint">
                                    <?php esc_html_e('Review credits added, debited balance, and recent ledger entries for the selected time range.', 'gpt3-ai-content-generator'); ?>
                                </p>
                            </div>
                            <div class="aipkit_stats_section_actions">
                                <label class="screen-reader-text" for="aipkit_stats_ledger_days_filter"><?php esc_html_e('Ledger date range', 'gpt3-ai-content-generator'); ?></label>
                                <select id="aipkit_stats_ledger_days_filter" class="aipkit_popover_select" data-aipkit-stats-days-filter>
                                    <option value="7" <?php selected($stats_default_days, 7); ?>><?php esc_html_e('Last 7 days', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="30" <?php selected($stats_default_days, 30); ?>><?php esc_html_e('Last 30 days', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="90" <?php selected($stats_default_days, 90); ?>><?php esc_html_e('Last 90 days', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="aipkit_stats_management_body aipkit_stats_shell_body">
                            <div class="aipkit_stats_ledger_summary_grid">
                                <div class="aipkit_stats_ledger_metric">
                                    <span class="aipkit_stats_ledger_metric_label"><?php esc_html_e('Credits Added', 'gpt3-ai-content-generator'); ?></span>
                                    <span class="aipkit_stats_ledger_metric_value" id="aipkit_stats_ledger_added">-</span>
                                </div>
                                <div class="aipkit_stats_ledger_metric">
                                    <span class="aipkit_stats_ledger_metric_label"><?php esc_html_e('Balance Debited', 'gpt3-ai-content-generator'); ?></span>
                                    <span class="aipkit_stats_ledger_metric_value" id="aipkit_stats_ledger_spent">-</span>
                                </div>
                                <div class="aipkit_stats_ledger_metric">
                                    <span class="aipkit_stats_ledger_metric_label"><?php esc_html_e('Quota-Only Usage', 'gpt3-ai-content-generator'); ?></span>
                                    <span class="aipkit_stats_ledger_metric_value" id="aipkit_stats_ledger_quota_only">-</span>
                                </div>
                                <div class="aipkit_stats_ledger_metric">
                                    <span class="aipkit_stats_ledger_metric_label"><?php esc_html_e('Ledger Entries', 'gpt3-ai-content-generator'); ?></span>
                                    <span class="aipkit_stats_ledger_metric_value" id="aipkit_stats_ledger_entries">-</span>
                                </div>
                            </div>

                            <div class="aipkit_data-table aipkit_stats_ledger_table">
                                <table class="aipkit_data-table__table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Time', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Actor', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Type', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Module / Model', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Credits', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php esc_html_e('Units', 'gpt3-ai-content-generator'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="aipkit_stats_ledger_body">
                                        <tr>
                                            <td colspan="6" class="aipkit_stats_table_placeholder">
                                                <?php esc_html_e('Loading ledger activity...', 'gpt3-ai-content-generator'); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="aipkit_stats_inner_panel"
                    id="aipkit_stats_billing_balances_panel"
                    role="tabpanel"
                    aria-labelledby="aipkit_stats_billing_tab_balances"
                    data-aipkit-billing-panel="balances"
                    hidden
                >
                    <div class="aipkit_sub_container aipkit_stats_management_card aipkit_stats_shell">
                        <div class="aipkit_sub_container_header aipkit_stats_panel_header">
                            <div class="aipkit_stats_panel_intro">
                                <div class="aipkit_sub_container_title"><?php esc_html_e('User Credits', 'gpt3-ai-content-generator'); ?></div>
                                <p class="aipkit_stats_management_hint">
                                    <?php esc_html_e('Manage user credit balances, periodic usage, and purchase history.', 'gpt3-ai-content-generator'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="aipkit_stats_management_body aipkit_stats_shell_body aipkit_stats_credits_panel_body">
                            <div class="aipkit_stats_user_sheet_section">
                                <div class="aipkit_stats_user_toolbar">
                                    <div class="aipkit_stats_user_search_row">
                                        <label class="screen-reader-text" for="aipkit_stats_user_search"><?php esc_html_e('Search users', 'gpt3-ai-content-generator'); ?></label>
                                        <input
                                            type="search"
                                            id="aipkit_stats_user_search"
                                            class="aipkit_form-input aipkit_stats_user_search_input"
                                            placeholder="<?php esc_attr_e('Search by username or email', 'gpt3-ai-content-generator'); ?>"
                                        />
                                    </div>
                                </div>

                                <div class="aipkit_data-table aipkit_stats_user_table">
                                    <table id="aipkit_stats_user_table">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('User', 'gpt3-ai-content-generator'); ?></th>
                                                <th><?php esc_html_e('Credit Balance', 'gpt3-ai-content-generator'); ?></th>
                                                <th><?php esc_html_e('Usage', 'gpt3-ai-content-generator'); ?></th>
                                                <th><?php esc_html_e('Latest Reset', 'gpt3-ai-content-generator'); ?></th>
                                                <th><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="aipkit_stats_user_table_body">
                                            <tr>
                                                <td colspan="5" class="aipkit_stats_table_placeholder">
                                                    <?php esc_html_e('Loading user credits...', 'gpt3-ai-content-generator'); ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot hidden>
                                            <tr>
                                                <th colspan="5">
                                                    <div class="aipkit_pagination" id="aipkit_stats_user_pagination"></div>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div id="aipkit_stats_user_no_results" class="aipkit_stats_user_empty" hidden>
                                    <?php esc_html_e('No user credit data found.', 'gpt3-ai-content-generator'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="aipkit_stats_inner_panel"
                    id="aipkit_stats_billing_shortcode_panel"
                    role="tabpanel"
                    aria-labelledby="aipkit_stats_billing_tab_shortcode"
                    data-aipkit-billing-panel="shortcode"
                    hidden
                >
                    <div class="aipkit_sub_container aipkit_stats_management_card aipkit_stats_shell">
                        <div class="aipkit_sub_container_header aipkit_stats_panel_header">
                            <div class="aipkit_stats_panel_intro">
                                <div class="aipkit_sub_container_title"><?php esc_html_e('Customer Access', 'gpt3-ai-content-generator'); ?></div>
                                <p class="aipkit_stats_management_hint">
                                    <?php esc_html_e('Set up the customer dashboard and surface WooCommerce credit packages from one place.', 'gpt3-ai-content-generator'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="aipkit_stats_management_body aipkit_stats_shell_body">
                            <div class="aipkit_stats_customer_stack">
                                <div class="aipkit_stats_shortcode_card">
                                    <div class="aipkit_stats_shortcode_intro">
                                        <div class="aipkit_stats_shortcode_intro_title"><?php esc_html_e('Customer Dashboard', 'gpt3-ai-content-generator'); ?></div>
                                        <p class="aipkit_stats_shortcode_intro_copy">
                                            <?php esc_html_e('Click the shortcode to copy it. The dashboard shows credit balance, recent purchases, and module quota usage. Use the display options below only if you want to hide specific quota sections.', 'gpt3-ai-content-generator'); ?>
                                        </p>
                                    </div>
                                    <div class="aipkit_stats_shortcode_controls">
                                        <button
                                            type="button"
                                            id="aipkit_stats_shortcode_snippet"
                                            class="aipkit_stats_shortcode_snippet"
                                            data-shortcode="[aipkit_token_usage]"
                                            title="<?php echo esc_attr('[aipkit_token_usage]'); ?>"
                                            aria-label="<?php esc_attr_e('Click to copy shortcode', 'gpt3-ai-content-generator'); ?>"
                                        >
                                            <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                                            <span class="aipkit_stats_shortcode_text">[aipkit_token_usage]</span>
                                        </button>
                                    </div>
                                    <div class="aipkit_stats_shortcode_customization">
                                        <div class="aipkit_stats_shortcode_toggle_card">
                                            <div class="aipkit_stats_shortcode_toggles">
                                                <label class="aipkit_settings_big_checkbox aipkit_stats_shortcode_toggle_checkbox">
                                                    <input type="checkbox" name="cfg_show_buycredits" class="aipkit_stats_shortcode_option" value="1" checked>
                                                    <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                                                        <span class="dashicons dashicons-yes"></span>
                                                    </span>
                                                    <span class="aipkit_stats_shortcode_toggle_label"><?php esc_html_e('Show buy credits button', 'gpt3-ai-content-generator'); ?></span>
                                                </label>
                                                <label class="aipkit_settings_big_checkbox aipkit_stats_shortcode_toggle_checkbox">
                                                    <input type="checkbox" name="cfg_show_purchasehistory" class="aipkit_stats_shortcode_option" value="1" checked>
                                                    <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                                                        <span class="dashicons dashicons-yes"></span>
                                                    </span>
                                                    <span class="aipkit_stats_shortcode_toggle_label"><?php esc_html_e('Show purchase history', 'gpt3-ai-content-generator'); ?></span>
                                                </label>
                                                <label class="aipkit_settings_big_checkbox aipkit_stats_shortcode_toggle_checkbox">
                                                    <input type="checkbox" name="cfg_show_chatbot" class="aipkit_stats_shortcode_option" value="1" checked>
                                                    <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                                                        <span class="dashicons dashicons-yes"></span>
                                                    </span>
                                                    <span class="aipkit_stats_shortcode_toggle_label"><?php esc_html_e('Show chatbot usage', 'gpt3-ai-content-generator'); ?></span>
                                                </label>
                                                <label class="aipkit_settings_big_checkbox aipkit_stats_shortcode_toggle_checkbox">
                                                    <input type="checkbox" name="cfg_show_aiforms" class="aipkit_stats_shortcode_option" value="1" checked>
                                                    <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                                                        <span class="dashicons dashicons-yes"></span>
                                                    </span>
                                                    <span class="aipkit_stats_shortcode_toggle_label"><?php esc_html_e('Show AI Forms usage', 'gpt3-ai-content-generator'); ?></span>
                                                </label>
                                                <label class="aipkit_settings_big_checkbox aipkit_stats_shortcode_toggle_checkbox">
                                                    <input type="checkbox" name="cfg_show_imagegenerator" class="aipkit_stats_shortcode_option" value="1" checked>
                                                    <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                                                        <span class="dashicons dashicons-yes"></span>
                                                    </span>
                                                    <span class="aipkit_stats_shortcode_toggle_label"><?php esc_html_e('Show image generator usage', 'gpt3-ai-content-generator'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aipkit_stats_shortcode_fields_card">
                                            <div class="aipkit_stats_shortcode_fields">
                                                <label class="aipkit_stats_shortcode_field aipkit_stats_shortcode_field--full">
                                                    <span class="aipkit_stats_shortcode_field_label"><?php esc_html_e('Intro text', 'gpt3-ai-content-generator'); ?></span>
                                                    <input
                                                        type="text"
                                                        class="aipkit_form-input aipkit_stats_shortcode_text_option"
                                                        name="cfg_dashboard_intro"
                                                        value="<?php echo esc_attr__('View your credits, purchases, and quotas.', 'gpt3-ai-content-generator'); ?>"
                                                        data-default-value="<?php echo esc_attr__('View your credits, purchases, and quotas.', 'gpt3-ai-content-generator'); ?>"
                                                    >
                                                </label>
                                                <label class="aipkit_stats_shortcode_field">
                                                    <span class="aipkit_stats_shortcode_field_label"><?php esc_html_e('Customer dashboard URL', 'gpt3-ai-content-generator'); ?></span>
                                                    <input
                                                        type="url"
                                                        class="aipkit_form-input aipkit_stats_shortcode_text_option"
                                                        name="cfg_dashboard_url"
                                                        value="<?php echo esc_attr($customer_dashboard_page_url); ?>"
                                                        data-default-value="<?php echo esc_attr($customer_dashboard_page_url); ?>"
                                                        placeholder="<?php esc_attr_e('https://example.com/customer-dashboard', 'gpt3-ai-content-generator'); ?>"
                                                    >
                                                </label>
                                                <label class="aipkit_stats_shortcode_field">
                                                    <span class="aipkit_stats_shortcode_field_label"><?php esc_html_e('Default buy credits URL', 'gpt3-ai-content-generator'); ?></span>
                                                    <input
                                                        type="url"
                                                        class="aipkit_form-input aipkit_stats_shortcode_text_option"
                                                        name="cfg_buycredits_url"
                                                        value="<?php echo esc_attr($customer_dashboard_buycredits_saved_url); ?>"
                                                        data-default-value="<?php echo esc_attr($customer_dashboard_buycredits_saved_url); ?>"
                                                        placeholder="<?php echo esc_attr($customer_dashboard_buycredits_default_url); ?>"
                                                    >
                                                </label>
                                                <label class="aipkit_stats_shortcode_field">
                                                    <span class="aipkit_stats_shortcode_field_label"><?php esc_html_e('Dashboard title', 'gpt3-ai-content-generator'); ?></span>
                                                    <input
                                                        type="text"
                                                        class="aipkit_form-input aipkit_stats_shortcode_text_option"
                                                        name="cfg_dashboard_title"
                                                        value="<?php echo esc_attr__('Credits & Usage', 'gpt3-ai-content-generator'); ?>"
                                                        data-default-value="<?php echo esc_attr__('Credits & Usage', 'gpt3-ai-content-generator'); ?>"
                                                    >
                                                </label>
                                                <label class="aipkit_stats_shortcode_field">
                                                    <span class="aipkit_stats_shortcode_field_label"><?php esc_html_e('Buy credits button label', 'gpt3-ai-content-generator'); ?></span>
                                                    <input
                                                        type="text"
                                                        class="aipkit_form-input aipkit_stats_shortcode_text_option"
                                                        name="cfg_buycredits_label"
                                                        value="<?php echo esc_attr__('Buy credits', 'gpt3-ai-content-generator'); ?>"
                                                        data-default-value="<?php echo esc_attr__('Buy credits', 'gpt3-ai-content-generator'); ?>"
                                                    >
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="aipkit_stats_shortcode_card aipkit_stats_customer_woo_card">
                                    <div class="aipkit_stats_shortcode_intro">
                                        <div class="aipkit_stats_shortcode_intro_title"><?php esc_html_e('Sell Credits with WooCommerce', 'gpt3-ai-content-generator'); ?></div>
                                        <p class="aipkit_stats_shortcode_intro_copy">
                                            <?php esc_html_e('Create credit products in WooCommerce and use the AI Puffer box on the product editor to define how many credits each package grants.', 'gpt3-ai-content-generator'); ?>
                                        </p>
                                    </div>

                                    <div class="aipkit_stats_customer_woo_status">
                                        <span class="aipkit_stats_customer_woo_badge <?php echo $woo_active ? 'is-active' : 'is-inactive'; ?>">
                                            <?php echo $woo_active ? esc_html__('WooCommerce active', 'gpt3-ai-content-generator') : esc_html__('WooCommerce not active', 'gpt3-ai-content-generator'); ?>
                                        </span>
                                        <span class="aipkit_stats_customer_woo_status_text">
                                            <?php
                                            if (!$woo_active) {
                                                esc_html_e('Activate WooCommerce to start selling AI Puffer credit packages.', 'gpt3-ai-content-generator');
                                            } elseif ($can_manage_woo_products) {
                                                esc_html_e('Open any WooCommerce product to find the AI Puffer credit package box.', 'gpt3-ai-content-generator');
                                            } else {
                                                esc_html_e('Your role can review credit package summaries, but cannot edit WooCommerce products.', 'gpt3-ai-content-generator');
                                            }
                                            ?>
                                        </span>
                                    </div>

                                    <div class="aipkit_stats_customer_woo_actions">
                                        <?php if ($woo_active && $can_manage_woo_products): ?>
                                            <a class="aipkit_btn aipkit_btn-primary" href="<?php echo esc_url($woo_create_product_url); ?>">
                                                <?php esc_html_e('Create credit product', 'gpt3-ai-content-generator'); ?>
                                            </a>
                                            <a class="aipkit_btn aipkit_btn-secondary" href="<?php echo esc_url($woo_products_url); ?>">
                                                <?php esc_html_e('View products', 'gpt3-ai-content-generator'); ?>
                                            </a>
                                        <?php elseif (!$woo_active): ?>
                                            <a class="aipkit_btn aipkit_btn-secondary" href="<?php echo esc_url(admin_url('plugins.php')); ?>">
                                                <?php esc_html_e('Manage plugins', 'gpt3-ai-content-generator'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="aipkit_stats_pricing_rule_meta"><?php esc_html_e('No WooCommerce product access', 'gpt3-ai-content-generator'); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($woo_active): ?>
                                        <div class="aipkit_stats_shortcode_notes aipkit_stats_customer_woo_notes">
                                            <div class="aipkit_stats_shortcode_note">
                                                <strong><?php esc_html_e('Current packages:', 'gpt3-ai-content-generator'); ?></strong>
                                                <?php
                                                printf(
                                                    /* translators: %s: number of WooCommerce credit products */
                                                    esc_html__('%s credit products found.', 'gpt3-ai-content-generator'),
                                                    esc_html(number_format_i18n(count($woo_credit_products)))
                                                );
                                                ?>
                                            </div>
                                            <div class="aipkit_stats_shortcode_note">
                                                <strong><?php esc_html_e('Tip:', 'gpt3-ai-content-generator'); ?></strong>
                                                <?php esc_html_e('Credit packages add balance to the user account. Module quotas still apply separately.', 'gpt3-ai-content-generator'); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($woo_active && !empty($woo_credit_products)): ?>
                                        <div class="aipkit_data-table aipkit_stats_customer_woo_table">
                                            <table class="aipkit_data-table__table">
                                                <thead>
                                                    <tr>
                                                        <th><?php esc_html_e('Product', 'gpt3-ai-content-generator'); ?></th>
                                                        <th><?php esc_html_e('Credits', 'gpt3-ai-content-generator'); ?></th>
                                                        <th><?php esc_html_e('Price', 'gpt3-ai-content-generator'); ?></th>
                                                        <th><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                                                        <th><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($woo_credit_products as $woo_credit_product): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="aipkit_stats_pricing_rule_title"><?php echo esc_html($woo_credit_product['title']); ?></div>
                                                                <div class="aipkit_stats_pricing_rule_meta">#<?php echo esc_html((string) $woo_credit_product['id']); ?></div>
                                                            </td>
                                                            <td><?php echo esc_html(number_format_i18n((int) $woo_credit_product['credits'])); ?></td>
                                                            <td><?php echo wp_kses_post($woo_credit_product['price']); ?></td>
                                                            <td><?php echo esc_html($woo_credit_product['status']); ?></td>
                                                            <td>
                                                                <div class="aipkit_stats_pricing_actions_inline">
                                                                    <?php if (!empty($woo_credit_product['edit_url'])): ?>
                                                                        <a class="aipkit_btn aipkit_btn-secondary" href="<?php echo esc_url($woo_credit_product['edit_url']); ?>">
                                                                            <?php esc_html_e('Edit', 'gpt3-ai-content-generator'); ?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <span class="aipkit_stats_pricing_rule_meta"><?php esc_html_e('No edit access', 'gpt3-ai-content-generator'); ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php elseif ($woo_active): ?>
                                        <div class="aipkit_stats_customer_woo_empty">
                                            <?php esc_html_e('No AI Puffer credit products yet. Create your first WooCommerce product and enable the AI Puffer credit package box there.', 'gpt3-ai-content-generator'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <div
        class="aipkit-modal-overlay aipkit_stats_retention_modal"
        id="aipkit_stats_retention_modal"
        aria-hidden="true"
    >
        <div class="aipkit-modal-content aipkit-modal-shell aipkit-modal-shell--compact aipkit_stats_retention_modal_content" role="dialog" aria-modal="true" aria-labelledby="aipkit_stats_retention_modal_title" aria-describedby="aipkit_stats_retention_modal_description">
            <div class="aipkit-modal-header aipkit-modal-shell-header">
                <div class="aipkit-modal-shell-intro">
                    <h2 class="aipkit-modal-shell-title" id="aipkit_stats_retention_modal_title"><?php esc_html_e('Log Retention', 'gpt3-ai-content-generator'); ?></h2>
                    <p class="aipkit-modal-shell-copy" id="aipkit_stats_retention_modal_description"><?php esc_html_e('Control how long saved conversation logs stay in your database.', 'gpt3-ai-content-generator'); ?></p>
                </div>
                <button type="button" class="aipkit-modal-close-btn aipkit-modal-shell-close" id="aipkit_stats_retention_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aipkit-modal-body aipkit-modal-shell-body aipkit_stats_retention_modal_body">
                <div class="aipkit_stats_retention_setting">
                    <div class="aipkit_stats_retention_setting_copy">
                        <span class="aipkit_stats_retention_setting_label"><?php esc_html_e('Auto-delete logs', 'gpt3-ai-content-generator'); ?></span>
                        <p class="aipkit_stats_retention_setting_hint"><?php esc_html_e('Run pruning automatically in the background.', 'gpt3-ai-content-generator'); ?></p>
                    </div>
                    <div class="aipkit_stats_retention_setting_control aipkit_stats_retention_setting_control--toggle">
                        <?php if ($is_pro): ?>
                            <label class="aipkit_settings_big_checkbox" for="aipkit_stats_auto_delete_toggle">
                                <input
                                    type="checkbox"
                                    id="aipkit_stats_auto_delete_toggle"
                                    <?php checked($log_settings_enabled); ?>
                                />
                                <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                                    <span class="dashicons dashicons-yes"></span>
                                </span>
                                <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
                            </label>
                        <?php endif; ?>
                        <?php if (!$is_pro): ?>
                            <a class="aipkit_btn aipkit_btn-primary" href="<?php echo esc_url($upgrade_url); ?>">
                                <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div
                    class="aipkit_stats_retention_setting aipkit_stats_retention_row"
                    data-aipkit-stats-retention-row
                    <?php echo $log_settings_enabled ? '' : 'hidden'; ?>
                >
                    <div class="aipkit_stats_retention_setting_copy">
                        <label class="aipkit_stats_retention_setting_label" for="aipkit_stats_retention_days">
                            <?php esc_html_e('Delete logs older than', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <p class="aipkit_stats_retention_setting_hint"><?php esc_html_e('Older logs are removed after this period.', 'gpt3-ai-content-generator'); ?></p>
                    </div>
                    <div class="aipkit_stats_retention_setting_control">
                        <select
                            id="aipkit_stats_retention_days"
                            class="aipkit_popover_select aipkit_stats_retention_select"
                            <?php disabled(!$is_pro || !$log_settings_enabled); ?>
                        >
                            <?php foreach ($retention_options as $retention_value => $retention_label): ?>
                                <option value="<?php echo esc_attr($retention_value); ?>" <?php selected($log_settings_retention, $retention_value); ?>>
                                    <?php echo esc_html($retention_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div
                    class="aipkit_stats_retention_info aipkit_stats_cron_row"
                    data-aipkit-stats-cron-row
                    <?php echo $log_settings_enabled ? '' : 'hidden'; ?>
                >
                    <div class="aipkit_stats_retention_info_copy">
                        <span class="aipkit_stats_retention_info_label"><?php esc_html_e('Pruning Schedule', 'gpt3-ai-content-generator'); ?></span>
                        <p class="aipkit_stats_retention_info_hint"><?php esc_html_e('Background cleanup runs automatically based on the current retention settings.', 'gpt3-ai-content-generator'); ?></p>
                    </div>
                    <div class="aipkit_stats_retention_info_meta">
                        <div
                            class="aipkit_stats_cron_status"
                            id="aipkit_stats_cron_status"
                            data-state="<?php echo esc_attr($cron_state); ?>"
                        >
                            <span class="aipkit_stats_cron_dot" aria-hidden="true"></span>
                            <span class="aipkit_stats_cron_text" id="aipkit_stats_cron_text"><?php echo esc_html($cron_text); ?></span>
                        </div>
                        <span class="aipkit_stats_cron_last_run" id="aipkit_stats_cron_last_run">
                            <?php
                            printf(
                                /* translators: %s: last run time */
                                esc_html__('Last run: %s', 'gpt3-ai-content-generator'),
                                esc_html($last_run_label)
                            );
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

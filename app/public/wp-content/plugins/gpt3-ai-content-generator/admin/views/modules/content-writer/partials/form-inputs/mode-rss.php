<?php
/**
 * Content Writer RSS Mode tab (module-specific).
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

// $is_pro is available from the parent scope (loader-vars.php)
$aipkit_show_rss_fetch_button = !empty($aipkit_show_rss_fetch_button);
$aipkit_premium_partial = WPAICG_LIB_DIR . 'views/modules/content-writer/partials/form-inputs/mode-rss.php';

if ($is_pro && file_exists($aipkit_premium_partial)) {
    include $aipkit_premium_partial;
    return;
}

$aipkit_feature_promo_class = 'aipkit_feature_promo--rss';
$aipkit_feature_promo_dashicon = 'dashicons-rss';
$aipkit_feature_promo_title = __('RSS Feed Content Generation', 'gpt3-ai-content-generator');
$aipkit_feature_promo_subtitle = __('Automatically turn RSS feeds into unique, AI-written posts — hands-free.', 'gpt3-ai-content-generator');
$aipkit_feature_promo_steps = [
    __('Add your RSS feed URLs', 'gpt3-ai-content-generator'),
    __('AI rewrites each item', 'gpt3-ai-content-generator'),
    __('Auto-publish to WordPress', 'gpt3-ai-content-generator'),
];
$aipkit_feature_promo_cards = [
    ['icon' => '⊞', 'color' => '#c2410c', 'label' => __('Multiple Feeds', 'gpt3-ai-content-generator')],
    ['icon' => '⚙', 'color' => '#16a34a', 'label' => __('Smart Parsing', 'gpt3-ai-content-generator')],
    ['icon' => '⏱', 'color' => '#2563eb', 'label' => __('Auto-Schedule', 'gpt3-ai-content-generator')],
];
$aipkit_feature_promo_upgrade_url = function_exists('wpaicg_gacg_fs') ? wpaicg_gacg_fs()->get_upgrade_url() : '#';
include WPAICG_PLUGIN_DIR . 'admin/views/modules/shared/feature-promo.php';

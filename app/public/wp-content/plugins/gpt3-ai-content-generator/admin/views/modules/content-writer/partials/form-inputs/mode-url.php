<?php
/**
 * Content Writer URL Mode tab (module-specific).
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

// $is_pro is available from the parent scope (loader-vars.php)
$aipkit_premium_partial = WPAICG_LIB_DIR . 'views/modules/content-writer/partials/form-inputs/mode-url.php';

if ($is_pro && file_exists($aipkit_premium_partial)) {
    include $aipkit_premium_partial;
    return;
}

$aipkit_feature_promo_class = 'aipkit_feature_promo--url';
$aipkit_feature_promo_dashicon = 'dashicons-admin-links';
$aipkit_feature_promo_title = __('URL Content Extraction', 'gpt3-ai-content-generator');
$aipkit_feature_promo_subtitle = __('Paste any URL and let AI extract, rewrite, and publish the content for you.', 'gpt3-ai-content-generator');
$aipkit_feature_promo_steps = [
    __('Paste website URLs', 'gpt3-ai-content-generator'),
    __('AI extracts key content', 'gpt3-ai-content-generator'),
    __('Generate unique posts', 'gpt3-ai-content-generator'),
];
$aipkit_feature_promo_cards = [
    ['icon' => '🌐', 'color' => '#2563eb', 'label' => __('Any Website', 'gpt3-ai-content-generator')],
    ['icon' => '✦', 'color' => '#9333ea', 'label' => __('Smart Extraction', 'gpt3-ai-content-generator')],
    ['icon' => '⚡', 'color' => '#c2410c', 'label' => __('Bulk Processing', 'gpt3-ai-content-generator')],
];
$aipkit_feature_promo_upgrade_url = function_exists('wpaicg_gacg_fs') ? wpaicg_gacg_fs()->get_upgrade_url() : '#';
include WPAICG_PLUGIN_DIR . 'admin/views/modules/shared/feature-promo.php';

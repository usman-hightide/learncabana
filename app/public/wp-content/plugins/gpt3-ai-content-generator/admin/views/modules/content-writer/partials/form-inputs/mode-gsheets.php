<?php
/**
 * Content Writer Google Sheets Mode tab (module-specific).
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

// $is_pro is available from the parent scope (loader-vars.php)
$aipkit_premium_partial = WPAICG_LIB_DIR . 'views/modules/content-writer/partials/form-inputs/mode-gsheets.php';

if ($is_pro && file_exists($aipkit_premium_partial)) {
    include $aipkit_premium_partial;
    return;
}

$aipkit_feature_promo_class = 'aipkit_feature_promo--gsheets';
$aipkit_feature_promo_dashicon = 'dashicons-media-spreadsheet';
$aipkit_feature_promo_title = __('Google Sheets Import', 'gpt3-ai-content-generator');
$aipkit_feature_promo_subtitle = __('Connect a spreadsheet, map columns to prompts, and bulk-generate content.', 'gpt3-ai-content-generator');
$aipkit_feature_promo_steps = [
    __('Connect your Google Sheet', 'gpt3-ai-content-generator'),
    __('Map columns to fields', 'gpt3-ai-content-generator'),
    __('Bulk-generate content', 'gpt3-ai-content-generator'),
];
$aipkit_feature_promo_cards = [
    ['icon' => '↻', 'color' => '#16a34a', 'label' => __('Live Sync', 'gpt3-ai-content-generator')],
    ['icon' => '⊞', 'color' => '#2563eb', 'label' => __('Column Mapping', 'gpt3-ai-content-generator')],
    ['icon' => '⚡', 'color' => '#9333ea', 'label' => __('Bulk Generation', 'gpt3-ai-content-generator')],
];
$aipkit_feature_promo_upgrade_url = function_exists('wpaicg_gacg_fs') ? wpaicg_gacg_fs()->get_upgrade_url() : '#';
include WPAICG_PLUGIN_DIR . 'admin/views/modules/shared/feature-promo.php';

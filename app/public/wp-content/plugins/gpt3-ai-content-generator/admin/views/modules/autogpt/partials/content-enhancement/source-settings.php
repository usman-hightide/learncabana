<?php
/**
 * Partial: Automated Task Form - Content Enhancement Source Settings Wrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$aipkit_premium_partial = WPAICG_LIB_DIR . 'views/modules/autogpt/partials/content-enhancement/source-settings.php';

if (!empty($is_pro) && file_exists($aipkit_premium_partial)) {
    include $aipkit_premium_partial;
    return;
}

$aipkit_feature_promo_class = 'aipkit_feature_promo--content-enhance';
$aipkit_feature_promo_dashicon = 'dashicons-update';
$aipkit_feature_promo_title = __('Bulk Content Enhancement', 'gpt3-ai-content-generator');
$aipkit_feature_promo_subtitle = __('Automatically refresh and improve your existing posts with AI — at scale.', 'gpt3-ai-content-generator');
$aipkit_feature_promo_steps = [
    __('Select posts to update', 'gpt3-ai-content-generator'),
    __('AI enhances content', 'gpt3-ai-content-generator'),
    __('Posts updated automatically', 'gpt3-ai-content-generator'),
];
$aipkit_feature_promo_cards = [
    ['icon' => '✏', 'color' => '#2563eb', 'label' => __('Rewrite & Polish', 'gpt3-ai-content-generator')],
    ['icon' => '⚙', 'color' => '#16a34a', 'label' => __('Custom Prompts', 'gpt3-ai-content-generator')],
    ['icon' => '⚡', 'color' => '#9333ea', 'label' => __('Bulk Processing', 'gpt3-ai-content-generator')],
];
$aipkit_feature_promo_upgrade_url = function_exists('wpaicg_gacg_fs') ? wpaicg_gacg_fs()->get_upgrade_url() : '#';
include WPAICG_PLUGIN_DIR . 'admin/views/modules/shared/feature-promo.php';

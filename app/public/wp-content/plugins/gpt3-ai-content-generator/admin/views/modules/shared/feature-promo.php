<?php
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Shared view partial configured by parent templates.

$aipkit_feature_promo_class = isset($aipkit_feature_promo_class) ? (string) $aipkit_feature_promo_class : '';
$aipkit_feature_promo_dashicon = isset($aipkit_feature_promo_dashicon) ? sanitize_html_class((string) $aipkit_feature_promo_dashicon) : '';
$aipkit_feature_promo_title = isset($aipkit_feature_promo_title) ? (string) $aipkit_feature_promo_title : '';
$aipkit_feature_promo_subtitle = isset($aipkit_feature_promo_subtitle) ? (string) $aipkit_feature_promo_subtitle : '';
$aipkit_feature_promo_steps = isset($aipkit_feature_promo_steps) && is_array($aipkit_feature_promo_steps) ? $aipkit_feature_promo_steps : [];
$aipkit_feature_promo_cards = isset($aipkit_feature_promo_cards) && is_array($aipkit_feature_promo_cards) ? $aipkit_feature_promo_cards : [];
$aipkit_feature_promo_upgrade_url = isset($aipkit_feature_promo_upgrade_url) ? (string) $aipkit_feature_promo_upgrade_url : '#';
$aipkit_feature_promo_docs_url = isset($aipkit_feature_promo_docs_url) ? (string) $aipkit_feature_promo_docs_url : 'https://docs.aipower.org/';
?>
<div class="aipkit_feature_promo <?php echo esc_attr($aipkit_feature_promo_class); ?>">
    <div class="aipkit_feature_promo_hero">
        <div class="aipkit_feature_promo_icon_ring">
            <span class="dashicons <?php echo esc_attr($aipkit_feature_promo_dashicon); ?>" aria-hidden="true"></span>
        </div>
        <h3 class="aipkit_feature_promo_title"><?php echo esc_html($aipkit_feature_promo_title); ?></h3>
        <p class="aipkit_feature_promo_subtitle"><?php echo esc_html($aipkit_feature_promo_subtitle); ?></p>
    </div>

    <div class="aipkit_feature_promo_steps">
        <?php foreach (array_values($aipkit_feature_promo_steps) as $aipkit_feature_promo_step_index => $aipkit_feature_promo_step) : ?>
            <?php if ($aipkit_feature_promo_step_index > 0) : ?>
                <span class="aipkit_feature_promo_step_arrow" aria-hidden="true">&rarr;</span>
            <?php endif; ?>
            <div class="aipkit_feature_promo_step">
                <span class="aipkit_feature_promo_step_num"><?php echo esc_html((string) ($aipkit_feature_promo_step_index + 1)); ?></span>
                <span class="aipkit_feature_promo_step_text"><?php echo esc_html((string) $aipkit_feature_promo_step); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="aipkit_feature_promo_cards">
        <?php foreach ($aipkit_feature_promo_cards as $aipkit_feature_promo_card) : ?>
            <?php
            $aipkit_feature_promo_card_icon = isset($aipkit_feature_promo_card['icon']) ? (string) $aipkit_feature_promo_card['icon'] : '';
            $aipkit_feature_promo_card_color = isset($aipkit_feature_promo_card['color']) ? sanitize_hex_color((string) $aipkit_feature_promo_card['color']) : '';
            $aipkit_feature_promo_card_label = isset($aipkit_feature_promo_card['label']) ? (string) $aipkit_feature_promo_card['label'] : '';
            ?>
            <div class="aipkit_feature_promo_card">
                <span class="aipkit_feature_promo_card_icon" style="color:<?php echo esc_attr($aipkit_feature_promo_card_color ?: '#2563eb'); ?>" aria-hidden="true"><?php echo esc_html($aipkit_feature_promo_card_icon); ?></span>
                <span class="aipkit_feature_promo_card_label"><?php echo esc_html($aipkit_feature_promo_card_label); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="aipkit_feature_promo_cta">
        <a class="aipkit_btn aipkit_btn-primary aipkit_feature_promo_btn" href="<?php echo esc_url($aipkit_feature_promo_upgrade_url); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
        </a>
        <a class="aipkit_feature_promo_link" href="<?php echo esc_url($aipkit_feature_promo_docs_url); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('Learn more', 'gpt3-ai-content-generator'); ?>
            <span aria-hidden="true">&rarr;</span>
        </a>
    </div>
</div>

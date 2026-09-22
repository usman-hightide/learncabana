<?php
/**
 * Partial: AutoGPT Settings Popover
 * Current: Cron status summary (future options will be added here).
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (!empty($aipkit_autogpt_cron_summary)) : ?>
    <div class="aipkit_autogpt_settings_section">
        <span class="aipkit_autogpt_settings_title"><?php esc_html_e('Cron Status', 'gpt3-ai-content-generator'); ?></span>
        <div class="aipkit_autogpt_settings_list">
            <div class="aipkit_autogpt_settings_item">
                <span class="aipkit_autogpt_settings_key"><?php esc_html_e('WP-Cron', 'gpt3-ai-content-generator'); ?></span>
                <span class="aipkit_autogpt_settings_value"><?php echo esc_html($aipkit_autogpt_cron_summary['status_label']); ?></span>
            </div>
            <div class="aipkit_autogpt_settings_item">
                <span class="aipkit_autogpt_settings_key"><?php esc_html_e('Next run', 'gpt3-ai-content-generator'); ?></span>
                <?php $aipkit_cron_next_ts = !empty($aipkit_autogpt_cron_summary['next_timestamp']) ? (int) $aipkit_autogpt_cron_summary['next_timestamp'] : 0; ?>
                <span
                    class="aipkit_autogpt_settings_value"
                    <?php if ($aipkit_cron_next_ts > 0) : ?>
                        data-aipkit-cron-timestamp="<?php echo esc_attr($aipkit_cron_next_ts); ?>"
                    <?php endif; ?>
                >
                    <?php echo esc_html($aipkit_autogpt_cron_summary['next_label']); ?>
                </span>
            </div>
            <div class="aipkit_autogpt_settings_item">
                <span class="aipkit_autogpt_settings_key"><?php esc_html_e('Tasks scheduled', 'gpt3-ai-content-generator'); ?></span>
                <span class="aipkit_autogpt_settings_value"><?php echo esc_html(number_format_i18n((int) $aipkit_autogpt_cron_summary['task_count'])); ?></span>
            </div>
        </div>
        <?php if (!empty($aipkit_autogpt_cron_summary['tip'])) : ?>
            <p class="aipkit_autogpt_settings_tip"><?php echo wp_kses_post($aipkit_autogpt_cron_summary['tip']); ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

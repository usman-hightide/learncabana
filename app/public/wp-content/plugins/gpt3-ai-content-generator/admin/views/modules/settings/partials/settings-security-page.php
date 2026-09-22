<?php
/**
 * Partial: Security Settings Page
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

use WPAICG\Core\Moderation\AIPKit_Global_Security_Settings;

$security_settings = class_exists(AIPKit_Global_Security_Settings::class)
    ? AIPKit_Global_Security_Settings::get_settings()
    : [
        'enable_ip_anonymization' => '0',
        'blocklists' => [
            'banned_words' => '',
            'banned_words_message' => '',
            'banned_ips' => '',
            'banned_ips_message' => '',
        ],
    ];
$security_blocklists = isset($security_settings['blocklists']) && is_array($security_settings['blocklists'])
    ? $security_settings['blocklists']
    : [];
$enable_ip_anonymization = isset($security_settings['enable_ip_anonymization']) && (string) $security_settings['enable_ip_anonymization'] === '1'
    ? '1'
    : '0';
$banned_words = (string) ($security_blocklists['banned_words'] ?? '');
$banned_words_message = (string) ($security_blocklists['banned_words_message'] ?? '');
$banned_ips = (string) ($security_blocklists['banned_ips'] ?? '');
$banned_ips_message = (string) ($security_blocklists['banned_ips_message'] ?? '');
?>
<div class="aipkit_form-group aipkit_settings_simple_row">
    <label class="aipkit_form-label" for="aipkit_settings_enable_ip_anonymization">
        <?php esc_html_e('IP Anonymization', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Store anonymized IPs in logs.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <label class="aipkit_settings_big_checkbox" for="aipkit_settings_enable_ip_anonymization">
        <input
            type="checkbox"
            id="aipkit_settings_enable_ip_anonymization"
            name="security[enable_ip_anonymization]"
            class="aipkit_autosave_trigger"
            value="1"
            <?php checked($enable_ip_anonymization, '1'); ?>
        />
        <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
            <span class="dashicons dashicons-saved"></span>
        </span>
        <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
    </label>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row">
    <label class="aipkit_form-label" for="aipkit_settings_banned_words">
        <?php esc_html_e('Banned Words', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Block messages containing listed words or phrases.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_security_field_group">
        <textarea
            id="aipkit_settings_banned_words"
            name="security[blocklists][banned_words]"
            class="aipkit_form-input aipkit_autosave_trigger aipkit_settings_security_textarea"
            rows="4"
            placeholder="<?php esc_attr_e('e.g., word1, another word, specific phrase', 'gpt3-ai-content-generator'); ?>"
        ><?php echo esc_textarea($banned_words); ?></textarea>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row">
    <label class="aipkit_form-label" for="aipkit_settings_banned_words_message">
        <?php esc_html_e('Banned Words Message', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Message shown when banned words are detected.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_security_field_group">
        <input
            type="text"
            id="aipkit_settings_banned_words_message"
            name="security[blocklists][banned_words_message]"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($banned_words_message); ?>"
            placeholder="<?php esc_attr_e('Sorry, your message could not be sent as it contains prohibited words.', 'gpt3-ai-content-generator'); ?>"
            autocomplete="off"
            data-lpignore="true"
            data-1p-ignore="true"
            data-form-type="other"
        />
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row">
    <label class="aipkit_form-label" for="aipkit_settings_banned_ips">
        <?php esc_html_e('Banned IPs', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Block requests from listed IP addresses.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_security_field_group">
        <textarea
            id="aipkit_settings_banned_ips"
            name="security[blocklists][banned_ips]"
            class="aipkit_form-input aipkit_autosave_trigger aipkit_settings_security_textarea"
            rows="4"
            placeholder="<?php esc_attr_e('e.g., 123.123.123.123, 111.222.333.444', 'gpt3-ai-content-generator'); ?>"
        ><?php echo esc_textarea($banned_ips); ?></textarea>
    </div>
</div>

<div class="aipkit_form-group aipkit_settings_simple_row">
    <label class="aipkit_form-label" for="aipkit_settings_banned_ips_message">
        <?php esc_html_e('Banned IP Message', 'gpt3-ai-content-generator'); ?>
        <span class="aipkit_form-label-helper"><?php esc_html_e('Message shown when a blocked IP is detected.', 'gpt3-ai-content-generator'); ?></span>
    </label>
    <div class="aipkit_settings_security_field_group">
        <input
            type="text"
            id="aipkit_settings_banned_ips_message"
            name="security[blocklists][banned_ips_message]"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($banned_ips_message); ?>"
            placeholder="<?php esc_attr_e('Access from your IP address has been blocked.', 'gpt3-ai-content-generator'); ?>"
            autocomplete="off"
            data-lpignore="true"
            data-1p-ignore="true"
            data-form-type="other"
        />
    </div>
</div>

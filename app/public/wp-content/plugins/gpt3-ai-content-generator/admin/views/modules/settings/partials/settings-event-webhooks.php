<?php
/**
 * Partial: Event Webhooks Settings Section
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$event_webhook_settings = \WPAICG\Core\AIPKit_Event_Webhooks_Settings::get_settings();
$event_webhooks_enabled = (string) ($event_webhook_settings['enabled'] ?? '0') === '1' ? '1' : '0';
$event_webhook_signing_secret = (string) ($event_webhook_settings['signing_secret'] ?? '');
$event_webhook_endpoints = isset($event_webhook_settings['endpoints']) && is_array($event_webhook_settings['endpoints'])
    ? array_values($event_webhook_settings['endpoints'])
    : [];
$event_webhook_definitions = \WPAICG\Core\AIPKit_Event_Registry::get_definitions();
$event_webhook_queue_store_class = \WPAICG\Core\AIPKit_Event_Queue_Store::class;
$event_webhook_field_key_map = \WPAICG\Core\AIPKit_Event_Webhooks_Settings::get_event_field_key_map();
$event_webhook_delivery_issues = class_exists($event_webhook_queue_store_class) && method_exists($event_webhook_queue_store_class, 'get_recent_failed_webhook_jobs')
    ? $event_webhook_queue_store_class::get_recent_failed_webhook_jobs(5)
    : [];
$event_webhook_field_key_by_event_name = [];
foreach ($event_webhook_field_key_map as $field_key => $event_name) {
    $event_webhook_field_key_by_event_name[(string) $event_name] = (string) $field_key;
}
$event_webhook_module_labels = [
    'chatbot' => __('Chatbot', 'gpt3-ai-content-generator'),
    'ai_forms' => __('AI Forms', 'gpt3-ai-content-generator'),
    'content_writer' => __('Content Writer', 'gpt3-ai-content-generator'),
    'automated_tasks' => __('Automated Tasks', 'gpt3-ai-content-generator'),
    'image_generator' => __('Image Generator', 'gpt3-ai-content-generator'),
    'knowledge_base' => __('Knowledge Base', 'gpt3-ai-content-generator'),
];
$event_webhook_groups = [];
foreach ($event_webhook_definitions as $event_name => $definition) {
    $field_key = $event_webhook_field_key_by_event_name[$event_name] ?? '';
    if ($field_key === '') {
        continue;
    }

    $module_key = sanitize_key((string) ($definition['module'] ?? 'other'));
    if (!isset($event_webhook_groups[$module_key])) {
        $event_webhook_groups[$module_key] = [
            'label' => $event_webhook_module_labels[$module_key]
                ?? ucwords(str_replace('_', ' ', $module_key !== '' ? $module_key : 'other')),
            'events' => [],
        ];
    }

    $event_webhook_groups[$module_key]['events'][] = [
        'name' => $event_name,
        'field_key' => $field_key,
        'definition' => $definition,
    ];
}

$event_webhook_group_order = [
    'chatbot',
    'content_writer',
    'ai_forms',
    'image_generator',
    'automated_tasks',
    'knowledge_base',
];

$ordered_event_webhook_groups = [];
foreach ($event_webhook_group_order as $module_key) {
    if (isset($event_webhook_groups[$module_key])) {
        $ordered_event_webhook_groups[$module_key] = $event_webhook_groups[$module_key];
        unset($event_webhook_groups[$module_key]);
    }
}

if (!empty($event_webhook_groups)) {
    $ordered_event_webhook_groups = array_merge($ordered_event_webhook_groups, $event_webhook_groups);
}

$event_webhook_groups = $ordered_event_webhook_groups;

$render_event_webhook_endpoint = static function ($index, array $endpoint = []) use ($event_webhook_groups): void {
    $endpoint_index = (string) $index;
    $endpoint_dom_index = sanitize_key($endpoint_index);
    $endpoint_id = sanitize_key((string) ($endpoint['id'] ?? ''));
    $endpoint_name = (string) ($endpoint['name'] ?? '');
    $endpoint_url = (string) ($endpoint['url'] ?? '');
    $endpoint_enabled = isset($endpoint['enabled']) && (string) $endpoint['enabled'] === '1';
    $endpoint_events = isset($endpoint['events']) && is_array($endpoint['events']) ? $endpoint['events'] : [];
    $endpoint_event_count = 0;
    $endpoint_selected_event_count = 0;
    foreach ($event_webhook_groups as $group) {
        if (empty($group['events']) || !is_array($group['events'])) {
            continue;
        }
        foreach ($group['events'] as $event_item) {
            $event_name = (string) ($event_item['name'] ?? '');
            $field_key = (string) ($event_item['field_key'] ?? '');
            if ($event_name === '' || $field_key === '') {
                continue;
            }
            $endpoint_event_count++;
            if (in_array($event_name, $endpoint_events, true)) {
                $endpoint_selected_event_count++;
            }
        }
    }
    if ($endpoint_selected_event_count === 0) {
        $endpoint_events_label = __('Select events', 'gpt3-ai-content-generator');
    } elseif ($endpoint_event_count > 0 && $endpoint_selected_event_count === $endpoint_event_count) {
        $endpoint_events_label = __('All events selected', 'gpt3-ai-content-generator');
    } else {
        $endpoint_events_label = sprintf(
            /* translators: %d: number of selected webhook events. */
            _n('%d event selected', '%d events selected', $endpoint_selected_event_count, 'gpt3-ai-content-generator'),
            number_format_i18n($endpoint_selected_event_count)
        );
    }
    $endpoint_events_panel_id = 'aipkit_event_webhook_endpoint_' . $endpoint_dom_index . '_events_panel';
    ?>
    <article class="aipkit_settings_event_webhook_endpoint" data-aipkit-event-webhook-endpoint data-endpoint-index="<?php echo esc_attr($endpoint_index); ?>">
        <input
            type="hidden"
            name="event_webhooks[endpoints][<?php echo esc_attr($endpoint_index); ?>][id]"
            value="<?php echo esc_attr($endpoint_id); ?>"
            class="aipkit_autosave_trigger"
            data-aipkit-endpoint-field="id"
        />
        <button
            type="button"
            class="aipkit_settings_event_webhook_remove_btn"
            data-aipkit-remove-event-webhook-endpoint
            aria-label="<?php esc_attr_e('Remove endpoint', 'gpt3-ai-content-generator'); ?>"
            title="<?php esc_attr_e('Remove endpoint', 'gpt3-ai-content-generator'); ?>"
        >
            <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
        </button>
        <div class="aipkit_settings_event_webhook_endpoint_header">
            <div class="aipkit_settings_event_webhook_endpoint_heading">
                <strong class="aipkit_settings_event_webhook_endpoint_title" data-aipkit-event-webhook-endpoint-title>
                    <?php esc_html_e('Endpoint', 'gpt3-ai-content-generator'); ?>
                </strong>
                <span class="aipkit_settings_event_webhook_endpoint_index" data-aipkit-event-webhook-endpoint-number></span>
            </div>
            <div class="aipkit_settings_event_webhook_endpoint_actions">
                <label class="aipkit_settings_event_webhook_toggle" for="aipkit_event_webhook_endpoint_<?php echo esc_attr($endpoint_dom_index); ?>_enabled">
                    <span><?php esc_html_e('Enabled', 'gpt3-ai-content-generator'); ?></span>
                    <span class="aipkit_switch">
                        <input
                            type="checkbox"
                            id="aipkit_event_webhook_endpoint_<?php echo esc_attr($endpoint_dom_index); ?>_enabled"
                            name="event_webhooks[endpoints][<?php echo esc_attr($endpoint_index); ?>][enabled]"
                            value="1"
                            class="aipkit_autosave_trigger"
                            data-aipkit-endpoint-field="enabled"
                            <?php checked($endpoint_enabled); ?>
                        />
                        <span class="aipkit_switch_slider"></span>
                    </span>
                </label>
            </div>
        </div>

        <div class="aipkit_settings_event_webhook_endpoint_fields">
            <label class="aipkit_settings_event_webhook_field" for="aipkit_event_webhook_endpoint_<?php echo esc_attr($endpoint_dom_index); ?>_name">
                <span class="aipkit_settings_event_webhook_field_label"><?php esc_html_e('Name', 'gpt3-ai-content-generator'); ?></span>
                <input
                    type="text"
                    id="aipkit_event_webhook_endpoint_<?php echo esc_attr($endpoint_dom_index); ?>_name"
                    name="event_webhooks[endpoints][<?php echo esc_attr($endpoint_index); ?>][name]"
                    value="<?php echo esc_attr($endpoint_name); ?>"
                    class="aipkit_form-input aipkit_autosave_trigger"
                    data-aipkit-endpoint-field="name"
                    placeholder="<?php esc_attr_e('Slack', 'gpt3-ai-content-generator'); ?>"
                />
            </label>
            <label class="aipkit_settings_event_webhook_field" for="aipkit_event_webhook_endpoint_<?php echo esc_attr($endpoint_dom_index); ?>_url">
                <span class="aipkit_settings_event_webhook_field_label"><?php esc_html_e('Endpoint URL', 'gpt3-ai-content-generator'); ?></span>
                <input
                    type="url"
                    id="aipkit_event_webhook_endpoint_<?php echo esc_attr($endpoint_dom_index); ?>_url"
                    name="event_webhooks[endpoints][<?php echo esc_attr($endpoint_index); ?>][url]"
                    value="<?php echo esc_attr($endpoint_url); ?>"
                    class="aipkit_form-input aipkit_autosave_trigger"
                    data-aipkit-endpoint-field="url"
                    placeholder="<?php esc_attr_e('https://example.com/webhooks/aipkit', 'gpt3-ai-content-generator'); ?>"
                />
            </label>
        </div>

        <div class="aipkit_settings_event_webhook_events">
            <span class="aipkit_settings_event_webhook_field_label"><?php esc_html_e('Subscribed Events', 'gpt3-ai-content-generator'); ?></span>
            <div
                class="aipkit_popover_multiselect aipkit_settings_event_webhook_events_dropdown"
                data-aipkit-event-webhook-events-dropdown
                data-placeholder="<?php echo esc_attr__('Select events', 'gpt3-ai-content-generator'); ?>"
                data-all-label="<?php echo esc_attr__('All events selected', 'gpt3-ai-content-generator'); ?>"
                <?php /* translators: %d: Number of selected webhook events. */ ?>
                data-singular-label="<?php echo esc_attr__('%d event selected', 'gpt3-ai-content-generator'); ?>"
                <?php /* translators: %d: Number of selected webhook events. */ ?>
                data-plural-label="<?php echo esc_attr__('%d events selected', 'gpt3-ai-content-generator'); ?>"
            >
                <button
                    type="button"
                    class="aipkit_popover_multiselect_btn aipkit_settings_event_webhook_events_btn"
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr($endpoint_events_panel_id); ?>"
                    data-aipkit-event-webhook-events-toggle
                >
                    <span class="aipkit_popover_multiselect_label" data-aipkit-event-webhook-events-label><?php echo esc_html($endpoint_events_label); ?></span>
                </button>
                <div
                    id="<?php echo esc_attr($endpoint_events_panel_id); ?>"
                    class="aipkit_popover_multiselect_panel aipkit_settings_event_webhook_events_panel"
                    hidden
                >
                    <div class="aipkit_settings_event_webhook_events_actions">
                        <button type="button" class="button aipkit_btn aipkit_btn-secondary" data-aipkit-event-webhook-events-select-all>
                            <?php esc_html_e('Select all', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <button type="button" class="button aipkit_btn aipkit_btn-secondary" data-aipkit-event-webhook-events-clear>
                            <?php esc_html_e('Clear', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </div>
                    <div class="aipkit_popover_multiselect_options aipkit_settings_event_webhook_group_list">
                        <?php foreach ($event_webhook_groups as $group) : ?>
                            <?php if (empty($group['events']) || !is_array($group['events'])) { continue; } ?>
                            <section class="aipkit_settings_event_webhook_group">
                                <h5 class="aipkit_settings_event_webhook_group_title"><?php echo esc_html((string) ($group['label'] ?? '')); ?></h5>
                                <div class="aipkit_settings_event_webhook_event_grid">
                                    <?php foreach ($group['events'] as $event_item) : ?>
                                        <?php
                                        $event_name = (string) ($event_item['name'] ?? '');
                                        $field_key = (string) ($event_item['field_key'] ?? '');
                                        $definition = isset($event_item['definition']) && is_array($event_item['definition'])
                                            ? $event_item['definition']
                                            : [];
                                        if ($event_name === '' || $field_key === '') {
                                            continue;
                                        }
                                        $event_checkbox_id = 'aipkit_event_webhook_endpoint_' . $endpoint_dom_index . '_event_' . sanitize_key($field_key);
                                        ?>
                                        <label class="aipkit_popover_multiselect_item aipkit_settings_event_webhook_event_option" for="<?php echo esc_attr($event_checkbox_id); ?>">
                                            <input
                                                type="checkbox"
                                                id="<?php echo esc_attr($event_checkbox_id); ?>"
                                                name="event_webhooks[endpoints][<?php echo esc_attr($endpoint_index); ?>][events][<?php echo esc_attr($field_key); ?>]"
                                                value="1"
                                                class="aipkit_autosave_trigger"
                                                data-aipkit-endpoint-field="event"
                                                data-aipkit-event-field-key="<?php echo esc_attr($field_key); ?>"
                                                <?php checked(in_array($event_name, $endpoint_events, true)); ?>
                                            />
                                            <span class="aipkit_settings_event_webhook_event_copy">
                                                <span class="aipkit_popover_multiselect_text aipkit_settings_event_webhook_event_label"><?php echo esc_html((string) ($definition['label'] ?? $event_name)); ?></span>
                                                <code class="aipkit_settings_event_webhook_event_code"><?php echo esc_html($event_name); ?></code>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </article>
    <?php
};

$render_event_webhook_issue = static function (array $issue = []): void {
    $job_uuid = sanitize_text_field((string) ($issue['job_uuid'] ?? ''));
    $event_name = sanitize_text_field((string) ($issue['event_name'] ?? ''));
    $target_summary = sanitize_text_field((string) ($issue['target_summary'] ?? __('Webhook endpoint', 'gpt3-ai-content-generator')));
    $error_message = sanitize_text_field((string) (($issue['error_message'] ?? '') ?: __('Webhook delivery failed.', 'gpt3-ai-content-generator')));
    $displayed_at = sanitize_text_field((string) ($issue['displayed_at'] ?? ''));
    ?>
    <article class="aipkit_settings_app_delivery_issue" data-aipkit-event-webhook-delivery-issue data-job-uuid="<?php echo esc_attr($job_uuid); ?>">
        <div class="aipkit_settings_app_delivery_issue_header">
            <div class="aipkit_settings_app_delivery_issue_heading">
                <strong><?php echo esc_html($target_summary); ?></strong>
                <span class="aipkit_settings_app_delivery_issue_meta">
                    <?php echo esc_html($event_name); ?>
                </span>
            </div>
            <span class="aipkit_settings_app_delivery_issue_status aipkit_settings_app_delivery_issue_status--failed">
                <?php esc_html_e('Failed', 'gpt3-ai-content-generator'); ?>
            </span>
        </div>
        <p class="aipkit_settings_app_delivery_issue_message"><?php echo esc_html($error_message); ?></p>
        <div class="aipkit_settings_app_delivery_issue_footer">
            <span class="aipkit_settings_app_delivery_issue_time"><?php echo esc_html($displayed_at); ?></span>
            <div class="aipkit_settings_app_delivery_issue_actions">
                <button type="button" class="button button-secondary aipkit_btn aipkit_btn-danger" data-aipkit-clear-event-webhook-delivery-issue data-job-uuid="<?php echo esc_attr($job_uuid); ?>">
                    <span class="aipkit_btn-text"><?php esc_html_e('Clear', 'gpt3-ai-content-generator'); ?></span>
                    <span class="aipkit_spinner"></span>
                </button>
                <button type="button" class="button button-secondary aipkit_btn" data-aipkit-retry-event-webhook-delivery-issue data-job-uuid="<?php echo esc_attr($job_uuid); ?>">
                    <span class="aipkit_btn-text"><?php esc_html_e('Retry', 'gpt3-ai-content-generator'); ?></span>
                    <span class="aipkit_spinner"></span>
                </button>
            </div>
        </div>
    </article>
    <?php
};
?>

<section id="aipkit_settings_event_webhooks_section">
    <div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_event_webhooks_enabled_row">
        <label class="aipkit_form-label" for="aipkit_event_webhooks_enabled">
            <?php esc_html_e('Event Webhooks', 'gpt3-ai-content-generator'); ?>
            <span class="aipkit_form-label-helper"><?php esc_html_e('Enable outbound event webhooks.', 'gpt3-ai-content-generator'); ?></span>
        </label>
        <label class="aipkit_settings_big_checkbox" for="aipkit_event_webhooks_enabled">
            <input
                type="checkbox"
                id="aipkit_event_webhooks_enabled"
                name="event_webhooks[enabled]"
                class="aipkit_autosave_trigger"
                value="1"
                <?php checked($event_webhooks_enabled, '1'); ?>
            />
            <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                <span class="dashicons dashicons-saved"></span>
            </span>
            <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
        </label>
    </div>

    <div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_event_webhooks_secret_row" <?php if ($event_webhooks_enabled !== '1') : ?>hidden<?php endif; ?>>
        <label class="aipkit_form-label" for="aipkit_event_webhooks_signing_secret">
            <?php esc_html_e('Signing Secret', 'gpt3-ai-content-generator'); ?>
            <span class="aipkit_form-label-helper"><?php esc_html_e('Sign outgoing webhook requests.', 'gpt3-ai-content-generator'); ?></span>
        </label>
        <input
            type="text"
            id="aipkit_event_webhooks_signing_secret"
            name="event_webhooks[signing_secret]"
            class="aipkit_form-input aipkit_autosave_trigger"
            value="<?php echo esc_attr($event_webhook_signing_secret); ?>"
            placeholder="<?php esc_attr_e('Enter a shared secret', 'gpt3-ai-content-generator'); ?>"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
        />
    </div>

    <div class="aipkit_form-group aipkit_settings_simple_row aipkit_settings_simple_row--event-webhooks" id="aipkit_settings_event_webhooks_endpoints_row" <?php if ($event_webhooks_enabled !== '1') : ?>hidden<?php endif; ?>>
        <div class="aipkit_form-label">
            <?php esc_html_e('Endpoints', 'gpt3-ai-content-generator'); ?>
            <span class="aipkit_form-label-helper"><?php esc_html_e('Choose events for each endpoint.', 'gpt3-ai-content-generator'); ?></span>
        </div>
        <div class="aipkit_settings_event_webhooks_main">
            <div class="aipkit_settings_event_webhooks_toolbar">
                <button type="button" class="button button-secondary aipkit_btn" id="aipkit_add_event_webhook_endpoint_btn">
                    <?php esc_html_e('Add Endpoint', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>

            <div class="aipkit_settings_event_webhooks_endpoint_list<?php echo empty($event_webhook_endpoints) ? ' is-empty' : ''; ?>" id="aipkit_settings_event_webhooks_endpoint_list" data-aipkit-event-webhook-list>
                <?php if (!empty($event_webhook_endpoints)) : ?>
                    <?php foreach ($event_webhook_endpoints as $endpoint_index => $endpoint) : ?>
                        <?php $render_event_webhook_endpoint($endpoint_index, is_array($endpoint) ? $endpoint : []); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <template id="aipkit_event_webhook_endpoint_template">
        <?php $render_event_webhook_endpoint('__INDEX__'); ?>
    </template>

    <?php if (!empty($event_webhook_delivery_issues)) : ?>
        <div class="aipkit_form-group aipkit_settings_simple_row aipkit_settings_simple_row--app-delivery-issues" id="aipkit_settings_event_webhook_delivery_issues_row">
            <div class="aipkit_form-label">
                <?php esc_html_e('Webhook Delivery Issues', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Showing the 5 most recent failed webhook deliveries.', 'gpt3-ai-content-generator'); ?></span>
            </div>
            <div class="aipkit_settings_app_delivery_issues_main" id="aipkit_settings_event_webhook_delivery_issues_section">
                <div class="aipkit_settings_app_delivery_issue_list" data-aipkit-event-webhook-delivery-issue-list>
                    <?php foreach ($event_webhook_delivery_issues as $issue) : ?>
                        <?php $render_event_webhook_issue(is_array($issue) ? $issue : []); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

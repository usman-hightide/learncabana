<?php

namespace WPAICG\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Settings access layer for the Universal Event Webhooks foundation.
 */
class AIPKit_Event_Webhooks_Settings
{
    private const EVENT_FIELD_KEY_SEPARATOR = '__dot__';
    private const LEGACY_EVENT_NAME_MAP = [
        'chatbot.feedback_submitted' => 'chatbot.fb_submitted',
    ];

    /**
     * Returns the default settings structure.
     *
     * @return array<string, mixed>
     */
    public static function get_defaults(): array
    {
        return [
            'enabled' => '0',
            'signing_secret' => '',
            'endpoints' => [],
        ];
    }

    /**
     * Ensures the event webhook settings exist in aipkit_options.
     *
     * @return void
     */
    public static function init(): void
    {
        $options = get_option('aipkit_options', []);
        if (!is_array($options)) {
            $options = [];
        }

        $defaults = self::get_defaults();
        $existing = isset($options['event_webhooks']) && is_array($options['event_webhooks'])
            ? $options['event_webhooks']
            : [];

        $merged = self::merge_settings($defaults, $existing);
        if ($merged !== $existing) {
            $options['event_webhooks'] = $merged;
            update_option('aipkit_options', $options, 'no');
        }
    }

    /**
     * Returns normalized event webhook settings.
     *
     * @return array<string, mixed>
     */
    public static function get_settings(): array
    {
        self::init();

        $options = get_option('aipkit_options', []);
        if (!is_array($options)) {
            return self::get_defaults();
        }

        $settings = isset($options['event_webhooks']) && is_array($options['event_webhooks'])
            ? $options['event_webhooks']
            : [];

        return self::merge_settings(self::get_defaults(), $settings);
    }

    /**
     * Returns active endpoints subscribed to the given event.
     *
     * @param string $event_name
     * @return array<int, array<string, mixed>>
     */
    public static function get_active_endpoints_for_event(string $event_name): array
    {
        $settings = self::get_settings();
        if (($settings['enabled'] ?? '0') !== '1') {
            return [];
        }

        $endpoints = isset($settings['endpoints']) && is_array($settings['endpoints'])
            ? $settings['endpoints']
            : [];

        $matched = [];
        foreach ($endpoints as $endpoint) {
            if (!is_array($endpoint)) {
                continue;
            }

            $is_enabled = isset($endpoint['enabled']) && (string) $endpoint['enabled'] === '1';
            $url = isset($endpoint['url']) ? esc_url_raw((string) $endpoint['url']) : '';
            $events = isset($endpoint['events']) && is_array($endpoint['events']) ? $endpoint['events'] : [];

            if (!$is_enabled || $url === '') {
                continue;
            }

            $normalized_events = array_values(array_filter(array_map(
                static function ($value): string {
                    return self::normalize_event_name((string) $value);
                },
                $events
            )));

            if (in_array($event_name, $normalized_events, true)) {
                $matched[] = [
                    'id' => sanitize_key((string) ($endpoint['id'] ?? '')),
                    'name' => sanitize_text_field((string) ($endpoint['name'] ?? '')),
                    'url' => $url,
                    'events' => $normalized_events,
                ];
            }
        }

        return $matched;
    }

    /**
     * Returns the HTML-safe event field key for a canonical event name.
     *
     * @param string $event_name
     * @return string
     */
    public static function encode_event_field_key(string $event_name): string
    {
        $normalized_event_name = strtolower(trim($event_name));
        return sanitize_key(str_replace('.', self::EVENT_FIELD_KEY_SEPARATOR, $normalized_event_name));
    }

    /**
     * Returns the mapping used by endpoint event checkboxes.
     *
     * @return array<string, string>
     */
    public static function get_event_field_key_map(): array
    {
        $map = [];
        foreach (array_keys(AIPKit_Event_Registry::get_definitions()) as $event_name) {
            $map[self::encode_event_field_key($event_name)] = $event_name;
        }

        return $map;
    }

    /**
     * Sanitizes raw event webhook settings input.
     *
     * @param mixed $raw_settings
     * @return array<string, mixed>
     */
    public static function sanitize_settings_input($raw_settings): array
    {
        if (!is_array($raw_settings)) {
            return self::get_defaults();
        }

        $settings = self::get_defaults();
        $settings['enabled'] = isset($raw_settings['enabled']) && (string) $raw_settings['enabled'] === '1' ? '1' : '0';
        $settings['signing_secret'] = sanitize_text_field((string) ($raw_settings['signing_secret'] ?? ''));

        $event_field_key_map = self::get_event_field_key_map();
        $raw_endpoints = isset($raw_settings['endpoints']) && is_array($raw_settings['endpoints'])
            ? $raw_settings['endpoints']
            : [];

        foreach ($raw_endpoints as $endpoint) {
            $normalized_endpoint = self::sanitize_endpoint($endpoint, $event_field_key_map);
            if ($normalized_endpoint !== null) {
                $settings['endpoints'][] = $normalized_endpoint;
            }
        }

        return $settings;
    }

    /**
     * Saves sanitized event webhook settings into aipkit_options.
     *
     * @param mixed $raw_settings
     * @return array<string, mixed>
     */
    public static function save_settings($raw_settings): array
    {
        $sanitized = self::sanitize_settings_input($raw_settings);

        $options = get_option('aipkit_options', []);
        if (!is_array($options)) {
            $options = [];
        }

        $options['event_webhooks'] = $sanitized;
        update_option('aipkit_options', $options, 'no');

        return $sanitized;
    }

    /**
     * Merges settings arrays while preserving normalized endpoint lists.
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private static function merge_settings(array $defaults, array $existing): array
    {
        $merged = array_merge($defaults, $existing);
        $merged['enabled'] = isset($merged['enabled']) && (string) $merged['enabled'] === '1' ? '1' : '0';
        $merged['signing_secret'] = sanitize_text_field((string) ($merged['signing_secret'] ?? ''));
        $merged['endpoints'] = isset($merged['endpoints']) && is_array($merged['endpoints'])
            ? array_values(array_filter(array_map(
                static function ($endpoint): ?array {
                    if (!is_array($endpoint)) {
                        return null;
                    }

                    return [
                        'id' => sanitize_key((string) ($endpoint['id'] ?? '')),
                        'name' => sanitize_text_field((string) ($endpoint['name'] ?? '')),
                        'enabled' => isset($endpoint['enabled']) && (string) $endpoint['enabled'] === '1' ? '1' : '0',
                        'url' => esc_url_raw((string) ($endpoint['url'] ?? '')),
                        'events' => isset($endpoint['events']) && is_array($endpoint['events'])
                            ? array_values(array_unique(array_filter(array_map(
                                static function ($event_name): string {
                                    $normalized_event_name = self::normalize_event_name((string) $event_name);
                                    if ($normalized_event_name === '' || !AIPKit_Event_Registry::has_event($normalized_event_name)) {
                                        return '';
                                    }

                                    return $normalized_event_name;
                                },
                                $endpoint['events']
                            ))))
                            : [],
                    ];
                },
                $merged['endpoints']
            )))
            : [];

        return $merged;
    }

    /**
     * Sanitizes one endpoint config row.
     *
     * @param mixed $endpoint
     * @param array<string, string> $event_field_key_map
     * @return array<string, mixed>|null
     */
    private static function sanitize_endpoint($endpoint, array $event_field_key_map): ?array
    {
        if (!is_array($endpoint)) {
            return null;
        }

        $name = sanitize_text_field((string) ($endpoint['name'] ?? ''));
        $url = esc_url_raw((string) ($endpoint['url'] ?? ''));
        $enabled = isset($endpoint['enabled']) && (string) $endpoint['enabled'] === '1' ? '1' : '0';
        $events = [];

        $raw_events = isset($endpoint['events']) && is_array($endpoint['events']) ? $endpoint['events'] : [];
        foreach ($event_field_key_map as $field_key => $event_name) {
            if (isset($raw_events[$field_key]) && (string) $raw_events[$field_key] === '1' && AIPKit_Event_Registry::has_event($event_name)) {
                $events[] = $event_name;
            }
        }

        $has_meaningful_data = $name !== '' || $url !== '' || $enabled === '1' || !empty($events);
        if (!$has_meaningful_data) {
            return null;
        }

        $endpoint_id = sanitize_key((string) ($endpoint['id'] ?? ''));
        if ($endpoint_id === '') {
            $endpoint_id = 'endpoint_' . sanitize_key(wp_generate_uuid4());
        }

        return [
            'id' => $endpoint_id,
            'name' => $name,
            'enabled' => $enabled,
            'url' => $url,
            'events' => array_values(array_unique($events)),
        ];
    }

    /**
     * Normalizes canonical and legacy event names to the current supported key.
     *
     * @param string $event_name
     * @return string
     */
    private static function normalize_event_name(string $event_name): string
    {
        $normalized_event_name = sanitize_text_field(trim($event_name));
        if ($normalized_event_name === '') {
            return '';
        }

        return self::LEGACY_EVENT_NAME_MAP[$normalized_event_name] ?? $normalized_event_name;
    }
}

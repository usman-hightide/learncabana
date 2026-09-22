<?php

/**
 * AIPKit Chatbot Module - Admin View
 *
 * Layout-only rebuild based on the provided reference UI.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local view variables here do not create public globals.
use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\DefaultBotSetup;
use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\Chat\Frontend\Shortcode;
use WPAICG\Chat\Utils\AIPKit_SVG_Icons;
use WPAICG\aipkit_dashboard;
// Required for addon status checks
use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
// Instantiate the storage classes
$bot_storage = new BotStorage();
$default_setup = new DefaultBotSetup();
DefaultBotSetup::ensure_default_chatbot();
// Fetch all bot settings up front so switching can be state-driven client-side.
$all_chatbots = [];
$all_chatbot_settings_by_id = [];
$all_chatbots_with_settings = $bot_storage->get_chatbots_with_settings();
if ( !empty( $all_chatbots_with_settings ) ) {
    foreach ( $all_chatbots_with_settings as $bot_entry_with_settings ) {
        $bot_post = $bot_entry_with_settings['post'] ?? null;
        if ( !$bot_post instanceof \WP_Post ) {
            continue;
        }
        $all_chatbots[] = $bot_post;
        $all_chatbot_settings_by_id[$bot_post->ID] = ( is_array( $bot_entry_with_settings['settings'] ?? null ) ? $bot_entry_with_settings['settings'] : [] );
    }
}
// These variables are defined by the AJAX loader and sanitized there.
$force_active_bot_id = ( isset( $force_active_bot_id ) ? intval( $force_active_bot_id ) : 0 );
$force_active_tab = ( isset( $force_active_tab ) ? sanitize_key( $force_active_tab ) : '' );
// Get the ID of the default bot
$default_bot_id = $default_setup->get_default_bot_id();
// Separate the default bot and sort the others alphabetically
$default_bot_post = null;
$other_bots_posts = [];
if ( !empty( $all_chatbots ) ) {
    foreach ( $all_chatbots as $bot_post ) {
        if ( $bot_post->ID === $default_bot_id ) {
            $default_bot_post = $bot_post;
        } else {
            $other_bots_posts[] = $bot_post;
        }
    }
    usort( $other_bots_posts, function ( $a, $b ) {
        return strcmp( $a->post_title, $b->post_title );
    } );
}
// Combine all bots into one list for the dropdown
$all_bots_ordered_entries = [];
if ( $default_bot_post ) {
    $all_bots_ordered_entries[] = [
        'post' => $default_bot_post,
    ];
}
foreach ( $other_bots_posts as $bot_post ) {
    $all_bots_ordered_entries[] = [
        'post' => $bot_post,
    ];
}
// Determine the initial active bot
$initial_active_bot_id = 0;
if ( $force_active_tab === 'create' ) {
    $initial_active_bot_id = 0;
} elseif ( $force_active_bot_id > 0 ) {
    $initial_active_bot_id = $force_active_bot_id;
} elseif ( $default_bot_post ) {
    $initial_active_bot_id = $default_bot_post->ID;
} elseif ( !empty( $other_bots_posts ) ) {
    $initial_active_bot_id = $other_bots_posts[0]->ID;
}
// Find the active bot post
$active_bot_post = null;
if ( $initial_active_bot_id ) {
    foreach ( $all_bots_ordered_entries as $bot_entry ) {
        if ( $bot_entry['post']->ID === $initial_active_bot_id ) {
            $active_bot_post = $bot_entry['post'];
            break;
        }
    }
}
// If a forced/stored bot ID no longer exists, gracefully fall back.
if ( !$active_bot_post ) {
    if ( $default_bot_post instanceof \WP_Post ) {
        $active_bot_post = $default_bot_post;
        $initial_active_bot_id = (int) $default_bot_post->ID;
    } elseif ( !empty( $other_bots_posts ) && $other_bots_posts[0] instanceof \WP_Post ) {
        $active_bot_post = $other_bots_posts[0];
        $initial_active_bot_id = (int) $other_bots_posts[0]->ID;
    } else {
        $initial_active_bot_id = 0;
    }
}
// Always initialize a bot ID variable for downstream partials/flyouts.
$bot_id = (int) $initial_active_bot_id;
$is_pro_plan = class_exists( '\\WPAICG\\aipkit_dashboard' ) && aipkit_dashboard::is_pro_plan();
$build_chatbot_embed_code = static function ( int $bot_id ) use($is_pro_plan) : string {
    if ( !$is_pro_plan || $bot_id <= 0 ) {
        return '';
    }
    if ( !function_exists( 'wpaicg_gacg_fs' ) ) {
        return '';
    }
    return '';
};
$build_inline_bot_switch_state_payload = static function (
    int $bot_id,
    string $bot_name,
    array $settings,
    int $default_bot_id
) use($build_chatbot_embed_code, $is_pro_plan) : array {
    $popup_enabled = isset( $settings['popup_enabled'] ) && (string) $settings['popup_enabled'] === '1';
    $raw_deploy_mode = ( isset( $settings['deploy_mode'] ) ? sanitize_key( (string) $settings['deploy_mode'] ) : '' );
    $deploy_mode = ( in_array( $raw_deploy_mode, ['inline', 'popup', 'external'], true ) ? $raw_deploy_mode : (( $popup_enabled ? 'popup' : 'inline' )) );
    if ( !$is_pro_plan && $deploy_mode === 'external' ) {
        $deploy_mode = ( $popup_enabled ? 'popup' : 'inline' );
    }
    $conversation_starters = $settings['conversation_starters'] ?? [];
    if ( !is_array( $conversation_starters ) ) {
        $conversation_starters = ( is_scalar( $conversation_starters ) && (string) $conversation_starters !== '' ? [(string) $conversation_starters] : [] );
    }
    $triggers_json = $settings['triggers_json'] ?? '[]';
    if ( is_array( $triggers_json ) ) {
        $triggers_json = ( wp_json_encode( $triggers_json ) ?: '[]' );
    } elseif ( is_string( $triggers_json ) ) {
        $triggers_json = ( trim( $triggers_json ) !== '' ? trim( $triggers_json ) : '[]' );
    } else {
        $triggers_json = '[]';
    }
    $settings['triggers_json'] = $triggers_json;
    $settings['deploy_mode'] = $deploy_mode;
    if ( !$is_pro_plan ) {
        unset($settings['embed_allowed_domains']);
    }
    return [
        'bot_id'                     => $bot_id,
        'bot_name'                   => $bot_name,
        'is_default'                 => $bot_id === $default_bot_id,
        'settings'                   => $settings,
        'deploy_mode'                => $deploy_mode,
        'shortcode'                  => sprintf( '[aipkit_chatbot id=%d]', $bot_id ),
        'embed_code'                 => $build_chatbot_embed_code( $bot_id ),
        'embed_allowed_domains'      => ( $is_pro_plan && isset( $settings['embed_allowed_domains'] ) ? (string) $settings['embed_allowed_domains'] : '' ),
        'conversation_starters_text' => implode( "\n", array_map( 'strval', $conversation_starters ) ),
        'triggers_json'              => $triggers_json,
        'connected_apps'             => ( class_exists( '\\WPAICG\\Lib\\Integrations\\Recipes\\AIPKit_Stored_Recipes' ) && method_exists( '\\WPAICG\\Lib\\Integrations\\Recipes\\AIPKit_Stored_Recipes', 'get_chatbot_connected_apps_payload' ) ? \WPAICG\Lib\Integrations\Recipes\AIPKit_Stored_Recipes::get_chatbot_connected_apps_payload( $bot_id ) : [
            'count'   => 0,
            'summary' => '',
            'recipes' => [],
        ] ),
    ];
};
$inline_bot_switch_states = [];
$inline_bot_switch_order = [];
foreach ( $all_bots_ordered_entries as $bot_entry_for_state ) {
    $bot_post_for_state = $bot_entry_for_state['post'] ?? null;
    if ( !$bot_post_for_state instanceof \WP_Post ) {
        continue;
    }
    $bot_id_for_state = (int) $bot_post_for_state->ID;
    if ( $bot_id_for_state <= 0 ) {
        continue;
    }
    $inline_bot_switch_order[] = $bot_id_for_state;
    $inline_bot_switch_states[(string) $bot_id_for_state] = $build_inline_bot_switch_state_payload(
        $bot_id_for_state,
        (string) $bot_post_for_state->post_title,
        $all_chatbot_settings_by_id[$bot_id_for_state] ?? [],
        (int) $default_bot_id
    );
}
$inline_bot_switch_payload = [
    'bots'           => $inline_bot_switch_states,
    'order'          => $inline_bot_switch_order,
    'default_bot_id' => (int) $default_bot_id,
];
$active_bot_settings = ( $active_bot_post instanceof \WP_Post && isset( $all_chatbot_settings_by_id[$active_bot_post->ID] ) ? $all_chatbot_settings_by_id[$active_bot_post->ID] : [] );
$active_bot_instructions = $active_bot_settings['instructions'] ?? '';
$saved_theme = $active_bot_settings['theme'] ?? 'dark';
$saved_greeting = $active_bot_settings['greeting'] ?? '';
$saved_subgreeting = $active_bot_settings['subgreeting'] ?? '';
$aipkit_hide_custom_theme = false;
$available_themes = [
    'light'   => __( 'Light', 'gpt3-ai-content-generator' ),
    'dark'    => __( 'Dark', 'gpt3-ai-content-generator' ),
    'chatgpt' => __( 'ChatGPT', 'gpt3-ai-content-generator' ),
];
if ( !$aipkit_hide_custom_theme || $saved_theme === 'custom' ) {
    $available_themes['custom'] = __( 'Custom', 'gpt3-ai-content-generator' );
}
$custom_theme_presets = ( class_exists( BotSettingsManager::class ) ? BotSettingsManager::get_custom_theme_presets() : [] );
$selected_theme_preset_key = '';
$selected_theme_preset_label = '';
if ( $saved_theme === 'custom' && !empty( $custom_theme_presets ) ) {
    $preset_label_map = [];
    $preset_color_map = [];
    foreach ( $custom_theme_presets as $preset ) {
        if ( !is_array( $preset ) ) {
            continue;
        }
        $preset_key = ( isset( $preset['key'] ) ? sanitize_key( (string) $preset['key'] ) : '' );
        if ( $preset_key === '' ) {
            continue;
        }
        $preset_label_map[$preset_key] = ( isset( $preset['label'] ) ? (string) $preset['label'] : '' );
        $preset_color_map[$preset_key] = [
            'primary'   => ( isset( $preset['primary'] ) ? strtolower( trim( (string) $preset['primary'] ) ) : '' ),
            'secondary' => ( isset( $preset['secondary'] ) ? strtolower( trim( (string) $preset['secondary'] ) ) : '' ),
        ];
    }
    $stored_theme_preset_key = ( isset( $active_bot_settings['theme_preset_key'] ) ? sanitize_key( (string) $active_bot_settings['theme_preset_key'] ) : '' );
    if ( $stored_theme_preset_key !== '' && isset( $preset_label_map[$stored_theme_preset_key] ) ) {
        $selected_theme_preset_key = $stored_theme_preset_key;
        $selected_theme_preset_label = $preset_label_map[$stored_theme_preset_key];
    } else {
        // Backward compatibility for bots saved before explicit preset keys.
        $saved_custom_theme_settings = ( isset( $active_bot_settings['custom_theme_settings'] ) && is_array( $active_bot_settings['custom_theme_settings'] ) ? $active_bot_settings['custom_theme_settings'] : [] );
        $saved_custom_primary = ( isset( $saved_custom_theme_settings['primary_color'] ) ? strtolower( trim( (string) $saved_custom_theme_settings['primary_color'] ) ) : '' );
        $saved_custom_secondary = ( isset( $saved_custom_theme_settings['secondary_color'] ) ? strtolower( trim( (string) $saved_custom_theme_settings['secondary_color'] ) ) : '' );
        if ( $saved_custom_primary !== '' && $saved_custom_secondary !== '' ) {
            foreach ( $preset_color_map as $preset_key => $preset_colors ) {
                if ( $preset_colors['primary'] !== '' && $preset_colors['secondary'] !== '' && $saved_custom_primary === $preset_colors['primary'] && $saved_custom_secondary === $preset_colors['secondary'] ) {
                    $selected_theme_preset_key = $preset_key;
                    $selected_theme_preset_label = $preset_label_map[$preset_key] ?? '';
                    break;
                }
            }
        }
    }
}
$popup_enabled = $active_bot_settings['popup_enabled'] ?? '0';
$popup_enabled = ( in_array( $popup_enabled, ['0', '1'], true ) ? $popup_enabled : '0' );
$site_wide_enabled = $active_bot_settings['site_wide_enabled'] ?? '0';
$site_wide_enabled = ( in_array( $site_wide_enabled, ['0', '1'], true ) ? $site_wide_enabled : '0' );
$raw_deploy_mode = ( isset( $active_bot_settings['deploy_mode'] ) ? sanitize_key( (string) $active_bot_settings['deploy_mode'] ) : '' );
$deploy_mode = ( in_array( $raw_deploy_mode, ['inline', 'popup', 'external'], true ) ? $raw_deploy_mode : (( $popup_enabled === '1' ? 'popup' : 'inline' )) );
$deploy_popup_scope = ( $site_wide_enabled === '1' ? 'sitewide' : 'page' );
$shortcode_text = ( $active_bot_post ? sprintf( '[aipkit_chatbot id=%d]', absint( $initial_active_bot_id ) ) : '' );
$embed_anywhere_active = $is_pro_plan;
$embed_allowed_domains = ( $is_pro_plan ? $active_bot_settings['embed_allowed_domains'] ?? '' : '' );
$embed_code = $build_chatbot_embed_code( absint( $initial_active_bot_id ) );
$embed_docs_url = 'https://docs.aipower.org/chatbots#external-embed';
$consent_feature_available = $is_pro_plan && class_exists( '\\WPAICG\\Lib\\Addons\\AIPKit_Consent_Compliance' );
$triggers_available = $is_pro_plan;
$pricing_url = admin_url( 'admin.php?page=wpaicg-pricing' );
$apps_logo_base_url = ( defined( 'WPAICG_PLUGIN_URL' ) ? WPAICG_PLUGIN_URL . 'admin/images/apps/' : '' );
$connected_apps_supported_destinations = [
    [
        'name'     => __( 'Slack', 'gpt3-ai-content-generator' ),
        'logo_url' => $apps_logo_base_url . 'slack.svg',
    ],
    [
        'name'     => __( 'HubSpot', 'gpt3-ai-content-generator' ),
        'logo_url' => $apps_logo_base_url . 'hubspot.svg',
    ],
    [
        'name'     => __( 'Notion', 'gpt3-ai-content-generator' ),
        'logo_url' => $apps_logo_base_url . 'notion.svg',
    ],
    [
        'name'     => __( 'Pipedrive', 'gpt3-ai-content-generator' ),
        'logo_url' => $apps_logo_base_url . 'pipedrive.svg',
    ],
    [
        'name'     => __( 'Zapier', 'gpt3-ai-content-generator' ),
        'logo_url' => $apps_logo_base_url . 'zapier.svg',
    ],
    [
        'name'     => __( 'Make', 'gpt3-ai-content-generator' ),
        'logo_url' => $apps_logo_base_url . 'make.svg',
    ],
    [
        'name'     => __( 'n8n', 'gpt3-ai-content-generator' ),
        'logo_url' => $apps_logo_base_url . 'n8n.svg',
    ]
];
$connected_apps_manage_url = admin_url( 'admin.php?page=wpaicg&aipkit_module=settings&aipkit_settings_page=apps' );
$connected_apps_store_class = '\\WPAICG\\Lib\\Integrations\\Recipes\\AIPKit_Stored_Recipes';
$active_chatbot_connected_apps = ( $initial_active_bot_id > 0 && class_exists( $connected_apps_store_class ) && method_exists( $connected_apps_store_class, 'get_chatbot_connected_apps_payload' ) ? $connected_apps_store_class::get_chatbot_connected_apps_payload( $initial_active_bot_id ) : [
    'count'   => 0,
    'summary' => '',
    'recipes' => [],
] );
$connected_apps_summary_fallback = '';
$connected_apps_summary_text = ( $is_pro_plan ? sanitize_text_field( (string) ($active_chatbot_connected_apps['summary'] ?? '') ) : $connected_apps_summary_fallback );
$render_chatbot_connected_apps_cards = static function ( array $connected_apps_payload ) : void {
    $recipes = ( isset( $connected_apps_payload['recipes'] ) && is_array( $connected_apps_payload['recipes'] ) ? $connected_apps_payload['recipes'] : [] );
    foreach ( $recipes as $connected_recipe ) {
        if ( !is_array( $connected_recipe ) ) {
            continue;
        }
        $recipe_name = sanitize_text_field( (string) ($connected_recipe['name'] ?? __( 'Untitled Recipe', 'gpt3-ai-content-generator' )) );
        $connection_label = sanitize_text_field( (string) ($connected_recipe['connection_label'] ?? __( 'No connection', 'gpt3-ai-content-generator' )) );
        $event_label = sanitize_text_field( (string) ($connected_recipe['event_label'] ?? __( 'No event', 'gpt3-ai-content-generator' )) );
        $action_label = sanitize_text_field( (string) ($connected_recipe['action_label'] ?? __( 'No action', 'gpt3-ai-content-generator' )) );
        $status_key = sanitize_key( (string) ($connected_recipe['status_key'] ?? 'warning') );
        if ( !in_array( $status_key, [
            'ready',
            'warning',
            'error',
            'reauth_required'
        ], true ) ) {
            $status_key = 'warning';
        }
        $status_label = sanitize_text_field( (string) ($connected_recipe['status_label'] ?? __( 'Warning', 'gpt3-ai-content-generator' )) );
        $scope_label = sanitize_text_field( (string) ($connected_recipe['scope_label'] ?? __( 'All Chatbots', 'gpt3-ai-content-generator' )) );
        $validation_summary = sanitize_text_field( (string) ($connected_recipe['validation_summary'] ?? '') );
        $is_enabled = !empty( $connected_recipe['is_enabled'] );
        ?>
        <article class="aipkit_chatbot_connected_apps_recipe">
            <div class="aipkit_chatbot_connected_apps_recipe_header">
                <strong class="aipkit_chatbot_connected_apps_recipe_title"><?php 
        echo esc_html( $recipe_name );
        ?></strong>
                <div class="aipkit_chatbot_connected_apps_recipe_flags">
                    <span class="aipkit_settings_recipe_status aipkit_settings_recipe_status--<?php 
        echo esc_attr( $status_key );
        ?>">
                        <?php 
        echo esc_html( $status_label );
        ?>
                    </span>
                    <span class="aipkit_settings_recipe_enabled_flag aipkit_settings_recipe_enabled_flag--<?php 
        echo ( $is_enabled ? 'enabled' : 'disabled' );
        ?>">
                        <?php 
        echo ( $is_enabled ? esc_html__( 'Enabled', 'gpt3-ai-content-generator' ) : esc_html__( 'Disabled', 'gpt3-ai-content-generator' ) );
        ?>
                    </span>
                </div>
            </div>
            <p class="aipkit_chatbot_connected_apps_recipe_summary">
                <?php 
        echo esc_html( $connection_label . ' | ' . $event_label . ' | ' . $action_label );
        ?>
            </p>
            <div class="aipkit_chatbot_connected_apps_recipe_meta">
                <span class="aipkit_chatbot_connected_apps_recipe_scope"><?php 
        echo esc_html( $scope_label );
        ?></span>
            </div>
            <p class="aipkit_chatbot_connected_apps_recipe_validation aipkit_chatbot_connected_apps_recipe_validation--<?php 
        echo esc_attr( $status_key );
        ?>">
                <?php 
        echo esc_html( $validation_summary );
        ?>
            </p>
        </article>
        <?php 
    }
};
$render_chatbot_accordion_header = static function ( array $args ) : void {
    $icon_class = trim( (string) ($args['icon'] ?? '') );
    $title = (string) ($args['title'] ?? '');
    $header_classes = trim( 'aipkit_accordion_header ' . (string) ($args['header_class'] ?? '') );
    $button_attrs = [];
    foreach ( [
        'sheet_title'       => 'data-sheet-title',
        'sheet_description' => 'data-sheet-description',
        'sheet_content'     => 'data-sheet-content',
    ] as $arg_key => $attr_name ) {
        if ( isset( $args[$arg_key] ) && (string) $args[$arg_key] !== '' ) {
            $button_attrs[$attr_name] = (string) $args[$arg_key];
        }
    }
    $hint_attr = ( isset( $args['hint_attr'] ) ? (string) $args['hint_attr'] : '' );
    $hint_attr = ( preg_match( '/^data-[a-z0-9_-]+$/', $hint_attr ) ? $hint_attr : '' );
    $hint_text = (string) ($args['hint_text'] ?? '');
    $hint_default = (string) ($args['hint_default'] ?? '');
    $badge_label = (string) ($args['badge_label'] ?? '');
    ?>
    <button type="button" class="<?php 
    echo esc_attr( $header_classes );
    ?>" aria-expanded="false"<?php 
    foreach ( $button_attrs as $attr_name => $attr_value ) {
        ?> <?php 
        echo esc_attr( $attr_name );
        ?>="<?php 
        echo esc_attr( $attr_value );
        ?>"<?php 
    }
    ?>>
        <span class="aipkit_accordion_header_icon dashicons <?php 
    echo esc_attr( $icon_class );
    ?>" aria-hidden="true"></span>
        <span class="aipkit_accordion_header_main">
            <span class="aipkit_accordion_header_title_group">
                <span class="aipkit_accordion_header_text"><?php 
    echo esc_html( $title );
    ?></span>
                <?php 
    if ( $badge_label !== '' ) {
        ?>
                    <span class="aipkit_chatbot_feature_badge"><?php 
        echo esc_html( $badge_label );
        ?></span>
                <?php 
    }
    ?>
            </span>
            <?php 
    if ( $hint_attr !== '' ) {
        ?>
                <span
                    class="aipkit_accordion_header_hint"
                    <?php 
        echo esc_attr( $hint_attr );
        ?>
                    data-default-summary="<?php 
        echo esc_attr( $hint_default );
        ?>"
                    title="<?php 
        echo esc_attr( $hint_text );
        ?>"
                ><?php 
        echo esc_html( $hint_text );
        ?></span>
            <?php 
    } else {
        ?>
                <span class="aipkit_accordion_header_hint" aria-hidden="true"></span>
            <?php 
    }
    ?>
        </span>
        <span class="aipkit_accordion_chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
    </button>
    <?php 
};
$post_types_args = [
    'public' => true,
];
$all_selectable_post_types = get_post_types( $post_types_args, 'objects' );
$all_selectable_post_types = array_filter( $all_selectable_post_types, function ( $post_type_obj ) {
    return $post_type_obj->name !== 'attachment';
} );
$popup_position = $active_bot_settings['popup_position'] ?? 'bottom-right';
$popup_position = ( in_array( $popup_position, [
    'bottom-right',
    'bottom-left',
    'top-right',
    'top-left'
], true ) ? $popup_position : 'bottom-right' );
$popup_delay = ( isset( $active_bot_settings['popup_delay'] ) ? absint( $active_bot_settings['popup_delay'] ) : BotSettingsManager::DEFAULT_POPUP_DELAY );
$popup_icon_type = $active_bot_settings['popup_icon_type'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_TYPE;
$popup_icon_type = ( in_array( $popup_icon_type, ['default', 'custom'], true ) ? $popup_icon_type : BotSettingsManager::DEFAULT_POPUP_ICON_TYPE );
$popup_icon_style = $active_bot_settings['popup_icon_style'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_STYLE;
$popup_icon_style = ( in_array( $popup_icon_style, ['circle', 'square', 'none'], true ) ? $popup_icon_style : BotSettingsManager::DEFAULT_POPUP_ICON_STYLE );
$popup_icon_value = $active_bot_settings['popup_icon_value'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_VALUE;
$popup_icon_size = $active_bot_settings['popup_icon_size'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_SIZE;
$allowed_icon_sizes = [
    'small',
    'medium',
    'large',
    'xlarge'
];
$popup_icon_size = ( in_array( $popup_icon_size, $allowed_icon_sizes, true ) ? $popup_icon_size : BotSettingsManager::DEFAULT_POPUP_ICON_SIZE );
$allowed_default_icons = [
    'chat-bubble',
    'spark',
    'openai',
    'plus',
    'question-mark'
];
if ( $popup_icon_type === 'default' && !in_array( $popup_icon_value, $allowed_default_icons, true ) ) {
    $popup_icon_value = BotSettingsManager::DEFAULT_POPUP_ICON_VALUE;
}
$saved_header_avatar_url = $active_bot_settings['header_avatar_url'] ?? '';
$saved_header_avatar_type = $active_bot_settings['header_avatar_type'] ?? BotSettingsManager::DEFAULT_HEADER_AVATAR_TYPE;
if ( !in_array( $saved_header_avatar_type, ['default', 'custom'], true ) ) {
    $saved_header_avatar_type = ( $saved_header_avatar_url !== '' ? 'custom' : BotSettingsManager::DEFAULT_HEADER_AVATAR_TYPE );
}
$saved_header_avatar_value = $active_bot_settings['header_avatar_value'] ?? BotSettingsManager::DEFAULT_HEADER_AVATAR_VALUE;
if ( $saved_header_avatar_type === 'custom' ) {
    if ( $saved_header_avatar_url === '' && !empty( $saved_header_avatar_value ) ) {
        $saved_header_avatar_url = $saved_header_avatar_value;
    }
} else {
    if ( !in_array( $saved_header_avatar_value, $allowed_default_icons, true ) ) {
        $saved_header_avatar_value = BotSettingsManager::DEFAULT_HEADER_AVATAR_VALUE;
    }
    $saved_header_avatar_url = '';
}
$saved_header_online_text = $active_bot_settings['header_online_text'] ?? __( 'Online', 'gpt3-ai-content-generator' );
$popup_label_enabled = $active_bot_settings['popup_label_enabled'] ?? BotSettingsManager::DEFAULT_POPUP_LABEL_ENABLED;
$popup_label_enabled = ( in_array( $popup_label_enabled, ['0', '1'], true ) ? $popup_label_enabled : BotSettingsManager::DEFAULT_POPUP_LABEL_ENABLED );
$popup_label_text = trim( (string) ($active_bot_settings['popup_label_text'] ?? '') );
if ( $popup_label_text === '' ) {
    $popup_label_text = BotSettingsManager::DEFAULT_POPUP_LABEL_TEXT;
}
$popup_label_mode = $active_bot_settings['popup_label_mode'] ?? BotSettingsManager::DEFAULT_POPUP_LABEL_MODE;
$popup_label_mode = ( in_array( $popup_label_mode, [
    'on_delay',
    'until_open',
    'until_dismissed',
    'always'
], true ) ? $popup_label_mode : BotSettingsManager::DEFAULT_POPUP_LABEL_MODE );
$popup_label_delay_seconds = ( isset( $active_bot_settings['popup_label_delay_seconds'] ) ? absint( $active_bot_settings['popup_label_delay_seconds'] ) : BotSettingsManager::DEFAULT_POPUP_LABEL_DELAY_SECONDS );
$popup_label_auto_hide_seconds = ( isset( $active_bot_settings['popup_label_auto_hide_seconds'] ) ? absint( $active_bot_settings['popup_label_auto_hide_seconds'] ) : BotSettingsManager::DEFAULT_POPUP_LABEL_AUTO_HIDE_SECONDS );
$popup_label_dismissible = $active_bot_settings['popup_label_dismissible'] ?? BotSettingsManager::DEFAULT_POPUP_LABEL_DISMISSIBLE;
$popup_label_dismissible = ( in_array( $popup_label_dismissible, ['0', '1'], true ) ? $popup_label_dismissible : BotSettingsManager::DEFAULT_POPUP_LABEL_DISMISSIBLE );
$popup_label_frequency = $active_bot_settings['popup_label_frequency'] ?? BotSettingsManager::DEFAULT_POPUP_LABEL_FREQUENCY;
$popup_label_frequency = ( in_array( $popup_label_frequency, ['once_per_visitor', 'once_per_session', 'always'], true ) ? $popup_label_frequency : BotSettingsManager::DEFAULT_POPUP_LABEL_FREQUENCY );
$popup_label_show_on_mobile = $active_bot_settings['popup_label_show_on_mobile'] ?? BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_MOBILE;
$popup_label_show_on_mobile = ( in_array( $popup_label_show_on_mobile, ['0', '1'], true ) ? $popup_label_show_on_mobile : BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_MOBILE );
$popup_label_show_on_desktop = $active_bot_settings['popup_label_show_on_desktop'] ?? BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_DESKTOP;
$popup_label_show_on_desktop = ( in_array( $popup_label_show_on_desktop, ['0', '1'], true ) ? $popup_label_show_on_desktop : BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_DESKTOP );
$popup_label_version = $active_bot_settings['popup_label_version'] ?? '';
$popup_label_size = $active_bot_settings['popup_label_size'] ?? BotSettingsManager::DEFAULT_POPUP_LABEL_SIZE;
$popup_label_size = ( in_array( $popup_label_size, $allowed_icon_sizes, true ) ? $popup_label_size : BotSettingsManager::DEFAULT_POPUP_LABEL_SIZE );
$default_popup_icons = [];
if ( class_exists( AIPKit_SVG_Icons::class ) ) {
    $default_popup_icons = [
        'chat-bubble'   => AIPKit_SVG_Icons::get_chat_bubble_svg(),
        'spark'         => AIPKit_SVG_Icons::get_spark_svg(),
        'openai'        => AIPKit_SVG_Icons::get_openai_svg(),
        'plus'          => AIPKit_SVG_Icons::get_plus_svg(),
        'question-mark' => AIPKit_SVG_Icons::get_question_mark_svg(),
    ];
}
$popup_icons = $default_popup_icons;
// Web & Grounding settings values (used in model settings sheet).
$current_provider_for_this_bot = $active_bot_settings['provider'] ?? 'OpenAI';
$openai_web_search_enabled_val = $active_bot_settings['openai_web_search_enabled'] ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_ENABLED;
$openai_web_search_context_size_val = $active_bot_settings['openai_web_search_context_size'] ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE;
$openai_web_search_loc_type_val = $active_bot_settings['openai_web_search_loc_type'] ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_LOC_TYPE;
$openai_web_search_loc_country_val = $active_bot_settings['openai_web_search_loc_country'] ?? '';
$openai_web_search_loc_city_val = $active_bot_settings['openai_web_search_loc_city'] ?? '';
$openai_web_search_loc_region_val = $active_bot_settings['openai_web_search_loc_region'] ?? '';
$openai_web_search_loc_timezone_val = $active_bot_settings['openai_web_search_loc_timezone'] ?? '';
$claude_web_search_enabled_val = $active_bot_settings['claude_web_search_enabled'] ?? BotSettingsManager::DEFAULT_CLAUDE_WEB_SEARCH_ENABLED;
$claude_web_search_max_uses_val = ( isset( $active_bot_settings['claude_web_search_max_uses'] ) ? absint( $active_bot_settings['claude_web_search_max_uses'] ) : BotSettingsManager::DEFAULT_CLAUDE_WEB_SEARCH_MAX_USES );
$claude_web_search_max_uses_val = max( 1, min( $claude_web_search_max_uses_val, 20 ) );
$claude_web_search_loc_type_val = $active_bot_settings['claude_web_search_loc_type'] ?? BotSettingsManager::DEFAULT_CLAUDE_WEB_SEARCH_LOC_TYPE;
$claude_web_search_loc_country_val = $active_bot_settings['claude_web_search_loc_country'] ?? '';
$claude_web_search_loc_city_val = $active_bot_settings['claude_web_search_loc_city'] ?? '';
$claude_web_search_loc_region_val = $active_bot_settings['claude_web_search_loc_region'] ?? '';
$claude_web_search_loc_timezone_val = $active_bot_settings['claude_web_search_loc_timezone'] ?? '';
$claude_web_search_allowed_domains_val = $active_bot_settings['claude_web_search_allowed_domains'] ?? '';
$claude_web_search_blocked_domains_val = $active_bot_settings['claude_web_search_blocked_domains'] ?? '';
$claude_web_search_cache_ttl_val = $active_bot_settings['claude_web_search_cache_ttl'] ?? BotSettingsManager::DEFAULT_CLAUDE_WEB_SEARCH_CACHE_TTL;
$openrouter_web_search_enabled_val = $active_bot_settings['openrouter_web_search_enabled'] ?? BotSettingsManager::DEFAULT_OPENROUTER_WEB_SEARCH_ENABLED;
$openrouter_web_search_engine_val = $active_bot_settings['openrouter_web_search_engine'] ?? BotSettingsManager::DEFAULT_OPENROUTER_WEB_SEARCH_ENGINE;
if ( !in_array( $openrouter_web_search_engine_val, ['auto', 'native', 'exa'], true ) ) {
    $openrouter_web_search_engine_val = BotSettingsManager::DEFAULT_OPENROUTER_WEB_SEARCH_ENGINE;
}
$openrouter_web_search_max_results_val = ( isset( $active_bot_settings['openrouter_web_search_max_results'] ) ? absint( $active_bot_settings['openrouter_web_search_max_results'] ) : BotSettingsManager::DEFAULT_OPENROUTER_WEB_SEARCH_MAX_RESULTS );
$openrouter_web_search_max_results_val = max( 1, min( $openrouter_web_search_max_results_val, 10 ) );
$openrouter_web_search_search_prompt_val = $active_bot_settings['openrouter_web_search_search_prompt'] ?? BotSettingsManager::DEFAULT_OPENROUTER_WEB_SEARCH_SEARCH_PROMPT;
$xai_web_search_enabled_val = $active_bot_settings['xai_web_search_enabled'] ?? BotSettingsManager::DEFAULT_XAI_WEB_SEARCH_ENABLED;
$web_toggle_default_on_val = $active_bot_settings['web_toggle_default_on'] ?? BotSettingsManager::DEFAULT_WEB_TOGGLE_DEFAULT_ON;
$show_sources_val = $active_bot_settings['show_sources'] ?? BotSettingsManager::DEFAULT_SHOW_SOURCES;
$show_sources_val = ( in_array( $show_sources_val, ['0', '1'], true ) ? $show_sources_val : BotSettingsManager::DEFAULT_SHOW_SOURCES );
$sources_label_val = ( isset( $active_bot_settings['sources_label'] ) ? sanitize_text_field( (string) $active_bot_settings['sources_label'] ) : BotSettingsManager::DEFAULT_SOURCES_LABEL );
$searching_web_text_val = ( isset( $active_bot_settings['searching_web_text'] ) ? sanitize_text_field( (string) $active_bot_settings['searching_web_text'] ) : BotSettingsManager::DEFAULT_SEARCHING_WEB_TEXT );
$google_search_grounding_enabled_val = $active_bot_settings['google_search_grounding_enabled'] ?? BotSettingsManager::DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED;
$google_grounding_mode_val = $active_bot_settings['google_grounding_mode'] ?? BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
$google_grounding_dynamic_threshold_val = ( isset( $active_bot_settings['google_grounding_dynamic_threshold'] ) ? floatval( $active_bot_settings['google_grounding_dynamic_threshold'] ) : BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD );
$google_grounding_dynamic_threshold_val = max( 0.0, min( $google_grounding_dynamic_threshold_val, 1.0 ) );
// Conversations settings values (used in model settings sheet).
$openai_conversation_state_enabled_val = $active_bot_settings['openai_conversation_state_enabled'] ?? BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED;
$openai_conversation_state_enabled_val = ( in_array( $openai_conversation_state_enabled_val, ['0', '1'], true ) ? $openai_conversation_state_enabled_val : BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED );
$saved_max_messages = ( isset( $active_bot_settings['max_messages'] ) ? absint( $active_bot_settings['max_messages'] ) : BotSettingsManager::DEFAULT_MAX_MESSAGES );
$saved_max_messages = max( 1, min( $saved_max_messages, 1024 ) );
$enable_image_upload = $active_bot_settings['enable_image_upload'] ?? BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD;
$enable_image_upload = ( in_array( $enable_image_upload, ['0', '1'], true ) ? $enable_image_upload : BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD );
$enable_vector_store = $active_bot_settings['enable_vector_store'] ?? BotSettingsManager::DEFAULT_ENABLE_VECTOR_STORE;
$enable_vector_store = ( in_array( $enable_vector_store, ['0', '1'], true ) ? $enable_vector_store : BotSettingsManager::DEFAULT_ENABLE_VECTOR_STORE );
$enable_file_upload = $active_bot_settings['enable_file_upload'] ?? BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD;
$enable_file_upload = ( in_array( $enable_file_upload, ['0', '1'], true ) ? $enable_file_upload : BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD );
$content_aware_enabled = $active_bot_settings['content_aware_enabled'] ?? BotSettingsManager::DEFAULT_CONTENT_AWARE_ENABLED;
$content_aware_enabled = ( in_array( $content_aware_enabled, ['0', '1'], true ) ? $content_aware_enabled : BotSettingsManager::DEFAULT_CONTENT_AWARE_ENABLED );
$vector_store_provider = $active_bot_settings['vector_store_provider'] ?? BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER;
$allowed_vector_store_providers = [
    'openai',
    'pinecone',
    'qdrant',
    'chroma',
    'claude_files'
];
if ( !in_array( $vector_store_provider, $allowed_vector_store_providers, true ) ) {
    $vector_store_provider = BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER;
}
$openai_vector_store_ids_saved = [];
if ( isset( $active_bot_settings['openai_vector_store_ids'] ) ) {
    if ( is_array( $active_bot_settings['openai_vector_store_ids'] ) ) {
        $openai_vector_store_ids_saved = $active_bot_settings['openai_vector_store_ids'];
    } elseif ( is_string( $active_bot_settings['openai_vector_store_ids'] ) ) {
        $decoded_ids = json_decode( $active_bot_settings['openai_vector_store_ids'], true );
        if ( is_array( $decoded_ids ) ) {
            $openai_vector_store_ids_saved = $decoded_ids;
        }
    }
}
$pinecone_index_name = $active_bot_settings['pinecone_index_name'] ?? BotSettingsManager::DEFAULT_PINECONE_INDEX_NAME;
$vector_embedding_provider = $active_bot_settings['vector_embedding_provider'] ?? BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER;
$default_embedding_provider_map = AIPKit_Providers::get_default_embedding_provider_map();
$embedding_provider_options = AIPKit_Providers::get_embedding_provider_map( 'chatbot_ui' );
$allowed_embedding_providers = array_values( array_unique( array_map( 'sanitize_key', array_keys( $embedding_provider_options ) ) ) );
if ( empty( $allowed_embedding_providers ) ) {
    $allowed_embedding_providers = array_keys( $default_embedding_provider_map );
}
$default_embedding_provider_key = ( isset( $embedding_provider_options[BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER] ) ? BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER : (( array_key_first( $embedding_provider_options ) ?: BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER )) );
if ( !in_array( $vector_embedding_provider, $allowed_embedding_providers, true ) ) {
    $vector_embedding_provider = $default_embedding_provider_key;
}
$vector_embedding_model = $active_bot_settings['vector_embedding_model'] ?? BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_MODEL;
$qdrant_collection_names = [];
if ( !empty( $active_bot_settings['qdrant_collection_names'] ) && is_array( $active_bot_settings['qdrant_collection_names'] ) ) {
    $qdrant_collection_names = $active_bot_settings['qdrant_collection_names'];
} elseif ( !empty( $active_bot_settings['qdrant_collection_name'] ) ) {
    $qdrant_collection_names = [$active_bot_settings['qdrant_collection_name']];
}
$chroma_collection_names = [];
if ( !empty( $active_bot_settings['chroma_collection_names'] ) && is_array( $active_bot_settings['chroma_collection_names'] ) ) {
    $chroma_collection_names = $active_bot_settings['chroma_collection_names'];
} elseif ( !empty( $active_bot_settings['chroma_collection_name'] ) ) {
    $chroma_collection_names = [$active_bot_settings['chroma_collection_name']];
}
$vector_store_top_k = ( isset( $active_bot_settings['vector_store_top_k'] ) ? absint( $active_bot_settings['vector_store_top_k'] ) : BotSettingsManager::DEFAULT_VECTOR_STORE_TOP_K );
$vector_store_top_k = max( 1, min( $vector_store_top_k, 20 ) );
$vector_store_confidence_threshold = $active_bot_settings['vector_store_confidence_threshold'] ?? BotSettingsManager::DEFAULT_VECTOR_STORE_CONFIDENCE_THRESHOLD;
$vector_store_confidence_threshold = max( 0, min( absint( $vector_store_confidence_threshold ), 100 ) );
$openai_vector_stores = [];
$pinecone_indexes = [];
$qdrant_collections = [];
$chroma_collections = [];
$embedding_models_by_provider = [];
$openai_provider_data = [];
$pinecone_provider_data = [];
$qdrant_provider_data = [];
$chroma_provider_data = [];
$google_provider_data = [];
$azure_provider_data = [];
$claude_provider_data = [];
$xai_provider_data = [];
if ( class_exists( AIPKit_Vector_Store_Registry::class ) ) {
    $openai_vector_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider( 'OpenAI' );
}
if ( class_exists( AIPKit_Providers::class ) ) {
    $pinecone_indexes = AIPKit_Providers::get_pinecone_indexes();
    $qdrant_collections = AIPKit_Providers::get_qdrant_collections();
    $chroma_collections = AIPKit_Providers::get_chroma_collections();
    $embedding_models_by_provider = AIPKit_Providers::get_embedding_models_by_provider( 'chatbot_ui' );
    $openai_provider_data = AIPKit_Providers::get_provider_data( 'OpenAI' );
    $pinecone_provider_data = AIPKit_Providers::get_provider_data( 'Pinecone' );
    $qdrant_provider_data = AIPKit_Providers::get_provider_data( 'Qdrant' );
    $chroma_provider_data = AIPKit_Providers::get_provider_data( 'Chroma' );
    $google_provider_data = AIPKit_Providers::get_provider_data( 'Google' );
    $azure_provider_data = AIPKit_Providers::get_provider_data( 'Azure' );
    $claude_provider_data = AIPKit_Providers::get_provider_data( 'Claude' );
    $xai_provider_data = AIPKit_Providers::get_provider_data( 'xAI' );
}
$openai_api_key = $openai_provider_data['api_key'] ?? '';
$pinecone_api_key = $pinecone_provider_data['api_key'] ?? '';
$qdrant_url = $qdrant_provider_data['url'] ?? '';
$qdrant_api_key = $qdrant_provider_data['api_key'] ?? '';
$chroma_url = $chroma_provider_data['url'] ?? '';
$google_api_key = $google_provider_data['api_key'] ?? '';
$azure_api_key = $azure_provider_data['api_key'] ?? '';
$claude_api_key = $claude_provider_data['api_key'] ?? '';
$xai_api_key = $xai_provider_data['api_key'] ?? '';
$image_triggers = $active_bot_settings['image_triggers'] ?? BotSettingsManager::DEFAULT_IMAGE_TRIGGERS;
$chat_image_model_id = $active_bot_settings['chat_image_model_id'] ?? BotSettingsManager::DEFAULT_CHAT_IMAGE_MODEL_ID;
$enable_image_generation = $active_bot_settings['enable_image_generation'] ?? BotSettingsManager::DEFAULT_ENABLE_IMAGE_GENERATION;
$enable_image_generation = ( in_array( $enable_image_generation, ['0', '1'], true ) ? $enable_image_generation : BotSettingsManager::DEFAULT_ENABLE_IMAGE_GENERATION );
$replicate_model_list = AIPKit_Providers::get_replicate_models();
$openrouter_image_model_list = AIPKit_Providers::get_openrouter_image_models();
$xai_image_model_list = AIPKit_Providers::get_xai_image_models();
$available_image_models = [
    'OpenAI' => AIPKit_Providers::get_openai_image_models(),
    'Azure'  => AIPKit_Providers::get_azure_image_models(),
    'Google' => AIPKit_Providers::get_google_image_models(),
];
if ( isset( $openrouter_image_model_list ) && is_array( $openrouter_image_model_list ) && !empty( $openrouter_image_model_list ) ) {
    $available_image_models['OpenRouter'] = $openrouter_image_model_list;
}
if ( isset( $xai_image_model_list ) && is_array( $xai_image_model_list ) && !empty( $xai_image_model_list ) ) {
    $available_image_models['xAI'] = $xai_image_model_list;
}
if ( isset( $replicate_model_list ) && is_array( $replicate_model_list ) && !empty( $replicate_model_list ) ) {
    $available_image_models['Replicate'] = $replicate_model_list;
}
$reasoning_effort_val = $active_bot_settings['reasoning_effort'] ?? BotSettingsManager::DEFAULT_REASONING_EFFORT;
$reasoning_effort_val = \WPAICG\Core\AIPKit_OpenAI_Reasoning::sanitize_effort( $reasoning_effort_val );
$allowed_reasoning_effort = [
    'none',
    'low',
    'medium',
    'high',
    'xhigh'
];
if ( !in_array( $reasoning_effort_val, $allowed_reasoning_effort, true ) ) {
    $reasoning_effort_val = BotSettingsManager::DEFAULT_REASONING_EFFORT;
}
// Audio settings values (used in audio flyout).
$enable_voice_input = $active_bot_settings['enable_voice_input'] ?? BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT;
$enable_voice_input = ( in_array( $enable_voice_input, ['0', '1'], true ) ? $enable_voice_input : BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT );
$stt_provider = $active_bot_settings['stt_provider'] ?? BotSettingsManager::DEFAULT_STT_PROVIDER;
$allowed_stt_providers = ['OpenAI', 'Azure'];
if ( !in_array( $stt_provider, $allowed_stt_providers, true ) ) {
    $stt_provider = BotSettingsManager::DEFAULT_STT_PROVIDER;
}
$stt_openai_model_id = $active_bot_settings['stt_openai_model_id'] ?? BotSettingsManager::DEFAULT_STT_OPENAI_MODEL_ID;
$openai_stt_models = AIPKit_Providers::get_openai_stt_models();
$tts_enabled = $active_bot_settings['tts_enabled'] ?? BotSettingsManager::DEFAULT_TTS_ENABLED;
$tts_enabled = ( in_array( $tts_enabled, ['0', '1'], true ) ? $tts_enabled : BotSettingsManager::DEFAULT_TTS_ENABLED );
$tts_provider = $active_bot_settings['tts_provider'] ?? BotSettingsManager::DEFAULT_TTS_PROVIDER;
$tts_providers = ['Google', 'OpenAI', 'ElevenLabs'];
if ( !in_array( $tts_provider, $tts_providers, true ) ) {
    $tts_provider = BotSettingsManager::DEFAULT_TTS_PROVIDER;
}
$tts_google_voice_id = $active_bot_settings['tts_google_voice_id'] ?? '';
$tts_openai_voice_id = $active_bot_settings['tts_openai_voice_id'] ?? 'alloy';
$tts_openai_model_id = $active_bot_settings['tts_openai_model_id'] ?? BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID;
$tts_elevenlabs_voice_id = $active_bot_settings['tts_elevenlabs_voice_id'] ?? '';
$tts_elevenlabs_model_id = $active_bot_settings['tts_elevenlabs_model_id'] ?? BotSettingsManager::DEFAULT_TTS_ELEVENLABS_MODEL_ID;
$tts_auto_play = $active_bot_settings['tts_auto_play'] ?? BotSettingsManager::DEFAULT_TTS_AUTO_PLAY;
$tts_auto_play = ( in_array( $tts_auto_play, ['0', '1'], true ) ? $tts_auto_play : BotSettingsManager::DEFAULT_TTS_AUTO_PLAY );
$google_tts_voices = ( class_exists( '\\WPAICG\\Core\\Providers\\Google\\GoogleSettingsHandler' ) ? \WPAICG\Core\Providers\Google\GoogleSettingsHandler::get_synced_google_tts_voices() : [] );
$elevenlabs_tts_voices = AIPKit_Providers::get_elevenlabs_voices();
$elevenlabs_tts_models = AIPKit_Providers::get_elevenlabs_models();
$openai_tts_models = AIPKit_Providers::get_openai_tts_models();
$openai_tts_voices = [
    [
        'id'   => 'alloy',
        'name' => 'Alloy',
    ],
    [
        'id'   => 'echo',
        'name' => 'Echo',
    ],
    [
        'id'   => 'fable',
        'name' => 'Fable',
    ],
    [
        'id'   => 'onyx',
        'name' => 'Onyx',
    ],
    [
        'id'   => 'nova',
        'name' => 'Nova',
    ],
    [
        'id'   => 'shimmer',
        'name' => 'Shimmer',
    ]
];
$enable_realtime_voice = $active_bot_settings['enable_realtime_voice'] ?? BotSettingsManager::DEFAULT_ENABLE_REALTIME_VOICE;
$enable_realtime_voice = ( in_array( $enable_realtime_voice, ['0', '1'], true ) ? $enable_realtime_voice : BotSettingsManager::DEFAULT_ENABLE_REALTIME_VOICE );
$direct_voice_mode = $active_bot_settings['direct_voice_mode'] ?? BotSettingsManager::DEFAULT_DIRECT_VOICE_MODE;
$direct_voice_mode = ( in_array( $direct_voice_mode, ['0', '1'], true ) ? $direct_voice_mode : BotSettingsManager::DEFAULT_DIRECT_VOICE_MODE );
$realtime_model = $active_bot_settings['realtime_model'] ?? BotSettingsManager::DEFAULT_REALTIME_MODEL;
$realtime_voice = $active_bot_settings['realtime_voice'] ?? BotSettingsManager::DEFAULT_REALTIME_VOICE;
$turn_detection = $active_bot_settings['turn_detection'] ?? BotSettingsManager::DEFAULT_TURN_DETECTION;
$speed = ( isset( $active_bot_settings['speed'] ) ? floatval( $active_bot_settings['speed'] ) : BotSettingsManager::DEFAULT_SPEED );
$speed = max( 0.25, min( $speed, 1.5 ) );
$input_audio_format = $active_bot_settings['input_audio_format'] ?? BotSettingsManager::DEFAULT_INPUT_AUDIO_FORMAT;
$output_audio_format = $active_bot_settings['output_audio_format'] ?? BotSettingsManager::DEFAULT_OUTPUT_AUDIO_FORMAT;
$input_audio_noise_reduction = $active_bot_settings['input_audio_noise_reduction'] ?? BotSettingsManager::DEFAULT_INPUT_AUDIO_NOISE_REDUCTION;
$input_audio_noise_reduction = ( in_array( $input_audio_noise_reduction, ['0', '1'], true ) ? $input_audio_noise_reduction : BotSettingsManager::DEFAULT_INPUT_AUDIO_NOISE_REDUCTION );
$realtime_models = ['gpt-4o-realtime-preview', 'gpt-4o-mini-realtime'];
$realtime_voices = [
    'alloy',
    'ash',
    'ballad',
    'coral',
    'echo',
    'fable',
    'onyx',
    'nova',
    'shimmer',
    'verse'
];
$direct_voice_mode_disabled = !($popup_enabled === '1' && $enable_realtime_voice === '1');
// Provider/model data for AI selection.
$allowed_main_providers = ( class_exists( AIPKit_Providers::class ) ? AIPKit_Providers::get_main_provider_allowlist() : [
    'OpenAI',
    'Google',
    'Claude',
    'OpenRouter',
    'Azure',
    'DeepSeek',
    'xAI'
] );
if ( !is_array( $allowed_main_providers ) || empty( $allowed_main_providers ) ) {
    $allowed_main_providers = [
        'OpenAI',
        'Google',
        'Claude',
        'OpenRouter',
        'Azure',
        'DeepSeek',
        'xAI'
    ];
}
// Backward-compatible alias used by shared provider/model partials.
$providers = $allowed_main_providers;
$default_main_provider = $allowed_main_providers[0] ?? 'OpenAI';
$is_pro = class_exists( '\\WPAICG\\aipkit_dashboard' ) && aipkit_dashboard::is_pro_plan();
$rt_disabled_by_plan = !$is_pro_plan;
$rt_controls_disabled = $rt_disabled_by_plan;
$rt_force_visible = $rt_controls_disabled;
$can_enable_file_upload = false;
if ( class_exists( aipkit_dashboard::class ) ) {
    if ( $is_pro_plan ) {
        $can_enable_file_upload = true;
    }
}
$file_upload_toggle_value = ( $can_enable_file_upload && $enable_file_upload === '1' ? '1' : '0' );
$grouped_openai_models = get_option( 'aipkit_openai_model_list', [] );
$openrouter_model_list = get_option( 'aipkit_openrouter_model_list', [] );
$google_model_list = get_option( 'aipkit_google_model_list', [] );
$azure_deployment_list = AIPKit_Providers::get_azure_deployments();
$claude_model_list = AIPKit_Providers::get_claude_models();
$deepseek_model_list = AIPKit_Providers::get_deepseek_models();
$xai_model_list = AIPKit_Providers::get_xai_models();
$ollama_model_list = AIPKit_Providers::get_ollama_models();
$saved_provider = ( isset( $active_bot_settings['provider'] ) ? sanitize_text_field( (string) $active_bot_settings['provider'] ) : $default_main_provider );
$saved_model = $active_bot_settings['model'] ?? '';
if ( class_exists( AIPKit_Providers::class ) ) {
    // Normalize legacy lowercase values against the current allowlist.
    $allowlist_by_lower = [];
    foreach ( $allowed_main_providers as $provider_name ) {
        if ( !is_string( $provider_name ) ) {
            continue;
        }
        $provider_name = sanitize_text_field( $provider_name );
        if ( $provider_name === '' ) {
            continue;
        }
        $allowlist_by_lower[strtolower( $provider_name )] = $provider_name;
    }
    $saved_provider_lookup = strtolower( $saved_provider );
    if ( isset( $allowlist_by_lower[$saved_provider_lookup] ) ) {
        $saved_provider = $allowlist_by_lower[$saved_provider_lookup];
    }
    $saved_provider = AIPKit_Providers::normalize_main_provider( (string) $saved_provider, $default_main_provider );
} elseif ( !in_array( $saved_provider, $allowed_main_providers, true ) ) {
    $saved_provider = $default_main_provider;
}
// Preview placeholder content
$preview_placeholder_key = ( $active_bot_post ? 'previewLoading' : 'previewPlaceholderSelect' );
$preview_placeholder_text = ( $active_bot_post ? __( 'Loading preview...', 'gpt3-ai-content-generator' ) : __( 'Select a bot to see the preview.', 'gpt3-ai-content-generator' ) );
$is_default_active = $active_bot_post && $default_bot_id && $active_bot_post->ID === $default_bot_id;
$aipkit_notice_id = 'aipkit_provider_notice_chatbot';
include WPAICG_PLUGIN_DIR . 'admin/views/shared/provider-key-notice.php';
?>

<div
    class="aipkit_chatbot_module_container aipkit_chatbot_builder"
    data-aipkit-chatbot-layout="next"
    data-active-bot-id="<?php 
echo esc_attr( $initial_active_bot_id );
?>"
    data-default-bot-id="<?php 
echo esc_attr( $default_bot_id );
?>"
    data-openai-api-key-set="<?php 
echo esc_attr( ( !empty( $openai_api_key ) ? 'true' : 'false' ) );
?>"
    data-pinecone-api-key-set="<?php 
echo esc_attr( ( !empty( $pinecone_api_key ) ? 'true' : 'false' ) );
?>"
    data-qdrant-api-key-set="<?php 
echo esc_attr( ( !empty( $qdrant_api_key ) ? 'true' : 'false' ) );
?>"
    data-qdrant-url-set="<?php 
echo esc_attr( ( !empty( $qdrant_url ) ? 'true' : 'false' ) );
?>"
    data-chroma-url-set="<?php 
echo esc_attr( ( !empty( $chroma_url ) ? 'true' : 'false' ) );
?>"
    data-google-api-key-set="<?php 
echo esc_attr( ( !empty( $google_api_key ) ? 'true' : 'false' ) );
?>"
    data-azure-api-key-set="<?php 
echo esc_attr( ( !empty( $azure_api_key ) ? 'true' : 'false' ) );
?>"
    data-claude-api-key-set="<?php 
echo esc_attr( ( !empty( $claude_api_key ) ? 'true' : 'false' ) );
?>"
    data-xai-api-key-set="<?php 
echo esc_attr( ( !empty( $xai_api_key ) ? 'true' : 'false' ) );
?>"
    data-model-settings-title="<?php 
esc_attr_e( 'Settings', 'gpt3-ai-content-generator' );
?>"
    data-model-settings-description="<?php 
esc_attr_e( 'Configure model settings and behavior for this chatbot.', 'gpt3-ai-content-generator' );
?>"
>
    <div class="aipkit_chatbot_module_header">
        <div class="aipkit_chatbot_module_header_copy">
            <div class="aipkit_chatbot_module_header_title_row">
                <h2 class="aipkit_container-title"><?php 
esc_html_e( 'Chatbot', 'gpt3-ai-content-generator' );
?></h2>
            </div>
            <p class="aipkit_chatbot_module_header_hint"><?php 
esc_html_e( 'Build, configure, preview, and deploy AI chatbots.', 'gpt3-ai-content-generator' );
?></p>
        </div>
    </div>
    <div class="aipkit_chatbot_module_body">
        <div class="aipkit_chatbot_builder_layout">
            <div class="aipkit_chatbot_builder_left">
                <div id="aipkit_chatbot_main_tab_content_container">
                    <div class="aipkit_tab-content aipkit_active">
                        <div class="aipkit_chatbot-settings-area aipkit_builder_settings_area">
                            <form
                                class="aipkit_chatbot_settings_form"
                                data-bot-id="<?php 
echo esc_attr( $initial_active_bot_id );
?>"
                                onsubmit="return false;"
                            >
                            <?php 
include WPAICG_PLUGIN_DIR . 'admin/views/shared/vector-store-nonce-fields.php';
?>
                            <?php 
if ( $active_bot_post ) {
    ?>
                            <section class="aipkit_chatbot_shell aipkit_chatbot_shell--workspace">
                                <div class="aipkit_chatbot_shell_header aipkit_chatbot_shell_header--workspace">
                                    <div class="aipkit_chatbot_shell_controls">
                                        <div class="aipkit_builder_shortcode_row">
                                            <div class="aipkit_builder_shortcode_meta">
                                                <button
                                                    type="button"
                                                    class="aipkit_shortcode_pill aipkit_builder_shortcode_pill"
                                                    data-shortcode="<?php 
    echo esc_attr( $shortcode_text );
    ?>"
                                                    title="<?php 
    esc_attr_e( 'Click to copy shortcode', 'gpt3-ai-content-generator' );
    ?>"
                                                >
                                                    <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                                                    <span class="aipkit_shortcode_text"><?php 
    echo esc_html( $shortcode_text );
    ?></span>
                                                </button>
                                            </div>
                                            <div class="aipkit_builder_mode_inline">
                                                <div class="aipkit_builder_mode_field">
                                                    <label
                                                        for="aipkit_builder_top_mode_select"
                                                        class="aipkit_builder_mode_label"
                                                    >
                                                        <?php 
    esc_html_e( 'Mode:', 'gpt3-ai-content-generator' );
    ?>
                                                    </label>
                                                    <select
                                                        id="aipkit_builder_top_mode_select"
                                                        class="aipkit_form-input aipkit_builder_mode_select"
                                                        data-aipkit-top-mode-select
                                                        data-aipkit-external-popup-enabled="<?php 
    echo esc_attr( ( $popup_enabled === '1' ? '1' : '0' ) );
    ?>"
                                                    >
                                                        <option value="inline" <?php 
    selected( $deploy_mode, 'inline' );
    ?>>
                                                            <?php 
    esc_html_e( 'On-page', 'gpt3-ai-content-generator' );
    ?>
                                                        </option>
                                                        <option value="popup" <?php 
    selected( $deploy_mode, 'popup' );
    ?>>
                                                            <?php 
    esc_html_e( 'Popup', 'gpt3-ai-content-generator' );
    ?>
                                                        </option>
                                                        <option value="external" <?php 
    selected( $deploy_mode, 'external' );
    ?>>
                                                            <?php 
    esc_html_e( 'External', 'gpt3-ai-content-generator' );
    ?>
                                                        </option>
                                                    </select>
                                                </div>
                                                <div
                                                    class="aipkit_builder_mode_field aipkit_builder_external_setup_row"
                                                    data-aipkit-external-setup-row
                                                    <?php 
    echo ( $deploy_mode === 'external' ? '' : 'hidden' );
    ?>
                                                >
                                                    <button
                                                        type="button"
                                                        class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_builder_external_setup_btn"
                                                        data-aipkit-external-setup-btn
                                                    >
                                                        <?php 
    esc_html_e( 'External Setup', 'gpt3-ai-content-generator' );
    ?>
                                                    </button>
                                                </div>
                                                <div
                                                    class="aipkit_builder_mode_field aipkit_builder_popup_scope_row"
                                                    <?php 
    echo ( $deploy_mode === 'popup' ? '' : 'hidden' );
    ?>
                                                >
                                                    <label
                                                        for="aipkit_builder_top_popup_scope_select"
                                                        class="aipkit_builder_mode_label"
                                                    >
                                                        <?php 
    esc_html_e( 'Site-Wide:', 'gpt3-ai-content-generator' );
    ?>
                                                    </label>
                                                    <select
                                                        id="aipkit_builder_top_popup_scope_select"
                                                        name="aipkit_deploy_popup_scope"
                                                        class="aipkit_form-input aipkit_builder_mode_select aipkit_builder_mode_select--compact"
                                                    >
                                                        <option value="sitewide" <?php 
    selected( $deploy_popup_scope, 'sitewide' );
    ?>>
                                                            <?php 
    esc_html_e( 'Yes', 'gpt3-ai-content-generator' );
    ?>
                                                        </option>
                                                        <option value="page" <?php 
    selected( $deploy_popup_scope, 'page' );
    ?>>
                                                            <?php 
    esc_html_e( 'No', 'gpt3-ai-content-generator' );
    ?>
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="aipkit_hidden aipkit_model_status_slot" aria-hidden="true">
                                            <span class="aipkit_model_sync_status"></span>
                                            <span
                                                id="aipkit_chatbot_global_save_status_container"
                                                class="aipkit_save_status_container aipkit_builder_save_status"
                                                aria-live="polite"
                                            ></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="aipkit_chatbot_shell_body aipkit_chatbot_shell_body--workspace">
                            <section class="aipkit_builder_card aipkit_builder_card--bot-tabs">
                                <div class="aipkit_builder_bot_tabs_row">
                                    <div class="aipkit_builder_bot_tabs_shell">
                                        <div class="aipkit_builder_bot_tabs" data-aipkit-bot-tabs role="tablist" aria-label="<?php 
    esc_attr_e( 'Chatbots', 'gpt3-ai-content-generator' );
    ?>">
                                            <?php 
    foreach ( $all_bots_ordered_entries as $bot_entry_for_tabs ) {
        ?>
                                                <?php 
        $bot_post_for_tabs = $bot_entry_for_tabs['post'];
        $is_active_tab = (int) $initial_active_bot_id === (int) $bot_post_for_tabs->ID;
        ?>
                                                <button
                                                    type="button"
                                                    class="aipkit_builder_bot_tab<?php 
        echo ( $is_active_tab ? ' is-active' : '' );
        ?>"
                                                    data-bot-id="<?php 
        echo esc_attr( $bot_post_for_tabs->ID );
        ?>"
                                                    role="tab"
                                                    aria-selected="<?php 
        echo ( $is_active_tab ? 'true' : 'false' );
        ?>"
                                                    tabindex="<?php 
        echo ( $is_active_tab ? '0' : '-1' );
        ?>"
                                                >
                                                    <span class="aipkit_builder_bot_tab_label">
                                                        <?php 
        echo esc_html( $bot_post_for_tabs->post_title );
        ?>
                                                    </span>
                                                </button>
                                            <?php 
    }
    ?>
                                        </div>
                                        <button
                                            type="button"
                                            class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_builder_new_bot_btn"
                                            aria-label="<?php 
    esc_attr_e( 'Create new chatbot', 'gpt3-ai-content-generator' );
    ?>"
                                            title="<?php 
    esc_attr_e( 'Create new chatbot', 'gpt3-ai-content-generator' );
    ?>"
                                        >
                                            <span class="aipkit_builder_new_bot_icon" aria-hidden="true"></span>
                                        </button>
                                        <div class="aipkit_builder_bot_overflow">
                                            <button
                                                type="button"
                                                class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn aipkit_builder_bot_overflow_trigger"
                                                aria-label="<?php 
    esc_attr_e( 'Show chatbot list', 'gpt3-ai-content-generator' );
    ?>"
                                                title="<?php 
    esc_attr_e( 'Show chatbot list', 'gpt3-ai-content-generator' );
    ?>"
                                                aria-haspopup="menu"
                                                aria-expanded="false"
                                                hidden
                                            >
                                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                            </button>
                                            <div class="aipkit_builder_bot_overflow_menu" role="menu" hidden></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="aipkit_builder_bot_select_shell" hidden>
                                    <label for="aipkit_chatbot_builder_bot_select" class="screen-reader-text">
                                        <?php 
    esc_html_e( 'Chatbot', 'gpt3-ai-content-generator' );
    ?>
                                    </label>
                                    <select
                                        id="aipkit_chatbot_builder_bot_select"
                                        name="aipkit_chatbot_builder_bot_select"
                                        class="aipkit_form-input aipkit_builder_bot_select_input"
                                        <?php 
    echo ( empty( $all_bots_ordered_entries ) ? 'disabled' : '' );
    ?>
                                    >
                                        <?php 
    if ( empty( $all_bots_ordered_entries ) ) {
        ?>
                                            <option value="">
                                                <?php 
        esc_html_e( 'No chatbots yet', 'gpt3-ai-content-generator' );
        ?>
                                            </option>
                                        <?php 
    } else {
        ?>
                                            <option value="__new__">
                                                <?php 
        esc_html_e( '+ New Bot', 'gpt3-ai-content-generator' );
        ?>
                                            </option>
                                            <option value="" disabled>----------</option>
                                            <?php 
        foreach ( $all_bots_ordered_entries as $bot_entry_for_select ) {
            ?>
                                                <?php 
            $bot_post_for_select = $bot_entry_for_select['post'];
            ?>
                                                <option
                                                    value="<?php 
            echo esc_attr( $bot_post_for_select->ID );
            ?>"
                                                    <?php 
            selected( $initial_active_bot_id, $bot_post_for_select->ID );
            ?>
                                                >
                                                    <?php 
            echo esc_html( $bot_post_for_select->post_title );
            ?>
                                                </option>
                                            <?php 
        }
        ?>
                                        <?php 
    }
    ?>
                                    </select>
                                </div>
                            </section>
                            <section class="aipkit_builder_card aipkit_builder_card--settings aipkit_settings_accordion" id="aipkit_settings_accordion">
                                <div
                                    id="aipkit_chatbot_accordion_overlay"
                                    class="aipkit_chatbot_accordion_overlay"
                                    aria-hidden="true"
                                    hidden
                                >
                                    <span
                                        class="aipkit_chatbot_accordion_overlay_spinner"
                                        aria-hidden="true"
                                    ></span>
                                </div>
                                <?php 
    $bot_id = $initial_active_bot_id;
    $bot_settings = $active_bot_settings;
    $chatbot_summary_fallback = __( 'Select model', 'gpt3-ai-content-generator' );
    $chatbot_summary_text = ( !empty( $saved_model ) ? $saved_model : $chatbot_summary_fallback );
    $interface_summary_fallback = __( 'Select theme', 'gpt3-ai-content-generator' );
    $saved_theme_key = ( isset( $saved_theme ) ? (string) $saved_theme : '' );
    $interface_summary_text = '';
    if ( $saved_theme_key === 'custom' && $selected_theme_preset_label !== '' ) {
        $interface_summary_text = $selected_theme_preset_label;
    } elseif ( !empty( $saved_theme_key ) && isset( $available_themes[$saved_theme_key] ) ) {
        $interface_summary_text = (string) $available_themes[$saved_theme_key];
    } elseif ( !empty( $saved_theme_key ) ) {
        $interface_summary_text = ucwords( str_replace( ['-', '_'], ' ', $saved_theme_key ) );
    }
    if ( $interface_summary_text === '' ) {
        $interface_summary_text = $interface_summary_fallback;
    }
    $popup_summary_fallback = __( 'Popup settings', 'gpt3-ai-content-generator' );
    $popup_position_labels = [
        'bottom-right' => __( 'Bottom Right', 'gpt3-ai-content-generator' ),
        'bottom-left'  => __( 'Bottom Left', 'gpt3-ai-content-generator' ),
        'top-right'    => __( 'Top Right', 'gpt3-ai-content-generator' ),
        'top-left'     => __( 'Top Left', 'gpt3-ai-content-generator' ),
    ];
    $popup_summary_parts = [];
    if ( isset( $popup_position_labels[$popup_position] ) ) {
        $popup_summary_parts[] = $popup_position_labels[$popup_position];
    }
    $popup_summary_text = ( !empty( $popup_summary_parts ) ? implode( ' | ', $popup_summary_parts ) : $popup_summary_fallback );
    $context_summary_fallback = '';
    $context_summary_parts = [];
    if ( $enable_vector_store === '1' ) {
        $vector_provider_labels = [
            'openai'       => __( 'OpenAI', 'gpt3-ai-content-generator' ),
            'pinecone'     => __( 'Pinecone', 'gpt3-ai-content-generator' ),
            'qdrant'       => __( 'Qdrant', 'gpt3-ai-content-generator' ),
            'chroma'       => __( 'Chroma', 'gpt3-ai-content-generator' ),
            'claude_files' => __( 'Anthropic Files', 'gpt3-ai-content-generator' ),
        ];
        $vector_provider_label = $vector_provider_labels[$vector_store_provider] ?? '';
        $vector_source_names = [];
        if ( $vector_store_provider === 'openai' ) {
            $openai_store_names = [];
            foreach ( $openai_vector_stores as $store_index => $store ) {
                if ( !is_array( $store ) ) {
                    continue;
                }
                $store_id = ( isset( $store['id'] ) ? trim( (string) $store['id'] ) : '' );
                if ( $store_id === '' ) {
                    continue;
                }
                $store_name = ( isset( $store['name'] ) ? trim( (string) $store['name'] ) : '' );
                if ( $store_name === '' ) {
                    $store_name = sprintf( 
                        /* translators: %d is the vector store index. */
                        __( 'Untitled store %d', 'gpt3-ai-content-generator' ),
                        (int) $store_index + 1
                     );
                }
                $openai_store_names[$store_id] = $store_name;
            }
            foreach ( $openai_vector_store_ids_saved as $saved_store_id ) {
                $saved_store_id = ( is_scalar( $saved_store_id ) ? trim( (string) $saved_store_id ) : '' );
                if ( $saved_store_id === '' ) {
                    continue;
                }
                $vector_source_names[] = $openai_store_names[$saved_store_id] ?? $saved_store_id;
            }
        } elseif ( $vector_store_provider === 'pinecone' ) {
            if ( !empty( $pinecone_index_name ) ) {
                $vector_source_names[] = (string) $pinecone_index_name;
            }
        } elseif ( $vector_store_provider === 'qdrant' ) {
            foreach ( $qdrant_collection_names as $collection_name ) {
                $collection_name = ( is_scalar( $collection_name ) ? trim( (string) $collection_name ) : '' );
                if ( $collection_name !== '' ) {
                    $vector_source_names[] = $collection_name;
                }
            }
        } elseif ( $vector_store_provider === 'chroma' ) {
            foreach ( $chroma_collection_names as $collection_name ) {
                $collection_name = ( is_scalar( $collection_name ) ? trim( (string) $collection_name ) : '' );
                if ( $collection_name !== '' ) {
                    $vector_source_names[] = $collection_name;
                }
            }
        }
        $vector_source_names = array_values( array_unique( array_filter( array_map( 'trim', $vector_source_names ) ) ) );
        if ( $vector_provider_label !== '' ) {
            $knowledge_summary = $vector_provider_label;
            if ( !empty( $vector_source_names ) ) {
                $knowledge_summary .= ': ' . implode( ', ', $vector_source_names );
            }
            $context_summary_parts[] = $knowledge_summary;
        } elseif ( !empty( $vector_source_names ) ) {
            $context_summary_parts[] = implode( ', ', $vector_source_names );
        }
        if ( in_array( $vector_store_provider, ['pinecone', 'qdrant', 'chroma'], true ) && !empty( $vector_embedding_model ) ) {
            $embedding_model_label = trim( (string) $vector_embedding_model );
            $selected_embedding_provider = trim( (string) $vector_embedding_provider );
            if ( $selected_embedding_provider !== '' && isset( $embedding_models_by_provider[$selected_embedding_provider] ) && is_array( $embedding_models_by_provider[$selected_embedding_provider] ) ) {
                foreach ( $embedding_models_by_provider[$selected_embedding_provider] as $embedding_model_option ) {
                    if ( !is_array( $embedding_model_option ) ) {
                        continue;
                    }
                    $embedding_option_id = ( isset( $embedding_model_option['id'] ) ? trim( (string) $embedding_model_option['id'] ) : '' );
                    if ( $embedding_option_id !== $embedding_model_label ) {
                        continue;
                    }
                    $embedding_option_name = ( isset( $embedding_model_option['name'] ) ? trim( (string) $embedding_model_option['name'] ) : '' );
                    if ( $embedding_option_name !== '' ) {
                        $embedding_model_label = $embedding_option_name;
                    }
                    break;
                }
            }
            if ( $embedding_model_label !== '' ) {
                $context_summary_parts[] = $embedding_model_label;
            }
        }
    }
    if ( $content_aware_enabled === '1' ) {
        $context_summary_parts[] = __( 'Page context', 'gpt3-ai-content-generator' );
    }
    $context_summary_text = ( !empty( $context_summary_parts ) ? implode( ' | ', $context_summary_parts ) : $context_summary_fallback );
    $tools_summary_fallback = '';
    $tools_summary_parts = [];
    if ( $file_upload_toggle_value === '1' ) {
        $tools_summary_parts[] = __( 'File Upload', 'gpt3-ai-content-generator' );
    }
    if ( $enable_image_upload === '1' ) {
        $tools_summary_parts[] = __( 'Image Analysis', 'gpt3-ai-content-generator' );
    }
    if ( $enable_image_generation === '1' ) {
        $tools_summary_parts[] = __( 'Image generation', 'gpt3-ai-content-generator' );
    }
    $web_search_enabled = false;
    if ( $saved_provider === 'OpenAI' ) {
        $web_search_enabled = $openai_web_search_enabled_val === '1';
    } elseif ( $saved_provider === 'Google' ) {
        $web_search_enabled = $google_search_grounding_enabled_val === '1';
    } elseif ( $saved_provider === 'Claude' ) {
        $web_search_enabled = $claude_web_search_enabled_val === '1';
    } elseif ( $saved_provider === 'OpenRouter' ) {
        $web_search_enabled = $openrouter_web_search_enabled_val === '1';
    } elseif ( $saved_provider === 'xAI' ) {
        $web_search_enabled = $xai_web_search_enabled_val === '1';
    }
    if ( $web_search_enabled ) {
        $tools_summary_parts[] = __( 'Web Search', 'gpt3-ai-content-generator' );
    }
    if ( $enable_voice_input === '1' ) {
        $tools_summary_parts[] = __( 'Speech to Text', 'gpt3-ai-content-generator' );
    }
    if ( $tts_enabled === '1' ) {
        $tools_summary_parts[] = __( 'Text to Speech', 'gpt3-ai-content-generator' );
    }
    if ( $enable_realtime_voice === '1' ) {
        $tools_summary_parts[] = __( 'Realtime Voice', 'gpt3-ai-content-generator' );
    }
    $tools_summary_count = count( $tools_summary_parts );
    if ( $tools_summary_count > 3 ) {
        $tools_summary_text = sprintf( 
            /* translators: %d is the number of enabled tools. */
            __( 'Enabled: %d', 'gpt3-ai-content-generator' ),
            $tools_summary_count
         );
    } elseif ( $tools_summary_count > 0 ) {
        $tools_summary_text = sprintf( 
            /* translators: %s is a comma-separated list of enabled tools. */
            __( 'Enabled: %s', 'gpt3-ai-content-generator' ),
            implode( ', ', $tools_summary_parts )
         );
    } else {
        $tools_summary_text = $tools_summary_fallback;
    }
    $token_limit_mode_value = $active_bot_settings['token_limit_mode'] ?? BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE;
    $token_limit_mode_value = ( is_scalar( $token_limit_mode_value ) ? sanitize_key( (string) $token_limit_mode_value ) : BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE );
    if ( !in_array( $token_limit_mode_value, ['general', 'role_based'], true ) ) {
        $token_limit_mode_value = BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE;
    }
    $token_guest_limit_value = $active_bot_settings['token_guest_limit'] ?? '';
    $token_guest_limit_value = ( is_scalar( $token_guest_limit_value ) ? trim( (string) $token_guest_limit_value ) : '' );
    $token_user_limit_value = $active_bot_settings['token_user_limit'] ?? '';
    $token_user_limit_value = ( is_scalar( $token_user_limit_value ) ? trim( (string) $token_user_limit_value ) : '' );
    $token_guest_summary_value = ( $token_guest_limit_value !== '' ? $token_guest_limit_value : __( 'Unlimited', 'gpt3-ai-content-generator' ) );
    $token_user_summary_value = ( $token_user_limit_value !== '' ? $token_user_limit_value : __( 'Unlimited', 'gpt3-ai-content-generator' ) );
    $quota_summary_text = ( $token_limit_mode_value === 'role_based' ? __( 'Role-based quota', 'gpt3-ai-content-generator' ) : sprintf( 
        /* translators: 1: guest token limit summary, 2: user token limit summary. */
        __( 'Guests %1$s · Users %2$s', 'gpt3-ai-content-generator' ),
        $token_guest_summary_value,
        $token_user_summary_value
     ) );
    $limits_summary_text = $quota_summary_text;
    $limits_summary_fallback = $limits_summary_text;
    $rules_count = 0;
    if ( $triggers_available ) {
        $saved_triggers_json = $active_bot_settings['triggers_json'] ?? '[]';
        if ( is_array( $saved_triggers_json ) ) {
            $rules_count = count( $saved_triggers_json );
        } elseif ( is_string( $saved_triggers_json ) && $saved_triggers_json !== '' ) {
            $decoded_rules = json_decode( $saved_triggers_json, true );
            if ( is_array( $decoded_rules ) ) {
                if ( isset( $decoded_rules['triggers'] ) && is_array( $decoded_rules['triggers'] ) ) {
                    $rules_count = count( $decoded_rules['triggers'] );
                } elseif ( isset( $decoded_rules['rules'] ) && is_array( $decoded_rules['rules'] ) ) {
                    $rules_count = count( $decoded_rules['rules'] );
                } else {
                    $rules_count = count( $decoded_rules );
                }
            }
        }
    }
    $rules_summary_fallback = '';
    $rules_summary_text = ( $rules_count > 0 ? sprintf( 
        /* translators: %d: number of chatbot rules. */
        _n(
            '%d rule',
            '%d rules',
            $rules_count,
            'gpt3-ai-content-generator'
        ),
        $rules_count
     ) : $rules_summary_fallback );
    ?>
                                <div class="aipkit_accordion_section" data-aipkit-accordion="chatbot">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'         => 'dashicons-format-chat',
        'title'        => __( 'General', 'gpt3-ai-content-generator' ),
        'hint_attr'    => 'data-aipkit-chatbot-summary',
        'hint_default' => $chatbot_summary_fallback,
        'hint_text'    => $chatbot_summary_text,
    ] );
    ?>
                                <div class="aipkit_accordion_body" data-aipkit-settings-panel="chatbot" hidden>
                                    <div class="aipkit_builder_field">
                                        <div class="aipkit_builder_ai_model aipkit_chatbot_tab_model_config">
                                            <?php 
    $is_next_layout = true;
    include __DIR__ . '/partials/ai-config/provider-model.php';
    ?>
                                        </div>
                                    </div>
                                    <div class="aipkit_builder_field">
                                        <label for="aipkit_bot_<?php 
    echo esc_attr( $initial_active_bot_id );
    ?>_instructions" class="aipkit_builder_label">
                                            <?php 
    esc_html_e( 'Instructions', 'gpt3-ai-content-generator' );
    ?>
                                        </label>
                                        <div class="aipkit_builder_textarea_wrap">
                                            <textarea
                                                id="aipkit_bot_<?php 
    echo esc_attr( $initial_active_bot_id );
    ?>_instructions"
                                                name="instructions"
                                                class="aipkit_builder_textarea aipkit_form-input"
                                                rows="5"
                                                placeholder="<?php 
    esc_attr_e( 'e.g., You are a helpful AI Assistant. Please be friendly.', 'gpt3-ai-content-generator' );
    ?>"
                                            ><?php 
    echo esc_textarea( $active_bot_instructions );
    ?></textarea>
                                            <button
                                                type="button"
                                                class="aipkit_builder_icon_btn aipkit_builder_textarea_expand aipkit_builder_instructions_expand"
                                                aria-label="<?php 
    esc_attr_e( 'Expand instructions editor', 'gpt3-ai-content-generator' );
    ?>"
                                            >
                                                <span class="dashicons dashicons-editor-expand"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="aipkit_builder_field aipkit_chatbot_response_settings">
                                        <?php 
    include __DIR__ . '/partials/ai-config/behavior-settings.php';
    ?>
                                    </div>
                                </div>
                                </div>
                            <div class="aipkit_accordion_section" data-aipkit-accordion="context_behavior">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'         => 'dashicons-admin-settings',
        'title'        => __( 'Knowledge', 'gpt3-ai-content-generator' ),
        'hint_attr'    => 'data-aipkit-context-summary',
        'hint_default' => $context_summary_fallback,
        'hint_text'    => $context_summary_text,
    ] );
    ?>
                                <div class="aipkit_accordion_body" data-aipkit-settings-panel="context" hidden>
                                    <div class="aipkit_accordion_subsection">
                                        <?php 
    include __DIR__ . '/partials/ai-config/context-settings.php';
    ?>
                                        <?php 
    include __DIR__ . '/partials/ai-config/training-settings.php';
    ?>
                                    </div>
                                </div>
                            </div>
                            <div class="aipkit_accordion_section" data-aipkit-accordion="tools">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'         => 'dashicons-admin-tools',
        'title'        => __( 'Tools', 'gpt3-ai-content-generator' ),
        'hint_attr'    => 'data-aipkit-tools-summary',
        'hint_default' => $tools_summary_fallback,
        'hint_text'    => $tools_summary_text,
    ] );
    ?>
                                <div class="aipkit_accordion_body" data-aipkit-settings-panel="tools" hidden>
                                    <?php 
    include __DIR__ . '/partials/ai-config/tools-settings.php';
    ?>
                                </div>
                            </div>
                            <div class="aipkit_accordion_section" data-aipkit-accordion="interface">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'         => 'dashicons-admin-appearance',
        'title'        => __( 'Interface', 'gpt3-ai-content-generator' ),
        'hint_attr'    => 'data-aipkit-interface-summary',
        'hint_default' => $interface_summary_fallback,
        'hint_text'    => $interface_summary_text,
    ] );
    ?>
                                <div class="aipkit_accordion_body" data-aipkit-settings-panel="appearance" hidden>
                                    <?php 
    include __DIR__ . '/partials/ai-config/appearance-settings.php';
    ?>
                                </div>
                            </div>
                            <div
                                class="aipkit_accordion_section"
                                data-aipkit-accordion="popup"
                                data-aipkit-popup-options
                                <?php 
    echo ( $popup_enabled === '1' ? '' : 'hidden' );
    ?>
                            >
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'         => 'dashicons-format-chat',
        'title'        => __( 'Popup', 'gpt3-ai-content-generator' ),
        'hint_attr'    => 'data-aipkit-popup-summary',
        'hint_default' => $popup_summary_fallback,
        'hint_text'    => $popup_summary_text,
    ] );
    ?>
                                <div class="aipkit_accordion_body" data-aipkit-settings-panel="popup" hidden>
                                    <?php 
    include __DIR__ . '/partials/ai-config/popup-settings.php';
    ?>
                                </div>
                            </div>
                            <div class="aipkit_accordion_section" data-aipkit-accordion="rules">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'              => 'dashicons-controls-repeat',
        'title'             => __( 'Rules', 'gpt3-ai-content-generator' ),
        'header_class'      => 'aipkit_builder_sheet_trigger',
        'sheet_title'       => __( 'Rules', 'gpt3-ai-content-generator' ),
        'sheet_description' => __( 'Create and manage rule-based automations for this chatbot.', 'gpt3-ai-content-generator' ),
        'sheet_content'     => 'triggers',
        'hint_attr'         => 'data-aipkit-rules-summary',
        'hint_default'      => $rules_summary_fallback,
        'hint_text'         => $rules_summary_text,
        'badge_label'       => ( !$is_pro_plan ? __( 'Pro', 'gpt3-ai-content-generator' ) : '' ),
    ] );
    ?>
                                <div class="aipkit_accordion_body aipkit_accordion_body--rules" data-aipkit-settings-panel="rules" hidden></div>
                            </div>
                            <div class="aipkit_accordion_section" data-aipkit-accordion="connected_apps">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'         => 'dashicons-share',
        'title'        => __( 'Connected Apps', 'gpt3-ai-content-generator' ),
        'hint_attr'    => 'data-aipkit-connected-apps-summary',
        'hint_default' => $connected_apps_summary_fallback,
        'hint_text'    => $connected_apps_summary_text,
        'badge_label'  => ( !$is_pro_plan ? __( 'Pro', 'gpt3-ai-content-generator' ) : '' ),
    ] );
    ?>
                                <div class="aipkit_accordion_body" data-aipkit-settings-panel="connected_apps" hidden>
                                    <?php 
    if ( $is_pro_plan ) {
        ?>
                                        <?php 
        $initial_connected_app_recipes = ( isset( $active_chatbot_connected_apps['recipes'] ) && is_array( $active_chatbot_connected_apps['recipes'] ) ? $active_chatbot_connected_apps['recipes'] : [] );
        ?>
                                        <div class="aipkit_chatbot_connected_apps_panel">
                                            <div class="aipkit_chatbot_connected_apps_intro">
                                                <div class="aipkit_chatbot_connected_apps_actions">
                                                    <a
                                                        href="<?php 
        echo esc_url( $connected_apps_manage_url );
        ?>"
                                                        class="button button-primary aipkit_btn aipkit_btn-primary"
                                                    >
                                                        <?php 
        esc_html_e( 'Manage in Apps', 'gpt3-ai-content-generator' );
        ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="aipkit_chatbot_connected_apps_list" data-aipkit-chatbot-connected-apps-list>
                                                <?php 
        $render_chatbot_connected_apps_cards( $active_chatbot_connected_apps );
        ?>
                                            </div>
                                            <p
                                                class="aipkit_chatbot_connected_apps_empty"
                                                data-aipkit-chatbot-connected-apps-empty
                                                <?php 
        echo ( !empty( $initial_connected_app_recipes ) ? 'hidden' : '' );
        ?>
                                            >
                                                <?php 
        esc_html_e( 'No Connected Apps yet for this chatbot.', 'gpt3-ai-content-generator' );
        ?>
                                            </p>
                                        </div>
                                    <?php 
    } else {
        ?>
                                        <div class="aipkit_chatbot_connected_apps_upsell">
                                            <p class="aipkit_chatbot_connected_apps_intro_text">
                                                <?php 
        esc_html_e( 'Send chatbot sessions, questions, responses, and feedback to Slack, HubSpot, Notion, Pipedrive, Make, and n8n.', 'gpt3-ai-content-generator' );
        ?>
                                            </p>
                                            <div class="aipkit_chatbot_connected_apps_logo_grid" aria-label="<?php 
        esc_attr_e( 'Supported app destinations', 'gpt3-ai-content-generator' );
        ?>">
                                                <?php 
        foreach ( $connected_apps_supported_destinations as $connected_app_destination ) {
            ?>
                                                    <span
                                                        class="aipkit_chatbot_connected_apps_logo_item"
                                                        title="<?php 
            echo esc_attr( (string) $connected_app_destination['name'] );
            ?>"
                                                    >
                                                        <img
                                                            class="aipkit_chatbot_connected_apps_logo"
                                                            src="<?php 
            echo esc_url( (string) $connected_app_destination['logo_url'] );
            ?>"
                                                            alt="<?php 
            echo esc_attr( (string) $connected_app_destination['name'] );
            ?>"
                                                            loading="lazy"
                                                            decoding="async"
                                                        />
                                                    </span>
                                                <?php 
        }
        ?>
                                                <a
                                                    href="<?php 
        echo esc_url( $pricing_url );
        ?>"
                                                    class="aipkit_btn aipkit_btn-primary aipkit_chatbot_connected_apps_grid_cta"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <span class="aipkit_btn-text"><?php 
        esc_html_e( 'Upgrade', 'gpt3-ai-content-generator' );
        ?></span>
                                                </a>
                                            </div>
                                        </div>
                                    <?php 
    }
    ?>
                                </div>
                            </div>
                            <div class="aipkit_accordion_section" data-aipkit-accordion="limits">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'         => 'dashicons-chart-bar',
        'title'        => __( 'Limits', 'gpt3-ai-content-generator' ),
        'hint_attr'    => 'data-aipkit-limits-summary',
        'hint_default' => $limits_summary_fallback,
        'hint_text'    => $limits_summary_text,
    ] );
    ?>
                                <div class="aipkit_accordion_body" data-aipkit-settings-panel="limits" hidden>
                                    <?php 
    include __DIR__ . '/partials/ai-config/limits-settings.php';
    ?>
                                </div>
                            </div>
                            <div class="aipkit_accordion_section" data-aipkit-accordion="actions">
                                <?php 
    $render_chatbot_accordion_header( [
        'icon'  => 'dashicons-admin-generic',
        'title' => __( 'Actions', 'gpt3-ai-content-generator' ),
    ] );
    ?>
                                <div class="aipkit_accordion_body aipkit_accordion_body--actions" hidden>
                                    <div class="aipkit_accordion_actions_row">
                                        <button
                                            type="button"
                                            class="aipkit_popover_footer_btn aipkit_popover_footer_btn--secondary"
                                            data-action="duplicate"
                                            title="<?php 
    esc_attr_e( 'Duplicate chatbot', 'gpt3-ai-content-generator' );
    ?>"
                                        >
                                            <?php 
    esc_html_e( 'Duplicate', 'gpt3-ai-content-generator' );
    ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="aipkit_popover_footer_btn aipkit_popover_footer_btn--secondary"
                                            data-action="reset"
                                            title="<?php 
    esc_attr_e( 'Reset chatbot settings', 'gpt3-ai-content-generator' );
    ?>"
                                        >
                                            <?php 
    esc_html_e( 'Reset', 'gpt3-ai-content-generator' );
    ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="aipkit_popover_footer_btn aipkit_popover_footer_btn--danger"
                                            data-action="delete"
                                            <?php 
    echo ( $is_default_active ? 'disabled aria-disabled="true"' : '' );
    ?>
                                            title="<?php 
    echo esc_attr( ( $is_default_active ? __( 'Default bot cannot be deleted.', 'gpt3-ai-content-generator' ) : __( 'Delete chatbot', 'gpt3-ai-content-generator' ) ) );
    ?>"
                                        >
                                            <?php 
    esc_html_e( 'Delete', 'gpt3-ai-content-generator' );
    ?>
                                        </button>
                                    </div>
                                    </div>
                                </div>
                            </section>
                                </div>
                            </section>
                        <?php 
}
?>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="aipkit_chatbot-preview-column aipkit_chatbot_builder_right">
                <section class="aipkit_chatbot_shell aipkit_chatbot_shell--preview">
                    <div class="aipkit_chatbot_shell_header aipkit_chatbot_shell_header--preview">
                        <div class="aipkit_chatbot_shell_intro">
                            <div class="aipkit_chatbot_shell_title"><?php 
esc_html_e( 'Preview', 'gpt3-ai-content-generator' );
?></div>
                        </div>
                    </div>
                    <div class="aipkit_chatbot_shell_body aipkit_chatbot_shell_body--preview">
                        <div class="aipkit_builder_preview_frame">
                            <div id="aipkit_admin_chat_preview_container">
                                <p class="aipkit_preview_placeholder" data-key="<?php 
echo esc_attr( $preview_placeholder_key );
?>">
                                    <?php 
echo esc_html( $preview_placeholder_text );
?>
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <?php 
if ( $active_bot_post ) {
    ?>
        <div
            class="aipkit_popover_hint_flyout"
            id="aipkit_popup_hint_flyout"
            aria-hidden="true"
            role="dialog"
        >
            <div class="aipkit_popover_flyout_header">
                <span class="aipkit_popover_flyout_title">
                    <?php 
    esc_html_e( 'Launcher hint', 'gpt3-ai-content-generator' );
    ?>
                </span>
                <button
                    type="button"
                    class="aipkit_popover_flyout_close aipkit_popup_hint_flyout_close"
                    aria-label="<?php 
    esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
    ?>"
                >
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aipkit_popover_flyout_body aipkit_popover_hint_body">
                <div class="aipkit_popover_option_row aipkit_popup_hint_extra" <?php 
    echo ( $popup_label_enabled === '1' ? '' : 'hidden' );
    ?>>
                    <div class="aipkit_popover_option_main">
                        <span
                            class="aipkit_popover_option_label"
                            tabindex="0"

                        >
                            <?php 
    esc_html_e( 'Dismissible', 'gpt3-ai-content-generator' );
    ?>
                        </span>
                        <select
                            id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_dismissible_deploy"
                            name="popup_label_dismissible"
                            class="aipkit_popover_option_select aipkit_popover_option_input--framed aipkit_popup_hint_toggle_select"
                        >
                            <option value="1" <?php 
    selected( $popup_label_dismissible, '1' );
    ?>><?php 
    esc_html_e( 'Yes', 'gpt3-ai-content-generator' );
    ?></option>
                            <option value="0" <?php 
    selected( $popup_label_dismissible, '0' );
    ?>><?php 
    esc_html_e( 'No', 'gpt3-ai-content-generator' );
    ?></option>
                        </select>
                    </div>
                </div>
                <div class="aipkit_popover_option_row aipkit_popup_hint_extra" <?php 
    echo ( $popup_label_enabled === '1' ? '' : 'hidden' );
    ?>>
                    <div class="aipkit_popover_option_main">
                        <span
                            class="aipkit_popover_option_label"
                            tabindex="0"

                        >
                            <?php 
    esc_html_e( 'Show on Desktop', 'gpt3-ai-content-generator' );
    ?>
                        </span>
                        <select
                            id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_show_on_desktop_deploy"
                            name="popup_label_show_on_desktop"
                            class="aipkit_popover_option_select aipkit_popover_option_input--framed aipkit_popup_hint_toggle_select"
                        >
                            <option value="1" <?php 
    selected( $popup_label_show_on_desktop, '1' );
    ?>><?php 
    esc_html_e( 'Yes', 'gpt3-ai-content-generator' );
    ?></option>
                            <option value="0" <?php 
    selected( $popup_label_show_on_desktop, '0' );
    ?>><?php 
    esc_html_e( 'No', 'gpt3-ai-content-generator' );
    ?></option>
                        </select>
                    </div>
                </div>
                <div class="aipkit_popover_option_row aipkit_popup_hint_extra" <?php 
    echo ( $popup_label_enabled === '1' ? '' : 'hidden' );
    ?>>
                    <div class="aipkit_popover_option_main">
                        <span
                            class="aipkit_popover_option_label"
                            tabindex="0"

                        >
                            <?php 
    esc_html_e( 'Show on Mobile', 'gpt3-ai-content-generator' );
    ?>
                        </span>
                        <select
                            id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_show_on_mobile_deploy"
                            name="popup_label_show_on_mobile"
                            class="aipkit_popover_option_select aipkit_popover_option_input--framed aipkit_popup_hint_toggle_select"
                        >
                            <option value="1" <?php 
    selected( $popup_label_show_on_mobile, '1' );
    ?>><?php 
    esc_html_e( 'Yes', 'gpt3-ai-content-generator' );
    ?></option>
                            <option value="0" <?php 
    selected( $popup_label_show_on_mobile, '0' );
    ?>><?php 
    esc_html_e( 'No', 'gpt3-ai-content-generator' );
    ?></option>
                        </select>
                    </div>
                </div>
                <div class="aipkit_popup_hint_conditional_row" <?php 
    echo ( $popup_label_enabled === '1' ? '' : 'hidden' );
    ?>>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <span
                                class="aipkit_popover_option_label"
                                tabindex="0"

                            >
                                <?php 
    esc_html_e( 'Hint Text', 'gpt3-ai-content-generator' );
    ?>
                            </span>
                            <input
                                type="text"
                                id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_text_deploy"
                                name="popup_label_text"
                                class="aipkit_popover_option_input aipkit_popover_option_input--wide aipkit_popover_option_input--framed"
                                value="<?php 
    echo esc_attr( $popup_label_text );
    ?>"
                                maxlength="60"
                                placeholder="<?php 
    esc_attr_e( 'Need help? Ask me!', 'gpt3-ai-content-generator' );
    ?>"
                            >
                        </div>
                    </div>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <span
                                class="aipkit_popover_option_label"
                                tabindex="0"

                            >
                                <?php 
    esc_html_e( 'Show Mode', 'gpt3-ai-content-generator' );
    ?>
                            </span>
                            <select
                                id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_mode_deploy"
                                name="popup_label_mode"
                                class="aipkit_popover_option_select"
                            >
                                <option value="on_delay" <?php 
    selected( $popup_label_mode, 'on_delay' );
    ?>><?php 
    esc_html_e( 'On delay', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="always" <?php 
    selected( $popup_label_mode, 'always' );
    ?>><?php 
    esc_html_e( 'Always (immediate)', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="until_open" <?php 
    selected( $popup_label_mode, 'until_open' );
    ?>><?php 
    esc_html_e( 'Until chat is opened', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="until_dismissed" <?php 
    selected( $popup_label_mode, 'until_dismissed' );
    ?>><?php 
    esc_html_e( 'Until hint is dismissed', 'gpt3-ai-content-generator' );
    ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <span
                                class="aipkit_popover_option_label"
                                tabindex="0"

                            >
                                <?php 
    esc_html_e( 'Hint Size', 'gpt3-ai-content-generator' );
    ?>
                            </span>
                            <select
                                id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_size_deploy"
                                name="popup_label_size"
                                class="aipkit_popover_option_select"
                            >
                                <option value="small" <?php 
    selected( $popup_label_size, 'small' );
    ?>><?php 
    esc_html_e( 'Small', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="medium" <?php 
    selected( $popup_label_size, 'medium' );
    ?>><?php 
    esc_html_e( 'Medium', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="large" <?php 
    selected( $popup_label_size, 'large' );
    ?>><?php 
    esc_html_e( 'Large', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="xlarge" <?php 
    selected( $popup_label_size, 'xlarge' );
    ?>><?php 
    esc_html_e( 'Extra Large', 'gpt3-ai-content-generator' );
    ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <span
                                class="aipkit_popover_option_label"
                                tabindex="0"

                            >
                                <?php 
    esc_html_e( 'Delay (sec)', 'gpt3-ai-content-generator' );
    ?>
                            </span>
                            <input
                                type="number"
                                id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_delay_seconds_deploy"
                                name="popup_label_delay_seconds"
                                class="aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact"
                                min="0"
                                step="1"
                                value="<?php 
    echo esc_attr( $popup_label_delay_seconds );
    ?>"
                            >
                        </div>
                    </div>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <span
                                class="aipkit_popover_option_label"
                                tabindex="0"

                            >
                                <?php 
    esc_html_e( 'Auto-hide (sec)', 'gpt3-ai-content-generator' );
    ?>
                            </span>
                            <input
                                type="number"
                                id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_auto_hide_seconds_deploy"
                                name="popup_label_auto_hide_seconds"
                                class="aipkit_popover_option_input aipkit_popover_option_input--framed aipkit_popover_option_input--compact"
                                min="0"
                                step="1"
                                value="<?php 
    echo esc_attr( $popup_label_auto_hide_seconds );
    ?>"
                            >
                        </div>
                    </div>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <span
                                class="aipkit_popover_option_label"
                                tabindex="0"

                            >
                                <?php 
    esc_html_e( 'Frequency', 'gpt3-ai-content-generator' );
    ?>
                            </span>
                            <select
                                id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_frequency_deploy"
                                name="popup_label_frequency"
                                class="aipkit_popover_option_select"
                            >
                                <option value="once_per_visitor" <?php 
    selected( $popup_label_frequency, 'once_per_visitor' );
    ?>><?php 
    esc_html_e( 'Once per visitor', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="once_per_session" <?php 
    selected( $popup_label_frequency, 'once_per_session' );
    ?>><?php 
    esc_html_e( 'Once per session', 'gpt3-ai-content-generator' );
    ?></option>
                                <option value="always" <?php 
    selected( $popup_label_frequency, 'always' );
    ?>><?php 
    esc_html_e( 'Always', 'gpt3-ai-content-generator' );
    ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <span
                                class="aipkit_popover_option_label"
                                tabindex="0"

                            >
                                <?php 
    esc_html_e( 'Version', 'gpt3-ai-content-generator' );
    ?>
                            </span>
                            <input
                                type="text"
                                id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_popup_label_version_deploy"
                                name="popup_label_version"
                                class="aipkit_popover_option_input aipkit_popover_option_input--framed"
                                value="<?php 
    echo esc_attr( $popup_label_version );
    ?>"
                                placeholder="v1"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="aipkit_popover_flyout_footer">
                <span class="aipkit_popover_flyout_footer_text">
                    <?php 
    esc_html_e( 'Need help? Read the docs.', 'gpt3-ai-content-generator' );
    ?>
                </span>
                <a
                    class="aipkit_popover_flyout_footer_link"
                    href="<?php 
    echo esc_url( 'https://docs.aipower.org/chatbots#popup' );
    ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php 
    esc_html_e( 'Documentation', 'gpt3-ai-content-generator' );
    ?>
                </a>
            </div>
        </div>
    <?php 
}
?>
    <?php 
if ( $active_bot_post ) {
    ?>
        <div
            class="aipkit_popover_starters_flyout"
            id="aipkit_starters_flyout"
            aria-hidden="true"
            role="dialog"
        >
        <div class="aipkit_popover_flyout_header">
            <span class="aipkit_popover_flyout_title">
                <?php 
    esc_html_e( 'Conversation starters', 'gpt3-ai-content-generator' );
    ?>
            </span>
            <button
                type="button"
                class="aipkit_popover_flyout_close aipkit_starters_flyout_close"
                aria-label="<?php 
    esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
    ?>"
            >
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aipkit_popover_flyout_body aipkit_popover_starters_body">
            <?php 
    $bot_id = $initial_active_bot_id;
    $bot_settings = $active_bot_settings;
    $conversation_starters = $bot_settings['conversation_starters'] ?? [];
    $conversation_starters_text = implode( "\n", $conversation_starters );
    ?>
            <div class="aipkit_popover_options_list">
                <div class="aipkit_popover_option_row">
                    <div class="aipkit_popover_option_main aipkit_popover_option_main--stacked">
                        <label
                            class="aipkit_popover_option_label"
                            for="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_conversation_starters"

                        >
                            <?php 
    esc_html_e( 'Conversation starters (max 6)', 'gpt3-ai-content-generator' );
    ?>
                        </label>
                        <textarea
                            id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_conversation_starters"
                            name="conversation_starters"
                            class="aipkit_popover_option_textarea"
                            rows="4"
                        ><?php 
    echo esc_textarea( $conversation_starters_text );
    ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="aipkit_popover_flyout_footer">
            <span class="aipkit_popover_flyout_footer_text">
                <?php 
    esc_html_e( 'Need help? Read the docs.', 'gpt3-ai-content-generator' );
    ?>
            </span>
                <a
                    class="aipkit_popover_flyout_footer_link"
                    href="<?php 
    echo esc_url( 'https://docs.aipower.org/chatbots#conversation-starters' );
    ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                <?php 
    esc_html_e( 'Documentation', 'gpt3-ai-content-generator' );
    ?>
                </a>
            </div>
        </div>
        <?php 
    if ( $consent_feature_available ) {
        ?>
            <div
                class="aipkit_popover_consent_flyout"
                id="aipkit_consent_flyout"
                aria-hidden="true"
                role="dialog"
            >
                <div class="aipkit_popover_flyout_header">
                    <span class="aipkit_popover_flyout_title">
                        <?php 
        esc_html_e( 'Consent notice', 'gpt3-ai-content-generator' );
        ?>
                    </span>
                    <button
                        type="button"
                        class="aipkit_popover_flyout_close aipkit_consent_flyout_close"
                        aria-label="<?php 
        esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
        ?>"
                    >
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="aipkit_popover_flyout_body aipkit_popover_consent_body">
                    <?php 
        $bot_id = $initial_active_bot_id;
        $bot_settings = $active_bot_settings;
        $consent_title = $bot_settings['consent_title'] ?? __( 'Consent Required', 'gpt3-ai-content-generator' );
        $consent_message = $bot_settings['consent_message'] ?? __( 'Before starting the conversation, please agree to our Terms of Service and Privacy Policy.', 'gpt3-ai-content-generator' );
        $consent_button = $bot_settings['consent_button'] ?? __( 'I Agree', 'gpt3-ai-content-generator' );
        ?>
                    <div class="aipkit_popover_options_list">
                        <div class="aipkit_popover_option_row">
                            <div class="aipkit_popover_option_main">
                                <label
                                    class="aipkit_popover_option_label"
                                    for="aipkit_bot_<?php 
        echo esc_attr( $bot_id );
        ?>_consent_title"
                                >
                                    <?php 
        esc_html_e( 'Title', 'gpt3-ai-content-generator' );
        ?>
                                </label>
                                <input
                                    type="text"
                                    id="aipkit_bot_<?php 
        echo esc_attr( $bot_id );
        ?>_consent_title"
                                    name="consent_title"
                                    class="aipkit_popover_option_input aipkit_popover_option_input--framed"
                                    value="<?php 
        echo esc_attr( $consent_title );
        ?>"
                                    placeholder="<?php 
        esc_attr_e( 'Consent Required', 'gpt3-ai-content-generator' );
        ?>"
                                    autocomplete="off"
                                    data-lpignore="true"
                                    data-1p-ignore="true"
                                    data-form-type="other"
                                />
                            </div>
                        </div>
                        <div class="aipkit_popover_option_row">
                            <div class="aipkit_popover_option_main">
                                <label
                                    class="aipkit_popover_option_label"
                                    for="aipkit_bot_<?php 
        echo esc_attr( $bot_id );
        ?>_consent_button"
                                >
                                    <?php 
        esc_html_e( 'Button label', 'gpt3-ai-content-generator' );
        ?>
                                </label>
                                <input
                                    type="text"
                                    id="aipkit_bot_<?php 
        echo esc_attr( $bot_id );
        ?>_consent_button"
                                    name="consent_button"
                                    class="aipkit_popover_option_input aipkit_popover_option_input--framed"
                                    value="<?php 
        echo esc_attr( $consent_button );
        ?>"
                                    placeholder="<?php 
        esc_attr_e( 'I Agree', 'gpt3-ai-content-generator' );
        ?>"
                                    autocomplete="off"
                                    data-lpignore="true"
                                    data-1p-ignore="true"
                                    data-form-type="other"
                                />
                            </div>
                        </div>
                        <div class="aipkit_popover_option_row">
                            <div class="aipkit_popover_option_main aipkit_popover_option_main--stacked">
                                <label
                                    class="aipkit_popover_option_label"
                                    for="aipkit_bot_<?php 
        echo esc_attr( $bot_id );
        ?>_consent_message"
                                >
                                    <?php 
        esc_html_e( 'Message', 'gpt3-ai-content-generator' );
        ?>
                                </label>
                                <textarea
                                    id="aipkit_bot_<?php 
        echo esc_attr( $bot_id );
        ?>_consent_message"
                                    name="consent_message"
                                    class="aipkit_popover_option_textarea"
                                    rows="4"
                                ><?php 
        echo esc_textarea( $consent_message );
        ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="aipkit_popover_flyout_footer">
                    <span class="aipkit_popover_flyout_footer_text">
                        <?php 
        esc_html_e( 'Need help? Read the docs.', 'gpt3-ai-content-generator' );
        ?>
                    </span>
                    <a
                        class="aipkit_popover_flyout_footer_link"
                        href="<?php 
        echo esc_url( 'https://docs.aipower.org/chatbots#consent-notice' );
        ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?php 
        esc_html_e( 'Documentation', 'gpt3-ai-content-generator' );
        ?>
                    </a>
                </div>
            </div>
        <?php 
    }
    ?>
    <?php 
}
?>
    <div
        class="aipkit-modal-overlay aipkit_builder_instructions_modal"
        id="aipkit_builder_instructions_modal"
        aria-hidden="true"
    >
        <div
            class="aipkit-modal-content"
            role="dialog"
            aria-modal="true"
            aria-labelledby="aipkit_builder_instructions_title"
            aria-describedby="aipkit_builder_instructions_description"
        >
            <div class="aipkit-modal-header">
                <div>
                    <h2 class="aipkit-modal-title" id="aipkit_builder_instructions_title">
                        <?php 
esc_html_e( 'Agent Instructions', 'gpt3-ai-content-generator' );
?>
                    </h2>
                    <p class="aipkit_builder_modal_subtitle" id="aipkit_builder_instructions_description">
                        <?php 
esc_html_e( 'Define how your agent should behave. Changes are saved automatically when you close this dialog.', 'gpt3-ai-content-generator' );
?>
                    </p>
                </div>
                <button
                    type="button"
                    class="aipkit-modal-close-btn aipkit_builder_instructions_close"
                    aria-label="<?php 
esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
?>"
                >
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aipkit-modal-body">
                <div class="aipkit_builder_field">
                    <textarea
                        id="aipkit_bot_<?php 
echo esc_attr( $initial_active_bot_id );
?>_instructions_modal"
                        class="aipkit_builder_textarea aipkit_builder_textarea_large aipkit_builder_instructions_modal_textarea"
                        rows="14"
                        aria-label="<?php 
esc_attr_e( 'Agent instructions', 'gpt3-ai-content-generator' );
?>"
                    ></textarea>
                </div>
                <div class="aipkit_builder_modal_meta">
                    <span class="aipkit_builder_char_count aipkit_builder_instructions_count">
                        <?php 
esc_html_e( '0 characters', 'gpt3-ai-content-generator' );
?>
                    </span>
                    <span class="aipkit_builder_key_hint">
                        <?php 
esc_html_e( 'Press ESC to close', 'gpt3-ai-content-generator' );
?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div
        class="aipkit_builder_sheet_overlay"
        id="aipkit_builder_sheet"
        aria-hidden="true"
    >
        <div
            class="aipkit_builder_sheet_panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="aipkit_builder_sheet_title"
            aria-describedby="aipkit_builder_sheet_description"
        >
            <div class="aipkit_builder_sheet_header">
                <div>
                    <div class="aipkit_builder_sheet_title_row">
                        <h3 class="aipkit_builder_sheet_title" id="aipkit_builder_sheet_title">
                            <?php 
esc_html_e( 'Sheet', 'gpt3-ai-content-generator' );
?>
                        </h3>
                    </div>
                    <p class="aipkit_builder_sheet_description" id="aipkit_builder_sheet_description">
                        <?php 
esc_html_e( 'Settings will appear here.', 'gpt3-ai-content-generator' );
?>
                    </p>
                </div>
                <button
                    type="button"
                    class="aipkit_builder_sheet_close"
                    aria-label="<?php 
esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
?>"
                >
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aipkit_builder_sheet_body">
                <div class="aipkit_builder_sheet_section" data-sheet="placeholder">
                    <p class="aipkit_builder_help_text">
                        <?php 
esc_html_e( 'This panel will contain the selected settings section.', 'gpt3-ai-content-generator' );
?>
                    </p>
                </div>
                <div class="aipkit_builder_sheet_section aipkit_accordion_body--rules" data-sheet="triggers" hidden>
                    <span class="aipkit_popover_status_inline aipkit_triggers_status" aria-live="polite"></span>
                    <?php 
if ( $triggers_available && $active_bot_post && $bot_id > 0 ) {
    ?>
                        <?php 
    $triggers_json = $active_bot_settings['triggers_json'] ?? '[]';
    $trigger_builder_view_path = ( defined( 'WPAICG_LIB_DIR' ) ? WPAICG_LIB_DIR . 'views/chatbot/partials/triggers/trigger-builder-main.php' : '' );
    if ( !empty( $trigger_builder_view_path ) && file_exists( $trigger_builder_view_path ) ) {
        include $trigger_builder_view_path;
    } else {
        echo '<p class="aipkit_builder_help_text">' . esc_html__( 'Rules builder UI is not available.', 'gpt3-ai-content-generator' ) . '</p>';
    }
    ?>
                        <textarea
                            id="aipkit_bot_<?php 
    echo esc_attr( $bot_id );
    ?>_triggers_json"
                            name="triggers_json"
                            class="aipkit_trigger_hidden_textarea"
                            aria-hidden="true"
                            tabindex="-1"
                        ><?php 
    echo esc_textarea( $triggers_json );
    ?></textarea>
                        <p class="aipkit_builder_help_text">
                            <?php 
    esc_html_e( 'Use the UI above to configure rules.', 'gpt3-ai-content-generator' );
    ?>
                            <a href="<?php 
    echo esc_url( 'https://docs.aipower.org/chatbots#rules' );
    ?>" target="_blank" rel="noopener noreferrer">
                                <?php 
    esc_html_e( 'Learn More', 'gpt3-ai-content-generator' );
    ?>
                            </a>
                        </p>
                    <?php 
} elseif ( $triggers_available ) {
    ?>
                        <p class="aipkit_builder_help_text">
                            <?php 
    esc_html_e( 'Create or select a chatbot to configure rules.', 'gpt3-ai-content-generator' );
    ?>
                        </p>
                    <?php 
} else {
    ?>
                        <div class="aipkit_rules_promo">
                            <div class="aipkit_rules_promo_hero">
                                <span class="aipkit_rules_promo_hero_icon" aria-hidden="true">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </span>
                                <div class="aipkit_rules_promo_hero_text">
                                    <h3 class="aipkit_rules_promo_hero_title"><?php 
    esc_html_e( 'Automate your chatbot with Rules', 'gpt3-ai-content-generator' );
    ?></h3>
                                    <p class="aipkit_rules_promo_hero_desc"><?php 
    esc_html_e( 'Build event-driven workflows that respond to messages, show forms, call webhooks, and more — no code required.', 'gpt3-ai-content-generator' );
    ?></p>
                                </div>
                            </div>

                            <div class="aipkit_rules_promo_how">
                                <div class="aipkit_rules_promo_step">
                                    <span class="aipkit_rules_promo_step_num">1</span>
                                    <span class="aipkit_rules_promo_step_label"><?php 
    esc_html_e( 'Choose a trigger', 'gpt3-ai-content-generator' );
    ?></span>
                                </div>
                                <span class="aipkit_rules_promo_step_arrow" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </span>
                                <div class="aipkit_rules_promo_step">
                                    <span class="aipkit_rules_promo_step_num">2</span>
                                    <span class="aipkit_rules_promo_step_label"><?php 
    esc_html_e( 'Set conditions', 'gpt3-ai-content-generator' );
    ?></span>
                                </div>
                                <span class="aipkit_rules_promo_step_arrow" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </span>
                                <div class="aipkit_rules_promo_step">
                                    <span class="aipkit_rules_promo_step_num">3</span>
                                    <span class="aipkit_rules_promo_step_label"><?php 
    esc_html_e( 'Pick an action', 'gpt3-ai-content-generator' );
    ?></span>
                                </div>
                            </div>

                            <div class="aipkit_rules_promo_grid" role="list">
                                <div class="aipkit_rules_promo_card" role="listitem">
                                    <span class="aipkit_rules_promo_card_icon aipkit_rules_promo_card_icon--triggers" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                    </span>
                                    <p class="aipkit_rules_promo_card_title"><?php 
    esc_html_e( 'Trigger Events', 'gpt3-ai-content-generator' );
    ?></p>
                                    <ul class="aipkit_rules_promo_card_list">
                                        <li><?php 
    esc_html_e( 'Message received', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Session started', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Form submitted', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'System error', 'gpt3-ai-content-generator' );
    ?></li>
                                    </ul>
                                </div>

                                <div class="aipkit_rules_promo_card" role="listitem">
                                    <span class="aipkit_rules_promo_card_icon aipkit_rules_promo_card_icon--actions" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m9 12 2 2 4-4"/></svg>
                                    </span>
                                    <p class="aipkit_rules_promo_card_title"><?php 
    esc_html_e( 'Action Types', 'gpt3-ai-content-generator' );
    ?></p>
                                    <ul class="aipkit_rules_promo_card_list">
                                        <li><?php 
    esc_html_e( 'Send reply / Show form', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Inject context', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Block message', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Set variable / Call webhook', 'gpt3-ai-content-generator' );
    ?></li>
                                    </ul>
                                </div>

                                <div class="aipkit_rules_promo_card" role="listitem">
                                    <span class="aipkit_rules_promo_card_icon aipkit_rules_promo_card_icon--conditions" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    </span>
                                    <p class="aipkit_rules_promo_card_title"><?php 
    esc_html_e( 'Condition Groups', 'gpt3-ai-content-generator' );
    ?></p>
                                    <ul class="aipkit_rules_promo_card_list">
                                        <li><?php 
    esc_html_e( 'Text / keyword matching', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'User role & auth state', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Page & context filters', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Regex & numeric operators', 'gpt3-ai-content-generator' );
    ?></li>
                                    </ul>
                                </div>

                                <div class="aipkit_rules_promo_card" role="listitem">
                                    <span class="aipkit_rules_promo_card_icon aipkit_rules_promo_card_icon--webhooks" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                    </span>
                                    <p class="aipkit_rules_promo_card_title"><?php 
    esc_html_e( 'External Workflows', 'gpt3-ai-content-generator' );
    ?></p>
                                    <ul class="aipkit_rules_promo_card_list">
                                        <li><?php 
    esc_html_e( 'Notify Slack on demo requests', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Send leads to Make / Zapier', 'gpt3-ai-content-generator' );
    ?></li>
                                        <li><?php 
    esc_html_e( 'Tickets, emails & follow-ups', 'gpt3-ai-content-generator' );
    ?></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="aipkit_rules_promo_cta">
                                <a
                                    class="aipkit_rules_promo_btn aipkit_rules_promo_btn--primary"
                                    href="<?php 
    echo esc_url( $pricing_url );
    ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                    <?php 
    esc_html_e( 'Upgrade', 'gpt3-ai-content-generator' );
    ?>
                                </a>
                                <a
                                    class="aipkit_rules_promo_btn aipkit_rules_promo_btn--secondary"
                                    href="<?php 
    echo esc_url( 'https://docs.aipower.org/chatbots#rules' );
    ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <?php 
    esc_html_e( 'Learn More', 'gpt3-ai-content-generator' );
    ?>
                                </a>
                            </div>
                        </div>
                    <?php 
}
?>
                </div>
                <div class="aipkit_builder_sheet_section" data-sheet="sources" hidden>
                    <?php 
if ( $active_bot_post ) {
    ?>
                        <div class="aipkit_sources_sheet_toolbar">
                            <div class="aipkit_sources_meta">
                                <span class="aipkit_sources_context_item aipkit_sources_context_item--provider">
                                    <span class="aipkit_sources_meta_label"><?php 
    esc_html_e( 'Provider', 'gpt3-ai-content-generator' );
    ?></span>
                                    <span class="aipkit_sources_meta_value" id="aipkit_sources_provider_label">—</span>
                                </span>
                                <span class="aipkit_sources_context_item aipkit_sources_context_item--store">
                                    <span class="aipkit_sources_meta_label"><?php 
    esc_html_e( 'Store', 'gpt3-ai-content-generator' );
    ?></span>
                                    <span class="aipkit_sources_meta_value" id="aipkit_sources_store_label">—</span>
                                </span>
                            </div>
                            <div class="aipkit_sources_toolbar">
                                <div class="aipkit_sources_toolbar_group aipkit_sources_toolbar_group--search">
                                    <input
                                        type="search"
                                        class="aipkit_popover_option_input aipkit_sources_search_input"
                                        placeholder="<?php 
    esc_attr_e( 'Search sources', 'gpt3-ai-content-generator' );
    ?>"
                                        aria-label="<?php 
    esc_attr_e( 'Search sources', 'gpt3-ai-content-generator' );
    ?>"
                                    >
                                </div>
                                <div class="aipkit_sources_toolbar_group aipkit_sources_toolbar_group--right">
                                    <select class="aipkit_popover_select aipkit_sources_filter_select" aria-label="<?php 
    esc_attr_e( 'Filter by status', 'gpt3-ai-content-generator' );
    ?>">
                                        <option value=""><?php 
    esc_html_e( 'All statuses', 'gpt3-ai-content-generator' );
    ?></option>
                                        <option value="indexed"><?php 
    esc_html_e( 'Trained', 'gpt3-ai-content-generator' );
    ?></option>
                                        <option value="processing"><?php 
    esc_html_e( 'Processing', 'gpt3-ai-content-generator' );
    ?></option>
                                        <option value="failed"><?php 
    esc_html_e( 'Failed', 'gpt3-ai-content-generator' );
    ?></option>
                                    </select>
                                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_refresh_btn">
                                        <?php 
    esc_html_e( 'Refresh', 'gpt3-ai-content-generator' );
    ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p id="aipkit_sources_status" class="aipkit_form-help"></p>
                        <div class="aipkit_data-table aipkit_sources_table">
                            <table>
                                <thead>
                                    <tr>
                                        <th><?php 
    esc_html_e( 'Status', 'gpt3-ai-content-generator' );
    ?></th>
                                        <th><?php 
    esc_html_e( 'Item', 'gpt3-ai-content-generator' );
    ?></th>
                                        <th><?php 
    esc_html_e( 'Type', 'gpt3-ai-content-generator' );
    ?></th>
                                        <th><?php 
    esc_html_e( 'Updated', 'gpt3-ai-content-generator' );
    ?></th>
                                        <th class="aipkit_actions_cell_header"><?php 
    esc_html_e( 'Actions', 'gpt3-ai-content-generator' );
    ?></th>
                                    </tr>
                                </thead>
                                <tbody id="aipkit_sources_table_body">
                                    <tr>
                                        <td colspan="5" class="aipkit_text-center">
                                            <?php 
    esc_html_e( 'Select a knowledge base to view sources.', 'gpt3-ai-content-generator' );
    ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="aipkit_sources_pagination" class="aipkit_logs_pagination_container"></div>
                    <?php 
} else {
    ?>
                        <p class="aipkit_builder_help_text">
                            <?php 
    esc_html_e( 'Select a bot to manage sources.', 'gpt3-ai-content-generator' );
    ?>
                        </p>
                    <?php 
}
?>
                </div>
            </div>
        </div>
    </div>

    <div
        class="aipkit-modal-overlay aipkit_sources_modal aipkit_builder_sources_editor_modal"
        id="aipkit_sources_editor_modal"
        aria-hidden="true"
    >
        <div
            class="aipkit-modal-content aipkit_sources_modal_panel aipkit_sources_editor_modal_content"
            role="dialog"
            aria-modal="true"
            aria-labelledby="aipkit_sources_editor_title"
            aria-describedby="aipkit_sources_editor_description"
        >
            <div class="aipkit-modal-header aipkit_sources_modal_header">
                <div class="aipkit_sources_modal_heading">
                    <h2 class="aipkit-modal-title aipkit_sources_modal_title" id="aipkit_sources_editor_title">
                        <?php 
esc_html_e( 'Edit source', 'gpt3-ai-content-generator' );
?>
                    </h2>
                    <p class="aipkit_builder_modal_subtitle aipkit_sources_modal_subtitle" id="aipkit_sources_editor_description">
                        <?php 
esc_html_e( 'Update the source text and save to retrain this entry.', 'gpt3-ai-content-generator' );
?>
                    </p>
                </div>
                <button
                    type="button"
                    class="aipkit-modal-close-btn aipkit_sources_modal_close aipkit_sources_editor_close"
                    aria-label="<?php 
esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
?>"
                >
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aipkit-modal-body aipkit_sources_modal_body">
                <div class="aipkit_builder_field">
                    <textarea
                        class="aipkit_builder_textarea aipkit_builder_textarea_large aipkit_sources_editor_textarea"
                        rows="10"
                        aria-label="<?php 
esc_attr_e( 'Source text', 'gpt3-ai-content-generator' );
?>"
                    ></textarea>
                </div>
            </div>
            <div class="aipkit_sources_modal_footer">
                <div class="aipkit_sources_modal_status" aria-hidden="true"></div>
                <div class="aipkit_builder_action_row aipkit_sources_editor_actions">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_editor_cancel">
                        <?php 
esc_html_e( 'Cancel', 'gpt3-ai-content-generator' );
?>
                    </button>
                    <button type="button" class="aipkit_btn aipkit_btn-primary aipkit_sources_editor_save">
                        <?php 
esc_html_e( 'Save & retrain', 'gpt3-ai-content-generator' );
?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="aipkit-modal-overlay aipkit_builder_sources_delete_modal"
        id="aipkit_sources_delete_modal"
        aria-hidden="true"
    >
        <div
            class="aipkit-modal-content"
            role="dialog"
            aria-modal="true"
            aria-labelledby="aipkit_sources_delete_title"
            aria-describedby="aipkit_sources_delete_description"
        >
            <div class="aipkit-modal-header">
                <div>
                    <h3 class="aipkit-modal-title" id="aipkit_sources_delete_title">
                        <?php 
esc_html_e( 'Delete source', 'gpt3-ai-content-generator' );
?>
                    </h3>
                    <p class="aipkit_builder_modal_subtitle" id="aipkit_sources_delete_description">
                        <?php 
esc_html_e( 'This cannot be undone. The source will be removed from your knowledge base.', 'gpt3-ai-content-generator' );
?>
                    </p>
                </div>
                <button
                    type="button"
                    class="aipkit-modal-close-btn aipkit_sources_delete_close"
                    aria-label="<?php 
esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
?>"
                >
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aipkit-modal-body">
                <div class="aipkit_builder_action_row">
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_delete_cancel">
                        <?php 
esc_html_e( 'Cancel', 'gpt3-ai-content-generator' );
?>
                    </button>
                    <button type="button" class="aipkit_btn aipkit_btn-danger aipkit_sources_delete_confirm">
                        <?php 
esc_html_e( 'Delete', 'gpt3-ai-content-generator' );
?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php 
if ( $active_bot_post ) {
    ?>
        <div
            class="aipkit-modal-overlay aipkit_builder_embed_modal"
            id="aipkit_builder_embed_modal"
            aria-hidden="true"
        >
            <div
                class="aipkit-modal-content"
                role="dialog"
                aria-modal="true"
                aria-labelledby="aipkit_builder_embed_title"
                aria-describedby="aipkit_builder_embed_description"
            >
                <div class="aipkit-modal-header">
                    <div>
                        <h3 class="aipkit-modal-title" id="aipkit_builder_embed_title">
                            <?php 
    esc_html_e( 'External Setup', 'gpt3-ai-content-generator' );
    ?>
                        </h3>
                        <p class="aipkit_builder_modal_subtitle" id="aipkit_builder_embed_description">
                            <?php 
    esc_html_e( 'Copy the snippet and set allowed domains for external websites.', 'gpt3-ai-content-generator' );
    ?>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="aipkit-modal-close-btn aipkit_builder_embed_close"
                        aria-label="<?php 
    esc_attr_e( 'Close', 'gpt3-ai-content-generator' );
    ?>"
                    >
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="aipkit-modal-body">
                    <?php 
    if ( $embed_anywhere_active ) {
        ?>
                        <div class="aipkit_builder_external_stack">
                            <div class="aipkit_builder_external_field">
                                <div class="aipkit_builder_external_field_header">
                                    <label
                                        class="aipkit_builder_external_label"
                                        for="aipkit_embed_code_<?php 
        echo esc_attr( $initial_active_bot_id );
        ?>"
                                    >
                                        <?php 
        esc_html_e( 'Embed code', 'gpt3-ai-content-generator' );
        ?>
                                    </label>
                                    <button
                                        type="button"
                                        class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_copy_embed_code_btn"
                                        data-target="aipkit_embed_code_<?php 
        echo esc_attr( $initial_active_bot_id );
        ?>"
                                    >
                                        <?php 
        esc_html_e( 'Copy code', 'gpt3-ai-content-generator' );
        ?>
                                    </button>
                                </div>
                                <textarea
                                    id="aipkit_embed_code_<?php 
        echo esc_attr( $initial_active_bot_id );
        ?>"
                                    class="aipkit_builder_external_textarea aipkit_builder_external_textarea--code"
                                    readonly
                                ><?php 
        echo esc_textarea( $embed_code );
        ?></textarea>
                            </div>
                            <div class="aipkit_builder_external_field">
                                <label
                                    class="aipkit_builder_external_label"
                                    for="aipkit_embed_allowed_domains_<?php 
        echo esc_attr( $initial_active_bot_id );
        ?>"
                                >
                                    <?php 
        esc_html_e( 'Allowed domains (one URL per line)', 'gpt3-ai-content-generator' );
        ?>
                                </label>
                                <textarea
                                    id="aipkit_embed_allowed_domains_<?php 
        echo esc_attr( $initial_active_bot_id );
        ?>"
                                    name="embed_allowed_domains"
                                    class="aipkit_builder_external_textarea aipkit_builder_external_textarea--domains"
                                    placeholder="<?php 
        esc_attr_e( 'https://example.com', 'gpt3-ai-content-generator' );
        ?>"
                                ><?php 
        echo esc_textarea( $embed_allowed_domains );
        ?></textarea>
                            </div>
                        </div>
                    <?php 
    } else {
        ?>
                        <div class="aipkit_embed_promo">
                            <div class="aipkit_embed_promo_hero">
                                <span class="aipkit_embed_promo_hero_icon" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                </span>
                                <div class="aipkit_embed_promo_hero_text">
                                    <h4 class="aipkit_embed_promo_hero_title"><?php 
        esc_html_e( 'Embed anywhere with one snippet', 'gpt3-ai-content-generator' );
        ?></h4>
                                    <p class="aipkit_embed_promo_hero_desc"><?php 
        esc_html_e( 'Add your AI chatbot to any website — Shopify, Wix, static HTML, or your custom app — with a single script tag.', 'gpt3-ai-content-generator' );
        ?></p>
                                </div>
                            </div>

                            <div class="aipkit_embed_promo_steps">
                                <div class="aipkit_embed_promo_step">
                                    <span class="aipkit_embed_promo_step_icon aipkit_embed_promo_step_icon--copy" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    </span>
                                    <span class="aipkit_embed_promo_step_text"><?php 
        esc_html_e( 'Copy the embed snippet', 'gpt3-ai-content-generator' );
        ?></span>
                                </div>
                                <div class="aipkit_embed_promo_step">
                                    <span class="aipkit_embed_promo_step_icon aipkit_embed_promo_step_icon--paste" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    </span>
                                    <span class="aipkit_embed_promo_step_text"><?php 
        esc_html_e( 'Paste into your site\'s HTML', 'gpt3-ai-content-generator' );
        ?></span>
                                </div>
                                <div class="aipkit_embed_promo_step">
                                    <span class="aipkit_embed_promo_step_icon aipkit_embed_promo_step_icon--secure" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    </span>
                                    <span class="aipkit_embed_promo_step_text"><?php 
        esc_html_e( 'Restrict with allowed domains', 'gpt3-ai-content-generator' );
        ?></span>
                                </div>
                            </div>

                            <div class="aipkit_embed_promo_code">
                                <div class="aipkit_embed_promo_code_bar">
                                    <span class="aipkit_embed_promo_code_dot"></span>
                                    <span class="aipkit_embed_promo_code_dot"></span>
                                    <span class="aipkit_embed_promo_code_dot"></span>
                                    <span class="aipkit_embed_promo_code_label">HTML</span>
                                </div>
                                <pre class="aipkit_embed_promo_code_body" aria-hidden="true">&lt;script src="https://your-site.com/aipkit-embed.js"
  data-bot-id="<?php 
        echo esc_html( ( $initial_active_bot_id ? $initial_active_bot_id : 'xxxxx' ) );
        ?>"
  async&gt;&lt;/script&gt;</pre>
                            </div>

                            <div class="aipkit_embed_promo_cta">
                                <a
                                    class="aipkit_embed_promo_btn aipkit_embed_promo_btn--primary"
                                    href="<?php 
        echo esc_url( $pricing_url );
        ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                    <?php 
        esc_html_e( 'Upgrade', 'gpt3-ai-content-generator' );
        ?>
                                </a>
                                <a
                                    class="aipkit_embed_promo_btn aipkit_embed_promo_btn--secondary"
                                    href="<?php 
        echo esc_url( $embed_docs_url );
        ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <?php 
        esc_html_e( 'Learn More', 'gpt3-ai-content-generator' );
        ?>
                                </a>
                            </div>
                        </div>
                    <?php 
    }
    ?>
                </div>
            </div>
        </div>
    <?php 
}
?>

    <?php 
if ( $active_bot_post && !$aipkit_hide_custom_theme ) {
    ?>
        <?php 
    $bot_id = $initial_active_bot_id;
    $bot_settings = $active_bot_settings;
    include __DIR__ . '/partials/appearance/custom-theme-flyout.php';
    ?>
    <?php 
}
?>
</div>

<div id="aipkit_available_bots_json" class="aipkit_hidden" data-bots="<?php 
$bot_list_for_filter = [];
if ( !empty( $all_bots_ordered_entries ) ) {
    foreach ( $all_bots_ordered_entries as $bot_entry_filter ) {
        $bot_list_for_filter[] = [
            'id'    => $bot_entry_filter['post']->ID,
            'title' => $bot_entry_filter['post']->post_title,
        ];
    }
}
echo esc_attr( wp_json_encode( $bot_list_for_filter ) );
?>"></div>

<script type="application/json" id="aipkit_chatbot_switch_state_json"><?php 
$inline_bot_switch_payload_json = wp_json_encode( $inline_bot_switch_payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* encoded payload for an application/json script tag.
echo ( $inline_bot_switch_payload_json !== false ? $inline_bot_switch_payload_json : '{}' );
?></script>

<div id="aipkit_google_tts_voices_json_main" class="aipkit_hidden" data-voices="<?php 
$google_voices_main = ( class_exists( '\\WPAICG\\Core\\Providers\\Google\\GoogleSettingsHandler' ) ? \WPAICG\Core\Providers\Google\GoogleSettingsHandler::get_synced_google_tts_voices() : [] );
echo esc_attr( wp_json_encode( ( $google_voices_main ?: [] ) ) );
?>"></div>
<?php 
$elevenlabs_voices_cached = AIPKit_Providers::get_elevenlabs_voices();
$elevenlabs_models_cached = AIPKit_Providers::get_elevenlabs_models();
foreach ( $all_bots_ordered_entries as $bot_entry_for_json ) {
    ?>
    <?php 
    $bot_id_for_json = $bot_entry_for_json['post']->ID;
    ?>
    <div
        id="aipkit_elevenlabs_voices_json_<?php 
    echo esc_attr( $bot_id_for_json );
    ?>"
        class="aipkit_hidden"
        data-voices="<?php 
    echo esc_attr( wp_json_encode( ( $elevenlabs_voices_cached ?: [] ) ) );
    ?>"
    ></div>
    <div
        id="aipkit_elevenlabs_models_json_<?php 
    echo esc_attr( $bot_id_for_json );
    ?>"
        class="aipkit_hidden"
        data-models="<?php 
    echo esc_attr( wp_json_encode( ( $elevenlabs_models_cached ?: [] ) ) );
    ?>"
    ></div>
<?php 
}
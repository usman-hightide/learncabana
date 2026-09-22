<?php
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only configures a shared partial.
$aipkit_post_settings_post_type_id = 'aipkit_task_cw_post_type';
$aipkit_post_settings_author_id = 'aipkit_task_cw_post_author';
$aipkit_post_settings_categories_id = 'aipkit_task_cw_post_categories';
$aipkit_post_settings_categories_panel_id = 'aipkit_task_cw_categories_panel';
$aipkit_post_settings_toc_id = 'aipkit_task_cw_generate_toc';
$aipkit_post_settings_slug_id = 'aipkit_task_cw_generate_seo_slug';
$aipkit_post_settings_post_type_helper = __('Choose the destination post type.', 'gpt3-ai-content-generator');
$aipkit_post_settings_include_author_login_attr = false;
$aipkit_post_settings_post_types = $cw_available_post_types;
$aipkit_post_settings_users = $cw_users_for_author;
$aipkit_post_settings_current_user_id = $cw_current_user_id;
$aipkit_post_settings_categories = $cw_wp_categories;

include WPAICG_PLUGIN_DIR . 'admin/views/modules/shared/post-settings-popover.php';

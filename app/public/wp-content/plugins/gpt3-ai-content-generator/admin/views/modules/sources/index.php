<?php
/**
 * AIPKit Sources Module - Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$is_pro_plan = class_exists('\\WPAICG\\aipkit_dashboard')
    ? \WPAICG\aipkit_dashboard::is_pro_plan()
    : false;
$upgrade_url = admin_url('admin.php?page=wpaicg-pricing');
$post_types_args = ['public' => true];
$all_selectable_post_types = get_post_types($post_types_args, 'objects');
$all_selectable_post_types = array_filter($all_selectable_post_types, function ($post_type_obj) {
    return $post_type_obj->name !== 'attachment';
});
?>
<div
    class="aipkit_container aipkit_sources_container"
    id="aipkit_sources_module_container"
>
    <div class="aipkit_container-header">
        <div class="aipkit_container-header-left">
            <div class="aipkit_sources_header_copy">
                <div class="aipkit_sources_header_title_row">
                    <div class="aipkit_container-title"><?php esc_html_e('Knowledge Base', 'gpt3-ai-content-generator'); ?></div>
                    <span id="aipkit_sources_status" class="aipkit_training_status aipkit_global_status_area" aria-live="polite"></span>
                </div>
                <p class="aipkit_sources_header_hint"><?php esc_html_e('Manage the data sources and vector stores that power your knowledge base.', 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div>
    <div class="aipkit_container-body" id="aipkit_sources_container_body">
        <div class="aipkit_chatbot_builder" data-aipkit-context="sources">
            <?php include WPAICG_PLUGIN_DIR . 'admin/views/shared/vector-store-nonce-fields.php'; ?>
            <div class="aipkit_sources_workspace_bar">
                <div class="aipkit_sources_workspace_tabs" role="tablist" aria-label="<?php esc_attr_e('Knowledge base sections', 'gpt3-ai-content-generator'); ?>">
                    <button type="button" class="aipkit_sources_workspace_tab is-active" id="aipkit_sources_data_tab" role="tab" aria-selected="true" aria-controls="aipkit_sources_data_panel" data-aipkit-sources-tab="data">
                        <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                        <?php esc_html_e('Data', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button type="button" class="aipkit_sources_workspace_tab" id="aipkit_sources_stores_tab" role="tab" aria-selected="false" aria-controls="aipkit_sources_stores_panel" data-aipkit-sources-tab="stores">
                        <span class="dashicons dashicons-archive" aria-hidden="true"></span>
                        <?php esc_html_e('Stores', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button type="button" class="aipkit_sources_workspace_tab" id="aipkit_sources_settings_tab" role="tab" aria-selected="false" aria-controls="aipkit_sources_settings_panel" data-aipkit-sources-tab="settings">
                        <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                        <?php esc_html_e('Settings', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>
                <div class="aipkit_sources_meta aipkit_sources_workspace_tools is-active" data-aipkit-sources-tools="data" aria-label="<?php esc_attr_e('Data tools', 'gpt3-ai-content-generator'); ?>">
                    <label class="screen-reader-text" for="aipkit_sources_provider_filter">
                        <?php esc_html_e('Provider filter', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select
                        id="aipkit_sources_provider_filter"
                        class="aipkit_sources_filter_select aipkit_sources_native_provider_select"
                    >
                        <option value=""><?php esc_html_e('All providers', 'gpt3-ai-content-generator'); ?></option>
                        <option value="openai"><?php esc_html_e('OpenAI', 'gpt3-ai-content-generator'); ?></option>
                        <option value="pinecone"><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
                        <option value="qdrant"><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
                        <option value="chroma"><?php esc_html_e('Chroma', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                    <label class="screen-reader-text" for="aipkit_sources_index_filter">
                        <?php esc_html_e('Index selection', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select
                        id="aipkit_sources_index_filter"
                        class="aipkit_popover_select aipkit_sources_filter_select"
                        data-aipkit-picker-title="<?php esc_attr_e('Index', 'gpt3-ai-content-generator'); ?>"
                        hidden
                        disabled
                    >
                        <option value=""><?php esc_html_e('All indexes', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                    <label class="screen-reader-text" for="aipkit_sources_search_input">
                        <?php esc_html_e('Search sources', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <span class="aipkit_sources_search_control">
                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                        <input
                            type="search"
                            id="aipkit_sources_search_input"
                            class="aipkit_sources_search_input"
                            placeholder="<?php esc_attr_e('Search', 'gpt3-ai-content-generator'); ?>"
                        >
                    </span>
                    <span class="aipkit_sources_meta_action">
                        <button
                            type="button"
                            class="aipkit_btn aipkit_btn-primary aipkit_btn-small"
                            id="aipkit_sources_training_toggle"
                            aria-expanded="false"
                            aria-controls="aipkit_sources_training_panel"
                        >
                            <?php esc_html_e('+ Add Data', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </span>
                </div>
                <div class="aipkit_sources_meta aipkit_sources_workspace_tools aipkit_sources_stores_meta" data-aipkit-sources-tools="stores" aria-label="<?php esc_attr_e('Store tools', 'gpt3-ai-content-generator'); ?>" hidden>
                    <label class="screen-reader-text" for="aipkit_sources_store_provider_filter">
                        <?php esc_html_e('Provider filter', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select
                        id="aipkit_sources_store_provider_filter"
                        class="aipkit_sources_filter_select aipkit_sources_native_provider_select"
                    >
                        <option value=""><?php esc_html_e('All providers', 'gpt3-ai-content-generator'); ?></option>
                        <option value="openai"><?php esc_html_e('OpenAI', 'gpt3-ai-content-generator'); ?></option>
                        <option value="pinecone"><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
                        <option value="qdrant"><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
                        <option value="chroma"><?php esc_html_e('Chroma', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                    <button
                        type="button"
                        id="aipkit_sources_stores_refresh"
                        class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_sources_sync_btn"
                    >
                        <span class="aipkit_btn_label"><?php esc_html_e('Refresh', 'gpt3-ai-content-generator'); ?></span>
                    </button>
                    <span class="aipkit_sources_meta_action">
                        <button type="button" id="aipkit_sources_create_store" class="aipkit_btn aipkit_btn-primary aipkit_btn-small">
                            <?php esc_html_e('Create Store', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </span>
                </div>
            </div>
            <div id="aipkit_sources_data_panel" class="aipkit_sources_workspace_panel is-active" role="tabpanel" aria-labelledby="aipkit_sources_data_tab">
            <div
                class="aipkit_sources_training_inline"
                id="aipkit_sources_training_panel"
                aria-hidden="true"
                aria-labelledby="aipkit_sources_training_panel_title"
                aria-describedby="aipkit_sources_training_panel_description"
                hidden
            >
                <div class="aipkit_sources_training_inline_header">
                    <div class="aipkit_sources_inline_heading">
                        <h2 class="aipkit_sources_inline_title" id="aipkit_sources_training_panel_title">
                            <?php esc_html_e('Add Data', 'gpt3-ai-content-generator'); ?>
                        </h2>
                        <p class="aipkit_sources_inline_subtitle" id="aipkit_sources_training_panel_description">
                            <?php esc_html_e('Choose a store, then add the content you want your AI to search.', 'gpt3-ai-content-generator'); ?>
                        </p>
                    </div>
                </div>
                <div id="aipkit_sources_training_card">
                    <div class="aipkit_sources_training_inline_body">
                        <div class="aipkit_builder_card_body">
                    <div class="aipkit_sources_training_target">
                        <div class="aipkit_sources_training_target_controls">
                            <span class="aipkit_sources_training_control">
                                <label class="aipkit_sources_training_control_label" for="aipkit_sources_training_provider">
                                    <?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <select id="aipkit_sources_training_provider" class="aipkit_sources_filter_select aipkit_sources_training_select">
                                    <option value="openai"><?php esc_html_e('OpenAI', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="pinecone"><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="qdrant"><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="chroma"><?php esc_html_e('Chroma', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                            </span>
                            <span class="aipkit_sources_training_control">
                                <label class="aipkit_sources_training_control_label" id="aipkit_sources_training_store_label" for="aipkit_sources_training_store">
                                    <?php esc_html_e('Vector store', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <select id="aipkit_sources_training_store" class="aipkit_sources_filter_select aipkit_sources_training_select">
                                    <option value=""><?php esc_html_e('Select store', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                            </span>
                            <span class="aipkit_sources_training_control" id="aipkit_sources_embedding_row" hidden>
                                <label class="aipkit_sources_training_control_label" for="aipkit_sources_embedding_model">
                                    <?php esc_html_e('Embedding model', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <select id="aipkit_sources_embedding_model" class="aipkit_sources_filter_select aipkit_sources_training_select">
                                    <option value=""><?php esc_html_e('Select embedding model', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                            </span>
                            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_sources_training_refresh_stores" id="aipkit_sources_training_refresh_stores">
                                <span class="aipkit_btn_label"><?php esc_html_e('Refresh', 'gpt3-ai-content-generator'); ?></span>
                            </button>
                            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small" id="aipkit_sources_training_create_store">
                                <?php esc_html_e('Create Store', 'gpt3-ai-content-generator'); ?>
                            </button>
                        </div>
                        <div class="aipkit_sources_training_target_empty" id="aipkit_sources_training_store_empty" hidden>
                            <?php esc_html_e('No stores found for this provider. Create one to start adding data.', 'gpt3-ai-content-generator'); ?>
                        </div>
                    </div>
                    <div class="aipkit_sources_training_content">
                    <div class="aipkit_builder_tabs aipkit_builder_tabs--training" role="tablist" aria-label="<?php esc_attr_e('Training sources', 'gpt3-ai-content-generator'); ?>" data-aipkit-tabs="training">
                        <button type="button" class="aipkit_builder_tab is-active" role="tab" aria-selected="true" data-aipkit-tab="qa">
                            <?php esc_html_e('Q&A', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <button type="button" class="aipkit_builder_tab" role="tab" aria-selected="false" data-aipkit-tab="text">
                            <?php esc_html_e('Text', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <button type="button" class="aipkit_builder_tab" role="tab" aria-selected="false" data-aipkit-tab="files">
                            <?php esc_html_e('Files', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <button type="button" class="aipkit_builder_tab" role="tab" aria-selected="false" data-aipkit-tab="website">
                            <?php esc_html_e('Website', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </div>

                    <div class="aipkit_builder_tab_panels aipkit_builder_tab_panels--training">
                        <div class="aipkit_builder_tab_panel is-active" data-aipkit-panel="qa">
                            <div class="aipkit_builder_training_qa">
                                <div class="aipkit_training_field">
                                    <label class="aipkit_training_label" for="aipkit_training_qa_question">
                                        <?php esc_html_e('Question', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <textarea
                                        id="aipkit_training_qa_question"
                                        class="aipkit_builder_textarea aipkit_training_textarea"
                                        rows="3"
                                        placeholder="<?php esc_attr_e('What is your refund policy?', 'gpt3-ai-content-generator'); ?>"
                                    ></textarea>
                                </div>
                                <div class="aipkit_training_field">
                                    <label class="aipkit_training_label" for="aipkit_training_qa_answer">
                                        <?php esc_html_e('Answer', 'gpt3-ai-content-generator'); ?>
                                    </label>
                                    <textarea
                                        id="aipkit_training_qa_answer"
                                        class="aipkit_builder_textarea aipkit_training_textarea"
                                        rows="3"
                                        placeholder="<?php esc_attr_e('We offer refunds within 30 days of purchase.', 'gpt3-ai-content-generator'); ?>"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="aipkit_builder_tab_panel" data-aipkit-panel="text" hidden>
                            <div class="aipkit_training_field">
                                <label class="aipkit_training_label" for="aipkit_training_text_input">
                                    <?php esc_html_e('Text', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <textarea
                                    id="aipkit_training_text_input"
                                    name="training_text"
                                    class="aipkit_builder_textarea aipkit_training_textarea aipkit_training_text_input"
                                    rows="6"
                                    placeholder="<?php esc_attr_e('Add training text...', 'gpt3-ai-content-generator'); ?>"
                                ></textarea>
                            </div>
                        </div>
                        <div class="aipkit_builder_tab_panel" data-aipkit-panel="files" hidden>
                            <div class="aipkit_training_field">
                                <span class="aipkit_training_label">
                                    <?php esc_html_e('Files', 'gpt3-ai-content-generator'); ?>
                                </span>
                                <div class="aipkit_builder_dropzone aipkit_training_dropzone">
                                    <div class="aipkit_builder_dropzone_inner">
                                        <?php if ( $is_pro_plan ) : ?>
                                            <input
                                                id="aipkit_training_files_input"
                                                class="aipkit_training_files_input"
                                                type="file"
                                                multiple
                                                accept=".pdf,.docx,.txt,.md,.csv,.json"
                                                hidden
                                            >
                                            <button
                                                type="button"
                                                class="aipkit_btn aipkit_btn-secondary aipkit_builder_action_btn aipkit_training_files_button"
                                            >
                                                <?php esc_html_e('Choose files', 'gpt3-ai-content-generator'); ?>
                                            </button>
                                        <?php else : ?>
                                            <a
                                                class="aipkit_btn aipkit_btn-primary aipkit_builder_action_btn aipkit_training_files_button"
                                                href="<?php echo esc_url($upgrade_url); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <p class="aipkit_builder_help_text">
                                            <?php esc_html_e('Supported: pdf, docx, txt, md, csv, json', 'gpt3-ai-content-generator'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="aipkit_training_file_list"
                                id="aipkit_training_file_list"
                                data-upgrade-url="<?php echo esc_url($upgrade_url); ?>"
                            ></div>
                        </div>
                        <div class="aipkit_builder_tab_panel" data-aipkit-panel="website" hidden>
                            <div class="aipkit_training_website">
                                <div class="aipkit_training_site_compact_row">
                                    <div class="aipkit_training_site_toggle" role="radiogroup" aria-label="<?php esc_attr_e('Website content scope', 'gpt3-ai-content-generator'); ?>">
                                        <label class="aipkit_training_site_option aipkit_training_site_mode_option">
                                            <input type="radio" name="aipkit_wp_content_mode" id="aipkit_wp_content_mode_bulk" value="bulk" checked>
                                            <span><?php esc_html_e('All', 'gpt3-ai-content-generator'); ?></span>
                                        </label>
                                        <label class="aipkit_training_site_option aipkit_training_site_mode_option">
                                            <input type="radio" name="aipkit_wp_content_mode" id="aipkit_wp_content_mode_specific" value="specific">
                                            <span><?php esc_html_e('Choose items', 'gpt3-ai-content-generator'); ?></span>
                                        </label>
                                    </div>
                                    <div id="aipkit_wp_content_bulk_panel" class="aipkit_training_site_field">
                                        <span class="aipkit_training_site_label"><?php esc_html_e('Content types', 'gpt3-ai-content-generator'); ?></span>
                                        <div
                                            class="aipkit_training_site_dropdown"
                                            data-aipkit-training-types="bulk"
                                            data-placeholder="<?php echo esc_attr__('Select types', 'gpt3-ai-content-generator'); ?>"
                                            data-selected-label="<?php echo esc_attr__('selected', 'gpt3-ai-content-generator'); ?>"
                                        >
                                            <button
                                                type="button"
                                                class="aipkit_training_site_dropdown_btn"
                                                aria-expanded="false"
                                                aria-controls="aipkit_training_types_menu_bulk"
                                            >
                                                <span class="aipkit_training_site_dropdown_label">
                                                    <?php esc_html_e('Select types', 'gpt3-ai-content-generator'); ?>
                                                </span>
                                            </button>
                                            <div
                                                id="aipkit_training_types_menu_bulk"
                                                class="aipkit_training_site_dropdown_panel"
                                                role="menu"
                                                hidden
                                            >
                                                <div id="aipkit_vs_wp_types_checkboxes" class="aipkit_training_site_checks aipkit_training_site_checks--dropdown">
                                                    <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                                        <label class="aipkit_training_site_check" data-ptype="<?php echo esc_attr($post_type_slug); ?>">
                                                            <input type="checkbox" class="aipkit_wp_type_cb" value="<?php echo esc_attr($post_type_slug); ?>" <?php checked(in_array($post_type_slug, ['post', 'page'], true)); ?> />
                                                            <span class="aipkit_training_site_check_label"><?php echo esc_html($post_type_obj->label); ?></span>
                                                            <span class="aipkit_count_badge" data-count="-1"></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <select id="aipkit_vs_wp_content_post_types" class="aipkit_training_site_hidden_select" multiple size="3">
                                        <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                            <option value="<?php echo esc_attr($post_type_slug); ?>" <?php selected(in_array($post_type_slug, ['post', 'page'], true)); ?>>
                                                <?php echo esc_html($post_type_obj->label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="aipkit_wp_content_specific_types_panel" class="aipkit_training_site_field" hidden>
                                        <span class="aipkit_training_site_label"><?php esc_html_e('Content types', 'gpt3-ai-content-generator'); ?></span>
                                        <div
                                            class="aipkit_training_site_dropdown"
                                            data-aipkit-training-types="specific"
                                            data-placeholder="<?php echo esc_attr__('Select types', 'gpt3-ai-content-generator'); ?>"
                                            data-selected-label="<?php echo esc_attr__('selected', 'gpt3-ai-content-generator'); ?>"
                                        >
                                            <button
                                                type="button"
                                                class="aipkit_training_site_dropdown_btn"
                                                aria-expanded="false"
                                                aria-controls="aipkit_training_types_menu_specific"
                                            >
                                                <span class="aipkit_training_site_dropdown_label">
                                                    <?php esc_html_e('Select types', 'gpt3-ai-content-generator'); ?>
                                                </span>
                                            </button>
                                            <div
                                                id="aipkit_training_types_menu_specific"
                                                class="aipkit_training_site_dropdown_panel"
                                                role="menu"
                                                hidden
                                            >
                                                <div id="aipkit_vs_wp_types_checkboxes_specific" class="aipkit_training_site_checks aipkit_training_site_checks--dropdown">
                                                    <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                                        <label class="aipkit_training_site_check" data-ptype="<?php echo esc_attr($post_type_slug); ?>">
                                                            <input type="checkbox" class="aipkit_wp_type_cb" value="<?php echo esc_attr($post_type_slug); ?>" <?php checked(in_array($post_type_slug, ['post', 'page'], true)); ?> />
                                                            <span class="aipkit_training_site_check_label"><?php echo esc_html($post_type_obj->label); ?></span>
                                                            <span class="aipkit_count_badge" data-count="-1"></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <select id="aipkit_vs_wp_content_post_types_specific" class="aipkit_training_site_hidden_select" multiple size="3">
                                        <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                            <option value="<?php echo esc_attr($post_type_slug); ?>" <?php selected(in_array($post_type_slug, ['post', 'page'], true)); ?>>
                                                <?php echo esc_html($post_type_obj->label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="aipkit_training_site_actions">
                                        <button
                                            type="button"
                                            class="aipkit_btn aipkit_btn-primary aipkit_builder_action_btn aipkit_training_action_btn aipkit_training_website_action_btn"
                                            data-training-action="website-add"
                                        >
                                            <?php esc_html_e('Add Website', 'gpt3-ai-content-generator'); ?>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="aipkit_vs_wp_content_mode" value="bulk" />
                                <select id="aipkit_vs_wp_content_status" class="aipkit_training_site_hidden_select" aria-hidden="true" tabindex="-1">
                                    <option value="publish" selected><?php esc_html_e('Published', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                                <select id="aipkit_vs_wp_content_status_specific" class="aipkit_training_site_hidden_select" aria-hidden="true" tabindex="-1">
                                    <option value="publish" selected><?php esc_html_e('Published', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                                <div id="aipkit_background_indexing_confirm" class="aipkit_inline_confirm" hidden>
                                    <div class="aipkit_inline_confirm_content">
                                        <p id="aipkit_background_indexing_message" class="aipkit_builder_help_text"></p>
                                        <div class="aipkit_inline_confirm_actions">
                                            <button type="button" id="aipkit_background_indexing_yes" class="aipkit_btn aipkit_btn-primary aipkit_btn-sm">
                                                <?php esc_html_e('Yes', 'gpt3-ai-content-generator'); ?>
                                            </button>
                                            <button type="button" id="aipkit_background_indexing_no" class="aipkit_btn aipkit_btn-secondary aipkit_btn-sm">
                                                <?php esc_html_e('No', 'gpt3-ai-content-generator'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="aipkit_wp_content_specific_panel" class="aipkit_training_site_panel aipkit_training_site_browser" hidden>
                                    <div class="aipkit_training_site_browser_header">
                                        <span class="aipkit_training_site_label"><?php esc_html_e('Items', 'gpt3-ai-content-generator'); ?></span>
                                    </div>
                                    <div id="aipkit_vs_wp_content_list_area" class="aipkit_training_site_list"></div>
                                    <div id="aipkit_vs_wp_content_pagination" class="aipkit_training_site_pagination"></div>
                                </div>
                                <div id="aipkit_vs_wp_content_messages_area" class="aipkit_form-help aipkit_training_site_status" aria-live="polite"></div>
                                <select id="aipkit_vs_global_target_select" class="aipkit_training_site_target_select" aria-hidden="true" tabindex="-1"></select>
                            </div>
                        </div>
                    </div>
                    <div class="aipkit_sources_training_inline_footer aipkit_training_footer">
                        <span id="aipkit_training_status" class="aipkit_training_status" aria-live="polite"></span>
                        <div class="aipkit_builder_action_row aipkit_training_action_row">
                            <div class="aipkit_builder_action_group aipkit_training_primary_actions">
                                <button
                                    type="button"
                                    class="aipkit_btn aipkit_btn-primary aipkit_builder_action_btn aipkit_training_action_btn"
                                    data-training-action="add"
                                >
                                    <?php esc_html_e('Add Q&A', 'gpt3-ai-content-generator'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="aipkit_sources_bulk_bar" id="aipkit_sources_bulk_bar" hidden>
                <div class="aipkit_sources_bulk_text">
                    <span class="aipkit_sources_bulk_count" id="aipkit_sources_bulk_count">
                        <?php esc_html_e('0 selected', 'gpt3-ai-content-generator'); ?>
                    </span>
                    <span class="aipkit_sources_bulk_progress" id="aipkit_sources_bulk_progress" aria-live="polite"></span>
                </div>
                <div class="aipkit_sources_bulk_actions">
                    <button type="button" class="aipkit_btn aipkit_btn-danger aipkit_sources_bulk_delete">
                        <?php esc_html_e('Delete', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_bulk_retry" hidden>
                        <?php esc_html_e('Retry failed', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_bulk_clear">
                        <?php esc_html_e('Clear', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>
            </div>
            <div class="aipkit_data-table aipkit_sources_table">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <span class="aipkit_sources_status_header">
                                    <input
                                        type="checkbox"
                                        id="aipkit_sources_select_all"
                                        class="aipkit_sources_select_all"
                                        aria-label="<?php esc_attr_e('Select all deletable sources', 'gpt3-ai-content-generator'); ?>"
                                        disabled
                                    />
                                    <span><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></span>
                                </span>
                            </th>
                            <th><?php esc_html_e('Item', 'gpt3-ai-content-generator'); ?></th>
                            <th><?php esc_html_e('Type', 'gpt3-ai-content-generator'); ?></th>
                            <th><?php esc_html_e('Updated', 'gpt3-ai-content-generator'); ?></th>
                            <th class="aipkit_actions_cell_header"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="aipkit_sources_table_body">
                        <tr>
                            <td colspan="5" class="aipkit_text-center">
                                <?php esc_html_e('Sources will appear here.', 'gpt3-ai-content-generator'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="aipkit_sources_pagination" class="aipkit_logs_pagination_container"></div>
            </div>
            <div
                id="aipkit_sources_stores_panel"
                class="aipkit_sources_workspace_panel"
                role="tabpanel"
                aria-labelledby="aipkit_sources_stores_tab"
                hidden
            >
                <div class="aipkit_data-table aipkit_sources_table aipkit_sources_stores_table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php esc_html_e('Store', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                                <th class="aipkit_actions_cell_header"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="aipkit_sources_stores_table_body">
                            <tr>
                                <td colspan="4" class="aipkit_text-center">
                                    <?php esc_html_e('Stores will appear here.', 'gpt3-ai-content-generator'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div
                id="aipkit_sources_settings_panel"
                class="aipkit_sources_workspace_panel aipkit_sources_settings_panel"
                role="tabpanel"
                aria-labelledby="aipkit_sources_settings_tab"
                hidden
            >
                <div class="aipkit_sources_settings_form">
                    <?php
                    $is_pro = $is_pro_plan;
                    include WPAICG_PLUGIN_DIR . 'admin/views/modules/settings/partials/settings-knowledge-base-page.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div
    class="aipkit-modal-overlay aipkit_sources_modal aipkit_sources_store_modal"
    id="aipkit_sources_store_modal"
    aria-hidden="true"
>
    <div
        class="aipkit-modal-content aipkit_sources_modal_panel aipkit_sources_store_modal_content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="aipkit_sources_store_modal_title"
        aria-describedby="aipkit_sources_store_modal_description"
    >
        <div class="aipkit-modal-header aipkit_sources_modal_header">
            <div class="aipkit_sources_modal_heading">
                <h2 class="aipkit-modal-title aipkit_sources_modal_title" id="aipkit_sources_store_modal_title">
                    <?php esc_html_e('Create Store', 'gpt3-ai-content-generator'); ?>
                </h2>
                <p class="aipkit_builder_modal_subtitle aipkit_sources_modal_subtitle" id="aipkit_sources_store_modal_description">
                    <?php esc_html_e('Create the destination where new knowledge base data is saved.', 'gpt3-ai-content-generator'); ?>
                </p>
            </div>
            <button
                type="button"
                class="aipkit-modal-close-btn aipkit_sources_modal_close aipkit_sources_store_modal_close"
                aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>"
            >
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aipkit-modal-body aipkit_sources_modal_body">
            <div class="aipkit_sources_store_form">
                <div class="aipkit_builder_field">
                    <label class="aipkit_builder_label" for="aipkit_sources_store_modal_provider">
                        <?php esc_html_e('Provider', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="aipkit_sources_store_modal_provider" class="aipkit_sources_filter_select">
                        <option value="openai"><?php esc_html_e('OpenAI', 'gpt3-ai-content-generator'); ?></option>
                        <option value="pinecone"><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
                        <option value="qdrant"><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
                        <option value="chroma"><?php esc_html_e('Chroma', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>
                <div class="aipkit_builder_field">
                    <label class="aipkit_builder_label" for="aipkit_sources_store_modal_name">
                        <?php esc_html_e('Name', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="aipkit_sources_store_modal_name"
                        class="aipkit_form-input"
                        placeholder="<?php esc_attr_e('my-knowledge-store', 'gpt3-ai-content-generator'); ?>"
                    >
                </div>
                <div class="aipkit_builder_field" id="aipkit_sources_store_modal_dimension_row">
                    <label class="aipkit_builder_label" for="aipkit_sources_store_modal_dimension">
                        <?php esc_html_e('Dimension', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="aipkit_sources_store_modal_dimension"
                        class="aipkit_form-input"
                        min="1"
                        step="1"
                        value="<?php echo esc_attr__('Not required', 'gpt3-ai-content-generator'); ?>"
                        disabled
                    >
                </div>
            </div>
        </div>
        <div class="aipkit_sources_modal_footer">
            <div id="aipkit_sources_store_modal_status" class="aipkit_sources_modal_status aipkit_form-help" aria-live="polite"></div>
            <div class="aipkit_builder_action_row aipkit_sources_store_modal_actions">
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_store_modal_cancel">
                    <?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-primary aipkit_sources_store_modal_create">
                    <?php esc_html_e('Create', 'gpt3-ai-content-generator'); ?>
                </button>
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
                    <?php esc_html_e('Edit source', 'gpt3-ai-content-generator'); ?>
                </h2>
                <p class="aipkit_builder_modal_subtitle aipkit_sources_modal_subtitle" id="aipkit_sources_editor_description">
                    <?php esc_html_e('Update the source text and save to retrain this entry.', 'gpt3-ai-content-generator'); ?>
                </p>
            </div>
            <button
                type="button"
                class="aipkit-modal-close-btn aipkit_sources_modal_close aipkit_sources_editor_close"
                aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>"
            >
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aipkit-modal-body aipkit_sources_modal_body">
            <div class="aipkit_builder_field">
                <textarea
                    class="aipkit_builder_textarea aipkit_builder_textarea_large aipkit_sources_editor_textarea"
                    rows="10"
                    aria-label="<?php esc_attr_e('Source text', 'gpt3-ai-content-generator'); ?>"
                ></textarea>
            </div>
        </div>
        <div class="aipkit_sources_modal_footer">
            <div class="aipkit_sources_modal_status" aria-hidden="true"></div>
            <div class="aipkit_builder_action_row aipkit_sources_editor_actions">
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_editor_cancel">
                    <?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-primary aipkit_sources_editor_save">
                    <?php esc_html_e('Save & retrain', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div
    class="aipkit-modal-overlay aipkit_sources_modal aipkit_sources_view_modal"
    id="aipkit_sources_view_modal"
    aria-hidden="true"
>
    <div
        class="aipkit-modal-content aipkit_sources_modal_panel aipkit_sources_view_modal_content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="aipkit_sources_view_title"
        aria-describedby="aipkit_sources_view_description"
    >
        <div class="aipkit-modal-header aipkit_sources_modal_header">
            <div class="aipkit_sources_modal_heading">
                <h2 class="aipkit-modal-title aipkit_sources_modal_title" id="aipkit_sources_view_title">
                    <?php esc_html_e('Source preview', 'gpt3-ai-content-generator'); ?>
                </h2>
                <p class="aipkit_builder_modal_subtitle aipkit_sources_modal_subtitle" id="aipkit_sources_view_description">
                    <?php esc_html_e('Review the indexed content stored for this source.', 'gpt3-ai-content-generator'); ?>
                </p>
            </div>
            <button
                type="button"
                class="aipkit-modal-close-btn aipkit_sources_modal_close aipkit_sources_view_close"
                aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>"
            >
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aipkit-modal-body aipkit_sources_modal_body">
            <div class="aipkit_builder_field">
                <textarea
                    class="aipkit_builder_textarea aipkit_builder_textarea_large aipkit_sources_view_textarea"
                    rows="12"
                    readonly
                    aria-label="<?php esc_attr_e('Source content preview', 'gpt3-ai-content-generator'); ?>"
                ></textarea>
            </div>
            <div
                class="aipkit_sources_view_meta"
                id="aipkit_sources_view_meta"
                hidden
            >
                <button
                    type="button"
                    class="aipkit_sources_view_meta_toggle"
                    aria-expanded="false"
                    aria-controls="aipkit_sources_view_meta_panel"
                >
                    <span><?php esc_html_e('Details', 'gpt3-ai-content-generator'); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                </button>
                <div
                    class="aipkit_sources_view_meta_panel"
                    id="aipkit_sources_view_meta_panel"
                    hidden
                ></div>
            </div>
        </div>
        <div class="aipkit_sources_modal_footer">
            <div class="aipkit_sources_modal_status" aria-hidden="true"></div>
            <div class="aipkit_builder_action_row aipkit_sources_view_actions">
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_view_close_btn">
                    <?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div
    class="aipkit-modal-overlay aipkit_sources_store_delete_modal"
    id="aipkit_sources_store_delete_modal"
    aria-hidden="true"
>
    <div
        class="aipkit-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="aipkit_sources_store_delete_title"
        aria-describedby="aipkit_sources_store_delete_description"
    >
        <div class="aipkit-modal-header">
            <div>
                <h3 class="aipkit-modal-title" id="aipkit_sources_store_delete_title">
                    <?php esc_html_e('Delete Store', 'gpt3-ai-content-generator'); ?>
                </h3>
                <p class="aipkit_builder_modal_subtitle" id="aipkit_sources_store_delete_description">
                    <?php esc_html_e('This removes the store and its source records from the knowledge base.', 'gpt3-ai-content-generator'); ?>
                </p>
            </div>
            <button
                type="button"
                class="aipkit-modal-close-btn aipkit_sources_store_delete_close"
                aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>"
            >
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aipkit-modal-body">
            <p class="aipkit_builder_help_text" id="aipkit_sources_store_delete_message"></p>
            <div class="aipkit_builder_action_row">
                <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_sources_store_delete_cancel">
                    <?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?>
                </button>
                <button type="button" class="aipkit_btn aipkit_btn-danger aipkit_sources_store_delete_confirm">
                    <?php esc_html_e('Delete', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

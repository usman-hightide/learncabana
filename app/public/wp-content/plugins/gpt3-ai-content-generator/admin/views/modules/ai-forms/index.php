<?php

/**
 * AIPKit AI Forms Module - Admin View
 * Main screen for managing AI Forms.
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

use WPAICG\AIPKit_Providers;

$aipkit_vector_store_localization = [
    'openai_vector_stores' => [],
    'pinecone_indexes' => [],
    'qdrant_collections' => [],
    'chroma_collections' => [],
];
if (class_exists(AIPKit_Providers::class)) {
    $aipkit_vector_store_localization = AIPKit_Providers::get_vector_store_localization_payload('ai_forms_editor_ui');
}
$openai_vector_stores = isset($aipkit_vector_store_localization['openai_vector_stores']) && is_array($aipkit_vector_store_localization['openai_vector_stores'])
    ? $aipkit_vector_store_localization['openai_vector_stores']
    : [];
$pinecone_indexes = isset($aipkit_vector_store_localization['pinecone_indexes']) && is_array($aipkit_vector_store_localization['pinecone_indexes'])
    ? $aipkit_vector_store_localization['pinecone_indexes']
    : [];
$qdrant_collections = isset($aipkit_vector_store_localization['qdrant_collections']) && is_array($aipkit_vector_store_localization['qdrant_collections'])
    ? $aipkit_vector_store_localization['qdrant_collections']
    : [];
$chroma_collections = isset($aipkit_vector_store_localization['chroma_collections']) && is_array($aipkit_vector_store_localization['chroma_collections'])
    ? $aipkit_vector_store_localization['chroma_collections']
    : [];

?>
<?php
$aipkit_notice_id = 'aipkit_provider_notice_ai_forms';
include WPAICG_PLUGIN_DIR . 'admin/views/shared/provider-key-notice.php';
?>
<div
    class="aipkit_container aipkit_ai_forms_container"
    id="aipkit_ai_forms_container"
>
    <?php include WPAICG_PLUGIN_DIR . 'admin/views/shared/vector-store-nonce-fields.php'; ?>
    <div class="aipkit_container-header">
        <div class="aipkit_container-header-left">
            <div class="aipkit_ai_forms_header_copy">
                <div class="aipkit_ai_forms_header_title_row">
                    <div class="aipkit_container-title" id="aipkit_ai_forms_header_title_default"><?php esc_html_e('AI Forms', 'gpt3-ai-content-generator'); ?></div>
                    <span id="aipkit_ai_forms_settings_status" class="aipkit_training_status aipkit_global_status_area" aria-live="polite"></span>
                </div>
                <p class="aipkit_ai_forms_header_hint"><?php esc_html_e('Build, manage, and preview AI-powered forms with reusable actions and prompts.', 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div>
    <div class="aipkit_container-body">
        <div id="aipkit_ai_forms_messages">
        </div>
        <div id="aipkit_ai_forms_import_messages">
        </div>
        <input type="file" id="aipkit_ai_forms_import_file_input" style="display: none;" accept="application/json">
        <div id="aipkit_form_editor_container" class="aipkit_form_editor_container" style="display:none;">
            <?php include __DIR__ . '/partials/form-editor.php'; ?>
        </div>
        <div id="aipkit_ai_forms_list_container">
            <div class="aipkit_ai_forms_workspace_bar">
                <div class="aipkit_ai_forms_workspace_tabs" role="tablist" aria-label="<?php esc_attr_e('AI Forms sections', 'gpt3-ai-content-generator'); ?>">
                    <button type="button" class="aipkit_ai_forms_workspace_tab is-active" id="aipkit_ai_forms_forms_tab" role="tab" aria-selected="true" aria-controls="aipkit_ai_forms_data_panel" data-aipkit-ai-forms-tab="forms">
                        <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                        <?php esc_html_e('Forms', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button type="button" class="aipkit_ai_forms_workspace_tab" id="aipkit_ai_forms_settings_tab" role="tab" aria-selected="false" aria-controls="aipkit_ai_forms_settings_panel" data-aipkit-ai-forms-tab="settings">
                        <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                        <?php esc_html_e('Settings', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>
                <div class="aipkit_ai_forms_workspace_tools is-active" data-aipkit-ai-forms-tools="forms" aria-label="<?php esc_attr_e('Form tools', 'gpt3-ai-content-generator'); ?>">
                    <label class="screen-reader-text" for="aipkit_ai_forms_search_input">
                        <?php esc_html_e('Search forms', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <span class="aipkit_ai_forms_search_control">
                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                        <input
                            type="search"
                            id="aipkit_ai_forms_search_input"
                            class="aipkit_ai_forms_search_input"
                            placeholder="<?php esc_attr_e('Search', 'gpt3-ai-content-generator'); ?>"
                        >
                    </span>
                    <span class="aipkit_ai_forms_meta_action">
                        <button type="button" id="aipkit_create_new_ai_form_btn" class="aipkit_btn aipkit_btn-primary">
                            <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                            <?php esc_html_e('Create Form', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </span>
                </div>
            </div>
            <div id="aipkit_ai_forms_data_panel" class="aipkit_ai_forms_workspace_panel is-active" role="tabpanel" aria-labelledby="aipkit_ai_forms_forms_tab">
                <div class="aipkit_ai_forms_bulk_bar" id="aipkit_ai_forms_bulk_bar" hidden>
                    <div class="aipkit_ai_forms_bulk_text">
                        <span class="aipkit_ai_forms_bulk_count" id="aipkit_ai_forms_bulk_count">
                            <?php esc_html_e('0 selected', 'gpt3-ai-content-generator'); ?>
                        </span>
                        <span class="aipkit_ai_forms_bulk_progress" id="aipkit_ai_forms_bulk_progress" aria-live="polite"></span>
                    </div>
                    <div class="aipkit_ai_forms_bulk_actions">
                        <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_ai_forms_bulk_duplicate">
                            <?php esc_html_e('Duplicate', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_ai_forms_bulk_export">
                            <?php esc_html_e('Export', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <button type="button" class="aipkit_btn aipkit_btn-danger aipkit_ai_forms_bulk_delete">
                            <?php esc_html_e('Delete', 'gpt3-ai-content-generator'); ?>
                        </button>
                        <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_ai_forms_bulk_clear">
                            <?php esc_html_e('Clear', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </div>
                </div>
                <div class="aipkit_data-table aipkit_ai_forms_list_table">
                    <table class="aipkit_data-table__table">
                        <thead>
                            <tr>
                                <th class="aipkit_ai_forms_select_cell">
                                    <input
                                        type="checkbox"
                                        id="aipkit_ai_forms_select_all"
                                        class="aipkit_ai_forms_select_all"
                                        aria-label="<?php esc_attr_e('Select all forms', 'gpt3-ai-content-generator'); ?>"
                                        disabled
                                    />
                                </th>
                                <th class="aipkit-sortable-col" data-sort-key="title"><span><?php esc_html_e('Form', 'gpt3-ai-content-generator'); ?></span></th>
                                <th><?php esc_html_e('Shortcode', 'gpt3-ai-content-generator'); ?></th>
                                <th class="aipkit-sortable-col" data-sort-key="modified"><span><?php esc_html_e('Updated', 'gpt3-ai-content-generator'); ?></span></th>
                                <th class="aipkit_actions_cell_header">
                                    <div class="aipkit_actions_header">
                                        <span><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></span>
                                        <div class="aipkit_actions_menu">
                                            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_btn-icon aipkit_actions_menu_toggle" aria-haspopup="menu" aria-expanded="false" title="<?php esc_attr_e('More actions', 'gpt3-ai-content-generator'); ?>" aria-label="<?php esc_attr_e('More actions', 'gpt3-ai-content-generator'); ?>">
                                                <span class="dashicons dashicons-ellipsis" aria-hidden="true"></span>
                                            </button>
                                            <div class="aipkit_actions_dropdown_menu" role="menu">
                                                <button type="button" role="menuitem" id="aipkit_export_all_ai_forms_btn" class="aipkit_dropdown-item-btn">
                                                    <span class="dashicons dashicons-download"></span>
                                                    <span><?php esc_html_e('Export All', 'gpt3-ai-content-generator'); ?></span>
                                                </button>
                                                <button type="button" role="menuitem" id="aipkit_import_ai_forms_btn" class="aipkit_dropdown-item-btn">
                                                    <span class="dashicons dashicons-upload"></span>
                                                    <span><?php esc_html_e('Import', 'gpt3-ai-content-generator'); ?></span>
                                                </button>
                                                <button type="button" role="menuitem" id="aipkit_delete_all_ai_forms_btn" class="aipkit_dropdown-item-btn aipkit_dropdown-item--danger">
                                                    <span class="dashicons dashicons-trash"></span>
                                                    <span><?php esc_html_e('Delete All', 'gpt3-ai-content-generator'); ?></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="aipkit_ai_forms_list_tbody">
                        </tbody>
                    </table>
                </div>
                <div id="aipkit_ai_forms_pagination" class="aipkit_logs_pagination_container"></div>
                <div id="aipkit_no_ai_forms_message" class="aipkit_ai_forms_empty_state">
                    <?php esc_html_e('No AI Forms have been created yet.', 'gpt3-ai-content-generator'); ?>
                </div>
            </div>
            <div id="aipkit_ai_forms_settings_panel" class="aipkit_ai_forms_workspace_panel aipkit_ai_forms_settings_panel" role="tabpanel" aria-labelledby="aipkit_ai_forms_settings_tab" hidden>
                <?php include __DIR__ . '/partials/settings-ai-forms.php'; ?>
            </div>
        </div>
    </div>
    <div class="aipkit_builder_sheet_overlay aipkit_ai_forms_preview_sheet" id="aipkit_ai_forms_preview_sheet" aria-hidden="true">
        <div
            class="aipkit_builder_sheet_panel"
            role="dialog"
            aria-labelledby="aipkit_ai_forms_preview_sheet_title"
            aria-describedby="aipkit_ai_forms_preview_sheet_description"
        >
            <div class="aipkit_builder_sheet_header">
                <div>
                    <div class="aipkit_builder_sheet_title_row">
                        <h3 class="aipkit_builder_sheet_title" id="aipkit_ai_forms_preview_sheet_title">
                            <?php esc_html_e('Preview', 'gpt3-ai-content-generator'); ?>
                        </h3>
                    </div>
                    <p class="aipkit_builder_sheet_description" id="aipkit_ai_forms_preview_sheet_description"></p>
                </div>
                <button
                    type="button"
                    class="aipkit_builder_sheet_close"
                    id="aipkit_ai_forms_preview_sheet_close"
                    aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                </button>
            </div>
            <div class="aipkit_builder_sheet_body">
                <div class="aipkit_ai_forms_preview_frame" id="aipkit_ai_forms_preview_frame"></div>
            </div>
        </div>
    </div>
</div>

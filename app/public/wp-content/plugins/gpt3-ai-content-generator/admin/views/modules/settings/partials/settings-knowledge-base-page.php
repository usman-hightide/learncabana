<?php
/**
 * Partial: Knowledge Base Settings Page
 */
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables.

$kb_is_pro = isset($is_pro) ? (bool) $is_pro : (class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan());
$kb_upgrade_url = function_exists('wpaicg_gacg_fs')
    ? wpaicg_gacg_fs()->get_upgrade_url()
    : admin_url('admin.php?page=wpaicg-pricing');

$kb_training_general_settings = get_option('aipkit_training_general_settings', [
    'hide_user_uploads' => true,
]);
$kb_hide_user_uploads_checked = $kb_training_general_settings['hide_user_uploads'] ?? true;
$kb_chunk_avg_chars = isset($kb_training_general_settings['chunk_avg_chars_per_token'])
    ? (int) $kb_training_general_settings['chunk_avg_chars_per_token']
    : 4;
$kb_chunk_avg_chars = min(4, max(2, $kb_chunk_avg_chars));
$kb_chunk_max_tokens = isset($kb_training_general_settings['chunk_max_tokens_per_chunk'])
    ? (int) $kb_training_general_settings['chunk_max_tokens_per_chunk']
    : 3000;
$kb_chunk_max_tokens = min(6000, max(256, $kb_chunk_max_tokens));
$kb_chunk_overlap_tokens = isset($kb_training_general_settings['chunk_overlap_tokens'])
    ? (int) $kb_training_general_settings['chunk_overlap_tokens']
    : 150;
$kb_chunk_overlap_tokens = min(1000, max(0, $kb_chunk_overlap_tokens), max(0, $kb_chunk_max_tokens - 1));
$kb_openai_file_search_chunking_mode = isset($kb_training_general_settings['openai_file_search_chunking_mode'])
    ? sanitize_key((string) $kb_training_general_settings['openai_file_search_chunking_mode'])
    : 'auto';
if (!in_array($kb_openai_file_search_chunking_mode, ['auto', 'custom'], true)) {
    $kb_openai_file_search_chunking_mode = 'auto';
}
$kb_openai_file_search_max_tokens = isset($kb_training_general_settings['openai_file_search_max_chunk_size_tokens'])
    ? (int) $kb_training_general_settings['openai_file_search_max_chunk_size_tokens']
    : 800;
$kb_openai_file_search_max_tokens = min(4096, max(100, $kb_openai_file_search_max_tokens));
$kb_openai_file_search_overlap_tokens = isset($kb_training_general_settings['openai_file_search_chunk_overlap_tokens'])
    ? (int) $kb_training_general_settings['openai_file_search_chunk_overlap_tokens']
    : 400;
$kb_openai_file_search_overlap_tokens = min((int) floor($kb_openai_file_search_max_tokens / 2), max(0, $kb_openai_file_search_overlap_tokens));
$kb_openai_custom_rows_hidden_attr = $kb_openai_file_search_chunking_mode !== 'custom' ? ' hidden' : '';

$kb_options = get_option('aipkit_options', []);
$kb_options = is_array($kb_options) ? $kb_options : [];
$kb_semantic_search_settings = isset($kb_options['semantic_search']) && is_array($kb_options['semantic_search'])
    ? $kb_options['semantic_search']
    : [];
$kb_semantic_vector_provider = sanitize_key((string) ($kb_semantic_search_settings['vector_provider'] ?? 'pinecone'));
if (!in_array($kb_semantic_vector_provider, ['pinecone', 'qdrant', 'chroma'], true)) {
    $kb_semantic_vector_provider = 'pinecone';
}
$kb_semantic_target_id = $kb_semantic_search_settings['target_id'] ?? '';
$kb_semantic_embedding_provider = $kb_semantic_search_settings['embedding_provider'] ?? 'openai';
$kb_semantic_embedding_model = $kb_semantic_search_settings['embedding_model'] ?? '';
$kb_semantic_num_results = $kb_semantic_search_settings['num_results'] ?? 5;
$kb_semantic_no_results_text = $kb_semantic_search_settings['no_results_text'] ?? __('No results found.', 'gpt3-ai-content-generator');

$kb_pinecone_index_list = is_array($pinecone_index_list ?? null) ? $pinecone_index_list : [];
$kb_qdrant_collection_list = is_array($qdrant_collection_list ?? null) ? $qdrant_collection_list : [];
$kb_chroma_collection_list = is_array($chroma_collection_list ?? null) ? $chroma_collection_list : [];
$kb_embedding_provider_options = [];
$kb_embedding_models_by_provider = [];
if (class_exists('\\WPAICG\\AIPKit_Providers')) {
    if (empty($kb_pinecone_index_list)) {
        $kb_pinecone_index_list = \WPAICG\AIPKit_Providers::get_pinecone_indexes();
    }
    if (empty($kb_qdrant_collection_list)) {
        $kb_qdrant_collection_list = \WPAICG\AIPKit_Providers::get_qdrant_collections();
    }
    if (empty($kb_chroma_collection_list)) {
        $kb_chroma_collection_list = \WPAICG\AIPKit_Providers::get_chroma_collections();
    }
    $kb_embedding_provider_options = \WPAICG\AIPKit_Providers::get_embedding_provider_map('knowledge_base_settings_ui');
    $kb_embedding_models_by_provider = \WPAICG\AIPKit_Providers::get_embedding_models_by_provider('knowledge_base_settings_ui');
}
if (!isset($kb_embedding_provider_options[$kb_semantic_embedding_provider])) {
    $kb_semantic_embedding_provider = array_key_first($kb_embedding_provider_options) ?: 'openai';
}

$kb_embedding_options_allowed_html = [
    'optgroup' => [
        'label' => true,
    ],
    'option' => [
        'value' => true,
        'data-provider' => true,
        'selected' => true,
        'hidden' => true,
        'disabled' => true,
    ],
];

$kb_semantic_current_list = [];
if ($kb_semantic_vector_provider === 'pinecone') {
    $kb_semantic_current_list = $kb_pinecone_index_list;
} elseif ($kb_semantic_vector_provider === 'qdrant') {
    $kb_semantic_current_list = $kb_qdrant_collection_list;
} elseif ($kb_semantic_vector_provider === 'chroma') {
    $kb_semantic_current_list = $kb_chroma_collection_list;
}
$kb_semantic_target_label = in_array($kb_semantic_vector_provider, ['qdrant', 'chroma'], true)
    ? __('Collection', 'gpt3-ai-content-generator')
    : __('Index', 'gpt3-ai-content-generator');
?>
<div
    class="aipkit_settings_knowledge_base_page"
    id="aipkit_settings_knowledge_base_page"
    data-indexing-nonce="<?php echo esc_attr(wp_create_nonce('aipkit_ai_training_settings_nonce')); ?>"
    data-indexing-is-pro="<?php echo $kb_is_pro ? '1' : '0'; ?>"
    data-semantic-pinecone-indexes="<?php echo esc_attr(wp_json_encode($kb_pinecone_index_list ?: [])); ?>"
    data-semantic-qdrant-collections="<?php echo esc_attr(wp_json_encode($kb_qdrant_collection_list ?: [])); ?>"
    data-semantic-chroma-collections="<?php echo esc_attr(wp_json_encode($kb_chroma_collection_list ?: [])); ?>"
>
    <div class="aipkit_settings_kb_tabs" role="tablist" aria-label="<?php esc_attr_e('Knowledge Base Settings', 'gpt3-ai-content-generator'); ?>">
            <button
                type="button"
                class="aipkit_settings_kb_tab aipkit_active"
                id="aipkit_settings_kb_tab_chunking"
                role="tab"
                aria-selected="true"
                aria-controls="aipkit_settings_kb_panel_chunking"
                data-aipkit-kb-tab="chunking"
        >
                <span><?php esc_html_e('Chunking', 'gpt3-ai-content-generator'); ?></span>
                <?php if (!$kb_is_pro) : ?>
                    <span class="aipkit_settings_apps_upsell_badge"><?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?></span>
                <?php endif; ?>
            </button>
            <button
                type="button"
                class="aipkit_settings_kb_tab"
                id="aipkit_settings_kb_tab_batches"
                role="tab"
                aria-selected="false"
                aria-controls="aipkit_settings_kb_panel_batches"
                data-aipkit-kb-tab="batches"
                tabindex="-1"
            >
                <span><?php esc_html_e('Batches', 'gpt3-ai-content-generator'); ?></span>
                <?php if (!$kb_is_pro) : ?>
                    <span class="aipkit_settings_apps_upsell_badge"><?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?></span>
                <?php endif; ?>
            </button>
            <button
                type="button"
                class="aipkit_settings_kb_tab"
                id="aipkit_settings_kb_tab_content_rules"
                role="tab"
                aria-selected="false"
                aria-controls="aipkit_settings_kb_panel_content_rules"
                data-aipkit-kb-tab="content-rules"
                tabindex="-1"
            >
                <span><?php esc_html_e('Content Rules', 'gpt3-ai-content-generator'); ?></span>
                <?php if (!$kb_is_pro) : ?>
                    <span class="aipkit_settings_apps_upsell_badge"><?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?></span>
                <?php endif; ?>
            </button>
            <button
                type="button"
                class="aipkit_settings_kb_tab"
                id="aipkit_settings_kb_tab_basics"
                role="tab"
                aria-selected="false"
                aria-controls="aipkit_settings_kb_panel_basics"
                data-aipkit-kb-tab="basics"
                tabindex="-1"
            >
                <?php esc_html_e('Others', 'gpt3-ai-content-generator'); ?>
            </button>
        </div>
        <section
            class="aipkit_settings_kb_group aipkit_settings_kb_tab_panel"
            id="aipkit_settings_kb_panel_basics"
            role="tabpanel"
            aria-labelledby="aipkit_settings_kb_tab_basics"
            data-aipkit-kb-tab-panel="basics"
            data-aipkit-kb-section="basics"
            hidden
        >
            <div class="aipkit_settings_kb_block aipkit_settings_kb_block--single">
                <div class="aipkit_settings_kb_block_header">
                    <div class="aipkit_settings_kb_group_title"><?php esc_html_e('Semantic Search', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <div class="aipkit_form-group aipkit_settings_simple_row aipkit_settings_kb_semantic_launcher_row">
                    <label class="aipkit_form-label" for="aipkit_open_semantic_search_settings_modal">
                        <?php esc_html_e('Search options', 'gpt3-ai-content-generator'); ?>
                        <span class="aipkit_form-label-helper"><?php esc_html_e('Vector and display options.', 'gpt3-ai-content-generator'); ?></span>
                    </label>
                    <div class="aipkit_settings_action_buttons">
                        <button
                            type="button"
                            id="aipkit_open_semantic_search_settings_modal"
                            class="aipkit_btn aipkit_btn-secondary aipkit_settings_kb_semantic_modal_btn"
                            aria-haspopup="dialog"
                            aria-controls="aipkit_semantic_search_settings_modal"
                        >
                            <?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div
                class="aipkit-modal-overlay aipkit_settings_kb_semantic_modal"
                id="aipkit_semantic_search_settings_modal"
                aria-hidden="true"
            >
                <div
                    class="aipkit-modal-content aipkit-modal-shell aipkit_settings_kb_semantic_modal_content"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="aipkit_semantic_search_settings_modal_title"
                    aria-describedby="aipkit_semantic_search_settings_modal_desc"
                    tabindex="-1"
                >
                    <div class="aipkit-modal-header aipkit-modal-shell-header">
                        <div class="aipkit-modal-shell-intro">
                            <h2 class="aipkit-modal-shell-title" id="aipkit_semantic_search_settings_modal_title"><?php esc_html_e('Semantic Search', 'gpt3-ai-content-generator'); ?></h2>
                            <p class="aipkit-modal-shell-copy" id="aipkit_semantic_search_settings_modal_desc"><?php esc_html_e('Vector and display options.', 'gpt3-ai-content-generator'); ?></p>
                        </div>
                        <button
                            type="button"
                            class="aipkit-modal-close-btn aipkit-modal-shell-close"
                            id="aipkit_semantic_search_settings_modal_close"
                            aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>"
                        >
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="aipkit-modal-body aipkit-modal-shell-body aipkit_settings_kb_semantic_modal_body">
                        <div class="aipkit_settings_kb_semantic_modal_form">
                            <div class="aipkit_form-group aipkit_settings_simple_row">
                                <label class="aipkit_form-label" for="aipkit_semantic_search_vector_provider">
                                    <?php esc_html_e('Vector DB', 'gpt3-ai-content-generator'); ?>
                                    <span class="aipkit_form-label-helper"><?php esc_html_e('Search storage provider.', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <select id="aipkit_semantic_search_vector_provider" name="semantic_search_vector_provider" class="aipkit_form-input aipkit_autosave_trigger">
                                    <option value="pinecone" <?php selected($kb_semantic_vector_provider, 'pinecone'); ?>><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="qdrant" <?php selected($kb_semantic_vector_provider, 'qdrant'); ?>><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
                                    <option value="chroma" <?php selected($kb_semantic_vector_provider, 'chroma'); ?>><?php esc_html_e('Chroma', 'gpt3-ai-content-generator'); ?></option>
                                </select>
                            </div>
                            <div class="aipkit_form-group aipkit_settings_simple_row">
                                <label class="aipkit_form-label" id="aipkit_semantic_search_target_label" for="aipkit_semantic_search_target_id">
                                    <span data-aipkit-semantic-target-label-text><?php echo esc_html($kb_semantic_target_label); ?></span>
                                    <span class="aipkit_form-label-helper"><?php esc_html_e('Target index or collection.', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <select id="aipkit_semantic_search_target_id" name="semantic_search_target_id" class="aipkit_form-input aipkit_autosave_trigger">
                                    <option value=""><?php esc_html_e('-- Select --', 'gpt3-ai-content-generator'); ?></option>
                                    <?php
                                    $kb_semantic_target_found = false;
                                    if (!empty($kb_semantic_current_list)) {
                                        foreach ($kb_semantic_current_list as $kb_semantic_item) {
                                            $kb_semantic_item_name = is_array($kb_semantic_item) ? ($kb_semantic_item['name'] ?? ($kb_semantic_item['id'] ?? '')) : $kb_semantic_item;
                                            if (empty($kb_semantic_item_name)) {
                                                continue;
                                            }
                                            $kb_semantic_is_selected = selected($kb_semantic_target_id, $kb_semantic_item_name, false);
                                            if ($kb_semantic_is_selected) {
                                                $kb_semantic_target_found = true;
                                            }
                                            echo '<option value="' . esc_attr($kb_semantic_item_name) . '" ' . $kb_semantic_is_selected . '>' . esc_html($kb_semantic_item_name) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selected() output is safe.
                                        }
                                    }
                                    if (!$kb_semantic_target_found && !empty($kb_semantic_target_id)) {
                                        echo '<option value="' . esc_attr($kb_semantic_target_id) . '" selected>' . esc_html($kb_semantic_target_id) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="aipkit_form-group aipkit_settings_simple_row">
                                <label class="aipkit_form-label" for="aipkit_semantic_search_embedding_model">
                                    <?php esc_html_e('Embedding', 'gpt3-ai-content-generator'); ?>
                                    <span class="aipkit_form-label-helper"><?php esc_html_e('Search embedding model.', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <select id="aipkit_semantic_search_embedding_model" name="semantic_search_embedding_model" class="aipkit_form-input aipkit_autosave_trigger">
                                    <?php
                                    if (class_exists('\\WPAICG\\AIPKit_Providers')) {
                                        echo wp_kses(
                                            \WPAICG\AIPKit_Providers::render_embedding_optgroup_options(
                                                $kb_embedding_provider_options,
                                                $kb_embedding_models_by_provider,
                                                $kb_semantic_embedding_provider,
                                                $kb_semantic_embedding_model,
                                                [
                                                    'value_mode' => 'model',
                                                    'include_manual_fallback' => true,
                                                ]
                                            ),
                                            $kb_embedding_options_allowed_html
                                        );
                                    }
                                    ?>
                                </select>
                                <input type="hidden" id="aipkit_semantic_search_embedding_provider" name="semantic_search_embedding_provider" value="<?php echo esc_attr($kb_semantic_embedding_provider); ?>" class="aipkit_autosave_trigger">
                            </div>
                            <div class="aipkit_form-group aipkit_settings_simple_row">
                                <label class="aipkit_form-label" for="aipkit_semantic_search_num_results">
                                    <?php esc_html_e('Number of Results', 'gpt3-ai-content-generator'); ?>
                                    <span class="aipkit_form-label-helper"><?php esc_html_e('Maximum results returned.', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <input type="number" id="aipkit_semantic_search_num_results" name="semantic_search_num_results" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($kb_semantic_num_results); ?>" min="1" max="20" />
                            </div>
                            <div class="aipkit_form-group aipkit_settings_simple_row">
                                <label class="aipkit_form-label" for="aipkit_semantic_search_no_results_text">
                                    <?php esc_html_e('No Results Text', 'gpt3-ai-content-generator'); ?>
                                    <span class="aipkit_form-label-helper"><?php esc_html_e('Message shown for empty searches.', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <input type="text" id="aipkit_semantic_search_no_results_text" name="semantic_search_no_results_text" class="aipkit_form-input aipkit_autosave_trigger" value="<?php echo esc_attr($kb_semantic_no_results_text); ?>" />
                            </div>
                            <div class="aipkit_form-group aipkit_settings_simple_row aipkit_settings_kb_shortcode_row">
                                <label class="aipkit_form-label" for="aipkit_semantic_search_shortcode_display">
                                    <?php esc_html_e('Shortcode', 'gpt3-ai-content-generator'); ?>
                                    <span class="aipkit_form-label-helper"><?php esc_html_e('Embed the search form.', 'gpt3-ai-content-generator'); ?></span>
                                </label>
                                <div class="aipkit_semantic_shortcode_group">
                                    <button
                                        type="button"
                                        id="aipkit_semantic_search_shortcode_display"
                                        class="aipkit_semantic_shortcode_snippet"
                                        data-shortcode="[aipkit_semantic_search]"
                                        title="<?php echo esc_attr('[aipkit_semantic_search]'); ?>"
                                        aria-label="<?php esc_attr_e('Click to copy shortcode', 'gpt3-ai-content-generator'); ?>"
                                    >
                                        <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                                        <span class="aipkit_semantic_shortcode_text">[aipkit_semantic_search]</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="aipkit_settings_kb_block aipkit_settings_kb_block--basics" data-aipkit-settings-autosave-exclude="true">
                <div class="aipkit_settings_kb_block_header">
                <div class="aipkit_settings_kb_group_title"><?php esc_html_e('Others', 'gpt3-ai-content-generator'); ?></div>
                </div>
            <div class="aipkit_form-group aipkit_settings_simple_row" id="aipkit_settings_kb_hide_uploads_row">
            <label class="aipkit_form-label" for="aipkit_hide_user_uploads">
                <?php esc_html_e('Hide user uploads', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Hide user-uploaded files from Data tab.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <label class="aipkit_settings_big_checkbox" for="aipkit_hide_user_uploads">
                <input
                    type="checkbox"
                    id="aipkit_hide_user_uploads"
                    name="hide_user_uploads"
                    value="1"
                    <?php checked((bool) $kb_hide_user_uploads_checked); ?>
                />
                <span class="aipkit_settings_big_checkbox_box" aria-hidden="true">
                    <span class="dashicons dashicons-yes"></span>
                </span>
                <span class="aipkit_settings_big_checkbox_text" aria-hidden="true"></span>
            </label>
            </div>
            </div>
        </section>
        <section
            class="aipkit_settings_kb_group aipkit_settings_kb_tab_panel"
            id="aipkit_settings_kb_panel_chunking"
            role="tabpanel"
            aria-labelledby="aipkit_settings_kb_tab_chunking"
            data-aipkit-kb-tab-panel="chunking"
            data-aipkit-kb-section="chunking"
            data-aipkit-settings-autosave-exclude="true"
        >
            <div class="aipkit_settings_kb_block aipkit_settings_kb_block--chunking">
                <div class="aipkit_settings_kb_block_header">
                    <div class="aipkit_settings_kb_group_title">
                        <span class="aipkit_settings_kb_label_line">
                        <span><?php esc_html_e('Chunking', 'gpt3-ai-content-generator'); ?></span>
                        <?php if (!$kb_is_pro) : ?>
                            <a
                                class="button aipkit_btn aipkit_btn-primary aipkit_settings_kb_upgrade_btn aipkit_settings_kb_header_upgrade_btn"
                                href="<?php echo esc_url($kb_upgrade_url); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                            </a>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="aipkit_form-group aipkit_settings_simple_row">
            <label class="aipkit_form-label" for="aipkit_chunk_avg_chars_per_token">
                <?php esc_html_e('Avg chars per token', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Applies to Pinecone/Qdrant only.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <input
                type="number"
                min="2"
                max="4"
                step="1"
                id="aipkit_chunk_avg_chars_per_token"
                name="chunk_avg_chars_per_token"
                class="aipkit_form-input"
                value="<?php echo esc_attr($kb_chunk_avg_chars); ?>"
                <?php echo !$kb_is_pro ? 'disabled' : ''; ?>
            />
            </div>
            <div class="aipkit_form-group aipkit_settings_simple_row">
            <label class="aipkit_form-label" for="aipkit_chunk_max_tokens_per_chunk">
                <?php esc_html_e('Max tokens per chunk', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Applies to Pinecone/Qdrant only.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <input
                type="number"
                min="256"
                max="6000"
                step="1"
                id="aipkit_chunk_max_tokens_per_chunk"
                name="chunk_max_tokens_per_chunk"
                class="aipkit_form-input"
                value="<?php echo esc_attr($kb_chunk_max_tokens); ?>"
                <?php echo !$kb_is_pro ? 'disabled' : ''; ?>
            />
            </div>
            <div class="aipkit_form-group aipkit_settings_simple_row">
            <label class="aipkit_form-label" for="aipkit_chunk_overlap_tokens">
                <?php esc_html_e('Overlap tokens', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Applies to Pinecone/Qdrant only.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <input
                type="number"
                min="0"
                max="1000"
                step="1"
                id="aipkit_chunk_overlap_tokens"
                name="chunk_overlap_tokens"
                class="aipkit_form-input"
                value="<?php echo esc_attr($kb_chunk_overlap_tokens); ?>"
                <?php echo !$kb_is_pro ? 'disabled' : ''; ?>
            />
            </div>
            <div class="aipkit_form-group aipkit_settings_simple_row">
            <label class="aipkit_form-label" for="aipkit_openai_file_search_chunking_mode">
                <?php esc_html_e('Indexing strategy', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Applies to OpenAI only.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <select
                id="aipkit_openai_file_search_chunking_mode"
                name="openai_file_search_chunking_mode"
                class="aipkit_form-input"
                <?php echo !$kb_is_pro ? 'disabled' : ''; ?>
            >
                <option value="auto" <?php selected($kb_openai_file_search_chunking_mode, 'auto'); ?>><?php esc_html_e('Auto', 'gpt3-ai-content-generator'); ?></option>
                <option value="custom" <?php selected($kb_openai_file_search_chunking_mode, 'custom'); ?>><?php esc_html_e('Custom', 'gpt3-ai-content-generator'); ?></option>
            </select>
            </div>
            <div class="aipkit_form-group aipkit_settings_simple_row" data-aipkit-openai-chunking-custom-row<?php echo $kb_openai_custom_rows_hidden_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute is a fixed string. ?>>
            <label class="aipkit_form-label" for="aipkit_openai_file_search_max_chunk_size_tokens">
                <?php esc_html_e('Max tokens per chunk', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Applies to OpenAI only.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <input
                type="number"
                min="100"
                max="4096"
                step="1"
                id="aipkit_openai_file_search_max_chunk_size_tokens"
                name="openai_file_search_max_chunk_size_tokens"
                class="aipkit_form-input"
                value="<?php echo esc_attr($kb_openai_file_search_max_tokens); ?>"
                <?php echo !$kb_is_pro ? 'disabled' : ''; ?>
            />
            </div>
            <div class="aipkit_form-group aipkit_settings_simple_row" data-aipkit-openai-chunking-custom-row<?php echo $kb_openai_custom_rows_hidden_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute is a fixed string. ?>>
            <label class="aipkit_form-label" for="aipkit_openai_file_search_chunk_overlap_tokens">
                <?php esc_html_e('Overlap tokens', 'gpt3-ai-content-generator'); ?>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Applies to OpenAI only.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <input
                type="number"
                min="0"
                max="<?php echo esc_attr((int) floor($kb_openai_file_search_max_tokens / 2)); ?>"
                step="1"
                id="aipkit_openai_file_search_chunk_overlap_tokens"
                name="openai_file_search_chunk_overlap_tokens"
                class="aipkit_form-input"
                value="<?php echo esc_attr($kb_openai_file_search_overlap_tokens); ?>"
                <?php echo !$kb_is_pro ? 'disabled' : ''; ?>
            />
            </div>
            </div>
        </section>

        <section
            class="aipkit_settings_kb_group aipkit_settings_kb_tab_panel"
            id="aipkit_settings_kb_panel_batches"
            role="tabpanel"
            aria-labelledby="aipkit_settings_kb_tab_batches"
            data-aipkit-kb-tab-panel="batches"
            data-aipkit-kb-section="embedding-batches"
            data-aipkit-settings-autosave-exclude="true"
            hidden
        >
            <div class="aipkit_settings_kb_block aipkit_settings_kb_block--embedding-batches">
                <div class="aipkit_settings_kb_block_header">
                    <div class="aipkit_settings_kb_group_title">
                        <span class="aipkit_settings_kb_label_line">
                            <span><?php esc_html_e('Embedding Batches', 'gpt3-ai-content-generator'); ?></span>
                            <?php if (!$kb_is_pro) : ?>
                                <a
                                    class="button aipkit_btn aipkit_btn-primary aipkit_settings_kb_upgrade_btn aipkit_settings_kb_header_upgrade_btn"
                                    href="<?php echo esc_url($kb_upgrade_url); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                                </a>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php
                $aipkit_batch_show_summary = false;
                $kb_embedding_batches_partial = defined('WPAICG_LIB_DIR')
                    ? WPAICG_LIB_DIR . 'views/modules/settings/partials/embedding-batches.php'
                    : '';
                if ($kb_embedding_batches_partial && file_exists($kb_embedding_batches_partial)) {
                    include $kb_embedding_batches_partial;
                }
                unset($aipkit_batch_show_summary);
                ?>
            </div>
        </section>

        <section
            class="aipkit_settings_kb_group aipkit_settings_kb_tab_panel"
            id="aipkit_settings_kb_panel_content_rules"
            role="tabpanel"
            aria-labelledby="aipkit_settings_kb_tab_content_rules"
            data-aipkit-kb-tab-panel="content-rules"
            data-aipkit-kb-section="indexing-controls"
            data-aipkit-settings-autosave-exclude="true"
            hidden
        >
            <div class="aipkit_settings_kb_block aipkit_settings_kb_block--single">
                <div class="aipkit_settings_kb_block_header">
                    <div class="aipkit_settings_kb_group_title">
                        <span class="aipkit_settings_kb_label_line">
                        <span><?php esc_html_e('Content Rules', 'gpt3-ai-content-generator'); ?></span>
                        <?php if (!$kb_is_pro) : ?>
                            <a
                                class="button aipkit_btn aipkit_btn-primary aipkit_settings_kb_upgrade_btn aipkit_settings_kb_header_upgrade_btn"
                                href="<?php echo esc_url($kb_upgrade_url); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                            </a>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="aipkit_form-group aipkit_settings_simple_row aipkit_settings_kb_indexing_launcher_row">
            <label class="aipkit_form-label" for="aipkit_open_indexing_settings_modal">
                <span class="aipkit_settings_kb_label_line">
                    <span><?php esc_html_e('Content type rules', 'gpt3-ai-content-generator'); ?></span>
                </span>
                <span class="aipkit_form-label-helper"><?php esc_html_e('Embedding content rules.', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <div class="aipkit_settings_action_buttons">
                <button
                    type="button"
                    id="aipkit_open_indexing_settings_modal"
                    class="aipkit_btn aipkit_btn-secondary aipkit_settings_kb_indexing_modal_btn"
                    aria-haspopup="dialog"
                    aria-controls="aipkit_indexing_settings_modal"
                >
                    <?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>
            </div>
        </div>
        <div
            class="aipkit-modal-overlay aipkit_settings_kb_indexing_modal"
            id="aipkit_indexing_settings_modal"
            aria-hidden="true"
        >
            <div
                class="aipkit-modal-content aipkit-modal-shell aipkit_settings_kb_indexing_modal_content"
                role="dialog"
                aria-modal="true"
                aria-labelledby="aipkit_indexing_settings_modal_title"
                aria-describedby="aipkit_indexing_settings_modal_desc"
                tabindex="-1"
            >
                <div class="aipkit-modal-header aipkit-modal-shell-header">
                    <div class="aipkit-modal-shell-intro">
                        <h2 class="aipkit-modal-shell-title" id="aipkit_indexing_settings_modal_title">
                            <span class="aipkit_settings_kb_modal_title_line">
                                <span><?php esc_html_e('Content type rules', 'gpt3-ai-content-generator'); ?></span>
                            </span>
                        </h2>
                        <p class="aipkit-modal-shell-copy" id="aipkit_indexing_settings_modal_desc"><?php esc_html_e('Embedding content rules.', 'gpt3-ai-content-generator'); ?></p>
                    </div>
                    <div class="aipkit-modal-shell-actions">
                        <button
                            type="button"
                            class="aipkit-modal-close-btn aipkit-modal-shell-close"
                            id="aipkit_indexing_settings_modal_close"
                            aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>"
                        >
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
                <div class="aipkit-modal-body aipkit-modal-shell-body aipkit_settings_kb_indexing_modal_body">
                    <?php if (!$kb_is_pro) : ?>
                        <div class="aipkit_settings_kb_pro_notice">
                            <div class="aipkit_settings_kb_pro_notice_copy">
                                <span class="aipkit_settings_apps_upsell_badge"><?php esc_html_e('Pro', 'gpt3-ai-content-generator'); ?></span>
                                <span><?php esc_html_e('Upgrade to edit content type rules.', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <a
                                class="button aipkit_btn aipkit_btn-primary aipkit_settings_kb_upgrade_btn"
                                href="<?php echo esc_url($kb_upgrade_url); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?php esc_html_e('Upgrade', 'gpt3-ai-content-generator'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div id="indexing-settings-tab-content" data-initialized="false">
                        <form id="aipkit_indexing_settings_form" onsubmit="return false;">
                            <div id="aipkit_indexing_settings_form_container"></div>
                        </form>
                    </div>
                </div>
            </div>
            </div>
        </section>

</div>

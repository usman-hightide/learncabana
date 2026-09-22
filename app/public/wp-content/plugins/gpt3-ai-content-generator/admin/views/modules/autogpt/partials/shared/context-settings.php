<?php
/**
 * Shared AutoGPT context settings renderer for content-writing and content-enhancement.
 *
 * Expected variables:
 * - $aipkit_autogpt_context_config (array)
 * - $embedding_provider_options (array)
 * - $default_embedding_provider_key (string)
 * - $openai_vector_stores (array)
 * - $pinecone_indexes (array)
 * - $qdrant_collections (array)
 * - $chroma_collections (array)
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$aipkit_autogpt_context_config = isset($aipkit_autogpt_context_config) && is_array($aipkit_autogpt_context_config)
    ? $aipkit_autogpt_context_config
    : [];

$aipkit_autogpt_context_scope = isset($aipkit_autogpt_context_config['scope'])
    ? (string) $aipkit_autogpt_context_config['scope']
    : 'cw';
$aipkit_autogpt_context_name_prefix = isset($aipkit_autogpt_context_config['name_prefix'])
    ? (string) $aipkit_autogpt_context_config['name_prefix']
    : '';
$aipkit_autogpt_context_root_classes = isset($aipkit_autogpt_context_config['root_classes'])
    ? (string) $aipkit_autogpt_context_config['root_classes']
    : 'aipkit_cw_vector_section aipkit_task_cw_kb_section';
$aipkit_autogpt_context_show_confidence = !empty($aipkit_autogpt_context_config['show_confidence_threshold']);
$aipkit_autogpt_context_use_autosave = !empty($aipkit_autogpt_context_config['use_autosave_class']);
$aipkit_autogpt_context_top_k_default = isset($aipkit_autogpt_context_config['top_k_default'])
    ? (int) $aipkit_autogpt_context_config['top_k_default']
    : 3;
$aipkit_autogpt_context_confidence_default = isset($aipkit_autogpt_context_config['confidence_default'])
    ? (int) $aipkit_autogpt_context_config['confidence_default']
    : 20;
$chroma_collections = isset($chroma_collections) && is_array($chroma_collections) ? $chroma_collections : [];

$aipkit_autogpt_context_base = 'aipkit_task_' . $aipkit_autogpt_context_scope;
$aipkit_autogpt_context_enable_id = $aipkit_autogpt_context_base . '_enable_vector_store';
$aipkit_autogpt_context_provider_field_id = $aipkit_autogpt_context_base . '_vector_store_provider';
$aipkit_autogpt_context_mode_control_id = $aipkit_autogpt_context_base . '_kb_mode_control';
$aipkit_autogpt_context_source_row_id = $aipkit_autogpt_context_base . '_kb_source_row';
$aipkit_autogpt_context_source_label_id = $aipkit_autogpt_context_base . '_kb_source_label';
$aipkit_autogpt_context_openai_panel_id = $aipkit_autogpt_context_base . '_openai_vector_store_panel';
$aipkit_autogpt_context_openai_select_id = $aipkit_autogpt_context_base . '_openai_vector_store_ids';
$aipkit_autogpt_context_pinecone_select_id = $aipkit_autogpt_context_base . '_pinecone_index_name';
$aipkit_autogpt_context_qdrant_select_id = $aipkit_autogpt_context_base . '_qdrant_collection_name';
$aipkit_autogpt_context_chroma_select_id = $aipkit_autogpt_context_base . '_chroma_collection_name';
$aipkit_autogpt_context_settings_trigger_id = $aipkit_autogpt_context_base . '_kb_settings_trigger';
$aipkit_autogpt_context_settings_popover_id = $aipkit_autogpt_context_base . '_kb_settings_popover';
$aipkit_autogpt_context_embedding_section_id = $aipkit_autogpt_context_base . '_kb_embedding_section';
$aipkit_autogpt_context_embedding_provider_id = $aipkit_autogpt_context_base . '_vector_embedding_provider';
$aipkit_autogpt_context_embedding_model_id = $aipkit_autogpt_context_base . '_vector_embedding_model';
$aipkit_autogpt_context_top_k_id = $aipkit_autogpt_context_base . '_vector_store_top_k';
$aipkit_autogpt_context_confidence_id = $aipkit_autogpt_context_base . '_vector_store_confidence_threshold';

$aipkit_autogpt_context_autosave_class = $aipkit_autogpt_context_use_autosave ? ' aipkit_autosave_trigger' : '';
?>
<div class="<?php echo esc_attr($aipkit_autogpt_context_root_classes); ?>">
    <input
        type="checkbox"
        id="<?php echo esc_attr($aipkit_autogpt_context_enable_id); ?>"
        name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'enable_vector_store'); ?>"
        class="<?php echo esc_attr('aipkit_task_' . $aipkit_autogpt_context_scope . '_vector_store_toggle' . $aipkit_autogpt_context_autosave_class); ?>"
        value="1"
        hidden
    >
    <input
        type="hidden"
        id="<?php echo esc_attr($aipkit_autogpt_context_provider_field_id); ?>"
        name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'vector_store_provider'); ?>"
        class="<?php echo esc_attr(($aipkit_autogpt_context_scope === 'cw' ? 'aipkit_task_cw_vector_store_provider_field' : '') . $aipkit_autogpt_context_autosave_class); ?>"
        value="openai"
    >

    <div class="aipkit_cw_kb_row aipkit_cw_kb_row--mode">
        <label class="aipkit_cw_panel_label" for="<?php echo esc_attr($aipkit_autogpt_context_mode_control_id); ?>">
            <?php esc_html_e('Context', 'gpt3-ai-content-generator'); ?>
        </label>
        <div class="aipkit_cw_kb_control">
            <select
                id="<?php echo esc_attr($aipkit_autogpt_context_mode_control_id); ?>"
                class="aipkit_form-input aipkit_cw_blended_chevron_select"
            >
                <option value="off"><?php esc_html_e('Off', 'gpt3-ai-content-generator'); ?></option>
                <option value="openai"><?php esc_html_e('OpenAI', 'gpt3-ai-content-generator'); ?></option>
                <option value="pinecone"><?php esc_html_e('Pinecone', 'gpt3-ai-content-generator'); ?></option>
                <option value="qdrant"><?php esc_html_e('Qdrant', 'gpt3-ai-content-generator'); ?></option>
                <option value="chroma"><?php esc_html_e('Chroma', 'gpt3-ai-content-generator'); ?></option>
            </select>
        </div>
    </div>

    <div class="aipkit_cw_kb_row aipkit_cw_kb_row--source" id="<?php echo esc_attr($aipkit_autogpt_context_source_row_id); ?>" hidden>
        <label
            class="aipkit_cw_panel_label"
            id="<?php echo esc_attr($aipkit_autogpt_context_source_label_id); ?>"
            for="<?php echo esc_attr($aipkit_autogpt_context_openai_select_id); ?>"
        >
            <?php esc_html_e('Source', 'gpt3-ai-content-generator'); ?>
        </label>
        <div class="aipkit_cw_kb_control aipkit_cw_kb_control--selection">
            <div class="aipkit_cw_kb_selection_inline">
                <div class="aipkit_cw_kb_source_fields">
                    <div class="<?php echo esc_attr('aipkit_task_' . $aipkit_autogpt_context_scope . '_vector_openai_field'); ?>" hidden>
                        <div
                            class="aipkit_popover_multiselect aipkit_vector_multiselect"
                            data-aipkit-vector-stores-dropdown
                            data-placeholder="<?php echo esc_attr__('Select stores', 'gpt3-ai-content-generator'); ?>"
                            data-selected-label="<?php echo esc_attr__('selected', 'gpt3-ai-content-generator'); ?>"
                        >
                            <button
                                type="button"
                                class="aipkit_popover_multiselect_btn aipkit_vector_multiselect_btn"
                                aria-expanded="false"
                                aria-controls="<?php echo esc_attr($aipkit_autogpt_context_openai_panel_id); ?>"
                            >
                                <span class="aipkit_popover_multiselect_label">
                                    <?php esc_html_e('Select stores', 'gpt3-ai-content-generator'); ?>
                                </span>
                            </button>
                            <div
                                id="<?php echo esc_attr($aipkit_autogpt_context_openai_panel_id); ?>"
                                class="aipkit_popover_multiselect_panel"
                                role="menu"
                                hidden
                            >
                                <div class="aipkit_popover_multiselect_options"></div>
                            </div>
                        </div>
                        <select
                            id="<?php echo esc_attr($aipkit_autogpt_context_openai_select_id); ?>"
                            name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'openai_vector_store_ids[]'); ?>"
                            class="<?php echo esc_attr('aipkit_popover_multiselect_select' . $aipkit_autogpt_context_autosave_class); ?>"
                            multiple
                            size="3"
                            hidden
                            aria-hidden="true"
                            tabindex="-1"
                        >
                            <?php if (!empty($openai_vector_stores)) : ?>
                                <?php foreach ($openai_vector_stores as $store) : ?>
                                    <option value="<?php echo esc_attr($store['id']); ?>">
                                        <?php echo esc_html($store['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>
                                    <?php esc_html_e('No stores found', 'gpt3-ai-content-generator'); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="<?php echo esc_attr('aipkit_task_' . $aipkit_autogpt_context_scope . '_vector_pinecone_field'); ?>" hidden>
                        <select
                            id="<?php echo esc_attr($aipkit_autogpt_context_pinecone_select_id); ?>"
                            name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'pinecone_index_name'); ?>"
                            class="<?php echo esc_attr('aipkit_form-input aipkit_vector_settings_select' . $aipkit_autogpt_context_autosave_class); ?>"
                            aria-label="<?php esc_attr_e('Pinecone index', 'gpt3-ai-content-generator'); ?>"
                        >
                            <option value=""><?php esc_html_e('Select index', 'gpt3-ai-content-generator'); ?></option>
                            <?php if (!empty($pinecone_indexes)) : ?>
                                <?php foreach ($pinecone_indexes as $index) : ?>
                                    <option value="<?php echo esc_attr($index['name']); ?>">
                                        <?php echo esc_html($index['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>
                                    <?php esc_html_e('No indexes found', 'gpt3-ai-content-generator'); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="<?php echo esc_attr('aipkit_task_' . $aipkit_autogpt_context_scope . '_vector_qdrant_field'); ?>" hidden>
                        <select
                            id="<?php echo esc_attr($aipkit_autogpt_context_qdrant_select_id); ?>"
                            name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'qdrant_collection_name'); ?>"
                            class="<?php echo esc_attr('aipkit_form-input aipkit_vector_settings_select' . $aipkit_autogpt_context_autosave_class); ?>"
                            aria-label="<?php esc_attr_e('Qdrant collection', 'gpt3-ai-content-generator'); ?>"
                        >
                            <option value=""><?php esc_html_e('Select collection', 'gpt3-ai-content-generator'); ?></option>
                            <?php if (!empty($qdrant_collections)) : ?>
                                <?php foreach ($qdrant_collections as $collection) : ?>
                                    <option value="<?php echo esc_attr($collection['name']); ?>">
                                        <?php echo esc_html($collection['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>
                                    <?php esc_html_e('No collections found', 'gpt3-ai-content-generator'); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="<?php echo esc_attr('aipkit_task_' . $aipkit_autogpt_context_scope . '_vector_chroma_field'); ?>" hidden>
                        <select
                            id="<?php echo esc_attr($aipkit_autogpt_context_chroma_select_id); ?>"
                            name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'chroma_collection_name'); ?>"
                            class="<?php echo esc_attr('aipkit_form-input aipkit_vector_settings_select' . $aipkit_autogpt_context_autosave_class); ?>"
                            aria-label="<?php esc_attr_e('Chroma collection', 'gpt3-ai-content-generator'); ?>"
                        >
                            <option value=""><?php esc_html_e('Select collection', 'gpt3-ai-content-generator'); ?></option>
                            <?php if (!empty($chroma_collections)) : ?>
                                <?php foreach ($chroma_collections as $collection) : ?>
                                    <?php
                                    $aipkit_chroma_collection_name = is_array($collection)
                                        ? ($collection['name'] ?? ($collection['collection_name'] ?? ($collection['id'] ?? '')))
                                        : (string) $collection;
                                    if ($aipkit_chroma_collection_name === '') {
                                        continue;
                                    }
                                    ?>
                                    <option value="<?php echo esc_attr($aipkit_chroma_collection_name); ?>">
                                        <?php echo esc_html($aipkit_chroma_collection_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>
                                    <?php esc_html_e('No collections found', 'gpt3-ai-content-generator'); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <button
                    type="button"
                    class="aipkit_cw_settings_icon_trigger"
                    id="<?php echo esc_attr($aipkit_autogpt_context_settings_trigger_id); ?>"
                    data-aipkit-popover-target="<?php echo esc_attr($aipkit_autogpt_context_settings_popover_id); ?>"
                    data-aipkit-popover-placement="left"
                    aria-controls="<?php echo esc_attr($aipkit_autogpt_context_settings_popover_id); ?>"
                    aria-expanded="false"
                    aria-label="<?php esc_attr_e('Context settings', 'gpt3-ai-content-generator'); ?>"
                    title="<?php esc_attr_e('Context settings', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="aipkit_model_settings_popover aipkit_cw_settings_popover" id="<?php echo esc_attr($aipkit_autogpt_context_settings_popover_id); ?>" aria-hidden="true">
    <div class="aipkit_model_settings_popover_panel aipkit_cw_settings_popover_panel aipkit_cw_kb_settings_popover_panel" role="dialog" aria-label="<?php esc_attr_e('Context settings', 'gpt3-ai-content-generator'); ?>">
        <div class="aipkit_model_settings_popover_header aipkit_cw_settings_sheet_header">
            <span class="aipkit_model_settings_popover_title"><?php esc_html_e('Context settings', 'gpt3-ai-content-generator'); ?></span>
        </div>
        <div class="aipkit_model_settings_popover_body aipkit_cw_settings_popover_body aipkit_cw_settings_sheet_body">
            <div class="aipkit_popover_options_list">
                <div id="<?php echo esc_attr($aipkit_autogpt_context_embedding_section_id); ?>" hidden>
                    <div class="aipkit_popover_option_row aipkit_popover_option_row--force-divider">
                        <div class="aipkit_popover_option_main">
                            <div class="aipkit_cw_settings_option_text">
                                <label class="aipkit_popover_option_label" for="<?php echo esc_attr($aipkit_autogpt_context_embedding_provider_id); ?>">
                                    <?php esc_html_e('Embedding Provider', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <span class="aipkit_popover_option_helper">
                                    <?php esc_html_e('Provider for embeddings.', 'gpt3-ai-content-generator'); ?>
                                </span>
                            </div>
                            <select
                                id="<?php echo esc_attr($aipkit_autogpt_context_embedding_provider_id); ?>"
                                name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'vector_embedding_provider'); ?>"
                                class="<?php echo esc_attr('aipkit_popover_option_select aipkit_popover_option_select--fit aipkit_cw_blended_chevron_select' . $aipkit_autogpt_context_autosave_class); ?>"
                            >
                                <?php foreach ($embedding_provider_options as $provider_key => $provider_label) : ?>
                                    <option value="<?php echo esc_attr($provider_key); ?>" <?php selected($provider_key, $default_embedding_provider_key); ?>>
                                        <?php echo esc_html($provider_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="aipkit_popover_option_row aipkit_popover_option_row--force-divider">
                        <div class="aipkit_popover_option_main">
                            <div class="aipkit_cw_settings_option_text">
                                <label class="aipkit_popover_option_label" for="<?php echo esc_attr($aipkit_autogpt_context_embedding_model_id); ?>">
                                    <?php esc_html_e('Embedding Model', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <span class="aipkit_popover_option_helper">
                                    <?php esc_html_e('Model for embeddings.', 'gpt3-ai-content-generator'); ?>
                                </span>
                            </div>
                            <select
                                id="<?php echo esc_attr($aipkit_autogpt_context_embedding_model_id); ?>"
                                name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'vector_embedding_model'); ?>"
                                class="<?php echo esc_attr('aipkit_popover_option_select aipkit_popover_option_select--fit aipkit_cw_blended_chevron_select' . $aipkit_autogpt_context_autosave_class); ?>"
                                disabled
                            >
                                <option value=""><?php esc_html_e('Select provider first', 'gpt3-ai-content-generator'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="aipkit_popover_option_row">
                    <div class="aipkit_popover_option_main">
                        <div class="aipkit_cw_settings_option_text">
                            <label class="aipkit_popover_option_label" for="<?php echo esc_attr($aipkit_autogpt_context_top_k_id); ?>">
                                <?php esc_html_e('Results Limit', 'gpt3-ai-content-generator'); ?>
                            </label>
                            <span class="aipkit_popover_option_helper">
                                <?php esc_html_e('How many matches to use.', 'gpt3-ai-content-generator'); ?>
                            </span>
                        </div>
                        <input
                            type="number"
                            id="<?php echo esc_attr($aipkit_autogpt_context_top_k_id); ?>"
                            name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'vector_store_top_k'); ?>"
                            class="<?php echo esc_attr('aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--compact' . $aipkit_autogpt_context_autosave_class); ?>"
                            value="<?php echo esc_attr($aipkit_autogpt_context_top_k_default); ?>"
                            min="1"
                            max="20"
                            step="1"
                        >
                    </div>
                </div>

                <?php if ($aipkit_autogpt_context_show_confidence) : ?>
                    <div class="aipkit_popover_option_row">
                        <div class="aipkit_popover_option_main">
                            <div class="aipkit_cw_settings_option_text">
                                <label class="aipkit_popover_option_label" for="<?php echo esc_attr($aipkit_autogpt_context_confidence_id); ?>">
                                    <?php esc_html_e('Confidence Threshold', 'gpt3-ai-content-generator'); ?>
                                </label>
                                <span class="aipkit_popover_option_helper">
                                    <?php esc_html_e('Minimum confidence to include.', 'gpt3-ai-content-generator'); ?>
                                </span>
                            </div>
                            <input
                                type="number"
                                id="<?php echo esc_attr($aipkit_autogpt_context_confidence_id); ?>"
                                name="<?php echo esc_attr($aipkit_autogpt_context_name_prefix . 'vector_store_confidence_threshold'); ?>"
                                class="<?php echo esc_attr('aipkit_form-input aipkit_popover_option_input aipkit_popover_option_input--compact' . $aipkit_autogpt_context_autosave_class); ?>"
                                value="<?php echo esc_attr($aipkit_autogpt_context_confidence_default); ?>"
                                min="0"
                                max="100"
                                step="1"
                            >
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

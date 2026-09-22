<?php
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.
?>
<section
    id="aipkit_sources_training_card"
    <?php echo ($enable_vector_store === '1') ? '' : 'hidden'; ?>
>
    <div class="aipkit_builder_meta aipkit_training_meta">
        <span class="aipkit_training_status" id="aipkit_training_status" aria-live="polite"></span>
    </div>
    <div class="aipkit_training_intro">
        <p class="aipkit_training_intro_title">
            <?php esc_html_e('Training Data', 'gpt3-ai-content-generator'); ?>
        </p>
        <p class="aipkit_training_intro_help">
            <?php esc_html_e('Add content to this chatbot knowledge base.', 'gpt3-ai-content-generator'); ?>
        </p>
    </div>
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
                    <label class="aipkit_training_field_label" for="aipkit_training_qa_question">
                        <?php esc_html_e('Question', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <textarea
                        id="aipkit_training_qa_question"
                        class="aipkit_builder_textarea aipkit_training_textarea"
                        rows="3"
                        placeholder="<?php esc_attr_e('Question: What is your refund policy?', 'gpt3-ai-content-generator'); ?>"
                    ></textarea>
                </div>
                <div class="aipkit_training_field">
                    <label class="aipkit_training_field_label" for="aipkit_training_qa_answer">
                        <?php esc_html_e('Answer', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <textarea
                        id="aipkit_training_qa_answer"
                        class="aipkit_builder_textarea aipkit_training_textarea"
                        rows="3"
                        placeholder="<?php esc_attr_e('Answer: We offer refunds within 30 days of purchase.', 'gpt3-ai-content-generator'); ?>"
                    ></textarea>
                </div>
            </div>
        </div>
        <div class="aipkit_builder_tab_panel" data-aipkit-panel="text" hidden>
            <div class="aipkit_training_field">
                <label class="aipkit_training_field_label" for="aipkit_training_text_input">
                    <?php esc_html_e('Text', 'gpt3-ai-content-generator'); ?>
                </label>
                <textarea
                    id="aipkit_training_text_input"
                    name="training_text"
                    class="aipkit_builder_textarea aipkit_training_textarea aipkit_training_text_input"
                    rows="4"
                    placeholder="<?php esc_attr_e('Add training text...', 'gpt3-ai-content-generator'); ?>"
                ></textarea>
            </div>
        </div>
        <div class="aipkit_builder_tab_panel" data-aipkit-panel="files" hidden>
            <div class="aipkit_training_field">
                <p class="aipkit_training_field_label aipkit_training_field_label--static">
                    <?php esc_html_e('Files', 'gpt3-ai-content-generator'); ?>
                </p>
                <div class="aipkit_builder_dropzone aipkit_training_dropzone">
                    <div class="aipkit_builder_dropzone_inner">
                        <?php if ($is_pro_plan) : ?>
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
                                href="<?php echo esc_url($pricing_url); ?>"
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
                data-upgrade-url="<?php echo esc_url($pricing_url); ?>"
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
                            <div
                                class="aipkit_training_site_dropdown"
                                data-aipkit-training-types="bulk"
                                data-placeholder="<?php echo esc_attr__('Select types', 'gpt3-ai-content-generator'); ?>"
                                data-selected-label="<?php echo esc_attr__('selected', 'gpt3-ai-content-generator'); ?>"
                            >
                                <button
                                    type="button"
                                    class="aipkit_training_site_dropdown_btn"
                                    aria-label="<?php esc_attr_e('Content types', 'gpt3-ai-content-generator'); ?>"
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
                            <select id="aipkit_vs_wp_content_post_types" class="aipkit_training_site_hidden_select" multiple size="3">
                                <?php foreach ($all_selectable_post_types as $post_type_slug => $post_type_obj) : ?>
                                    <option value="<?php echo esc_attr($post_type_slug); ?>" <?php selected(in_array($post_type_slug, ['post', 'page'], true)); ?>>
                                        <?php echo esc_html($post_type_obj->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="aipkit_wp_content_specific_types_panel" class="aipkit_training_site_field" hidden>
                            <div
                                class="aipkit_training_site_dropdown"
                                data-aipkit-training-types="specific"
                                data-placeholder="<?php echo esc_attr__('Select types', 'gpt3-ai-content-generator'); ?>"
                                data-selected-label="<?php echo esc_attr__('selected', 'gpt3-ai-content-generator'); ?>"
                            >
                                <button
                                    type="button"
                                    class="aipkit_training_site_dropdown_btn"
                                    aria-label="<?php esc_attr_e('Content types', 'gpt3-ai-content-generator'); ?>"
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
                                                <input type="checkbox" class="aipkit_wp_type_cb_specific" value="<?php echo esc_attr($post_type_slug); ?>" <?php checked(in_array($post_type_slug, ['post', 'page'], true)); ?> />
                                                <span class="aipkit_training_site_check_label"><?php echo esc_html($post_type_obj->label); ?></span>
                                                <span class="aipkit_count_badge" data-count="-1"></span>
                                            </label>
                                        <?php endforeach; ?>
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
                        </div>
                        <div class="aipkit_training_site_actions">
                            <button
                                type="button"
                                class="aipkit_popover_footer_btn aipkit_popover_footer_btn--secondary aipkit_builder_action_btn aipkit_training_action_btn aipkit_training_website_action_btn"
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
                                    <?php esc_html_e('Yes, run in background', 'gpt3-ai-content-generator'); ?>
                                </button>
                                <button type="button" id="aipkit_background_indexing_no" class="aipkit_btn aipkit_btn-secondary aipkit_btn-sm">
                                    <?php esc_html_e('No, run now', 'gpt3-ai-content-generator'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="aipkit_wp_content_specific_panel" class="aipkit_training_site_panel aipkit_training_site_browser" hidden>
                        <div class="aipkit_training_site_browser_header">
                            <span class="aipkit_training_site_label"><?php esc_html_e('Items', 'gpt3-ai-content-generator'); ?></span>
                        </div>
                        <div id="aipkit_vs_wp_content_list_area" class="aipkit_training_site_list">
                            <p class="aipkit_builder_help_text">
                                <?php esc_html_e('Select criteria to load content.', 'gpt3-ai-content-generator'); ?>
                            </p>
                        </div>
                        <div id="aipkit_vs_wp_content_pagination" class="aipkit_training_site_pagination"></div>
                    </div>

                    <div id="aipkit_vs_wp_content_messages_area" class="aipkit_form-help aipkit_training_site_status" aria-live="polite"></div>
                    <select id="aipkit_vs_global_target_select" class="aipkit_training_site_target_select" aria-hidden="true" tabindex="-1">
                        <option value=""></option>
                    </select>
            </div>
        </div>
    </div>

    <div class="aipkit_training_footer">
        <div class="aipkit_builder_action_row aipkit_training_action_row">
            <div class="aipkit_builder_action_group aipkit_training_primary_actions">
                <button
                    type="button"
                    class="aipkit_popover_footer_btn aipkit_popover_footer_btn--secondary aipkit_builder_action_btn aipkit_training_action_btn"
                    data-training-action="add"
                >
                    <?php esc_html_e('Add Q&A', 'gpt3-ai-content-generator'); ?>
                </button>
                <button
                    type="button"
                    class="aipkit_popover_footer_btn aipkit_popover_footer_btn--danger aipkit_builder_action_btn aipkit_training_stop_btn"
                    hidden
                >
                    <?php esc_html_e('Stop', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>
            <div class="aipkit_builder_action_group aipkit_training_sources_row">
                <button
                    type="button"
                    class="aipkit_training_sources_btn aipkit_builder_sheet_trigger"
                    data-base-label="<?php echo esc_attr__('Sources', 'gpt3-ai-content-generator'); ?>"
                    data-sheet-title="<?php echo esc_attr__('Sources', 'gpt3-ai-content-generator'); ?>"
                    data-sheet-description="<?php echo esc_attr__('Browse and manage training sources for this chatbot.', 'gpt3-ai-content-generator'); ?>"
                    data-sheet-content="sources"
                >
                    <span class="aipkit_training_sources_label">
                        <?php echo esc_html__('Sources', 'gpt3-ai-content-generator'); ?>
                    </span>
                    <span class="aipkit_training_sources_count" aria-hidden="true">0</span>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</section>

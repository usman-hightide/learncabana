<?php
/**
 * Main UI for Task Automation.
 * Includes the form for creating/editing tasks and the list of existing tasks/queue.
 * Variable definitions are now in admin/views/modules/autogpt/index.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_container-body">
    <div id="aipkit_automated_task_form_wrapper">
        <div class="aipkit_task_form_container">
            <form id="aipkit_automated_task_form" onsubmit="return false;">
                <input type="hidden" name="task_id" id="aipkit_automated_task_id" value="">
                <input type="hidden" name="task_name" id="aipkit_automated_task_name" value="">

                <div class="aipkit_wizard_content_container">
                    <div class="aipkit_autogpt_form_layout">
                        <div class="aipkit_autogpt_form_sidebar aipkit_autogpt_form_left">
                            <?php include __DIR__ . '/shared/category-selector.php'; ?>
                        </div>
                        <div class="aipkit_autogpt_form_main aipkit_autogpt_form_shell">
                            <div class="aipkit_autogpt_form_shell_body">
                                <div class="aipkit_wizard_content_step" data-content-id="task_form_setup">
                                    <?php include __DIR__ . '/task-form-setup.php'; ?>
                                </div>
                                <div class="aipkit_wizard_content_step" data-content-id="task_config_comment_reply">
                                    <?php include __DIR__ . '/task-form-config-comment-reply.php'; ?>
                                </div>
                            </div>
                            <div
                                id="aipkit_autogpt_editor_actions"
                                class="aipkit_form_editor_actions aipkit_autogpt_editor_dock aipkit_content_writer_output_actions aipkit_cw_output_dock"
                                style="display: none;"
                            >
                                <div class="aipkit_cw_output_dock_main">
                                    <div class="aipkit_cw_output_actions_row aipkit_autogpt_editor_actions_row">
                                        <button type="button" id="aipkit_cancel_edit_task_btn" class="aipkit_btn aipkit_btn-secondary aipkit_cw_output_action_btn">
                                            <?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?>
                                        </button>
                                        <button type="submit" id="aipkit_save_task_btn" class="aipkit_btn aipkit_btn-primary aipkit_cw_output_action_btn" form="aipkit_automated_task_form">
                                            <span class="aipkit_btn-text"><?php esc_html_e('Save', 'gpt3-ai-content-generator'); ?></span>
                                            <span class="aipkit_spinner" style="display:none;"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="aipkit_autogpt_form_right aipkit_cw_inspector_stack aipkit_autogpt_inspector_stack">
                            <section
                                id="aipkit_autogpt_schedule_card"
                                class="aipkit_cw_inspector_card aipkit_autogpt_inspector_card aipkit_autogpt_inspector_card--schedule"
                                data-aipkit-autogpt-inspector-card="schedule"
                                data-status="active"
                            >
                                <div class="aipkit_cw_inspector_card_header">
                                    <button
                                        type="button"
                                        class="aipkit_autogpt_inspector_card_toggle"
                                        data-aipkit-autogpt-card-toggle="schedule"
                                        aria-expanded="true"
                                        aria-controls="aipkit_autogpt_schedule_card_body"
                                    >
                                        <span class="aipkit_cw_inspector_card_header_copy">
                                            <span class="aipkit_cw_inspector_card_title"><?php esc_html_e('Schedule', 'gpt3-ai-content-generator'); ?></span>
                                        </span>
                                        <span class="aipkit_autogpt_inspector_card_toggle_icon" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div id="aipkit_autogpt_schedule_card_body" class="aipkit_cw_inspector_card_body aipkit_autogpt_inspector_card_body aipkit_autogpt_inspector_card_body--schedule">
                                    <div class="aipkit_wizard_content_step aipkit_autogpt_sidebar_step" data-content-id="task_config_status">
                                        <?php include __DIR__ . '/task-form-config-status.php'; ?>
                                    </div>
                                </div>
                            </section>

                            <section
                                id="aipkit_autogpt_setup_card"
                                class="aipkit_cw_inspector_card aipkit_autogpt_inspector_card aipkit_autogpt_inspector_card--setup"
                                data-aipkit-autogpt-inspector-card="setup"
                            >
                                <div class="aipkit_cw_inspector_card_header">
                                    <button
                                        type="button"
                                        class="aipkit_autogpt_inspector_card_toggle"
                                        data-aipkit-autogpt-card-toggle="setup"
                                        aria-expanded="true"
                                        aria-controls="aipkit_autogpt_setup_card_body"
                                    >
                                        <span class="aipkit_cw_inspector_card_header_copy">
                                            <span class="aipkit_cw_inspector_card_title"><?php esc_html_e('General', 'gpt3-ai-content-generator'); ?></span>
                                        </span>
                                        <span class="aipkit_autogpt_inspector_card_toggle_icon" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div id="aipkit_autogpt_setup_card_body" class="aipkit_cw_inspector_card_body aipkit_autogpt_inspector_card_body aipkit_autogpt_inspector_card_body--setup">
                                    <div class="aipkit_autogpt_setup_sections">
                                        <div
                                            class="aipkit_autogpt_setup_section"
                                            data-aipkit-autogpt-setup-section="content_writing"
                                            hidden
                                        >
                                            <?php include __DIR__ . '/content-writing/setup-model-row.php'; ?>
                                        </div>
                                        <div
                                            class="aipkit_autogpt_setup_section"
                                            data-aipkit-autogpt-setup-section="enhance_existing_content"
                                            hidden
                                        >
                                            <?php include __DIR__ . '/content-enhancement/setup-model-row.php'; ?>
                                        </div>
                                        <div
                                            class="aipkit_autogpt_setup_section"
                                            data-aipkit-autogpt-setup-section="community_reply_comments"
                                            hidden
                                        >
                                            <?php include __DIR__ . '/community-engagement/setup-model-row.php'; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section
                                id="aipkit_autogpt_media_card"
                                class="aipkit_cw_inspector_card aipkit_autogpt_inspector_card aipkit_autogpt_inspector_card--media"
                                data-aipkit-autogpt-inspector-card="media"
                            >
                                <div class="aipkit_cw_inspector_card_header">
                                    <button
                                        type="button"
                                        class="aipkit_autogpt_inspector_card_toggle"
                                        data-aipkit-autogpt-card-toggle="media"
                                        aria-expanded="false"
                                        aria-controls="aipkit_autogpt_media_card_body"
                                    >
                                        <span class="aipkit_cw_inspector_card_header_copy">
                                            <span class="aipkit_cw_inspector_card_title"><?php esc_html_e('Media', 'gpt3-ai-content-generator'); ?></span>
                                        </span>
                                        <span class="aipkit_autogpt_inspector_card_toggle_icon" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div id="aipkit_autogpt_media_card_body" class="aipkit_cw_inspector_card_body aipkit_autogpt_inspector_card_body aipkit_autogpt_inspector_card_body--media">
                                    <?php include __DIR__ . '/content-writing/image-settings.php'; ?>
                                </div>
                            </section>

                            <section
                                id="aipkit_autogpt_advanced_card"
                                class="aipkit_cw_inspector_card aipkit_autogpt_inspector_card aipkit_autogpt_inspector_card--advanced"
                                data-aipkit-autogpt-inspector-card="advanced"
                            >
                                <div class="aipkit_cw_inspector_card_header">
                                    <button
                                        type="button"
                                        class="aipkit_autogpt_inspector_card_toggle"
                                        data-aipkit-autogpt-card-toggle="advanced"
                                        aria-expanded="false"
                                        aria-controls="aipkit_autogpt_advanced_card_body"
                                    >
                                        <span class="aipkit_cw_inspector_card_header_copy">
                                            <span class="aipkit_cw_inspector_card_title"><?php esc_html_e('Advanced', 'gpt3-ai-content-generator'); ?></span>
                                        </span>
                                        <span class="aipkit_autogpt_inspector_card_toggle_icon" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div id="aipkit_autogpt_advanced_card_body" class="aipkit_cw_inspector_card_body aipkit_autogpt_inspector_card_body aipkit_autogpt_inspector_card_body--advanced">
                                    <div class="aipkit_autogpt_advanced_sections">
                                        <div
                                            class="aipkit_autogpt_advanced_section"
                                            data-aipkit-autogpt-advanced-section="content_writing"
                                            hidden
                                        >
                                            <?php include __DIR__ . '/content-writing/knowledge-base-settings.php'; ?>
                                        </div>
                                        <div
                                            class="aipkit_autogpt_advanced_section"
                                            data-aipkit-autogpt-advanced-section="enhance_existing_content"
                                            hidden
                                        >
                                            <?php include __DIR__ . '/content-enhancement/knowledge-base-settings.php'; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                        </div>
                    </div>
                </div>


            </form>
        </div>
    </div>

    <?php include __DIR__ . '/task-list.php'; ?>

    <?php include __DIR__ . '/task-queue.php'; ?>

</div>

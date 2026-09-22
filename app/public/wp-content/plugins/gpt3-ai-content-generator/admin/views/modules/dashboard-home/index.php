<?php
/**
 * AIPKit Dashboard Home — Module Selection Landing
 */

use WPAICG\aipkit_dashboard;
use WPAICG\AIPKit_Role_Manager;

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$module_settings = aipkit_dashboard::get_module_settings();
$can_manage_modules = AIPKit_Role_Manager::user_can_manage_settings();
$can_access_settings = AIPKit_Role_Manager::user_can_access_module('settings');
$can_access_usage = AIPKit_Role_Manager::user_can_access_module('stats');

$modules = array(
    'chat_bot' => array(
        'label'       => __('Chatbots', 'gpt3-ai-content-generator'),
        'description' => __('Build assistants, configure behavior, and deploy chat experiences.', 'gpt3-ai-content-generator'),
        'icon'        => 'format-chat',
        'data_module' => 'chatbot',
    ),
    'content_writer' => array(
        'label'       => __('Content Writer', 'gpt3-ai-content-generator'),
        'description' => __('Create articles, pages, and product content from structured prompts.', 'gpt3-ai-content-generator'),
        'icon'        => 'edit',
        'data_module' => 'content-writer',
    ),
    'autogpt' => array(
        'label'       => __('Automations', 'gpt3-ai-content-generator'),
        'description' => __('Run scheduled workflows and recurring AI tasks with less manual work.', 'gpt3-ai-content-generator'),
        'icon'        => 'airplane',
        'data_module' => 'autogpt',
    ),
    'ai_forms' => array(
        'label'       => __('AI Forms', 'gpt3-ai-content-generator'),
        'description' => __('Collect user input and generate AI-powered responses.', 'gpt3-ai-content-generator'),
        'icon'        => 'feedback',
        'data_module' => 'ai-forms',
    ),
    'image_generator' => array(
        'label'       => __('Images', 'gpt3-ai-content-generator'),
        'description' => __('Generate and iterate on images using model-specific settings.', 'gpt3-ai-content-generator'),
        'icon'        => 'format-image',
        'data_module' => 'image-generator',
    ),
    'sources' => array(
        'label'       => __('Knowledge Base', 'gpt3-ai-content-generator'),
        'description' => __('Manage data sources, embeddings, and retrieval context for AI.', 'gpt3-ai-content-generator'),
        'icon'        => 'media-document',
        'data_module' => 'sources',
    ),
    'stats_viewer' => array(
        'label'       => __('Usage', 'gpt3-ai-content-generator'),
        'description' => __('Track token consumption, activity trends, and usage patterns.', 'gpt3-ai-content-generator'),
        'icon'        => 'chart-bar',
        'data_module' => 'stats',
    ),
);

$available_modules = array();
foreach ($modules as $option_key => $module) {
    if (!AIPKit_Role_Manager::user_can_access_module($module['data_module'])) {
        continue;
    }

    $is_enabled = !isset($module_settings[$option_key]) || !empty($module_settings[$option_key]);
    if (!$can_manage_modules && !$is_enabled) {
        continue;
    }

    $module['option_key'] = $option_key;
    $module['is_enabled'] = $is_enabled;
    $available_modules[] = $module;
}
?>
<div class="aipkit_home" id="aipkit_dashboard_home">
    <div class="aipkit_home_layout">
        <section class="aipkit_home_primary" aria-labelledby="aipkit_home_title">
            <header class="aipkit_home_hero">
                <p class="aipkit_home_eyebrow"><?php esc_html_e('AI Puffer', 'gpt3-ai-content-generator'); ?></p>
                <h2 class="aipkit_home_title" id="aipkit_home_title"><?php esc_html_e('Your AI engine for WordPress.', 'gpt3-ai-content-generator'); ?></h2>
                <p class="aipkit_home_summary">
                    <?php esc_html_e('Chat, write, automate, and generate — all in one workspace.', 'gpt3-ai-content-generator'); ?>
                </p>
            </header>

            <?php if (!empty($available_modules)): ?>
                <section class="aipkit_home_section aipkit_home_section--modules" aria-labelledby="aipkit_home_modules_title">
                    <div class="aipkit_home_section_header">
                        <div class="aipkit_home_section_header_copy">
                            <h3 class="aipkit_home_section_title" id="aipkit_home_modules_title"><?php esc_html_e('Modules', 'gpt3-ai-content-generator'); ?></h3>
                        </div>
                    </div>
                    <div class="aipkit_home_section_body">
                        <div class="aipkit_home_module_list" id="aipkit_home_module_list">
                            <?php foreach ($available_modules as $module): ?>
                                <?php
                                $row_class = 'aipkit_home_module_row';
                                if (!$module['is_enabled']) {
                                    $row_class .= ' is-disabled';
                                }
                                ?>
                                <article
                                    class="<?php echo esc_attr($row_class); ?>"
                                    data-module="<?php echo esc_attr($module['data_module']); ?>"
                                    data-module-label="<?php echo esc_attr($module['label']); ?>"
                                >
                                    <div
                                        class="aipkit_home_module_main"
                                        role="button"
                                        tabindex="<?php echo $module['is_enabled'] ? '0' : '-1'; ?>"
                                        aria-disabled="<?php echo $module['is_enabled'] ? 'false' : 'true'; ?>"
                                        <?php if ($module['is_enabled']): ?>
                                            data-aipkit-open-module="<?php echo esc_attr($module['data_module']); ?>"
                                        <?php endif; ?>
                                    >
                                        <div class="aipkit_home_module_text">
                                            <h4 class="aipkit_home_module_title"><?php echo esc_html($module['label']); ?></h4>
                                            <p class="aipkit_home_module_desc"><?php echo esc_html($module['description']); ?></p>
                                        </div>
                                    </div>

                                    <?php if ($can_manage_modules): ?>
                                        <?php
                                        $aipkit_module_toggle_label = sprintf(
                                            /* translators: %s: dashboard module label. */
                                            __('Enable %s module', 'gpt3-ai-content-generator'),
                                            $module['label']
                                        );
                                        ?>
                                        <div class="aipkit_home_module_controls">
                                            <label class="aipkit_home_toggle" for="aipkit_home_toggle_<?php echo esc_attr($module['option_key']); ?>">
                                                <span class="screen-reader-text"><?php esc_html_e('Enable module', 'gpt3-ai-content-generator'); ?></span>
                                                <span class="aipkit_home_toggle_switch">
                                                    <input
                                                        type="checkbox"
                                                        id="aipkit_home_toggle_<?php echo esc_attr($module['option_key']); ?>"
                                                        class="aipkit_home_toggle_input"
                                                        data-option-key="<?php echo esc_attr($module['option_key']); ?>"
                                                        data-module="<?php echo esc_attr($module['data_module']); ?>"
                                                        <?php checked($module['is_enabled']); ?>
                                                        aria-label="<?php echo esc_attr($aipkit_module_toggle_label); ?>"
                                                    />
                                                    <span class="aipkit_home_toggle_slider" aria-hidden="true"></span>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </section>

        <aside class="aipkit_home_sidebar">
            <section class="aipkit_home_panel aipkit_home_panel--setup">
                <div class="aipkit_home_panel_header">
                    <div class="aipkit_home_panel_header_copy">
                        <h3 class="aipkit_home_panel_title"><?php esc_html_e('Quick Setup', 'gpt3-ai-content-generator'); ?></h3>
                    </div>
                </div>
                <div class="aipkit_home_panel_body">
                    <ol class="aipkit_home_steps">
                        <?php if ($can_access_settings): ?>
                            <li>
                                <?php
                                $settings_link = '<a href="#" data-aipkit-open-module="settings">' . esc_html__('Settings', 'gpt3-ai-content-generator') . '</a>';
                                $aipkit_settings_step = sprintf(
                                    /* translators: %s: link to the Settings module. */
                                    __('Go to %s.', 'gpt3-ai-content-generator'),
                                    $settings_link
                                );
                                echo wp_kses(
                                    $aipkit_settings_step,
                                    [
                                        'a' => [
                                            'href' => [],
                                            'data-aipkit-open-module' => [],
                                        ],
                                    ]
                                );
                                ?>
                            </li>
                            <li><?php esc_html_e('Select your provider and enter your API key. That\'s it!', 'gpt3-ai-content-generator'); ?></li>
                        <?php else: ?>
                            <li><?php esc_html_e('Provider and API settings are managed by an administrator.', 'gpt3-ai-content-generator'); ?></li>
                            <li><?php esc_html_e('Ask an administrator to update global settings when needed.', 'gpt3-ai-content-generator'); ?></li>
                        <?php endif; ?>
                        <li>
                            <?php
                            $documentation_link = '<a href="https://docs.aipower.org/" target="_blank" rel="noopener noreferrer">' . esc_html__('documentation', 'gpt3-ai-content-generator') . '</a>';
                            $aipkit_documentation_step = sprintf(
                                /* translators: %s: link to AI Power documentation. */
                                __('Need help? Check out our %s.', 'gpt3-ai-content-generator'),
                                $documentation_link
                            );
                            echo wp_kses(
                                $aipkit_documentation_step,
                                [
                                    'a' => [
                                        'href' => [],
                                        'target' => [],
                                        'rel' => [],
                                    ],
                                ]
                            );
                            ?>
                        </li>
                    </ol>
                </div>
            </section>

            <?php if ($can_access_usage): ?>
                <section class="aipkit_home_panel aipkit_home_panel--chart" aria-labelledby="aipkit_home_chart_title">
                    <div class="aipkit_home_panel_header">
                        <div class="aipkit_home_panel_header_copy">
                            <h3 class="aipkit_home_panel_title" id="aipkit_home_chart_title"><?php esc_html_e('Usage', 'gpt3-ai-content-generator'); ?></h3>
                        </div>
                    </div>
                    <div class="aipkit_home_panel_body">
                        <div
                            id="aipkit_token_usage_chart_container"
                            class="aipkit_token_usage_chart_container aipkit_home_chart_canvas"
                            data-default-days="7"
                        >
                            <div class="aipkit_chart_loading_placeholder">
                                <span class="aipkit_spinner" aria-hidden="true"></span>
                                <span><?php esc_html_e('Loading chart data...', 'gpt3-ai-content-generator'); ?></span>
                            </div>
                            <div class="aipkit_chart_error_placeholder"></div>
                            <div class="aipkit_chart_nodata_placeholder"></div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</div>

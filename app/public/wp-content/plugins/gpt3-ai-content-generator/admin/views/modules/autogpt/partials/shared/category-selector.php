<?php

/**
 * Partial: AutoGPT - Category Selector
 * Renders category cards for task creation.
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file only uses local helper/template variables and does not define public globals.

$aipkit_autogpt_is_pro = !empty($is_pro);

$aipkit_autogpt_category_groups = [
    [
        'id' => 'aipkit_autogpt_mode_section_create',
        'heading' => __('Create', 'gpt3-ai-content-generator'),
        'items' => [
            [
                'slug' => 'content_creation',
                'task_type' => 'content_writing_bulk',
                'label' => __('Manual Entry', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-edit-page',
            ],
            [
                'slug' => 'content_creation',
                'task_type' => 'content_writing_csv',
                'label' => __('Import CSV', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-media-spreadsheet',
            ],
            [
                'slug' => 'content_creation',
                'task_type' => 'content_writing_rss',
                'label' => __('RSS Feed', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-rss',
                'pro' => true,
            ],
            [
                'slug' => 'content_creation',
                'task_type' => 'content_writing_url',
                'label' => __('Web Page', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-admin-links',
                'pro' => true,
            ],
            [
                'slug' => 'content_creation',
                'task_type' => 'content_writing_gsheets',
                'label' => __('Google Sheets', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-table-col-before',
                'pro' => true,
            ],
        ],
    ],
    [
        'id' => 'aipkit_autogpt_mode_section_optimize',
        'heading' => __('Optimize', 'gpt3-ai-content-generator'),
        'items' => [
            [
                'slug' => 'content_enhancement',
                'task_type' => 'enhance_existing_content',
                'label' => __('Rewrite Content', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-edit-large',
                'pro' => true,
            ],
        ],
    ],
    [
        'id' => 'aipkit_autogpt_mode_section_index',
        'heading' => __('Index', 'gpt3-ai-content-generator'),
        'items' => [
            [
                'slug' => 'knowledge_base',
                'task_type' => 'content_indexing',
                'label' => __('Content Indexing', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-database',
            ],
        ],
    ],
    [
        'id' => 'aipkit_autogpt_mode_section_engagement',
        'heading' => __('Engagement', 'gpt3-ai-content-generator'),
        'items' => [
            [
                'slug' => 'community_engagement',
                'task_type' => 'community_reply_comments',
                'label' => __('Comment Replies', 'gpt3-ai-content-generator'),
                'icon' => 'dashicons-admin-comments',
            ],
        ],
    ],
];
?>
<div class="aipkit_cw_source_selector_wrapper" data-aipkit-autogpt-category-selector data-template-ready="1">
    <div class="aipkit_cw_workflow_header">
        <div class="aipkit_cw_workflow_title"><?php esc_html_e('Task Type', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <?php foreach ($aipkit_autogpt_category_groups as $group) : ?>
        <div class="aipkit_cw_mode_section" aria-labelledby="<?php echo esc_attr($group['id']); ?>">
            <div class="aipkit_cw_mode_section_heading" id="<?php echo esc_attr($group['id']); ?>">
                <?php echo esc_html($group['heading']); ?>
            </div>
            <div class="aipkit_cw_mode_group_list aipkit_cw_mode_group_list--workflow" role="list" aria-labelledby="<?php echo esc_attr($group['id']); ?>">
                <?php foreach ($group['items'] as $item) : ?>
                    <?php
                    $aipkit_autogpt_item_is_locked = !empty($item['pro']) && !$aipkit_autogpt_is_pro;
                    $aipkit_autogpt_item_classes = ['aipkit_cw_mode_card'];
                    ?>
                    <button
                        type="button"
                        class="<?php echo esc_attr(implode(' ', $aipkit_autogpt_item_classes)); ?>"
                        data-category="<?php echo esc_attr($item['slug']); ?>"
                        data-task-type="<?php echo esc_attr($item['task_type']); ?>"
                        aria-pressed="false"
                        <?php if ($aipkit_autogpt_item_is_locked) : ?>
                            title="<?php esc_attr_e('This is a Pro feature.', 'gpt3-ai-content-generator'); ?>"
                        <?php endif; ?>
                    >
                        <span class="aipkit_cw_mode_icon dashicons <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></span>
                        <span class="aipkit_cw_mode_text">
                            <span class="aipkit_cw_mode_title"><?php echo esc_html($item['label']); ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php

namespace StellarWP\LearnDashCloud\Modules;

use StellarWP\PluginFramework\Concerns\RendersTemplates;
use StellarWP\PluginFramework\Contracts\ProvidesSettings;
use StellarWP\PluginFramework\Modules\Module;

class Support extends Module
{
    use RendersTemplates;

    /**
     * Settings provider object
     *
     * @var ProvidesSettings
     */
    protected $settings;

    /**
     * Construct a new instance of Setup Wizard module.
     *
     * @param ProvidesSettings $settings     Plugin settings.
     */
    public function __construct(ProvidesSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Setup method
     *
     * @return void
     */
    public function setup()
    {
        $this->setTemplateDirectories([
            dirname(__DIR__) . '/templates/support/',
        ]);

        add_action('admin_head', [ $this, 'outputInternalCss' ], 100);
        add_action('admin_menu', [ $this, 'registerMenu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ]);
    }

    /**
     * Output internal CSS on admin pages
     *
     * @return void
     */
    public function outputInternalCss()
    {
        ?>
        <?php // phpcs:ignore?>
        <?php if (isset($_GET['page']) && in_array($_GET['page'], ['learndash-cloud-support'], true)) : ?>
            <style>
                body .notice {
                    display: none;
                }
            </style>
        <?php endif; ?>
        <?php
    }

    /**
     * Register menu on admin pages
     *
     * @deprecated 1.0.18 No longer used.
     *
     * @return void
     */
    public function registerMenu()
    {
        // Do not register the menu if LearnDash core is active.
        if (defined('LEARNDASH_VERSION')) {
            return;
        }

        add_menu_page(
            __('LearnDash Cloud', 'learndash-cloud'),
            __('LearnDash Cloud', 'learndash-cloud'),
            'manage_options',
            'learndash-cloud',
            '__return_null',
            'dashicons-cloud',
            3
        );

        add_submenu_page(
            'learndash-cloud',
            __('LearnDash Cloud Support', 'learndash-cloud'),
            __('Support', 'learndash-cloud'),
            'manage_options',
            'learndash-cloud-support',
            [ $this, 'outputSupportPage' ],
            1
        );

        remove_submenu_page('learndash-cloud', 'learndash-cloud');
    }

    /**
     * Output support page HTML
     *
     * @return void
     */
    public function outputSupportPage()
    {
        $categories = $this->getCategories();

        $this->renderTemplate('support', [
            'categories' => $categories,
        ]);
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @return void
     */
    public function enqueueAdminScripts()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-cloud-support' === $_GET['page']) {
            $this->enqueueSupportAssets();
        }
    }

    /**
     * Enqueue support assets
     *
     * @return void
     */
    public function enqueueSupportAssets()
    {
        wp_enqueue_style(
            'learndash-cloud-support',
            \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/css/support.css',
            [],
            \StellarWP\LearnDashCloud\PLUGIN_VERSION,
            'all'
        );

        wp_enqueue_script(
            'learndash-cloud-support',
            \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/js/support.js',
            [ 'jquery' ],
            \StellarWP\LearnDashCloud\PLUGIN_VERSION,
            true
        );
    }

    /**
     * Get categories
     *
     * @return array<string, array<string, string>>
     */
    public function getCategories()
    {
        $categories = [
            'getting-started' => [
                'id' => 'getting-started',
                'helpScoutId' => '',
                'label' => __('Getting Started', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Not sure what to do next? Read our top articles to get more information.', 'learndash-cloud'),
                'icon' => 'getting-started',
            ],
            'learndash-core' => [
                'id' => 'learndash-core',
                'helpScoutId' => '',
                'label' => __('LearnDash Core', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Everything about LearnDash LMS core plugin.', 'learndash-cloud'),
                'icon' => 'core',
            ],
            'add-ons' => [
                'id' => 'add-ons',
                'helpScoutId' => '',
                'label' => __('Add-Ons', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Course Grid, Stripe, WooCommerce, Zapier, and other official add-ons documentations.', 'learndash-cloud'),
                'icon' => 'addons',
            ],
            'users-and-groups' => [
                'id' => 'users-and-groups',
                'helpScoutId' => '',
                'label' => __('Users & Groups', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Have questions about users & groups? Our articles may help.', 'learndash-cloud'),
                'icon' => 'users-groups',
            ],
            'reporting' => [
                'id' => 'reporting',
                'helpScoutId' => '',
                'label' => __('Reporting', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('LearnDash reporting guides.', 'learndash-cloud'),
                'icon' => 'reporting',
            ],
            'user-guides' => [
                'id' => 'user-guides',
                'helpScoutId' => '',
                'label' => __('User Guides', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Collection of guides that will help you accomplish certain tasks.', 'learndash-cloud'),
                'icon' => 'user-guides',
            ],
            'troubleshooting' => [
                'id' => 'troubleshooting',
                'helpScoutId' => '',
                'label' => __('Troubleshooting', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Have issues? Follow our troubleshooting guides to resolve them.', 'learndash-cloud'),
                'icon' => 'troubleshooting',
            ],
            'faqs' => [
                'id' => 'faqs',
                'helpScoutId' => '',
                'label' => __('FAQs', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Have a question? See if it\'s already been answered.', 'learndash-cloud'),
                'icon' => 'faqs',
            ],
            'account-and-billing' => [
                'id' => 'account-and-billing',
                'helpScoutId' => '',
                'label' => __('Accounts & Billing', 'learndash-cloud'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'description' => __('Accounts & Billing related articles.', 'learndash-cloud'),
                'icon' => 'accounts-billing',
            ],
        ];

        return $categories;
    }

    /**
     * Get article categories
     *
     * @param array<string> $exclude_categories
     * @return array<string, string>
     */
    public function getArticlesCategories($exclude_categories = [])
    {
        $categories = [
            'additional_resources' => __('Additional Resources', 'learndash-cloud'),
            'build_courses' => __('Build Courses', 'learndash-cloud'),
            'sell_courses' => __('Sell Your Courses', 'learndash-cloud'),
            'manage_students' => __('Manage Students', 'learndash-cloud'),
        ];

        if (! empty($exclude_categories)) {
            $categories = array_filter($categories, function ($category) use ($exclude_categories) {
                return ! in_array($category, $exclude_categories, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $categories;
    }

    /**
     * Get selected articles
     *
     * @param string        $category
     * @param array<string> $exclude_categories
     * @return array<int, array<string, array<int, string>|string>>
     */
    public function getArticles($category = null, $exclude_categories = [])
    {
        $articles = [
            [
                'type' => 'youtube_video',
                'title' => __('Welcome to LearnDash', 'learndash-cloud'),
                'youtube_id' => 'hcSTaMhZi64',
                'category' => 'overview_video',
            ],
            [
                'type' => 'article',
                'title' => __('A Brief Overview of LearnDash', 'learndash-cloud'),
                'helpscout_id' => '62575b4092fc506189806fac',
                'category' => 'overview_article',
            ],
            [
                'type' => 'helpscout_action',
                'title' => __('LearnDash Documentation', 'learndash-cloud'),
                'action' => 'open_doc',
                'keyword' => '',
                'category' => 'additional_resources',
            ],
            [
                'type' => 'article',
                'title' => __('Getting Started', 'learndash-cloud'),
                'helpscout_id' => '62a0e4f0e1d2cf0eac00f2bb',
                'category' => 'additional_resources',
            ],
            [
                'type' => 'helpscout_action',
                'title' => __('Contact Support', 'learndash-cloud'),
                'action' => 'open_chat',
                'keyword' => '',
                'category' => 'additional_resources',
            ],
            [
                'type' => 'youtube_video',
                'title' => __('Creating Courses with the Course Builder [Video]', 'learndash-cloud'),
                'youtube_id' => '9Ux2kBUhb20',
                'category' => 'build_courses',
            ],
            [
                'type' => 'helpscout_action',
                'title' => __('Adding Content with Lessons & Topics', 'learndash-cloud'),
                'action' => 'suggest_articles',
                'articles' => [
                    '6255f91ffc54e76aac36c6d8',
                    '62560b43ffcff713a5aa96e5',
                ],
                'category' => 'build_courses',
            ],
            [
                'type' => 'article',
                'title' => __('Creating Quizzes', 'learndash-cloud'),
                'helpscout_id' => '62180103efb7ce7c73443b2a',
                'category' => 'build_courses',
            ],
            [
                'type' => 'article',
                'title' => __('PayPal Settings [Article]', 'learndash-cloud'),
                'helpscout_id' => '6214263e025ca67522c7db34',
                'category' => 'sell_courses',
            ],
            [
                'type' => 'article',
                'title' => __('Stripe Integration [Article]', 'learndash-cloud'),
                'helpscout_id' => '6217fe86528a5515a2fcc635',
                'category' => 'sell_courses',
            ],
            [
                'type' => 'article',
                'title' => __('WooCommerce Integration [Article]', 'learndash-cloud'),
                'helpscout_id' => '6216b293aca5bb2b753c5c7f',
                'category' => 'sell_courses',
            ],
            [
                'type' => 'article',
                'title' => __('Course Access Settings [Article]', 'learndash-cloud'),
                'helpscout_id' => '6216c23eefb7ce7c73443441',
                'category' => 'sell_courses',
            ],
            [
                'type' => 'article',
                'title' => __('Setting Up User Registration', 'learndash-cloud'),
                'helpscout_id' => '624f7676eee5210422ea6b47',
                'category' => 'manage_students',
            ],
            [
                'type' => 'article',
                'title' => __('Adding a User Profile Page', 'learndash-cloud'),
                'helpscout_id' => '6216c2961173d072c69fb37a',
                'category' => 'manage_students',
            ],
            [
                'type' => 'article',
                'title' => __('LearnDash Login & Registration [Guide]', 'learndash-cloud'),
                'helpscout_id' => '6217ffea1173d072c69fba4d',
                'category' => 'manage_students',
            ],
        ];

        if (! empty($category)) {
            $articles = array_values(array_filter($articles, function ($article) use ($category) {
                return $article['category'] === $category;
            }));
        }

        if (! empty($exclude_categories)) {
            $articles = array_values(array_filter($articles, function ($article) use ($exclude_categories) {
                return ! in_array($article['category'], $exclude_categories, true);
            }));
        }

        return $articles;
    }
}

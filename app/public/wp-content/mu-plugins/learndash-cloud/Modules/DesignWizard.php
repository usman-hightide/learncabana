<?php

namespace StellarWP\LearnDashCloud\Modules;

use StellarWP\PluginFramework\Concerns\RendersTemplates;
use StellarWP\PluginFramework\Console\Command;
use StellarWP\PluginFramework\Contracts\ProvidesSettings;
use StellarWP\PluginFramework\Modules\Module;

/**
 * DesignWizard class
 *
 * @phpstan-type KadencePalette array{ colors: string[] }
 */
class DesignWizard extends Module
{
    use RendersTemplates;

    /**
     * Design templates
     *
     * @var array<string, array<string, array<string, string>|string>>
     */
    protected $templates = [];

    /**
     * Settings provider object
     *
     * @var ProvidesSettings
     */
    protected $settings;

    /**
     * Construct a new instance of Setup Wizard module.
     *
     * @param ProvidesSettings $settings Plugin settings.
     */
    public function __construct(ProvidesSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Required to set up this module container
     *
     * @return void
     */
    public function setup()
    {
        $this->setTemplateDirectories([
            dirname(__DIR__) . '/templates/design-wizard/',
        ]);

        $this->registerAjaxHandlers();
        $this->registerTemplates();
        add_action('admin_head', [ $this, 'loadFonts' ], 9);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ]);
        add_action('admin_menu', [ $this, 'registerPages']);
    }

    /**
     * Register AJAX handlers
     *
     * @return void
     */
    public function registerAjaxHandlers()
    {
        $ajax_actions = [
            'LdCloudDwBuildTemplate',
        ];

        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_' . $action, [ $this, 'ajax' . $action ]);
        }
    }

    /**
     * Enqueue scripts and styles on admin pages
     *
     * @return void
     */
    public function enqueueAdminScripts()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-cloud-design-wizard' === $_GET['page']) {
            remove_all_actions('admin_notices');

            wp_register_script(
                'js-cookie',
                'https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js',
                [],
                '3.0.1',
                true
            );

            wp_enqueue_style(
                'learndash-cloud-design-wizard',
                \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/css/design-wizard.css',
                [],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION
            );

            wp_enqueue_script(
                'learndash-cloud-design-wizard',
                \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/js/design-wizard.js',
                [ 'jquery', 'js-cookie', 'updates' ],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION,
                true
            );

            ob_start();
            $this->renderTemplate('actions-success');
            $actions_success = ob_get_clean();

            ob_start();
            $this->renderTemplate('actions-error');
            $actions_error = ob_get_clean();

            $templates = compact('actions_success', 'actions_error');

            wp_localize_script(
                'learndash-cloud-design-wizard',
                'LearnDashCloudDesignWizard',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'site_url' => site_url(),
                    'admin_dashboard_url' => admin_url(),
                    'learndash_cloud_setup_url' => add_query_arg(
                        [ 'page' => 'learndash-cloud-setup' ],
                        admin_url('admin.php')
                    ),
                    'ajax_init_nonce' => wp_create_nonce('ld_dw_build_template'),
                    'ajax_kadence_security_nonce' => wp_create_nonce('kadence-ajax-verification'),
                    'fonts' => $this->getThemeFonts(),
                    'palettes' => $this->getThemePalettes(),
                    'messages' => [
                        'dw_error_prefix' => '<strong>' . __('Error', 'learndash-cloud') . '</strong>',
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        'dw_error_default' => __('There\'s unknown error with the design wizard. Please try again later or contact our support if the issue perists.', 'learndash-cloud'),
                    ],
                    'templates' => $templates,
                ]
            );
        }

        if (
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            isset($_GET['page']) && 'learndash-cloud-design-wizard' === $_GET['page']
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            && isset($_GET['step']) && '2' === $_GET['step']
        ) {
            wp_enqueue_style(
                'learndash-cloud-design-wizard-gfonts',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://fonts.googleapis.com/css2?family=Antic+Didone&family=Gilda+Display&family=Inter&family=Josefin+Sans:wght@700&family=Karla&family=Lato&family=Libre+Baskerville&family=Libre+Franklin:wght@700&family=Lora:wght@700&family=Merriweather:wght@400;700&family=Montserrat:wght@700&family=Nunito:wght@700&family=Open+Sans:wght@400;700&family=Oswald:wght@700&family=Playfair+Display:wght@700&family=Poppins:wght@700&family=Proza+Libre:wght@700&family=Raleway&family=Roboto&family=Roboto+Condensed:wght@700&family=Rubik:wght@700&family=Source+Sans+Pro&family=Vollkorn:wght@700&family=Work+Sans:wght@400;700&display=swap',
                [],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION,
                'all'
            );
        }
    }

    /**
     * Load fonts on admin pages
     *
     * @return void
     */
    public function loadFonts()
    {
        if (
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            isset($_GET['page']) && 'learndash-cloud-design-wizard' === $_GET['page']
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            && isset($_GET['step']) && '2' === $_GET['step']
        ) {
            ?>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <?php
        }
    }

    /**
     * Register admin pages
     *
     * @return void
     */
    public function registerPages()
    {
        add_submenu_page(
            'learndash-lms',
            __('LearnDash Design Wizard', 'learndash-cloud'),
            __('Design', 'learndash-cloud'),
            'manage_options',
            'learndash-cloud-design-wizard',
            [ $this, 'outputPageDesignWizard' ],
            90
        );
    }

    /**
     * Output desing wizard page HTML
     *
     * @return void
     */
    public function outputPageDesignWizard()
    {
        if (isset($_GET['step']) && is_numeric($_GET['step'])) {
            $step = intval($_GET['step']);
        } else {
            $step = 1;
        }

        $template_name = isset($_GET['template']) && is_string($_GET['template'])
            ? sanitize_key(wp_unslash($_GET['template']))
            : null;

        switch ($step) {
            case 1:
                $templates = $this->getTemplates();
                shuffle($templates);

                $this->renderTemplate('wizard-1', [
                    'templates' => $templates,
                    'designWizard' => $this,
                ]);
                break;

            case 2:
                if ($template_name) {
                    $template = $this->getTemplate($template_name);
                    $fonts = $this->getTemplateFonts($template_name, true);

                    $this->renderTemplate('wizard-2', [
                        'templateDetails' => $template,
                        'fonts' => $fonts,
                        'designWizard' => $this,
                    ]);
                }
                break;

            case 3:
                if ($template_name) {
                    $template = $this->getTemplate($template_name);
                    $palettes = $this->getTemplatePalettes($template_name, true);

                    $this->renderTemplate('wizard-3', [
                        'templateDetails' => $template,
                        'palettes' => $palettes,
                        'designWizard' => $this,
                    ]);
                }
                break;

            case 4:
                if ($template_name) {
                    $template = $this->getTemplate($template_name);

                    $this->renderTemplate('wizard-4', [
                        'templateDetails' => $template,
                        'designWizard' => $this,
                    ]);
                }
                break;

            case 5:
                if ($template_name) {
                    check_admin_referer('ld_dw_build_template', 'nonce');

                    $template = $this->getTemplate($template_name);

                    $this->renderTemplate('wizard-5', [
                        'templateDetails' => $template,
                        'designWizard' => $this,
                    ]);
                }
                break;
        }
    }

    /**
     * Register templates
     *
     * @return array<string, array<string, array<string, string>|string>>
     */
    public function registerTemplates()
    {
        $this->templates = [
            'kadence_life_coach' => [
                'id'                => 'kadence_life_coach',
                'label'             => 'Life Coach',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g46',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g46/?cache=bust',
            ],
            'kadence_homesteading' => [
                'id'                => 'kadence_homesteading',
                'label'             => 'HomeSteading',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g74',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g74/?cache=bust',
            ],
            'kadence_meditation_course' => [
                'id'                => 'kadence_meditation_course',
                'label'             => 'Meditation Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g73',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g73/?cache=bust',
            ],
            'kadence_cooking_course' => [
                'id'                => 'kadence_cooking_course',
                'label'             => 'Cooking Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g63',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g63/?cache=bust',
            ],
            'kadence_baking_course' => [
                'id'                => 'kadence_baking_course',
                'label'             => 'Baking Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g64',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g64/?cache=bust',
            ],
            'kadence_sewing_course' => [
                'id'                => 'kadence_sewing_course',
                'label'             => 'Sewing Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g42',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g42/?cache=bust',
            ],
            'kadence_writing_course' => [
                'id'                => 'kadence_writing_course',
                'label'             => 'Writing Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g41',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g41/?cache=bust',
            ],
            'kadence_nutrition_course' => [
                'id'                => 'kadence_nutrition_course',
                'label'             => 'Nutrition Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g37',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g37/?cache=bust',
            ],
            'kadence_makeup' => [
                'id'                => 'kadence_makeup',
                'label'             => 'Makeup',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g28',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g28/?cache=bust',
            ],
            'kadence_design_course' => [
                'id'                => 'kadence_design_course',
                'label'             => 'Design Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g36',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g36/?cache=bust',
            ],
            'kadence_fitness_course'  => [
                'id'                => 'kadence_fitness_course',
                'label'             => 'Fitness Course',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g40',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g40/?cache=bust',
            ],
            'kadence_photo_courses' => [
                'id'                => 'kadence_photo_courses',
                'label'             => 'Photo Courses',
                'theme'             => 'kadence',
                'theme_label'       => 'Kadence',
                'theme_template_id' => 'g34',
                'plugins'           => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url'       => 'https://startertemplatecloud.com/g34/?cache=bust',
            ],
            'kadence_seo_skills' => [
                'id' => 'kadence_seo_skills',
                'label' => 'SEO Skills',
                'theme' => 'kadence',
                'theme_label' => 'Kadence',
                'theme_template_id' => 'g21',
                'plugins' => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url' => 'https://startertemplatecloud.com/g21/?cache=bust',
            ],
            'kadence_digital_course' => [
                'id' => 'kadence_digital_course',
                'label' => 'Digital Course',
                'theme' => 'kadence',
                'theme_label' => 'Kadence',
                'theme_template_id' => 'g22',
                'plugins' => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url' => 'https://startertemplatecloud.com/g22/?cache=bust',

            ],
            'kadence_business_course' => [
                'id' => 'kadence_business_course',
                'label' => 'Business Course',
                'theme' => 'kadence',
                'theme_label' => 'Kadence',
                'theme_template_id' => 'g20',
                'plugins' => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url' => 'https://startertemplatecloud.com/g20/?cache=bust',

            ],
            'kadence_course' => [
                'id' => 'kadence_course',
                'label' => 'Course',
                'theme' => 'kadence',
                'theme_label' => 'Kadence',
                'theme_template_id' => 'g03',
                'plugins' => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url' => 'https://startertemplatecloud.com/g03/?cache=bust',

            ],
            'kadence_get_income' => [
                'id' => 'kadence_get_income',
                'label' => 'Get Income',
                'theme' => 'kadence',
                'theme_label' => 'Kadence',
                'theme_template_id' => 'member_g01',
                'plugins' => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url' => 'https://startertemplatecloud.com/member-g01/?cache=bust',

            ],
            'kadence_online_course' => [
                'id' => 'kadence_online_course',
                'label' => 'Online Course',
                'theme' => 'kadence',
                'theme_label' => 'Kadence',
                'theme_template_id' => 'g04',
                'plugins' => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url' => 'https://startertemplatecloud.com/g04/?cache=bust',
            ],
            'kadence_painting_course' => [
                'id' => 'kadence_painting_course',
                'label' => 'Painting Course',
                'theme' => 'kadence',
                'theme_label' => 'Kadence',
                'theme_template_id' => 'g35',
                'plugins' => [
                    'kadence-starter-templates' => __('Starter Templates by Kadence WP', 'learndash-cloud'),
                ],
                'preview_url' => 'https://startertemplatecloud.com/g35/?cache=bust',
            ],
        ];

        return apply_filters('learndash_design_wizard_templates', $this->templates);
    }

    /**
     * Get design templates
     *
     * @return array<string, array<string, array<string, string>|string>>
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Get template
     *
     * @param string $template
     * @return bool|array<string, array<string, string>|string>
     */
    public function getTemplate($template)
    {
        if (isset($this->templates[ $template ])) {
            return $this->templates[ $template ];
        }
        return false;
    }

    /**
     * Get theme fonts
     *
     * @param string $theme
     *
     * phpcs:ignore Generic.Files.LineLength.TooLong
     * @return array<int|string, array<int|string, array<string, array<string, array<string, int|string>|int|string>|int|string>|string>>
     */
    public function getThemeFonts($theme = '')
    {
        $fonts = [];

        $kadence_fonts = [
            'monserrat' => [
                'label' => 'Monserrat & Source Sans Pro',
                'families' => [
                    'heading' => 'Monserrat',
                    'body' => 'Source Sans Pro',
                ],
            ],
            'libre' => [
                'label' => 'Libre Franklin & Libre Baskerville',
                'families' => [
                    'heading' => 'Libre Franklin',
                    'body' => 'Libre Baskerville',
                ]
            ],
            'proza' => [
                'label' => 'Proza Libre & Open Sans',
                'families' => [
                    'heading' => 'Proza Libre',
                    'body' => 'Open Sans',
                ]
            ],
            'worksans' => [
                'label' => 'Work Sans',
                'families' => [
                    'heading' => 'Work Sans',
                    'body' => 'Work Sans',
                ]
            ],
            'josefin' => [
                'label' => 'Josefin Sans & Lato',
                'families' => [
                    'heading' => 'Josefin Sans',
                    'body' => 'Lato',
                ]
            ],
            'oswald' => [
                'label' => 'Oswald & Open Sans',
                'families' => [
                    'heading' => 'Oswald',
                    'body' => 'Open Sans',
                ]
            ],
            'nunito' => [
                'label' => 'Nunito & Roboto',
                'families' => [
                    'heading' => 'Nunito',
                    'body' => 'Roboto',
                ]
            ],
            'rubik' => [
                'label' => 'Rubik & Karla',
                'families' => [
                    'heading' => 'Rubik',
                    'body' => 'Karla',
                ]
            ],
            'lora' => [
                'label' => 'Lora & Merriweather',
                'families' => [
                    'heading' => 'Lora',
                    'body' => 'Merriweather',
                ]
            ],
            'playfair' => [
                'label' => 'Playfair Dislay & Raleway',
                'families' => [
                    'heading' => 'Playfair Dislay',
                    'body' => 'Raleway',
                ]
            ],
            'antic' => [
                'label' => 'Antic Didone & Raleway',
                'families' => [
                    'heading' => 'Antic Didone',
                    'body' => 'Raleway',
                ]
            ],
            'gilda' => [
                'label' => 'Gilda Display & Raleway',
                'families' => [
                    'heading' => 'Gilda Display',
                    'body' => 'Raleway',
                ]
            ],
        ];

        if (! empty($theme)) {
            switch ($theme) {
                case 'kadence':
                    $fonts = $kadence_fonts;
                    break;
            }
        } else {
            $fonts = [
                'kadence' => $kadence_fonts,
            ];
        }

        return $fonts;
    }

    /**
     * Get template fonts
     *
     * @param string  $template
     * @param boolean $omit_default
     *
     * phpcs:ignore Generic.Files.LineLength.TooLong
     * @return array<int|string, array<int|string, array<string, array<string, array<string, int|string>|int|string>|int|string>|string>>
     */
    public function getTemplateFonts($template = '', $omit_default = false)
    {
        /** @var array{ theme: string } */
        $template = $this->getTemplate($template);
        $fonts = [];

        if (! empty($template['theme'])) {
            switch ($template['theme']) {
                case 'kadence':
                    $fonts = $this->getThemeFonts('kadence');
                    break;
            }
        }

        if ($omit_default && isset($fonts['default'])) {
            unset($fonts['default']);
        }

        return $fonts;
    }

    /**
     * Get theme palettes
     *
     * @param string $theme
     *
     * phpcs:ignore Generic.Files.LineLength.TooLong
     * @return array<string, KadencePalette>|array{ kadence: array<string, KadencePalette> }
     */
    public function getThemePalettes($theme = '')
    {
        $palettes = [];

        $kadence_palettes = [
            'base' => [
                'colors' => [
                    '#2B6CB0',
                    '#3B3B3B',
                    '#E1E1E1',
                    '#F7F7F7',
                ]
            ],
            'orange' => [
                'colors' => [
                    '#e47b02',
                    '#3E4C59',
                    '#F3F4F7',
                    '#F9F9FB',
                ]
            ],
            'pinkish' => [
                'colors' => [
                    '#E21E51',
                    '#032075',
                    '#DEDDEB',
                    '#EFEFF5',
                ]
            ],
            'mint' => [
                'colors' => [
                    '#2cb1bc',
                    '#133453',
                    '#e0fcff',
                    '#f5f7fa',
                ]
            ],
            'green' => [
                'colors' => [
                    '#049f82',
                    '#353535',
                    '#EEEEEE',
                    '#F7F7F7',
                ]
            ],
            'rich' => [
                'colors' => [
                    '#295CFF',
                    '#1C0D5A',
                    '#E1EBEE',
                    '#EFF7FB',
                ]
            ],
            'fem' => [
                'colors' => [
                    '#D86C97',
                    '#282828',
                    '#f7dede',
                    '#F6F2EF',
                ]
            ],
            'teal' => [
                'colors' => [
                    '#7ACFC4',
                    '#000000',
                    '#F6E7BC',
                    '#F9F7F7',
                ]
            ],
            'bold' => [
                'colors' => [
                    '#000000',
                    '#000000',
                    '#F6E7BC',
                    '#F9F7F7',
                ]
            ],
            'hot' => [
                'colors' => [
                    '#FF5698',
                    '#000000',
                    '#FDEDEC',
                    '#FDF6EE',
                ]
            ],
            'darkmode' => [
                'colors' => [
                    '#3296ff',
                    '#F7FAFC',
                    '#2D3748',
                    '#252C39',
                ]
            ],
            'pinkishdark' => [
                'colors' => [
                    '#E21E51',
                    '#EFEFF5',
                    '#514D7C',
                    '#221E5B',
                ]
            ],
        ];

        if (! empty($theme)) {
            switch ($theme) {
                case 'kadence':
                    $palettes = $kadence_palettes;
                    break;
            }
        } else {
            $palettes = [
                'kadence' => $kadence_palettes,
            ];
        }

        return $palettes;
    }

    /**
     * Get theme palettes
     *
     * @param string  $template
     * @param boolean $omit_default
     * @return array<string, array<string, array<array<array<string>|string>|string>>>
     */
    public function getTemplatePalettes($template, $omit_default = false)
    {
        /** @var array{ theme: string, color_scheme: string } */
        $template = $this->getTemplate($template);
        $palettes = [];

        switch ($template['theme']) {
            case 'kadence':
                $palettes = $this->getThemePalettes('kadence');
                break;
        }

        if ($omit_default && isset($palettes['default'])) {
            unset($palettes['default']);
        }

        return $palettes;
    }

    /**
     * Get template preview image URL
     *
     * @param array<string, string> $template
     * @return string
     */
    public function getTemplatePreviewImageUrl($template)
    {
        $image_dir_url  = \StellarWP\LearnDashCloud\PLUGIN_URL . '/images/design-wizard/previews/';
        $image_dir_path = \StellarWP\LearnDashCloud\PLUGIN_PATH . '/images/design-wizard/previews/';

        $image_url = '';
        if (file_exists($image_dir_path . $template['id'] . '.jpg')) {
            $image_url = $image_dir_url . $template['id'] . '.jpg';
        } elseif (file_exists($image_dir_path . $template['id'] . '.png')) {
            $image_url = $image_dir_url . $template['id'] . '.png';
        }

        return apply_filters('learndash_design_wizard_template_preview_image_url', $image_url, $template);
    }

    /**
     * Install theme
     *
     * @param string $theme
     * @return array{action: 'install', slug: string, themeName?: mixed, success?: true}
     */
    public function installTheme($theme)
    {
        $status = [
            'action' => 'install',
            'slug'    => $theme,
        ];

        $command = new Command('wp theme install', [ $theme ]);
        $result = $command->execute();

        if ($result->wasSuccessful()) {
            $status['themeName'] = $this->getThemeData($theme, 'name');
            $status['success'] = true;
        } else {
            $status['exitCode']    = $result->getExitCode();
            $status['errorMessage'] = $result->getErrors();
            wp_send_json_error($status);
        }

        return $status;
    }

    /**
     * Install plugin
     *
     * @param string $plugin
     * @return array{action: 'install', slug: string, pluginName?: mixed, success?: true}
     */
    public function installPlugin($plugin)
    {
        $status = [
            'action'  => 'install',
            'slug'    => $plugin,
        ];

        $command = new Command('wp plugin install', [ $plugin ]);
        $result = $command->execute();

        if ($result->wasSuccessful()) {
            $status['pluginName'] = $this->getPluginData($plugin, 'name');
            $status['success'] = true;
        } else {
            $status['exitCode']    = $result->getExitCode();
            $status['errorMessage'] = $result->getErrors();
            wp_send_json_error($status);
        }

        return $status;
    }

    /**
     * Get theme data
     *
     * @param string $theme
     * @param string $field
     * @return mixed
     */
    public function getThemeData($theme, $field = '')
    {
        $fieldCommand = ! empty($field) ? "--field={$field}" : null;

        $command = new Command('wp theme get', [ $theme, $fieldCommand, '--format=json' ]);
        $result = $command->execute();

        if ($result->wasSuccessful()) {
            $value = json_decode($result->getOutput(), true);
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * Get plugin data
     *
     * @param string $plugin
     * @param string $field
     * @return mixed
     */
    public function getPluginData($plugin, $field = '')
    {
        $fieldCommand = ! empty($field) ? "--field={$field}" : null;

        $command = new Command('wp plugin get', [ $plugin, $fieldCommand, '--format=json' ]);
        $result = $command->execute();

        if ($result->wasSuccessful()) {
            $value = json_decode($result->getOutput(), true);
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * Activate theme
     *
     * @param string $theme
     * @return array{action: 'activate', slug: string, themeName?: mixed, success?: true}
     */
    public function activateTheme($theme)
    {
        $status = [
            'action'  => 'activate',
            'slug'    => $theme,
        ];

        $command = new Command('wp theme activate', [ $theme ]);
        $result = $command->execute();

        if ($result->wasSuccessful()) {
            $status['themeName'] = $this->getThemeData($theme, 'name');
            $status['success'] = true;
        } else {
            $status['exitCode']    = $result->getExitCode();
            $status['errorMessage'] = $result->getErrors();
            wp_send_json_error($status);
        }

        return $status;
    }

    /**
     * Activate plugin
     *
     * @param string $plugin
     * @return array{action: 'activate', slug: string, pluginName?: mixed, success?: true}
     */
    public function activatePlugin($plugin)
    {
        $status = [
            'action'  => 'activate',
            'slug'    => $plugin,
        ];

        $command = new Command('wp plugin activate', [ $plugin ]);
        $result = $command->execute();

        if ($result->wasSuccessful()) {
            $status['pluginName'] = $this->getPluginData($plugin, 'name');
            $status['success'] = true;
        } else {
            $status['exitCode']    = $result->getExitCode();
            $status['errorMessage'] = $result->getErrors();
            wp_send_json_error($status);
        }

        return $status;
    }

    /**
     * AJAX handlers
     */

    /**
     * AJAX handler for design wizard
     *
     * @return void
     */
    public function ajaxLdCloudDwBuildTemplate()
    {
        check_ajax_referer('ld_dw_build_template', 'nonce');

        if (! current_user_can('switch_themes')) {
            $response = [
                'message' => __('User doesn\'t have enough capability', 'learndash-cloud'),
                'time' => date('Y-m-d H:i:s'),
            ];

            wp_send_json_error($response);
        }

        /** @var array{ theme: string, plugins: array<string, string>, id: string, name: string } */
        /** @phpstan-ignore-next-line */
        $template = isset($_REQUEST['template']) ? $this->getTemplate(sanitize_key(strval($_REQUEST['template']))) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,Generic.Files.LineLength.TooLong

        $steps = [
            'install_theme',
            'install_plugin',
            'activate_theme',
            'activate_plugin',
            'build_template',
            'end_build_process',
        ];

        if (isset($_REQUEST['init']) && 'true' === $_REQUEST['init']) {
            $last_step = false;

            update_option('show_on_front', 'post');
            delete_option('page_on_front');
        } else {
            $last_step = get_option('ld_cloud_dw_build_last_step');
        }

        if (! $last_step) {
            $current_step = $steps[0];
            $current_step_n = 0;
        } else {
            $key = array_search($last_step, $steps, true) + 1;
            $current_step = isset($steps[ $key ]) ? $steps[ $key ] : false;
            $current_step_n = $key;
        }

        $message = '';
        $complete = false;
        $percentage = 0;

        switch ($current_step) {
            case 'install_theme':
                $themes = wp_get_themes();

                if (! isset($themes[ $template['theme'] ])) {
                    $install = $this->installTheme($template['theme']);
                }

                if (isset($install['success'])) {
                    $message = __('Install plugin(s)', 'learndash-cloud');
                }
                break;

            case 'install_plugin':
                $plugins = get_plugins();
                $plugin_keys = array_keys($plugins);
                $plugin_keys = array_map(function ($key) {
                    return preg_replace('/\/.*/', '', $key);
                }, $plugin_keys);

                foreach ($template['plugins'] as $key => $label) {
                    if (! in_array($key, $plugin_keys, true)) {
                        $install = $this->installPlugin($key);
                    }
                }

                if (isset($install['success'])) {
                    $message = __('Activate theme', 'learndash-cloud');
                }

                break;

            case 'activate_theme':
                $this->activateTheme($template['theme']);
                $message = __('Activate plugin(s)', 'learndash-cloud');
                break;

            case 'activate_plugin':
                foreach ($template['plugins'] as $key => $label) {
                    $this->activatePlugin($key);
                }

                $message = __('Build template', 'learndash-cloud');
                break;

            case 'build_template':
                switch ($template['theme']) {
                    case 'kadence':
                        $message = __('Run Kadence template building process', 'learndash-cloud');
                        break;
                }
                break;

            case 'end_build_process':
                $complete = true;
                $message = __('Finished', 'learndash-cloud');
                break;
        }

        $step_n = $current_step_n;
        $total_steps = count($steps);

        if ($complete) {
            $message = __('Template has been built and is ready to use.', 'learndash-cloud');
            update_option('learndash_cloud_design_wizard_status', 'completed');
        }

        if (! $complete) {
            update_option('ld_cloud_dw_build_last_step', $current_step);
        } else {
            delete_option('ld_cloud_dw_build_last_step');
        }

        $response = [
            'step' => $current_step,
            'theme' => $template['theme'],
            'template' => $template['id'],
            'complete' => $complete,
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
        ];

        wp_send_json_success($response);
    }
}

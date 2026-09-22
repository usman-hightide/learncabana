<?php

namespace StellarWP\LearnDashCloud\Modules;

use LearnDash_Settings_Section_Stripe_Connect as StripeConnect;
use StellarWP\LearnDashCloud\Modules\Support as Support;
use StellarWP\LearnDashCloud\Services\Domain;
use StellarWP\PluginFramework\Concerns\RendersTemplates;
use StellarWP\PluginFramework\Contracts\ProvidesSettings;
use StellarWP\PluginFramework\Exceptions\RequestException;
use StellarWP\PluginFramework\Exceptions\ValidationException;
use StellarWP\PluginFramework\Modules\Module;
use StellarWP\PluginFramework\Support\Attributes;
use WP_Error;

class SetupWizard extends Module
{
    use RendersTemplates;

    /**
     * Support module object
     *
     * @var Support
     */
    protected $support;

    /**
     * Settings provider object
     *
     * @var ProvidesSettings
     */
    protected $settings;

    /**
     * The domain instance.
     *
     * @var Domain
     */
    protected $domain;

    /**
     * Constants to store static AJAX actions strings
     */
    public const AJAX_ACTIONS = [
        'domainChange'     => 'stellarwp-change-domain',
        'processUserSetup' => 'LdCloudSwProcessUserSetup',
        'processSiteSetup' => 'LdCloudSwProcessSiteSetup',
    ];

    /**
     * Construct a new instance of Setup Wizard module.
     *
     * @param Support          $support  Support module.
     * @param ProvidesSettings $settings Plugin settings.
     * @param Domain           $domain   Domain instance.
     */
    public function __construct(Support $support, ProvidesSettings $settings, Domain $domain)
    {
        $this->support  = $support;
        $this->settings = $settings;
        $this->domain   = $domain;
    }

    /**
     * Set up the module
     *
     * @return void
     */
    public function setup()
    {
        $this->setTemplateDirectories([
            dirname(__DIR__) . '/templates/setup-wizard/',
        ]);

        add_filter('wp_kses_allowed_html', [ $this, 'filterWpKsesAllowedHtml' ], 10, 2);

        add_action('admin_head', [ $this, 'outputInternalCss' ], 100);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ]);
        add_action('admin_menu', [ $this, 'registerMenu' ]);

        add_action('wp_login', [ $this, 'initWizardScreen' ], 1, 2);
        add_filter('learndash_setup_wizard_completed_redirect_url', [ $this, 'setupWizardRedirectUrl']);
        add_filter('learndash_setup_wizard_available_scenes', [ $this, 'setupWizardRemoveLicenseScene' ]);

        // AJAX handlers.
        add_action('wp_ajax_' . static::AJAX_ACTIONS['processUserSetup'], [ $this, 'ajaxProcessUserSetup' ]);
        add_action('wp_ajax_' . static::AJAX_ACTIONS['processSiteSetup'], [ $this, 'ajaxProcessSiteSetup' ]);
        add_action('wp_ajax_' . static::AJAX_ACTIONS['domainChange'], [ $this, 'ajaxDomainChange' ], 1);

        add_action('admin_init', [ $this, 'validateLearndashLicense' ]);

        add_action('admin_init', [ $this, 'welcomeFlushPermalinks' ]);

        // after domain change processing.
        add_filter('wp_auth_check_load', [ $this, 'hideDefaultPopupAfterDomainChange' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueAfterDomainChangeScripts' ]);
        add_action('wp_login', [ $this, 'flushPermalinksAfterDomainChange' ], 10, 2);
        add_action('admin_init', [ $this, 'maybeFlushPermalinks' ]);
    }

    /**
     * Validate the LearnDash license in the first time.
     *
     * @return void
     */
    public function validateLearndashLicense()
    {
        $check_license = get_option('learndash_cloud_learndash_license_checked', false);
        if (! $check_license && defined('LEARNDASH_VERSION')) {
            // @phpstan-ignore-next-line -- LearnDash function.
            $updater_sfwd_lms = learndash_get_updater_instance(true);
            if (($updater_sfwd_lms) && (is_a($updater_sfwd_lms, 'nss_plugin_updater_sfwd_lms'))) {
                /**
                 * Remove the time to check timestamp. Within the getRemote_license() method
                 * is calls time_to_recheck_license() which uses this option to determine if
                 * the license needs to be checked again.
                 */
                delete_option('nss_plugin_check_sfwd_lms');

                // @phpstan-ignore-next-line -- LearnDash function.
                $updater_sfwd_lms->getRemote_license();
            }

            update_option('learndash_cloud_learndash_license_checked', true);
        }
    }

    /**
     * Filter wp_kses allowed html
     *
     * @param array<string, array<string, bool>> $html HTML tags and their attributes in array format.
     * @param string                             $context
     * @return array<string, array<string, bool>> Filtered HTML array.
     */
    public function filterWpKsesAllowedHtml($html, $context)
    {
        if ('svg' === $context) {
            $html = [
                'svg'   => [
                    'class' => true,
                    'aria-hidden' => true,
                    'aria-labelledby' => true,
                    'role' => true,
                    'xmlns' => true,
                    'xmlns:xlink' => true,
                    'xml:space' => true,
                    'x' => true,
                    'y' => true,
                    'width' => true,
                    'height' => true,
                    'viewbox' => true,
                    'fill' => true,
                    'version' => true,
                    'id' => true,
                    'style' => true,
                ],
                'g'     => [ 'fill' => true ],
                'polygon' => [ 'fill' => true, 'class' => true, 'points' => true ],
                'title' => [ 'title' => true ],
                'path'  => [ 'd' => true, 'fill' => true, 'class' => true ],
                'style' => [ 'type' => true ],
            ];
        }

        return $html;
    }

    /**
     * Output internal CSS in head tag
     *
     * @return void
     */
    public function outputInternalCss()
    {
        // Hide LearnDash 4.0 onboarding wizard on load before modified by custom script.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-setup-wizard' === $_GET['page']) {
            ?>
            <style>
                #learndash-setup-wizard {
                    display: none;
                }
            </style>
            <?php
        }

        // Set up page.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-cloud-setup' === $_GET['page']) {
            ?>
            <style>
                .notice {
                    display: none;
                }
            </style>
            <?php
        }

        // LearnDash cloud setup general CSS styles.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-cloud-setup-wizard' === $_GET['page']) {
            ?>
            <style>
                html,
                body {
                    padding: 0 !important;
                    max-height: 100vh !important;
                    min-height: 100vh !important;
                    overflow: hidden;
                }

                #adminmenumain,
                #wpadminbar,
                #wpfooter,
                .notice {
                    display: none !important;
                }

                #wpcontent,
                #wpfooter {
                    padding: 0 !important;
                    margin: 0 !important;
                }

                .wrap {
                    margin: 10px 0 !important;
                }
            </style>
            <?php
        }
    }

    /**
     * Enqueue scripts and styles on admin pages.
     *
     * @return void
     */
    public function enqueueAdminScripts()
    {
        $nonces = [];
        foreach (static::AJAX_ACTIONS as $key => $value) {
            $nonces[$key] = wp_create_nonce($value);
        }

        $object = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'plugin_url' => \StellarWP\LearnDashCloud\PLUGIN_URL,
            'admin_dashboard_url' => admin_url('/'),
            'learndash_cloud_setup_url' => add_query_arg(
                [ 'page' => 'learndash-cloud-setup' ],
                admin_url('admin.php')
            ),
            'learndash_cloud_setup_wizard_url' => add_query_arg(
                [ 'page' => 'learndash-cloud-setup-wizard' ],
                admin_url('admin.php')
            ),
            'learndash_setup_wizard_url' => add_query_arg(
                [ 'page' => 'learndash-setup-wizard' ],
                admin_url('admin.php')
            ),
            'doc_url' => [
                'custom_domain' => '#'
            ],
            'actions' => static::AJAX_ACTIONS,
            'nonces' => $nonces,
            'has_custom_domain' => $this->settings->get('has_custom_domain'),
            'inputs' => get_option('learndash_cloud_setup_wizard_data', []),
        ];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-cloud-setup-wizard' === $_GET['page']) {
            wp_enqueue_script(
                'learndash-cloud-setup-wizard',
                \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/js/setup-wizard.js',
                [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION,
                true
            );

            wp_localize_script(
                'learndash-cloud-setup-wizard',
                'LearnDashCloudSetupWizard',
                $object
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-setup-wizard' === $_GET['page']) {
            wp_enqueue_style(
                'learndash-cloud-setup-wizard-modifier',
                \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/css/setup-wizard-modifier.css',
                [],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION,
                'all'
            );

            wp_enqueue_script(
                'learndash-cloud-setup-wizard-modifier',
                \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/js/setup-wizard-modifier.js',
                [ 'jquery' ],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION,
                true
            );

            wp_localize_script(
                'learndash-cloud-setup-wizard-modifier',
                'LearnDashCloudSetupWizard',
                $object
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page']) && 'learndash-cloud-setup' === $_GET['page']) {
            $this->support->enqueueSupportAssets();

            wp_enqueue_style(
                'learndash-cloud-setup',
                \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/css/setup.css',
                [],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION,
                'all'
            );

            wp_enqueue_script(
                'learndash-cloud-setup',
                \StellarWP\LearnDashCloud\PLUGIN_URL . '/assets/js/setup.js',
                [ 'jquery', 'wp-element' ],
                \StellarWP\LearnDashCloud\PLUGIN_VERSION,
                true
            );

            wp_localize_script(
                'learndash-cloud-setup',
                'LearnDashCloudSetup',
                $object
            );
        }
    }

    /**
     * Register menu on admin sidebar.
     *
     * @return void
     */
    public function registerMenu()
    {
        add_menu_page(
            __('LearnDash Cloud Setup', 'learndash-cloud'),
            __('Set Up', 'learndash-cloud'),
            'manage_options',
            'learndash-cloud-setup',
            [ $this, 'outputSetupPage' ],
            \StellarWP\LearnDashCloud\PLUGIN_URL . '/images/blitz.svg',
            4
        );

        add_submenu_page(
            'non-existent-page',
            __('LearnDash Setup Wizard', 'learndash-cloud'),
            __('LearnDash Setup', 'learndash-cloud'),
            'manage_options',
            'learndash-cloud-setup-wizard',
            [ $this, 'outputSetupWizardPage' ]
        );
    }

    /**
     * Output setup page HTML
     *
     * @return void
     */
    public function outputSetupPage()
    {
        if (! class_exists('LearnDash_Settings_Section_Stripe_Connect')) {
            return;
        }

        $site_setup   = get_option('learndash_setup_wizard_status');
        $design_setup = get_option('learndash_cloud_design_wizard_status');

        $completed_steps = [
            'site_setup'    => 'completed' === $site_setup,
            'design_setup'  => 'completed' === $design_setup,
            'payment_setup' => StripeConnect::is_stripe_connected(),
            'go_live'       => $this->settings->get('has_custom_domain'),
        ];

        $this->renderTemplate('setup', [
            'completed_steps' => $completed_steps,
            'setup_wizard' => $this,
            'stripe_connect_url' => StripeConnect::generate_connect_url(),
            'dns_help_url' => $this->settings->get('dns_help_url', ''),
            'nameservers' => Domain::NEXCESS_NAMESERVERS,
            'ip_address' => $completed_steps['go_live'] ? '' : $this->domain->getCurrentIpAddress(),
            'overview_video' => $this->support->getArticles('overview_video')[0],
            'overview_article' => $this->support->getArticles('overview_article')[0],
        ]);
    }

    /**
     * Output setup wizard page HTML
     *
     * @return void
     */
    public function outputSetupWizardPage()
    {
        update_option('learndash_cloud_run_setup_wizard', 1);
        $this->renderTemplate('wizard');
    }

    /**
     * Display wizard screen after user login
     *
     * @param string   $user_login
     * @param \WP_User $user
     * @return void
     */
    public function initWizardScreen($user_login, $user)
    {
        if (
            defined('LEARNDASH_ADMIN_CAPABILITY_CHECK')
            && $user->has_cap(LEARNDASH_ADMIN_CAPABILITY_CHECK)
        ) {
            $run_setup = get_option('learndash_cloud_run_setup_wizard', false);

            if (! $run_setup) {
                // Filter to customize wp_die() handler for this redirect.
                add_filter('wp_die_handler', [$this, 'wpDieHandler']); // @phpstan-ignore-line -- handler customization

                wp_safe_redirect(add_query_arg([ 'page' => 'learndash-cloud-setup-wizard'], admin_url('admin.php')));
                wp_die();
            }
        }
    }

    /**
     * Filter LearnDash setup wizard completed redirect URL
     *
     * @param string $url
     * @return string
     */
    public function setupWizardRedirectUrl($url)
    {
        return add_query_arg(
            [ 'page' => 'learndash-cloud-setup' ],
            admin_url('admin.php')
        );
    }

    /**
     * Filters LearnDash setup wizard scenes to remove the License scene.
     *
     * @param array<string> $scenes
     * @return array<string>
     */
    public function setupWizardRemoveLicenseScene($scenes)
    {
        unset($scenes['step-0']);
        unset($scenes['step-1']);

        return $scenes;
    }

    /**
     * Custom wp_die() handler
     *
     * @return void
     */
    public function wpDieHandler()
    {
        die();
    }

    /**
     * AJAX handler for processing setup wizard form.
     *
     * @return void
     */
    public function ajaxProcessUserSetup()
    {
        if (
            empty($_POST['nonce']) ||
            /** @phpstan-ignore-next-line */
            ! wp_verify_nonce(strval($_POST['nonce']), static::AJAX_ACTIONS['processUserSetup']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,Generic.Files.LineLength.TooLong
        ) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-nonce-failure',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                __('The security nonce has expired or is invalid. Please refresh the page and try again.', 'learndash-cloud')
            ), 401);
        }

        if (! current_user_can('manage_options')) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-capabilities-failure',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                __('You do not have permission to perform this action. Please contact a site administrator to run the setup wizard.', 'learndash-cloud')
            ), 403);
        }

        /**
         * @var array<string, mixed>
         */
        $user_data = get_option('learndash_cloud_setup_wizard_data', []);
        $password_placeholder = 'YourPasswordIsSafe';

        $errors = [
            'others' => []
        ];

        // Create admin user.
        if (
            ! empty($_REQUEST['username'])
            && ! empty($_REQUEST['password'])
            && $password_placeholder !== $_REQUEST['password']
        ) {
            $username = sanitize_user(wp_unslash(is_string($_REQUEST['username']) ? $_REQUEST['username'] : ''));
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $password = strval($_REQUEST['password']); /** @phpstan-ignore-line */

            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_pass' => $password,
                'role' => 'administrator',
            ]);

            if (is_wp_error($user_id)) {
                $error_code = $user_id->get_error_code();

                if (
                    is_string($error_code) &&
                    (
                        false !== mb_strpos($error_code, 'user_login') ||
                        false !== mb_strpos($error_code, 'username')
                    )
                ) {
                    $errors['username'] = $user_id->get_error_message();
                } else {
                    $errors['others'][] = $user_id->get_error_message();
                }

                wp_send_json_error([
                    'message' => $user_id->get_error_message(),
                    'code' => $user_id->get_error_code(),
                    'errors' => $errors,
                ], 422);
            }

            $user_data['username'] = $username;
            // Make sure not to save plain text password because we'll use this as placeholder.
            $user_data['password'] = $password_placeholder;
        }

        if (! empty($user_data)) {
            update_option('learndash_cloud_setup_wizard_data', $user_data);
        }

        $response = [
            'message' => __('Admin user setup has been successfully completed.', 'learndash-cloud'),
        ];

        wp_send_json_success($response);
    }

    /**
     * AJAX handler for processing site setup form.
     *
     * @return void
     */
    public function ajaxProcessSiteSetup()
    {
        if (
            empty($_POST['nonce']) ||
            /** @phpstan-ignore-next-line */
            ! wp_verify_nonce(strval($_POST['nonce']), static::AJAX_ACTIONS['processSiteSetup']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,Generic.Files.LineLength.TooLong
        ) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-nonce-failure',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                __('The security nonce has expired or is invalid. Please refresh the page and try again.', 'learndash-cloud')
            ), 401);
        }

        if (! current_user_can('manage_options')) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-capabilities-failure',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                __('You do not have permission to perform this action. Please contact a site administrator to run the setup wizard.', 'learndash-cloud')
            ), 403);
        }

        /**
         * @var array<string, mixed>
         */
        $user_data = get_option('learndash_cloud_setup_wizard_data', []);

        $errors = [
            'others' => []
        ];

        $sitename = isset($_REQUEST['sitename']) && is_string($_REQUEST['sitename'])
            ? sanitize_text_field(wp_unslash($_REQUEST['sitename']))
            : null;
        $tagline = isset($_REQUEST['tagline']) && is_string($_REQUEST['tagline'])
            ? sanitize_text_field(wp_unslash($_REQUEST['tagline']))
            : null;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $logo = isset($_FILES['logo']) ? $_FILES['logo'] : null;

        // Set site details.
        if (! empty($sitename)) {
            update_option('blogname', $sitename);
            $user_data['sitename'] = $sitename;
        }

        if (! empty($tagline)) {
            update_option('blogdescription', $tagline);
            $user_data['tagline'] = $tagline;
        }

        // Upload logo.
        if (! empty($logo)) {
            // @phpstan-ignore-next-line
            $file = wp_handle_upload($logo, [ 'test_form' => false ]);

            if (isset($file['error'])) {
                $errors['logo'] = $file['error'];

                wp_send_json_error([
                    'message' => sprintf(
                        // translators: error message.
                        __('Can\'t upload the logo. Error: %s.', 'learndash-cloud'),
                        $file['error']
                    ),
                    'errors' => $errors,
                ], 422);
            }

            $url      = $file['url'];
            $type     = $file['type'];
            $file     = $file['file'];
            $filename = wp_basename($file);

            // Construct the object array.
            $object = [
                'post_title'     => $filename,
                'post_content'   => $url,
                'post_mime_type' => $type,
                'guid'           => $url,
            ];

            // Save the data.
            $id = wp_insert_attachment($object, $file);

            // Add the metadata.
            wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $file));

            update_option('site_logo', $id);

            $user_data['logo'] = $filename;
            $user_data['logoBase64'] = ! empty($_POST['logoBase64']) && is_string($_POST['logoBase64'])
                ? sanitize_text_field(wp_unslash($_POST['logoBase64']))
                : null;
        }

        if (! empty($user_data)) {
            update_option('learndash_cloud_setup_wizard_data', $user_data);
        }

        $response = [
            'message' => __('Site setup has been successfully completed.', 'learndash-cloud'),
        ];

        wp_send_json_success($response);
    }

    /**
     * AJAX handler for domain change request
     *
     * @return void
     */
    public function ajaxDomainChange()
    {
        if (
            empty($_POST['_wpnonce']) ||
            /** @phpstan-ignore-next-line */
            ! wp_verify_nonce(strval($_POST['_wpnonce']), self::AJAX_ACTIONS['domainChange']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,Generic.Files.LineLength.TooLong
        ) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-nonce-failure',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                __('The security nonce has expired or is invalid. Please refresh the page and try again.', 'learndash-cloud')
            ), 401);
        }

        if (! current_user_can('manage_options')) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-capabilities-failure',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                __('You do not have permission to perform this action. Please contact a site administrator to change the site domain.', 'learndash-cloud')
            ), 403);
        }

        if (empty($_POST['domain'])) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-validation-failure',
                __('Missing the required "domain" parameter.', 'learndash-cloud')
            ), 422);
        }

        try {
            $new_domain = sanitize_text_field(wp_unslash(is_string($_POST['domain']) ? $_POST['domain'] : ''));
            $domain_changed = $this->domain->changeDomain(
                $new_domain,
                ! empty($_POST['skipDnsVerification'])
            );

            if ($domain_changed) {
                update_option('learndash_cloud_domain_changed_to', $new_domain);
            }
        } catch (ValidationException $e) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-validation-failure',
                $e->getMessage()
            ), 422);
        } catch (RequestException $e) {
            wp_send_json_error(new WP_Error(
                'learndash-cloud-request-failure',
                $e->getMessage()
            ), 500);
        }

        wp_send_json_success(null, 202);
    }

    /**
     * Prevents showing the default screen WP check if a domain changes.
     *
     * @param  bool $show Whether to load the authentication check.
     *
     * @return bool
     */
    public function hideDefaultPopupAfterDomainChange(bool $show): bool
    {
        return get_option('learndash_cloud_domain_changed_to') ? false : $show;
    }

    /**
     * Enqueue specific popup scripts if domain changes.
     *
     * @return void
     */
    public function enqueueAfterDomainChangeScripts()
    {
        $cloud_domain_changed_to = get_option('learndash_cloud_domain_changed_to');

        if ($this->settings->get('has_custom_domain') && ! $cloud_domain_changed_to) {
            return;
        }

        if ($cloud_domain_changed_to) {
            wp_enqueue_style('wp-auth-check');
            wp_enqueue_script('wp-auth-check');
        }

        add_action('admin_print_footer_scripts', [ $this, 'wpDomainChangeHtml' ], 5);
        add_action('wp_print_footer_scripts', [ $this, 'wpDomainChangeHtml' ], 5);
    }

    /**
     * Output the HTML that shows the wp-login dialog after the domain changes.
     *
     * @return void -- Outputs the HTML.
     */
    public function wpDomainChangeHtml()
    {
        $login_url  = wp_login_url('', true);
        $new_domain = strval(get_option('learndash_cloud_domain_changed_to', '')); /** @phpstan-ignore-line */
        $old_domain = (string) wp_parse_url($login_url, PHP_URL_HOST);

        if (! empty($new_domain)) {
            $login_url = str_replace($old_domain, $new_domain, $login_url);
        }

        $id_suffix = empty($new_domain) ? '-ld-cloud-domain-changed' : ''; ?>
          <div id="wp-auth-check-wrap<?php echo esc_attr($id_suffix); ?>" class="hidden fallback"
                data-baseurl="<?php echo esc_attr($old_domain); ?>">
            <div id="wp-auth-check-bg<?php echo esc_attr($id_suffix); ?>"></div>
            <div id="wp-auth-check<?php echo esc_attr($id_suffix); ?>">
              <button type="button" class="wp-auth-check-close button-link">
                <span class="screen-reader-text"><?php esc_html_e('Close dialog', 'learndash-cloud'); ?></span>
              </button>

              <div class="wp-auth-fallback">
                <p>
                  <b class="wp-auth-fallback-expired" tabindex="0">
                    <?php esc_html_e('Success!', 'learndash-cloud'); ?>
                  </b>
                </p>
                <p>
                  <?php esc_html_e('Your website is now live on your new domain.', 'learndash-cloud'); ?>
                  <a id="ld-cloud-setup-domain-changed-login"
                    href="<?php echo esc_url($login_url); ?>" target="_blank">
                    <?php esc_html_e('Please log in to continue.', 'learndash-cloud'); ?>
                  </a>
                </p>
              </div>
            </div>
          </div>
        <?php
    }

    /**
     * Flushes WP permalinks after domain change
     *
     * @param string   $user_login
     * @param \WP_User $user
     *
     * @return void
     */
    public function flushPermalinksAfterDomainChange(string $user_login, \WP_User $user)
    {
        if (
            ! defined('LEARNDASH_ADMIN_CAPABILITY_CHECK')
            || ! $user->has_cap(LEARNDASH_ADMIN_CAPABILITY_CHECK)
            || empty(get_option('learndash_cloud_domain_changed_to'))
        ) {
            return;
        }

        flush_rewrite_rules();
        delete_option('learndash_cloud_domain_changed_to');
        update_option('learndash_cloud_flush_permalinks_later', true);
    }

    /**
     * Flushes WP permalinks at the first access.
     *
     * @return void
     */
    public function welcomeFlushPermalinks()
    {
        if (get_option('learndash_cloud_welcome_setup_finished')) {
            return;
        }

        flush_rewrite_rules();
        update_option('learndash_cloud_welcome_setup_finished', true);
    }

    /**
     * Flushes WP permalinks if necessary.
     *
     * @return void
     */
    public function maybeFlushPermalinks()
    {
        if (! get_option('learndash_cloud_flush_permalinks_later')) {
            return;
        }

        flush_rewrite_rules();
        delete_option('learndash_cloud_flush_permalinks_later');
    }

    /**
     * Render the domain change form React component.
     *
     * This method will print the div#learndash-cloud-change-domain-form that React will look for as its root element.
     *
     * @return void
     */
    public function renderDomainChangeForm()
    {
        echo wp_kses_post(sprintf(
            '<div id="learndash-cloud-change-domain-form" %s></div>',
            Attributes::getDataAttributeString([
                'ajaxUrl'       => admin_url('admin-ajax.php'),
                'currentDomain' => wp_parse_url(site_url(), PHP_URL_HOST),
                'dnsHelpUrl'    => $this->settings->get('dns_help_url', ''),
                'nonce'         => wp_create_nonce(static::AJAX_ACTIONS['domainChange']),
            ])
        ));
    }
}

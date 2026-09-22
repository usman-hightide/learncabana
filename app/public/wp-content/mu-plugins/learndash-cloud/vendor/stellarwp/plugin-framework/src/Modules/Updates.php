<?php

/**
 * Telemetry data collected for Managed Application services.
 */

namespace StellarWP\PluginFramework\Modules;

use Core_Upgrader;
use StellarWP\PluginFramework\Concerns\MakesHttpRequests;
use StellarWP\PluginFramework\Contracts\ProvidesSettings;
use StellarWP\PluginFramework\Services\FeatureFlags as FeatureFlagsService;
use StellarWP\PluginFramework\Support\Branding as BrandingSupport;
use StellarWP\PluginFramework\Support\WPPackages;
use WP_Error;
use WP_Upgrader;

class Updates extends Module
{
    use MakesHttpRequests;

    /**
     * The Feature Flags service.
     *
     * @var FeatureFlagsService
     */
    protected $features;

    /**
     * @var ProvidesSettings
     */
    protected $settings;

    /**
     * @param ProvidesSettings    $settings
     * @param FeatureFlagsService $features
     */
    public function __construct(ProvidesSettings $settings, FeatureFlagsService $features)
    {
        $this->features = $features;
        $this->settings = $settings;
    }

    /**
     * Perform any necessary setup for the integration.
     *
     * This method is automatically called as part of Plugin::loadIntegration(), and is the
     * entry-point for all integrations.
     */
    public function setup()
    {
        // Control which automatic updates are permitted by default (minor only - no dev or major).
        add_filter('allow_dev_auto_core_updates', '__return_false', 1);
        add_filter('allow_major_auto_core_updates', [ $this, 'maybeAutoUpdateCoreMajor' ], 1);
        add_filter('allow_minor_auto_core_updates', '__return_true', 1);

        // Disable the update if it is blocked with Feature Flag service.
        add_filter('upgrader_pre_download', [ $this, 'preventBlockedCoreUpdates' ], 10, 3);

        // Disable auto plugin updates if handled by the platform.
        if (false === $this->settings->plugin_updates_enabled) {
            add_filter('auto_update_plugin', '__return_false', 1);
            add_filter('plugins_auto_update_enabled', '__return_false', 1);
        }

        add_action('admin_init', [ $this, 'removeUpdateNag' ]);
        add_action('after_core_auto_updates_settings', [ $this, 'renderAutoUpdateNotice' ]);

        // Disable the "Try Gutenberg" dashboard widget (WP < 5.x only).
        remove_action('try_gutenberg_panel', 'wp_try_gutenberg_panel');
    }

    /**
     * Whether to enable major automatic core updates.
     *
     * @param bool $upgrade_major True if allowed to upgrade to major versions, else false.
     *
     * @return bool
     */
    public function maybeAutoUpdateCoreMajor($upgrade_major)
    {
        $updates = (object) get_transient('update_core');

        if (empty($updates->updates)) {
            return $this->settings->enable_auto_core_updates_major;
        }

        foreach ($updates->updates as $update) {
            if ('autoupdate' !== $update->response) {
                continue;
            }

            $update_parts = explode('.', $update->current);
            $major_version = $update_parts[0] . '.' . $update_parts[1];

            if (! array_key_exists($major_version, $this->features->getActive())) {
                continue;
            }

            // This major version has a flag, but this site isn't allowed to run it right now.
            if (! $this->features->enabled($major_version)) {
                return false;
            }
        }

        return $this->settings->enable_auto_core_updates_major;
    }

    /**
     * Prevents downloading of WordPress core updates blocked by Feature Flags.
     *
     * This method checks if the specified WordPress core update version is currently
     * blocked by active Feature Flags. It prevents the update from proceeding if the version
     * is flagged.
     *
     * @param bool        $reply Whether to bail without returning the package.
     * @param string      $package The package file name.
     * @param WP_Upgrader $upgrader The WP_Upgrader instance.
     *
     * @return bool|WP_Error
     */
    public function preventBlockedCoreUpdates($reply, $package, $upgrader)
    {
        // Verify core updates only.
        if (! $upgrader instanceof Core_Upgrader) {
            return $reply;
        }

        // Get package version.
        $version = WPPackages::extractVersionFromPackage($package);
        if (! $version) {
            return $reply;
        }

        // Check if the version is blocked.
        $response = wp_remote_get($this->settings->feature_flags_url);
        $json     = json_decode($this->validateHttpResponse($response, 200), true);
        $blocked  = isset($json['blocks']['core']) ? (array) $json['blocks']['core'] : []; // @phpstan-ignore-line

        // Prevent download if version is blocked.
        if (in_array($version, $blocked, true)) {
            return new WP_Error(
                'update_blocked',
                sprintf(
                    // translators: %s is the blocked version number.
                    __('WordPress %s is currently blocked for technical issues. Please try again later, or contact Nexcess for more information.', 'stellarwp-framework'), // phpcs:ignore Generic.Files.LineLength.TooLong
                    $version
                )
            );
        }

        return $reply;
    }

    /**
     * Remove the "WordPress X.X is available! Please notify the site administrator" nags.
     *
     * @see update_nag()
     * @return void
     */
    public function removeUpdateNag()
    {
        if ($this->settings->core_updates_enabled) {
            remove_action('admin_notices', 'update_nag', 3);
        }
    }

    /**
     * Render a notice reminding users that core updates are currently being handled by the platform.
     *
     * @return void
     */
    public function renderAutoUpdateNotice()
    {
        if (! $this->settings->core_updates_enabled) {
            return;
        }

        ob_start();
        ?>
        <div class="notice notice-info inline">
            <p>
                <?php printf(
                    /* Translators: %1$s is the brand name. */
                    esc_attr__(
                        'WordPress core is currently being automatically updated by %1$s.',
                        'stellarwp-framework'
                    ),
                    esc_attr(BrandingSupport::getCompanyName())
                ); ?>
            </p>
        </div>
        <?php
    }
}

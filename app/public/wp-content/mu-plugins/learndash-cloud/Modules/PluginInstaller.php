<?php

namespace StellarWP\LearnDashCloud\Modules;

use Psr\Log\LoggerInterface;
use StellarWP\PluginFramework\Contracts\ProvidesSettings;
use StellarWP\PluginFramework\Exceptions\InstallationException;
use StellarWP\PluginFramework\Exceptions\LicensingException;
use StellarWP\PluginFramework\Modules\Module;
use StellarWP\PluginFramework\Services\FeatureFlags;
use StellarWP\PluginFramework\Services\Managers\CronEventManager;
use StellarWP\PluginFramework\Services\Nexcess\MappsApiClient;

class PluginInstaller extends Module
{
    /**
     * The daily cron action name.
     */
    public const DAILY_OCP_PLUGIN_MIGRATION_CRON_ACTION = 'learndashcloud_daily_ocp_plugin_migration';

    /**
     * Action name used to prevent the check from running multiple times in one request.
     */
    public const MIGRATE_TO_OCP_ACTION = 'LearnDashCloud\\Integrations\\PluginInstaller\\MigrateToOCP';

    public const MIGRATE_TO_OCP_OPTION = 'learndashcloud_did_migrate_to_ocp';

    /**
     * The cron event manager.
     *
     * @var CronEventManager
     */
    protected $cron_manager;

    /**
     * @var FeatureFlags
     */
    protected $featureFlags;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var ProvidesSettings
     */
    protected $settings;

    /**
     * @var MappsApiClient
     */
    protected $api_client;

    /**
     * @param ProvidesSettings $settings
     * @param LoggerInterface  $logger
     * @param FeatureFlags     $feature_flags
     * @param MappsApiClient   $api_client
     * @param CronEventManager $cron_manager
     */
    public function __construct(
        ProvidesSettings $settings,
        LoggerInterface $logger,
        FeatureFlags $feature_flags,
        MappsApiClient $api_client,
        CronEventManager $cron_manager
    ) {
        $this->settings     = $settings;
        $this->logger       = $logger;
        $this->featureFlags = $feature_flags;
        $this->api_client   = $api_client;
        $this->cron_manager = $cron_manager;
    }

    /**
     * Perform any necessary setup for the integration.
     *
     * This method is automatically called as part of Plugin::loadIntegration(), and is the
     * entry-point for all integrations.
     */
    public function setup()
    {
        add_action(self::DAILY_OCP_PLUGIN_MIGRATION_CRON_ACTION, [ $this, 'migrateToOCP' ]);

        // Register cron events.
        if ($this->featureFlags->enabled('migrate-redis-cache-to-ocp')) {
            $this->cron_manager->register(self::DAILY_OCP_PLUGIN_MIGRATION_CRON_ACTION, 'daily');
        }
    }

    /**
     * Migrate site to OCP.
     *
     * @return void
     */
    public function migrateToOCP()
    {
        // Make sure that we don't run this action multiple times in one request.
        if (did_action(self::MIGRATE_TO_OCP_ACTION)) {
            return;
        }

        // Fire the action to avoid a loop.
        do_action(self::MIGRATE_TO_OCP_ACTION);

        // If we've already migrated to OCP, no need to check it and do it again.
        if (! empty(get_option(self::MIGRATE_TO_OCP_OPTION))) {
            return;
        }

        // Remove Redis Cache.
        if ($this->isPluginActive('redis-cache/redis-cache.php')) {
            $this->deactivatePlugin('redis-cache/redis-cache.php');
        }

        try {
            $this->api_client->install('object-cache-pro');
        } catch (InstallationException $e) {
            $this->logger->info(sprintf(
                /* Translators: %1$s is the previous exception message. */
                __('Unable to install Object Cache Pro: %1$s', 'learndash-cloud'),
                $e->getMessage()
            ));
            return;
        }

        try {
            $this->api_client->license('object-cache-pro');
        } catch (LicensingException $e) {
            $this->logger->info(sprintf(
                /* Translators: %1$s is the previous exception message. */
                __('Unable to license Object Cache Pro: %1$s', 'learndash-cloud'),
                $e->getMessage()
            ));
            return;
        }

        // Prevent this from happening again.
        update_option(self::MIGRATE_TO_OCP_OPTION, true);

        $timestamp = wp_next_scheduled(self::DAILY_OCP_PLUGIN_MIGRATION_CRON_ACTION);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::DAILY_OCP_PLUGIN_MIGRATION_CRON_ACTION);
        }
    }

    /**
     * Verify that a plugin is both installed and active.
     *
     * This is a wrapper around WordPress' is_plugin_active() function, ensuring the necessary
     * file is loaded before checking.
     *
     * @see is_plugin_active()
     *
     * @param string $plugin The directory/file path.
     *
     * @return bool
     */
    public function isPluginActive($plugin)
    {
        if (! function_exists('is_plugin_active')) {
            // @phpstan-ignore-next-line
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active($plugin);
    }

    /**
     * Deactivate one or more plugins.
     *
     * This is a wrapper around WordPress' deactivate_plugins() function, ensuring the necessary
     * file is loaded before checking.
     *
     * @param string $plugin The plugin file(s) to deactivate.
     * @param bool   $silent Whether or not to bypass deactivation hooks. Default is false.
     *
     * @see deactivate_plugins()
     *
     * @return void
     */
    public function deactivatePlugin($plugin, $silent = false)
    {
        if (! function_exists('deactivate_plugins')) {
            // @phpstan-ignore-next-line
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        deactivate_plugins($plugin, $silent);
    }
}

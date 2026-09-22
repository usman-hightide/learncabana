<?php

namespace StellarWP\LearnDashCloud\Console\Commands;

use StellarWP\LearnDashCloud\Modules\PluginInstaller;
use StellarWP\PluginFramework\Console\Commands\ObjectCacheProCommand as BaseCommand;
use StellarWP\PluginFramework\Extensions\Plugins\ObjectCachePro as Plugin;

/**
 * Commands for managing Object Cache Pro.
 */
class ObjectCacheProCommand extends BaseCommand
{
    /**
     * @var PluginInstaller
     */
    public $pluginInstaller;

    /**
     * @param Plugin          $plugin
     * @param PluginInstaller $pluginInstaller
     */
    public function __construct(Plugin $plugin, PluginInstaller $pluginInstaller)
    {
        parent::__construct($plugin);

        $this->pluginInstaller = $pluginInstaller;
    }

    /**
     * Adds the cron event for migrating to Object Cache Pro.
     *
     * ## EXAMPLES
     *
     * $ wp nxmapps object-cache-pro migrate
     * Success: OCP migration cron scheduled successfully.
     *
     * @return void
     */
    public function migrate()
    {
        $this->pluginInstaller->migrateToOCP();
        if (! empty(get_option(PluginInstaller::MIGRATE_TO_OCP_OPTION, false))) {
            $this->success(__('OCP migration complete.', 'learndash-cloud'));
        } else {
            $this->warning(__('OCP migration did not complete successfully.', 'learndash-cloud'));
        }
    }
}

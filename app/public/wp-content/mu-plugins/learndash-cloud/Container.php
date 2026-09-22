<?php

namespace StellarWP\LearnDashCloud;

use StellarWP\PluginFramework as Framework;

/**
 * The Dependency Injection (DI) container definition for the plugin.
 *
 * @link https://github.com/stellarwp/container
 */
class Container extends Framework\Container
{
    /**
     * Retrieve a mapping of abstract identifiers to callables.
     *
     * When an abstract is requested through the container, the container will find the given
     * dependency in this array, execute the callable, and return the result.
     *
     * @return Array<string,callable|object|string|null> A mapping of abstracts to callables.
     *
     * @codeCoverageIgnore
     */
    public function config()
    {
        return array_merge(parent::config(), [
            Framework\Plugin::class => Plugin::class,
            Plugin::class           => function ($app) {
                return new Plugin(
                    $app,
                    $app->make(Framework\Services\Logger::class)
                );
            },
            Settings::class         => null,

            // Contracts.
            Framework\Contracts\ProvidesSettings::class     => Settings::class,
            Framework\Contracts\ProvidesSupportUsers::class => Modules\SupportUsers::class,

            // WP-CLI Commands.
            Console\Commands\CacheCommand::class => null,
            Console\Commands\ObjectCacheProCommand::class => function ($app) {
                return new Console\Commands\ObjectCacheProCommand(
                    $app->make(Framework\Extensions\Plugins\ObjectCachePro::class),
                    $app->make(Modules\PluginInstaller::class)
                );
            },
            Console\Commands\SetupCommand::class => function ($app) {
                return new Console\Commands\SetupCommand(
                    $app->make(Framework\Services\Cache::class),
                    $app->make(Framework\Contracts\ProvidesSettings::class),
                    $app->make(Services\SetupInstructions::class)
                );
            },

            // Modules.
            Modules\DesignWizard::class => function ($app) {
                return new Modules\DesignWizard(
                    $app->make(Framework\Contracts\ProvidesSettings::class)
                );
            },

            Modules\PluginInstaller::class => function ($app) {
                return new Modules\PluginInstaller(
                    $app->make(Framework\Contracts\ProvidesSettings::class),
                    $app->make(Framework\Services\Logger::class),
                    $app->make(Framework\Services\FeatureFlags::class),
                    $app->make(Framework\Services\Nexcess\MappsApiClient::class),
                    $app->make(Framework\Services\Managers\CronEventManager::class)
                );
            },

            Modules\SetupWizard::class => function ($app) {
                return new Modules\SetupWizard(
                    $app->make(Modules\Support::class),
                    $app->make(Framework\Contracts\ProvidesSettings::class),
                    $app->make(Services\Domain::class)
                );
            },

            Modules\Support::class => function ($app) {
                return new Modules\Support(
                    $app->make(Framework\Contracts\ProvidesSettings::class)
                );
            },

            Modules\SupportUsers::class => function ($app) {
                return new Modules\SupportUsers(
                    $app->make(Framework\Services\Managers\CronEventManager::class),
                    $app->make(Framework\Contracts\ProvidesSettings::class)
                );
            },

            Modules\VisualComparison::class => null,

            // Services.
            Services\Domain::class => function ($app) {
                return new Services\Domain(
                    $app->make(Framework\Contracts\ProvidesSettings::class),
                    $app->make(Framework\Services\Nexcess\MappsApiClient::class)
                );
            },

            Services\SetupInstructions::class => function ($app) {
                return new Services\SetupInstructions(
                    $app->make(Framework\Contracts\ProvidesSettings::class),
                    $app->make(Framework\Services\Logger::class)
                );
            },
        ]);
    }
}

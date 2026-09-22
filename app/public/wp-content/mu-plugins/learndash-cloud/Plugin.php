<?php

namespace StellarWP\LearnDashCloud;

use StellarWP\PluginFramework as Framework;

/**
 * The main plugin instance.
 */
class Plugin extends Framework\Plugin
{
    /**
     * An array containing all registered WP-CLI commands.
     *
     * @var Array<string,class-string<Framework\Console\WPCommand>>
     */
    protected $commands = [
        'learndash-cloud extension'        => Framework\Console\Commands\ExtensionCommand::class,
        'learndash-cloud ithemes'          => Framework\Console\Commands\iThemesCommand::class,
        'learndash-cloud object-cache-pro' => Console\Commands\ObjectCacheProCommand::class,
        'learndash-cloud setup'            => Console\Commands\SetupCommand::class,
        'learndash-cloud support-user'     => Framework\Console\Commands\SupportUserCommand::class,
        'learndash-cloud telemetry'        => Framework\Console\Commands\TelemetryCommand::class,
        'learndash-cloud vc'               => Framework\Console\Commands\VisualComparisonCommand::class,

        // Needed for processing cancellations (NO-OP for now).
        'nxmapps cache'                => Console\Commands\CacheCommand::class,
        // The MAPPS API is expecting the license command to be available as "nxmapps add".
        'nxmapps ithemes'              => Framework\Console\Commands\iThemesCommand::class,
        // NXCLI calls the OCP command with the "nxmapps" prefix to avoid duplication.
        'nxmapps object-cache-pro'     => Console\Commands\ObjectCacheProCommand::class,
    ];

    /**
     * An array containing all registered modules.
     *
     * @var Array<int,class-string<Framework\Modules\Module>>
     */
    protected $modules = [
        Framework\Modules\AutoLogin::class,
        Framework\Modules\ExtensionConfig::class,
        Framework\Modules\GoLiveWidget::class,
        Framework\Modules\Maintenance::class,
        Framework\Modules\PurgeCaches::class,
        Framework\Modules\Robots::class,
        Framework\Modules\Telemetry::class,
        Framework\Modules\Updates::class,
        Framework\Modules\VisualComparison::class,
        Modules\DesignWizard::class,
        Modules\PluginInstaller::class,
        Modules\SetupWizard::class,
        Modules\Support::class,
        Modules\SupportUsers::class,
        Modules\VisualComparison::class,
    ];

    /**
     * An array containing all registered plugin configurations.
     *
     * @var Array<string,class-string<Framework\Extensions\Plugins\PluginConfig>>
     */
    protected $plugins = [
        'affiliate-wp/affiliate-wp.php'                     => Framework\Extensions\Plugins\AffiliateWP::class,
        'cache-enabler/cache-enabler.php'                   => Framework\Extensions\Plugins\CacheEnabler::class,
        'easy-digital-downloads/easy-digital-downloads.php' => Framework\Extensions\Plugins\EasyDigitalDownloads::class,
        'events-calendar-pro/events-calendar-pro.php'       => Framework\Extensions\Plugins\TheEventsCalendar::class,
        'object-cache-pro/object-cache-pro.php'             => Framework\Extensions\Plugins\ObjectCachePro::class,
        'redis-cache/redis-cache.php'                       => Framework\Extensions\Plugins\RedisCache::class,
        'sfwd-lms/sfwd_lms.php'                             => Framework\Extensions\Plugins\LearnDash::class,
        'the-events-calendar/the-events-calendar.php'       => Framework\Extensions\Plugins\TheEventsCalendar::class,
        'woocommerce/woocommerce.php'                       => Framework\Extensions\Plugins\WooCommerce::class,
    ];
}

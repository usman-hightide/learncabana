<?php

namespace StellarWP\PluginFramework\Extensions\Plugins;

use StellarWP\PluginFramework\Services\Cache;
use WP_Filesystem_Direct;

/**
 * Plugin configuration for Solid Performance.
 *
 * @link https://wordpress.org/plugins/solid-performance/
 */
class SolidPerformance extends PluginConfig
{
    /**
     * Actions to perform upon plugin activation.
     *
     * @param bool $network_wide Optional. Is the plugin being activated network-wide?
     *                           Default is false.
     *
     * @return void
     */
    public function activate($network_wide = false)
    {
        $option = get_option('solid_performance_settings', []);
        // @phpstan-ignore-next-line
        $option['page_cache']['exclusions'] = [
            '/events/*',
            '/courses/*',
            '/lessons/*',
            '/topic/*',
            '/quizzes/*',
            '/groups/*',
            '/cart',
            '/checkout',
            '/my-account',
            '/donation-confirmation',
            '/donation-failed',
            '/donation-history',
            '/donor-dashboard',
            '/recurring-donations',
            '/index\\.php',
            '/wp-.*\\.php'
        ];
        update_option('solid_performance_settings', $option);
    }

    /**
     * Actions to perform every time the plugin is loaded.
     *
     * @return void
     */
    public function load()
    {
        add_action(Cache::ACTION_PURGE_PAGE_CACHE, [$this, 'purgePageCache']);
    }

    /**
     * Clear the Solid Performance page cache.
     *
     * @return void
     */
    public static function purgePageCache()
    {
        // @phpstan-ignore-next-line
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        // @phpstan-ignore-next-line
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        $fs = new WP_Filesystem_Direct(false);
        $fs->delete(WP_CONTENT_DIR . '/cache/solid-performance/page', true);
    }
}

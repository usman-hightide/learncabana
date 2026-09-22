<?php

namespace StellarWP\PluginFramework\Modules;

use StellarWP\AdminNotice\AdminNotice;
use StellarWP\PluginFramework\Contracts\LoadsConditionally;
use StellarWP\PluginFramework\Contracts\PublishesAdminNotices;
use StellarWP\PluginFramework\Services\Cache;
use StellarWP\PluginFramework\Support\WPAdmin;
use WP_Admin_Bar;

class PurgeCaches extends Module implements LoadsConditionally, PublishesAdminNotices
{
    /**
     * The Cache service.
     *
     * @var Cache $cache
     */
    protected $cache;

    /**
     * The action hook for purging all caches.
     */
    const ACTION_PURGE_ALL_CACHES = 'stellarwp-purge-all-caches';

    /**
     * The ID of the WP Admin Bar menu item.
     */
    const ADMIN_BAR_MENU_ID = 'stellarwp-purge-caches';

    /**
     * Construct the PurgeCaches module.
     *
     * @param Cache $cache The Cache service.
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Perform any necessary setup for the module.
     *
     * This method is automatically called as part of Plugin::load_modules(), and is the
     * entry-point for all modules.
     *
     * @return void
     */
    public function setup()
    {
        // A priority of 1000 puts our button well-after all core menu items.
        add_action('admin_bar_menu', [$this, 'registerPurgeCachesButton'], 1000);
        add_action('init', [$this, 'purgeAllCaches']);
    }

    /**
     * Determine whether or not this extension should load.
     *
     * @return bool True if the extension should load, false otherwise.
     */
    public function shouldLoad()
    {
        return apply_filters('show_admin_bar', true);
    }

    /**
     * Add a button to the admin bar that flushes all caches.
     *
     * @param WP_Admin_Bar $wp_admin_bar The global WP_Admin_Bar instance, passed by reference.
     *
     * @return void
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     */
    public function registerPurgeCachesButton(WP_Admin_Bar $wp_admin_bar)
    {
        /*
         * Hide the "Purge All Caches" button if the current user lacks the "manage_options" capability.
         *
         * Ideally we'd rule out this user in $this->shouldLoad(), but the current user isn't available
         * until much later in the WordPress lifecycle.
         */
        if (! current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_menu([
            'id'     => self::ADMIN_BAR_MENU_ID,
            'parent' => null,
            'group'  => null,
            'href'   => null,
            'title'  => WPAdmin::adminPostButton(
                self::ACTION_PURGE_ALL_CACHES,
                _x('Purge All Caches', 'WP Admin Bar menu', 'stellarwp-framework'),
                '',
                self::ACTION_PURGE_ALL_CACHES
            )
        ]);
    }

    /**
     * Purge all caches for the current site.
     *
     * @return void
     */
    public function purgeAllCaches()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (empty($_POST['stellarwpAction']) || self::ACTION_PURGE_ALL_CACHES !== $_POST['stellarwpAction']) {
            return;
        }

        if (
            ! isset($_POST[self::ACTION_PURGE_ALL_CACHES]) ||
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            ! wp_verify_nonce(strval($_POST[self::ACTION_PURGE_ALL_CACHES]), self::ACTION_PURGE_ALL_CACHES)
        ) {
            AdminNotice::factory(
                sprintf(
                    '<p><strong>%s</strong></p><p>%s</p>',
                    __('Unable to purge caches: nonce verification failed.', 'stellarwp-framework'),
                    'Please refresh the page and try again.'
                ),
                AdminNotice::TYPE_ERROR
            )
                ->setDismissible(true)
                ->queue();
            return;
        }

        $this->cache->purgeAll();

        AdminNotice::factory(
            __('All caches are currently being purged!', 'stellarwp-framework'),
            AdminNotice::TYPE_SUCCESS
        )
            ->setDismissible(true)
            ->queue();
    }
}

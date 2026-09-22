<?php

namespace StellarWP\PluginFramework\Extensions\Plugins;

/**
 * Plugin configuration for W3 Total Cache by BoldGrid.
 *
 * @link https://wordpress.org/plugins/w3-total-cache/
 */
class W3TotalCache extends PluginConfig
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
        update_option('nxmu_w3tc_plugin_needs_configured', true);
    }

    /**
     * Actions to perform every time the plugin is loaded.
     *
     * @return void
     */
    public function load()
    {
        if (get_option('nxmu_w3tc_plugin_needs_configured')) {
            $this->configurePlugin();
        }
    }

    /**
     * Configure the plugin's settings.
     *
     * @return void
     */
    protected function configurePlugin()
    {
        $config = new \W3TC\Config();
        // No license key for now.
        $config->set('plugin.license_key', '');
        $config->set('dbcache.enabled', false);
        $config->set('dbcache.file.gc', 1800);
        $config->set('objectcache.enabled', false);
        $config->set('objectcache.enabled_for_wp_admin', true);
        $config->set('objectcache.groups.nonpersistent', [
            'comment',
            'counts',
            'plugins'
        ]);
        $config->set('pgcache.enabled', true);
        $config->set('pgcache.engine', 'file_generic');
        $config->set('pgcache.file.gc', 1800);
        $config->set('pgcache.reject.uri', [
            'cart',
            'checkout',
            'donation-confirmation',
            'donation-failed',
            'donation-history',
            'donor-dashboard',
            'event',
            'events',
            'index\\.php',
            'my-account',
            'recurring-donations',
            'wp-.*\\.php'
        ]);
        $config->set('pgcache.reject.cookie', [
            'affwp_campaign',
            'affwp_ref',
            'affwp_ref_visit_id',
            'wp-give_session_*',
            'wptouch_switch_toggle',
            'wptouch_switch_toggle'
        ]);
        $config->set('stats.access_log.webserver', 'apache');
        $config->set('minify.html.comments.ignore', [
            'google_ad_',
            'RSPEAK_'
        ]);
        $config->set('cdn.engine', 'maxcdn');
        $config->set('browsercache.security.referrer.policy.directive', 0);
        $config->set('common.track_usage', false);
        $config->set('extensions.active', []);
        $config->set('cdn.maxcdn.authorization_key', '');
        $config->set('cdn.maxcdn.domain', []);
        $config->set('cdn.maxcdn.ssl', 'auto');
        $config->set('cdn.maxcdn.zone_id', 0);
        $config->set('cdnfsd.maxcdn.api_key', '');
        $config->set('cdnfsd.maxcdn.zone_id', 0);
        $config->set('widget.pagespeed.key', '');
        $config->set('widget.pagespeed.key.restrict.referrer', '');
        $config->set('widget.pagespeed.show_in_admin_bar', false);
        $config->set('minify.css.combine', false);
        $config->set('common.support', '');
        $config->set('timelimit.cdn_container_create', 300);
        $config->save();

        delete_option('nxmu_w3tc_plugin_needs_configured');
    }
}

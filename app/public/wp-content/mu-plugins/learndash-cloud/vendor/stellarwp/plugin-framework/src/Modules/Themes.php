<?php

namespace StellarWP\PluginFramework\Modules;

/**
 * Customizations to themes.
 */
class Themes extends Module
{
    /**
     * Perform any necessary setup for the module.
     *
     * @return void
     */
    public function setup()
    {
        // Whitelabel Astra to hide ads and up-sells (outside of Astra Pro).
        add_filter('astra_is_white_labelled', '__return_true');

        // Hide Kadence's welcome notice.
        add_filter('pre_option_kadence_starter_plugin_notice', '__return_true');
        add_filter('pre_option_kadence_blocks_redirect_on_activation', '__return_zero');
    }
}

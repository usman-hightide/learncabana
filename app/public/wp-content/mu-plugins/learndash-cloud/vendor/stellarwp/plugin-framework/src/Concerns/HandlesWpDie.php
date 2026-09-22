<?php

namespace StellarWP\PluginFramework\Concerns;

trait HandlesWpDie
{
    /**
     * Registers a custom handler for the wp_die() function.
     *
     * @return void
     */
    public function handleWpDie()
    {
        add_filter('wp_die_ajax_handler', [ $this, 'maybeRegisterHandler' ]);
        add_filter('wp_die_json_handler', [ $this, 'maybeRegisterHandler' ]);
        add_filter('wp_die_jsonp_handler', [ $this, 'maybeRegisterHandler' ]);
        add_filter('wp_die_xmlrpc_handler', [ $this, 'maybeRegisterHandler' ]);
        add_filter('wp_die_xml_handler', [ $this, 'maybeRegisterHandler' ]);
        add_filter('wp_die_handler', [ $this, 'maybeRegisterHandler' ]);
    }

    /**
     * Register the handler callback.
     *
     * @param callable $handler Callback method.
     *
     * @return callable
     */
    public function maybeRegisterHandler($handler)
    {
        if (
            ! empty($_SERVER['argv'][1]) && in_array($_SERVER['argv'][1], [ 'nexcess-mapps', 'nxmapps' ], true)
            && ! empty($_SERVER['argv'][2]) && 'setup' === $_SERVER['argv'][2]
        ) {
            return [ $this, 'handler' ];
        }
        return $handler;
    }

    /**
     * Overwrites the native wp_die() to prevent calling PHP die().
     *
     * @param string $message Message passed in to wp_die().
     *
     * @return null
     */
    public function handler($message)
    {
        return null;
    }
}

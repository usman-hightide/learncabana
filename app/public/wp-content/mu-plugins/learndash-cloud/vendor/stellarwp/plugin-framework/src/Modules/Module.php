<?php

namespace StellarWP\PluginFramework\Modules;

/**
 * The basic definition for a module.
 */
abstract class Module
{
    /**
     * Perform any necessary setup for the module.
     *
     * This method is automatically called as part of Plugin::load_modules(), and is the
     * earliest possible time to run code. Due to timing issues, this method should be
     * reserved for code that needs to happen before the WordPress `init` action.
     *
     * @return void
     */
    public function setup()
    {
    }

    /**
     * Perform any necessary setup for the module.
     *
     * This method is automatically called as part of Plugin::load_modules(), and should
     * be the default entry-point for all modules. It is hooked into the WordPress `init`
     * action.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Perform any necessary setup for the module.
     *
     * This method is directly called by Plugin::load_modules().
     *
     * @return void
     */
    public function load()
    {
        $this->setup();
        add_action('init', [ $this, 'init' ]);
    }
}

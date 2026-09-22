<?php

namespace StellarWP\LearnDashCloud\Console\Commands;

use StellarWP\PluginFramework\Console\WPCommand;

/**
 * Commands for managing cache.
 */
class CacheCommand extends WPCommand
{
    /**
     * Flush caches (NO-OP).
     *
     * ## EXAMPLES
     *
     * $ wp nxmapps cache flush
     * Success: Caches flushed successfully.
     *
     * @return void
     */
    public function flush()
    {
        $this->success('Caches flushed successfully.');
    }
}

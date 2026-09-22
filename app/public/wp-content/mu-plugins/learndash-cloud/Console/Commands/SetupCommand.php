<?php

namespace StellarWP\LearnDashCloud\Console\Commands;

use StellarWP\LearnDashCloud\Settings;
use StellarWP\PluginFramework\Console\Commands\Setup as BaseSetup;
use StellarWP\PluginFramework\Services\Cache;
use StellarWP\PluginFramework\Services\SetupInstructions;

/**
 * Commands to handle the initial setup of a site.
 */
class SetupCommand extends BaseSetup
{
    /**
     * The Cache service.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * The Settings instance.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * The SetupInstructions service.
     *
     * @var SetupInstructions
     */
    protected $setupInstructions;

    /**
     * Construct a new instance of the Setup command.
     *
     * @param Cache             $cache
     * @param Settings          $settings
     * @param SetupInstructions $instructions
     */
    public function __construct(Cache $cache, Settings $settings, SetupInstructions $instructions)
    {
        $this->cache             = $cache;
        $this->settings          = $settings;
        $this->setupInstructions = $instructions;
    }

    /**
     * Run the initial setup of the site.
     *
     * ## OPTIONS
     *
     * [--provision]
     * : Signal that this is being run as part of the initial site provisioning.
     *
     * @synopsis [--provision]
     *
     * @param Array<int,scalar>     $args Positional arguments.
     * @param Array<string,?scalar> $opts Options passed to the command.
     *
     * @return void
     */
    public function __invoke(array $args, array $opts)
    {
        // If running the initial provisioning, explicitly flush the SiteWorx cache.
        if (! empty($opts['provision'])) {
            $this->enableProvisioningLogs();

            $this->step('Provisioning: Clearing all caches');
            $this->cache->purgeAll();
            $this->settings->refresh();
            $this->success('All caches have been cleared.');
        }

        $this->updateWordPress()
            ->setDefaultPermalinkStructure()
            ->runSetupInstructions();
    }

    /**
     * Execute the setup instructions provided by the StellarWP Partner Gateway.
     *
     * @return $this
     */
    protected function runSetupInstructions()
    {
        $this->step('Performing LearnDash Setup Instructions');
        $instructions = $this->setupInstructions->getInstructions();

        foreach ($instructions->getCommands() as $command) {
            $this->newline()
                ->log('+ ' . $command->getShellCommand());

            if ($output = $command->execute()->getOutput()) {
                $this->log($output);
            }
        }
        $this->success('LearnDash setup complete.');

        return $this;
    }
}

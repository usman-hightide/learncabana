<?php

namespace StellarWP\LearnDashCloud\Services;

use StellarWP\PluginFramework\Services\SetupInstructions as BaseSetupInstructions;

/**
 * Handles retrieving and executing setup instructions from the StellarWP Partner Gateway.
 */
class SetupInstructions extends BaseSetupInstructions
{
    /**
     * Retrieve setup instructions from the Partner Gateway.
     *
     * @return array{temp_files: Array<mixed>, wp_cli: Array<mixed>} The body of the setup API response.
     */
    protected function fetchInstructionsFromGateway()
    {
        $instructions = [
            'temp_files' => [],
            'wp_cli'     => [
                'theme install astra',
                'plugin install astra-sites code-snippets fluentform ' .
                    '/usr/local/plugins/wordpress/5/kadence-starter-templates.zip',
                'plugin install --activate cache-enabler',
                'learndash-cloud extension install --license learndash kadence ithemes-security-pro',

                // Ensure that any plugins we just installed are up-to-date.
                'plugin update --all',

                // Apply default iThemes Security Pro settings.
                sprintf(
                    'itsec import-export import %s%s',
                    \StellarWP\LearnDashCloud\PLUGIN_PATH,
                    '/assets/snippets/ithemes-security-pro-default-config.json'
                ),

                // Enable automatic updates through WordPress for plugins and themes.
                'plugin auto-updates enable --all',
                'theme auto-updates enable --all',
            ],
        ];

        return $instructions;
    }
}

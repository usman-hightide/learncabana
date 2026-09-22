<?php

/**
 * Plugin Name: LearnDash Cloud
 * Plugin URI:  https://www.learndash.com/
 * Description: Support for running LearnDash in a managed WordPress environment.
 * Version:     1.0.18
 * Author:      LearnDash
 * Author URI:  https://www.learndash.com/
 * Text Domain: learndash-cloud
 */

namespace StellarWP\LearnDashCloud;

use StellarWP\PluginFramework\Exceptions\StellarWPException;
use StellarWP\PluginFramework\Services\Logger;

// Define a few constants to help with pathing.
define('StellarWP\LearnDashCloud\PLUGIN_VERSION', '1.0.18');
define('StellarWP\LearnDashCloud\PLUGIN_URL', plugins_url('/learndash-cloud/', __FILE__));
define('StellarWP\LearnDashCloud\PLUGIN_PATH', plugin_dir_path(__FILE__) . '/learndash-cloud/');
define('StellarWP\LearnDashCloud\VENDOR_DIR', __DIR__ . '/learndash-cloud/vendor/');

require_once VENDOR_DIR . 'autoload.php';

// Initialize the plugin.
try {
    /** @var Plugin $plugin */
    $plugin = Container::getInstance()
        ->get(Plugin::class);
    $plugin->init();
} catch (\Exception $e) {
    $message = $e instanceof StellarWPException
        ? 'The LearnDash Cloud plugin generated an error: %s'
        : 'The LearnDash Cloud plugin caught the following error: %s';

    /** @var Logger $logger */
    $logger = Container::getInstance()
        ->get(Logger::class);

    $logger->error(sprintf($message, $e->getMessage()), [
        'exception' => $e,
    ]);
}

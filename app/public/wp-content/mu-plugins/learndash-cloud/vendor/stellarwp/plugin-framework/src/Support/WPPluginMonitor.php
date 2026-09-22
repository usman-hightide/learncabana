<?php

namespace StellarWP\PluginFramework\Support;

use InvalidArgumentException;

class WPPluginMonitor
{
    /**
     * Retrieves the count of plugins based on the specified type.
     *
     * This method returns the count of all plugins, active plugins, or plugins
     * with updates available depending on the type parameter.
     *
     * @param string $type The type of plugin count to retrieve ('all', 'active', 'updates').
     *
     * @return int The count of plugins based on the specified type.
     *
     * @throws InvalidArgumentException If an invalid type is specified.
     */
    public function getPluginsCount(string $type)
    {
        switch ($type) {
            case 'all':
                return count(get_plugins());
            case 'active':
                return count($this->getActivePluginsOption());
            case 'updates':
                return count($this->getUpdatePluginsResponse());
            default:
                throw new InvalidArgumentException('Invalid type specified.');
        }
    }

    /**
     * Retrieves the list of active plugins from the WordPress options table.
     *
     * This method fetches the 'active_plugins' option, which contains an array
     * of active plugin file paths. If the option does not exist or is not an array,
     * an empty array is returned.
     *
     * @return string[]
     */
    protected function getActivePluginsOption()
    {
        $active_plugins = get_option('active_plugins');
        if (! is_array($active_plugins)) {
            return [];
        }

        return array_filter($active_plugins, function ($item) {
            return is_string($item);
        });
    }

    /**
     * Retrieves the response data for plugins with updates available.
     *
     * This method fetches the 'update_plugins' site transient, which contains
     * an object with a 'response' property. This property is an array of plugin
     * update data. If the transient does not exist, is not an object, or the
     * 'response' property is not set or not an array, an empty array is returned.
     *
     * @return array<mixed> An array of plugin update data.
     */
    protected function getUpdatePluginsResponse()
    {
        $update_plugins = get_site_transient('update_plugins');
        if (is_object($update_plugins) && ! empty($update_plugins->response) && is_array($update_plugins->response)) {
            return $update_plugins->response;
        }

        return [];
    }
}

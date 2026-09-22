<?php

namespace StellarWP\PluginFramework\Support;

class WPPackages
{
    /**
     * Extracts the version number from the package URI.
     *
     * @param string $package Package URI.
     *
     * @return string The extracted version or empty if not found.
     */
    public static function extractVersionFromPackage($package)
    {
        if (! preg_match('/wordpress-([\d\.]+)(?:-.+)?\.zip/', $package, $matches) || empty($matches[1])) {
            return '';
        }

        return $matches[1];
    }
}

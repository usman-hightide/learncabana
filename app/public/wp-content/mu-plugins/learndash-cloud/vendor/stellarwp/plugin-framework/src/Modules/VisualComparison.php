<?php

/**
 * Controls for VisualComparison.
 */

namespace StellarWP\PluginFramework\Modules;

use StellarWP\PluginFramework\Contracts\ProvidesSettings;
use StellarWP\PluginFramework\Exceptions\InvalidUrlException;
use StellarWP\PluginFramework\Services\Logger;
use StellarWP\PluginFramework\Support\PostUrlRetrieval;
use StellarWP\PluginFramework\Support\TaxonomyUrlRetrieval;
use StellarWP\PluginFramework\Support\UrlRetrievalContext;
use StellarWP\PluginFramework\Support\VisualRegressionUrl;

class VisualComparison extends Module
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ProvidesSettings
     */
    protected $settings;

    /**
     * The settings group.
     */
    const SETTINGS_GROUP = 'stellarwp_visual_comparison';

    /**
     * The option name used to store custom URLs.
     */
    const SETTING_NAME = 'stellarwp_visual_regression_urls';

    /**
     * The maximum number of URLs permitted per site.
     */
    const MAXIMUM_URLS = 5;

    /**
     * @var UrlRetrievalContext
     */
    protected $urlRetrieval;

    /**
     * @param ProvidesSettings $settings
     * @param Logger           $logger
     */
    public function __construct(ProvidesSettings $settings, Logger $logger)
    {
        $this->settings     = $settings;
        $this->logger       = $logger;
        $this->urlRetrieval = new UrlRetrievalContext();
    }

    /**
     * Perform any necessary setup for the integration.
     *
     * This method is automatically called as part of Plugin::loadIntegration(), and is the
     * entry-point for all integrations.
     */
    public function setup()
    {
        add_filter('option_' . static::SETTING_NAME, [ $this, 'expandOptionValue' ]);
    }

    /**
     * Automatically expand the contents of self::SETTING_NAME to an array of
     * VisualRegressionUrl objects.
     *
     * @param array<mixed> $value The option value.
     *
     * @return array<VisualRegressionUrl> An array of regression URLs.
     */
    public function expandOptionValue($value)
    {
        if (! is_array($value)) {
            $value = json_decode($value, true) ?: [];
        }

        $values = array_map(function ($entry) {
            if (! is_array($entry)) {
                return '';
            }

            $path = ! empty($entry['path']) ? $entry['path'] : false;

            if ($path) {
                $path = new VisualRegressionUrl(
                    strval($path),
                    ! empty($entry['description']) ? strval($entry['description']) : ''
                );
            }

            return $path;
        }, (array) $value);

        return array_values(array_filter($values));
    }

    /**
     * Retrieve the URLs that should be checked during visual comparison.
     *
     * @return VisualRegressionUrl[]
     */
    public function getUrls()
    {
        $urls = get_option(static::SETTING_NAME, false);

        // Only if the option isn't set do we want to generate the default urls.
        if (empty($urls)) {
            $urls = (array) $this->getDefaultUrls();
        }

        if (static::MAXIMUM_URLS < count($urls)) { /** @phpstan-ignore-line */
            $this->logger->warning(sprintf(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Visual regression testing is currently limited to %1$d URLs, but %2$s were provided. Only the first %1$d will be processed.',
                static::MAXIMUM_URLS,
                count($urls) /** @phpstan-ignore-line */
            ));
        }

        /** @var VisualRegressionUrl[] */
        return $urls;
    }

    /**
     * Resolves path trailing slashes based on permalink_structure option for url array.
     *
     * @param string[] $urls
     *
     * @return string[]
     */
    public function resolveTrailingSlashes($urls)
    {
        $link_structure = strval(get_option('permalink_structure')) ?: '';
        if (! $urls || ! $link_structure) {
            return $urls;
        }

        $ends_with_slash = '/' === mb_substr($link_structure, -1);

        $resolved = [];
        foreach ($urls as $url) {
            $query = '';
            if (false !== mb_strpos($url, '?')) {
                $parsed_url = wp_parse_url($url);
                $url        = ! empty($parsed_url['path']) ? $parsed_url['path'] : $url;
                $query      = ! empty($parsed_url['query']) ? $parsed_url['query'] : $query;
            }

            $url = untrailingslashit($url);

            if (! $url || $ends_with_slash) {
                $url .= '/';
            }

            $resolved[] = $url . ($query ? '?' . $query : '');
        }

        return $resolved;
    }

    /**
     * Get the default URLs to check during visual comparison.
     *
     * @return array<mixed>
     */
    protected function getDefaultUrls()
    {
        if (! function_exists('is_plugin_active')) {
            // @phpstan-ignore-next-line
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $urls = [
            new VisualRegressionUrl('/', 'Homepage'),
        ];

        $woo_active = is_plugin_active('woocommerce/woocommerce.php');

        // If the site has a static front page, and there is no woocommerce enabled explicitly grab its page_for_posts.
        if (! $woo_active) {
            if ('page' === get_option('show_on_front')) {
                $urls[] = new VisualRegressionUrl(
                    get_permalink(intval(get_option('page_for_posts', ''))) ?: '',
                    'Page for posts'
                );
            }

            $urls = array_merge(
                $urls,
                $this->getDefaultVisualComparisonUrls()
            );
        }

        if ($woo_active) {
            $urls = array_merge($urls, $this->getDefaultWooCommerceUrls(), $this->getOldestProductPage());
        }

        // Limit the defaults to the maximum number of URLs.
        $urls = array_slice($urls, 0, static::MAXIMUM_URLS);

        /**
         * Filter the default URLs provided to the Visual Comparison tool.
         *
         * @param array<VisualRegressionUrl> $urls An array of VisualComparisonUrl objects to be checked.
         */
        return (array) apply_filters('stellarwp_default_visual_regression_urls', $urls);
    }

    /**
     * Get the default WooCommerce-specific URLs to check during visual comparison.
     *
     * @return array<VisualRegressionUrl>
     */
    protected function getDefaultWooCommerceUrls()
    {
        $pages = [
            'woocommerce_shop_page_id'      => 'Shop',
            'woocommerce_cart_page_id'      => 'Cart',
            'woocommerce_checkout_page_id'  => 'Checkout'
        ];
        $urls  = [];

        foreach ($pages as $option => $name) {
            try {
                $page_id = intval(get_option($option, false));

                if (! $page_id) {
                    continue;
                }

                $urls[] = new VisualRegressionUrl(get_permalink($page_id) ?: '', $name);
            } catch (InvalidUrlException $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Skip over the URL.
            }
        }

        return $urls;
    }

    /**
     * Gets one oldest post, page and one category visual regression URLS.
     *
     * @return VisualRegressionUrl[]
     */
    protected function getDefaultVisualComparisonUrls()
    {
        $this->urlRetrieval->setStrategy(new PostUrlRetrieval());

        $urls = array_merge(
            $this->urlRetrieval->getUrls('post', 1, 'Oldest Post'),
            $this->urlRetrieval->getUrls('page', 1, 'Oldest Page')
        );

        $this->urlRetrieval->setStrategy(new TaxonomyUrlRetrieval());

        return array_merge(
            $urls,
            $this->urlRetrieval->getUrls('category', 1, 'Oldest Category')
        );
    }

    /**
     * @return VisualRegressionUrl[]
     */
    protected function getOldestProductPage()
    {
        $this->urlRetrieval->setStrategy(new PostUrlRetrieval());

        return $this->urlRetrieval->getUrls('product', 1, 'Oldest Product', [
            'meta_query' => [
                [
                    'key'   => '_stock_status',
                    'value' => 'instock',
                ],
            ]
        ]);
    }
}

<?php

namespace StellarWP\LearnDashCloud\Modules;

use StellarWP\PluginFramework\Modules\Module;
use StellarWP\PluginFramework\Support\VisualRegressionUrl;

class VisualComparison extends Module
{
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
        add_filter('stellarwp_default_visual_regression_urls', [ $this, 'defaultVisualRegressionUrls' ], 10000);
    }

    /**
     * Add LD URLs to the default Visual Comparison URLs.
     *
     * @param array<mixed> $urls Default URLS.
     *
     * @return array<mixed>
     */
    public function defaultVisualRegressionUrls($urls)
    {
        // Courses archive page (/courses).
        $archive_link = get_post_type_archive_link('sfwd-courses');
        if (! empty($archive_link)) {
            $urls[] = new VisualRegressionUrl(
                $archive_link,
                'Courses'
            );
        }

        return $urls;
    }
}

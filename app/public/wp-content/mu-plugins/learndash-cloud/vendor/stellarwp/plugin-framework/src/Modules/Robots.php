<?php

/**
 * Functionality related to Branding.
 */

namespace StellarWP\PluginFramework\Modules;

use StellarWP\PluginFramework\Contracts\ProvidesSettings;

class Robots extends Module
{
    /**
     * @var array<string> $blockList Default list of bots to block.
     */
    protected $blockList = [];

    /**
     * @var ProvidesSettings $settings
     */
    protected $settings;

    /**
     * @var array<string, int> $throttleList Default list of bots to throttle and throttle time in seconds.
     */
    protected $throttleList = [
        'facebookexternalhit' => 60
    ];

    /**
     * @param \StellarWP\PluginFramework\Contracts\ProvidesSettings $settings
     */
    public function __construct(ProvidesSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function setup()
    {
        $this->maybeBlockBots();
        $this->maybeThrottleBots();

        add_filter('robots_txt', [ $this, 'maybeDisallowAll' ], 99);
    }

    /**
     * Disallow bots via the robots.txt when the site is not on a custom domain (non-production).
     *
     * @param  string $output Current robots.txt content.
     *
     * @return string         Modified robots.txt content.
     */
    public function maybeDisallowAll($output = '')
    {
        if (! $this->settings->has_custom_domain && apply_filters('stellarwp_robots_block_non_production', true)) {
            $output = 'User-agent: *' . PHP_EOL;
            $output .= 'Disallow: /*' . PHP_EOL;
        }
        return $output;
    }

    /**
     * Block bots in the block list.
     *
     * @return void
     */
    private function maybeBlockBots()
    {
        $blockedBots = apply_filters('stellarwp_robots_bots_blocklist', $this->blockList);

        if (empty($blockedBots)) {
            return;
        }

        $bot = $this->getBot();
        foreach ($blockedBots as $blockedBot) {
            if (false !== mb_strpos($bot, $blockedBot)) {
                $this->response(403, 'Forbidden');
            }
        }
    }

    /**
     * Throttle bots in the throttle list.
     *
     * @return void
     */
    private function maybeThrottleBots()
    {
        $throttledBots = apply_filters('stellarwp_robots_bots_throttlelist', $this->throttleList);

        if (empty($throttledBots)) {
            return;
        }

        $bot = $this->getBot();

        foreach ($throttledBots as $throttledBot => $limit) {
            if (false !== mb_strpos($bot, $throttledBot)) {
                $lastAccess = get_transient("bot_{$bot}_last_access_time");

                if (empty($lastAccess)) { // Allow the visit.
                    set_transient("bot_{$bot}_last_access_time", time(), $limit);
                } else { // Already accessed within the given time limit.
                    $this->response(503, 'Service Temporarily Unavailable');
                }
            }
        }
    }

    /**
     * Get the bot user agent.
     *
     * @return string User agent.
     */
    private function getBot()
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        return ! empty($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) // @phpstan-ignore-line
            : '';
    }

    /**
     * Sets the HTTP response code and echoes a message.
     *
     * @param  int    $code    HTTP response code.
     * @param  string $message Message.
     *
     * @return void
     */
    public function response($code, $message)
    {
        http_response_code($code);
        echo esc_html($message);
        die;
    }
}

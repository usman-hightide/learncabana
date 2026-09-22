<?php

/**
 * Functionality related to Branding.
 */

namespace StellarWP\PluginFramework\Modules;

use StellarWP\PluginFramework\Concerns\RendersTemplates;
use StellarWP\PluginFramework\Contracts\ProvidesSettings;
use StellarWP\PluginFramework\Support\Branding;

abstract class Dashboard extends Module
{
    use RendersTemplates;

    const TEMPLATES_DIR = '';

    /**
     * @var ProvidesSettings $settings
     */
    protected $settings;

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
        if (! empty(static::TEMPLATES_DIR)) {
            $this->setTemplateDirectories([
                static::TEMPLATES_DIR,
            ]);
        }

        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'saveSettings']);
    }

    /**
     * Callback for adding the wp-admin menu page.
     *
     * @return void
     */
    public function addMenuPage()
    {
        $title = Branding::getDashboardPageTitle();

        $hook = add_menu_page(
            $title . ' Admin Settings',
            $title,
            apply_filters('stellarsites_admin_menu_caps', 'manage_options'),
            'stellarsites',
            [ $this, 'renderMainSettingsPage' ],
            $this->settings->icon_url,
            3,
        );

        add_action("load-{$hook}", [$this, 'loadPage']);
    }

    /**
     * Executes when our admin page loads.
     *
     * @return void Description
     */
    public function loadPage()
    {
        // Do nothing right now. If needed, overwrite this method in extending classes.
    }

    /**
     * Process and save settings.
     *
     * @return void Description
     */
    public function saveSettings()
    {
        // Do nothing right now. If needed, overwrite this method in extending classes.
    }

    /**
     * Callback for rendering the menu page's content.
     * This needs implemented by any class that extends this class.
     *
     * @return void
     */
    abstract public function renderMainSettingsPage();
}

<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wpmessiah.com
 * @since      1.0.0
 *
 * @package    Ai_Content_Helper
 * @subpackage Ai_Content_Helper/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ai_Content_Helper
 * @subpackage Ai_Content_Helper/includes
 * @author     WP Messiah <contact@wpmessiah.com>
 */
class Ai_Content_Helper_Activator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        $models = [
            "gpt-3.5-turbo-16k-0613",
            "gpt-3.5-turbo-16k",
            "gpt-3.5-turbo",
            "gpt-3.5-turbo-0301",
            "gpt-4-0613",
            "gpt-4",
            "gpt-4-0314",
            "gpt-3.5-turbo-0613"
        ];

        if (get_option('wp_ai_pilot_models') !== false) {
            update_option('wp_ai_pilot_models', $models);
        } else {
            add_option('wp_ai_pilot_models', $models);
        }

        update_option("permalink_structure", "/%postname%/");
    }
}

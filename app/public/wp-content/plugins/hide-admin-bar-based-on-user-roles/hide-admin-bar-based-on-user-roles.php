<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://pluginstack.dev
 * @since             1.0.0
 * @package           Hide_Admin_Bar_Based_On_User_Roles
 *
 *
 * @wordpress-plugin
 * Plugin Name:       Hide Admin Bar Based on User Roles
 * Plugin URI:        https://wordpress.org/plugins/hide-admin-bar-based-on-user-roles/
 * Description:       Hide the WordPress Admin Bar for specific user roles, capabilities, devices, pages, or time windows. Lightweight and works out of the box.
 * Version:           7.2.4
 * Author:            PluginStackDev
 * Author URI:        https://pluginstack.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hide-admin-bar-based-on-user-roles
 * Domain Path:       /languages
 */
/*
Hide Admin Bar Based on User Roles is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Hide Admin Bar Based on User Roles is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Hide Admin Bar Based on User Roles. If not, see {URI to Plugin License}.
*/
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
/**
 * Currently plugin version.
 * Start at version 1.7.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
// Use the existing version constant if the free plugin defined it first.
if ( !defined( 'HIDE_ADMIN_BAR_BASED_ON_USER_ROLES' ) ) {
    define( 'HIDE_ADMIN_BAR_BASED_ON_USER_ROLES', '7.2.4' );
}
if ( !function_exists( 'habbourp_fs' ) ) {
    // Create a helper function for easy SDK access.
    function habbourp_fs() {
        global $habbourp_fs;
        if ( !isset( $habbourp_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_18739_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_18739_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/includes/freemius/start.php';
            $habbourp_fs = fs_dynamic_init( array(
                'id'               => '18739',
                'slug'             => 'hide-admin-bar-based-on-user-roles',
                'premium_slug'     => 'hide-admin-bar-based-on-user-roles-pro',
                'type'             => 'plugin',
                'public_key'       => 'pk_86e4f935219abb51f4bba4983e178',
                'is_premium'       => false,
                'premium_suffix'   => 'Pro',
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'has_affiliation'  => 'selected',
                'menu'             => array(
                    'slug'    => 'hide-admin-bar-settings',
                    'support' => false,
                    'network' => true,
                    'parent'  => array(
                        'slug' => 'options-general.php',
                    ),
                ),
                'is_live'          => true,
                'is_org_compliant' => true,
            ) );
        }
        return $habbourp_fs;
    }

    // Init Freemius.
    habbourp_fs();
    // Signal that SDK was initiated.
    do_action( 'habbourp_fs_loaded' );
    if ( !defined( 'HAB_PRO_VERSION' ) ) {
        if ( function_exists( 'habbourp_fs' ) && habbourp_fs()->can_use_premium_code() ) {
            define( 'HAB_PRO_VERSION', true );
        }
    }
    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-hide-admin-bar-based-on-user-roles-activator.php
     */
    function hab_activate_hide_admin_bar_based_on_user_roles() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-hide-admin-bar-based-on-user-roles-activator.php';
        hab_Hide_Admin_Bar_Based_On_User_Roles_Activator::activate();
    }

    /**
     * The code that runs during plugin deactivation.
     * This action is documented in includes/class-hide-admin-bar-based-on-user-roles-deactivator.php
     */
    function hab_deactivate_hide_admin_bar_based_on_user_roles() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-hide-admin-bar-based-on-user-roles-deactivator.php';
        hab_Hide_Admin_Bar_Based_On_User_Roles_Deactivator::deactivate();
    }

    register_activation_hook( __FILE__, 'hab_activate_hide_admin_bar_based_on_user_roles' );
    register_deactivation_hook( __FILE__, 'hab_deactivate_hide_admin_bar_based_on_user_roles' );
    /**
     * The core plugin class that is used to define internationalization,
     * admin-specific hooks, and public-facing site hooks.
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-hide-admin-bar-based-on-user-roles.php';
    // If Pro is active and the loader exists, load Pro loader
    if ( function_exists( 'habbourp_fs' ) && habbourp_fs()->can_use_premium_code() ) {
        $pro_loader = plugin_dir_path( __FILE__ ) . 'pro/class-pro-loader.php';
        if ( file_exists( $pro_loader ) ) {
            require_once $pro_loader;
        }
    }
    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.7.0
     */
    function hab_run_hide_admin_bar_based_on_user_roles() {
        $plugin = new hab_Hide_Admin_Bar_Based_On_User_Roles();
        $plugin->run();
    }

    add_action( 'plugins_loaded', 'hab_run_hide_admin_bar_based_on_user_roles' );
    // Function to handle promotional banner dismissal
    function hab_dismiss_promotional_banner() {
        check_ajax_referer( 'hab_dismiss_promo_nonce', 'nonce' );
        $dismiss_type = ( isset( $_POST['dismiss_type'] ) ? sanitize_text_field( $_POST['dismiss_type'] ) : '' );
        $user_id = get_current_user_id();
        if ( $dismiss_type === 'permanent' ) {
            update_user_meta( $user_id, 'hab_hide_promo_banner', 'permanent' );
        } elseif ( $dismiss_type === '30days' ) {
            $hide_until = time() + 30 * 24 * 60 * 60;
            // 30 days from now
            update_user_meta( $user_id, 'hab_hide_promo_until', $hide_until );
        } else {
            // For 'now' option, just update the timestamp to current time
            update_user_meta( $user_id, 'hab_hide_promo_until', time() );
        }
        wp_send_json_success();
    }

    add_action( 'wp_ajax_hab_dismiss_promotional_banner', 'hab_dismiss_promotional_banner' );
    // Function to store the dismissed state using AJAX
    function custom_advertisement_dismiss_habou() {
        update_user_meta( get_current_user_id(), 'dismiss_custom_ad_habou', true );
    }

    add_action( 'wp_ajax_custom_advertisement_dismiss_habou', 'custom_advertisement_dismiss_habou' );
    // Enqueue the script to handle the dismiss action via AJAX
    function custom_advertisement_enqueue_script_habou() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // When the dismiss button is clicked, trigger the AJAX call
                $(document).on('click', '.notice.is-dismissible', function() {
                    var adBar = $(this).attr('id');
                    if (adBar === 'custom-advertisement-bar-dbefm') {
                        $.post(ajaxurl, {
                            action: 'custom_advertisement_dismiss_habou'
                        });
                    }
                });
            });
        </script>
        <?php 
    }

    add_action( 'admin_footer', 'custom_advertisement_enqueue_script_habou' );
    // Not like register_uninstall_hook(), you do NOT have to use a static function.
    habbourp_fs()->add_action( 'after_uninstall', 'habbourp_fs_uninstall_cleanup' );
    function habbourp_fs_uninstall_cleanup() {
        // Delete individual site settings
        delete_option( 'hab_settings' );
        delete_option( 'hab_reset_key' );
    }

}
/* Show a small promotional notice for PluginStack bundle. */
if ( !function_exists( 'hab_pluginstack_promo_notice' ) ) {
    function hab_pluginstack_promo_notice() {
        $dismissed = get_option( 'hab_pluginstack_promo_dismissed' );
        if ( $dismissed ) {
            return;
        }
        ?>
		<div class="notice hab-promo-notice" style="border-left-color:#6c47ff;padding:8px 12px;display:flex;align-items:center;gap:10px;">
			<span style="font-size:18px;">⚡</span>
			<p style="margin:0;font-size:13px;">
				<strong>Enjoying this plugin?</strong> Get the <a href="https://pluginstack.dev/?utm_source=hide-admin-bar&utm_medium=admin_notice&utm_campaign=pluginstack_bundle" target="_blank" rel="noopener noreferrer" style="color:#6c47ff;font-weight:600;">PluginStack Bundle</a> — AI, WooCommerce, Gravity Forms, Analytics &amp; more. All current + upcoming plugins. <strong>One-time payment, no subscription.</strong>
				<a href="<?php 
        echo esc_url( wp_nonce_url( add_query_arg( 'hab_dismiss_promo', '1' ), 'hab_dismiss_promo' ) );
        ?>" style="margin-left:10px;color:#999;font-size:12px;text-decoration:none;"><?php 
        esc_html_e( 'Dismiss', 'hide-admin-bar-based-on-user-roles' );
        ?></a>
			</p>
		</div>
		<?php 
    }

    add_action( 'admin_notices', 'hab_pluginstack_promo_notice' );
}
/* Handle dismiss action for PluginStack promo notice. */
if ( !function_exists( 'hab_handle_promo_dismiss' ) ) {
    function hab_handle_promo_dismiss() {
        if ( isset( $_GET['hab_dismiss_promo'] ) && check_admin_referer( 'hab_dismiss_promo' ) ) {
            update_option( 'hab_pluginstack_promo_dismissed', true );
            wp_safe_redirect( remove_query_arg( array('hab_dismiss_promo', '_wpnonce') ) );
            exit;
        }
    }

    add_action( 'admin_init', 'hab_handle_promo_dismiss' );
}
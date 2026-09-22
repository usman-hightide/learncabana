<?php
/**
 * Initialize the TrustedLogin Connector plugin.
 *
 * @package TrustedLogin\Connector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TrustedLogin\Vendor\Helpers;
use TrustedLogin\Vendor\Status\Onboarding;
use TrustedLogin\Vendor\MaybeRedirect;
use TrustedLogin\Vendor\MenuPage;
use TrustedLogin\Vendor\SettingsApi;

add_action( 'init', 'trustedlogin_connector_initialize' );

/**
 * Initialize the TrustedLogin Connector plugin.
 *
 * @since 2.0.0
 */
function trustedlogin_connector_initialize() {

	// Add main menu page.
	new MenuPage(
		// Do not pass args, would make it a child page.
	);

	$has_onboarded = Onboarding::hasOnboarded();

	/**
	 *  Add (sub)menu pages.
	 */
	if ( $has_onboarded ) {
		// Add settings submenu page.
		new MenuPage(
			MenuPage::SLUG_SETTINGS,
			__( 'Settings', 'trustedlogin-connector' ),
			'settings',
			false
		);

		// Add access key submenu page.
		new MenuPage(
			MenuPage::SLUG_TEAMS,
			__( 'Teams', 'trustedlogin-connector' ),
			'teams',
			false
		);

		// Help desks is no longer a standalone submenu — the
		// IntegrationSettings component is now rendered as a section
		// inside Settings (see GeneralSettings.js). The legacy slug
		// continues to resolve via MaybeRedirect::handle_legacy_helpdesks
		// so bookmarks and docs links don't 404.

		// Secrets submenu page.
		new MenuPage(
			MenuPage::SLUG_SECRETS,
			__( 'Secrets', 'trustedlogin-connector' ),
			'secrets',
			false
		);

		// Login Activity submenu page.
		new MenuPage(
			MenuPage::SLUG_ACTIVITY,
			__( 'Login Activity', 'trustedlogin-connector' ),
			'activity',
			false
		);

		// Permissions submenu — first-class page now, not buried inside
		// Settings. Keeps the admin's "set up role access" path one
		// click away from the top-level TrustedLogin menu.
		new MenuPage(
			MenuPage::SLUG_PERMISSIONS,
			__( 'Permissions', 'trustedlogin-connector' ),
			'permissions',
			false
		);

		$has_connected_team = SettingsApi::fromSaved()->hasConnectedTeam();

		if ( $has_connected_team ) {
			// Add access key submenu page.
			new MenuPage(
				MenuPage::SLUG_ACCESS_KEY,
				__( 'Access Key Log-In', 'trustedlogin-connector' ),
				'teams/access_key',
				true
			);
		}
	} else {
		// Add onboarding submenu page.
		new MenuPage(
			MenuPage::SLUG_SETTINGS,
			__( 'Onboarding', 'trustedlogin-connector' ),
			'onboarding',
			false
		);
	}

	// The parent menu slug ('trustedlogin') is distinct from SLUG_SETTINGS
	// so Settings stays reachable regardless of default_landing_page. WP
	// still auto-adds a submenu entry mirroring the parent — hide it.
	//
	// WP derives the parent-menu link from the FIRST remaining submenu
	// (after remove_submenu_page runs). That means clicking the top-level
	// "TrustedLogin" item always goes to whichever submenu was registered
	// first — historically `trustedlogin-settings`. To make the
	// `default_landing_page` setting actually take effect, move the
	// configured target submenu to the top of the list so WP uses its
	// slug as the parent link.
	add_action(
		'admin_menu',
		static function () {
			remove_submenu_page( MenuPage::PARENT_MENU_SLUG, MenuPage::PARENT_MENU_SLUG );

			global $submenu;
			$parent = MenuPage::PARENT_MENU_SLUG;
			if ( ! isset( $submenu[ $parent ] ) || empty( $submenu[ $parent ] ) ) {
				return;
			}

			// Compute the CURRENT user's best-accessible landing (honours
			// default_landing_page when reachable, otherwise falls through
			// MenuPage::LANDING_PRIORITY). For a support-role user who
			// only holds ACCESS_KEY_LOGIN, this returns Access Key Log-In
			// regardless of what the site admin picked as the default.
			$target_slug = MenuPage::user_default_slug();
			if ( null === $target_slug ) {
				return;
			}

			// Find the target submenu entry and, if it isn't already first,
			// move it to index 0. Each entry is a numerically-indexed array
			// where [2] is the page slug. WP uses $submenu[$parent][0]'s
			// slug as the top-level menu's href, so putting the accessible
			// target first makes the TrustedLogin menu link go to the
			// right place for this user.
			foreach ( $submenu[ $parent ] as $index => $entry ) {
				if ( isset( $entry[2] ) && $entry[2] === $target_slug ) {
					if ( 0 === $index ) {
						return;
					}
					$moved = $entry;
					unset( $submenu[ $parent ][ $index ] );
					array_unshift( $submenu[ $parent ], $moved );
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- intentional: re-indexing $submenu is the documented WP way to reorder admin submenus.
					$submenu[ $parent ] = array_values( $submenu[ $parent ] );
					return;
				}
			}
		},
		999
	);
}

// Register assets for TrustedLogin Settings.
add_action( 'admin_enqueue_scripts', 'trustedlogin_connector_register_assets' );

/**
 * Register assets for TrustedLogin Settings.
 *
 * @since 2.0.0
 */
function trustedlogin_connector_register_assets() {

	// Bail early on non-TrustedLogin admin screens so we don't load the
	// media stack + SPA bundles on every admin page. Checks the current
	// screen's ID against the plugin's top-level slug.
	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( $screen && false === strpos( (string) $screen->id, MenuPage::PARENT_MENU_SLUG ) ) {
			return;
		}
	}

	$admin_page_file_path = '/wpbuild/admin-page-trustedlogin-settings.asset.php';

	/**
	 * Register assets
	 */
	// This needs to be done once, not once per menu.
	if ( ! is_readable( dirname( __DIR__, 2 ) . $admin_page_file_path ) ) {
		return;
	}

	$assets       = include dirname( __DIR__, 2 ) . $admin_page_file_path;
	$js_url       = plugins_url( '/wpbuild/admin-page-trustedlogin-settings.js', dirname( __DIR__, 1 ) );
	$css_url      = plugins_url( '/trustedlogin-dist.css', __DIR__ );
	$dependencies = $assets['dependencies'];

	wp_register_script(
		MenuPage::ASSET_HANDLE,
		$js_url,
		$dependencies,
		$assets['version'],
		false
	);
	$settings_api = SettingsApi::fromSaved();
	$data         = trustedlogin_connector_prepare_data( $settings_api );

	$error = Helpers::get_post_or_get( 'error', 'sanitize_text_field' );
	if ( ! empty( $error ) ) {
		// Allowlist: only render banners for slugs MaybeRedirect actually emits.
		$message = '';
		switch ( $error ) {
			case MaybeRedirect::EXTERNAL_SLUG_NONCE:
				$message = __( 'Your link has expired. Please try again.', 'trustedlogin-connector' );
				break;
			case MaybeRedirect::EXTERNAL_SLUG_INVALID_REQUEST:
				$message = __( 'We couldn’t complete the access-key login. Double-check the access key and account ID, then try again.', 'trustedlogin-connector' );
				break;
		}
		if ( '' !== $message ) {
			$data['errorMessage'] = $message;
		}
	}

	wp_localize_script( MenuPage::ASSET_HANDLE, 'tlVendor', $data );

	$css_path    = dirname( __DIR__, 1 ) . '/trustedlogin-dist.css';
	$css_version = is_readable( $css_path ) ? md5_file( $css_path ) : TRUSTEDLOGIN_PLUGIN_VERSION;

	wp_register_style(
		MenuPage::ASSET_HANDLE,
		$css_url,
		array(),
		$css_version
	);
}

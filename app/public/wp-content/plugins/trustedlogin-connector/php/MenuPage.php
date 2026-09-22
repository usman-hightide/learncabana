<?php
/**
 * MenuPage implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\VerifyUser;
use TrustedLogin\Vendor\Capabilities;
use TrustedLogin\Vendor\Status\Onboarding;

/**
 * Create a menu page.
 *
 * Creates parent and child pages.
 */
class MenuPage {


	use VerifyUser;

	/**
	 * Distinct from SLUG_SETTINGS so the Settings submenu is still reachable
	 * even when `default_landing_page` is set to a non-settings sub-page.
	 * Bare visits to `?page=trustedlogin` redirect to the configured landing
	 * page; `?page=trustedlogin-settings` always loads Settings directly.
	 */
	const PARENT_MENU_SLUG = 'trustedlogin';

	const SLUG_ACCESS_KEY = 'trustedlogin_access_key_login';

	const SLUG_TEAMS = 'trustedlogin-teams';

	const SLUG_SETTINGS = 'trustedlogin-settings';

	const SLUG_HELPDESKS = 'trustedlogin-helpdesks';

	const SLUG_SECRETS = 'trustedlogin-secrets';

	const SLUG_ACTIVITY = 'trustedlogin-activity';

	const SLUG_PERMISSIONS = 'trustedlogin-permissions';

	/**
	 * Map of allowed values for the `default_landing_page` global setting →
	 * the menu slug to redirect to. Kept as a class constant so the
	 * redirect handler, settings endpoint validator, and React dropdown all
	 * share one source of truth.
	 *
	 * @since 2.0.0
	 */
	const DEFAULT_LANDING_PAGES = array(
		'settings'         => self::SLUG_SETTINGS,
		'teams'            => self::SLUG_TEAMS,
		'secrets'          => self::SLUG_SECRETS,
		'activity'         => self::SLUG_ACTIVITY,
		'access_key_login' => self::SLUG_ACCESS_KEY,
		'permissions'      => self::SLUG_PERMISSIONS,
	);

	/**
	 * Per-submenu capability overrides. Any submenu slug not in this map
	 * falls back to `manage_options`. Kept in sync with the REST
	 * permission_callbacks so a user who can hit the REST endpoint can
	 * also load its UI (and vice versa); the previous divergence —
	 * Activity submenu on manage_options, REST on VIEW_ACTIVITY — meant
	 * a user with one cap but not the other either saw an empty SPA or
	 * had API access without a UI gate.
	 *
	 * @since 2.0.0
	 */
	/**
	 * Per-submenu capability gates. Each value is either a single cap
	 * slug (user needs that cap) or an array of cap slugs (user needs
	 * ANY ONE of them — logical OR, not AND).
	 *
	 * The "OR" form is used where a page has multiple meaningful
	 * sections that each gate on their own cap — the page itself opens
	 * for users with any relevant cap, and the page's in-React code
	 * gates sections individually. Secrets is the canonical case: a
	 * CREATE_SECRET-only user sees the Create form; a MANAGE_SECRETS-only
	 * user sees the list; a user with both sees both.
	 *
	 * All caps referenced here are primitive caps shown in the
	 * Permissions matrix — there is no hidden cap, so an admin
	 * configuring permissions can always see exactly what they're
	 * granting.
	 */
	const SUBMENU_CAPABILITIES = array(
		self::SLUG_ACTIVITY    => Capabilities::VIEW_ACTIVITY,
		self::SLUG_SECRETS     => array(
			Capabilities::CREATE_SECRET,
			Capabilities::MANAGE_SECRETS,
		),
		// Team configs hold the SaaS keypair, webhook secrets, and
		// approved-roles list — anyone who can edit them can effectively
		// impersonate the team to clients. `manage_options` matches the
		// realistic "WP admin owns the integration" deployment model.
		self::SLUG_TEAMS       => 'manage_options',
		self::SLUG_ACCESS_KEY  => Capabilities::ACCESS_KEY_LOGIN,
		// Permissions is the most sensitive admin-config page; mirror
		// the REST endpoint's manage_options gate instead of opening
		// it to any TL cap holder.
		self::SLUG_PERMISSIONS => 'manage_options',
	);

	/**
	 * Priority order used by {@see self::user_default_slug()} when the
	 * site-wide `default_landing_page` setting points at a page the
	 * user can't access. Support-primary tasks (log-in, secrets) come
	 * before read-only and admin-config pages.
	 *
	 * @since 2.0.0
	 */
	const LANDING_PRIORITY = array(
		// Access Key Log-In is the most-used surface for support
		// agents — it's the page they actually do support work from.
		// First in priority means a logged-in support agent who hits
		// the top-level TrustedLogin menu lands directly on the
		// access-key form without an extra hop through Settings.
		self::SLUG_ACCESS_KEY,
		// Settings is the second priority because it's the only page
		// admins reliably have access to (every TL cap is a delegated
		// cap, but `manage_options` is the WP admin's baseline). When
		// the configured landing page isn't accessible to the current
		// user, an admin should still land on something they can act
		// on, not on a series of cap-gated pages they'll be 403'd from.
		self::SLUG_SETTINGS,
		// Secrets before Activity: creating + sharing secrets is the
		// primary action a support agent takes; Activity is the
		// secondary review surface. Locked in by
		// MenuVisibilityTest::test_landing_priority_secrets_before_activity.
		self::SLUG_SECRETS,
		self::SLUG_ACTIVITY,
		self::SLUG_TEAMS,
		self::SLUG_PERMISSIONS,
		// SLUG_HELPDESKS kept in LANDING_PRIORITY fallback despite no
		// longer being a rendered submenu — a user whose admin set
		// default_landing_page="helpdesks" before 1.4.1 should still
		// land on something useful (the Settings page, via the legacy
		// redirect in MaybeRedirect::maybeRedirectParentMenu).
		self::SLUG_HELPDESKS,
	);

	const ASSET_HANDLE = 'trustedlogin-settings';

	/**
	 * ID of root element for React app
	 *
	 * @var string
	 */
	const REACT_ROOT_ID = 'trustedlogin-settings';

	/**
	 * Page name/title.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Slug for the child/submenu page.
	 *
	 * @var string
	 */
	protected $childSlug;

	/**
	 * Optional name for the menu page.
	 *
	 * @var string|null
	 */
	protected $childName;

	/**
	 * Initial view to display on page load.
	 *
	 * @var string
	 */
	protected $initialView;

	/**
	 * Whether to show the menu to support roles.
	 *
	 * @var bool
	 */
	protected $show_menu_to_support;

	/**
	 * Initialise the menu page configuration.
	 *
	 * @param string|null $childSlug Optional slug for the child page. If null, parent page is created.
	 * @param string|null $name Optional name for the menu page.
	 * @param string|null $initialView Optional, passed to ViewProvider's initialView prop.
	 * @param bool        $show_menu_to_support Optional, whether to show the menu to users with any of the Support Roles.
	 */
	public function __construct( $childSlug = null, $name = null, $initialView = null, $show_menu_to_support = false ) {
		$this->childSlug            = $childSlug;
		$this->childName            = $name;
		$this->initialView          = $initialView;
		$this->show_menu_to_support = $show_menu_to_support;
		add_action( 'admin_menu', array( $this, 'addMenuPage' ), 25 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Check if assets should be enqueued.
	 *
	 * @param string $page Hook suffix for the current admin page.
	 *
	 * @return bool True if assets should be enqueued.
	 */
	public function shouldEnqueueAssets( $page ) {

		// The top-level admin menu's hook suffix is `toplevel_page_<slug>`,
		// where <slug> is the parent menu slug, NOT the asset handle.
		if ( 'toplevel_page_' . self::PARENT_MENU_SLUG === $page ) {
			return true;
		}

		if ( in_array(
			// trustedlogin_page_trustedlogin_access_key_login.
			str_replace( 'trustedlogin_page_', '', $page ),
			array(
				self::SLUG_TEAMS,
				self::SLUG_HELPDESKS,
				self::SLUG_SETTINGS,
				self::SLUG_ACCESS_KEY,
				self::SLUG_SECRETS,
				self::SLUG_ACTIVITY,
				self::SLUG_PERMISSIONS,
				self::PARENT_MENU_SLUG,
			),
			true
		) ) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if the current user has any of the roles that can be redirected to the client site.
	 *
	 * @since 0.13.0
	 *
	 * @return bool
	 */
	private function userHasAnySupportRole() {
		return self::currentUserHasAnySupportRole();
	}

	/**
	 * Static variant of {@see self::userHasAnySupportRole()} so external
	 * callers (MaybeRedirect, init.php reorder) can re-run the same
	 * support-role fallback without instantiating a MenuPage.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function currentUserHasAnySupportRole() {
		return self::userIdHasAnySupportRole( get_current_user_id() );
	}

	/**
	 * Whether the given user has any role configured as a team's
	 * approved_role. Static so callers don't have to instantiate
	 * MenuPage (which would re-register its admin_menu hooks every
	 * call). Mirrors VerifyUser::verifyUserRole() but reads roles
	 * from a passed user_id instead of get_current_user_id().
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id User ID to check.
	 *
	 * @return bool
	 */
	public static function userIdHasAnySupportRole( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! is_array( $user->roles ) ) {
			return false;
		}

		$settings = SettingsApi::fromSaved();
		$allTeams = $settings->allTeams();
		if ( empty( $allTeams ) ) {
			return false;
		}

		foreach ( $allTeams as $teamSettings ) {
			$required = (array) $teamSettings->get( 'approved_roles' );
			if ( array_intersect( $required, $user->roles ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * For a submenu slug with an OR-list of caps in SUBMENU_CAPABILITIES,
	 * returns whichever single cap the current user already satisfies;
	 * for a single-cap slug, returns that cap verbatim. Used by
	 * addMenuPage() to feed a cap string into add_submenu_page that
	 * actually opens the submenu for the current request's user —
	 * registering with a cap the user lacks causes WP to hide the
	 * submenu regardless of the other caps they hold.
	 *
	 * Returns `'do_not_allow'` when the user holds none of the listed
	 * caps so add_submenu_page fail-closes.
	 *
	 * @since 2.0.0
	 *
	 * @param string $slug Submenu slug.
	 *
	 * @return string Cap slug safe to pass into add_submenu_page.
	 */
	private static function resolve_submenu_capability( $slug ) {
		$mapped = self::SUBMENU_CAPABILITIES[ $slug ] ?? 'manage_options';

		if ( is_string( $mapped ) ) {
			return $mapped;
		}

		foreach ( (array) $mapped as $candidate ) {
			if ( current_user_can( $candidate ) ) {
				return $candidate;
			}
		}

		return 'do_not_allow';
	}

	/**
	 * Whether the given user can load the admin page for `$slug`.
	 *
	 * Mirrors the gate that `add_submenu_page()` applies at registration
	 * time, including the support-role fallback for SLUG_ACCESS_KEY
	 * (support agents see the Access Key Log-In page via `read` even
	 * without ACCESS_KEY_LOGIN).
	 *
	 * @since 2.0.0
	 *
	 * @param string   $slug    Submenu slug (a SLUG_* constant).
	 * @param int|null $user_id User to check; null = current user.
	 *
	 * @return bool
	 */
	public static function user_can_access_slug( $slug, $user_id = null ) {
		$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		// Support-role escape for SLUG_ACCESS_KEY — only active until the
		// one-shot approved_roles → ACCESS_KEY_LOGIN migration has run.
		// Once {@see Capabilities::run_version_upgrade()} marks the
		// migration complete, caps are the source of truth: revoking
		// ACCESS_KEY_LOGIN in the Permissions UI must actually block
		// access, even for users still listed in a team's approved_roles.
		// Always check the same $user_id we're authorizing — checking
		// the current user's roles to admit a different user_id would
		// let any signed-in support agent enable access for arbitrary
		// user IDs.
		if ( self::SLUG_ACCESS_KEY === $slug
			&& ! get_option( Capabilities::APPROVED_ROLES_MIGRATION_OPTION )
			&& self::userIdHasAnySupportRole( $user_id )
		) {
			return user_can( $user_id, 'read' );
		}

		$mapped = self::SUBMENU_CAPABILITIES[ $slug ] ?? 'manage_options';

		// Array form means "any one of these primitive caps satisfies
		// access to this page". Used for Secrets (CREATE_SECRET OR
		// MANAGE_SECRETS) so the page opens for users holding either,
		// with in-React section gates deciding what they see inside.
		foreach ( (array) $mapped as $candidate ) {
			if ( user_can( $user_id, $candidate ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolves the best landing slug for the given user.
	 *
	 * Rules, in order:
	 *   1. The site-wide `default_landing_page` setting, if the user
	 *      can access it.
	 *   2. First slug in {@see self::LANDING_PRIORITY} that the user
	 *      can access.
	 *   3. null if nothing is accessible — callers should not redirect
	 *      in that case (WP will show its own "not allowed" screen).
	 *
	 * @since 2.0.0
	 *
	 * @param int|null $user_id User to check; null = current user.
	 *
	 * @return string|null Submenu slug, or null if nothing is reachable.
	 */
	public static function user_default_slug( $user_id = null ) {
		$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
		if ( $user_id <= 0 ) {
			return null;
		}

		// Site-wide preference first — admins configuring landing pages
		// expect them to stick for users who can reach them.
		//
		// Fallback when no preference is saved: Access Key Log-In once
		// the install has been onboarded (the support agent's daily
		// destination); Settings before onboarding (the admin still
		// needs to finish setup, and Access Key has no useful state
		// to land on yet).
		$settings = SettingsApi::fromSaved()->getGlobalSettings();
		$default  = Onboarding::hasOnboarded() ? 'access_key_login' : 'settings';
		$choice   = isset( $settings['default_landing_page'] ) ? (string) $settings['default_landing_page'] : $default;
		if ( isset( self::DEFAULT_LANDING_PAGES[ $choice ] ) ) {
			$preferred = self::DEFAULT_LANDING_PAGES[ $choice ];
			if ( self::user_can_access_slug( $preferred, $user_id ) ) {
				return $preferred;
			}
		}

		// Fall through priority order for users the default doesn't
		// serve — support agents with only ACCESS_KEY_LOGIN, editors
		// with only VIEW_ACTIVITY, etc.
		foreach ( self::LANDING_PRIORITY as $slug ) {
			if ( self::user_can_access_slug( $slug, $user_id ) ) {
				return $slug;
			}
		}

		return null;
	}

	/**
	 * Add the menu page(s) to WordPress.
	 *
	 * @uses admin_menu
	 *
	 * @return void
	 */
	public function addMenuPage() {

		$name = $this->childName ?? __( 'TrustedLogin', 'trustedlogin-connector' );

		// Top-level menu: use the virtual MENU_ACCESS cap so users
		// who hold any TL cap see the menu node. WP hides the whole
		// branch when the parent cap fails, regardless of submenu
		// caps — MENU_ACCESS is the parent gate that lets non-admin
		// TL-cap holders reach their submenus.
		//
		// Submenus: look up a per-slug override in SUBMENU_CAPABILITIES;
		// fall back to manage_options so existing admin-only submenus
		// behave as before. Array values (e.g. Secrets → [CREATE_SECRET,
		// MANAGE_SECRETS]) are resolved to whichever single cap the
		// current user already holds — add_submenu_page takes a single
		// cap string and only cares that it passes current_user_can,
		// so registering with a cap the user has is equivalent to
		// "any of these caps".
		if ( $this->childSlug ) {
			$capability = self::resolve_submenu_capability( $this->childSlug );
		} else {
			$capability = Capabilities::MENU_ACCESS;
		}

		// Pre-migration only: allow users in any configured support role
		// to see the page even without ACCESS_KEY_LOGIN. After the
		// approved_roles migration completes, caps are authoritative
		// and revoking ACCESS_KEY_LOGIN must hide the menu node.
		if ( $this->show_menu_to_support
			&& ! get_option( Capabilities::APPROVED_ROLES_MIGRATION_OPTION )
			&& $this->userHasAnySupportRole()
		) {
			$capability = 'read';
		}

		if ( $this->childSlug ) {
			add_submenu_page(
				self::PARENT_MENU_SLUG,
				$name,
				$name,
				$capability,
				$this->childSlug,
				array( $this, 'renderPage' )
			);
		} else {
			// Top level page.
			add_menu_page(
				$name,
				$name,
				$capability,
				self::PARENT_MENU_SLUG,
				array( $this, 'renderPage' ),
				'data:image/svg+xml;base64,PHN2ZyBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxODAgMjAwIiB2aWV3Qm94PSIwIDAgMTgwIDIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJtMTMzLjYgNzF2LTUuOWguMXYtMjEuNWMwLTI0LTE5LjYtNDMuNi00My42LTQzLjYtMjQuMiAwLTQzLjcgMTkuNi00My43IDQzLjZ2MTMgOC41IDUuOWMtMTIgMS44LTE5LjUgNC4zLTE5LjUgN3Y0OWg1MS42di04LjljMC0yLjMgMS42LTMuMyAzLjYtMi4xbDI1LjYgMTQuOWMyIDEuMiAyIDMgMCA0LjJsLTI1LjYgMTQuOWMtMiAxLjItMy42LjItMy42LTIuMXYtOC45aC01MS42djExLjVjMCAzNC45IDQzIDQ5LjcgNjMuMiA0OS43czYzLjItMTQuOCA2My4yLTQ5Ljd2LTcyLjVjLS4xLTIuNy03LjctNS4yLTE5LjctN3ptLTY4LjYtNy44Yy4xIDAgLjEgMCAwIDBsLjEtMTkuNmMwLTEzLjggMTEuMS0yNC45IDI0LjktMjQuOSAxMy43IDAgMjQuOSAxMS4xIDI0LjkgMjQuOXYxM2gtLjF2MTIuNGMtNy42LS41LTE2LS44LTI0LjgtLjgtOC45IDAtMTcuMy4zLTI1IC44em0yNS4xIDExNmMtMjAuOCAwLTM4LjUtMTMuOS00NC4zLTMyLjhoMTMuNGM1LjMgMTEuOSAxNy4xIDIwLjIgMzAuOSAyMC4yIDE4LjYgMCAzMy43LTE1LjEgMzMuNy0zMy43cy0xNS4xLTMzLjctMzMuNy0zMy43Yy0xMy44IDAtMjUuNiA4LjMtMzAuOSAyMC4yaC0xMy41YzUuOC0xOC45IDIzLjUtMzIuOCA0NC4zLTMyLjggMjUuNiAwIDQ2LjMgMjAuOCA0Ni4zIDQ2LjNzLTIwLjggNDYuMy00Ni4yIDQ2LjN6IiBmaWxsPSIjMDEwMTAxIi8+PC9zdmc+'
			);
		}
	}

	/**
	 * Enqueue CSS and JavaScript assets for the admin page.
	 *
	 * @uses admin_enqueue_scripts
	 *
	 * @param string $hook The current admin page hook.
	 *
	 * @return void
	 */
	public function enqueueAssets( $hook ) {

		// Only enqueue on relevant admin pages. See https://github.com/trustedlogin/vendor/issues/116.
		if ( ! $this->shouldEnqueueAssets( $hook ) ) {
			return;
		}

		/**
		 * Whether to strip third-party admin notices on TL admin pages.
		 *
		 * Default true. The TL admin UI is a React-rendered chrome-free
		 * experience where stray plugin notices (WP Rocket "cache
		 * cleared", core update nags, etc.) intrude visually on every
		 * page load. Admins encounter the same WP-core / security
		 * notices on every OTHER admin page they visit, so suppressing
		 * them here doesn't meaningfully delay disclosure — it just
		 * keeps the TL pages clean.
		 *
		 * Integrators that prefer the original WP-default chrome (e.g.
		 * to never miss a notice in any context) can opt out:
		 *
		 *     add_filter( 'trustedlogin/connector/suppress_admin_notices', '__return_false' );
		 *
		 * Covers the full set of WP notice hooks — `update_nag` is
		 * the WP-core update banner (NOT routed through
		 * `admin_notices`), `network_admin_notices` /
		 * `user_admin_notices` fire on multisite / user-edit screens.
		 *
		 * @since 2.0.0
		 *
		 * @see https://github.com/trustedlogin/trustedlogin-connector/issues/35
		 *
		 * @param bool $suppress Default true (hide third-party notices on TL pages).
		 */
		if ( apply_filters( 'trustedlogin/connector/suppress_admin_notices', true ) ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
			remove_all_actions( 'update_nag' );
		}

		if ( Helpers::get_post_or_get( MaybeRedirect::REDIRECT_KEY ) ) {
			return;
		}

		// Enqueue assets.
		wp_enqueue_script( self::ASSET_HANDLE );
		wp_enqueue_style( self::ASSET_HANDLE );
	}

	/**
	 * Render callback for admin page
	 */
	public function renderPage() {

		if ( $this->initialView ) {
			// wp_json_encode is the correct escaper for content inside a
			// <script> body — esc_attr is for HTML attributes and lets
			// `</script>` and quotes through unchanged. The current
			// caller passes a const slug, but the inline-script idiom is
			// the kind of thing that gets copy-pasted with a request param
			// later; use the right escaper now.
			printf(
				'<script>window.tlInitialView = %s</script>',
				wp_json_encode( $this->initialView ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
		// React root.
		printf( '<div id="%s"></div>', self::REACT_ROOT_ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

<?php
/**
 * Custom capabilities for TrustedLogin features.
 *
 * Single source of truth for:
 *   - the four cap slug constants used across the plugin,
 *   - the human-readable labels the Permissions matrix UI renders,
 *   - the role-migration run on activate / uninstall, and
 *   - every write to WP's capability map (set_role_cap).
 *
 * REST permission_callbacks route their current-user checks through
 * {@see Capabilities::current_user_can()} so there is one choke point
 * to audit and stub.
 *
 * @package TrustedLogin\Vendor
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor;

/**
 * Capabilities implementation.
 */
class Capabilities {

	/**
	 * Create and manage one-time secrets.
	 */
	const MANAGE_SECRETS = 'trustedlogin_manage_secrets';

	/**
	 * Create secrets only (subset of MANAGE_SECRETS for support agents).
	 */
	const CREATE_SECRET = 'trustedlogin_create_secret';

	/**
	 * View the Activity page — logins and secret history.
	 */
	const VIEW_ACTIVITY = 'trustedlogin_view_activity';

	/**
	 * Use the Access Key Log-In page: submit an access key, decrypt the
	 * envelope with the team's sodium keypair, and land on the client
	 * site with a temporary login token.
	 *
	 * Sensitive — admin only by default.
	 */
	const ACCESS_KEY_LOGIN = 'trustedlogin_access_key_login';

	/**
	 * Virtual meta-capability: `current_user_can(MENU_ACCESS)` returns
	 * true if the user holds at least one TrustedLogin cap.
	 *
	 * Used as the top-level `TrustedLogin` admin-menu cap so a user who
	 * holds only VIEW_ACTIVITY (and not manage_options) still sees the
	 * menu node — without it, WP hides the parent and every submenu
	 * under it, no matter what caps the submenus declare.
	 *
	 * Not stored against any role. Resolved at runtime by
	 * {@see self::map_meta_cap()}.
	 *
	 * @since 2.0.0
	 */
	const MENU_ACCESS = 'trustedlogin_menu_access';

	/**
	 * The one role that the Permissions matrix refuses to modify.
	 *
	 * {@see Capabilities::activate()} re-grants every TL cap to this role
	 * on every plugin version bump. A toggle in the matrix UI would
	 * desync immediately after the next update, so the administrator row
	 * is locked on both client and server. Also prevents a last-admin
	 * lockout scenario.
	 */
	const IMMUTABLE_ROLE = 'administrator';

	/**
	 * All TL-managed capability slugs.
	 *
	 * Derived from {@see self::labels()} so adding a cap means editing
	 * one place — a cap without a label can't ship.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array_keys( self::labels() );
	}

	/**
	 * Human-readable label + one-line description for each cap, keyed
	 * on cap slug. Wrapped in `__()` at call time (rather than stored
	 * as a class constant) so the translation extractor picks up the
	 * strings and the runtime resolves the active locale.
	 *
	 * Surfaced by the Permissions matrix UI and the REST
	 * `/v1/permissions` GET response.
	 *
	 * @return array<string, array{label: string, description: string}>
	 */
	public static function labels() {
		// Order is rendered as the matrix column order; sign-in first,
		// audit-read next to it, secrets paired at the end. Team config
		// is locked to `manage_options` and not in this matrix.
		$labels = array(
			self::ACCESS_KEY_LOGIN => array(
				'label'       => __( 'Sign in to client sites', 'trustedlogin-connector' ),
				'description' => __( 'Use an access key to sign in to a customer site as a support agent. Sensitive — grants live support access.', 'trustedlogin-connector' ),
			),
			self::VIEW_ACTIVITY    => array(
				'label'       => __( 'View login activity', 'trustedlogin-connector' ),
				'description' => __( 'View the Login Activity page and read the history of support logins.', 'trustedlogin-connector' ),
			),
			self::CREATE_SECRET    => array(
				'label'       => __( 'Create secrets', 'trustedlogin-connector' ),
				'description' => __( 'Generate new one-time secrets; cannot view or revoke them after creation.', 'trustedlogin-connector' ),
			),
			self::MANAGE_SECRETS   => array(
				'label'       => __( 'Manage secrets', 'trustedlogin-connector' ),
				'description' => __( 'Create, view, and revoke one-time secrets.', 'trustedlogin-connector' ),
			),
		);

		return $labels;
	}

	/**
	 * Grants every TL cap to {@see Capabilities::IMMUTABLE_ROLE}.
	 *
	 * Called on plugin activation and on every version bump (see the
	 * admin_init hook in trustedlogin-connector.php). Idempotent —
	 * add_cap() on a role that already has the cap is a no-op.
	 */
	public static function activate() {
		$admin = get_role( self::IMMUTABLE_ROLE );
		if ( ! $admin ) {
			return;
		}
		foreach ( self::all() as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Option marker recording completion of the one-shot
	 * approved_roles → {@see self::ACCESS_KEY_LOGIN} grant migration. A
	 * dedicated option (vs. gating on the plugin version) ensures the
	 * migration runs exactly once and stays migrated across upgrade /
	 * downgrade cycles.
	 *
	 * @since 2.0.0
	 */
	const APPROVED_ROLES_MIGRATION_OPTION = 'trustedlogin_connector_approved_roles_migrated';

	/**
	 * Grants {@see self::ACCESS_KEY_LOGIN} to every role that appears in
	 * any team's `approved_roles` config. One-shot — records a flag
	 * option on completion and returns early on subsequent calls.
	 *
	 * Required because the access-key login flow combines (1) team-level
	 * `approved_roles` membership and (2) the {@see self::ACCESS_KEY_LOGIN}
	 * cap with AND semantics — both must pass. Without this migration a
	 * role that previously cleared only the `approved_roles` path would
	 * lose access on upgrade.
	 *
	 * Run once from the admin_init version-bump hook in
	 * trustedlogin-connector.php.
	 *
	 * @since 2.0.0
	 *
	 * @return int Count of role→cap grants performed.
	 */
	public static function migrate_approved_roles_to_caps() {
		if ( get_option( self::APPROVED_ROLES_MIGRATION_OPTION ) ) {
			return 0;
		}

		$granted = 0;

		try {
			$teams = SettingsApi::fromSaved()->allTeams();
		} catch ( \Exception $e ) {
			unset( $e );
			// Real load failure (bad serialized payload, DB error). Do
			// not mark the migration complete — retry on the next request
			// rather than stranding agents whose role only cleared the
			// `approved_roles` path.
			return 0;
		}

		$seen_roles = array();
		foreach ( $teams as $team ) {
			$approved = $team->get( 'approved_roles' );
			if ( ! is_array( $approved ) ) {
				continue;
			}
			foreach ( $approved as $role_slug ) {
				$role_slug = (string) $role_slug;
				if ( '' === $role_slug || isset( $seen_roles[ $role_slug ] ) ) {
					continue;
				}
				$seen_roles[ $role_slug ] = true;

				$role = get_role( $role_slug );
				if ( ! $role || $role->has_cap( self::ACCESS_KEY_LOGIN ) ) {
					continue;
				}
				$role->add_cap( self::ACCESS_KEY_LOGIN );
				++$granted;

				do_action(
					'trustedlogin/connector/capabilities/changed',
					$role_slug,
					self::ACCESS_KEY_LOGIN,
					true,
					0  // 0 = system actor (migration, no user)
				);
			}
		}

		update_option( self::APPROVED_ROLES_MIGRATION_OPTION, current_time( 'mysql' ), false );
		return $granted;
	}

	/**
	 * Removes every TL cap from every role. Called on plugin uninstall.
	 */
	public static function uninstall() {
		foreach ( wp_roles()->roles as $role_name => $role_data ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::all() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

	/**
	 * Whether the current user holds a given capability.
	 *
	 * The single entry point used by every REST permission_callback in
	 * the plugin. Kept as a thin wrapper over `current_user_can()` so
	 * tests can stub behaviour for a user and so future policy layers
	 * (Teams-plugin segregation, MU-pool fences) have a hook site.
	 *
	 * @param string $capability Any cap slug — a {@see Capabilities::}
	 *                            constant or a core WP cap.
	 *
	 * @return bool
	 */
	public static function current_user_can( $capability ) {
		return (bool) current_user_can( $capability );
	}

	/**
	 * Registers the `map_meta_cap` filter that resolves
	 * {@see self::MENU_ACCESS}. Call once during plugin bootstrap.
	 *
	 * Idempotent — `add_filter` dedupes by callback identity, so re-
	 * registering on subsequent requests (under Plugin::__construct
	 * churn in tests) is a no-op.
	 *
	 * @since 2.0.0
	 */
	public static function register_meta_caps() {
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Filter callback for {@see self::MENU_ACCESS}.
	 *
	 * Resolves the virtual cap to `granted` if the user holds any real
	 * TL cap, `do_not_allow` otherwise. Unauthenticated requests and
	 * unknown user ids always deny.
	 *
	 * @internal WP filter callback — do not call directly.
	 *
	 * @param string[] $caps    Required primitive caps (what we return).
	 * @param string   $cap     Cap being checked.
	 * @param int      $user_id User whose permissions are being checked.
	 * @param array    $args    Extra context passed to the check.
	 *
	 * @return string[] Primitive caps required for the check to pass.
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- WP map_meta_cap filter signature.
		if ( self::MENU_ACCESS !== $cap ) {
			return $caps;
		}

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array( 'do_not_allow' );
		}

		foreach ( self::all() as $tl_cap ) {
			if ( user_can( $user_id, $tl_cap ) ) {
				// Empty array = no further primitive caps needed; the
				// virtual cap is satisfied by any one TL cap.
				return array();
			}
		}

		return array( 'do_not_allow' );
	}

	// =====================================================================
	// Role ↔ cap reads and writes (Permissions matrix UI)
	// =====================================================================

	/**
	 * Whether a specific role has a capability stored against it.
	 *
	 * Reads the state in the options table via `get_role()->has_cap()` —
	 * the matrix UI wants the persisted value, not an effective-cap
	 * computation from a live `map_meta_cap` filter.
	 *
	 * No cap allowlist here; callers may read non-TL caps for UI context.
	 * Only {@see self::set_role_cap()} enforces the allowlist.
	 *
	 * @param string $role_slug Role slug, e.g. `editor`.
	 * @param string $cap       Cap slug.
	 *
	 * @return bool False if the role doesn't exist or lacks the cap.
	 */
	public static function role_has_cap( $role_slug, $cap ) {
		$role = get_role( (string) $role_slug );
		if ( ! $role ) {
			return false;
		}
		return (bool) $role->has_cap( $cap );
	}

	/**
	 * Returns the full `{ role_slug: { cap_slug: bool } }` map for the
	 * Permissions matrix GET.
	 *
	 * Rows: every role registered with WP (including custom ones).
	 * Columns: every TL cap. Administrator row is included with all
	 * values true so the client can render the locked row without
	 * special-casing.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function matrix() {
		$matrix = array();
		$caps   = self::all();

		foreach ( wp_roles()->roles as $role_slug => $_data ) {
			$row = array();
			foreach ( $caps as $cap ) {
				$row[ $cap ] = self::role_has_cap( $role_slug, $cap );
			}
			$matrix[ $role_slug ] = $row;
		}

		return $matrix;
	}

	/**
	 * Grants or revokes a TL cap on a role. The audit boundary for the
	 * Permissions REST endpoint.
	 *
	 * Three invariants:
	 *
	 *   1. {@see Capabilities::IMMUTABLE_ROLE} is never modifiable.
	 *      The activation migration re-grants on every version bump; a
	 *      toggle here would desync on the next update.
	 *   2. Only caps in {@see Capabilities::all()} can be touched. The
	 *      endpoint is not a generic "grant any WP cap" primitive, so a
	 *      compromised manage_options session can't use it to grant
	 *      itself `delete_users`, `install_plugins`, etc.
	 *   3. The role must already exist. Roles are never created here.
	 *
	 * Writes a single audit line to the debug log on success:
	 *
	 *     Capabilities::set_role_cap role={slug} cap={slug} granted={0|1} actor={id}
	 *
	 * The log call no-ops when `error_logging` is off, so this doesn't
	 * spam sites that haven't opted into logging.
	 *
	 * @param string $role_slug Parameter.
	 * @param string $cap Parameter.
	 * @param bool   $granted Parameter.
	 *
	 * @return true|\WP_Error True on success; WP_Error with one of
	 *                         `admin_immutable` / `unknown_cap` /
	 *                         `unknown_role` on failure.
	 */
	public static function set_role_cap( $role_slug, $cap, $granted ) {
		// Reject non-scalar inputs early so a JSON body with
		// `{"role": {"sub": "x"}}` can't coerce through (string) to
		// the literal "Array" and burn a request on an unknown_role
		// error — harmless but noisy.
		if ( ! is_scalar( $role_slug ) || ! is_scalar( $cap ) ) {
			return new \WP_Error(
				'unknown_role',
				__( 'Role not found.', 'trustedlogin-connector' )
			);
		}

		$role_slug = (string) $role_slug;
		$cap       = (string) $cap;
		$granted   = (bool) $granted;

		if ( self::IMMUTABLE_ROLE === $role_slug ) {
			return new \WP_Error(
				'admin_immutable',
				__( 'The administrator role always holds every TrustedLogin capability.', 'trustedlogin-connector' )
			);
		}

		if ( ! in_array( $cap, self::all(), true ) ) {
			return new \WP_Error(
				'unknown_cap',
				__( 'Only TrustedLogin capabilities can be modified through this endpoint.', 'trustedlogin-connector' )
			);
		}

		$role = get_role( $role_slug );
		if ( ! $role ) {
			return new \WP_Error(
				'unknown_role',
				__( 'Role not found.', 'trustedlogin-connector' )
			);
		}

		if ( $granted ) {
			$role->add_cap( $cap );
		} else {
			$role->remove_cap( $cap );
		}

		$actor_id = (int) get_current_user_id();

		// Log at `warning` — this is a security-relevant state change,
		// not debug noise. Greppable alongside other security events
		// without wading through the info-level API-call chatter.
		trustedlogin_connector()->log(
			sprintf(
				'Capabilities::set_role_cap role=%s cap=%s granted=%s actor=%d',
				$role_slug,
				$cap,
				$granted ? '1' : '0',
				$actor_id
			),
			__METHOD__,
			'warning'
		);

		/**
		 * Fires after a TrustedLogin capability has been granted or
		 * revoked on a role via the Permissions endpoint.
		 *
		 * External audit plugins (Stream, Simple History, WP-CLI audit
		 * hooks) can subscribe without depending on the plugin's debug
		 * log being enabled. The hook fires after the options-table
		 * write has completed, so listeners see the committed state.
		 *
		 * @since 2.0.0
		 *
		 * @param string $role_slug Role whose capability map was changed.
		 * @param string $cap       Capability slug that was modified.
		 * @param bool   $granted   True if granted, false if revoked.
		 * @param int    $actor_id  WP user ID that initiated the change.
		 */
		do_action( 'trustedlogin/connector/capabilities/changed', $role_slug, $cap, $granted, $actor_id );

		return true;
	}
}

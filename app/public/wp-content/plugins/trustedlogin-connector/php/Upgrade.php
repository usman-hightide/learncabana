<?php
/**
 * Per-version admin_init upgrade orchestrator.
 *
 * Owns the plugin-wide `db_version` marker and coordinates the steps
 * that have to run once per shipped version: cap activation, the
 * one-shot approved_roles → ACCESS_KEY_LOGIN migration, and the
 * rewrite-rules flush for routes added since the previous version.
 *
 * Lives outside Capabilities because the responsibility spans more
 * than capabilities — it also drives the rewrite flush — and the
 * `db_version` option is plugin-wide, not cap-specific.
 *
 * @package TrustedLogin\Vendor
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor;

/**
 * Runs the admin_init steps gated on the plugin's stored db_version.
 *
 * @since 2.0.0
 */
class Upgrade {

	/**
	 * Option name recording completion of the per-version upgrade
	 * flow. Bumped only when {@see self::run()} succeeds; if the
	 * caps migration is still pending the bump is skipped so the
	 * admin_init guard retries on the next request.
	 *
	 * @since 2.0.0
	 */
	const DB_VERSION_OPTION = 'trustedlogin_connector_db_version';

	/**
	 * Runs the per-version upgrade steps once the calling admin_init
	 * hook has already cleared its own cap and version guards.
	 *
	 * Steps, in order:
	 *
	 *   1. {@see Capabilities::activate()} — re-grants every TL cap
	 *      to the immutable admin role. Idempotent.
	 *   2. {@see Capabilities::migrate_approved_roles_to_caps()} —
	 *      one-shot: grants ACCESS_KEY_LOGIN to every role appearing
	 *      in any team's approved_roles. Returns early without
	 *      setting its marker on a teams-load failure.
	 *   3. `flush_rewrite_rules()` — persists rewrite rules added
	 *      since the previous version (e.g. the Secrets recipient
	 *      route added in 1.4 / 2.0).
	 *
	 * Marks {@see self::DB_VERSION_OPTION} only when the migration's
	 * own marker is set. Bumping it on a failed migration would make
	 * the admin_init guard short-circuit on every subsequent request
	 * and the migration would never retry — stranding approved-roles
	 * users without their ACCESS_KEY_LOGIN cap.
	 *
	 * @since 2.0.0
	 *
	 * @param string $version Plugin version to record once the
	 *                        migration has completed. Pass the
	 *                        active TRUSTEDLOGIN_PLUGIN_VERSION
	 *                        from the caller (this method does not
	 *                        couple to the constant directly so it
	 *                        is testable in isolation).
	 *
	 * @return bool True if db_version was set; false if the migration
	 *              is still pending and should be retried on the next
	 *              admin_init.
	 */
	public static function run( $version ) {
		Capabilities::activate();
		Capabilities::migrate_approved_roles_to_caps();

		if ( ! get_option( Capabilities::APPROVED_ROLES_MIGRATION_OPTION ) ) {
			// Migration is still pending — the caller will retry on the
			// next admin_init. Skip the rewrite flush too: it's expensive
			// (rebuilds the entire rewrite_rules option) and pointless to
			// repeat until the version actually advances.
			return false;
		}

		flush_rewrite_rules();

		update_option( self::DB_VERSION_OPTION, $version );
		return true;
	}
}

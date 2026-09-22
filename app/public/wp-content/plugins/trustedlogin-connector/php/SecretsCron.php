<?php
/**
 * Cron jobs for the one-time secrets feature.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\Logger;

/**
 * Registers and handles cron events for secret lifecycle management.
 *
 * @since 2.0.0
 */
class SecretsCron {

	use Logger;

	/**
	 * Hook name for the hourly garbage-collection cron.
	 *
	 * @var string
	 */
	const GC_HOOK = 'tl_secrets_gc';

	/**
	 * Hook name for the single-shot "drain the backlog" follow-up that
	 * fires shortly after a full-batch GC run. Distinct from
	 * {@see self::GC_HOOK} so that `wp_next_scheduled()` can detect a
	 * pending catch-up without being permanently satisfied by the
	 * recurring hourly event.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	const GC_CATCHUP_HOOK = 'tl_secrets_gc_catchup';

	/**
	 * Hook name for per-secret grace-period burn crons.
	 *
	 * @var string
	 */
	const BURN_HOOK = 'tl_secret_burn';

	/**
	 * Registers the GC cron and the burn handler.
	 *
	 * Called during plugin bootstrap.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function register() {

		// Hourly GC: clean up expired secrets + old audit rows.
		if ( ! wp_next_scheduled( self::GC_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::GC_HOOK );
		}

		add_action( self::GC_HOOK, array( static::class, 'run_gc' ) );

		// Catch-up handler: fires when a full-batch GC indicates the
		// backlog likely extends past one tick. Runs the same drain
		// routine, with the same batch cap, so a long backlog drains
		// across several closely-spaced ticks instead of waiting an hour.
		add_action( self::GC_CATCHUP_HOOK, array( static::class, 'run_gc' ) );

		// Per-secret burn: destroys in_flight records whose DELETE never arrived.
		add_action( self::BURN_HOOK, array( static::class, 'run_burn' ) );
	}

	/**
	 * Clears all secrets cron events on plugin deactivation.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::GC_HOOK );
		wp_clear_scheduled_hook( self::GC_CATCHUP_HOOK );
		// Individual burn crons are one-shot and will expire on their own,
		// but we can't enumerate them without scanning wp_options. They'll
		// harmlessly no-op if the action handler is unregistered.
	}

	/**
	 * Maximum rows scanned in a single GC run.
	 *
	 * The expiration timestamp is buried inside the serialized
	 * option_value, so MySQL can't filter on it server-side — every
	 * candidate row has to be loaded into PHP and unserialized. An
	 * unbounded SELECT on a vendor with tens of thousands of stale
	 * secrets will OOM the cron worker. Cap each run; if the cap is
	 * hit, immediately re-schedule another run so the backlog drains
	 * across cron ticks rather than wedging the worker.
	 *
	 * @var int
	 */
	const GC_BATCH_SIZE = 500;

	/**
	 * Hourly garbage collection.
	 *
	 * 1. Scans for tl_secret_* options whose embedded expiration has passed.
	 * 2. Destroys them and logs 'expired' audit events.
	 * 3. Prunes old audit rows past the retention window.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function run_gc() {
		global $wpdb;

		// Find tl_secret_* options. Bounded to GC_BATCH_SIZE so a
		// large backlog doesn't OOM the worker. SecretAudit lives in
		// its own custom DB table, NOT in wp_options, so no audit-row
		// exclusion is needed here.
		$like_secret = $wpdb->esc_like( SecretManager::OPTION_PREFIX ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM `$wpdb->options` WHERE option_name LIKE %s LIMIT %d",
				$like_secret,
				self::GC_BATCH_SIZE
			)
		);

		if ( empty( $rows ) ) {
			// No secrets in the database. Just clean up audit rows.
			SecretAudit::cleanup();
			return;
		}

		$manager = new SecretManager();

		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row->option_value );

			// Check the embedded expiration from our Utils transient format.
			if ( is_array( $data ) && isset( $data['expiration'] ) && 0 !== $data['expiration'] && time() > $data['expiration'] ) {
				// Extract the token from the option name.
				$token = str_replace( SecretManager::OPTION_PREFIX, '', $row->option_name );

				if ( strlen( $token ) === SecretManager::TOKEN_LENGTH && ctype_xdigit( $token ) ) {
					$manager->destroy( $token, 'expired' );
					SecretAudit::log( 'expired', $token );
				}
			}
		}

		// Prune old audit rows.
		SecretAudit::cleanup();

		// If the batch was full, the backlog probably extends beyond
		// it — schedule a catch-up run shortly so we drain rather than
		// waiting an hour. Use the dedicated catch-up hook (not GC_HOOK)
		// so the recurring hourly event doesn't permanently satisfy
		// wp_next_scheduled() and silently prevent every catch-up.
		if ( count( $rows ) >= self::GC_BATCH_SIZE && ! wp_next_scheduled( self::GC_CATCHUP_HOOK ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::GC_CATCHUP_HOOK );
		}
	}

	/**
	 * Per-secret grace-period burn.
	 *
	 * Scheduled 30 seconds after fetch_and_lock() sets a record to in_flight.
	 * If the record is still in_flight (DELETE never arrived), destroy it.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token The secret token.
	 *
	 * @return void
	 */
	public static function run_burn( $token ) {

		if ( ! is_string( $token ) || strlen( $token ) !== SecretManager::TOKEN_LENGTH || ! ctype_xdigit( $token ) ) {
			return;
		}

		$manager = new SecretManager();
		$record  = $manager->get_record( $token );

		if ( null === $record ) {
			// Already destroyed (recipient's DELETE arrived before the cron).
			return;
		}

		// Only burn if still in_flight — if revealed_at is set, the DELETE already processed.
		if ( ! empty( $record['in_flight'] ) && null === $record['revealed_at'] ) {
			$manager->destroy( $token, 'burned' );
			SecretAudit::log( 'burned', $token, array( 'memo' => 'grace_period_expired' ) );
		}
	}
}

<?php
/**
 * Class SecretAudit
 *
 * Append-only audit trail for one-time secret sharing lifecycle events.
 *
 * @package TrustedLogin\Vendor
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor;

// Table name comes from $wpdb->prefix + a literal suffix constant — never user
// input. All value bindings inside these SQL strings are still %s/%d prepared.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

use TrustedLogin\Vendor\Traits\Logger;

/**
 * Manages an append-only audit table for secret lifecycle events.
 *
 * Valid event types: 'created', 'previewed', 'revealed', 'burned', 'expired', 'passphrase_succeeded', 'passphrase_failed', 'tampered'.
 *
 * @since 2.0.0
 */
class SecretAudit {

	use Logger;

	/**
	 * Table name suffix, appended to $wpdb->prefix.
	 *
	 * @var string
	 */
	const TABLE_SUFFIX = 'tl_secret_audit';

	/**
	 * Number of days to retain audit rows before cleanup.
	 *
	 * @var int
	 */
	const RETENTION_DAYS = 90;

	/**
	 * Returns the full audit table name including the WordPress table prefix.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	/**
	 * Returns the current UTC time as a MySQL DATETIME(6) string with
	 * microsecond precision. Microsecond granularity is required so that
	 * tightly-spaced events on the same token (e.g. create → auto-preview on
	 * the recipient landing page) sort deterministically in the audit log.
	 *
	 * @return string e.g. "2026-04-16 12:34:56.123456"
	 */
	private static function now_microseconds() {
		$now  = microtime( true );
		$sec  = (int) $now;
		$usec = (int) round( ( $now - $sec ) * 1_000_000 );
		if ( $usec >= 1_000_000 ) {
			++$sec;
			$usec = 0;
		}
		return gmdate( 'Y-m-d H:i:s', $sec ) . '.' . sprintf( '%06d', $usec );
	}

	/**
	 * Creates the audit table via dbDelta. Called on plugin activation.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			token_hash char(64) NOT NULL,
			event varchar(20) NOT NULL,
			actor_user_id bigint(20) UNSIGNED NULL,
			actor_ip varchar(45) NOT NULL,
			actor_ua varchar(512) NOT NULL,
			team_account_id varchar(50) NOT NULL,
			memo varchar(255) NOT NULL,
			ttl_seconds int(10) UNSIGNED NULL,
			burn_after_reading tinyint(1) NULL,
			event_at datetime(6) NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_token_hash (token_hash),
			KEY idx_event_at (event_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	/**
	 * Inserts an audit row for a secret lifecycle event.
	 *
	 * @since 2.0.0
	 *
	 * @param string $event The event type (e.g. 'created', 'revealed', 'burned').
	 * @param string $token The raw secret token (will be hashed before storage).
	 * @param array  $extra {
	 *     Optional overrides.
	 *
	 *     @type string $team_account_id    Team account identifier.
	 *     @type string $memo               Human-readable note about the event.
	 *     @type int    $actor_user_id      Override for the acting user ID.
	 *     @type int    $ttl_seconds        TTL chosen at creation (only meaningful for `created`).
	 *     @type bool   $burn_after_reading Single-use flag (only meaningful for `created`).
	 * }
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function log( string $event, string $token, array $extra = array() ) {
		global $wpdb;

		// Lazy table creation: handles edge cases where the table doesn't exist
		// yet (e.g. manual DB reset, or first secret on a fresh install before
		// the activation hook ran).
		if ( ! self::table_exists() ) {
			self::create_table();
		}

		$token_hash    = hash( 'sha256', $token );
		$current_uid   = get_current_user_id();
		$actor_user_id = $current_uid > 0 ? $current_uid : null;
		$actor_ip      = Utils::get_ip();
		$actor_ua      = Utils::get_user_agent( 512 );

		$data = array(
			'token_hash'         => $token_hash,
			'event'              => $event,
			'actor_user_id'      => isset( $extra['actor_user_id'] ) ? (int) $extra['actor_user_id'] : $actor_user_id,
			'actor_ip'           => $actor_ip,
			'actor_ua'           => $actor_ua,
			'team_account_id'    => isset( $extra['team_account_id'] ) ? $extra['team_account_id'] : '',
			'memo'               => isset( $extra['memo'] ) ? $extra['memo'] : '',
			'ttl_seconds'        => isset( $extra['ttl_seconds'] ) ? (int) $extra['ttl_seconds'] : null,
			'burn_after_reading' => isset( $extra['burn_after_reading'] ) ? (int) (bool) $extra['burn_after_reading'] : null,
			'event_at'           => self::now_microseconds(),
		);

		$formats = array(
			'%s', // token_hash.
			'%s', // event.
			'%d', // actor_user_id.
			'%s', // actor_ip.
			'%s', // actor_ua.
			'%s', // team_account_id.
			'%s', // memo.
			'%d', // ttl_seconds.
			'%d', // burn_after_reading.
			'%s', // event_at.
		);

		if ( null === $data['ttl_seconds'] ) {
			$formats[7] = null;
		}
		if ( null === $data['burn_after_reading'] ) {
			$formats[8] = null;
		}

		// If actor_user_id is null, use NULL format.
		if ( null === $data['actor_user_id'] ) {
			$formats[2] = null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			self::get_table_name(),
			$data,
			$formats
		);

		return false !== $result;
	}

	/**
	 * Returns audit rows for a given token hash, ordered by event_at DESC.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token_hash SHA-256 hash of the token.
	 * @param int    $limit      Maximum number of rows to return.
	 *
	 * @return \stdClass[]|null Array of row objects on success, null on a DB failure.
	 */
	public static function get_history( string $token_hash, int $limit = 50 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table_name}` WHERE token_hash = %s ORDER BY event_at DESC LIMIT %d",
				$token_hash,
				$limit
			)
		);
	}

	/**
	 * Returns recent audit rows, optionally filtered by user.
	 *
	 * Groups by token_hash and returns only the latest event per token.
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id If > 0, filter by actor_user_id for "your recent secrets" view.
	 * @param int $limit   Maximum number of rows to return.
	 * @param int $offset  Number of rows to skip.
	 *
	 * @return \stdClass[]|null Array of row objects on success, null on a DB failure.
	 */
	public static function get_recent( int $user_id = 0, int $limit = 50, int $offset = 0 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// When $user_id > 0, scope to tokens THIS user created — but still
		// surface the latest event across ALL actors for each of those
		// tokens. Recipient-driven events (previewed/revealed/burned) are
		// logged with actor_user_id = NULL, so filtering the "latest per
		// token" subquery by actor_user_id directly would hide them and
		// every secret would appear stuck in "created".
		//
		// Tiebreak on MAX(id) rather than MAX(event_at): id auto-increments
		// monotonically and is therefore collision-free, whereas legacy
		// rows logged before the event_at(6) schema bump share an exact
		// second and would surface as duplicate list entries.
		//
		// Also surface the original creation timestamp (`created_at`) so
		// the listing UI can show the real "Created" time separately from
		// the latest-activity time.
		$user_filter  = '';
		$prepare_args = array();
		if ( $user_id > 0 ) {
			$user_filter    = "WHERE token_hash IN (
				SELECT token_hash FROM `{$table_name}`
				WHERE event = 'created' AND actor_user_id = %d
			)";
			$prepare_args[] = $user_id;
		}
		$prepare_args[] = $limit;
		$prepare_args[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Placeholder count is dynamic based on $user_id; args are passed as an array.
			$wpdb->prepare(
				"SELECT a.*, c.event_at AS created_at, c.ttl_seconds AS ttl_seconds, c.burn_after_reading AS burn_after_reading, c.memo AS created_memo,
					COALESCE(agg.viewed_count, 0) AS viewed_count,
					COALESCE(agg.succeeded_count, 0) AS succeeded_count,
					COALESCE(agg.failed_count, 0) AS failed_count
				FROM `{$table_name}` a
				INNER JOIN (
					SELECT token_hash, MAX(id) AS max_id
					FROM `{$table_name}`
					{$user_filter}
					GROUP BY token_hash
				) b ON a.id = b.max_id
				LEFT JOIN (
					-- Pin to the FIRST `created` row per token so a token
					-- with multiple `created` events (data migration drift,
					-- a future bug, etc.) doesn't multiply the outer rows
					-- via the LEFT JOIN.
					SELECT token_hash, MIN(id) AS first_id
					FROM `{$table_name}`
					WHERE event = 'created'
					GROUP BY token_hash
				) fc ON fc.token_hash = a.token_hash
				LEFT JOIN `{$table_name}` c ON c.id = fc.first_id
				LEFT JOIN (
					-- Per-token view counts so the listing can render a
					-- 'Views' column without a second roundtrip per row.
					-- SUM(<bool>) is MySQL idiom for COUNT(*) FILTER WHERE.
					-- viewed     = page loads that fetched ciphertext
					-- succeeded  = passphrase verifications that passed
					-- failed     = passphrase verifications that failed
					SELECT token_hash,
						SUM(event = 'previewed') AS viewed_count,
						SUM(event = 'passphrase_succeeded') AS succeeded_count,
						SUM(event = 'passphrase_failed') AS failed_count
					FROM `{$table_name}`
					GROUP BY token_hash
				) agg ON agg.token_hash = a.token_hash
				ORDER BY a.event_at DESC
				LIMIT %d OFFSET %d",
				$prepare_args
			)
		);
	}

	/**
	 * Deletes audit rows older than RETENTION_DAYS.
	 *
	 * @since 2.0.0
	 *
	 * @return int|false Number of rows deleted, or false on failure.
	 */
	public static function cleanup() {
		global $wpdb;

		$table_name = self::get_table_name();
		$cutoff     = gmdate( 'Y-m-d H:i:s', time() - self::RETENTION_DAYS * DAY_IN_SECONDS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$table_name}` WHERE event_at < %s",
				$cutoff
			)
		);
	}

	/**
	 * Checks if the audit table exists in the database.
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if the table exists, false otherwise.
	 */
	public static function table_exists() {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return $result === $table_name;
	}
}

<?php
/**
 * Class FailureLog
 *
 * Bounded ring buffer of vendor-side failure events the admin should
 * see — envelope verification failed, decryption failed, SaaS
 * unreachable, signing-key mismatch, webhook signature failed, etc.
 *
 * Storage: a single non-autoload `wp_options` row keyed by
 * self::OPTION. Self-bounded at self::MAX_ENTRIES (FIFO eviction).
 * No new database table; no SaaS round-trip; not coupled to the
 * debug-logging toggle (which defaults off and was the reason these
 * failures were invisible in the first place).
 *
 * @package TrustedLogin\Vendor
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor;

/**
 * Append-only bounded ring buffer for connector-side failure events.
 *
 * @since 2.0.0
 */
class FailureLog {

	/**
	 * Option name holding the JSON-encoded ring buffer.
	 *
	 * @var string
	 */
	const OPTION = 'tl_recent_issues';

	/**
	 * Max events retained. Older events drop FIFO.
	 *
	 * @var int
	 */
	const MAX_ENTRIES = 50;

	/**
	 * Coalescing window in seconds. A second event with the same
	 * coalescing key fired within this many seconds of the first
	 * bumps the existing row's count + last_seen instead of writing
	 * a new entry. Keeps a flapping failure from filling the ring
	 * with redundant rows.
	 *
	 * @var int
	 */
	const COALESCE_WINDOW_SECONDS = 300;

	/**
	 * Allowlist of context keys allowed onto a stored event.
	 *
	 * Anything not in this list is dropped at record() time. The
	 * allowlist exists so a future caller cannot accidentally
	 * stuff envelope ciphertext, decrypted identifiers, full public
	 * keys, access keys, or other sensitive material into a row that
	 * lands in the database. Same redaction discipline that
	 * EnvelopeLogRedactionTest enforces on the debug log.
	 *
	 * @var string[]
	 */
	const ALLOWED_CONTEXT_KEYS = array(
		'team_id',
		'http_code',
		'fingerprint_old',
		'fingerprint_new',
		'site_host',
		'error_code',
	);

	/**
	 * Severity levels in ascending order. Drives the badge logic
	 * (badge counts only `error`-severity rows).
	 */
	const SEVERITY_NOTICE  = 'notice';
	const SEVERITY_WARNING = 'warning';
	const SEVERITY_ERROR   = 'error';

	/**
	 * Record a failure event. Coalesces with an existing recent row
	 * if the coalescing key matches AND the existing row is within
	 * the coalescing window; otherwise writes a new row and evicts
	 * the oldest if the buffer is full.
	 *
	 * Not atomic — two concurrent writes may interleave their
	 * read-modify-write on the option; last write wins.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type     Event type (e.g. 'envelope_sig_failed',
	 *                         'saas_unreachable'). Free-form string;
	 *                         the React renderer maps known types to
	 *                         labels and CTAs.
	 * @param string $severity One of self::SEVERITY_*.
	 * @param array  $context  Optional context fields. Keys not in
	 *                         self::ALLOWED_CONTEXT_KEYS are dropped.
	 *                         Values are coerced to scalars / cast
	 *                         to string before storage.
	 *
	 * @return void
	 */
	public static function record( $type, $severity, array $context = array() ) {
		$type     = (string) $type;
		$severity = self::normalize_severity( $severity );
		$context  = self::filter_context( $context );

		$now = time();

		$buffer = self::read();

		// Coalesce with the most recent row sharing the same key.
		// We scan from newest to oldest so the first match is also
		// the most recent.
		$key = self::coalescing_key( $type, $context );
		foreach ( $buffer as $i => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( ( $row['_key'] ?? '' ) !== $key ) {
				continue;
			}
			if ( ( $now - (int) ( $row['last_seen'] ?? 0 ) ) > self::COALESCE_WINDOW_SECONDS ) {
				// Same key, but the existing row is older than the
				// coalescing window — treat as a fresh event so the
				// admin sees it as a new occurrence.
				break;
			}
			$buffer[ $i ]['count']     = ( (int) ( $row['count'] ?? 1 ) ) + 1;
			$buffer[ $i ]['last_seen'] = $now;
			// Severity escalation: a coalesced event of higher
			// severity bumps the row to the higher level.
			$buffer[ $i ]['severity'] = self::higher_severity( $row['severity'] ?? self::SEVERITY_NOTICE, $severity );
			self::write( $buffer );
			return;
		}

		// New event. `_v` pins the schema so a future shape change can
		// branch on the version rather than guessing from key presence.
		array_unshift(
			$buffer,
			array(
				'_v'         => 1,
				'_key'       => $key,
				'type'       => $type,
				'severity'   => $severity,
				'context'    => $context,
				'count'      => 1,
				'first_seen' => $now,
				'last_seen'  => $now,
			)
		);

		if ( count( $buffer ) > self::MAX_ENTRIES ) {
			$buffer = array_slice( $buffer, 0, self::MAX_ENTRIES );
		}

		self::write( $buffer );
	}

	/**
	 * Returns the most-recent N events, optionally filtered by
	 * severity floor. Order is newest first.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $limit         Max rows to return (caps at MAX_ENTRIES).
	 * @param string $min_severity  One of self::SEVERITY_*. Rows below
	 *                              this severity are dropped from the
	 *                              result. Pass null/empty to return all.
	 *
	 * @return array<int, array> Newest first.
	 */
	public static function recent( $limit = self::MAX_ENTRIES, $min_severity = null ) {
		$limit  = max( 1, min( (int) $limit, self::MAX_ENTRIES ) );
		$buffer = self::read();

		if ( null !== $min_severity && '' !== $min_severity ) {
			$threshold = self::severity_rank( $min_severity );
			$buffer    = array_values(
				array_filter(
					$buffer,
					function ( $row ) use ( $threshold ) {
						return is_array( $row )
							&& self::severity_rank( $row['severity'] ?? '' ) >= $threshold;
					}
				)
			);
		}

		return array_slice( $buffer, 0, $limit );
	}

	/**
	 * Empties the ring buffer. Intended for plugin-deactivate cleanup
	 * and test fixtures; not exposed via REST.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}

	/**
	 * Build the coalescing key for an event. Aggregating by
	 * (type, fingerprint_old, fingerprint_new, team_id) — not just
	 * by type — matters because rotation-suspected vs. forgery-
	 * suspected `envelope_sig_failed` events end up on different rows
	 * with different CTAs.
	 *
	 * @param string $type Parameter.
	 * @param array  $context Parameter.
	 *
	 * @return string
	 */
	private static function coalescing_key( $type, array $context ) {
		return implode(
			'|',
			array(
				$type,
				(string) ( $context['fingerprint_old'] ?? '' ),
				(string) ( $context['fingerprint_new'] ?? '' ),
				(string) ( $context['team_id'] ?? '' ),
				// http_code distinguishes a 500 from a 404 from a 503
				// for the same event type — collapsing them under one
				// key would hide the difference admins need to act on.
				(string) ( $context['http_code'] ?? '' ),
			)
		);
	}

	/**
	 * Drops any keys not in ALLOWED_CONTEXT_KEYS and coerces
	 * remaining values to safe scalars. The allowlist is the load-
	 * bearing safety property: any future caller that tries to log
	 * an envelope, ciphertext, identifier, or access key has the
	 * dangerous payload silently stripped.
	 *
	 * @param array $context Parameter.
	 *
	 * @return array
	 */
	private static function filter_context( array $context ) {
		$out = array();
		foreach ( self::ALLOWED_CONTEXT_KEYS as $allowed ) {
			if ( ! array_key_exists( $allowed, $context ) ) {
				continue;
			}
			$value = $context[ $allowed ];
			if ( is_scalar( $value ) || null === $value ) {
				$out[ $allowed ] = is_string( $value ) ? $value : (string) $value;
			}
		}
		return $out;
	}

	/**
	 * Normalize an arbitrary severity string to one of the three
	 * known levels. Unknown values fall back to `notice` so a typo
	 * never inflates the badge.
	 *
	 * @param string $severity Parameter.
	 *
	 * @return string
	 */
	private static function normalize_severity( $severity ) {
		switch ( (string) $severity ) {
			case self::SEVERITY_ERROR:
			case self::SEVERITY_WARNING:
			case self::SEVERITY_NOTICE:
				return $severity;
			default:
				return self::SEVERITY_NOTICE;
		}
	}

	/**
	 * Numeric ranking for severity comparison.
	 *
	 * @param string $severity Parameter.
	 *
	 * @return int
	 */
	private static function severity_rank( $severity ) {
		switch ( (string) $severity ) {
			case self::SEVERITY_ERROR:
				return 3;
			case self::SEVERITY_WARNING:
				return 2;
			case self::SEVERITY_NOTICE:
				return 1;
			default:
				return 0;
		}
	}

	/**
	 * Returns whichever of $a / $b is the higher severity.
	 *
	 * @param string $a Parameter.
	 * @param string $b Parameter.
	 *
	 * @return string
	 */
	private static function higher_severity( $a, $b ) {
		return self::severity_rank( $a ) >= self::severity_rank( $b ) ? $a : $b;
	}

	/**
	 * Read + decode the ring buffer from the option. Always returns
	 * an array (empty if missing or corrupt) so callers can iterate
	 * unconditionally.
	 *
	 * @return array
	 */
	private static function read() {
		$raw = get_option( self::OPTION, '' );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Persist the ring buffer back to the option (autoload=false to
	 * avoid loading on every WP request).
	 *
	 * @param array $buffer Parameter.
	 *
	 * @return void
	 */
	private static function write( array $buffer ) {
		$encoded = wp_json_encode( $buffer );
		if ( false === $encoded ) {
			// JSON encode failure (malformed UTF-8 in a stored
			// value, etc.) — drop the write rather than corrupt
			// the existing buffer.
			return;
		}
		update_option( self::OPTION, $encoded, false );
	}
}

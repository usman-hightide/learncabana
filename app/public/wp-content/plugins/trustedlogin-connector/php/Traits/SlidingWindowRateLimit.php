<?php
/**
 * Sliding-window rate limiting trait.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Traits;

use TrustedLogin\Vendor\Utils;

/**
 * Per-key sliding-window rate limit backed by option-table transients.
 *
 * Bucket entries are integer timestamps. On each call: prune entries
 * older than $window, refuse if the bucket has reached $max, otherwise
 * append now() and persist. Utils::set_transient is used (direct DB)
 * so Redis/Memcached LRU eviction can't silently reset the counter.
 *
 * ## Concurrency contract
 *
 * The read-modify-write on the bucket transient is NOT atomic. Two
 * concurrent requests racing on the same key can both observe N
 * entries, both decide they're under the cap, and both write back
 * N+1 — so the effective cap is `$max + (concurrent racers - 1)`,
 * not `$max`. This is the canonical WordPress transient rate-limit
 * pattern; full atomicity would require InnoDB row locking around
 * the option write, which isn't worth the cost for a per-IP /
 * per-user limiter that self-heals as the window slides.
 *
 * Callers that need true cap-strict counting (e.g. money or auth
 * primitives) MUST NOT use this trait — they need a dedicated DB
 * counter row with `SELECT … FOR UPDATE`.
 *
 * @since 2.0.0
 */
trait SlidingWindowRateLimit {

	/**
	 * Enforces a sliding-window rate limit on $key.
	 *
	 * @param string $key    Transient key — callers are responsible for
	 *                       namespacing (typically a per-user or per-IP suffix).
	 * @param int    $max    Cap on entries within $window before refusal.
	 * @param int    $window Window length in seconds.
	 *
	 * @return bool True when the caller should refuse (429); false on accept.
	 */
	private function enforce_rate_limit( string $key, int $max, int $window ): bool {

		$bucket = Utils::get_transient( $key );
		if ( ! is_array( $bucket ) ) {
			$bucket = array();
		}

		$now    = time();
		$cutoff = $now - $window;
		$bucket = array_values(
			array_filter(
				$bucket,
				static function ( $ts ) use ( $cutoff ) {
					return is_int( $ts ) && $ts > $cutoff;
				}
			)
		);

		if ( count( $bucket ) >= $max ) {
			Utils::set_transient( $key, $bucket, $window );
			return true;
		}

		$bucket[] = $now;
		Utils::set_transient( $key, $bucket, $window );
		return false;
	}
}

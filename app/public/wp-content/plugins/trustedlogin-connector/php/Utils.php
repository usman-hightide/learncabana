<?php
/**
 * Class Utils
 *
 * Ported from TrustedLogin\Utils in the client SDK (client/src/Utils.php).
 * Provides direct-to-DB transient storage that bypasses the WordPress object cache,
 * preventing silent eviction by Redis/Memcached under LRU memory pressure.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

/**
 * Utility class for cache-safe transient operations.
 *
 * @since 2.0.0
 */
class Utils {

	/**
	 * Retrieves a transient value directly from the database, bypassing object cache.
	 *
	 * @since 2.0.0
	 *
	 * @param string $transient Transient name (used as the option_name).
	 *
	 * @return mixed|false Transient value, or false if not set or expired.
	 */
	public static function get_transient( $transient ) {
		global $wpdb;

		if ( ! is_string( $transient ) || ! is_object( $wpdb ) ) {
			return false;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$pre = apply_filters( "pre_transient_{$transient}", false, $transient );

		if ( false !== $pre ) {
			return $pre;
		}

		// Bypass object cache — read directly from the options table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_value FROM `$wpdb->options` WHERE option_name = %s LIMIT 1",
				$transient
			)
		);

		if ( ! is_object( $row ) ) {
			return false;
		}

		$data = maybe_unserialize( $row->option_value );

		$value = self::retrieve_value_and_maybe_expire_transient( $transient, $data );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters( "transient_{$transient}", $value, $transient );
	}

	/**
	 * Stores a transient value directly in the database, bypassing object cache.
	 *
	 * Unlike the client SDK version, this sets autoload = 'no' so secrets don't
	 * load into memory on every WP request.
	 *
	 * @since 2.0.0
	 *
	 * @param string $transient  Transient name (used as the option_name).
	 * @param mixed  $value      Transient value.
	 * @param int    $expiration Time until expiration in seconds. Default: 0 (no expiration).
	 *
	 * @return bool True if the value was set, false otherwise.
	 */
	public static function set_transient( $transient, $value, $expiration = 0 ) {
		global $wpdb;

		if ( ! is_string( $transient ) || ! is_object( $wpdb ) ) {
			return false;
		}

		wp_protect_special_option( $transient );

		$expiration = (int) $expiration;

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$value = apply_filters( "pre_set_transient_{$transient}", $value, $expiration, $transient );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$expiration = apply_filters( "expiration_of_transient_{$transient}", $expiration, $value, $transient );

		$data = self::format_transient_data( $value, $expiration );

		// Insert or update directly — autoload = 'no' to prevent secrets loading on every request.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)",
				$transient,
				maybe_serialize( $data ),
				'no'
			)
		);

		// INSERT ... ON DUPLICATE KEY UPDATE returns 0 when the row exists
		// with identical values (no rows affected). Treat anything other
		// than explicit false as success so callers don't misinterpret
		// an unchanged row as a storage failure.
		if ( false !== $result ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( "set_transient_{$transient}", $data['value'], $data['expiration'], $transient );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'setted_transient', $transient, $data['value'], $data['expiration'] );
		}

		return false !== $result;
	}

	/**
	 * Deletes a transient directly from the database, bypassing object cache.
	 *
	 * @since 2.0.0
	 *
	 * @param string $transient Transient name (used as the option_name).
	 *
	 * @return bool True if deleted, false otherwise.
	 */
	public static function delete_transient( $transient ) {
		global $wpdb;

		if ( ! is_string( $transient ) || ! is_object( $wpdb ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $transient ) );

		if ( $result ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( "delete_transient_{$transient}", $transient );
		}

		return (bool) $result;
	}

	/**
	 * Checks expiration and returns the value or false if expired.
	 *
	 * @since 2.0.0
	 *
	 * @param string $transient      Transient name.
	 * @param mixed  $transient_data Unserialized transient data from the DB.
	 *
	 * @return mixed|false The stored value, or false if expired/invalid.
	 */
	private static function retrieve_value_and_maybe_expire_transient( $transient, $transient_data ) {

		if ( ! is_array( $transient_data ) ) {
			return false;
		}

		if ( ! array_key_exists( 'expiration', $transient_data ) || ! array_key_exists( 'value', $transient_data ) ) {
			return false;
		}

		// Non-zero expiration that has passed: delete and return false.
		if ( 0 !== $transient_data['expiration'] && time() > $transient_data['expiration'] ) {
			self::delete_transient( $transient );

			return false;
		}

		return $transient_data['value'];
	}

	/**
	 * Formats a value + expiration into the storage array shape.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value      Transient value.
	 * @param int   $expiration Seconds until expiration. 0 = no expiration.
	 *
	 * @return array { expiration: int, value: mixed }
	 */
	private static function format_transient_data( $value, $expiration = 0 ) {
		return array(
			'expiration' => 0 === $expiration ? 0 : time() + $expiration,
			'value'      => $value,
		);
	}

	/**
	 * Query args whose values are replaced with `REDACTED` in any URL that
	 * {@see Utils::redact_url_for_logging()} hands to the debug log.
	 *
	 * The SaaS's `/logs/logins/*` routes authenticate via the `api_key`
	 * query param; writing that key into a log file under wp-content/uploads
	 * is a one-step exfil primitive for anything that can read the uploads
	 * directory (backup grab, file-disclosure bug, shared-hosting mishap).
	 * The list is intentionally broader than the single arg in use today so
	 * future endpoints that put a different credential in the URL don't
	 * silently regress this hardening.
	 *
	 * @since 2.0.0
	 */
	const REDACTED_QUERY_ARGS = array(
		'api_key',
		'private_key',
		'access_key',
		'token',
		'secret',
	);

	/**
	 * Returns a copy of the URL with the values of any query args in
	 * {@see Utils::REDACTED_QUERY_ARGS} replaced with the literal string
	 * `REDACTED`. Keys that aren't in the URL are left alone — we never
	 * fabricate an arg on a call that didn't carry one, which would be
	 * misleading in a log.
	 *
	 * Chose value-replacement over `remove_query_arg()` so the log line
	 * still proves the credential was attached. A missing `api_key=` in a
	 * logged URL should mean "the call went out unauthenticated", not
	 * "we scrubbed it".
	 *
	 * @since 2.0.0
	 *
	 * @param string $url Full URL (with or without a query string).
	 *
	 * @return string
	 */
	public static function redact_url_for_logging( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}

		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( ! $query ) {
			return $url;
		}

		parse_str( $query, $current_args );
		if ( ! is_array( $current_args ) ) {
			return $url;
		}

		$redactions = array_intersect_key(
			array_fill_keys( self::REDACTED_QUERY_ARGS, 'REDACTED' ),
			$current_args
		);

		return $redactions ? add_query_arg( $redactions, $url ) : $url;
	}

	/**
	 * Returns the sanitized HTTP user agent string.
	 *
	 * @since 2.0.0
	 *
	 * @param int $max_length Maximum length. 0 = no limit.
	 *
	 * @return string
	 */
	public static function get_user_agent( $max_length = 0 ) {

		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

		if ( ! $max_length ) {
			return $user_agent;
		}

		return substr( $user_agent, 0, (int) $max_length );
	}

	/**
	 * Returns the client IP address, handling common proxy headers.
	 *
	 * @since 2.0.0
	 *
	 * @return string IPv4/IPv6 address, or empty string if unavailable.
	 */
	public static function get_ip() {

		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		/**
		 * Filters the list of proxies whose forwarded headers we trust
		 * when determining the client IP.
		 *
		 * Each entry can be either:
		 *   - An exact IPv4/IPv6 address matching the proxy's REMOTE_ADDR
		 *     (e.g. `192.0.2.10`).
		 *   - A CIDR range covering the proxy set (e.g. `173.245.48.0/20`
		 *     or `2a06:98c0::/29`). Useful for CDNs like Cloudflare that
		 *     publish edge ranges rather than individual IPs.
		 *
		 * **Do not return `0.0.0.0/0` or `::/0`** — wildcard entries
		 * are rejected. The filter must enumerate concrete proxy IPs
		 * or narrow CIDR ranges only.
		 *
		 * Only when REMOTE_ADDR matches one of these do we read
		 * X-Forwarded-For / CF-Connecting-IP for the real client.
		 *
		 * See docs/Connector/running-behind-a-proxy.md for a full
		 * walkthrough including a Cloudflare-specific snippet.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $proxies Trusted proxy IPs or CIDR ranges. Default empty.
		 */
		$trusted = (array) apply_filters( 'trustedlogin/connector/trusted-proxies', array() );

		if ( self::is_trusted_proxy( $remote, $trusted ) ) {
			foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR' ) as $header ) {
				if ( empty( $_SERVER[ $header ] ) ) {
					continue;
				}
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '';
	}

	/**
	 * Tests whether $ip matches any entry in $trusted (exact-IP or CIDR).
	 *
	 * Invalid entries (non-strings, un-parseable values, address-family
	 * mismatches) are silently skipped — they can't match and won't
	 * throw. Callers get the permissive "no match" result instead of a
	 * crash when a typo lands in the filter.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip      An IPv4 or IPv6 address, typically REMOTE_ADDR.
	 * @param array  $trusted Entries from the trusted-proxies filter.
	 *
	 * @return bool True if any entry matches $ip.
	 */
	public static function is_trusted_proxy( $ip, array $trusted ) {
		if ( ! is_string( $ip ) || '' === $ip ) {
			return false;
		}

		foreach ( $trusted as $entry ) {
			if ( ! is_string( $entry ) || '' === $entry ) {
				continue;
			}
			if ( self::ip_matches_cidr( $ip, $entry ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true if $ip falls inside the CIDR range $entry, or exactly
	 * equals $entry when $entry is a plain IP (no `/` prefix). Supports
	 * both IPv4 and IPv6.
	 *
	 * Algorithm: convert both to packed-binary via `inet_pton` and
	 * compare the first $prefix bits. The full-bytes run is a direct
	 * `substr()` equality; the remaining 1–7 bits of a partial byte are
	 * compared via bitmask on the next byte. This is the same byte-level
	 * compare used by WordPress core's IP helpers (e.g.
	 * wp_privacy_anonymize_ip).
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip    An IPv4 or IPv6 address.
	 * @param string $entry A plain IP or CIDR range (`192.0.2.0/24`, `2a06:98c0::/29`).
	 *
	 * @return bool
	 */
	public static function ip_matches_cidr( $ip, $entry ) {
		// Plain IP — exact-match only. Also catches malformed entries
		// like "foo" that don't contain a slash.
		if ( false === strpos( $entry, '/' ) ) {
			return $ip === $entry;
		}

		$parts = explode( '/', $entry, 2 );
		if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] || ! ctype_digit( $parts[1] ) ) {
			return false;
		}
		$subnet = $parts[0];
		$prefix = (int) $parts[1];

		// @ suppresses the PHP warning on malformed input; we check
		// the return value instead.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional; inet_pton emits notices on bad input and we branch on the false return.
		$ip_packed = @inet_pton( $ip );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Same as above.
		$subnet_packed = @inet_pton( $subnet );

		if ( false === $ip_packed || false === $subnet_packed ) {
			return false;
		}

		// IPv4 (4 bytes) and IPv6 (16 bytes) have different widths —
		// a mismatch means they can't possibly compare.
		if ( strlen( $ip_packed ) !== strlen( $subnet_packed ) ) {
			return false;
		}

		$max_bits = strlen( $ip_packed ) * 8;
		// Reject prefixes outside [floor..max_bits]. The floor enforces
		// the docblock contract that rejects `0.0.0.0/0` and `::/0`:
		// IPv4 must declare /8 or narrower, IPv6 /32 or narrower — the
		// thresholds match the widest blocks any CDN/edge provider
		// actually publishes. Wildcards (/0) would skip the byte-compare
		// and return true unconditionally on the remaining-bits check.
		$min_prefix = 32 === $max_bits ? 8 : 32;
		if ( $prefix < $min_prefix || $prefix > $max_bits ) {
			return false;
		}

		$full_bytes     = intdiv( $prefix, 8 );
		$remaining_bits = $prefix % 8;

		if ( $full_bytes > 0
			&& substr( $ip_packed, 0, $full_bytes ) !== substr( $subnet_packed, 0, $full_bytes )
		) {
			return false;
		}

		if ( 0 === $remaining_bits ) {
			return true;
		}

		// Compare the leading $remaining_bits of the next byte.
		$mask = chr( ( 0xFF << ( 8 - $remaining_bits ) ) & 0xFF );

		return ( $ip_packed[ $full_bytes ] & $mask ) === ( $subnet_packed[ $full_bytes ] & $mask );
	}
}

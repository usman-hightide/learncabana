<?php
/**
 * Sodium-based encryption + key management for the TrustedLogin Connector.
 *
 * Owns the long-term keypair used to decrypt access-grant envelopes and sign
 * outbound identity nonces, plus the short-lived historical keyring that lets
 * the connector accept envelopes sealed against a recently-retired key.
 *
 * @package trustedlogin-vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\Logger;

use WP_Error;

/**
 * Class: TrustedLogin Encryption.
 *
 * CamelCase method names are established API; renaming would be breaking.
 *
 * @package trustedlogin-vendor
 * @version 0.1.0
 */
class Encryption {

	use Logger;

	/**
	 * Name of the site option that stores the public-key tombstone.
	 *
	 * Filterable via `trustedlogin/connector/encryption/keys-option`.
	 *
	 * @since 0.8.0
	 *
	 * @var string
	 */
	private $key_option_name = 'trustedlogin_keys';

	/**
	 * Sibling option holding wrapped private-key material.
	 *
	 * The well-known `trustedlogin_keys` option is preserved as a tombstone that
	 * holds only public keys + a timestamp (i.e. a public-key cache, which is what
	 * the name implies to a reader scanning a DB dump). Private bits live here in
	 * wrapped form, under a name that does not appear in keyword grep lists.
	 *
	 * @since 2.0.0
	 *
	 * @const string
	 */
	const STATE_OPTION = 'tlc_runtime_state';

	/**
	 * Domain-separation label baked into the wrap-key derivation.
	 *
	 * Changing this string will invalidate every previously wrapped secret on this
	 * install — only rev it during a planned re-keying.
	 *
	 * @since 2.0.0
	 *
	 * @const string
	 */
	const WRAP_KEY_CONTEXT = 'trustedlogin-connector-state-v2';

	/**
	 * Maximum length of an index in MySQL.
	 *
	 * @since 1.1
	 *
	 * @see https://github.com/WordPress/WordPress/commit/b2cf8231059bb1c762a321a0a481f57f47ae9a1b
	 *
	 * @const int
	 */
	const MAX_INDEX_LENGTH = 191;

	/**
	 * Resolves the (filterable) option name used to store the public-key tombstone.
	 */
	public function __construct() {

		/**
		 * Filter allows site admins to change the site option key for storing the keys data.
		 *
		 * @since 0.8.0
		 *
		 * @param string     $key_option_name The name of the option in the database.
		 * @param Encryption $instance        The Encryption object.
		 */
		$key_option_name = apply_filters( 'trustedlogin/connector/encryption/keys-option', $this->key_option_name, $this );

		/**
		 * Deprecated alias of `trustedlogin/connector/encryption/keys-option`.
		 *
		 * @deprecated 1.1
		 */
		$key_option_name = apply_filters_deprecated( 'trustedlogin/vendor/encryption/keys-option', array( $key_option_name, $this ), '1.1', 'trustedlogin/connector/encryption/keys-option' );

		// If the key_option_name is valid, use it.
		if ( is_string( $key_option_name ) && strlen( $key_option_name ) <= self::MAX_INDEX_LENGTH ) {
			$this->key_option_name = $key_option_name;
		} else {
			$this->log(
				'Key option name is too long or not a string. Using default.',
				__METHOD__,
				'error',
				array(
					'key_option_name' => $key_option_name,
				)
			);
		}
	}

	/**
	 * The option name for storing historical keypairs after rotation.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	const KEYPAIR_HISTORY_OPTION = 'tlc_runtime_state_history';

	/**
	 * How long (in seconds) to retain a demoted keypair in the historical keyring.
	 *
	 * Set to 20 minutes — 2× the client SDK's 10-minute transient cache TTL
	 * (VENDOR_PUBLIC_KEY_EXPIRY = 600). Any envelope sealed with a stale cached
	 * key will have arrived well within this window.
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	const KEYPAIR_RETENTION_SECONDS = 20 * MINUTE_IN_SECONDS;

	/**
	 * Delete the keys, demoting the current keypair to the historical keyring first.
	 *
	 * @return void
	 */
	public function deleteKeys() {
		$current = $this->getKeys( false );
		if ( $current && ! is_wp_error( $current ) ) {
			$this->demoteKeypair( $current );
		}

		// Both legacy plaintext (option/site-option) and the new tombstone/state pair.
		delete_option( $this->key_option_name );
		delete_site_option( $this->key_option_name );
		delete_site_option( self::STATE_OPTION );
	}
	/**
	 * Returns the existing/saved key set.
	 *
	 * @since 0.8.0
	 *
	 * @param bool $generate_if_not_set If keys aren't saved in the database, should create using {@see generateKeys}.
	 *
	 * @return \stdClass|WP_Error If keys exist, returns the stdClass of keys. Otherwise, WP_Error explaning things.
	 */
	private function getKeys( $generate_if_not_set = true ) {

		$keys = $this->loadStoredKeys();

		if ( ! $keys && $generate_if_not_set ) {
			$keys = $this->generateKeys( true );
		}

		/**
		 * Filter allows site admins to change where the key is fetched from.
		 *
		 * @since 1.1
		 *
		 * @param \stdClass|WP_Error $keys     The keys object or WP_Error if any issues.
		 * @param Encryption         $instance The Encryption object.
		 */
		$filtered_keys = apply_filters( 'trustedlogin/connector/encryption/get-keys', $keys, $this );

		/**
		 * Deprecated alias of `trustedlogin/connector/encryption/get-keys`.
		 *
		 * @deprecated 1.1
		 */
		$filtered_keys = apply_filters_deprecated( 'trustedlogin/vendor/encryption/get-keys', array( $filtered_keys, $this ), '1.1', 'trustedlogin/connector/encryption/get-keys' );

		// Log when the filter substitutes a different object so admins can
		// audit the override. Identity-compare so re-returning the input
		// (the typical no-op) doesn't trigger.
		if ( $filtered_keys !== $keys ) {
			$this->log(
				'trustedlogin/connector/encryption/get-keys filter changed the return value.',
				__METHOD__,
				'warning'
			);
		}

		return $filtered_keys;
	}

	/**
	 * Creates a new public/private key set.
	 *
	 * @since 0.8.0
	 *
	 * @param bool $update Whether to update the database with the new keys. Default: true.
	 *
	 * @return  \stdClass|WP_Error  $keys or WP_Error if any issues
	 *    $keys = {
	 *        private_key: (string) The private key used for encrypt/decrypt.
	 *        public_key: (string) The public key used for encrypt/decrypt.
	 *        sign_public_key: (string) The public key used for signing/verifying.
	 *        sign_private_key: (string) The private key used for signing/verifying.
	 *    }
	 */
	private function generateKeys( $update = true ) {

		if ( ! function_exists( 'sodium_crypto_box_keypair' ) ) {
			return new WP_Error( 'sodium_not_exists', 'Sodium isn\'t loaded. Upgrade to PHP 7.0 or WordPress 5.2 or higher.' );
		}

		try {
			// Keeping named $bob_{name} for clarity while implementing the pattern from:
			// https://paragonie.com/book/pecl-libsodium/read/05-publickey-crypto.md.
			$bob_box_kp        = \sodium_crypto_box_keypair();
			$bob_box_secretkey = \sodium_crypto_box_secretkey( $bob_box_kp );
			$bob_box_publickey = \sodium_crypto_box_publickey( $bob_box_kp );

			$bob_sign_kp        = \sodium_crypto_sign_keypair();
			$bob_sign_publickey = \sodium_crypto_sign_publickey( $bob_sign_kp );
			$bob_sign_secretkey = \sodium_crypto_sign_secretkey( $bob_sign_kp );

			$keys = (object) array(
				'private_key'      => \sodium_bin2hex( $bob_box_secretkey ),
				'public_key'       => \sodium_bin2hex( $bob_box_publickey ),
				'sign_private_key' => \sodium_bin2hex( $bob_sign_secretkey ),
				'sign_public_key'  => \sodium_bin2hex( $bob_sign_publickey ),
			);

			if ( $update ) {
				// Demote the current keypair to the historical keyring before overwriting.
				// This preserves the old private key for ~20 minutes so that access
				// envelopes (and messages) sealed by client sites with a stale transient
				// cache can still be decrypted.
				$current = $this->getKeys( false );
				if ( $current && ! is_wp_error( $current ) ) {
					$this->demoteKeypair( $current );
				}

				$updated = $this->updateKeys( $keys );

				if ( is_wp_error( $updated ) ) {
					return $updated;
				}
			}

			return $keys;
		} catch ( \SodiumException $e ) {
			return new WP_Error( 'sodium-error', $e->getMessage() );
		}
	}

	/**
	 * Loads keys from storage, migrating 1.x plaintext rows on first read.
	 *
	 * Refuses to silently auto-rewrap on unwrap-fail so tamper / wrap-key
	 * change can't be masked as a regeneration.
	 *
	 * @since 2.0.0
	 *
	 * @return \stdClass|WP_Error|false Keys on success; WP_Error on tamper/wrap-key mismatch; false if absent.
	 */
	private function loadStoredKeys() {

		$public_raw = get_site_option( $this->key_option_name );

		if ( ! $public_raw ) {
			return false;
		}

		$public_record = json_decode( $public_raw );

		if ( ! $public_record || ! is_object( $public_record ) ) {
			// Shape-only log: $public_raw is the JSON; passing it as $context
			// could land the (potentially still-legacy) private key in the debug log.
			$this->log(
				'Keys were not decoded properly.',
				__METHOD__,
				'error',
				array(
					'value_length'    => is_string( $public_raw ) ? strlen( $public_raw ) : 0,
					'json_last_error' => function_exists( 'json_last_error' ) ? json_last_error() : null,
				)
			);
			$detail = __(
				'The stored TrustedLogin record could not be read. It may have been changed outside of TrustedLogin, or the database row may be corrupted.',
				'trustedlogin-connector'
			);
			$this->notifyAdminOfKeyTamper( 'keys_unreadable', $detail );
			// Fail closed when the option exists but can't be parsed; getKeys()
			// must not silently regenerate.
			return new WP_Error(
				'keys_unreadable',
				__(
					'TrustedLogin can’t read its encryption keys, so new support access can’t be granted. Go to TrustedLogin › Settings and click “Reset Encryption” to recover.',
					'trustedlogin-connector'
				)
			);
		}

		// Legacy plaintext path: the well-known option still holds the private bits.
		// Rebuild the canonical shape, then rewrite as tombstone+state (one-shot upgrade).
		if ( isset( $public_record->private_key, $public_record->sign_private_key ) ) {
			$migrated = (object) array(
				'public_key'       => isset( $public_record->public_key ) ? (string) $public_record->public_key : '',
				'private_key'      => (string) $public_record->private_key,
				'sign_public_key'  => isset( $public_record->sign_public_key ) ? (string) $public_record->sign_public_key : '',
				'sign_private_key' => (string) $public_record->sign_private_key,
			);

			// Best-effort upgrade. If the rewrite fails the keys remain
			// readable from the legacy row on the next call; the on-disk
			// state is no worse than before.
			$rewrite = $this->updateKeys( $migrated );

			if ( is_wp_error( $rewrite ) ) {
				$this->log(
					'Could not upgrade legacy plaintext key storage; returning legacy values for this request.',
					__METHOD__,
					'warning',
					array( 'reason' => $rewrite->get_error_code() )
				);
			}

			return $migrated;
		}

		$state_raw = get_site_option( self::STATE_OPTION );

		if ( ! $state_raw ) {
			$this->log(
				'Public-key tombstone exists but private-key state is missing — refusing to auto-regenerate.',
				__METHOD__,
				'error'
			);
			$detail = __(
				'Part of TrustedLogin’s encryption record is missing from the database. This usually means a row was deleted outside of TrustedLogin.',
				'trustedlogin-connector'
			);
			$this->notifyAdminOfKeyTamper( 'keys_state_missing', $detail );
			// Fail closed; getKeys() must not silently regenerate here.
			return new WP_Error(
				'keys_state_missing',
				__(
					'TrustedLogin’s encryption data is incomplete, so new support access can’t be granted. Go to TrustedLogin › Settings and click “Reset Encryption” to recover.',
					'trustedlogin-connector'
				)
			);
		}

		$state = json_decode( $state_raw, true );

		if ( ! is_array( $state ) || ! isset( $state['pk'], $state['spk'] ) ) {
			$this->log(
				'Private-key state record has unexpected shape.',
				__METHOD__,
				'error',
				array( 'value_length' => is_string( $state_raw ) ? strlen( $state_raw ) : 0 )
			);
			$detail = __(
				'TrustedLogin’s encryption record is in an unexpected format. It may have been edited outside of TrustedLogin.',
				'trustedlogin-connector'
			);
			$this->notifyAdminOfKeyTamper( 'keys_state_invalid', $detail );
			return new WP_Error(
				'keys_state_invalid',
				__(
					'TrustedLogin’s encryption data is in an unexpected format, so new support access can’t be granted. Go to TrustedLogin › Settings and click “Reset Encryption” to recover.',
					'trustedlogin-connector'
				)
			);
		}

		$private_key      = self::unwrap_secret_hex( (string) $state['pk'] );
		$sign_private_key = self::unwrap_secret_hex( (string) $state['spk'] );

		if ( null === $private_key || null === $sign_private_key ) {
			$this->log(
				'Could not unwrap stored private keys — possible tampering or AUTH_KEY rotation. Refusing to silently regenerate.',
				__METHOD__,
				'error'
			);
			$detail = __(
				'TrustedLogin couldn’t unlock its encryption keys. The most common cause is that AUTH_KEY in wp-config.php was changed (for example, by a security plugin rotating salts). A less common cause is that a TRUSTEDLOGIN_KEY_WRAP_KEY constant was added or modified after the keys were generated.',
				'trustedlogin-connector'
			);
			$this->notifyAdminOfKeyTamper( 'keys_unwrap_failed', $detail );
			return new WP_Error(
				'keys_unwrap_failed',
				__(
					'TrustedLogin can’t unlock its encryption keys — this usually happens when AUTH_KEY in wp-config.php has changed. Go to TrustedLogin › Settings and click “Reset Encryption” to recover.',
					'trustedlogin-connector'
				)
			);
		}

		return (object) array(
			'public_key'       => isset( $public_record->public_key ) ? (string) $public_record->public_key : '',
			'private_key'      => $private_key,
			'sign_public_key'  => isset( $public_record->sign_public_key ) ? (string) $public_record->sign_public_key : '',
			'sign_private_key' => $sign_private_key,
		);
	}

	/**
	 * Saves the key pair to the local database for future use.
	 *
	 * The well-known option (`trustedlogin_keys`) is written as a tombstone
	 * holding only public keys + an updated_at timestamp. Private keys are
	 * wrapped with `sodium_crypto_secretbox` and persisted to the sibling
	 * state option.
	 *
	 * @since 0.8.0
	 *
	 * @see Encryption::create_keys()
	 *
	 * @param object $keys The keys to save.
	 *
	 * @return true|WP_Error True if keys saved. WP_Error if not.
	 */
	private function updateKeys( $keys ) {

		if ( ! isset( $keys->public_key, $keys->private_key, $keys->sign_public_key, $keys->sign_private_key ) ) {
			return new WP_Error( 'invalid_keys', 'Refusing to save incomplete key set.' );
		}

		try {
			$wrapped_private      = self::wrap_secret_hex( (string) $keys->private_key );
			$wrapped_sign_private = self::wrap_secret_hex( (string) $keys->sign_private_key );
		} catch ( \SodiumException $e ) {
			return new WP_Error( 'wrap_failed', 'Could not wrap private keys.' );
		}

		$public_record = (object) array(
			'public_key'      => $keys->public_key,
			'sign_public_key' => $keys->sign_public_key,
			'updated_at'      => time(),
		);

		$state = array(
			'pk'  => $wrapped_private,
			'spk' => $wrapped_sign_private,
		);

		$public_json = wp_json_encode( $public_record );
		$state_json  = wp_json_encode( $state );

		if ( ! $public_json || ! $state_json ) {
			return new WP_Error( 'json_error', 'Could not encode keys to JSON.', $keys );
		}

		// STATE_OPTION first: closes the "new tombstone but no state"
		// TOCTOU window the prior delete-then-add ordering allowed.
		// update_site_option() returns false on no-change, so re-read confirms.
		$saved_state = update_site_option( self::STATE_OPTION, $state_json );

		if ( ! $saved_state && get_site_option( self::STATE_OPTION ) !== $state_json ) {
			return new WP_Error( 'db_error', 'Could not save wrapped private keys.' );
		}

		$saved_public = update_site_option( $this->key_option_name, $public_json );

		if ( ! $saved_public && get_site_option( $this->key_option_name ) !== $public_json ) {
			// State was saved but tombstone update failed. We intentionally do NOT
			// roll back the state — a fresh state under the new wrap-key is more
			// valuable than the (now stale) tombstone, and a subsequent retry will
			// re-converge both rows. Inflight handoffs may need a "Reset Encryption"
			// to recover; flagged via the returned WP_Error.
			//
			// No admin email here: this is a transient DB-write failure (disk full,
			// connection drop, etc.) that self-heals on the next call. Repeated
			// failures would still produce loud errors via the calling code paths.
			return new WP_Error(
				'db_error',
				__(
					'TrustedLogin saved part of its encryption update but couldn’t finish. If granting access fails, go to TrustedLogin › Settings and click “Reset Encryption”.',
					'trustedlogin-connector'
				)
			);
		}

		return true;
	}

	/**
	 * Wraps a hex-encoded key with `sodium_crypto_secretbox`.
	 *
	 * Returns a bare base64 string (no version prefix, no JSON envelope).
	 *
	 * @since 2.0.0
	 *
	 * @param string $hex_value Hex-encoded plaintext (typically a sodium private key).
	 *
	 * @return string base64(nonce ‖ ciphertext)
	 * @throws \SodiumException If randombytes or secretbox fails.
	 */
	private static function wrap_secret_hex( $hex_value ) {
		$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( (string) $hex_value, $nonce, self::wrap_key() );
		// Binary-safe encode of (nonce ‖ ciphertext) for storage in a TEXT option.
		return base64_encode( $nonce . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Unwraps a value produced by {@see Encryption::wrap_secret_hex()}.
	 *
	 * Returns null on any failure (malformed base64, wrong length, AEAD tag
	 * mismatch). The caller MUST treat null as a hard error — it indicates
	 * tampering or that the wrap-key has changed (e.g. AUTH_KEY rotation).
	 *
	 * @since 2.0.0
	 *
	 * @param string $blob base64(nonce ‖ ciphertext) as written by wrap_secret_hex.
	 *
	 * @return string|null Hex plaintext on success, null on tamper / wrong key.
	 */
	private static function unwrap_secret_hex( $blob ) {
		if ( ! is_string( $blob ) || '' === $blob ) {
			return null;
		}

		// Strict-mode base64 decode of the (nonce ‖ ciphertext) blob written by wrap_secret_hex().
		$raw = base64_decode( $blob, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$min = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;

		if ( false === $raw || strlen( $raw ) < $min ) {
			return null;
		}

		$nonce      = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		try {
			$plain = sodium_crypto_secretbox_open( $ciphertext, $nonce, self::wrap_key() );
		} catch ( \SodiumException $e ) {
			return null;
		}

		return false === $plain ? null : $plain;
	}

	/**
	 * Derives the symmetric wrap key.
	 *
	 * Preference order:
	 *   1. `TRUSTEDLOGIN_KEY_WRAP_KEY` constant (base64-encoded raw 32 bytes) —
	 *      strongest isolation when the integrator sets it in wp-config.php.
	 *   2. BLAKE2b in keyed mode (`sodium_crypto_generichash`) with `AUTH_KEY`
	 *      as the message and `WRAP_KEY_CONTEXT` as the key, falling back to
	 *      `wp_salt('auth')` when `AUTH_KEY` is the WP installer placeholder.
	 *      (This is keyed-BLAKE2b, not RFC-5869 HKDF — the message is the
	 *      secret input.)
	 *
	 * The result is cached per-request so we don't re-hash on every wrap call.
	 * A long-running CLI process that survives an `AUTH_KEY` rotation will
	 * therefore continue to write under the previous wrap key until it
	 * restarts — operational caveat documented for integrators.
	 *
	 * @since 2.0.0
	 *
	 * @return string 32 raw bytes (SODIUM_CRYPTO_SECRETBOX_KEYBYTES).
	 */
	private static function wrap_key() {

		static $cached = null;

		if ( null !== $cached ) {
			return $cached;
		}

		if ( defined( 'TRUSTEDLOGIN_KEY_WRAP_KEY' ) ) {
			// Integrator-supplied raw key, expected base64-encoded in wp-config.php.
			$raw = base64_decode( (string) constant( 'TRUSTEDLOGIN_KEY_WRAP_KEY' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false !== $raw && SODIUM_CRYPTO_SECRETBOX_KEYBYTES === strlen( $raw ) ) {
				$cached = $raw;
				return $cached;
			}
		}

		$material = ( defined( 'AUTH_KEY' ) && AUTH_KEY !== 'put your unique phrase here' )
			? AUTH_KEY
			: wp_salt( 'auth' );

		$cached = sodium_crypto_generichash(
			(string) $material,
			self::WRAP_KEY_CONTEXT,
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);

		return $cached;
	}

	/**
	 * Emails the site administrator when an encryption-key health check fails.
	 *
	 * Rate-limited to one email per reason code per 6 hours via a site
	 * transient. The body is generic by design and never includes private keys,
	 * wrapped blobs, or fingerprints.
	 *
	 * Filterable via `trustedlogin/connector/encryption/notify-admin-on-tamper`
	 * for integrators who route notifications through their own channel.
	 *
	 * @since 2.0.0
	 *
	 * @param string $reason_code   Internal error code (e.g. 'keys_unwrap_failed').
	 * @param string $human_detail  One-sentence description of the failure mode for the admin.
	 *
	 * @return void
	 */
	private function notifyAdminOfKeyTamper( $reason_code, $human_detail ) {

		$site_name    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$settings_url = admin_url( 'admin.php?page=trustedlogin-settings' );

		$subject = sprintf(
			/* translators: %s: site name. */
			__( '[%s] TrustedLogin: support access is blocked — reset encryption to recover', 'trustedlogin-connector' ),
			$site_name
		);

		$body_lines = array(
			sprintf(
				/* translators: %s: site URL. */
				__( 'TrustedLogin Connector on %s could not read its stored encryption keys.', 'trustedlogin-connector' ),
				home_url()
			),
			'',
			$human_detail,
			'',
			__( 'What this means right now:', 'trustedlogin-connector' ),
			__( ' • You can’t grant new support sessions.', 'trustedlogin-connector' ),
			__( ' • Any access grants already in progress will fail.', 'trustedlogin-connector' ),
			__( ' • Active sessions (support agents already logged in) keep working.', 'trustedlogin-connector' ),
			'',
			__( 'This is usually a configuration change, not an attack — but if you didn’t change AUTH_KEY or your database, treat it as suspicious.', 'trustedlogin-connector' ),
			'',
			__( 'To recover:', 'trustedlogin-connector' ),
			sprintf(
				/* translators: %s: settings URL. */
				__( ' 1. Open TrustedLogin › Settings: %s', 'trustedlogin-connector' ),
				$settings_url
			),
			__( ' 2. Click “Reset Encryption”.', 'trustedlogin-connector' ),
			'',
			__( 'If you didn’t expect this notice, or it keeps coming back after you reset, contact support.', 'trustedlogin-connector' ),
			'',
			__( 'You won’t get another copy of this notice for at least 6 hours.', 'trustedlogin-connector' ),
		);

		$this->send_admin_key_notification( $reason_code, $subject, $body_lines );
	}

	/**
	 * Shared transport for the tamper + reset admin notifications.
	 *
	 * Honors the `trustedlogin/connector/encryption/notify-admin-on-tamper`
	 * suppression filter and enforces a 6-hour per-reason rate limit
	 * via {@see Utils::set_transient} (direct DB so Redis eviction
	 * can't silently drop the row and re-trigger the email).
	 *
	 * Bail order is filter → admin_email shape check → rate-limit
	 * transient. The earlier order (rate-limit before admin_email
	 * validation) consumed the 6-hour slot even when no mail went
	 * out, so a misconfigured admin_email used to silently swallow
	 * the budget and the operator never saw the prompt after fixing
	 * the address.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $reason_code Internal reason code; used as the
	 *                              rate-limit key and surfaced to the
	 *                              suppression filter.
	 * @param string   $subject     Email subject.
	 * @param string[] $body_lines  Body lines (joined with "\n").
	 *
	 * @return void
	 */
	private function send_admin_key_notification( $reason_code, $subject, array $body_lines ) {

		/**
		 * Filters whether to email the site administrator on encryption-tamper
		 * and encryption-reset signals.
		 *
		 * Return false to suppress the email (e.g. when notifications are handled
		 * by a separate monitoring channel). The internal error log entry is
		 * always written regardless.
		 *
		 * @since 2.0.0
		 *
		 * @param bool   $send         Whether to send the email. Default true.
		 * @param string $reason_code  The internal reason code that triggered the alert.
		 */
		$send = apply_filters( 'trustedlogin/connector/encryption/notify-admin-on-tamper', true, $reason_code );

		if ( ! $send ) {
			return;
		}

		$admin_email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );

		if ( ! $admin_email || ! is_email( $admin_email ) ) {
			return;
		}

		// Per-reason rate limit: at most one email per 6 hours per install.
		// Set AFTER the admin_email shape check so a misconfigured site
		// doesn't burn the slot when no email actually goes out.
		$transient_key = 'tlc_key_alert_' . md5( (string) $reason_code );
		if ( false !== Utils::get_transient( $transient_key ) ) {
			return;
		}
		Utils::set_transient( $transient_key, time(), 6 * HOUR_IN_SECONDS );

		wp_mail( $admin_email, $subject, implode( "\n", $body_lines ) );
	}

	/**
	 * Emails the site administrator when the operator invokes a
	 * legitimate Reset Encryption from the Settings UI.
	 *
	 * The reset wipes the keypair + the historical retention ring;
	 * any envelopes still in flight will fail to decrypt. The
	 * notification gives the admin a paper trail of who triggered
	 * the rotation and when, distinct from the tamper-alert path
	 * used by {@see self::notifyAdminOfKeyTamper()}.
	 *
	 * Same filter and rate-limit shape as the tamper notice so the
	 * two paths share testing surface and a single off-switch.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function notifyAdminOfReset() {

		$site_name    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$settings_url = admin_url( 'admin.php?page=trustedlogin-settings' );
		$user         = wp_get_current_user();
		$actor        = $user && (int) $user->ID > 0
			? sprintf( '%s (#%d)', $user->user_login, (int) $user->ID )
			: __( '(unauthenticated)', 'trustedlogin-connector' );

		$subject = sprintf(
			/* translators: %s: site name. */
			__( '[%s] TrustedLogin: encryption keys were reset', 'trustedlogin-connector' ),
			$site_name
		);

		$body_lines = array(
			sprintf(
				/* translators: 1: site URL, 2: actor (user_login + id) */
				__( 'A TrustedLogin Connector encryption reset was performed on %1$s by %2$s.', 'trustedlogin-connector' ),
				home_url(),
				$actor
			),
			'',
			__( 'After a reset:', 'trustedlogin-connector' ),
			__( ' • A fresh encryption keypair was generated.', 'trustedlogin-connector' ),
			__( ' • The historical retention ring was cleared.', 'trustedlogin-connector' ),
			__( ' • Any access-key envelopes still in flight will fail to decrypt.', 'trustedlogin-connector' ),
			'',
			__( 'If you did not initiate this reset, treat it as suspicious and review the audit log.', 'trustedlogin-connector' ),
			'',
			sprintf(
				/* translators: %s: settings URL. */
				__( 'Settings: %s', 'trustedlogin-connector' ),
				$settings_url
			),
		);

		$this->send_admin_key_notification( 'keys_reset_by_operator', $subject, $body_lines );
	}

	/**
	 * Gets a specific public cryptographic key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key_slug The slug of the key to fetch.
	 *                          Options are 'public_key' (default), 'sign_public_key'.
	 *
	 * @return string|WP_Error  Returns key if found, otherwise WP_Error.
	 */
	public function getPublicKey( $key_slug = 'public_key' ) {

		$keys = $this->getKeys();

		if ( is_wp_error( $keys ) ) {
			return $keys;
		}

		if ( ! in_array( $key_slug, array( 'public_key', 'sign_public_key' ), true ) ) {
			return new WP_Error( 'not_public_key', 'This function can only return public keys' );
		}

		if ( ! $keys || ! is_object( $keys ) || ! property_exists( $keys, $key_slug ) ) {
			return new WP_Error( 'get_key_failed', \sprintf( 'Could not get %s from get_key.', $key_slug ) );
		}

		return $keys->{$key_slug};
	}

	/**
	 * Gets a specific private cryptographic key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key_slug The slug of the private key to fetch.
	 *                          Options are 'private_key' (default), 'sign_private_key'.
	 *
	 * @return string|WP_Error  Returns key if found, otherwise WP_Error.
	 */
	private function getPrivateKey( $key_slug = 'private_key' ) {

		$keys = $this->getKeys();

		if ( is_wp_error( $keys ) ) {
			return $keys;
		}

		if ( ! in_array( $key_slug, array( 'private_key', 'sign_private_key' ), true ) ) {
			return new WP_Error( 'not_private_key', 'This function can only return private keys' );
		}

		if ( ! $keys || ! is_object( $keys ) || ! property_exists( $keys, $key_slug ) ) {
			return new WP_Error( 'get_key_failed', \sprintf( 'Could not get %s from get_key.', $key_slug ) );
		}

		return $keys->{$key_slug};
	}

	/**
	 * Decrypts an encrypted sodium_crypto_box payload.
	 *
	 * @since 0.8.0
	 * @since 1.0.0 - Added $nonce and $client_public_key params
	 *
	 * @uses \sodium_crypto_box_keypair_from_secretkey_and_publickey()
	 * @uses \sodium_crypto_box_open()
	 *
	 * @param string $encoded_and_encrypted_payload Base 64-encoded string that needs to be decrypted.
	 * @param string $hex_nonce Single use nonce for a specific Client. Passed as string; needs to be converted to binary. Must be 24 bytes.
	 * @param string $alice_public_key The public key from the Client plugin that generated the envelope.
	 *
	 * @return string|WP_Error If successful the decrypted string (could be a JSON string), otherwise WP_Error.
	 */
	public function decryptCryptoBox( $encoded_and_encrypted_payload, $hex_nonce, $alice_public_key ) {

		$bob_private_key_hex = $this->getPrivateKey();

		if ( is_wp_error( $bob_private_key_hex ) ) {
			return new WP_Error( 'key_error', 'Cannot decrypt: can\'t get private keys from the local DB.', $bob_private_key_hex );
		}

		return $this->crypto_box_open_and_zeroize(
			$encoded_and_encrypted_payload,
			$hex_nonce,
			$alice_public_key,
			$bob_private_key_hex
		);
	}

	/**
	 * Shared decrypt pipeline for {@see self::decryptCryptoBox()} and
	 * {@see self::decryptCryptoBoxWithKeypair()}.
	 *
	 * Both public entries previously duplicated ~80 lines of identical
	 * validation + open + try/catch boilerplate, differing only in
	 * where they sourced the private key (current stored key vs. an
	 * historical-keyring entry). The shared helper takes the private
	 * key as an explicit parameter so the callers contribute only the
	 * key-source decision.
	 *
	 * @since 2.0.0
	 *
	 * @param string $encoded_and_encrypted_payload Base64-encoded ciphertext.
	 * @param string $hex_nonce                     Hex-encoded nonce.
	 * @param string $alice_public_key              Hex-encoded sender public key.
	 * @param string $bob_private_key_hex           Hex-encoded recipient private key.
	 *
	 * @return string|WP_Error Decrypted plaintext, or WP_Error with one of:
	 *                         sodium_not_exists, nonce_wrong_length,
	 *                         data_empty, data_malformated,
	 *                         decryption_failed, decryption_failed_typeerror,
	 *                         decryption_failed_sodiumexception.
	 */
	private function crypto_box_open_and_zeroize( $encoded_and_encrypted_payload, $hex_nonce, $alice_public_key, $bob_private_key_hex ) {

		if ( ! function_exists( 'sodium_crypto_box_open' ) ) {
			return new WP_Error( 'sodium_not_exists', 'Sodium isn\'t loaded. Upgrade to PHP 7.0 or WordPress 5.2 or higher.' );
		}

		// $hex_nonce is sensitive material; do not persist it.
		// Pinned by EnvelopeLogRedactionTest. The wrong-length
		// error path below already covers the only diagnostic an
		// intermediate debug log here previously provided.
		$bin_nonce = \sodium_hex2bin( $hex_nonce );

		if ( SODIUM_CRYPTO_BOX_NONCEBYTES !== strlen( $bin_nonce ) ) {
			return new WP_Error( 'nonce_wrong_length', sprintf( 'The nonce must be %d characters. Instead it\'s %d.', SODIUM_CRYPTO_BOX_NONCEBYTES, strlen( $bin_nonce ) ) );
		}

		try {
			if ( empty( $encoded_and_encrypted_payload ) ) {
				return new WP_Error( 'data_empty', 'Will not decrypt an empty payload.' );
			}

			// Inbound envelope payload from the SaaS, already base64-encoded by the client SDK.
			$encrypted_payload = base64_decode( $encoded_and_encrypted_payload ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( false === $encrypted_payload ) {
				// Data was not successfully base64_decode'd. Note: this
				// branch is rarely reached because base64_decode runs in
				// non-strict mode and tolerates most garbage input.
				return new WP_Error( 'data_malformated', 'Encrypted data must be base64 encoded.' );
			}

			$bob_private_key    = \sodium_hex2bin( $bob_private_key_hex );
			$alice_public_key   = \sodium_hex2bin( $alice_public_key );
			$crypto_box_keypair = \sodium_crypto_box_keypair_from_secretkey_and_publickey( $bob_private_key, $alice_public_key );
			$decrypted_payload  = \sodium_crypto_box_open( $encrypted_payload, $bin_nonce, $crypto_box_keypair );

			if ( false === $decrypted_payload ) {
				return new WP_Error( 'decryption_failed', 'Decryption failed.' );
			}

			return $decrypted_payload;
		} catch ( \TypeError $e ) {
			return new WP_Error(
				'decryption_failed_typeerror',
				sprintf( 'Error while decrypting envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \SodiumException $e ) {
			return new WP_Error(
				'decryption_failed_sodiumexception',
				sprintf( 'Error while decrypting envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		}
	}

	/**
	 * Returns an pair of values to verify identity.
	 *
	 * This pair acts as a signature, helping to verify that this site is indeed the sender of the data.
	 *
	 * @since 0.8.0
	 *
	 * @return  array|WP_Error  $identity or WP_Error if any issues
	 *    $identity = [
	 *        'nonce'  => (string)  A base64 encoded random string
	 *        'signed' => (string)  The `nonce` encrypted with this site's Private Key, also base64 encoded.
	 *    ]
	 */
	public function createIdentityNonce() {

		$unsigned_nonce = $this->generateNonce();

		if ( is_wp_error( $unsigned_nonce ) ) {
			return $unsigned_nonce;
		}

		$key = $this->getPrivateKey( 'sign_private_key' );

		if ( is_wp_error( $key ) ) {
			return $key;
		}

		$signed_nonce = $this->sign( $unsigned_nonce, $key );

		if ( is_wp_error( $signed_nonce ) ) {
			return $signed_nonce;
		}

		$verified = $this->verifySignature( $signed_nonce, $unsigned_nonce );

		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		$identity = array();
		// Binary-safe transport-encoding of the nonce / signed-nonce pair for the SaaS envelope.
		$identity['nonce']  = base64_encode( $unsigned_nonce ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$identity['signed'] = base64_encode( $signed_nonce ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		return $identity;
	}

	/**
	 * Verifies that a signature was produced by this site's signing key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $signed_nonce   Signature bytes returned by {@see Encryption::sign()}.
	 * @param string $unsigned_nonce Plaintext nonce that was signed.
	 *
	 * @return bool|WP_Error True if the signature validates, otherwise false. Returns WP_Error on issue.
	 */
	private function verifySignature( $signed_nonce, $unsigned_nonce ) {

		try {
			$sign_public_key = $this->getPublicKey( 'sign_public_key' );

			if ( is_wp_error( $sign_public_key ) ) {
				return $sign_public_key;
			}

			$message_valid = \sodium_crypto_sign_verify_detached(
				$signed_nonce,
				$unsigned_nonce,
				\sodium_hex2bin( $sign_public_key )
			);
			$this->log(
				'message_valid: ',
				__METHOD__,
				'debug',
				array(
					'message_valid' => $message_valid,
				)
			);

			if ( ! $message_valid ) {
				return new WP_Error( 'signature-failure', 'Signature will not pass verification' );
			}

			return $message_valid;
		} catch ( \SodiumException $e ) {
			return new WP_Error( 'sodium-error', $e->getMessage(), $e );
		} catch ( \TypeError $e ) {
			return new WP_Error( 'sodium-type-error', $e->getMessage(), $e );
		} catch ( \RangeException $e ) {
			return new WP_Error( 'sodium-range-error', $e->getMessage(), $e );
		}
	}

	/**
	 * Generates a cryptographic nonce .
	 *
	 * @since 1.0.0
	 *
	 * @uses \random_bytes()
	 *
	 * @return string|WP_Error  If generated, a nonce. Otherwise a WP_Error.
	 */
	private function generateNonce() {

		if ( ! function_exists( 'sodium_bin2hex' ) ) {
			return new WP_Error( 'sodium_not_exists', 'Sodium isn\'t loaded. Upgrade to PHP 7.0 or WordPress 5.2 or higher.' );
		}

		try {
			return \sodium_bin2hex( \random_bytes( SODIUM_CRYPTO_BOX_NONCEBYTES ) );
		} catch ( \SodiumException $e ) {
			return new WP_Error( 'sodium-error', $e->getMessage() );
		}
	}

	/**
	 * Signs a string using the Signature Private Key provided by the plugin/theme developers' server.
	 *
	 * @since 0.8.0
	 * @since 1.0.0
	 *
	 * @uses \sodium_crypto_sign_detached for signing.
	 *
	 * @param string $data Data to encrypt.
	 * @param string $key Key to use to encrypt the data.
	 *
	 * @return string|WP_Error  Signed value or WP_Error on failure.
	 */
	private function sign( $data, $key ) {

		try {
			if ( empty( $data ) || empty( $key ) ) {
				return new WP_Error( 'no_data', 'No data provided.' );
			}

			if ( ! function_exists( 'sodium_crypto_sign_detached' ) ) {
				return new WP_Error( 'sodium_not_exists', 'Sodium isn\'t loaded. Upgrade to PHP 7.0 or WordPress 5.2 or higher.' );
			}

			$signed = \sodium_crypto_sign_detached( $data, \sodium_hex2bin( $key ) );

			return $signed;
		} catch ( \SodiumException $e ) {
			return new WP_Error( 'sodium-error', $e->getMessage() );
		}
	}

	/**
	 * Resets the encryption keys.
	 *
	 * @return true|WP_Error
	 */
	public function resetKeys() {

		$reset = $this->generateKeys( true );

		if ( is_wp_error( $reset ) ) {
			return $reset;
		}

		return true;
	}

	/**
	 * Demotes a keypair into the historical keyring with a retention window.
	 *
	 * Called automatically by deleteKeys() and generateKeys() before the current
	 * keypair is overwritten. The historical keyring allows decryption of envelopes
	 * sealed with a recently-retired public key (e.g. during the client SDK's
	 * 10-minute transient cache window after a key rotation).
	 *
	 * @since 2.0.0
	 *
	 * @param \stdClass $keypair The keypair being retired.
	 *
	 * @return void
	 */
	private function demoteKeypair( $keypair ) {

		if ( ! isset( $keypair->public_key, $keypair->private_key ) ) {
			return;
		}

		$history = $this->getKeypairHistory();
		$now     = time();

		// Avoid duplicating an entry for the same public key (e.g. if deleteKeys()
		// and generateKeys() both fire in the same request).
		$fingerprint = hash( 'sha256', $keypair->public_key );
		foreach ( $history as $entry ) {
			if ( $entry['fingerprint'] === $fingerprint ) {
				return;
			}
		}

		try {
			$wrapped_private      = self::wrap_secret_hex( (string) $keypair->private_key );
			$wrapped_sign_private = isset( $keypair->sign_private_key )
				? self::wrap_secret_hex( (string) $keypair->sign_private_key )
				: '';
		} catch ( \SodiumException $e ) {
			$this->log(
				'Could not wrap retired keypair; skipping demote.',
				__METHOD__,
				'error',
				array( 'fingerprint' => $fingerprint )
			);
			return;
		}

		// Storage shape mirrors getKeypairHistory()'s in-memory contract but with
		// private fields stored as wrapped blobs. Field names use the same abbreviated
		// form as the state option so on-disk records do not advertise their content.
		$history[] = array(
			'public_key'      => $keypair->public_key,
			'sign_public_key' => isset( $keypair->sign_public_key ) ? $keypair->sign_public_key : '',
			'pk_wrapped'      => $wrapped_private,
			'spk_wrapped'     => $wrapped_sign_private,
			'fingerprint'     => $fingerprint,
			'retired_at'      => $now,
			'retention_until' => $now + self::KEYPAIR_RETENTION_SECONDS,
		);

		update_site_option( self::KEYPAIR_HISTORY_OPTION, wp_json_encode( $history ) );

		$this->log(
			'Keypair demoted to historical keyring.',
			__METHOD__,
			'info',
			array(
				'fingerprint'     => $fingerprint,
				'retention_until' => $now + self::KEYPAIR_RETENTION_SECONDS,
				'history_count'   => count( $history ),
			)
		);
	}

	/**
	 * Returns the historical keypair list, pruning any expired entries.
	 *
	 * Performs lazy cleanup: if any entries have passed their retention_until
	 * timestamp, they are filtered out and the option is updated.
	 *
	 * @since 2.0.0
	 *
	 * @return array Array of historical keypair arrays. May be empty.
	 */
	public function getKeypairHistory() {

		$raw = get_site_option( self::KEYPAIR_HISTORY_OPTION, '' );

		if ( empty( $raw ) ) {
			return array();
		}

		$history = json_decode( $raw, true );

		if ( ! is_array( $history ) ) {
			return array();
		}

		$now    = time();
		$pruned = false;

		$history = array_filter(
			$history,
			function ( $entry ) use ( $now, &$pruned ) {
				if ( ! isset( $entry['retention_until'] ) || $entry['retention_until'] <= $now ) {
					$pruned = true;
					return false;
				}
				return true;
			}
		);

		// Unwrap private bits for callers. On-disk entries always carry
		// pk_wrapped / spk_wrapped; surface the unwrapped hex in-memory
		// so callers don't have to know about the wrap step.
		$out = array();
		foreach ( $history as $entry ) {
			$unwrapped_private      = self::unwrap_secret_hex( (string) ( $entry['pk_wrapped'] ?? '' ) );
			$unwrapped_sign_private = isset( $entry['spk_wrapped'] ) && '' !== $entry['spk_wrapped']
				? self::unwrap_secret_hex( (string) $entry['spk_wrapped'] )
				: '';

			if ( null === $unwrapped_private || ( ! empty( $entry['spk_wrapped'] ) && null === $unwrapped_sign_private ) ) {
				// Tamper or wrap-key change. Drop this entry — same posture as
				// loadStoredKeys(): refuse to silently recover.
				$this->log(
					'Could not unwrap a historical keypair entry; dropping.',
					__METHOD__,
					'error',
					array( 'fingerprint' => isset( $entry['fingerprint'] ) ? $entry['fingerprint'] : null )
				);
				$detail = __(
					'TrustedLogin removed older encryption entries that could no longer be unlocked. Your current keys may be affected by the same cause — most likely a change to AUTH_KEY in wp-config.php.',
					'trustedlogin-connector'
				);
				$this->notifyAdminOfKeyTamper( 'keys_unwrap_failed', $detail );
				$pruned = true;
				continue;
			}

			$entry['private_key']      = $unwrapped_private;
			$entry['sign_private_key'] = is_string( $unwrapped_sign_private ) ? $unwrapped_sign_private : '';

			$out[] = $entry;
		}

		if ( $pruned ) {
			$rewritten = $this->rewriteHistoryForStorage( $out );
			if ( empty( $rewritten ) ) {
				delete_site_option( self::KEYPAIR_HISTORY_OPTION );
			} else {
				update_site_option( self::KEYPAIR_HISTORY_OPTION, wp_json_encode( array_values( $rewritten ) ) );
			}
		}

		return array_values( $out );
	}

	/**
	 * Converts an in-memory history array (plaintext private fields) back to the
	 * on-disk wrapped shape. Used by {@see Encryption::getKeypairHistory()} when
	 * pruning expired entries or rewrapping legacy plaintext rows.
	 *
	 * @since 2.0.0
	 *
	 * @param array $history Array of history entries with plaintext private fields.
	 *
	 * @return array History entries ready for `wp_json_encode()` storage.
	 */
	private function rewriteHistoryForStorage( array $history ) {

		$out = array();
		foreach ( $history as $entry ) {
			$private_hex      = isset( $entry['private_key'] ) ? (string) $entry['private_key'] : '';
			$sign_private_hex = isset( $entry['sign_private_key'] ) ? (string) $entry['sign_private_key'] : '';

			try {
				$pk_wrapped  = '' !== $private_hex ? self::wrap_secret_hex( $private_hex ) : '';
				$spk_wrapped = '' !== $sign_private_hex ? self::wrap_secret_hex( $sign_private_hex ) : '';
			} catch ( \SodiumException $e ) {
				// If we can't wrap, skip the entry rather than write plaintext back.
				continue;
			}

			$entry['private_key']      = '';
			$entry['sign_private_key'] = '';
			$entry['pk_wrapped']       = $pk_wrapped;
			$entry['spk_wrapped']      = $spk_wrapped;

			$out[] = $entry;
		}
		return $out;
	}

	/**
	 * Side-effect-only entry point for the daily prune cron.
	 *
	 * `getKeypairHistory()` lazy-prunes expired entries as a side effect of
	 * its read, but wiring a getter to `add_action()` is brittle: WordPress
	 * discards the return value and a future refactor that splits read
	 * from prune would silently turn the cron into a no-op. This wrapper
	 * makes the prune intent explicit at the call site.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function pruneKeypairHistory() {
		$this->getKeypairHistory();
	}

	/**
	 * Looks up a keypair by its public-key fingerprint.
	 *
	 * Checks the current keypair first, then walks the historical keyring.
	 *
	 * @since 2.0.0
	 *
	 * @param string $fingerprint sha256 hex of the public key.
	 *
	 * @return \stdClass|null Keypair object (same shape as getKeys() returns) or null if not found.
	 */
	public function findKeypairByFingerprint( $fingerprint ) {

		// Try current keypair first.
		$current = $this->getKeys( false );

		if ( $current && ! is_wp_error( $current ) && isset( $current->public_key ) ) {
			if ( hash( 'sha256', $current->public_key ) === $fingerprint ) {
				return $current;
			}
		}

		// Walk history.
		foreach ( $this->getKeypairHistory() as $entry ) {
			if ( isset( $entry['fingerprint'] ) && $entry['fingerprint'] === $fingerprint ) {
				return (object) array(
					'public_key'       => $entry['public_key'],
					'private_key'      => $entry['private_key'],
					'sign_public_key'  => isset( $entry['sign_public_key'] ) ? $entry['sign_public_key'] : '',
					'sign_private_key' => isset( $entry['sign_private_key'] ) ? $entry['sign_private_key'] : '',
				);
			}
		}

		return null;
	}

	/**
	 * Decrypts a sodium_crypto_box payload using a specific keypair.
	 *
	 * Same as decryptCryptoBox() but accepts the private key directly instead
	 * of reading from getPrivateKey(). Used when decrypting an envelope that
	 * was sealed with a recently-retired public key.
	 *
	 * @since 2.0.0
	 *
	 * @param string $encoded_and_encrypted_payload Base64-encoded ciphertext.
	 * @param string $hex_nonce                     Hex-encoded nonce.
	 * @param string $alice_public_key              Hex-encoded sender public key.
	 * @param string $bob_private_key_hex           Hex-encoded private key to use for decryption.
	 *
	 * @return string|WP_Error Decrypted plaintext or WP_Error.
	 */
	public function decryptCryptoBoxWithKeypair( $encoded_and_encrypted_payload, $hex_nonce, $alice_public_key, $bob_private_key_hex ) {

		return $this->crypto_box_open_and_zeroize(
			$encoded_and_encrypted_payload,
			$hex_nonce,
			$alice_public_key,
			$bob_private_key_hex
		);
	}

	/**
	 * Deletes the historical keypair list.
	 *
	 * Called during full uninstall to wipe all key material.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function deleteKeypairHistory() {
		delete_site_option( self::KEYPAIR_HISTORY_OPTION );
	}

	/**
	 * Returns a hex-encoded BLAKE2b digest of the given input.
	 *
	 * Used to derive the support-login endpoint segment from
	 * `siteurl . identifier`. Not a cryptographic primitive in its own right
	 * — collision resistance is the only relied-upon property.
	 *
	 * @since 0.8.0
	 *
	 * @param string $input Plaintext input to hash.
	 *
	 * @return string|WP_Error Hex-encoded digest on success, WP_Error on failure.
	 */
	public static function hash( $input ) {

		if ( ! function_exists( 'sodium_crypto_generichash' ) ) {
			return new WP_Error( 'sodium_crypto_generichash_not_available', 'sodium_crypto_generichash not available' );
		}

		try {
			$hash_bin = sodium_crypto_generichash( $input, '', 16 );
			$hash     = sodium_bin2hex( $hash_bin );
		} catch ( \SodiumException $e ) {
			return new WP_Error(
				'encryption_failed_generichash',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \Throwable $e ) {
			return new WP_Error(
				'encryption_failed_generichash_typeerror',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		}

		return $hash;
	}
}

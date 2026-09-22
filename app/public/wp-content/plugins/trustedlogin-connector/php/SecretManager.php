<?php
/**
 * Class SecretManager
 *
 * Manages the lifecycle of one-time secrets stored in wp_options via Utils transients.
 * Each secret is a symmetric-encrypted blob (sodium_crypto_secretbox) where the
 * decryption key lives only in the URL fragment — the server never sees it.
 *
 * @package TrustedLogin\Vendor
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\Logger;

/**
 * One-time secret CRUD manager.
 *
 * @since 2.0.0
 */
class SecretManager {

	use Logger;

	/**
	 * Prefix for the wp_options key.
	 *
	 * @var string
	 */
	const OPTION_PREFIX = 'tl_secret_';

	/**
	 * HMAC versioning for future-proofing.
	 *
	 * @var int
	 */
	const HMAC_VERSION = 1;

	/**
	 * Maximum number of passphrase verification attempts before permanent lockout.
	 *
	 * After this many failures, the secret is permanently locked (not destroyed).
	 * The sender can still burn it manually.
	 *
	 * @var int
	 */
	const MAX_PASSPHRASE_ATTEMPTS = 10;

	/**
	 * Base delay in seconds for the exponential backoff on wrong passphrase.
	 *
	 * Delay doubles each attempt: 1s, 2s, 4s, 8s, 16s, 32s, 64s, 128s, 256s, 512s.
	 *
	 * @var int
	 */
	const PASSPHRASE_BASE_DELAY = 1;

	/**
	 * How long (seconds) an in_flight record lives before the burn cron fires.
	 *
	 * @var int
	 */
	const GRACE_PERIOD_SECONDS = 30;

	/**
	 * Default time-to-live in seconds (24 hours).
	 *
	 * @var int
	 */
	const DEFAULT_TTL = 86400;

	/**
	 * Maximum time-to-live in seconds (30 days).
	 *
	 * @var int
	 */
	const MAX_TTL = 2592000;

	/**
	 * Number of random bytes used to generate a token (hex-encoded = 2x this length).
	 *
	 * 16 bytes → 32 hex chars → 128 bits of entropy.
	 *
	 * @var int
	 */
	const TOKEN_BYTES = 16;

	/**
	 * Length of a hex-encoded token string (TOKEN_BYTES * 2).
	 *
	 * @var int
	 */
	const TOKEN_LENGTH = 32;

	/**
	 * Number of random bytes used to generate a reveal nonce.
	 *
	 * @var int
	 */
	const NONCE_BYTES = 16;

	/**
	 * Maximum passphrase length in characters (prevents Argon2 DoS).
	 *
	 * @var int
	 */
	const MAX_PASSPHRASE_LENGTH = 256;

	/**
	 * Length of the secretbox key in bytes (SODIUM_CRYPTO_SECRETBOX_KEYBYTES = 32).
	 *
	 * @var int
	 */
	const KEY_BYTES = SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

	/**
	 * Creates a new one-time secret.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args {
	 *     Arguments for creating a secret.
	 *
	 *     @type string $plaintext       Required. The plaintext value to encrypt.
	 *     @type int    $ttl             Optional. Time-to-live in seconds. Default 86400.
	 *     @type string $passphrase      Optional. Passphrase to protect the secret.
	 *     @type string $memo            Optional. A note about the secret.
	 *     @type string $team_account_id Optional. Associated team account ID.
	 * }
	 *
	 * @return array|null {
	 *     On success, an array with secret metadata. Null on failure.
	 *
	 *     @type string $token      The public token for the secret URL.
	 *     @type string $key        Hex-encoded decryption key (for the URL fragment).
	 *     @type int    $expires_at Unix timestamp when the secret expires.
	 * }
	 */
	public function create( array $args ) {

		if ( empty( $args['plaintext'] ) || ! is_string( $args['plaintext'] ) ) {
			$this->log( 'Cannot create secret: plaintext is required.', __METHOD__, 'error' );
			return null;
		}

		$plaintext = $args['plaintext'];

		// Clamp TTL between 1 hour and 30 days.
		$ttl = max( 3600, min( $args['ttl'] ?? self::DEFAULT_TTL, self::MAX_TTL ) );

		try {
			$key        = \sodium_crypto_secretbox_keygen();
			$nonce      = \random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = \sodium_crypto_secretbox( $plaintext, $nonce, $key );
			$token      = bin2hex( \random_bytes( self::TOKEN_BYTES ) );
		} catch ( \Exception $e ) {
			$this->log( 'Sodium encryption failed: ' . $e->getMessage(), __METHOD__, 'error' );
			return null;
		}

		$passphrase_hash = null;

		if ( ! empty( $args['passphrase'] ) && is_string( $args['passphrase'] ) ) {
			if ( strlen( $args['passphrase'] ) > self::MAX_PASSPHRASE_LENGTH ) {
				$this->log( 'Passphrase exceeds maximum length.', __METHOD__, 'error' );
				return null;
			}
			try {
				$passphrase_hash = \sodium_crypto_pwhash_str(
					$args['passphrase'],
					SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
					SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
				);
			} catch ( \SodiumException $e ) {
				$this->log( 'Passphrase hashing failed: ' . $e->getMessage(), __METHOD__, 'error' );
				return null;
			}
		}

		$now        = time();
		$expires_at = $now + $ttl;

		$current_user = wp_get_current_user();
		// `display_name` is user-editable on the Profile screen; sanitize
		// each component before composing so render sites don't have to
		// trust the stored value.
		$creator_label = sanitize_text_field( (string) $current_user->display_name )
			. ' (' . sanitize_email( (string) $current_user->user_email ) . ')';

		$record = array(
			'nonce'               => \sodium_bin2hex( $nonce ),
			'ciphertext'          => base64_encode( $ciphertext ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			// One-way fingerprint of the fragment key. Used on /prepare so
			// anyone holding only the URL *path* (access logs, proxies,
			// browser history) can't stamp a reveal_nonce onto the record
			// and lock/destroy a single-use secret.
			'key_hash'            => hash( 'sha256', $key ),
			'burn_after_reading'  => (bool) ( $args['burn_after_reading'] ?? true ),
			// Reusable-link only. When false, the recipient's reveal
			// page renders without the "Destroy this secret" button, so
			// the secret stays alive for everyone with the link until
			// its TTL expires. Creator can still destroy from their own
			// dashboard. Ignored for burn_after_reading=true secrets.
			'viewer_can_destroy'  => (bool) ( $args['viewer_can_destroy'] ?? true ),
			'in_flight'           => false,
			'previewed_at'        => null,
			'revealed_at'         => null,
			'creator_user_id'     => get_current_user_id(),
			'creator_label'       => $creator_label,
			'team_account_id'     => $args['team_account_id'] ?? null,
			'memo'                => $args['memo'] ?? null,
			'created_at'          => $now,
			'expires_at'          => $expires_at,
			'passphrase_hash'     => $passphrase_hash,
			'passphrase_attempts' => 0,
		);

		if ( ! $this->write_record( $token, $record ) ) {
			$this->log( 'Failed to write secret record.', __METHOD__, 'error' );
			return null;
		}

		$this->log(
			'Secret created.',
			__METHOD__,
			'info',
			array(
				'token_hash'     => hash( 'sha256', $token ),
				'expires_at'     => $expires_at,
				'has_passphrase' => ! empty( $passphrase_hash ),
			)
		);

		return array(
			'token'      => $token,
			'key'        => \sodium_bin2hex( $key ),
			'expires_at' => $expires_at,
		);
	}

	/**
	 * Atomic first-GET-wins fetch with row-level locking.
	 *
	 * Uses a database transaction with SELECT ... FOR UPDATE to ensure
	 * only one consumer can claim the secret.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token The secret token.
	 *
	 * @return array|null Record data on success, null on failure.
	 */
	public function fetch_and_lock( string $token ) {
		global $wpdb;

		$option_name = self::OPTION_PREFIX . $token;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Row-level lock via SELECT ... FOR UPDATE.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT option_value FROM `$wpdb->options` WHERE option_name = %s LIMIT 1 FOR UPDATE",
					$option_name
				)
			);

			if ( ! is_object( $row ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				$this->log( 'Secret not found during fetch_and_lock.', __METHOD__, 'warning', array( 'token_hash' => hash( 'sha256', $token ) ) );
				return null;
			}

			// IMPORTANT: Use the row we already hold under the FOR UPDATE lock.
			// Do NOT call verify_and_unwrap() here — that would issue a separate
			// SELECT outside the lock, creating a TOCTOU race under concurrency.
			$record = $this->verify_and_unwrap_from_row( $row->option_value, $token );

			if ( null === $record ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				return null;
			}

			// Check: not already in flight.
			if ( ! empty( $record['in_flight'] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				$this->log( 'Secret already in flight.', __METHOD__, 'warning', array( 'token_hash' => hash( 'sha256', $token ) ) );
				return null;
			}

			// Check: not already revealed.
			if ( ! empty( $record['revealed_at'] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				$this->log( 'Secret already revealed.', __METHOD__, 'warning', array( 'token_hash' => hash( 'sha256', $token ) ) );
				return null;
			}

			// Check: not expired.
			if ( time() > $record['expires_at'] ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				$this->log( 'Secret expired.', __METHOD__, 'warning', array( 'token_hash' => hash( 'sha256', $token ) ) );
				return null;
			}

			// Multi-view secrets: skip the in_flight lock and burn cron.
			// The secret stays available until the TTL expires or is manually destroyed.
			$is_burn_after_reading = $record['burn_after_reading'] ?? true;

			if ( $is_burn_after_reading ) {
				// Mark as in-flight and record preview timestamp.
				$record['in_flight']    = true;
				$record['previewed_at'] = time();

				if ( ! $this->write_record( $token, $record ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query( 'ROLLBACK' );
					$this->log( 'Failed to write in_flight record.', __METHOD__, 'error', array( 'token_hash' => hash( 'sha256', $token ) ) );
					return null;
				}
			} elseif ( null === $record['previewed_at'] ) {
				// Multi-view: just record the view timestamp without locking.
				$record['previewed_at'] = time();
				if ( ! $this->write_record( $token, $record ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query( 'ROLLBACK' );
					$this->log( 'Failed to write previewed_at for multi-view record.', __METHOD__, 'error', array( 'token_hash' => hash( 'sha256', $token ) ) );
					return null;
				}
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'COMMIT' );

			if ( $is_burn_after_reading ) {
				// Schedule one-shot burn cron (only for single-view secrets).
				wp_schedule_single_event(
					time() + self::GRACE_PERIOD_SECONDS,
					'tl_secret_burn',
					array( $token )
				);
			}

			$this->log( 'Secret locked for reveal.', __METHOD__, 'info', array( 'token_hash' => hash( 'sha256', $token ) ) );

			return array(
				'nonce'               => $record['nonce'],
				'ciphertext'          => $record['ciphertext'],
				'passphrase_required' => ! empty( $record['passphrase_hash'] ),
				'burn_after_reading'  => $is_burn_after_reading,
				'expires_at'          => $record['expires_at'],
			);
		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			$this->log( 'Exception in fetch_and_lock: ' . $e->getMessage(), __METHOD__, 'error', array( 'token_hash' => hash( 'sha256', $token ) ) );
			return null;
		}
	}

	/**
	 * Destroys a secret record.
	 *
	 * @since 2.0.0
	 *
	 * @param string     $token  The secret token.
	 * @param string     $reason The reason for destruction. One of 'burned', 'revealed', 'passphrase_lockout', 'tampered'.
	 * @param array|null $record Optional pre-fetched record to avoid a
	 *                           re-SELECT after the row lock has been
	 *                           released. When null, the method reads
	 *                           the record itself.
	 *
	 * @return void
	 */
	public function destroy( string $token, string $reason = 'burned', ?array $record = null ) {

		if ( 'revealed' === $reason ) {
			// Prefer the caller's already-held record so we don't re-
			// SELECT after the lock has been released. A fresh read
			// here can race the grace-period cron and lose `revealed_at`.
			if ( null === $record ) {
				$record = $this->verify_and_unwrap( $token );
			}

			if ( is_array( $record ) ) {
				$record['revealed_at'] = time();
				$this->write_record( $token, $record );
			}
		}

		Utils::delete_transient( self::OPTION_PREFIX . $token );

		wp_clear_scheduled_hook( 'tl_secret_burn', array( $token ) );

		$this->log(
			'Secret destroyed.',
			__METHOD__,
			'info',
			array(
				'token_hash' => hash( 'sha256', $token ),
				'reason'     => $reason,
			)
		);
	}

	/**
	 * Verifies a passphrase against the stored hash.
	 *
	 * Uses exponential backoff on failures: each wrong guess doubles the delay
	 * before the next attempt is accepted (1s, 2s, 4s, 8s, 16s…). After
	 * MAX_PASSPHRASE_ATTEMPTS failures the secret is permanently locked.
	 * The secret is never destroyed by wrong guesses — only the sender can
	 * burn it. This prevents an attacker from DoS-ing a passphrase-protected
	 * secret by submitting 5 wrong guesses.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token      The secret token.
	 * @param string $passphrase The passphrase to verify.
	 *
	 * @return array|null {
	 *     Result array on success/failure, null if record not found.
	 *
	 *     @type bool $valid              Whether the passphrase was correct.
	 *     @type int  $attempts_remaining Number of attempts left before permanent lockout.
	 *     @type int  $retry_after        Seconds until the next attempt is accepted (0 if ready).
	 * }
	 */
	public function verify_passphrase( string $token, string $passphrase ) {
		global $wpdb;

		$option_name = self::OPTION_PREFIX . $token;
		$token_hash  = hash( 'sha256', $token );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		try {
			// FOR UPDATE serializes concurrent guesses; without it, two
			// parallel POSTs both read attempts=N and write N+1, bypassing
			// the attempt cap.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT option_value FROM `$wpdb->options` WHERE option_name = %s LIMIT 1 FOR UPDATE",
					$option_name
				)
			);

			if ( ! is_object( $row ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				$this->log( 'Cannot verify passphrase: record not found.', __METHOD__, 'warning', array( 'token_hash' => $token_hash ) );
				return null;
			}

			$record = $this->verify_and_unwrap_from_row( $row->option_value, $token );

			if ( null === $record ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				return null;
			}

			// Enforce the record's intended TTL here as well as in
			// fetch_and_lock(). Otherwise a passphrase-protected secret
			// whose `expires_at` has passed could still be brute-forced
			// against — the transient layer's storage TTL is a separate
			// clock that can drift, and verify_passphrase MUST honour the
			// authoritative `expires_at` field on the record.
			if ( isset( $record['expires_at'] ) && time() > (int) $record['expires_at'] ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				$this->log( 'Cannot verify passphrase: record expired.', __METHOD__, 'warning', array( 'token_hash' => $token_hash ) );
				return null;
			}

			if ( empty( $record['passphrase_hash'] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				$this->log( 'Passphrase verification attempted on record without passphrase.', __METHOD__, 'warning', array( 'token_hash' => $token_hash ) );
				return null;
			}

			$attempts = (int) ( $record['passphrase_attempts'] ?? 0 );

			// Permanently locked after MAX_PASSPHRASE_ATTEMPTS failures.
			if ( $attempts >= self::MAX_PASSPHRASE_ATTEMPTS ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'COMMIT' );
				return array(
					'valid'              => false,
					'attempts_remaining' => 0,
					'retry_after'        => 0,
					'locked'             => true,
				);
			}

			// Exponential backoff: check if the caller must wait.
			$locked_until = (int) ( $record['passphrase_locked_until'] ?? 0 );
			$now          = time();

			if ( $locked_until > $now ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'COMMIT' );
				return array(
					'valid'              => false,
					'attempts_remaining' => self::MAX_PASSPHRASE_ATTEMPTS - $attempts,
					'retry_after'        => $locked_until - $now,
				);
			}

			// Cap passphrase length to prevent Argon2 DoS amplification.
			if ( strlen( $passphrase ) > self::MAX_PASSPHRASE_LENGTH ) {
				$result = $this->record_failed_attempt( $token, $record );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Argument is a literal-string ternary ('ROLLBACK'/'COMMIT'); $result is in the comparison only.
				$wpdb->query( null === $result ? 'ROLLBACK' : 'COMMIT' );
				return $result;
			}

			// sodium_crypto_pwhash_str_verify() throws SodiumException when
			// the stored hash is malformed/corrupted (e.g. truncated by a
			// past write bug, or rewritten by a different Argon variant).
			// Treat any throw as a failed attempt — same outcome the user
			// would have if their passphrase was wrong — so a poisoned
			// record can't crash the verify endpoint and leak via 500s.
			try {
				$valid = \sodium_crypto_pwhash_str_verify( $record['passphrase_hash'], $passphrase );
			} catch ( \SodiumException $e ) {
				$this->log(
					'Passphrase verify threw: ' . $e->getMessage(),
					__METHOD__,
					'error',
					array( 'token_hash' => $token_hash )
				);
				$result = $this->record_failed_attempt( $token, $record );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Argument is a literal-string ternary ('ROLLBACK'/'COMMIT'); $result is in the comparison only.
				$wpdb->query( null === $result ? 'ROLLBACK' : 'COMMIT' );
				return $result;
			}

			if ( $valid ) {
				// Reset attempt counter on success.
				$record['passphrase_attempts']     = 0;
				$record['passphrase_locked_until'] = 0;
				$this->write_record( $token, $record );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'COMMIT' );

				$this->log( 'Passphrase verified successfully.', __METHOD__, 'info', array( 'token_hash' => $token_hash ) );

				return array(
					'valid'              => true,
					'attempts_remaining' => self::MAX_PASSPHRASE_ATTEMPTS,
					'retry_after'        => 0,
				);
			}

			$result = $this->record_failed_attempt( $token, $record );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Argument is a literal-string ternary ('ROLLBACK'/'COMMIT'); $result is in the comparison only.
			$wpdb->query( null === $result ? 'ROLLBACK' : 'COMMIT' );
			return $result;
		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			$this->log( 'Exception in verify_passphrase: ' . $e->getMessage(), __METHOD__, 'error', array( 'token_hash' => $token_hash ) );
			return null;
		}
	}

	/**
	 * Records a failed passphrase attempt and computes the exponential backoff.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token  The secret token.
	 * @param array  $record The current record.
	 *
	 * @return array|null Result array, or null if write failed.
	 */
	private function record_failed_attempt( string $token, array $record ) {

		$now                           = time();
		$prior_attempts                = (int) ( $record['passphrase_attempts'] ?? 0 );
		$attempts                      = $prior_attempts + 1;
		$record['passphrase_attempts'] = $attempts;

		// Exponential delay: 1s, 2s, 4s, 8s, 16s, 32s, 64s, 128s, 256s, 512s.
		$delay                             = (int) ( self::PASSPHRASE_BASE_DELAY * pow( 2, $attempts - 1 ) );
		$record['passphrase_locked_until'] = $now + $delay;

		if ( ! $this->write_record( $token, $record ) ) {
			$this->log( 'Failed to persist attempt increment.', __METHOD__, 'error', array( 'token_hash' => hash( 'sha256', $token ) ) );
			return null;
		}

		$attempts_remaining = self::MAX_PASSPHRASE_ATTEMPTS - $attempts;

		$this->log(
			'Passphrase verification failed.',
			__METHOD__,
			'warning',
			array(
				'token_hash'         => hash( 'sha256', $token ),
				'attempts_remaining' => $attempts_remaining,
				'retry_after'        => $delay,
			)
		);

		$result = array(
			'valid'              => false,
			'attempts_remaining' => max( 0, $attempts_remaining ),
			'retry_after'        => $delay,
		);

		if ( $attempts >= self::MAX_PASSPHRASE_ATTEMPTS ) {
			$result['locked'] = true;
		}

		return $result;
	}

	/**
	 * HMAC-wraps a record and stores it as a transient.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token  The secret token.
	 * @param array  $record The record data to store.
	 *
	 * @return bool True if the value was set, false otherwise.
	 */
	public function write_record( string $token, array $record ) {

		$payload = wp_json_encode( $record );

		if ( false === $payload ) {
			$this->log( 'Failed to JSON-encode record.', __METHOD__, 'error', array( 'token_hash' => hash( 'sha256', $token ) ) );
			return false;
		}

		$token_hash = hash( 'sha256', $token );
		$hmac       = hash_hmac( 'sha256', $payload . '|' . $token_hash, self::hmac_key() );

		$stored = array(
			'payload' => $payload,
			'hmac'    => $hmac,
			'v'       => self::HMAC_VERSION,
		);

		// TTL = remaining time to expires_at (not original interval),
		// so state-mutating writes don't drift the transient expiry
		// past the canonical expires_at. Floor at 60s for clock-edge.
		$ttl = max( 60, $record['expires_at'] - time() );

		return Utils::set_transient( self::OPTION_PREFIX . $token, $stored, $ttl );
	}

	/**
	 * Verifies and unwraps a record from a raw option_value string.
	 *
	 * Used by fetch_and_lock() to process the row already held under a
	 * FOR UPDATE lock, avoiding a second SELECT.
	 *
	 * @since 2.0.0
	 *
	 * @param string $raw_option_value The raw option_value from the DB row.
	 * @param string $token            The secret token.
	 *
	 * @return array|null The record array, or null if invalid/tampered.
	 */
	public function verify_and_unwrap_from_row( string $raw_option_value, string $token ) {

		$data = maybe_unserialize( $raw_option_value );

		if ( ! is_array( $data ) || ! isset( $data['value'] ) ) {
			return null;
		}

		// The Utils transient format wraps the actual value in ['value' => ..., 'expiration' => ...].
		$stored = $data['value'];

		if ( ! is_array( $stored ) ) {
			return null;
		}

		// Check expiration (same logic as Utils::retrieve_value_and_maybe_expire_transient).
		if ( isset( $data['expiration'] ) && 0 !== $data['expiration'] && time() > $data['expiration'] ) {
			$this->destroy( $token, 'expired' );
			return null;
		}

		return $this->verify_hmac_payload( $stored, $token );
	}

	/**
	 * Verify the HMAC signature and unwrap the stored secret record.
	 *
	 * @param string $token The plaintext access token to look up and verify.
	 * @return array|null Decoded record array on success, null on failure.
	 */
	public function verify_and_unwrap( string $token ) {

		$stored = Utils::get_transient( self::OPTION_PREFIX . $token );

		if ( empty( $stored ) || ! is_array( $stored ) ) {
			return null;
		}

		return $this->verify_hmac_payload( $stored, $token );
	}

	/**
	 * Verifies the HMAC over a stored secret envelope and decodes the
	 * inner JSON payload. Shared by {@see self::verify_and_unwrap()} and
	 * {@see self::verify_and_unwrap_from_row()} so both paths handle
	 * tamper detection and payload decoding identically.
	 *
	 * On HMAC mismatch the record is destroyed (`tampered` reason),
	 * the admin is notified, and the method returns null. On a
	 * payload that doesn't decode as a JSON object, the method logs
	 * and returns null without destroying the record (the bytes are
	 * still HMAC-valid; the decode failure is a programming bug, not
	 * a tamper signal).
	 *
	 * @since 2.0.0
	 *
	 * @param array  $stored The Utils-wrapped record value with `payload`
	 *                       and `hmac` keys.
	 * @param string $token  The plaintext token; used to recompute the
	 *                       per-record HMAC and to destroy the record on
	 *                       tamper.
	 *
	 * @return array|null Decoded record on success, null on missing
	 *                    keys / HMAC mismatch / non-JSON payload.
	 */
	private function verify_hmac_payload( array $stored, string $token ) {

		if ( ! isset( $stored['payload'], $stored['hmac'] ) ) {
			return null;
		}

		$token_hash    = hash( 'sha256', $token );
		$expected_hmac = hash_hmac( 'sha256', $stored['payload'] . '|' . $token_hash, self::hmac_key() );

		if ( ! hash_equals( $expected_hmac, $stored['hmac'] ) ) {
			$this->log( 'HMAC mismatch — record tampered.', __METHOD__, 'error', array( 'token_hash' => $token_hash ) );
			self::notifyAdminOfHmacFailure();
			$this->destroy( $token, 'tampered' );
			return null;
		}

		$record = json_decode( $stored['payload'], true );

		if ( ! is_array( $record ) ) {
			$this->log( 'Failed to decode record payload.', __METHOD__, 'error', array( 'token_hash' => $token_hash ) );
			return null;
		}

		return $record;
	}

	/**
	 * Returns the HMAC key used for record integrity verification.
	 *
	 * Prefers the TRUSTEDLOGIN_SECRETS_HMAC_KEY constant if defined,
	 * otherwise derives a key from AUTH_KEY.
	 *
	 * @since 2.0.0
	 *
	 * @return string The HMAC key.
	 */
	public static function hmac_key() {

		if ( defined( 'TRUSTEDLOGIN_SECRETS_HMAC_KEY' ) ) {
			return TRUSTEDLOGIN_SECRETS_HMAC_KEY;
		}

		return hash_hmac( 'sha256', 'tl-secrets-v1', AUTH_KEY );
	}

	/**
	 * Emails the site admin once per 6h when secret HMAC verification
	 * fails. Single-row tampering and AUTH_KEY rotation produce the same
	 * signal here, but the operational outcome is identical: rows are
	 * being destroyed and the admin needs to know. Without this notice,
	 * an AUTH_KEY rotation by a security plugin would silently wipe
	 * every passphrase-protected and burn-after-reading secret across
	 * the site, mirroring the encryption-key rotation gap that
	 * {@see Encryption::notifyAdminOfKeyTamper()} already closes.
	 *
	 * Rate-limited via {@see Utils::set_transient()} so a bulk-destroy
	 * (multiple secrets fail HMAC in the same request) produces a
	 * single email, not one per row.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private static function notifyAdminOfHmacFailure() {
		$reason_code = 'secrets_hmac_failed';

		/**
		 * Filters whether to email the site administrator on secret-HMAC
		 * verification failures. Same contract as the encryption-tamper
		 * filter — return false to suppress.
		 *
		 * @since 2.0.0
		 *
		 * @param bool   $send         Whether to send the email. Default true.
		 * @param string $reason_code  The internal error code. Always `secrets_hmac_failed`.
		 */
		$send = apply_filters( 'trustedlogin/connector/secrets/notify-admin-on-hmac-failure', true, $reason_code );

		if ( ! $send ) {
			return;
		}

		$transient_key = 'tlc_secret_alert_' . md5( $reason_code );

		if ( false !== Utils::get_transient( $transient_key ) ) {
			return;
		}

		Utils::set_transient( $transient_key, time(), 6 * HOUR_IN_SECONDS );

		$admin_email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );

		if ( ! $admin_email || ! is_email( $admin_email ) ) {
			return;
		}

		$site_name    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$settings_url = admin_url( 'admin.php?page=trustedlogin-secrets' );

		$subject = sprintf(
			/* translators: %s: site name. */
			__( '[%s] TrustedLogin: stored secrets could not be verified', 'trustedlogin-connector' ),
			$site_name
		);

		$body_lines = array(
			sprintf(
				/* translators: %s: site URL. */
				__( 'TrustedLogin Connector on %s detected an HMAC verification failure on one or more stored secrets.', 'trustedlogin-connector' ),
				home_url()
			),
			'',
			__( 'The most common cause is that AUTH_KEY in wp-config.php was changed (for example, by a security plugin rotating salts). A less common cause is per-row tampering.', 'trustedlogin-connector' ),
			'',
			__( 'Affected secrets have been removed. New secrets created after the change will work normally.', 'trustedlogin-connector' ),
			'',
			__( 'You won’t get another copy of this notice for at least 6 hours.', 'trustedlogin-connector' ),
			'',
			sprintf(
				/* translators: %s: settings URL. */
				__( 'Secrets page: %s', 'trustedlogin-connector' ),
				$settings_url
			),
		);

		wp_mail( $admin_email, $subject, implode( "\n", $body_lines ) );
	}

	/**
	 * Public read-only accessor for a secret record (no locking).
	 *
	 * @since 2.0.0
	 *
	 * @param string $token The secret token.
	 *
	 * @return array|null The record array, or null if not found/invalid.
	 */
	public function get_record( string $token ) {
		return $this->verify_and_unwrap( $token );
	}

	/**
	 * Checks whether a user is the creator of a secret.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token   The secret token.
	 * @param int    $user_id The WordPress user ID to check.
	 *
	 * @return bool True if the user_id matches the creator, false otherwise.
	 */
	public function is_creator( string $token, int $user_id ) {

		$record = $this->verify_and_unwrap( $token );

		if ( null === $record ) {
			return false;
		}

		return isset( $record['creator_user_id'] ) && (int) $record['creator_user_id'] === $user_id;
	}
}

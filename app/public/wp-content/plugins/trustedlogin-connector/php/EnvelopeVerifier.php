<?php
/**
 * Ed25519 envelope signature verification for SaaS responses.
 *
 * Counterpart to the SaaS-side `EnvelopeSigner`. Verifies a detached Ed25519
 * signature over a canonical JSON form of the envelope to confirm integrity
 * of the `siteUrl`, `publicKey`, and other payload fields.
 *
 * Behavior:
 *
 *   - No public key configured  → accept anything (legacy path, one-time log).
 *   - Pubkey + signature present → verify; WP_Error on mismatch.
 *   - Pubkey + signature missing → WP_Error in hard mode, warning log in soft.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\Logger;
/**
 * EnvelopeVerifier implementation.
 */
class EnvelopeVerifier {

	use Logger;

	/**
	 * Default tolerance between the SaaS-stamped `issuedAt` and the
	 * Connector's clock, in seconds. ±5 minutes. Wide enough to absorb
	 * typical NTP drift on both sides, narrow enough to keep a captured
	 * envelope from being replayed indefinitely. Overridable via the
	 * `trustedlogin/connector/envelope/freshness-window` filter.
	 *
	 * @since 2.0.0
	 */
	const FRESHNESS_WINDOW_SECONDS = 300;

	/**
	 * Hex-encoded sodium public key of the SaaS signer, or null when verification
	 * is disabled.
	 *
	 * @var string|null
	 */
	private $public_key_hex;

	/**
	 * Initialise the verifier with the SaaS sodium public key.
	 *
	 * @param string|null $saas_public_key_hex 64-char hex sodium public key. Null = verification disabled.
	 */
	public function __construct( $saas_public_key_hex ) {
		$saas_public_key_hex  = is_string( $saas_public_key_hex ) ? trim( $saas_public_key_hex ) : '';
		$this->public_key_hex = '' !== $saas_public_key_hex ? $saas_public_key_hex : null;
	}

	/**
	 * Whether verification is actively enforcing signatures.
	 *
	 * @return bool True when a valid-length public key is configured.
	 */
	public function is_enabled() {
		return null !== $this->public_key_hex
			&& 64 === strlen( $this->public_key_hex )
			&& ctype_xdigit( $this->public_key_hex );
	}

	/**
	 * Verify an envelope.
	 *
	 * @param array $envelope Decoded envelope from the SaaS.
	 * @param bool  $enforce  Retained for caller compatibility but no longer
	 *                        consulted. When the verifier is enabled (a
	 *                        public key is configured) a missing or invalid
	 *                        signature is always a hard failure.
	 *
	 * @return true|\WP_Error True on success, WP_Error on signature mismatch
	 *                        or missing signature when verification is enabled.
	 */
	public function verify( array $envelope, $enforce = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- BC: callers still pass $enforce; behavior is unconditionally strict.
		if ( ! $this->is_enabled() ) {
			// No public key configured — legacy path. Log once per request so
			// integrators get a nudge to configure signing without spamming.
			static $logged_once = false;
			if ( ! $logged_once ) {
				$this->log(
					'SaaS envelope public key is not configured — envelope signature verification is disabled. '
					. 'Set the `trustedlogin_vendor_saas_envelope_public_key` option or the '
					. '`trustedlogin/connector/envelope/saas-public-key` filter.',
					__METHOD__,
					'notice'
				);
				$logged_once = true;
			}
			return true;
		}

		$has_signature = ! empty( $envelope['signature'] ) && is_string( $envelope['signature'] );

		if ( ! $has_signature ) {
			// Missing signature is always a hard failure when the verifier
			// is enabled. Once the SaaS is known to be signing, a missing
			// signature is indistinguishable from a strip attack and must
			// be refused regardless of $enforce mode.
			$this->log(
				'Envelope is missing a signature; refusing to process.',
				__METHOD__,
				'error'
			);
			return new \WP_Error(
				'envelope_signature_missing',
				esc_html__( 'Envelope is missing a signature. Refusing to process.', 'trustedlogin-connector' )
			);
		}

		try {
			$signature = sodium_hex2bin( $envelope['signature'] );
			$pubkey    = sodium_hex2bin( $this->public_key_hex );
		} catch ( \SodiumException $e ) {
			$this->log( 'Envelope signature decode failed: ' . $e->getMessage(), __METHOD__, 'error' );
			return new \WP_Error(
				'envelope_signature_invalid',
				esc_html__( 'Envelope signature is malformed.', 'trustedlogin-connector' )
			);
		}

		$canonical = self::canonical( $envelope );

		// wp_json_encode returns false on encoding failure (e.g. malformed
		// UTF-8 in any string value). Without this guard, verify_detached
		// would receive a boolean as its message argument, PHP would cast
		// it to '', and the result space becomes ambiguous. Treat any
		// encoding failure as a verification failure.
		if ( false === $canonical ) {
			return new \WP_Error(
				'envelope_canonicalize_failed',
				esc_html__( 'Envelope could not be canonicalized for verification.', 'trustedlogin-connector' )
			);
		}

		// sodium_crypto_sign_verify_detached() throws SodiumException on
		// wrong-sized inputs (signature must be 64 bytes, pubkey 32).
		// is_enabled() checks the pubkey shape; a malformed signature
		// from the envelope would otherwise surface as an uncaught
		// exception. Treat any throw as a verification failure.
		try {
			$verified = sodium_crypto_sign_verify_detached( $signature, $canonical, $pubkey );
		} catch ( \SodiumException $e ) {
			$this->log( 'Envelope signature verify threw: ' . $e->getMessage(), __METHOD__, 'error' );
			return new \WP_Error(
				'envelope_signature_invalid',
				esc_html__( 'Envelope signature is malformed.', 'trustedlogin-connector' )
			);
		}

		if ( ! $verified ) {
			$this->log(
				'Envelope signature verification failed.',
				__METHOD__,
				'error',
				array(
					'siteUrl' => isset( $envelope['siteUrl'] ) ? $envelope['siteUrl'] : '(missing)',
				)
			);
			return new \WP_Error(
				'envelope_signature_invalid',
				esc_html__( 'Envelope signature did not verify. Refusing to process.', 'trustedlogin-connector' )
			);
		}

		$freshness = $this->check_freshness( $envelope );
		if ( true !== $freshness ) {
			return $freshness;
		}

		return true;
	}

	/**
	 * Verifies the envelope's `issuedAt` is within the freshness window.
	 *
	 * The SaaS-side `EnvelopeSigner::sign()` now stamps `issuedAt` (Unix
	 * seconds) into the canonical input before signing, so a value
	 * present in a verified envelope was definitely set by the SaaS at
	 * sign time. A connector that captured a valid envelope days ago
	 * could still satisfy `sodium_crypto_sign_verify_detached()` —
	 * binding to `issuedAt` is what makes the signature usable only
	 * within the legitimate window.
	 *
	 * Back-compat: an envelope without `issuedAt` (older SaaS, or a
	 * future field rename) is accepted unless an operator forces
	 * enforcement via the require-freshness filter. The default-allow
	 * keeps the rollout one-sided — Connectors built from this commit
	 * forward against an older SaaS still work.
	 *
	 * @param array $envelope Decoded envelope from the SaaS.
	 *
	 * @return true|\WP_Error True when fresh (or freshness check is
	 *                        unenforced for a missing/malformed value),
	 *                        WP_Error when the envelope is too old / in
	 *                        the future.
	 */
	private function check_freshness( array $envelope ) {
		/**
		 * Tolerance between the SaaS-stamped `issuedAt` and the
		 * Connector's clock, in seconds. Wider windows shrink
		 * operational friction (NTP drift, request queueing) but
		 * lengthen the time a captured envelope can be replayed.
		 *
		 * @since 2.0.0
		 *
		 * @param int $seconds Default 300 (±5min).
		 */
		$window = (int) apply_filters(
			'trustedlogin/connector/envelope/freshness-window',
			self::FRESHNESS_WINDOW_SECONDS
		);

		/**
		 * When true, an envelope that lacks `issuedAt` — or carries
		 * a non-integer value — is rejected instead of accepted on
		 * the back-compat fallback. Flip to true once the deployed
		 * SaaS is known to be on the issuedAt-stamping build.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $enforce Default false.
		 */
		$require_freshness = (bool) apply_filters(
			'trustedlogin/connector/envelope/require-freshness',
			false
		);

		$has_issued_at = array_key_exists( 'issuedAt', $envelope )
			&& ( is_int( $envelope['issuedAt'] ) || ( is_string( $envelope['issuedAt'] ) && ctype_digit( $envelope['issuedAt'] ) ) );

		if ( ! $has_issued_at ) {
			if ( $require_freshness ) {
				$this->log(
					'Envelope is missing issuedAt and freshness enforcement is enabled.',
					__METHOD__,
					'error'
				);
				return new \WP_Error(
					'envelope_freshness_missing',
					esc_html__( 'Envelope is missing the issuedAt freshness field. Refusing to process.', 'trustedlogin-connector' )
				);
			}

			return true;
		}

		$issued_at = (int) $envelope['issuedAt'];
		$skew      = time() - $issued_at;

		if ( abs( $skew ) <= $window ) {
			return true;
		}

		$this->log(
			'Envelope issuedAt is outside the freshness window.',
			__METHOD__,
			'error',
			array(
				'skew_seconds' => $skew,
				'window'       => $window,
			)
		);

		return new \WP_Error(
			'envelope_freshness_stale',
			esc_html__( 'Envelope is outside the freshness window. Refusing to process.', 'trustedlogin-connector' )
		);
	}

	/**
	 * Canonical JSON form, identical to SaaS-side EnvelopeSigner::canonical().
	 *
	 * Strips `signature` / `signaturePublicKey`, sorts keys, encodes with the
	 * same flags so the bytes match what the SaaS signed.
	 *
	 * @param array $envelope Envelope to canonicalize.
	 *
	 * @return string|false Canonical JSON, or `false` if wp_json_encode() fails
	 *                     (e.g. malformed UTF-8 in a string value).
	 */
	public static function canonical( array $envelope ) {
		unset( $envelope['signature'], $envelope['signaturePublicKey'] );
		ksort( $envelope );

		return wp_json_encode(
			$envelope,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
		);
	}
}

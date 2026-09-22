<?php
/**
 * Intercepts and forwards login-attempt query parameters to the activity page.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\LoginAttempts;

use TrustedLogin\Vendor\Capabilities;
use TrustedLogin\Vendor\MenuPage;

/**
 * Catches `?tl_attempt=lpat_…` appended by the customer-side
 * Endpoint::fail_login() redirect, validates format + capability,
 * and forwards to the canonical attempt-detail route on the
 * activity page.
 */
final class Intercept {

	/** Hook priority — early enough to redirect before page chrome renders. */
	const ADMIN_INIT_PRIORITY = 1;

	/**
	 * Wire the admin_init listener. Called once from the plugin
	 * bootstrap; subsequent calls would re-register the hook.
	 *
	 * @return void
	 */
	public function bootstrap(): void {
		add_action( 'admin_init', array( $this, 'handle' ), self::ADMIN_INIT_PRIORITY );
	}

	/**
	 * Inspect the current request for `?tl_attempt=lpat_…`. When
	 * present and well-formed, redirect to the canonical activity-
	 * page detail route. Cheap early-return on the common case (no
	 * query param) keeps the cost off every admin page load.
	 *
	 * @return void
	 */
	public function handle(): void {
		// EVERY admin page hits this hook — keep the early-return cheap.
		if ( empty( $_GET[ Constants::QUERY_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// `?tl_attempt[]=…&tl_attempt[]=evil` produces an array. PHP 8
		// emits a notice on the (string) cast and the regex would
		// match "Array" against nothing — strip explicitly and bail.
		if ( ! is_string( $_GET[ Constants::QUERY_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			unset( $_GET[ Constants::QUERY_PARAM ] );

			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Format is regex-validated below; raw value never reaches a sink.
		$raw_id = sanitize_text_field( wp_unslash( (string) $_GET[ Constants::QUERY_PARAM ] ) );

		// 1. Format validate. Malformed → silently strip + return
		// (likely a stale bookmark, not an attack).
		if ( ! preg_match( Constants::ATTEMPT_ID_REGEX, $raw_id ) ) {
			unset( $_GET[ Constants::QUERY_PARAM ] );

			return;
		}

		// 2. wp-login.php correction. Agent isn't authed yet; carrying
		// the param through the auth flow isn't worth the state
		// machinery for a rare case. Strip and let WP render
		// wp-login.php normally.
		if ( $this->is_wp_login_php() ) {
			unset( $_GET[ Constants::QUERY_PARAM ] );

			return;
		}

		// 3. Capability gate. Use the Connector wrapper, NOT bare
		// current_user_can — wrapper is the forward-compat hook
		// for per-team approved_roles checks (see Capabilities
		// docblock).
		if ( ! Capabilities::current_user_can( Capabilities::VIEW_ACTIVITY ) ) {
			wp_die(
				esc_html__(
					"You don't have permission to view login attempts.",
					'trustedlogin-connector'
				),
				esc_html__( 'TrustedLogin', 'trustedlogin-connector' ),
				array(
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Int constant (403); wp_die() routes it to HTTP status, never echoed.
					'response'  => Constants::STATUS_FORBIDDEN,
					'back_link' => true,
				)
			);
		}

		// 4. Canonical redirect. Uses a different param name than
		// `tl_attempt` so the activity page handler reads a single
		// source of truth. Attempt IDs are auth-gated, not bearer
		// tokens.
		$target = add_query_arg(
			array(
				'page'                     => MenuPage::SLUG_ACTIVITY,
				Constants::CANONICAL_PARAM => $raw_id,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $target );

		exit;
	}

	/**
	 * Prefer $pagenow (WordPress's authoritative page-discriminator)
	 * over SCRIPT_NAME, which can be rewritten by reverse proxies +
	 * mod_rewrite combos. Fall back to SCRIPT_NAME for very-early
	 * hooks where $pagenow isn't yet populated.
	 *
	 * @return bool
	 */
	private function is_wp_login_php(): bool {
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
			return true;
		}
		$script = isset( $_SERVER['SCRIPT_NAME'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['SCRIPT_NAME'] ) )
			: '';

		return false !== strpos( $script, 'wp-login.php' );
	}
}

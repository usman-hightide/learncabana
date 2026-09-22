<?php
/**
 * Public-facing secret recipient page.
 *
 * Registers the rewrite rule for tl-secret/{token} URLs and renders a minimal
 * HTML page with inline JavaScript that handles the two-phase reveal flow:
 * pre-reveal confirmation → ciphertext fetch → client-side decrypt → DELETE.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

/**
 * Handles the public secret-view page.
 *
 * @since 2.0.0
 */
class SecretPage {

	/**
	 * Query var for the secret token.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'tl_secret_token';

	/**
	 * Registers hooks for the rewrite rule and template handling.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', array( static::class, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( static::class, 'add_query_var' ) );
		add_action( 'template_redirect', array( static::class, 'maybe_render' ), 5 ); // Before default 10.
		add_filter( 'redirect_canonical', array( static::class, 'prevent_trailing_slash_redirect' ), 10, 2 );
		add_filter( 'rest_pre_dispatch', array( static::class, 'block_cross_origin_secret_requests' ), 10, 3 );
	}

	/**
	 * Rejects cross-origin requests to secrets REST endpoints.
	 *
	 * Fires on `rest_pre_dispatch` — before the route callback runs — so a
	 * cross-origin attacker's page can never trigger prepare/fetch/burn even
	 * though WordPress reflects the Origin header in CORS.
	 *
	 * Same-origin requests (no Origin header, or Origin matches site URL)
	 * pass through normally.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed            $result  Response to replace the requested version with. Default null.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 *
	 * @return mixed|\WP_Error Unchanged $result for same-origin, WP_Error for cross-origin.
	 */
	public static function block_cross_origin_secret_requests( $result, $server, $request ) {
		unset( $server ); // Required by `rest_pre_dispatch` filter signature.

		$route = $request->get_route();

		// Only apply to secrets endpoints.
		if ( strpos( $route, '/trustedlogin/v1/secrets' ) === false ) {
			return $result;
		}

		$origin = $request->get_header( 'Origin' );

		// No Origin header = same-origin (direct curl, same-site JS, server-to-server).
		if ( empty( $origin ) ) {
			return $result;
		}

		// Compare origin against site URL.
		$site_origin = wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . wp_parse_url( home_url(), PHP_URL_HOST );
		$port        = wp_parse_url( home_url(), PHP_URL_PORT );

		if ( $port ) {
			$site_origin .= ':' . $port;
		}

		if ( rtrim( $origin, '/' ) === rtrim( $site_origin, '/' ) ) {
			return $result;
		}

		return new \WP_Error(
			'rest_forbidden_cross_origin',
			__( 'Cross-origin requests to this endpoint are not allowed.', 'trustedlogin-connector' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Adds the rewrite rule for clean secret URLs.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function add_rewrite_rule() {
		add_rewrite_rule(
			'^trustedlogin/s/([a-f0-9]{' . SecretManager::TOKEN_LENGTH . '})/?$',
			'index.php?' . self::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Registers the query variable so WordPress recognizes it.
	 *
	 * @since 2.0.0
	 *
	 * @param array $vars Existing query vars.
	 *
	 * @return array
	 */
	public static function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Prevents WordPress from adding a trailing slash redirect to tl-secret/ URLs.
	 *
	 * The 301 redirect to add a trailing slash strips the URL fragment, which
	 * contains the decryption key — making the secret unrecoverable.
	 *
	 * @since 2.0.0
	 *
	 * @param string $redirect_url  The URL WordPress wants to redirect to.
	 * @param string $requested_url The original request URL.
	 *
	 * @return string|false The redirect URL, or false to cancel the redirect.
	 */
	public static function prevent_trailing_slash_redirect( $redirect_url, $requested_url ) {
		unset( $requested_url ); // Required by `redirect_canonical` filter signature.

		if ( get_query_var( self::QUERY_VAR ) ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Renders the secret page if the query var is present.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function maybe_render() {

		$token = get_query_var( self::QUERY_VAR );

		if ( empty( $token ) || strlen( $token ) !== SecretManager::TOKEN_LENGTH || ! ctype_xdigit( $token ) ) {
			return;
		}

		// Check if a passphrase is required (peek at the record without locking).
		$manager = new SecretManager();
		$record  = $manager->get_record( $token );

		$passphrase_required = false;
		$is_creator          = false;
		$burn_after_reading  = true;
		$viewer_can_destroy  = true;
		$is_available        = ( null !== $record && empty( $record['in_flight'] ) && null === $record['revealed_at'] );

		if ( $record && $is_available ) {
			$passphrase_required = ! empty( $record['passphrase_hash'] );
			$burn_after_reading  = $record['burn_after_reading'] ?? true;
			// Default true preserves the existing reveal-page behavior
			// for any secret created before the field landed. The flag
			// only matters for reusable links; single-use secrets
			// self-destroy on reveal regardless of this value.
			$viewer_can_destroy = $record['viewer_can_destroy'] ?? true;
			$is_creator         = is_user_logged_in() && $manager->is_creator( $token, get_current_user_id() );
			// No reveal nonce is generated here — the JS calls POST /prepare
			// on button click to get one. This prevents crawlers from extracting
			// the nonce from the page HTML.
		}

		// Prevent caching and referrer leakage.
		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );

		self::render_page( $token, $passphrase_required, $is_creator, $is_available, $burn_after_reading, $viewer_can_destroy );
		exit;
	}

	/**
	 * Renders the full HTML page for the secret recipient.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token              The secret token.
	 * @param bool   $passphrase_required Whether a passphrase is needed.
	 * @param bool   $is_creator         Whether the current user created this secret.
	 * @param bool   $is_available       Whether the secret is still available.
	 * @param mixed  $burn_after_reading Whether the secret is destroyed after first view.
	 * @param mixed  $viewer_can_destroy Whether the viewer has permission to destroy the secret.
	 *
	 * @return void
	 */
	private static function render_page( $token, $passphrase_required, $is_creator, $is_available, $burn_after_reading = true, $viewer_can_destroy = true ) {

		$rest_url   = rest_url( 'trustedlogin/v1/secrets/' . $token );
		$verify_url = rest_url( 'trustedlogin/v1/secrets/' . $token . '/verify' );
		$site_name  = get_bloginfo( 'name' );

		?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<meta name="referrer" content="no-referrer">
		<?php /* translators: %s is the site name displayed in the secure-message browser tab title. */ ?>
	<title><?php echo esc_html( sprintf( __( 'Secure Message — %s', 'trustedlogin-connector' ), $site_name ) ); ?></title>
	<style>
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background: #f0f0f1; color: #1d2327; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
		.tl-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); max-width: 600px; width: 100%; padding: 40px 40px 24px; text-align: center; }
		.tl-card h1 { font-size: 1.4em; margin-bottom: 12px; }
		.tl-card p { color: #50575e; margin-bottom: 20px; line-height: 1.5; }
		.tl-btn { display: inline-block; padding: 12px 32px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 1em; cursor: pointer; text-decoration: none; }
		.tl-btn:hover { background: #135e96; }
		.tl-btn:disabled { background: #a7aaad; cursor: not-allowed; }
		.tl-btn--danger { background: #d63638; }
		.tl-btn--danger:hover { background: #b32d2e; }
		.tl-input { width: 100%; padding: 10px 14px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 1em; margin-bottom: 16px; }
		.tl-textarea { width: 100%; min-height: 120px; padding: 12px; border: 1px solid #8c8f94; border-radius: 4px; font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace; font-size: 0.9em; resize: vertical; background: #f6f7f7; margin-bottom: 16px; }
		.tl-error { color: #d63638; margin-bottom: 16px; min-height: 1.2em; }
		.tl-banner { padding: 10px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9em; }
		.tl-banner--warn { background: #fcf0e3; color: #996800; border: 1px solid #dba617; }
		.tl-banner--info { background: #e7f3fe; color: #1d4ed8; border: 1px solid #72aee6; }
		.tl-footer { margin-top: 20px; font-size: 0.85em; color: #646970; }
		.tl-hidden { display: none; }
		.tl-copy-btn, .tl-destroy-btn { padding: 8px 20px; font-size: 0.9em; }
		.tl-action-row { display: flex; gap: 24px; justify-content: center; flex-wrap: wrap; }
		.tl-btn:focus-visible, .tl-input:focus-visible, .tl-textarea:focus-visible { outline: 2px solid #2271b1; outline-offset: 2px; }
		.tl-powered-by { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e4e7; font-size: 0.8em; color: #646970; display: flex; align-items: center; justify-content: center; gap: 6px; }
		.tl-powered-by a { color: #646970; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
		.tl-powered-by a:hover { color: #2271b1; }
		.tl-powered-by .tl-logo { display: inline-block; width: 14px; height: 20px; background: transparent url("data:image/svg+xml,%3c%3fxml version='1.0' encoding='UTF-8'%3f%3e%3csvg enable-background='new 0 0 139.3 220.7' version='1.1' viewBox='0 0 139.3 220.7' xml:space='preserve' xmlns='http://www.w3.org/2000/svg'%3e%3cstyle type='text/css'%3e .st0%7bfill:%231099D6%3b%7d .st1%7bfill:%231B2B59%3b%7d .st2%7bfill:white%3b%7d%3c/style%3e%3cpath class='st0' d='M70.7%2c0C44.1%2c0%2c22.5%2c21.6%2c22.5%2c48.1V88h20.6V48.1c0-15.2%2c12.3-27.5%2c27.5-27.5c15.1%2c0%2c27.5%2c12.3%2c27.5%2c27.5V88 h20.6V48.1C118.8%2c21.6%2c97.2%2c0%2c70.7%2c0z'/%3e%3cpath class='st1' d='m70.7 75.3c-38.5 0-69.7 4.9-69.7 10.8v79.8c0 38.5 47.5 54.8 69.7 54.8s69.7-16.3 69.7-54.8v-79.8c-0.1-5.9-31.3-10.8-69.7-10.8z'/%3e%3cpath class='st2' d='m70.7 95.6c-23 0-42.5 15.3-48.9 36.2h14.8c5.8-13.1 18.9-22.3 34.1-22.3 20.5 0 37.2 16.7 37.2 37.2s-16.7 37.2-37.2 37.2c-15.2 0-28.3-9.2-34.1-22.3h-14.8c6.4 20.9 25.9 36.2 48.9 36.2 28.2 0 51.1-22.9 51.1-51.1-0.1-28.2-23-51.1-51.1-51.1z'/%3e%3cpath class='st2' d='m90.3 144.3l-28.4-16.3c-2.2-1.3-4-0.2-4 2.3v9.8h-56.9v13h56.9v9.8c0 2.5 1.8 3.6 4 2.3l28.3-16.4c2.3-1.1 2.3-3.2 0.1-4.5z'/%3e%3c/svg%3e") no-repeat center / contain; }
	</style>
</head>
<body>
	<main class="tl-card" id="tl-secret-app" role="main" aria-live="polite">
		<?php if ( ! $is_available ) { ?>
			<!-- State 4: Unavailable -->
			<h1><?php esc_html_e( 'Information no longer available', 'trustedlogin-connector' ); ?></h1>
			<p><?php esc_html_e( 'This secret has been viewed or expired.', 'trustedlogin-connector' ); ?></p>
			<p style="font-size:0.9em;"><?php esc_html_e( "Contact the person who sent you this link and ask them to create a new secret. Let them know you weren't able to access the original information.", 'trustedlogin-connector' ); ?></p>
		<?php } else { ?>
			<!-- State 1: Pre-reveal confirmation -->
			<div id="tl-state-confirm">
				<?php if ( $is_creator && $burn_after_reading ) { ?>
					<div class="tl-banner tl-banner--warn">
						<?php esc_html_e( 'You created this secret. If you view it, the recipient will not be able to see it.', 'trustedlogin-connector' ); ?>
					</div>
				<?php } ?>

				<?php if ( $passphrase_required ) { ?>
					<h1><?php esc_html_e( 'This message requires a passphrase', 'trustedlogin-connector' ); ?></h1>
					<input type="hidden" name="username" autocomplete="username" class="tl-hidden" tabindex="-1">
					<input type="password" id="tl-passphrase" class="tl-input" placeholder="<?php esc_attr_e( 'Enter the passphrase here', 'trustedlogin-connector' ); ?>" aria-label="<?php esc_attr_e( 'Passphrase', 'trustedlogin-connector' ); ?>" autocomplete="current-password">
				<?php } else { ?>
					<h1><?php esc_html_e( "You've received a secure message", 'trustedlogin-connector' ); ?></h1>
					<?php if ( $burn_after_reading ) { ?>
						<p><?php esc_html_e( 'This message can only be viewed once.', 'trustedlogin-connector' ); ?></p>
					<?php } else { ?>
						<p><?php esc_html_e( 'This message is available until it expires.', 'trustedlogin-connector' ); ?></p>
					<?php } ?>
				<?php } ?>

				<div class="tl-error" id="tl-error" role="alert"></div>
				<button class="tl-btn" id="tl-reveal-btn"><?php esc_html_e( 'Click to reveal →', 'trustedlogin-connector' ); ?></button>
			</div>

			<!-- State 2: Revealed -->
			<div id="tl-state-revealed" class="tl-hidden">
				<?php if ( $is_creator && $burn_after_reading ) { ?>
					<div class="tl-banner tl-banner--info">
						<?php esc_html_e( 'You have viewed your own secret. It is no longer available for anyone else.', 'trustedlogin-connector' ); ?>
					</div>
				<?php } ?>
				<h1><?php esc_html_e( 'Your secure message is shown below.', 'trustedlogin-connector' ); ?></h1>
				<textarea class="tl-textarea" id="tl-plaintext" readonly aria-label="<?php esc_attr_e( 'Secret content', 'trustedlogin-connector' ); ?>"></textarea>
				<div class="tl-action-row">
					<button class="tl-btn tl-copy-btn" id="tl-copy-btn"><?php esc_html_e( 'Copy to clipboard', 'trustedlogin-connector' ); ?></button>
					<?php if ( ! $burn_after_reading && $viewer_can_destroy ) { ?>
					<button class="tl-btn tl-btn--danger tl-destroy-btn" id="tl-destroy-btn" style="display:none;"><?php esc_html_e( 'Destroy this secret', 'trustedlogin-connector' ); ?></button>
					<?php } ?>
				</div>
				<p class="tl-footer"><?php esc_html_e( 'You can close this window when done.', 'trustedlogin-connector' ); ?></p>
			</div>

			<!-- State 4: Unavailable (shown after errors) -->
			<div id="tl-state-unavailable" class="tl-hidden">
				<h1><?php esc_html_e( 'Information no longer available', 'trustedlogin-connector' ); ?></h1>
				<p><?php esc_html_e( 'This secret has been viewed or expired.', 'trustedlogin-connector' ); ?></p>
				<p style="font-size:0.9em;"><?php esc_html_e( 'Contact the person who sent you this link and ask them to create a new secret.', 'trustedlogin-connector' ); ?></p>
			</div>
		<?php } ?>
		<div class="tl-powered-by">
			<a href="https://www.trustedlogin.com" target="_blank" rel="noopener noreferrer">
				<span class="tl-logo"></span>
				<?php esc_html_e( 'Secured by TrustedLogin', 'trustedlogin-connector' ); ?>
			</a>
		</div>
	</main>

		<?php if ( $is_available ) { ?>
			<?php // Recipient reveal page is a standalone HTML response, not a WP admin/front-end view. wp_enqueue_script is unavailable here. ?>
	<script src="<?php echo esc_url( plugins_url( 'assets/js/sodium/libsodium.js', TRUSTEDLOGIN_PLUGIN_FILE ) ); ?>"></script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script src="<?php echo esc_url( plugins_url( 'assets/js/sodium/libsodium-wrappers.js', TRUSTEDLOGIN_PLUGIN_FILE ) ); ?>"></script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script>
	(function() {
		'use strict';

			<?php $jf = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT; ?>
		var config = {
			token: <?php echo wp_json_encode( $token, $jf ); ?>,
			restUrl: <?php echo wp_json_encode( $rest_url, $jf ); ?>,
			prepareUrl: <?php echo wp_json_encode( rest_url( 'trustedlogin/v1/secrets/' . $token . '/prepare' ), $jf ); ?>,
			verifyUrl: <?php echo wp_json_encode( $verify_url, $jf ); ?>,
			nonce: null,
			passphraseRequired: <?php echo $passphrase_required ? 'true' : 'false'; ?>,
			burnAfterReading: <?php echo $burn_after_reading ? 'true' : 'false'; ?>,
			keyBytes: <?php echo (int) SecretManager::KEY_BYTES; ?>,
			strings: {
				enterPassphrase: <?php echo wp_json_encode( __( 'Please enter the passphrase.', 'trustedlogin-connector' ), $jf ); ?>,
				malformedLink: <?php echo wp_json_encode( __( 'This link appears to be malformed. Ask the sender to create a new one.', 'trustedlogin-connector' ), $jf ); ?>,
				prepareError: <?php echo wp_json_encode( __( "Couldn't prepare the secret. Check your connection and try again.", 'trustedlogin-connector' ), $jf ); ?>,
				fetchError: <?php echo wp_json_encode( __( "Couldn't reach the secret server. Check your connection and try again.", 'trustedlogin-connector' ), $jf ); ?>,
				verifyError: <?php echo wp_json_encode( __( 'Could not verify passphrase. Check your connection and try again.', 'trustedlogin-connector' ), $jf ); ?>,
				permanentlyLocked: <?php echo wp_json_encode( __( 'Too many incorrect attempts. This secret is permanently locked.', 'trustedlogin-connector' ), $jf ); ?>,
				retryAfter: <?php /* translators: 1: seconds to wait, 2: attempts remaining */ echo wp_json_encode( __( 'Incorrect passphrase. Try again in %1$s seconds. %2$s attempts remaining.', 'trustedlogin-connector' ), $jf ); ?>,
				wrongPassphrase: <?php /* translators: %s: attempts remaining */ echo wp_json_encode( __( 'Incorrect passphrase. %s attempts remaining.', 'trustedlogin-connector' ), $jf ); ?>,
				destroyConfirm: <?php echo wp_json_encode( __( 'Destroy this secret? This cannot be undone.', 'trustedlogin-connector' ), $jf ); ?>,
				copied: <?php echo wp_json_encode( __( 'Copied', 'trustedlogin-connector' ), $jf ); ?>,
				copyToClipboard: <?php echo wp_json_encode( __( 'Copy to clipboard', 'trustedlogin-connector' ), $jf ); ?>
			}
		};

		var stateConfirm = document.getElementById('tl-state-confirm');
		var stateRevealed = document.getElementById('tl-state-revealed');
		var stateUnavailable = document.getElementById('tl-state-unavailable');
		var revealBtn = document.getElementById('tl-reveal-btn');
		var errorEl = document.getElementById('tl-error');
		var plaintextEl = document.getElementById('tl-plaintext');
		var destroyBtn = document.getElementById('tl-destroy-btn');
		var copyBtn = document.getElementById('tl-copy-btn');
		var passphraseInput = document.getElementById('tl-passphrase');

		function showState(state) {
			stateConfirm.classList.add('tl-hidden');
			stateRevealed.classList.add('tl-hidden');
			stateUnavailable.classList.add('tl-hidden');
			state.classList.remove('tl-hidden');
		}

		function showError(msg) {
			errorEl.textContent = msg;
		}

		// Read the decryption key from the URL fragment.
		function getKeyFromFragment() {
			var hash = window.location.hash.substring(1);
			if (!hash) return null;
			// URL-safe base64 decode: replace -_ with +/, add padding.
			hash = hash.replace(/-/g, '+').replace(/_/g, '/');
			while (hash.length % 4) hash += '=';
			try {
				var raw = atob(hash);
				var bytes = new Uint8Array(raw.length);
				for (var i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
				return bytes;
			} catch(e) {
				return null;
			}
		}

		// Verify passphrase outcomes. 'retry' and 'locked' mean the button
		// must stay disabled (verifyPassphrase owns the re-enable path via
		// setTimeout for 'retry'); 'wrong' and 'error' let the caller
		// re-enable for another try. The enum replaces a bool because a plain
		// false was ambiguous about whether the button should be re-enabled,
		// which let callers bypass the server-side exponential backoff.
		var PP = Object.freeze({
			OK: 'ok',
			LOCKED: 'locked',
			RETRY: 'retry',
			WRONG: 'wrong',
			ERROR: 'error'
		});

		// Verify passphrase if required.
		async function verifyPassphrase() {
			if (!config.passphraseRequired) return PP.OK;
			var passphrase = passphraseInput ? passphraseInput.value : '';
			if (!passphrase) { showError(config.strings.enterPassphrase); return PP.WRONG; }

			try {
				var resp = await fetch(config.verifyUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ passphrase: passphrase })
				});
				var data = await resp.json();
				if (data.valid) return PP.OK;
				if (data.locked) {
					showError(config.strings.permanentlyLocked);
					revealBtn.disabled = true;
					return PP.LOCKED;
				}
				if (data.retry_after > 0) {
					showError(config.strings.retryAfter.replace('%1$s', data.retry_after).replace('%2$s', data.attempts_remaining || 0));
					// Keep the button disabled for the full lockout so the caller can't
					// race the server. A 500ms buffer absorbs integer-second rounding
					// on the server's locked_until check.
					revealBtn.disabled = true;
					setTimeout(function() { revealBtn.disabled = false; revealBtn.textContent = originalBtnText; showError(''); }, data.retry_after * 1000 + 500);
					return PP.RETRY;
				}
				showError(config.strings.wrongPassphrase.replace('%s', data.attempts_remaining || 0));
				return PP.WRONG;
			} catch(e) {
				showError(config.strings.verifyError);
				return PP.ERROR;
			}
		}

		// Enter key on passphrase input triggers the reveal button.
		if (passphraseInput) {
			passphraseInput.addEventListener('keydown', function(e) {
				if (e.key === 'Enter') { e.preventDefault(); revealBtn.click(); }
			});
		}

		// Main reveal flow.
		var originalBtnText = revealBtn.textContent;
		revealBtn.addEventListener('click', async function() {
			revealBtn.disabled = true;
			revealBtn.textContent = '<?php echo esc_js( __( 'Revealing…', 'trustedlogin-connector' ) ); ?>';
			showError('');

			var key = getKeyFromFragment();
			if (!key || key.length !== config.keyBytes) {
				showError(config.strings.malformedLink);
				revealBtn.disabled = false; revealBtn.textContent = originalBtnText;
				return;
			}

			// Step 1: Verify passphrase if required.
			if (config.passphraseRequired) {
				var ppResult = await verifyPassphrase();
				if (ppResult !== PP.OK) {
					revealBtn.textContent = originalBtnText;
					// Only re-enable for outcomes that don't have their own
					// lockout/setTimeout already managing the button. RETRY
					// and LOCKED must stay disabled so the caller can't bypass
					// the server-side backoff.
					if (ppResult === PP.WRONG || ppResult === PP.ERROR) {
						revealBtn.disabled = false;
					}
					return;
				}
			}

			// Step 2: Get a reveal nonce via POST /prepare.
			// This is a JS-only step — crawlers that fetch the HTML page never
			// reach this code, so they can't get a nonce to lock the secret.
			// Send sha256(key) so token-only leaks can't stamp a nonce and
			// destroy the secret (the fragment key never leaves the browser).
			try {
				var keyDigest = await crypto.subtle.digest('SHA-256', key);
				var keyHash = Array.from(new Uint8Array(keyDigest))
					.map(function(b) { return b.toString(16).padStart(2, '0'); })
					.join('');
				var prepResp = await fetch(config.prepareUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ key_hash: keyHash })
				});
				if (!prepResp.ok) { showState(stateUnavailable); return; }
				var prepData = await prepResp.json();
				config.nonce = prepData.nonce;
			} catch(e) {
				showError(config.strings.prepareError);
				revealBtn.disabled = false; revealBtn.textContent = originalBtnText;
				return;
			}

			// Step 3: Fetch ciphertext from server.
			var record;
			try {
				var resp = await fetch(config.restUrl, {
					headers: { 'X-TL-Secret-Nonce': config.nonce }
				});
				if (!resp.ok) { showState(stateUnavailable); return; }
				record = await resp.json();
			} catch(e) {
				showError(config.strings.fetchError);
				revealBtn.disabled = false; revealBtn.textContent = originalBtnText;
				return;
			}

			// Step 3: Decrypt client-side.
			try {
				await sodium.ready;

				var nonce = sodium.from_hex(record.nonce);
				var ciphertext = Uint8Array.from(atob(record.ciphertext), function(c) { return c.charCodeAt(0); });
				var plaintext = sodium.crypto_secretbox_open_easy(ciphertext, nonce, key);
				var decoded = new TextDecoder().decode(plaintext);

				if (config.burnAfterReading) {
					// Step 4a: Fire DELETE before rendering (speculative delete).
					try {
						await fetch(config.restUrl, {
							method: 'DELETE',
							headers: { 'X-TL-Secret-Nonce': config.nonce }
						});
					} catch(e) {
						// DELETE failed — grace-period cron is the safety net.
					}
				} else {
					// Step 4b: Multi-view — show the Destroy button instead.
					if (destroyBtn) { destroyBtn.style.display = 'inline-block'; }
				}

				// Step 5: Render plaintext and move focus for screen readers.
				plaintextEl.value = decoded;
				showState(stateRevealed);
				plaintextEl.focus();

			} catch(e) {
				showState(stateUnavailable);
			}
		});

		// Copy button with screen reader announcement.
		if (copyBtn) {
			copyBtn.addEventListener('click', function() {
				var writePromise = navigator.clipboard && navigator.clipboard.writeText
					? navigator.clipboard.writeText(plaintextEl.value)
					: Promise.reject();
				writePromise.then(function() {
					copyBtn.textContent = config.strings.copied;
					copyBtn.setAttribute('aria-label', config.strings.copied);
					setTimeout(function() {
						copyBtn.textContent = config.strings.copyToClipboard;
						copyBtn.removeAttribute('aria-label');
					}, 1500);
				}).catch(function() {
					// Fallback: select the textarea so the user can copy manually.
					plaintextEl.focus();
					plaintextEl.select();
				});
			});
		}

		// Destroy button (multi-view secrets only).
		if (destroyBtn) {
			destroyBtn.addEventListener('click', async function() {
				if (!confirm(config.strings.destroyConfirm)) { return; }
				destroyBtn.disabled = true;
				destroyBtn.textContent = 'Destroying…';
				try {
					await fetch(config.restUrl, {
						method: 'DELETE',
						headers: { 'X-TL-Secret-Nonce': config.nonce }
					});
				} catch(e) { /* best-effort */ }
				showState(stateUnavailable);
			});
		}
	})();
	</script>
	<?php } ?>
</body>
</html>
		<?php
	}
}

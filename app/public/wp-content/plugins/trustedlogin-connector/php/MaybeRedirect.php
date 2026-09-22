<?php
/**
 * Support-login redirect dispatcher.
 *
 * Wired to admin_init and template_redirect; converts inbound TrustedLogin
 * URLs into either a JSON response (webhook flow) or a wp-admin redirect
 * with a `?error=…` slug (access-key flow). Also owns the externally-facing
 * error-slug contract consumed by `src/trustedlogin-settings/init.php`.
 *
 * @package trustedlogin-vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Status\IsIntegrationActive;
use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Traits\VerifyUser;
use TrustedLogin\Vendor\Webhooks\Factory;
use TrustedLogin\Vendor\Webhooks\Webhook;

/**
 * Checks for support redirect logins and tries to handle them.
 */
class MaybeRedirect {

	use Logger;
	use VerifyUser;

	const REDIRECT_KEY = 'tl_redirect';

	/*
	 * Externally-facing error slugs surfaced via `?error=…` on redirects back
	 * to the settings page. The settings boot script
	 * (`src/trustedlogin-settings/init.php`) is the consumer; consumers should
	 * reference these constants instead of literal strings. AccessKeyLogin
	 * failures collapse to a single value; the precise internal slug is
	 * preserved on the WP_Error `$code` and recorded in the log.
	 */
	const EXTERNAL_SLUG_NONCE           = 'nonce';
	const EXTERNAL_SLUG_INVALID_REQUEST = 'invalid_request';

	/**
	 * Handle the "Reset All" button in UI
	 *
	 * @uses "admin_init" action
	 */
	public static function adminInit() {

		self::maybeRedirectParentMenu();

		$action = Helpers::get_post_or_get( 'action' );

		if ( Reset::ACTION_NAME !== $action ) {
			return;
		}

		$nonce = Helpers::get_post_or_get( '_wpnonce', 'sanitize_text_field' );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, Reset::NONCE_ACTION ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'trustedlogin-settings',
						'error' => self::EXTERNAL_SLUG_NONCE,
					),
					admin_url()
				)
			);
			exit;
		}

		// Reset wipes plugin state; requires manage_options.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'trustedlogin-settings',
						'error' => self::EXTERNAL_SLUG_INVALID_REQUEST,
					),
					admin_url()
				)
			);
			exit;
		}

		// Reset all data.
		( new Reset() )->resetAll(
			\trustedlogin_connector()
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'trustedlogin-settings',
					'success' => 'reset',
				),
				admin_url()
			)
		);
		exit;
	}

	/**
	 * Redirects the top-level TrustedLogin menu to a configured sub-page.
	 *
	 * The parent menu slug matches SLUG_SETTINGS so clicking the top-level
	 * "TrustedLogin" item in wp-admin lands on Settings by default. Admins
	 * who spend most of their time on Teams/Secrets/Activity can override
	 * this via the `default_landing_page` global setting.
	 *
	 * Only fires when the user requested the BARE parent page — loads that
	 * already specify a sub-page, action, or any other query string are
	 * passed through untouched so deep links keep working.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function maybeRedirectParentMenu() {

		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		$page = Helpers::get_post_or_get( 'page', 'sanitize_key' );

		// Legacy: Help Desks used to be its own submenu (SLUG_HELPDESKS
		// = `trustedlogin-helpdesks`). Moved into Settings in 1.4.1 as
		// the `#helpdesks-integrations` section. Redirect so docs links
		// and bookmarks keep working. Scroll target lives on the
		// Settings page; anchor deep-linking works because the section
		// id matches.
		if ( MenuPage::SLUG_HELPDESKS === $page ) {
			wp_safe_redirect(
				add_query_arg( 'page', MenuPage::SLUG_SETTINGS, admin_url( 'admin.php' ) ) . '#helpdesks-integrations'
			);
			exit;
		}

		if ( MenuPage::PARENT_MENU_SLUG !== $page ) {
			return;
		}

		// Don't interfere with action-driven submissions on the parent page
		// (Reset, AccessKeyLogin deep-links, etc.) — they're identifiable by
		// having ANY additional query param the parent menu link wouldn't have.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$extras = array_diff( array_keys( $_GET ), array( 'page' ) );
		if ( ! empty( $extras ) ) {
			return;
		}

		// Pick a target the current user can actually load. Honours
		// default_landing_page when accessible, otherwise falls
		// through MenuPage::LANDING_PRIORITY to the user's reachable
		// pages (support agents → Access Key Log-In, activity viewers
		// → Activity, etc.).
		$target_slug = MenuPage::user_default_slug();
		if ( null === $target_slug ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'page', $target_slug, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Checks if the specified attributes are set has a valid access_key before checking if we can redirect support agent.
	 *
	 * @uses "template_redirect" action
	 * @since 1.0.0
	 */
	public static function handle() {

		// Access key redirect.
		if ( ! Helpers::get_post_or_get( AccessKeyLogin::REDIRECT_ENDPOINT ) ) {
			return;
		}

		if ( Webhook::WEBHOOK_ACTION === Helpers::get_post_or_get( 'action' ) ) {
			$provider = Helpers::get_post_or_get( Factory::PROVIDER_KEY );

			if ( ! in_array( $provider, Factory::getProviders(), true ) ) {
				return;
			}

			if ( ! IsIntegrationActive::check( $provider ) ) {
				return;
			}

			$accountId = Helpers::get_post_or_get( AccessKeyLogin::ACCOUNT_ID_INPUT_NAME, 'sanitize_text_field' );

			try {
				$team    = SettingsApi::fromSaved()->getByAccountId( $accountId );
				$webhook = Factory::webhook( $team );
				$r       = $webhook->webhookEndpoint();

				// Help Scout's widget renders a generic "error" label whenever it receives a non-2xx
				// HTTP response, discarding any HTML body we send. When the webhook produced an HTML
				// payload (including error states), force HTTP 200 so the widget actually displays
				// it. The semantic error status is still available in $r['status'] for clients that
				// care. Pass the status code explicitly rather than relying on whatever
				// status_header() may already be set to.
				self::add_timing_jitter();

				if ( ! empty( $r['html'] ) ) {
					wp_send_json( $r, 200 );
				} else {
					wp_send_json( $r, $r['status'] );
				}
			} catch ( \Throwable $th ) {
				// Return the same generic unauthorized response the webhook produces
				// for a valid account with a bad signature, with HTTP 200, so valid
				// and invalid account IDs are indistinguishable on the wire.
				self::add_timing_jitter();

				$providerClass = Factory::providerClass( $provider );

				wp_send_json(
					$providerClass::build_error_message(
						403,
						'Unauthorized.',
						'Verify your site\'s TrustedLogin Settings match the help desk widget settings.'
					),
					200
				);
			}
		}

		$handler = new AccessKeyLogin();
		$parts   = $handler->handle();

		// Record the precise failure branch server-side; only the generic
		// external slug is surfaced in the URL.
		if ( is_wp_error( $parts ) ) {
			\trustedlogin_connector()->log(
				'AccessKeyLogin returned WP_Error',
				__METHOD__,
				'warning',
				array( 'internal_slug' => (string) $parts->get_error_code() )
			);
		}

		// Constant-deadline timing on every exit, matching the webhook path.
		self::add_timing_jitter();

		if ( is_array( $parts ) ) {
			wp_send_json_success( $parts );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'trustedlogin-settings',
					'error' => self::EXTERNAL_SLUG_INVALID_REQUEST,
				),
				admin_url()
			)
		);
		exit;
	}

	/**
	 * Introduces 5–15ms of random response-latency jitter.
	 *
	 * Pinned by integration tests; see internal threat model for rationale.
	 *
	 * @since 2.0.0
	 */
	private static function add_timing_jitter() {
		usleep( random_int( 5000, 15000 ) ); // 5-15ms
	}
}

<?php
/**
 * Plugin Name: TrustedLogin Connector
 * Description: Authenticate support team members to securely log them in to client sites via TrustedLogin
 * Version: 2.0.1
 * Requires PHP: 7.4
 * Author: TrustedLogin
 * Author URI: https://www.trustedlogin.com
 * Text Domain: trustedlogin-connector
 * License: GPL v2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Copyright: © 2020 Katz Web Services, Inc.
 *
 * @package TrustedLogin\Vendor
 */

use TrustedLogin\Vendor\AccessKeyLogin;
use TrustedLogin\Vendor\Reset;
use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Status\Onboarding;
use TrustedLogin\Vendor\Webhooks\Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'TRUSTEDLOGIN_PLUGIN_VERSION', '2.0.1' );
define( 'TRUSTEDLOGIN_PLUGIN_FILE', __FILE__ );

if ( ! defined( 'TRUSTEDLOGIN_API_URL' ) ) {
	define( 'TRUSTEDLOGIN_API_URL', 'https://app.trustedlogin.com/api/v1/' );
}

// Set this to true, in wp-config.php to log all PHP errors/warnings/notices to trustedlogin.log.
// Code: define( 'TRUSTEDLOGIN_DEBUG', true );.
if ( ! defined( 'TRUSTEDLOGIN_DEBUG' ) ) {
	define( 'TRUSTEDLOGIN_DEBUG', null );
}


/**
 * Path to the plugin root directory.
 *
 * @define "$trustedlogin_connector_path" "./"
 */
$trustedlogin_connector_path = plugin_dir_path( __FILE__ );

/**
 * Bootstrap plugin hooks and includes.
 *
 * Registers activation/deactivation hooks and loads the autoloader.
 */
// Set register deactivation hook.
register_deactivation_hook( __FILE__, 'trustedlogin_connector_deactivate' );
// Create the audit table on activation.
register_activation_hook(
	__FILE__,
	function () {
		if ( is_readable( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
			include_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
			\TrustedLogin\Vendor\SecretAudit::create_table();
			\TrustedLogin\Vendor\Capabilities::activate();

			// One-shot: grant ACCESS_KEY_LOGIN to every role in any team's.
			// approved_roles. Required because the access check is AND, not OR.
			\TrustedLogin\Vendor\Capabilities::migrate_approved_roles_to_caps();
		}
		// Persist the `^trustedlogin/s/{token}` rule (registered on `init`.
		// by SecretPage) into the rewrite_rules option. Without this,.
		// `/trustedlogin/s/...` URLs 404 until an admin saves the.
		// Permalinks screen manually.
		flush_rewrite_rules();
	}
);
// Include files and call trustedlogin_connector() function.
if ( is_readable( $trustedlogin_connector_path . 'vendor/autoload.php' ) ) {
	include_once $trustedlogin_connector_path . 'vendor/autoload.php';
	// Include admin init file.
	include_once __DIR__ . '/src/trustedlogin-settings/init.php';

	// This will initialize the plugin.
	$trustedlogin_connector_plugin = trustedlogin_connector();

	// Register the map_meta_cap filter that resolves the virtual.
	// Capabilities::MENU_ACCESS cap. Must run on every request (not just.
	// admin_init) because the top-level admin menu cap is checked by WP.
	// during menu registration on every admin page load — before our.
	// admin_init hooks fire.
	\TrustedLogin\Vendor\Capabilities::register_meta_caps();

	// Idempotent schema migration. Split into two halves:.
	//
	// - DDL on `init` so it runs on every request type (front-end,.
	// wp-cron, REST, and the integration test suite which never.
	// enters admin_init). `CREATE TABLE IF NOT EXISTS` is idempotent.
	// and the table only carries audit rows written through.
	// cap-gated REST endpoints — empty schema is not privileged.
	//
	// - Role/cap grants + rewrite flush on `admin_init` with a.
	// manage_options check. These are the privileged steps; running.
	// them off a subscriber's admin_init traffic would be wrong.
	//
	// Each half gates on its own version option so subsequent requests.
	// no-op once the migration has run.
	add_action(
		'init',
		function () {
			$version = get_option( 'trustedlogin_connector_db_schema_version' );
			if ( TRUSTEDLOGIN_PLUGIN_VERSION === $version ) {
				return;
			}
			\TrustedLogin\Vendor\SecretAudit::create_table();
			update_option( 'trustedlogin_connector_db_schema_version', TRUSTEDLOGIN_PLUGIN_VERSION );
		},
		5
	);

	add_action(
		'admin_init',
		function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$version = get_option( \TrustedLogin\Vendor\Upgrade::DB_VERSION_OPTION );
			if ( TRUSTEDLOGIN_PLUGIN_VERSION === $version ) {
				return;
			}
			// Bundles the cap activation, approved_roles migration, and
			// rewrite flush. Returns false (and skips the db_version bump)
			// when the migration is still pending so this hook retries on
			// the next admin_init instead of stranding agents without their
			// ACCESS_KEY_LOGIN cap.
			\TrustedLogin\Vendor\Upgrade::run( TRUSTEDLOGIN_PLUGIN_VERSION );
		}
	);

	// Maybe register error handler.
	// @phpstan-ignore-next-line.
	if ( TRUSTEDLOGIN_DEBUG || $trustedlogin_connector_plugin->getSettings()->isErrorLogggingEnabled() ) {
		\TrustedLogin\Vendor\ErrorHandler::register();
	}

	/**
	 * Runs when plugin is ready.
	 *
	 * @deprecated 1.1 Use "trustedlogin_connector" action instead.
	 *
	 * @param TrustedLogin\Vendor\Plugin $plugin
	 */
	do_action_deprecated( 'trustedlogin_vendor', array( $trustedlogin_connector_plugin ), '1.1', 'trustedlogin_connector' );

	/**
	 * Runs when plugin is ready.
	 *
	 * @since 1.1
	 *
	 * @param TrustedLogin\Vendor\Plugin $plugin
	 */
	do_action( 'trustedlogin_connector', $trustedlogin_connector_plugin );

	// Add REST API endpoints.
	add_action( 'rest_api_init', array( $trustedlogin_connector_plugin, 'restApiInit' ) );

	// Schedule daily cleanup of expired historical keypairs.
	if ( ! wp_next_scheduled( 'trustedlogin_connector_prune_keypair_history' ) ) {
		wp_schedule_event( time(), 'daily', 'trustedlogin_connector_prune_keypair_history' );
	}
	// Wire to the void-return pruning method, not the data-returning.
	// `getKeypairHistory()` whose prune is only a side effect. Otherwise.
	// a future refactor that separates read from prune silently disables.
	// the cron.
	add_action( 'trustedlogin_connector_prune_keypair_history', array( $trustedlogin_connector_plugin->getEncryption(), 'pruneKeypairHistory' ) );

	// One-time secrets: register cron jobs (GC + per-secret burn).
	\TrustedLogin\Vendor\SecretsCron::register();

	// One-time secrets: register the public recipient page (tl-secret/{token}).
	\TrustedLogin\Vendor\SecretPage::register();

	// Handle access key login if requests.
	add_action( 'template_redirect', array( \TrustedLogin\Vendor\MaybeRedirect::class, 'handle' ) );

	// Handle the "Reset All" button in UI.
	add_action( 'admin_init', array( \TrustedLogin\Vendor\MaybeRedirect::class, 'adminInit' ) );

	// Catch ?tl_attempt=lpat_… appended by the customer-side.
	// Endpoint::fail_login() redirect and forward to the canonical.
	// attempt-detail route on the activity page.
	( new \TrustedLogin\Vendor\LoginAttempts\Intercept() )->bootstrap();

	$trustedlogin_connector_return_screen = new \TrustedLogin\Vendor\ReturnScreen(
		trustedlogin_connector()->getSettings()
	);

	/**
	 * Handle the request to log in to client sites from help desks.
	 *
	 * This request will be validated by {@see \TrustedLogin\Vendor\ReturnScreen::shouldHandle()}.
	 *
	 * The reason for this is to not pass any details about the  all GET parameters from the URL.
	 */
	add_action( 'admin_init', array( $trustedlogin_connector_return_screen, 'callback' ) );

	// Persistent admin notice for a JIT-auto-pinned SaaS verification.
	// key rotation. The notice stays until an admin explicitly dismisses.
	// it (admin-ajax `wp_ajax_trustedlogin_dismiss_pubkey_rotation`),.
	// because silent rotation can also indicate a network-level.
	// interception and shouldn't pass unacknowledged. Scoped to.
	// manage_options users + every wp-admin page (not just TL pages) so.
	// it can't be missed.
	add_action(
		'admin_notices',
		function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$pending = get_option( 'trustedlogin_connector_saas_pubkey_rotation_pending', false );
			if ( ! is_array( $pending ) ) {
				return;
			}

			$old_fp      = isset( $pending['fingerprint_old'] ) ? (string) $pending['fingerprint_old'] : '';
			$new_fp      = isset( $pending['fingerprint_new'] ) ? (string) $pending['fingerprint_new'] : '';
			$rotated_at  = isset( $pending['rotated_at'] ) ? (int) $pending['rotated_at'] : 0;
			$rotated_str = $rotated_at > 0 ? wp_date( 'Y-m-d H:i T', $rotated_at ) : '';

			$dismiss_nonce = wp_create_nonce( 'trustedlogin_dismiss_pubkey_rotation' );
			$ajax_url      = admin_url( 'admin-ajax.php' );
			?>
			<div class="notice notice-warning trustedlogin-pubkey-rotation-notice" data-trustedlogin-nonce="<?php echo esc_attr( $dismiss_nonce ); ?>" data-trustedlogin-ajax="<?php echo esc_url( $ajax_url ); ?>">
				<p><strong><?php esc_html_e( 'TrustedLogin: SaaS envelope signing key was rotated.', 'trustedlogin-connector' ); ?></strong></p>
				<p>
					<?php
					printf(
						/* translators: 1: old fingerprint, 2: new fingerprint, 3: rotated-at timestamp. */
						esc_html__( 'The Connector auto-pinned a new signing key (old %1$s → new %2$s) during a signature retry on %3$s. If this rotation was announced by TrustedLogin, no action is needed. If not, this could indicate a network-level interception — investigate before dismissing.', 'trustedlogin-connector' ),
						esc_html( $old_fp ),
						esc_html( $new_fp ),
						esc_html( $rotated_str )
					);
					?>
				</p>
				<p>
					<button type="button" class="button button-primary trustedlogin-pubkey-rotation-dismiss"><?php esc_html_e( 'I confirm this rotation was expected', 'trustedlogin-connector' ); ?></button>
				</p>
			</div>
			<script>
				( function () {
					var notices = document.querySelectorAll( '.trustedlogin-pubkey-rotation-notice' );
					notices.forEach( function ( notice ) {
						var btn = notice.querySelector( '.trustedlogin-pubkey-rotation-dismiss' );
						if ( ! btn ) { return; }
						btn.addEventListener( 'click', function () {
							btn.disabled = true;
							var body = new URLSearchParams();
							body.set( 'action', 'trustedlogin_dismiss_pubkey_rotation' );
							body.set( '_ajax_nonce', notice.dataset.trustedloginNonce );
							fetch( notice.dataset.trustedloginAjax, {
								method: 'POST',
								credentials: 'same-origin',
								body: body
							} ).then( function ( r ) {
								if ( r.ok ) { notice.style.display = 'none'; }
								else { btn.disabled = false; }
							} ).catch( function () { btn.disabled = false; } );
						} );
					} );
				} )();
			</script>
			<?php
		}
	);

	// Dismiss handler for the rotation notice above. Requires.
	// manage_options + a fresh nonce; clears the pending option so.
	// the notice doesn't reappear until the next auto-rotation.
	add_action(
		'wp_ajax_trustedlogin_dismiss_pubkey_rotation',
		function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( null, 403 );
			}
			check_ajax_referer( 'trustedlogin_dismiss_pubkey_rotation' );
			delete_option( 'trustedlogin_connector_saas_pubkey_rotation_pending' );
			wp_send_json_success();
		}
	);
} else {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Autoloader missing means our own log channel isn't available either.
	error_log(
		sprintf(
		// translators: %s is the error message.
			esc_html__( 'Cannot load TrustedLogin Connector plugin: %s', 'trustedlogin-connector' ),
			esc_html__( 'Autoloader not found.', 'trustedlogin-connector' )
		)
	);

	return;
}

/**
 * Deactivation function.
 *
 * @since 1.1
 * @return void
 */
function trustedlogin_connector_deactivate() {
	delete_option( 'tl_permalinks_flushed' );
	delete_option( 'trustedlogin_vendor_config' );
	delete_option( 'trustedlogin_connector_saas_pubkey_rotation_pending' );

	// Clear the keypair history prune cron. The history option itself is retained.
	// across deactivation (same pattern as the keypair option) so reactivation.
	// doesn't lose access to sealed data still in transit.
	wp_clear_scheduled_hook( 'trustedlogin_connector_prune_keypair_history' );

	\TrustedLogin\Vendor\SecretsCron::deactivate();
}

/**
 * Deactivation function.
 *
 * @deprecated 1.1 Use {@see trustedlogin_connector_deactivate()} instead.
 */
function trustedlogin_vendor_deactivate() {
	_deprecated_function( __FUNCTION__, '1.1', 'trustedlogin_connector_deactivate' );
	trustedlogin_connector_deactivate();
}


/**
 * Accessor for main plugin container.
 *
 * @return \TrustedLogin\Vendor\Plugin;
 */
function trustedlogin_connector() {
	/**
	 * Singleton plugin instance.
	 *
	 * @var \TrustedLogin\Vendor\Plugin
	 */
	static $trustedlogin_connector;

	if ( $trustedlogin_connector ) {
		return $trustedlogin_connector;
	}

	$trustedlogin_connector = new \TrustedLogin\Vendor\Plugin(
		new \TrustedLogin\Vendor\Encryption()
	);

	return $trustedlogin_connector;
}

/**
 * Accessor for main plugin container.
 *
 * @deprecated 1.1 Use {@see trustedlogin_connector()} instead.
 *
 * @return \TrustedLogin\Vendor\Plugin;
 */
function trustedlogin_vendor() {
	_deprecated_function( __FUNCTION__, '1.1', 'trustedlogin_connector()' );

	return trustedlogin_connector();
}

/**
 * Get data to set window.tlVendor object in the dashboard.
 *
 * @since 1.1.0
 *
 * @param SettingsApi $settingsApi Parameter.
 *
 * @return array
 */
function trustedlogin_connector_prepare_data( SettingsApi $settingsApi ) {
	$accessKey = AccessKeyLogin::fromRequest( true );
	$accountId = AccessKeyLogin::fromRequest( false );
	$helpdesk  = $settingsApi->toArray()['teams'][0]['helpdesk'][0] ?? 'helpscout';

	// Current user's resolved TL capabilities, keyed by cap slug with.
	// bool values. Exposed as `window.tlVendor.capabilities` so React.
	// components can gate buttons, sections, and REST calls without a.
	// client-side cap-lookup round-trip. Values come from.
	// current_user_can(), which re-reads role caps per request, so.
	// they reflect the Permissions matrix's current state at page.
	// render time.
	$current_user_caps = array();
	foreach ( \TrustedLogin\Vendor\Capabilities::all() as $cap_slug ) {
		$current_user_caps[ $cap_slug ] = \TrustedLogin\Vendor\Capabilities::current_user_can( $cap_slug );
	}

	// Teams the current user is permitted to view activity for. Drives.
	// the Activity page's team dropdown — unauthorized teams are.
	// omitted from the payload entirely so they never reach the DOM,.
	// not just hidden via a CSS rule that a local tampering attack.
	// could flip. REST-layer enforcement lives in.
	// Activity::user_can_access_team — both layers must agree or the.
	// user sees a dropdown option that 403s when selected.
	$activity_teams = array();
	try {
		foreach ( $settingsApi->allTeams() as $_team ) {
			if ( \TrustedLogin\Vendor\Endpoints\Activity::current_user_is_approved_for_team( $_team ) ) {
				$activity_teams[] = array(
					'account_id' => (int) $_team->get( 'account_id' ),
					'name'       => (string) $_team->get( 'name' ),
				);
			}
		}
	} catch ( \Exception $e ) {
		$activity_teams = array();
	}

	$data = array(
		'roles'          => wp_roles()->get_names(),
		'onboarding'     => Onboarding::hasOnboarded() ? 'COMPLETE' : '0',
		'capabilities'   => $current_user_caps,
		// `manage_options` is a WP core cap, not a TL cap, so it isn't.
		// in the capabilities bag above (which is filtered to TL caps.
		// per the allowlist). React surfaces that only WP admins should.
		// see — Teams page, integrator-facing TopBar controls, the.
		// "Need Help?" docs link — gate on this flag.
		'is_admin'       => current_user_can( 'manage_options' ),
		'activity_teams' => $activity_teams,
		'accessKey'      => array(
			AccessKeyLogin::ACCOUNT_ID_INPUT_NAME => $accountId,
			AccessKeyLogin::ACCESS_KEY_INPUT_NAME => $accessKey,
			AccessKeyLogin::REDIRECT_ENDPOINT     => true,
			'action'                              => AccessKeyLogin::ACCESS_KEY_ACTION_NAME,
			Factory::PROVIDER_KEY                 => $helpdesk,
			AccessKeyLogin::NONCE_NAME            => wp_create_nonce( AccessKeyLogin::NONCE_ACTION ),
		),
		'settings'       => $settingsApi->toResponseData(),
		// 0..6 (Sun..Sat) — drives which day the Activity chart.
		// anchors its x-axis ticks to.
		'start_of_week'  => (int) get_option( 'start_of_week', 0 ),
	);

	// manage_options-only payload.
	if ( current_user_can( 'manage_options' ) ) {
		$data['log_file_name'] = trustedlogin_connector()->getLogFileName( false );
		$data['resetAction']   = esc_url_raw( Reset::actionUrl() );
	}

	// Pre-fill redirectData when the deep-link carries an access key.
	// and the current user holds ACCESS_KEY_LOGIN. The cap gate is the.
	// authorization barrier on this path — the deep-link is rendered.
	// only inside wp-admin pages and only for users who can already.
	// initiate a support login.
	if (
		! empty( $accessKey )
		&& ! empty( $accountId )
		&& \TrustedLogin\Vendor\Capabilities::current_user_can( \TrustedLogin\Vendor\Capabilities::ACCESS_KEY_LOGIN )
	) {
		$handler = new AccessKeyLogin();
		$parts   = $handler->handle(
			array(
				AccessKeyLogin::ACCOUNT_ID_INPUT_NAME => $accountId,
				AccessKeyLogin::ACCESS_KEY_INPUT_NAME => $accessKey,
			),
			true
		);
		if ( ! is_wp_error( $parts ) ) {
			$data['redirectData'] = $parts;
		}
	}

	return $data;
}

/**
 * Prepare plugin settings data for output (deprecated wrapper).
 *
 * @deprecated 1.1 Use {@see trustedlogin_connector_prepare_data()} instead.
 *
 * @param SettingsApi $settingsApi Parameter.
 *
 * @return array
 */
function trusted_login_vendor_prepare_data( SettingsApi $settingsApi ) {
	_deprecated_function( __FUNCTION__, '1.1', 'trustedlogin_connector_prepare_data()' );

	return trustedlogin_connector_prepare_data( $settingsApi );
}

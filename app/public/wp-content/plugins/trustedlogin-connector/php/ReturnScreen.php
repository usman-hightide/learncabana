<?php
/**
 * Return screen handler for webhook/helpdesk redirect flow.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

/**
 * Validates access key and displays the return screen mini-app.
 */
class ReturnScreen {


	/**
	 * Script + style handle for the return-screen bundle.
	 *
	 * @since 2.0.0
	 */
	const ASSET_HANDLE = 'trustedlogin-return-screen';

	/**
	 * Settings API instance.
	 *
	 * @var SettingsApi
	 */
	protected $settings;

	/**
	 * Initializes the return screen handler.
	 *
	 * @param SettingsApi $settings Settings API instance.
	 */
	public function __construct( SettingsApi $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Should we attempt to handle this request?
	 *
	 * Both `ak` and `ak_account_id` must be present, and the caller
	 * must hold {@see Capabilities::ACCESS_KEY_LOGIN}.
	 *
	 * @return bool
	 */
	public function shouldHandle() {
		if (
			empty( AccessKeyLogin::fromRequest( true ) )
			|| empty( AccessKeyLogin::fromRequest( false ) )
		) {
			return false;
		}

		return Capabilities::current_user_can( Capabilities::ACCESS_KEY_LOGIN );
	}

	/**
	 * Render the return-screen mini-app and exit.
	 *
	 * @uses "admin_init"
	 *
	 * @return void
	 */
	public function callback() {
		if ( ! $this->shouldHandle() ) {
			return;
		}

		$data = trustedlogin_connector_prepare_data( $this->settings );

		if ( ! isset( $data['redirectData'] ) ) {
			return;
		}

		$plugin_dir_url  = plugin_dir_url( TRUSTEDLOGIN_PLUGIN_FILE );
		$plugin_dir_path = plugin_dir_path( TRUSTEDLOGIN_PLUGIN_FILE );

		$asset_path = $plugin_dir_path . 'wpbuild/return-screen.asset.php';

		if ( ! is_readable( $asset_path ) ) {
			wp_die(
				sprintf(
					// translators: %s is the replaced by the error message.
					esc_html__( 'Cannot load TrustedLogin Connector plugin: %s', 'trustedlogin-connector' ),
					esc_html__( 'A required asset manifest was not found. Please re-install the plugin.', 'trustedlogin-connector' )
				),
				esc_html__( 'Asset manifest not found.', 'trustedlogin-connector' ),
				424
			);
		}

		$asset = include $asset_path;

		wp_register_script(
			self::ASSET_HANDLE,
			$plugin_dir_url . 'wpbuild/return-screen.js',
			isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
			isset( $asset['version'] ) ? $asset['version'] : TRUSTEDLOGIN_PLUGIN_VERSION,
			// $in_footer=false is intentional. The render uses
			// `wp_print_scripts()` manually after the #root div in the
			// body — see callback() below. Setting `true` here registers
			// the script in WP's "in_footer" list, which can cause an
			// auto-print before the explicit wp_print_scripts() call,
			// landing the bundle in <head> and blanking the page.
			false
		);

		// JSON_HEX_* flags so customer-controlled values inside the envelope
		// (siteUrl, decrypted identifier) cannot break out of the surrounding
		// <script> tag. The escapes are inert at runtime — the JS engine
		// parses < back to `<`, so the React app sees identical strings.
		$payload = wp_json_encode(
			$data,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		wp_add_inline_script(
			self::ASSET_HANDLE,
			'window.tlVendor=' . $payload . ';',
			'before'
		);
		wp_enqueue_script( self::ASSET_HANDLE );

		// Tailwind dist CSS has a stable filename, so use filemtime as the
		// version to bust caches after a rebuild. file_exists() can return
		// true while filemtime() returns false (e.g. opcache + missing
		// stat permission); guard so the cache-buster never collapses to ''.
		$css_path    = $plugin_dir_path . 'src/trustedlogin-dist.css';
		$css_url     = $plugin_dir_url . 'src/trustedlogin-dist.css';
		$css_mtime   = file_exists( $css_path ) ? filemtime( $css_path ) : false;
		$css_version = false !== $css_mtime ? (string) $css_mtime : TRUSTEDLOGIN_PLUGIN_VERSION;
		wp_register_style( self::ASSET_HANDLE, $css_url, array(), $css_version );
		wp_enqueue_style( self::ASSET_HANDLE );

		$favicon_url = $plugin_dir_url . 'assets/tlfavicon.ico';

		?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<meta name="theme-color" content="#000000" />
	<meta name="description" content="<?php esc_attr_e( 'TrustedLogin Access Key Login', 'trustedlogin-connector' ); ?>" />
	<link rel="icon" href="<?php echo esc_url( $favicon_url ); ?>" />
	<title><?php esc_html_e( 'TrustedLogin Access Key Login', 'trustedlogin-connector' ); ?></title>
		<?php wp_print_styles( array( self::ASSET_HANDLE ) ); ?>
</head>
<body>
	<noscript><?php esc_html_e( 'You need to enable JavaScript to run this app.', 'trustedlogin-connector' ); ?></noscript>
	<div id="root"></div>
		<?php
		// Scripts go after #root so the bundle's synchronous
		// `render(<App/>, document.getElementById("root"))` finds the
		// container in the DOM. Putting them in <head> blanks the page.
		wp_print_scripts( array( self::ASSET_HANDLE ) );
		?>
</body>
</html>
		<?php
		exit;
	}
}

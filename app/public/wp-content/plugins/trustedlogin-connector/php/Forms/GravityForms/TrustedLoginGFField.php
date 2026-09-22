<?php
/**
 * TrustedLoginGFField implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Forms\GravityForms;

use GF_Field;
use GFAPI;
use TrustedLogin\Vendor\AccessKeyLogin;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}
/**
 * TrustedLoginGFField implementation.
 */
class TrustedLoginGFField extends GF_Field {

	/**
	 * Gravity Forms field type identifier.
	 *
	 * @var string
	 */
	public $type = 'trustedlogin';


	/**
	 * TrustedLogin vendor namespace.
	 *
	 * @var string
	 */
	public $tlNamespace = '';

	/**
	 * TrustedLogin vendor name.
	 *
	 * @var string
	 */
	public $tlVendor = '';

	/**
	 * TrustedLogin team/account ID used when constructing the "Log in with
	 * TrustedLogin" link in the entry detail view. Optional — when empty
	 * and there's exactly one configured team, we fall back to its ID.
	 *
	 * @var string
	 */
	public $tlAccountId = '';

	/**
	 * Heading text shown above the URL input. Values:
	 *   - null  — admin hasn't configured the setting; show the default heading.
	 *   - ''    — admin explicitly blanked the setting; suppress the heading.
	 *   - other — custom heading text.
	 *
	 * @var string|null
	 */
	public $tlHeadingText = null;

	/**
	 * Per-request guard so the action hooks below register exactly once.
	 *
	 * GF_Field subclasses are instantiated repeatedly during form
	 * processing (render, validate, save). Registering against
	 * `[ $this, 'method' ]` produces a fresh callable for each new
	 * instance, which WP doesn't deduplicate — the static gate
	 * guarantees we register hooks once per request.
	 *
	 * @var bool
	 */
	private static $hooks_registered = false;

	/**
	 * Initialize the TrustedLogin field.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data Field configuration data.
	 */
	public function __construct( $data = array() ) {

		parent::__construct( $data );

		$this->init_default_settings();

		if ( ! self::$hooks_registered ) {
			add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_init', array( $this, 'add_custom_settings' ) );
			// Whitelist the field's CSS handle for GF's no-conflict mode so
			// it isn't dropped on Gravity Forms admin pages.
			add_filter( 'gform_noconflict_styles', array( $this, 'register_noconflict_style' ) );
			add_filter( 'gform_noconflict_scripts', array( $this, 'register_noconflict_script' ) );
			self::$hooks_registered = true;
		}
	}

	/**
	 * Enables default values.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	protected function init_default_settings() {
		// GF stamps new fields with __( 'Untitled', 'gravityforms' ),
		// which is localised — compare against both the source string
		// and the translated form before stamping our own default.
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Intentional cross-domain lookup of GF's "Untitled" translation.
		$gf_untitled = function_exists( '__' ) ? __( 'Untitled', 'gravityforms' ) : 'Untitled';

		if ( ! $this->label || 'Untitled' === $this->label || $gf_untitled === $this->label ) {
			$this->label = esc_html__( 'Site URL', 'trustedlogin-connector' );
		}

		if ( ! $this->placeholder ) {
			// URL scheme — not translatable. Rendered through esc_attr() at output time.
			$this->placeholder = 'https://';
		}
	}

	/**
	 * Adds custom settings.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function add_custom_settings() {
		$tooltip_template = '<h6>[title]</h6>[content]';

		new GFFieldSetting(
			'tl_namespace',
			__( 'TrustedLogin Namespace', 'trustedlogin-connector' ),
			strtr(
				$tooltip_template,
				array(
					'[title]'   => __( 'TrustedLogin Namespace', 'trustedlogin-connector' ),
					'[content]' => __( 'Please provide your TrustedLogin Namespace', 'trustedlogin-connector' ),
				)
			)
		);
		new GFFieldSetting(
			'tl_vendor',
			__( 'Vendor Name', 'trustedlogin-connector' ),
			strtr(
				$tooltip_template,
				array(
					'[title]'   => __( 'Vendor Name', 'trustedlogin-connector' ),
					'[content]' => __( 'Please provide your Vendor Name', 'trustedlogin-connector' ),
				)
			),
			__( 'Vendor', 'trustedlogin-connector' ),
		);
		new GFFieldSetting(
			'tl_account_id',
			__( 'TrustedLogin Team Account ID', 'trustedlogin-connector' ),
			strtr(
				$tooltip_template,
				array(
					'[title]'   => __( 'TrustedLogin Team Account ID', 'trustedlogin-connector' ),
					'[content]' => __( 'Optional. The team to log into from this entry. Leave blank to auto-select when only one team is configured.', 'trustedlogin-connector' ),
				)
			),
			'',
			'gform_field_standard_settings',
			'number'
		);
		// Heading text only — the Grant button cycles through state labels
		// ("Granting…", "Access Granted!", etc.) that are already localized,
		// so customising just the idle label would produce incoherent flows.
		// Keep the static heading overridable; leave the button labels to i18n.
		new GFFieldSetting(
			'tl_heading_text',
			__( 'Heading Text', 'trustedlogin-connector' ),
			strtr(
				$tooltip_template,
				array(
					'[title]'   => __( 'Heading Text', 'trustedlogin-connector' ),
					'[content]' => __( 'Heading shown above the URL input. Defaults to "Grant Access with TrustedLogin".', 'trustedlogin-connector' ),
				)
			),
			__( 'Grant Access with TrustedLogin', 'trustedlogin-connector' ),
			'gform_field_appearance_settings'
		);
	}

	/**
	 * Enqueues the field styles on admin pages.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		// Use Gravity Forms' canonical page detector instead of WP screen
		// hooks — the form-editor screen isn't tied to a single hook
		// (it varies by GF menu placement), but `GFForms::get_page()`
		// always returns 'form_editor' on /wp-admin/admin.php?page=gf_edit_forms&id=N.
		if ( ! class_exists( 'GFForms' ) || 'form_editor' !== \GFForms::get_page() ) {
			return;
		}

		$this->enqueue_field_style();
	}

	/**
	 * Adds the field stylesheet handle to GF's no-conflict allow-list so
	 * Gravity Forms doesn't strip it on its admin screens.
	 *
	 * @since 2.0.0
	 *
	 * @param array $styles Existing allow-listed style handles.
	 *
	 * @return array
	 */
	public function register_noconflict_style( $styles ) {
		$styles[] = 'tl-field-css';
		return $styles;
	}

	/**
	 * Adds the field script handle to GF's no-conflict allow-list.
	 *
	 * @since 2.0.0
	 *
	 * @param array $scripts Existing allow-listed script handles.
	 *
	 * @return array
	 */
	public function register_noconflict_script( $scripts ) {
		$scripts[] = 'tl-field-js';
		return $scripts;
	}

	/**
	 * Enqueues the field scripts.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form Parameter.
	 * @param bool  $is_ajax Parameter.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $form, $is_ajax ) {
		if ( $is_ajax || ! $this->has_tl_field( $form ) ) {
			return;
		}

		$script_path = plugin_dir_path( TRUSTEDLOGIN_PLUGIN_FILE ) . 'assets/forms/tl-field.js';
		$script_url  = plugin_dir_url( TRUSTEDLOGIN_PLUGIN_FILE ) . 'assets/forms/tl-field.js';

		wp_enqueue_script(
			'tl-field-js',
			$script_url,
			array( 'jquery' ),
			file_exists( $script_path ) ? filemtime( $script_path ) : null,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Localize the script. Keys ending in `…Missing`, `popupBlocked`,
		// `popupClosedEarly`, `revokeTimeout`, `grantFailed`, `revokeFailed`,
		// `openManually`, `invalidUrl` are user-facing fallback messages used
		// by tl-field.js when a postMessage event cannot complete normally.
		$translations = array(
			'accessGranted'     => esc_html__( 'Access Granted!', 'trustedlogin-connector' ),
			'accessRevoked'     => esc_html__( 'Access Revoked!', 'trustedlogin-connector' ),
			'granting'          => esc_html__( 'Granting Access…', 'trustedlogin-connector' ),
			'revoking'          => esc_html__( 'Revoking Access…', 'trustedlogin-connector' ),
			'grantAccess'       => esc_html__( 'Grant Access', 'trustedlogin-connector' ),
			'grantAccessWithTl' => esc_html__( 'Grant Access with TrustedLogin', 'trustedlogin-connector' ),
			'revokeAccess'      => esc_html__( 'Revoke Access', 'trustedlogin-connector' ),
			'hasAccessToSite'   => esc_html__( '[vendor] has access to [login_site]', 'trustedlogin-connector' ),
			'vendorName'        => $this->tlVendor ? esc_html( $this->tlVendor ) : esc_html__( 'Vendor', 'trustedlogin-connector' ),
			'textCopied'        => esc_html__( 'The access key has been copied to your clipboard.', 'trustedlogin-connector' ),
			'textCopiedShort'   => esc_html__( 'Copied!', 'trustedlogin-connector' ),
			// Fallback messages for failure modes.
			'invalidUrl'        => esc_html__( 'Please enter a valid URL (for example, https://example.com).', 'trustedlogin-connector' ),
			'popupBlocked'      => esc_html__( 'Your browser blocked the popup. Click the link below to grant access in a new tab.', 'trustedlogin-connector' ),
			'openManually'      => esc_html__( 'Open grant-access page in a new tab', 'trustedlogin-connector' ),
			'popupClosedEarly'  => esc_html__( 'The grant window was closed before completing. Please try again.', 'trustedlogin-connector' ),
			'keyMissing'        => esc_html__( 'The grant response did not include an access key. Please try again.', 'trustedlogin-connector' ),
			'grantFailed'       => esc_html__( 'Grant failed. Please try again.', 'trustedlogin-connector' ),
			'revokeFailed'      => esc_html__( 'Revoke failed. Please try again.', 'trustedlogin-connector' ),
			'revokeTimeout'     => esc_html__( 'The revoke request is taking longer than expected. If access is still active, please try again.', 'trustedlogin-connector' ),
			// Manual access-key entry — shown when the popup flow fails
			// to deliver a key back to the parent page (popup blocked,
			// closed early, opener lost, etc). The user can copy the
			// key from the client's "Access granted" screen and paste
			// it here to recover without restarting the grant.
			'manualKeyLabel'    => esc_html__( 'Or enter the access key manually', 'trustedlogin-connector' ),
			'manualKeyHint'     => esc_html__( 'Copy the access key shown on the client site after granting access, then paste it here.', 'trustedlogin-connector' ),
			// `manualKeyPlaceholder` is intentionally NOT localized — access
			// keys are 64 lowercase hex characters in every locale, so the
			// illustrative pattern is hardcoded JS-side in tl-field.js.
			'manualKeySubmit'   => esc_html__( 'Use this key', 'trustedlogin-connector' ),
			'manualKeyMissing'  => esc_html__( 'Please paste the access key before continuing.', 'trustedlogin-connector' ),
		);
		wp_localize_script( 'tl-field-js', 'tl_field_vars', $translations );

		$this->enqueue_field_style();
	}

	/**
	 * Enqueues the field style.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	protected function enqueue_field_style() {
		$style_path = plugin_dir_path( TRUSTEDLOGIN_PLUGIN_FILE ) . 'assets/forms/tl-field.css';
		$style_url  = plugin_dir_url( TRUSTEDLOGIN_PLUGIN_FILE ) . 'assets/forms/tl-field.css';

		wp_enqueue_style(
			'tl-field-css',
			$style_url,
			array(),
			file_exists( $style_path ) ? filemtime( $style_path ) : null
		);
	}

	/**
	 * Checks if the form has the TrustedLogin field.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form Parameter.
	 *
	 * @return bool
	 */
	public function has_tl_field( $form ) {

		$fields = GFAPI::get_fields_by_type( $form, $this->type );

		return ! empty( $fields );
	}

	/**
	 * Enables conditional logic rules to hide the Submit button.
	 *
	 * @inheritDoc
	 *
	 * @since 2.0.0
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Force full-width layout on the field container.
	 *
	 * GF applies the `gfield--width-{size}` modifier class to the wrapper
	 * based on this field method. Returning 'full' ensures the input and
	 * Grant Access controls occupy the full form column regardless of the
	 * user's width choice — the field's internal layout already balances
	 * the logo + input + submit button, and a half-width field clips it.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_css_class() {
		$class = parent::get_css_class();
		if ( false === strpos( $class, 'gfield--width-full' ) ) {
			$class .= ' gfield--width-full';
		}
		return $class;
	}

	/**
	 * Returns the field button properties for the form editor.
	 *
	 * @since 2.0.0
	 *
	 * @inheritDoc
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
		);
	}

	/**
	 * Returns the field title.
	 *
	 * @since 2.0.0
	 *
	 * @inheritDoc
	 */
	public function get_form_editor_field_title() {
		return esc_html__( 'TrustedLogin', 'trustedlogin-connector' );
	}

	/**
	 * Provides form editor field settings.
	 *
	 * @since 2.0.0
	 *
	 * @inheritDoc
	 */
	public function get_form_editor_field_settings() {
		return array(
			'label_setting',
			'description_setting',
			'placeholder_setting',
			'css_class_setting',
			'tl_namespace_setting',
			'tl_vendor_setting',
			'tl_account_id_setting',
			'tl_heading_text_setting',
			'tl_connector_field_needs_setup',
		);
	}

	/**
	 * Disable the default field label and provide custom structure instead.
	 *
	 * @since 2.0.0
	 *
	 * @param bool  $force_frontend_label Whether to force the frontend label.
	 * @param mixed $value The field value.
	 *
	 * @return string
	 */
	public function get_field_label( $force_frontend_label, $value ) {
		return '';
	}

	/**
	 * Provides the field HTML.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $form The form object.
	 * @param string $value The field value.
	 * @param array  $entry The entry object.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		if ( empty( $this->tlNamespace ) ) {
			return $this->show_setup_notice(
				esc_html__( 'Please provide your TrustedLogin Namespace', 'trustedlogin-connector' ),
				'warning',
				'tl_namespace',
			);
		}

		// Resolve the effective label placement: the field's own labelPlacement
		// (if set) wins, otherwise fall back to the form-level labelPlacement.
		// GF supports top_label (default), left_label, right_label, hidden_label,
		// plus the per-field "inputs_above_labels" (label below input).
		$label_placement = '';
		if ( ! empty( $this->labelPlacement ) ) {
			$label_placement = (string) $this->labelPlacement;
		} elseif ( ! empty( $form['labelPlacement'] ) ) {
			$label_placement = (string) $form['labelPlacement'];
		}

		switch ( $label_placement ) {
			case 'hidden_label':
				$field_label_pos = 'tl-label-hidden';
				break;
			case 'left_label':
				$field_label_pos = 'tl-label-left';
				break;
			case 'right_label':
				$field_label_pos = 'tl-label-right';
				break;
			case 'inputs_above_labels':
				$field_label_pos = 'tl-label-below';
				break;
			case 'top_label':
			default:
				$field_label_pos = 'tl-label-above';
				break;
		}

		$placeholders = array(
			'[field_id]'              => esc_attr( $this->id ),
			'[form_id]'               => esc_attr( $form['id'] ),
			'[url_id]'                => esc_attr( 'input_url_' . $form['id'] . '_' . $this->id ),
			'[namespace_id]'          => esc_attr( 'input_' . $form['id'] . '_' . $this->id ),
			'[button_id]'             => esc_attr( 'gform_submit_button_' . $this->id ),
			'[field_name]'            => esc_attr( 'input_' . $this->id ),
			'[label_pos]'             => esc_attr( $field_label_pos ),
			// Use the field-editor-supplied label (defaults to "Site URL" via
			// init_default_settings()). Form editors can rename it in the GF
			// editor's Field Label input just like any other field.
			'[label]'                 => esc_html( $this->label ),
			'[placeholder]'           => esc_attr( $this->placeholder ),
			'[namespace]'             => esc_attr( $this->tlNamespace ),
			'[copied]'                => esc_html_x( 'Copied!', 'Text shown when the access key has been copied to your clipboard.', 'trustedlogin-connector' ),
			'[error]'                 => esc_html__( 'Error', 'trustedlogin-connector' ),
			// Heading renders only when the setting has a non-empty value; if
			// the admin cleared "Heading Text", omit the .tl-header block
			// entirely instead of rendering an empty label.
			'[header]'                => $this->render_header( 'input_url_' . $form['id'] . '_' . $this->id ),
			'[grant_access]'          => esc_attr__( 'Grant Access', 'trustedlogin-connector' ),
			'[key_title]'             => esc_html__( 'Site Access Key:', 'trustedlogin-connector' ),
			'[copy_key]'              => esc_attr__( 'Copy Site Key', 'trustedlogin-connector' ),
			'[disabled_if_url_empty]' => empty( $value ) ? 'disabled="disabled"' : '',
		);

		$field = <<<'HTML'
    <div class="tl-grant-access gform-theme__disable-reset">
        [header]

        <div class="tl-input">
            <div class="tl-progress" style="width: 0%;"></div>
            <div class="tl-logo">
                <svg id="eVRu5XlC1mq1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 72 72" width="72" height="72" shape-rendering="geometricPrecision" text-rendering="geometricPrecision"><g id="eVRu5XlC1mq10" transform="translate(24.138892 17.927138)" clip-path="url(#eVRu5XlC1mq8)"><g><path d="M11.8612,0.140625c-4.34515,0-7.88683,3.510095-7.88683,7.816465v6.46771h3.37771v-6.46771c0-2.47007,2.0168-4.46887,4.50912-4.46887c2.4759,0,4.5091,1.9988,4.5091,4.46887v6.46771h3.3777v-6.46771c0-4.30637-3.5253-7.816465-7.8868-7.816465Z" fill="#1099d6"/><path d="M11.8611,12.377c-6.31271,0-11.428483.78-11.428483,1.755v12.9679c0,6.2564,7.788433,8.9052,11.428483,8.9052s11.4285-2.6488,11.4285-8.9052v-12.9679c0-.975-5.1158-1.755-11.4285-1.755Z" fill="#1b2b59"/><path d="M11.861,15.6602c-3.77124,0-6.96859,2.4863-8.01798,5.8826h2.42671C7.22074,19.414,9.3687,17.919,11.861,17.919c3.3613,0,6.0996,2.7138,6.0996,6.0451c0,3.3314-2.7383,6.0452-6.0996,6.0452-2.4923,0-4.64026-1.495-5.59127-3.6239h-2.42671c1.04939,3.3964,4.24674,5.8827,8.01798,5.8827c4.6239,0,8.3787-3.7213,8.3787-8.304c0-4.5826-3.7548-8.3039-8.3787-8.3039Z" fill="#fff"/><path d="M15.0912,23.5904L10.451,20.9253c-.3608-.2113-.65591-.0325-.65591.3738v1.5925h-9.346115v2.1126h9.329715v1.5925c0,.4063.29511.585.65591.3738l4.6402-2.6651c.3608-.1788.3608-.5038.0164-.715Z" fill="#fff"/></g><clipPath id="eVRu5XlC1mq8"><rect width="24" height="36" rx="0" ry="0" fill="#fff"/></clipPath></g><g transform="translate(0 0.000005)"><ellipse id="eVRu5XlC1mq11" rx="34.000034" ry="34.000034" transform="matrix(.999999 0 0 0.999999 36 36)" fill="none" stroke="#ddd"/><ellipse id="eVRu5XlC1mq12" rx="34.000034" ry="34.000034" transform="matrix(.999999 0 0 0.999999 36.000036 36)" fill="none" stroke="#119a27" stroke-width="2" stroke-linecap="round" stroke-linejoin="bevel" stroke-dashoffset="213.63" stroke-dasharray="213.63"/></g><g id="eVRu5XlC1mq13" transform="matrix(.634916 0 0 0.634916 23.936596 23.936596)" opacity="0"><g clip-path="url(#eVRu5XlC1mq18)"><g><rect width="34" height="34" rx="17" ry="17" transform="translate(2 2)" fill="#fff"/><path d="M19,2C9.65,2,2,9.65,2,19s7.65,17,17,17s17-7.65,17-17-7.65-17-17-17ZM16.875,26.225L9.65,19l2.975-2.975l4.25,4.25l8.5-8.5L28.35,14.75L16.875,26.225Z" fill="#119a27"/></g><clipPath id="eVRu5XlC1mq18"><rect width="34" height="34" rx="17" ry="17" transform="translate(2 2)" fill="#fff"/></clipPath></g><rect width="36" height="36" rx="18" ry="18" transform="translate(1 1)" fill="none" stroke="#fff" stroke-width="2"/></g></svg>
                <svg id="eVRu5XlC1mq2" width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 0C7.65 0 0 7.65 0 17C0 26.35 7.65 34 17 34C26.35 34 34 26.35 34 17C34 7.65 26.35 0 17 0ZM14.875 24.225L7.65 17L10.625 14.025L14.875 18.275L23.375 9.775L26.35 12.75L14.875 24.225Z" fill="#119A27"/></svg>
            </div>
            <div class="tl-field [label_pos]">
                <label class="gfield_label gform-field-label tl-field-label" for="[url_id]">[label]</label>
                <div class="ginput_container ginput_container_website">
                    <input id="[url_id]" class="tl-site-url large" type="url" data-namespace="[namespace]" placeholder="[placeholder]">
                </div>
            </div>
            <div class="tl-submit">
                <input type="submit" id="[button_id]" class="gform_button button" value="[grant_access]" [disabled_if_url_empty]>
            </div>
        </div>
        <div class="tl-footer">
            <span>[key_title]</span>
            <div class="tl-site-key-row">
                <span class="tl-site-key">[error]</span>
                <span class="tl-key-copied-message">[copied]</span>
                <input name="[field_name]" id="[namespace_id]" type="hidden" data-namespace="[namespace]" class="tl-field-value" />
                <button type="button" id="tl-copy-key" aria-label="[copy_key]" title="[copy_key]">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 4H2C1.4 4 1 4.4 1 5V15C1 15.6 1.4 16 2 16H10C10.6 16 11 15.6 11 15V5C11 4.4 10.6 4 10 4Z" fill="#565D67"/><path d="M14 0H4V2H13V13H15V1C15 0.4 14.6 0 14 0Z" fill="#565D67"/></svg>
                </button>
            </div>                
        </div>
    </div>
HTML;

		return strtr( $field, $placeholders );
	}

	/**
	 * Render the heading block, or an empty string if the admin has
	 * cleared the "Heading Text" setting. The setting's default is
	 * "Grant Access with TrustedLogin"; saving an explicit blank string
	 * suppresses the header entirely.
	 *
	 * @param string $url_input_id ID of the URL input the heading's `<label>` targets.
	 *
	 * @return string
	 */
	protected function render_header( $url_input_id = 'tl-grant-access-url' ) {
		// null  = never configured → use default.
		// ''    = admin explicitly blanked it → suppress the whole header.
		// other = custom heading.
		if ( '' === $this->tlHeadingText ) {
			return '';
		}

		$text = ( null === $this->tlHeadingText || ! is_string( $this->tlHeadingText ) )
			? __( 'Grant Access with TrustedLogin', 'trustedlogin-connector' )
			: $this->tlHeadingText;

		return sprintf(
			'<div class="tl-header"><label for="%s">%s</label></div>',
			esc_attr( $url_input_id ),
			esc_html( $text )
		);
	}

	/**
	 * Parse the stored field value back into its components.
	 *
	 * The frontend JS concatenates the site URL + access key with a
	 * "🔗" separator when the user grants access, so stored values look
	 * like "https://example.com 🔗 TL.abc123...". Here we split them
	 * back out for display; if the string doesn't contain the separator
	 * we assume it's a URL-only value.
	 *
	 * @param string $value Raw stored field value.
	 * @return array{site_url: string, access_key: string}
	 */
	protected function parse_stored_value( $value ) {
		$value = (string) $value;
		if ( false !== strpos( $value, "\u{1F517}" ) ) {
			list( $site_url, $access_key ) = array_map( 'trim', explode( "\u{1F517}", $value, 2 ) );
		} else {
			$site_url   = trim( $value );
			$access_key = '';
		}
		return array(
			'site_url'   => $site_url,
			'access_key' => $access_key,
		);
	}

	/**
	 * Build the "Log in with TrustedLogin" URL for a given access key.
	 *
	 * Points at the connector's Access Key Login admin page with `ak`
	 * prefilled. Support staff clicks the link, lands on the
	 * already-populated form, and confirms with one click to log in.
	 *
	 * @param string $access_key Parameter.
	 * @return string
	 */
	protected function build_login_url( $access_key ) {
		if ( '' === $access_key ) {
			return '';
		}

		$args = array(
			'page'                                => AccessKeyLogin::PAGE_SLUG,
			AccessKeyLogin::ACCESS_KEY_INPUT_NAME => rawurlencode( $access_key ),
		);

		// When both `ak_account_id` and `ak` are present, the Access Key Login
		// page's init handler skips the nonce check, pre-fetches redirectData
		// via AccessKeyLogin::handle(), and the React AccessKeyForm auto-
		// redirects the agent into the logged-in session without another
		// click. Mirrors the Help Scout widget's login-link pattern.
		$account_id = $this->resolve_account_id();
		if ( '' !== $account_id ) {
			$args[ AccessKeyLogin::ACCOUNT_ID_INPUT_NAME ] = rawurlencode( $account_id );
		}

		// Returns a raw (NOT HTML-encoded) URL — `esc_url()` mangles `&`
		// to `&amp;` which breaks plain-text contexts like notification
		// emails and CSV exports. All HTML render sites wrap with
		// esc_url() at output.
		return esc_url_raw( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	}

	/**
	 * Resolve the team/account ID that should be used for this field's
	 * "Log in with TrustedLogin" link.
	 *
	 * The field's `tl_account_id` setting wins when set. Otherwise fall
	 * back to the single configured team when there's only one — covering
	 * the common case where admins haven't explicitly mapped the GF field
	 * to a specific team.
	 *
	 * @return string Account ID (string to preserve leading zeros / callers),
	 *                or empty string when no determinate match is available.
	 */
	protected function resolve_account_id() {
		if ( ! empty( $this->tlAccountId ) ) {
			return (string) $this->tlAccountId;
		}

		if ( ! class_exists( '\\TrustedLogin\\Vendor\\SettingsApi' ) ) {
			return '';
		}

		$teams = \TrustedLogin\Vendor\SettingsApi::fromSaved()->allTeams();
		if ( count( $teams ) === 1 ) {
			$team = reset( $teams );
			return (string) $team->get( 'account_id' );
		}

		return '';
	}

	/**
	 * Display the field value on the entry-detail page.
	 *
	 * Renders three rows: "Site URL: <url>", "Access Key: <key>", and a
	 * "Log in with TrustedLogin" button that opens the connector's
	 * access-key login page in a new tab with the key pre-filled.
	 *
	 * @since 2.0.0
	 *
	 * @inheritDoc
	 *
	 * @param string $value The field value.
	 * @param string $currency Optional. The currency code.
	 * @param bool   $use_text Optional. Whether to use text format.
	 * @param string $format Optional. The output format (html, text, etc.).
	 * @param string $media Optional. The media type (screen, print, etc.).
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		$parts = $this->parse_stored_value( $value );

		if ( '' === $parts['site_url'] && '' === $parts['access_key'] ) {
			return '';
		}

		// Text/email formats (CSV exports, notifications): plain text.
		// Labels share the html branch's text domain so translations stay
		// consistent across renderings.
		if ( 'html' !== $format ) {
			$lines = array();
			if ( '' !== $parts['site_url'] ) {
				$lines[] = __( 'Site URL:', 'trustedlogin-connector' ) . ' ' . $parts['site_url'];
			}
			if ( '' !== $parts['access_key'] ) {
				$lines[] = __( 'Access Key:', 'trustedlogin-connector' ) . ' ' . $parts['access_key'];
				$lines[] = __( 'Log in:', 'trustedlogin-connector' ) . ' ' . $this->build_login_url( $parts['access_key'] );
			}
			return implode( "\n", $lines );
		}

		$output = '<div class="tl-entry-detail">';
		if ( '' !== $parts['site_url'] ) {
			$output .= sprintf(
				'<div><strong>%s</strong> <a href="%s" target="_blank" rel="noopener noreferrer">%s<span class="screen-reader-text"> %s</span></a></div>',
				esc_html__( 'Site URL:', 'trustedlogin-connector' ),
				esc_url( $parts['site_url'] ),
				esc_html( $parts['site_url'] ),
				esc_html__( '(opens in a new tab)', 'trustedlogin-connector' )
			);
		}
		if ( '' !== $parts['access_key'] ) {
			$output   .= sprintf(
				'<div><strong>%s</strong> <code>%s</code></div>',
				esc_html__( 'Access Key:', 'trustedlogin-connector' ),
				esc_html( $parts['access_key'] )
			);
			$login_url = $this->build_login_url( $parts['access_key'] );
			// rel="noopener noreferrer" — the access key rides in the URL.
			$output .= sprintf(
				'<div><a href="%s" class="button button-primary" target="_blank" rel="noopener noreferrer">%s<span class="screen-reader-text"> %s</span></a></div>',
				esc_url( $login_url ),
				esc_html__( 'Log in with TrustedLogin', 'trustedlogin-connector' ),
				esc_html__( '(opens in a new tab)', 'trustedlogin-connector' )
			);
		}
		$output .= '</div>';

		return $output;
	}

	/**
	 * Display the field value in the entry-list table.
	 *
	 * Compact single-line rendering. The entries list column is narrow,
	 * so show just the URL + a truncated access key. Links remain
	 * functional for quick triage.
	 *
	 * @since 2.0.0
	 *
	 * @inheritDoc
	 *
	 * @param string $value The field value.
	 * @param array  $entry The entry object.
	 * @param int    $field_id The field ID.
	 * @param array  $columns The list of columns.
	 * @param array  $form The form object.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		$parts = $this->parse_stored_value( $value );
		if ( '' === $parts['site_url'] && '' === $parts['access_key'] ) {
			return '';
		}

		$pieces = array();
		if ( '' !== $parts['site_url'] ) {
			$pieces[] = esc_html( $parts['site_url'] );
		}
		if ( '' !== $parts['access_key'] ) {
			$truncated = substr( $parts['access_key'], 0, 12 )
				. ( strlen( $parts['access_key'] ) > 12 ? '…' : '' );
			// rel="noopener noreferrer" — the access key rides in the URL.
			$pieces[] = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer"><code>%s</code><span class="screen-reader-text"> %s</span></a>',
				esc_url( $this->build_login_url( $parts['access_key'] ) ),
				esc_html( $truncated ),
				esc_html__( '— log in with TrustedLogin (opens in a new tab)', 'trustedlogin-connector' )
			);
		}
		return implode( ' &middot; ', $pieces );
	}

	/**
	 * Show a setup notice.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text The text to display in the notice.
	 * @param string $type The type of notice to display (e.g., 'warning', 'error').
	 * @param string $label_for The ID of the label to attach the notice to.
	 *
	 * @return string The HTML for the notice.
	 */
	private function show_setup_notice( $text = '', $type = 'warning', $label_for = '' ) {
		ob_start();
		?>
			<style>
				.tl_connector_field_needs_setup h3 {
					margin: 0 0 1rem;
					font-size: 1.2rem;
					font-weight: 600;
				}
				.tl_connector_field_needs_setup label {
					cursor: pointer;
				}
				.tl_connector_field_needs_setup.gform-settings__wrapper {
					padding: 0;
					margin: 0;
				}
			</style>
			<div class="tl_connector_field_needs_setup gform-settings__wrapper">
				<label class="alert gforms_note_[notice_type]" [for]>[notice_text]</label>
			</div>
		<?php
		$notice = ob_get_clean();

		return strtr(
			$notice,
			array(
				'[notice_type]' => esc_attr( $type ),
				'[for]'         => $label_for ? 'for="' . esc_attr( $label_for ) . '"' : '',
				'[notice_text]' => esc_html( $text ),
			)
		);
	}
}

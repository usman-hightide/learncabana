<?php
/**
 * Gravity Forms field setting registration helper.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Forms\GravityForms;

/**
 * GFFieldSetting implementation.
 *
 * @since 2.0.0
 */
class GFFieldSetting {

	/**
	 * Field ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Field label.
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * Tooltip text.
	 *
	 * @var string
	 */
	protected $tooltip;

	/**
	 * Default value.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * GF form-editor action hook used to place the setting in a specific tab.
	 *
	 * - gform_field_standard_settings:  General tab (default)
	 * - gform_field_appearance_settings: Appearance tab
	 * - gform_field_advanced_settings:  Advanced tab
	 *
	 * @var string
	 */
	protected $tab_hook;

	/**
	 * HTML input type for the field.
	 *
	 * Valid: "text" (default), "number", "email", "url".
	 *
	 * @var string
	 */
	protected $input_type;

	/**
	 * Register a custom Gravity Forms field setting.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id         Setting ID.
	 * @param string $label      Label string.
	 * @param string $tooltip    Tooltip string.
	 * @param string $default    Default setting value.
	 * @param string $tab_hook   Which editor-tab action to hook on (default: General).
	 * @param string $input_type HTML input type (default: "text").
	 */
	public function __construct( $id, $label, $tooltip, $default = '', $tab_hook = 'gform_field_standard_settings', $input_type = 'text' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- $default mirrors GF's add_field_setting() API.
		$this->id         = $id;
		$this->label      = $label;
		$this->tooltip    = $tooltip;
		$this->default    = $default;
		$this->tab_hook   = $tab_hook;
		$this->input_type = $input_type;

		$this->register_setting();
	}

	/**
	 * Registers actions to enable the custom setting.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_setting() {
		add_action( $this->tab_hook, array( $this, 'add_setting' ), 10, 2 );
		add_filter( 'gform_tooltips', array( $this, 'add_tooltip' ) );
		add_action( 'gform_editor_js', array( $this, 'add_script' ) );
	}

	/**
	 * Adds the setting to HTML.
	 *
	 * @since 2.0.0
	 *
	 * @param int $position Parameter.
	 *
	 * @return void
	 */
	public function add_setting( $position ) {
		if ( 25 !== $position ) {
			return;
		}
		?>
		<li class="<?php echo esc_attr( $this->id ); ?>_setting field_setting">
			<label for="<?php echo esc_attr( $this->id ); ?>" style="display:inline;">
				<?php echo esc_html( $this->label ); ?>
				<?php gform_tooltip( 'form_' . esc_attr( $this->id ) ); ?>
			</label>
			<input type="<?php echo esc_attr( $this->input_type ); ?>" id="<?php echo esc_attr( $this->id ); ?>"/>
		</li>
		<?php
	}

	/**
	 * Adds the tooltip to the list of tooltips.
	 *
	 * @param array $tooltips Parameter.
	 *
	 * @return array
	 */
	public function add_tooltip( $tooltips ) {
		$tooltips[ 'form_' . esc_attr( $this->id ) ] = esc_html( $this->tooltip );

		return $tooltips;
	}

	/**
	 * Adds the script to handle the setting field in the form editor.
	 *
	 * @return void
	 */
	public function add_script() {
		$camelId = $this->to_camel_case( $this->id );
		?>
		<script type='text/javascript'>
			jQuery( document ).on( 'gform_load_field_settings', function ( event, field, form ) {
			const value = rgar( field, <?php echo wp_json_encode( $camelId ); ?> );
			jQuery( '#' + <?php echo wp_json_encode( $this->id ); ?> )
				.val( value ? value : <?php echo wp_json_encode( $this->default ); ?> )
				.off( 'input.tlGfField change.tlGfField' )
				.on( 'input.tlGfField change.tlGfField', function () {
					SetFieldProperty( <?php echo wp_json_encode( $camelId ); ?>, this.value );
					RefreshSelectedFieldPreview();
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Convert a snake_case string to camelCase.
	 *
	 * @param string $string The string to convert.
	 *
	 * @return string
	 */
	protected function to_camel_case( $string ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound -- Local helper; renaming would obscure intent.
		return lcfirst(
			preg_replace_callback(
				'/_([a-z])/',
				function ( $matches ) {
					return strtoupper( $matches[1] );
				},
				$string
			)
		);
	}
}

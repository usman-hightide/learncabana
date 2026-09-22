<?php
/**
 * Province helpers — group-level only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical Canadian province / territory codes.
 *
 * @return array<string, string>
 */
function lc_get_province_choices() {
	return array(
		'AB' => 'Alberta (AB)',
		'BC' => 'British Columbia (BC)',
		'MB' => 'Manitoba (MB)',
		'NB' => 'New Brunswick (NB)',
		'NL' => 'Newfoundland and Labrador (NL)',
		'NS' => 'Nova Scotia (NS)',
		'NT' => 'Northwest Territories (NT)',
		'NU' => 'Nunavut (NU)',
		'ON' => 'Ontario (ON)',
		'PE' => 'Prince Edward Island (PE)',
		'QC' => 'Quebec (QC)',
		'SK' => 'Saskatchewan (SK)',
		'YT' => 'Yukon (YT)',
	);
}

/**
 * Legacy ACF slug values → codes.
 *
 * @return array<string, string>
 */
function lc_get_province_slug_map() {
	return array(
		'alberta'                   => 'AB',
		'british_columbia'          => 'BC',
		'manitoba'                  => 'MB',
		'new_brunswick'             => 'NB',
		'newfoundland_and_labrador' => 'NL',
		'nova_scotia'               => 'NS',
		'northwest_territories'     => 'NT',
		'nunavut'                   => 'NU',
		'ontario'                   => 'ON',
		'prince_edward_island'      => 'PE',
		'quebec'                    => 'QC',
		'saskatchewan'              => 'SK',
		'yukon'                     => 'YT',
	);
}

/**
 * Normalize a stored / requested province value to a canonical code.
 *
 * @param string $value Raw value.
 * @return string
 */
function lc_normalize_province_code( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value || 'all' === strtolower( $value ) ) {
		return '';
	}

	$upper   = strtoupper( $value );
	$choices = lc_get_province_choices();
	if ( isset( $choices[ $upper ] ) ) {
		return $upper;
	}

	$slug_map = lc_get_province_slug_map();
	$slug     = strtolower( str_replace( ' ', '_', $value ) );
	if ( isset( $slug_map[ $slug ] ) ) {
		return $slug_map[ $slug ];
	}

	return '';
}

/**
 * Infer province from a LearnDash group title (unambiguous prefix / code only).
 *
 * @param string $title Group title.
 * @return string
 */
function lc_infer_province_from_group_title( $title ) {
	$title = html_entity_decode( (string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	$prefix_map = array(
		'Alberta'                   => 'AB',
		'British Columbia'          => 'BC',
		'Manitoba'                  => 'MB',
		'New Brunswick'             => 'NB',
		'Newfoundland and Labrador' => 'NL',
		'Nova Scotia'               => 'NS',
		'Northwest Territories'     => 'NT',
		'Nunavut'                   => 'NU',
		'Ontario'                   => 'ON',
		'Prince Edward Island'      => 'PE',
		'Quebec'                    => 'QC',
		'Saskatchewan'              => 'SK',
		'Saskatchwan'               => 'SK',
		'Yukon'                     => 'YT',
	);

	foreach ( $prefix_map as $prefix => $code ) {
		if ( 0 === stripos( $title, $prefix ) ) {
			return $code;
		}
	}

	if ( preg_match( '/\b(AB|BC|MB|NB|NL|NS|NT|NU|ON|PE|QC|SK|YT)\b/i', $title, $matches ) ) {
		return strtoupper( $matches[1] );
	}

	return '';
}

/**
 * Force ACF Group province field choices to the 13 ticket codes (no DB write).
 *
 * @param array $field ACF field.
 * @return array
 */
function lc_acf_load_province_field( $field ) {
	$field['choices']       = lc_get_province_choices();
	$field['return_format'] = 'value';
	$field['allow_null']    = 1;
	return $field;
}
add_filter( 'acf/load_field/key=field_6a5fa4d682bfd', 'lc_acf_load_province_field' );

/**
 * Province code from the current request (reports UI / REST query string).
 *
 * @return string
 */
function lc_get_requested_province() {
	$raw = '';
	if ( isset( $_GET['province'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_GET['province'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	} elseif ( isset( $_REQUEST['province'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_REQUEST['province'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	return lc_normalize_province_code( $raw );
}

/**
 * LearnDash group IDs tagged with a province code.
 *
 * @param string $province_code Code.
 * @return int[]
 */
function lc_get_group_ids_for_province( $province_code ) {
	$province_code = lc_normalize_province_code( $province_code );
	if ( '' === $province_code ) {
		return array();
	}

	$slug_map   = lc_get_province_slug_map();
	$legacy     = array_search( $province_code, $slug_map, true );
	$meta_query = array(
		'relation' => 'OR',
		array(
			'key'   => 'province',
			'value' => $province_code,
		),
	);
	if ( false !== $legacy ) {
		$meta_query[] = array(
			'key'   => 'province',
			'value' => $legacy,
		);
	}

	$ids = get_posts(
		array(
			'post_type'              => 'groups',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);

	return array_map( 'absint', $ids );
}

/**
 * User IDs in any group tagged with the province.
 * Optimized: one usermeta IN() query instead of N learndash_get_groups_user_ids() calls.
 *
 * @param string $province_code Code.
 * @return int[]
 */
function lc_get_user_ids_for_province( $province_code ) {
	static $cache = array();

	$province_code = lc_normalize_province_code( $province_code );
	if ( '' === $province_code ) {
		return array();
	}

	if ( isset( $cache[ $province_code ] ) ) {
		return $cache[ $province_code ];
	}

	$group_ids = lc_get_group_ids_for_province( $province_code );
	if ( empty( $group_ids ) ) {
		$cache[ $province_code ] = array();
		return array();
	}

	global $wpdb;

	$meta_keys = array();
	foreach ( $group_ids as $group_id ) {
		$meta_keys[] = 'learndash_group_users_' . absint( $group_id );
	}

	$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$sql = $wpdb->prepare(
		"SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ($placeholders)",
		$meta_keys
	);

	$user_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$user_ids = array_values( array_unique( array_map( 'absint', (array) $user_ids ) ) );

	$cache[ $province_code ] = $user_ids;
	return $user_ids;
}

<?php
/**
 * Tin Canny User Report filters — Province + District Manager + Area Manager.
 * Update-safe (hooks + assets). No Tin Canny core edits required.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serve custom groups dropdown template without editing Tin Canny core.
 *
 * @param string $asset_uri Default path.
 * @param string $file_name Template file name.
 * @return string
 */
function lc_override_tincanny_groups_dropdown_template( $asset_uri, $file_name ) {
	if ( 'groups-drop-down.php' !== $file_name ) {
		return $asset_uri;
	}

	$custom = LC_GROUP_PROVINCE_DIR . '/templates/groups-drop-down.php';
	return file_exists( $custom ) ? $custom : $asset_uri;
}
add_filter( 'tinccanny_get_part_path', 'lc_override_tincanny_groups_dropdown_template', 10, 2 );

/**
 * Org-filter config for JS (page URL + localize).
 *
 * @return array
 */
function lc_get_org_filter_js_config() {
	$dm_id  = function_exists( 'lc_get_requested_district_manager_id' ) ? lc_get_requested_district_manager_id() : 0;
	$am_map = array();

	if ( function_exists( 'lc_get_managers_for_dropdown' ) ) {
		foreach ( lc_get_managers_for_dropdown( 'District Manager' ) as $dm ) {
			$am_map[ (string) $dm['id'] ] = lc_get_area_managers_for_district( $dm['id'] );
		}
		$am_map['all'] = lc_get_managers_for_dropdown( 'Area Manager' );
	}

	return array(
		'province'         => lc_get_requested_province(),
		'areaManager'      => function_exists( 'lc_get_requested_area_manager_id' ) ? lc_get_requested_area_manager_id() : 0,
		'districtManager'  => $dm_id,
		'areaManagersByDm' => $am_map,
	);
}

/**
 * Enqueue companion JS/CSS once Tin Canny reporting script is present.
 *
 * Frontend shortcode registers reporting_js_handle during content render
 * (after wp_enqueue_scripts), so we also hook wp_footer / admin_footer.
 */
function lc_enqueue_province_reporting_assets( $hook = '' ) {
	static $done = false;
	if ( $done ) {
		return;
	}

	$should = false;

	if ( is_admin() && 'toplevel_page_uncanny-learnDash-reporting' === $hook ) {
		$should = true;
	}

	if ( ! $should && ( wp_script_is( 'reporting_js_handle', 'enqueued' ) || wp_script_is( 'reporting_js_handle', 'registered' ) ) ) {
		$should = true;
	}

	if ( ! $should ) {
		return;
	}

	$done = true;

	if ( ! wp_script_is( 'reporting_js_handle', 'enqueued' ) && wp_script_is( 'reporting_js_handle', 'registered' ) ) {
		wp_enqueue_script( 'reporting_js_handle' );
	}

	wp_enqueue_style(
		'lc-province-reporting',
		LC_GROUP_PROVINCE_URL . '/assets/province-reporting.css',
		array(),
		LC_GROUP_PROVINCE_VERSION
	);

	// Match Tin Canny: header script so our patch runs before getData() on DOM ready.
	wp_enqueue_script(
		'lc-province-reporting',
		LC_GROUP_PROVINCE_URL . '/assets/province-reporting.js',
		array( 'jquery', 'reporting_js_handle' ),
		LC_GROUP_PROVINCE_VERSION,
		false
	);

	$config = lc_get_org_filter_js_config();
	wp_localize_script( 'lc-province-reporting', 'lcProvinceReporting', $config );

	/*
	 * Inject into reportingApiSetup immediately after Tin Canny defines it,
	 * and patch reportingApiCall before any getData() AJAX fires.
	 */
	$inline = sprintf(
		'(function(){window.lcProvinceReporting=window.lcProvinceReporting||%1$s;if(typeof reportingApiSetup!=="undefined"){reportingApiSetup.isolated_province=String(lcProvinceReporting.province||"");reportingApiSetup.isolated_district_manager=String(lcProvinceReporting.districtManager||"");reportingApiSetup.isolated_area_manager=String(lcProvinceReporting.areaManager||"");}if(window.uoReportingAPI&&typeof uoReportingAPI.reportingApiCall==="function"&&!uoReportingAPI.__lcOrgFiltersBootstrapped){var _orig=uoReportingAPI.reportingApiCall.bind(uoReportingAPI);uoReportingAPI.reportingApiCall=function(endpoint,path){path=path||"";if(endpoint==="courses_overview"&&window.lcOrgAppendFilters){path=window.lcOrgAppendFilters(path);}return _orig(endpoint,path);};uoReportingAPI.__lcOrgFiltersBootstrapped=true;}})();',
		wp_json_encode( $config )
	);
	wp_add_inline_script( 'reporting_js_handle', $inline, 'after' );
}
add_action( 'admin_enqueue_scripts', 'lc_enqueue_province_reporting_assets', 30 );
add_action( 'wp_enqueue_scripts', 'lc_enqueue_province_reporting_assets', 99 );
add_action( 'wp_footer', 'lc_enqueue_province_reporting_assets', 1 );
add_action( 'admin_footer', 'lc_enqueue_province_reporting_assets', 1 );

/**
 * Tin Canny fires courses_overview AJAX during script parse — often before our JS
 * can append query params. Copy Province/DM/AM from the reporting page Referer.
 */
function lc_hydrate_org_filters_from_referer() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	$keys = array( 'province', 'district_manager', 'area_manager', 'tab' );
	$missing = false;
	foreach ( $keys as $key ) {
		if ( empty( $_GET[ $key ] ) && empty( $_REQUEST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$missing = true;
			break;
		}
	}
	if ( ! $missing ) {
		return;
	}

	$referer = '';
	if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
	if ( ! $referer ) {
		return;
	}

	$query = wp_parse_url( $referer, PHP_URL_QUERY );
	if ( ! is_string( $query ) || '' === $query ) {
		return;
	}

	$params = array();
	wp_parse_str( $query, $params );

	foreach ( $keys as $key ) {
		if ( empty( $params[ $key ] ) ) {
			continue;
		}
		if ( ! empty( $_GET[ $key ] ) || ! empty( $_REQUEST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $params[ $key ] ) );
		$_GET[ $key ]     = $value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_REQUEST[ $key ] = $value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
add_action( 'rest_api_init', 'lc_hydrate_org_filters_from_referer', 1 );

/**
 * Whether request should apply User Report org filters.
 *
 * @return bool
 */
function lc_is_user_report_filter_request() {
	lc_hydrate_org_filters_from_referer();

	$tab = '';
	if ( isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $_REQUEST['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$has_org_filter = (
		'' !== lc_get_requested_province()
		|| ( function_exists( 'lc_get_requested_area_manager_id' ) && lc_get_requested_area_manager_id() )
		|| ( function_exists( 'lc_get_requested_district_manager_id' ) && lc_get_requested_district_manager_id() )
	);

	// REST AJAX often has filters but no tab — treat as User Report when filters present.
	if ( '' === $tab ) {
		return $has_org_filter;
	}

	return 'userReportTab' === $tab && $has_org_filter;
}

/** @deprecated Use lc_is_user_report_filter_request */
function lc_is_user_report_province_request() {
	return lc_is_user_report_filter_request();
}

/**
 * Build allowed user ID set from Province + DM + AM request filters.
 *
 * @return int[]|null Null = no org filters active; empty array = no matches.
 */
function lc_get_requested_org_filter_user_ids() {
	$sets = array();

	$province = lc_get_requested_province();
	if ( '' !== $province ) {
		$sets[] = lc_get_user_ids_for_province( $province );
	}

	$dm_id = function_exists( 'lc_get_requested_district_manager_id' ) ? lc_get_requested_district_manager_id() : 0;
	if ( $dm_id ) {
		$sets[] = lc_get_user_ids_for_district_manager( $dm_id );
	}

	$am_id = function_exists( 'lc_get_requested_area_manager_id' ) ? lc_get_requested_area_manager_id() : 0;
	if ( $am_id ) {
		$sets[] = lc_get_user_ids_for_area_manager( $am_id );
	}

	if ( empty( $sets ) ) {
		return null;
	}

	$allowed = array_shift( $sets );
	foreach ( $sets as $set ) {
		$allowed = array_values( array_intersect( $allowed, $set ) );
	}

	return array_values( array_unique( array_map( 'absint', $allowed ) ) );
}

/**
 * Apply org filters to a Tin Canny userList / overview array.
 *
 * @param array  $return Overview / userList payload.
 * @param string $type   Type hint.
 * @return array
 */
function lc_apply_org_filters_to_overview( $return, $type = '' ) {
	if ( empty( $return ) || ! is_array( $return ) ) {
		return $return;
	}

	if ( ! lc_is_user_report_filter_request() ) {
		return $return;
	}

	$allowed = lc_get_requested_org_filter_user_ids();
	if ( null === $allowed ) {
		return $return;
	}
	if ( empty( $allowed ) ) {
		return lc_empty_tincanny_overview_payload( $return, $type );
	}

	$lookup = array_flip( $allowed );

	if ( isset( $return['all_user_ids'] ) && is_array( $return['all_user_ids'] ) ) {
		$filtered = array();
		foreach ( $return['all_user_ids'] as $key => $user_id ) {
			$id = is_numeric( $user_id ) ? (int) $user_id : (int) $key;
			if ( isset( $lookup[ $id ] ) ) {
				$filtered[ $key ] = $user_id;
			}
		}
		$return['all_user_ids'] = $filtered;
	}

	foreach ( array( 'users_overview', 'completions', 'in_progress' ) as $bucket ) {
		if ( empty( $return[ $bucket ] ) || ! is_array( $return[ $bucket ] ) ) {
			continue;
		}
		foreach ( $return[ $bucket ] as $user_id => $value ) {
			if ( ! isset( $lookup[ (int) $user_id ] ) ) {
				unset( $return[ $bucket ][ $user_id ] );
			}
		}
	}

	if ( ! empty( $return['course_access_list'] ) && is_array( $return['course_access_list'] ) ) {
		foreach ( $return['course_access_list'] as $course_id => $users ) {
			if ( ! is_array( $users ) ) {
				continue;
			}
			foreach ( $users as $user_id => $value ) {
				if ( ! isset( $lookup[ (int) $user_id ] ) ) {
					unset( $return['course_access_list'][ $course_id ][ $user_id ] );
				}
			}
			if ( isset( $return['course_access_count'] ) && is_array( $return['course_access_count'] ) ) {
				$return['course_access_count'][ $course_id ] = count( $return['course_access_list'][ $course_id ] );
			}
		}
	}

	if ( isset( $return['dashboard_data'] ) && is_array( $return['dashboard_data'] ) && isset( $return['all_user_ids'] ) ) {
		$return['dashboard_data']['total_users'] = count( $return['all_user_ids'] );
	}

	return $return;
}

/**
 * Apply User Report org filters to Tin Canny overview payload.
 *
 * @param array  $return Overview data.
 * @param string $type   Data type.
 * @return array
 */
function lc_filter_tincanny_overview_by_province( $return, $type = '' ) {
	return lc_apply_org_filters_to_overview( $return, $type );
}
add_filter( 'uo_get_courses_overview_data', 'lc_filter_tincanny_overview_by_province', 20, 2 );

/**
 * Outer API response filter (covers cached / nested userList path).
 *
 * @param array $json Full courses_overview response.
 * @return array
 */
function lc_filter_tc_api_courses_overview( $json ) {
	if ( empty( $json['data']['userList'] ) || ! is_array( $json['data']['userList'] ) ) {
		return $json;
	}
	$json['data']['userList'] = lc_apply_org_filters_to_overview( $json['data']['userList'], 'both' );
	return $json;
}
add_filter( 'tc_api_get_courses_overview', 'lc_filter_tc_api_courses_overview', 20 );

/**
 * Empty-ish payload when filters match no users.
 *
 * @param array  $return Original.
 * @param string $type   Type.
 * @return array
 */
function lc_empty_tincanny_overview_payload( $return, $type ) {
	if ( 'dashboard-only' === $type ) {
		return is_array( $return ) ? $return : array();
	}

	$empty = array(
		'users_overview'              => array(),
		'completions'                 => array(),
		'in_progress'                 => array(),
		'all_user_ids'                => array(),
		'course_access_count'         => array(),
		'course_access_list'          => array(),
		'course_quiz_averages'        => isset( $return['course_quiz_averages'] ) ? $return['course_quiz_averages'] : array(),
		'course_completion_by_dates'  => array(),
		'course_completion_by_course' => array(),
	);

	if ( isset( $return['dashboard_data'] ) ) {
		$empty['dashboard_data'] = $return['dashboard_data'];
		if ( is_array( $empty['dashboard_data'] ) ) {
			$empty['dashboard_data']['total_users'] = 0;
		}
	}

	if ( isset( $return['microtime'] ) ) {
		$empty['microtime'] = $return['microtime'];
	}

	return $empty;
}

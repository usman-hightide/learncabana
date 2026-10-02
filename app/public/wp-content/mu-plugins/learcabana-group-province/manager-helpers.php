<?php
/**
 * Area / District Manager helpers via supervisor hierarchy + LearnDash groups.
 *
 * Data model (existing Learn Cabana weekly reports):
 * - job_titles = Area Manager | District Manager | Store Manager | ...
 * - supervisor = parent manager user ID
 * - DM → multiple AMs; AM → multiple Store Managers; SM → team members
 * - Store membership is also reflected in LearnDash groups where the AM/DM is group leader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Users with a given job_titles value (unlocked preferred list for dropdowns).
 *
 * @param string $job_title Job title label.
 * @return array<int, array{id:int,name:string}>
 */
function lc_get_managers_for_dropdown( $job_title ) {
	$users = get_users(
		array(
			'number'       => -1,
			'orderby'      => 'display_name',
			'order'        => 'ASC',
			'meta_key'     => 'job_titles',
			'meta_value'   => $job_title,
			'fields'       => array( 'ID', 'display_name' ),
			'count_total'  => false,
		)
	);

	$list = array();
	foreach ( $users as $user ) {
		$locked = get_user_meta( $user->ID, 'baba_user_locked', true );
		if ( 'yes' === $locked ) {
			continue;
		}
		$list[] = array(
			'id'   => (int) $user->ID,
			'name' => $user->display_name,
		);
	}

	return $list;
}

/**
 * Direct reports: users whose supervisor meta equals $manager_id.
 *
 * @param int $manager_id Manager user ID.
 * @return int[]
 */
function lc_get_direct_report_ids( $manager_id ) {
	$manager_id = absint( $manager_id );
	if ( ! $manager_id ) {
		return array();
	}

	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id FROM {$wpdb->usermeta}
			 WHERE meta_key = 'supervisor' AND meta_value = %s",
			(string) $manager_id
		)
	);

	return array_values( array_unique( array_map( 'absint', (array) $ids ) ) );
}

/**
 * All descendant user IDs under a manager via supervisor tree (BFS).
 * Includes people at every level (AMs, SMs, team members) under the manager.
 * Does not include the manager themselves.
 *
 * @param int $manager_id Manager user ID.
 * @return int[]
 */
function lc_get_descendant_user_ids( $manager_id ) {
	$manager_id = absint( $manager_id );
	if ( ! $manager_id ) {
		return array();
	}

	static $cache = array();
	if ( isset( $cache[ $manager_id ] ) ) {
		return $cache[ $manager_id ];
	}

	$all      = array();
	$queue    = array( $manager_id );
	$visited  = array();
	$max_hops = 8; // safety against cycles
	$hop      = 0;

	while ( ! empty( $queue ) && $hop < $max_hops ) {
		++$hop;
		$next_queue = array();
		foreach ( $queue as $current_id ) {
			if ( isset( $visited[ $current_id ] ) ) {
				continue;
			}
			$visited[ $current_id ] = true;
			$direct = lc_get_direct_report_ids( $current_id );
			foreach ( $direct as $uid ) {
				if ( isset( $visited[ $uid ] ) ) {
					continue;
				}
				$all[]        = $uid;
				$next_queue[] = $uid;
			}
		}
		$queue = $next_queue;
	}

	$cache[ $manager_id ] = array_values( array_unique( $all ) );
	return $cache[ $manager_id ];
}

/**
 * LearnDash group IDs where the user is a group leader.
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function lc_get_group_ids_led_by_user( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return array();
	}

	static $cache = array();
	if ( isset( $cache[ $user_id ] ) ) {
		return $cache[ $user_id ];
	}

	if ( function_exists( 'learndash_get_administrators_group_ids' ) ) {
		$group_ids = array_map( 'absint', (array) learndash_get_administrators_group_ids( $user_id ) );
		$cache[ $user_id ] = array_values( array_filter( $group_ids ) );
		return $cache[ $user_id ];
	}

	global $wpdb;
	$keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_key FROM {$wpdb->usermeta}
			 WHERE user_id = %d AND meta_key LIKE %s",
			$user_id,
			$wpdb->esc_like( 'learndash_group_leaders_' ) . '%'
		)
	);

	$group_ids = array();
	foreach ( (array) $keys as $key ) {
		if ( preg_match( '/^learndash_group_leaders_(\d+)$/', (string) $key, $matches ) ) {
			$group_ids[] = absint( $matches[1] );
		}
	}

	$cache[ $user_id ] = array_values( array_unique( array_filter( $group_ids ) ) );
	return $cache[ $user_id ];
}

/**
 * User IDs who are members of any LearnDash group led by $manager_id.
 * Does not include the manager themselves.
 *
 * @param int $manager_id Manager user ID.
 * @return int[]
 */
function lc_get_user_ids_from_managed_groups( $manager_id ) {
	$manager_id = absint( $manager_id );
	if ( ! $manager_id ) {
		return array();
	}

	static $cache = array();
	if ( isset( $cache[ $manager_id ] ) ) {
		return $cache[ $manager_id ];
	}

	$group_ids = lc_get_group_ids_led_by_user( $manager_id );
	if ( empty( $group_ids ) ) {
		$cache[ $manager_id ] = array();
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

	// Reporting scope excludes the manager account itself.
	$user_ids = array_values( array_diff( $user_ids, array( $manager_id ) ) );

	$cache[ $manager_id ] = $user_ids;
	return $user_ids;
}

/**
 * Manager filter scope = supervisor hierarchy UNION LearnDash group memberships.
 *
 * Store-based org charts are often reflected in group leadership even when
 * supervisor meta is incomplete — union both sources for accurate filters.
 *
 * @param int $manager_id Manager user ID.
 * @return int[]
 */
function lc_get_user_ids_for_manager_scope( $manager_id ) {
	$manager_id = absint( $manager_id );
	if ( ! $manager_id ) {
		return array();
	}

	$ids = array_merge(
		lc_get_descendant_user_ids( $manager_id ),
		lc_get_user_ids_from_managed_groups( $manager_id )
	);

	return array_values( array_unique( array_map( 'absint', $ids ) ) );
}

/**
 * Area Manager filter users = supervisor tree + members of groups they lead.
 *
 * @param int $area_manager_id AM user ID.
 * @return int[]
 */
function lc_get_user_ids_for_area_manager( $area_manager_id ) {
	return lc_get_user_ids_for_manager_scope( $area_manager_id );
}

/**
 * District Manager filter users = supervisor tree + DM-led groups + AM-led groups under them.
 *
 * @param int $district_manager_id DM user ID.
 * @return int[]
 */
function lc_get_user_ids_for_district_manager( $district_manager_id ) {
	$district_manager_id = absint( $district_manager_id );
	if ( ! $district_manager_id ) {
		return array();
	}

	$ids = lc_get_user_ids_for_manager_scope( $district_manager_id );

	// Include store groups led by Area Managers who report to this DM.
	foreach ( lc_get_area_managers_for_district( $district_manager_id ) as $am ) {
		$ids = array_merge( $ids, lc_get_user_ids_from_managed_groups( (int) $am['id'] ) );
	}

	return array_values( array_unique( array_map( 'absint', $ids ) ) );
}

/**
 * Requested Area Manager user ID from query string.
 *
 * @return int 0 if none/all.
 */
function lc_get_requested_area_manager_id() {
	$raw = '';
	if ( isset( $_GET['area_manager'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_GET['area_manager'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	} elseif ( isset( $_REQUEST['area_manager'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_REQUEST['area_manager'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
	if ( '' === $raw || 'all' === $raw ) {
		return 0;
	}
	return absint( $raw );
}

/**
 * Requested District Manager user ID from query string.
 *
 * @return int 0 if none/all.
 */
function lc_get_requested_district_manager_id() {
	$raw = '';
	if ( isset( $_GET['district_manager'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_GET['district_manager'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	} elseif ( isset( $_REQUEST['district_manager'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_REQUEST['district_manager'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
	if ( '' === $raw || 'all' === $raw ) {
		return 0;
	}
	return absint( $raw );
}

/**
 * Area Managers that report to a given District Manager (for cascading dropdown).
 *
 * @param int $district_manager_id DM ID.
 * @return array<int, array{id:int,name:string}>
 */
function lc_get_area_managers_for_district( $district_manager_id ) {
	$district_manager_id = absint( $district_manager_id );
	if ( ! $district_manager_id ) {
		return lc_get_managers_for_dropdown( 'Area Manager' );
	}

	$direct = lc_get_direct_report_ids( $district_manager_id );
	if ( empty( $direct ) ) {
		return array();
	}

	$list = array();
	foreach ( $direct as $uid ) {
		$title = get_user_meta( $uid, 'job_titles', true );
		if ( 'Area Manager' !== $title ) {
			// Still include if they sit under DM (data quirks); prefer AM title.
			continue;
		}
		$locked = get_user_meta( $uid, 'baba_user_locked', true );
		if ( 'yes' === $locked ) {
			continue;
		}
		$user = get_userdata( $uid );
		if ( ! $user ) {
			continue;
		}
		$list[] = array(
			'id'   => $uid,
			'name' => $user->display_name,
		);
	}

	usort(
		$list,
		static function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		}
	);

	return $list;
}

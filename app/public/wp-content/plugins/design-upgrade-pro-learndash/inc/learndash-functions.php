<?php
/**
 * These are plugin features that tie into LearnDash functions,
 * hooks & filters.
 *
 * @since  1.7
 */

/**
 * Custom URL for the "Edit Profile" link in [ld_profile] shortcode
 */

/**
 * @Legacy
 */
$ldx_edit_profile_url = get_theme_mod( 'ldx_profile_custom_edit_profile_link', '' );

function ldx_custom_edit_profile_link() {

	$link = get_theme_mod( 'ldx_profile_custom_edit_profile_link', '' );
	return $link;

}

if( $ldx_edit_profile_url != '' && ! is_admin() ):

	add_filter( 'get_edit_user_link', 'ldx_custom_edit_profile_link', 30, 2 );

endif;


/**
 * @LD3
 */
$ldx3_edit_profile_url = get_option( 'ldx3_design_upgrade' );

function ldx3_custom_edit_profile_link() {

	$ldx3_edit_profile_url = get_option( 'ldx3_design_upgrade' );

	$link = $ldx3_edit_profile_url['profile_custom_edit_profile_url'];
	return $link;

}

if( isset( $ldx3_edit_profile_url['profile_custom_edit_profile_url'] ) && $ldx3_edit_profile_url['profile_custom_edit_profile_url'] != '' && ! is_admin() ):

	add_filter( 'get_edit_user_link', 'ldx3_custom_edit_profile_link', 30, 2 );

endif;


/**
 * Focus Mode
 * Welcome Name/Username Customization
 *
 * @since  2.3
 */
function ldx3_focus_mode_display_name( $user_nicename, $user_data ) {

	$ldx3_focus_mode_name = get_option( 'ldx3_design_upgrade' );

	$first_name = $user_data->first_name;
	$last_name = $user_data->last_name;
	$display_name = $user_data->display_name;
	$username = $user_data->user_login;

	// Username
	if( isset( $ldx3_focus_mode_name['focus_mode_display_name'] ) && $ldx3_focus_mode_name['focus_mode_display_name'] === 'username' ) {

		return $username;

	// First Name
	} elseif( isset( $ldx3_focus_mode_name['focus_mode_display_name'] ) && $ldx3_focus_mode_name['focus_mode_display_name'] === 'firstname' ) {

		return $first_name;

	// First & Last Name
	} elseif( isset( $ldx3_focus_mode_name['focus_mode_display_name'] ) && $ldx3_focus_mode_name['focus_mode_display_name'] === 'fullname' ) {

		return "$first_name $last_name";

	// Display Name
	} elseif( isset( $ldx3_focus_mode_name['focus_mode_display_name'] ) && $ldx3_focus_mode_name['focus_mode_display_name'] === 'displayname' ) {

		return $display_name;

	} else {

		return $username;

	}

} // function ldx3_focus_mode_display_name()

// if( isset( $ldx3_focus_mode_name['focus_mode_display_name'] ) && $ldx3_focus_mode_name['focus_mode_display_name'] != '' ):

	add_filter( 'ld_focus_mode_welcome_name', 'ldx3_focus_mode_display_name', 9999, 2 );

// endif;
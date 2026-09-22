<?php
/*
Plugin Name: WP Custom Avatar
Plugin URI: https://wordpress.org/plugins/wp-custom-avatar/
Description: Replace Default Avatar with custom image of your choice. 
Author: Hareesh Pillai
Author URI: https://profiles.wordpress.org/hareesh-pillai/
Text Domain: wp-custom-avatar
Version: 1.2.1
License: GPLv2 or later
*/

/*  Copyright 2015 Hareesh Pillai (email: hareesh.hsr289 at gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'WPCA_VERSION', '1.2.1' );

function wp_custom_avatar_customize_register( $wp_customize ) {

	$wp_customize->add_section( 'wp_custom_avatar_section' , array(
		'title'      => __( 'WP Custom Avatar', 'wp-custom-avatar' ),
		'priority'   => 200
	) );

    $wp_customize->add_setting( 'wp_custom_avatar' );

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'wp_custom_avatar', array(
        'label'    => __( 'Upload Custom Avatar', 'wp-custom-avatar' ),
        'section'  => 'wp_custom_avatar_section',
        'settings' => 'wp_custom_avatar',
    ) ) );
}
add_action( 'customize_register', 'wp_custom_avatar_customize_register' );


function wp_custom_avatar($avatar_defaults) {
    $avatar = get_theme_mod( 'wp_custom_avatar' );
    $avatar_defaults[$avatar] = get_bloginfo('name');
    return $avatar_defaults;
} 
add_filter( 'avatar_defaults', 'wp_custom_avatar' );

function wp_custom_avatar_load_textdomain() {
	load_plugin_textdomain( 'wp-custom-avatar', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}
add_action('plugins_loaded', 'wp_custom_avatar_load_textdomain');

?>
<?php

/**
 * Adds <body> class when plugin is active.
 *
 * @copyright Copyright (c) 2019, Escape Creative, LLC
 * @license   GPL2+
 */

function ldx3_body_classes( $classes ) {

	// Global plugin class
	$classes[] = 'ldx-plugin';

	return $classes;

}
add_filter( 'body_class', 'ldx3_body_classes' );
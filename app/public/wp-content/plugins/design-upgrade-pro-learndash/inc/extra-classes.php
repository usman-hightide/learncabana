<?php

/**
 * Add extra class names to various elements.
 *
 * @copyright Copyright (c) 2019, Escape Creative, LLC
 * @license   GPL2+
 */

// Check to see if function exists in free version
if ( ! function_exists( 'ldx_learndash_course_grid_class' ) ) {

	function ldx_pro_learndash_course_grid_class( $class, $course_id, $course_options ) {

		$user_id = get_current_user_id();

		if ( learndash_course_completed( $user_id, $course_id ) ) {

			$class .= 'ldx-grid-course-complete';

		}

		return $class;

	}

	add_filter( 'learndash_course_grid_class', 'ldx_pro_learndash_course_grid_class', 10, 3 );

}
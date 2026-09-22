<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_lms {

	function __construct() {
		add_action("grassblade_xapi_tracked" , array($this, 'lms_content_attempted'), 10, 3);
	}

	static function is_course($course) {
		$course = grassblade_lms::get_course($course);
		return !empty($course);
	}
	static function get_course_content_ids($course) {
		return apply_filters("grassblade_get_course_content_ids", array(), $course);
	}
	static function get_course_content_list($course) {
		global $wpdb;

		if(is_string($course) && $course == "all") {
			$content_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish'");
		}
		else
		$content_ids = grassblade_lms::get_course_content_ids($course);

		if(!empty($content_ids) && count($content_ids) > 0)
			$results = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE ID IN (".implode(",", $content_ids).")");

		$return = array();
		if(!empty($results))
		foreach ($results as $key => $value) {
			$return[$value->ID] = $value->post_title;
		}
		asort($return);
		return $return;
	}
	static function get_course($course) {
		return apply_filters("grassblade_get_course", false, $course);
	}

	static function get_courses($params = array()) {	// "post_status", "user" for course of group leader. "group_id" for courses of group.
		return apply_filters("grassblade_get_courses", array(), $params);
	}

	/*
	* Change the Course Status to In Progress if any content has been started
	*/
    function lms_content_attempted($statement_json, $xapi_content_id, $user) {
	    $statement = json_decode($statement_json);
	    if(!is_object($statement) || empty($user->ID) || empty($xapi_content_id))
	    	return;

	    if(is_string($statement->verb))
	    	$verb = $statement->verb;
	    else if(is_object($statement->verb) && is_string($statement->verb->id))
	    	$verb = $statement->verb->id;
	    else
	    	return;

		$user_id = $user->ID;

	    if(in_array($verb, array("attempted", "launched", "initialized", "http://adlnet.gov/expapi/verbs/attempted", "http://adlnet.gov/expapi/verbs/launched", "http://adlnet.gov/expapi/verbs/initialized"))) {
			$started = get_user_meta($user_id, "content_started_".$xapi_content_id, true );
			if(empty($started)) {
				update_user_meta($user_id, "content_started_".$xapi_content_id, time() );
			}
			global $wpdb;
			$posts = grassblade_xapi_content::get_posts_with_content($xapi_content_id);

	    	$done = array();
	    	if(!empty($posts))
			foreach ($posts as $post) {
				$post_id = is_object($post)? $post->ID:intVal($post);

				$course_ids = apply_filters('grassblade_post_course_ids', array(), $post_id, $user_id);
				do_action("gb_post_started", $user_id, $post_id, $course_ids);

				if (!empty($course_ids)) {
					foreach ($course_ids as $course_id => $course) {
						$course_user_access = apply_filters('grassblade_course_check_user_access', false, $course_id, $user_id);
						if(empty($done[$course_id]) && !empty($course_user_access)) {

							$data = array("timestamp" => time(), "post_id" => $post_id, "content_id" => $xapi_content_id);
							grassblade_lms::grassblade_course_started($user_id, $course_id, $data);

							$done[$course_id] = 1;
						}
					}
				}
			}
	    } // end of if
	}

	/*
	* Set LMS Course Started and Send Attempted Statement.
	*/
	static function grassblade_course_started($user_id, $course_id, $data) {

		grassblade_debug('grassblade_course_started');
		$started = get_user_meta($user_id, "content_course_started_".$course_id, true );

		if(empty($started) || !is_array($started)) {
			update_user_meta($user_id, "content_course_started_".$course_id, $data);

			//Send Course Attempted
			do_action("grassblade_course_started", $user_id, $course_id, $data);
		}
	}

	static function is_admin($user_id = 0) {
		static $is_admin = array();

		if(empty($user_id))
			$user_id = empty($user_id)? get_current_user_id():$user_id;

		if(empty($user_id))
			return false;

		if(!isset($is_admin[$user_id])) {
			$lms_admin_role = grassblade_settings("reports_lms_admin");
			$cap = (empty($lms_admin_role) || !current_user_can($lms_admin_role))? "manage_options":$lms_admin_role;
			$is_admin[$user_id] = apply_filters("grassblade_lms_is_admin", user_can($user_id, $cap), $user_id);
		}

		return $is_admin[$user_id];
	}
}

$gb_lms = new grassblade_lms();

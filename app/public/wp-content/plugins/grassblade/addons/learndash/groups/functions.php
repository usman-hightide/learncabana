<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// base function are protected and addon private
class gb_groups_learndash extends gb_groups_base
{
	public static $short_key = "ld";
	public static $meta_key = ""; //gb_group_leader_".self::$short_key;
	public static $group_type = "WP: LearnDash LMS";

	function __construct()
	{
		parent::__construct();
	}

	function run()
	{
		if ($this->is_plugin_active()) {
			add_filter("grassblade_groups", array($this, "filter_grassblade_groups"), 10, 2); // in base
			add_filter("grassblade_get_courses", array($this, "filter_get_courses"), 10, 2); // in base

			add_filter("grassblade_reports_menu_cap", array($this, "filter_group_leader_menu_cap"), 10, 1);
			add_filter("grassblade_is_group_leader", array($this, "filter_is_group_leader"), 11, 2);
			add_filter("grassblade_is_group_leader_of_user", array($this, "filter_is_group_leader_of_user"), 10, 3);
			add_filter("grassblade_group_user_query", array($this, 'filter_get_group_user_query'), 10, 3);
			add_filter('grassblade/groups/get/courses/'.self::$short_key, array($this, "filter_get_group_courses"), 10, 4);

			add_action('save_post', array($this, 'update_group_users_in_lrs'), 100, 1);
			add_action('ld_added_group_access', array($this, 'send_user_added_to_group'), 10, 2);
			add_action('ld_removed_group_access', array($this, 'send_user_removed_from_group'), 10, 2);

			add_action('ld_added_leader_group_access', array($this, 'send_group_leader_added_to_group'), 10, 2);
			add_action('ld_removed_leader_group_access', array($this, 'send_group_leader_removed_from_group'), 10, 2);

			add_filter("grassblade/groups/get/group_type", array($this,'filter_get_group_type' ), 10, 2);
		}
	}

	function send_group_leader_added_to_group($leader_id, $group_id) {
		$this->set_statement($group_id, $leader_id, "joined-group", "group_leader");

		if ( empty( $_POST[ 'learndash_group_leaders' ] ) ) // Not editing Group. Triggerred from other places.
		$this->send_statements_for_group_users();
	}

	function send_group_leader_removed_from_group($leader_id, $group_id) {
		$this->set_statement($group_id, $leader_id, "left-group", "group_leader");

		if ( empty( $_POST[ 'learndash_group_leaders' ] ) ) // Not editing Group. Triggerred from other places.
		$this->send_statements_for_group_users();
	}

	function update_group_users_in_lrs($group_id) {

		// if ( !isset( $_POST[ 'learndash_group_users' ] ) || !isset( $_POST[ 'learndash_group_leaders' ] ) )
			// 	return;

		$group = get_post($group_id);
		if(empty($group) || $group->post_type != "groups" || $group->post_status != "publish")
			return;

		$this->send_statements_for_group_users();
	}

	function send_user_removed_from_group($user_id, $group_id) {

		$this->set_statement($group_id, $user_id, "left-group", "user");

		if ( empty( $_POST[ 'learndash_group_users' ] ) ) // Not editing Group. Triggerred from other places.
		$this->send_statements_for_group_users();
	}

	function send_user_added_to_group($user_id, $group_id) {

		$this->set_statement($group_id, $user_id, "joined-group", "user");

		if ( empty( $_POST[ 'learndash_group_users' ] ) ) // Not editing Group. Triggerred from other places.
		$this->send_statements_for_group_users();
	}

	function send_statements_for_group_users() {
		global $grassblade;
		if(!empty($grassblade["xapi"]->statements)) {
			$ret = $grassblade["xapi"]->SendStatements($grassblade["xapi"]->statements);
			$grassblade["xapi"]->statements = array();
		}
	}

	/*
	 * Set statement for user added/removed from group
	 * @param int $group_id
	 * @param int $user_id
	 * @param string $verb_id		: joined-group, left-group
	 * @param string $user_type  	: user, group_leader
	 */
	function set_statement($group_id, $user_id, $verb_id, $user_type) {
		global $grassblade, $current_user;

		$group = get_post($group_id);
		$user  = get_userdata($user_id);

		if(empty($group) || empty($user))
			return;

		$actor = grassblade_getactor(false, 1.0, $user);
		$grassblade["xapi"]->new_statement();
		$grassblade["xapi"]->set_actor_by_object($actor);
		$grassblade["xapi"]->set_verb($verb_id);

		$group_url   = get_permalink($group_id);
		$group_title = $group->post_title;

		$grassblade["xapi"]->set_object($group_url, $group_title, '', '','Activity');
		$context_extensions = array(
			"http://nextsoftwaresolutions.com/xapi/extensions/details" =>  array(
					"user_id"  	  => $user->ID,
					"group_id"    => $group_id,
					"group_name"  => $group_title,
					"group_type"  => self::$group_type,
					"user_type"   => $user_type
				)
		);

		if(!empty($current_user)) {
			$name = (!empty($current_user->user_firstname) || !empty($current_user->user_lastname))? $current_user->user_firstname. " ".$current_user->user_lastname:$current_user->user_login;
			$context_extensions["https://w3id.org/xapi/acrossx/extensions/by-whom"] = array('user_id' => $current_user->ID,'name' => $name);
		}

		$grassblade["xapi"]->set_context_extensions($context_extensions);
		$grassblade["xapi"]->build_statement();
		$grassblade["xapi"]->new_statement();
	}

	function is_plugin_active() {
		return defined("LEARNDASH_VERSION");
	}
	function filter_get_group_type( $r, $post ) {
		if( !empty($r) )
			return $r;

		if( is_object($post) && !empty($post->post_type) && $post->post_type == "groups" ) {
			//check if LearnDash Group or Groups Group group
			return self::$group_type;
		}

		$group_id = is_numeric($post) ? $post : gb_get_value($post, "ID", gb_get_value($post, "id", 0));
		if( is_numeric($group_id) && !empty($group_id) && get_post_type($group_id) == "groups" ) {
			//check if LearnDash Group or Groups Group group
			return self::$group_type;
		}
		return $r;
	}
	static function filter_get_group_courses($all_courses, $group_id, $params, $ids_only = false) {

		if($group_id == 'all') {
			$post_status = !empty($params["post_status"])? trim($params["post_status"]):"publish";
			$courses = get_posts("post_type=sfwd-courses&post_status=$post_status&posts_per_page=-1");
			foreach($courses as $course) {
				$course->lms = self::$short_key;
				$all_courses[] = $course;
			}

			return ($ids_only)? wp_list_pluck($all_courses, "ID"):$all_courses;
		}

		$group = is_numeric($group_id) ? get_post($group_id) : array();
		if (empty($group) || empty($group->post_type) || $group->post_type != "groups")
			return $all_courses;

		$course_ids = learndash_get_group_courses_list($group_id);

		if( $ids_only )
			return $course_ids;

		if(!empty($course_ids))
		foreach ($course_ids as $course_id) {
			$course = get_post($course_id);
			$course->lms = self::$short_key;
			$all_courses[] = $course;
		}

		return $all_courses;
	}
	function filter_get_group_user_query($sql, $group_id, $group_type = '')
	{
		if (!is_numeric($group_id) || !empty($sql) || !empty($group_type) && $group_type != self::$group_type)
			return $sql;

		$group = get_post($group_id);
		if (empty($group) || empty($group->post_type) || $group->post_type != "groups")
			return $sql;

		global $wpdb;
		return $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'learndash_group_users_{$group_id}' AND meta_value = '%d'", $group_id);
	}

	static function get_groups($params)
	{
		$groups = array();
		$params["post_type"] = "groups";

		if (empty($params["posts_per_page"]))
			$params["posts_per_page"] = -1;

		if (isset($params["leaders_list"]))
			unset($params["leaders_list"]);

		if (isset($params["users_list"]))
			unset($params["users_list"]);

		if (!empty($params["group_leader_id"])) {
			$group_leader_group_ids = self::get_leaders_group_ids($params["group_leader_id"]);
			if (empty($group_leader_group_ids) || !empty($params["id"]) && !in_array($params["id"], $group_leader_group_ids))
				return array();

			$params["post__in"] = $group_leader_group_ids;
			$groups = get_posts($params);
		} else if (!empty($params["id"])) {
			$groups = array(get_post($params["id"]));
		} else {
			$groups = get_posts($params);
		}

		$groups_return = array();
		foreach ($groups as $k => $group) {
			if (!empty($group->ID) && !empty($group->post_type) && $group->post_type == "groups")
				$groups_return[] = array(
					"ID" 	=> $group->ID,
					"name"	=> $group->post_title,
					"type"	=> self::$group_type
				);
		}
		return $groups_return;
	}

	function filter_is_group_leader_of_user($r, $leader_id, $user_id)
	{
		if (!function_exists('learndash_is_group_leader_user') || !empty($r))
			return $r;

		return learndash_is_group_leader_user($leader_id) && learndash_is_group_leader_of_user($leader_id, $user_id);
	}

	function filter_is_group_leader($r, $current_user_id)
	{
		if (!function_exists('learndash_is_group_leader_user') || $r)
			return $r;

		return learndash_is_group_leader_user($current_user_id);
	}

	function filter_group_leader_menu_cap($menu_cap)
	{
		if (function_exists("learndash_is_group_leader_user") && !current_user_can("manage_options") && learndash_is_group_leader_user())
			return LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK;
		return $menu_cap;
	}
	static function get_leader_ids($group_id)
	{
		return (!function_exists("learndash_get_groups_administrator_ids")) ? array() : learndash_get_groups_administrator_ids($group_id);
	}
	static function get_leaders_group_ids($group_leader_id)
	{
		if(function_exists('learndash_is_group_leader_user') && !learndash_is_group_leader_user($group_leader_id) || !function_exists("learndash_get_administrators_group_ids"))
			return array();

		return apply_filters("grassblade/groups/get/get_leaders_group_ids", learndash_get_administrators_group_ids($group_leader_id), $group_leader_id, self::$short_key);
	}
	static function get_user_ids($group_id)
	{
		return (!function_exists("learndash_get_groups_user_ids")) ? array() : learndash_get_groups_user_ids($group_id);
	}
} // end of gb_groups_learndash class

return new gb_groups_learndash();

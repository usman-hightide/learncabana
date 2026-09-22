<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class gb_groups_base
{
	function __construct()
	{
		if (static::is_enabled())
			add_action('plugins_loaded', array($this, "run"), 1000);
	}
	static function is_enabled()
	{
		return gb_integrations::is_enabled("gb_groups_" . static::$short_key);
	}
	static function leader_meta_key()
	{
		return "gb_group_leader_" . static::$short_key;
	}

	function filter_get_courses($courses, $params)
	{
		// grassblade_debug("gb_groups_base/filter_get_courses/".static::$short_key. " : ".wp_debug_backtrace_summary());
		// grassblade_debug($params);

		if (!empty($params["group_type"]) && $params["group_type"] != static::$group_type)
			return $courses;

		$all_courses = array();
		if(!empty($params["group_id"])) {
			if( is_numeric($params["group_id"]) )
			$all_courses = static::get_group_courses($params["group_id"], $params);
		}
		else
		if (!empty($params["user"])) { //Group Leader, user is group leader.
			if(!empty($params["user"]->ID))
			$group_ids = static::get_leaders_group_ids($params["user"]->ID);

			if (!empty($group_ids))
			foreach ($group_ids as $group_id) {
				$params["group_type"] = static::$group_type;
				$all_courses  = array_merge( $all_courses, static::get_group_courses($group_id, $params) );
			}
		}
		else
		$all_courses = static::get_group_courses('all', $params);

		if(!empty($all_courses))
		foreach ($all_courses as $course) {
			if (isset($params["return"]) && $params["return"] == "object")
				$courses[$course->ID] = $course;
			else
				$courses[$course->ID] = $course->post_title;
		}

		return $courses;
	}
	function filter_is_group_leader_of_user($r, $leader_id, $user_id)
	{
		if (!empty($r))
			return $r;

		if (gb_groups::is_group_leader($leader_id)) {
			$leaders_groups_ids = static::get_leaders_group_ids($leader_id);
			foreach ($leaders_groups_ids as $group_id) {
				$group_users_ids = static::get_user_ids($group_id);
				if (in_array($user_id, $group_users_ids))
					return true;
			}
		}
		return $r;
	}
	function filter_grassblade_groups($return, $params)
	{

		if (empty($return) || !is_array($return))
			$return = array();

		if (!empty($params["group_type"]) && $params["group_type"] != static::$group_type)
			return $return;

		$groups = static::get_groups($params);
		if (!empty($groups))
			foreach ($groups as $group) {

				$group = static::add_users_and_leaders($group, $params);
				if (!empty($params["id"])) {
					if ($params["id"] == $group["ID"] && (empty($params["group_type"]) || $params["group_type"] == static::$group_type))
						return $group;
				} else
					$return[] = $group;
			}

		return $return;
	}
	static function get_group_courses($group_id, $params = array(), $ids_only = false)
	{
		if(empty($group_id) || !empty($params["group_type"]) && $params["group_type"] != static::$group_type)
			return array();

		if (isset($params["lms"]) && is_array($params["lms"]) && !in_array(static::$short_key, $params["lms"]))
			return array();

		$courses = apply_filters("grassblade/groups/get/courses/" . static::$short_key, array(), $group_id, $params, $ids_only);
		foreach($courses as $k => $course) {
			if( empty($course) )
			unset($courses[$k]);
		}
		return array_values( $courses );
	}
	function save_group_leader_list($group_id)
	{
		if (!empty($_POST['gb_group_leader']) && is_array($_POST['gb_group_leader']))
			$new_group_leader_ids = array_map('wp_strip_all_tags', $_POST['gb_group_leader']);
		else
			$new_group_leader_ids = array(0);
		static::gb_save_group_leader($group_id, static::leader_meta_key(), $new_group_leader_ids);
	}
	static function text_group_leader_selection_title()
	{
		return __("GrassBlade Group Leader", "grassblade");
	}
	static function text_group_leader_selection_desc()
	{
		return __("Select groups leader to provide access to users and reports.", "grassblade");
	}
	static function group_leaders_dropdown_html($group_id)
	{
		$select_html = '<div class="gb_group_leaders_dropdown">';
		$select_html .= '<select class="gb_select2 gb_group_leaders_dropdown-' . static::$short_key . '" name="gb_group_leader[]" id="gb_group_leader" placeholder="select group leader" multiple>';
		$existing_group_leaders = empty($group_id) ? array() : static::get_group_leader_ids(static::leader_meta_key(), $group_id);
		$group_leader_ids = static::get_group_leader_ids();
		foreach ($group_leader_ids as $group_leader_id) {
			$selected = in_array($group_leader_id, $existing_group_leaders) ? "selected" : "";
			$select_html .= '<option value="' . $group_leader_id . '"' . $selected . '>' . gb_name_format($group_leader_id) . '</option>';
		}
		$select_html .= '</select>';
		$select_html .= '<script>
		jQuery(window).on("load", function() {
			if( jQuery(".gb_group_leaders_dropdown select").length > 0 && typeof jQuery(".gb_group_leaders_dropdown select.gb_select2").select2 == "function" )
			jQuery(".gb_group_leaders_dropdown select.gb_select2").select2({
				width: "100%",
				placeholder: "Select Group Leader",
			});
		});
		</script>';
		$select_html .= '</div>';
		return $select_html;
	}
	static function add_users_and_leaders($group, $params)
	{

		if (!empty($params["leaders_list"]))
			$group["group_leaders"] = static::get_leaders($group["ID"]);

		if (!empty($params["users_list"]))
			$group["group_users"] = static::get_users($group["ID"]);

		return empty($group) ? array() : $group;
	}
	static function get_leaders($group_id)
	{
		return gb_users_with_email(static::get_leader_ids($group_id));
	}
	static function get_leader_ids($group_id)
	{
		return static::get_group_leader_ids(static::leader_meta_key(), $group_id);
	}
	static function get_leaders_group_ids($group_leader_id)
	{
		$leader_group_ids = array();
		if (user_can($group_leader_id, gb_groups::role()))
			$leader_group_ids = get_user_meta($group_leader_id, static::leader_meta_key(), false);

		return apply_filters("grassblade/groups/get/leaders_group_ids", $leader_group_ids, $group_leader_id, static::$short_key);
	}
	static function get_users($group_id)
	{
		return gb_users_with_email(static::get_user_ids($group_id), true);
	}
	static function get_group_leaders($meta_key = "", $group_id = 0)
	{
		return gb_users_with_email(self::get_group_leader_ids($meta_key, $group_id));
	}
	static function get_group_leader_ids($meta_key = "", $group_id = 0)
	{
		if (empty($meta_key)) {
			$leaders = get_users(["role" => gb_groups::role()]);
			return empty($leaders) ? array() : wp_list_pluck($leaders, "ID");
		}
		global $wpdb;
		$group_leader_ids = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE `meta_key` LIKE %s AND meta_value = %d", $meta_key, $group_id));
		$group_leader_ids = empty($group_leader_ids) ? array() : $group_leader_ids;

		return apply_filters("grassblade/groups/get/group_leader_ids", $group_leader_ids, $group_id, static::$short_key);
	}
	static function gb_save_group_leader($group_id, $meta_key, $new_group_leader_ids)
	{

		$existing_group_leader = self::get_group_leader_ids($meta_key, $group_id);
		foreach ($new_group_leader_ids as $user_id) {
			if (!in_array($user_id, $existing_group_leader))
				add_user_meta($user_id, $meta_key, $group_id);
		}

		foreach ($existing_group_leader as $user_id) {
			if (!in_array($user_id, $new_group_leader_ids))
				delete_user_meta($user_id, $meta_key, $group_id);
		}
	}
}

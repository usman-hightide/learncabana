<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_groups_group extends gb_groups_base
{
	static $short_key = "gg";
	static $meta_key = "";//gb_group_leader_".self::$short_key;
	static $group_type = "WP: Groups Plugin";

	function __construct() {
		parent::__construct();
	}

	function run()
	{
		if ($this->is_plugin_active()) {
			add_filter("grassblade_groups", array($this,'filter_grassblade_groups'), 10, 2); //in base
			//add_filter("grassblade_is_group_leader", array($this, "filter_is_group_leader"), 11, 2); //in base
			add_filter("grassblade_is_group_leader_of_user", array($this, "filter_is_group_leader_of_user"), 10, 3); //in base

			add_filter("grassblade_group_user_query", array($this, 'filter_get_group_user_query'), 10, 3);
			add_filter( 'grassblade_add_scripts_on_page', array($this, 'filter_add_to_scripts') );

			// add_filter("grassblade_get_courses", array($this, "filter_get_courses"), 10, 2); No Integration between LearnDash and Groups plugin
			add_filter("groups_admin_groups_add_form_after_fields", array($this, "add_group_leader_settings"), 10, 1);
			add_filter("groups_admin_groups_edit_form_after_fields", array($this, "add_group_leader_settings"), 10, 2);

			//groups_admin_groups_add_submit_success: BuddyPress and Groups plugin uses the same action "groups_created_group". Show error when you try to create new group in Groups plugin, but works properly at the time editing the same group.
			add_action("groups_admin_groups_add_submit_success", array($this, "save_group_leader_list"), 10, 1); // in base
			add_action("groups_admin_groups_edit_submit_success", array($this, "save_group_leader_list"), 10, 1); // in base

			add_filter("grassblade/groups/get/group_type", array($this,'filter_get_group_type' ), 10, 2);
		}
	}
	function is_plugin_active() {
		return class_exists("Groups_Group");
	}
	function filter_get_group_type( $r, $post ) {
		if( !empty($r) )
			return $r;

		if( is_object($post) ) {
			if(get_class($post) == "Groups_Group" || empty($post->post_type) && !empty($post->creator_id))
				return self::$group_type;
			else
				return $r;
		}
		$group_id = is_numeric($post) ? $post : gb_get_value($post, "ID", gb_get_value($post, "id", 0));
		if( is_numeric($group_id) && !empty($group_id) ) {
			$group = Groups_Group::read($group_id);
			if( !empty($group) )
				return self::$group_type;
		}
		return $r;
	}
	function filter_get_group_user_query($sql, $group_id, $group_type = '')
	{
		if (!is_numeric($group_id) || !empty($group_type) && $group_type != self::$group_type)
			return $sql;

		$group = Groups_Group::read($group_id);
		if (empty($group))
			return $sql;

		global $wpdb;
		$sql = $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}groups_user_group WHERE group_id = '%d'", $group_id);
		return $sql;
	}

	function filter_add_to_scripts($grassblade_add_scripts_on_page) {
		$grassblade_add_scripts_on_page[] = "groups-admin";
		return $grassblade_add_scripts_on_page;
	}

	static function get_user_ids($group_id)
	{
		$Group = new Groups_Group($group_id);
		$user_ids = $Group->__get('user_ids');
		return empty($user_ids) ? array() : $user_ids;
	}

	static function get_groups($params) {
		$groups = array();
		if (!empty($params["group_leader_id"])) {
			$group_leader_group_ids = static::get_leaders_group_ids($params["group_leader_id"]);
			if (empty($group_leader_group_ids) || !empty($params["id"]) && !in_array($params["id"], $group_leader_group_ids))
				return array();

			foreach($group_leader_group_ids as $group_id) {
				$group = Groups_Group::read($group_id);
				if(!empty($group))
					$groups[$group_id] = $group;
			}
		}
		else if(!empty($params["id"]))
			$groups[] = Groups_Group::read($params["id"]);
		else
			$groups = Groups_Group::get_groups();

		$groups_return = array();
		foreach($groups as $group) {
			if( !empty($group) && !empty($group->group_id) )
			$groups_return[] = array(
				"ID" 	=> $group->group_id,
				"name"	=> $group->name,
				"type"	=> self::$group_type
			);
		}
		return $groups_return;
	}

	function add_group_leader_settings( $output, $group_id = 0 )
	{
		$output .= '<fieldset><h3>'.self::text_group_leader_selection_title().'</h3>';
		$output .= '<p>'. self::text_group_leader_selection_desc() . '</p>';
		$output .= 	static::group_leaders_dropdown_html( $group_id );
		$output .= '</fieldset><br>';

		return $output;
	}

} // end of grassblade_groups_group class

return new grassblade_groups_group();


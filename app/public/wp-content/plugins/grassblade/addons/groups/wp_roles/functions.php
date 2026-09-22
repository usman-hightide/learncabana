<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class gb_groups_wp_roles extends gb_groups_base  {
	static $short_key = "role";
	static $meta_key = "";//gb_group_leader_".self::$short_key;
	static $group_type = "WP: Roles";

	function __construct() {
		parent::__construct();
	}

	function run() {
		add_filter("grassblade_groups", array($this,'filter_grassblade_groups'), 10, 2);
		add_filter("grassblade_group_user_query", array($this, 'filter_get_group_user_query'), 10, 3);
		add_filter("grassblade/groups/get/group_type", array($this,'filter_get_group_type' ), 10, 2);
	}
	function filter_get_group_type( $r, $post ) {
		if( !empty($r) )
			return $r;

		if( is_string($post) && !empty(wp_roles()->roles[$post]) )
			return self::$group_type;

		if(is_object($post) && get_class($post) == 'WP_Role')
			return self::$group_type;

		$group_id = is_string($post) ? $post : gb_get_value($post, "ID", gb_get_value($post, "id", 0));
		if( is_numeric($group_id) && !empty($group_id) ) {
			$all_roles = self::get_all();
			if( !empty($all_roles[$group_id]) )
				return self::$group_type;
		}
		return $r;
	}
	function filter_get_group_user_query($sql, $group_id, $group_type = '')
	{
		if (!is_numeric($group_id) || !empty($group_type) && $group_type != self::$group_type)
			return $sql;

		$role = self::get($group_id);
		if (empty($role))
			return $sql;

		global $wpdb;
		$sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%\"$role\"%'";
		return $sql;
	}

	static function get_groups($params)
	{
		$groups = array();

		if (!empty($params["group_leader_id"])) {
			if( ! grassblade_lms::is_admin($params["group_leader_id"]) )
			return [];
		}

		if (!empty($params["id"]))
			$groups[$params["id"]] = self::get($params["id"]);
		else
			$groups = self::get_all();

		$groups_return = array();
		foreach ($groups as $group_id => $role) {
			$groups_return[] = array(
				"ID" 	=> $group_id,
				"name"	=> "Role: " . self::get_role_name($role),
				"type"	=> self::$group_type
			);
		}

		return $groups_return;
	}
	static function get_role_name($role) {
		$wp_roles = wp_roles()->roles;
		return ( !empty($wp_roles[$role]) && !empty($wp_roles[$role]["name"]) )? $wp_roles[$role]["name"]:$role;
	}
	static function get_leaders_group_ids( $group_leader_id ){
		return array();
	}

	static function get_user_ids($group_id){

		$roles = get_option('gb_groups_wp_roles');
		$role = !empty($roles[$group_id]) ? $roles[$group_id] : '';

		$args = array('role' => $role);
		$users = get_users($args);

		$user_ids = array();

		if(!empty($users))
		foreach ($users as $user) {
			$user_ids[] = $user->ID;
		}
		return $user_ids;
	}

	static function get_all() {
		global $grassblade;
		if( !empty($grassblade["gb_groups_wp_roles"]) ) {
			return $grassblade["gb_groups_wp_roles"];
		}

		$wp_roles = wp_roles()->roles;
		$my_roles = get_option("gb_groups_wp_roles");

		if(empty($my_roles)) {
			$my_roles = array( 200000 => "administrator" );
		}

		foreach($wp_roles as $role_id => $role){
			if( !in_array($role_id, $my_roles) ) {
				$my_roles[] = $role_id;
			}
		}
		update_option( 'gb_groups_wp_roles' , $my_roles);
		$grassblade["gb_groups_wp_roles"] = $my_roles;

		return $grassblade["gb_groups_wp_roles"];
	}

	static function get($group_id){
		$groups = self::get_all();
		return isset($groups[$group_id]) ? $groups[$group_id] : '';
	}

} // end of gb_groups_wp_roles class

return new gb_groups_wp_roles();

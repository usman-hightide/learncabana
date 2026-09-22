<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class gb_groups {
	public static $addons = array();

	function __construct() {
		add_action( "plugins_loaded", array($this, "run"), 20);
		add_filter( 'xmlrpc_methods', array($this, "filter_xmlrpc_get_groups") );
		add_action( 'rest_api_init', array($this, "filter_rest_api_get_groups") );
	}

	function run() {
		$this->load_addons();
        /* Applicable only for Group Integrations that use GB Group Leader */
		add_filter("grassblade_reports_menu_cap", array($this, "filter_reports_menu_capability"), 10, 1);
		add_filter("grassblade/integrations/settings", array($this, 'filter_settings_fields'), 10, 1);
		add_filter("grassblade_is_group_leader", array($this, "filter_is_group_leader"), 11, 2);
	}

	static function filter_settings_fields($settings) {

		if( empty( $settings['section-groups'] ))
		$settings['section-groups'] = array(
			'title'				=> __('Manage Groups', 'grassblade'),
			'fields'			=> array()
		);

		$addons = self::get_addons();
		$fields = array();
		foreach($addons as $addon) {
			$addon_name = trim(str_replace("WP:", "", $addon::$group_type));
			$fields[] = array(
									"id" 	=> "gb_groups_" . $addon::$short_key,
									"title"	=> $addon_name,
									"type" 	=> "checkbox",
                                    "checked" => true // Default: enabled
								);
		}
		$settings['section-groups']['fields'] = $fields;
		return $settings;
	}
	static function role() {
		$role = "gb_group_leader";

		$group_leader_role = get_role($role);
		if(empty($group_leader_role)) {
			$capabilities = array(
				'read' => true
			);
			add_role($role, "GB Group Leader", $capabilities);
		}
		return $role;
	}
	function filter_is_group_leader($r, $user_id)
	{
		if (!empty($r))
			return $r;

		return user_can($user_id, self::role());
	}
	function filter_reports_menu_capability($cap) {

		if(current_user_can(self::role()))
			return self::role();

		return $cap;
	}
	static function get_addons() {
		global $grassblade;
		return empty($grassblade["groups_addons"])? array():$grassblade["groups_addons"];
	}
	static function load_addons() {
		global $grassblade;
		if( empty($grassblade["groups_addons"]) )
			$grassblade["groups_addons"] = array();

		$addon_files = glob(dirname(__FILE__)."/*/functions.php");
		$addon_files = apply_filters("grassblade/groups/addon_files", $addon_files);

		foreach($addon_files as $addon_file) {
			if( file_exists($addon_file) ) {
				$addon = include_once($addon_file);
				if( is_object($addon) )
				$grassblade["groups_addons"][$addon::$short_key] = $addon;
			}
		}
	}
    /*
        Function to get group leaders of ANY group
    */
	static function get_group_leaders($group) {
		global $grassblade_group_leaders;
		if(is_object($group))
			$group = (array) $group;

		$group_id = !empty($group["ID"])? $group["ID"]: ( !empty($group["id"])? $group["id"]:0 );
		$group_type = !empty($group["type"])? $group["type"]:"";
		$group_key = $group_type."::".$group["ID"];

		if(empty($group_id))
			return array();

		if(!isset($grassblade_group_leaders[$group_id])) {
			$group_data = self::get($group_id, $group_type, $add_users_list = false, $add_leaders_list = true);
			$grassblade_group_leaders[$group_key] = empty( $group_data["group_leaders"] )? array():$group_data["group_leaders"];
		}

		return $grassblade_group_leaders[$group_key];
	}
    /*
        Function to get users of ANY group
    */
	static function get_group_users($group) {
		if( is_object($group))
		$group = (array) $group;

		$group_id = !empty($group["ID"])? $group["ID"]: ( !empty($group["id"])? $group["id"]:0 );

		if(empty($group_id))
		return array();

		$group_type = !empty($group["group_type"])? $group["group_type"]:"";
		$group_data = self::get($group_id, $group_type, $add_users_list = true, $add_leaders_list = false);
		return $group_data["group_users"];
	}
    /*
        Function to check if user is group leader of ANY group
    */
	static function is_group_leader($user_id = 0) {
		global $grassblade;

		if(empty($user_id))
			$user_id = get_current_user_id();

		if(empty($user_id))
			return false;

		$is_group_leader = user_can($user_id, self::role());

		if(empty($grassblade["grassblade_is_group_leader"]))
			$grassblade["grassblade_is_group_leader"] = array();

		$grassblade["grassblade_is_group_leader"][$user_id] = apply_filters("grassblade_is_group_leader", $is_group_leader, $user_id);
		return $grassblade["grassblade_is_group_leader"][$user_id];
	}
	static function is_group_leader_of_user($group_leader_id, $user_id, $force = false) {
		global $grassblade_is_group_leader_of_user;

		if(empty($group_leader_id) || empty($user_id))
			return false;

		if(!isset($grassblade_is_group_leader_of_user[$group_leader_id]))
			$grassblade_is_group_leader_of_user[$group_leader_id] = array();

		if(!isset($grassblade_is_group_leader_of_user[$group_leader_id][$user_id]) || $force )
			$grassblade_is_group_leader_of_user[$group_leader_id][$user_id] = apply_filters("grassblade_is_group_leader_of_user", false, get_current_user_id(), $user_id);

		return $grassblade_is_group_leader_of_user[$group_leader_id][$user_id];
	}
	static function is_group_leader_of_group($group_leader_id, $group, $group_type = "") {
		if(is_numeric($group))
			$group = self::get($group, $group_type);

		if(is_object($group))
			$group = (array) $group;

		$leaders = self::get_group_leaders($group);

		if(!empty($group["ID"]) && !empty($leaders[$group_leader_id]))
			return true;
		else
			return false;
	}
	static function is_group_leader_of_course($group_leader_id, $course_id) {
		if(empty($group_leader_id))
			return false;

		$group_leader = get_user_by("id", $group_leader_id);
		if(empty($group_leader->ID))
			return false;

		$post_status = "publish,private,draft";
		$courses = grassblade_lms::get_courses(array("post_status" => $post_status, "user" => $group_leader));

		return !empty($courses[$course_id]);
	}


	function filter_xmlrpc_get_groups( $methods ) {
		$methods['grassblade.getGroups'] = array($this, "xmlrpc_callback_get_groups");
		return $methods;
	}
	function xmlrpc_callback_get_groups( $args ) {
		global $wp_xmlrpc_server;
		$wp_xmlrpc_server->escape( $args );

		$blog_id  = $args[0];
		$username = $args[1];
		$password = $args[2];

		if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) )
			return $wp_xmlrpc_server->error;

		if(user_can($user, "manage_options") || user_can($user, "connect_grassblade_lrs")) {
			$params = $args[3];
			return self::get_all($params);
		}

		return;
	}
	function filter_rest_api_get_groups() {
		register_rest_route( 'grassblade/v1', '/getGroups', array(
			'methods' => 'GET',
			'callback' => array($this, "rest_api_get_groups"),
			'permission_callback' => function () {
			return current_user_can( 'connect_grassblade_lrs' ) ||  current_user_can( 'manage_options' );
			}
		) );
	}
	function rest_api_get_groups( $d ) {
		$params = array();

		if(!empty($_REQUEST["id"]) && is_numeric($_REQUEST["id"]))
			$params["id"] = intval($_REQUEST["id"]);

		if(!empty($_REQUEST["type"]))
			$params["group_type"] = $_REQUEST["type"];

		if(!empty($_REQUEST["posts_per_page"]) && is_numeric($_REQUEST["posts_per_page"]))
			$params["posts_per_page"] = intval($_REQUEST["posts_per_page"]);

		if(!empty($_REQUEST["leaders_list"]) && is_numeric($_REQUEST["leaders_list"]))
			$params["leaders_list"] = true;

		if(!empty($_REQUEST["users_list"]) && is_numeric($_REQUEST["users_list"]))
			$params["users_list"] = true;

		return self::get_all($params);
	}
	static function user_query($group_id, $group_type = "") {
		$return = apply_filters("grassblade_group_user_query", $sql = "", $group_id, $group_type);
		return $return;
	}
	static function add_user_query($sql, $group_id = "", $user_id_key = "user_id", $group_type = "") {
		if(!empty($group_id) && is_numeric($group_id))
		$group_user_query = self::user_query($group_id, $group_type);

		if(!empty($group_user_query))
		$sql = preg_replace("/\sWHERE\s/i", " WHERE `".sanitize_key($user_id_key)."` IN ( ".$group_user_query." ) AND ", $sql);
		// echo "ful query: $sql";

		return $sql;
	}
	static function get( $id, $group_type = "", $add_users_list = false, $add_leaders_list = false ) {
		$groups = self::get_all( array("id" => $id, "group_type" => $group_type, "leaders_list" => $add_leaders_list, "users_list" => $add_users_list) );
		return $groups;
	}
	static function get_all( $params = array() ) {
		$groups = apply_filters("grassblade_groups", array(), $params);

        if( empty($groups) || !is_array($groups) )
        return array();

        if( !isset($groups["ID"]) )
        uasort($groups, function($g1, $g2) {
            return  ( strtolower( $g1["name"] ) > strtolower( $g2["name"] ) )? 1:-1;
        });

		return $groups;
	}
	/*
	* Function to get group leaders of a group
	* @param $post 		: is $post object or $group_id
	*/
	static function get_group_type( $post ) {
		return apply_filters("grassblade/groups/get/group_type", "", $post);
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) exit;
//Note: BuddyPress and Groups plugin uses the same action "groups_created_group". Show error when you try to create new group in Groups plugin, but works properly at the time editing the same group.

class grassblade_bp_group extends gb_groups_base {
	static $short_key = "bp";
	static $meta_key = "";//gb_group_leader_".self::$short_key;
	static $group_type = "WP: Buddypress Group";

	function __construct() {
		parent::__construct();
	}

	function run(){
		if ($this->is_plugin_active()) {
			add_filter("grassblade_groups", array($this,'filter_grassblade_groups'), 10, 2); //in base
			// add_filter("grassblade_is_group_leader", array($this, "filter_is_group_leader"), 11, 2); //required when addon has it's own group leader
			add_filter("grassblade_is_group_leader_of_user", array($this, "filter_is_group_leader_of_user"), 10, 3); //in base
			add_filter("grassblade_get_courses", array($this, "filter_get_courses"), 10, 2); // in base

			add_filter("grassblade_group_user_query", array($this, 'filter_get_group_user_query'), 10, 3);
			add_filter('grassblade/groups/get/courses/'.self::$short_key, array($this, "filter_get_group_courses"), 10, 4);

			add_filter( 'grassblade_add_scripts_on_page', array($this, 'filter_add_to_scripts') );
			add_action( 'bp_groups_admin_meta_boxes', array($this,'add_group_leader_settings' ), 10 ) ;
			add_action( 'bp_group_admin_edit_after', array($this,'save_group_leader_list' ), 10, 1);

			add_filter("grassblade/groups/get/group_type", array($this,'filter_get_group_type' ), 10, 2);
			/*
				Future Improvement to give Group Leader access to Moderators
				1. Add Filter to give Access to Moderator: add_filter("grassblade_is_group_leader", array($this, "filter_is_group_leader"), 11, 2);
				2. Add Filter to add moderator's ids to get_leaders_group_ids: apply_filter("grassblade/groups/get/leaders_group_ids", $leader_group_ids, $group_leader_id);
				3. Can add a setting to enable/disable group leader access to moderator.
			*/
		}
	}
	function is_plugin_active() {
		return function_exists("groups_get_group");
	}

	function filter_get_group_type( $r, $post ) {
		if( !empty($r) )
			return $r;

		if( is_object($post) )
			return ( get_class($post) == "BP_Groups_Group" ) ? self::$group_type : $r;

		$group_id = is_numeric($post) ? $post : gb_get_value($post, "ID", gb_get_value($post, "id", 0));
		if( is_numeric($group_id) && !empty($group_id) ) {
			$group = groups_get_group($group_id);
			if( !empty($group) && !empty($group->id) )
				return self::$group_type;
		}
		return $r;
	}
	function filter_get_group_courses($courses, $group_id, $params, $ids_only) {
		if( !defined("LEARNDASH_VERSION") || !is_numeric($group_id) )
			return $courses;

		$course_id = groups_get_groupmeta( $group_id, 'bp_course_attached', true );
		if( empty($course_id) )
			return $courses;

		if( $ids_only )
			$courses[] = $course_id;
		else {
			$course	= get_post($course_id);

			if(!empty($course))
				$courses[] = $course;
		}
		return $courses;
	}

	function filter_get_group_user_query($sql, $group_id, $group_type = '') {

		if(!is_numeric($group_id) || !empty($group_type) && $group_type != self::$group_type)
			return $sql;

		$group = groups_get_group($group_id);
		if(empty($group))
			return $sql;

		global $wpdb;
		$sql = $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}bp_groups_members WHERE group_id = %d ", $group_id);
		return $sql;
	}

	function filter_add_to_scripts($grassblade_add_scripts_on_page) {
		$grassblade_add_scripts_on_page[] = "bp-groups";
		return $grassblade_add_scripts_on_page;
	}

	static function get_user_ids($group_id){

		$user_ids = array();
		$members = groups_get_group_members(['group_id' => $group_id]);
		$members = $members['members'];
		if(!empty($members))
		foreach ($members as $member) {
			$user_ids[] = $member->id;
		}

		$moderators = groups_get_group_mods($group_id);
		foreach($moderators as $moderator){
			$user_ids[] = $moderator->user_id;
		}
		$admins = groups_get_group_admins($group_id);
		foreach($admins as $admin){
			$user_ids[] = $admin->user_id;
		}
		return empty($user_ids) ? array() : $user_ids;
	}

	static function get_groups($params) {

		$groups = array();
		if (!empty($params["group_leader_id"])) {
			$group_leader_group_ids = self::get_leaders_group_ids($params["group_leader_id"]);
			if (empty($group_leader_group_ids) || !empty($params["id"]) && !in_array($params["id"], $group_leader_group_ids))
				return array();

			foreach($group_leader_group_ids as $group_id) {
				$group = groups_get_group($group_id);
				if(!empty($group))
					$groups[$group_id] = $group;
			}
		} else if(!empty($params["id"]))
			$groups[] = groups_get_group($params["id"]);
		else
		{
			$groups_data = groups_get_groups();
			$groups = $groups_data["groups"];
		}

		$groups_return = array();
		foreach($groups as $group) {
			if( !empty($group) && !empty($group->id) )
			$groups_return [] = array(
				"ID" 	=> $group->id,
				"name"	=> $group->name,
				"type"	=> self::$group_type
			);
		}
		return $groups_return;
	}

	function add_group_leader_settings() {
        add_meta_box(
            self::$meta_key, // Meta box ID
            self::text_group_leader_selection_title(), // Meta box title
            array($this, "render_settings_metabox"), // Meta box callback function
            get_current_screen()->id, // Screen on which the metabox is displayed. In our case, the value is toplevel_page_bp-groups
            'side', // Where the meta box is displayed
            'core' // Meta box priority
        );
    }

    function render_settings_metabox() {
        $group_id = intval( $_GET['gid'] );
        ?>
        <div class="bp-groups-settings-section" id="bp-groups-settings-section-content-protection">
            <fieldset>
                <p><?php echo self::text_group_leader_selection_desc() ?> </p>
				<?php echo static::group_leaders_dropdown_html($group_id) ?>
            </fieldset>
        </div>

        <?php
    }
} // end of grassblade_bp_group class

return new grassblade_bp_group();


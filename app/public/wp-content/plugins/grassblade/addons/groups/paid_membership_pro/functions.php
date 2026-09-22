<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class gb_groups_paid_membership_pro extends gb_groups_base
{
	public static $short_key = "pmpro";
	public static $group_type = 'WP: Paid Membership Pro';
	function __construct()
	{
		parent::__construct();
	}
	function run()
	{
		if ($this->is_plugin_active()) {
			add_filter("grassblade_groups", array($this, 'filter_grassblade_groups'), 10, 2); //in base
			//add_filter("grassblade_is_group_leader", array($this, "filter_is_group_leader"), 11, 2); //in base
			add_filter("grassblade_is_group_leader_of_user", array($this, "filter_is_group_leader_of_user"), 10, 3); //in base
			add_filter("grassblade_get_courses", array($this, "filter_get_courses"), 10, 2); //in base

			add_filter("grassblade_group_user_query", array($this, 'filter_get_group_user_query'), 10, 3);

			add_filter('grassblade/groups/get/courses/'.self::$short_key, array($this, "filter_get_group_courses_ld"), 10, 4);
			add_filter('grassblade/groups/get/courses/'.self::$short_key, array($this, "filter_get_group_courses_lifter"), 10, 4);

			add_action("pmpro_membership_level_after_other_settings", array($this, 'add_group_leader_settings'), 10, 1);
			add_action("pmpro_save_membership_level", array($this, "save_group_leader_list"), 10, 1); // in base

			add_filter("grassblade/groups/get/group_type", array($this,'filter_get_group_type' ), 10, 2);
		}
	}
	function is_plugin_active() {
		return function_exists("pmpro_getLevel");
	}

	function filter_get_group_type( $r, $post ) {
		if( !empty($r) )
			return $r;

		//check if $post is a PMPro level object
		if( is_object($post) && isset($post->initial_payment) && isset($post->billing_amount) && isset($post->cycle_number) && isset($post->cycle_period) )
			return self::$group_type;

		$group_id = is_numeric($post) ? $post : gb_get_value($post, "ID", gb_get_value($post, "id", 0));
		if( is_numeric($group_id) && !empty($group_id) ) {
			$group = pmpro_getLevel($group_id);
			if( !empty($group) )
				return self::$group_type;
		}
		return $r;
	}

	function filter_get_group_courses_lifter($courses, $group_id, $params, $ids_only){
		if( !is_numeric($group_id) )
		return $courses;

		if(class_exists("LLMS_Membership") && class_exists("PMPro_Courses_LifterLMS"))
			$course_ids = PMPro_Courses_LifterLMS::get_courses_for_levels($group_id);

		if(!empty($course_ids)) {
			if( $ids_only )
				return $course_ids;

			foreach ($course_ids as $course_id) {
				$course = get_post($course_id);
				if( ! empty($course->ID) ) {
					$course->lms = "lifter";
					$courses[] = $course;
				}
			}
		}

		return $courses;
	}

	function filter_get_group_courses_ld($courses, $group_id, $params, $ids_only){
		if( !defined("LEARNDASH_VERSION") || !is_numeric($group_id) )
			return $courses;

		$course_ids = array();
		if(class_exists("Learndash_Paidmemberships"))
			$course_ids = $course_ids + Learndash_Paidmemberships::get_level_objects($group_id);

		if(class_exists("PMPro_Courses_LearnDash"))
			$course_ids = $course_ids + PMPro_Courses_LearnDash::get_courses_for_levels($group_id);

		if(!empty($course_ids)) {
			if( $ids_only )
				return $course_ids;

			foreach ($course_ids as $course_id) {
				$course = get_post($course_id);
				if( ! empty($course->ID) ) {
					$course->lms = "ld";
					$courses[] = $course;
				}
			}
		}

		return $courses;
	}

	function filter_get_group_user_query($sql, $group_id, $group_type = '')
	{
		if (!is_numeric($group_id) || !empty($group_type) && $group_type != self::$group_type)
			return $sql;

		$group = pmpro_getLevel($group_id);

		if (empty($group))
			return $sql;

		global $wpdb;
		$sql = $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}pmpro_memberships_users WHERE membership_id = '%d' AND status = 'active'", $group_id);
		return $sql;
	}

	static function get_groups($params)
	{
		/*
			id: 				return group in array
			group_leader_id: 	return groups of group leader
			<no params>:		return all groups
		*/
		$groups = array();
		if (!empty($params["group_leader_id"])) {
			$group_leader_group_ids = static::get_leaders_group_ids($params["group_leader_id"]);
			if (empty($group_leader_group_ids) || !empty($params["id"]) && !in_array($params["id"], $group_leader_group_ids))
				return array();

			foreach ($group_leader_group_ids as $group_id) {
				$group = pmpro_getLevel($group_id);
				if (!empty($group))
					$groups[$group_id] = $group;
			}
		} else if (!empty($params["id"]))
			$groups[] = pmpro_getLevel($params["id"]);
		else
			$groups = pmpro_getAllLevels(true);

		$groups_return = array();
		foreach ($groups as $group) {
			if (!empty($group) && !empty($group->id))
				$groups_return[] = array(
					"ID" 	=> $group->id,
					"name"	=> $group->name,
					"type"	=> self::$group_type
				);
		}
		return $groups_return;
	}

	function add_group_leader_settings($level)
	{
	?>
		<table class="form-table">
			<tbody>
				<tr>
					<h3><?php echo self::text_group_leader_selection_title() ?></h3>
					<th>
						<p class="description"> <?php echo self::text_group_leader_selection_desc() ?></p>
					</th>
					<td>
						<?php
						echo static::group_leaders_dropdown_html($level->id)
						?>

					</td>
				</tr>
			</tbody>
		</table>
	<?php
	}

	static function get_user_ids($group_id)
	{
		global $wpdb;
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
			"SELECT u.ID FROM $wpdb->users u
			 LEFT JOIN {$wpdb->pmpro_memberships_users} mu
			 ON u.ID = mu.user_id
			 WHERE membership_id = %d
			 AND status = 'active'", $group_id
		));
		return empty($user_ids) ? array() : $user_ids;
	}

} // end of gb_groups_paid_membership_pro class

return new gb_groups_paid_membership_pro();
<?php
/**
 * @package GrassBlade
 * @version 1.7
 */
/*
Plugin Name: Visibility Control for LearnDash
Plugin URI: https://www.nextsoftwaresolutions.com/learndash-visibility-control
Description: Control visibility of HTML elements, menus, and other details on your website based on User's access to specific LearnDash Course, Group, Role or Login status. Add CSS class: visible_to_course_123 to show the element/menu item to user with access to Course with ID 123. Add CSS Class: hidden_to_course_123 to hide the element from user with access to Course with ID 123. Add CSS class: visible_to_logged_in to show the element/menu item to a logged in user. Add CSS class: hidden_to_logged_in or visible_to_logged_out to show the element/menu item to a logged out users. More Classes: visible_to_group_123, hidden_to_group_123, visible_to_course_none, hidden_to_course_none, visible_to_course_all, hidden_to_course_all, visible_to_group_none, hidden_to_group_none, visible_to_group_all, hidden_to_group_all, visible_to_role_administrator, hidden_to_role_administrator, visible_to_course_complete_123, hidden_to_course_complete_123, visible_to_course_incomplete_123 and hidden_to_course_incomplete_123. Currently, this will only hide the content using CSS.
Author: Next Software Solutions
Version: 1.7
Author URI: https://www.nextsoftwaresolutions.com
*/

class visibility_control_for_learndash {
	function __construct() {
		add_action("wp_head", array($this, "add_css"));

		if(!class_exists('grassblade_addons'))
		require_once(dirname(__FILE__)."/addon_plugins/functions.php");

		add_action( 'admin_menu', array($this,'menu'), 10);
		add_filter("learndash_submenu", array($this, "learndash_submenu"), 1, 1 );
	}

	function menu() {
		global $submenu, $admin_page_hooks;
		$icon = plugin_dir_url(__FILE__)."img/icon-gb.png";

		if(empty( $admin_page_hooks[ "grassblade-lrs-settings" ] )) {
			add_menu_page("GrassBlade", "GrassBlade", "manage_options", "grassblade-lrs-settings", array($this, 'menu_page'), $icon, null);
		}

		add_submenu_page("grassblade-lrs-settings", "Visibility Control for LearnDash", "Visibility Control for LearnDash",'manage_options','grassblade-visibility-control-learndash', array($this, 'menu_page'));
	}
	function learndash_submenu($add_submenu) {
		$add_submenu["visibility_control_for_learndash"] = array(
			"name"  => __('Visibility Control', "visibility_control_for_learndash"),
			"cap"   => "manage_options",
			"link"  => 'admin.php?page=grassblade-visibility-control-learndash'
		);
		return $add_submenu;
	}
	function menu_page() {

		if(!current_user_can("manage_options"))
			return;

		$enabled = get_option("visibility_control_for_learndash");

		if( !empty($_POST["submit"]) && check_admin_referer('visibility_control_for_learndash') ) {
			$enabled = intVal(isset($_POST["visibility_control_for_learndash"]));
			update_option("visibility_control_for_learndash", $enabled);
		}

		if($enabled === false) {
			$enabled = 1;
			update_option("visibility_control_for_learndash", $enabled);
		}

		?>
		<style type="text/css">
			div#visibility_control_for_learndash {
				padding: 30px;
				background: white;
				margin: 50px;
				border-radius: 5px;
			}
			div#visibility_control_for_learndash input[type=checkbox] {
			    margin-left: 50px;
			}
		</style>
		<div id="visibility_control_for_learndash" class="wrap">
			<h3>Visibility Control for LearnDash</h3>
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<?php wp_nonce_field( 'visibility_control_for_learndash' ); ?>
				<p style="padding: 20px;"><b><?php _e("Enable"); ?></b> <input name="visibility_control_for_learndash" type="checkbox" value="1" <?php if($enabled) echo 'checked="checked"'; ?>> </p>

				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e("Save Changes"); ?>"></p>

			</form>
			<br>
			<?php _e("For instructions on how to control visibility of different elements on your site based on LearnDash course access. Or login/logout state and more.", "visibility_control_for_learndash"); ?> <a href="https://wordpress.org/plugins/visibility-control-for-learndash/" target="_blank"><?php _e("Check the plugin page here.", "visibility_control_for_learndash"); ?></a>
		</div>
		<?php
	}
	function add_css() {
		if(!function_exists('sfwd_lms_has_access'))
			return;

		global $pagenow, $post;

		if( is_admin() && $pagenow == "post.php" ||  is_admin() && $pagenow == "post-new.php" )
			return; //Disable for Edit Pages

		if( !empty($post->ID) ) {
			if( $post->post_type == "page" && current_user_can( 'edit_page', $post->ID ) || $post->post_type != "page" &&  current_user_can( 'edit_post', $post->ID ) ) {
				//User with Edit Access
				if( isset($_GET["elementor-preview"]) || isset($_GET['brizy-edit'])  || isset($_GET['brizy-edit-iframe'])  || isset($_GET['vcv-editable'])   || isset($_GET['vc_editable']) || isset($_GET['fl_builder'])  || isset($_GET['et_fb'])  )
					return; //Specific Front End Editor Pages. Elementor, Brizy Builder, Beaver Builder, Divi, WPBakery Builder, Visual Composer
			}
		}

		$enabled = get_option("visibility_control_for_learndash", true);

		if(empty($enabled))
			return;

		global $current_user, $wpdb;
		$hidden_classes = array();
		if(!empty($current_user->ID)) { //Logged In
			$hidden_classes[] = ".hidden_to_logged_in";
			$hidden_classes[] = ".visible_to_logged_out";
		}
		else //Logged Out
		{
			$hidden_classes[] = ".hidden_to_logged_out";
			$hidden_classes[] = ".visible_to_logged_in";
		}

		$roles = wp_roles();
		$role_ids = array_keys($roles->roles);

		foreach($role_ids as $role_id) {
			if( empty($current_user->roles) || !in_array($role_id, $current_user->roles) ) { //User not with Role
				$hidden_classes[] = ".visible_to_role_".$role_id;
			}
			else //Has Role
			{
				$hidden_classes[] = ".hidden_to_role_".$role_id;
			}
		}

		$courses = get_posts("post_type=sfwd-courses&post_status=publish&numberposts=-1");
		$user_id = empty($current_user->ID)? null:$current_user->ID;

		$completed_courses_ids = empty($user_id) ? array() : $wpdb->get_col( $wpdb->prepare( "SELECT REPLACE(meta_key,'course_completed_','') as course_id FROM $wpdb->usermeta where user_id = %d and (meta_key like 'course_completed_%');", $user_id ));
		$course_ids = array();
		if(!empty($courses))
		foreach ($courses as $course) {
			$course_ids[] = $course->ID;
			if(!in_array($course->ID, $completed_courses_ids)){
				$hidden_classes[] = ".hidden_to_course_incomplete_".$course->ID;
				$hidden_classes[] = ".visible_to_course_complete_".$course->ID;
			}
		}
		if(!empty($completed_courses_ids))
		foreach ($completed_courses_ids as $course_id) {
			if(!empty($course_id) && is_numeric($course_id) && in_array($course_id, $course_ids)) {
				$hidden_classes[] = ".hidden_to_course_complete_" . intVal( $course_id );
				$hidden_classes[] = ".visible_to_course_incomplete_" . intVal( $course_id );
			}
		}
		$count_visible_courses = 0;

		if(!empty($courses))
		foreach ($courses as $course) {
			$has_access = sfwd_lms_has_access($course->ID, $user_id);
			if($has_access) {
				$count_visible_courses++;
				$hidden_classes[] = ".hidden_to_course_".$course->ID;
			}
			else
			{
				$hidden_classes[] = ".visible_to_course_".$course->ID;
			}
		}

		if(empty($count_visible_courses)) //Access to no courses
		{
			$hidden_classes[] = ".hidden_to_course_none";
		}
		else
		{
			$hidden_classes[] = ".visible_to_course_none";
		}

		if(empty($courses) || count($courses) == $count_visible_courses)
		{
			$hidden_classes[] = ".hidden_to_course_all";
		}
		else
		{
			$hidden_classes[] = ".visible_to_course_all";
		}

		$count_visible_groups = 0;
		$group_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'groups'");
		if(!empty($group_ids)) {
			$user_group_ids = empty($user_id)? array():$wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE user_id = '%d' AND ( meta_key LIKE 'learndash_group_administrators_%' OR meta_key LIKE 'learndash_group_users_%' ) ", $user_id));
			foreach($group_ids as $group_id) {
				$has_access = !empty($user_group_ids) && in_array( $group_id, $user_group_ids );

				if($has_access) {
					$count_visible_groups++;
					$hidden_classes[] = ".hidden_to_group_".$group_id;
			 	}
				else
				{
					$hidden_classes[] = ".visible_to_group_".$group_id;
			 	}
			}
		}

		if(empty($count_visible_groups)) //Access to no groups
		{
			$hidden_classes[] = ".hidden_to_group_none";
		}
		else
		{
			$hidden_classes[] = ".visible_to_group_none";
		}

		if(empty($group_ids) || count($group_ids) == $count_visible_groups)
		{
			$hidden_classes[] = ".hidden_to_group_all";
		}
		else
		{
			$hidden_classes[] = ".visible_to_group_all";
		}
		?>
		<style type="text/css" id="visibility_control_for_learndash">
			<?php echo implode(", ", $hidden_classes) ?> {
				display: none !important;
			}
		</style>
		<script>
			if(typeof jQuery == "function")
			jQuery(document).ready(function(){
				jQuery(window).on("load", function(e) {
					//<![CDATA[
					var hidden_classes = <?php echo json_encode( $hidden_classes ); ?>;
					//]]>
					jQuery(hidden_classes.join(",")).remove();
				});
			});
		</script>
		<?php
/*
			var p = <?php echo json_encode($post); ?>;
			var g = <?php echo json_encode($_GET); ?>;
			console.log(p);
			console.log(g);
			console.log("<?php echo $pagenow.":".current_user_can('edit_post', $post->ID).":".current_user_can('edit_page', $post->ID).":".$x; ?>");
*/
	}
}

new visibility_control_for_learndash();

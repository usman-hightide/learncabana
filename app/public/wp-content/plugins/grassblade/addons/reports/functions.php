<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports {
	public $groups = array();
	function __construct() {
		add_shortcode("grassblade_reports", array($this, "show_reports"));
		add_action( 'wp_ajax_grassblade_report', array($this, 'ajax') );
		add_action( 'admin_menu', array($this,'report_menu'));
		add_filter( 'grassblade_add_scripts_on_page', array($this, 'add_to_scripts') );
		add_filter('grassblade_settings_fields', array($this, "settings"), 10,1);

		add_filter( 'grassblade/reports/available_reports', array($this, "hide_reports_from_group_leaders"), 100, 1);
		add_filter( 'grassblade/reports/available_reports', array($this, "collect_reports_list"), 15, 1);
        add_filter( "grassblade_localize_script_data", array($this, "add_script_localizations"), 10, 2);

		add_action( "grassblade/reports/js/gb_reports", array($this, "add_filters_ux_to_gb_reports"), 10, 2);

		include_once dirname(__FILE__)."/gutenberg/admin-reports/index.php";

		$reports = array("completions_report", "gradebook", "achievements", "progress_snapshot", "video_report", "quiz_report","question_report", "user_profile_report");
		foreach($reports as $report) {
			if(file_exists(dirname(__FILE__)."/$report/functions.php"))
			include_once dirname(__FILE__)."/$report/functions.php";
		}
	}
	function report_menu() {
		$lms_admin_role = grassblade_settings("reports_lms_admin");
		$cap = (empty($lms_admin_role) || !current_user_can($lms_admin_role))? "manage_options":$lms_admin_role;
		add_submenu_page("grassblade-lrs-settings", __("Reports", "grassblade")." (beta)", __("Reports", "grassblade")." (beta)", apply_filters("grassblade_reports_menu_cap", $cap),'grassblade_reports', array($this, 'report_page') );
	}
	function report_page() {
		echo $this->show_reports(array());
	}
	function add_to_scripts($grassblade_add_scripts_on_page) {
		$grassblade_add_scripts_on_page[] = "grassblade_reports";
		return $grassblade_add_scripts_on_page;
	}

	function collect_reports_list($available_reports) {
		global $grassblade;
		if( empty($grassblade["reports"]) )
		$grassblade["reports"] = [];

		$grassblade["reports"]["list"] = $available_reports;

		return $available_reports;
	}
	static function get_list() {
		global $grassblade;
		if( !empty($grassblade["reports"]) && !empty($grassblade["reports"]["list"]) )
		return $grassblade["reports"]["list"];

		apply_filters("grassblade/reports/available_reports", array());
		return $grassblade["reports"]["list"];
	}
	function hide_reports_from_group_leaders($available_reports){

		if(!gb_groups::is_group_leader())
			return $available_reports;

		$hidden_reports = grassblade_settings("hide_reports", []);
		if(empty($hidden_reports))
			return $available_reports;

		foreach($hidden_reports as $report)
		{
			if(isset($available_reports[$report]))
			unset($available_reports[$report]);
		}

		return $available_reports;
	}
	function add_script_localizations($gb_data, $content_post) {
		$gb_data["lang"]["S.No."] 		= __("S.No.", "grassblade");
		$gb_data["lang"]["User"] 		= __("User", "grassblade");
		$gb_data["lang"]["Email"] 		= __("Email", "grassblade");
		$gb_data["lang"]["Video"] 		= __("Video", "grassblade");
		$gb_data["lang"]["Length"] 		= __("Length", "grassblade");
		$gb_data["lang"]["Attempts"] 	= __("Attempts", "grassblade");
		$gb_data["lang"]["Timespent"] 	= __("Timespent", "grassblade");
		$gb_data["lang"]["Heatmap"] 	= __("Heatmap", "grassblade");
		$gb_data["lang"]["Completed %"] = __("Completed %", "grassblade");

		$gb_data["lang"]["Not Watched"] = __("Not Watched", "grassblade");

		$gb_data["lang"]["Type"] 		= __("Type", "grassblade");
		$gb_data["lang"]["Percentage Watched"] = __("Percentage Watched", "grassblade");

		$gb_data["lang"]["Select All"] 	= __("Select All", "grassblade");
		$gb_data["lang"]["Select None"] = __("Select None", "grassblade");
		$gb_data["lang"]["Loading..."] 	= __("Loading...", "grassblade");
		$gb_data["lang"]["No data."] 	= __("No data.", "grassblade");

		return $gb_data;
	}
	function settings($fields) {
		global $wp_roles;
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

		$fields[] = array('id' => 'reports_setting', 'label' => __("Reports Settings", "grassblade"), "type" => "html", "subtype" => "field_group_start");

		$roles = $wp_roles->get_names();
		unset($roles["administrator"]);

		$name_formats = array(
			"display_name" => "Display Name",
			"last_name, first_name" => "Last Name, First Name",
			"first_name, last_name" => "First Name, Last Name",
			"user_login"	=> "Username",
		);
		$fields[] = array( 'id' => 'reports_lms_admin', 'label' => __( 'LMS Administrator', 'grassblade' ),  'type' => 'select' ,'help' => 'Select role with LMS Administrator access giving full access to reports.', "values" =>  array("" => "None") + $roles, "class" => "enable-select2" );

		$fields[] = array( 'id' => 'reports_all_courses_roles', 'label' => __( 'Report on all Courses to:', 'grassblade' ),  'type' => 'select-multiple', 'help' => 'Select role(s) with access to reporting on all xAPI Content and Courses. Reports are still limited to users in their group.'." <a href='https://www.nextsoftwaresolutions.com/kb/reports-for-group-leaders-admins/' target='_blank'><i class='dashicons  dashicons-editor-help gb_no_underscore'></i></a>", "values" => $roles, "class" => "enable-select2" );
		$fields[] = array( 'id' => 'reports_name_format', 'label' => __( 'Name format', 'grassblade' ),  'type' => 'select', 'help' => "Select how the names of users show on the reports", "values" => $name_formats, "class" => "enable-select2" );

		$available_reports = self::get_list();

        $fields[] = array( 'id' => 'hide_reports', 'label' => __( 'Hide Reports from Group Leaders', 'grassblade' ),  'type' => 'select-multiple', 'help' => 'Select to hide reports from group leaders.', "values" => $available_reports, "class" => "enable-select2" );
		$fields[] = array('id' => 'reports_setting_end', 'label' => __("Reports Settings", "grassblade"), "type" => "html", "subtype" => "field_group_end");

		return $fields;
	}
	static function get_user( $data ) {
		if(!empty($data["user"])) {
			$u = explode(":", $data["user"]);
			if(empty($u[0]) || $u[0] == "all")
				return "";
			else {
				if(is_numeric($u[0]))
				return get_user_by("id", $u[0]);
				else if(!empty($u[1]) && is_string($u[1]))
				return get_user_by("email", sanitize_email( $u[1] ));
				else
				return "invalid_user";
			}
		}
		return "";
	}
	function format_data( $data ) {
		$data = gb_sanitize_data( $data, "wp_strip_all_tags" );
		$data = gb_sanitize_data( $data, "addslashes" );

		$required_fields = array("report", "function", "date_range", "contents", "course_id", "group_id", "user");
		foreach($required_fields as $field)
		$data[$field] 	= !empty($data[$field]) ? $data[$field]:"";

		$data["course_id"] 	= ($data["course_id"] == "all") ? "all" : intVal( $data["course_id"] );
		$data["group_id"] 	= ($data["group_id"] == "all") ? "all" : intVal( $data["group_id"] );
		$data["user"] = self::get_user($data);

		return $data;
	}
	function ajax() { //$_POST = $_REQUEST;
		$request_data = $this->format_data( $_POST );

		if(!empty($request_data["function"])) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			if(!grassblade_lms::is_admin() && !gb_groups::is_group_leader()) {
				$return = array("error" => "Unauthorized access.");
				echo json_encode($return);
				exit;
			}
			global $current_user, $wpdb;

			$report 	= $request_data["report"];
			$user 		= $request_data["user"];
			$course_id 	= $request_data["course_id"];
			$course = $course_id == "all"? null:grassblade_lms::get_course($course_id);

			if( $user == "invalid_user" ) {
				$return = array("error" => "Invalid access.");
				echo json_encode($return);
				exit;
			}
			if(!empty($course_id)) { //Check only if Course ID is present
				if(empty($course) && $course_id != "all" ) { //$course can be empty only for 'all'
					$return = array("error" => "Invalid course selection.");
				}

				if(empty($return))
				if( !grassblade_lms::is_admin() && !$this->can_report_on_all_courses() ) { //IF doesn't have access to all courses.
					if($course_id == "all")
						$return = array("error" => "Invalid course selection.");
					else if(!gb_groups::is_group_leader_of_course($current_user->ID, $course_id)) //No access to report on this Course
						$return = array("error" => "Invalid course selection.");
				}

				if(!empty($return))
				{
					echo json_encode($return);
					exit;
				}
			}

			if(!empty($course_id) && empty($course) && ($course_id != "all" || !grassblade_lms::is_admin() && !$this->can_report_on_all_courses() )) {
				$return = array("error" => "Invalid course selection.");
				echo json_encode($return);
				exit;
			}

			$group_id 	= $request_data["group_id"]; //Need to change for integrations?
			$group_type = !empty($request_data["group_type"]) ? $request_data["group_type"] : ""; //Need to change for integrations?
			$group 		= ($group_id == "all" || empty($group_id))? null:gb_groups::get($group_id, $group_type);

			if(	!empty($group) && $group_id != $group["ID"] || //Mismatch in Group ID
				empty($group) && $group_id != "all" || // Group not found.
				$group_id == "all" && !grassblade_lms::is_admin($current_user->ID) || // All Users (for all groups) access to admin only
				$group_id != "all" && !empty($group) && !grassblade_lms::is_admin() && !gb_groups::is_group_leader_of_group($current_user->ID, $group_id, $group_type) ) { // Valid Group ID, Access to admin and group leader only
				$return = array("error" => "Invalid group selection.");
				echo json_encode($return);
				exit;
			}

			if(!empty($group_id) && ( !grassblade_lms::is_admin() && !gb_groups::is_group_leader_of_group($current_user->ID, $group_id, $group_type) )) {
				$return = array("error" => "Invalid access.3");
				echo json_encode($return);
				exit;
			}
			$function = grassblade_sanitize_filename($request_data["function"]);

			switch ($function) {
				case 'get_users':
					$return = array(array(
						"ID" 	=> "all",
						"name" 	=> __("All Users", "grassblade"),
						"email" => "all",
						"class" => "show_on",// Adding additional classes from filter: show_on_report_achievement_report show_on_report_completions_report"
					));
					$return = array_merge($return, self::get_users( $group_id, false, $group_type)); //add group_type

					break;
				case 'get_contents_list':
					$c = $course_id == "all"? $course_id:$course;
					$contents_list = grassblade_lms::get_course_content_list($c);

					$return = array();
					if(!empty($contents_list)) {
						// get postmeta with meta_key = xapi_content, unserialize it and get the content_type
						$meta_data = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'xapi_content' AND post_id IN (".implode(",", array_keys($contents_list)).")");
						$meta_data = wp_list_pluck($meta_data, "meta_value", "post_id");

						foreach ($contents_list as $content_id => $content_title) {
							$content_title = sanitize_text_field($content_title);
							$content_title = empty($content_title)? $content_id:$content_title;
							$xapi_content = !empty($meta_data[$content_id])? maybe_unserialize($meta_data[$content_id]):array();
							$content_type = !empty($xapi_content["content_type"])? $xapi_content["content_type"]:"";
							$return[] = array("ID" => $content_id, "name" => $content_title, "content_type" => $content_type);
						}

					}

					break;
				case 'get_report':

					$contents = $request_data["contents"];
					$date_range = $request_data["date_range"];
					$date_range = explode(" - ", str_replace(",", "", $date_range));

					$from 	= !empty($date_range[0])? gb_strtotime_utc($date_range[0]):"";
					$to 	= !empty($date_range[1])? (gb_strtotime_utc($date_range[1]) + 86399):"";

					if(!empty($contents))
					foreach ($contents as $key => $value) {
						if(intval($key) != $key)
							unset($contents);
						else
							$contents[$key] = intval($value);
					}
					if(!empty($user) && !empty($user->ID) ) {
						if( !grassblade_lms::is_admin() && !gb_groups::is_group_leader_of_user($current_user->ID, $user->ID )) {
							$return = array("error" => "Invalid access.7");
						}
					}
					if(empty($return)) {
						$params = array(
							'user' 		=> $user,
							'course_id' => $course_id,
							'course'	=> $course,
							'group_id'	=> $group_id,
							'group_type' => $group_type,
							'group'		=> $group,
							'contents' 	=> $contents,
							'from' 		=> $from,
							'to' 		=> $to
						);

						$return = apply_filters("grassblade/reports/get/".$report, array("data" => array()), $params);
						if(!empty($return) && !empty($return["data"]) && is_array($return["data"])) { //Cleanup any extra users
							$user_ids = self::get_users( $group_id, true, $group_type );

							foreach($return["data"] as $k => $row) {
								if(isset($row["user_id"]) && !in_array($row["user_id"], $user_ids)) {
									$return["data"] = array();
									$return["error"] = "Invalid data detected in the report.";
									break;
								}
							}
						}
					}
					break;
			}
			$return = apply_filters("grassblade/reports/" . $function . "/return", $return, $request_data);
			echo json_encode($return);
			exit();
		}
	}
	/*
	* @since 5.2
	*/
	static function get_users( $group_id, $ids_only = false, $group_type = "" ) { //need to pass group_type some how
		global $wpdb;
		if(empty($group_id))
		return array();

		$users = array();
		$user_ids = array();

		if($group_id == "all") {
			if(!grassblade_lms::is_admin())
			return array();

			$users_r = $wpdb->get_results("SELECT ID, user_email, user_login, display_name FROM $wpdb->users");
			foreach ($users_r as $user) {
				$user_ids[] = $user->ID;
				$users[] = array(
					"ID" 	=> $user->ID,
					"name" 	=> gb_name_format($user),
					"email" => $user->user_email
				);
			}

			if ( $ids_only )
			return $user_ids;
		}
		else
		{
			$group_data = gb_groups::get($group_id, $group_type, $add_users_list = true );
			if(!empty($group_data["group_users"]))
			$user_ids = array_map("intVal", array_keys($group_data["group_users"]));

			if ( $ids_only )
			return $user_ids;

			while(!empty($user_ids) && count($user_ids) > 0) {
				$per_query = 500;
				$user_ids_to_query = array_splice($user_ids, 0, $per_query);

				if(!empty($user_ids_to_query)) {
					$users_r = $wpdb->get_results("SELECT ID, user_email, user_login, display_name FROM $wpdb->users WHERE ID IN ( ".implode(",", $user_ids_to_query)." )");
					foreach ($users_r as $user) {
						$users[] = array(
							"ID" 	=> $user->ID,
							"name" 	=> gb_name_format($user),
							"email" => $user->user_email
						);
					}
				}
			}
		}

		uasort($users, function($u1, $u2) {
			return ( strtolower( $u1["name"] ) > strtolower( $u2["name"] ) )? 1:-1;
		});

		return $users;
	}
	function add_filters_ux_to_gb_reports( $GB_REPORTS ) {
		$report_filters_ux = array();
		$report_filters_ux = apply_filters("grassblade/reports/filters/ux", $report_filters_ux);
		$GB_REPORTS["report_filters_ux"] = $report_filters_ux;
		return $GB_REPORTS;
	}
	function show_reports($attr = array()) {
		global $current_user;
		if ( post_password_required() || empty($current_user->ID))
		    return 'Invalid access.1';

        if ( !grassblade_lms::is_admin() && !gb_groups::is_group_leader() )
		    return 'Invalid access.2';

		$shortcode_defaults = array(

		);

		$shortcode_atts = shortcode_atts ( $shortcode_defaults, $attr);
		extract($shortcode_atts);

		wp_enqueue_script("jquery");

		wp_enqueue_script( 'js-moment', plugins_url('/../../assets/moment.min.js', __FILE__ ), array("jquery"), GRASSBLADE_VERSION);
		wp_enqueue_script( 'js-daterangepicker', plugins_url('/../../assets/DateRangePicker/daterangepicker.min.js', __FILE__ ), array("jquery", "js-moment"), GRASSBLADE_VERSION);

		wp_register_script( 'datatable', plugins_url('/../../assets/DataTables/datatables.min.js', __FILE__ ), array("jquery"), GRASSBLADE_VERSION);
//		wp_localize_script("datatable", "GB_USER_REPORT_LIST", $data);
		wp_enqueue_script("datatable");


		wp_enqueue_style("datatable", plugins_url('/../../assets/DataTables/datatables.min.css', __FILE__ ), array(), GRASSBLADE_VERSION);
		wp_enqueue_style("grassblade-reports", plugins_url('/assets/style.css', __FILE__ ), array(), GRASSBLADE_VERSION);
		wp_enqueue_style("daterangepicker", plugins_url('/../../assets/DateRangePicker/daterangepicker.css', __FILE__ ), array(), GRASSBLADE_VERSION);

		wp_register_script( 'grassblade-reports', plugins_url('/assets/script.js', __FILE__ ), array("jquery", "datatable", "js-daterangepicker"), GRASSBLADE_VERSION);

		$available_reports = array();
		$available_reports = apply_filters("grassblade/reports/available_reports", $available_reports);

		$grassblade_report_defaults = array();
		if(!empty($_REQUEST["group_id"])) {
			$grassblade_report_defaults["group_id"] = sanitize_text_field($_REQUEST["group_id"]);
		}
		if(!empty($_REQUEST["course_id"])) {
			$grassblade_report_defaults["course_id"] = sanitize_text_field($_REQUEST["course_id"]);
		}
		if(!empty($_REQUEST["user"]) && filter_var($_REQUEST["user"], FILTER_VALIDATE_EMAIL)) {
			$grassblade_report_defaults["user"] = sanitize_text_field($_REQUEST["user"]);
		}
		if(!empty($_REQUEST["report"]) && is_string($_REQUEST["report"]) && in_array($_REQUEST["report"], array_keys($available_reports) )) {
			$grassblade_report_defaults["report"] = sanitize_text_field($_REQUEST["report"]);
		}
		if(!empty($_REQUEST["content"]) && is_array($_REQUEST["content"])) {
			$grassblade_report_defaults["content"] = array();
			foreach ($_REQUEST["content"] as $content_id) {
				if(is_numeric($content_id) && intval($content_id) == $content_id)
				{
					$grassblade_report_defaults["content"][$content_id] = $content_id;
				}
			}
		}
		if(!empty($_REQUEST["date_range"]) && is_string($_REQUEST["date_range"])) {
			$date_range = explode(" - ", $_REQUEST["date_range"]);

			$grassblade_report_defaults["date_range"]["start"] = date("Y-m-d", strtotime($date_range[0]));
			$grassblade_report_defaults["date_range"]["end"] = date("Y-m-d", strtotime($date_range[1]));
		}

		$GB_REPORTS = array(
					"defaults" 	=> $grassblade_report_defaults,
					"ajaxurl" 	=> admin_url( 'admin-ajax.php' ),
					"images" 	=> array(
						"locked" => plugins_url('assets/img/locked.png', __FILE__ ),
						"unlocked" => plugins_url('assets/img/unlocked.png', __FILE__ ),
						"progress" => plugins_url('assets/img/progress.png', __FILE__ ),
						"check" => plugins_url('assets/img/check.png', __FILE__ ),
						"quiz" => plugins_url('assets/img/quiz.png', __FILE__ ),
						"gbicon" => plugins_url('assets/img/gbicon.png', __FILE__ ),
					),
			);
		$GB_REPORTS = apply_filters("grassblade/reports/js/gb_reports", $GB_REPORTS);
		wp_localize_script("datatable", "GB_REPORTS", $GB_REPORTS);
		wp_enqueue_script("grassblade-reports");

		ob_start();
		include(dirname(__FILE__)."/form.php");
		$content = ob_get_clean();

		return $content;
	}
	function get_groups() {
		global $current_user;
		if(!empty($this->groups))
			return $this->groups;

		$groups = array();

		if(grassblade_lms::is_admin())
		$groups = gb_groups::get_all();
		else
		if(gb_groups::is_group_leader())
		$groups = gb_groups::get_all( array("group_leader_id" => $current_user->ID ));
		else
		$groups = array();

		return $groups;
	}
	function get_group_options() {
		$groups = $this->get_groups();
		$options = "";
		foreach ($groups as $group) {
			$group_id = $group["ID"];
			$type = trim($group["type"]);

			$selected = (!empty($_REQUEST["group_id"]) && $_REQUEST["group_id"] == $group_id)? "SELECTED":"";
			$options .= "<option value='".$group_id."' data-type='".$type."' $selected >".$group["name"]."</option>";
		}
		return $options;
	}
	static function can_report_on_all_courses($user_id = null) {
		$user = null;
		if(grassblade_lms::is_admin($user_id))
			$r = true;
		else {
			$reports_all_courses_roles = grassblade_settings("reports_all_courses_roles");
			$r = false;
			$user = empty($user_id)? wp_get_current_user():get_user_by("id", $user_id);

			if(!empty($reports_all_courses_roles))
			{
				if(!empty($user) && !empty($user->roles)) {
					if(!empty($reports_all_courses_roles) && is_array($reports_all_courses_roles)) {
						foreach ($reports_all_courses_roles as $role) {
							if(in_array($role, $user->roles)) {
								$r = true;
								break;
							}
						}
					}
				}
			}
		}
		return apply_filters("grassblade/reports/can_report_on_all_courses", $r, $user);
	}
	function get_course_options_html() {
		$courses = $this->get_course_options();
		$options = "";
		foreach ($courses as $course_id => $course) {
			$options .= "<option value='".$course_id."' class='".esc_attr($course["class"])."'>".sanitize_text_field($course["post_title"])."</option>";
		}

		return $options;
	}
	function get_course_options() {
		global $current_user;
		$post_status = grassblade_lms::is_admin()? "publish,private,draft":"publish";
		$options = "";
		$courses = array();
		if(grassblade_lms::is_admin() || $this->can_report_on_all_courses($current_user->ID)) {
			$courses["all"] = array(
				"ID"			=> "all",
				"post_title" 	=> __("All Content", "grassblade"),
				"class" 		=> "hide_on ",	//Added from filter: hide_on_report_progress_snapshot"
			);
			$all_courses = grassblade_lms::get_courses(array("post_status" => $post_status));
			foreach ($all_courses as $course_id => $course_name) {
				$courses[$course_id] = array("post_title" => $course_name, "group_ids" => array(), "class" => "course_option show_on show_on_group_id_all ");
			}
			$groups = gb_groups::get_all();
//			echo "<pre>";print_r($groups); exit;
		}
		else if(gb_groups::is_group_leader())
		{
			$groups = gb_groups::get_all(array("group_leader_id" => $current_user->ID));
		}

		$in_group = array();
		if(!empty($groups))
		foreach ($groups as $group) {
			$group_id = $group["ID"];
			$group_courses = grassblade_lms::get_courses(array("post_status" => $post_status, "group_id" => $group_id, "group_type" => $group['type']));
			foreach ($group_courses as $course_id => $course_name) {
				if(empty($courses[$course_id]))
				$courses[$course_id] = array("ID" => $course_id, "post_title" => $course_name, "group_ids" => array(), "class" => "");

				$courses[$course_id]["group_ids"][] = $group_id;

				if( empty($courses[$course_id]["class"]) )
				$courses[$course_id]["class"] = "course_option show_on show_on_group_id_all ";

				$in_group[$course_id] = $course_id;

				$group_type = str_replace("WP: ", "",$group['type']);
				$group_type = str_replace(" ",'',$group_type);
				$courses[$course_id]["class"] .= " show_on_group_id_" . $group_id . "_" . strtolower($group_type);
			}
		}

		foreach($courses as $course_id => $course) {
			if(empty($in_group[$course_id]))
			$courses[$course_id]["class"] .= " show_on_group_id_none ";
		}
		$courses = apply_filters("grassblade/reports/get_courses/return", $courses, array());
		return $courses;
	}
	static function add_missing_keys( $data ) {
		if(empty($data) || !is_array($data))
		return $data;

		$header_keys = array();
		foreach($data as $row_no => $row) {
			foreach($row as $key => $cell) {
				$header_keys[$key] = $key;
			}
		}
		foreach($data as $row_no => $row) {
			foreach($header_keys as $key) {
				if(!isset($data[$row_no][$key]))
				$data[$row_no][$key] = "";
			}
		}
		return $data;
	}
}

$grassblade_reports = new grassblade_reports();

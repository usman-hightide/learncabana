<?php
/*
Plugin Name: Manual Completions for LearnDash
Plugin URI: https://www.nextsoftwaresolutions.com/manual-completions-for-learndash/
Description: The Manual Bulk Completions for LearnDash addon allows you to manually check and mark the completion status of courses, lessons, topics, and quizzes in bulk.
Author: Next Software Solutions
Version: 2.0
Requires PHP: 7.4
Author URI: https://www.nextsoftwaresolutions.com
Text Domain: manual-completions-learndash
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Manual Completions LearnDash
 */
class manual_completions_learndash {
	public $version = "2.0";
	public $learndash_link = "https://www.nextsoftwaresolutions.com/r/learndash/manual_completions_learndash";

	function __construct() {
		if(!is_admin())
			return;

		$addon_plugins_file = dirname(__FILE__)."/addon_plugins/functions.php";
		if(!class_exists('grassblade_addons') && file_exists($addon_plugins_file))
		require_once($addon_plugins_file);

		global $manual_completions_learndash;
		$manual_completions_learndash = array("uploaded_data" => array(), "upload_error" => array(), "course_structure" => array(), "ajax_url" => admin_url("admin-ajax.php"));

		add_action( 'admin_menu', array($this,'menu'), 10);

		add_action( 'wp_ajax_manual_completions_learndash_course_selected', array($this, 'course_selected') );

		add_action( 'wp_ajax_manual_completions_learndash_mark_complete', array($this, 'mark_complete') );

		add_action( 'wp_ajax_manual_completions_learndash_check_completion', array($this, 'check_completion') );

		add_action( 'wp_ajax_manual_completions_learndash_get_enrolled_users', array($this, 'get_enrolled_users') );

		add_filter("learndash_submenu", array($this, "learndash_submenu"), 1, 1 );

		add_action('admin_init', array($this, "process_upload"));

	}

	function get_enrolled_users() {

		if( ! isset( $_POST['nonce'] ) || ! current_user_can( "manage_options" ) || ! check_ajax_referer( 'gbmc_learndash_nonce', 'nonce', false ) || empty( $_POST["course_id"] ) )
			wp_send_json( array("status" => 0, "message" => self::get_message("invalid_request") ) );

		$course_id 		  = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);
	    $course_group_ids = learndash_get_course_groups($course_id);
	    $user_ids = array();
	    foreach ($course_group_ids as $group_id) {
	        $user_ids = array_merge($user_ids, learndash_get_groups_user_ids($group_id, true));
	    }
	    $user_ids = array_unique( array_merge( $user_ids, learndash_get_course_users_access_from_meta( $course_id ) ) );

		wp_send_json( array("status" => 1, "data" => $user_ids, "course_id" => $course_id) );
	}

	function manual_completions_learndash_scripts() {
		global $manual_completions_learndash;

		wp_enqueue_script('manual_completions_learndash', plugins_url('/script.js', __FILE__), array('jquery'), $this->version );
		wp_enqueue_style("manual_completions_learndash", plugins_url("/style.css", __FILE__), array(), $this->version );
		wp_enqueue_script("select2js", plugins_url("/vendor/select2/js/select2.min.js", __FILE__), array(), $this->version );
		wp_enqueue_style("select2css", plugins_url("/vendor/select2/css/select2.min.css", __FILE__), array(), $this->version );

		if(empty($manual_completions_learndash) || !is_array($manual_completions_learndash))
			$manual_completions_learndash = [];

		$manual_completions_learndash['nonce'] = wp_create_nonce('gbmc_learndash_nonce');

		wp_localize_script( 'manual_completions_learndash', 'manual_completions_learndash',  $manual_completions_learndash);

		wp_add_inline_style("manual_completions_learndash", '#manual_completions_learndash_table .has_xapi {background: url('.esc_url( plugins_url("img/icon-gb.png", __FILE__) ).')}');
	}
	function upload_mimes ( $existing_mimes=array() ) {
	    // add your extension to the mimes array as below
	    $existing_mimes['csv'] = 'text/csv';
	    return $existing_mimes;
	}
	function process_upload() {

		if (empty($_GET["page"]) || $_GET["page"] != "grassblade-manual-completions-learndash")
			return;

		add_action("admin_print_styles", array($this, "manual_completions_learndash_scripts"));
		if( empty($_FILES) || empty( $_FILES["completions_file"]["name"] ))
			return;

		global $manual_completions_learndash;
		if(empty($manual_completions_learndash) || !is_array($manual_completions_learndash))
		$manual_completions_learndash = array();

		add_filter("upload_mimes", array($this, "upload_mimes"));
		if (!current_user_can("manage_options") || empty($_POST["manual_completions_learndash_csv_nonce"]) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST["manual_completions_learndash_csv_nonce"] ) ), 'manual_completions_learndash_csv')) {
			$manual_completions_learndash["upload_error"] = self::get_message("invalid_request");
			return;
		}
		if(!empty($_FILES["completions_file"]["name"])&& !empty($_FILES["completions_file"]["type"]))
		{
			$file_name = sanitize_text_field($_FILES["completions_file"]["name"]);
			$file_type = sanitize_text_field($_FILES["completions_file"]["type"]);
		}

		if(strtolower( pathinfo($file_name, PATHINFO_EXTENSION) ) != "csv" || $file_type != "text/csv" && $file_type != "application/vnd.ms-excel")
		{
			$manual_completions_learndash["upload_error"] = __('Upload Error: Invalid file format. Please upload a valid csv file', 'grassblade');
			return;
		}
		require_once(dirname(__FILE__)."/../grassblade/addons/parsecsv.lib.php");
		$csv = new parseCSV($_FILES['completions_file']['tmp_name']);
		if(empty($csv->data) || !is_array($csv->data) || empty($csv->data[0]))
		{
			$manual_completions_learndash["upload_error"] = __('Upload Error: Empty csv file', 'grassblade');
			return;
		}
		$csv_data = array();
		foreach ($csv->data as $k => $data) {
			$csv_data[$k] = array();
			foreach ($data as $j => $val) {
				$j = str_replace(" ", "_", strtolower(trim($j)));
				$csv_data[$k][$j] = $val;
			}
		}

		if(!isset($csv_data[0]["user_id"]) || !isset($csv_data[0]["course_id"])) {
			$manual_completions_learndash["upload_error"] = __('Upload Error: Invalid file format. Expected columns: user_id, course_id, lesson_id, topic_id, quiz_id ', 'grassblade');
			return;
		}

		$uploaded_data = $courses = $course_structure = $rejected_rows = array();
		$allowed_columns = array("user_id", "course_id", "lesson_id", "topic_id", "quiz_id");
		foreach ($csv_data as $k => $data) {
			$row = array();
			$empty_row = true;

			foreach ($allowed_columns as $col) {
				if(!empty($data[$col]))
					$empty_row = false;

				$row[$col] = (isset($data[$col]) && (is_numeric($data[$col]) || $data[$col] == "all"))? $data[$col]:"";
			}

			if($empty_row)
				continue;

			if(empty($row["user_id"])) {
				if(!empty($data["user_email"])) {
					$user = get_user_by("email", $data["user_email"]);
					if(!empty($user->ID))
						$row["user_id"] = $user->ID;
				}
			}
			if(empty($row["user_id"])) {
				if(!empty($data["user_login"])) {
					$user = get_user_by("login", $data["user_login"]);
					if(!empty($user->ID))
						$row["user_id"] = $user->ID;
				}
			}

			if(!empty($row["course_id"]) && !empty($row["user_id"])) {
				$course_id = $row["course_id"];
				if(!empty($courses[$course_id]))
					$course = $courses[$course_id];
				else {
					$course = get_post($course_id);
					if(!empty($course->ID) && $course->post_status == "publish" && $course->post_type == "sfwd-courses")
						$courses[$course_id] = $course;
					else
						$course = null;
				}

				if(empty($course->ID)) {
					$rejected_rows[] = $k + 2;
					continue;
				}

				if(!isset($course_structure[$course_id]))
					$course_structure[$course_id] = grassblade_learndash_get_course_structure($course);

				if(empty($row["lesson_id"]) && empty($row["topic_id"]) && empty($row["quiz_id"]))
				$row["lesson_id"] = "all";
				else if(!empty($row["lesson_id"]) && empty($row["topic_id"]) && empty($row["quiz_id"]))
					$row["topic_id"] = "all";

				$uploaded_data[] = $row;
			}
			else
				$rejected_rows[] = $k + 2;
		}

		$manual_completions_learndash["uploaded_data"] 		= $uploaded_data;
		$manual_completions_learndash["course_structure"] 	= $course_structure;

		if(!empty($rejected_rows))
		$manual_completions_learndash["upload_error"] = "Rejected Rows: ".implode(", ", $rejected_rows);
	}
	function menu() {
		global $submenu, $admin_page_hooks;
		$icon = plugin_dir_url(__FILE__)."img/icon-gb.png";

		if(empty( $admin_page_hooks[ "grassblade-lrs-settings" ] )) {
			add_menu_page("GrassBlade", "GrassBlade", "manage_options", "grassblade-lrs-settings", array($this, 'menu_page'), $icon, null);
			add_action("admin_print_styles", array($this, "manual_completions_learndash_scripts"));
		}

		add_submenu_page("grassblade-lrs-settings", __('Manual Completions LearnDash', "manual-completions-learndash"), __('Manual Completions LearnDash', "manual-completions-learndash"),'manage_options','grassblade-manual-completions-learndash', array($this, 'menu_page'));
	}

	function form() {
		global $wpdb;

		$courses = get_posts("post_type=sfwd-courses&posts_per_page=-1&post_status=publish");
		$users = $wpdb->get_results("SELECT ID, display_name, user_login, user_email FROM $wpdb->users ORDER BY display_name ASC");

		$this->manual_completions_learndash_scripts();
		include_once (dirname(__FILE__) . "/form.php");
	}
	function menu_page() {

	    if (!current_user_can('manage_options'))
	    {
	      wp_die( esc_html__('You do not have sufficient permissions to access this page.', "manual-completions-learndash") );
	    }

		$grassblade_plugin_file_path = WP_PLUGIN_DIR . '/grassblade/grassblade.php';
		if(!defined("GRASSBLADE_VERSION") && file_exists($grassblade_plugin_file_path)) {
			$grassblade_plugin_data = get_plugin_data($grassblade_plugin_file_path);
			define('GRASSBLADE_VERSION', @$grassblade_plugin_data['Version']);
		}

		$learndash_plugin_file_path = WP_PLUGIN_DIR . '/sfwd-lms/sfwd_lms.php';
		if(!defined("LEARNDASH_VERSION") && file_exists($learndash_plugin_file_path)) {
			$learndash_plugin_data = get_plugin_data($learndash_plugin_file_path);
			define('LEARNDASH_VERSION', @$learndash_plugin_data['Version']);
		}

		$dependency_active = true;

	    if (!file_exists($grassblade_plugin_file_path) ) {
	    	$xapi_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/no.png"/> '.(defined("GRASSBLADE_VERSION")? GRASSBLADE_VERSION:"").'</td>';
	    	$xapi_td .= '<td>
							<a class="buy-btn" href="https://www.nextsoftwaresolutions.com/grassblade-xapi-companion/">'.__("Buy Now", "grassblade").'</a>
						</td>';
	    	$dependency_active = false;
		}
	    else {
	    	$xapi_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/> '.(defined("GRASSBLADE_VERSION")? GRASSBLADE_VERSION:"").'</td>';
	    	if ( !is_plugin_active('grassblade/grassblade.php') ) {
				$xapi_td .= '<td>'.$this->activate_plugin("grassblade/grassblade.php").'</td>';
		    	$dependency_active = false;
			}else {
	    		$xapi_td .= '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/></td>';
	    	}
	    }

	    if (!file_exists( $learndash_plugin_file_path ) ) {
	    	$learndash_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/no.png"/> '.(defined("LEARNDASH_VERSION")? LEARNDASH_VERSION:"").'</td>';
	    	$learndash_td .= '<td colspan="2">
							<a class="buy-btn" href="'.$this->learndash_link.'">'.__("Buy Now", "grassblade").'</a>
						</td>';
		    	$dependency_active = false;
	    }
	    else {
	    	$learndash_td = '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/> '.(defined("LEARNDASH_VERSION")? LEARNDASH_VERSION:"").'</td>';
	    	if ( !is_plugin_active('sfwd-lms/sfwd_lms.php') ) {
				$learndash_td .= '<td>'.$this->activate_plugin("sfwd-lms/sfwd_lms.php").'</td>';
		    	$dependency_active = false;
			} else {
	    		$learndash_td .= '<td><img src="'.plugin_dir_url(__FILE__).'img/check.png"/></td>';
	    	}
	    }

		if($dependency_active)
			$this->form();
		else {
			add_filter( 'safe_style_css', function( $styles ) { $styles[] = 'display';	return $styles;	} );
			$allowed_html = array(
				'td' => array(
					'colspan' => array(),
					'class' => array(),
				),
				'img' => array(
					'src' => array(),
					'alt' => array(),
				),
				'a' => array(
					'href' => array(),
					'onclick' => array(),
					'class' => array(),
				),
				'span' => array(
					'class' => array(),
					'style' => array(),
					'id' => array(),
				),
			);
		?>
		<div id="manual_completions_learndash" class="manual_completions_learndash_requirements">
			<h2>
				<img style="margin-right: 10px;" src="<?php echo esc_url(plugin_dir_url(__FILE__)."img/icon_30x30.png"); ?>"/>
				Manual Completions for LearnDash
			</h2>
			<hr>
			<div>
				<p class="text">To use Manual Completions for LearnDash, you need to meet the following requirements.</p>
				<h2>Requirements:</h2>
				<table class="requirements-tbl">
					<thead>
						<tr>
							<th>SNo</th>
							<th>Requirements</th>
							<th>Installed</th>
							<th>Active</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>1. </td>
							<td><a class="links" href="https://www.nextsoftwaresolutions.com/grassblade-xapi-companion/">GrassBlade xAPI Companion</a></td>
							<?php
								echo wp_kses( $xapi_td, $allowed_html );
							?>
						</tr>
						<tr>
							<td>2. </td>
							<td><a class="links" href="<?php echo esc_url($this->learndash_link); ?>">LearnDash LMS</a></td>
							<?php echo wp_kses( $learndash_td, $allowed_html ); ?>
						</tr>
					</tbody>
				</table>
				<br>
			</div>
		</div>
	<?php }
	}
	/**
	 * Generate an activation URL for a plugin like the ones found in WordPress plugin administration screen.
	 *
	 * @param  string $plugin A plugin-folder/plugin-main-file.php path (e.g. "my-plugin/my-plugin.php")
	 *
	 * @return string         The plugin activation url
	 */
	function activate_plugin($plugin)
	{
		$activation_link = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin ), 'activate-plugin_' . $plugin );
		$link = '<a href="#" onClick="return grassblade_learndash_activate_plugin(\''.$activation_link.'\');">'.esc_html__("Activate", "manual-completions-learndash").'</a>';
		return $link;
	}
	function learndash_submenu($add_submenu) {
		$add_submenu["manual_completions_learndash"] = array(
			"name"  => __('Manual Completions', "manual-completions-learndash"),
			"cap"   => "manage_options",
			"link"  => 'admin.php?page=grassblade-manual-completions-learndash'
		);
		return $add_submenu;
	}
	function course_selected() {

		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( 'gbmc_learndash_nonce', 'nonce', false ) || ! current_user_can("manage_options") || empty($_POST["course_id"]))
			wp_send_json(array("status" => 0));

		$course_id = isset( $_POST['course_id'] ) ? absint( wp_unslash( $_POST['course_id'] ) ) : 0;
		$course = get_post($course_id);

		if(empty($course->ID) || $course->post_status != "publish")
			wp_send_json(array("status" => 0));


		wp_send_json(array("status" => 1, "data" => grassblade_learndash_get_course_structure($course) ));

	}
	function check_completion($return = false) {

		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( 'gbmc_learndash_nonce', 'nonce', false ) || !current_user_can("manage_options") )
			wp_send_json(array("status" => 0, "message" => self::get_message("invalid_request")));

		if( empty($_POST["data"]) || (!is_array($_POST["data"]) && !is_object($_POST["data"])) )
			wp_send_json(array("status" => 0, "message" => self::get_message("invalid_data")));

		$completions = map_deep( wp_unslash( $_POST['data'] ), 'sanitize_text_field' );
		foreach ($completions as $k => $completion) {
			$course_id 	= $completion["course_id"] = intVal($completion["course_id"]);
			$lesson_id 	= $completion["lesson_id"] = (!empty($completion["lesson_id"]) && $completion["lesson_id"] != "all")? intVal($completion["lesson_id"]):$completion["lesson_id"];
			$topic_id 	= $completion["topic_id"] = (!empty($completion["topic_id"]) && $completion["topic_id"] != "all")? intVal($completion["topic_id"]):$completion["topic_id"];
			$quiz_id 	= $completion["quiz_id"] = intVal($completion["quiz_id"]);
			$user_id 	= $completion["user_id"] = intVal($completion["user_id"]);

			if(empty($course_id)) {
				$completions[$k]["message"] = self::get_message("course_not_selected");
				$completions[$k]["status"] = 0;
			}
			else
			if(empty($user_id)) {
				$completions[$k]["message"] = self::get_message("user_not_selected");
				$completions[$k]["status"] = 0;
			}
			else if( !ld_course_check_user_access($course_id, $user_id) ) {
				$completions[$k]["message"] = self::get_message("not_enrolled");
				$completions[$k]["status"] = 0;
			}
			else
			{
				$completed = null;

				if(!empty($quiz_id))
					$completed = learndash_is_quiz_complete($user_id, $quiz_id, $course_id);
				else
				if(!empty($topic_id) && !empty($lesson_id) && $topic_id != "all")
					$completed = learndash_is_topic_complete($user_id, $topic_id);
				else
				if(!empty($lesson_id)) {
					if($lesson_id == "all")
					{
						$completed = learndash_course_status($course_id, $user_id, true);
					}
					else
					$completed = learndash_is_lesson_complete($user_id, $lesson_id, $course_id);
				}
				else
				{
					$completions[$k]["message"] = self::get_message("items_not_selected");
					$completions[$k]["status"] = 0;
				}

				if(isset($completed)) {
					global $learndash_course_statuses;
					$completions[$k]["message"] = is_bool($completed)? (empty($completed)? self::get_message("not_completed"):self::get_message("already_completed")):$learndash_course_statuses[$completed];
					$completions[$k]["status"] 	= 1;
					$completed = is_string($completed)? ($completed == "completed"):$completed;
					$completions[$k]["completed"] 	= intVal($completed);
				}
			}
		}
		if( $return )
			return $completions;

		wp_send_json( array("status" => 1, "data" => $completions) );
	}

	function mark_complete() {

		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( 'gbmc_learndash_nonce', 'nonce', false ) || !current_user_can("manage_options") ) {
			wp_send_json(array("status" => 0, "message" => self::get_message("invalid_request")));
		}

		if(empty($_POST["data"]) || (!is_array($_POST["data"]) && !is_object($_POST["data"])) )
			wp_send_json(array("status" => 0, "message" => self::get_message("invalid_data")));

		$completions = map_deep( wp_unslash( $_POST['data'] ), 'sanitize_text_field' );
		$check_completions = $this->check_completion(true);
		foreach ($completions as $k => $completion) {
			if(!empty($check_completions[$k]) && !empty($check_completions[$k]["completed"])) {
				$completions[$k]["status"] = 1;
				$completions[$k]["message"] = self::get_message("already_completed");
				$completions[$k]["info"] = $check_completions[$k];
				continue;
			}
			$course_id 	= $completion["course_id"] = intVal($completion["course_id"]);
			$lesson_id 	= $completion["lesson_id"] = (!empty($completion["lesson_id"]) && $completion["lesson_id"] != "all")? intVal($completion["lesson_id"]):$completion["lesson_id"];
			$topic_id 	= $completion["topic_id"] = (!empty($completion["topic_id"]) && $completion["topic_id"] != "all")? intVal($completion["topic_id"]):$completion["topic_id"];
			$quiz_id 	= $completion["quiz_id"] = intVal($completion["quiz_id"]);
			$user_id 	= $completion["user_id"] = intVal($completion["user_id"]);

			if(empty($course_id)) {
				$completions[$k]["message"] = self::get_message("course_not_selected");
				$completions[$k]["status"] = 0;
			}
			else
			if(empty($user_id)) {
				$completions[$k]["message"] = self::get_message("user_not_selected");
				$completions[$k]["status"] = 0;
			}
			else if( !ld_course_check_user_access($course_id, $user_id) ) {
				$completions[$k]["message"] = self::get_message("not_enrolled");
				$completions[$k]["status"] = 0;
			}
			else
			{
				if(!empty($_REQUEST["force_completion"])) {
					$completions[$k]["a"] = self::get_message("force_completion");

					remove_filter("learndash_process_mark_complete", "grassblade_learndash_process_mark_complete", 1, 3);
				}
				if(!empty($quiz_id)) {

					$can_complete = true;
					if ( empty($_REQUEST["force_completion"]) ) {
						$can_complete = grassblade_learndash_process_mark_complete($can_complete, get_post( $quiz_id ), get_user( $user_id ) );
						if ( ! $can_complete && empty($_REQUEST["force_completion"]) ){
							$completions[$k]["message"] = self::get_message("failed");
							$completions[$k]["status"] = 0;
						}
					}

					if ( $can_complete )
					$completions[$k] = $this->mark_quiz_complete($completion);
				}
				else
				if(!empty($topic_id) && !empty($lesson_id) && $topic_id != "all") {
					$completions[$k] = $this->mark_topic_complete($completion);
				}
				else
				if(!empty($lesson_id)) {

					if($lesson_id == "all" || !empty($topic_id) && $topic_id == "all")
						remove_filter("learndash_process_mark_complete", "grassblade_learndash_process_mark_complete", 1, 3);

					if($lesson_id == "all")
						$completions[$k] = $this->mark_course_complete($completion);
					else
						$completions[$k] = $this->mark_lesson_complete($completion);

					if(empty($_REQUEST["force_completion"]))
					if($lesson_id == "all" || !empty($topic_id) && $topic_id == "all")
					add_filter("learndash_process_mark_complete", "grassblade_learndash_process_mark_complete", 1, 3);
				}
				else
				{
					$completions[$k]["message"] = self::get_message("items_not_selected");
					$completions[$k]["status"] = 0;
				}
			}
		}

		wp_send_json( array("status" => 1, "data" => $completions) );
	}
	function mark_course_complete($completion) {
		$course_id 		= intval($completion["course_id"]);
		$user_id 		= intval($completion["user_id"]);
		$user 			= get_user_by("id", $user_id);
		$course 		= get_post($course_id);
		$course_structure = grassblade_learndash_get_course_structure($course);
		$completion["status_slug"] 	= learndash_course_status($course_id, $user_id, true);

		$status 		= array();
		if($completion["status_slug"] != "completed") {
			if(!empty($course_structure->lessons))
			foreach ($course_structure->lessons as $lesson_id => $lesson) {
				if(!empty($lesson->topics))
				foreach ($lesson->topics as $topic_id => $topic) {

					if(!empty($topic->quizzes))
					foreach ($topic->quizzes as $quiz_id => $quiz) {
						$status["quiz_".$quiz_id] = $this->mark_quiz_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id, "quiz_id" => $quiz_id));
					}
					$status["topic_".$topic_id] = $this->mark_topic_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id));

				}
				if(!empty($lesson->quizzes))
				foreach ($lesson->quizzes as $quiz_id => $quiz) {
					$status["quiz_".$quiz_id] = $this->mark_quiz_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "quiz_id" => $quiz_id));
				}

				$status["lesson_".$lesson_id] = $this->mark_lesson_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id));
			}
			if(!empty($course_structure->quizzes))
			foreach ($course_structure->quizzes as $quiz_id => $quiz) {
				$status["quiz_".$quiz_id] = $this->mark_quiz_complete(array("course_id" => $course_id, "user_id" => $user_id, "quiz_id" => $quiz_id));
			}
		}

		$completion["status_slug"] 	= learndash_course_status($course_id, $user_id, true);
		$completion["status"]  		= ($completion["status_slug"] == "completed")*1;
		$completion["message"]		= learndash_course_status($course_id, $user_id, false);
		$completion["info"]			= $status;
		return $completion;
	}
	function mark_quiz_complete($completion) {
		$course_id 		= intval($completion["course_id"]);
		$quiz_id 		= intval($completion["quiz_id"]);
		$user_id 		= intval($completion["user_id"]);
		$user 			= get_user_by("id", $user_id);

		// $course 		= get_post($course_id);
		// $course_title 	= $course->post_title;
		$score 			= 100;
		$percentage 	= 100;
		$percentage 	= round($percentage, 2);

		$timespent 		= 1;
		$time 			= time();
		$count 			= 1;

		$quiz = get_post_meta($quiz_id, '_sfwd-quiz', true);
		$passingpercentage = empty($quiz['sfwd-quiz_passingpercentage'])? 0:intVal($quiz['sfwd-quiz_passingpercentage']);
		$pass = ($percentage >= $passingpercentage)? 1:0;


		$quiz = get_post($quiz_id);
		$quizdata = array( "statement_id" => "", "course" => $course_id, "quiz" => $quiz_id, "quiz_title" => $quiz->post_title, "score" => $score, "total" => $score, "count" => $count, "pass" => $pass, "rank" => '-', "time" => $time, 'percentage' => $percentage, 'timespent' => $timespent, 'pro_quizid' => 0, 'm_edit_by' => get_current_user_id(), 'm_edit_time' => $time);

		$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
		$usermeta = maybe_unserialize( $usermeta );
		if ( !is_array( $usermeta ) ) $usermeta = array();

		$usermeta[] = $quizdata;

		$quizdata['quiz'] = $quiz;
		$quizdata['course'] = get_post($course_id);
		$quizdata['started'] = $quizdata['completed'] = $time; //strtotime(@$statement->stored);

		update_user_meta( $user_id, '_sfwd-quizzes', $usermeta );

		if ( true === $quizdata['pass'] ) {
			$quizdata_pass = true;
		} else {
			$quizdata_pass = false;
		}

		// Then we add the quiz entry to the activity database.
		learndash_update_user_activity(
			array(
				'course_id' => $course_id,
				'user_id' => $user_id,
				'post_id' => $quiz_id,
				'activity_type' => 'quiz',
				'activity_action' => 'insert',
				'activity_status' => $quizdata_pass,
				'activity_started' => $time,
				'activity_completed' => $time,
				'activity_meta' => $quizdata,
			)
		);

		do_action("learndash_quiz_completed", $quizdata, $user); //Hook for completed quiz

		if(!empty($completion["topic_id"]))
		self::mark_complete_if_children_complete($completion["lesson_id"], $completion["user_id"], $completion["course_id"]);
		else
		if(!empty($completion["lesson_id"]))
		self::mark_complete_if_children_complete($completion["lesson_id"], $completion["user_id"], $completion["course_id"]);

		$completion["message"] = self::get_message("completed");
		$completion["status"] = 1;
		$completion["usermeta"] = $usermeta;
		return $completion;
	}
	function mark_lesson_complete($completion) {
		if(!empty($completion["topic_id"]) && $completion["topic_id"] == "all") {
			$course_id 		= intval($completion["course_id"]);
			$user_id 		= intval($completion["user_id"]);
			$course 		= get_post($course_id);
			$lesson_id 		= intval($completion["lesson_id"]);
			$course_structure = grassblade_learndash_get_course_structure($course);
			if(!empty($course_structure->lessons) && !empty($course_structure->lessons->{$lesson_id})) {
				$status = array();
				$lesson = $course_structure->lessons->{$lesson_id};
				if(!empty($lesson->topics))
				foreach ($lesson->topics as $topic_id => $topic) {

					if(!empty($topic->quizzes))
					foreach ($topic->quizzes as $quiz_id => $quiz) {
						$status["quiz_".$quiz_id] = $this->mark_quiz_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id, "quiz_id" => $quiz_id));
					}
					$status["topic_".$topic_id] = $this->mark_topic_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id));

				}
				if(!empty($lesson->quizzes))
				foreach ($lesson->quizzes as $quiz_id => $quiz) {
					$status["quiz_".$quiz_id] = $this->mark_quiz_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "quiz_id" => $quiz_id));
				}

				$status["lesson_".$lesson_id] = $this->mark_lesson_complete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id));
			}

			$completion["status"] 	= learndash_is_lesson_complete($user_id, $lesson_id, $course_id);
			$completion["message"] 	= empty($completion["status"])? self::get_message("failed"):self::get_message("completed");
			$completion["info"]		= $status;
			return $completion;
		}

		$return = self::mark_complete_if_children_complete($completion["lesson_id"], $completion["user_id"], $completion["course_id"]);
		if(empty($return)) {
			$completion["status"] = 0;
			$completion["message"] = self::get_message("failed_child_incomplete");
		}
		else
		if(strpos($return, "Failed")) {
			$completion["status"] = 0;
			$completion["message"] = self::get_message("failed");
		}
		else {
			$completion["status"] = 1;
			$completion["message"] = self::get_message("completed");
		}
		return $completion;
	}
	function mark_topic_complete($completion) {
		$return = self::mark_complete_if_children_complete($completion["topic_id"], $completion["user_id"], $completion["course_id"]);
		if(empty($return) || strpos($return, "Failed")) {
			$completion["status"] = 0;
			$completion["message"] = strpos($return, "Failed") ? $return : self::get_message("failed_child_incomplete");
		}
		else {
			$completion["status"] = 1;
			$completion["message"] = self::get_message("completed");

			self::mark_complete_if_children_complete($completion["lesson_id"], $completion["user_id"], $completion["course_id"]);
		}
		return $completion;
	}
	function mark_complete_if_children_complete($post_id, $user_id, $course_id){
		if( version_compare(GRASSBLADE_VERSION, "6.2.7", ">=") )
			return grassblade_learndash_mark_lesson_complete_if_children_complete($post_id, $user_id, $course_id, true);
		else
			return grassblade_learndash_mark_lesson_complete_if_children_complete($post_id, $user_id, $course_id);
	}
	function get_message($key){
		$messages = array(
			"course_not_selected" 	  => __("Course not selected.", "manual-completions-learndash"),
			"user_not_selected"   	  => __("User not selected.", "manual-completions-learndash"),
			"items_not_selected"  	  => __("Lesson/Topic/Quiz not selected.", "manual-completions-learndash"),
			"not_enrolled" 		  	  => __("User not enrolled to course.", "manual-completions-learndash"),
			"already_completed"   	  => __("Already Completed!", "manual-completions-learndash"),
			"completed"			  	  => __("Successfully Marked Complete", "manual-completions-learndash"),
			"incompleted"		  	  => __("Successfully Marked Incomplete", "manual-completions-learndash"),
			"failed" 			  	  => __("Completion Failed", "manual-completions-learndash"),
			"invalid_data" 		  	  => __("Invalid Data", "manual-completions-learndash"),
			"invalid_request" 	  	  => __("Invalid Request", "manual-completions-learndash"),
			"not_completed" 	  	  => __("Not Completed", "manual-completions-learndash"),
			"force_completion" 	  	  => __("Force Completion", "manual-completions-learndash"),
			"failed_child_incomplete" => __("Completion Failed. Child/Dependency not complete.", "manual-completions-learndash"),
			"already_incomplete"      => __("Already Incomplete", "manual-completions-learndash")
		);
		return isset($messages[$key])? esc_html($messages[$key]):"";
	}
}

new manual_completions_learndash();
include_once (dirname(__FILE__) . "/mark_incomplete.php");
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once dirname(__FILE__)."/gutenberg/user-report/index.php";

class grassblade_user_report {

	function __construct() {
		add_shortcode("gb_user_report", array($this,"user_report"));
		add_action( 'init', array($this,'custom_script_gb_profile') );
		add_filter("grassblade_custom_labels_fields", array($this,"user_report_custom_labels_fields"),10,1);
		add_filter( 'grassblade_add_scripts_on_page', array($this, 'add_to_scripts') );

		if( is_admin() ) {
			add_filter ('user_row_actions', array($this, 'add_view_user_report'), 10, 2) ;
			add_action( 'admin_menu', array($this,'admin_user_report_menu'));
		}
	}
	function add_to_scripts($grassblade_add_scripts_on_page) {
		$grassblade_add_scripts_on_page[] = "grassblade_user_report";
		return $grassblade_add_scripts_on_page;
	}
	function admin_user_report_menu() {
		add_submenu_page("","","",'manage_options','grassblade_user_report', array($this, 'admin_user_report_page') );
	}
	function admin_user_report_page() {
		?>
		<div id="grassblade_admin_user_report" class="grassblade_admin_wrap" style="max-width: 100%;">
			<a href="<?php echo admin_url("users.php"); ?>"><?php _e("Return to Users List", "grassblade"); ?></a>
			<?php echo $this->user_report(array()); ?>
		</div>
		<?php
	}
	function add_view_user_report($actions, $user)
	{
		$href = admin_url("admin.php?page=grassblade_user_report&user_id=".$user->ID);
		$actions['add_view_author_page'] = "<a href='".$href."'><br>".__("User Report", "grassblade")."</a>" ;

		return $actions;
	}
	function user_report($attr){

		$shortcode_defaults = array(
	 		'user_id' 		=> null,
	 		'bg_color' 		=> null,
	 		'class'			=> '',
	 		'filter'		=> 'attempted'
		);

		$shortcode_atts = shortcode_atts ( $shortcode_defaults, $attr);

		extract($shortcode_atts);

		$current_user = wp_get_current_user();

		if( empty($current_user->ID) )
			return __('Please Login', 'grassblade');

		$requested_user_id = !empty($user_id)? $user_id:(!empty( $_REQUEST['user_id'] )? intVal($_REQUEST['user_id']):$current_user->ID);

		if( empty($requested_user_id) || $requested_user_id == $current_user->ID || grassblade_lms::is_admin() || gb_groups::is_group_leader_of_user($current_user->ID, $requested_user_id) ) {

			if(!empty( $requested_user_id )) {
				$user = get_user_by("id", $requested_user_id);

				if( empty($user->ID) )
					return  __('Invalid request.', 'grassblade');

				$user_id = $user->ID;
			}

		}

		if( empty($user_id) )
		{
			$user_id = $current_user->ID;
			$user = $current_user;
        }

		if(empty($user_id))
			return __('Please Login', 'grassblade');

		if (empty($user))
            $user = get_userdata($user_id);

        if(empty($bg_color))
			$bg_color = '#83BA39';

		$xapi_contents = $this->get_xapi_contents($user_id);
		$completed = 0;
		$in_progress = 0;
		$total_score = 0;
		$count = 0;
		foreach ($xapi_contents as $key => $value) {
			if ($value['content_status'] == 'Passed' || $value['content_status'] == 'Completed') {
				$completed++;
			}
			if ($value['total_attempts'] == 0 && !empty($value['is_inprogress'])) {
				$in_progress++;
			}
			if(is_numeric($value['best_score'])) {
				$total_score += intval($value['best_score']);
				$count++;
			}
		}
		//$course_label = grassblade_get_label('user_report_course', 'Course');
		$courses_label = grassblade_get_label('user_report_courses', 'Courses');
		$count 			= empty($count)? 1:$count;
		$avg_score 		= (count($xapi_contents) == 0)? 0:round($total_score/$count,2);

		$profile_data = array(  'user' => $user,
								'profile_pic' => get_avatar( $user->user_email, 150 ),
								'edit_profile' => get_edit_user_link($user->ID),
								'blog_url' => get_bloginfo('wpurl'),
								'xapi_contents' => $xapi_contents,
								'total_xapi_contents' => count($xapi_contents),
								'total_completed' => $completed,
								'total_in_progress' => $in_progress,
								'avg_score' => $avg_score,
								'filter'	=> $filter
							);

		$profile_data = apply_filters("gb_profile_data", $profile_data,$user_id);

		extract($profile_data);
		ob_start();

		include_once(dirname(__FILE__)."/templates/xapi_default.php");

		$user_report =  ob_get_clean();

		return "<div id='gb_user_report_".$user_id."' class='gb_user_report ".$class."'>".$user_report."</div>";
	}

	function get_xapi_contents($user_id){
		global $wpdb;
		$xapi_contents = array();
		$xapi_contents_data = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish' ORDER BY post_title ASC");
		$all_attempts_raw = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}grassblade_completions` WHERE user_id = '%d' ORDER BY id DESC", $user_id), ARRAY_A);
		$all_attempts = $best_attempts = array();
		if(!empty($all_attempts_raw))
		foreach ($all_attempts_raw as $attempt) {
			$content_id = $attempt["content_id"];
			if(!empty($attempt["status"]))
				$attempt["status"] = __($attempt["status"], "grassblade");
			$all_attempts[$content_id] = empty($all_attempts[$content_id])? array():$all_attempts[$content_id];
			$all_attempts[$content_id][] = $attempt;

			if(empty($best_attempts[$content_id]) || $attempt["percentage"] > $best_attempts[$content_id]["percentage"])
				$best_attempts[$content_id] = $attempt;
		}


		$ids = $xapi_contents_params = $xapi_contents_params = array();
		if(!empty($xapi_contents_data)) {
			foreach ($xapi_contents_data as $xapi_data) {
				$ids[] = $xapi_data->ID;
			}
			$xapi_contents_params_r = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'xapi_content' AND post_id IN (".implode(",", $ids).")");

			foreach( $xapi_contents_params_r as $content_params) {
				$xapi_contents_params[$content_params->post_id] = maybe_unserialize($content_params->meta_value);
			}

			foreach ($xapi_contents_data as $xapi_data) {
				$attempts = !empty($all_attempts[$xapi_data->ID])? $all_attempts[$xapi_data->ID]:array();
				$best_score = '-';
				$content_status = '-';
				$is_inprogress = false;
				$xapi_data->url = grassblade_xapi_content::get_permalink($xapi_data->ID, (isset($xapi_contents_params[$xapi_data->ID]) && !empty($xapi_contents_params[$xapi_data->ID]["show_here"])));

				if (!empty($attempts)) {
					$total_time_spent = 0;
					$best_score = $best_attempts[$xapi_data->ID]['percentage'];
					$content_status = $best_attempts[$xapi_data->ID]['status'];
					foreach ($attempts as $key => $attempt) {
						$total_time_spent += $attempt['timespent'];
						$attempts[$key]['timespent'] = grassblade_seconds_to_time($attempt['timespent']);
						$attempts[$key]['timestamp'] = gb_datetime( $attempt['timestamp'] );
					}
					$total_time_spent = grassblade_seconds_to_time($total_time_spent);
				} else {
					$in_progress = grassblade_xapi_content::is_inprogress($xapi_data->ID,$user_id);
					if (!empty($in_progress)) {
						$content_status = 'In Progress';
						$is_inprogress = true;
					}
					$total_time_spent = '-';
				}
				$xapi_contents[] = array('content' => $xapi_data,
										'best_score' => $best_score,
										'content_status' => __($content_status, "grassblade"),
										'total_time_spent' => $total_time_spent,
										'attempts' => $attempts,
										'total_attempts' => count($attempts),
										'is_inprogress' => $is_inprogress,
										'quiz_report_enable' => !empty($attempts) && gb_rich_quiz_report::is_enabled($xapi_data->ID)
									  );

			} // end of foreach
		}
		$xapi_contents = apply_filters("gb_user_report_contents", $xapi_contents,$user_id);
		return $xapi_contents;
	}

	function custom_script_gb_profile(){
		wp_enqueue_script( 'gb-user-profile', plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ) , GRASSBLADE_VERSION);

		$gb_profile = array('date' 			=> __("Date", "grassblade"),
							 'score'		=> __("Score", "grassblade"),
							 'status' 		=> __("Status", "grassblade"),
							 'timespent'	=> __("Timespent", "grassblade"),
							 'quiz_report' 	=> __("Quiz Report", "grassblade"),
							 'completed'	=>  __("Completed", "grassblade"),
							 'attempted'	=>  __("Attempted", "grassblade"),
							 'passed'		=>  __("Passed", "grassblade"),
							 'failed'		=>  __("Failed", "grassblade"),
							 'in_progress'	=>  __("In Progress", "grassblade"),
							 "datatables_language" => $this->datatables_localize(),
							 "plugin_dir_url" 	=> dirname(dirname(plugin_dir_url( __FILE__ )))
							);
		wp_localize_script( 'gb-user-profile', 'gb_profile',  $gb_profile);
	} //end of custom_script_gb_profile function

	function datatables_localize() {
		$language = array(
						"sEmptyTable" =>     __("No data available in table", "grassblade"),
						"sInfo" =>           __("Showing _START_ to _END_ of _TOTAL_ entries", "grassblade"),
						"sInfoEmpty" =>      __("Showing 0 to 0 of 0 entries", "grassblade"),
						"sInfoFiltered" =>   __("(filtered from _MAX_ total entries)", "grassblade"),
						"sInfoPostFix" =>    __("", "grassblade"),
						"sInfoThousands" =>  ",",
						"sLengthMenu" =>     __("Show _MENU_ entries", "grassblade"),
						"sLoadingRecords" => __("Loading...", "grassblade"),
						"sProcessing" =>     __("Processing...", "grassblade"),
						"sSearch" =>         __("Search:", "grassblade"),
						"sZeroRecords" =>    __("No matching records found", "grassblade"),
						"oPaginate" => array(
							"sFirst" =>    __("First", "grassblade"),
							"sLast" =>     __("Last", "grassblade"),
							"sNext" =>     __("Next", "grassblade"),
							"sPrevious" => __("Previous", "grassblade")
						),
						"oAria" => array(
							"sSortAscending" =>  __(": activate to sort column ascending", "grassblade"),
							"sSortDescending" =>__(": activate to sort column descending", "grassblade")
						)
					);
		return $language;
	}
	function user_report_custom_labels_fields($labels_fields){
		//$labels_fields[] = array( 'id' => 'label_user_report_course', 'label' => __( 'User Report Course', 'grassblade' ),  'placeholder' => __( "Course", "grassblade"), 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Change Label for Course in User Report.', 'grassblade'));
		$labels_fields[] = array( 'id' => 'label_user_report_courses', 'label' => __( 'User Report Courses', 'grassblade' ),  'placeholder' => __( "Courses", "grassblade"), 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Change Label for Courses in User Report.', 'grassblade'));

		return $labels_fields;
	}

} // end of class

$gb_ur = new grassblade_user_report();




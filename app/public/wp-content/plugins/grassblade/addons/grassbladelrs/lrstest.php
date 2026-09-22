<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *
 */
class LRSTEST
{
	public $grassblade_settings;
	public $xapi;
	public $activityId 	= "http://gblrs.com/lrstest";
	public $agent_email = "test@gblrs.com";
	public $agent_name 	= "test@gblrs.com";
	public $stateId 	= "gblrs_test";

	function __construct()
	{
		add_action( 'wp_ajax_lrstest', array($this, "run") );

		add_action( 'wp_ajax_nopriv_grassblade_completion_tracking', array($this, "completion_triggers"), 5 );
		add_action( 'wp_ajax_grassblade_completion_tracking', array($this, "completion_triggers"), 5 );

		add_action( 'wp_ajax_nopriv_grassblade_xapi_track', array($this, "completion_triggers"), 5 );
		add_action( 'wp_ajax_grassblade_xapi_track', array($this, "completion_triggers"), 5 );

		if(!empty($_GET["xapi_preview"]) && !empty($_SERVER["REQUEST_URI"]))
		add_action( 'template_redirect', array($this, "check_for_404") );

		add_action( 'edit_user_profile', array($this, "add_completion_test_button"), 10, 1 );
		add_action( 'show_user_profile', array($this, "add_completion_test_button"), 10, 1 );

		add_action( 'admin_enqueue_scripts', array($this, 'add_script_in_user_edit_page'), 10, 1);

		add_action( "wp_ajax_show_completion_test", array($this, "show_completion_test"), 10 , 1); //Show Completion Testing page.

		add_action( "wp_ajax_reset_learner_progress", array($this, "reset_learner_progress"), 10 , 1);
	}

	function reset_learner_progress() {
		$user_id 	= !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
		$content_id = !empty($_REQUEST['content_id']) ? intval($_REQUEST['content_id']) : 0;

		if(empty($user_id) || empty($content_id))
			$this->out(array("status" => false, "message" => "Invalid User or Content ID"));

		$status = grassblade_reset_learner_progress($content_id, $user_id);
		$this->out(array("status" => $status));
	}

	function run() {
		require_once(dirname(__FILE__)."/../nss_xapi_state.class.php");
		$this->grassblade_settings 	= $grassblade_settings	= grassblade_settings();
		if(empty($grassblade_settings) || empty($grassblade_settings["endpoint"]) || empty($grassblade_settings["user"]) || empty($grassblade_settings["password"]))
		{
			$this->out(array("error" => "not_configured"));
		}
		if( !grassblade_lms::is_admin() ) {
			$this->out(array("error" => "not_authorized"));
		}

		$this->xapi 				= new NSS_XAPI_STATE($grassblade_settings["endpoint"], $grassblade_settings["user"], $grassblade_settings["password"]);
		$this->xapi->set_actor( $this->agent_name, $this->agent_email );


		$content_id           = !empty($_REQUEST['content_id']) ? $_REQUEST['content_id'] : 0;
		$user_id    = !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;

		if( !empty($user_id) )
		$user = get_user_by('id', $user_id);

		if( !empty($content_id) ) {
			$xapi_content 		  = (new grassblade_xapi_content)->get_params($content_id);
			$activity_id          = $xapi_content["activity_id"];
			$original_activity_id = !empty($xapi_content["original_activity_id"]) ? $xapi_content["original_activity_id"] : "";
		}

		if( !empty($_REQUEST["check"]) )
		switch ( $_REQUEST["check"] ) {
			case 'state':
				$send_state = $this->send_state();

				if(empty($send_state) || !isset($send_state["status"]))
					$send_state = array("status" => false);
				else
				if( !current_user_can("manage_options") )
					$send_state = array("status" => $send_state["status"]);

				$this->out($send_state);
				break;
			case 'triggers':
				$this->out( array("triggers" => get_option("grassblade_lrstest_triggers", array()) ));
			case 'lms_check':
				$status = $this->lms_check();
				$this->out( $status );
				break;
			case 'error_logs':
				$grassblade_settings = grassblade_settings();
				if( empty($grassblade_settings["endpoint"]) )
					$this->out( array("status" => 0, 'message' => "No LRS Endpoint configured", "data" => []) );

				if( empty($user) )
					$this->out( array("status" => 0, 'message' => "Invalid User", "data" => []) );

				//$this->out( array("status" => 1,"data" =>  $xapi_content) );
				if( empty($xapi_content["activity_id"]) )
					$this->out( array("status" => 0, 'message' => "No Activity ID", "data" => []) );

				$agent = grassblade_getactor(false, "1.0", $user);
				$agent_id = grassblade_get_actor_id($agent);
				$activity_id          = $xapi_content["activity_id"];
				$original_activity_id = !empty($xapi_content["original_activity_id"]) ? $xapi_content["original_activity_id"] : "";
				$error_logs = $this->get_error_logs($activity_id, $agent_id);
//				$this->out( $error_logs );

				if( empty( $error_logs['status'] ))
					$this->out( $error_logs );
				else if ( empty($error_logs['data']) && !empty($original_activity_id) ) {
					$org_error_logs = $this->get_error_logs($original_activity_id, $agent_id);

					if( empty( $org_error_logs['status'] ))
						$this->out( $error_logs );
					else if( isset($org_error_logs['data']) ) {
						$error_logs['args'] = $error_logs['args'] + ['original_activity_id' => $original_activity_id];
						$error_logs['data_original_activity_id'] = $org_error_logs['data'];
						$this->out( $error_logs );
					}
				}
				$this->out( $error_logs );
				break;
				case 'content_added_on_and_completion':
					$return = $this->content_added_on_and_completion();
					$this->out( $return );
				break;
				case 'check_completion_tracking':
					$return = $this->is_completion_tracking_enabled();
					$this->out( $return );
				break;

				/* LRS Statements Related Requests */
				case 'current_activity_statement':
					$status = $this->run_statement_test($activity_id);
					$this->out( $status );
					break;

				case 'current_user_activity_statement':
					$user_id = isset($_REQUEST['user_id']) ? intVal( $_REQUEST['user_id'] ) : '';
					$status 	= $this->run_statement_test($activity_id, $user_id, 10);
					$this->out( $status );
					break;

				case 'original_activity_statement':
					if(empty($original_activity_id) || $activity_id == $original_activity_id)
						return $this->out( ['message' => 'Original Activiy ID is same as Current Activity ID', 'status'=> true] );
					else {
						$status = $this->run_statement_test($original_activity_id);
						$this->out( $status );
						break;
					}
					break;
				case 'original_user_activity_statement':

					if(empty($original_activity_id) || $activity_id == $original_activity_id)
						return $this->out( ['message' => 'Original Activiy ID is same as Current Activity ID', 'status'=> true] );
					else{
						$user_id = isset($_REQUEST['user_id']) ? intVal( $_REQUEST['user_id'] ) : '';
						$status     = $this->run_statement_test($original_activity_id, $user_id);
						$this->out( $status );
					}
					break;

				case 'multiple_content' :
					$status = $this->multiple_contents_with_same_activityids($activity_id);
					$this->out( $status );
					break;

				case 'revision_activity_id_test' :
					$status = $this->revision_activity_id_test($content_id, $activity_id);
					$this->out( $status );
					break;
				case 'registrations_test':
					$registrations = $this->registrations_test($activity_id, $user_id, $xapi_content, $content_id);
					$this->out( $registrations );
					break;
		}

		$this->out(array("error" => "invalid_method"));
		exit();
	}

	function registrations_test($activity_id, $user_id, $xapi_content, $content_id) {
		$registrations = get_user_meta($user_id, 'xapi_reg_' . $activity_id, true);
		$registrations = maybe_unserialize($registrations);

		if(!is_array($registrations))
			$registrations = ['latest' => '', 'registrations' => [] ];

		// if the xapi content has fixed registration then always put it first in the list
		if( !empty($xapi_content['registration']) && $xapi_content['registration'] != "auto" && empty($registrations['registrations'][$xapi_content['registration']]) ) {
			$fixed_registration = [ 'is_fixed' => 1 ];
			if( empty($registrations['registrations'] ) ){
				if(!is_array($registrations))
					$registrations = [];

				$registrations['latest'] = $xapi_content['registration'];
			}
			$registrations['registrations'] = [ $xapi_content['registration'] => $fixed_registration ] + $registrations['registrations'];
		}

		$registration_ids = array_keys( $registrations['registrations'] );
		// wp_is_uuid @since WP 4.9.0
		if( !empty( $_REQUEST['set_current'] ) && wp_is_uuid($_REQUEST['set_current']) ) {
			$registration_id = strip_tags( $_REQUEST['set_current'] );

			if( in_array($registration_id, $registration_ids) ) {
				$registrations['latest'] = $registration_id;
				$registrations['registrations'][$registration_id]['restored_by'] = get_current_user_id();
				$registrations['registrations'][$registration_id]['restored_on'] = time();

				update_user_meta($user_id, 'xapi_reg_' . $activity_id, $registrations);
			}
		}

		global $wpdb;
		$results = $wpdb->get_results($wpdb->prepare("SELECT user_id, content_id, status, registration FROM {$wpdb->prefix}grassblade_completions WHERE user_id = %d AND content_id = %d AND registration IN ('".implode("','", $registration_ids)."')", $user_id, $content_id));

		$final_data = [];
		$template_launch_url = grassblade(['id' => $content_id, "target" => 'url', 'actor_user_id' => $user_id]);
		foreach($registrations['registrations'] as $id => $registration) {
			$reset_by 	  = !empty( $registration['reset_by'] ) ? gb_name_format($registration['reset_by']) : '';
			$generated_on = !empty( $registration['generated'] ) ? wp_date('Y-m-d H:i:s', $registration['generated']) : '';

			if (str_contains($template_launch_url, 'registration='))
				$launch_url = preg_replace('/registration=[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}/', 'registration=' . $id, $template_launch_url);
			else
				$launch_url = $template_launch_url . "&registration=" . $id;

			$final_data[$id] = [
				'is_latest' 	=> $registrations['latest'] == $id ? 1 : 0,
				'content_id' 	=> '',
				'status' 		=> 'Not Found',
				'registration'  => $id,
				'generated' 	=> $generated_on,
				'reset_by'  	=> $reset_by,
				'is_fixed'  	=> !empty($registration['is_fixed']) ? 1 : 0,
				'launch_url'	=> $launch_url,
			];
			foreach($results as $result) {
				if($result->registration == $id) {
					$final_data[$id]['content_id'] = $result->content_id;
					$final_data[$id]['status'] = $result->status;
				}
			}
		}

		usort($final_data, function($a, $b){
			if ($a['generated'] == $b['generated']) {
				return 0;
			}
			return ($a['generated'] > $b['generated']) ? -1 : 1;
		});

		return ['status' => 1, 'message' => '', 'data' => $final_data]; //, 'results' => $results, 'q' => "SELECT user_id, content_id, status, registration FROM {$wpdb->prefix}grassblade_completions WHERE registration IN ('".implode("','", $registration_ids)."')"
	}

	function get_error_logs($activity_id, $agent_id) {
		$grassblade_settings = grassblade_settings();
		$url = str_ireplace("xAPI/", "", $grassblade_settings["endpoint"]) . "api/v1/error_log/get";
		$pass = grassblade_generate_secure_token(9, $grassblade_settings["password"], $agent_id);
		$auth = base64_encode($grassblade_settings["user"].":".$pass);
		$trigger_url = admin_url("admin-ajax.php?action=grassblade_completion_tracking");
		//$url = $url."?agent_id=".rawurlencode($agent_id)."&activity_id=".rawurlencode($activity_id)."&trigger_url=".rawurlencode($trigger_url)."&auth=".$auth;
		//echo $url;
		$args = [
			'agent_id' => $agent_id,
			'activity_id' => $activity_id,
			'trigger_url' => $trigger_url,
		];
		$response = wp_remote_request($url, [
							'method' => 'GET',
							'sslverify' => false,
							'headers' => [
								//'content-type' => 'application/json'
							],
							'body' => $args + ['auth' => $auth]
						]);
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return array("status" => 0, 'message' => $error_message, "args" => $args );
		} else {
			$response_data = json_decode( wp_remote_retrieve_body( $response ) );

			return array("status" => 1, 'message' => "", "data" => $response_data, "args" => $args );
		}
	}

	function content_added_on_and_completion() {

		$content_id = !empty($_REQUEST['content_id']) ? $_REQUEST['content_id'] : 0;
		$user_id    = !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;

		$data       = [];
		$posts      = grassblade_xapi_content::get_posts_with_content($content_id);
		$test_status = true;
		$message    = '<div class="sub-tests">';

		if(!empty($posts))
		foreach($posts as $post){
			$lms_data = apply_filters("gb_get_lms_step_status", array(), $post->ID, $user_id);

			if(empty($lms_data))
				continue;

			//$message .= "<p>";
			$step_info = !empty($lms_data['type'])? $lms_data['type']:ucwords($post->post_type);
			$step_info .= ", ";
			if(!empty($lms_data['course_id'])) {
				$is_enrolled	 = ($lms_data['enrolled']) ? "Enrolled" : "Not Enrolled";
				$step_info .= "Course: <a href='".$lms_data['course_edit_url']."' target='_blank'>".$lms_data['course_title']."</a>, $is_enrolled, ";
			}
			$step_info .= !empty($lms_data['step_status']) ? "Completed" : "Not Completed";

			$step_title = !empty( $lms_data['step_title'] )? $lms_data['step_title']:$post->post_title;
			$step_url   = !empty( $lms_data['step_edit_url'] )? $lms_data['step_edit_url']:get_edit_post_url( $post->ID );
			$sub_test_status = ( empty($lms_data["enrolled"]) || empty( $lms_data["step_status"] ) );
			$sub_test_status_class = $sub_test_status? 'passed':'failed';
			$message .= '<div class="sub-test '.$sub_test_status_class.'">';
			$message .= '<span class="dashicons dashicons-'.($sub_test_status? 'yes':'no').'"></span>';
			$message .= ' <a href="'.$step_url.'"  target="_blank">'.$step_title.'</a> ('.$step_info.')';
			$message .= '</div>';


			$test_status   	 = empty($sub_test_status) ? false : $test_status;
			//$message 		.= "Added On: <a href=".$lms_data['step_edit_url']." target='_blank'>".$lms_data['step_title']."</a> | Status: $is_completed </p>";
		}
		else
			$message .= '<div class="sub-test"><span class="dashicons dashicons-dash dashicons-yes"></span> Content not added on any LMS page.</div>';

		$message .= '</div>';
//		$data['message'] = !empty(strip_tags($message)) ? $message : "<br>Content not added on any LMS page.";
		$data['message'] = $message;
		$data['status']  = $test_status;

		return $data;
	}

	function add_script_in_user_edit_page($admin_page)
	{
		if ($admin_page !== "user-edit.php" && $admin_page !== "profile.php")
			return;

		wp_enqueue_script('user_edit_script', plugins_url('../../js/script.js', __FILE__), array(), '1.0.0', true);
	}
	function revision_activity_id_test($content_id, $CurrentActivityID){
		global $wpdb;
		$data = [];
		$revisions = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'xapi_content_versions' AND post_id = '%d'", $content_id));

		if(!empty($revisions));
		foreach($revisions as $revision){
			$revision    = maybe_unserialize($revision->meta_value);
			$activity_id = !empty($revision['activity_id']) ? $revision['activity_id'] : "";

			if($activity_id != $CurrentActivityID)
		 		$data[$activity_id] = $this->run_statement_test($activity_id);
		}

		return $data;
	}

	function multiple_contents_with_same_activityids($activityId){
		global $wpdb;
		$content_ids = $wpdb->get_col("SELECT * from $wpdb->postmeta WHERE meta_key LIKE 'xapi_activity_id' AND meta_value LIKE '$activityId' ", 1);

		$data = [];
		$data['message'] = '';
		$data['status'] = ( count($content_ids) <= 1 );


		$message = '<div class="sub-tests">';

		$currently_using_content_id = ( new grassblade_xapi_content() )->get_id_by_activity_id($activityId);

		if(!$data['status'])
		foreach($content_ids as $content_id) {

			if(get_post_status($content_id) != 'publish') continue;

			$icon 	  = ( $content_id == $currently_using_content_id ) ? 'dashicons-yes' : 'dashicons-no';
			$is_pass  =  ( $content_id == $currently_using_content_id ) ? 'passed' : 'failed';
			$title 	  = $content_id . ': ' . get_the_title($content_id) . ( $is_pass == 'passed' ? ' (Currently Using)' : '' );
			$message .= "<div class='sub-test $is_pass '><span class='dashicons $icon '></span> <a href=".get_edit_post_link($content_id)." target='_blank'>" . $title . "</a></div>";
		}
		$message .= '</div>';
		$data['message'] = $message;
		return $data;
	}


	function is_completion_tracking_enabled() {

		$content_id = !empty($_REQUEST["content_id"]) ? intval($_REQUEST["content_id"]) : 0;

		if(empty($content_id))
			return false;

		$xapi_content 	  = (new grassblade_xapi_content)->get_params($content_id);
		$xapi_content['edit_url'] = get_edit_post_link($content_id, "&");
		return $xapi_content;
	}

	function add_completion_test_button($profile_user){

		$user_id 	= $profile_user->ID;
		if(!grassblade_lms::is_admin())
			return;

		//$user_email = grassblade_user_email($user_id);
		$url 		= admin_url("admin-ajax.php?action=show_completion_test&user_id=$user_id");
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">GrassBlade:<br>Test Completion Tracking</th>
					<td><?php 	echo do_shortcode("[grassblade src='$url' target='lightbox' class='button' text='Start Testing' version='none' ]"); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	function show_completion_test() {
		if( !grassblade_lms::is_admin() )
		{
			echo "Unauthorized Access";
			exit;
		}
		$content_id = !empty($_REQUEST['content_id']) ? intVal( $_REQUEST['content_id'] ) : "";
		$user = null;
		$agent_id = $name = "";
		if( !empty($_REQUEST['user_id']) )
			$user = get_user_by('id', intVal( $_REQUEST['user_id'] ));

		if( empty($user->ID) ) {
			$user_search = !empty($_REQUEST['user_search']) ? strip_tags( $_REQUEST['user_search'] ) : "";
			$user = gb_user_search($user_search);
		}

		if(!empty($user->ID)) {
			$user_id = $user->ID;
			$user_email = $user->user_email;
			$agent = grassblade_getactor(false, "1.0", $user);
			$agent_id = grassblade_get_actor_id($agent);
			$name = gb_name_format($user);
			$related_users = $this->get_related_users($user_id);
		}
		$grassblade_settings = grassblade_settings();
		$config = array(
			"endpoint"  => $grassblade_settings["endpoint"],
			"auth"      => "Basic ".base64_encode( $grassblade_settings["user"].":".$grassblade_settings["password"] ),
			"actor"     => array('mbox' => "mailto:test@gblrs.com", 'name' => "LRS TEST", "objectType" =>  "Agent"),
			"timestamp" => time(),
			"activityId" => "http://gblrs.com/lrstest",
			'strictCallbacks' => true
		);

		include(dirname(__FILE__)."/completion_test_template.php");
		exit;
	}
	function get_related_users($user_id) {
		$grassblade_email = grassblade_user_email($user_id);
		global $wpdb;
		$grassblade_email_users = $wpdb->get_results($wpdb->prepare("SELECT u.ID, display_name, user_email FROM $wpdb->usermeta um, $wpdb->users u WHERE u.ID = um.user_id AND meta_key = 'grassblade_email'  AND meta_value = '%s'", $grassblade_email ));
		return [
			"grassblade_email" => $grassblade_email,
			"user" => get_user_by_grassblade_email($grassblade_email),
			"all" => $grassblade_email_users
		];
	}

	function run_statement_test($activity_id, $user_id = '', $limit = 1){

		if(empty($activity_id))
			return ['message' => 'Activity ID not found', 'status' => false];

		$data = array();
		$args = array(
			"activity"  => $activity_id,
			"verb"		=> "http://adlnet.gov/expapi/verbs/passed",
			"email"		=> "none",
			"limit"		=> $limit,
			"show"		=> ''
		);

		global $grassblade;
		$lrs_url = (!empty($grassblade['grassblade_settings']) && !empty($grassblade['grassblade_settings']['endpoint'])) ? $grassblade['grassblade_settings']['endpoint'] : "" ;
		$lrs_url = str_replace("xAPI/","", $lrs_url);

		if( !empty($user_id)) {
			$user = get_user_by("id", $user_id);
			$args['agent'] 	   = grassblade_getactor($guest = false, $version = "1.0", $user);
			$agent_id = grassblade_get_actor_id($args['agent']);
			$data['statement'] = get_statement( $args );// ['agent' => $args['agent'], "verb" => "http://adlnet.gov/expapi/verbs/passed" ] );// $args);

			if(empty($data['statement']))
			{
				$args["verb"] 	   = "http://adlnet.gov/expapi/verbs/completed";
				$data['statement'] = get_statement($args);
			}
			$found = (empty( $data['statement'] )? "not found":"found");
			$message = "Completion Statement [found] for User ($agent_id) and Activity ID (<a href='".$lrs_url."/Reports/?objectid=".rawurlencode( $activity_id )."&agent_id=".rawurlencode( $agent_id )."' target='_blank'>".htmlentities($activity_id)."</a>)";
			$data['message']   = str_replace("[found]", $found, $message);
		}else{
			$data['statement'] = get_statement($args);
			if(empty($data['statement']))
			{
				$args["verb"] 		= "http://adlnet.gov/expapi/verbs/completed";
				$data['statement']  = get_statement($args);
			}
			$found = (empty( $data['statement'] )? "not found":"found");
			$message = "Completion Statement [found] for Activity ID (<a href='".$lrs_url."/Reports/?objectid=".rawurlencode( $activity_id )."' target='_blank'>".htmlentities($activity_id)."</a>)";
			$data['message']   = str_replace("[found]", $found, $message);
		}
		$data['status']  = empty($data['statement']) ? false : true;
		$data['args'] = $args;
		return $data;
	}
	function check_for_404() {
		if(is_404() && !empty($_SERVER["REQUEST_URI"]) && current_user_can("manage_options")) {
			$request_uri = $_SERVER["REQUEST_URI"];
			$slug = grassblade_settings("url_slug");
			$slug = empty($slug)? "gb_xapi_content":$slug;
			if(strpos($request_uri, "/".$slug."/") !== false) {
				wp_redirect(self_admin_url("options-permalink.php"));
				exit();
			}
		}
	}
	static function lms_check() {
		$lms_installed = array(
			"learnpress" 	=> class_exists("LearnPress"),
			"lifterlms" 	=> class_exists('LifterLMS'),
			"wp-courseware" => function_exists("wpcw"),
			"learndash" 	=> defined("LEARNDASH_VERSION"),
			"tutorlms" 		=> defined("TUTOR_VERSION"),
			"masterstudy"	=> defined("STM_LMS_VERSION"),
			'sensei'		=> defined("SENSEI_LMS_VERSION")
		);
		$required_addons = array(
			"learnpress"	=> array("grassblade-xapi-learnpress/functions.php" => array("name" => "Experience API for LearnPress", "slug" => "grassblade-xapi-learnpress", "link" => "https://www.nextsoftwaresolutions.com/experience-api-for-learnpress/")),
			"lifterlms"	=> array("grassblade-xapi-lifterlms/functions.php" => array("name" => "Experience API for LifterLMS", "slug" => "grassblade-xapi-lifterlms", "link" => "https://www.nextsoftwaresolutions.com/experience-api-for-lifterlms/")),
			"wp-courseware"	=> array("grassblade-xapi-wp-courseware/functions.php" => array("name" => "Experience API for WP Courseware", "slug" => "grassblade-xapi-wp-courseware", "link" => "https://www.nextsoftwaresolutions.com/experience-api-for-wp-courseware/")),
			"tutorlms"	=> array("grassblade-xapi-tutorlms/functions.php" => array("name" => "Experience API for TutorLMS", "slug" => "grassblade-xapi-tutorlms", "link" => "https://www.nextsoftwaresolutions.com/experience-api-for-tutorlms/")),
			"masterstudy"	=> array("grassblade-xapi-masterstudy/functions.php" => array("name" => "Experience API for MasterStudy LMS", "slug" => "grassblade-xapi-masterstudy", "link" => "https://www.nextsoftwaresolutions.com/experience-api-for-masterstudy-lms/")),
			"sensei"	=> array("grassblade-xapi-sensei/functions.php" => array("name" => "Experience API for Sensei LMS", "slug" => "grassblade-xapi-sensei", "link" => "https://www.nextsoftwaresolutions.com/experience-api-for-sensei-lms/")),
			"learndash"	=> array()
		);
		$to_install = $to_activate = array();
	   	$installed_plugins = get_plugins();
	   	$message = "";

		foreach ($lms_installed as $lms => $installed) {
			if($installed) {
				foreach ($required_addons[$lms] as $plugin_file => $info) {
					if(empty($installed_plugins[$plugin_file])) {
						$info["install_link"] =  wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . urlencode($info["slug"]) ), 'install-plugin_' . $info["slug"] );
						$to_install[$plugin_file] = $info;
						$message = $message. ( !empty($message)? "<br>":"" ). " <a target='_blank' href='".$info["install_link"]."'>Click here</a> to install <b>".$info["name"]."</b>. ";
					}
					else
					if(!is_plugin_active($plugin_file)) {
						$info["activate_link"] = self_admin_url( wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin_file ), 'activate-plugin_' . $plugin_file ) );
						$to_activate[$plugin_file] = $info;
						$message = $message. ( !empty($message)? "<br>":"" ). " <a target='_blank' href='".$info["activate_link"]."'>Click here</a> to activate <b>".$info["name"]."</b>. ";
					}
				}
			}
		}
		return array("status" => ( empty($to_install) && empty($to_activate) )*1, "lms_installed" => $lms_installed, "to_install" => $to_install, "to_activate" => $to_activate, "message" => $message );
	}
	function send_state() {
		$time = time();
		$data = array(
			$this->grassblade_settings["user"] => array(
				"time" => $time,
			)
		);

		$data = json_encode( $data );
		$send_state = $this->xapi->SendState($this->activityId, $this->xapi->actor, $this->stateId, $data);
		grassblade_debug("LRSTEST: send_state = ". print_r($send_state, true));
		$get_state = $this->xapi->GetState($this->activityId, $this->xapi->actor, $this->stateId);
		$get_state = json_decode($get_state, true);
		grassblade_debug("LRSTEST: get_state = ". print_r($get_state, true));

		if( !empty($get_state) && !empty( $get_state[$this->grassblade_settings["user"]] ) && !empty($get_state[$this->grassblade_settings["user"]]["time"]) &&  $get_state[$this->grassblade_settings["user"]]["time"] == $time )
			$status = true;
		else {
			$status = false;
		}

		$return = array(
			"status" 	=> $status,
			"send" 		=> $send_state,
			"get" 		=> $get_state
		);
		return $return;
	}

	function completion_triggers() {
		if(empty($_REQUEST["grassblade_trigger"]) || !empty($_REQUEST["action"]) && $_REQUEST["action"] == "grassblade_completion_tracking" && empty($_REQUEST["grassblade_completion_tracking"]))
			return;

		if( !empty($_REQUEST["statement"]) && !empty($_REQUEST["objectid"]) && !empty($_REQUEST["agent_id"]) && !empty($_REQUEST["verb_id"]) && $_REQUEST["agent_id"] == $this->agent_email && $_REQUEST["objectid"] == $this->activityId )
		{
			$lrstest = get_option("grassblade_lrstest_triggers");

			if(empty($lrstest))
				$lrstest = array();

			$lrstest[$_REQUEST["verb_id"]] = array(
					"time" 		=> time(),
					"action"	=> strip_tags( $_REQUEST["action"] ),
					"grassblade_completion_tracking" => !empty($_REQUEST["grassblade_completion_tracking"])? 1:0,
					"grassblade_trigger" => !empty($_REQUEST["grassblade_completion_tracking"])? 1:0
				);
			update_option("grassblade_lrstest_triggers", $lrstest);
			grassblade_show_trigger_debug_messages("LRS Configuration Test verb_id: ". strip_tags( $_REQUEST["verb_id"] ) );
			exit();
		}
	}
	function out($data) {

		if( !is_array( $data ) )
			$data = array();

		$data["v"] = GRASSBLADE_VERSION;
		header('Content-Type: application/json');
		echo json_encode($data);
		exit();
	}
}

new LRSTEST();


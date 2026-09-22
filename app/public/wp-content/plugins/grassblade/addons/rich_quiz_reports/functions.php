<?php
if ( ! defined( 'ABSPATH' ) ) exit;

 class gb_rich_quiz_report{

 	function __construct() {
 		add_action( 'wp_ajax_gb_rich_quiz_report', array($this, 'gb_rich_quiz_report' ));
 		add_action( 'wp_ajax_gb_question_report', array($this, 'gb_question_report' ));
 		add_filter( 'grassblade_your_scores',array($this, 'show_rich_quiz_report'), 10, 4);
	}

	function gb_question_report(){
		$content_id = intval($_REQUEST["id"]);
		$objectid 	= grassblade_post_activityid($content_id);
		$group_id 	= !empty($_REQUEST["group_id"])? intval($_REQUEST["group_id"]):"";
		$group_type = !empty($_REQUEST["group_type"])? strip_tags($_REQUEST["group_type"]):"";
		$timestamp_start = !empty($_REQUEST["timestamp_start"])? strip_tags($_REQUEST["timestamp_start"]):"";
		$timestamp_end = !empty($_REQUEST["timestamp_end"])? strip_tags($_REQUEST["timestamp_end"]):"";

		$user_id = get_current_user_id();
		global $wpdb;

		/*
			Who has access:
			1. Admin
			2. Group Leader

			Who doesn't have access:
			1. Non-admin, non-group leader
			2. Group Leader with Group ID of group in which user is not group leader.
		*/
		if(empty($objectid) || !grassblade_lms::is_admin($user_id) && !gb_groups::is_group_leader($user_id) || !empty($group_id) && !grassblade_lms::is_admin($user_id) && !gb_groups::is_group_leader_of_group($user_id, $group_id, $group_type)) {
			echo "Unauthorized Access";
			exit;
		}

		echo $this->get_question_report($objectid, $group_id, $group_type, $timestamp_start, $timestamp_end);
		exit();
	}

	function get_question_report($objectid, $group_id, $group_type, $timestamp_start, $timestamp_end){
		$url = $this->get_question_report_url($objectid, $group_id, $group_type, $timestamp_start, $timestamp_end);

		if(empty($url))
			return __("GrassBlade LRS is required to run this report.", "grassblade");

		$html = grassblade_file_get_contents_curl($url, 120);
		return $html;
	}
	function get_question_report_url($objectid, $group_id, $group_type, $timestamp_start, $timestamp_end){

		$agent_id = "admin@gblrs.com";
		$grassblade_settings = grassblade_settings();
		$pass = grassblade_generate_secure_token(9, $grassblade_settings["password"], $agent_id);
		$auth = base64_encode($grassblade_settings["user"].":".$pass);
		$grassblade_settings = grassblade_settings();
		if( empty($grassblade_settings["endpoint"]) )
			return "";

		$endpoint = $grassblade_settings["endpoint"];
		$endpoint = str_replace("xAPI/", "api/v1/question_report/get", $endpoint);

		$params_string = "agent_id=".rawurlencode($agent_id)."&auth=".$auth."&objectid=".rawurlencode($objectid);//  http_build_query($params);
		if( !empty($group_id) )
		$params_string = $params_string."&group_remote=".intVal($group_id)."&group_type=".$group_type;

		if( !empty($timestamp_start) )
		$params_string = $params_string."&timestamp_start=".($timestamp_start);

		if( !empty($timestamp_end) )
		$params_string = $params_string."&timestamp_end=".rawurlencode($timestamp_end);

		$url = $endpoint."?".$params_string;
		return $url;

	}

	function gb_rich_quiz_report() {
		//echo 'test';
		$content_id = intval($_REQUEST["id"]);
		$registration = isset($_REQUEST["registration"])?$_REQUEST["registration"] : '';
		$user_id = isset($_REQUEST["user_id"])?$_REQUEST["user_id"] : '';
		$statement_id = isset($_REQUEST["statement_id"])?$_REQUEST["statement_id"] : '';

		$current_user = wp_get_current_user();
		global $wpdb;
		if(!empty($current_user->ID) && (empty($user_id) || $user_id == $current_user->ID || grassblade_lms::is_admin() ) ) {
			//User has access to report.
			if(empty($user_id))
				$user_id = $current_user->ID;
		}
		else
		{
			if(!empty($current_user->ID) ) {
				$is_group_leader = gb_groups::is_group_leader_of_user($current_user->ID, $user_id);
			}

			if(empty($is_group_leader)) {
				echo "Unauthorized Access";
				exit;
			}
        }

		if (empty($registration)  && !empty($statement_id)) {
			$statement = $wpdb->get_var($wpdb->prepare("SELECT statement FROM `{$wpdb->prefix}grassblade_completions` WHERE user_id = %d AND content_id = %d AND statement LIKE %s LIMIT 1", $user_id , $content_id, '%' . $wpdb->esc_like($statement_id) . '%'));
			if (!empty($statement)) {
				$statement = json_decode($statement);
				$registration = $statement->context->registration;
				$agent_id = NSS_XAPI::get_agent_id($statement);
			}
		}

		if (empty($agent_id) && !empty($registration)) {
			$statement = $wpdb->get_var($wpdb->prepare("SELECT statement FROM `{$wpdb->prefix}grassblade_completions` WHERE user_id = %d AND content_id = %d AND statement LIKE %s LIMIT 1", $user_id , $content_id, '%' . $wpdb->esc_like($registration) . '%'));
			if (!empty($statement)) {
				$agent_id = NSS_XAPI::get_agent_id($statement);
			}
		}
		if (empty($agent_id)) {
			$agent = grassblade_getactor(NULL, NULL, get_user_by("id", $user_id));
			$agent_id = grassblade_get_actor_id($agent);
		}

		$objectid = grassblade_post_activityid($content_id);

		if(!empty($agent_id) && !empty($objectid))
		echo $this->get_rich_quiz_report($objectid, $agent_id,$registration);

		exit();
	}
	function get_rich_quiz_report($objectid, $agent_id,$registration) {
		$url = $this->get_rich_quiz_report_url($objectid, $agent_id,$registration);
		if(empty($url))
			return __("GrassBlade LRS is required to run this report.", "grassblade");

		$html = grassblade_file_get_contents_curl($url, 120);
		return $html;
	}
	function get_rich_quiz_report_url($objectid, $agent_id,$registration) {

		$grassblade_settings = grassblade_settings();
		$pass = grassblade_generate_secure_token(9, $grassblade_settings["password"], $agent_id);
		$auth = base64_encode($grassblade_settings["user"].":".$pass);
		$grassblade_settings = grassblade_settings();

		if( empty($grassblade_settings["endpoint"]) )
		return "";

		$endpoint = $grassblade_settings["endpoint"];
		$endpoint = str_replace("xAPI/", "api/v1/quiz_report/get", $endpoint);

		$params_string = "related=1&agent_id=".rawurlencode($agent_id)."&auth=".$auth."&objectid=".rawurlencode($objectid);//  http_build_query($params);
		if (!empty($registration))
		$params_string = $params_string."&registration=".rawurlencode($registration);

		//print_r($_REQUEST);
		//print_r($params);
		//echo $params_string;
		$url = $endpoint."?".$params_string;
		return $url;

	}

	function show_rich_quiz_report($scores, $user_id, $content_id, $raw_scores) {

		$show_rich_quiz_report = $this->is_enabled($content_id);

		if(empty($show_rich_quiz_report) )
			return $scores;

		foreach ($raw_scores as $key => $raw_score) {
			$statement = json_decode($raw_score['statement']);
			$registration = empty($statement->context->registration)? "":$statement->context->registration;

			$scores[$key][__("Quiz Report", "grassblade")] = '<a onclick="return get_gb_quiz_report('.$content_id.','.$user_id.',\''.$registration.'\');"><img class="gb-icon-img" src="'.plugins_url().'/grassblade/img/stats.png" width="20px"></a>';
		}
		return $scores;
	}
    static function is_enabled($content_id) {
        $content_data = grassblade_xapi_content::get_params($content_id);
        return !empty($content_data["show_rich_quiz_report"]);
    }

} // end of class

global $grassblade;
$grassblade["gb_rich_quiz_report"] = new gb_rich_quiz_report();

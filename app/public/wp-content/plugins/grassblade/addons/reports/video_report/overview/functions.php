<?php

class video_report_overview extends video_report {
    public function __construct() {
        add_filter("grassblade/reports/available_reports", array($this, "report_name"), 10, 1);
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/get/video_report_overview",  array($this, "video_report_overview"), 10, 2);
		add_action("wp_ajax_gb_video_report", array($this, "get_video_report_overview_from_lrs"));
		add_filter("grassblade/reports/get_users/return", array($this, "gb_users"), 10, 2);
    }
	function report_name( $available_reports ) {
		$available_reports['video_report_overview'] = __("Video Overview Report", "grassblade");
		return $available_reports;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["video_report_overview"]	= array("" => "group", "group" => "course", "course"  => "user", "user" => "nss_report_show_contents_one", "content" => "nss_report_submit");
		return $report_filters_ux;
	}
    /* Show "All Users" option in Video Overview Report's User List */
	function gb_users($return, $request_data) {

		if( is_array($return) )
		foreach($return as $k => $u) {
			if(!empty($u["ID"]) && !empty($u["class"]) && $u["ID"] == "all")
			$return[$k]["class"] .= " show_on_report_video_report_overview ";
		}
		return $return;
	}
	function get_video_report_overview_from_lrs() {

		if ( !grassblade_lms::is_admin() && !gb_groups::is_group_leader() )
			die("Access Denied");

		$content_id = intval($_REQUEST["id"]);
		$objectid 	= str_replace("&amp;","&", grassblade_post_activityid($content_id));
		$group_id 	= !empty($_REQUEST["group_id"])? intval($_REQUEST["group_id"]):"";
		$group_type = !empty($_REQUEST["group_type"])? strip_tags($_REQUEST["group_type"]):"";
		$user_id    = !empty($_REQUEST["user_id"])? intval($_REQUEST["user_id"]):"";
		$current_user_id = get_current_user_id();

		/*
			Who has access:
			1. Admin
			2. Group Leader

			Who doesn't have access:
			1. Non-admin, non-group leader
			2. Group Leader with Group ID of group in which user is not group leader.
		*/

		if( empty($content_id) || empty($objectid) || !grassblade_lms::is_admin($current_user_id) && !gb_groups::is_group_leader($current_user_id) || !empty($group_id) && !grassblade_lms::is_admin($current_user_id) && !gb_groups::is_group_leader_of_group($current_user_id, $group_id, $group_type)) {
			echo "Unauthorized Access";
			exit;
		}

		if( !empty($group_id) ) {
			$query['group_remote'] = $group_id;
			$query['group_type']   = $group_type;
		}
		$query['agent_id'] = !empty($user_id)? [ grassblade_get_actor_id($user_id) ] : "";
		$query['objectid'] = $objectid;

		$lrs_error_msg = grassblade_lms::is_admin()? "GrassBlade Cloud LRS v2.14.0+ is required to run this report." : __("Server not reachable.", "grassblade");
		$lrs_error_msg = "<html><body style='background: white;'>".$lrs_error_msg."</body></html>";

		$report_url = $this->get_report_url('overview', $query);
		if( empty($report_url) )
			$r = $lrs_error_msg;
		else
			$r = grassblade_file_get_contents_curl($report_url);

		echo empty($r)? $lrs_error_msg : $r;
		wp_die();
	}
	function video_report_overview($return, $params) {

		if(empty($params) || empty($params['contents']))
			return $return;

		if ( !grassblade_lms::is_admin() && !gb_groups::is_group_leader() )
			return $return;

		$group_id 	= !empty($params["group_id"])? intVal($params["group_id"]):"";
		$group_type = !empty($params["group_type"])? strip_tags($params["group_type"]):"";
		$user_id	= !empty($params["user"]->ID)? intVal($params["user"]->ID):"";
		$report_url = admin_url('admin-ajax.php')."?action=gb_video_report&id=".intval($params['contents'][0])."&group_id=".$group_id."&group_type=".rawurlencode($group_type)."&user_id=".$user_id;
		$iframe 	= '<iframe class="gb_iframe_loader" src="'.$report_url.'" style="height:100vh; width:inherit;"></iframe>';

		return array("html" => $iframe);
	}
}

$video_report_overview = new video_report_overview();
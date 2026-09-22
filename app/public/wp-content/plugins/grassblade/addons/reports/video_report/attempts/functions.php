<?php

class video_report_attempts extends video_report {

    public function __construct() {
        add_filter("grassblade/reports/available_reports", array($this, "report_name"), 10, 1);
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/get/video_report_attempts",  array($this, "video_report_attempts"), 10, 2);
		add_filter("grassblade/reports/get_users/return", array($this, "gb_users"), 10, 2);
    }
	function report_name( $available_reports ) {
		$available_reports['video_report_attempts'] = __("Video Attempts Report", "grassblade");
		return $available_reports;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["video_report_attempts"] = array("" => "group", "group" => "course", "course"  => "user", "user" => ["date_range", "nss_report_show_contents"], "content" => "nss_report_submit");
		return $report_filters_ux;
	}
    /* Show All Users option for Achievements Report */
	function gb_users($return, $request_data) {

		if( is_array($return) )
		foreach($return as $k => $u) {
			if(!empty($u["ID"]) && !empty($u["class"]) && $u["ID"] == "all")
			$return[$k]["class"] .= " show_on_report_video_report_attempts ";
		}
		return $return;
	}
	function video_report_attempts($return, $params) {

		if(empty($params) || empty($params['contents']))
			return $return;

		if ( !grassblade_lms::is_admin() && !gb_groups::is_group_leader() )
			return $return;

		$group_id 		 = !empty($params["group_id"])? intVal($params["group_id"]):"";
		$group_type      = !empty($params["group_type"])? strip_tags($params["group_type"]):"";
		$user_id	     = !empty($params["user"]->ID)? intVal($params["user"]->ID):"";
        $contents        = !empty($params['contents']) ? $params['contents'] : [];

        $objectids = [];
        foreach($contents as $content_id) {
            $objectids[] = grassblade_post_activityid($content_id);
        }

        $query = [];
        $query['agent_id'] = !empty($user_id)? [ grassblade_get_actor_id($user_id) ] : "";
        $query['objectid'] = $objectids;
        if( !empty($group_id) ) {
            $query['group_remote'] = $group_id;
            $query['group_type'] = $group_type;
        }

		if( !empty($params["from"]) )
			$query['timestamp_start'] = wp_date("Y-m-d", $params["from"]);

		if( !empty($params["to"]) )
			$query['timestamp_end'] = wp_date("Y-m-d", $params["to"]);

        $report_url  = $this->get_report_url('attempts', $query);
		// return ["html" => htmlentities( $report_url ) ];
        $lrs_error_msg = grassblade_lms::is_admin()? "GrassBlade Cloud LRS v2.14.0+ is required to run this report." : __("Server not reachable.", "grassblade");

		if( empty($report_url) )
			return ['error' => $lrs_error_msg];

        $response = grassblade_file_get_contents_curl($report_url);
        $response = json_decode($response, true);

		if( empty($response) )
			return ['error' => $lrs_error_msg];

        if( empty($response["status"]) || empty($response["data"]) ) {
            if( !empty($response["message"]) )
                return ['error' => $response["message"]];

            return ['data' => []];
        }

        $data = $response["data"];

		//'sno', 'name', 'user_email', 'content', 'length', 'attempts', 'timespent', 'played_segments', 'percentage', 'objectid'
		foreach($data as $k => $row) {
			$data[$k]["length_h"] = gmdate("H:i:s", intVal( $row["length"] ));
			$hours = intVal( $row["timespent"] / 3600 );
			$hours = str_pad($hours, 2, "0", STR_PAD_LEFT);
			$data[$k]["timespent_h"] = $hours . ":" . gmdate("i:s",  intVal( $row["timespent"]));
			$data[$k]["started"] = wp_date("Y-m-d H:i:s", strtotime($row["started"]));
			$data[$k]["completed"] = empty($row["completed"])? "" : wp_date("Y-m-d H:i:s", strtotime($row["completed"]));
		}
		// return ["html" => "<pre>". print_r($data, true) . "</pre>"];
		return ['data' => $data];
	}
}

$video_report_attempts = new video_report_attempts();
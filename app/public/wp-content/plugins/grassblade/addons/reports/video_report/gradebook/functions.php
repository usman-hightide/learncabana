<?php

class video_report_gradebook extends video_report {

    public function __construct() {
        add_filter("grassblade/reports/available_reports", array($this, "report_name"), 10, 1);
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/get/video_report_gradebook",  array($this, "video_report_gradebook"), 10, 2);
		add_filter("grassblade/reports/get_users/return", array($this, "gb_users"), 10, 2);
    }
	function report_name( $available_reports ) {
		$available_reports['video_report_gradebook'] = __("Video Gradebook Report", "grassblade");
		return $available_reports;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["video_report_gradebook"] = array("" => "group", "group" => "course", "course"  => "user", "user" => ["date_range", "nss_report_show_contents"],  "content" => ["nss_report_submit", "video_report_gradebook_type"]);
		return $report_filters_ux;
	}
    /* Show All Users option for Achievements Report */
	function gb_users($return, $request_data) {

		if( is_array($return) )
		foreach($return as $k => $u) {
			if(!empty($u["ID"]) && !empty($u["class"]) && $u["ID"] == "all")
			$return[$k]["class"] .= " show_on_report_video_report_gradebook ";
		}
		return $return;
	}
	function video_report_gradebook($return, $params) {

        if(empty($params) || empty($params['contents']))
			return $return;

		if ( !grassblade_lms::is_admin() && !gb_groups::is_group_leader() )
			return $return;

		$group_id 	     = !empty($params["group_id"])? intVal($params["group_id"]):"";
		$group_type      = !empty($params["group_type"])? strip_tags($params["group_type"]):"";
		$user_id	     = !empty($params["user"]->ID)? intVal($params["user"]->ID):"";
        $contents        = !empty($params['contents']) ? $params['contents'] : [];

        $objectids = $objectid_content_ids = [];
        foreach($contents as $content_id) {
            $objectids[$content_id] = str_replace("&amp;", "&", grassblade_post_activityid($content_id));
            $objectid_content_ids[strtolower($objectids[$content_id])] = $content_id;
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

        $report_url = $this->get_report_url('attempts', $query);
        // return ["html" => $report_url];

        $lrs_error_msg = grassblade_lms::is_admin()? "GrassBlade Cloud LRS v2.14.0+ is required to run this report." : __("Server not reachable.", "grassblade");
        // $report_url = $this->get_report_url('video_report_gradebook', $query);
        // echo $report_url;
        if(empty($report_url))
            return ['error' => $lrs_error_msg];

        $response = grassblade_file_get_contents_curl($report_url);
        $response = json_decode($response, true);

        if( empty($response) ) {
            return ['error' => $lrs_error_msg];
        }
        if( empty($response["status"]) || empty($response["data"])) {
            if( !empty($response["message"]) )
                return ['error' => $response["message"]];

            return ['data' => []];
        }

        $data = $response["data"];

        // return ["html" => "<pre>". print_r($data, true)."</pre>"];

        $r = [];

        $sno = 1;
        foreach($data as $record ) {
            $objectid = strtolower($record["objectid"]);
            if( empty($objectid_content_ids[$objectid]) )
                continue;
            $content_id = $objectid_content_ids[$objectid];

            if( empty($r[$record["agent_id"]]) ) {
                $r[$record["agent_id"]] = [
                    "sno" => $sno++,
                    "name" => $record["name"],
                    "agent_id" => $record["agent_id"],
                    "count" => 1
                ];
            }

            $user_record = $r[$record["agent_id"]];

            $user_record[$content_id . "_content"] = $record["content"];

            if( empty($user_record[$content_id . "_played_segments"]) ) {
                $user_record[$content_id . "_played_segments"] = $record["played_segments"];
                $user_record[$content_id . "_attempts"] = 1;
                $user_record[$content_id . "_timespent" ] = $record["timespent"];
                $user_record[$content_id . "_timespent_h" ] = gmdate("H:i:s", intVal( $record["timespent"] ));
                $user_record[$content_id . "_percentage" ] = number_format( $record["percentage"], 2, ".", "");
                $user_record[$content_id . "_length" ] = $record["length"];
                $user_record[$content_id . "_objectid" ] = $record["objectid"];
            }
            else
            {
                $user_record[$content_id . "_played_segments"] .= '[,]' . $record["played_segments"];
                $user_record[$content_id . "_timespent" ] += $record["timespent"];
                $user_record[$content_id . "_timespent_h" ] =  gmdate("H:i:s", intVal( $user_record[$content_id . "_timespent" ] ));

                if( $record["percentage"] * 1 > $user_record[$content_id . "_percentage" ] * 1 )
                $user_record[$content_id . "_percentage" ] = number_format( $record["percentage"], 2, ".", "");

                $user_record[$content_id . "_length" ] = max($user_record[$content_id . "_length" ], $record["length"]);
                $user_record[$content_id . "_attempts"]++;
            }

            $r[$record["agent_id"]] = $user_record;
        }
        $record_keys = ["content", "played_segments", "attempts", "timespent", "timespent_h", "percentage", "length", "objectid"];
        foreach($r as $agent_id => $user_record) {
            foreach($objectid_content_ids as $content_id) {
                if( !isset($user_record[$content_id . "_content"]) ) {
                    foreach($record_keys as $key) {
                        $user_record[$content_id . "_" . $key] = '';
                    }
                }
            }
            $r[$agent_id] = $user_record;
        }

		return ['data' => array_values($r)];
	}
}

$video_report_gradebook = new video_report_gradebook();
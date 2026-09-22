<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports_completions_report {
	function __construct()
	{
		add_filter("grassblade/reports/available_reports", array($this, "report_name"));
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/scripts", array($this, "report_scripts"), 10, 1);
		add_filter("grassblade/reports/get/completions_report",  array($this, "get_report"), 10, 2);
		add_filter("grassblade/reports/get_users/return", array($this, "gb_users"), 10, 2);
		add_filter( "grassblade_localize_script_data", array($this, "add_script_localizations"), 10, 2);
	}
	function report_name( $available_reports ) {
		$available_reports['completions_report'] = __("Completions Report", "grassblade");

		return $available_reports;
	}
	function report_scripts( $scripts ) {
		$scripts["completions_report"] = array("file" => dirname(__FILE__)."/completions_report.js");
		return $scripts;
	}
	function add_script_localizations($gb_data, $content_post) {
		$gb_data["lang"]["S.No."] = __("S.No.", "grassblade");
		$gb_data["lang"]["User"] = __("User", "grassblade");
		$gb_data["lang"]["Email"] = __("Email", "grassblade");
		$gb_data["lang"]["Content"] = __("Content", "grassblade");
		$gb_data["lang"]["Date"] = __("Date", "grassblade");
		$gb_data["lang"]["Student Score %"] = __("Student Score %", "grassblade");
		$gb_data["lang"]["Group Avg"] = __("Group Avg", "grassblade");
		$gb_data["lang"]["Global Avg"] = __("Global Avg", "grassblade");
		$gb_data["lang"]["Time Spent"] = __("Time Spent", "grassblade");

		return $gb_data;
	}
	/* Show All Users option for Achievements Report */
	function gb_users($return, $request_data) {

		if( is_array($return) )
		foreach($return as $k => $u) {
			if(!empty($u["ID"]) && !empty($u["class"]) && $u["ID"] == "all")
			$return[$k]["class"] .= " show_on_report_completions_report ";
		}
		return $return;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["completions_report"]	= array(
														""			=> "group",
														"group"		=> "course",
														"course"	=> "user",
														"user"		=> ["date_range", "nss_report_show_contents"],
														"content" 	=> "nss_report_submit"
													);
		return $report_filters_ux;
	}
	function get_report($return, $params) {
		global $wpdb;
		extract($params);

		if(empty($contents)) {
			return $return;
		}

		$user_report_where_clause = !empty($user->ID)? "user_id = ".$user->ID. " AND ":"";

		$k = 1;
		$c = $course_id == "all"? $course_id:$course;
		$contents_list = grassblade_lms::get_course_content_list($c);

		$fields = "content_id, user_id, percentage, timespent, timestamp";// id, content_id, user_id, status, percentage, timespent, statement, timestamp, score

		$group_type = !empty($params["group_type"]) ? $params["group_type"] : "";
		$group_averages = $this->get_group_averages($group_id, $contents, $group_type);

		$sql = "SELECT {$fields} FROM {$wpdb->prefix}grassblade_completions WHERE $user_report_where_clause content_id IN (".implode(",", $contents).")";

		if(!empty($from) && !empty($to))
		$sql = $wpdb->prepare($sql." AND `timestamp` >= '%s' AND `timestamp` <= '%s'", date("Y-m-d H:i:s", $from),  date("Y-m-d H:i:s", $to));

		/*
		if(!empty($group_id))
		$sql = $wpdb->prepare($sql." AND user_id IN (SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'learndash_group_users_{$group_id}' AND meta_value = '%d' )", $group_id);
		*/

		$sql = gb_groups::add_user_query($sql, $group_id, 'user_id', $group_type);
		$results = $wpdb->get_results($sql." ORDER BY ID DESC");

		$ret = array();


		if(function_exists("grassblade_get_global_avg")) {
			$global_avg = array();
			foreach ($contents as $content_id) {
				$global_avg[$content_id] = grassblade_get_global_avg($content_id);
			}
		}
		else
		$global_avg = $this->get_group_averages("", $contents);

		//echo "<pre>";print_r($contents_list);print_r($results);
		foreach ($results as $key => $value) {
			if(!isset($contents_list[$value->content_id]))
			{
				$return = array("error" => "Invalid access.5");
				return json_encode($return);
			}

			$user = get_user_by("id", $value->user_id);
			if(!empty($user->ID)) {
				//$content = get_post($value->content_id);
				$group_avg = !isset($group_averages[$value->content_id])? "":$group_averages[$value->content_id];

				$ret_data = array(
					"sno" 			=> $k,
					"name"			=> gb_name_format($user),
					"user_id" 		=> $user->ID,
					"user_email" 	=> $user->user_email,
					"content_id" 	=> $value->content_id,
					"content"		=> $contents_list[$value->content_id],
					"date"			=> wp_date("Y-m-d", strtotime($value->timestamp)),// gb_date( strtotime($value->timestamp), "Y-m-d H:i:s"),
					"score"			=> $value->percentage,
					"group_avg"		=> $group_avg,
					"time_spent"	=> $value->timespent,
					"time_spent_h"	=> gb_seconds_to_time($value->timespent)
				);

				//if(function_exists('grassblade_get_global_avg'))
				$ret_data["global_avg"]	= empty($global_avg[$value->content_id])? "":$global_avg[$value->content_id];

				if(!empty($group_id))
				$ret_data["group_avg"] = $group_avg;

				$ret[] = $ret_data;
				$k++;
			}
		}
		$data  = $ret;
		$return = array("data" => $data, 'global_avg' => $global_avg, 'group_avg' => $group_averages);

		return $return;
	}
	function get_group_averages($group_id, $contents, $group_type='') {
		global $wpdb;

		$sql = "SELECT content_id, AVG(percentage) as group_avg  FROM {$wpdb->prefix}grassblade_completions WHERE  `content_id` IN (".implode(",", $contents).") GROUP BY content_id";

		$sql = gb_groups::add_user_query($sql, $group_id, 'user_id', $group_type);

		//$sql = $wpdb->prepare("SELECT content_id, AVG(percentage) as group_avg  FROM {$wpdb->prefix}grassblade_completions WHERE  `content_id` IN (".implode(",", $contents).") AND user_id IN (SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'learndash_group_users_{$group_id}' AND meta_value = '%d' ) GROUP BY content_id", $group_id );
		$results = $wpdb->get_results($sql);
		if(empty($results))
		return array();
		$group_averages = array();
		foreach($results as $key => $value) {
			$group_averages[$value->content_id] = number_format($value->group_avg, 2);
		}
		return $group_averages;
	}
}
$grassblade_reports_completions_report = new grassblade_reports_completions_report();


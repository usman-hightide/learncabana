<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports_gradebook {
	function __construct()
	{
		add_filter("grassblade/reports/available_reports", array($this, "report_name"));
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/scripts", array($this, "report_scripts"), 10, 1);
		add_filter("grassblade/reports/get/gradebook",  array($this, "get_report"), 10, 2);
	}
	function report_name( $available_reports ) {
		$available_reports['gradebook'] = __( 'Gradebook Report', 'grassblade' );

		return $available_reports;
	}
	function report_scripts( $scripts ) {
		$scripts["gradebook"] = array("file" => dirname(__FILE__)."/gradebook.js");
		return $scripts;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["gradebook"]	= array(
												""			=> "group",
												"group"		=> "course",
												"course"	=> ["date_range", "nss_report_show_contents"],
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

		$group_type = !empty($params["group_type"]) ? $params["group_type"] : "";

		/*
		$data = array(
			"sno" => 1,
				"name" 		=> "Pankaj Agrawal",
				"user_email"=> "panka@gmail.com",
				1449 => "10% ".print_r($contents, true),
				1795	=> "20% "
			);
		$return[] = $data;
		$data = array(
			"sno" => 2,
				"name" 		=> "Pankaj Agrawal 2",
				"user_email"=> "panka@gmail.com",
				1449 => "10% ",
				1795	=> "20% "
			);
		$return[] = $data;
		*/
		$k = 1;
		//echo $course_id;
		$c = $course_id == "all"? $course_id:$course;
		$contents_list = grassblade_lms::get_course_content_list($c);

		$sql = "SELECT * FROM {$wpdb->prefix}grassblade_completions WHERE content_id IN (".implode(",", $contents).")";

		if(!empty($from) && !empty($to))
		$sql = $wpdb->prepare($sql." AND `timestamp` >= '%s' AND `timestamp` <= '%s'", date("Y-m-d H:i:s", $from),  date("Y-m-d H:i:s", $to));

		/*
		if(!empty($group_id))
		$sql = $wpdb->prepare($sql." AND user_id IN (SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'learndash_group_users_{$group_id}' AND meta_value = '%d')", $group_id);
		*/

		$sql = gb_groups::add_user_query($sql, $group_id, 'user_id', $group_type);
		$results = $wpdb->get_results($sql." ORDER BY ID DESC");
		$ret = array();

		//echo "<pre>";print_r($contents_list);print_r($results);
		foreach ($results as $key => $value) {
			if(!isset($contents_list[$value->content_id]))
			{
				$return = array("error" => "Invalid access.4");
				return json_encode($return);
			}

			$user = get_user_by("id", $value->user_id);
			if(!empty($user->ID)) {
				if(!empty($ret[$user->ID])) {
					if(!isset($ret[$user->ID][$value->content_id]))
					$ret[$user->ID][$value->content_id] = $value->percentage;
				}
				else
				{
					$ret[$user->ID] = array(
						"sno" 	=> $k,
						"name"	=> gb_name_format($user),
						"user_id" => $user->ID,
						"user_email" => $user->user_email,
						$value->content_id => $value->percentage,
					);
					$k++;
					//echo $k.":".$value->content_id."<br>\t\n";
				}
			}
		}
		$data  = array();
		$global_avg = array();
		foreach ($contents as $content_id) {
			if(function_exists('grassblade_get_global_avg'))
			$global_avg[$content_id] = grassblade_get_global_avg($content_id);
		}

		foreach($ret as $r) {
			foreach ($contents as $content_id) {
				if(!isset($r[$content_id]))
					$r[$content_id] = "";
			}
			$data[] = $r;
		}
		$return = array("data" => $data, 'global_avg' => $global_avg);
		return $return;
	}
}
$grassblade_reports_gradebook = new grassblade_reports_gradebook();


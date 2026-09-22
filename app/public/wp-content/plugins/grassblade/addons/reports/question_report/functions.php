<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports_question {
	function __construct()
	{
		add_filter("grassblade/reports/available_reports", array($this, "report_name"));
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		//add_filter("grassblade/reports/scripts", array($this, "report_scripts"), 10, 1);
		add_filter("grassblade/reports/get/question_report",  array($this, "get_report"), 10, 2);
	}
	function report_name( $available_reports ) {
		$available_reports['question_report'] = __( 'Question Report', 'grassblade' );

		return $available_reports;
	}
	// function report_scripts( $scripts ) {
	// 	$scripts["question_report"] = array("file" => dirname(__FILE__)."/question.js");
	// 	return $scripts;
	// }
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["question_report"]	= array(
												""			=> "group",
												"group"		=> "course",
												"course"	=> ["date_range", "nss_report_show_contents_one"],
												"content" 	=> "nss_report_submit"
											);
		return $report_filters_ux;
	}

	function get_report($return, $params) {

		if(empty($params) || empty($params['contents']))
			return $return;

		//if(!grassblade_lms::is_admin())
		if ( !grassblade_lms::is_admin() && !gb_groups::is_group_leader() )
			return $return;

		$group_id = !empty($params["group_id"])? intVal($params["group_id"]):"";
		$group_type = !empty($params["group_type"])? strip_tags($params["group_type"]):"";

		$report_url = admin_url('admin-ajax.php')."?action=gb_question_report&id=".intval($params['contents'][0])."&group_id=".$group_id."&group_type=".rawurlencode($group_type);

		if( !empty($params["from"]) ) {
			$params["from"] = $params["from"] < 0 ? 0 : $params["from"];
			$report_url .= "&timestamp_start=".wp_date("Y-m-d", $params["from"]);
		}
		if( !empty($params["to"]) && wp_date("Y-m-d", $params["to"]) != date("Y-m-d") )
			$report_url .= "&timestamp_end=".wp_date("Y-m-d", $params["to"]);

		$iframe = '<iframe class="gb_iframe_loader" src="'.$report_url.'" style="height:100vh; width:inherit;"></iframe>';

		return array("html" => $iframe);
	}
}
$grassblade_reports_question = new grassblade_reports_question();


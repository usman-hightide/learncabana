<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports_quiz {
	function __construct()
	{
		add_filter("grassblade/reports/available_reports", array($this, "report_name"));
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		//add_filter("grassblade/reports/scripts", array($this, "report_scripts"), 10, 1);
		add_filter("grassblade/reports/get/quiz_report",  array($this, "get_report"), 10, 2);
	}
	function report_name( $available_reports ) {
		$available_reports['quiz_report'] = __( 'Quiz Report', 'grassblade' );

		return $available_reports;
	}
	// function report_scripts( $scripts ) {
	// 	$scripts["quiz_report"] = array("file" => dirname(__FILE__)."/quiz.js");
	// 	return $scripts;
	// }
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["quiz_report"]	= array(
												""			=> "group",
												"group"		=> "course",
												"course"	=> "user",
												"user"		=> ["date_range", "nss_report_show_contents_one"],
												"content" 	=> "nss_report_submit"
											);
		return $report_filters_ux;
	}
	function get_report($return, $params) {

		if(empty($params) || empty($params['contents']) || empty($params['user']))
			return $return;

		if(!grassblade_lms::is_admin() && !gb_groups::is_group_leader())
				return $return;

		$report_url = admin_url('admin-ajax.php')."?action=gb_rich_quiz_report&id=".intval($params['contents'][0])."&user_id=".intval($params['user']->ID);
		$iframe = '<iframe class="gb_iframe_loader" src="'.$report_url.'" style="height:100vh; width:inherit;"></iframe>';
		return array("html" => $iframe);
	}
}
$grassblade_reports_quiz = new grassblade_reports_quiz();


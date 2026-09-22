<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports_user_profile {
	function __construct()
	{
		add_filter("grassblade/reports/available_reports", array($this, "report_name"));
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/get/user_profile_report",  array($this, "get_report"), 10, 2);
	}
	function report_name( $available_reports ) {
		$available_reports['user_profile_report'] = __( 'User Report', 'grassblade' );

		return $available_reports;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["user_profile_report"]	= array(
															""			=> "group",
															"group"		=> "user",
															"user"		=> "nss_report_submit"
														);
		return $report_filters_ux;
	}
	function get_report($return, $params) {
		extract($params);

		if(empty($user->ID)) {
			$return = array("error" => "Invalid selection.4");
			return json_encode($return);
		}

		$grassblade_user_report = new grassblade_user_report();
		$return = array("html" => $grassblade_user_report->user_report(array("user_id" => $user->ID)));

		return $return;
	}
}
$grassblade_reports_user_profile = new grassblade_reports_user_profile();
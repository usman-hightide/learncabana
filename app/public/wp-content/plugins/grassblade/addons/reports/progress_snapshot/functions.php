<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports_progress_snapshot {
	function __construct()
	{
		add_filter("grassblade/reports/available_reports", array($this, "report_name"));
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/scripts", array($this, "report_scripts"), 10, 1);
		add_filter("grassblade/reports/get/progress_snapshot",  array($this, "get_report"), 10, 2);
		add_filter("grassblade/reports/get/progress_snapshot_details",  array($this, "gb_progress_snapshot_details"), 10, 2);
		add_filter("grassblade/reports/get_courses/return", array($this, "get_courses"), 10, 2);
	}
	function report_name( $available_reports ) {
		$available_reports['progress_snapshot'] = __("Progress Snapshot Report", "grassblade");

		return $available_reports;
	}
	function report_scripts( $scripts ) {
		$scripts["progress_snapshot"] = array("file" => dirname(__FILE__)."/progress_snapshot.js");
		return $scripts;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["progress_snapshot"]	= array(
														""			=> "group",
														"group"		=> "course",
														"course"	=> "nss_report_submit"
													);
		return $report_filters_ux;
	}
	/* Hide All Courses option for Progress Snapshot Report */
	function get_courses($return, $p) {
		if( is_array($return) )
		foreach($return as $k => $r) {
			if(!empty($r["ID"]) && !empty($r["class"]) && $r["ID"] == "all")
			$return[$k]["class"] .= " hide_on_report_progress_snapshot ";
		}
		return $return;
	}
	function get_report($return, $params) {
		$return = apply_filters("grassblade/reports/progress_snapshot/data", array(), array("course_id" => $params["course_id"], "group_id" => $params["group_id"], 'group_type' => $params['group_type']));
		return $return;
	}
	function gb_progress_snapshot_details($return, $params) {

		if(empty($params['course_id']) || empty($params['user']) || empty($_POST['gb_lesson_id'])) {
			echo json_encode(array('error' => 'Invalid Request. PS1'));
			wp_die();
		}

		$report_data = apply_filters("grassblade/reports/progress_snapshot/details", array(), intval($_POST['gb_lesson_id']), intval($params['course_id']), $params['user']);
		echo json_encode(['data' => $report_data, 'error' => '']);
		wp_die();
	}
	static function get_xapi_content_attempts($post_id, $user_id) {

		$xapi_content_ids = grassblade_xapi_content::get_post_xapi_contents($post_id, true);
		$xapi_attempts = [];

		if(empty($xapi_content_ids)) {
			return [
				'attempts' => [],
				'status' => '',
				'status_msg' => '',
				'percentage' => 0,
				'attempts_count' => 0,
				'has_xapi' => 0,
			];
		}

		$xapi_content_id = array_pop($xapi_content_ids); // last xapi content id
		$attempts = grassblade_xapi_content::get_attempts($xapi_content_id, $user_id);

		$hightest_percentage = 0;
		// $last_completed_date = 0;
		$statuses = ['incomplete' => 1, 'failed' => 2, 'completed' => 3, 'passed' => 4 ];
		$overall_status = 'incomplete';

		foreach($attempts as $attempt) {
			// $last_completed_date = $last_completed_date > intval($attempt['timestamp']) ? $last_completed_date : intval($attempt['timestamp']);
			// $is_complete = in_array(strtolower($attempt['status']), array('passed', 'completed')) ? true : false;
			$hightest_percentage = $hightest_percentage > $attempt['percentage'] ? $hightest_percentage : $attempt['percentage'];
			$overall_status = isset($statuses[$attempt['status']]) && $statuses[$attempt['status']] > $statuses[$overall_status] ? $attempt['status'] : $overall_status;
			$xapi_attempts[] = array(
				'percentage' => $attempt['percentage'] . '%',
				'status' 	 => $attempt['status'],
				'status_msg' => __( ucwords($attempt['status'] ), 'grassblade' ),
				'timespent'  => gb_seconds_to_time($attempt['timespent']),
				'completed'  => gb_datetime(strtotime($attempt['timestamp'])),
			);
		}

		return [
			'attempts' 		 => $xapi_attempts,
			'status' 	 => $overall_status,
			'status_msg' => __( ucwords($overall_status), 'grassblade' ),
			'percentage' 	 => $hightest_percentage . '%',
			'attempts_count' => count($attempts),
			'has_xapi' 		 => 1,
		];
	}
}
$grassblade_reports_progress_snapshot = new grassblade_reports_progress_snapshot();


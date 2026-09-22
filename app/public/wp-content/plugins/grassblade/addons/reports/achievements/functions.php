<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_reports_achievements {
	function __construct()
	{
		add_filter("grassblade/reports/available_reports", array($this, "report_name"));
		add_filter("grassblade/reports/filters/ux", array($this, "reports_field_ux"), 10, 1);
		add_filter("grassblade/reports/filters/fields", array($this, "reports_field"), 10, 1);
		add_filter("grassblade/reports/scripts", array($this, "report_scripts"), 10, 1);
		add_filter("grassblade/reports/js/gb_reports", array($this, "gb_reports_data"), 10, 1);
		add_filter("grassblade/reports/get_users/return", array($this, "gb_users"), 10, 2);
		add_filter("grassblade/reports/get/achievement_report",  array($this, "get_report"), 10, 2);
	}
	function report_name( $available_reports ) {
		$show_achievement_report = apply_filters("grassblade/reports/show_achievement_report",  current_user_can("manage_options"), $available_reports);

		if( $show_achievement_report )
		$available_reports['achievement_report'] = __( 'Achievements Report', 'grassblade' );

		return $available_reports;
	}
	function report_scripts( $scripts ) {
		$scripts["achievement_report"] = array("file" => dirname(__FILE__)."/achievement.js");
		return $scripts;
	}
	function gb_reports_data( $GB_REPORTS ) {
		if(!empty($_REQUEST["achievement_id"]) && is_array($GB_REPORTS["defaults"]) ) {
			$GB_REPORTS["defaults"]["achievement_id"] = sanitize_text_field($_REQUEST["achievement_id"]);
		}
		return $GB_REPORTS;
	}
	/* Show All Users option for Achievements Report */
	function gb_users($return, $request_data) {

		if( is_array($return) )
		foreach($return as $k => $u) {
			if(!empty($u["ID"]) && !empty($u["class"]) && $u["ID"] == "all")
			$return[$k]["class"] .= " show_on_report_achievement_report ";
		}
		return $return;
	}
	function reports_field_ux( $report_filters_ux ) {
		$report_filters_ux["achievement_report"]	= array(
			""			=> "group",
			"group"		=> "achievement",
			"achievement" 	=> "user",
			"user"	=> ['date_range', 'nss_report_submit']
		);
		return $report_filters_ux;
	}
	function reports_field( $report_filter_fields ) {
		$field = array(
			"achievement" => array("file" => dirname(__FILE__)."/achievement_field.php")
		);
		$report_filter_fields = gb_array_push($report_filter_fields, $field, "group");

		return $report_filter_fields;
	}
	static function get_field() {
		return '<select id="nss_report_achievement" name="achievement">'. grassblade_reports_achievements::get_options_html(). '</select>';
	}
	static function get_options_html() {
		$achievement_options = grassblade_reports_achievements::get_options();
		$options = "";
		foreach($achievement_options as $achivement_id => $achivement_title) {
			$options .= "<option value='".$achivement_id."'>". $achivement_title."</option>";
		}
		return $options;
	}
	static function get_options() {
		$achievement_options = apply_filters("grassblade/reports/achievement_options", array());
		if(!empty($achievement_options))
		$achievement_options = array("all" => __("All Achievements", "grassblade")) + $achievement_options;

		$achievement_options = array("" => __("--- Select an Achievement ---", "grassblade")) + $achievement_options;
		return $achievement_options;
	}
	function get_report($return, $params) {
		$return = apply_filters("grassblade/reports/achievement_report/data", $return, $params);
		$return["data"] = empty($return["data"])? array():grassblade_reports::add_missing_keys($return["data"]);
		return $return;
	}
}
$grassblade_reports_achievements = new grassblade_reports_achievements();


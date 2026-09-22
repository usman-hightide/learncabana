<?php

if ( ! defined( 'ABSPATH' ) ) wp_die("No script kiddies please.");

class video_report {
	function __construct()
	{
		include_once( dirname(__FILE__)."/overview/functions.php" );
		include_once( dirname(__FILE__)."/attempts/functions.php" );
		include_once( dirname(__FILE__)."/gradebook/functions.php" );

		add_filter("grassblade/reports/scripts", array($this, "report_scripts"), 10, 1);
	}

	function report_scripts( $scripts ) {
		$scripts["video_reports"] = array("file" => dirname(__FILE__)."/video_reports.js");
		return $scripts;
	}

	function get_report_url($report_name, $query = []) {

		$endpoint = grassblade_settings("endpoint");
		if(empty($endpoint))
		return "";

		if(empty($query['agent_id']))
			$query['agent_id'] = [];

		if(is_array($query['agent_id']))
			$query['agent_id'][] = "admin@gblrs.com";

		// replace &amp; with & in objectids because WordPress stores & as &amp; in xapi_activity_id
		if( !empty($query["objectid"]) && is_array($query["objectid"])) {
			$query["objectid"] = array_map(function($objectid) {
				return str_replace("&amp;", "&", $objectid);
			}, $query["objectid"]);
		}

		$pass 	  = grassblade_generate_secure_token(9, grassblade_settings("password"), 'admin@gblrs.com');
		$auth 	  = base64_encode(grassblade_settings("user").":".$pass);
		$endpoint = str_replace("xAPI/", "api/v1/video_report/".$report_name, $endpoint);
		$query['auth'] = $auth;
		$endpoint = $endpoint . "?" . http_build_query($query);
		return $endpoint;
	}
}
$video_report = new video_report();


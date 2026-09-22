<?php

class grassblade_cmi5 {
	function __construct() {
		add_filter( 'grassblade_content_versions', array($this, 'add_versions'),10, 1);
		add_filter( 'grassblade_shortcode_atts', array($this, 'shortcode_atts'), 3, 2);
		add_filter( 'grassblade_process_upload', array($this,'process_cmi5_upload'), 10 , 3);
		add_filter( 'wp_ajax_nopriv_cmi5_token', array($this, 'cmi5_token') );
		add_filter( 'wp_ajax_cmi5_token', array($this, 'cmi5_token') );
		add_filter( 'grassblade_shortcode_return', array($this, 'send_launch_data_to_lrs'), 10, 5);
	}
	function add_versions($versions) {
		$versions["cmi5"] = 'cmi5 (beta)';
		return $versions;
	}
	function shortcode_atts($shortcode_atts, $attr) {

		if(!empty($shortcode_atts["id"]) && in_array($shortcode_atts["version"], array("cmi5"))) {
			//$content_id = $shortcode_atts["id"];
			$shortcode_atts["actor_type"] = "account";
			$shortcode_atts["src"] .= strpos($shortcode_atts["src"], "?")? "&":"?";
			$grassblade_settings = grassblade_settings();
			if($shortcode_atts["guest"] === false)
			$grassblade_tincan_track_guest = $grassblade_settings["track_guest"];
			else
			$grassblade_tincan_track_guest = $shortcode_atts["guest"];

			$actor = grassblade_getactor($grassblade_tincan_track_guest, null, null, "account");

			if(empty($actor))
				return $shortcode_atts;
			$t = time();
			$query_array = array(
									"action" 	=> "cmi5_token",
									"t" 		=> $t,
									"id"		=> $shortcode_atts["id"],
									"activity_id" => $shortcode_atts["activity_id"],
									"agent"		=> rawurlencode(json_encode($actor))
								);
			if(!empty($_REQUEST["actor_mbox"]))
				$query_array["actor_mbox"] = $_REQUEST["actor_mbox"];
			if(!empty($_REQUEST["actor_name"]))
				$query_array["actor_name"] = $_REQUEST["actor_name"];


			$query_string = http_build_query($query_array);

			$k = md5($query_string);
			$fetch_url = admin_url("admin-ajax.php?".$query_string."&k=".$k);

			$shortcode_atts["src"] .= "fetch=".urlencode($fetch_url);
			$shortcode_atts["src"] .= "&activityId=".urlencode($shortcode_atts["activity_id"]);

			$user_id = get_current_user_id();
			if(!empty($user_id))
				$cmi5_tokens = get_user_meta($user_id, "cmi5_tokens", true);
			else
				$cmi5_tokens = get_option("cmi5_tokens");

			$cmi5_tokens = $this->clean_expired_tokens($cmi5_tokens, !empty($user_id));

			$cmi5_tokens[$k] = $t;
			if(!empty($user_id))
				$cmi5_tokens = update_user_meta($user_id, "cmi5_tokens", $cmi5_tokens);
			else
				$cmi5_tokens = update_option("cmi5_tokens", $cmi5_tokens);
		}
		return $shortcode_atts;
	}
	function clean_expired_tokens($cmi5_tokens, $loggedin) {
		if(!is_array($cmi5_tokens))
			return array();

		$expiry = $loggedin? 24*3600:2*3600;

		foreach ($cmi5_tokens as $token => $time) {
			if($time < time() - $expiry)
			unset($cmi5_tokens[$time]);
		}
		return $cmi5_tokens;
	}
	function send_launch_data_to_lrs($return, $params, $shortcode_atts, $attr, $completion_data) {

		if(empty($params["version"]) || $params["version"] != "cmi5")
			return $return;

		/*
			TODO 1: Send LaunchData only once per registration/session?
			TODO 2: Check if objectives can be added
		*/

		$stateId 		= "LMS.LaunchData";
		$agent 			= json_decode(rawurldecode($params["actor"]));

		$activityId 	= $params["activity_id"];
		$xapi_content 	= get_post_meta($shortcode_atts["id"], "xapi_content", true);

		$launchMethod 	= "AnyWindow";
		$masteryScore 	= !is_numeric($shortcode_atts["passing_percentage"])? "":$shortcode_atts["passing_percentage"]/100;
		$moveOn 		= "Completed";
		$launchMode 	= "Normal";

		if(!empty($xapi_content["cmi5_xml_settings"]) && !empty($xapi_content["cmi5_xml_settings"]["au"])) {
			if(!empty($xapi_content["cmi5_xml_settings"]["au"]["moveOn"]))
			$moveOn = $xapi_content["cmi5_xml_settings"]["au"]["moveOn"];

			if(!empty($xapi_content["cmi5_xml_settings"]["au"]["launchMethod"]))
			$moveOn = $xapi_content["cmi5_xml_settings"]["au"]["launchMethod"];

			if(empty($masteryScore) && !empty($xapi_content["cmi5_xml_settings"]["au"]["masteryScore"]))
				$masteryScore = $xapi_content["cmi5_xml_settings"]["au"]["masteryScore"];

			if(!empty($xapi_content["cmi5_xml_settings"]["au"]["launchParameters"]))
			$launchParameters = $xapi_content["cmi5_xml_settings"]["au"]["launchParameters"];

			if(!empty($xapi_content["cmi5_xml_settings"]["au"]["entitlementKey"]))
			$entitlementKey = $xapi_content["cmi5_xml_settings"]["au"]["entitlementKey"];
		}

		$data = array(
			"contextTemplate" => array(
				"contextActivities" => array(
					"grouping" => array( array(
						"objectType" => "Activity",
						"id" => $params["activity_id"],
					))
				),
				"extensions" => array("https://w3id.org/xapi/cmi5/context/extensions/sessionid" => grassblade_gen_uuid()),
			),
			"launchMethod" => $launchMethod,
			"launchMode" => $launchMode,
			"moveOn" => $moveOn,
			//"returnUrl" => "" //LMS may include the returnURL when the learner SHOULD be redirected to the returnURL on exiting the AU.
		);
		if(!empty($masteryScore))
		$data["masteryScore"] = $masteryScore;
		if(!empty($launchParameters))
		$data["launchParameters"] = $launchParameters;
		if(!empty($entitlementKey))
		$data["entitlementKey"] = $entitlementKey;
		$data = json_encode($data);

        $grassblade_settings = grassblade_settings();
        $endpoint 	= !empty($shortcode_atts["endpoint"])? $shortcode_atts["endpoint"]:$grassblade_settings["endpoint"];
        $user 		= !empty($shortcode_atts["user"])? $shortcode_atts["user"]:$grassblade_settings["user"];
        $password 	= !empty($shortcode_atts["pass"])? $shortcode_atts["pass"]:$grassblade_settings["password"];

		$xapi = new NSS_XAPI_STATE($endpoint, $user, $password);
		$r = $xapi->SendState($activityId, $agent, $stateId, $data, $params["registration"]);

		return $return;
	}
	/**
	 * Add cmi5 Versions to the content version List.
	 *
	 * @param array $params.
	 * @param obj $post.
	 * @param array $upload with required index $upload['content_path'] and $upload['content_url'].
	 *
	 * @return array $params with scorm versions.
	 */
	function process_cmi5_upload($params, $post , $upload) {

		if(!empty($params["response"]) && $params["response"] == "error")
			return $params;

		if ( empty($params['process_status']) && isset($upload['content_path']) && is_dir($upload["content_path"]) ) {

			$cmi5_xml_subdir = $this->get_cmi5_xml($upload['content_path']);

			if(empty($cmi5_xml_subdir))
			$cmi5_xml_file = $upload['content_path'].DIRECTORY_SEPARATOR."cmi5.xml";
			else
			$cmi5_xml_file = $upload['content_path'].DIRECTORY_SEPARATOR.$cmi5_xml_subdir.DIRECTORY_SEPARATOR."cmi5.xml";

			if(file_exists($cmi5_xml_file))
			{
				$cmi5_xml_settings = $this->get_manifest_settings($cmi5_xml_file);
				$upload['version'] = "cmi5";
				$upload['content_type'] = "cmi5";
				$upload["process_status"] = 1;
				$upload["cmi5_xml_settings"] = $cmi5_xml_settings;
				$upload['original_activity_id'] = $cmi5_xml_settings["original_activity_id"];

				if(empty($upload["activity_id"]))
					$upload["activity_id"] = $cmi5_xml_settings["original_activity_id"];

				if(!empty($cmi5_xml_settings["au"]["masteryScore"]) && $cmi5_xml_settings["au"]["masteryScore"] <= 1)
					$upload["passing_percentage"] = $cmi5_xml_settings["au"]["masteryScore"]*100;

				if(!empty($cmi5_xml_settings["au"]["launchMethod"]) && $cmi5_xml_settings["au"]["launchMethod"] == "OwnWindow")
					$upload["target"] = "_blank";

				if(!empty($cmi5_xml_settings["title"]))
					$upload["title"] = $cmi5_xml_settings["title"];

				$launch_file = $cmi5_xml_settings["au"]["url"];

				if(strpos($launch_file, "://") > 1)
					$upload["src"] = $launch_file;
				else {
					$upload["src"] = $upload['content_url']."/".$launch_file;
					$upload['launch_path'] = dirname($cmi5_xml_file).DIRECTORY_SEPARATOR.$launch_file;

					if(!gb_file_exists($upload["launch_path"]) || is_dir($upload["launch_path"]))
					return array("response" => 'error', "info" => 'Error: cmi5:<i>'.$upload['launch_path'].'</i>. Launch file not found in cmi5 package');
				}

				foreach($upload as $k=>$v) {
					if(is_string($v))
					$params[$k] = addslashes($v);
					else
					$params[$k] = $v;
				}
			}
//			if(!empty($cmi5_xml_settings))
//			$params["cmi5_xml_settings"] = $cmi5_xml_settings;
		}

		if(empty($params['process_status'])) {
			if(isset($params["src"]))
				unset($params["src"]);
			if(isset($params["launch_path"]))
				unset($params["launch_path"]);
			if(isset($params["cmi5_xml_settings"]))
				unset($params["cmi5_xml_settings"]);
		}

		return $params;
	}

	function get_cmi5_xml($dir) {
		$cmi5_xml_file = $dir.DIRECTORY_SEPARATOR."cmi5.xml";

		if(file_exists($cmi5_xml_file))
			return "";
		else
		{
			$dirlist = scandir($dir);
			foreach($dirlist as $d)
			{
				if($d != "." && $d != "..")
				{
					$cmi5_xml_file = $dir.DIRECTORY_SEPARATOR.$d.DIRECTORY_SEPARATOR."cmi5.xml";
					if(is_dir($dir) && is_dir($dir.DIRECTORY_SEPARATOR.$d))
					if(file_exists($cmi5_xml_file))
						return $d;
				}
			}
		}
		return 0;
	}
	function get_manifest_settings($file = "") {

		$dom = gb_get_xml_dom($file);

		$courseStructure = $dom->getElementsByTagName("courseStructure");

		$xml_summary = array();
		foreach ($courseStructure->item(0)->childNodes as $node) {
			$tagName = gb_get_value($node, "tagName", "");
			if($tagName == "course")
				$course = $node;
			if($tagName == "au")
				$au = $node;

			if(empty($xml_summary[$tagName]))
				$xml_summary[$tagName] = 0;
			$xml_summary[$tagName]++;
		}

		$xmlns = $courseStructure->item(0)->getAttribute("xmlns");
		$course_title = $course->getElementsByTagName("title")->item(0)->getElementsByTagName("langstring")->item(0)->textContent;
		$original_activity_id = $course->getAttribute("id");

		$return = array(
			"title" => $course_title,
			"original_activity_id" => $original_activity_id,
			"xmlns" => $xmlns,
			"xml_summary" => $xml_summary,
			"au" => array()
		);

		if(!empty($au)) {
			//AU
			$moveOn = $au->getAttribute("moveOn");
			$masteryScore = $au->getAttribute("masteryScore");
			$launchMethod = $au->getAttribute("launchMethod");
			$activityType = $au->getAttribute("activityType");
			$id = $au->getAttribute("id");
			$title = $au->getElementsByTagName("title")->item(0)->getElementsByTagName("langstring")->item(0)->textContent;
			$url = trim($au->getElementsByTagName("url")->item(0)->textContent);

			$launchParameters = !empty($au->getElementsByTagName("launchParameters")->item(0)->textContent)? trim($au->getElementsByTagName("launchParameters")->item(0)->textContent):"";
			$entitlementKey = !empty($au->getElementsByTagName("entitlementKey")->item(0)->textContent)? trim($au->getElementsByTagName("entitlementKey")->item(0)->textContent):"";

			$return["au"] = array(
				"title" => $title,
				"id" => $id,
				"url" => $url,
				"launchParameters" => $launchParameters,
				"entitlementKey" => $entitlementKey,
				"activityType" => $activityType,
				"moveOn" => $moveOn,
				"masteryScore" => $masteryScore,
				"launchMethod" => $launchMethod
			);
		}

		return $return;
	}
	function cmi5_token() {

		header('Content-Type: application/json');

		//Request needs to be POST Request
		if(empty($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != "POST") {
			echo json_encode(array("error-code" => "2", "Invalid request"));
			//http_response_code(401);
			exit();
		}

		//Token can be max 24 hrs old, or 1hrs in advance.
		if(empty($_GET['k']) || empty($_GET['t']) || $_GET['t'] > time() + 3600 || $_GET['t'] < time() - 86400 ) {
			echo json_encode(array("error-code" => "2", "Expired token"));
			exit();
		}

		//Token can be used only once
		$user_id = get_current_user_id();
		if(!empty($user_id))
			$cmi5_tokens = get_user_meta($user_id, "cmi5_tokens", true);
		else
			$cmi5_tokens = get_option("cmi5_tokens");

		if(empty($cmi5_tokens[$_GET["k"]])) {
			echo json_encode(array("error-code" => "2", "Invalid token"));
			exit();
		}

		unset($cmi5_tokens[$_GET["k"]]);
		$cmi5_tokens = $this->clean_expired_tokens($cmi5_tokens, !empty($user_id));

		if(!empty($user_id))
			$cmi5_tokens = update_user_meta($user_id, "cmi5_tokens", $cmi5_tokens);
		else
			$cmi5_tokens = update_option("cmi5_tokens", $cmi5_tokens);

		$xapi_content = new grassblade_xapi_content();
		$params = $xapi_content->get_shortcode(intVal($_GET['id']), true);
		if(empty($params["secure_tokens"]))
			$params["secure_tokens"] = 9;

		if(empty($_GET["activity_id"]) || $_GET["activity_id"] != $params["activity_id"])
		{
			echo json_encode(array("error-code" => "2", "Invalid activity"));
			exit();
		}

		$grassblade_settings = grassblade_settings();
		if(!isset($params["guest"]) || $params["guest"] === false)
		$grassblade_tincan_track_guest = $grassblade_settings["track_guest"];
		else
		$grassblade_tincan_track_guest = $params["guest"];

		$actor = grassblade_getactor($grassblade_tincan_track_guest, null, null, "account");

		if(empty($actor)) {
			echo json_encode(array("error-code" => "2", "error-text" => "Could not authenticate user"));
			exit();
		}

		$request_actor = json_decode( rawurldecode( $_GET["agent"] ), true);
		$actor_id = grassblade_get_actor_id($actor);
		$request_actor_id = grassblade_get_actor_id($request_actor);

		if(empty($actor_id) || $actor_id != $request_actor_id) {
			echo json_encode(array("error-code" => "2", "error-text" => "Detected link sharing"));
			exit();
		}

		$params["actor_type"] = "account";
		$data = grassblade_secure_tokens($params, array());

		if(empty($data["user"]) || empty($data["pass"])) {
			echo json_encode(array("error-code" => "2", "error-text" => "Could not authenticate request"));
			exit();
		}

		$return = array(
			"auth-token" => base64_encode($data["user"].":".$data["pass"]),
		);
		if(!empty($data["debug"]))
			$return["debug"] = $data["debug"];

		echo json_encode($return);
		exit();
	}
	static function launch($content_id = 0, $content_data = []) {
		if( empty($content_id) )
		$content_id = !is_numeric($_REQUEST['content_id'])? intVal(base64_decode($_REQUEST['content_id'])):intval($_REQUEST['content_id']);

		if(empty($content_data))
		$content_data = grassblade_xapi_content::get_params($content_id);
		$registration = !empty($_REQUEST['registration'])? $_REQUEST['registration']:'';
		if( $content_data["version"] == 'cmi5' ) {
			$url = grassblade(['id' => $content_id, 'registration' => $registration, 'target' => 'url']);
			?>
			<html>
			<head>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			</head>
			<body style="margin: 0 !important; padding: 0 !important;">
				<iframe frameborder="0" framespacingframespacing="0" border="0" width="100%" height="100%" src="<?php echo $url; ?>" name="course" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen="" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"></iframe>
			</body>
			</html>
			<?php
		}
		exit;
	}
}
new grassblade_cmi5();
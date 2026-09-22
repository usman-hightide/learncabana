<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_xapi {

	function __construct() {
		add_filter( 'grassblade_content_versions', array($this,'add_xapi_versions'),10, 1);
		add_filter( 'grassblade_process_upload', array($this,'process_xapi_upload'),20, 3);
		add_action( 'rest_api_init', array($this,'get_xml_api'));
		add_shortcode( 'grassblade_attempt_progress', array($this, 'grassblade_attempt_progress') );
	}

	/**
	 * Add xAPI Versions to the content version List.
	 *
	 * @param array $versions List of existing versions.
	 *
	 * @return array $versions with xapi versions.
	 */
	function add_xapi_versions($versions) {

		$xapi_versions = array(
					'1.0' => 'xAPI 1.0',
					'0.95' => 'xAPI 0.95',
					'0.9' => 'xAPI 0.9'
				);

		return array_merge($versions,$xapi_versions);
	}

	/**
	 * Add xAPI Versions to the content version List.
	 *
	 * @param array $params.
	 * @param obj $post.
	 * @param array $upload with required index $upload['content_path'] and $upload['content_url'].
	 *
	 * @return array $params with xapi versions.
	 */
	function process_xapi_upload($params, $post , $upload) {

		if(!empty($params["response"]) && $params["response"] == "error")
			return $params;

		if (empty($params['process_status']) && isset($upload['content_path']) && is_dir($upload["content_path"])) {

			$tincanxml_subdir = $this->get_tincanxml($upload['content_path']);

			if(empty($tincanxml_subdir))
			$tincanxml_file = $upload['content_path'].DIRECTORY_SEPARATOR."tincan.xml";
			else
			$tincanxml_file = $upload['content_path'].DIRECTORY_SEPARATOR.$tincanxml_subdir.DIRECTORY_SEPARATOR."tincan.xml";

			if(file_exists($tincanxml_file))
			{
				$tincanxmlstring = trim(file_get_contents($tincanxml_file));
				$tincanxml = simplexml_load_string($tincanxmlstring);
				if(!empty($tincanxml->activities->activity->launch))
				{
					$launch_file = (string)  $tincanxml->activities->activity->launch;
					if(empty($post->post_title)) {
						$content_name = (string)  $tincanxml->activities->activity->name;
						if(!empty($content_name))
						{
/*							$post->post_title = strip_tags( $content_name );

							global $wpdb;
							$wpdb->update($wpdb->posts, array("post_title" => $content_name), array("ID" => $post->ID));
*/
							$upload["title"] = $content_name;
						}
					}
					$upload['original_activity_id'] = isset($tincanxml->activities->activity['id'])? (string) $tincanxml->activities->activity['id']:"";
					if(empty($upload['activity_id']))
					$upload['activity_id'] = (string) $upload['original_activity_id'];
				}
				else
					return array("response" => 'error', "info" => "XML Error:  Launch file reference not found in tincan.xml");

				$upload['launch_path'] = dirname($tincanxml_file).DIRECTORY_SEPARATOR.$launch_file;

				if(empty($tincanxml_subdir))
				$upload['src'] =  $upload['content_url']."/".$launch_file;
				else
				$upload['src'] =  $upload['content_url']."/".$tincanxml_subdir."/".$launch_file;
				if(!gb_file_exists($upload['launch_path']))
					return array("response" => 'error', "info" => 'Error: <i>'.$upload['launch_path'].'</i>. Launch file not found in tincan package');

				if(isset($upload['version']) && $upload['version'] == "none")
				$upload['version'] = "";

				$upload["content_type"] = "xapi";
				$upload['process_status'] = 1;
			} else {

				$nonxapi_files = [
					"player.html", // Check if No tincan.xml Articulate Studio File
					"story.html", // Check if No tincan.xml Articulate Storyline File
					"index.html", // Check if No tincan.xml Captivate File
					"presentation.html", // Check if No tincan.xml Articulate Studio 13 File
					"content".DIRECTORY_SEPARATOR."index.html", // Check if No tincan.xml Articulate Rise File
					"index.htm", // Check if No tincan.xml Gomo Learning File
					"*/index.html", // Check if No tincan.xml index.html File in subfolder
					"*/index.htm", // Check if No tincan.xml index.htm File in subfolder
				];

				foreach($nonxapi_files as $file) {
					$nonxapi_file = $upload['content_path'].DIRECTORY_SEPARATOR.$file;
					$nonxapi_file = glob($nonxapi_file);

					if(empty($nonxapi_file))
						continue;

					$nonxapi_file = is_array($nonxapi_file)? $nonxapi_file[0]:$nonxapi_file;
					if(file_exists($nonxapi_file)) {
						$upload['src'] =  str_replace( DIRECTORY_SEPARATOR, "/", str_replace($upload['content_path'], $upload['content_url'], $nonxapi_file) );
						$upload['launch_path'] =  $nonxapi_file;
						$upload['version'] = "none";
						$upload['process_status'] = 1;
						$upload["content_type"] = "not_xapi";
						break;
					}
				}
			}

			foreach($upload as $k=>$v)
				$params[$k] = addslashes($v);

			if(!empty($params['process_status']) && empty($params['title'])) {
				$params['title'] = ucwords( str_replace(array("-", "_"), array(" ", " "), $upload["content_filename"] ));
			}
		}

		if(empty($params['process_status'])) {
			if(isset($params["src"]))
				unset($params["src"]);
			if(isset($params["launch_path"]))
				unset($params["launch_path"]);
		}

		return $params;
	}

	function get_tincanxml($dir) {
		$tincanxml_file = $dir.DIRECTORY_SEPARATOR."tincan.xml";

		if(file_exists($tincanxml_file))
			return "";
		else
		{
			$dirlist = scandir($dir);
			foreach($dirlist as $d)
			{
				if($d != "." && $d != "..")
				{
					$tincanxml_file = $dir.DIRECTORY_SEPARATOR.$d.DIRECTORY_SEPARATOR."tincan.xml";
					if(is_dir($dir) && is_dir($dir.DIRECTORY_SEPARATOR.$d))
					if(file_exists($tincanxml_file))
						return $d;
				}
			}
		}
		return 0;
	}
	function get_xml_api() {
		  register_rest_route( 'grassblade/v1', '/getXML', array(
				'methods' => 'GET',
				'callback' => array($this, "get_xml"),
				'permission_callback' => function () {
				 	return current_user_can( 'connect_grassblade_lrs' ) ||  current_user_can( 'manage_options' );
				}
		  ) );
	}
	function get_xml() {
		$ids = @$_GET["ids"];
		if(empty($ids))
			return;
		$ids = explode(",", $ids);
		$paths = array();
		foreach ($ids as $id) {
			$id = intVal($id);
			if(empty($id))
			continue;

			$xapi_content = get_post_meta($id, "xapi_content", true);

			if(!empty($xapi_content["content_path"]) && !empty($xapi_content["content_url"])) {
				$content_path = $xapi_content["content_path"];
				$content_url = $xapi_content["content_url"];
				$path = "";
				if(file_exists($content_path."/tincan.xml"))
					$path = $content_path."/tincan.xml";
				if(empty($path))
					$path = glob($content_path."/*/tincan.xml");
				//if(empty($path))
				//	$path = glob($content_path."/*/*/tincan.xml");

				if(is_array($path))
					$path = $path[0];

				if(!empty($path))
				$paths[$id] = str_replace($content_path, $content_url, $path);
			}
			else if(!empty($xapi_content["src"]))
			{
				$content_url = $xapi_content["src"];
				$tincan_url  = html_entity_decode(dirname($content_url)."/tincan.xml");
				if(grassblade_url_exists($tincan_url))
					$paths[$id] = $tincan_url;
				/* else
				{
					$tincan_url  = html_entity_decode(dirname(dirname($content_url))."/tincan.xml");
					if(grassblade_url_exists($tincan_url))
					$paths[$id] = $tincan_url;
				} */
			}
			//$msg[$id] = $xapi_content;
		}

		//$paths['msg'] = $msg;
		//$paths['request'] = $_REQUEST;

		return $paths;
	}
	function grassblade_attempt_progress($attr) {
		global $current_user, $post;
		if(empty($current_user))
			return '';

		$shortcode_defaults = array(
		 		'id' => 0,
		 		'progress_bar' => 1,
		 		'message' => 1,
		 		'raw' => 0,
		 		'decimals' => 0,
		 		'user_id'	=> 0,
				);
		$shortcode_atts = shortcode_atts ( $shortcode_defaults, $attr);
		$shortcode_atts = apply_filters("grassblade_attempt_progress_atts", $shortcode_atts, $attr);
		extract($shortcode_atts);

		if(!empty($user_id))
			$user = get_user_by("id", $user_id);
		else
			$user = $current_user;

		if(empty($user->ID))
			return '';

		if(empty($id) && !empty($post->ID) && $post->post_type == "gb_xapi_content")
			$id = $post->ID;

		$re_check = false;
		if(!empty($post->post_type) && $post->post_type == "gb_xapi_content")
			$re_check = true;
		else if(!empty($post->ID)) {
			$xapi_content_ids = grassblade_xapi_content::get_post_xapi_contents($post->ID);
			if(in_array($id, $xapi_content_ids))
				$re_check = true;
		}

		$completed = get_user_meta($user->ID, "completed_".$id, true);
		if(!empty($completed)) {
			$progress = 100;
		}
		else
			$progress = $this->get_attempt_progress_by_id($id, $user, $re_check);

		$return = "";
		$progress_number = number_format(floatVal($progress), $decimals);

		if($raw)
			return $progress_number;

		if(!empty($progress_bar))
		{
			$return .= "<div class='progress_bar' style='width: 100%; height: 4px; background: #ccc;'><div style='width: ".intVal($progress_number)."%; height: 4px;background:#00dc00;'></div></div>";
		}
		if(!empty($message))
		{
			if(!is_numeric($progress_number))
			$msg = __("Not started", "grassblade");
			else
			$msg = __(sprintf("%s complete", $progress_number."%"), "grassblade");

			$return .= "<div class='progress_message' style='color: #333; font-size: 14px;'>".$msg."</div>";
		}

		return $return;
	}
	function get_attempt_progress_by_id( $content_id, $user = null, $re_check = false ) {

		if(!empty($user->ID))
		$current_user = $user;
		else
		$current_user = wp_get_current_user();
		$xapi_content = new grassblade_xapi_content();
		$params = $xapi_content->get_params($content_id);

		if(empty($params["activity_id"]))
			return '';

		grassblade_xapi::update_all_attempt_progress($user);
		$r = get_user_meta($current_user->ID, "xapi_reg_".$params["activity_id"], true);

		if(!empty($r["latest"]) && !empty($r["registrations"][$r["latest"]]))
			$record = $r["registrations"][$r["latest"]];
		else
		{
                        $registrations = !empty($r["registrations"])? $r["registrations"]:array();
                        $record = is_array($registrations)? end($registrations):array();
		}
		if(isset($record["progress"])) {
			$progress = $record["progress"];
			if(!is_array($progress))
				$progress = json_decode($progress, true);

			if(isset($progress["progress"])) {
				return $progress["progress"] * 100;
			}
		}

		return '';
		//$r["registrations"][$registration]["progress"]["progress"]
		/*
		$xapi_content = new grassblade_xapi_content();
		$params = $xapi_content->get_params($content_id);

		if(empty($params["activity_id"]))
			return '';

		$grassblade_settings = grassblade_settings();

		if(!empty($params["version"]))
		$version = $params["version"];
		else {
			$version = $grassblade_settings["version"];
		}

		if(!in_array($version, array("0.95", "1.0"))) //Works only for xAPI Version 0.95 and 1.0
			return '';

		$activity_id = $params["activity_id"];
		$r = get_user_meta($current_user->ID, "xapi_reg_".$activity_id, true);
		$registration = !empty($r["latest"])? $r["latest"]:"";

		if(empty($registration))
			return '';

		if( !empty($registration) && !empty($r["registrations"][$registration]["progress"]) )
			$progress = $r["registrations"][$registration]["progress"];

		if(empty($progress) || $re_check || !empty($r["registrations"][$registration]["progress_re_check"]))
		{
			if(empty($actor_type))
				$actor_type = empty($grassblade_settings["actor_type"])? "mbox":$grassblade_settings["actor_type"];

			$actor = grassblade_getactor(false, $version, $current_user, $actor_type);
			$agent_id = grassblade_get_actor_id($actor);

			if(!empty($params["endpoint"]))
				$lrs_params = $params;
			else
				$lrs_params = $grassblade_settings;

			$progress = $this->get_attempt_progress($activity_id, $agent_id, $registration, $lrs_params);
		}

		if(!empty($progress)) {
			$return = json_decode($progress, true);
			if(empty($return["status"])) {
				if(current_user_can("manage_options"))
					return print_r($return, true);
				else
					return '';
			}

			if(empty($r["registrations"][$registration]["progress"]) || $re_check) {
				$r["registrations"][$registration]["progress"] = $progress;
				if(isset($r["registrations"][$registration]["progress_re_check"]))
					unset($r["registrations"][$registration]["progress_re_check"]);

				update_user_meta($current_user->ID, "xapi_reg_".$activity_id, $r);
			}

			if(isset($return["message"]) && is_numeric($return["message"]))
				return $return["message"];
		}
		*/
		return '';
	}
	function get_attempt_progress($objectid, $agent_id, $registration, $params = null) {
		if(empty($params))
		$params = grassblade_settings();

		$pass = grassblade_generate_secure_token(9, $params["password"], $agent_id);
		$auth = base64_encode($params["user"].":".$pass);
		$endpoint = $params["endpoint"];
		$endpoint = str_replace("xAPI/", "api/v1/attempt_progress/get", $endpoint);

		$params = array(
					"agent_id" 	=> $agent_id,
					"auth"		=> $auth,
					"objectid" 	=> $objectid,
				);
		if (!empty($registration))
			$params['registration'] = $registration;

		$params_string = http_build_query($params);
		$url = $endpoint."?".$params_string;

		$return = grassblade_file_get_contents_curl($url);
		return $return;
	}
	static function update_all_attempt_progress($user = null) {
		global $grassblade;

		if(empty($user->ID))
			$user = wp_get_current_user();

		if(empty($user->ID))
			return;

		if(empty($grassblade["update_all_attempt_progress"]))
			$grassblade["update_all_attempt_progress"] = array();

		if(isset($grassblade["update_all_attempt_progress"][$user->ID]))
			return $grassblade["update_all_attempt_progress"][$user->ID];

		$attempt_progress_check = get_user_meta($user->ID, "attempt_progress_check", true);
		$last_attempted 		= get_user_meta($user->ID, "last_attempted", true);
		//$last_attempted = array("timestamp" => time());

		if(empty($attempt_progress_check))
			$attempt_progress_check = array("count" => 0, "failed_count" => 0, "success_count" => 0);

		if(!empty($attempt_progress_check["timestamp"])) {

			$since = $attempt_progress_check["timestamp"] - 60;
			if( empty($last_attempted["timestamp"]) || $last_attempted["timestamp"] < $since ) { //No attempts after last successful check. Recheck if last failed
				$grassblade["update_all_attempt_progress"][$user->ID] = '';
				return;
			}
		}
		else
			$since = 0;

		if( !empty($attempt_progress_check["failing"]) && !empty($attempt_progress_check["failing"]["count"]) && $attempt_progress_check["failing"]["count"] >= 3  ) {
			//Failing

			if( empty($last_attempted["timestamp"]) || $last_attempted["timestamp"] < $attempt_progress_check["failing"]["timestamp"] ) //No Attempt. Or No Attempt after last failed check
			{
				$grassblade["update_all_attempt_progress"][$user->ID] = '';
				return;
			}
		}

		$params = grassblade_settings();

		$actor_type = empty($params["actor_type"])? "mbox":$params["actor_type"];
		$version 	= empty($params["version"])? "1.0":$params["version"];

		if(!in_array($version, array("0.95", "1.0"))) //Works only for xAPI Version 0.95 and 1.0
		{
			$grassblade["update_all_attempt_progress"][$user->ID] = '';
			return '';
		}

		$actor = grassblade_getactor(false, $version, $user, $actor_type);
		$agent_id = grassblade_get_actor_id($actor);

		$data = grassblade_xapi::get_all_attempt_progress($user, $since);

		if(empty($data) || empty($data["timestamp"])) {
			$data = array("status" => 0, "timestamp" => time()); //Assume failed request
		}

		if(empty($data["status"]))
		{
			if(empty($attempt_progress_check["failing"]))
			$attempt_progress_check["failing"] = array("count" => 0);

			$attempt_progress_check["failing"]["count"]++;
			$attempt_progress_check["failed_count"]++;
			$attempt_progress_check["failing"]["timestamp"] = $data["timestamp"];
		}
		else
		{
			$timestamp = $data["timestamp"];
			$data = $data["data"];

			if(!empty($data["count"]) && !empty($data["records"]) && is_array($data["records"]))
			foreach ($data["records"] as $record) {
				if( strtolower( $record["agent_id"] ) == strtolower( $agent_id ) )
				grassblade_xapi::update_attempt_progress($user, $record);
			}

			$attempt_progress_check["count"]++;
			$attempt_progress_check["success_count"]++;
			$attempt_progress_check["timestamp"] = $timestamp;
			if(isset($attempt_progress_check["failing"]))
				unset($attempt_progress_check["failing"]);
		}

		update_user_meta($user->ID, "attempt_progress_check", $attempt_progress_check);
		$grassblade["update_all_attempt_progress"][$user->ID] = $data;
		return $data;
	}
	static function update_attempt_progress($user, $progress) {
		/*
		[agent_id] => test@email.com
        [objectid] => http://MVagOb3GzqKeDlhHK0sN0swb5xXkYYMv_rise
        [progress] => 1
        [state] => Array
            (
                [position] => 1
                [count] => 1
                [updated] => 1580559925
                [started] => 1580559925
                [name] => Articulate Rise 360 Demo Course/blocks
            )

        [registration] => 937345a7-96da-4f98-ba82-fc99a0f2a4a2
        [activity_id] => 38133
        [learner_id] => 43712
       	*/
       	if(empty($progress["objectid"]))
       		return;

		$objectid = $progress["objectid"];
		$registration = $progress["registration"];

		$key = "xapi_reg_".$objectid;
		$r = get_user_meta($user->ID, $key, true);

                if(empty($r))
                        $r = array("latest" => $registration, "registrations" => array());

                if(empty($r["registrations"][$registration]))
                        $r["registrations"][$registration] = array("generated" => time());

		$r["registrations"][$registration]["progress"] = $progress;
		$r["updated_by"] = "update_attempt_progress";

		if(isset( $r["registrations"][$registration]["progress_re_check"] ))
			unset( $r["registrations"][$registration]["progress_re_check"] );

		update_user_meta($user->ID, $key, $r);
	}
	static function get_all_attempt_progress($user = null, $since = null) {
		if(empty($user->ID))
			$user = wp_get_current_user();

		if(empty($user->ID))
			return;

		$params = grassblade_settings();

		$actor_type = empty($params["actor_type"])? "mbox":$params["actor_type"];
		$version 	= empty($params["version"])? "1.0":$params["version"];

		if(!in_array($version, array("0.95", "1.0"))) //Works only for xAPI Version 0.95 and 1.0
			return '';

		$actor = grassblade_getactor(false, $version, $user, $actor_type);
		$agent_id = grassblade_get_actor_id($actor);

		$pass = grassblade_generate_secure_token(9, $params["password"], $agent_id);
		$auth = base64_encode($params["user"].":".$pass);
		$endpoint = $params["endpoint"];
		$endpoint = str_replace("xAPI/", "api/v1/attempt_progress/get_all", $endpoint);

		$params = array(
					"agent_id" 	=> $agent_id,
					"auth"		=> $auth,
				);
		if(!empty($since))
		$params["since"] = $since;

		if (!empty($registration))
			$params['registration'] = $registration;

		$params_string = http_build_query($params);
		$url = $endpoint."?".$params_string;

		$return = grassblade_file_get_contents_curl($url);
		return json_decode( $return, true );
	}
	static function launch($content_id = 0, $content_data = []) {
		if( empty($content_id) )
		$content_id = !is_numeric($_REQUEST['content_id'])? intVal(base64_decode($_REQUEST['content_id'])):intval($_REQUEST['content_id']);

		if(empty($content_data))
		$content_data = grassblade_xapi_content::get_params($content_id);
		$registration = !empty($_REQUEST['registration'])? $_REQUEST['registration']:'';
		if( empty($content_data["version"]) || in_array($content_data["version"], ['', '0.9', '0.95', '1.0', 'none']) ) {
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
} // end of grassblade_xapi class

$gb_xapi = new grassblade_xapi();

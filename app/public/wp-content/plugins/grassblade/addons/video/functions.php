<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once(dirname(__FILE__).'/interactive-video/interactive-video.php');

add_filter("grassblade_shortcode_atts", "grassblade_video_shortcode_atts", 2,2);
add_action( 'wp_ajax_nopriv_grassblade_video_launch', 'grassblade_video_launch' );
add_action( 'wp_ajax_grassblade_video_launch', 'grassblade_video_launch' );

function grassblade_video_launch($content_id = 0, $content_data = []) {
	if(defined("GB_VIDEO_DEV")) {
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
	}
	if( empty($content_id) )
	$content_id = !is_numeric($_REQUEST['content_id'])? intVal(base64_decode($_REQUEST['content_id'])):intval($_REQUEST['content_id']);
	$user_id = !empty($_REQUEST['user_id']) && grassblade_lms::is_admin()? intval($_REQUEST['user_id']):0;

	$config = array();
	if(!empty($content_id)) {
		if(empty($content_data))
		$content_data = grassblade_xapi_content::get_params($content_id);

		$grassblade_settings = grassblade_settings();
		$content = get_post($content_id);

		$user = wp_get_current_user();

		$endpoint 	= !empty($content_data["endpoint"])?  $content_data["endpoint"]:$grassblade_settings["endpoint"];
		$api_user 	= !empty($content_data["user"])?  $content_data["user"]:$grassblade_settings["user"];
		$api_pass 	= !empty($content_data["pass"])?  $content_data["pass"]:$grassblade_settings["password"];
		$guest 		= (!is_null($content_data["guest"]) && $content_data["guest"] !== false && $content_data["guest"] != "")?  $content_data["guest"]:$grassblade_settings["track_guest"];
		$actor_type	= empty($grassblade_settings["actor_type"])? "mbox":$grassblade_settings["actor_type"];
		$actor_user = !empty($user_id)? get_user_by('id', $user_id):null;
		$actor 		= grassblade_getactor($guest, "1.0", $actor_user, $actor_type);
		$agent_id 	= grassblade_get_actor_id($actor);
		$agent_name	= empty($actor["name"])? "":$actor["name"];
		$activity_id 	= $content_data["activity_id"];

		if( grassblade_lms::is_admin() && !empty($_REQUEST['registration']) )
		{
			$registration = $_REQUEST['registration'];
		}
		else
		{
			$registration = !empty($_REQUEST['registration'])? $_REQUEST['registration']:$content_data["registration"];
			$registration 	= grassblade_get_registration($registration, $activity_id, $actor_user);
		}

		if(empty($user->ID) && empty($guest) || empty($agent_id)) { //Require Login
			return '';
		}
		$completion_threshold = empty($content_data["passing_percentage"])? 1:number_format( $content_data["passing_percentage"]/100, 2);
//echo "<pre>";print_r($content_data);exit();
		//Video Config
		$config = array(
			"activity_name" => $content->post_title,
			"endpoint"  	=> (empty($endpoint) || $endpoint == "/")? "":$endpoint,
			"auth"      	=> "Basic ".base64_encode($api_user.":".$api_pass),
			"registration" 	=> $registration,
			"actor"     	=> json_encode($actor),
			"user"			=> isset($user->ID)? $user->ID:0,
			"activity_id" 	=> $content_data['activity_id'],
			"width"			=> !empty($content_data['width'])? $content_data['width']:$grassblade_settings["width"],
			"height"		=> !empty($content_data['height'])? $content_data['height']:$grassblade_settings["height"],
			"aspect"		=> !empty($content_data['aspect'])? $content_data['aspect']:"1.7777",
			"target"		=> !empty($content_data['target'])? $content_data['target']:"_blank",
			"video_autoplay"		=> !empty($content_data['video_autoplay'])? $content_data['video_autoplay']:"",
			"completion_threshold" 	=> $completion_threshold,
			"exit_msg" 				=> grassblade_get_label("grassblade_video_exit_msg", __("You have reached the end of the video", "grassblade")),
			"exit_msg_completed" 	=> grassblade_get_label("grassblade_video_exit_msg_completed", __("Congratulations! You have completed the video", "grassblade")),
			"exit_button_name" 		=> __("Exit", "grassblade"),
			"restart_button_name" 	=> __("Restart", "grassblade"),
			"results_button_name" 	=> __("Results", "grassblade"),
			"grassblade_version"	=> GRASSBLADE_VERSION
			);
		if(!empty($content_data['video_hide_controls']))
			$config["video_controls"] = 0;

		if(defined("GB_VIDEO_DISABLE_PROGRESS_BAR"))
			$config["disable_progress_bar"] = 1;
	}
	else
	{
		http_response_code(404);
		exit;
	}
	$config = apply_filters("grassblade_video_config", $config, $content_data);
	$video_url = !empty($_GET["activity_id"])? $_GET["activity_id"]:(!empty($config["activity_id"])? $config["activity_id"]:"");


	$html = '<!DOCTYPE html><html><head>';
	$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
	$html .= grassblade_video_assets($video_url);

	$params = !empty($config)? $config:$_REQUEST;
	$html .= apply_filters("grassblade_video_head", "", $params);
	if(!empty($config))
	$html .= '<script type="text/javascript">
//<![CDATA[
var config='.json_encode($config).';
//]]>
</script>';

	$html .= '</head><body prefix="dcterms: http://purl.org/dc/terms/" data-ver="gbvideo-1.0"><div class="gb_video"></div>'.apply_filters("grassblade/video/extra_html", "", $content_id, $params).'</body></html>';
	echo $html;
	exit();
}
function grassblade_video_assets($video_url) {

	if(!defined("GB_VIDEO_DEV"))
	{
		$scripts 		= array("gbvideo.js");
		$stylesheets 	= array("gbvideo.css");
	}
	else
	{
		$stylesheets 	= array("gbvideo.css", "plugins/video.js/video.min.css", "style.css", "plugins/videojs-markers/videojs.markers.css");
		$scripts = array(
							"plugins/jquery/jquery.min.js",
							"plugins/video.js/video.js",
							"plugins/videojs-vimeo/videojs-vimeo.js",
							"plugins/videojs-wistia/wistia.js",
							"plugins/videojs-youtube/youtube.js",
							"plugins/xAPIWrapper-latest/lib/cryptojs_v3.1.2.js",
							"plugins/xAPIWrapper-latest/src/xapiwrapper.js",
							"plugins/xapi-videojs.js?v=12.33",
							"plugins/videojs-markers/videojs-markers.js",
							"script.js",
						);
	}

	$base = plugins_url("", __FILE__)."/v2/";

	/* Debug
		$scripts[] = "plugins/log.js";
		$scripts[] = "plugins/jquery.device.detector.js";
	*/
	if(!empty($video_url) && strpos(strtolower($video_url), ".mpd"))
		$scripts[] = "dash.min.js";

	$html = "";
	foreach ($stylesheets as $url) {
		$html .= '<link type="text/css" href="'.$base.$url.'" rel="stylesheet">';
	}
	foreach ($scripts as $url) {
		$html .= '<script type="text/javascript" src="'.$base.$url.'"></script>';
	}

	return $html;
}
function grassblade_video_noxapi_url_content_types($content_types, $version, $shortcode_atts, $attr) {
	$content_types["video"] = 'video';
	return $content_types;
}
function grassblade_video_shortcode_atts($shortcode_atts, $attr) {

	if(!empty($shortcode_atts["id"]) && !empty($shortcode_atts["video"])) {
		$content_id = $shortcode_atts["id"];
		$src = admin_url('admin-ajax.php').'?action=grassblade_video_launch&content_id='.$content_id;
		$src = apply_filters( "grassblade_video_player", $src, $shortcode_atts, $attr );

		if(strpos($src, "action=grassblade_video_launch") === false)
			return grassblade_video_shortcode_atts_old($shortcode_atts, $attr);

		add_filter("noxapi_url_content_types", "grassblade_video_noxapi_url_content_types", 10, 4);

		$shortcode_atts["src"] = $src;
		$guest_name = strip_tags(isset($_REQUEST['actor_name'])?$_REQUEST['actor_name']:'');
		$guest_mailto = strip_tags(isset($_REQUEST['actor_mbox'])?$_REQUEST['actor_mbox']:'');
		if(!empty($guest_name))
			$shortcode_atts["src"] .= '&actor_name='.rawurlencode($guest_name);
		if(!empty($guest_mailto))
			$shortcode_atts["src"] .= '&actor_mbox='.rawurlencode($guest_mailto);
	}
	return $shortcode_atts;
}
function grassblade_video_shortcode_atts_old($shortcode_atts, $attr) {
	if(!empty($shortcode_atts["video"]))
	{
		$shortcode_atts["activity_id"] = $shortcode_atts["video"];
		$ext = pathinfo($shortcode_atts["activity_id"], PATHINFO_EXTENSION);
		$index_path = strtolower($ext) == "mpd" ? 'v2/dash.html':'v2/index.html';
		$shortcode_atts["src"] = apply_filters( "grassblade_video_player", admin_url( 'admin-ajax.php?action=grassblade_video_launch' ), $shortcode_atts, $attr );
		if(!empty($shortcode_atts["activity_name"]))
			 $shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "activity_name=".rawurlencode($shortcode_atts["activity_name"]);

		if(!empty($shortcode_atts["passing_percentage"]) && is_numeric($shortcode_atts["passing_percentage"])) {
			$completion_threshold = number_format( $shortcode_atts["passing_percentage"]/100, 2);
			if(!empty($completion_threshold)) {
				$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "completion_threshold=".$completion_threshold;
			}
		}

		if(!empty($shortcode_atts["video_hide_controls"])) {
			$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "video_controls=0";
		}
		if(!empty($shortcode_atts["video_autoplay"]) && (empty($_REQUEST['context']) || is_admin() && $_REQUEST['context'] != "edit" ) ) {
			$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "video_autoplay=1";
		}
		if(!empty($shortcode_atts["width"])) {
			$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "width=".rawurlencode($shortcode_atts["width"]);
		}
		if(!empty($shortcode_atts["height"])) {
			$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "height=".rawurlencode($shortcode_atts["height"]);
		}
		if(!empty($shortcode_atts["aspect"])) {
			$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "aspect=".rawurlencode($shortcode_atts["aspect"]);
		}
		if(!empty($shortcode_atts["target"])) {
			$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "target=".rawurlencode($shortcode_atts["target"]);
		}

		$exit_msg 				= __("You have reached the end of the video", "grassblade");
		$exit_button_name 		= __("Exit", "grassblade");
		$restart_button_name 	= __("Restart", "grassblade");
		$results_button_name 	= __("Results", "grassblade");

		$shortcode_atts["src"] .= ( strpos($shortcode_atts["src"], "?")?  "&":"?" ) . "exit_msg=".rawurlencode($exit_msg)."&exit_button_name=".rawurlencode($exit_button_name)."&restart_button_name=".rawurlencode($restart_button_name)."&results_button_name=".rawurlencode($results_button_name);
	}
	return $shortcode_atts;
}


add_filter("xapi_content_params_update", "grassblade_video_params_update", 10, 2);
function grassblade_video_params_update($params, $post_id) {
	if(!empty($params['video'])) {
		$video_url = $params['activity_id'] = $params['video'];
		$params['src'] = '';
		$params["content_type"] = "video";

		if(!empty($params['content_url'])) {
			$content_url = str_replace(array("http://", "https://"), array("",""), strtolower($params['content_url']));
			$video_url = str_replace(array("http://", "https://"), array("",""), strtolower($video_url));
			if(strpos($video_url, $content_url) === false)
			{
				if(isset($params["content_url"]))
					unset($params["content_url"]);
				if(isset($params["content_path"]))
					unset($params["content_path"]);
				if(isset($params["type"]))
					unset($params["type"]);
				if(isset($params["content_size"]))
					unset($params["content_size"]);
			}
		}
		if(isset($params["original_activity_id"]))
			unset($params["original_activity_id"]);
	}
	else {
		if(isset($params['video_type']))
			unset($params['video_type']);

		if(!empty($params["content_type"]) && $params["content_type"] == "video" )
			$params["content_type"] = "";
	}
	return $params;
}

add_filter( 'grassblade_process_upload', 'video_content_upload' , 30, 3);

function video_content_upload($params, $post , $upload) {
	$supported_file_formats = array("mp4", "mp3", "webm", "mov"); // "mkv", "ogg", "ogv", "flac", "wav", "m4a", "avi"
	$supported_zipped_file_formats = array(
							"playlist.m3u8", //HLS
							"*/playlist.m3u8", //HLS
							"index.m3u8", //HLS
							"*/index.m3u8", //HLS
							"*.m3u8", //HLS
							"*.mpd", //MPEG-DASH
							"*/*.mpd", //MPEG-DASH
						);

	$supported_file_formats = apply_filters("grassblade_video_file_formats", $supported_file_formats);
	$supported_zipped_file_formats = apply_filters("grassblade_video_zipped_file_formats", $supported_zipped_file_formats);

	if (empty($params['process_status'])) {

		if (isset($params['src'])) {
			unset($params['src']);
		}

		if ($ext = pathinfo($upload['content_url'], PATHINFO_EXTENSION)) {
			if(in_array($ext, $supported_file_formats)) {
				$params['video'] =  $upload['content_url'];
				$params['activity_id'] = $upload['content_url'];
				$params['video_type'] = $ext;
				$params['process_status'] = 1;
			}
		} else if(is_dir($upload["content_path"])) {
			$file_url = gb_get_video_url($upload['content_path'], $upload['content_url'], $supported_zipped_file_formats);

			if ($file_url) {
				$params['video'] =  $file_url;
				$params['activity_id'] = $file_url;
				$ext = pathinfo($file_url, PATHINFO_EXTENSION);
				$params['video_type'] = $ext;
				$params['process_status'] = 1;
			}
		}

		if(!empty($params['process_status'])) {
			$params["content_url"] 	= $upload["content_url"];
			$params["content_path"] = $upload["content_path"];
			$params["type"] 		= $upload["type"];
			$params["content_type"] = "video";
			$params["content_tool"] = "";

			if(!empty($upload["version"]) && !is_numeric($upload["version"])) {
				$params["version"] = "";
			}

			$params["title"] = ucwords( str_replace(array("-", "_"), array(" ", " "), $upload["content_filename"] ));
		}

		return $params;
	}
	else
	if (isset($params['video'])) {
		unset($params['video']);
		unset($params['video_hide_controls']);
		unset($params['video_autoplay']);
	}

	return $params;
}

function gb_get_video_url($dir, $url, $formats) {

	foreach ($formats as $format) {
		$files = glob($dir."/".$format);
	//	grassblade_debug($dir."/".$format);
	//	grassblade_debug($files);
		if(!empty($files[0]))
			return str_replace($dir, $url, $files[0]);
	}

	return '';
}

add_filter("grassblade_custom_labels_fields","grassblade_video_custom_labels_fields",10,1);
function grassblade_video_custom_labels_fields($labels_fields) {
	$labels_fields[] = array( 'id' => 'label_grassblade_video_exit_msg', 'label' => __( 'Video End Screen Message (InComplete)', 'grassblade' ),  'placeholder' => __("You have reached the end of the video", "grassblade"), 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Message shown at the end of a video, if user has not completed the video.', 'grassblade'));
	$labels_fields[] = array( 'id' => 'label_grassblade_video_exit_msg_completed', 'label' => __( 'Video End Screen Message (Completed)', 'grassblade' ),  'placeholder' => __("Congratulations! You have completed the video", "grassblade"), 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Message shown at the end of a video, if user has completed the video.', 'grassblade'));

	return $labels_fields;
}
/*
add_filter( "grassblade_video_config", function($config, $content_data) {
	if(!isset($config["video_endscreen"]))
		$config["video_endscreen"] = 0;
	return $config;
}, 10, 3);
*/

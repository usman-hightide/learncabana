<?php
/*
Plugin Name: GrassBlade xAPI Companion
Plugin URI: https://www.nextsoftwaresolutions.com
Description: Upload and host xAPI, SCORM, cmi5 and HTML5 content built using authoring tools like Articulate, iSpring, DominKnow, Lectora and more. Also host and track H5P content, and videos like YouTube, Vimeo, Wistia, MP4, HLS, etc. Use all these content to track completion, and restrict progress in LMSes like LearnDash LMS, LifterLMS, WP Courseware and LearnPress. You can also tracking events like Enrolled, Unenrolled, Signup, Page Views, Post Edits, etc.
Author: Next Software Solutions
Version: 6.2.12
Author URI: https://www.nextsoftwaresolutions.com
*/
if ( ! defined( 'ABSPATH' ) ) exit;

define("GRASSBLADE_VERSION", "6.2.12");
define("GRASSBLADE_ADDON_DIR", dirname(__FILE__)."/addons");
require_once(dirname(__FILE__)."/db.update.php");
new GrassBlade_DB();

include(GRASSBLADE_ADDON_DIR."/grassblade_addons.php");
require_once(GRASSBLADE_ADDON_DIR."/nss_xapi.class.php");
require_once(GRASSBLADE_ADDON_DIR."/nss_xapi_verbs.class.php");
require_once(GRASSBLADE_ADDON_DIR."/functions.php");

if(!class_exists('grassblade_addons'))
require_once(dirname(__FILE__)."/addon_plugins/functions.php");

$GrassBladeAddons = new GrassBladeAddons();
$GrassBladeAddons->IncludeFunctionFiles();
define('GRASSBLADE_ICON15', plugin_dir_url(__FILE__)."img/button-15.png");
//define('GB_DEBUG', true);
//define("GB_LAUNCH_REVEAL_CONTENT_URL", 1);

function grassblade($attr) {
	if ( post_password_required() )
	      return '';
	$grassblade_settings = grassblade_settings();

	$shortcode_defaults = array(
	 		'id' => 0,
			'version' => empty($grassblade_settings["version"])? "1.0":$grassblade_settings["version"],
			'extra' => '',
			'target' => 'iframe',
			'width' => $grassblade_settings["width"],
			'height' => $grassblade_settings["height"],
			'aspect' => '',
			'endpoint' => '',
			'auth' => '',
			'user' => '',
			'pass' => '',
			'src' => '',
			'video'	=> '',
			'activity_name' => '',
			'title'	=> '',
			'video'	=> '',
			'text' => 'Launch',
			'link_button_image'	=> '',
			'guest' => false,
			'actor_type' => '',
			'actor_user_id' => null,
			'activity_id' => '',
			'registration' => '',
			'show_results' => '',
			'show_rich_quiz_report' => '',
			'passing_percentage' => '',
			'video_hide_controls'	=> 0,
			'video_autoplay'	=> 0,
			'content_type' => '',
			'class'	=> '',
			'remove_content' => false
			);
	$shortcode_defaults = apply_filters("grassblade_shortcode_defaults", $shortcode_defaults);
	$id = !empty($attr["id"])? intVal($attr["id"]):0;
	if(!empty($id)) {
		$xapi_content_post 	 = get_post($id);
		$xapi_content_status = !empty($xapi_content_post->post_status) ? $xapi_content_post->post_status:"deleted";

		if( empty($xapi_content_post) || $xapi_content_status != "publish" ) {
			if( ( current_user_can("manage_options") || current_user_can( 'edit_post', $id) )  ) {
				$status_labels = [
					"trash" => __('in Trash', 'grassblade'),
					"draft" => __('a Draft', 'grassblade'),
					"pending" => __('Pending Review', 'grassblade'),
					"future" => __('Scheduled', 'grassblade'),
					"publish" => __('Published', 'grassblade'),
					"auto-draft" => __('an Auto-Draft', 'grassblade'),
				];

				$status_label = isset($status_labels[$xapi_content_status]) ? $status_labels[$xapi_content_status] : esc_html($xapi_content_status);

				if(  empty($_GET['preview']) && empty($_GET['xapi_preview']) )
				return "<div style='color: red'>" . sprintf( __("xAPI Content (#%d) is currently %s. Please publish it to display the content, or if it's trash/deleted, remove it from this page.", "grassblade"), $id, $status_label ) . "</div>";
			}
			else
				return "";
		}

		$xapi_content = new grassblade_xapi_content();
		$params = $xapi_content->get_shortcode($id, true);
		$shortcode_params = shortcode_atts ($params, $attr);
		if(empty($shortcode_params["title"])) {
			$shortcode_params["title"] = @$xapi_content_post->post_title;
		}
		if(!empty($params["aspect_lock"]) && !empty($shortcode_params["height"]) && !empty( $shortcode_params["width"] ) ) {
			$shortcode_params["aspect"] = number_format( floatval($shortcode_params["width"]) / floatval($shortcode_params["height"]) , 4);
		}
		$shortcode_params["id"] = $id;
		$shortcode_defaults = shortcode_atts ( $shortcode_defaults, $shortcode_params);
	}
	$shortcode_atts = shortcode_atts ( $shortcode_defaults, $attr);
	$shortcode_atts = apply_filters("grassblade_shortcode_atts", $shortcode_atts, $attr);
	extract($shortcode_atts);

	if(!empty($shortcode_atts["id"])) {
		global $post;
		$has_access  = apply_filters( "grassblade_has_access", true, $shortcode_atts["id"], $post, wp_get_current_user());
		if(empty($has_access))
		return "";
	}
	// Read in existing option value from database
	if(empty($endpoint))
	$endpoint = !empty($grassblade_settings["endpoint"])? $grassblade_settings["endpoint"]:"";

	if(empty($user))
	$user = !empty($grassblade_settings["user"])? $grassblade_settings["user"]:"";

	if(empty($pass))
	$pass = !empty($grassblade_settings["password"])? $grassblade_settings["password"]:"";

	$completion_tracking = empty($id)? false:grassblade_xapi_content::is_completion_tracking_enabled($id);

	if($guest === false)
	$grassblade_tincan_track_guest = $grassblade_settings["track_guest"];
	else
	$grassblade_tincan_track_guest = $guest;

	if(empty($actor_type))
		$actor_type = empty($grassblade_settings["actor_type"])? "mbox":$grassblade_settings["actor_type"];

	$actor_user = !empty($actor_user_id)? get_user_by("id", $actor_user_id):null;
	$actor = grassblade_getactor($grassblade_tincan_track_guest, $version, $actor_user, $actor_type);

	if(empty($actor))
	{
		if($grassblade_tincan_track_guest == 2)
		{
			$guest_name = strip_tags(isset($_REQUEST['actor_name'])?$_REQUEST['actor_name']:'');
			$guest_mailto = strip_tags(isset($_REQUEST['actor_mbox'])?$_REQUEST['actor_mbox']:'');

			$name_email_form = "
				<div id='grassblade_name_email_form'>
				".__("Please enter your name and email to view the content:","grassblade")."<br>
				<form method='post'>
					<label>".__("Name", "grassblade")."</label>
					<input type='text' name='actor_name' value='".$guest_name."'/>
					<label>".__("Email", "grassblade")."</label>
					<input type='text' name='actor_mbox' value='".$guest_mailto."'/>
					<input type='submit' name='submit' value='".__("Submit", "grassblade")."'/>
				</form>
				</div>
			";
			return $name_email_form;
		}
		else
		return  __( 'Please login.', 'grassblade' );
	}
	$actor = rawurlencode(json_encode($actor));

	if(!empty($auth))
	$auth = rawurlencode($auth);
	else
	$auth = rawurlencode("Basic ".base64_encode($user.":".$pass));

	$endpoint = !empty($endpoint) ? rawurlencode($endpoint) : "";

	if(!empty($activity_id))
	$activity = 'activity_id='.rawurlencode($activity_id);
	else
	$activity = '';

	$registration = grassblade_get_registration($registration, $activity_id, $actor_user);

	//$content_endpoint = "content_endpoint=".rawurlencode(dirname($src).'/');
	//$content_token = "content_token=".grassblade_gen_uuid();
	$noxapi_url_versions = apply_filters("noxapi_url_versions", array("none" => "No Tracking"), $version, $shortcode_atts, $attr);
	$noxapi_url_content_types = apply_filters("noxapi_url_content_types", array(), $version, $shortcode_atts, $attr);

	if(empty($src)) {
		//Don't change src if src is empty.
	}
	else if(isset($noxapi_url_versions[$version]) || isset($noxapi_url_content_types[$content_type]))
	{
		//if(!empty($video))
		//	$src = $src."&".$activity;

		if($content_type == "h5p" && !empty($completion_tracking)) { //For completion without sending statements to LRS
			$src .= (strpos($src,"?") !== false)? "&":"?";
			$src .= "actor=".$actor."&auth=&endpoint=&registration=".$registration."&".$activity;
		}

		if(!empty($actor_user_id) && in_array( $content_type, ['scorm', 'video'] ) ) {
			$src .= "&user_id=".$actor_user_id;
		}

		//Don't change SRC. Supporting Non xAPI Content.
		$endpoint = $auth = "";
	}
	else
	{
		$src .= (strpos($src,"?") !== false)? "&":"?";
		$src .= "actor=".$actor;
		if($version != "cmi5")
		$src .= "&auth=".$auth;
		$src .= "&endpoint=".$endpoint."&registration=".$registration."&".$activity;//."&".$content_endpoint."&".$content_token;
	}
	if(!empty($text))
		$text = sanitize_text_field( $text );

	if(!empty($link_button_image)) {
		$text = "<img src='". esc_url( $link_button_image )."' />";
	}
	$src = esc_url($src);

	$aspect_attr = !empty($aspect)? "data-aspect='".$aspect."'":"";

	$current_user = wp_get_current_user();
	if (empty($current_user->ID)) {
		$is_guest = true;
		$completed = false;
	} else {
		$is_guest = false;
		$completed = get_user_meta($current_user->ID, 'completed_'.$id, true);
		$completed = !empty($completed);
	}

	$completion_data = array(
						"registration" => $registration,
						"content_id" => $id,
						"completion_tracking" => $completion_tracking,
						"completion_type" => grassblade_xapi_content::get_completion_type($id),
						"activity_id" => $activity_id,
						"is_guest" => $is_guest,
						"content_type" => $content_type,
						"completion_without_lrs" => ($content_type == "h5p")? 1:"",
						"completed" => $completed
					);

	$completion_data = json_encode($completion_data);

	if( !empty($shortcode_atts["id"]) && in_array($target, ['_blank', '_self']) && !defined("GB_LAUNCH_REVEAL_CONTENT_URL") ) {
		$params = ['content_id' => str_replace("=", "", base64_encode($shortcode_atts["id"]))];

		if( !empty($_REQUEST['actor_mbox']) ) {
			$params['actor_mbox'] = strip_tags( $_REQUEST['actor_mbox'] );
			$params['actor_name'] = strip_tags( $_REQUEST['actor_name'] );
		}
		$src = admin_url('admin-ajax.php?action=grassblade_launch&t='.time().'&'.http_build_query($params));
	}

	if(empty($src) || !empty($remove_content))
		$return = ''; //Return blank if empty src
	else if($target == 'iframe') {
		$style = "margin:0; position: relative;";
		if( strpos($width, "v") !== false ) $style .= " width:$width;";
		if( strpos($height, "v") !== false ) $style .= " height:$height;";

		$return = "<iframe class='grassblade_iframe' data-completion='$completion_data' frameBorder='0' data-src='$src' ".$aspect_attr."  style='$style' width='$width' height='$height' webkitallowfullscreen mozallowfullscreen allowfullscreen allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' onLoad='if(typeof grassblade_xapi_content_autosize_content == \"function\") { grassblade_xapi_content_autosize_content();}'></iframe>";
	}
	else if($target == '_blank')
	$return = "<a class='grassblade_launch_link' data-completion='$completion_data' href='$src' target='_blank' onClick='return grassblade_launch_link_click(this);'>$text</a>";
	else if($target == '_self')
	$return = "<a class='grassblade_launch_link' data-completion='$completion_data' href='$src' target='_self'>$text</a>";
	else if($target == 'lightbox')
	{
		$return = grassblade_lightbox($src, $completion_data, $text,$width, $height, $aspect);
	}
	else
	$return = '';

	$params = array(
			"src" 	=> $src,
			"actor" => $actor,
			"auth"	=> $auth,
			"activity_id"	=> $activity_id,
			"registration"	=> $registration,
			"endpoint"	=> $endpoint,
			"auth"		=> $auth,
			"version"  	=> $version
		);
	$id_attr = empty($id)? "":"id='grassblade-".$id."'";

	$classes = 'grassblade';
	if (!empty($class)) {
		$additional_class = explode(" ", $class);
		in_array("grassblade", $additional_class)? '' : $class .= ' grassblade';
		$classes = $class;
	}

	$gb_remark = '';
	if($target == 'url')
	$return = $src;
	else {
		$gb_remark = '<div id="grassblade_remark'.$id.'"></div>';
		$return = "<div $id_attr class='$classes'>".$return."</div>";
	}
	return apply_filters("grassblade_shortcode_return", $return, $params, $shortcode_atts, $attr, $completion_data).$gb_remark;
}
function grassblade_scripts($page = "") {
	global $pagenow;

	$grassblade_add_scripts_on_page = array("grassblade-lrs-settings");
	$grassblade_add_scripts_on_page = apply_filters("grassblade_add_scripts_on_page", $grassblade_add_scripts_on_page);
	$page = !empty($page)? $page:(!empty($_GET["page"])? $_GET["page"]:"");

	if(!is_admin() || $pagenow == "post-new.php" || $pagenow == "post.php" || !empty($page) && in_array($page, $grassblade_add_scripts_on_page)) {
		wp_enqueue_script(
			'grassblade',
			plugins_url('/js/script.js', __FILE__),
			apply_filters('grassblade_scripts_dep', array('jquery')), GRASSBLADE_VERSION
		);

		if(is_admin() && !empty($page))
		wp_enqueue_script(
			'grassblade-search',
			plugins_url('/js/hilitor.js', __FILE__),
			array('jquery'), GRASSBLADE_VERSION
		);

		if(is_admin())
		wp_enqueue_script(
			'select2',
			plugins_url('/assets/select2/select2.full.min.js', __FILE__),
			array('jquery'), GRASSBLADE_VERSION
		);
	}

	global $post, $current_user;

	$content_post = apply_filters("grassblade_content_post", $post);
	$post_id = empty($content_post->ID)?'':$content_post->ID;
	$grassblade_settings = grassblade_settings();
	$lrs_exists = (!empty($grassblade_settings['endpoint']) && $grassblade_settings['endpoint'] != "/")? 1:"";

	$completion_tracking_enabled = grassblade_xapi_content::is_completion_tracking_enabled_by_post($post_id);
	$completion_tracking_enabled = apply_filters("grassblade_completion_tracking_enabled", $completion_tracking_enabled, $post_id);
	$completed = !empty($current_user->ID)? grassblade_xapi_content::post_contents_completed($post_id, $current_user->ID):false;
	$completed = !empty($completed);

	$gb_data = array('plugin_dir_url' => plugin_dir_url( __FILE__ ),
					 'is_admin' => is_admin(),
					 "is_guest" => (get_current_user_id() == 0),
					 "ajax_url" => admin_url('admin-ajax.php'),
					 "post_id" => $post_id,
					 "lrs_exists" => $lrs_exists,
					 "completion_tracking_enabled" => $completion_tracking_enabled,
					 "post_completion" => $completed,
					 "lang" => array(
					 	"confirm_reset_learner_progress" => __("Are you sure you want to reset progress on this content for all learners?", "grassblade")
					 )
					);
	$gb_data = apply_filters('grassblade_localize_script_data', $gb_data, $content_post);

	wp_localize_script( 'grassblade', 'gb_data',  $gb_data);
}
function grassblade_styles() {
	global $pagenow;

	wp_enqueue_style(
		'grassblade',
		plugins_url('/css/styles.css', __FILE__),
		null, GRASSBLADE_VERSION
	);
	if(!is_admin())
	wp_enqueue_style(
		'grassblade-frontend',
		plugins_url('/css/frontend-styles.css', __FILE__),
		null, GRASSBLADE_VERSION
	);

	$page = !empty($page)? $page:(!empty($_GET["page"])? $_GET["page"]:"");
	$grassblade_add_scripts_on_page = apply_filters("grassblade_add_scripts_on_page", array("grassblade-lrs-settings"));

	if(is_admin() && ($pagenow == "post-new.php" || $pagenow == "post.php" || !empty($page) && in_array($page, $grassblade_add_scripts_on_page)))
	wp_enqueue_style(
		'select2',
		plugins_url('/assets/select2/select2.min.css', __FILE__),
		null, GRASSBLADE_VERSION
	);
}

add_action("init", "grassblade_styles");
add_action("wp_print_scripts", "grassblade_scripts");
add_action("admin_enqueue_scripts", "grassblade_scripts");


function grassblade_lightbox($src, $completion_data, $text, $width, $height, $aspect = null) {
	$completion_data_lightbox = addslashes($completion_data);
	$return = '';
	$id = 'grassblade_'.md5($src);
	$return .= "<a class='grassblade_launch_link' data-completion='$completion_data' href='#' onClick='grassblade_show_lightbox(\"$id\", \"$src\", \"$completion_data_lightbox\", \"$width\", \"$height\", \"$aspect\"); return false;'>".$text."</a>";
	return $return;
}
function grassblade_reset_learner_progress( $content_id, $user_id = null) {
	$params = grassblade_xapi_content::get_params($content_id);

	if( empty($params["activity_id"]) || !empty($params["version"]) && $params["version"] == "none" )
		return false; //No content url or No tracking, stop here.

	$current_user_id = get_current_user_id();
	$current_time = time();
	$new_registration = grassblade_gen_uuid();

	if( empty($user_id) && !empty( $params["registration"] ) && trim($params["registration"]) != "auto") {
		$params["registration"] = $new_registration;
		grassblade_xapi_content::set_params($content_id, $params);
		// return $params["registration"];
	}
	$meta_key = "xapi_reg_".$params["activity_id"];
	global $wpdb;

	$sql = "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = %s";
	if(!empty($user_id))
	$results = $wpdb->get_results($wpdb->prepare($sql." AND user_id = %d", $meta_key, $user_id));
	else
	$results = $wpdb->get_results($wpdb->prepare($sql, $meta_key));

	$c = 0;

	if(!empty($results))
	foreach ($results as $key => $meta) {
		$user_id = $meta->user_id;
		$meta_value = maybe_unserialize($meta->meta_value);

		if(!empty($meta_value["latest"])) {
			if(empty($meta_value["registrations"]))
				$meta_value["registrations"] = array();

			$latest_reg = $meta_value["latest"];
			if(!empty($meta_value["registrations"][$latest_reg])) {
				$meta_value["registrations"][$latest_reg]["reset_by"] = $current_user_id;
				$meta_value["registrations"][$latest_reg]["reset_on"] = $current_time;
			}

			$meta_value["latest"] = $new_registration;
			$meta_value["registrations"][$new_registration] = array(
				"generated" => $current_time,
				"generated_by_reset_by" => $current_user_id
			);
			update_user_meta($user_id, $meta_key, $meta_value);
			$c++;
		}
	}

	if (empty($results) && !empty($user_id)) {
		$meta_value = [
			"latest" => $new_registration,
			"registrations" => [
				$new_registration => [
					"generated" => $current_time,
					"generated_by_reset_by" => $current_user_id
				]
			]
		];
		update_user_meta($user_id, $meta_key, $meta_value);
	}
	return true;
}
function grassblade_get_registration($registration, $activity_id, $current_user = null) {

	if(empty($current_user->ID))
		$current_user = wp_get_current_user();

	if(empty($current_user->ID))
		return grassblade_gen_uuid(); //Guest

	if( !empty($_GET["restart"]) ) {
		$content_id = grassblade_xapi_content::get_id_by_activity_id($activity_id);
		if( !empty($content_id) )
		grassblade_reset_learner_progress($content_id, $current_user->ID);
	}

	$r = get_user_meta($current_user->ID, "xapi_reg_".$activity_id, true);
	if(!empty($r["latest"])) { //Add restart=1 in url to reset resume
		$registration = strip_tags( $r["latest"] );
		return $registration;
	}
	else if( empty($registration) || $registration == "auto")
	{
		$registration = empty($registration)? "36fc1ee0-2849-4bb9-b697-71cd4cad1b6e" : grassblade_gen_uuid();

		if(empty($r))
		$r = array();

		if(empty($r["registrations"]))
		$r["registrations"] = array();

		$r["latest"] = $registration;
		$r["registrations"][$registration] = array("generated" => time());

		update_user_meta($current_user->ID, "xapi_reg_".$activity_id, $r);
	}
	else
	$registration = strip_tags( $registration );

	return $registration;
}
function grassblade_gen_uuid() {
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

		// 16 bits for "time_mid"
		mt_rand( 0, 0xffff ),

		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand( 0, 0x0fff ) | 0x4000,

		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand( 0, 0x3fff ) | 0x8000,

		// 48 bits for "node"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}
function grassblade_textdomain() {
 $plugin_dir = basename(dirname(__FILE__));
 load_plugin_textdomain( 'grassblade', $plugin_dir."/languages", $plugin_dir."/languages" );
}
add_action('plugins_loaded', 'grassblade_textdomain');



function grassblade_getdomain()
{

	if(!isset($_SERVER["HTTP_HOST"]) && !isset($_SERVER["SERVER_NAME"])) {
		return "no-domain";
	}

	if(isset($_SERVER["HTTP_HOST"]))
	$domain = $_SERVER["HTTP_HOST"];

	if(empty($domain) && isset($_SERVER["SERVER_NAME"]))
	$domain = $_SERVER["SERVER_NAME"];

	if(filter_var($domain, FILTER_VALIDATE_IP))
	return $domain.".com";
	else
	return $domain;
}
function grassblade_getactor($guest = false, $version = "1.0", $user = null, $actor_type = null)
{
	if(!empty($user->ID))
	$current_user = $user;
	else
	$current_user = wp_get_current_user();

	$actor = _grassblade_getactor($guest, $version, $current_user, $actor_type);
	return apply_filters("grassblade/getactor", $actor, $guest, $version, $current_user, $actor_type);
}
function _grassblade_getactor($guest = false, $version = "1.0", $user = null, $actor_type = null)
{
	if(!empty($user->ID))
		$current_user = $user;
	else
		$current_user = wp_get_current_user();

	if(empty($actor_type)) {
		 $grassblade_settings = grassblade_settings();
		 $actor_type = empty($grassblade_settings["actor_type"])? "mbox":$grassblade_settings["actor_type"];
	}

	if(empty($current_user->ID))
	{
		if(empty($guest))
			return false;
		else if(intval($guest) === 2) // Guest with Name and Email
		{
			if(empty($_REQUEST['actor_mbox']) || empty($_REQUEST['actor_name']) || filter_var($_REQUEST['actor_mbox'], FILTER_VALIDATE_EMAIL) === false)
				return false;

			$guest_name = strip_tags($_REQUEST['actor_name']);
			$guest_mailto = "mailto:".$_REQUEST['actor_mbox'];
			$accountName = $_REQUEST['actor_mbox'];

			if( $actor_type == "account" ) {
				$accountHomePage = get_site_url(null, '', 'http');

				if($version == "0.90")
				$actor = array('account' => array( array("accountServiceHomePage" => $accountHomePage, "accountName" => $accountName ) ), 'name' => array( $guest_name ), "objectType" =>  "Agent");
				else
				$actor = array('account' => array("homePage" => $accountHomePage, "name" => $accountName ), 'name' => $guest_name, "objectType" =>  "Agent");
			}
			else {
				if($version == "0.90")
					$actor = array('mbox' => array($guest_mailto), 'name' => array($guest_name), "objectType" =>  "Agent");
				else
					$actor = array('mbox' => $guest_mailto, 'name' => $guest_name, "objectType" =>  "Agent");
			}
			return $actor;
		}
		else
		{ // Guest with IP
			$guest_mailto = "mailto:guest-".$_SERVER['REMOTE_ADDR'].'@'.grassblade_getdomain();
			$guest_name = "Guest ".$_SERVER['REMOTE_ADDR'];
			$accountName = $_SERVER['REMOTE_ADDR'].'@'.grassblade_getdomain();

			if( $actor_type == "account" ) {
				$accountHomePage = get_site_url(null, '', 'http');

				if($version == "0.90")
				$actor = array('account' => array( array("accountServiceHomePage" => $accountHomePage, "accountName" => $accountName ) ), 'name' => array( $guest_name ), "objectType" =>  "Agent");
				else
				$actor = array('account' => array("homePage" => $accountHomePage, "name" => $accountName ), 'name' => $guest_name, "objectType" =>  "Agent");
			}
			else {
				if($version == "0.90")
					$actor = array('mbox' => array($guest_mailto), 'name' => array($guest_name), "objectType" =>  "Agent");
				else
					$actor = array('mbox' => $guest_mailto, 'name' => $guest_name, "objectType" =>  "Agent");
			}
			return $actor;
		}
	}

	$name = gb_name_format($current_user);

	if( $actor_type == "mbox" ) {
		$mbox = "mailto:".grassblade_user_email($current_user->ID);
		if($version == "0.90")
		$actor = array('mbox' => array($mbox), 'name' => array($name), "objectType" =>  "Agent");
		else
		$actor = array('mbox' => $mbox, 'name' => $name, "objectType" =>  "Agent");
	} else if( $actor_type == "account" ) {
		$accountHomePage = get_site_url(null, '', 'http');

		if($version == "0.90")
		$actor = array('account' => array( array("accountServiceHomePage" => $accountHomePage, "accountName" => (string) $current_user->ID ) ), 'name' => array( $name ), "objectType" =>  "Agent");
		else
		$actor = array('account' => array("homePage" => $accountHomePage, "name" => (string) $current_user->ID ), 'name' => $name, "objectType" =>  "Agent");
	}
	return $actor;
}
/*
* Get the agent_id of the user
* @param $actor - mixed - actor object or user_id
* @return string - agent_id
*/
function grassblade_get_actor_id($actor) {
	if( empty($actor) ) return '';

	if( is_numeric($actor) ) {
		$user_id = $actor;

		if(grassblade_settings('actor_type') != 'account')
		return grassblade_user_email($user_id);

		$user       = get_user_by('id', $user_id);
		$actor      = grassblade_getactor(false, '1.0', $user);
	}

	if(isset($actor["mbox"]) && is_array($actor["mbox"]) && is_string($actor["mbox"][0]))
		return str_replace("mailto:", "", $actor["mbox"][0]);
	if(isset($actor["mbox"]) && is_string($actor["mbox"]))
		return str_replace("mailto:", "", $actor["mbox"]);
	if(isset($actor["mbox_sha1sum"]))
		return $actor["mbox_sha1sum"];
	if(isset($actor["openid"]))
		return $actor["openid"];
	if(isset($actor["account"]) && is_string($actor["account"]["homePage"]) && is_string($actor["account"]["name"]))
		return $actor["account"]["homePage"]."/". ($actor["account"]["name"]);
}
add_shortcode("grassblade", "grassblade");

function grassblade_user_email($user_id) {
	$email = get_user_meta($user_id, "grassblade_email", true);
	if(!empty($email))
		return $email;
	$user = get_user_by("id", $user_id);
	update_user_meta($user_id, "grassblade_email", $user->user_email);
	return $user->user_email;
}
function grassblade_post_activityid($post_id) {
	if(empty($post_id))
		return '';

	$activity_id = get_post_meta($post_id, "xapi_activity_id", true);
	if(!empty($activity_id))
		return $activity_id;

	$activity_id = get_post_meta($post_id, "activity_id", true);
	if(!empty($activity_id))
		return $activity_id;

	$activity_id = get_permalink($post_id);
	update_post_meta($post_id, "activity_id", $activity_id);
	return $activity_id;
}

function get_user_by_grassblade_email($email) {
	global $wpdb;
	$email = trim($email);
	$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'grassblade_email'  AND meta_value = '%s' LIMIT 1", $email ));

	if(!empty($user_id))
	$user = get_user_by("id", $user_id);

	if(!empty($user))
		return $user;

	return get_user_by("email", $email);
}
function get_post_by_grassblade_activity_id($activity_id){

	if (empty($activity_id)) {
		return false;
	}

	$args = array (
	    'post_type'              => array( 'gb_xapi_content' ),
	    'post_status'            => array( 'publish' ),
	    'meta_query'             => array(
	        array(
	            'key'       => 'xapi_activity_id',
	            'value'     => $activity_id,
	        ),
	    ),
	);

	$xapi_contents = get_posts($args);

	if (!empty($xapi_contents)) {
		return $xapi_contents[0];
	}

	return false;
}
add_filter('grassblade_settings_fields', 'grassblade_add_custom_label_settings', 10,1);
function grassblade_add_custom_label_settings($fields){

	$custom_labels = apply_filters('grassblade_custom_labels_fields', array());

	if (!empty($custom_labels)) {
		$fields[] = array('id' => 'custom_label_setting', 'label' => __("Custom Label Settings", "grassblade"), "type" => "html", "subtype" => "field_group_start");
		foreach ($custom_labels as $custom_label) {
			$fields[] = $custom_label;
		}
		$fields[] = array('id' => 'custom_label_setting_end', 'label' => __("Custom Label Settings", "grassblade"), "type" => "html", "subtype" => "field_group_end");
	}
	return $fields;
}
add_filter('grassblade_settings_fields', 'grassblade_show_blacklist_urls', 10,1);
function grassblade_show_blacklist_urls($fields){
	global $grassblade_blacklist_statements_urls;

	if(!empty($grassblade_blacklist_statements_urls) && is_array($grassblade_blacklist_statements_urls))
	{
		$fields[] = array('id' => 'blacklist_urls', 'label' => __("Blacklisted URLs", "grassblade"), "type" => "html", "subtype" => "field_group_start");
		$html = "Statements with these URLs will not be sent to the LRS by GrassBlade:<br>".implode("<br>", array_map("htmlentities", $grassblade_blacklist_statements_urls))."<br><br>Statements sent from content is not restricted by this blacklist. You can update the list from wp-config.php file";
		$fields[] = array('id' => 'blacklist_urls_html', "type" => "html", "html" => $html);

		$fields[] = array('id' => 'blacklist_urls_end', 'label' => __("Blacklisted URLs", "grassblade"), "type" => "html", "subtype" => "field_group_end");
	}
	return $fields;
}
function grassblade_get_label($key, $default = "") {
	return grassblade_settings("label_".$key, $default);
}
function grassblade_settings($return = null, $default = null) {
	global $grassblade;

	if(empty($grassblade) || !is_array($grassblade))
	$grassblade = array();

	if(empty($grassblade["grassblade_settings"]) && method_exists($grassblade["grassblade_xapi_companion"], 'get_params'))
	$grassblade["grassblade_settings"] = $grassblade["grassblade_xapi_companion"]->get_params();

	$grassblade_settings = empty($grassblade["grassblade_settings"])? array():$grassblade["grassblade_settings"];
	$grassblade_settings = apply_filters("grassblade_settings", $grassblade["grassblade_settings"], $return);

	if(empty($return))
		return $grassblade_settings;
	else if(isset($grassblade_settings[$return]))
		return $grassblade_settings[$return];
	else
		return $default;
}
class grassblade_xapi_companion {
	public $debug = false;
	public $secure_token_options;
	public $fields;
	function __construct() {
		$this->secure_token_options = array(
					"" => __("None", "grassblade"),
					"9" => __("Low", "grassblade"), //User
					"11" => __("Medium", "grassblade"), //User + Activity
					"15" => __("High", "grassblade"), //User + Activity + IP
				);
	}
	function run() {
		add_action('admin_menu', array($this, 'admin_menu'), 0);
	}
	function define_fields() {
		if(!empty($this->fields))
			return $this->fields;
		// define the product metadata fields used by this plugin
		$versions = array(
					'1.0' => 'xAPI 1.0',
					'0.95' => 'xAPI 0.95',
					'0.9' => 'xAPI 0.9',
					'none' => 'No Tracking'
				);

		$completion_option = array(
									'hide_button' => __('Hide Button', "grassblade"),
									'hidden_until_complete' => __('Show button on completion', "grassblade"),
									'disable_until_complete' => __('Enable button on completion', "grassblade"),
									'completion_move_nextlevel' => __('Auto-redirect on completion', "grassblade"),
								);

		$track_guest_values = array(
					'' => __('No', 'grassblade'),
					'1' => __('Allow Guests', 'grassblade'),
					'2' => __('Allow Guests (ask Name/Email)', 'grassblade'),
				);
		$actor_types = array(
					"mbox"	=> "Name and Email",
					"account" => "Name and User ID"
				);
		$domain = grassblade_getdomain();
		$this->fields = array(
			array( 'id' => "lrs_settings", 'label' => __("LRS Settings", "grassblade"), "type" => "html", "subtype" => "field_group_start", "class" => ""),
			array( 'id' => 'endpoint', 'label' => __( 'Endpoint URL', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true , 'help' => __( 'provided by your LRS, check FAQ below for details', 'grassblade').". ".__("You might need to add a trailing slash (<b>/</b>) at the end of the url if its not already there.","grassblade")),
			array( 'id' => 'user', 'label' => __( 'API User', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'provided by your LRS, check FAQ below for details', 'grassblade')),
			array( 'id' => 'password', 'label' => __( 'API Password', 'grassblade' ),  'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'provided by your LRS, check FAQ below for details', 'grassblade')),
			array( 'id' => 'secure_tokens', 'label' => __( 'Secure Tokens', 'grassblade' ),   'placeholder' => '', 'type' => 'select', 'values'=> $this->secure_token_options, 'never_hide' => true ,'help' => __( 'Generates secure random tokens when launching xAPI Content.', 'grassblade')),
			array( 'id' => "lrs_settings_end", "type" => "html", "subtype" => "field_group_end"),
			array( 'id' => "content_settings", 'label' => __("Content Settings", "grassblade"), "type" => "html", "subtype" => "field_group_start"),
			array( 'id' => 'width', 'label' => __( 'Width', 'grassblade' ),  'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __('Default width of iframe/lightbox in which content is launched', 'grassblade')),
			array( 'id' => 'height', 'label' => __( 'Height', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __('Default height of iframe/lightbox in which content is launched', 'grassblade')),
			array( 'id' => 'completion_type', 'label' => __( 'Completion Type', 'grassblade' ),   'placeholder' => '', 'type' => 'select', 'values'=> $completion_option, 'never_hide' => true ,'help' => __('Default Completion on content', 'grassblade')),
			array( 'id' => 'version', 'label' => __( 'Version', 'grassblade' ),   'placeholder' => '', 'type' => 'select', 'values'=> $versions, 'never_hide' => true ,'help' => __( 'Default xAPI (Tin Can) version of content. Generally depends on your Authoring Tool.', 'grassblade')),
			array( 'id' => 'track_guest', 'label' => __( 'Track Guest Users', 'grassblade' ),  'placeholder' => '', 'type' => 'select', 'values'=> $track_guest_values, 'never_hide' => true ,'help' => sprintf(__('<b>Allow Guests:</b> Tracked as <b>{"name":"Guest XXX.XXX.XXX.XXX", "actor":{"mbox": "mailto:guest-XXX.XXX.XXX.XXX@%s"}</b>) where <i>XXX.XXX.XXX.XXX</i> is users IP. Not Logged In users will be able to access content, and their page views will also be tracked.  <br><b>Allow Guests (ask Name/Email):</b> Asks for Name and Email from not logged in user before taking content.', 'grassblade'), $domain)),
			array( 'id' => 'actor_type', 'label' => __( 'User Identifier', 'grassblade' ),  'placeholder' => '', 'type' => 'select', 'values'=> $actor_types, 'never_hide' => true ,'help' => __('This is the user identification information sent to the LRS.', 'grassblade')),
			array( 'id' => 'url_slug', 'label' => __( 'URL Slug', 'grassblade' ),  'placeholder' => 'gb_xapi_content', 'type' => 'text', 'values'=> '', 'never_hide' => true, 'help' => sprintf(__('This is part of the url of your xAPI Content page. Please visit Permalink Settings page %s if you see page not found error after update.', 'grassblade'), "<a href='".self_admin_url("options-permalink.php")."' target='_blank'>".__("here", "grassblade")."</a>") ),
			array( 'id' => 'disable_statement_viewer', 'label' => __( 'Disable Statement Viewer', 'grassblade' ),  'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => __('Disable Statement Viewer on xAPI Content Edit Page.', "grassblade")),
			array( 'id' => "content_settings_end", "type" => "html", "subtype" => "field_group_end"),
			array( 'id' => "upload_settings", 'label' => __("Upload Settings", "grassblade"), "type" => "html", "subtype" => "field_group_start"),
			array( 'id' => 'dropbox_app_key', 'label' => __( 'Dropbox APP Key', 'grassblade' ),  'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Required only if you want to upload xAPI Content from Dropbox, Work well with large file.', 'grassblade'). " (<a href='https://www.nextsoftwaresolutions.com/kb/direct-upload-of-tin-can-api-content-from-dropbox-to-wordpress-using-grassblade-xapi-companion/'  target='_blank'>".__('Get your Dropbox App Key, takes less than minutes','grassblade')."</a>)" ),
			array( 'id' => "upload_settings_end", "type" => "html", "subtype" => "field_group_end"),
		);
		$this->fields = apply_filters("grassblade_settings_fields", $this->fields);
	}
	function form() {
			global $post;
			$data = $this->get_params();

			$this->define_fields();
			do_action("grassblade_settings_form_start", $data);

		?>
			<div id="grassblade_xapi_settings_form"><table width="100%">
			<?php
				foreach ($this->fields as $f_i => $field) {
					$subtype = empty($field["subtype"])? "":$field["subtype"];
					$class = empty($field["class"])? "":$field["class"];
					$style = empty($field["style"])? "":$field["style"];
					$label = empty($field["label"])? "":$field["label"];
					$type = empty($field["type"])? "":$field["type"];
					$id = empty($field["id"])? "":$field["id"];
					if(!empty($_GET["open"]) && $_GET["open"] == $id || empty($_GET["open"]) && $f_i == 0)
					{
						$class .= " default_open";
					}
					if($type == "html" && $subtype == "field_group_start") {
						echo "<tr id='".$id."'><td colspan='2'  class='grassblade_field_group ".esc_attr( $class )."'>";
						echo "<div class='grassblade_field_group_label'><div class='dashicons dashicons-arrow-down-alt2'></div><span>".esc_html( $label )."</span></div>";
						echo "<div class='grassblade_field_group_fields' style='". esc_attr( $style )."'><table width='100%'>";
						continue;
					}
					if($type == "html" && $subtype == "field_group_end") {
						echo "</table></div></td></tr>";
						continue;
					}

					$value = isset($data[$field['id']])? $data[$field['id']]:'';
					echo '<tr id="field-'.esc_attr( $field['id'] ).'"><td width="20%" valign="top"><label for="'.esc_attr( $field['id'] ).'">'. esc_html( $label ).'</label></td><td width="100%">';
					switch ($type) {
						case 'html' :
							echo $field["html"];
						break;
						case 'text' :
							echo '<input  style="width:80%" type="text"  id="'.esc_attr( $field['id'] ).'" name="'.esc_attr( $field['id'] ).'" value="'.sanitize_text_field( $value ).'" placeholder="'.sanitize_text_field( $field['placeholder']).'" class="'.esc_attr( $class ).'"/>';
						break;
						case 'wp-color-picker':
							if(empty($value) && !empty($field['placeholder']))
								$value = $field['placeholder'];

							echo '<input class="wp-color-picker" type="text" data-default-color="'.esc_attr( $field['placeholder'] ).'"  id="'.esc_attr( $field['id'] ).'" name="'.esc_attr( $field['id'] ).'" value="'.sanitize_text_field( $value ).'" class="'.esc_attr( $class ).'"/>';
							break;
						case 'file' :
							echo '<input  style="width:80%" type="file"  id="'.esc_attr( $field['id'] ).'" name="'.esc_attr( $field['id'] ).'" value="'.sanitize_text_field( $value ).'" placeholder="'.sanitize_text_field( $field['placeholder']).'" class="'.esc_attr( $class ).'"/>';
						break;
						case 'number' :
							echo '<input  style="width:80%" type="number" id="'.esc_attr( $field['id'] ).'" name="'.esc_attr( $field['id'] ).'" value="'.sanitize_text_field( $value ).'" placeholder="'.sanitize_text_field( $field['placeholder'] ).'" class="'.esc_attr( $class ).'"/>';
						break;
						case 'textarea' :
							echo '<textarea   style="width:80%"  id="'.esc_attr( $field['id'] ).'" name="'.esc_attr( $field['id'] ).'" placeholder="'.sanitize_text_field(  $field['placeholder'] ).'" class="'.esc_attr( $class ).'">'.esc_textarea( $value ).'</textarea>';
						break;
						case 'checkbox' :
							$checked = !empty($value) ? ' checked=checked' : '';
							echo '<input type="checkbox" id="'.esc_attr( $field['id'] ).'" name="'.esc_attr( $field['id'] ).'" value="1"'.$checked.' class="'.esc_attr( $class ).'"/>';
						break;
						case 'select' :
							echo '<select id="'.esc_attr( $field ['id'] ).'" name="'.esc_attr( $field['id'] ).'" class="'.esc_attr( $class ).'">';
							foreach ($field['values'] as $k => $v) :
								$selected = ($value == $k && $value != '') ? ' selected="selected"' : '';
								echo '<option value="'.esc_attr( $k ).'"'.$selected.'>'.sanitize_text_field( $v ).'</option>';
							endforeach;
							echo '</select>';
						break;
						case 'select-multiple':

							echo '<select id="'.esc_html( $field['id'] ).'" name="'. esc_attr( $field['id'] ).'[]" multiple="multiple" class="'.esc_attr( $class ).'">';

							foreach ($field['values'] as $k => $v) :
								if(!is_array($value)) $value = (array) $value;
								$selected = (in_array($k, $value)) ? ' selected="selected"' : '';
								echo '<option value="'.esc_attr( $k ).'"'.$selected.'>'.sanitize_text_field( $v ).'</option>';
							endforeach;
							echo '</select>';

					}
					if(!empty($field['help'])) {
						echo '<br><small class="'.esc_attr( $class ).'-help">'. $field['help'] .'</small><br><br>';
						echo '</td></tr>';
					}
				}
				?>
				</table>
				<br>
			</div>
		<?php
		do_action("grassblade_settings_form_end", $data);
	}
	function set_params($id = null, $value = null) {
		if(!empty($id) && !is_null($value)) {
			update_option("grassblade_tincan_".$id, $value);
			return;
		}
		if( !isset($_POST[ "update_GrassBladeSettings" ]) )
			return;

    	$grassblade_settings_old = $this->get_params();

		$this->define_fields();
		foreach ($this->fields as $field) {
			if($field["type"] == "html")
				continue;
			$value = isset($_POST[$field["id"]])? $_POST[$field["id"]]:"";
			switch ($field["id"]) {
				case 'endpoint':
					$value = (empty($value) || $value == "/")? "":rtrim($value, '/') . '/';
					break;
				/*case 'track_guest':
					$value = (isset($_POST[$field["id"]]) && !empty($_POST[$field["id"]]))? 1:0;
					break;*/
				case 'width':
					$value = empty($value)? "940px":grassblade_xapi_companion::clean_width_height($value);
					break;
				case 'height':
					$value = empty($value)? "640px":grassblade_xapi_companion::clean_width_height($value);
					break;
				default:
					$value = is_array($value)? $value:trim($value);
					break;
			}
			update_option("grassblade_tincan_".$field["id"], $value);
		}
		global $grassblade;
		$grassblade_settings_new = $grassblade["grassblade_settings"] = $this->get_params();
		do_action("grassblade_settings_update", $grassblade_settings_old, $grassblade_settings_new);
	}
	function get_params($id = null) {
		if(!empty($id)) {
			return $this->maybe_migrate_field($id, get_option("grassblade_tincan_".$id));
		}

		$this->define_fields();
		$data = array();
		foreach ($this->fields as $key => $field) {
			if($field["type"] == "html")
				continue;
			$data[$field["id"]] = $this->maybe_migrate_field($field["id"], get_option("grassblade_tincan_".$field["id"]));

			if($field["id"] == "width") {
				$data[$field["id"]] = empty($data[$field["id"]])? "940px":grassblade_xapi_companion::clean_width_height($data[$field["id"]]);
			}
			else if($field["id"] == "height") {
				$data[$field["id"]] = empty($data[$field["id"]])? "640px":grassblade_xapi_companion::clean_width_height($data[$field["id"]]);
			}
		}
		return $data;
	}
	function maybe_migrate_field($field, $data) {
		if(!empty($data))
			return $data;

		if($field == "dropbox_app_key") {
			$dropbox_app_key = get_option("grassblade_dropbox_app_key");
			if(!empty($dropbox_app_key)) {
				update_option("grassblade_tincan_dropbox_app_key", $dropbox_app_key);
			//	delete_option("grassblade_dropbox_app_key");
			}
			return $dropbox_app_key;
		}
	}
	function admin_menu() {
	    add_menu_page("GrassBlade", "GrassBlade", "manage_options", "grassblade-lrs-settings", null, GRASSBLADE_ICON15, null);
	    add_submenu_page("grassblade-lrs-settings", __("GrassBlade Settings", "grassblade"), __("GrassBlade Settings", "grassblade"),'manage_options','grassblade-lrs-settings', array($this, 'menu_page') );
	}
	function menu_page() {
	    //must check that the user has the required capability
	    if (!current_user_can('manage_options'))
	    {
	      wp_die( __('You do not have sufficient permissions to access this page.','grassblade') );
	    }

	    if( isset($_POST[ "update_GrassBladeSettings" ]) ) {
	    	$this->set_params();
	        // Put an settings updated message on the screen
		?>
		<div class="updated"><p><strong><?php _e('settings saved.', 'grassblade' ); ?></strong></p></div>
		<?php
	    }
		?>
		<div>
		<form method="post" action="<?php echo admin_url("admin.php?page=grassblade-lrs-settings"); ?>">
		<h2><img style="top: 6px; position: relative;" src="<?php echo plugins_url('img/icon_30x30.png', __FILE__); ?>"/>
		<?php _e('GrassBlade Settings', 'grassblade'); ?></h2>
		<div id="grassblade_settings_form" class="grassblade_admin_wrap">
			<div class="grassblade-search">
				<input id="grassblade_setting_search" type="text" value="" placeholder="<?php _e("Type here to search settings", "grassblade"); ?>" autofocus>
			</div>
			<?php
			   echo $this->form();
			?>
			<input type="submit" class="button-primary" name="update_GrassBladeSettings" value="<?php _e('Update Settings', 'grassblade') ?>" />
			<?php
				do_action("grassblade_settings_form_end_2");
			?>
		</div>
		</form>
		</div>
		<?php
	}
	static function clean_width_height( $str ) {
		$str = strtolower( trim($str) );
		// separate digits and string
		$str = explode( " ", preg_replace('/([0-9]+)(.+)/i', '$1 $2', $str) );
		return intVal( $str[0] ).( in_array($str[1], ["px", "%", "vw", "vh"]) ? $str[1] : "px" );
	}

} //end of class
global $grassblade;
$grassblade["grassblade_xapi_companion"] = new grassblade_xapi_companion();
$grassblade["grassblade_xapi_companion"]->run();

function grassblade_connect() {
	$grassblade_settings = grassblade_settings();
	global $grassblade; //$xapi, $xapi_verbs;
	$grassblade["xapi"] = new NSS_XAPI($grassblade_settings["endpoint"], $grassblade_settings["user"], $grassblade_settings["password"]);
	$grassblade["xapi_verbs"] = new NSS_XAPI_Verbs();
}
add_action('init', 'grassblade_connect');

function grassblade_plugin_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=grassblade-lrs-settings">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

function grassblade_getverb($verb) {
	global $grassblade;
	return $grassblade["xapi_verbs"]->get_verb($verb);
}
function grassblade_getobject($id, $name, $description, $type, $objectType = 'Activity') {
	global $grassblade;
	return $grassblade["xapi"]->set_object($id, $name, $description, $type, $objectType);
}
function get_statement($atts) {
	global $grassblade;
	$shortcode_atts = shortcode_atts(array(
			'statementId' => '',
			'voidedStatementId' => '',
			'email' => '',
			'agent' => '',//Object
			'actor' => '',//Object
			'version' => null,
			'guest' => false,
			'verb' => '', //IRI
			'activity' => '', //IRI
			'object' => '', //Object
			'registration' => '', //UUID
			'show' => 'array',
			//'related_activities' => false,
			//'related_agents' => false,
			'since' => '', //Timestamp: Only Statements stored since the specified timestamp (exclusive) are returned.
			'until' => '', //Timestamp: Only Statements stored at or before the specified timestamp are returned.
			//'format' => 'exact', // ids, exact, canonical
			//'attachments' => false,
			//'ascending' => false,
			'context' => false,
			'authoritative' => false,
			'sparse' => false,
			'limit' => 1,
			), $atts);

	//extract($shortcode_atts);

	$show = $shortcode_atts['show'];
	unset($shortcode_atts['show']);

	if(empty($shortcode_atts['email'])) {
		$actor = grassblade_getactor($shortcode_atts['guest'], $shortcode_atts['version']);
	} else {
		$email = $shortcode_atts['email'];
		if( $email != "none" ) {
			$actor = array(
							"objectType" =>	"Agent",
							"mbox" => "mailto:".$email
						);
		}
	}
	if(!empty($actor))
		$shortcode_atts['actor'] = $actor;

	if(!empty($shortcode_atts['activity']) && isset($shortcode_atts['version']) && $shortcode_atts['version'] < "1.0") {
		$shortcode_atts['object'] = array(
			"id" => $shortcode_atts['activity'],
			"objectType" => "Activity"
			);
		unset($shortcode_atts['activity']);
	}
	unset($shortcode_atts['guest']);
	unset($shortcode_atts['version']);
	unset($shortcode_atts["email"]);

	foreach($shortcode_atts as $key=>$value) {
		if($value === "")
		unset($shortcode_atts[$key]);
	}

	$statements = $grassblade["xapi"]->GetStatements($shortcode_atts, 1);
	if(empty($statements['statements'][0]))
		return '';
	if($shortcode_atts['limit'] > 1)
	$statements = (array) $statements['statements'];
	else
	$statements = (array) $statements['statements'][0];

	if($show == '')
	return (array) $statements;
	if($show == 'array')
	return "<pre>".print_r($statements, true)."</pre>";

	$show = explode(".", $show);
	$value = (array) $statements;
	foreach($show as $key) {
		if(!isset($value[$key]))
			return "";
		$value = (is_object($value[$key]))? (array) $value[$key]:$value[$key];
	}
	return print_r($value, true);
}
add_shortcode("get_statement", "get_statement");

// Add settings link on plugin page
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'grassblade_plugin_settings_link' );

add_action('init', 'nss_plugin_updater_activate_grassblade');

function nss_plugin_updater_activate_grassblade()
{
	if(!class_exists('nss_plugin_updater'))
	require_once ('wp_autoupdate.php');

	$nss_plugin_updater_plugin_remote_path = 'https://license.nextsoftwaresolutions.com/';
	$nss_plugin_updater_plugin_slug = plugin_basename(__FILE__);

	new nss_plugin_updater ($nss_plugin_updater_plugin_remote_path, $nss_plugin_updater_plugin_slug);
}

/*** WYSIWYG Button ***/
/*add_action('init', 'add_grassblade_button');
function add_grassblade_button() {
   if ( current_user_can('edit_posts') &&  current_user_can('edit_pages') )
   {
     add_filter('mce_external_plugins', 'add_grassblade_plugin');
     add_filter('mce_buttons', 'register_grassblade_button');
   }
}*/

function grassblade_debug($msg) {
	if(isset($_GET['debug']) || defined('GB_DEBUG')) {
		$original_log_errors = ini_get('log_errors');
		$original_error_log = ini_get('error_log');
		ini_set('log_errors', true);
		ini_set('error_log', dirname(__FILE__).DIRECTORY_SEPARATOR.'debug.log');

		global $processing_id;
		if(empty($processing_id))
		$processing_id	= time();

		error_log("[$processing_id] ".print_r($msg, true)); //Comment This line to stop logging debug messages.

		ini_set('log_errors', $original_log_errors);
		ini_set('error_log', $original_error_log);
	}
}

function grassblade_send_statements($statements) {
	global $grassblade;
	if(empty($grassblade["xapi"]))
		return false;

	return $grassblade["xapi"]->SendStatements($statements);
}

/*function register_grassblade_button($buttons) {
   array_push($buttons, "grassblade");
   return $buttons;
}*/

/*function add_grassblade_plugin($plugin_array) {
   $plugin_array['grassblade'] = get_bloginfo('wpurl').'/wp-content/plugins/grassblade/js/grassblade_button.min.js';
   return $plugin_array;
} */
function grassblade_seconds_to_time($inputSeconds) {
    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = 24 * $secondsInAnHour;

    $return = "";
    // extract days
    $days = floor($inputSeconds / $secondsInADay);
    $return .= empty($days)? "":sprintf(__("%d day", "grassblade"), $days);

    // extract hours
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);
    $return .= (empty($hours) && empty($days))? "":" ".sprintf(__("%d hr", "grassblade"), $hours);

    // extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);
    $return .= (empty($hours) && empty($days) && empty($minutes))? "":" ".sprintf(__("%d min", "grassblade"), $minutes);

    // extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);
    $return .= " ".sprintf(__("%d sec", "grassblade"), $seconds);

    return trim($return);
}
function grassblade_add_to_content_box() {
	$post_types = get_post_types();
	foreach ($post_types as $post_type) {
		add_meta_box(
			'grassblade_add_to_content_box',
			__( 'xAPI Content', 'grassblade' ),
			'grassblade_add_to_content_box_content',
			$post_type,
			'side',
			'high'
		);
	}
}
function grassblade_add_to_content_save($post_id) {
	$post = get_post( $post_id);

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
	return;

	if ( !isset($_POST['grassblade_add_to_content_box_content_'.$post_id.'_nonce']) || !wp_verify_nonce( $_POST['grassblade_add_to_content_box_content_'.$post_id.'_nonce'], plugin_basename( __FILE__ ) ) )
	return;

	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
		return;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
		return;
	}

	if(isset($_POST['show_xapi_content']))
		update_post_meta($post_id, "show_xapi_content", $_POST['show_xapi_content']);
}
function grassblade_add_to_content_box_content() {
	global $post;
	wp_nonce_field( plugin_basename( __FILE__ ), 'grassblade_add_to_content_box_content_'.$post->ID.'_nonce' );

	if($post->post_type != "gb_xapi_content") {
		global $wpdb;

		$xapi_contents = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish' ORDER BY post_title ASC"); // get_posts("post_type=gb_xapi_content&orderby=post_title&posts_per_page=-1");
		$selected_id = get_post_meta($post->ID, "show_xapi_content", true);
		$onChange = apply_filters("grassblade_add_to_content_box_onchange", "alert('".__("You can add the shortcode [grassblade] in the content to change the placement of the content.","grassblade")."');", $post);
		?>
		<div id="grassblade_add_to_content">
		<b><?php _e("Add to Page:", "grassblade"); ?> <a href='<?php echo admin_url('post-new.php?post_type=gb_xapi_content'); ?>'><?php _e("Add New", "grassblade"); ?></a></b><br>
		<select name="show_xapi_content" id="show_xapi_content" onChange="<?php echo $onChange; ?>">
			<option value="0"> -- <?php _e("Select", "grassblade"); ?> -- </option>
			<?php
				foreach ($xapi_contents as $xapi_content) {
					$completion_tracking = grassblade_xapi_content::is_completion_tracking_enabled($xapi_content->ID);
					$selected = ($selected_id == $xapi_content->ID)? 'selected="selected"':''; ?>
					<option value="<?php echo $xapi_content->ID; ?>" completion-tracking="<?php echo $completion_tracking; ?>" <?php echo $selected; ?>><?php echo sanitize_text_field($xapi_content->post_title); ?></option>
			<?php } ?>
		</select>
		<a href="#" id="grassblade_add_to_content_edit_link" onClick="post_id = document.getElementById('show_xapi_content').value; if(post_id > 0) window.location = '<?php admin_url("post.php"); ?>?action=edit&message=1&post=' + post_id; return false;" style="<?php if(empty($selected_id)) echo 'display:none'; ?>"><?php _e("Edit", "grassblade"); ?></a>
		<br>
		<div>
			<div id="completion_tracking_enabled" style="display:none;">
				<a href="#" onClick="post_id = document.getElementById('show_xapi_content').value; if(post_id > 0) window.location = '<?php admin_url("post.php"); ?>?action=edit&message=1&post=' + post_id; return false;"><?php _e("Completion Tracking Enabled.", "grassblade"); ?></a>
				<?php do_action("grassblade_edit_extra_message", $post); ?>
			</div>
			<div id="completion_tracking_disabled" style="display:none;">
				<a href="#" onClick="post_id = document.getElementById('show_xapi_content').value; if(post_id > 0) window.location = '<?php admin_url("post.php"); ?>?action=edit&message=1&post=' + post_id; return false;"><?php _e("Completion Tracking Disabled.", "grassblade"); ?></a>
			</div>
			</a>
		</div>
		</div>
		<?php
	}
	else
	{
		$completion_tracking = grassblade_xapi_content::is_completion_tracking_enabled($post->ID);
		$xapi_contents = grassblade_xapi_content::get_posts_with_content($post->ID);

		if(empty($xapi_contents)) {
			 _e("This xAPI Content is not added to any post/page.", "grassblade");
			 if($completion_tracking) {
			 	echo "<div class='error'>".__("Completion Tracking enabled but xAPI Content not added to any page/post")."</div>";
			 }
		}
		else
		{
			echo "<b>".__("Added on:", "grassblade")."</b><div id='xapi_posts_list'><ul>";
			foreach($xapi_contents as $xapi_content) {
				echo "<li><a href='".get_edit_post_link($xapi_content->ID)."'>".$xapi_content->post_title."</a></li>";

			}
			echo "</ul></div>";

			if($completion_tracking) { ?>
				<div id="completion_tracking_enabled">
					<?php _e("Completion Tracking Enabled."); ?>
				</div>
			<?php } else { ?>
				<div id="completion_tracking_disabled">
					<?php _e("Completion Tracking Disabled."); ?>
				</div>
			<?php
			}
		}
	}
}
function grassblade_get_post_with_content($content_id) {
	_deprecated_function(__FUNCTION__, "4.1.7", "grassblade_xapi_content::get_posts_with_content");
	return grassblade_xapi_content::get_posts_with_content($content_id);
}
function grassblade_add_to_content_post($content) {
	global $post;
	if(empty($post->ID))
        return $content;
	$selected_id = get_post_meta($post->ID, "show_xapi_content", true);
	$selected_id = apply_filters("grassblade_add_to_content_post", $selected_id, $post, $content);

	// Regex: match id="grassblade-<id>" or id='grassblade-<id>' with optional spaces
	$pattern = '/\bid\s*=\s*(["\'])\s*grassblade-' . preg_quote((string) $selected_id, '/') . '\s*\1/i';
	if(!empty($selected_id) && !preg_match($pattern, $content)) {
		 $has_access  = apply_filters( "grassblade_has_access", true, $selected_id, $post, wp_get_current_user());
		 if(empty($has_access))
		 	return $content;

		if(strpos($content, "[grassblade]") === false)
		$content .= do_shortcode('[grassblade id='.$selected_id."]");
		else
		$content = str_replace("[grassblade]", do_shortcode('[grassblade id='.$selected_id."]"),	$content);
	}
	return $content;
}
function grassblade_file_get_contents_curl($url, $timeout = 10) {
        $url = str_replace(" ", "%20", $url);
        $url = str_replace("(", "%28", $url);
        $url = str_replace(")", "%29", $url);
        $ch = curl_init();
        $userAgent = !empty($_SERVER["HTTP_USER_AGENT"])? $_SERVER["HTTP_USER_AGENT"]:"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)";
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        if(!ini_get('safe_mode') && !ini_get('open_basedir'))
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        $timeout = empty($timeout)? 10:$timeout;
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $connection_timeout = 5;
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connection_timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
}
function grassblade_url_exists($url) {
	$url = str_replace(" ", "%20", $url);
	$url = str_replace("(", "%28", $url);
	$url = str_replace(")", "%29", $url);

	$ch = curl_init($url);
	$userAgent = !empty($_SERVER["HTTP_USER_AGENT"])? $_SERVER["HTTP_USER_AGENT"]:"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)";
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $httpCode == 200;
}
function grassblade_sanitize_filename($file) {
	return preg_replace('/[^A-Za-z0-9\-\_\.]/', '', $file);
}
function gb_ie_version() {
	preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
	if(count($matches)<2){
	  preg_match('/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $_SERVER['HTTP_USER_AGENT'], $matches);
	}

	if (count($matches)>1){
	  //Then we're using IE
	  $version = $matches[1];
	  return $version;
	}

	return 0;
}

add_action( 'add_meta_boxes', 'grassblade_add_to_content_box');
add_filter( 'the_content', 'grassblade_add_to_content_post', 2, 1);
add_action( 'save_post', 'grassblade_add_to_content_save');
add_action( 'admin_notices', 'grassblade_admin_notice_handler');

function grassblade_error_check() {
	global $post;
    if(!empty($_POST))
    return;

	if( !empty($_GET['page']) && $_GET['page'] == "grassblade-lrs-settings" || !empty($_GET['post_type']) && $_GET['post_type'] == "gb_xapi_content" || !empty($post) && $post->post_type == "gb_xapi_content" ) {
		$site_url = get_site_url();
		if(strpos('a'.$site_url, "https://")) {
			$grassblade_settings = grassblade_settings();
			if( strpos('a'.$grassblade_settings["endpoint"], "http://" ) ) {
	    		$grassblade_settings_page = get_admin_url(null,'admin.php?page=grassblade-lrs-settings');
	            grassblade_admin_notice( sprintf("You are using SSL/https for your website, but http for your LRS. Please change the LRS endpoint url to https. If you do not have SSL installed on your LRS, you might need to do that first. %s: ", "<a href='".$grassblade_settings_page."'>Update LRS Endpoint</a>" ));
			}
		}
	}
}
add_action( 'admin_head', 'grassblade_error_check', 200);
// Display any errors
function grassblade_admin_notice_handler() {

	$errors = get_option('grassblade_admin_errors');

	if($errors) {
		echo '<div class="grassblade_errors error"><p>' . $errors . '</p></div>';
		 update_option('grassblade_admin_errors', false);
	}

}
function grassblade_admin_notice($message, $type = "error") {
	update_option('grassblade_admin_errors', $message);
}
/*** WYSIWYG Button ***/


/** Plugins Filter for GrassBlade **/
add_filter( 'views_plugins', 'grassblade_plugins');

/**
 * Add 'GrassBlade' tab into views of plugins manage.
 *
 * @since 3.0.0
 *
 * @param array $views
 *
 * @return array
 */
function grassblade_plugins( $views ) {
	global $s;

	$search          = get_grassblade_addons();
	$count_activated = 0;

	if ( $active_plugins = get_option( 'active_plugins' ) ) {
		if ( $search ) {
			foreach ( $search as $k => $v ) {
				if ( in_array( $k, $active_plugins ) ) {
					$count_activated ++;
				}
			}
		}
	}

	if ( $s && false !== stripos( $s, 'grassblade' ) ) {
		$views['grassblade-plugin'] = sprintf( '<a href="%s" class="current">%s <span class="count">(%d/%d)</span></a>', admin_url( 'plugins.php?s=grassblade' ), __( 'GrassBlade', 'grassblade' ), $count_activated, sizeof( $search ) );
	} else {
		$views['grassblade-plugin'] = sprintf( '<a href="%s">%s <span class="count">(%d/%d)</span></a>', admin_url( 'plugins.php?s=grassblade' ), __( 'GrassBlade', 'grassblade' ), $count_activated, sizeof( $search ) );
	}

	return $views;
}

function get_grassblade_addons() {
	$all_plugins = apply_filters( 'all_plugins', get_plugins() );

	return array_filter( $all_plugins, 'grassblade_plugins_search_callback' );
}

/**
 * Callback function for searching plugins have 'grassblade' inside.
 *
 * @since 3.0.0
 *
 * @param array $plugin
 *
 * @return bool
 */
function grassblade_plugins_search_callback( $plugin ) {
	foreach ( $plugin as $value ) {
		if ( is_string( $value ) && false !== stripos( strip_tags( $value ), 'grassblade' ) ) {
			return true;
		}
	}

	return false;
}
/** Plugins Filter for GrassBlade **/

function gb_other_ids($statement) {
	return gb_context_ids($statement, "other");
}
function gb_parent_ids($statement) {
	return gb_context_ids($statement, "parent");
}
function gb_grouping_ids($statement) {
	return gb_context_ids($statement, "grouping");
}
function gb_category_ids($statement) {
	return gb_context_ids($statement, "category");
}
function gb_context_ids($statement, $id_type) {
	//$id_type = parent, grouping, category, other
	$categories = array();
	if(!empty($statement->{"context"}->contextActivities->{$id_type})) {
		$categories = array();
		$category = $statement->{"context"}->contextActivities->{$id_type};
		if(is_array($category)) {
			foreach($category as $p) {
				if(!empty($p->id))
				$categories[] = $p->id;
			}
		}
		else
		if(!empty($category->id))
			$categories[] = $category->id;
	}
	return $categories;
}
function gb_generate_time() {
	$milliseconds = round(microtime(true) * 1000);
	$timestamp =  floor($milliseconds/1000);
	$milli_part = $milliseconds - $timestamp*1000;
	$d = new DateTime(date("Y-m-d H:i:s", $timestamp));
	$d->setTimezone(new DateTimeZone("UTC"));
	$tz = $d->format(DateTime::ATOM);
	$tz = str_replace("+00:00", ".".$milli_part."Z", $tz);
	return $tz;
}
function gb_file_exists($file) {
	$file = explode("?", $file);
	return file_exists($file[0]);
}
add_filter("grassblade_send_statements", function($statements) {
	global $grassblade_blacklist_statements_urls;

	/* Add configration in wp-config.php like:
		$GLOBALS["grassblade_blacklist_statements_urls"] = array(
			"http://domain.com/course/math-101/",
			"http://domain.com/course/math-101/",
		);
	*/
	if(empty($statements) || empty($grassblade_blacklist_statements_urls) || !is_array($grassblade_blacklist_statements_urls))
		return $statements;

	$statements_string = stripslashes( json_encode($statements) );
	foreach($grassblade_blacklist_statements_urls as $url) {
		if(strpos($statements_string, $url) !== false)
			return;
	}

	return $statements;
}, 10, 1);

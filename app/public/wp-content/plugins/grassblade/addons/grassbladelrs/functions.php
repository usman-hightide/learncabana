<?php
if ( ! defined( 'ABSPATH' ) ) exit;

//require_once(dirname(__FILE__)."/../nss_xapi.class.php");
require_once(dirname(__FILE__)."/lrstest.php");
add_action( 'wp_ajax_nopriv_grassblade_completion_tracking', 'grassblade_grassbladelrs_process_triggers' );
add_action( 'wp_ajax_grassblade_completion_tracking', 'grassblade_grassbladelrs_process_triggers' );

add_action( 'wp_ajax_nopriv_grassblade_xapi_track', 'grassblade_grassbladelrs_xapi_track' );
add_action( 'wp_ajax_grassblade_xapi_track', 'grassblade_grassbladelrs_xapi_track' );

add_action('admin_menu', 'grassblade_grassbladelrs_menu', 1);
function grassblade_grassbladelrs_menu() {
	add_submenu_page("grassblade-lrs-settings", "GrassBlade LRS", "GrassBlade LRS",'manage_options','grassbladelrs-settings', 'grassblade_grassbladelrs_menupage');
}
function grassblade_show_trigger_debug_messages($msg) {
	if(!empty($_REQUEST["action"]) && in_array($_REQUEST["action"], array("grassblade_completion_tracking", "grassblade_xapi_track"))) {
		echo "\n";
		print_r($msg);
		echo "\n";
	}
}
function grassblade_grassbladelrs_menupage()
{
   //must check that the user has the required capability
	if (!current_user_can('manage_options'))
	{
	  wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	$grassblade_settings = grassblade_settings();
	$endpoint = $grassblade_settings["endpoint"];

	$url_parts = parse_url(get_site_url());
	$params = array(
			"api_user" => $grassblade_settings["user"],
			"api_pass" => $grassblade_settings["password"],
			"t" 	   => time(),
			"domain"	=> $url_parts["host"]
		);

	$sso_auth = grassblade_file_get_contents_curl($endpoint."?".http_build_query($params));
	if(!empty($_GET['test'])) {
		echo $endpoint."?".http_build_query($params)."<br>Direct Response: ";
		print_r($sso_auth);
	}
	$invalid_access = (strpos($sso_auth, "Invalid Access") > -1);
	$sso_auth = json_decode($sso_auth);
	if(!empty($sso_auth))
	if(!empty($sso_auth->sso_auth_token))
		$sso_auth_token = $sso_auth->sso_auth_token;
	else if(!empty($sso_auth->response))
	{
		global $wpdb;
		$sso_auth = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'grassblade_sso_auth' LIMIT 1");
		if(!empty($sso_auth))
		$sso_auth = maybe_unserialize($sso_auth);

		if(!empty($sso_auth))
		if(is_object($sso_auth) && !empty($sso_auth->sso_auth_token))
			$sso_auth_token = $sso_auth->sso_auth_token;
		else
		if(is_array($sso_auth) && !empty($sso_auth["sso_auth_token"]))
			$sso_auth_token = $sso_auth["sso_auth_token"];

		if(!empty($_GET['test'])) {
			echo "<br>Received Stored Token: <br>";
			print_r($sso_auth);
		}
	}


	if(!empty($sso_auth_token)) {
		$grassblade_lrs_launch_url = apply_filters("grassblade_lrs_launch_url", $endpoint."?sso_auth_token=".$sso_auth_token, $endpoint, $sso_auth_token);
		?>
		<div class="wrap">
		<a class="button-primary" style="margin-bottom: 10px" href="<?php echo str_replace("xAPI/", "", $endpoint); ?>" target="_blank"><?php _e("Open In New Window", "grassblade"); ?></a><br>
		<iframe width="100%" height="1000px" src="<?php echo $grassblade_lrs_launch_url; ?>"></iframe>
		</div>
		<?php
	}
	else {
	?>
		<div class=wrap>
		<h2><img style="top: 6px; position: relative;" src="<?php echo plugins_url('img/icon_30x30.png', dirname(dirname(__FILE__))); ?>"/>
		GrassBlade LRS</h2>
		<br>
		<?php
			if($invalid_access) {
				$hosts = array($_SERVER['SERVER_ADDR']);
				if(!in_array($_SERVER['HTTP_HOST'], $hosts))
					$hosts[] = $_SERVER['HTTP_HOST'];
				if(!in_array($_SERVER['SERVER_NAME'], $hosts))
					$hosts[] = $_SERVER['SERVER_NAME'];

				$url = str_replace("xAPI/", "Configure/Integrations#tab_2", $endpoint);
				echo sprintf(__("Your GrassBlade LRS did not authorize your request. Try adding your WordPress IP and domain:  %s in your %s ", "grassblade"), "<input id='ip_select' onClick='jQuery(this).select();' value='".implode(",",  $hosts)."' />", "<a href='".$url."'' target='_blank'>".__("GrassBlade LRS > Configure > Integrations > SSO", "grassblade")."</a>") ;
			}
			else
			echo sprintf(__("Please install %s and configure the API credentials to use this LRS Management Page", "grassblade"), "<a href='https://www.nextsoftwaresolutions.com/grassblade-lrs-experience-api/' target='_blank'>GrassBlade LRS</a>");
		?>
		</div>
	<?php
	}
}

function grassblade_grassbladelrs_xapi_track() {
	if(empty($_REQUEST["grassblade_trigger"]))
		return;

	if(empty($_REQUEST["statement"]) || empty($_REQUEST["objectid"]) || empty($_REQUEST["agent_id"]))
	{
		grassblade_show_trigger_debug_messages( "Incomplete Data" );
		exit;
	}
	$statement = stripcslashes($_REQUEST["statement"]);
	//$statement_array = json_decode($statement);
	$objectid = urldecode(stripcslashes($_REQUEST["objectid"]));
	$objectid = explode("#", $objectid);
	$objectid = $objectid[0];
	$grassblade_xapi_content = new grassblade_xapi_content();
	$xapi_content_id = $grassblade_xapi_content->get_id_by_activity_id($objectid);
	if(empty( $xapi_content_id)) {
		grassblade_show_trigger_debug_messages( "Activity [".$objectid."] not linked to any content" );
		exit;
	}

	//$email = rawurldecode(stripcslashes($_REQUEST["agent_id"]));
	$user = grassblade_get_statement_user($statement);// get_user_by_grassblade_email($email);
	if(empty($user->ID)) {
		grassblade_show_trigger_debug_messages( "Unknown user: ".print_r($statement, true) );
		exit;
	}

	$statement = apply_filters("grassblade_xapi_tracked_pre", $statement, $xapi_content_id, $user);
	if(!empty($statement)) {
	   // update_user_meta($user->ID, "completed_".$xapi_content_id, $statement);
		do_action("grassblade_xapi_tracked", $statement, $xapi_content_id, $user);
	}
	grassblade_show_trigger_debug_messages( "Processed ".$xapi_content_id );
}
add_action("parse_request", "grassblade_grassbladelrs_process_triggers");
function grassblade_grassbladelrs_process_triggers() {
	if(empty($_REQUEST["grassblade_trigger"]) || empty($_REQUEST["grassblade_completion_tracking"]))
		return;

	if(empty($_REQUEST["statement"]) || empty($_REQUEST["objectid"]) || empty($_REQUEST["agent_id"]))
	{
		grassblade_show_trigger_debug_messages( "Incomplete Data" );
		exit;
	}
	$statement = stripcslashes($_REQUEST["statement"]);
	//$statement_array = json_decode($statement);
	$objectid = urldecode(stripcslashes($_REQUEST["objectid"]));
	$grassblade_xapi_content = new grassblade_xapi_content();
	$xapi_content_id = $grassblade_xapi_content->get_id_by_activity_id($objectid);
	$statement_json = json_decode($statement);

	if(empty($xapi_content_id)) {
		grassblade_show_trigger_debug_messages( "Activity [".$objectid."] not linked to any content" );
		$grouping_ids = gb_grouping_ids($statement_json);

		if(!empty($grouping_ids) && !empty($grouping_ids[0])) {
			$objectid = $grouping_ids[0];
			$xapi_content_id = $grassblade_xapi_content->get_id_by_activity_id($objectid);
			$params = grassblade_xapi_content::get_params($xapi_content_id);
			if(empty($params["completion_by_module"])) {
				unset($xapi_content_id);
				exit();
			}
			else
			{
				grassblade_show_trigger_debug_messages( " Completion on Module Completion [using grouping id: ".$objectid."]" );
			}
		}
		else
		{
			exit();
		}
	}

	if(empty($statement_json->timestamp)) {
		$statement_json->timestamp = $statement_json->stored = gb_generate_time();
		$statement = json_encode($statement_json);
	}
	if(empty( $xapi_content_id)) {
		grassblade_show_trigger_debug_messages( "Activity [".$objectid."] not linked to any content" );
		exit;
	}

	//$email = rawurldecode(stripcslashes($_REQUEST["agent_id"]));
	$user = grassblade_get_statement_user($statement);// get_user_by_grassblade_email($email);
	if(empty($user->ID)) {
		grassblade_show_trigger_debug_messages( "Unknown user: ".print_r($statement, true) );
		exit;
	}

	$statement = apply_filters("grassblade_completed_pre", $statement, $xapi_content_id, $user);
	if(!empty($statement)) {
		$completed = apply_filters("grassblade_mark_complete", true, $_REQUEST, $statement, $xapi_content_id, $user->ID);
		if($completed) {
			grassblade_show_trigger_debug_messages( " Mark content completed " );
			update_user_meta($user->ID, "completed_".$xapi_content_id, $statement);
		}
		do_action("grassblade_completed", $statement, $xapi_content_id, $user);
	}
	grassblade_show_trigger_debug_messages( "Completion Processed ".$xapi_content_id );
	exit;
}
add_filter("grassblade_mark_complete", function($return, $data, $statement_json, $xapi_content_id, $user_id) {
	$statement = json_decode($statement_json);
	$xapi_content = get_post_meta($xapi_content_id, "xapi_content", true);
	$score_and_status = gb_get_statement_score_and_status($statement, $xapi_content["passing_percentage"] ?? null);

	return strtolower($score_and_status["status"]) != "failed";
}, 10, 5);
function grassblade_get_statement_user($statement) {
	if (!is_string($statement))
		$statement = json_encode($statement);

	$statement = json_decode($statement);

	if(empty($statement) || empty($statement->actor))
		return false;

	return grassblade_get_user_from_actor($statement->actor);
}
function grassblade_get_user_from_actor($actor) {
	if(empty($actor))
		return false;

	if( !empty($actor->account) ) {
		$homePage = @$actor->account->homePage;
		$site_homePage = get_site_url(null, '', 'http');
		if($homePage == $site_homePage) {
			$user_id = @$actor->account->name;
			if(!empty($user_id))
			return get_user_by("id", $user_id);
		}
		else
			grassblade_show_trigger_debug_messages('Mismatch in actor.account.homePage: '.$homePage." != ".$site_homePage);
	}
	if(!empty($actor->mbox)) {
		$mbox = is_array($actor->mbox)? $actor->mbox[0]:$actor->mbox;
		$email = str_replace("mailto:", "", $mbox);
		$user = get_user_by_grassblade_email($email);
		if(!empty($user->ID))
			return $user;
	}
	return false;
}
add_action("grassblade_completed", "grassblade_lrs_update_registration", 10, 3);
function grassblade_lrs_update_registration($statement_json, $xapi_content_id, $user) {
	if(empty($xapi_content_id))
		return;

	$grassblade_xapi_content = new grassblade_xapi_content();
	$xapi_content = $grassblade_xapi_content->get_params($xapi_content_id);
	if(empty($xapi_content["activity_id"]) || !empty($xapi_content["registration"]) && $xapi_content["registration"] != "auto" )
		return;

	$statement = json_decode($statement_json);
	$activity_id = $xapi_content["activity_id"];
	$r = get_user_meta($user->ID, "xapi_reg_".$activity_id, true);
	$registration = grassblade_gen_uuid();

	if(empty($r["latest"]))
	{
	if(empty($r))
	$r = array();

	if(empty($r["registrations"]))
	$r["registrations"] = array();

	$r["latest"] = $registration;
	$r["registrations"][$registration] = array("generated" => time());
	}
	else
	{
		$r["latest"] = $registration;
		if(!empty($statement->registration) && !empty($r["registrations"][$statement->registration])) {
			$r["registrations"][$statement->registration]["completed"] = $statement_json;
			$r["registrations"][$statement->registration]["completed_timestamp"] = $statement->timestamp;
		}

		if(empty($r["registrations"]))
			$r["registrations"] = array();

		$r["registrations"][$registration] = array(
												"generated" => time()
											);
	}

	update_user_meta($user->ID, "xapi_reg_".$activity_id, $r);
}
add_action("grassblade_xapi_tracked", "grassblade_xapi_attempted_tracked", 10, 3);
function grassblade_xapi_attempted_tracked($statement_json, $xapi_content_id, $user) {
	if(empty($xapi_content_id))
		return;

	$grassblade_xapi_content = new grassblade_xapi_content();
	$xapi_content = $grassblade_xapi_content->get_params($xapi_content_id);
	if(empty($xapi_content["activity_id"]))
		return;

	$statement = json_decode($statement_json);
	$activity_id = $xapi_content["activity_id"];
	$r = get_user_meta($user->ID, "xapi_reg_".$activity_id, true);
	$registration = @$statement->registration;

	if(isset($r["registrations"][$registration]) && empty($r["registrations"][$registration]["started_timestamp"])) {
		$r["registrations"][$registration]["started_timestamp"] = $statement->timestamp;
		$r["updated_by"] = "grassblade_xapi_attempted_tracked";
		update_user_meta($user->ID, "xapi_reg_".$activity_id, $r);
	}

	$last_attempted = array( "statement" => $statement, "timestamp" => time() );
	update_user_meta($user->ID, "last_attempted", $last_attempted);
}
add_action("grassblade_completed", "grassblade_lrs_store_completion", 10, 3);
function grassblade_lrs_store_completion($statement_json, $xapi_content_id, $user) {
		$user_id = $user->ID;
		$statement = json_decode($statement_json);
		$xapi_content = get_post_meta($xapi_content_id, "xapi_content", true);
		$score_and_status = gb_get_statement_score_and_status($statement, $xapi_content["passing_percentage"] ?? null);

		$duration = gb_get_value($statement, "result.duration", null);
		$timespent = $score_and_status["timespent"];

		$registration = gb_get_value($statement, "context.registration", "");
		$timestamp = !empty($statement->timestamp)? strtotime($statement->timestamp):time();

		$data = array(
				"content_id" => $xapi_content_id,
				"user_id" => $user_id,
				"percentage" => $score_and_status["percentage"],
				"status" => $score_and_status["status"],
				"score" => $score_and_status["score"],
				"statement" => $statement_json,
				"registration" => $registration,
				"timespent" => $timespent,
				"timestamp" => date("Y-m-d H:i:s", $timestamp),
			);
		$data = apply_filters("grassblade_completions_data", $data);
		if(!empty($data)) {
			global $wpdb;
			$insert = true;

			if(!empty($statement->id)) {
				$found = $wpdb->get_results($wpdb->prepare("SELECT `statement` FROM {$wpdb->prefix}grassblade_completions WHERE user_id='%d' AND content_id='%d' AND status='%s' AND registration='%s'", $data["user_id"], $data["content_id"], $data["status"], $data["registration"]));

				if(!empty($found)) {
					foreach ($found as $completion) {
						$s = json_decode($completion->statement);
						if(!empty($s) && !empty($s->id) && $s->id == $statement->id) {
							grassblade_show_trigger_debug_messages(" Existing statement (".$s->id.") not stored again. ");
							$insert = false;
						}
					}
				}
			}
			if ($insert) {
				$inserted = $wpdb->insert($wpdb->prefix . "grassblade_completions", $data);
				if ( $inserted === false && !empty($wpdb->last_error) ) {
					$error_code = $wpdb->last_error;
					grassblade_show_trigger_debug_messages("Error: " . $error_code);

					// Check if the error is a duplicate entry error
					// TODO: Remove this content in next version
					if ( strpos( $error_code, 'Duplicate entry' ) !== false ) {
						$removed_index = $wpdb->query("ALTER TABLE {$wpdb->prefix}grassblade_completions DROP INDEX unique_statement");
						if ( $removed_index === false )
							grassblade_show_trigger_debug_messages("Error: " . $wpdb->last_error);
					}
				}
			}
		}
}
add_action('delete_user', 'delete_grassblade_data');

function delete_grassblade_data($user_id) {
	global $wpdb;
	if (!empty($user_id) && is_numeric($user_id)) {
		$wpdb->delete($wpdb->prefix."grassblade_completions", array('user_id' => $user_id));
	}
	return true;
}
/*
add_action('delete_post', 'delete_xapi_content', 10);
function delete_xapi_content($post_id) {
	if (!empty($post_id) && is_numeric($post_id)) {
		delete_post_meta($post_id, "xapi_activity_id");
		delete_post_meta($post_id, "xapi_content");
	}
	return true;
}
*/

add_filter( 'authenticate',  'grassblade_rest_api_authenticate', 20, 3);
add_filter( 'determine_current_user',  'grassblade_rest_api_auth_handler', 15, 1);
function grassblade_rest_api_auth_handler( $input_user ) {

	if ( ! empty( $input_user ) ) {
		return $input_user;
	}

	$headers = getallheaders();
	$token = gb_get_element($headers, "X-GB-Token");

	$api_request = ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	if ( ! apply_filters( 'application_password_is_api_request', $api_request ) && !empty( $token ) ) {
		return $input_user;
	}

	/* Check route to confirm if we want to change thing only on our rest route */
	if(!defined("GRASSBLADE_FULL_REST_API_ACCESS")) {
		if ( ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$path = $GLOBALS['wp']->query_vars['rest_route'];
		} else {
			$path = $_SERVER['REQUEST_URI'];
		}
		$request_method = !empty($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD']:"";
		$request = new \WP_REST_Request( $request_method, $path );
		$request->set_body_params( wp_unslash( $_POST ) ); // phpcs:ignore

		$is_grassblade_rest_api = (strpos($request->get_route(), "/grassblade/") !== false);
		if( ! $is_grassblade_rest_api )
			return $input_user;
	}
	/* Check route to confirm if we want to change thing only on our rest route */

	if(empty($token))
		$token = gb_get_element($_REQUEST, "X-GB-Token");

	if(!empty($token)) {
		$token = base64_decode($token);
		if(!empty($token))
		$token = explode(".", $token);

		if(!empty($token) && count($token) == 3) {
			$t 		= $token[1];
			//grassblade_debug( base64_decode($token[0]).":".$t.":".base64_decode($token[2]));

			if( $t > time() - 3600 && $t < time() + 3600 ) {
				//grassblade_debug( "time in range" );
				$user 		= base64_decode($token[0]);
				$pass 		= base64_decode($token[2]);
			}
		}
	}

	if ( empty($user)  || empty($pass) ) {

		if( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$user = $_SERVER['PHP_AUTH_USER'];
			$pass = $_SERVER['PHP_AUTH_PW'];
		}
		else
		{
			if(!empty($headers["Authorization"]))
				$auth = $headers["Authorization"];
			else if(!empty($_SERVER["REMOTE_USER"]))
				$auth = $_SERVER["REMOTE_USER"];
			else if(!empty($_SERVER["REDIRECT_REMOTE_USER"]))
				$auth = $_SERVER["REDIRECT_REMOTE_USER"];
			else if(!empty($_SERVER["HTTP_AUTHORIZATION"]))
				$auth = $_SERVER["HTTP_AUTHORIZATION"];
			else if(!empty($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]))
				$auth = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];

			if(empty($auth))
				return $input_user;

			$auth = explode(":", base64_decode( str_replace("Basic ", "", $auth) ) );
			$user = @$auth[0];
			$pass = @$auth[1];
		}

	}

	// Check that we're trying to authenticate
	if ( empty($user)  || empty($pass) ) {
		return $input_user;
	}

	$user = grassblade_rest_api_authenticate( $input_user, $user, $pass );

	if ( $user instanceof WP_User ) {
		//grassblade_debug("Authenticated User: ".$user->ID);
		return $user->ID;
	}

	// If it wasn't a user what got returned, just pass on what we had received originally.
	return $input_user;
}
if( !function_exists('getallheaders') )
{
	function getallheaders()
	{
	   $headers = [];
	   foreach ($_SERVER as $name => $value)
	   {
		   if (substr($name, 0, 5) == 'HTTP_')
		   {
			   $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
		   }
	   }
	   return $headers;
	}
}
function grassblade_rest_api_authenticate($input_user, $username, $password ) {
	$headers = getallheaders();
	$token = gb_get_element($headers, "X-GB-Token");

	$api_request = ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	if ( ! apply_filters( 'application_password_is_api_request', $api_request ) && !empty( $token ) ) {
		return $input_user;
	}

	/* Check route to confirm if we want to change thing only on our rest route */
	if(!defined("GRASSBLADE_FULL_REST_API_ACCESS")) {
		if ( ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$path = $GLOBALS['wp']->query_vars['rest_route'];
		} else {
			$path = $_SERVER['REQUEST_URI'];
		}
		$request_method = !empty($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD']:"";
		$request = new \WP_REST_Request( $request_method, $path );
		$request->set_body_params( wp_unslash( $_POST ) ); // phpcs:ignore

		$is_grassblade_rest_api = (strpos($request->get_route(), "/grassblade/") !== false);
		if( ! $is_grassblade_rest_api )
			return $input_user;
	}
	/* Check route to confirm if we want to change thing only on our rest route */

	$user = get_user_by( 'login',  $username );

	if(empty($user))
	$user = get_user_by( 'email',  $username );

	// If the login name is invalid, short circuit.
	if ( ! $user ) {
		return $input_user;
	}

	if(wp_check_password($password, $user->user_pass, $user->ID)) {
	   return $user;
	}

	/*
	 * Strip out anything non-alphanumeric. This is so passwords can be used with
	 * or without spaces to indicate the groupings for readability.
	 *
	 * Generated application passwords are exclusively alphanumeric.
	 */

	$password = preg_replace( '/[^a-z\d]/i', '', $password );

	$hashed_passwords = WP_Application_Passwords::get_user_application_passwords( $user->ID );

	// If there aren't any, there's nothing to return.  Avoid the foreach.
	if ( empty( $hashed_passwords ) ) {
		return $input_user;
	}

	foreach ( $hashed_passwords as $key => $item ) {
		if ( wp_check_password( $password, $item['password'], $user->ID ) ) {
			$error = new WP_Error();
			do_action( 'wp_authenticate_application_password_errors', $error, $user, $item, $password );
			if ( is_wp_error( $error ) && $error->has_errors() ) {
				/** This action is documented in wp-includes/user.php */
				do_action( 'application_password_failed_authentication', $error );

				return $error;
			}
			WP_Application_Passwords::record_application_password_usage( $user->ID, $item['uuid'] );
			do_action( 'application_password_did_authenticate', $user, $item );
			$item['last_used'] = time();
			$item['last_ip']   = $_SERVER['REMOTE_ADDR'];
			$hashed_passwords[ $key ] = $item;
			update_user_meta( $user->ID, 'grassblade_application_passwords', $hashed_passwords );

			return $user;
		}
	}


	// By default, return what we've been passed.
	return $input_user;
}
/**
 * Prevent caching of unauthenticated status.
 */
add_filter( 'wp_rest_server_class', 'grassblade_wp_rest_server_class' );
function grassblade_wp_rest_server_class( $class ) {
	global $current_user;
	if ( defined( 'REST_REQUEST' )
		 && REST_REQUEST
		 && $current_user instanceof WP_User
		 && 0 === $current_user->ID ) {
		/*
		 * For our authentication to work, we need to remove the cached lack
		 * of a current user, so the next time it checks, we can detect that
		 * this is a rest api request and allow our override to happen.  This
		 * is because the constant is defined later than the first get current
		 * user call may run.
		 */
		$current_user = null;
	}
	return $class;
}

add_action("grassblade_settings_form_start", "gblrs_grassblade_settings_form_start", 10, 1);
function gblrs_grassblade_settings_form_start($data) {
	if(!empty($_POST["grassblade_lrs_autoconfig"])) {
		$fields = array("api_user", "api_pass", "api_endpoint");
		$settings = array();

		foreach ($_POST as $key => $value) {
			if(in_array($key, $fields))
				$settings[$key] = strip_tags($value);
		}

		?>
		<div class='grassblade_lrs_settings_update'><?php _e("Endpoint URL, API User and API Password are updated here from GrassBlade LRS. Please click <b>Update Settings</b> below to accept the settings, or close the page to reject it."); ?></div>
		<div class='grassblade_lrs_settings_update2'><?php _e("Only fields in <span class='field_yellow'>Yellow</span> have changed."); ?></div>
		<script type="text/javascript">
			jQuery(window).on("load", function() {
				var settings = <?php echo json_encode($settings); ?>;

				if(jQuery("#endpoint").val() != settings.api_endpoint) {
					jQuery("#endpoint").val(settings.api_endpoint);
					jQuery("#endpoint").css("background-color", "yellow");
				}
				if(jQuery("#user").val() != settings.api_user) {
					jQuery("#user").val(settings.api_user);
					jQuery("#user").css("background-color", "yellow");
				}
				if(jQuery("#password").val() != settings.api_pass) {
					jQuery("#password").val(settings.api_pass);
					jQuery("#password").css("background-color", "yellow");
				}

				jQuery(".grassblade_field_group_label,[id^=field-],#grassblade_setting_search").hide();
				jQuery("#field-endpoint,#field-user,#field-password").show();
			});
		</script>
		<?php
	}
	else if(!empty($_GET["autoconfig"])) {
		?>
		<div class='grassblade_lrs_settings_update'><?php _e("Looks like we might have lost the settings data sent from GrassBlade LRS? If yes, please go back and click the Auto configure button once again."); ?></div>
		<?php
	}
}

//do_action("grassblade_settings_update", $grassblade_settings_old, $grassblade_settings_new);

add_action("grassblade_settings_update", "grassblade_settings_update_statement", 10, 2);
function grassblade_settings_update_statement($old, $new) {
	global $grassblade;
	$grassblade["grassblade_settings"] = $new;

	$grassblade_tincan_endpoint = $new["endpoint"];
	$grassblade_tincan_user = $new["user"];
	$grassblade_tincan_password = $new["password"];
	$grassblade_tincan_track_guest = $new["track_guest"];

	if(empty($grassblade_tincan_endpoint))
		return;

	$user = wp_get_current_user();

	$xapi = new NSS_XAPI($grassblade_tincan_endpoint, $grassblade_tincan_user, $grassblade_tincan_password);
	$actor = grassblade_getactor($grassblade_tincan_track_guest, "1.0", $user);

	if(empty($actor))
	{
		grassblade_debug("No Actor. Shutting Down.");
		return;
	}

	$settings_changes = array();
	foreach ($new as $key => $value) {
		if( $old[$key] != $new[$key] ) {
			if($key == "password") {
				$to = empty($new[$key])? "":str_repeat('*', max(0, strlen($new[$key]) - 4)) . substr($new[$key], -4);
				$from = empty($old[$key])? "":str_repeat('*', max(0, strlen($old[$key]) - 4)) . substr($old[$key], -4);

				$settings_changes[$key] = array("to" => $to , "from" => $from  );
			}
			else
			$settings_changes[$key] = array("to" => $new[$key], "from" => $old[$key]);
		}
	}

	if( empty($settings_changes) )
		return;

	$post_url = get_bloginfo('wpurl')."/wp-admin/admin.php?page=grassblade-lrs-settings";
	$post_title = "GrassBlade Settings";
	$context_extensions = array(
						"http://nextsoftwaresolutions.com/xapi/extensions/user-info" =>  array(
							"user-agent" =>  $_SERVER['HTTP_USER_AGENT'],
							"user-ip" =>   $_SERVER['REMOTE_ADDR'],
							"user-port" => $_SERVER['REMOTE_PORT'],
						),
						"http://nextsoftwaresolutions.com/xapi/extensions/referer" =>  (isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER']:""),
						"http://www.nextsoftwaresolutions.com/xapi/extensions/changed"=> $settings_changes,
					);

	$xapi->set_verb('updated');
	$xapi->set_actor_by_object($actor);
	$xapi->set_context_extensions($context_extensions);
	$xapi->set_object($post_url, $post_title, null, '','Activity');
	$statement = $xapi->build_statement();

	$ret = $xapi->SendStatements(array($statement));
}

add_action("grassblade_settings_form_end_2", "gblrs_grassblade_settings_form_end_2");

function gblrs_grassblade_settings_form_end_2() {
	$grassblade_settings = grassblade_settings();

	_e('Don\'t have an LRS? ','grassblade') ?><a href='https://www.nextsoftwaresolutions.com/learning-record-store' target="_blank"><?php _e(' Find an LRS','grassblade'); ?></a><?php

	if( empty($_GET["autoconfig"]) && !empty($grassblade_settings["endpoint"]) && !empty($grassblade_settings["user"]) && !empty($grassblade_settings["password"]) ) {
		?>
		<div class="button-lrstest" style="float: right;" onclick="grassblade_lrstest_start()"><?php _e("LRS Connection Test", "grassblade") ?></div>
		<?php
		grassblade_lrstest_screen();
	}
}
function grassblade_lrstest_screen() {
	wp_enqueue_script( 'grassblade-lrstest', plugins_url('/lrstest.js', __FILE__), array('jquery'), GRASSBLADE_VERSION );
	wp_enqueue_script( 'grassblade-xapiwrapper', plugins_url('/js/xapiwrapper.js', dirname(dirname(__FILE__)) ), array('jquery'), GRASSBLADE_VERSION );
	wp_localize_script( 'grassblade-lrstest', 'grassblade_lrstest', array( "siteurl" => get_bloginfo("url") ) );
	$grassblade_settings = grassblade_settings();

	$config = array(
		"endpoint"  => $grassblade_settings["endpoint"],
		"auth"      => "Basic ".base64_encode( $grassblade_settings["user"].":".$grassblade_settings["password"] ),
		"actor"     => array('mbox' => "mailto:test@gblrs.com", 'name' => "LRS TEST", "objectType" =>  "Agent"),
		"timestamp" => time(),
		"activityId" => "http://gblrs.com/lrstest",
		'strictCallbacks' => true
	);
	include(dirname(__FILE__)."/lrstest-template.php");
}

function grassblade_lrs_sso_auth( $d ) {
	$params = array();

	if(isset($_REQUEST["sso_auth"]))
		$return = array("success" => update_option("grassblade_sso_auth", $_REQUEST["sso_auth"]));
	else
		$return = array("success" => false);

	return json_encode($return);
}
add_action( 'rest_api_init', function () {
	register_rest_route( 'grassblade/v1', '/sso_auth', array(
		'methods' => 'GET',
		'callback' => 'grassblade_lrs_sso_auth',
		'permission_callback' => function () {
			return current_user_can( 'connect_grassblade_lrs' ) ||  current_user_can( 'manage_options' );
		}
	) );
} );

function grassblade_lrs_get_user_meta( $d ) {
	$params = array();

	if(empty($_REQUEST["meta_key"]) || !is_string($_REQUEST["meta_key"]))
		$return = array("success" => false);

	$request_meta_key = sanitize_title(trim($_REQUEST["meta_key"]));
	if(!empty($_REQUEST["actor"]))
	{
		$actor = json_decode($_REQUEST["actor"]);
		$user = grassblade_get_user_from_actor($actor);
		if(empty($user->ID)) {
			$return = array("success" => true, "data" => array());
		}
		else {
			if(isset($user->{$request_meta_key}))
				$meta_value = $user->{$request_meta_key};
			else
				$meta_value = get_user_meta($user->ID, $request_meta_key, true);

			$grassblade_email = get_user_meta($user->ID, "grassblade_email", true);
			if(empty($grassblade_email))
				$grassblade_email = $user->user_email;

			$data = array();
			$data[] = array(
				"id" => $user->ID,
				"email" => $grassblade_email,
				$request_meta_key => $meta_value
			);
			return array("success" => true, "data" => $data);
		}
	}
	else
	{
		global $wpdb;
		$users = $wpdb->get_results("SELECT ID, user_email FROM $wpdb->users");
		foreach ($users as $key => $value) {
			$users[$value->ID] = $value->user_email;
		}
		$test_user = get_user_by("id", $value->ID);

		$grassblade_emails = $wpdb->get_results("SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = 'grassblade_email'");
		foreach ($grassblade_emails as $key => $value) {
			$grassblade_emails[$value->user_id] = $value->meta_value;
		}


		if(isset($test_user->{$request_meta_key})) {
			$meta_values = $wpdb->get_results("SELECT ID as `user_id`, `$request_meta_key` as meta_value FROM $wpdb->users");
		}
		else {
			$meta_values = $wpdb->get_results($wpdb->prepare("SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = '%s'", $request_meta_key));
		}

		$data = array();
		foreach ($meta_values as $key => $value) {
			$user_id = $value->user_id;
			$grassblade_email = !empty($grassblade_emails[$user_id])? $grassblade_emails[$user_id]:$users[$user_id];
			if(!is_string($grassblade_email))
				continue;

			if($request_meta_key == "user_email" && $grassblade_email == $value->meta_value && (empty($_REQUEST["return"]) || $_REQUEST["return"] != "all") ) //Don't send value if meta_key = user_email is requested and it is same as grassblade_email
			continue;

			$data[] = array(
				"id" 	=> $user_id ,
				"email" => $grassblade_email,
				$request_meta_key => $value->meta_value
			);
		}
		return array("success" => true, "data" => $data);
	}
	return json_encode($return);
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'grassblade/v1', '/get_user_meta', array(
		'methods' => 'GET',
		'callback' => 'grassblade_lrs_get_user_meta',
		'permission_callback' => function () {
			return current_user_can( 'connect_grassblade_lrs' ) ||  current_user_can( 'manage_options' );
		}
	) );
} );

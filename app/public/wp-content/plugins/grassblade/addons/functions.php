<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* UTC time to WordPress local time in WordPress configured format */
function gb_datetime( $time, $date_time_format = null ) {
	global $grassblade;
	if(empty($date_time_format)) {
		if(empty($grassblade["date_time_format"])) {
			$grassblade["date_format"] = get_option( 'date_format' );
			$grassblade["time_format"] = get_option( 'time_format' );
			$grassblade["date_time_format"] = $grassblade["date_format"] . " " . $grassblade["time_format"];
		}

		$date_time_format = $grassblade["date_time_format"];
	}

	if(!is_numeric($time))
	$time = strtotime($time);

	$date = function_exists("wp_date")? wp_date( $date_time_format, $time ):date_i18n( $date_time_format, $time );
	return $date;
}

function gb_date( $time, $date_format = null ) {
	global $grassblade;
	if(empty($date_format)) {
		if(empty($grassblade["date_format"])) {
			$grassblade["date_format"] = get_option( 'date_format' );
		}

		$date_format = $grassblade["date_format"];
	}
	return gb_datetime( $time, $date_format );
}

function gb_array_push( $array, $insert, $after = "", $before = "") {
	if(!is_array($array))
	return $array;
	$new_array = array();
	$inserted = false;

	if(empty($after) && empty($before)) {
		$value = reset($insert);
		$key = key($insert);
		if(empty($key))
		$array[] = $value;
		else
		$array[$key] = $value;
		return $array;
	}

	foreach($array as $k => $v) {
		if( !$inserted && !empty($before) && $before == $k) {
			$new_array = gb_array_push($new_array, $insert);
		}

		$new_array[$k] = $v;

		if( !$inserted && !empty($after) && $after == $k) {
			$new_array = gb_array_push($new_array, $insert);
		}
	}
	if(!$inserted) {
		$new_array = gb_array_push($new_array, $insert);
	}
	return $new_array;
}
function gb_get_json_script($object_name, $l10n) {
	if ( is_string( $l10n ) ) {
		$l10n = html_entity_decode( $l10n, ENT_QUOTES, 'UTF-8' );
	} else {
		foreach ( (array) $l10n as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		}
	}
	$script = "/* <![CDATA[ */ \n";
	$script .= "var $object_name = " . wp_json_encode( $l10n ) . ';';
	$script .= "\n /* ]]> */ \n";
	return "<script>\n ".$script." \n</script>";
}
function gb_get_scripts( $scripts ) {
	if(empty($scripts) || !is_array($scripts))
	return "";

	ob_start();
	foreach($scripts as $s) {
		if(isset($s["file"]) && file_exists($s["file"])) {
			echo " \n ";
			include_once ($s["file"]);
			echo " \n ";
		}
		if(!empty($s["script"]))
			echo " \n ". $s["script"]. " \n ";
	}
	$script = " <script>\n ".ob_get_clean()." \n</script> ";
	return $script;
}
function gb_sanitize_data( $data, $f = "sanitize_key", $f_key = "sanitize_key" ) {
	if(!function_exists($f) || !function_exists($f_key))
		return $data;

	if(!is_array($data) && !is_object($data)) {
		return call_user_func( $f, $data );
	}
	$new_data = array();
	foreach($data as $k => $v) {
		$k = call_user_func($f_key, $k);
		$new_data[$k] = gb_sanitize_data($v, $f, $f_key);
	}
	return $new_data;
}
function gb_seconds_to_time($inputSeconds) {
	if( empty($inputSeconds) )
		return "";

	$secondsInAMinute = 60;
	$secondsInAnHour  = 60 * $secondsInAMinute;
	$secondsInADay    = 24 * $secondsInAnHour;

	$return = "";
	// extract days
	$days = floor($inputSeconds / $secondsInADay);
	$return .= empty($days)? "":$days."day";

	// extract hours
	$hourSeconds = round( $inputSeconds ) % $secondsInADay;
	$hours = floor($hourSeconds / $secondsInAnHour);
	$return .= (empty($hours) && empty($days))? "":" ".$hours."hr";

	// extract minutes
	$minuteSeconds = $hourSeconds % $secondsInAnHour;
	$minutes = floor($minuteSeconds / $secondsInAMinute);
	$return .= (empty($hours) && empty($days) && empty($minutes))? "":" ".$minutes."min";

	// extract the remaining seconds
	$remainingSeconds = $minuteSeconds % $secondsInAMinute;
	$seconds = ceil($remainingSeconds);
	$return .= " ".$seconds."sec";

	return trim($return);
}
/*
* @since 5.2
*/
function gb_name_format($user, $format = null, $format2 = "user_login") {
	if(is_numeric($user))
	$user = get_user_by("id", $user);

	if(empty($user->ID))
	return "";

	if(is_null($format))
	$format = grassblade_settings("reports_name_format", "display_name");

	if(empty($format))
	return "";

	if($format == $format2)
	$format2 = "";

	$format = strtolower($format);

	if(strpos($format, ",")) {
		$multi_format = array_map("trim", explode(",", $format));
		$name_array = array();
		foreach($multi_format as $format) {
			$n = gb_name_format($user, $format, "");

			if(!empty($n))
			$name_array[] = $n;
		}

		return !empty($name_array)? implode(", ", $name_array):gb_name_format($user, $format2);
	}
	switch($format) {
		case "id";
			return $user->ID;
		case "user_login";
			return !empty($user->user_login)? $user->user_login:gb_name_format($user, $format2);
		case "user_email";
			return !empty($user->user_email)? $user->user_email:gb_name_format($user, $format2);
		case "display_name";
			return !empty($user->display_name)? $user->display_name:gb_name_format($user, $format2);
		case "last_name";
			if( !empty($user->last_name) )
			return $user->last_name;

			$last_name = get_user_meta($user->ID, "last_name", true);
			return !empty($last_name)? $last_name:gb_name_format($user, $format2);
		case "first_name";
			if( !empty($user->first_name) )
			return $user->first_name;

			$first_name = get_user_meta($user->ID, "first_name", true);
			return !empty($first_name)? $first_name:gb_name_format($user, $format2);
	}
	return "";
}
function gb_strtotime_utc( $date_time_str  ) { 	//str provided in EST (or whatever configured)
	$time = strtotime( $date_time_str );	//time based on EST
	$time_utc = 2*$time - strtotime( wp_date("Y-m-d H:i:s", $time) );
	return $time_utc;
}
function gb_users_with_email($user_ids = array(), $grassblade_email = false)
{
	if( !is_array( $user_ids ) )
	return array();

	$users = array();
	if (!empty($user_ids))
	foreach ($user_ids as $user_id) {
		if(  $grassblade_email )
		$email = grassblade_user_email($user_id);
		else {
			$user = get_user_by("id", $user_id);
			$email = empty($user->user_email)? "":$user->user_email;
		}
		$users[$user_id] = $email;
	}

	return $users;
}

function gb_get_element($array, $key, $default = "") {
	if( is_object($array) )
	$array = (array) $array;

	if( is_array($array) ) {
		if( isset($array[$key]) )
		return $array[$key];

		foreach($array as $k => $v) {
			if(strtolower($k) == strtolower($key))
			return $v;
		}
	}
	else
	return $default;
}

function gb_user_search($user_search) {
	$user = null;
	if( is_numeric($user_search) )
		$user = get_user_by('id', intVal($user_search));

	$user_search = trim($user_search);
	if( empty($user->ID) && strpos($user_search, "@") > 0 )
		$user = get_user_by("email", $user_search);

	if( empty($user->ID) )
		$user = get_user_by("login", $user_search);

	return $user;
}
function gb_get_value($array_or_object, $dot_separated_key, $default = "") {
	if(!isset($array_or_object))
	return $default;

	if( !is_array($array_or_object) && !is_object($array_or_object) ) {
		return $default;
	}

	if( is_array($array_or_object) && isset($array_or_object[$dot_separated_key]) ) {
		return $array_or_object[$dot_separated_key];
	}

	if( is_object($array_or_object) && isset($array_or_object->{ $dot_separated_key }) ) {
		return $array_or_object->{ $dot_separated_key };
	}

	$keys = explode(".", $dot_separated_key, 2);
	if( is_object($array_or_object) && isset($array_or_object->{ $keys[0] }) ) {
		return gb_get_value($array_or_object->{ $keys[0] }, $keys[1], $default);
	}

	if( is_array($array_or_object) && isset($array_or_object[$keys[0]]) )
	{
		return gb_get_value($array_or_object[$keys[0]], $keys[1], $default);
	}
	else
	return $default;
}
function gb_get_statement_score_and_status( $statement, $passing_percentage = null ) {
	if( is_string($statement) )
	$statement = json_decode($statement);

	$passed_text = __("Passed", "grassblade");
	$failed_text = __("Failed", "grassblade");
	$completed_text = __("Completed", "grassblade");

	$scaled = gb_get_value($statement, "result.score.scaled", null);// isset($statement->result->score->scaled) ? $statement->result->score->scaled : null;
	$raw 	= gb_get_value($statement, "result.score.raw", null); //isset($statement->result->score->raw) ? $statement->result->score->raw : null;
	$max 	= gb_get_value($statement, "result.score.max", 100 ); //!empty($statement->result->score->max) ? $statement->result->score->max : null;
	$min 	= gb_get_value($statement, "result.score.min", 0); //!empty($statement->result->score->min) ? $statement->result->score->min : 0;
	$completion = gb_get_value($statement, "result.completion", null); //!empty($statement->result->completion) ? $statement->result->completion : null;
	$success = gb_get_value($statement, "result.success", null); //!empty($statement->result->success) ? $statement->result->success : null;

	$raw = (isset($raw) && isset($min) && $raw < $min)? $min:$raw; // Fix raw score less than min score
	$raw = (isset($raw) && isset($max) && $raw > $max)? $max:$raw; // Fix raw score more than max score

	$verbs = [
		"http://adlnet.gov/expapi/verbs/passed" => "passed",
		"http://adlnet.gov/expapi/verbs/completed" => "completed",
		"http://adlnet.gov/expapi/verbs/failed" => "failed",
	];
	$verb = gb_get_value($statement, "verb.id", "");
	$verb = isset($verbs[$verb])? $verbs[$verb]:"";
	$default_score = in_array($verb, ["completed", "passed"]) && $success !== false? 100:0;

	$score 		= isset($raw)? $raw:(isset($scaled)? $scaled*100:$default_score);
	$percentage = isset($scaled) ? $scaled*100:((!empty($max) && isset($raw)) ? ($raw - $min)*100/($max - $min):($raw === 0? 0 : $default_score) );
	$percentage = round($percentage, 2);

	if(isset($passing_percentage) && is_numeric($passing_percentage) && trim($passing_percentage) != "") {
		$status = ($percentage >= $passing_percentage)? "Passed":"Failed";
		$messsge = " Status (".$percentage." >= ".$passing_percentage."): ".$status;
	}
	else
	if( isset( $success ) )
	$status 	= !empty( $success ) ? "Passed" : "Failed";
	else
	$status 	= ucwords($verb);

	grassblade_show_trigger_debug_messages( !empty($messsge)? $messsge : " Status: ".$status );
	$duration = gb_get_value($statement, "result.duration", null); //isset($statement->result->duration)? $statement->result->duration:null;
	$timespent = isset($duration)? grassblade_duration_to_seconds($duration):null;

	return array("score" => $score, "percentage" => $percentage, "timespent" => $timespent, "status" => $status);
}

function gb_get_xml_dom($xmlfile)
{
    $dom = new DomDocument;
    $dom->preserveWhiteSpace = false;
    $xml_file_contents = file_get_contents($xmlfile);

    // Regular expression pattern to match XML comments
    $comment_pattern = '/<!--[\s\S]*?-->/';

    // Remove all XML comments from the text
    $xml_file_contents = preg_replace($comment_pattern, '', $xml_file_contents);

    // Optional: Trim the result to remove any leading or trailing whitespace
    $xml_file_contents = trim($xml_file_contents);

    $dom->loadXML($xml_file_contents);
    return $dom;
}
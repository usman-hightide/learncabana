<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// essential functions
require_once "subs.php";

class grassblade_scorm {

	function __construct() {

		add_filter( 'grassblade_content_versions', array($this,'add_scorm_versions'),10, 1);

		add_filter( 'grassblade_process_upload', array($this,'process_scorm_upload'), 10 , 3);

		add_action( 'wp_ajax_nopriv_grassblade_scorm', array( $this,'scorm_launch') );
		add_action( 'wp_ajax_grassblade_scorm', array( $this,'scorm_launch') );

		add_action( 'wp_ajax_nopriv_grassblade_scorm_commit', array( $this,'scorm_commit') );
		add_action( 'wp_ajax_grassblade_scorm_commit', array( $this,'scorm_commit') );

		add_action( 'wp_ajax_nopriv_grassblade_scorm_finish', array( $this,'scorm_finish') );
		add_action( 'wp_ajax_grassblade_scorm_finish', array( $this,'scorm_finish') );

		add_filter("grassblade_shortcode_atts", array($this, "grassblade_scorm_shortcode_atts"), 3, 2);
		add_filter("noxapi_url_versions", array($this, "noxapi_url_versions"), 10, 4);

	}

	/**
	 * Add Scorm Versions to not generate xAPI URL for SCORM content
	 *
	 * @param array $versions List of existing versions.
	 *
	 * @return array $versions with scorm versions.
	 */
	function noxapi_url_versions($versions, $version, $shortcode_atts, $attr) {
		$versions["scorm1.2"] = 'SCORM 1.2';
		$versions["scorm2004"] = 'SCORM 2004';
		return $versions;
	}

	/**
	 * Add Scorm Versions to the content version List.
	 *
	 * @param array $versions List of existing versions.
	 *
	 * @return array $versions with scorm versions.
	 */
	function add_scorm_versions($versions) {
		$versions["scorm1.2"] = 'SCORM 1.2';
		$versions["scorm2004"] = 'SCORM 2004';
		return $versions;
	}

	/**
	 * Add Scorm Versions to the content version List.
	 *
	 * @param array $params.
	 * @param obj $post.
	 * @param array $upload with required index $upload['content_path'] and $upload['content_url'].
	 *
	 * @return array $params with scorm versions.
	 */
	function process_scorm_upload($params, $post , $upload) {

		if(!empty($params["response"]) && $params["response"] == "error")
			return $params;

		if ( empty($params['process_status']) && isset($upload['content_path']) && is_dir($upload["content_path"]) ) {

			$imsmanifestxml_subdir = $this->get_imsmanifestxml($upload['content_path']);

			if(empty($imsmanifestxml_subdir))
			$imsmanifest_file = $upload['content_path'].DIRECTORY_SEPARATOR."imsmanifest.xml";
			else
			$imsmanifest_file = $upload['content_path'].DIRECTORY_SEPARATOR.$imsmanifestxml_subdir.DIRECTORY_SEPARATOR."imsmanifest.xml";

			if(file_exists($imsmanifest_file))
			{
				$xml_dom = gb_get_xml_dom($imsmanifest_file);
				$scorm_version_ar = get_gb_scormversion( $xml_dom );

				$scorm_version = 'scorm1.2';

				if (isset($scorm_version_ar['schemaversion'])) {
					if ( (strpos($scorm_version_ar['schemaversion'], '2004') !== false) ||  (trim($scorm_version_ar['schemaversion']) == 'CAM 1.3') ) {
						$scorm_version = 'scorm2004';
					}
				}

				$mastery_score = get_gb_masteryscore( $xml_dom );
				$SCOdata = gb_read_imsmanifestfile( $xml_dom );
				$ORGdata = gb_getORGdata( $xml_dom );

				foreach ($ORGdata as $identifier => $ORG)
				{
					if ( !empty($ORG['identifierref']) && isset($SCOdata[$identifier]) && isset($SCOdata[$identifier]["href"]) ) {
						$launch_file = gb_cleanVar($SCOdata[$identifier]["href"]);

						if(empty($upload['activity_id']))
							$upload['activity_id'] = $upload['content_url'];

						$upload['launch_path'] = dirname($imsmanifest_file).DIRECTORY_SEPARATOR.$launch_file;

						if(empty($imsmanifestxml_subdir))
						$upload['src'] =  $upload['content_url'].'/'.$launch_file;
						else
						$upload['src'] =  $upload['content_url'].'/'.$imsmanifestxml_subdir.'/'.$launch_file;

						if(!gb_file_exists($upload['launch_path']))
							return array("response" => 'error', "info" => 'Error: <i>'.$upload['launch_path'].'</i>. Launch file not found in package');
						if(!empty($ORG["name"]))
						$upload["title"] = strip_tags( $ORG["name"] );

						if(!empty($ORGdata["description"]))
						$upload["description"] = strip_tags( $ORGdata["description"] );

						$upload['version'] = $scorm_version;
						$upload["content_type"] = "scorm";
						$upload["mastery_score"] = $mastery_score;
						$upload['process_status'] = 1;

					}
				}

				if( empty($upload['launch_path']) ) {
					return array("response" => 'error', "info" => "XML Error:  Launch file reference not found in imsmanifest.xml");
				}

				foreach($upload as $k=>$v) {
					if(is_string($v))
					$params[$k] = addslashes($v);
					else
					$params[$k] = $v;
				}
			}
		}

		if(empty($params['process_status'])) {
			if(isset($params["src"]))
				unset($params["src"]);
			if(isset($params["launch_path"]))
				unset($params["launch_path"]);
			if(isset($params["mastery_score"]))
				unset($params["mastery_score"]);
		}

		return $params;
	}

	function get_imsmanifestxml($dir) {
		$imsmanifestxml_file = $dir.DIRECTORY_SEPARATOR."imsmanifest.xml";

		if(file_exists($imsmanifestxml_file))
			return "";
		else
		{
			$dirlist = scandir($dir);
			foreach($dirlist as $d)
			{
				if($d != "." && $d != "..")
				{
					$imsmanifestxml_file = $dir.DIRECTORY_SEPARATOR.$d.DIRECTORY_SEPARATOR."imsmanifest.xml";
					if(is_dir($dir) && is_dir($dir.DIRECTORY_SEPARATOR.$d))
					if(file_exists($imsmanifestxml_file))
						return $d;
				}
			}
		}
		return 0;
	}

	function scorm_launch($content_id = 0, $content_data = []) {
		if( empty($content_id) )
		$content_id = !is_numeric($_REQUEST['content_id'])? intVal(base64_decode($_REQUEST['content_id'])):intval($_REQUEST['content_id']);
		$user_id = !empty($_REQUEST['user_id']) && grassblade_lms::is_admin()? intval($_REQUEST['user_id']):0;

		if(empty($content_data))
		$content_data = grassblade_xapi_content::get_params($content_id);
		$grassblade_settings = grassblade_settings();
		$content = get_post($content_id);

		$user = wp_get_current_user();

		if(empty($content_data["content_tool"])) {
			$content_data["content_tool"] = grassblade_xapi_content::tool($content_data);
			if(!empty($content_data["content_tool"]))
				grassblade_xapi_content::set_params($content_id, $content_data);
		}

		$endpoint 	= !empty($content_data["endpoint"])?  $content_data["endpoint"]:$grassblade_settings["endpoint"];
		$api_user 	= !empty($content_data["user"])?  $content_data["user"]:$grassblade_settings["user"];
		$api_pass 	= !empty($content_data["pass"])?  $content_data["pass"]:$grassblade_settings["password"];
		$guest 		= ($content_data["guest"] !== false && $content_data["guest"] != "")?  $content_data["guest"]:$grassblade_settings["track_guest"];
		$actor_type	= empty($grassblade_settings["actor_type"])? "mbox":$grassblade_settings["actor_type"];
		$actor_user = !empty($user_id)? get_user_by('id', $user_id):null;
		$actor 		= grassblade_getactor($guest, "1.0", $actor_user, $actor_type);
		$agent_id 	= grassblade_get_actor_id($actor);
		$agent_name	= $actor["name"];
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
		//LRS Config
	    $config = array(
            "endpoint"  	=> (empty($endpoint) || $endpoint == "/")? "":$endpoint,
            "auth"      	=> "Basic ".base64_encode($api_user.":".$api_pass),
            "registration" 	=> $registration,
            "actor"     	=> $actor,
			"activity_id" 	=> $content_data['activity_id'],
            "isScorm2004"	=> ($content_data['version'] == 'scorm2004')
        );
		if(!empty($user_id))
			$config["user_id"] = $user_id;

		$scorm_data = gb_get_scorm_data($content_id, $registration, null, "", $user_id);
		$scorm_content_version = ($content_data['version'] == 'scorm2004')? "2004":"1.2";
		$keys = $this->scorm_cmi_keys($scorm_content_version);

		if (empty($scorm_data))
		$scorm_data = array( 	// test score
							$keys["cmi.score.raw"] => '',
							$keys["cmi.scaled_passing_score"] => $content_data['mastery_score'],

							// SCO launch and suspend data
							$keys["cmi.launch_data"] => '',
							$keys["cmi.suspend_data"] => '',

							// progress and completion tracking
							$keys["cmi.location"] => '',
							$keys["cmi.credit"] => 'credit',
							$keys["cmi.completion_status"] => 'not attempted',
							$keys["cmi.entry"] => "ab-initio",
							$keys["cmi.exit"] => '',

							// seat time
							$keys["cmi.total_time"] => '0000:00:00',
							$keys["cmi.session_time"] => '',
							$keys["cmi.interactions._count"] => '0'
					);
		$scorm_data[$keys['cmi.mode']] = 'normal';
		$scorm_data[$keys['cmi.learner_id']] = $agent_id;
		$scorm_data[$keys['cmi.learner_name']] = $agent_name;
		$scorm_data[$keys['cmi.content_id']] = $content_id;
		$scorm_data[$keys['cmi.registration_id']] = $registration;

		$commit_checks = (@$content_data["content_tool"] == "articulate_rise" || @$content_data["content_tool"] == "ispring")? array($keys["cmi.completion_status"], $keys["cmi.location"]):array($keys["cmi.suspend_data"], $keys["cmi.completion_status"], $keys["cmi.location"]);

		$GB_SCORM = array(
				"content_provider" 	=> array(),
				"all_ajax_content" 	=> array(),
				"interaction_index"	=> array(),
				"completion_stmt"	=> "",
				"mastery_score" 	=> $content_data['mastery_score'],
				"activity_id" 		=> $content_data['activity_id'],
				"scorm_version" 	=> $scorm_content_version,
				"content_name" 		=> $content->post_title,
				"ajax_url" 			=> admin_url( 'admin-ajax.php' ),
				"cache" 			=> $scorm_data,
				"content_tool"		=> $content_data["content_tool"],
				"commit_checks"		=> $commit_checks,
			);
		?>
		<html>
		<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<script type="text/javascript">
		    //<![CDATA[
			var GB_SCORM = <?php echo json_encode($GB_SCORM); ?>;
			var cache = GB_SCORM.cache;
		    //]]>
		</script>
		<?php echo grassblade_xapi_content::get_code_at_path($content_data["src"], "copy_protect"); ?>
		<?php
 		if(defined("GB_SCORM_DEV")) {
 		?>
		<script src="<?php echo plugins_url('/js/jquery.min.js', __FILE__); ?>" type="text/javascript"></script>
		<script src="<?php echo plugins_url('/js/json_library.js', __FILE__); ?>" type="text/javascript"></script>
		<script src="<?php echo plugins_url('/js/rte_functions.js', __FILE__); ?>" type="text/javascript"></script>
		<script src="<?php echo plugins_url('/js/xapiwrapper.min.js', __FILE__); ?>" type="text/javascript"></script>
		<script src="<?php echo plugins_url('/js/SCORMToXAPIFunctions.js', __FILE__); ?>" type="text/javascript"></script>
		<script type="text/javascript">window.gbdebug=true;</script>
			<?php if($scorm_content_version != '1.2'){ ?>
				<script src="<?php echo plugins_url('/js/rte_2004.js', __FILE__); ?>" type="text/javascript"></script>
			<?php } else { ?>
				<script src="<?php echo plugins_url('/js/rte_1.2.js', __FILE__); ?>" type="text/javascript"></script>
			<?php } ?>
		<?php } else { 	?>
			<script src="<?php echo plugins_url('/js/scorm.js', __FILE__)."?v=".GRASSBLADE_VERSION; ?>" type="text/javascript"></script>
			<?php if($scorm_content_version != '1.2'){ ?>
				<script src="<?php echo plugins_url('/js/rte_2004.min.js', __FILE__)."?v=".GRASSBLADE_VERSION; ?>" type="text/javascript"></script>
			<?php } else { ?>
				<script src="<?php echo plugins_url('/js/rte_1.2.min.js', __FILE__)."?v=".GRASSBLADE_VERSION; ?>" type="text/javascript"></script>
			<?php } ?>
		<?php } ?>
		<script type="text/javascript">
		    //<![CDATA[
		      ADL.XAPIWrapper.changeConfig(<?php echo json_encode($config); ?>);
		    //]]>
		</script>
		<style type="text/css">
			body, frameset, frame {
				width: 100%; height: 100%; padding: 0; margin: 0;
			}
		</style>
		</head>
		<?php
		if($scorm_content_version != '1.2'){
	//		echo '<frameset frameborder="0" framespacingframespacing="0" border="0" rows="*" cols="*" onbeforeunload="API_1484_11.Terminate(\'\');" onunload="API_1484_11.Terminate(\'\');">';
	//			echo '<frame src="'.utf8_encode($content_data['src']).'" name="course">';
	//		echo '</frameset>';
			echo '<iframe frameborder="0" framespacingframespacing="0" border="0" onbeforeunload="API_1484_11.Terminate(\'\');" width="100%" height="100%" onunload="API_1484_11.Terminate(\'\');"  src="'.esc_url($content_data['src']).'" name="course" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen="" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"></iframe>';
		} else {
//			echo '<frameset frameborder="0" framespacing="0" border="0" rows="*" cols="*" onbeforeunload="API.LMSFinish(\'\');" onunload="API.LMSFinish(\'\');">';
//				echo '<frame src="'.utf8_encode($content_data['src']).'" name="course">';
//			echo '</frameset>';
			echo '<iframe frameborder="0" framespacingframespacing="0" border="0" onbeforeunload="API.LMSFinish(\'\');" width="100%" height="100%"  onunload="API.LMSFinish(\'\');" src="'.esc_url($content_data['src']).'" name="course" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen="" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" ></iframe>';

		}
		echo "</html>";
		exit;
	}
	function scorm_cmi_keys($version = "") {
		$all_keys = array();
		$all_keys["1.2"] = array(
			'cmi.score.raw' 		=> 'cmi.core.score.raw',
			'cmi.location' 			=> 'cmi.core.lesson_location',
			'cmi.credit' 			=> 'cmi.core.credit',
			'cmi.completion_status' => 'cmi.core.lesson_status',
			'cmi.entry' 			=> 'cmi.core.entry',
			'cmi.exit' 				=> 'cmi.core.exit',
			'cmi.total_time' 		=> 'cmi.core.total_time',
			'cmi.session_time' 		=> 'cmi.core.session_time',
			'cmi.mode' 				=> 'cmi.core.lesson_mode',
			'cmi.learner_id' 		=> 'cmi.core.student_id',
			'cmi.learner_name' 		=> 'cmi.core.student_name',
			'cmi.content_id' 		=> 'cmi.core.content_id',
			'cmi.registration_id' 	=> 'cmi.core.registration_id',
			'cmi.scaled_passing_score' 	=> 'adlcp:masteryscore',
			'cmi.launch_data' 		=> 'cmi.launch_data',
			'cmi.suspend_data' 		=> 'cmi.suspend_data',
			'cmi.interactions._count' => 'cmi.interactions._count',
		);
		$all_keys["2004"] = array(
			'cmi.score.raw' 		=> 'cmi.score.raw',
			'cmi.location' 			=> 'cmi.location',
			'cmi.credit' 			=> 'cmi.credit',
			'cmi.completion_status' => 'cmi.completion_status',
			'cmi.entry' 			=> 'cmi.entry',
			'cmi.exit' 				=> 'cmi.exit',
			'cmi.total_time' 		=> 'cmi.total_time',
			'cmi.session_time' 		=> 'cmi.session_time',
			'cmi.mode' 				=> 'cmi.mode',
			'cmi.learner_id' 		=> 'cmi.learner_id',
			'cmi.learner_name' 		=> 'cmi.learner_name',
			'cmi.content_id' 		=> 'cmi.content_id',
			'cmi.registration_id' 	=> 'cmi.registration_id',
			'cmi.scaled_passing_score' 	=> 'cmi.scaled_passing_score',
			'cmi.launch_data' 		=> 'cmi.launch_data',
			'cmi.suspend_data' 		=> 'cmi.suspend_data',
			'cmi.interactions._count' => 'cmi.interactions._count',
			'cmi.objectives._count' => 'cmi.objectives._count',
		);

		if(!empty($version) && !empty($all_keys[$version]))
			return $all_keys[$version];
		else
			return $all_keys["2004"];
	}
	function scorm_commit() {
		global $current_user;
		if(empty($current_user->ID)) {
			echo json_encode("Guest User");
			exit;
		}

		$params = $_REQUEST['params'];
		$data = $params['data'];
		$scorm_version = $params['scorm_version'];
		$keys = $this->scorm_cmi_keys( $scorm_version );

		$content_id 		= $data[$keys['cmi.content_id']];
		$registration_id 	= $data[$keys['cmi.registration_id']];
		$user_id = !empty($_REQUEST['user_id']) && grassblade_lms::is_admin()? intval($_REQUEST['user_id']):null;

		foreach ($data as $varname => $varvalue) {
			if ($varname == $keys['cmi.entry']) {
				if(in_array($data[$keys['cmi.completion_status']], array('completed', 'passed'))) {
					gb_set_scorm_data($content_id, $registration_id, $keys['cmi.entry'],'', $user_id);
				}
				else
				if ($data[$keys['cmi.exit']] == 'suspend') {
					gb_set_scorm_data($content_id, $registration_id, 'cmi.entry','resume', $user_id);
				}
			} else {
				// save data to the 'scormvars' table
				if($varname == $keys["cmi.suspend_data"])
					$varvalue = stripslashes($varvalue); //Fixed for Articulate Rise Resume.  suspend_data is JSON, and ajax is adding slashes
				gb_set_scorm_data($content_id, $registration_id, $varname, $varvalue, $user_id);
			}
		}

		echo json_encode("Commit Success");
		exit();
	}
	function scorm_finish() {
		global $current_user;
		if(empty($current_user->ID)) {
			echo json_encode("Guest User");
			exit;
		}

		$params = $_REQUEST['params'];
		$data = $params['data'];
		$scorm_version = $params['scorm_version'];
		$keys = $this->scorm_cmi_keys( $scorm_version );

		$content_id 		= $data[$keys['cmi.content_id']];
		$registration_id 	= $data[$keys['cmi.registration_id']];
		$user_id = !empty($_REQUEST['user_id']) && grassblade_lms::is_admin()? intval($_REQUEST['user_id']):null;

		// find existing value of cmi.completion_status
		$lessonstatus = trim(gb_get_scorm_data($content_id, $registration_id, $keys['cmi.completion_status'], '', $user_id));
		// if it's 'not attempted', change it to 'completed'
		if ($lessonstatus == 'not attempted') {
			gb_set_scorm_data($content_id, $registration_id, $keys['cmi.completion_status'], 'completed', $user_id);
		}

		// ------------------------------------------------------------------------------------
		// set cmi.entry based on the value of cmi.exit

		// clear existing value
		gb_set_scorm_data($content_id, $registration_id, $keys['cmi.entry'], '', $user_id);

		// new entry value depends on exit value
		$exit = gb_get_scorm_data($content_id, $registration_id, $keys['cmi.exit'], '', $user_id);
		if (($exit === 'suspend' && $lessonstatus != 'completed') || ($exit === 'suspend' && $lessonstatus != 'passed')) {
			gb_set_scorm_data($content_id, $registration_id, $keys['cmi.entry'], 'resume', $user_id);
		}

		elseif (($exit == 'suspend' && $lessonstatus == 'completed') || ($exit == 'suspend' && $lessonstatus == 'passed')) {
			gb_set_scorm_data($content_id, $registration_id, $keys['cmi.entry'], '', $user_id);
		}

		// ------------------------------------------------------------------------------------
		// process changes to cmi.core.total_time

		// read cmi.core.total_time from the 'scormvars' table
		$totaltime = gb_get_scorm_data($content_id, $registration_id, $keys['cmi.total_time'], '', $user_id);
		$totalseconds = $this->time_to_seconds($totaltime);

		// read the last-set cmi.core.session_time from the 'scormvars' table
		$sessiontime = gb_get_scorm_data($content_id, $registration_id, $keys['cmi.session_time'], '', $user_id);
		$sessionseconds = $this->time_to_seconds($sessiontime);

		// new total time is ...
		$totalseconds += $sessionseconds;

		// reformat to comply with the SCORM data model
		$totaltime = $this->seconds_to_time($totalseconds);

		// save new total time to the 'scormvars' table
		gb_set_scorm_data($content_id, $registration_id, $keys['cmi.total_time'], $totaltime, $user_id);

		// delete the last session time
		gb_set_scorm_data($content_id, $registration_id, $keys['cmi.session_time'], '', $user_id);

		echo json_encode("Finish Success");
		exit();
	}
	function time_to_seconds($time_str) {
		if(empty($time_str))
			return 0;

		$time_str = (string) $time_str;
		$time_str = trim($time_str);

		if(strpos($time_str, "P") === 0)
			return grassblade_duration_to_seconds($time_str);

		$time = array_reverse(explode(':', $time_str));
		$seconds = intVal($time[0]);

		if(!empty($time[1]))
		$seconds += intVal($time[1]) * 60;

		if(!empty($time[2]))
		$seconds += intVal($time[2]) * 3600;

		return $seconds;
	}
	function seconds_to_time($seconds) {
		$hours = intval($seconds / 3600);
		$seconds -= $hours * 3600;
		$minutes = intval($seconds / 60);
		$seconds -= $minutes * 60;

		// reformat to comply with the SCORM data model
		return sprintf("%04d:%02d:%02d", $hours, $minutes, $seconds);
	}
	function grassblade_scorm_shortcode_atts($shortcode_atts, $attr) {

		if(!empty($shortcode_atts["id"]) && in_array($shortcode_atts["version"], array("scorm1.2", "scorm2004"))) {
			$content_id = $shortcode_atts["id"];
			$shortcode_atts["src"] = admin_url('admin-ajax.php').'?t='.time().'&action=grassblade_scorm&content_id='.$content_id;

			$guest_name = strip_tags(isset($_REQUEST['actor_name'])?$_REQUEST['actor_name']:'');
			$guest_mailto = strip_tags(isset($_REQUEST['actor_mbox'])?$_REQUEST['actor_mbox']:'');
			if(!empty($guest_name))
				$shortcode_atts["src"] .= '&actor_name='.rawurlencode($guest_name);
			if(!empty($guest_mailto))
				$shortcode_atts["src"] .= '&actor_mbox='.rawurlencode($guest_mailto);
		}
		return $shortcode_atts;
	}

} // end of grassblade_scorm class
global $gb_scorm;
$gb_scorm = new grassblade_scorm();


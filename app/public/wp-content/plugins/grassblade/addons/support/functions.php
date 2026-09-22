<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_support {

	function __construct() {

		add_action( 'admin_menu', array($this,'addon_plugins_menu'), 11);
                add_filter( 'grassblade_add_scripts_on_page', array($this, 'add_to_scripts') );
	}
        function add_to_scripts($grassblade_add_scripts_on_page) {
                $grassblade_add_scripts_on_page[] = "grassblade-support";
                return $grassblade_add_scripts_on_page;
        }
	/**
	 * Add menu.
	 */
	function addon_plugins_menu() {
		add_submenu_page("grassblade-lrs-settings", __("Support", "grassblade"), __("Support", "grassblade"),'manage_options','grassblade-support', array($this, 'support_menupage') );
	}

	function support_menupage(){
		//must check that the user has the required capability
	    if (!current_user_can('manage_options'))
	    {
	      wp_die( __('You do not have sufficient permissions to access this page.') );
	    }

	    $user = wp_get_current_user();

	    if( isset($_POST[ "GB_support_request" ]) ) {
	    	$this->send_zendesk_support();
	    	echo '<div class="updated"><p><strong>'.__('Request successfully submitted.', 'grassblade').'</strong></p></div>';
	    }

	    $other_information = $this->other_support_information();

	    ?>
	    <style type="text/css">
	    .grassblade-support{
	    	margin-top: 10px;
	    	background-color: white;
	    	padding: 1%;
	    	max-width: 860px;
	    }
	    .gb-support-tbl{
	    	width: 100%;
			margin-bottom: 15px;
	    }
	    .gb-support-tbl .label-td{
	    	font-size: 15px;
		    width: 1%;
		    font-weight: 500;
	    }
	    .gb-support-tbl .label-td span{
	    	color: red;
	    }
	    .gb-support-tbl .input-td{
	    	width: 100%;
	    }
	    .gb-support-tbl .input-td input {
	    	width: 100%;
    		height: 30px;
    		margin-bottom: 10px;
	    }

	    </style>
	    <div class="wrap">
			<h2>
				<img style="top: 6px; position: relative;" src="<?php echo plugins_url('img/icon_30x30.png', dirname(dirname(__FILE__))); ?>"/>
				GrassBlade Support
			</h2>
			<br>
			<div class="grassblade-support">
				<h1>Knowledge Base</h1>
				<a href="https://www.nextsoftwaresolutions.com/knowledge-base/" target="_blank">Click here to go to Knowledge Base to find support articles for common tasks and issues</a>
				<br>
				<br>
				<h1>Generate Support Ticket</h1>
				<form id="grassblade_support_request_form">
					<table class="gb-support-tbl">
						<tr>
							<td class="label-td">Your Name:</td>
						</tr>
						<tr>
							<td class="input-td"><input type="text" name="name" value="<?php echo $user->display_name; ?>" required></td>
						</tr>
						<tr>
							<td class="label-td">Your Email:</td>
						</tr>
						<tr>
							<td class="input-td"><input type="email" name="email" value="<?php echo $user->user_email; ?>" required></td>
						</tr>
						<tr>
							<td class="label-td">Subject: <span>*</span></td>
						</tr>
						<tr>
							<td class="input-td"><input type="text" name="subject" id="subject" value="" required></td>
						</tr>
						<tr>
							<td class="label-td">Description: <span>*</span></td>
						</tr>
						<tr>
							<td class="input-td"><textarea rows="8" name="description" id="description" required style="width: inherit;"></textarea></td>
						</tr>
						<tr>
							<td><b><h3 id="from_msg"></h3></b></td>
						</tr>
						<tr style="text-align: right;">
							<td>
								<input style="margin-top: 10px;" type="button" name="GB_support_request" value="<?php _e('Submit Request', 'grassblade') ?>" class="button-primary" onclick="gb_submit_support_request();">
								<br>
								<br>
								<input id="gb_include_info"  type="checkbox" name="gb_include_info" checked="checked"><span>Include useful support information</span><br>
<a href="#grassblade_help_faq" onclick="return showHideOptional('grassblade_support_info');" name="grassblade_support_info"><span style="margin-left:10px;"><?php _e('Show included support information', 'grassblade'); ?></span></a>
							</td>
						</tr>
						<tr><td>Alternatively, email us at: support@nextsoftwaresolutions.com</td></tr>
					</table>
					<div id="grassblade_support_info" class="infoblocks"  style="display:none;">
						<table style="border-collapse: collapse; width: 100%;">
							<?php foreach ($other_information as $head_key => $head_value) { ?>
								<tr>
									<td style="text-align: center !important;font-size: 18px !important;padding: 5px !important;color: white;background-color: #83ba39;font-weight: 500;border: 1px solid #dddddd;" colspan="2"><?php echo ucfirst($head_key); ?></td>
								</tr>
								<?php foreach ($head_value as $key => $value) { ?>
									<tr>
										<td style="font-size: 15px; font-weight: 500;border-bottom: 1px solid #dddddd;text-align: left;padding: 4px;width: 50%;"><?php echo $value['label_html']; ?></td>
										<td style="font-size: 15px; font-weight: 500;border-bottom: 1px solid #dddddd;text-align: left;padding: 4px;width: 50%;">
											<?php echo $value['value']; ?>
										</td>
									</tr>
								<?php } ?>
							<?php } ?>
						</table>
					</div>
				</form>
				<br><br>
		                <?php include(dirname(__FILE__)."/../../help.php"); ?>
			</div>
		</div>
		<script type="text/javascript">

			function gb_submit_support_request(){
				var form = jQuery('#grassblade_support_request_form').serializeArray();
				var data = {action: "grassblade_support_ticket", form : form};

				if (jQuery('#gb_include_info:checkbox:checked').length > 0)
					data.support_info = jQuery('#grassblade_support_info').html();

				jQuery.ajax({
			        type : "POST",
		            dataType : "json",
		            url : 'https://www.nextsoftwaresolutions.com/wp-admin/admin-ajax.php',
		            data : data,
			        success: function(response){
			        	jQuery('#from_msg').text('Support ticket raised successfully.');
			        	jQuery('#subject').val('');
			        	jQuery('#description').val('');
			        },
					error: function(errorThrown){
					    jQuery('#from_msg').text('Please contact at support@nextsoftwaresolutions.com');
					}
			    });
			}
		</script>
	    <?php
	}

	function other_support_information(){
		global $wpdb, $wp_version;
		$other_information = array();

		$other_information['grassblade']['grassblade_version'] = array(
				'label_html' => esc_html__( 'Grassblade Version', 'grassblade' ),
				'value'      => GRASSBLADE_VERSION
			);

		$other_information['grassblade']['grassblade_license'] = array(
				'label_html' => esc_html__( 'Grassblade License', 'grassblade' ),
				'value'      => get_option('nss_plugin_license_grassblade')
			);

		$other_information['grassblade']['grassblade_license_email'] = array(
				'label_html' => esc_html__( 'Grassblade License Email', 'grassblade' ),
				'value'      => get_option('nss_plugin_license_email_grassblade')
			);

		$grassblade_settings = grassblade_settings();
		$other_information['grassblade']['grassblade_endpoint'] = array(
				'label_html' => esc_html__( 'LRS Endpoint', 'grassblade' ),
				'value'      => $grassblade_settings['endpoint']
			);

		$other_information['wordpress']['site_url'] = array(
				'label_html' => esc_html__( 'Domain', 'grassblade' ),
				'value'      => str_replace(array("http://", "https://"), "", get_bloginfo("url"))
			);

		$other_information['wordpress']['wordpress_version'] = array(
				'label_html' => esc_html__( 'Wordpress Version', 'grassblade' ),
				'value'      => $wp_version
			);

		$other_information['wordpress']['is_multisite'] = array(
				'label_html' => esc_html__( 'Is Multisite', 'grassblade' ),
				'value'      => is_multisite() ? 'Yes' : 'No'
			);

		$active_plugins = get_option( 'active_plugins' );
		$all_plugins = get_plugins();

		$plugins = array();
		foreach ($active_plugins as $key => $value) {
			if (array_key_exists($value,$all_plugins)) {
				$plugins[] = $all_plugins[$value]['Name'].' (v'.$all_plugins[$value]['Version'].')';
			}
		}

		$other_information['wordpress']['active_plugins'] = array(
				'label_html' => esc_html__( 'Active Plugins', 'grassblade' ),
				'value'      => esc_html__( 'Plugins', 'grassblade' ) . ': ' . join( ',  ', $plugins )
			);

		$other_information['server']['php_version'] = array(
				'label_html' => esc_html__( 'PHP Version', 'grassblade' ),
				'value'      => phpversion()
			);

		$other_information['server']['php_os'] = array(
				'label_html' => esc_html__( 'PHP OS', 'grassblade' ),
				'value'      => $this->getOSInformation()
			);

		$other_information['server']['mysql_version'] = array(
				'label_html' => esc_html__( 'MySQL Version', 'grassblade' ),
				'value'      => $wpdb->db_version()
			);

		$other_information['server']['max_execution_time'] = array(
				'label_html' => esc_html__( 'Max Execution Time', 'grassblade' ),
				'value'      => ini_get('max_execution_time')
			);

		$other_information['server']['max_input_time'] = array(
				'label_html' => esc_html__( 'Max Input Time', 'grassblade' ),
				'value'      => ini_get('max_input_time')
			);

		$other_information['server']['max_file_uploads'] = array(
				'label_html' => esc_html__( 'Max File Upload', 'grassblade' ),
				'value'      => ini_get('max_file_uploads')
			);

		$other_information['server']['upload_max_filesize'] = array(
				'label_html' => esc_html__( 'Upload Max File-Size', 'grassblade' ),
				'value'      => ini_get('upload_max_filesize')
			);

		$apache_get_modules = function_exists('apache_get_modules')? apache_get_modules():array(__("Unknown", "grassblade"));
		$other_information['server']['apache_enabled_modules'] = array(
				'label_html' => esc_html__( 'Apache Enabled Modules', 'grassblade' ),
				'value'      => esc_html__( 'Modules', 'grassblade' ) . ': ' . join( ', ', $apache_get_modules )
			);

		if ( ! extension_loaded( 'curl' ) ) {
			$other_information['server']['curl'] = array(
						'label_html' => esc_html__( 'Curl', 'grassblade' ),
						'value'      => 'No'
					);
		} else {
			$version = curl_version();
			$other_information['server']['curl'] = array(
						'label_html' => esc_html__( 'Curl', 'grassblade' ),
						'value'      => 'Yes'. '<br />'
					);
			$other_information['server']['curl']['value'] .= esc_html__( 'Version', 'grassblade' ) . ': ' . $version['version'] . '<br />';
			$other_information['server']['curl']['value'] .= esc_html__( 'SSL Version', 'grassblade' ) . ': ' . $version['ssl_version'] . '<br />';
			$other_information['server']['curl']['value'] .= esc_html__( 'Libz Version', 'grassblade' ) . ': ' . $version['libz_version'] . '<br />';
			$other_information['server']['curl']['value'] .= esc_html__( 'Protocols', 'grassblade' ) . ': ' . join( ', ', $version['protocols'] ) . '<br />';
		}

		$other_information = apply_filters('grassblade_other_support_information',$other_information);
		return $other_information;

	} // end of other_support_information
    function getOSInformation()
    {
        if (false == is_readable("/etc/os-release")) {
	    	if(defined('PHP_OS'))
	    		return PHP_OS;
	    	else
	    		return '';
        }

        $os         = file_get_contents("/etc/os-release");
        $listIds    = preg_match_all('/.*=/', $os, $matchListIds);
        $listIds    = $matchListIds[0];

        $listVal    = preg_match_all('/=.*/', $os, $matchListVal);
        $listVal    = $matchListVal[0];

        array_walk($listIds, function(&$v, $k){
            $v = strtolower(str_replace('=', '', $v));
        });

        array_walk($listVal, function(&$v, $k){
            $v = preg_replace('/=|"/', '', $v);
        });

        $os_info 	= array_combine($listIds, $listVal);

    	if($os_info["pretty_name"])
    		return $os_info["pretty_name"];
    	else
    	if($os_info["name"])
    		return $os_info["name"]." ".(@$os_info["version"]);
    	else
    	if(defined('PHP_OS'))
    		return PHP_OS;
    	else
    		return '';
    }
} // end of class

$gbs = new grassblade_support();

<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_xapi_content {
	public $debug = false;
	public $fields;
	function __construct() {
	}
	function run() {
		add_action( 'init', array($this, 'grassblade_xapi_post_content') );
		add_action( 'admin_head', array($this, 'grassblade_xapi_portfolio_icons') );

		add_action( 'add_meta_boxes', array($this, 'gb_xapi_content_box') );
		add_action( 'save_post', array($this, 'gb_xapi_content_box_save' ));

		add_action( 'post_edit_form_tag', array($this, 'grassblade_xapi_post_edit_form_tag'));
		add_action( 'the_content', array($this, 'add_xapi_shortcode' ));

		add_filter( 'grassblade_shortcode_return', array($this, 'show_results'), 10, 4);
		add_shortcode( 'grassblade_user_score', array($this, 'user_score'));

		if( is_admin() && ( !empty($_GET['post_type']) && $_GET['post_type'] == "gb_xapi_content" || !empty($_GET['post']) ) )
		add_action( 'init', array($this,'custum_script') );

		add_action( 'wp_ajax_gb_upload_content_file', array($this,'upload_content_file' ));
		add_action( 'wp_ajax_dropbox_upload_file', array($this,'dropbox_upload_file' ));

		add_action( 'wp_ajax_gb_content_enable_copy_protect', array($this,'gb_content_enable_copy_protect' ));
		add_action( 'wp_ajax_gb_reset_learner_progress', array($this,'gb_reset_learner_progress' ));
		add_action( 'wp_ajax_gb_delete_revisions', array($this,'gb_delete_revisions' ));

		add_filter("xapi_content_help_text", array($this, "xapi_content_help_text"), 10, 3);

		add_action( 'wp_ajax_nopriv_grassblade_launch', array( $this,'content_launch') );
		add_action( 'wp_ajax_grassblade_launch', array( $this,'content_launch') );
	}

	function custum_script(){
		wp_enqueue_script('plupload');

		wp_localize_script( 'plupload', 'content_data', array(
			'uploading' => __("[file_name] is uploading... [percent] uploaded.", "grassblade"),
			'processing' => __('[file_name] uploaded. Processing...', "grassblade"),
			'processed' => __('[file_name] uploaded & processed.', "grassblade"),
			'dropbox_uploading' => __('[file_name] is uploading... 95% uploaded.', "grassblade"),
			'uploadSize' => wp_max_upload_size(),
			'plupload' => array(
				'max_retries' => 1
				)
		) );
	}

	function upload_content_file() {

		if ( empty($_REQUEST["post_id"]) || !isset($_REQUEST['gb_nonce']) || !wp_verify_nonce( $_REQUEST['gb_nonce'], plugin_basename( __FILE__ ) ) )
			$error = array("response" => 'error', "info" => __("You don't have permissions to edit this page.","grassblade"));

		$post_id = intVal($_REQUEST['post_id']);

		if ( !current_user_can( 'edit_post', $post_id ) )
		$error = array("response" => 'error', "info" => __("You don't have permissions to edit this page.","grassblade"));

		if(!empty($error))
		$this->upload_response($error);

		$post = get_post($post_id);
		if (empty($post->post_name)) {
			$post_name = sanitize_file_name($_REQUEST['name']);
			$post_name = substr($post_name, 0, strrpos($post_name, "."));
			$post_name = grassblade_sanitize_filename($post_name);

			$my_post = array(
			      'ID'           => $post_id,
			      'post_name'   => $post_name,
			);

			// Update the post into the database
			wp_update_post( $my_post );
		}

		$post = get_post($post_id);

		$data = $this->get_params($post_id);

		add_filter('upload_dir', array($this, 'grassblade_upload_dir'));
		add_filter('upload_mimes', array($this, 'upload_mimes'));

		if ( ! function_exists( 'wp_handle_upload' ) ) {
	        require_once( ABSPATH . 'wp-admin/includes/file.php' );
	    }

		if(!empty($_FILES['xapi_content']['name'])) {
			$upload = wp_handle_upload($_FILES['xapi_content'], array('test_form' => FALSE));
		}
		/*
		$upload = Array
		(
		    [file] => Full Path of ZIP file
		    [url] => Full URL of ZIP file
		    [type] => application/zip
		)
		*/

		if (!empty($upload["error"])) {
			$params = array("response" => 'error', "info" => $upload["error"] );
		}
		else
		if(!empty($upload) && !is_wp_error($upload) && empty($upload["error"])) {
			$params = $this->process_upload($post, $data, $upload);
		}

		remove_filter('upload_dir', array($this, 'grassblade_upload_dir'));
		$this->upload_response($params);
	}

	function dropbox_upload_file(){

		if ( empty($_REQUEST["post_id"]) || !isset($_REQUEST['gb_nonce']) || !wp_verify_nonce( $_REQUEST['gb_nonce'], plugin_basename( __FILE__ ) ) )
			return array("response" => 'error', "info" => "Permission denied.");

		$post_id = intVal($_REQUEST['post_id']);

		if ( !current_user_can('edit_posts', $post_id ) )
		return array("response" => 'error', "info" => "You don't have permissions to edit this page.");

		$post = get_post($post_id);

		if (empty($post->post_name)) {
			$post_name = sanitize_file_name($_REQUEST['file']);
			$post_name = substr($post_name, 0, strrpos($post_name, "."));
			$post_name = grassblade_sanitize_filename($post_name);

			$my_post = array(
			      'ID'           => $post_id,
			      'post_name'   => $post_name,
			);

			// Update the post into the database
			wp_update_post( $my_post );
		}

		$post = get_post($post_id);

		$data = $this->get_params($post_id);

		add_filter('upload_dir', array($this, 'grassblade_upload_dir'));
		add_filter('upload_mimes', array($this, 'upload_mimes'));

		$url = $_POST['link'];
		$file = $_POST['file'];

		$file_info = pathinfo($file);
		$filename = grassblade_sanitize_filename(basename($file_info['basename']));

		$upload = wp_upload_dir();
		$file = $upload['path']."/".$filename;

		set_time_limit(0); // unlimited max execution time
		$return = $this->cURLdownload($url, $file);

		if($return === true)
		{
			$upload['file'] = realpath($file);
			$upload['url'] .= "/".$filename;
			$params = $this->process_upload($post, $data, $upload);
		}
		else
		{
			$params = array("response" => 'error', "info" => $return );
            grassblade_debug('error');
			grassblade_debug($return);
		}
		remove_filter('upload_dir', array($this, 'grassblade_upload_dir'));

		$this->upload_response($params);
	}

	function upload_response($params) {
		if (isset($params['info']) && isset($params['response'])) {
			$response_data =  $params;
		}
		else
		{
			if (!empty($params['src'])) {
				$response_data = array("response" => 'success', "info" => "Completed", "data" => $params, "switch_tab" => ".nav-tab-content-url" );
			} else if (!empty($params['video'])) {
				$response_data = array("response" => 'success', "info" => "Completed", "data" => $params, "switch_tab" => ".nav-tab-video" );
			} else {
				$response_data = array("response" => 'error', "info" => __("Incompatible content. Processing failed.","grassblade") );
			}
		}
		$response_data = apply_filters( 'gb_upload_response', $response_data, $params);

		echo json_encode($response_data);
		exit();
	}

	function upload_mimes ( $existing_mimes=array() ) {

		// add your extension to the mimes array as below
		$existing_mimes['zip']  = 'application/zip';
		$existing_mimes['gz']   = 'application/x-gzip';
		$existing_mimes['mp3']  = 'audio/mpeg';
		$existing_mimes['mp4']  = 'video/mp4';
		$existing_mimes['webm'] = 'video/webm';
		$existing_mimes['m3u8'] = 'application/x-mpegURL';
		$existing_mimes['mpd']  = 'application/dash+xml';
		$existing_mimes['mov']  = 'video/quicktime';

		return $existing_mimes;
	}
	function grassblade_upload_dir($upload) {
		global $post;
		$upload['subdir']	= '/grassblade';
		$upload['path']		=  $upload['basedir'] . $upload['subdir'];

		$upload['url']		= filter_var($upload['baseurl'] . $upload['subdir'], FILTER_SANITIZE_URL);
		return $upload;
	}
	function grassblade_xapi_post_content() {
		$labels = array(
			'name'               => _x( 'xAPI Content', 'post type general name' ),
			'singular_name'      => _x( 'xAPI Content', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'grassblade' ),
			'add_new_item'       => __( 'Add New xAPI Content' ),
			'edit_item'          => __( 'Edit xAPI Content' ),
			'new_item'           => __( 'New xAPI Content' ),
			'all_items'          => __( 'All xAPI Content' ),
			'view_item'          => __( 'View xAPI Content' ),
			'search_items'       => __( 'Search xAPI Content' ),
			'not_found'          => __( 'No xAPI Content found' ),
			'not_found_in_trash' => __( 'No xAPI Content found in the Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'xAPI Content'
		);
		$slug = grassblade_settings("url_slug");
		$args = array(
			'labels'        => $labels,
			'description'   => 'Holds our GrassBlade xAPI Content',
			'public'        => true,
			'menu_position' => 5,
			'supports'      => array( 'title',  'editor', 'revisions', 'custom-fields'),
			'has_archive'   => false,
			'taxonomies' => array('category'),
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'menu_icon' => plugins_url('img/button-15.png', dirname(dirname(__FILE__))),
			'rewrite' 	=> array("slug" => $slug),
			'show_in_rest' => true,
		);
		$args = apply_filters("gb_xapi_content_post_args", $args);
		register_post_type( 'gb_xapi_content', $args );
		//wp_enqueue_media();
	}
	public function get_name_by_activity_id($activity_id) {
		global $wpdb;
		$post_id = $this->get_id_by_activity_id($activity_id);
		$xpost = get_post($post_id);

		if(!empty($post_id) && isset($xpost->post_title))
		return $xpost->post_title;
		else
		return "";
	}
	public function get_categories() {
		$args = array(
			'type'                     => 'gb_xapi_content',
			'child_of'                 => 0,
			'parent'                   => '',
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 1,
			'hierarchical'             => 1,
			'exclude'                  => '',
			'include'                  => '',
			'number'                   => '',
			'taxonomy'                 => apply_filters('grassblade_content_taxonomies',
														array('category')
														),
			'pad_counts'               => false );
		$categories = get_categories($args);
		return $this->hierarchy($categories);
	}
	function hierarchy($categories){
		$catpool = $categories;
		$hierarchy = array();
		$num = count($catpool);
		foreach($catpool as $i => $cati)
		{
			$categories_withcatid[$catpool[$i]->cat_ID] = $catpool[$i];

			foreach($catpool as $j => $catj)
			{
				$catid = $catpool[$j]->cat_ID;
				$parent = $catpool[$j]->category_parent;
				$hierarchy[$parent][$catid] = 1;
			}
		}
		return  $this->hierarchy_rec(0, $hierarchy, $categories_withcatid);
	}
	function hierarchy_rec($find, $hierarchy,$categories, $return = array(), $depth = 0) {
		$cat_name = empty($categories[$find]->name)? "":$categories[$find]->name;

		if(empty($hierarchy[$find])) {
			$categories[$find]->name = $this->depth_spaces($depth).$cat_name;
			$return[] = $categories[$find];
			return $return;
		}
		else
		{
			$ret = "";
			if(!empty($categories[$find]->term_id)) {
				$categories[$find]->name = $this->depth_spaces($depth).$cat_name;
				$return[] = $categories[$find];
			}

			foreach($hierarchy[$find] as $k => $v)
			{
				$return = $this->hierarchy_rec($k, $hierarchy,$categories, $return, $depth + 1);
			}
			return $return;
		}
	}
	function depth_spaces($depth) {
		$d = '';
		$i = $depth;
		while($i > 1) {
			$d .= '&nbsp;&nbsp;&nbsp;';
			$i--;
		}
		return $d;
	}
	public function get_category_selector() {
		$categories = $this->get_categories();
		$ret = '';
		$ret .= '<script>
					function xapi_content_report_change(cat) {
						jQuery(".xapi_category_all").hide();
						jQuery(".xapi_category_" + cat.value).show();
					}
				</script>';
		$ret .= "<select onChange='xapi_content_report_change(this);'>";
		$ret .= 	"<option value='all'>All</option>";
		foreach($categories as $cat) {
			$ret .= "<option value='$cat->cat_ID'>$cat->name</option>";
		}
		$ret .= "</select>";
		return $ret;
	}
	public function get_categories_by_activity_id($activity_id) {
		global $wpdb;
		$post_id = $this->get_id_by_activity_id($activity_id);

		if(empty($post_id))
			return "";

		return wp_get_post_categories( $post_id );
	}
	public function get_category_classes_by_activity_id($activity_id) {
		$categories = $this->get_categories_by_activity_id($activity_id);
		$r = "";
		if(!empty($categories))
		foreach($categories as $cat) {
			$r .= " xapi_category_".$cat;
		}
		return $r;
	}
	static function get_id_by_activity_id($activity_id) {
		global $wpdb;

		$sql = $wpdb->prepare("
						SELECT post_id FROM $wpdb->postmeta
						WHERE meta_key = 'xapi_activity_id'
						AND meta_value IN ('%s', '%s')
						", $activity_id, esc_attr($activity_id));
		$post_ids = $wpdb->get_col( $sql );

		if(empty($post_ids) || count($post_ids) == 0)
			return 0;

		foreach ($post_ids as $post_id) {
			$post = get_post($post_id);
			if(!empty($post)) {
				if($post->post_status == "publish")
					return $post->ID;

				$existing_post = $post;
			}
		}
		if(!empty($existing_post->ID))
			return $existing_post->ID;
		else
			return 0;
	}
	function add_xapi_shortcode($content) {
		global $post;
		if(!empty($post->post_type) && $post->post_type == "gb_xapi_content")
		{
			$xapi_content = $this->get_params($post->ID);
			if(!empty($xapi_content['show_here']) || !empty($_GET["xapi_preview"]) && current_user_can("edit_post", $post->ID)) {
				if(strpos($content, "[grassblade]") === false)
				$content .= "[grassblade id='".$post->ID."']";
				else
				$content = str_replace("[grassblade]", "[grassblade id='".$post->ID."']", $content);
			}
		}
		return $content;
	}
	function grassblade_xapi_portfolio_icons() {
		?>
		<style type="text/css" media="screen">
			.icon32-posts-gb_xapi_content {
				background: url(<?php echo plugins_url('img/icon_30x30.png', dirname(dirname(__FILE__))) ?>) no-repeat 6px 6px !important;
			}
		</style> <?php
	}
	function debug($msg) {
		if(isset($_GET['debug']) || !empty($this->debug)) {

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
	function upload_limit() {
		$upload_size_unit = $max_upload_size = wp_max_upload_size();
        $sizes = array( 'KB', 'MB', 'GB' );

        for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ ) {
                $upload_size_unit /= 1024;
        }

        if ( $u < 0 ) {
                $upload_size_unit = 0;
                $u = 0;
        } else {
                $upload_size_unit = (int) $upload_size_unit;
        }
        return $upload_size_unit.$sizes[$u];
	}
	static function get_code_at_path( $src, $code_name ) {
		$file_path = grassblade_xapi_content::get_path_for_content_to_update( $src );
		if(empty($file_path))
			return '';
		$code_path = grassblade_xapi_content::get_code_path("copy_protect");

		if(!empty($code_path))
		$check = grassblade_xapi_content::gb_content_check_code($file_path, $code_path);

		if( !empty($check) ) {
			return file_get_contents($code_path);
		}
		return "";
	}
	static function get_path_for_content_to_update( $src ) {
		$ext = pathinfo($src, PATHINFO_EXTENSION);

		if(in_array($ext, array("html", "htm"))) {
			$url_parts = explode("/wp-content/", $src);
			if(empty($url_parts[1]))
				return false;

			$path = realpath( WP_CONTENT_DIR."/".$url_parts[1] );
			//echo $path;

			if(!file_exists($path) || !is_writable($path))
				return false;

			return $path;
		}
		return false;
	}
	function copy_protect_button($gb_src_tools, $params) {
		global $post;

		if(!empty($post->ID) && !empty($params["src"])) {
			$file_path = grassblade_xapi_content::get_path_for_content_to_update($params["src"]);
			$code_path = grassblade_xapi_content::get_code_path("copy_protect");
			if(!empty($file_path) && !empty($code_path)) {
				$has_code = grassblade_xapi_content::gb_content_check_code($file_path, $code_path);
				$tooltip = "<span class='tooltiptext tooltiptext-top'>Copy Protect (beta) disables copy,paste,cut,drag and right click inside the content.</span>";

				if($has_code)
				$gb_src_tools["copy_protect"] = "<span class='button gb_help_text_button gb_tooltip copy_protect_button' onclick='gb_content_copy_protect(this, ".$post->ID.", false)'>" .__("Disable Copy Protect", "grassblade").$tooltip."</span>";
				else
				$gb_src_tools["copy_protect"] = "<span class='button gb_help_text_button gb_tooltip copy_protect_button' onclick='gb_content_copy_protect(this, ".$post->ID.", true)'>" .__("Enable Copy Protect", "grassblade").$tooltip."</span>";
			}
			else
			{
				$tooltip = "<span class='tooltiptext tooltiptext-top'>Could not access content folder.</span>";
				$gb_src_tools["copy_protect"] = "<span class='button gb_help_text_button gb_tooltip copy_protect_button disabled'>" .__("Copy Protect", "grassblade").$tooltip."</span>";
			}
		}
		else
		{
			$tooltip = "<span class='tooltiptext tooltiptext-top'>Content URL is empty.</span>";
			$gb_src_tools["copy_protect"] = "<span class='button gb_help_text_button gb_tooltip copy_protect_button disabled'>" .__("Copy Protect", "grassblade").$tooltip."</span>";
		}
		return $gb_src_tools;
	}
	function reset_learner_progress_button($gb_src_tools, $params) {
		global $post;

		if(!empty($post->ID) && !empty($params["activity_id"]) && (empty($params["version"]) || $params["version"] != "none") ) {
			$tooltip = "<span class='tooltiptext tooltiptext-top'>Reset Learner Progress (beta) will reset the progress of all partially completed attempts. Completed attempts will stay completed. You need to do this if your content doesn't launch for some users after updating the content.</span>";
			$gb_src_tools["reset_learner_progress"] = "<span class='button gb_help_text_button gb_tooltip reset_learner_progress_button' onclick='gb_reset_learner_progress(this, ".$post->ID.")'>" .__("Reset Learner Progress", "grassblade").$tooltip."</span>";
		}
		return $gb_src_tools;
	}
	function content_versions_button($gb_src_tools, $params) {
		global $post;

		if(!empty($post->ID) && !empty($params)) {
			$xapi_content_versions = get_post_meta($post->ID, "xapi_content_versions");
			if(!empty($xapi_content_versions) && count($xapi_content_versions) > 1 ) {
				$reset = " <span style='display:none' class='gb_revisions_reset' data-settings='".json_encode($params)."' onClick='gb_switch_revision(this);'>reset</span>";
				$tooltip_html = "Total Revisions: ".count($xapi_content_versions).$reset;
				$file_versions = array();

				$current_src = str_replace(array("http://", "https://"), array("",""), strtolower($params["src"]));

				foreach ($xapi_content_versions as $key => $content_version) {
					$content_version = maybe_unserialize($content_version);

					if(!is_array($content_version))
						continue;

					$version_no = empty($content_version["version_no"])? 1:intVal($content_version["version_no"]);

					$file_version = $src = "";
					if(!empty($content_version["src"])) {
						$src = str_replace(array("http://", "https://"), array("",""), strtolower($content_version["src"]));
						if(empty($file_versions[$src]))
							$file_versions[$src] = $version_no;

						if(!empty($file_versions[$src]))
							$file_version = $file_versions[$src];
					}

					$size = empty($content_version["content_size"])? "":$this->readable_size($content_version["content_size"]);
					$is_current_file_version = intVal($src == $current_src);
					$delete_html = "";
					if( empty($is_current_file_version) && $file_version == $version_no && !empty($content_version["content_url"]) ) {
						$path = explode("wp-content", $content_version["content_url"], 2); //content_path backslashes not getting stored on windows. Content URL should work

						if(!empty($path[1]) && file_exists(WP_CONTENT_DIR. str_replace("/", DIRECTORY_SEPARATOR, $path[1]))) {
							$delete_html = " <span class='gb_delete_revisions' style='display:none' onClick='gb_delete_revisions(this, ".$post->ID.",".$file_version.", \"".rawurlencode($path[1])."\")'>(delete files)</span>";
						}
					}
					$size = empty($file_version)? $size:"<span class='file_versions' onClick='gb_select_file_versions(this, ".$file_version.", ".$is_current_file_version.")'>".$size."</span>".$delete_html;

					$switch_to = " <span class='gb_switch_revision_link' style='display:none' data-settings='".json_encode($content_version)."' onClick='gb_switch_revision(this);'>(switch to this)</span>";
					$tooltip_html .= "<div class='gb_revisions gb_file_version_".$file_version."'>".gb_date($content_version["timestamp"])." Revision ".$version_no. " ".$size.$switch_to."</div>";
				}

				$tooltip = "<span class='tooltiptext tooltiptext-top'>".$tooltip_html."</span>";
				$gb_src_tools["content_versions"] = "<span class='button gb_help_text_button gb_tooltip content_versions_button' onClick='jQuery(this).toggleClass(\"tooltip-locked\")'>" .__("Revisions", "grassblade").$tooltip."</span>";
			}
		}
		return $gb_src_tools;
	}
	static function delete_dir($dir_path) {

		$dir_path = trailingslashit($dir_path);

		if (! is_dir($dir_path)) {
			return;
		}

		// rmdir($dir_path);
		WP_Filesystem();
		global $wp_filesystem;
		$wp_filesystem->rmdir($dir_path, true);
	}
	function gb_delete_revisions() {

		if(!empty($_REQUEST["path"])) {
			$path = strtolower( rawurldecode(stripslashes($_REQUEST["path"])) );
			$path_url = str_replace(array( DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), array("/","/"), $path);
		}
		if ( empty($_REQUEST["post_id"]) || empty($_REQUEST["revision"]) || empty($_REQUEST["path"]) || !isset($_REQUEST['gb_nonce']) || !wp_verify_nonce( $_REQUEST['gb_nonce'], plugin_basename( __FILE__ ) ) || !current_user_can( 'edit_post', intVal($_REQUEST["post_id"]) ) )
			$return = array("response" => 'error', "info" => "Permission denied.");
		else if(!file_exists(WP_CONTENT_DIR.$path))
			$return = array("response" => 'error', "info" => "Invalid request.");
		else {
			$post_id = intVal($_REQUEST["post_id"]);
			$revision = intVal($_REQUEST["revision"]);

			global $wpdb;
			$results = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'xapi_content_versions' AND post_id = '%d'", $post_id));
			$xapi_content_versions = array();
			foreach ($results as $key => $value) {
				$xapi_content_versions[$value->meta_id] = maybe_unserialize($value->meta_value);
			}

			//$xapi_content_versions = get_post_meta($post_id, "xapi_content_versions");

			if(!empty($xapi_content_versions) && is_array($xapi_content_versions)) {
				$matches = array();
				$has_matching_version_no = false;
				foreach ($xapi_content_versions as $meta_id => $content_version) {
					if(empty($content_version["src"]))
						continue;

					$content_version = maybe_unserialize($content_version);
					if( !empty($content_version["content_url"]) && strpos( strtolower( $content_version["src"] ), $path_url) !== false) {
						$content_path = explode("wp-content", strtolower( $content_version["content_url"] ), 2);
						if(!empty($content_path[1]) && $content_path[1] == $path_url) {
							if($content_version["version_no"] == $revision)
								$has_matching_version_no = true;

							$matches[$meta_id] = $content_version;
						}
					}
				}
			}

			if($has_matching_version_no && !empty($matches)) {
				foreach ($matches as $meta_id => $match) {
					if(file_exists($match["content_path"])) {
						$content_path = $match["content_path"];
					}
					else if(!empty($match["content_url"]))
					{
						$content_path = explode("wp-content", strtolower( $match["content_url"] ), 2);
						if(!empty($content_path[1]))
							$content_path = WP_CONTENT_DIR.str_replace("/", DIRECTORY_SEPARATOR, $content_path[1]);
						else {
							$content_path = "";
							$matches[$meta_id]["deleted"] = "content url doesn't exists";
							continue;
						}
					}

					if(!empty($content_path))
						grassblade_xapi_content::delete_dir($content_path);

					if(!empty($content_path) && file_exists($content_path)) {
						$matches[$meta_id]["deleted"] = "could not delete folder";
						continue;
					}

					$matches[$meta_id]["deleted"] = delete_metadata_by_mid("post", $meta_id);// delete_post_meta($post_id, "xapi_content_versions", $match);
				}
			}

			$response = !file_exists(WP_CONTENT_DIR . $path)? "success":"failed";
			$response_text = __("Delete Revisions", "grassblade"). ": " . __($response, "grassblade");

			$return = array("response" => $response, "info" => $response_text, 'p' => $matches);
		}

		echo json_encode($return);
		exit();
	}
	function gb_reset_learner_progress() {

		if ( empty($_REQUEST["post_id"]) || !isset($_REQUEST['gb_nonce']) || !wp_verify_nonce( $_REQUEST['gb_nonce'], plugin_basename( __FILE__ ) ) || !current_user_can( 'edit_post', $_REQUEST["post_id"] ) )
			$return = array("response" => 'error', "info" => "Permission denied.");
		else {
			$post_id = intVal($_REQUEST["post_id"]);
			$r = grassblade_reset_learner_progress( $post_id );

			$response = $r? "success":"failed";
			$response_text = __("Reset Learner Progress", "grassblade"). ": " . __($response, "grassblade");
			$registration = (is_string($r) && strlen($r) == 36)? $r:"";
			$return = array("response" => $response, "info" => $response_text, "registration" => $registration);
		}

		echo json_encode($return);
		exit();
	}
	function gb_content_enable_copy_protect() {

		if ( empty($_REQUEST["post_id"]) || empty($_REQUEST["enable"]) || !isset($_REQUEST['gb_nonce']) || !wp_verify_nonce( $_REQUEST['gb_nonce'], plugin_basename( __FILE__ ) ) || !current_user_can( 'edit_post', $_REQUEST["post_id"] ) )
			$return = array("response" => 'error', "info" => "Permission denied.");
		else {
			$post_id = intVal($_REQUEST["post_id"]);
			$enable = (trim($_REQUEST["enable"]) == "true");

			$params = $this->get_params($post_id);
			if(empty($params["src"]))
				$return = array("response" => 'error', "info" => "Empty Content URL.");
			else {
				$src = $params["src"];
				$file_path = grassblade_xapi_content::get_path_for_content_to_update( $params["src"] );
				$code_path = grassblade_xapi_content::get_code_path("copy_protect");

				if(empty($file_path))
					$return = array("response" => 'error', "info" => "File not writable.");
				if(empty($code_path))
					$return = array("response" => 'error', "info" => "Code File not readable.");
				else {

					if($enable) {
						$response_text = __("Enable Copy Protect", "grassblade");
						$response = $this->gb_content_add_code_all($file_path, $code_path);
					}
					else {
						$response_text = __("Disable Copy Protect", "grassblade");
						$response = $this->gb_content_remove_code_all($file_path, $code_path);
					}

					$response = $response? "success":"failed";

					$response_text .= ": " . __($response, "grassblade");

					$return = array("response" => $response, "info" => $response_text);
				}
			}
		}

		echo json_encode($return);
		exit();
	}
	static function get_code_path($code_name) {
		$code_path = dirname(__FILE__)."/".$code_name;
		if(file_exists($code_path) && is_readable($code_path))
			return $code_path;
		else if(file_exists($code_path.".txt") && is_readable($code_path.".txt"))
			return $code_path.".txt";
		else
			return "";
	}
	static function gb_content_get_all_related_file_paths($file_path) {
		$all_related_file_paths = array($file_path);

		if(strpos($file_path, "story.html") !== false && strpos($file_path, "story_html5.html") === false && file_exists(dirname($file_path)."/story_html5.html"))
			$all_related_file_paths[] = dirname($file_path)."/story_html5.html";	//Storyline old
		else
		if(strpos($file_path, "index_lms.html") !== false && strpos($file_path, "index_lms_html5.html") === false && file_exists(dirname($file_path)."/index_lms_html5.html"))
			$all_related_file_paths[] = dirname($file_path)."/index_lms_html5.html";	//Storyline new

		if(/* $params["content_tool"] == "articulate_rise" && */ strpos($file_path, "indexAPI.html") !== false && file_exists(dirname(dirname($file_path))."/scormcontent/index.html"))
			$all_related_file_paths[] = dirname(dirname($file_path))."/scormcontent/index.html";	//Rise SCORM

		return $all_related_file_paths;
	}
	static function gb_content_add_code_all($file_path, $code_path) {
		$return = true;
		$all_related_file_paths = self::gb_content_get_all_related_file_paths($file_path);
		if(is_array($all_related_file_paths))
		foreach($all_related_file_paths as $file_path) {
			$r = self::gb_content_add_code($file_path, $code_path);
			if (empty($r))
			$return = false;
		}
		return $return;
	}
	static function gb_content_remove_code_all($file_path, $code_path) {
		$return = true;
		$all_related_file_paths = self::gb_content_get_all_related_file_paths($file_path);
		//print_r($all_related_file_paths);
		if(is_array($all_related_file_paths))
		foreach($all_related_file_paths as $file_path) {
			$r = self::gb_content_remove_code($file_path, $code_path);
			//echo "\n".$file_path.":".$r;
			if (empty($r))
			$return = false;
		}
		return $return;
	}
	static function gb_content_add_code($file_path, $code_path) {
		if(!is_writable($file_path) || !is_readable($code_path))
			return false;

		$pathinfo = pathinfo($code_path);
		$tag = $pathinfo["filename"];
		$add_code = file_get_contents($code_path);
		$add_code = "<!-- Added by GrassBlade - $tag Start -->\n".$add_code."\n<!-- Added by GrassBlade - $tag End -->";

		$file_text = file_get_contents($file_path);
		$has_code = grassblade_xapi_content::gb_content_check_code($file_path, $code_path);

		if(empty($has_code)) {
			$file_text = str_replace("</head>", $add_code."\n</head>", $file_text);
			if(!empty($file_text))
			$return = file_put_contents($file_path, $file_text);
			return !empty($return);
		}
		return true;
	}
	static function gb_content_remove_code($file_path, $code_path) {
		if(!is_writable($file_path) || !is_readable($code_path))
			return false;

		$pathinfo = pathinfo($code_path);
		$tag = $pathinfo["filename"];
		$file_text = file_get_contents($file_path);
		$pattern = "/<!-- Added by GrassBlade - $tag Start(.*)$tag End -->/ism";
		$file_text = preg_replace($pattern, "", $file_text);
		if(empty($file_text)) { //Failed preg_replace? increasing limits and try again.
			ini_set("pcre.backtrack_limit", 100000000);

			$file_text = preg_replace($pattern, "", $file_text);

			if(empty($file_text))
			return false;
		}

		$return = file_put_contents($file_path, $file_text);
		return !empty($return);
	}
	static function gb_content_check_code($file_path, $code_path) {
		if(!is_readable($file_path))
			return false;

		$pathinfo = pathinfo($code_path);
		$tag = $pathinfo["filename"];
		$file_text = file_get_contents($file_path);
		return (strpos($file_text, "Added by GrassBlade") !== false &&  strpos($file_text, $tag." Start") !== false);
	}
	/**
	* defines the fields used in the plugin
	*
	* @since
	* @return void
	*/
	function define_fields($params = array()) {
		global $grassblade;

		$grassblade_settings = grassblade_settings();

	    $grassblade_tincan_endpoint = $grassblade_settings["endpoint"];
	    $grassblade_tincan_user = $grassblade_settings["user"];
	    $grassblade_tincan_password = $grassblade_settings["password"];
		$grassblade_tincan_track_guest = intval($grassblade_settings["track_guest"]);

		$grassblade_tincan_width = $grassblade_settings["width"];
		$grassblade_tincan_height = $grassblade_settings["height"];
		$grassblade_tincan_version = $grassblade_settings["version"];
		$grassblade_tincan_completion_type = empty($grassblade_settings["completion_type"])?'hide_button':$grassblade_settings["completion_type"];
		$secure_token_options = $grassblade["grassblade_xapi_companion"]->secure_token_options;
		$grassblade_tincan_secure_tokens = $secure_token_options[$grassblade_settings["secure_tokens"]];
		//$grassblade_tincan_guest = get_option( 'grassblade_tincan_guest');

		// define the product metadata fields used by this plugin
		$versions = apply_filters('grassblade_content_versions',
								  array(
									'' => __('Use Default', 'grassblade'),
									'none' => __('No Tracking', 'grassblade'),
								  )
					);

		$target = array(
					'' => __('In Page', "grassblade"),
					'_blank' => __('Link to open in New Window', "grassblade"),
					'_self' => __('Link to open in Same Window', "grassblade"),
					'lightbox' => __('Link to open in a Popup Lightbox', "grassblade"),
				);
		$button_type = array(
					'0' => __('Text Link', "grassblade"),
					'1' => __('Button Image', "grassblade"),
				);

		$completion_option = array(
									'' => __('Use Global', 'grassblade'),
									'hide_button' => __('Hide Button', "grassblade"),
									'hidden_until_complete' => __('Show button on completion', "grassblade"),
									'disable_until_complete' => __('Enable button on completion', "grassblade"),
									'completion_move_nextlevel' => __('Auto-redirect on completion', "grassblade"),
								);

		$guest = array(
					'' => __('Use Default', 'grassblade'),
					'1' => __('Allow Guests', 'grassblade'),
					'2' => __('Allow Guests (ask Name/Email)', 'grassblade'),
					'0' => __('Require Login', 'grassblade'),
				);

		$resume_behaviour = array(
					'auto' => __('Auto: Reset Progress After Completion', 'grassblade'),
					'fixed' => __('Fixed: Always Resume', 'grassblade'),
				);

		$h5p_arr = array();

		if(defined("GB_H5P_SUPPORT_ENABLED")) {
			if (current_user_can( 'manage_options' )) {
				$content_query = new H5PContentQuery(array('id', 'title'));
			}else{
				$content_query = new H5PContentQuery(array('id', 'title'), NULL, NULL, NULL, NULL, array(array('user_id', get_current_user_id())));
			}
			$h5p_results = $content_query->get_rows();
			$h5p_options = array("0" => "Select");
			if(!empty($h5p_results)) {
				foreach($h5p_results as $result) {
					$h5p_options[$result->id] = $result->title;
				}
			}
			$h5p_arr = array( 'id' => 'h5p_content', 'label' => __( 'H5P Content', 'grassblade' ), 'title' => __( 'H5P Content', 'grassblade' ), 'placeholder' => '', 'type' => 'select', 'values'=> $h5p_options, 'never_hide' => true ,'help' => '');
		}else{
			$h5p_arr = array( 'id' => 'h5p_content', 'label' => __( 'H5P Content', 'grassblade' ), 'title' => __( 'H5P Content', 'grassblade' ), 'placeholder' => '', 'type' => 'html', 'html' => sprintf(__('Please install %s to select an H5P content. You can create interactive HTML5 Tin Can content using this free plugin.', 'grassblade'), "<a href='https://h5p.org/wordpress' target='_blank'>".__("H5P Plugin", "grassblade")."</a>")."<br><br>", 'values'=> '', 'never_hide' => true );
		}

		add_filter("gb_src_tools", array($this, "copy_protect_button"), 10, 2);
		add_filter("gb_src_tools", array($this, "reset_learner_progress_button"), 10, 2);
		add_filter("gb_src_tools", array($this, "content_versions_button"), 10, 2);
		$gb_src_tools = apply_filters("gb_src_tools", array(), $params);
		$gb_src_tools_buttons = implode("", $gb_src_tools);
		$gb_src_tools_html = empty($gb_src_tools_buttons)?  '':'<span class="dashicons dashicons-admin-tools" onClick="gb_src_tools_show();"></span><span class="gb_src_tools" style="display:none">'.implode(" ", $gb_src_tools).'<span class="dashicons dashicons-no" onClick="gb_src_tools_hide();"></span></span>';

		$upload_limit = $this->upload_limit();
		$this->fields = array(
			array( 'id' => 'selector', 'label' => '', 'title' => '', 'html' => $this->content_selector(), 'placeholder' => '', 'type' => 'html', 'values'=> '', 'never_hide' => true ,'help' => ''),
			array( 'id' => 'src', 'label' => __( 'Content Url', 'grassblade' ), 'title' => __( 'Content Url', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Set the content launch url, or uploading a content will automatically generate it. Needs to be a valid URL. Required', 'grassblade'), 'info' => $gb_src_tools_html),
			array( 'id' => 'xapi_content', 'label' => __( 'Upload Content', 'grassblade' ), 'title' => __( 'Content Url', 'grassblade' ), 'placeholder' => '', 'type' => 'file', 'values'=> '', 'never_hide' => true ,'help' => sprintf(__( 'Your current server upload limit: %s %s', 'grassblade'), $upload_limit, "<a href='https://www.nextsoftwaresolutions.com/kb/error-in-uploading-content/' target='_blank'>".__("Help?", "grassblade")."</a>" )),
			array( 'id' => 'dropbox', 'label' => __( 'DropBox Upload', 'grassblade' ), 'title' => __( 'DropBox Upload', 'grassblade' ), 'placeholder' => '', 'type' => 'html', 'html' => $this->dropbox_chooser(), 'values'=> '', 'never_hide' => true ,'help' => __( 'Upload the file to your server from your Dropbox.', 'grassblade')),
			array( 'id' => 'video', 'label' => __( 'Video URL', 'grassblade' ), 'title' => __( 'Video URL', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __('YouTube, Vimeo, MP3 (audio), MP4, HLS (.m3u8) or MPEG-DASH (.mpd). Enter the URL or upload via direct upload or DropBox ','grassblade') ),
			array( 'id' => 'video_hide_controls', 'label' => __( 'Hide Controls', 'grassblade' ), 'title' => '', 'placeholder' => '', 'type' => 'checkbox', 'values'=> '1', 'never_hide' => true ,'help' => __( 'Check to hide video controls, uncheck to show to users.', 'grassblade')),
			array( 'id' => 'video_autoplay', 'label' => __( 'AutoPlay Video', 'grassblade' ), 'title' => '', 'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => __( 'AutoPlay video as soon as it is loaded (depends on the browser and other factors.)', 'grassblade')),

			$h5p_arr,
			array( 'id' => 'activity_id', 'label' => __( 'Activity ID', 'grassblade' ), 'title' => __( 'A Unique URL', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => sprintf(__('Recommended to leave blank when uploading content, and use the one generated by content, or Set your own activity id, or %s to generate it.  Needs to be a unique URL. Required. Original Activity ID: %s', 'grassblade'), '<a href="#" onClick="return gb_update_activity_id_field()">'.__('click here', 'grassblade').'</a>', @$params['original_activity_id'])),
			array( 'id' => 'target', 'label' => __( 'Where to launch this content?', 'grassblade' ), 'title' => __( 'Where to launch this content?', 'grassblade' ), 'placeholder' => 'Width', 'type' => 'select', 'values'=> $target, 'never_hide' => true ,'help' => __( 'Default: In Page', 'grassblade')),
			array( 'id' => 'button_type', 'label' => __( 'Button Type?', 'grassblade' ), 'title' => __( 'Button Type?', 'grassblade' ),  'type' => 'select', 'values'=> $button_type, 'never_hide' => true ,'help' => ''),
			array( 'id' => 'text', 'label' => __( 'Link text if opening in new window?', 'grassblade' ), 'title' => __( 'Link text if opening in new window?', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Default: Launch', 'grassblade')),
			array( 'id' => 'link_button_image','label' => __( 'Link Button Image?', 'grassblade' ), 'title' => __( 'Link Button Image?', 'grassblade' ), 'placeholder' => '', 'type' => 'image-selector', 'value'=> 'Select', 'never_hide' => true ,'help' => __( 'Select the image you want to show as a button.', 'grassblade')),
			array( 'id' => 'completion_tracking', 'label' => __( 'Completion Tracking', 'grassblade' ), 'title' => __( 'Completion Trigger', 'grassblade' ), 'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => sprintf(__( 'Enable to allow completion tracking. You need to use the metabox dropdown or xAPI Content block to add content, and use %s. ', 'grassblade'), "<a href='https://www.nextsoftwaresolutions.com/grassblade-lrs-experience-api/' target='_blank'>GrassBlade LRS</a>"). "<a href='https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/' target='_blank'>".__("Setup Help?", "grassblade")." </a>.". $this->test_completion_tracking_link()),
			array( 'id' => 'completion_by_module', 'label' => __( 'Completion on Module Completion', 'grassblade' ), 'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => __( 'Fixes completion not happening on iSpring or Articulate Storyline due to only one passed statement received in the LRS. Uncheck if you don\'t have this issue.', 'grassblade')),
			array( 'id' => 'completion_type', 'label' => __( 'Completion Type', 'grassblade' ), 'title' => __( 'Completion type', 'grassblade' ),  'type' => 'select', 'values'=> $completion_option, 'never_hide' => true ,'help' => __('This setting decides the behaviour of Mark Complete button of your LMS.', 'grassblade')." <a href='https://www.nextsoftwaresolutions.com/kb/advanced-completion-behaviour/' target='_blank'>".__('Help?')."</a> ".__( 'Global', 'grassblade').": ".esc_html ( $completion_option[$grassblade_tincan_completion_type]) ),
			array( 'id' => 'passing_percentage', 'label' => __( 'Passing Percentage', 'grassblade' ), 'title' => __( 'Passing Percentage', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Passing Percentage', 'grassblade')),
			array( 'id' => 'show_results', 'label' => __( 'Show Results to Users', 'grassblade' ), 'title' => __( 'Show Results to Users', 'grassblade' ), 'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => __('Enable to show score to users below the xAPI Content.', 'grassblade')),
			array( 'id' => 'show_rich_quiz_report', 'label' => __( 'Show Rich Quiz Report to Users', 'grassblade' ), 'title' => __( 'Show Rich Quiz Report to Users', 'grassblade' ), 'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => __('Enable to show Rich Quiz Report to users.', 'grassblade')." <a href='https://www.nextsoftwaresolutions.com/kb/enable-rich-quiz-report-for-learners/' target='_blank'>".__('Help?')."</a> " ),
			array( 'id' => 'show_here', 'label' => __( 'I want to show the content on this page.', 'grassblade' ), 'title' => __( 'I want to show the content on this page.', 'grassblade' ), 'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => __( 'Check to show the content on this page. Click View above to see.', 'grassblade')),

			array( 'id' => "global_settings", 'label' => __("Override Global Settings", "grassblade"), "type" => "html", "subtype" => "field_group_start"),
			array( 'id' => 'width', 'label' => __( 'Width', 'grassblade' ), 'title' => __( 'Width', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Global', 'grassblade').": ".esc_html ( $grassblade_tincan_width) ),
			array( 'id' => 'height', 'label' => __( 'Height', 'grassblade' ), 'title' => __( 'Height', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'If set in %: When aspect ratio is locked, this is % of width else, it is % of height of window.', 'grassblade')." ".__('Global', 'grassblade').": ".esc_html ( $grassblade_tincan_height) ),
			array( 'id' => 'aspect_lock', 'label' => __( 'Lock Aspect Ratio', 'grassblade' ), 'title' => __( 'Lock Aspect Ratio', 'grassblade' ), 'placeholder' => '', 'type' => 'checkbox', 'values'=> '', 'never_hide' => true ,'help' => __( 'This will lock the Width/Height ratio to make the content responsive but stay in exact ratio. Popular ratios:', 'grassblade')." <span class='grassblade_aspect_ratios'></span> " ),
			array( 'id' => 'version', 'label' => __( 'Version', 'grassblade' ), 'title' => __( 'Version', 'grassblade' ), 'placeholder' => '', 'type' => 'select', 'values'=> $versions, 'never_hide' => true ,'help' => __( 'Set the version of xAPI the content uses. ', 'grassblade'). __( 'Global', 'grassblade').": ".esc_html ( $versions[$grassblade_tincan_version]) ),
			array( 'id' => 'guest', 'label' => __( 'Guest Access', 'grassblade' ), 'title' => __( 'Guest Access', 'grassblade' ), 'placeholder' => '', 'type' => 'select', 'values'=> $guest, 'never_hide' => true ,'help' => __( 'Allow not logged in user to access content. Global: ', 'grassblade').esc_html ( $guest[$grassblade_tincan_track_guest] ) ),
			array( 'id' => 'resume_behaviour', 'label' => __( 'Resume Behaviour', 'grassblade' ), 'title' => __( 'Resume Behaviour', 'grassblade' ), 'placeholder' => '', 'type' => 'select', 'values'=> $resume_behaviour, 'never_hide' => true ,'help' => __( 'Auto: The content resumes where the user left, till completion. Restarts from beginning after completion. (This will automatically generate and use a new registration after every completion if completion tracking is enabled.) <br>Fixed: The content will always resume, unless the registration value is manually changed, or restart is forced.', 'grassblade')),
			array( 'id' => 'registration', 'label' => __( 'Registration', 'grassblade' ), 'title' => __( 'Registration', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'value'=> 'auto', 'never_hide' => true ,'help' => __( 'Registration: Changing this value will mean, all users in the middle of this content will loose their progress and restart from beginning. Value must be in a 36 character UUID format.', 'grassblade')),
			array( 'id' => 'endpoint', 'label' => __( 'Endpoint', 'grassblade' ), 'title' => __( 'Endpoint', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Global', 'grassblade').": ".esc_html ( $grassblade_tincan_endpoint)),
			array( 'id' => 'user', 'label' => __( 'User', 'grassblade' ), 'title' => __( 'User', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Global', 'grassblade').": ".esc_html ( $grassblade_tincan_user) ),
			array( 'id' => 'pass', 'label' => __( 'Password', 'grassblade' ), 'title' => __( 'Password', 'grassblade' ), 'placeholder' => '', 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Global', 'grassblade').": ".esc_html ( $grassblade_tincan_password) ),
			array( 'id' => 'secure_tokens', 'label' => __( 'Secure Tokens', 'grassblade' ),   'placeholder' => '', 'type' => 'select', 'values'=> $secure_token_options, 'never_hide' => true ,'help' => __( 'Generates secure random tokens when launching xAPI Content.', 'grassblade')." ".__("Global", "grassblade").": ".$grassblade_tincan_secure_tokens ),
			array( 'id' => "global_settings_end", "type" => "html", "subtype" => "field_group_end"),
		);
		$this->fields = apply_filters("grassblade_xapi_edit_page_fields", $this->fields, $params);
	}
	function show_results($return, $params, $shortcode_atts, $attr) {
		extract($shortcode_atts);

		if(empty($src) || empty($show_results) || !in_array($target, array("iframe", "_blank", "_self", "lightbox")))
			return $return;

		$user_id = get_current_user_id();
		$content_id = !empty($attr["id"])? $attr["id"]:$this->get_id_by_activity_id($params["activity_id"]);

		if(empty($content_id))
			return $return;

		$score_table = $this->get_score_table($user_id, $content_id);

		return $return.$score_table;
	}

	function get_score_table($user_id, $content_id) {
		$raw_scores = $scores = $this->get_scores($user_id, $content_id);

		foreach ($scores as $key => $score) {
			$row = array();
			$row[__("Date", "grassblade")] = gb_datetime( $score["timestamp"] );
			$row[__("Score", "grassblade")] = $score["score"];
			$row[__("Status", "grassblade")] = __( $score["status"] , "grassblade");
			$row[__("Timespent", "grassblade")] = grassblade_seconds_to_time($score["timespent"]);
			$scores[$key] = $row;
		}
		ob_start();
		?>
		<div id="grassblade_result-<?php echo $content_id; ?>" class="grassblade_show_results">
			<?php
			include_once(dirname(__FILE__)."/../nss_arraytotable.class.php");
			$scores = apply_filters("grassblade_your_scores", $scores, $user_id, $content_id, $raw_scores);
			if(!empty($scores)) {
				$ArrayToTable = new NSS_ArrayToTable($scores);
				?>
				<div><strong><?php _e("Your Results:", "grassblade"); ?></strong></div>
				<?php
				$ArrayToTable->show();
			} ?>
		</div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters("gb_show_results", $html, $scores, $user_id, $content_id);
		return $html;
	}

	function get_scores($user_id, $content_id) {
		global $wpdb;
		$scores = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."grassblade_completions WHERE user_id = '%d' AND content_id = '%d' ORDER BY id DESC", $user_id, $content_id), ARRAY_A);
		$scores = apply_filters("gb_get_scores", $scores, $user_id, $content_id);
		return $scores;
	}
	static function user_score($attr) {
		global $wpdb;

		$shortcode_defaults = array(
	 		'user_id' 		=> null,
	 		'content_id'	=> null,
	 		'show'			=> 'total_score',
	 		'add'			=> null,
		);
		$shortcode_defaults = apply_filters("grassblade_user_score_shortcode_defaults", $shortcode_defaults);

		$shortcode_atts = shortcode_atts ( $shortcode_defaults, $attr);
		$shortcode_atts = apply_filters("grassblade_user_score_shortcode_atts", $shortcode_atts);

		extract($shortcode_atts);

		if(empty($user_id))
		{
			$user = wp_get_current_user();
			$user_id = $user->ID;
			if(empty($user_id))
				return '';
		}

		$where_content_id = (empty($content_id))? " AND 1=1 ":$wpdb->prepare(" AND content_id = %d ", $content_id);

		switch ($show) {
			case 'badgeos_points':
				$shortcode_atts["add"] = "badgeos_points";
				$return = 0;
				break;
			case 'total_score':
				$results = $wpdb->get_results( $wpdb->prepare("SELECT content_id, score, timestamp FROM `{$wpdb->prefix}grassblade_completions` where user_id = %d {$where_content_id} ORDER BY content_id ASC, timestamp ASC", $user_id), ARRAY_A);

				if(empty($results))
					$return = 0;
				else
				{
					$content_ids = array();
					$total_score = 0;
					foreach ($results as $key => $result) {
						$content_id = $result["content_id"];
						if(empty($content_ids[$content_id]))
						{
							$content_ids[$content_id] = $result["score"];
							$total_score += $result["score"];
						}
					}
					//echo "<pre>"; print_r($content_ids); echo "</pre>";
					$return = $total_score;
				}
				break;
			case 'average_percentage':
				$results = $wpdb->get_results( $wpdb->prepare("SELECT content_id, percentage, timestamp FROM `{$wpdb->prefix}grassblade_completions` where user_id = %d {$where_content_id} ORDER BY content_id ASC, timestamp ASC", $user_id), ARRAY_A);
				//echo "<pre>"; print_r($results); echo "</pre>";

				if(empty($results))
					$return = 0;
				else
				{
					$content_ids = array();
					$total_percentage = 0;
					foreach ($results as $key => $result) {
						$content_id = $result["content_id"];
						if(empty($content_ids[$content_id]))
						{
							$content_ids[$content_id] = $result["percentage"];
							$total_percentage += $result["percentage"];
						}
					}
					//echo "<pre>"; print_r($content_ids); echo "</pre>";
					$average_percentage = empty($content_ids)? 0:$total_percentage/count($content_ids);
					$return = $average_percentage;
				}
				break;
			default:
				$return = 0;
				break;
		}

		$return = number_format($return);
		return apply_filters("grassblade_user_score", $return, $attr, $shortcode_atts);
	}
	/*
	function test_completion_tracking($test = false) {
		global $post;
		if(empty($post->ID))
			return "";
		$testing_time = !empty($_GET["testing_time"])? $_GET["testing_time"]:3;
		$test_completion_tracking =  "<a href='".admin_url("post.php?action=edit&test_completion_tracking=1&testing_time=".$testing_time."&post=".$post->ID)."'>".__("Test Setup", "grassblade")." </a>";
		if(!$test)
			return $test_completion_tracking;

		if(empty($_GET["test_completion_tracking"]))
			return "";

		$errors = array();
		$error_html = '';
		$success_html = '';
		$xapi_content = $this->get_params($post->ID);

		$lms_check = LRSTEST::lms_check();
		$errors[] = (!empty($lms_check) && !empty($lms_check["message"]))? "<b>Missing Dependency:</b> ".$lms_check["message"]:"";


		if(empty($xapi_content["completion_tracking"])) {
			$errors[] = __("Completion Tracking is not enabled.", "grassblade");
		}
		if(!empty($xapi_content["activity_id"]))
		{
			$args = array(
					"activity"  => $xapi_content["activity_id"],
					"verb"		=> "http://adlnet.gov/expapi/verbs/passed",
					"since"		=> date(DATE_ATOM, time() - $testing_time*3600),
					"email"		=> "none"
				);
			$statements = get_statement($args);
			if(empty($statements))
			{
				$args["verb"] = "http://adlnet.gov/expapi/verbs/completed";
				$statements = get_statement($args);
			}

			if(empty($statements)) {
				$errors[] = sprintf(__("We haven't seen any statements in the LRS with 'completed' or 'passed' verbs for Activity/Object ID: <u>%s</u> in past %d hours. You might need to attempt the entire content once, or fix your content.", "grassblade"), $xapi_content["activity_id"], $testing_time). " " .sprintf(__("%s to check for past %d hours.", "grassblade"), "<a href='".admin_url("post.php?action=edit&test_completion_tracking=1&testing_time=".($testing_time*2)."&post=".$post->ID)."'>".__("Click here")." </a>", $testing_time*2);

				if(!empty($xapi_content["original_activity_id"]) && $xapi_content["original_activity_id"] != $xapi_content["activity_id"]) {
					$args = array(
							"activity"  => $xapi_content["original_activity_id"],
							"verb"		=> "http://adlnet.gov/expapi/verbs/passed",
							"since"		=> date(DATE_ATOM, time() - $testing_time*3600),
							"email"		=> "none"
						);
					$statements = get_statement($args);

					$original_activity_id_error_message =  sprintf(__("We have found '[verb]' statements in the LRS for the content generated Activity/Object ID: <u>%s</u>. This indicates that your content doesn't accept modification of Activity ID, please change the Activity ID to  <u>%s</u>. Also, please leave the field blank when uploading new content so that content generated Activity ID is configured automatically.", "grassblade"), $xapi_content["original_activity_id"], $xapi_content["original_activity_id"]);
					if(!empty($statements)) {
						$errors[] = str_replace("[verb]", "passed", $original_activity_id_error_message);
					}
					else
					{
						$args["verb"] = "http://adlnet.gov/expapi/verbs/completed";
						$statements = get_statement($args);
						if(!empty($statements)) {
							$errors[] = str_replace("[verb]", "completed", $original_activity_id_error_message);
						}
					}
				}
			}
		}
		else {
			$errors[] = __("Empty Activity ID", "grassblade");
		}

		$posts = grassblade_xapi_content::get_posts_with_content($post->ID);

		if(!empty($posts) && empty($errors))
		{
			$success_html = "<div class='updated'>".__("Everything looks good here.", "grassblade")."</div>";
		}

		$success_html .= "<div class='gb_test_success'>".sprintf(__("If the issue persists try these steps: <br>1. Try our %s <br>2. Make sure you have setup the required Triggers on the LRS using this url: <u>%s</u>. <br>3. %s to read the setup guide again.", "grassblade"), "<a target='_blank' href='".admin_url("admin.php?page=grassblade-lrs-settings")."'>LRS Connection Test</a>", admin_url("admin-ajax.php?action=grassblade_completion_tracking"), "<a href='https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/' target='_blank'>Click here</a>") . "</div>";

		if(!empty($errors))
		{
			foreach ($errors as $key => $error_text) {
				if(!empty($error_text))
				$error_html .= "<div class='gb_test_error'>".$error_text."</div>";
			}
		}
		return "<div class='gb_test'><b>Test Setup Results:</b><br>".$error_html.$success_html."</div>";
	}
	*/
	function dropbox_chooser() {
		$grassblade_settings = grassblade_settings();
		$grassblade_dropbox_app_key = $grassblade_settings['dropbox_app_key'];

		if(empty($grassblade_dropbox_app_key))
		return sprintf(__("Please %s to configure your Dropbox App Key"), "<a href='".admin_url("admin.php?page=grassblade-lrs-settings")."' target='_blank'>".__("click here")."</a>");
		else
		return '<script type="text/javascript" src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs" data-app-key="'.$grassblade_dropbox_app_key.'"></script>
				<div id="dropbox"></div>';
	}
	function content_selector() {
		return '<h2 class="nav-tab-wrapper gb-content-selector">
			<a class="nav-tab nav-tab-content-url" href="#" >Content URL</a>
			<a class="nav-tab nav-tab-video" href="#" >Video</a>
			<a class="nav-tab nav-tab-h5p" href="#" >H5P</a>
			<a class="nav-tab nav-tab-upload" href="#" >Upload</a>
			<a class="nav-tab nav-tab-dropbox" href="#" >Dropbox</a>
		</h2>';
	}
	function form() {
			if(isset($_GET["test"]))
				update_option('grassblade_admin_errors', 'Upload Test: '.$this->upload_tests());

			global $post;
			$data = empty($post->ID)? array():$this->get_params($post->ID);//get_post_meta( $post->ID, 'xapi_content', true );

			$this->define_fields($data);
		?>
			<div id="grassblade_xapi_content_form"><table width="100%">
			<?php
				foreach ($this->fields as $field) {
					if($field["type"]  == "html" && @$field["subtype"] == "field_group_start") {
						echo "<tr><td colspan='2'  class='grassblade_field_group'>";
						echo "<div class='grassblade_field_group_label'><div class='dashicons dashicons-arrow-down-alt2'></div><span>". esc_html( $field["label"] )."</span></div>";
						echo "<div class='grassblade_field_group_fields' style='". esc_attr(@$field["style"])."'><table width='100%'>";
						continue;
					}
					if($field["type"] == "html" && @$field["subtype"] == "field_group_end") {
						echo "</table></div></td></tr>";
						continue;
					}

					$value = isset($data[$field['id']])? $data[$field['id']]:'';
					echo '<tr id="field-'.$field['id'].'"><td width="20%" valign="top"><label for="'.$field['id'].'">'.$field['label'].'</label></td><td width="100%">';
					switch ($field['type']) {
						case 'html' :
							echo $field["html"];
						break;
						case 'text' :
							$value = !isset($data[$field['id']]) && !empty($field['value'])? $field['value']:$value;
							echo '<input  style="width:80%" type="text"  id="'.esc_attr( $field['id'] ).'" name="'. esc_attr( $field['id'] ).'" value="'. sanitize_text_field( $value ).'" placeholder="'. esc_html( $field['placeholder'] ).'"/>';
							if(!empty($field['info']))
							echo '<span class="gb_form_info">'.$field['info'].'</span>';
						break;
						case 'image-selector' :
							echo '<img class="gb_upload-src" src="'.esc_url( $value ).'"  id="'.esc_attr( $field ['id'] ).'-src" style="max-width: 150px; max-height: 50px;"/>';
							echo '<input class="gb_upload-url" type="hidden"  id="'. esc_attr( $field['id'] ).'-url" name="'. esc_attr( $field['id'] ).'" value="'. sanitize_text_field( $value ).'"/>';
							echo '<input class="button button-secondary gb_upload_button" type="button"  id="'.esc_attr( $field['id'] ).'" value="'.esc_attr( $field['value'] ).'"  style="width: 100px;display:block"/>';
						break;
						case 'file' :
							echo '<input  style="width:80%" type="file"  id="'. esc_attr( $field['id'] ).'" name="'. esc_attr( $field['id'] ).'" value="'.sanitize_text_field( $value ).'" placeholder="'.esc_html( $field['placeholder'] ).'"/>';
						break;
						case 'number' :
							echo '<input  style="width:80%" type="number" id="'. esc_attr( $field['id'] ).'" name="'. esc_attr( $field['id'] ).'" value="'. sanitize_text_field( $value ).'" placeholder="'.esc_html( $field['placeholder'] ).'"/>';
						break;
						case 'textarea' :
							echo '<textarea   style="width:80%"  id="'. esc_attr ( $field['id'] ).'" name="'. esc_attr( $field['id'] ).'" placeholder="'.esc_html( $field['placeholder'] ).'">'. esc_textarea( $value ).'</textarea>';
						break;
						case 'checkbox' :
							$checked = !empty($value) ? ' checked=checked' : '';
							echo '<input type="checkbox" id="'. $field['id'].'" name="'.$field['id'].'" value="on"'.$checked.'>';
						break;
						case 'select' :
							echo '<select id="'.$field['id'] .'" name="'. $field['id'] .'">';
							foreach ($field['values'] as $k => $v) :
								$selected = ($value == $k && $value != '') ? ' selected="selected"' : '';
								echo '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
							endforeach;
							echo '</select>';
						break;
						case 'select-multiple':

							echo '<select id="'.esc_attr ( $field['id'] ).'" name="'. esc_attr( $field['id'] ).'[]" multiple="multiple">';

							foreach ($field['values'] as $k => $v) :
								if(!is_array($value)) $value = (array) $value;
								$selected = (in_array($k, $value)) ? ' selected="selected"' : '';
								echo '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
							endforeach;
							echo '</select>';

					}
					if(!empty($field['help'])) {
						$help_text = apply_filters("xapi_content_help_text", $field['help'], $field, $data);
						echo '<br><small>'. $help_text .'</small><br><br>';
						echo '</td></tr>';
					}
				}
				?>
				</table>
				<br>
			</div>
		<?php

	}
	function xapi_content_help_text($help_text, $field, $data) {
		if(!empty($data["content_size"]) && $field["id"] == "src") {
			$help_text = sprintf("(Current content size: %s) ", $this->readable_size($data["content_size"])).$help_text;
		}
		return $help_text;
	}
	function grassblade_xapi_post_edit_form_tag() {
		global $post;
		if((!empty($post->post_type) && $post->post_type == "gb_xapi_content") || !empty($_GET['post_type']) && $_GET['post_type'] == "gb_xapi_content" )
		echo ' enctype="multipart/form-data"';
	}
	function gb_xapi_content_box() {
		add_meta_box(
			'gb_xapi_content_box',
			__( 'xAPI Content Details', 'grassblade' ),
			array($this, 'gb_xapi_content_box_content'),
			'gb_xapi_content',
			'advanced',
			'high'
		);
	}
	static function update_content_info($post_id, $params, $current_params) {

		if(!empty($current_params) && !empty($current_params["src"]) && strtolower( $current_params["src"] ) != strtolower( $params["src"] ) ) {

			if(empty($params["src"]))
			{
				$params["content_path"] = $params["content_url"] = $params["content_filename"] = "";
			}

			$xapi_content_versions = get_post_meta($post_id, "xapi_content_versions");
			$current_src = str_replace(array("http://", "https://"), array("",""), strtolower($params["src"]));

			if(!empty($xapi_content_versions) && is_array($xapi_content_versions))
			foreach ($xapi_content_versions as $key => $content_version) {

				$content_version = maybe_unserialize($content_version);
				if(!empty($content_version["src"])) {

					$src = str_replace(array("http://", "https://"), array("",""), strtolower($content_version["src"]));

					if($current_src == $src) {
						if(isset($content_version["content_path"]))
							$params["content_path"] = $content_version["content_path"];
						if(isset($content_version["content_url"]))
							$params["content_url"] = $content_version["content_url"];
						if(isset($content_version["content_filename"]))
							$params["content_filename"] = $content_version["content_filename"];
						if(isset($content_version["content_size"]))
							$params["content_size"] = $content_version["content_size"];
						if(isset($content_version["content_type"]))
							$params["content_type"] = $content_version["content_type"];
						if(isset($content_version["content_tool"]))
							$params["content_tool"] = $content_version["content_tool"];
						if(isset($content_version["launch_path"]))
							$params["launch_path"] = $content_version["launch_path"];

					}
				}
			}
		}
		if(!empty($params["content_path"])) {
			$path = $params["content_path"];

			if(!file_exists($path)) {
				$path = explode("wp-content", $path, 2);

				if(!empty($path[1]) && file_exists(WP_CONTENT_DIR.$path[1]))
				$params["content_path"] = WP_CONTENT_DIR.$path[1];
				else if(!empty($params["content_url"])) {
					$path = explode("wp-content", $params["content_url"], 2);
					if(!empty($path[1]) && file_exists(WP_CONTENT_DIR.str_replace("/", DIRECTORY_SEPARATOR, $path[1])))
					$params["content_path"] = WP_CONTENT_DIR.str_replace("/", DIRECTORY_SEPARATOR, $path[1]);
				}
			}

			if(!empty($params["content_path"]) && strpos($params["content_path"], "\\\\") === false && strpos($params["content_path"], "\\") !== false)
				$params["content_path"] = addslashes($params["content_path"]);
		}

		if(!empty($params["launch_path"]) && strpos($params["launch_path"], "\\\\") === false && strpos($params["launch_path"], "\\") !== false)
			$params["launch_path"] = addslashes($params["launch_path"]);

		$params["content_size"] = empty($params["content_path"])? "":grassblade_xapi_content::get_size($params["content_path"]);
		return $params;
	}
	static function set_params($post_id, $params) {
		$current_params = get_post_meta( $post_id, 'xapi_content', true );

		if(!empty($params["passing_percentage"]))
			$params["passing_percentage"] = number_format(floatval($params["passing_percentage"]), 2);

		if( !empty($params["video"]) )
			$params["video"] = str_replace("&amp;", "&", $params["video"]);

		$params = grassblade_xapi_content::update_content_info($post_id, $params, $current_params);

		$params = apply_filters("xapi_content_params_update", $params, $post_id);

		if(empty($params["content_tool"]))
			$params["content_tool"] = grassblade_xapi_content::tool( $params );

		if(!empty($params["title"]))
		{
			global $wpdb;
			$wpdb->update($wpdb->posts, array("post_title" => strip_tags( $params["title"] )), array("ID" => $post_id, "post_title" => ""));
			//unset($params['title']);
		}
		if(!empty($params["description"]))
		{
			global $wpdb;
			if(is_admin() && !empty($_GET["page"]) && $_GET["page"] == "grassblade-bulk-import")
			$wpdb->update($wpdb->posts, array("post_content" => wp_kses_post( $params["description"] )), array("ID" => $post_id, "post_content" => ""));

			unset($params["description"]);
		}

		if( !empty($params["resume_behaviour"]) ) {
			if( $params["resume_behaviour"] == "auto" )
			$params["registration"] = "auto";
			else if( $params["resume_behaviour"] == "fixed" ) {
				if( empty( $params["registration"] ) )
				$params["registration"] = "36fc1ee0-2849-4bb9-b697-71cd4cad1b6e";
				else if( trim( $params["registration"] ) == "auto" )
				$params["registration"] = grassblade_gen_uuid();
			}
		}

		grassblade_xapi_content::add_version($post_id, $params);
		update_post_meta( $post_id, 'xapi_content', $params);

		if(isset($params['activity_id']))
			update_post_meta( $post_id, 'xapi_activity_id', $params['activity_id']);
	}
	static function get_size($path)
	{
		if(is_file($path))
			return filesize($path);

		$size = 0;
		foreach( glob(rtrim($path, '/').'/*', GLOB_NOSORT) as $each ) {
			$size += is_file($each) ? filesize($each) : grassblade_xapi_content::get_size($each);
		}
		return $size;
	}
	static function readable_size($bytes, $decimals = 2) {
		$size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}
	static function array_diff($a, $b) {
		if(!empty($a) && (is_array($a) || is_object($a)))
		foreach ($a as $k => $v) {
			if(isset($b[$k]) && $b[$k] != $v)
				return true;
			if(!isset($b[$k]) && !empty($v))
				return true;
		}
		if(!empty($b) && (is_array($b) || is_object($b)))
		foreach ($b as $k => $v) {
			if(isset($a[$k]) && $a[$k] != $v)
				return true;
			if(!isset($a[$k]) && !empty($v))
				return true;
		}
		return false;
	}
	static function add_version($post_id, $params) {
		$xapi_content = get_post_meta( $post_id, 'xapi_content', true );

		if( !grassblade_xapi_content::array_diff($xapi_content, $params) )
			return;

		$version_no = get_post_meta( $post_id, 'xapi_content_version_no', true );

		$xapi_content_versions = get_post_meta($post_id, "xapi_content_versions");
		foreach ($xapi_content_versions as $content_version) {
			if(!empty($content_version["version_no"]) && $content_version["version_no"] > $version_no)
				$version_no = $content_version["version_no"];
		}

		$version_no = empty($version_no)? 1:(intVal($version_no) + 1);

		if(!empty($xapi_content["version_no"]) && $xapi_content["version_no"] == $version_no)
			$version_no++;

		$params["version_no"] 	= $version_no;
		$params["timestamp"]	= time();
		update_post_meta( $post_id, 'xapi_content_version_no', $version_no );
		add_post_meta( $post_id, 'xapi_content_versions', $params );
	}
	static function get_params($post_id) {
		$xapi_content = (array) get_post_meta( $post_id, 'xapi_content', true);

		if(!isset($xapi_content['version'])) {  //For Version older than V0.5
			$xapi_content['version'] = get_post_meta( $post_id, 'xapi_version', true);
			if(!empty($xapi_content['notxapi'])) {
				$xapi_content['version'] = "none";
				unset($xapi_content['notxapi']);
				grassblade_xapi_content::set_params( $post_id, $xapi_content );
			}
		}
		if(isset($xapi_content['launch_url'])) {
			$xapi_content['src'] = $xapi_content['launch_url'];
			unset($xapi_content['launch_url']);
			grassblade_xapi_content::set_params( $post_id, $xapi_content);
		}
		if( isset($xapi_content["registration"]) )
		if( empty( $xapi_content["resume_behaviour"] ) ) {
			$xapi_content["resume_behaviour"] = ( trim( $xapi_content["registration"] ) == "auto" )? "auto":"fixed";
			grassblade_xapi_content::set_params( $post_id, $xapi_content );
		}

		$xapi_content['activity_id'] = isset($xapi_content['activity_id'])? $xapi_content['activity_id']:"";
		return $xapi_content;
	}
	function get_shortcode($post_id, $return_params = false) {
		$xapi_content = $this->get_params($post_id);
		if(empty($xapi_content["activity_name"])) {
			$xapi_content_post = get_post($post_id);
                        $xapi_content["activity_name"] = !empty($xapi_content_post->post_title)? $xapi_content_post->post_title:"";
		}
		if(empty($xapi_content["button_type"])) {
			unset($xapi_content["link_button_image"]);
		}
		$params = array();
		if((!isset($xapi_content['version']) || $xapi_content['version'] != "none")) {
			$xapi_content_fields = array("width", "height", "aspect_lock", "target", "video","activity_name", "version", "src", "text", "link_button_image", "guest","src","endpoint","user","pass","auth","registration", "activity_id", "youtube_id", "show_results","show_rich_quiz_report", "passing_percentage", "video_autoplay", "video_hide_controls", "content_type");
			$xapi_content_fields = apply_filters("grassblade_xapi_content_fields", $xapi_content_fields, $xapi_content);

			$shortcode = "[grassblade ";
			foreach($xapi_content as $k=>$v) {
				if($v != '' && in_array($k, $xapi_content_fields)) {
					$shortcode .= $k.'="'.$v.'" ';
					$params[$k] = $v;
				}
			}
			$shortcode .= "]";
		}
		else
		{
			$xapi_content_fields = array("width", "height", "aspect_lock", "target", "video", "activity_name","version", "src", "text", "link_button_image", "guest", "youtube_id", "content_type");
			$xapi_content_fields = apply_filters("grassblade_xapi_content_fields", $xapi_content_fields, $xapi_content);

			$src = $xapi_content['src'];
			$shortcode = "[grassblade ";
			foreach($xapi_content as $k=>$v) {
				if($v != '' && in_array($k, $xapi_content_fields)) {
					$shortcode .= $k.'="'.$v.'" ';
					$params[$k] = $v;
				}
			}
			$shortcode .= "]";
		}
		if($return_params)
			return $params;
		else
			return $shortcode;
	}

	function gb_xapi_content_box_content($post ){
		global $wpdb;
		wp_nonce_field( plugin_basename( __FILE__ ), 'gb_xapi_content_box_content_nonce' );
		$xapi_content = $this->get_params($post->ID);

		//$this->dropbox_chooser();
		$html = '';
		$has_content = (!empty($xapi_content['src']) || !empty($xapi_content['video']));
		$upload_message = $has_content? "":__("You haven't uploaded any package yet. Select the TinCan zip package using the uploader below and click on Publish/Update.", "grassblade");

		//$src = grassblade(array("target" => "url") + $xapi_content);
		$preview = get_permalink($post->ID);
		$preview .= strpos($preview, "?")? "&xapi_preview=true":"?xapi_preview=true";
		$html .= '<div id="gb_preview_message" class="'.($has_content? "has_content":"").'"><div><a class="button button-primary button-large" href="'.$preview.'" target="_blank">'.__("Preview", "grassblade").'</a></div>';
		$html .= "<br><b>".__('Add this xAPI Content using the xAPI Content Gutenberg Block, or Metabox Dropdown, or use the following shortcode in your content:', 'grassblade').'</b><br>';
		$html .= '<input style="" value="[grassblade id='.$post->ID.']" /><br><br><br></div>';
		$html .= '<div id="gb_upload_message" class="'.($has_content? "has_content":"").'">'.$upload_message.'</div>';
		$html .= '<div id="gb_progress" style="margin-bottom:10px;">
					<span id="gb_progress_text"></span>
					<div id="gb_progress_bar"></div>
				  </div>';

		if($xapi_content['version'] != "none" && $xapi_content['version'] != "0.9"  && $post->post_status != "auto-draft" && !strpos($xapi_content['activity_id'], "://")) {
			echo "<div class='gb_test gb_red'>".__(" Activity ID is not a valid URI", "grassblade")."</div>";
		}
		if($xapi_content['version'] != "none" && $post->post_status != "auto-draft" && !empty($xapi_content['activity_id'])) {
			$content_ids = $wpdb->get_results($wpdb->prepare("SELECT * FROM  $wpdb->postmeta WHERE meta_key = 'xapi_activity_id' AND meta_value='%s' AND post_id <> '%d'", $xapi_content['activity_id'], $post->ID));
			if(!empty($content_ids)) {
				$content_names = "";
				foreach ($content_ids as $key => $value) {
					$cp = get_post($value->post_id);
					if($cp->post_status == "publish")
					$content_names .= "<li><a target='_blank' href='".get_edit_post_link($cp->ID)."'>".$cp->ID.". ".$cp->post_title."</a></li>";
				}
				if(!empty($content_names))
				echo "<div class='gb_test gb_red'>".__("Activity ID already exists on another xAPI Content: ", "grassblade")."<br><ol>".$content_names."</ol></div>";
			}
		}
		if( isset($xapi_content["src"]) && strpos($xapi_content["src"], "http://") === 0 )
		{
			echo "<div class='gb_test'>".__("Warning: HTTP Content URL's will not work if launched from HTTPS page.", "grassblade"). " (<a href='https://www.nextsoftwaresolutions.com/kb/changing-from-http-to-https-website/' target='_blank'>".__("Help?")."</a>)</div>";
		}

		//echo $this->test_completion_tracking(true);
		echo $html;
		$this->form();
		echo '<input type="submit" name="publish" id="publish" class="button button-primary button-large" value="'.__("Update", "grassblade").'" onClick="grassblade_update();"/><br><br>';

		if(!grassblade_settings("disable_statement_viewer")) {
			echo "<div id='grassblade_statementviewer'>".do_shortcode("[grassblade_statementviewer activityid='".$xapi_content['activity_id']."']")."</div>";
		}

		if(!empty($xapi_content["completion_tracking"]))
		echo "<div id='grassblade_leaderboard'><div style='font-size: 16px; margin-bottom: 10px;'><b>".__("Leaderboard (Top 20):", "grassblade")."</b></div>".do_shortcode("[gb_leaderboard id='".$post->ID."']")."<br>".__("Add the shortcode <code>[gb_leaderboard id='".$post->ID."']</code> to any page to show this Leaderboard", "grassblade")."</div>";

	}
	function test_completion_tracking_link() {
		global $post;
		if( empty($post->ID) )
		return '';

		$url = admin_url("admin-ajax.php?action=show_completion_test&user_id=&content_id=".$post->ID);
		return "<br>".do_shortcode("[grassblade src='$url' target='lightbox' class='button' text='Test Completion Tracking' version='none' ]");
	}

	function cURLdownload($url, $file){

	  if(!function_exists("curl_init"))
	  	return "FAIL: curl_init() not available.";
	  $ch = curl_init();

	  if($ch)
	  {
		$fp = fopen($file, "w");
		if($fp)
		{
		  if( !curl_setopt($ch, CURLOPT_URL, $url) )
		  {
			fclose($fp); // to match fopen()
			curl_close($ch); // to match curl_init()
			return "FAIL: curl_setopt(CURLOPT_URL)";
		  }

		  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		  if( !curl_setopt($ch, CURLOPT_FILE, $fp) ) return "FAIL: curl_setopt(CURLOPT_FILE)";
		  if( !curl_setopt($ch, CURLOPT_HEADER, 0) ) return "FAIL: curl_setopt(CURLOPT_HEADER)";
		  if( !curl_exec($ch) ) return array('error' => curl_error($ch));//"FAIL: curl_exec()";

		  curl_close($ch);
		  fclose($fp);
		  return true;
		}
		else return "FAIL: fopen()";
	  }
	  else return "FAIL: curl_init()";
	}
	function gb_xapi_content_box_save( $post_id ) {
		$post = get_post( $post_id);
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

		if ( !isset($_POST['gb_xapi_content_box_content_nonce']) || !wp_verify_nonce( $_POST['gb_xapi_content_box_content_nonce'], plugin_basename( __FILE__ ) ) )
		return;


		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
			return;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) )
			return;
		}

		if( 'gb_xapi_content' != $_POST["post_type"] || !empty($post->post_type) && $post->post_type != "gb_xapi_content" )
			return;

		//$xapi_version = isset($_POST['xapi_version'] )? $_POST['xapi_version'] : "";
		//update_post_meta( $post_id, 'xapi_version', $xapi_version );

		$this->define_fields();
		$data = $this->get_params($post->ID);

		foreach ( $this->fields as $field ) {
			if(isset($_POST[$field['id']]))
			$data[$field['id']] = esc_attr( trim($_POST[$field['id']] ));

			if($field["type"] == "checkbox")
				$data[$field['id']] = !empty($_POST[$field['id']]);

			if($field["id"] == "activity_id" && $data[$field['id']] == "[GENERATE]")
				$data[$field['id']] = rtrim(get_permalink($post_id),'/');
		}

		$this->set_params( $post->ID, $data);
		$this->debug($data);
	}

	/**
	 * Process Upload.
	 *
	 * @param obj $post.
	 * @param array $data.
	 * @param array $upload.
	 *
	 * @return array $upload.
	 */
	function process_upload($post, $data, $upload) {
		/*
		$upload = Array
		(
		    [file] => Full Path of ZIP file
		    [url] => Full URL of ZIP file
		    [type] => application/zip
		)
		*/
		/* $data = Array params data */

		$post_id = $post->ID;
		$this->debug("No upload error");
		$upload = array_merge($data, $upload);
		$content_at = $this->grassblade_handle_contentupload($upload, $post);

		if(empty($content_at["content_path"]))
			return $content_at;

		// no errors, do what you like
		$this->debug("Merged arrays");
		$this->debug($upload);

		if($content_at) {
			$upload['content_path'] 	= realpath($content_at['content_path']);
			$upload['content_url'] 		= $content_at['content_url'];
			$upload['content_size'] 	= $content_at['content_size'];
			$upload['content_filename'] = $content_at['content_filename'];
		} else{
			if (isset($upload['content_path']))
				unset($upload['content_path']);
			if (isset($upload['content_url']))
				unset($upload['content_url']);
			if (isset($upload['content_size']))
				unset($upload['content_size']);
			if (isset($upload['content_filename']))
				unset($upload['content_filename']);
		}
		unset($upload["file"]);
		unset($upload["url"]);

		$params = $this->get_params( $post_id );
		$params['process_status'] = 0;

		$params = apply_filters('grassblade_process_upload', $params , $post , $upload);

		if ($params['process_status'] == 0) {
			$info = !empty($params["info"])? $params["info"]:__("Incompatible content. Processing failed.", "grassblade");
			$info = str_replace(ABSPATH, "", $info);
			$upload_path = !empty($upload["path"])? $upload["path"]:"";
			return array("path" => $upload_path, "upload" => $upload, "response" => 'error', "info" => $info);
		}

		if (array_key_exists('process_status', $params)) {
			unset($params['process_status']);
		}

		$this->set_params( $post_id, $params);
		return $params;
	}
	function grassblade_handle_contentupload($upload, $post) {
		$post_id = $post->ID;
		$sub_dir = $post->ID.'-'.$post->post_name;

		add_filter('upload_dir', array($this, 'grassblade_upload_dir'));
		add_filter('upload_mimes', array($this, 'upload_mimes'));
		$upload_dir = wp_upload_dir();

		$to = $upload_dir['path']."/".$sub_dir;
		$url = $upload_dir['url']."/".$sub_dir;
		WP_Filesystem();

		if (is_dir($to)) {
			$post_dir = glob($upload_dir['path'] . '/'.$post_id.'-*' , GLOB_ONLYDIR);
			$i = count($post_dir);
			while (is_dir($upload_dir['path']."/".$sub_dir.'-'.$i)) {
				$i++;
			}
			$to = $upload_dir['path']."/".$sub_dir.'-'.$i;
			$url = $upload_dir['url']."/".$sub_dir.'-'.$i;
		}

		$file_name = '';
		if( $upload["type"] == "folder" && is_dir($upload['file']) ) {
			$file_name = basename($upload['file']);

			// Move folder contents from $upload['file'] to $to
			$move = rename($upload['file'], $to.'/');
			if(empty($move)) {
				$response = array("response" => 'error', "info" => __("Failed to move folder. Please check permissions.", "grassblade"));
				return $response;
			}
		} else {
			mkdir($to);
		}

		if( empty($move) ) {
			$file_info = pathinfo($upload['file']);
			$file_name = $file_info["filename"];
			if ($file_info['extension'] == 'zip') {
				$size = (intVal(filesize($upload['file']) / (1024 * 1024)) + 128) . "M" ;
				ini_set('memory_limit', $size );

				$unzip = unzip_file($upload['file'], $to);
				if(is_wp_error($unzip)){
					$response =  array("response" => 'error', "info" => $unzip->get_error_message() );
					return $response;
				} else {
					unlink($upload['file']);
				}
			} else {
				$to = $to.'/'.$file_info['basename'];
				$url = $url.'/'.$file_info['basename'];
				$move = rename($upload['file'],$to);
			}
		}

		$content_size = grassblade_xapi_content::get_size($to);
		return array('content_path' => $to, 'content_url' => $url, 'content_size' => $content_size, "content_filename" => $file_name);
	}
	function upload_tests() {
		add_filter('upload_dir', array($this, 'grassblade_upload_dir'));
		$upload = wp_upload_dir();
		$info = "<br><br><b><u>Running exhaustive Tests.</u></b><br><b>Upload Folder Path:</b> ".$upload["path"]."<br>";
		$folder_exists = file_exists($upload["path"]);
		$info .= "<b>Folder Exists?</b> ".( $folder_exists? "Yes":"No" )."<br>";
		if(empty($folder_exists)) {
			$mkdir = mkdir($upload["path"]);
			$folder_exists = file_exists($upload["path"]);
			$info .= "<b>Creating Folder:</b> ".( $folder_exists? "Success":"Failed. Need enough Permissions to create folders. Create folder <i>".$upload["path"]."</i> with 744, 774 or 777 permission, whichever works, or contact your server admin." )."<br>";
		}
		$info .= "<b>Upload Folder Permission:</b> ".decoct(fileperms($upload["path"]) & 0777)."<br>";
		$copy_file = $upload["path"]."/test.zip";
		copy(dirname(__FILE__)."/test.zip", $copy_file);
		$copy = file_exists($copy_file);
		$info .= "<b>Copy a file to Folder Path:</b> ".((!empty($copy))? "Passed":"Failed.");

		if(!file_exists($upload["path"]."/test_folder/")) {
			$mkdir = mkdir($upload["path"]."/test_folder/");
			$folder_exists = file_exists($upload["path"]."/test_folder/");
			rmdir($upload["path"]."/test_folder/");
			$info .= "<br><b>Creating test Folder:</b> ".( $folder_exists? "Success":"Failed. Need enough Permissions to create folders. Change folder permission for <i>".$upload["path"]."</i> to 755, 775 or 777, whichever works, or contact your server admin." )."<br>";
		}
		if($copy) {
			$unzip = unzip_file($upload["path"]."/test.zip", $upload["path"]);

			if($unzip === true) {
				unlink($copy_file);
				unlink($upload["path"]."/empty");
				$info .= "<b>Unzip test file: Success";
			}
			else
				$info .= "<b>Unzip test file: Failed. Need enough permissions to unzip files. Change folder permission for <i>".$upload["path"]."</i> to 744, 774 or 777, whichever works, or contact your server admin. <br>";

		}
		$info .= "<br><b>Possible Suggestions that might fix the issue:</b><br>1. Change permissions on <i>".$upload["path"]."</i> to 744, 774 or 777, whichever works, or contact your server admin.<br>";
		$user_group = $this->get_php_user_group();
		$info .= "2. Try changing the user/group of the folder to: ".$user_group.", running this command will do it on Linux/Mac: <pre><i>chown -R ".$user_group." ".$upload["path"]."</i></pre>";
		$info .= "3. Try adding this line to wp-config.php file:<i><pre>define('FS_METHOD', 'direct');</pre></i>";

		remove_filter('upload_dir', array($this, 'grassblade_upload_dir'));
		return $info;
	}
	function get_php_user_group() {
            $php_u = null;

            if ( function_exists( 'posix_getpwuid' ) ) {
                    $u = posix_getpwuid( posix_getuid() );
                    $g = posix_getgrgid( $u['gid'] );
                    $php_u = $u['name'] . ':' . $g['name'];
            }

            if ( empty( $php_u ) and isset( $_ENV['APACHE_RUN_USER'] ) ) {
                    $php_u = $_ENV['APACHE_RUN_USER'];
                    if ( isset( $_ENV['APACHE_RUN_GROUP'] ) ) {
                            $php_u .= ':' . $_ENV['APACHE_RUN_GROUP'];
                    }
            }

            if ( empty( $php_u ) and isset( $_SERVER['USER'] ) ) {
                    $php_u = $_SERVER['USER'];
            }

            if ( empty( $php_u ) and function_exists( 'exec' ) ) {
                    $php_u = exec( 'whoami' );
            }

            if ( empty( $php_u ) and function_exists( 'getenv' ) ) {
                    $php_u = getenv( 'USERNAME' );
            }

            return $php_u;
    }

	static function is_completion_tracking_enabled($content_id) {
		$completion = get_post_meta($content_id, "xapi_content", true);
		return !empty($completion["completion_tracking"]);
	}
	static function get_completion_type($content_id) {
		$content_data = get_post_meta($content_id, "xapi_content", true);
		$completion_type = isset($content_data['completion_type'])? $content_data['completion_type'] : '';

		if (empty($completion_type)) {
			$grassblade_settings = grassblade_settings();
			$completion_type = isset($grassblade_settings['completion_type'])? $grassblade_settings['completion_type']:"hide_button";
		}
		return $completion_type;
	}
	static function get_content_post_meta_keys() {
		$keys = apply_filters("grassblade_get_content_post_meta_keys", array('show_xapi_content'));
		foreach ($keys as $k => $v) {
			$keys[$k] = sanitize_key($v);
		}
		return $keys;
	}
	static function get_posts_with_content($content_id) {
		if(empty($content_id) || !is_numeric($content_id))
			return array();
		else
		{
			global $wpdb;
			$keys = grassblade_xapi_content::get_content_post_meta_keys();
			$post_ids 	= $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key IN ('".implode("','", $keys)."') AND meta_value = '%d'", $content_id));

			if(empty($post_ids))
				return array();

			$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN (".implode(",", $post_ids).") AND post_status IN ('publish', 'draft') ORDER BY post_title");

			return empty($posts)? array():$posts;
		}
	}
	static function get_post_xapi_contents($post_ids, $with_completion_tracking_enabled_only = false) {

		if (empty($post_ids)) {
			return array(); //Empty post ID
		}
		global $wpdb;

		$keys = grassblade_xapi_content::get_content_post_meta_keys();
		if(is_numeric($post_ids)) {
			$post_id = intVal($post_ids);
			$all_content_ids 	= $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key IN ('".implode("','", $keys)."') AND post_id = '%d' AND meta_value > 0 ORDER BY LENGTH(meta_key) DESC, meta_id ASC", $post_id));
		}
		else if(is_array($post_ids)) {
			$post_ids = array_map("intval", $post_ids);
			$all_content_ids 	= $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key IN ('".implode("','", $keys)."') AND post_id IN (".implode(",", $post_ids).") AND meta_value > 0 ORDER BY LENGTH(meta_key) DESC, meta_id ASC");
		}

		if(empty($all_content_ids))
			return array();

		$contents_with_completion_tracking_enabled = array();

		if( $with_completion_tracking_enabled_only ) {
			foreach ($all_content_ids as $content_id) {
				if( grassblade_xapi_content::is_completion_tracking_enabled($content_id) ) {
					$contents_with_completion_tracking_enabled[] = $content_id;
				}
			}
			return $contents_with_completion_tracking_enabled;
		}

		return $all_content_ids;
	}
	static function is_completion_tracking_enabled_by_post($post_id) {

		if (empty($post_id)) {
			return false; //Empty post ID
		}

		$all_content_ids = grassblade_xapi_content::get_post_xapi_contents($post_id, $with_completion_tracking_enabled_only = true);

		if (!empty($all_content_ids)) {
			return true;
		}

		$post = get_post($post_id);
		if ($post->post_type == 'gb_xapi_content') {
			$content_data = get_post_meta($post_id, "xapi_content", true);
			if (!empty($content_data['completion_tracking'])) {
				return true;
			}
		}
		return false;
	} // end of is_completion_tracking_enabled_by_post function

	static function post_contents_completed($post_id,$user_id = null) {
		if ($user_id == null) {
			$current_user = wp_get_current_user();
			if (empty($current_user)) {
				return false;
			} else {
				$user_id = $current_user->ID;
			}
		}
		$all_content_ids = grassblade_xapi_content::get_post_xapi_contents($post_id, $with_completion_tracking_enabled_only = true);

		if(empty($all_content_ids) || count($all_content_ids) == 0) {
			return true;	//No content with completion tracking means completed
		}

		$completion_tracking_enabled = false;
		$enabled_statements = array();

		foreach ($all_content_ids as $content_id) {
			$completed = get_user_meta($user_id, "completed_".$content_id, true);
			if( empty($completed) ) { //Completion Tracking enabled, but content not completed, means not completed.
				return false;
			} else {
				array_push($enabled_statements,$completed);
			}
		} // end of foreach

		return $enabled_statements;
	} // end of post_contents_completed function

	static function post_completion_type($post_id) {

		$all_content_ids = grassblade_xapi_content::get_post_xapi_contents($post_id, $with_completion_tracking_enabled_only = true);

		if (empty($all_content_ids)) {
			return apply_filters("grassblade_post_completion_type", false, $post_id);
		} else {

			$last_content_id = end($all_content_ids);
			$completion_type = grassblade_xapi_content::get_completion_type($last_content_id);

			return apply_filters("grassblade_post_completion_type", $completion_type, $post_id);
		}
	} // end of completion_type function

	static function last_post_content_with_completion_tracking($post_id) {

		$all_content_ids = grassblade_xapi_content::get_post_xapi_contents($post_id,true);

		if (empty($all_content_ids)) {
			return false;
		} else {

			$last_content_id = end($all_content_ids);
			return $last_content_id;
		}
	} // end of last_post_content_with_completion_tracking function

	static function is_inprogress($content_id,$user_id = null){

		if (empty($user_id)) {
			$user_id = get_current_user_id();
		}

		$started = get_user_meta($user_id, "content_started_".$content_id, true );
		if (!empty($started)) {
			return true;
		}
		return false;
	} // end of is_inprogress function


	static function tool( $content_data ) {

		if(!empty($content_data["content_tool"]))
			return $content_data["content_tool"];

		if(empty($content_data["content_path"]))
			return '';

		if(file_exists($content_data["content_path"]."/res/lms.js"))
			return "ispring";
		else if(file_exists($content_data["content_path"]."/lib/main.bundle.js") || file_exists($content_data["content_path"]."/scormcontent/lib/main.bundle.js"))
			return 'articulate_rise';
		else if(file_exists($content_data["content_path"]."/story.html"))
			return 'articulate_storyline';
		else if(file_exists($content_data["content_path"]."/presentation_content/presentation.js"))
			return 'articulate_presenter';
		else if(file_exists($content_data["content_path"]."/assets/js/CPM.js"))
			return 'captivate';
		else if(file_exists($content_data["content_path"]."/launch.html") && strpos(file_get_contents($content_data["content_path"]."/launch.html"), "learning.elucidat"))
			return 'elucidat';
		else
			return '';
	}

	static function get_list() {
		global $wpdb;
		$contents_list = array();
		$xapi_contents = $wpdb->get_results("SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish' ORDER BY post_title ASC");
		//$xapi_contents = get_posts("post_type=gb_xapi_content&orderby=post_title&posts_per_page=-1");

		foreach ($xapi_contents as $xapi_content) {

			$completion_tracking = grassblade_xapi_content::is_completion_tracking_enabled($xapi_content->ID);
			$contents_list[] = array(
							'id' => $xapi_content->ID,
						  	'post_title' => $xapi_content->post_title,
						  	'completion_tracking' => $completion_tracking,
						 );
		} // end of for each

		return $contents_list;
	}
	static function get_permalink($content_id, $show_here = true) {
		if($show_here)
			return get_permalink($content_id);

		$posts = grassblade_xapi_content::get_posts_with_content($content_id);
		if(!empty($posts[0]))
			return get_permalink($posts[0]->ID);
		else
			return "";
	}
	function content_launch() {
		$content_id = !is_numeric($_REQUEST['content_id'])? intVal(base64_decode($_REQUEST['content_id'])):intval($_REQUEST['content_id']);
		$content_data = grassblade_xapi_content::get_params($content_id);
		$content_type = !empty($content_data["content_type"])? $content_data["content_type"]:"";
		$grassblade_settings = grassblade_settings();

		$version = empty($content_data["version"])? $grassblade_settings["version"]:$content_data["version"];

		if( empty( $version ) && $content_type != "video")
		{
			//echo "<pre>"; print_r($content_data); echo "testing"; exit;
			http_response_code(404);
			exit;
		}
		switch($version) {
			case 'cmi5':
				$content_data["version"] = $version;
				grassblade_cmi5::launch($content_id, $content_data);
				break;
			case 'scorm1.2':
			case 'scorm2004':
				global $gb_scorm;
				if( method_exists($gb_scorm, "scorm_launch" ) )
					$gb_scorm->scorm_launch($content_id, $content_data);
				else
					http_response_code(404);
				break;
			case '1.0':
			case '0.9':
			case '0.95':
			case '':
			case 'none': //No Tracking
				if( $content_type == "video" )
					grassblade_video_launch();
				else
				{
					//xAPI Launch function.
					$content_data["version"] = $version;
					grassblade_xapi::launch($content_id, $content_data);
				}
				break;
		}
	}
	static function get_attempts($content_id, $user_id, $registration_id = 0) {
		global $wpdb;

		if(empty($content_id) || empty($user_id))
			return array();

		$attempts = array();
		$sql = "SELECT * FROM {$wpdb->prefix}grassblade_completions WHERE content_id = %d AND user_id = %d ";

		if(!empty($registration_id) && wp_is_uuid($registration_id))
			$sql .= " AND registration_id = " . $registration_id;

		$sql = $wpdb->prepare($sql, $content_id, $user_id);
		return $wpdb->get_results($sql, ARRAY_A);
	}
}

$xc = new grassblade_xapi_content();
$xc->run();

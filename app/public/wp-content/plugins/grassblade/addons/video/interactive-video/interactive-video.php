<?php

/*
	Things can be added in question settings:
		1. Message for correct/incorrect.
		2. Points for the questions.
		3. Allow Skip
		4. Can submit again, like h5p interactive video.
*/

class grassblade_questions
{
	function __construct()
	{
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		add_action('admin_menu', array($this, 'menu'), 11);
		add_action('plugins_loaded', array($this, "plugins_loaded"));
	}

	function plugins_loaded()
	{
		add_action('admin_enqueue_scripts', array($this, 'add_style_and_script'), 10, 1);
		add_filter('grassblade_xapi_edit_page_fields', array($this, "settings"), 10, 2);
		add_filter('grassblade_add_scripts_on_page', array($this, 'add_to_scripts'));
		add_action('wp_ajax_save_question', array($this, 'save_question'));
		add_action('wp_ajax_delete_question', array($this, 'delete_question'));
		add_filter("grassblade/video/extra_html", array($this, "add_video_quiz_data"), 10, 3);
	}

	function menu()
	{
		if( !empty($_GET['content_id']) && current_user_can("edit_post", intVal($_GET['content_id'])) )
		add_submenu_page("", __('Quiz Questions', "grassblade"), __('Quiz Questions', "grassblade"), 'read', 'grassblade-questions', array($this, 'menu_page'));
	}

	function add_to_scripts($grassblade_add_scripts_on_page)
	{
		$grassblade_add_scripts_on_page[] = "grassblade-questions";
		return $grassblade_add_scripts_on_page;
	}

	function menu_page()
	{
		self::create_questions();
	}

	function add_style_and_script($admin_page)
	{
		if ($admin_page !== "admin_page_grassblade-questions")
			return;

		wp_enqueue_style('interactive_video_style', plugins_url('/style.css', __FILE__));
		wp_enqueue_script('interactive_video_script', plugins_url('/script.js', __FILE__), array(), '1.0.0', true);

		$content_id = !empty($_GET['content_id']) ? intVal($_GET['content_id']) : 0;
		$questions = self::get_saved_questions($content_id);
		$GB_QUESTION_DATA = array(
			"questions" => $questions
		);
		wp_localize_script('interactive_video_script', 'GB_QUESTION_DATA',  $GB_QUESTION_DATA);
	}

	public function settings($fields, $params)
	{
		if(isset($_GET['post']) && !empty($params["video"])) {
			$post_id = intVal( $_GET['post'] );
			$questions =  self::get_saved_questions($post_id);

			$button_text =  __('Open Quiz Builder', 'grassblade');
			if(!empty($questions))
			$button_text = $button_text . " (". sprintf( __("%d questions", "grassblade"), count($questions)).") ";


			$html = '<a target="_blank" class="button button-primary" href="' . get_admin_url() . 'admin.php?page=grassblade-questions&content_id=' . $post_id . '">'.$button_text.'</a>';
			$help_text = __("Add questions at specific points in the video to make it interactive.", "grassblade");

			$f = array('id' => 'interactive_video', 'label' => __('Interactive Video', 'grassblade'), 'title' => __('Interactive Video', 'grassblade'), 'placeholder' => '', 'type' => 'html', 'values' => '', 'never_hide' => true, 'html' => $html, 'help' => $help_text);

			$new_fields = array();
			foreach($fields as $k => $field) {
				$new_fields[] = $field;

				if($field["id"] == "video")
				$new_fields[] = $f;
			}
			return $new_fields;
		}
		return $fields;
	}

	public static function delete_question()
	{
		$data 			= $_REQUEST;
		$question_id 	= empty($data['question_id']) ? 0 : intVal($data['question_id']);
		$post_id 		= intVal($data['post_id']);

		global $wpdb;
		$status = $wpdb->delete("{$wpdb->prefix}grassblade_questions", array('ID' => $question_id), array('%d'));

		$sql = $wpdb->last_query;
		$error = ($status === false) ? $wpdb->last_error : "";

		//grassblade_debug("sql: " . print_r($sql, true));
		$questions = array();
		$questions = self::get_saved_questions($post_id);

		$return = array(
			"status" => (!empty($status)),
			"questions" => $questions,
			"sql" => $sql,
			"error" => $error
		);

		echo json_encode($return);
		exit;
	}

	public static function save_question()
	{
		$data = $_REQUEST;
		$post_id 			= intVal($data['post_id']);

		if ($data['action'] != "save_question" || get_post_type($post_id) != "gb_xapi_content")
			return;

		$question_id 		= empty($data['question_id']) ? 0 : intVal($data['question_id']);
		$question_title 	= !empty($data['question_title']) ? strip_tags($data['question_title']) : "";
		$question_type  	= !empty($data['question_title']) ? strip_tags($data['question_type']) : "";
		$options 			= empty($data['options']) ? array() : array_map('strip_tags', $data['options']);
		$correct_choices 	= isset($data['correct_choice']) ? $data['correct_choice'] : "";
		$timestamp 			= !empty($data['timestamp']) ? strip_tags($data['timestamp']) : "";

		$answer_settings 	= json_encode(array(
			'options' 			=> $options,
			'correct_choices' 	=> $correct_choices,
			'timestamp' 		=> $timestamp,
		));

		$data = array(
			"post_id"			=> $post_id,
			"question"			=> $question_title,
			"question_type"		=> $question_type,
			"answer_settings" 	=> $answer_settings,
			"updated" 			=> date("Y-m-d H:i:s")
		);

		$format = array(
			"%d", "%s", "%s", "%s", "%s"
		);

		global $wpdb;
		if ($question_id) {
			$where  = array("ID" => $question_id);
			$status = $wpdb->update("{$wpdb->prefix}grassblade_questions", $data, $where, $format, array("%d"));
		} else {
			$format 		 = array_merge($format, array("%s"));
			$data["created"] = $data["updated"];
			$status 		 = $wpdb->insert("{$wpdb->prefix}grassblade_questions", $data, $format);
		}
		$sql = $wpdb->last_query;
		$error = ($status === false) ? $wpdb->last_error : "";

		$questions = array();
		$questions = self::get_saved_questions($post_id);

		$return = array(
			"status" => ($status !== false),
			"questions" => $questions,
			"sql" => $sql,
			"error" => $error
		);

		echo json_encode($return);
		exit;
	}

	public function	add_video_quiz_data($html, $content_id, $params)
	{
		ob_start();
		$questions = self::get_saved_questions($content_id);
		$GB_QUESTION_DATA = array(
			"questions" => $questions
		);
		include_once(dirname(__FILE__) . "/frontend/templates.php");
	?>
	<div class="gb_hidden">
		<script id="interactive_video_script_frontend" src="<?php echo plugins_url("frontend/script.js", __FILE__); ?>"></script>
		<link rel="stylesheet" href="<?php echo plugins_url("frontend/style.css", __FILE__); ?>">
		<script type="text/javascript">
			//<![CDATA[
			var GB_QUESTION_DATA = <?php echo json_encode($GB_QUESTION_DATA); ?>;
			//]]> </script>
	</div>
		<div class="question_container_overlay gb_hidden">
			<div class="gb_question_container">
			</div>
		</div>
	<?php

		return $html . ob_get_clean();
	}

	public static function get_saved_questions($content_id)
	{
		global $wpdb;
		$sql  = $wpdb->prepare("
				SELECT ID, question, question_type, answer_settings, question_settings
				FROM {$wpdb->prefix}grassblade_questions
				WHERE post_id = '%d' ", $content_id);
		$question_data = $wpdb->get_results($sql, OBJECT);
		$questions = array();

		if (!empty($question_data))
			foreach ($question_data as $k => $question) {
				$question->answer_settings = empty($question->answer_settings) ? array() : json_decode($question->answer_settings);
				$question->question_settings = empty($question->question_settings) ? array() : json_decode($question->question_settings);

				$questions[$question->ID] = $question;
			}
		return $questions;
	}

	public static function create_questions()
	{
		$content_id = !empty($_GET['content_id']) ? intVal($_GET['content_id']) : 0;
		$post = get_post($content_id);
		$config = grassblade_xapi_content::get_params($content_id);
		$config["width"] = "600px";
		$config["height"] = "400px";
		$config["autoplay"] = $config["video_autoplay"] = 0;

		echo gb_get_json_script("config", $config);
		echo grassblade_video_assets();
	?>
		<div class="gb_question_builder_header">
			<h1>Quiz Builder</h1>
			<div class="gb_navigation">
				<a href="<?php echo get_edit_post_link($content_id); ?>"><button class="button button-secondary"><?php echo __('Go Back')?></button></a>
				<a href="<?php echo get_the_permalink($content_id) . "?xapi_preview=true" ?>" target="_blank"><button class="button button-primary"><?php echo __('Preview')?></button></a>
			</div>
		</div>
		<div class="gb_question_builder_header">
			<h2><?php echo $post->post_title; ?> <a href="<?php echo get_edit_post_link($content_id); ?>"><i class="dashicons dashicons-admin-generic"></i></a></h2>

		</div>
		<div class="main-container">
			<?php include_once(dirname(__FILE__) . "/form.php"); ?>
		</div>
	<?php
		include_once(dirname(__FILE__) . "/templates.php");
	}
} //end of grassblade_questions class

$gb_questions = new grassblade_questions();

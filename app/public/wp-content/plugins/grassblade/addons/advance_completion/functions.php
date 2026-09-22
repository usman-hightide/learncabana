<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class grassblade_advance_completion {

	function __construct() {
		add_action( 'wp_ajax_grassblade_content_completion', array($this,'content_completion' ));
		add_filter('grassblade_localize_script_data',array( $this,'grassblade_advance_completion_data'),10,2);
		add_filter("grassblade_custom_labels_fields", array($this,"custom_labels_fields"),10,1);
	}

	function content_completion() {
		$data = $_POST['data'];

		$content_id = !empty($data['content_id'])? intVal($data['content_id']):0;
		$registration = !empty($data['registration'])? $data['registration']:"";
		$post_id = !empty($data['post_id'])? intVal($data['post_id']):0;
		$user = wp_get_current_user();

		if (!empty($user->ID)) {

			$completion_result = $this->get_completion($user->ID,$content_id,$registration);

			if( !empty($completion_result) ) {

				$grassblade_xapi_content = new grassblade_xapi_content();
				$score_table = $grassblade_xapi_content->get_score_table($user->ID,$content_id);

				$time = 0;
				while( empty($completed) && $time <= 10 ) {
					$completed = grassblade_xapi_content::post_contents_completed($post_id,$user->ID);

					if( empty($completed) )
					sleep(2);
					$time += 2;
				}
				if (empty($completed)) {
					$post_completion = false;
				} else {
					$post_completion = true;
				}

				$is_show_hide_button = apply_filters("grassblade_is_show_hide_button", $post_completion, $post_id, $content_id, $user);

				$data = array( "score_table" => $score_table, "completion_result" => $completion_result[0],"post_completion" => $post_completion ,"is_show_hide_button" => $is_show_hide_button );

				echo json_encode($data);
				die();
			}
		}
	}

	function get_completion($user_id,$content_id,$registration) {
		global $wpdb;

		//Write and close the session if some other plugin has started it. Otherwise longpolling will restrict other requests to the website.
		if(session_status() == PHP_SESSION_ACTIVE)
		session_write_close();

		$completion_check_time = defined("GB_COMPLETION_CHECK_TIME")? GB_COMPLETION_CHECK_TIME:5;
		$long_pooling_time = defined("GB_POOLING_TIME")? GB_POOLING_TIME:15;
		set_time_limit(intVal($long_pooling_time) + 60);

		$count = ceil( $long_pooling_time / $completion_check_time );

		for( $i=0; $i < $count; $i++) {
			$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}grassblade_completions` WHERE user_id = %d AND content_id = %d AND statement LIKE %s ORDER BY id DESC LIMIT 1", $user_id , $content_id, '%' . $wpdb->esc_like($registration) . '%'));

			if(!empty($result)) {
				//Delete User Meta if sleep has happened before return.
				//Fixing bug: get_user_meta is not reading completed_<xapi_content> in case of Open in New Window
				//because the completion happened after this request started.
				if($i > 0)
					wp_cache_delete($user_id, "user_meta");

				return $result;
			}

			sleep($completion_check_time); //30 second wait by default
		}

		return;
	}

	function grassblade_advance_completion_data($gb_data, $post) {
		if(empty($post->ID))
			return $gb_data;

		$completed = grassblade_xapi_content::post_contents_completed($post->ID);

		//No content = true - No change
		//Has content but completion tracking disabled = true - No change
		//Has content with completion tracking but at least one incomplete = false - Hide or Disable Mark Complete
		//Has content with completion tracking and all complete = statements - No change

		$grassblade_advance_completion_data = apply_filters("grassblade_advance_completion_data", false, $post);
		if(empty($completed) || !empty($grassblade_advance_completion_data)) {
			$gb_data['completion_type'] = grassblade_xapi_content::post_completion_type($post->ID);
			$gb_data['mark_complete_button'] = apply_filters('grassblade_lms_mark_complete_button_id','',$post);
			$gb_data['next_button'] = apply_filters('grassblade_lms_next_button_id','',$post);
			$gb_data['next_link'] = apply_filters('grassblade_lms_next_link','',$post);
		}

		if(empty($gb_data["labels"]))
		$gb_data["labels"] = array();

		$gb_data["labels"]["content_passed_message"] = grassblade_get_label("content_passed_message", __( "Congratulations! You have successfully %s the content.", "grassblade"));
		$gb_data["labels"]["content_failed_message"] = grassblade_get_label("content_failed_message", __( "You did not pass.", "grassblade"));
		$gb_data["labels"]["content_getting_result"] = __("Getting your Result ...", "grassblade");
		$gb_data["labels"]["passed"] = __("Passed", "grassblade");
		$gb_data["labels"]["failed"] = __("Failed", "grassblade");
		$gb_data["labels"]["completed"] = __("Completed", "grassblade");

		return $gb_data;
	}

	function custom_labels_fields($labels_fields){
		$labels_fields[] = array( 'id' => 'label_content_passed_message', 'label' => __( 'Content Passed Message', 'grassblade' ),  'placeholder' => __( "Congratulations! You have successfully %s the content.", "grassblade"), 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Message displayed when xAPI Content is passed or completed. %s is replaced with status passed or completed', 'grassblade'));
		$labels_fields[] = array( 'id' => 'label_content_failed_message', 'label' => __( 'Content Failed Message', 'grassblade' ),  'placeholder' => __( "You did not pass.", "grassblade"), 'type' => 'text', 'values'=> '', 'never_hide' => true ,'help' => __( 'Message displayed when xAPI Content is failed.', 'grassblade'));

		return $labels_fields;
	}
}

$gb_ac = new grassblade_advance_completion();

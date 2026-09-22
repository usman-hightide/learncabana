<?php

class manual_completions_learndash_mark_incomplete extends manual_completions_learndash {

   function __construct() {

		if( ! is_admin() ) return;

        add_action( 'wp_ajax_manual_completions_learndash_mark_incomplete', array($this, 'mark_incomplete') );
		add_action('learndash_mark_incomplete_process', array($this, 'send_incompleted_statement'), 10, 3);
   }

   function mark_incomplete() {

		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( 'gbmc_learndash_nonce', 'nonce', false ) || !current_user_can("manage_options") ) {
			wp_send_json(array("status" => 0, "message" => self::get_message("invalid_request")));
		}

		if(empty($_POST["data"]) || (!is_array($_POST["data"]) && !is_object($_POST["data"])) )
			wp_send_json(array("status" => 0, "message" => self::get_message("invalid_data")));

		$completions 	   = map_deep( wp_unslash( $_POST['data'] ), 'sanitize_text_field' );
		$check_completions = $this->check_completion(true);

		foreach ($completions as $k => $completion) {

			$course_id 	= $completion["course_id"] = intVal($completion["course_id"]);
			$lesson_id 	= $completion["lesson_id"] = (!empty($completion["lesson_id"]) && $completion["lesson_id"] != "all")? intVal($completion["lesson_id"]):$completion["lesson_id"];
			$topic_id 	= $completion["topic_id"] = (!empty($completion["topic_id"]) && $completion["topic_id"] != "all")? intVal($completion["topic_id"]):$completion["topic_id"];
			$quiz_id 	= $completion["quiz_id"] = intVal($completion["quiz_id"]);
			$user_id 	= $completion["user_id"] = intVal($completion["user_id"]);

			if( !empty($check_completions[$k]) && empty($check_completions[$k]["completed"]) && $lesson_id != "all" && $topic_id != "all" ) {
				$completions[$k]["status"] = 1;
				$completions[$k]["message"] = self::get_message("already_incomplete");
				$completions[$k]["info"] = $check_completions[$k];
				continue;
			}

			if( empty($course_id) ) {
				$completions[$k]["message"] = self::get_message("course_not_selected");
				$completions[$k]["status"] = 0;
			} else if( empty($user_id) ) {
				$completions[$k]["message"] = self::get_message("user_not_selected");
				$completions[$k]["status"] = 0;
			} else if( !ld_course_check_user_access($course_id, $user_id) ) {
				$completions[$k]["message"] = self::get_message("not_enrolled");
				$completions[$k]["status"] = 0;
			} else {
                if( !empty($quiz_id) )
					$completions[$k] = self::mark_quiz_incomplete($completion);
				else if( !empty($topic_id) && !empty($lesson_id) && $topic_id != "all" )
					$completions[$k] = $this->mark_topic_incomplete($completion);
				else if( !empty($lesson_id) ) {
					if($lesson_id == "all")
						$completions[$k] = $this->mark_course_incomplete($completion);
					else
						$completions[$k] = $this->mark_lesson_incomplete($completion);
				} else {
					$completions[$k]["message"] = self::get_message("items_not_selected");
					$completions[$k]["status"] = 0;
				}

			}
		}

		wp_send_json( array("status" => 1, "data" => $completions) );
	}

    function mark_lesson_incomplete($completion) {

        $course_id = $completion["course_id"];
        $user_id   = $completion["user_id"];
    	$lesson_id = $completion["lesson_id"];
		if( !empty($completion["topic_id"]) && $completion["topic_id"] == "all" ) {
			$course = get_post($course_id);
			$course_structure = grassblade_learndash_get_course_structure($course);
			if( !empty($course_structure->lessons) && !empty($course_structure->lessons->{$lesson_id}) ) {
				$status = array();
				$lesson = $course_structure->lessons->{$lesson_id};
				if( !empty($lesson->topics) )
				foreach ($lesson->topics as $topic_id => $topic) {

					if( !empty($topic->quizzes) )
					foreach ($topic->quizzes as $quiz_id => $quiz) {
						$status["quiz_".$quiz_id] = $this->mark_quiz_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id, "quiz_id" => $quiz_id));
					}
					$status["topic_".$topic_id] = $this->mark_topic_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id));

				}
				if( !empty($lesson->quizzes) )
				foreach ($lesson->quizzes as $quiz_id => $quiz) {
					$status["quiz_".$quiz_id] = $this->mark_quiz_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "quiz_id" => $quiz_id));
				}

				$status["lesson_".$lesson_id] = $this->mark_lesson_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id));
			}

			$lesson_status = learndash_is_lesson_complete($user_id, $lesson_id, $course_id);
			$completion["status"] 	= empty( $lesson_status ) ? 1 : 0;
			$completion["message"] 	= empty( $lesson_status ) ? self::get_message("incompleted") : self::get_message("failed");
			$completion["info"]		= $status;
			return $completion;
		}

		$status = learndash_process_mark_incomplete( $user_id, $course_id, $lesson_id );
		if ( $status )
			$this->delete_xapi_content_completion($lesson_id, $user_id);

        $completion["status"]  = intval( $status );
		$completion["message"] = $status ? self::get_message("incompleted") : self::get_message("failed");

		return $completion;
	}

    function mark_course_incomplete($completion) {
		$course_id 		= $completion["course_id"];
		$user_id 		= $completion["user_id"];
		$course 		= get_post($course_id);
		$course_structure = grassblade_learndash_get_course_structure($course);
		$completion["status_slug"] 	= learndash_course_status($course_id, $user_id, true);
		$status = array();
		if( !empty($course_structure->lessons) )
		foreach ($course_structure->lessons as $lesson_id => $lesson) {
			if( !empty($lesson->topics) )
			foreach ($lesson->topics as $topic_id => $topic) {

				if( !empty($topic->quizzes) )
				foreach ($topic->quizzes as $quiz_id => $quiz) {
					$status["quiz_".$quiz_id] = $this->mark_quiz_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id, "quiz_id" => $quiz_id));
				}
				$status["topic_".$topic_id] = $this->mark_topic_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "topic_id" => $topic_id));

			}
			if( !empty($lesson->quizzes) )
			foreach ($lesson->quizzes as $quiz_id => $quiz) {
				$status["quiz_".$quiz_id] = $this->mark_quiz_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id, "quiz_id" => $quiz_id));
			}

			$status["lesson_".$lesson_id] = $this->mark_lesson_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "lesson_id" => $lesson_id));
		}

		if( !empty($course_structure->quizzes) )
		foreach ($course_structure->quizzes as $quiz_id => $quiz) {
			$status["quiz_".$quiz_id] = $this->mark_quiz_incomplete(array("course_id" => $course_id, "user_id" => $user_id, "quiz_id" => $quiz_id));
		}

		$completion["status_slug"] 	= learndash_course_status($course_id, $user_id, true);
		$completion["status"]  		= ($completion["status_slug"] != "completed")*1;
		$completion["message"]		= ($completion["status"] == 1) ? self::get_message("incompleted") : self::get_message("failed");
		$completion["info"]			= $status;
		return $completion;
	}

    function mark_topic_incomplete($completion) {

        $quizzes = learndash_get_lesson_quiz_list( $completion['topic_id'] , null, $completion['course_id'] );
        $info = [];
        if ( !empty( $quizzes ) )
        foreach ($quizzes as $q) {
            $quiz = $q["post"];
            $info['quiz_' . $quiz->ID] = self::mark_quiz_incomplete( [ "quiz_id" => $quiz->ID, "user_id" => $completion['user_id'], "course_id" => $completion["course_id"] ]);
        }

        $status = learndash_process_mark_incomplete( $completion['user_id'], $completion['course_id'], $completion['topic_id'] );

		if ( $status )
			$this->delete_xapi_content_completion($completion['topic_id'], $completion['user_id']);

   		$completion["message"] = $status ? self::get_message("incompleted") : self::get_message("failed");
		$completion["status"]  = intval($status);

        if( !empty( $info ) )
    		$completion["info"] = $info;

        return $completion;
    }

    function mark_quiz_incomplete($completion) {

        $user_id   = $completion['user_id'] ?? 0;
        $quiz_id   = $completion['quiz_id'] ?? 0;
        $course_id = $completion['course_id'] ?? 0;

        if ( empty($user_id) || empty( $quiz_id ) || empty( $course_id ) )
            return $completion;

		$quiz_progress = get_user_meta($user_id, '_sfwd-quizzes', true);
		$quiz_status  = false;
		if (! empty($quiz_progress)) {
			foreach ($quiz_progress as $quiz_idx => $quiz_item) {

				if ($quiz_item['quiz'] == $quiz_id && true === (bool) $quiz_item['pass']) {
					$quiz_progress[$quiz_idx]['pass'] = false;

					// We need to update the activity database records for this quiz_id.
					$activity_query_args = array(
						'post_ids' => $quiz_id,
						'user_ids' => $user_id,
						'activity_type' => 'quiz',
					);
					$quiz_activity = learndash_reports_get_activity($activity_query_args);
					if ( isset($quiz_activity['results']) && ! empty($quiz_activity['results']) ) {
						foreach ($quiz_activity['results'] as $result) {
							if ( ! isset($result->activity_meta['pass']) || true !== $result->activity_meta['pass'] )
								continue;

							// If the activity meta 'pass' element is set to true we want to update it to false.
							learndash_update_user_activity_meta($result->activity_id, 'pass', false);

							// Also we need to update the 'activity_status' for this record.
							learndash_update_user_activity(
								array(
									'activity_id' => $result->activity_id,
									'course_id' => $course_id,
									'user_id' => $user_id,
									'post_id' => $quiz_id,
									'activity_type' => 'quiz',
									'activity_action' => 'update',
									'activity_status' => false,
								)
							);
						}
					}

					$quiz_status = true;
				}

				/**
				 * Remove the quiz lock.
				 *
				 * @since 2.3.1
				 */
				if ((isset($quiz_item['pro_quizid'])) && (! empty($quiz_item['pro_quizid']))) {
					learndash_remove_user_quiz_locks($user_id, $quiz_item['quiz']);
				}
			}
		}

        if ( true === $quiz_status ) {
			do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $quiz_id );
			update_user_meta( $user_id, '_sfwd-quizzes', $quiz_progress );
		}

		if ( $quiz_status )
			$this->delete_xapi_content_completion($quiz_id, $user_id);

   		$completion["message"] = $quiz_status ? self::get_message("incompleted") : self::get_message("failed");
		$completion["status"] = intval($quiz_status);

		return $completion;
	}

	function send_incompleted_statement( $user_id, $course_id, $post_id ) {

		if( empty( $user_id ) || empty( $course_id ) || empty( $post_id ) )
			return false;

		$post = get_post($post_id);
		grassblade_debug('grassblade_learndash_' . $post->post_type . '_incompleted');

		$grassblade_settings 		   = grassblade_settings();
		$grassblade_tincan_endpoint    = $grassblade_settings["endpoint"];
		$grassblade_tincan_user 	   = $grassblade_settings["user"];
		$grassblade_tincan_password    = $grassblade_settings["password"];
		$grassblade_tincan_track_guest = $grassblade_settings["track_guest"];

		$xapi  = new NSS_XAPI($grassblade_tincan_endpoint, $grassblade_tincan_user, $grassblade_tincan_password);
		$user  = get_user_by("id", $user_id);
		$actor = grassblade_getactor($grassblade_tincan_track_guest, "1.0", $user);

		if(empty($actor)) {
			grassblade_debug("No Actor. Shutting Down.");
			return;
		}

		if ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) {

			$course 	  = get_post($course_id);
			$course_title = $course->post_title;
			$course_url   = grassblade_post_activityid($course_id);
			$post_title   = $post->post_title;
			$post_url 	  = grassblade_post_activityid($post->ID);
			$xapi->set_verb('uncompleted');
			$xapi->set_actor_by_object($actor);
			$activity_type = $post->post_type == 'sfwd-quiz' ? 'quiz' : ($post->post_type == 'sfwd-topic' ? 'topic' : 'lesson');
			$xapi->set_object($post_url, $post_title, '', 'http://adlnet.gov/expapi/activities/' . $activity_type, 'Activity');
			$xapi->set_grouping($course_url, $course_title, '', 'http://adlnet.gov/expapi/activities/course','Activity');

			$post_parent_id = $activity_type == 'lesson' ? $course_id : grassblade_learndash_course_get_single_parent_step($course_id, $post_id);
			if ( !empty($post_parent_id) ) {
				$post_parent = get_post($post_parent_id);
				$parent_activity_type = $post_parent->post_type == 'sfwd-topic' ? 'topic' : ($post_parent->post_type == 'sfwd-lessons' ? 'lesson' : 'course');
				$xapi->set_parent(grassblade_post_activityid($post_parent_id), $post_parent->post_title, '', 'http://adlnet.gov/expapi/activities/' . $parent_activity_type,'Activity');
			}
			$xapi->build_statement();
			$xapi->new_statement();
		}

		if( !empty( $xapi->statements ) )
		foreach($xapi->statements as $statement) {
			$ret = $xapi->SendStatements(array($statement));
		}
	}

	function delete_xapi_content_completion($post_id, $user_id) {
		$all_content_ids = grassblade_xapi_content::get_post_xapi_contents($post_id, true);
		foreach ($all_content_ids as $content_id) {
			delete_user_meta($user_id, 'completed_' . $content_id);
		}
	}
}

new manual_completions_learndash_mark_incomplete();

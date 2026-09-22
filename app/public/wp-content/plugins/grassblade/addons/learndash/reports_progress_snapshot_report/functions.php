<?php

class grassblade_learndash_progress_snapshot_report {

	function __construct() {
		add_filter("grassblade/reports/progress_snapshot/data", array($this, "get_progress_report_data"), 10, 2);
		add_filter("grassblade/reports/progress_snapshot/details", array($this, "get_progress_report_details"), 10, 4);
	}

	function get_progress_report_data($r, $params) {
		global $wpdb;

		if(!empty($r))
			return $r;

		$course_id = intVal($params["course_id"]);
		$group_id = intVal($params["group_id"]);
		$group_type = !empty($params["group_type"]) ? $params["group_type"] : "";
		$course = get_post($course_id);

		if(empty($course) || empty($course->post_type) || $course->post_type != "sfwd-courses")
			return $r;

		$lessons_and_contents = $this->get_lessons_and_contents($course_id);
		$lessons = $lessons_and_contents["lessons"];
		$course_contents = $lessons_and_contents["course_contents"];

		$lesson_completion_results = array();

		$users = array();

		$sql = $wpdb->prepare("SELECT user_id, post_id, activity_status, activity_completed FROM {$wpdb->prefix}learndash_user_activity WHERE course_id = '%d'", $course_id);

		$sql = gb_groups::add_user_query($sql, $group_id, 'user_id', $group_type);
		$lesson_completion_results_raw = $wpdb->get_results($sql);

		if(!empty($lesson_completion_results_raw))
		foreach ($lesson_completion_results_raw as $key => $value) {
			if(empty($lesson_completion_results[$value->user_id]))
			$lesson_completion_results[$value->user_id] = array();

			if(empty($lesson_completion_results[$value->user_id][$value->post_id]))
			$lesson_completion_results[$value->user_id][$value->post_id] = array();

			$lesson_completion_results[$value->user_id][$value->post_id] = $value;

			$users[$value->user_id] = 1;
		}
		unset($lesson_completion_results_raw);

		$content_completion_results = array();
		$content_completion_results_raw = array();

		$k = 0;
		$ret = array();
		foreach ($users as $user_id => $v) {
			$user = get_user_by("id", $user_id);
			if(!empty($user->ID))
			{
				$data = array(
					"sno" 	=> $k,
					"name"	=> gb_name_format($user),
					"user_id" => $user->ID,
					"user_email" => $user->user_email,
				);
				foreach ($lessons as $key => $lesson) {
					$data[$lesson->ID] = $this->lesson_completion_date($lesson->lesson_contents, gb_get_value($lesson_completion_results, $user_id, []), gb_get_value($lesson_completion_results, $user_id.".".$lesson->ID, []));
				}
				$ret[$k++] = $data;
			}
		}
		$lesson_order = $lessons_list = array();
		$k = 1;
		foreach ($lessons as $key => $lesson) {
			$lessons_list[$lesson->ID] = $lesson->post_title;
			$lesson_order[$k++] = $lesson->ID;
		}
		$return = array("data" => $ret, "lessons" => $lessons_list, 'lesson_order' => $lesson_order);
		return $return;
	}
	function get_lessons_and_contents($course_id) {
		global $wpdb;
//$cs = grassblade_learndash_get_course_structure(get_post($course_id));echo "<pre>";print_r($cs);

		if(class_exists( 'LDLMS_Course_Steps' ) && LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' )  {
			/*
			$post_contents_raw = $wpdb->get_results("SELECT post_id, meta_value as content_id FROM $wpdb->postmeta WHERE meta_key = 'show_xapi_content' AND  meta_value > 0");
			$post_contents = array();
			foreach ($post_contents_raw as $value) {
				$post_contents[$value->post_id] = $value->content_id;
			}
			unset($post_contents_raw);
			*/
			$LDLMS_Course_Steps = new LDLMS_Course_Steps($course_id);
			$lessons = $steps_ids = $post_ids = array();
			$children = $LDLMS_Course_Steps->get_steps('h');
			$course_contents = array();

			if(!empty($children["sfwd-lessons"]))
			foreach ($children["sfwd-lessons"] as $lesson_id => $lesson_children) {
				$lesson = get_post($lesson_id);
				$lesson_contents = array();
//				if(!empty($post_contents[$lesson_id]))
					$course_contents[$lesson_id] = $lesson_contents[$lesson_id] = 1;// $post_contents[$lesson_id];

				if(!empty($lesson->ID)) {
					if(!empty($lesson_children["sfwd-topic"]) && is_array($lesson_children["sfwd-topic"]))
					foreach ($lesson_children["sfwd-topic"] as $topic_id => $topic_children) {
//						if(!empty($post_contents[$topic_id]))
						$lesson_contents[$topic_id] = 1;//$post_contents[$topic_id];

						if(!empty($topic_children["sfwd-quiz"]) && is_array($topic_children["sfwd-quiz"]))
						foreach ($topic_children["sfwd-quiz"] as $quiz_id => $quiz) {
//							if(!empty($post_contents[$quiz_id]))
							$course_contents[$lesson_id] = $lesson_contents[$quiz_id] = 1;//$post_contents[$quiz_id];
						}
					}
					if(!empty($lesson_children["sfwd-quiz"]) && is_array($lesson_children["sfwd-quiz"]))
					foreach ($lesson_children["sfwd-quiz"] as $quiz_id => $quiz) {
//						if(!empty($post_contents[$quiz_id]))
						$course_contents[$lesson_id] = $lesson_contents[$quiz_id] = 1;//$post_contents[$quiz_id];
					}
					$lesson->lesson_contents = $lesson_contents;
					$lessons[$lesson_id] = $lesson;
				}
			}

			return array("lessons" => $lessons, "course_contents" => $course_contents);
		}
		else
		{
			/*
			$course_contents_raw = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value as content_id FROM $wpdb->postmeta WHERE meta_key = 'show_xapi_content' AND  meta_value > 0 AND post_id IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'course_id' AND meta_value = '%d')", $course_id));
			$course_contents = array();
			foreach ($course_contents_raw as $value) {
				$course_contents[$value->post_id] = $value->content_id;
			}
			unset($course_contents_raw);
			*/
			$course_contents = array();

			$lessons = learndash_get_lesson_list($course_id);
			foreach ($lessons as $key => $lesson) {
				$lesson_contents = array();
			//	if(!empty($course_contents[$lesson->ID]))
					$lesson_contents[$lesson->ID] = $course_contents[$lesson->ID] = 1;

				$topics = learndash_get_topic_list($lesson->ID, $course_id);
				if(!empty($topics))
				foreach ($topics as $topic) {
				//	if(!empty($course_contents[$topic->ID]))
						$lesson_contents[$topic->ID] = $course_contents[$topic->ID] = 1;

					$quizzes = learndash_get_lesson_quiz_list($topic->ID, null, $course_id);

					if(!empty($quizzes))
					foreach ($quizzes as $quiz) {
				//		if(!empty($course_contents[$quiz->ID]))
							if(!empty($quiz["post"]->ID))
							$lesson_contents[$quiz["post"]->ID] = $course_contents[$quiz["post"]->ID] = 1;
					}
				}

				$quizzes = learndash_get_lesson_quiz_list($lesson->ID, null, $course_id);
				$lessons[$key]->quizzes = $quizzes;

				if(!empty($quizzes))
				foreach ($quizzes as $quiz) {
					if(!empty($quiz["post"]))// && !empty($course_contents[$quiz["post"]->ID]))
						$lesson_contents[$quiz["post"]->ID] = $course_contents[$quiz["post"]->ID] = 1;
				}

				$lessons[$key]->lesson_contents = $lesson_contents;
			}
			$return = array('lessons' => $lessons, 'course_contents' => $course_contents);
			return $return;
		}
	}
	function lesson_completion_date($lesson_contents, $lesson_completion_results_all = null, $lesson_completion_results = null) {
		$date = "";
		$completed_count = 0;
		if( !empty($lesson_completion_results->activity_status) )
		if( !empty($lesson_completion_results->activity_completed) )
			return date("Y-m-d", $lesson_completion_results->activity_completed);
		else
			$completed_count = count($lesson_contents);

		if(!empty($lesson_contents))
		foreach ($lesson_contents as $post_id => $val) {
			if(!empty($lesson_completion_results_all) && !empty($lesson_completion_results_all[$post_id]) && !empty($lesson_completion_results_all[$post_id]->activity_completed) )
				$completed_count++;
		}

		if($completed_count >= count($lesson_contents))
			return !empty($date)? $date:__("Completed", "grassblade");
		else
			return $completed_count."/".count($lesson_contents);
	}
    function get_progress_report_details($return, $gb_lesson_id, $course_id, $user) {

		if(!empty($return) || get_post_type($gb_lesson_id) != 'sfwd-lessons')
			return $return;

		$lesson_activity = learndash_get_user_activity(['user_id' => $user->ID, 'post_id' => $gb_lesson_id, 'course_id' => $course_id]);

		$lesson_status_msg = __('Not Started', 'grassblade');
		$lesson_status = 'not_started';

		if(!empty($lesson_activity)) {
			if(!empty($lesson_activity->activity_status)) {
				$lesson_status_msg = __("Completed on", "grassblade") . ' ' . gb_datetime($lesson_activity->activity_completed);
				$lesson_status = 'completed';
			} else {
				$lesson_status_msg = __('In Progress', 'grassblade');
				$lesson_status = 'in_progress';
			}
		}

		$section_title = get_the_title($gb_lesson_id);
		$return['course_id']   	   = $course_id;
		$return['user_id'] 	       = $user->ID;
		$return['user_email']      = $user->user_email;
		$return['user_name']   	   = gb_name_format($user);
		$return['section_id']  	   = $gb_lesson_id;
		$return['name'] 	   	   = $section_title;
		$return['sub_steps']   	   = [];
		$return['total_steps']	   = 0;
		$return['completed_steps'] = 0;
		$return['status_msg']	   = $lesson_status_msg;
		$return['status']		   = $lesson_status;

		$total_steps 	 = 1;
		$completed_steps = !empty($lesson_activity->activity_status) ? intval($lesson_activity->activity_status) : 0;

		$topics = learndash_get_topic_list($gb_lesson_id, $course_id);

		if(!empty($topics))
		foreach($topics as $topic) {
			$total_steps++;
			$topic_status_msg = __('Not Started', 'grassblade');
			$topic_status = 'not_started';
			$topic_activity = learndash_get_user_activity(['user_id' => $user->ID, 'post_id' => $topic->ID, 'course_id' => $course_id]);

			if(!empty($topic_activity)) {
				if(!empty($topic_activity->activity_status)) {
					$topic_status_msg = __("Completed on", "grassblade") . ' ' . gb_datetime($topic_activity->activity_completed);
					$topic_status = 'completed';
					$completed_steps++;
				} else {
					$topic_status_msg = __('In Progress', 'grassblade');
					$topic_status = 'in_progress';
				}
			}

			$topic_xapi_attempts = grassblade_reports_progress_snapshot::get_xapi_content_attempts($topic->ID, $user->ID);
			$return['sub_steps'][$topic->ID] = array(
				'count' 		=> $total_steps,
				'id' 	 		=> $topic->ID,
				'name' 	 		=> $topic->post_title,
				'status_msg' 	=> $topic_status_msg,
				'status' 		=> $topic_status,
				'type'   	    => 'topic',
				'attempts' 		=> $topic_xapi_attempts['attempts'],
				'percentage' 	=> $topic_xapi_attempts['percentage'],
				'attempts_count' => $topic_xapi_attempts['attempts_count'],
				'has_xapi' 		=> $topic_xapi_attempts['has_xapi'],
				'sub_steps' 	=> array(),
			);

			$topic_quizzes = learndash_course_get_quizzes($course_id, $topic->ID);
			if(!empty($topic_quizzes)) {
				$return['sub_steps'][$topic->ID]['sub_steps'] = array();
				foreach($topic_quizzes as $quiz) {
					$total_steps++;
					$quiz_data = $this->get_quiz_attempts($quiz->ID, $user->ID);
					if($quiz_data['status'] == 'completed') {
						$completed_steps++;
					}
					$quiz_data['count'] = $total_steps;
					$return['sub_steps'][$topic->ID]['sub_steps'][$quiz->ID] = $quiz_data;
				}
			}
		}

		$lesson_quizzes = learndash_course_get_quizzes($course_id, $gb_lesson_id);
		if(!empty($lesson_quizzes)) {
			foreach($lesson_quizzes as $lesson_quiz) {
				$total_steps++;
				$quiz_data = $this->get_quiz_attempts($lesson_quiz->ID, $user->ID);
				$quiz_data['count'] = $total_steps;
				if($quiz_data['status'] == 'completed') {
					$completed_steps++;
				}
				$return['sub_steps'][$lesson_quiz->ID] = $quiz_data;
			}
		}

		$return['total_steps'] 		= $total_steps;
		$return['completed_steps']  = $completed_steps;

		$section_attempts = grassblade_reports_progress_snapshot::get_xapi_content_attempts($gb_lesson_id, $user->ID);
		$return['sub_steps'] =
			[
				[
					'count'	  		 => 1,
					'id' 	  		 => $gb_lesson_id,
					'name'    		 => $section_title,
					'type'    		 => 'lesson',
					'status_msg'  	 => $lesson_status_msg,
					'status' 		 => $lesson_status,
					'sub_steps' 	 => $return['sub_steps'],
					'attempts' 		 => $section_attempts['attempts'],
					'percentage' 	 => $section_attempts['percentage'],
					'attempts_count' => $section_attempts['attempts_count'],
					'has_xapi' 		 => $section_attempts['has_xapi'],
				]
			];

		return $return;
	}

	function get_quiz_attempts($quiz_id, $user_id) {

		$is_quiz_completed = false;
		$quiz_attempts = [];
		$hightest_percentage = 0;
		$last_completed_date = 0;

		$user_quiz_attempts_meta = get_user_meta($user_id, '_sfwd-quizzes', true);
		if(empty($user_quiz_attempts_meta) || !is_array($user_quiz_attempts_meta))
			$user_quiz_attempts_meta = [];

		foreach($user_quiz_attempts_meta as $quiz_attempt) {
			if($quiz_attempt['quiz'] != $quiz_id)
				continue;

			$last_completed_date = $last_completed_date > intval($quiz_attempt['time']) ? $last_completed_date : intval($quiz_attempt['time']);
			$is_quiz_completed   = !$is_quiz_completed && intval($quiz_attempt['pass']) == 1 ? true : $is_quiz_completed;
			$hightest_percentage = $hightest_percentage > $quiz_attempt['percentage'] ? $hightest_percentage : $quiz_attempt['percentage'];

			$percentage  = $quiz_attempt['percentage'] . '%';
			if(!empty($quiz_attempt['m_edit_time'])) {
				$percentage .= ' (m)';
			}

			$quiz_attempts[] = array(
				'id' 		 => $quiz_attempt['quiz'],
				'percentage' => $percentage,
				'status'	 => intval($quiz_attempt['pass']) ? 'passed' : 'failed',
				'status_msg' => intval($quiz_attempt['pass']) ? __('Passed', 'grassblade') : __('Failed', 'grassblade'),
				'timespent'  => !empty($quiz_attempt['timespent']) ? gb_seconds_to_time($quiz_attempt['timespent']) : "-",
				'completed'  => gb_datetime($quiz_attempt['time']),
			);
		}

		$quiz_status_msg = __('Not Started', 'grassblade');
		$quiz_status = 'not_started';
		if(!empty($quiz_attempts)){
			$quiz_status_msg = $is_quiz_completed ? __("Completed on", "grassblade") . ' ' . gb_datetime($last_completed_date) : __('In Progress', 'grassblade');
			$quiz_status = $is_quiz_completed ? 'completed' : 'in_progress';
		}

		$xapi_content_ids = grassblade_xapi_content::get_post_xapi_contents($quiz_id, true);
		$quiz_data = array(
			'id' 		 	 => $quiz_id,
			'name' 		 	 => get_the_title($quiz_id),
			'status_msg'     => $quiz_status_msg,
			'status'     	 => $quiz_status,
			'type' 		 	 => 'quiz',
			'attempts'   	 => $quiz_attempts,
			'attempts_count' => count($quiz_attempts),
			'percentage' 	 => number_format($hightest_percentage, 2) . '%',
			'has_xapi'       => intval(!empty($xapi_content_ids)),
		);

		return $quiz_data;
	}
}

$grassblade_learndash_progress_snapshot_report = new grassblade_learndash_progress_snapshot_report();
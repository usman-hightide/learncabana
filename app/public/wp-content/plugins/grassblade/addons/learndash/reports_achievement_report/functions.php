<?php

class grassblade_learndash_achievement_report {
	function __construct()
	{
		add_filter("grassblade/reports/show_achievement_report", array($this, "show_achievement_report"), 10, 2);
		add_filter("grassblade/reports/achievement_options", array($this, "achievement_options"), 10, 1);
		add_filter("grassblade/reports/achievement_report/data", array($this, "achievement_report"), 10, 2);
	}
	function show_achievement_report($r, $available_reports) {
		return true;
	}
	function achievement_options($achievement_options) {

		$post_args = array(
			'numberposts' => -1,
			'post_type'   => 'ld-achievement'
			);
		$achievements = get_posts($post_args);

		if(!empty($achievements))
		foreach($achievements as $achievement) {
			$achievement_options[$achievement->ID] = $achievement->post_title;
		}

		return $achievement_options;
	}
	function achievement_report($return, $params){
		global $wpdb;

		$user = empty($params['user']) ? null : $params['user'];
		$request_achievements_id = empty($_POST["achievement_id"])? 0 : intVal($_POST["achievement_id"]);
		$request_group_id = empty($params['group_id'])? 0 : intVal($params["group_id"]);

		$from = empty($params['from']) ? 0 : $params["from"];
		$to   = empty($params['to']) ? 0 : $params["to"];
		$group_type = !empty($params["group_type"]) ? $params["group_type"] : "";

		$sql = "SELECT user_id, post_id as achievement_id, points, created_at, `trigger` as achieved_for FROM {$wpdb->prefix}ld_achievements WHERE 1 = 1 ";

		if(!empty($request_achievements_id)) {
			$sql = $wpdb->prepare($sql . " AND  post_id = %d ", $request_achievements_id);
		}
		if(!empty($user->ID)) {
			$sql = $wpdb->prepare($sql . " AND  user_id = %d ", $user->ID);
		}
		if(!empty($from) && !empty($to))
		$sql = $wpdb->prepare($sql . " AND `created_at` >= '%s' AND `created_at` <= '%s'", date("Y-m-d H:i:s", $from),  date("Y-m-d H:i:s", $to));

		$sql = gb_groups::add_user_query($sql, $request_group_id, 'user_id', $group_type);
		$achievements_earned = $wpdb->get_results($sql);

		if(empty($achievements_earned))
			return $return;

		$achievement_ids = array();
		foreach($achievements_earned as $achieved) {
			$achievement_ids[] = $achieved->achievement_id;
		}

		$achievement_ids_string = implode(",", $achievement_ids);

		$triggers = LearnDash\Achievements\Achievement::get_triggers();
		$all_triggers = array();
		foreach($triggers as $triggers2) {
			foreach($triggers2 as $trigger_id => $trigger)
			$all_triggers[$trigger_id] = $trigger;
		}

		$sql = "SELECT post_id as achievement_id, meta_key, meta_value
		FROM $wpdb->postmeta
		WHERE post_id IN ($achievement_ids_string)
		AND meta_key IN ('achievement_message', 'image')";

		$achievement_data = $wpdb->get_results($sql);

		foreach($achievement_data as $k => $achievement_meta_){
			if(empty($achievement_meta[$achievement_meta_->achievement_id]))
			$achievement_meta[$achievement_meta_->achievement_id] = array();
			$achievement_meta[$achievement_meta_->achievement_id][$achievement_meta_->meta_key] = $achievement_meta_->meta_value;
		}

		if(empty($return["data"]) || !is_array($return["data"]))
		$return["data"] = array();

		$achievement_info = $user_info = array();
		foreach($achievements_earned as $achieved) {
			$achievement_id   = $achieved->achievement_id;
			$achievement_date = wp_date( "Y-m-d H:i:s", strtotime( $achieved->created_at ));
			$user_id 		  = $achieved->user_id;

			$user_info[$user_id] 			   = isset($user_info[$user_id]) ? $user_info[$user_id] : get_userdata($user_id);
			$achievement_info[$achievement_id] = isset($achievement_info[$achievement_id]) ? $achievement_info[$achievement_id] : get_post($achievement_id);

			if( empty($achievement_info[$achievement_id]) || empty($user_info[$user_id]) )
				continue;

			$achievement_image_url = empty($achievement_meta[$achieved->achievement_id]["image"]) ? "" : $achievement_meta[$achieved->achievement_id]["image"];
			$achievement_image 	   = empty($achievement_image_url) ? "No Image Available" : "<img width = 50px src=".$achievement_image_url.">";
			$achievement_desc 	   = empty($achievement_meta[$achieved->achievement_id]["achievement_message"]) ? "" : $achievement_meta[$achieved->achievement_id]["achievement_message"] ."<br>";

			$achieved_for = isset($all_triggers[$achieved->achieved_for])? $all_triggers[$achieved->achieved_for]:$achieved->achieved_for;

			$return["data"][] = [
				"sno" => 1,
				"achievement_date"	=> $achievement_date,
				"name"			=> gb_name_format( $user_info[$user_id] ),
				"user_id" 		=> $user_info[$user_id]->ID,
				"user_email" 	=> $user_info[$user_id]->user_email,
				"achivement_id" 	=> $achievement_id,
				"achievement_title"	=> wp_strip_all_tags( $achievement_info[$achievement_id]->post_title ),
				"achievement_image"	=> $achievement_image,
				"achievement_desc"	=> wp_strip_all_tags( $achievement_desc ),
				"achieved_for" 	=> $achieved_for,
				"points_earned"	=> $achieved->points
			];
		}

		return $return;
	}
}

$grassblade_learndash_achievement_report = new grassblade_learndash_achievement_report();
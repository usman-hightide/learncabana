<?php

if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if(empty($attributes["content_id"]))
{
    echo "<div style='color:red'>".__("Please select the xAPI Content from Block settings to create Leaderboard.", "grassblade")."</div>";
}
else
{
    $className = isset($attributes["className"]) ? $attributes["className"] : '';
    $role = isset($attributes["role"]) ? $attributes["role"] : 'all';
    $score = isset($attributes["score"]) ? $attributes["score"] : 'score';
    $limit = isset($attributes["limit"]) ? $attributes["limit"] : 20;
    $table = gb_leaderboard(array("id" => $attributes["content_id"], "allow" => $role, "score" => $score, "limit" => $limit, "class" => $className));

    if( empty($table) && isset($_REQUEST['context']) && $_REQUEST['context'] == 'edit' )
        $table = grassblade_leaderboard_table(array(array("user_id" => get_current_user_id(), "total" => 100, "total_timespent" => 100, "status" => __("Passed", "grassblade"),"timestamp" => date("Y-m-d H:i:s") )));

    echo $table;
}
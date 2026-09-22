<?php

function grassblade_get_course_structure_rest_api( $d ) {
    $params = array();

    if(isset($_REQUEST["id"]) && is_numeric($_REQUEST["id"]))
        $params["id"] = $_REQUEST["id"];

    if(isset($_REQUEST["posts_per_page"]) && is_numeric($_REQUEST["posts_per_page"]))
        $params["posts_per_page"] = $_REQUEST["posts_per_page"];

    if(isset($_REQUEST["modified_time"]) && is_numeric($_REQUEST["modified_time"]))
        $params["modified_time"] = $_REQUEST["modified_time"];

    return apply_filters("grassblade_get_course_structure", (object) array(), $params);
}

add_filter("grassblade_get_course_structure", function($r, $params) { //was: grassblade_learndash_get_courses till v5.3.2
    if( !empty($r->contents) )
        return $r;

    ini_set('set_time_limit', 1200);
    $contents = array();

    global $wpdb;

    if( !empty($params["modified_time"]) )
    $sql = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish' AND post_modified_gmt > '%s' ", gmdate("Y-m-d H:i:s", $params["modified_time"]));
    else
    $sql = "SELECT * FROM $wpdb->posts WHERE post_type = 'gb_xapi_content' AND post_status = 'publish'";


    $xapi_contents = $wpdb->get_results($sql);
    foreach ($xapi_contents as $value) {
        $contents[$value->ID] = $value;
    }

    foreach ($contents as $key => $value) {
        $contents[$key]->activity_id = grassblade_post_activityid($value->ID);
    }

    $r->contents = $contents;
    return $r;
}, 1000, 2);
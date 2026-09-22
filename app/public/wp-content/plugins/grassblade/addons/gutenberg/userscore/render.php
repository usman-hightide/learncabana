<?php

if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if (isset($attributes["content_id"])) {
    if ($attributes["content_id"] == '') {
        $content_id =  null;
    } else {
        $content_id =  $attributes["content_id"];
    }

} else {
    $content_id =  null;
}

$className = isset($attributes["className"]) ? $attributes["className"] : '';
$show = isset($attributes["show"]) ? $attributes["show"] : 'total_score';

$add = isset($attributes["add"]) ? $attributes["add"] : null;

$label = isset($attributes["label"]) ? $attributes["label"] : 'User Score';

$userscore = grassblade_xapi_content::user_score( array("show" => $show, "add" => $add, "content_id" => $content_id));
$label = strip_tags($label, "<b><p><div><span>");

echo '<div class="'.$className.' grassblade_userscore">'.$label.' '.$userscore.'</div>';
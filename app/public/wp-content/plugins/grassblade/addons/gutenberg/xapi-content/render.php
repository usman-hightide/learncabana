<?php

if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if(empty($attributes["content_id"]))
{
    echo "<div style='color:red'>".__("Please select the xAPI Content from Block settings on the right.", "grassblade")."</div>";
    return;
}

$className = isset($attributes["className"]) ? $attributes["className"] : '';
$current_post = get_post();
$display_status = apply_filters("gb_xapi_block_access", true, $current_post, $attributes, $content);
if( $display_status )
    echo grassblade(array("id" => $attributes["content_id"], "class" => $className));

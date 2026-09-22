<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( dirname(__FILE__) ."/class.gb_groups.php");
include_once( dirname(__FILE__) ."/class.gb_groups_base.php");

$gb_groups = new gb_groups();

//Depricated since GrassBlade xAPI Companion v5.4.0 use gb_groups::add_user_query instead
function grassblade_add_group_user_query($sql, $group_id = "", $user_id_key = "user_id", $group_type = "") {
	return gb_groups::add_user_query($sql, $group_id, $user_id_key, $group_type);
}
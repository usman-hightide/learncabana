<?php

add_action("init", "grassblade_table_styles");
function grassblade_table_styles() {
	if(is_admin() && !empty($_GET['page']) && $_GET['page'] == "grassblade-lrs-settings")
	wp_enqueue_style( 'wp-color-picker', '', array('jquery') );
}

add_filter("grassblade_scripts_dep", function($deps) {
	if(is_admin() && !empty($_GET['page']) && $_GET['page'] == "grassblade-lrs-settings")
	$deps[] = "wp-color-picker";
	return $deps;
}, 10, 1);
add_filter('grassblade_settings_fields', 'grassblade_table_styles_settings', 10,1);
function grassblade_table_styles_settings($fields){

	$table_styles = array();

	include_once(dirname(__FILE__)."/../nss_arraytotable.class.php");
	$data = array(
		array(
			"Head1" => "Value 1",
			"Head2" => "Value 2",
		),
		array(
			"Head1" => "Value 3",
			"Head2" => "Value 4",
		),
	);
	$ArrayToTable = new NSS_ArrayToTable($data);
	$ArrayToTable->get();

	$table_styles[] = array( 'id' => 'table_preview', 'label' => '',  'placeholder' => '#FBB216', 'type' => 'html', 'never_hide' => true ,'help' => '', 'html' => "<b>".__("Preview")."</b><br>".$ArrayToTable->get() );

	$table_styles[] = array( 'id' => 'table_th_bg_color', 'label' => __( 'Header Background', 'grassblade' ),  'placeholder' => '#FBB216', 'type' => 'wp-color-picker', 'never_hide' => true ,'help' => '' );
	$table_styles[] = array( 'id' => 'table_th_txt_color', 'label' => __( 'Header Text', 'grassblade' ),  'placeholder' => '#FFFFFF', 'type' => 'wp-color-picker', 'never_hide' => true ,'help' => '' );
	$table_styles[] = array( 'id' => 'table_tb_txt_color', 'label' => __( 'Text Color', 'grassblade' ),  'placeholder' => '#000000', 'type' => 'wp-color-picker', 'never_hide' => true ,'help' => '' );
	$table_styles[] = array( 'id' => 'table_tb_color1', 'label' => __( 'Cell Color 1', 'grassblade' ),  'placeholder' => '#B7CF3C', 'type' => 'wp-color-picker', 'never_hide' => true ,'help' => '' );
	$table_styles[] = array( 'id' => 'table_tb_color2', 'label' => __( 'Cell Color 2', 'grassblade' ),  'placeholder' => '#90B53D', 'type' => 'wp-color-picker', 'never_hide' => true ,'help' => '' );


	if (!empty($table_styles)) {
		$fields[] = array('id' => 'table_style_setting', 'label' => __("Table Styles", "grassblade"), "type" => "html", "subtype" => "field_group_start");
		foreach ($table_styles as $table_style) {
			$fields[] = $table_style;
		}
		$fields[] = array('id' => 'table_style_setting_end', 'label' => __("Table Styles", "grassblade"), "type" => "html", "subtype" => "field_group_end");
	}
	return $fields;
}

add_action("wp_head", "grassblade_table_styles_set", 1000);
add_action("admin_head", "grassblade_table_styles_set", 1000);

function grassblade_table_styles_set() {
	$grassblade_settings = grassblade_settings();

	if(empty($grassblade_settings["table_th_bg_color"]) || $grassblade_settings["table_th_bg_color"] == "#FBB216" &&  $grassblade_settings["table_th_txt_color"] == "#FFFFFF" &&  $grassblade_settings["table_tb_txt_color"] == "#000000" && $grassblade_settings["table_tb_color1"] == "#B7CF3C" && $grassblade_settings["table_tb_color2"] == "#90B53D")
		return;

	?><style id="grassblade_table_styles">
	.grassblade_table th {
		background: <?php echo $grassblade_settings["table_th_bg_color"];?> !important;
		color: <?php echo $grassblade_settings["table_th_txt_color"];?> !important;
	}

	.grassblade_table td {
		background: <?php echo $grassblade_settings["table_tb_color1"];?> !important;
		color: <?php echo $grassblade_settings["table_tb_txt_color"];?> !important;
	}
	.grassblade_table tr.tr_odd td, .grassblade_table tr.odd td {
		background: <?php echo $grassblade_settings["table_tb_color2"];?> !important;
	}
	</style><?php
}

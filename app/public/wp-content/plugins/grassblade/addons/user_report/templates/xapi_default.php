<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<link rel="stylesheet" type="text/css" href="<?php echo plugins_url() ?>/grassblade/assets/DataTables/datatables.min.css?v=<?php echo GRASSBLADE_VERSION; ?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo plugins_url() ?>/grassblade/assets/DataTables/media/css/jquery.dataTables.min.css?v=<?php echo GRASSBLADE_VERSION; ?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo plugins_url() ?>/grassblade/addons/user_report/css/style.css?v=<?php echo GRASSBLADE_VERSION; ?>"/>
<style>
.gb-user-info{
	background-color: <?php echo esc_attr($bg_color); ?>;
}
.gb-course-info {
	border: 2px solid <?php echo esc_attr($bg_color); ?> !important;
}
.gb-expand-filter button{
	background: <?php echo esc_attr($bg_color); ?> !important;
}
.xapi-content-link:hover{
	color: <?php echo esc_attr($bg_color); ?> !important;
}
.paginate_button:hover {
	background: <?php echo esc_attr($bg_color); ?> !important;
}
.gb-collapsed #collapse_text {
	display: none;
}
.gb-expanded #expand_text {
	display: none;
}


</style>
<script type="text/javascript" src="<?php echo plugins_url() ?>/grassblade/assets/DataTables/datatables.min.js?v=<?php echo GRASSBLADE_VERSION; ?>"></script>
<div id="gb_user_report" class="gb-profile">
	<div class='gb-user-profile'><?php echo $profile_pic; ?></div>
	<div class='gb-user-info'>
		<h3><?php echo gb_name_format( $user ); ?></h3>
		<a class='gb-edit-profile' href='<?php echo $edit_profile?>'><?php echo __("Edit profile", "grassblade"); ?></a>
		<div class='gb-course-data'>
			<div><p class="gb-data-value"><?php echo intVal($total_xapi_contents); ?></p><p><?php echo ucwords(__($courses_label, "grassblade")); ?></p></div>
			<div><p class="gb-data-value"><?php echo intVal($total_completed); ?></p><p><?php echo __("Completed", "grassblade"); ?></p></div>
			<div><p class="gb-data-value"><?php echo intVal($total_in_progress); ?></p><p><?php echo __("In Progress", "grassblade"); ?></p></div>
			<div><p class="gb-data-value"><?php echo number_format($avg_score, 2).'%'; ?></p><p><?php echo __("Avg Score", "grassblade"); ?></p></div>
		</div>
		<div style="clear: both;"></div>
	</div>
	<div class="gb-course-info">
		<div class="gb-status-filter">
			<label><b><?php echo __("Result Filter:", "grassblade"); ?></b>  </label>
			<select id="gb_result_filter" data-default="<?php echo $filter; ?>">
				<option value="all"><?php echo __("All", "grassblade"); ?></option>
				<option><?php echo __("Attempted", "grassblade"); ?></option>
				<option><?php echo __("Passed", "grassblade"); ?></option>
				<option><?php echo __("Completed", "grassblade"); ?></option>
				<option><?php echo __("Failed", "grassblade"); ?></option>
				<option><?php echo __("In Progress", "grassblade"); ?></option>
			</select>
		</div>
		<div class="gb-expand-filter">
			<button id="gb_expand_btn" class="gb-collapsed" onclick="gb_expand_attempts('<?php echo $user->ID; ?>');">
				<b>
					<span id="expand_text"><?php echo __("Expand All", "grassblade"); ?></span>
					<span id="collapse_text"><?php echo __("Collapse All", "grassblade"); ?></span>
				</b>
			</button>
		</div>
		<table id="gb_report_table">
			<thead>
				<tr>
					<th style="min-width:55px;"><?php echo __("SNo.", "grassblade"); ?></th>
					<th><?php echo ucwords(__($courses_label, "grassblade")); ?></th>
					<th style="min-width:64px;"><?php echo __("Score", "grassblade"); ?></th>
					<th style="min-width:88px;"><?php echo __("Status", "grassblade"); ?></th>
					<th><?php echo __("Time Spent", "grassblade"); ?></th>
					<th style="min-width:100px;"><?php echo __("Attempts", "grassblade"); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php $sno = 1;
			 $attempts = array();
			 $content_report_enable = array();
			 foreach ($xapi_contents as $key => $xapi_content) {
				$attempts['attempts_'.$xapi_content['content']->ID.'_'.$user->ID] = json_encode($xapi_content['attempts']);
				$content_report_enable[$xapi_content['content']->ID] = $xapi_content['quiz_report_enable'];
				?>
				<tr id="gb_row_<?php echo $xapi_content['content']->ID; ?>">
					<td scope="row" data-label="<?php echo __("SNo.", "grassblade"); ?>">
						<span><?php echo $sno; ?></span>
					</td>
					<td data-label="<?php echo __("Courses", "grassblade"); ?>"><a class='xapi-content-link' href='<?php echo $xapi_content["content"]->url; ?>'><?php echo strip_tags($xapi_content['content']->post_title); ?></a></td>
					<td data-label="<?php echo __("Score", "grassblade"); ?>"><?php echo $xapi_content['best_score']; ?> </td>
					<td data-label="<?php echo __("Status", "grassblade"); ?>"><?php echo $xapi_content['content_status']; ?> </td>
					<td data-label="<?php echo __("Time Spent", "grassblade"); ?>"><?php echo $xapi_content['total_time_spent']; ?> </td>
					<?php if (!empty($xapi_content['attempts'])) { ?>
						<td data-label="<?php echo __("Attempts", "grassblade"); ?>"><a onclick='gb_get_score("<?php echo intVal($xapi_content['content']->ID); ?>","<?php echo intVal($user->ID); ?>");'><img class="gb-icon-img" src='<?php echo plugins_url(); ?>/grassblade/img/down-arrow.png' width="20px"></a></td>
					<?php } else { ?>
						<td data-label="<?php echo __("Attempts", "grassblade"); ?>"> - </td>
					<?php } ?>
				</tr>
			<?php $sno++; } ?>
			</tbody>
		</table>
	</div>
</div>
<script type="text/javascript">
//<![CDATA[
	var gb_content_attempts = <?php echo json_encode($attempts); ?>;
	var gb_content_quiz_enable = <?php echo json_encode($content_report_enable); ?>;
	var total_xapi_contents = '<?php echo intVal($total_xapi_contents); ?>';
//]]>
</script>

<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<tr class="group report_options" style=" display:none " onchange="grassblade_option_selected(jQuery(this).find('select'), 'group', 'course'); grassblade_report_remove_user_list();">
	<th><?php _e("Group", "grassblade"); ?></th>
	<td>
		<select id="nss_report_group" name="group_id">
			<option value=""><?php _e("--- Select a Group ---", "grassblade"); ?></option>
			<?php if(grassblade_lms::is_admin()) { ?>
			<option value="all"><?php _e("All Users", "grassblade"); ?></option>
			<?php } ?>
			<?php echo $this->get_group_options(); ?>
		</select>
	</td>
</tr>
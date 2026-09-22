<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<tr class="achievement report_options"  style=" display:none;" onchange="grassblade_option_selected(jQuery(this).find('select'), 'achievement');" call-onunload="grassblade_report_unselect_course">
	<th><?php _e("Achievements", "grassblade"); ?></th>
	<td>
		<?php echo grassblade_reports_achievements::get_field(); ?>
	</td>
</tr>
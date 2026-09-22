<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<tr class="course report_options" style=" display:none; " onchange="grassblade_option_selected(jQuery(this).find('select'), 'course');" call-onunload="grassblade_report_unselect_course">
	<th><?php _e("Course", "grassblade"); ?><br>
		<?php if( grassblade_reports::can_report_on_all_courses() ) { ?>
		<small class='show_all_courses_toggle' onClick="show_all_courses_toggle();"><span class='show_all_courses'>Show All Courses</span><span class='show_group_courses'>Show Group Courses</span></small>
		<?php } ?>
	</th>
	<td>
		<select id="nss_report_course" name="course_id">
			<option value=""><?php _e("--- Select a Course ---", "grassblade"); ?></option>
			<?php echo $this->get_course_options_html(); ?>
		</select>
	</td>
</tr>
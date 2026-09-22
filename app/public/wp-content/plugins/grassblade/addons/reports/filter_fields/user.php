<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<tr class="user report_options" style="display:none;" onchange="grassblade_option_selected(jQuery(this).find('select'), 'user');" call-onload="grassblade_report_show_user_list" call-onunload="grassblade_report_remove_user_list">
	<th><?php _e("User", "grassblade"); ?></th>
	<td>
		<input id="nss_report_users_search" type="text" placeHolder="Search" onKeyUp="grassblade_report_user_search()" /> <br>
		<select id="nss_report_users" name="user" class="enable-select2" ></select>
	</td>
</tr>
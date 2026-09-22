<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<tr class="nss_report_show_contents report_options"  style="display:none;" call-onload="grassblade_report_remove_content_list" call-onunload="grassblade_report_remove_content_list">
	<th></th>
	<td>
		<input id="nss_report_show_contents" type="button" value="Continue" onClick="return nss_report_show_contents();" class="btn btn-green btn-smaller"/>
	</td>
</tr>
<tr class="nss_report_show_contents_one report_options"  style="display:none;" call-onload="grassblade_report_remove_content_list" call-onunload="grassblade_report_remove_content_list">
	<th></th>
	<td>
		<input id="nss_report_show_contents_one" type="button" value="Continue" onClick="return nss_report_show_contents(true);" class="btn btn-green btn-smaller"/>
	</td>
</tr>
<tr class="select_content report_options" style="display:none;" onChange="grassblade_option_selected(jQuery(this).find('input:checked'), 'content');">
	<th><?php _e("Select Content", "grassblade"); ?></th>
	<td>
		<div style="border: 1px solid #ccc;padding: 10px;background: #fafafa;">
			<div>
				<a class="nss_report_select_all" href="#" onClick="return nss_report_select_contents(this);" style="font-size: 12px; margin-right: 20px;"><?php _e("Select All", "grassblade"); ?></a>
				<input id="nss_report_content_search" type="text" placeHolder="Search" onKeyUp="grassblade_report_content_search()" style="width: 60%; padding: 3px 10px;"/>
			</div>
			<div id="nss_report_contents">
			</div>
		</div>
	</td>
</tr>
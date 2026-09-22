
GB_REPORTS_FUNCTIONS["gradebook"] = [];
GB_REPORTS_FUNCTIONS["gradebook"]["columns"] = function(columns, data, response, context) {
	//console.log(columns, data, context);
	columns  = 	[
					{ data: "sno", title: "S.No.", orderable:false, searchable: false },
					{ data: "name", title: "User" },
					{ data: "user_email", title: "Email", visible: false },
				];
	var i = columns.length;
	jQuery("#nss_report_contents :checkbox:checked").each(function(t) {
		columns[i++] = { data: jQuery(this).val(), title: jQuery(this).parent().text(), className: "content_"+jQuery(this).val() + " gb-center" };
	});
	return columns;
}


GB_REPORTS_FUNCTIONS["gradebook"]["createdCell"] = function(params, context) {
	var col = params.col;
	var content_id = params.updated_column_list[col].data;
	var global_avg = (typeof params.response == "object" && typeof params.response.global_avg == "object" && !isNaN(content_id) && typeof params.response.global_avg[content_id] == "number")? params.response.global_avg[content_id]:NaN;
	gb_reports_score_click(params.td, params.cellData, global_avg );
}
GB_REPORTS_FUNCTIONS["gradebook"]["initComplete"] = function(settings, context) {

	var head_tr = jQuery(context).find("thead tr").clone().addClass('gb_question_report_head');
	jQuery(head_tr).find("th").each(function(i, th) {
		jQuery(th).removeClass('sorting').removeClass('sorting_asc').removeClass('sorting_desc');
		var content_class_arr =  th.className.split(/\s+/).filter((c) => {return c.match(/content_(\d+)/)  != null;});
		var content_id = content_class_arr.length? content_class_arr[0].replace('content_', '') * 1: 0;
		var group_id = nss_report_get_param('group');
		var group_type = nss_report_get_param('group', 'selected-data-type');
		if( content_id ) {
			jQuery(th).html('Question Report').addClass('gb_link').on('click', (e) => { get_gb_question_report(content_id, group_id, group_type);});
		}
		else
		jQuery(th).html('');
	});
	jQuery(context).find("thead").append(head_tr);
}

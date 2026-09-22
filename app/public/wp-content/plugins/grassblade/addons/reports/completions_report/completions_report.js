
GB_REPORTS_FUNCTIONS["completions_report"] = [];
GB_REPORTS_FUNCTIONS["completions_report"]["columns"] = function(columns, data, response, context) {
	var group_avg_visibility = (typeof data.group_id == "number");
	columns = [
				{ data: "sno", title: gb_localize("S.No."), orderable:false, searchable: false },
				{ data: "name", title: gb_localize("User"), visible: true },
				{ data: "user_email", title: gb_localize("Email"), visible: false },
				{ data: "content", title: gb_localize("Content") },
				{ data: "date", title: gb_localize("Date"), className: "gb-center" },
				{ data: "score", title: gb_localize("Student Score %"), className: "gb-center"},
				{ data: "group_avg", title: gb_localize("Group Avg"), visible: group_avg_visibility, className: "gb-center"},
				{ data: "global_avg", title: gb_localize("Global Avg"), className: "gb-center"},
				{ data: "time_spent_h", title: gb_localize("Time Spent"), orderData: [9], className: "gb-center"},
				{ data: "time_spent", visible: false, className: "gb-center" }
			];
	return columns;
}
GB_REPORTS_FUNCTIONS["completions_report"]["buttons"] = function(buttons, response, context) {
	buttons["excel"] = 1;
	buttons["print"] = 1;
	buttons["pdf"]	= 1;
	return buttons;
}

GB_REPORTS_FUNCTIONS["completions_report"]["createdCell"] = function(params, context) {
	if(typeof params.response.global_avg == "object" && params.updated_column_list[params.col].data == "score") {
		var global_avg = params.rowData.global_avg*1;
		gb_reports_score_click(params.td, params.cellData, global_avg);
	}
	if(params.updated_column_list[params.col].data == "content") {
		gb_reports_content_click(params.td, params.cellData);
	}
}

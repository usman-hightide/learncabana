
GB_REPORTS_FUNCTIONS["achievement_report"] = [];
GB_REPORTS_FUNCTIONS["achievement_report"]["columns"] = function(columns, data, response, context) {
	columns = [
		{ "data" : "sno",	 			"title"	: gb_localize("S.No."), 		"orderable"	: false, "searchable"	: false },
		{ "data" : "achievement_date", 	"title"	: gb_localize("Achievement Date") },
		{ "data" : "name", 				"title"	: gb_localize("User") },
		{ "data" : "user_email", 		"title"	: gb_localize("Email"), 		"visible"	: false },
		{ "data" : "achivement_id", 	"title"	: gb_localize("Achivement Id"), className	: "gb-center" },
		{ "data" : "achievement_title", "title"	: gb_localize("Achievement Title") },
		{ "data" : "achievement_image", "title"	: gb_localize("Achievement Image"), className: "gb-center" },
		{ "data" : "achievement_desc", 	"title"	: gb_localize("Achievement Description") },
		{ "data" : "achieved_for", 		"title"	: gb_localize("Achieved For") },
		{ "data" :	"points_earned", 	"title" : gb_localize("Points Earned"), className: "gb-center" }
	];
	return columns;
}
GB_REPORTS_FUNCTIONS["achievement_report"]["data"] = function(data, context) {
	var achievement_id = jQuery("#nss_report_achievement").val();
	achievement_id = (achievement_id == "all")? achievement_id:parseInt(achievement_id);

	data.achievement_id = achievement_id;
	console.log(data);
	return data;
}
GB_REPORTS_FUNCTIONS["achievement_report"]["export_data_format"] = function(data, params) {
	var column = grassblade_report_table.settings().init().columns[params.column + 1];
	switch(column.data) {
		case 'achievement_image':
			return '';
		case 'achieved_for':
			var ret = [];
			data.split("</li><li>").forEach(function(v) {ret.push(v.replace(/<\/?[^>]+(>|$)/g, ""))});
			return ret.join(", ");
		default:
			return data;
//			return data.replace(/<\/?[^>]+(>|$)/g, ""); //strip tags
	}

	return data;
}
function nss_report_level1_submit() {
	return nss_report_show_contents();
}
function nss_report_get_param(id, attr = "val", parse = 'int') {
	var return_type = '';
	switch(id) {
		case 'report':
			id = '#nss_report';
			parse = 'string';
			break;
		case 'contents':
			id = '#nss_report_contents :checkbox:checked, #nss_report_contents :radio:checked';
			return_type = 'array';
			break;
		default:
			id = "#nss_report_" + id;
	}

	var get_value = function(el, parse, attr) {
		var value = "";
		switch(attr) {
			case 'val':
				value = el.val();
				break;
			case 'selected-data-type':
				el = el.find("option:selected");
				value = el.data('type');
				parse = 'string';
				break;
			case 'data-type':
				value = el.data('type');
				parse = 'string';
				break;
			case 'name':
				value = el.find("option:selected").text();
				parse = 'string';
				break;
			default:
				value = el.attr(attr);
				break;
		}
		switch(parse) {
			case 'int':
				if( value != 'all' ) {
					value = parseInt(value);
					value = isNaN(value)? 0:value;
				}
				break;
			case 'string':
				value = (typeof value == 'string')? value.trim():"";
				break;
		}
		if( value == undefined )
		value = '';
		return value;
	};

	var el = jQuery(id);
	if( el.length == 0 )
	return '';
	else if( el.length > 1 || return_type == 'array' ) {
		var r = [];
		for(var i = 0; i < el.length; i++) {
			r[i] = get_value(jQuery(el[i]), parse, attr);
		}
	}
	else if(el.length == 1) {
		var r = get_value(el, parse, attr);
	}
	return r;
}
function nss_report_show_contents( content_one = false ) {
	var course_id = nss_report_get_param('course');
	var group_id = nss_report_get_param('group');
	var group_type = nss_report_get_param('group', 'selected-data-type');

	if(isNaN(course_id) && course_id != "all") {
		jQuery("#nss_report_contents").html("Invalid Selection.");
		return;
	}

	//Fetch contents list
	jQuery("#nss_report_contents").html(gb_localize("Loading..."));
	jQuery("#nss_report_content_search").siblings("a").html(gb_localize("Select All"));
	jQuery("#nss_report_content_search").val('');
	jQuery(".select_content").show();


	var data = {
		'action'	: 'grassblade_report',
		'function' 	: 'get_contents_list',
		'course_id'	: course_id,
		'group_id'	: group_id,
		'group_type'	: group_type
	};

	if(grassblade_report_course_contents[group_id + "." + course_id] != undefined &&  typeof grassblade_report_course_contents[group_id + "." + course_id] != undefined) {
		grassblade_report_show_content_list(grassblade_report_course_contents[group_id + "." + course_id], content_one);
	}
	else
	{
		jQuery.post(GB_REPORTS.ajaxurl, data, function(response) {
			if(typeof response == "string")
				response = JSON.parse(response);

			grassblade_report_course_contents[group_id + "." + course_id] = response;
			grassblade_report_show_content_list(response, content_one);
		});
	}
}
function grassblade_reports_add_classes() {
	jQuery("#grassblade_reports").attr("class", "");
	jQuery("#grassblade_reports").find(".show_on").hide();
	jQuery("#grassblade_reports").find(".hide_on").show();

	jQuery("#grassblade_reports select").each(function(i, v) {
	//	console.log(i, v);
		var c = jQuery(v).attr("name") + "_" + jQuery(v).val();
		c = c.replace(/[^a-z_0-9\s]/gi, '').replace(/[\s]/g, '-');

		if(v.id == 'nss_report_group' && c.includes("group_id_") && typeof v.selectedOptions[0].dataset != "undefined" && typeof v.selectedOptions[0].dataset.type != "undefined"){
			c = c + "_" + v.selectedOptions[0].dataset.type.replace("WP:", "").replaceAll(" ", "").trim().toLowerCase();
//			console.log(c);
		}
		jQuery("#grassblade_reports").addClass(c);
		jQuery("#grassblade_reports").find(".show_on_" + c).show();
		jQuery("#grassblade_reports").find(".hide_on_" + c).hide();
	});
}
function grassblade_option_selected(context, field_name, default_next = "") {
	grassblade_reports_add_classes();

	var report_name = nss_report_get_param('report');
	if(report_name == "")
		jQuery("#grassblade_reports .report_options").hide();

	if(typeof GB_REPORTS == "undefined" || GB_REPORTS == null || typeof GB_REPORTS["report_filters_ux"] == "undefined" || typeof GB_REPORTS["report_filters_ux"][report_name] == "undefined" )
	return;

	var filters_ux = GB_REPORTS["report_filters_ux"][report_name];
	var next_options = [];

	if(typeof filters_ux[field_name] == "string")
	next_options.push(  filters_ux[field_name] );
	else if( typeof filters_ux[field_name] == "object" )
	next_options = filters_ux[field_name];
	else if( default_next != "" )
	next_options.push(  default_next );

	if(next_options.length == 0)
	return;

	jQuery.each(next_options, function(i,v){
		if(jQuery(context).val())
		grassblade_show(v);
		else
		grassblade_hide(v);
	});
}
function grassblade_hide(c) {
	if(typeof c == "string")
		c = "#grassblade_reports ." + c;

	if(jQuery(c).length) {
		jQuery(c).hide();
		var call = jQuery(c).attr("call-onunload");
		if(typeof call == "string" && call.length > 1)
			window[call]();
		jQuery(c).trigger("change");
	}
	//jQuery(".nss_report_submit").hide();
}
function grassblade_show(c) {
	if(jQuery("#grassblade_reports ." + c).length) {
		jQuery("#grassblade_reports ." + c).show();
		var call = jQuery("#grassblade_reports ." + c).attr("call-onload");
		if(typeof call == "string" && call.length > 1)
			window[call]();
		jQuery(c).trigger("change");
	}
}
function grassblade_report_show_content_list(response, radio = false) {
	var html = "";//<option value='0'> --- Select a User --- (" + response.length + ")</option>";
	var type = (radio)? 'radio':'checkbox';

	if(typeof response.error == "string")
		html = response.error;
	else
	jQuery.each(response, function(i, u) {
		let checked = (typeof GB_REPORTS.defaults.content == "object" && typeof GB_REPORTS.defaults.content[u.ID] != "undefined")? "CHECKED" : "";
		html += "<label class='content_type content_type_" + u.content_type + "' ><input type='" + type + "' name='content[]' value='" + u.ID + "' " + checked + " /> " + u.name + "</label>";
	});
	if(html == "")
		html = "No content found";
	jQuery("#nss_report_contents").html(html);
	jQuery("#grassblade_reports .select_content").removeClass("choice_" + (radio? 'checkbox' : 'radio') ).addClass("choice_" + type);

	if(typeof GB_REPORTS.defaults.content != "undefined")
		GB_REPORTS.defaults.content = undefined;

}
function grassblade_reset_display(el) {
	if( typeof el != "object" || typeof el.style != "object" )
		return;

	if (typeof el.style.removeProperty == "function") {
		el.style.removeProperty('display');
	} else {
		el.style.removeAttribute('display');
	}
}
function grassblade_report_content_search() {
	var search_text = jQuery("#nss_report_content_search").val().toLowerCase().trim();
//	console.log(search_text);
	var zero_option = '';
	var count = 0;
	// jQuery("#nss_report_contents > label").show();
	jQuery("#nss_report_contents > label").each(function(i, v) {
		grassblade_reset_display(v);
		var v_html = jQuery(v).html();

// 	console.log("search_text: " + search_text + " v_html: " + v_html + " checked: " + jQuery(v).find("input").prop("checked"));
// 	console.log("jQuery(v).val() == 0 " + (jQuery(v).val() == 0) + " ; search_text == '' : " + (search_text == "") + " ; v_val.toLowerCase().indexOf(search_text) != -1 :  " + (v_val.toLowerCase().indexOf(search_text) != -1));
		if( search_text == "" || v_html.toLowerCase().indexOf(search_text) != -1 || jQuery(v).find("input").prop("checked") || jQuery(v).find("input").val() == search_text ) {
			//jQuery(v).show();
			grassblade_reset_display(v);

			if( jQuery(v).val() == 0 )
				zero_option = v;
			else
				count++;
		}
		else
			jQuery(v).hide();
	});

}
jQuery(function() {
	if(typeof GB_REPORTS.defaults.report == "string") {
		jQuery("#nss_report").val(GB_REPORTS.defaults.report);
		jQuery("#nss_report").trigger("change");
	}

	jQuery("#nss_report_contents input").on("change", function() {
		if(this.checked) {
			jQuery(this).parent().prependTo("#nss_report_contents");
		//	console.log(jQuery(this).val());
		}
	});
});
jQuery(function() {
	if(typeof GB_REPORTS.defaults.date_range != "undefined" && typeof GB_REPORTS.defaults.date_range.start != "undefined")
	var start = moment(GB_REPORTS.defaults.date_range.start);
	else
	var start = moment().subtract(29, 'days');

	if(typeof GB_REPORTS.defaults.date_range != "undefined" && typeof GB_REPORTS.defaults.date_range.end != "undefined")
	var end = moment(GB_REPORTS.defaults.date_range.end);
	else
	var end = moment();

	function cb(start, end) {
		jQuery('#nss_report_date_range span').html(start.format('DD MMM, YYYY') + ' - ' + end.format('DD MMM, YYYY'));
	}

	var datepicker_ranges = {};

	  	//   'Today': [moment(), moment()],
		//   'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
		//   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
	datepicker_ranges['All Time'] =  [moment('1970-01-01'), moment()];
	datepicker_ranges['Last 30 Days'] = [moment().subtract(29, 'days'), moment()];
	datepicker_ranges['This Month (' + moment().startOf('month').format("MMM") + ')']  =  [moment().startOf('month'), moment().endOf('month')];
	datepicker_ranges['Last Month (' + moment().subtract(1, 'month').startOf('month').format("MMM") + ')'] =  [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
	datepicker_ranges['This Year (' + moment().startOf('year').format("Y") + ')'] =  [moment().startOf('year'), moment().endOf('year')];
	datepicker_ranges['Last Year (' + moment().subtract(1, 'year').startOf('year').format("Y") + ')'] =  [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')];
	jQuery('#nss_report_date_range').daterangepicker({
		startDate: start,
		endDate: end,
		ranges: datepicker_ranges,
		locale: {
			format: 'DD MMM, YYYY'
		}
	}, cb);

	cb(start, end);

});

function nss_report_select_contents(elem) {
	var current = jQuery(elem).html();
	if(current == gb_localize("Select All")) {
		jQuery(elem).html(gb_localize("Select None"));
		jQuery("#nss_report_contents input").prop('checked', false);
		jQuery("#nss_report_contents input:visible").prop('checked', true);

		jQuery("#nss_report_content_search").val("");
		grassblade_report_content_search();
	}
	else
	{
		jQuery(elem).html(gb_localize("Select All"));
		jQuery("#nss_report_contents input").prop('checked', false);
	}
	jQuery("#nss_report_contents").trigger("change");
	return false;
}
function grassblade_report_user_search() {
	var search_text = jQuery("#nss_report_users_search").val().toLowerCase().trim();
//	console.log(search_text);
	var zero_option = '';
	var count = 0;
	jQuery("#nss_report_users option").show();
	jQuery("#nss_report_users option").each(function(i, v) {
		var v_val = jQuery(v).val();
		var v_html = jQuery(v).html();

		if( jQuery(v).val() == 0 ||  jQuery(v).val() == "" || search_text == "" || v_val.toLowerCase().indexOf(search_text) != -1 || v_html.toLowerCase().indexOf(search_text) != -1 ) {
			if(typeof v_val == "string" && v_val.search("all") === 0)
				return;

			jQuery(v).show();
			if( jQuery(v).val() == 0 ||  jQuery(v).val() == "" )
				zero_option = v;
			else
				count++;
		}
		else
			jQuery(v).hide();


	});

	jQuery(zero_option).html("Updating...");
	setTimeout(function(){ jQuery(zero_option).html(" --- Select a User --- (" + count + ")"); }, 200);

}

function grassblade_report_selected() {

	jQuery("#grassblade_reports .report_options").each(function(i, option) {
		grassblade_hide(option);
	});

	if(typeof GB_REPORTS.defaults.group_id != "undefined") { //need to add group_type
		jQuery("#grassblade_reports #nss_report_group").val(GB_REPORTS.defaults.group_id);
		jQuery("#grassblade_reports #nss_report_group").trigger("change");
		GB_REPORTS.defaults.group_id = undefined;
	}
	else
	{
		jQuery("#grassblade_reports #nss_report_group").val('');
	}
	if(typeof GB_REPORTS.defaults.course_id != "undefined") {
		jQuery("#grassblade_reports #nss_report_course").val(GB_REPORTS.defaults.course_id);
		jQuery("#grassblade_reports #nss_report_course").trigger("change");
		GB_REPORTS.defaults.course_id = undefined;
	}
}

var grassblade_report_course_users = [];
var grassblade_report_course_contents = [];

function grassblade_report_show_user_list() {
	if(!jQuery("#nss_report_course").is(":visible") && !jQuery("#nss_report_group").is(":visible"))
	return;

	var course_id = nss_report_get_param('course');
	var group_id = nss_report_get_param('group');
	var group_type = nss_report_get_param('group', 'selected-data-type');

	jQuery("#nss_report_users").html("<option>" + gb_localize("Loading...") + "</option>");

	var data = {
		'action'	: 'grassblade_report',
		'function' 	: 'get_users',
		'course_id'	: course_id,
		'group_id'	: group_id,
		'group_type' : group_type
	};

	if(grassblade_report_course_users[group_id + "." + course_id] != undefined &&  typeof grassblade_report_course_users[group_id + "." + course_id] != undefined) {
		grassblade_report_course_selected_process_response(grassblade_report_course_users[group_id + "." + course_id]);
	}
	else
	{
		jQuery.post(GB_REPORTS.ajaxurl, data, function(response) {
			if(typeof response == "string")
				response = JSON.parse(response);

			grassblade_report_course_users[group_id + "." + course_id] = response;
			grassblade_report_course_selected_process_response(response);
		});
	}
}
function grassblade_report_remove_user_list() {
	jQuery("#nss_report_users").html("");
	jQuery("#nss_report_users_search").val("");
	setTimeout(function() {
		if( jQuery(".report_options.user").is(":visible") )
			grassblade_report_show_user_list();
	}, 500);
}
function grassblade_report_remove_content_list() {
	jQuery("#nss_report_contents input:checked").prop('checked', false);
	jQuery("#nss_report_contents").html("");
	grassblade_hide("select_content");
	//jQuery("#nss_report_contents").trigger("change")
}
function grassblade_report_unselect_course() {
	jQuery("#nss_report_course").val('');
}
function grassblade_report_course_selected_process_response(response) {
	if(typeof response.error == "string") {
		jQuery("#nss_report_users").html("<option value='0'>" + response.error + "</option");
		return;
	}
	var count = response.filter((user) => user.ID != "all").length;
	var options = "<option value=''> --- Select a User --- (" + count + ")</option>";
	jQuery.each(response, function(i, u) {
		var name, email;
		id = (u.ID == "all")? "all":parseInt(u.ID);
		name = u.name.trim();
		email = u.email.trim();
		c_class = (typeof u.class == "string")? u.class:"";

		if(u.name.trim() == "")
			name = email;

		options += '<option class="' + c_class + '" value="' + id + ':' + email + '" >' + name + "</option>";
	});
	jQuery("#nss_report_users").html(options);
	if(typeof GB_REPORTS.defaults.user != "undefined") {
		jQuery("#nss_report_users").val(GB_REPORTS.defaults.user);
		GB_REPORTS.defaults.user = undefined;
	}
	jQuery("#nss_report_users").trigger("change");
}

var grassblade_report_table;
jQuery(document).on("click", function(e) {
	jQuery(".closable").each(function(i,element) {
		if(jQuery(e.target).closest(element).length == 0 && ( jQuery(e.target).closest('.closable_button').attr('id') == undefined || jQuery(e.target).closest('.closable_button').attr('id').replace('_button','') != jQuery(element).attr('id') ) )
			jQuery(element).hide();
	});
});

function grassblade_reports_export_data_format(data, params) {
	//data, {report_type: "excel", row: row, column: column, node: node}
	var column 	= params.column;
	var row 	= params.row;

	data = column === 0 ? (row * 1 + 1):data;

	if(typeof window["GB_REPORTS_FUNCTIONS"] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][params.request_data.report] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][params.request_data.report]["export_data_format"] == "function")
	data = window["GB_REPORTS_FUNCTIONS"][params.request_data.report]["export_data_format"](data, params);

	return data;
}
function grassblade_nss_show_report() {
	if(typeof grassblade_report_table != "undefined" && typeof grassblade_report_table.destroy == "function" ) {
		grassblade_report_table.destroy();
		grassblade_report_table = undefined;
	}
	jQuery('#grassblade_reports_output_main').show();
	jQuery('#grassblade_reports_output').html('');
	jQuery('#buttons123').html(gb_localize("Loading..."));
	jQuery('#columns-list').html('');


	var course_id = nss_report_get_param('course');
	var group_id = nss_report_get_param('group');
	var group_type = nss_report_get_param('group', 'selected-data-type');

	var report_name = nss_report_get_param('report');

	var group_avg_visibility = (typeof group_id == "number");
	//TO DO: Validate here which report needs which values

	var contents = nss_report_get_param("contents");

	var data = {
		'action'	: 'grassblade_report',
		'function' 	: 'get_report',
		'report'	: report_name,
		'course_id'	: course_id,
		'group_id'	: group_id,
		'group_type' : group_type,
		'date_range': nss_report_get_param("date_range", 'val', 'string'),
		'user'		: nss_report_get_param("users"),
		'contents'	: contents
	};

	if(typeof window["GB_REPORTS_FUNCTIONS"] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][data.report] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][data.report]["data"] == "function")
	var data = window["GB_REPORTS_FUNCTIONS"][data.report]["data"](data, this);

	var removed_column_list;
	jQuery.post(GB_REPORTS.ajaxurl, data, function(response) {
			if(typeof response == "string")
				response = JSON.parse(response);
			//console.log(response);

			if(response.error)
			{
				jQuery("#buttons123").html("");
				jQuery('#grassblade_reports_output').html(response.error);
				return;
			}
			if(typeof response.html == "string")
			{
				jQuery("#buttons123").html("");
				// jQuery("#nss_report_contents, #grassblade_reports_output_main > div").html("");
				jQuery("#grassblade_reports_output_main > div").html("");
				jQuery('#grassblade_reports_output').html(response.html);
				gb_user_report_loaded();
				return;
			}
			if(response.length == 0 || typeof response.data != "object" || response.data.length == 0)
			{
				jQuery('#grassblade_reports_output').html(gb_localize("No data."));
				jQuery("#buttons123").html("");
				return;
			}
			window.GB_REPORTS.last_request = {response: response, data: data};
			grassblade_nss_show_report_by_response_data(response, data);
	});

	return false;
}
function grassblade_nss_show_report_by_response_data(response, data) {

			var report_name = nss_report_get_param('report');
			var report_name_txt = nss_report_get_param('report', 'name');
			var group_name_txt = nss_report_get_param('group', 'name');
			var user_name_txt = nss_report_get_param('users', 'name');

			var columns_list = [];

			if(typeof window["GB_REPORTS_FUNCTIONS"] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][data.report] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][data.report]["columns"] == "function")
			var columns_list = window["GB_REPORTS_FUNCTIONS"][data.report]["columns"](columns_list, data, response, this);

			var updated_column_list = [];
			removed_column_list = [];
			var x = 0, y=0;
			if(typeof response == "object" && typeof response.data == "object" && response.error == undefined)
			jQuery.each(columns_list, function(i, column) {
				var hide = true;
				if(column.data == "sno")
					hide = false;
				else
				jQuery.each(response.data, function(j, v) {
					if(v[column.data] != undefined && v[column.data] != "")
						hide = false;
				});
				if(!hide)
					updated_column_list[x++] = column;
				else {
					jQuery.each(columns_list, function(j, jcol) {
						if(j > i) {
							if(typeof jcol.orderData == "object")
							jQuery.each(jcol.orderData, function(k, order) {
								columns_list[j].orderData[k]--;
							});
						}
					});
					if(column.data != "global_avg")
					removed_column_list[y++] = column;
				}
			});
			var report_title = report_name_txt + " - " + group_name_txt;
			if(user_name_txt.length > 0)
				report_title = report_title + " - " + user_name_txt;

			var buttons = {"excel" : 1};
			if(typeof window["GB_REPORTS_FUNCTIONS"] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][report_name] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][report_name]["buttons"] == "function")
			buttons = window["GB_REPORTS_FUNCTIONS"][report_name]["buttons"](buttons, response, this);

			var buttonOptions = [];

			if( buttons["excel"] )
			buttonOptions.push( {
						text: 'Excel',
						extend: 'excel',
						title: report_title,
						exportOptions: {
						  columns: ':visible',
						  orthogonal: 'export',
						  format: {
								body: function ( row_data, row, column, node ) {
									return grassblade_reports_export_data_format(row_data, {export_type: "excel", row: row, column: column, node: node, request_data:data, response:response});
								}
						  }
						}
					} );

			if( buttons["print"] )
			buttonOptions.push( {
						text: 'Print',
						extend: 'print',
						title: report_title,
						exportOptions: {
						  columns: ':visible',
						  orthogonal: 'export',
  						  format: {
								body: function ( row_data, row, column, node ) {
									return grassblade_reports_export_data_format(row_data, {export_type: "print", row: row, column: column, node: node, request_data:data, response:response});
								}
						  }
						}
					});
			if( buttons["pdf"] )
			buttonOptions.push( {
						text: 'PDF',
						extend: 'pdfHtml5',
						title: report_title,
						exportOptions: {
						  columns: ':visible',
						  orthogonal: 'export',
  						  format: {
								body: function ( row_data, row, column, node ) {
									return grassblade_reports_export_data_format(row_data, {export_type: "pdf", row: row, column: column, node: node, request_data:data, response:response});
								}
						  }
						}
					});

			window.grassblade_report_table = jQuery('#grassblade_reports_output').DataTable( {
				destroy: true,
				searchable: true,
				processing: true,
				pageLength: 50,
				fixedColumns: true,

				/*
				buttons: [
					jQuery.extend( true, {}, buttonCommon, {
						extend: 'copyHtml5'
					} ),
					jQuery.extend( true, {}, buttonCommon, {
						extend: 'excelHtml5'
					} ),
					jQuery.extend( true, {}, buttonCommon, {
						extend: 'pdfHtml5'
					} )
				], */
				buttons: buttonOptions,
				data: response.data,
				columns: updated_column_list,
				"initComplete": function( settings, json ) {
					//console.log("initComplete");
					//console.log(settings);
					//console.log(json);
					if(typeof json == "object" && typeof json.error == "string")
					{
						if(typeof grassblade_report_table != "undefined" && typeof grassblade_report_table.destroy == "function" ) {
							grassblade_report_table.destroy();
							grassblade_report_table = undefined;
						}
						jQuery('#grassblade_reports_output').html(json.error);
						return;
					}
					if(typeof window["GB_REPORTS_FUNCTIONS"] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][report_name] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][report_name]["initComplete"] == "function")
					window["GB_REPORTS_FUNCTIONS"][report_name]["initComplete"](settings, this);
				},
				"createdRow": function ( row, data, index ) {
						jQuery(row).data("details", data);
				},
				"columnDefs": [ {
					"targets": '_all',
					"createdCell": function (td, cellData, rowData, row, col) {
						// console.log("td");
						// console.log(td);
						// console.log("cellData");
						// console.log(cellData);
						// console.log("rowData");
						// console.log(rowData);
						// console.log("row");
						// console.log(row);
						// console.log("col");
						// console.log(col);
						// console.log("response.global_avg");
						// console.log(response.global_avg);
						// console.log(typeof response.global_avg);
						if(cellData == "")
							return;

						var params  = {
							td 			: td,
							cellData 	: cellData,
							rowData 	: rowData,
							row			: row,
							col			: col,
							report_name	: report_name,
							updated_column_list 	: updated_column_list,
							response	: response,
						};
						if(typeof window["GB_REPORTS_FUNCTIONS"] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][report_name] == "object" && typeof window["GB_REPORTS_FUNCTIONS"][report_name]["createdCell"] == "function")
							window["GB_REPORTS_FUNCTIONS"][report_name]["createdCell"](params, this);
					}
				  }, {
				  	targets: [0], // SNo
					draw: function ( data, type, row, meta ) {
						return (data * 1 + 1);
					}
				  } ]
			} );
			//console.log(grassblade_report_table);
			if(typeof grassblade_report_table == "object" && typeof grassblade_report_table.buttons == "function") {
				grassblade_report_table.on( 'order.dt search.dt', function () {
					if(typeof grassblade_report_table != "undefined")
					grassblade_report_table.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
						cell.innerHTML = i+1;
					   // grassblade_report_table.cell(cell).invalidate('dom');
					} );
				} ).draw();

				jQuery('#buttons123').html(grassblade_report_table.buttons().container());

				var columns = '<button id="column_selector_button" class="dt-button closable_button" tabindex="0" aria-controls="grassblade_reports_output" type="button" onClick="jQuery(\'ul#column_selector\').toggle();"><span>Columns</span></button><ul id="column_selector" class="closable" style="display:none">';
				jQuery.each(grassblade_report_table.columns().header(), function(i, v) {
					// debugger;
					var checked = jQuery(v).is(":visible")? 'checked' : '';
					if(v.innerHTML != "")
					columns += '<li><input type="checkbox" data-column="' + i + '" onClick="grassblade_report_table.column(' + i + ').visible(jQuery(this).is(\':checked\'));" ' + checked + ' /> ' + v.innerHTML + '</li>';
				});
				columns += "</ul>";

				var removed_columns = '';
				if(removed_column_list.length > 0)
				{
					removed_columns += '<button id="removed_columns_button" class="dt-button closable_button" tabindex="0" aria-controls="grassblade_reports_output" type="button" onClick="jQuery(\'ul#removed_columns\').toggle();"><span>Removed</span></button><ul id="removed_columns" class="closable" style="display:none">';
					jQuery.each(removed_column_list, function(i, v) {
						if(typeof v != "undefined" && typeof v.title != "undefined" && v.title != "")
						removed_columns += '<li>' + v.title + '</li>';
					});
					removed_columns += "</ul>";
				}
				jQuery('#columns-list').html(columns + removed_columns);

				/*
				jQuery.each(grassblade_report_table.columns().data(), function(i, v) {
					var show_column = false;
					jQuery.each(v, function(j, u) {
						if(u != "")
							show_column = true;
					});
					if(show_column == false) {
						jQuery("#columns-list input[data-column='" + i + "']").trigger('click');
						//grassblade_report_table.column(i).visible(0);
					}
				});
				*/
			}

			jQuery.fn.dataTable.ext.errMode = function ( settings, helpPage, message ) {
				// console.log("errMode");
				// console.log(settings);
				// console.log(helpPage);
				// console.log(message);
				if(typeof grassblade_report_table != "undefined" && typeof grassblade_report_table.destroy == "function" ) {
					grassblade_report_table.destroy();
					grassblade_report_table = undefined;
					jQuery.fn.dataTable.ext.errMode = "none";
				}
				jQuery('#grassblade_reports_output').html(message);
			};
}

function show_all_courses_toggle() {
	jQuery(".show_all_courses_toggle").toggleClass("show_all_courses");

	if(jQuery(".show_all_courses_toggle").hasClass("show_all_courses")) {
		jQuery(".course_option").removeClass("show_on").show();
	}
	else {
		jQuery(".course_option").addClass("show_on");
		grassblade_reports_add_classes();
	}
}

function gb_reports_data_to_progressbar(data) {
	if( typeof data.split == "function" && data.split("/").length == 2) {
		var completed = data.split("/")[0];
		var total = data.split("/")[1];
		var percentage = (total > 0)? (completed*100/total) : 0;
		var progress_html =  '<dd class="grassblade_progress" title="' + data + '"><div class="grassblade_progress_blue" style="width: ' + percentage + '%;"> </div></dd>';
		return progress_html;
	}
	return "";
}

function gb_reports_score_click(el, score, global_avg_score ) {
	if(!isNaN(global_avg_score) && global_avg_score != null) {
		if(parseFloat(global_avg_score) > parseFloat(score))
			jQuery(el).css('color','red');
		else
			jQuery(el).css('color','green');
	}
	jQuery(el).css('cursor', 'pointer');
	jQuery(el).on("click", function() {
		var details = jQuery(this).parent().data("details");

		if(typeof details.content_id == "number" || typeof details.content_id == "string")
		var content_id = details.content_id * 1;
		else
		{
			var classes = jQuery(this).attr("class")? jQuery(this).attr("class").split(" "):[];
			var content_id = 0;
			for(i = 0; i < classes.length; i++)
			{
				if(!isNaN(classes[i].replace("content_", "")))
					var content_id = classes[i].replace("content_", "");
			}
		}

		if(content_id > 0 && details.user_id > 0)
		get_gb_quiz_report(content_id, details.user_id);
	});
}

function gb_reports_content_click(el, cellData ) {
	jQuery(el).addClass("gb_link")
	jQuery(el).css('cursor', 'pointer');
	jQuery(el).on("click", function() {
		var details = jQuery(this).parent().data("details");
		if(typeof details.content_id == "number" || typeof details.content_id == "string")
		var content_id = details.content_id * 1;
		else
		{
			var classes = jQuery(this).attr("class")? jQuery(this).attr("class").split(" "):[];
			var content_id = 0;
			for(i = 0; i < classes.length; i++)
			{
				if(!isNaN(classes[i].replace("content_", "")))
					var content_id = classes[i].replace("content_", "");
			}
		}

		if(content_id > 0) {
			var group_id = nss_report_get_param('group');
			var group_type = nss_report_get_param('group', 'selected-data-type');
			get_gb_question_report(content_id, group_id, group_type);
		}
	});
}
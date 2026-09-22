GB_REPORTS_FUNCTIONS["video_report_gradebook"] = [];
GB_REPORTS_FUNCTIONS["video_report_gradebook"]["columns"] = function(columns, data, response, context) {
	columns  = 	[
					{ data: "sno", title: gb_localize("S.No."), orderable:false, searchable: false },
					{ data: "name", title: gb_localize("User") },
					{ data: "agent_id", title: gb_localize("Email"), visible: false },
				];
	var i = columns.length;
	var gradebook_type = typeof window.GB_REPORTS["video_report_gradebook_type"] == "string"? window.GB_REPORTS["video_report_gradebook_type"] : "heatmap";

	jQuery("#nss_report_contents :checkbox:checked").each(function(t) {
		var content_id = jQuery(this).val();
		if( gradebook_type == "heatmap" )
			gradebook_type = "percentage"; // Use percentage value for sorting and for download excel of heatmap column

		var key = content_id + "_" + gradebook_type;
		columns[i++] = { data: key, title: jQuery(this).parent().text(), className: "content_"+jQuery(this).val() + " gb-center " };
	});
	return columns;
}

GB_REPORTS_FUNCTIONS["video_report_gradebook"]["createdCell"] = function(params, context) {

	var key =  params.updated_column_list[params.col].data;

	var key_parts = key.split("_");
	var valid_keys = ["played_segments", "percentage", "attempts", "timespent_h"];
	if( typeof key_parts[1] != "string" || valid_keys.indexOf(key_parts[1]) == -1 )
		return;

	var content_id = key_parts[0] * 1;
	if ( isNaN(content_id) )
		return;

	var gradebook_type = typeof window.GB_REPORTS["video_report_gradebook_type"] == "string"? window.GB_REPORTS["video_report_gradebook_type"] : "heatmap";
	if( gradebook_type === "heatmap" ) {
		var cellValue = gb_get_heatmap(params.rowData[content_id + '_played_segments'], params.rowData[content_id + "_length"]);
	} else if( gradebook_type === "percentage" ) {
		var cellValue = gb_percentage_bar(params.rowData[content_id + "_percentage"], 20, 0, 'royalblue');
	} else {
		var cellValue = params.rowData[content_id + "_" + gradebook_type];
	}

	jQuery(params.td).html(cellValue);
}

GB_REPORTS_FUNCTIONS["video_report_gradebook"]["initComplete"] = function(settings, context) {
	var html = `
	<div id="video_report_gradebook_type_selectors">
		<div>
			<b>${gb_localize("Type")}:</b>
			<select name="video_report_gradebook_type">
				<option value="heatmap">${gb_localize("Heatmap")}</option>
				<option value="attempts">${gb_localize("Attempts")}</option>
				<option value="timespent_h">${gb_localize("Timespent")}</option>
				<option value="percentage">${gb_localize("Percentage Watched")}</option>
			</select>
		</div>
	</div>`;
	jQuery(html).insertBefore('#grassblade_reports_output_main .dataTables_length');

	if( typeof window.GB_REPORTS["video_report_gradebook_type"] == "string" )
		jQuery("#video_report_gradebook_type_selectors select").val(window.GB_REPORTS["video_report_gradebook_type"]);

	jQuery("#video_report_gradebook_type_selectors select").on('change', function() {
		//console.log( "#video_report_gradebook_type_selectors select changed: " + jQuery(this).val() );
		window.GB_REPORTS["video_report_gradebook_type"] = jQuery(this).val();
		grassblade_nss_show_report_by_response_data(window.GB_REPORTS.last_request.response, window.GB_REPORTS.last_request.data)
	});
}

GB_REPORTS_FUNCTIONS["video_report_attempts"] = [];
GB_REPORTS_FUNCTIONS["video_report_attempts"]["columns"] = function(columns, data, response, context) {
	/* used data: percentage of Heatmap column so that download and sorting is based on percentage, heatmap display is changed using createdCell below */
	columns  = 	[
					{ data: "sno", title: gb_localize("S.No."), orderable:false, searchable: false },
					{ data: "name", title: gb_localize("User") },
					{ data: "agent_id", title: gb_localize("Email"), visible: false},
					{ data: "content", title: gb_localize("Video") },
					{ data: "started", title: gb_localize("Started On")},
					{ data: "completed", title: gb_localize("Completed On")},
					{ data: "length_h", title: gb_localize("Length"), className:"gb-center"},
					{ data: "timespent_h", title: gb_localize("Timespent"), className:"gb-center"},
					{ data: "percentage", title: gb_localize("Heatmap"),},
					{ data: "percentage", title: gb_localize("Completed %"),},
				];
	return columns;
}

GB_REPORTS_FUNCTIONS["video_report_attempts"]["createdCell"] = function(params, context) {
    if(params.updated_column_list[params.col].title == gb_localize("Heatmap") ) {
		var progressBar = gb_get_heatmap(params.rowData['played_segments'], params.rowData['length']);
    	jQuery(params.td).html(progressBar);
	}

    if(params.updated_column_list[params.col].title == gb_localize("Completed %") ) {
		jQuery(params.td).html(gb_percentage_bar(params.rowData["percentage"]));
	}
}

function gb_percentage_bar(percentage, height = 20, marginTop = 0, color = '#4CAF50'){
	percentage = percentage * 1;
    var progressBar = jQuery('<dd class="grassblade_progress" title="'+percentage+'%" style="height:'+height+'px;"> <div class="grassblade_progress_blue" style="width:' + percentage + '%; height:'+height+'px;">  </dd>');
	return progressBar;
}

function gb_get_heatmap(played_segments, length) {

	var segments = played_segments.split("[,]");
	var processedSegments = [];

	jQuery.each(segments, function(index, segment) {
		var times = segment.split('[.]');
		var start = parseFloat(times[0]);
		var end = parseFloat(times[1]);
		var videoLength = parseFloat(length);

		var left = (start / videoLength) * 100;
		var width = ((end - start) / videoLength) * 100;

		processedSegments.push({
			'start': start,
			'end': end,
			'left': parseFloat(left),
			'width': parseFloat(width)
		});
	});

	var progressBar = jQuery('<div class="grassblade-heatmap" title="' + gb_localize("Not Watched") + '"></div>');
	jQuery.each(processedSegments, function(index, segment) {
	var color = segment.repeatCount > 0 ? 'rgb(0, ' + (255 - segment.repeatCount * 20) + ', 0)' : '#4CAF50';
	var div = jQuery('<div class="grassblade-heatmap-segment" title="'+ segment.start +' to ' + segment.end + ' sec"></div>').css({
		'left': segment.left + '%',
		'width': segment.width + '%'
	});
	progressBar.append(div);
	});
	return progressBar;
}

GB_REPORTS_FUNCTIONS["progress_snapshot"] = [];
GB_REPORTS_FUNCTIONS["progress_snapshot"]["columns"] = function (columns, data, response, context) {
	//console.log(columns, data, context);
	columns  = 	[
					{ data: "sno", title: "S.No.", orderable:false, searchable: false },
					{ data: "name", title: "User" },
					{ data: "user_email", title: "Email", visible: false },
				];

	if(typeof response.lessons == "object" && typeof response.lesson_order == "object") {
		var i = 3;
		jQuery.each(response.lesson_order, function(order, lesson_id) {
			var lesson_title = response.lessons[lesson_id];
			columns[i++] = { data: lesson_id, title: lesson_title };
		});
	}
	return columns;
}

GB_REPORTS_FUNCTIONS["progress_snapshot"]["createdCell"] = function(params, context) {
	var progress_html = gb_reports_data_to_progressbar(params.cellData);
	if(progress_html.length > 0)
		jQuery(params.td).html(progress_html);
	else
		jQuery(params.td).html('<dd style="position:relative;">' + params.cellData + "</dd>");

	if (params.col > 2) {
		var post_id = params.updated_column_list[params.col].data;
		jQuery(params.td).find('dd')
			.append('<i class="info-icon gb_reports_progress_snapshot" ' +
				'data-user="' + params.rowData.user_email + '" ' +
				'data-gb_lesson_id="' + post_id + '" ' +
				'data-course_id="' + jQuery("#nss_report_course").val() + '" ' +
				'data-cellData="' + params.cellData + '"></i>'); // Add the info icon with data attributes

	}
}

jQuery(document).on("click", ".gb_reports_progress_snapshot", function (e) {

	// get all the data from the element send a ajax request to get the section structure and progress of the user.
	var user_email = jQuery(this).data("user");
	var gb_lesson_id = jQuery(this).data("gb_lesson_id");
	var course_id = jQuery(this).data("course_id");

	var data = {
		action: "grassblade_report",
		function: "get_report",
		report: "progress_snapshot_details",
		user: "null:" + user_email,
		gb_lesson_id: gb_lesson_id,
		course_id: course_id,
		group_id: nss_report_get_param('group'),
		group_type: nss_report_get_param('group', 'selected-data-type')
	};

	// Show the loader
	jQuery(e.target).addClass("loader-icon");

	jQuery.post(GB_REPORTS.ajaxurl, data, function (response) {

		// Hide the loader
		jQuery(e.target).removeClass("loader-icon");

		var r = JSON.parse(response);
		if (typeof r.error == "string" && r.error.length > 0) {
			alert(r.error);
			return;
		}
		var data = r.data;
		if (data.length == 0) {
			alert("No data found.");
			return;
		}
		var steps_html = "";
		if (Object.keys(data.sub_steps).length > 0) {
			jQuery.each(data.sub_steps, function (index, step) {
				steps_html += gb_get_step(step);
			});
		}
		else
			steps_html += 	`<span>No sub steps found.</span>`;

		var html = `<div style="margin-bottom: 15px;">
						<div class="gb-section-meta">
							<span class="section_status">Status: ${data.status_msg}</span>
							<span class="section_steps">Completed Steps: ${data.completed_steps}/${data.total_steps}</span>
						</div>
						<dd class="grassblade_progress" title="${data.completed_steps}/${data.total_steps}"><div class="grassblade_progress_blue" style="width:${parseInt(data.completed_steps / data.total_steps * 100)}%;"> </div></dd>
					</div>
					<span>Steps:</span>
					<div class="section_content">
						${steps_html}
					</div>`;

		show_grassblade_reports_popup( html, data.name );
	});
});
function gb_get_step(step) {
	var has_subs = (typeof step.sub_steps != "undefined" && Object.keys(step.sub_steps).length > 0) ? "has-subs" : "";
	var has_attempts = (typeof step.attempts != "undefined" && Object.keys(step.attempts).length > 0) ? "gb-has-attempts" : "";
	var attempts_toggle = has_attempts.length > 0 ? 'gb-attempts-toggle' : "";

	var icons = "";
	icons += step.type == 'quiz' ? `<img src="${GB_REPORTS.images.quiz}" />` : '';
	icons += step.has_xapi ? `<img src="${GB_REPORTS.images.gbicon}" />` : '';
	const completed_check_icon = step.status == 'completed' ? `<img src="${GB_REPORTS.images.check}" />` : '';

	var attempts_html = "";
	if (Object.keys(step.attempts).length > 0) {
		attempts_html += `<div class="gb-item-attempts" style="display:none;">`;
		attempts_html += `<div class="gb-item-attempts-meta"><span>Attempts: ${step.attempts_count}</span><span>Score: ${step.percentage}</span></div>`;
		jQuery.each(step.attempts, function (index, attempt) {
			attempts_html += `<div class="gb-item-attempt ${attempt.status.toLowerCase()}">
                                <div>${attempt.completed}</div>
                                <div>${attempt.timespent}</div>
                                <div>${attempt.status_msg}</div>
                                <div>${attempt.percentage}</div>
                            </div>`;
		});
		attempts_html += `</div>`;
	}
	var sub_steps_html = "";
	if (has_subs.length > 0) {
		sub_steps_html += `<div class="gb-step-subs">`;
		jQuery.each(step.sub_steps, function (index, sub_step) {
			sub_steps_html += gb_get_step(sub_step);
		});
		sub_steps_html += `</div>`;
	}

	html = `<div class="gb-step-container gb-step-no-${step.count} ${step.type}">
				<div class="gb-step ${has_subs} ${step.status} ${has_attempts} ${attempts_toggle}">
					<div class="gb-step-data">
						<div class="gb-step-title">
							<div class="gb-step-count">${step.count}.</div> ${step.name}
						</div>
						<div class="gb-step-actions">${icons}</div>
						<div class="gb-step-status">${step.status_msg} ${completed_check_icon}</div>
					</div>
				</div>
				${attempts_html}
				${sub_steps_html}
			</div>`;

	return html;
}

jQuery(document).on("click", ".gb-attempts-toggle", function (e) {
	jQuery(e.target).closest(".gb-has-attempts").next(".gb-item-attempts").slideToggle(300);
});
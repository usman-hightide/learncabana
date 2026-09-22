jQuery('#gb_report_table').ready(function() {
	gb_user_report_loaded();
});
function gb_user_report_loaded() {

	if (jQuery('#gb_report_table').length) {

		var Paginate = false;
		if(parseInt(total_xapi_contents) > 50){
			Paginate = true;
		}
	    var table = jQuery('#gb_report_table').DataTable({
			responsive: true,
			bFilter: true,
			bInfo: false,
			pageLength: 50,
			bAutoWidth: false,
			bPaginate: Paginate,
			bLengthChange: Paginate,
			language: gb_profile.datatables_language
	    });

	    jQuery('#gb_report_table').closest("input").addClass('search-input');

		var default_filter = jQuery("#gb_result_filter").data("default");
		if(default_filter != "all") {
			default_filter = (typeof gb_profile[default_filter] == "string")? gb_profile[default_filter]:gb_profile.attempted;
			jQuery("#gb_result_filter").val(default_filter);
			setTimeout(function() {
				jQuery("#gb_result_filter").trigger("change");
			}, 10);
		}
	}

	jQuery('#gb_result_filter').on('change', function() {

		var table = jQuery('#gb_report_table').DataTable();

		var filter_text = jQuery("#gb_result_filter option:selected").text();
		var filter_val = jQuery("#gb_result_filter option:selected").val();
		if (filter_val == 'all')
			filter_val = '';
		if (filter_val == gb_profile.attempted)
			filter_val = '\s?[^-|^\u2011|^\u2012|^\u2013|^\u2014]\s?';
		table
            .column(3)
            .search( filter_val, true, true )
            .draw();
	});
}
function gb_get_score(content_id,user_id,expandall) {

	if (typeof(expandall)==='undefined')
		expandall = false;

	var key = 'attempts_'+content_id+'_'+user_id;

	var attempts = JSON.parse(gb_content_attempts[key]);

	if (attempts != '') {
		if (jQuery("#score_row"+content_id).length == 0) {

			var scoreTD = '';
			var headTD =   '<th>'+gb_profile.date+'</th>\
							<th>'+gb_profile.score+'</th>\
							<th>'+gb_profile.status+'</th>\
							<th>'+gb_profile.timespent+'</th>';

			if (gb_content_quiz_enable[content_id]) {
				headTD += '<th>'+gb_profile.quiz_report+'</th>';
			}

			jQuery.each(attempts, function (index, value) {
				var tr_class = (index % 2 == 1)? 'tr_odd':'tr_even';

				var content_td =   '<td scope="row" data-label="'+gb_profile.date+'">'+value.timestamp+'</td>\
									<td data-label="'+gb_profile.score+'">'+value.percentage+'</td>\
									<td data-label="'+gb_profile.status+'">'+value.status+'</td>\
									<td data-label="'+gb_profile.timespent+'">'+value.timespent+'</td>';

				var statement = JSON.parse(value.statement);
				var registration = statement.context.registration;
				if (gb_content_quiz_enable[content_id]) {
					content_td += '<td data-label="'+gb_profile.quiz_report+'">\
									<a onclick="return get_gb_quiz_report('+content_id+','+user_id+',\''+registration+'\');">\
										<img class="gb-icon-img" src="'+gb_profile.plugin_dir_url+'/img/stats.png" width="20px">\
									</a>\
								  </td>';
				}
				scoreTD += '<tr class='+tr_class+'>'+content_td+'</tr>';
			});

			var HTML =	'<tr id="score_row'+content_id+'" style="display: none;">\
							<td colspan="6">\
								<div class="grassblade_table" id="attempts'+content_id+'" style="display: none;">\
									<table id="sub_report_tbl" style="margin-bottom: 0px !important;">\
										<thead>\
											<tr>\
												'+headTD+'\
											</tr>\
										</thead>\
										<tbody>\
											'+scoreTD+'\
										</tbody>\
									</table>\
								</div>\
							</td>\
						</tr>';
		}

		if(jQuery(".row-details-open"+content_id).length > 0 && expandall == false){
			jQuery("#attempts"+content_id).slideUp(1000,'swing', function(){jQuery("#score_row"+content_id).hide();});
			jQuery('#score_row'+content_id).addClass("row-details-close"+content_id).removeClass("row-details-open"+content_id);
		} else {
			jQuery("#gb_row_"+content_id).after(HTML);
			jQuery('#score_row'+content_id).addClass("row-details-open"+content_id).removeClass("row-details-close"+content_id);
            jQuery("#score_row"+content_id).show();
            jQuery("#attempts"+content_id).slideDown(1000);
		}
	}
}

function gb_expand_attempts(user_id){
	if (jQuery('#gb_expand_btn.gb-collapsed').length > 0) {
		jQuery('#gb_expand_btn').addClass("gb-expanded").removeClass('gb-collapsed');
		jQuery.each(gb_content_quiz_enable,function (index, value) {
			gb_get_score(index,user_id,true);
		});
	} else {
		jQuery('#gb_expand_btn').addClass("gb-collapsed").removeClass('gb-expanded');
		jQuery.each(gb_content_quiz_enable,function (index, value) {
			jQuery("#attempts"+index).slideUp(1000,'swing', function(){jQuery("#score_row"+index).hide();});
			jQuery('#score_row'+index).addClass("row-details-close"+index).removeClass("row-details-open"+index);
		});
	}
}

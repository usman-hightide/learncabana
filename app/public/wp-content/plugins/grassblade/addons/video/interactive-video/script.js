
jQuery(document).ready(function() {
	gb_question_list_all();

	if( typeof ADL != "object" || typeof ADL.GrassBladeVideo.myPlayer != "object")
		return false;

	var player = ADL.GrassBladeVideo.myPlayer;
	player.on("timeupdate", function () {
		gb_question_add_video_markers();
	});

	player.on("timeupdate", function () {
		currentTime = human_timestamp(ADL.GrassBladeVideo.myPlayer.currentTime());
		jQuery(".gb_selected .gb_timestamp").val(currentTime).addClass('gb_changed').trigger('change');
		setTimeout(function() { jQuery(".gb_selected .gb_timestamp").removeClass('gb_changed'); }, 3000);
	});

	jQuery("#gb_questions_form").on("click focus keyup", function(e) {
		gb_question_highlight_selected_question(e.target);
	});
});
function gb_question_highlight_selected_question(el) {
	var closest = jQuery(el).closest(".gb_question_form");
	if( closest.hasClass("gb_selected") || closest.length == 0 )
	return;

	jQuery(".gb_question_form").removeClass("gb_selected");
	closest.addClass("gb_selected");
}
function add_question_option(el, question_type, value = "", checked = false ) {
	var gb_question_settings_el = jQuery(el).closest(".gb_question_settings");
    var option_html = jQuery("#gb_tmpl_" + question_type + "_option").html();
	var checked = checked ? ' checked="checked" ':'';
	var count = jQuery(gb_question_settings_el).find(".gb_question_options li").length? jQuery(gb_question_settings_el).find(".gb_question_options li").length:0;
	var data = {
		"value": value,
		"checked": checked,
		"count": count
	};
	//console.log(option_html, data);
	option_html = gb_replace_tags(option_html, data);
    jQuery(gb_question_settings_el).find(".gb_question_options").append(option_html);
}

function gb_remove_option(el) {
	var gb_question_settings_el = jQuery(el).closest(".gb_question_settings");
	jQuery(el).closest("li").remove();
	jQuery(gb_question_settings_el).find("div.option .gb_correct_choice").each(function(i, choice) {
		jQuery(choice).val(i);
	});
}

function human_timestamp(timestamp){
	return new Date(timestamp * 1000).toISOString().substr(11, 8)
}
function gb_question_validate(el) {
	if(el.checkValidity())
		jQuery(el).parent().find('.gb_error_msg').hide();
	else
		jQuery(el).parent().find('.gb_error_msg').show();
}
function gb_save_question(el) {
	var form = jQuery(el).closest('form.gb_question_builder');
	form.addClass("save_clicked");

	var ok = true;
	form.find("input").each(function(i, el) {
		if( ! el.checkValidity() )
		{
			jQuery(el).trigger("keyup");
			ok = false;
		}
	});
	if( !ok )
	return;

	jQuery(el).closest(".gb_question_form").addClass("gb_saving");
	form.find(".message").html("Saving...");
	var question_data = form.serialize();
	jQuery.ajax({
		url: ajaxurl + '?action=save_question',
		type: "POST",
		dataType: 'json',
		data: question_data,
		success: function (response) {
			if(response.status) {
				GB_QUESTION_DATA.questions = response.questions;
				jQuery(el).closest(".gb_question_form").removeClass("gb_saving").addClass("gb_saved");
				form.find(".message").html("Saved");
				setTimeout(function() { gb_question_close() }, 1000);
			} else {
				jQuery(el).closest(".gb_question_form").removeClass("gb_saving").addClass("gb_failed");
				form.find(".message").html("Save Failed");
			}
		}, error: function (data) {
			jQuery(el).closest(".gb_question_form").removeClass("gb_saving").addClass("gb_failed");
			form.find(".message").html("Error saving question");
		}
	});

	return false;
}

function gb_delete_question(el) {
	var ok = confirm("Are you sure you want to delete this question?");
	if( !ok )
	return;

	var form = jQuery(el).closest('form.gb_question_builder');

	var question_data = form.serialize();
	jQuery.ajax({
		url: ajaxurl + '?action=delete_question',
		type: "POST",
		dataType: 'json',
		data: question_data,
		success: function (response) {
			if(response.status) {
				GB_QUESTION_DATA.questions = response.questions;
				gb_question_list_all();
			} else {

			}
			jQuery(".success_msg").css("display", "block");
		}, error: function (data) {
			jQuery(".error_msg").css("display", "block");
		}
	});

	return false;
}

function gb_question_add_new(el) {
	var template = jQuery("#gb_tmpl_question_form").html();
	template = gb_replace_tags(template, {"ID": "New"});
	template = template.replaceAll(/{{([A-Za-z_.0-9:]+)}}/g, "");
	template = `<div id="gb_question_new" class="gb_grid_1_col gb_question_form">${template}</div>`;
	jQuery(template).insertAfter(jQuery(el).parent());
}
function gb_question_type_changed(el) {
    count = 0;
    var question_form = jQuery(el).closest("form");
	var type = jQuery(el).val();
	var tmpl_id = "#gb_tmpl_" + type + "_question";
	var template = jQuery(tmpl_id)? jQuery(tmpl_id).html():"";

	if(jQuery(question_form).closest(".gb_question_form").length && jQuery(question_form).closest(".gb_question_form").data("question_id")) {
		var question_id = jQuery(question_form).closest(".gb_question_form").data("question_id");
		var question = (typeof GB_QUESTION_DATA.questions == "object" && typeof GB_QUESTION_DATA.questions[question_id] == "object")? GB_QUESTION_DATA.questions[question_id]:{};
		template = gb_replace_tags(template, question);
	}

	jQuery(question_form).find(".gb_question").hide();
	jQuery(question_form).find(".gb_question_settings").html(template);
	jQuery(question_form).find(".gb_question").slideDown(1000);
	gb_question_highlight_selected_question(el);
}
function gb_question_list_all() {

	if(typeof GB_QUESTION_DATA != "object" || typeof GB_QUESTION_DATA.questions != "object")
	return;

    var html = jQuery("#gb_tmpl_question_list_item_new").html();
	jQuery.each(GB_QUESTION_DATA.questions, function(question_id, question) {
		var template = jQuery("#gb_tmpl_question_list_item").html();
        question_id = question.ID;
		template = template.replaceAll("{{question_id}}", question.ID);
		//console.log(question);

		template = gb_replace_tags(template, question);
		html = html + template;
	});
	jQuery("#gb_question_list").html(html);
	setTimeout(function() {
		gb_question_add_video_markers();
	}, 1000);
}
function gb_question_add_video_markers() {
	if( typeof ADL.GrassBladeVideo.myPlayer  != "object" || typeof ADL.GrassBladeVideo.myPlayer.duration != "function")
	return;

	var markerOptions = {
		markers: [],
		markerTip:{
			display: true,
			text: function(marker) {
			   return "Question: " + marker.text;
			},
			time: function(marker) {
				return marker.time;
			}
		},
		onMarkerClick: function(marker) { console.log("onMarkerClick", marker); },
		onMarkerReached: function(marker) {console.log("onMarkerReached", marker);},
	};
	if(typeof ADL.GrassBladeVideo.myPlayer.markers == "function")
	ADL.GrassBladeVideo.myPlayer.markers(markerOptions);

	ADL.GrassBladeVideo.myPlayer.markers.removeAll();

	//jQuery(".gb_video .vjs-slider #gb_markers").remove();
	//jQuery(".gb_video .vjs-slider").append("<div id='gb_markers'></div>");

	jQuery.each(GB_QUESTION_DATA.questions, function(question_id, question) {
		var timestamp = gb_question_to_seconds( question.answer_settings.timestamp );
		ADL.GrassBladeVideo.myPlayer.markers.add([
			{time: timestamp, text: question_id, overlayText: question_id},
		 ]);

//		var time_pc = (timestamp * 100/length)
//		var html = "<div class='gb_marker' style='left:" + time_pc + "%;'  onClick='gb_question_edit(" + question_id + ")'><div></div><span>Q" + question_id + "</span></div>";
//		jQuery(".gb_video .vjs-slider #gb_markers").append(html);
	});
}
function gb_replace_tags(template, data, prefix = "") {

	if(typeof data != "object" || data == null)
	return template;

	jQuery.each(data, function(tag, value) {
		if(value == null || typeof value == "undefined")
		value = "";

		if(typeof value != "object") {
			template = template.replaceAll("{{" + prefix + tag + "}}", value);
			template = template.replaceAll("{{selected:" + tag + "." + value + "}}", ' selected="selected" ');
			//console.log("{{checked:" + prefix + value + "}}");
			template = template.replaceAll("{{checked:" + tag + "." + value + "}}", ' checked="checked" ');
		}
		else
		template = gb_replace_tags(template, value, tag + ".");
	});

	return template;
}
function gb_question_edit(el) {

	if(typeof el ==  "number")
		var question_el = jQuery('[data-question_id="' + el + '"]');
	else
		var question_el = jQuery(el).closest("div");
	var question_id = jQuery(question_el).data("question_id");

	var question = (typeof GB_QUESTION_DATA.questions == "object" && typeof GB_QUESTION_DATA.questions[question_id] == "object")? GB_QUESTION_DATA.questions[question_id]:{};
	var template = jQuery("#gb_tmpl_question_form").html();
	template = gb_replace_tags(template, question);
	template = template.replaceAll(/{{([A-Za-z_.0-9:]+)}}/g, "");

	jQuery(question_el).html(template).addClass("gb_grid_1_col").addClass("gb_question_form").find('[name="question_type"]').trigger("change");
	var gb_question_settings_el = jQuery(question_el).find(".gb_question_settings");
	if( ["single_choice", "multi_choice"].indexOf(question.question_type) >= 0 && typeof question.answer_settings.options == "object" )
	for (let index = 0; index < question.answer_settings.options.length; index++) {
		const option_value = question.answer_settings.options[index];
		if( typeof question.answer_settings.correct_choices == "undefined" || typeof question.answer_settings.correct_choices == null || question.answer_settings.correct_choices === "")
		var checked = false;
		else if( typeof question.answer_settings.correct_choices == "object" )
		var checked = (question.answer_settings.correct_choices.indexOf( index ) >= 0 || question.answer_settings.correct_choices.indexOf( "" + index ) >= 0);
		else
		var checked = (index == question.answer_settings.correct_choices);

		add_question_option(gb_question_settings_el, question.question_type, option_value, checked);
	}
	jQuery(question_el).find(".gb_button_delete").show();
}
function gb_question_close(el) {
	jQuery(".gb_question_form").slideUp(1000);
	setTimeout(function() {
		gb_question_list_all();
	}, 1000);
}
function gb_question_to_seconds( timestamp ) {
    if(typeof timestamp == "number")
    return timestamp;

    return (new Date("2000-01-01 " + timestamp)/1000 - new Date("2000-01-01 00:00:00")/1000);
}
function gb_question_goto(el_or_time) {
//	debugger;
	if( typeof ADL.GrassBladeVideo.myPlayer  != "object" || typeof ADL.GrassBladeVideo.myPlayer.currentTime != "function")
	return;

	if(typeof el_or_time == "object")
	var timestamp =	gb_question_to_seconds( el_or_time.innerHTML.trim() );
	else
	if(typeof el_or_time == "string")
	var timestamp = gb_question_to_seconds( el_or_time );
	else
	if(typeof el_or_time == "number")
	var timestamp = el_or_time;

	if(typeof timestamp  != "number")
	return;
	ADL.GrassBladeVideo.myPlayer.muted(true);
	ADL.GrassBladeVideo.myPlayer.currentTime( timestamp );
	ADL.GrassBladeVideo.myPlayer.play();
	ADL.GrassBladeVideo.myPlayer.pause();
}
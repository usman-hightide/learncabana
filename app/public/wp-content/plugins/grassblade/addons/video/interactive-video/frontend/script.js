jQuery(document).ready( function () {
	gb_init_questions();
});

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

function gb_show_question(ID) {

	if(typeof GB_QUESTION_DATA != "object" || typeof GB_QUESTION_DATA.questions != "object" || GB_QUESTION_DATA.questions.length === 0)
		return;

	jQuery("body").addClass("gb_showing_question");
	jQuery(".question_container_overlay").removeClass("gb_hidden");

	gb_add_pending_question( ID );

	ADL.GrassBladeVideo.myPlayer.pause();

	question     = GB_QUESTION_DATA.questions[ID];
	var template = jQuery("#gb_tmpl_show_question").html();
	question_id  = question.ID;

	//console.log(question)

	data = {
		"question_id" : question.ID,
		"question_title" : question.question
	}
	template = gb_replace_tags(template, data);
	jQuery(".gb_question_container").html(template);

	if(question.question_type == "true_false") {
			//var template = jQuery("#gb_tmpl_show_question_true_false_options").html();
			add_options_to_questions(question.question_type);
			//jQuery(".question_options").append(template);
	} else {
			var count = 0;
			jQuery.each(question.answer_settings.options, function(index, option) {
				add_options_to_questions(question.question_type, option, count)
				count++;
			})
	}

	var fullscreenElement = document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement || document.msFullscreenElement;

	if(document.fullscreen || fullscreenElement)
	ADL.GrassBladeVideo.myPlayer.exitFullscreen()
}

function add_options_to_questions(question_type="", option="", count=""){
	var option_template = (question.question_type == "true_false")? jQuery("#gb_tmpl_show_question_true_false_options").html():jQuery("#gb_tmpl_show_question_option").html();
	input_type = (["single_choice", "true_false"].indexOf(question.question_type) >= 0) ? "radio" : "checkbox";
	data = {
		"question_id" : question.ID,
		"option_value" : option,
		"input_type" : input_type,
		"option_index" : count,
	}
	option_template = gb_replace_tags(option_template, data);
	jQuery(".question_options").append(option_template);
}

function gb_question_to_seconds( timestamp ) {
	var time_parts = timestamp.split(":").reverse();
	var seconds = isNaN( parseInt(time_parts[0]) )? 0 : parseInt(time_parts[0]);
	var minutes = isNaN( parseInt(time_parts[1]) )? 0 : parseInt(time_parts[1]);
	var hours = isNaN( parseInt(time_parts[2]) )? 0 : parseInt(time_parts[2]);
	var seconds = seconds + minutes * 60 + hours * 3600 ;
	return seconds;
}

function gb_init_questions() {
	var player = ADL.GrassBladeVideo.myPlayer;
	window.gb_question_completed = {};
	window.gb_question_timestamps = {};
	window.gb_pending_completion = [];
	jQuery.each(GB_QUESTION_DATA.questions, function (question_id, question) {
		var timestamp = gb_question_to_seconds(GB_QUESTION_DATA.questions[question_id].answer_settings.timestamp);
		if(typeof gb_question_timestamps[timestamp] != "object")
		gb_question_timestamps[timestamp] = [];

		gb_question_timestamps[timestamp].push( question_id * 1 );
	});

	player.on("timeupdate", function () {
		gb_check_and_show_question();
	});
	player.on("play", function () {
		gb_check_and_show_question();
	});
}
function gb_check_and_show_question() {
	if( jQuery("body").hasClass("gb_showing_question") )
	{
		if(! ADL.GrassBladeVideo.myPlayer.paused())
		ADL.GrassBladeVideo.myPlayer.pause();

		return;
	}
	currentTime = parseInt(ADL.GrassBladeVideo.myPlayer.currentTime());
	if (typeof gb_question_timestamps[currentTime] == "object") {
		jQuery.each(gb_question_timestamps[currentTime], function(i, question_id) {
			if( !gb_question_completed[question_id] )
			gb_add_pending_question(question_id);
		});
		//&& !gb_question_completed[gb_question_timestamps[currentTime]]

	}

	gb_show_pending_questions();
}
function gb_show_pending_questions() {
	if( window.gb_pending_completion.length )
	{
		var question_id = window.gb_pending_completion[0] * 1;
		gb_show_question(question_id);
	}
}
function gb_add_pending_question(question_id) {
	var question_id = question_id * 1;
	if(window.gb_pending_completion.indexOf(question_id) == -1 )
	window.gb_pending_completion.push(question_id);
}
function gb_submit_response() {

	selected = jQuery(".choice_input:checked");
	if(selected.length !== 0) {
		var user_choices = []
		jQuery.each(selected, function(index, value){
			var super_value = jQuery(value).val();
			user_choices.push(super_value);
		})

		var question_id = jQuery(".question_id").val() * 1;
		var question    = GB_QUESTION_DATA.questions[question_id];

		gb_question_completed[question_id] = 1;
		var pending_index = window.gb_pending_completion.indexOf(question_id);
		if(pending_index > -1)
		window.gb_pending_completion.splice(pending_index, 1); //Remove question_id from window.gb_pending_completion

		//console.log(selected_choices);
		if(window.gb_pending_completion.length)
		{
			gb_show_pending_questions();
		}
		else {
			jQuery("body").removeClass("gb_showing_question");
			jQuery(".question_container_overlay").addClass("gb_hidden");
			ADL.GrassBladeVideo.myPlayer.play();
		}

		send_answered_statement(question, user_choices);
	} else {
		jQuery("#gb_error_message").show();
		return false;
	}
}
function gb_question_option_key(option) {
	option = (typeof option == "string")? option:JSON.stringify(option);
	return option.toLowerCase().replace(/[^A-Za-z0-9._-]/g,"-");
}
function send_answered_statement(question, user_choices) {
	if( typeof question != "object" || typeof question.ID == "undefined" || isNaN( parseInt(question.ID) ) )
	return false;

	stmt = getInteractionsBaseStatement(question);
	//console.log(stmt)
	if( stmt == false )
	return false;

	switch(question.question_type) {
		case "single_choice":
		case "multi_choice":
			stmt.object.definition.interactionType = "choice";

			var correct_choices = (typeof question.answer_settings.correct_choices == "object") ? question.answer_settings.correct_choices:[question.answer_settings.correct_choices];
			user_choices = (typeof user_choices != "object")? [user_choices]:user_choices;


			if(correct_choices[0] !== "") {
				var user_incorrect_choices = user_choices.filter((c) => correct_choices.indexOf(c) == -1 );
				var user_unselected_correct_choices = correct_choices.filter((c) => user_choices.indexOf(c) == -1 );
				var success = ( user_incorrect_choices.length == 0 && user_unselected_correct_choices == 0);
				stmt.result.success = success;
			}

			correct_choices.forEach(function(choice, index) {
				var choice_option = question.answer_settings.options[choice];
				correct_choices[index] = ( typeof choice_option == "undefined" && ! choice_option )? "":gb_question_option_key( choice_option );
			});
			var correctResponsesPattern = [ correct_choices.join("[,]") ];
			stmt.object.definition.correctResponsesPattern = correctResponsesPattern;

			var choices = [];
			question.answer_settings.options.forEach(function(option, index) {
				choices.push({
						"id": gb_question_option_key(option),
						"description": {
							"und": option
						}
					});
				//console.log(option, index);
			});
			stmt.object.definition.choices = choices;
			var responses = [];
			user_choices.forEach(function(option_index, index) {
				var option = question.answer_settings.options[option_index];
				responses.push( gb_question_option_key(option) );
			});
			stmt.result.response = responses.join("[,]");

			/*
			"correctResponsesPattern": [
				"choice_6ky4rrSCA6d"
			],
			"choices": [
				{
					"id": "choice_6ky4rrSCA6d",
					"description": {
						"und": "True"
					}
				},
				{
					"id": "choice_5y3QpL8xfRs",
					"description": {
						"und": "False"
					}
				}
			]
			*/
			break;
		case "true_false":
			stmt.object.definition.interactionType = "true-false";
			stmt.object.definition.correctResponsesPattern = ( typeof question.answer_settings.correct_choices == "string" )? [ (( question.answer_settings.correct_choices == 1 ) + '' ) ]:[""];
			var user_choice = (typeof user_choices[0] == "string")? user_choices[0]:"";
			user_choice = (user_choice == 1) + '';
			stmt.result.response = user_choice;

			if(question.answer_settings.correct_choices !== "") {
				var success = (stmt.object.definition.correctResponsesPattern[0] == user_choice);
				stmt.result.success = success;
			}

			/*
			"interactionType": "true-false",
			"correctResponsesPattern": [
				"true"
			]
			"result": {
				"success": false,
				"response": "false"
			}
			*/

			break;
	}
	//console.log(stmt);
	//console.log(user_choices);

	ADL.XAPIWrapper.sendStatement(stmt);
}

function getInteractionsBaseStatement (question) {

	if( typeof ADL != "object" || typeof ADL.XAPIWrapper != "object" || typeof ADL.XAPIWrapper.lrs != "object" || typeof ADL.XAPIWrapper.lrs.actor != "string"  || typeof ADL.XAPIWrapper.lrs.activity_id != "string" || typeof ADL.XAPIWrapper.lrs.registration != "string" || typeof ADL.XAPIWrapper.lrs.grassblade_version  != "string")
	return false;

	var activity_id = decodeURIComponent( ADL.XAPIWrapper.lrs.activity_id ).replace("&amp;", "&");

	return {
		actor: ADL.XAPIWrapper.lrs.actor,
		verb: {
			id : "http://adlnet.gov/expapi/verbs/answered",
			display : {
				"de-DE": "beantwortete",
				"en-US": "answered",
				"fr-FR": "a répondu",
				"es-ES": "contestó"
			},
		},
		object: {
			objectType: "Activity",
			id: activity_id + "/" + question.ID,
			definition: {
				type: "http://adlnet.gov/expapi/activities/cmi.interaction",
				name: {
					"en-US" : question.question,
				},
				// interactionType : "choice",
				// correctResponsesPattern : JSON.stringify(question.answer_settings.correct_choices)
			},
		},
		context: {
			contextActivities: {
				parent: [
					{
						id: activity_id,
						objectType: "Activity",
					/*    definition: {
							type: "http://adlnet.gov/expapi/activities/lesson"
						}*/
					}
				],
				grouping: [
					{
						id: activity_id,
						objectType: "Activity",
					/*    definition: {
							type: "http://adlnet.gov/expapi/activities/attempt"
						}*/
					}
				],
				category: [
					{
						id: "tool://grassblade/xapi/#" + ADL.XAPIWrapper.lrs.grassblade_version
					}
				]
			},
			registration: ADL.XAPIWrapper.lrs.registration,
		},
		result: {

		},
	};
}

if( typeof String.prototype.replaceAll != "function" )
String.prototype.replaceAll = function(search, replacement) {
	var target = this;
	return target.split(search).join(replacement);
};

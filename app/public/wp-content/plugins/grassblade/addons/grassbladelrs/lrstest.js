
function grassblade_lrstest_anim() {
	var connecting = setInterval(function() {
		if(jQuery(".lrstest-diagram.connecting").length == 0)
		{
			clearInterval(connecting);
			return;
		}

		if(jQuery(".lrstest-diagram.connecting .dashicons.highlighted").length > 0) {
			var count = jQuery(".lrstest-diagram.connecting .dashicons").length;
			highlighted = jQuery(".lrstest-diagram.connecting .dashicons.highlighted:first").data("no");
			jQuery(".lrstest-diagram.connecting .dashicons.highlighted").removeClass("highlighted");
			var highlight = highlighted % count + 1;
			jQuery(".lrstest-diagram.connecting .dashicons[data-no^=" + highlight + "]").addClass("highlighted");
		}
		else
		jQuery(".lrstest-diagram.connecting .dashicons:first").addClass("highlighted");
	}, 500);
}
function grassblade_lrstest_status(id, status, message) {
	switch(status) {
		case "testing":
		message = message? message:"Testing...";

		jQuery("#lrstest" + id + " .lrstest-diagram").addClass("connecting");
		jQuery("#lrstest" + id + " .lrstest-diagram").removeClass("connected");
		jQuery("#lrstest" + id + " .lrstest-diagram").removeClass("failed");
		jQuery("#lrstest" + id + " .status").text(message);
		grassblade_lrstest_anim();

		break;

		case "connected":
		message = message? message:"Connected";

		jQuery("#lrstest" + id + " .lrstest-diagram").removeClass("connecting");
		jQuery("#lrstest" + id + " .lrstest-diagram").addClass("connected");
		jQuery("#lrstest" + id + " .status").text(message);
		break;

		case "failed":
		message = message? message:"Test Failed";

		jQuery("#lrstest" + id + " .lrstest-diagram").removeClass("connecting");
		jQuery("#lrstest" + id + " .lrstest-diagram").addClass("failed");
		jQuery("#lrstest" + id + " .status").text(message);

		break;

		case "message":
		if( message )
		jQuery("#lrstest" + id + " .status").text(message);
		break;
	}
}

function grassblade_lrstest_start() {
	jQuery(".grassblade_lrstest").slideDown(1500);
	setTimeout(function() {
		grassblade_lrstest3_start();
		grassblade_lrstest1_start();
		grassblade_lrstest2_start();
		grassblade_lrstest4_start();
	}, 3000);
}
function grassblade_lrstest_subtests_start(testno) {
	grassblade_lrstest_status(testno, "testing");

	jQuery("#lrstest" + testno + " [data-test-no]").each(function(i, test) {
		var subtest_no = jQuery(test).data("test-no");
		setTimeout(function() {
			grassblade_reset_test(test);
			grassblade_start_test_message(test);
			var test_name = jQuery(test).data("test-name");
			if(typeof window["grassblade_test_" + test_name] == "function")
	            window["grassblade_test_" + test_name](test);
        }, subtest_no * 500);
	});
}
function grassblade_lrstest1_start() {
	var testno = 1;
	grassblade_lrstest_subtests_start(testno);
}
function grassblade_lrstest4_start() {
	var testno = 4;
	grassblade_lrstest_subtests_start(testno);
}
function grassblade_reset_test(context) {
	jQuery(context).removeClass("failed").removeClass("passed");
	jQuery(context).children(".dashicons").removeClass("dashicons-yes").removeClass("dashicons-no").addClass("dashicons-minus");

	if( jQuery(context).hasClass("lrs-test") ) { //Top Level
		jQuery(context).find(".status").html("")
	}
	else
	{
		jQuery(context).children(".response").html("");

		if( jQuery(context).parent().closest(".lrs-test").length )
			grassblade_reset_test(jQuery(context).parent().closest(".lrs-test"));
	}
}
function grassblade_start_test_message(context) {
	if( jQuery(context).hasClass("lrs-test") ) { //Top Level
		jQuery(context).find(".status").html("Testing...")
	}
	else
	{
		jQuery(context).children(".response").html(" : Testing...");
		if( jQuery(context).parent().closest(".lrs-test").length )
			grassblade_start_test_message(jQuery(context).parent().closest(".lrs-test"));
	}
}
function grassblade_test_post_statement(context) {
	var context = jQuery(context);
	var statement = grassblade_test_build_statement("attempted", "http://adlnet.gov/expapi/verbs/attempted");

	grassblade_xhrRequestOnError(context);
	var response = ADL.XAPIWrapper.sendStatement(statement, function(xhr, obj) {
		console.log({xhr:xhr, obj:obj});

		var status = grassblade_handle_adl_response({xhr: xhr}, context);
		grassblade_change_test_status(context, status);
	});
}
function grassblade_test_get_statement(context) {
	var context = jQuery(context);
	var statement = grassblade_test_build_statement("attempted", "http://adlnet.gov/expapi/verbs/attempted");

	grassblade_xhrRequestOnError(context);
	ADL.XAPIWrapper.getStatements({"limit":1}, null, function(xhr) {
		var status = grassblade_handle_adl_response({"xhr": xhr}, context);

		if( status ) {
			try {
				var res_body = JSON.parse(xhr.response);
				console.log("getStatements Response", res_body);
			} catch (e) {
				//
			}

			if(typeof res_body != "object" || typeof res_body.statements != "object") {
				jQuery(context).find(".response").html(": Invalid Response");

				status = false;
			}
		}
		grassblade_change_test_status(context, status);
	});
}
function grassblade_xhrRequestOnError(context) {
	ADL.xhrRequestOnError = function(xhr, method, url, callback, callbackargs) {
		if(typeof callback == "function")
		callback(xhr,xhr.responseText);
		/*
	  	console.log({"function": "ADL.xhrRequestOnError", xhr:xhr, method:method, url:url, callback:callback, callbackargs:callbackargs});
		grassblade_change_test_status(context, false);
		jQuery(context).find(".response").html(": " + xhr.status + " - " + xhr.statusText);
		*/
	};
}
function grassblade_test_put_state(context) {
	var context = jQuery(context);

	var val = [{"lrstest": new Date() * 1}];

	grassblade_xhrRequestOnError(context);
	var response = ADL.XAPIWrapper.sendState( ADL.XAPIWrapper.lrs.activityId, ADL.XAPIWrapper.lrs.actor, "grassblade_test", null, val, null, null, function(xhr) {
		var status = grassblade_handle_adl_response({"xhr": xhr}, context);
		grassblade_change_test_status(context, status);
	});
}
function grassblade_test_get_state(context) {
	var context = jQuery(context);

	grassblade_xhrRequestOnError(context);
	ADL.XAPIWrapper.getState( ADL.XAPIWrapper.lrs.activityId, ADL.XAPIWrapper.lrs.actor, "grassblade_test", null, null, function(xhr) {
		var status = grassblade_handle_adl_response({"xhr": xhr}, context);

		if( status ) {
			try {
				var res_body = JSON.parse(xhr.response);
				console.log("getState Response", res_body);
			} catch (e) {
				//
			}
			if(typeof res_body != "object" || typeof res_body[0] != "object" || typeof res_body[0].lrstest == "undefined") {
				jQuery(context).find(".response").html(": Invalid Response");

				status = false;
			}
		}
		grassblade_change_test_status(context, status);
	});
}
function grassblade_test_lms_check(context) {
	console.log("grassblade_test_lms_check", context);
	var data = {
		"action" : "lrstest",
		"check" : "lms_check"
	};
	jQuery.post(gb_data.ajax_url, data)
	.done(function( data ) {
		console.error(data);

		if( typeof data == "object" && data.status ) {
			var s = true;
		}
		else
		{
			var s = false;
		}
		var message = (typeof data == "object" && data.message)? data.message:"";
		grassblade_change_test_status(context, s, message);
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		var s = false;
		grassblade_change_test_status(context, s);
	});

}
function grassblade_handle_adl_response(response, context) {
	console.log( response );

	if(response.xhr.status == 200 || response.xhr.status == 204) {
		return true;
	}
	else {
		jQuery(context).find(".response").html(": " + response.xhr.status + " - " + response.xhr.statusText);
		return false;
	}
}
function grassblade_change_test_status(context, status, status_msg = "") {
	if( jQuery(context).hasClass("lrs-test") ) { //Top Level
		if( jQuery(".lrstest-diagram").length ) {
			if(status == null)
				jQuery(context).children(".lrstest-diagram").removeClass("connecting").removeClass("connected").removeClass("failed");
			else if( status == true)
				jQuery(context).children(".lrstest-diagram").removeClass("connecting").removeClass("connected").removeClass("failed").addClass("connected");
			else
				jQuery(context).children(".lrstest-diagram").removeClass("connecting").removeClass("connected").removeClass("failed").addClass("failed");
		}
		if(status == null)
			jQuery(context).children(".status_div").children(".status").html("Unknown");
		else if(status == true)
			jQuery(context).children(".status_div").children(".status").html("Passed");
		else if(status == false)
			jQuery(context).children(".status_div").children(".status").html("Failed");
	}
	else
	{
		if( status ) {
			jQuery(context).removeClass("failed").addClass("passed");
			jQuery(context).children(".dashicons").removeClass("dashicons-minus").removeClass("dashicons-no").addClass("dashicons-yes");
			status_msg = (status_msg == "")? " : Passed":" : " + status_msg;
			jQuery(context).children(".response").html( status_msg );
		}
		else {
			jQuery(context).removeClass("passed").addClass("failed");
			jQuery(context).children(".dashicons").removeClass("dashicons-minus").removeClass("dashicons-yes").addClass("dashicons-no");
			status_msg = (status_msg == "")? " : Failed":" : " + status_msg;
			jQuery(context).children(".response").html( status_msg );
		}

		if( jQuery(context).parent().closest(".lrs-test").length && jQuery(context).parent().hasClass("sub-tests") ) {
			var parent = jQuery(context).parent().closest(".lrs-test");
			var parent_status = grassblade_test_check_status( parent );
			grassblade_change_test_status(parent, parent_status)
		}
	}
}
function grassblade_test_check_status(context) {
	var status = "";

	if( jQuery(context).find(".sub-tests .failed").length )
		status = false;
	else
	if( jQuery(context).find(".sub-tests .dashicons-minus:visible").length ) //has incomplete tests?
		status = null;
	else if( jQuery(context).find(".sub-tests .passed").length )
		status = true;

	return status;
}
function grassblade_lrstest1_start_old() {
	testno = 1;
	grassblade_lrstest_status(testno, "testing");

	var data = {
		"action" : "lrstest",
		"check" : "state"
	};
	jQuery.post(gb_data.ajax_url, data)
	.done(function( data ) {
		console.error(data);

		if( typeof data == "object" && data.status ) {
			grassblade_lrstest_status(testno, "connected");
		}
		else
		{
			grassblade_lrstest_status(testno, "failed");
		}
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		grassblade_lrstest_status(testno, "failed", "Test Failed: " + xhr.status + " " + xhr.statusText);
	});
}
function grassblade_lrstest2_start() {
	testno = 2;
	grassblade_lrstest_status(testno, "testing");

	var data = {
		"auth" : ADL.XAPIWrapper.lrs.auth
	};

	var url =  ADL.XAPIWrapper.lrs.endpoint.replace("xAPI/","api/v1/wp/check");

	jQuery.post(url, data)
	.done(function( data ) {
		console.error(data);
		if( typeof data == "object" && data.status ) {
			grassblade_lrstest_status(testno, "connected", data.message);
		}
		else
		{
			var message = (typeof data.message == "undefined")? "Check LRS Version?":data.message;
			grassblade_lrstest_status(testno, "failed", "Test Failed: " + message);
		}
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		grassblade_lrstest_status(testno, "failed", "Test Failed: " + xhr.status + " " + xhr.statusText);
	});

}
function grassblade_lrstest3_start() {
	var testno = 3;
	grassblade_lrstest_status(testno, "testing");

	setTimeout(function() {
		grassblade_lrstest_status(testno, "message", "Sending Statements...");

		setTimeout(function() {
			var statements = [
				grassblade_test_build_statement("attempted", "http://adlnet.gov/expapi/verbs/attempted"),
				grassblade_test_build_statement("passed", "http://adlnet.gov/expapi/verbs/passed"),
				grassblade_test_build_statement("failed", "http://adlnet.gov/expapi/verbs/failed"),
				grassblade_test_build_statement("completed", "http://adlnet.gov/expapi/verbs/completed")
			];

			var count = statements.length;
			ADL.XAPIWrapper.sendStatements( statements, function( xhr, responseData ) {
				if( xhr.status == 200 || xhr.status == 204 ) {
					grassblade_lrstest_status(testno, "message", "Statements Sent. Checking Triggers...");
					setTimeout(function() {
						grassblade_lrstest_check_triggers(testno);
					}, 5000);
				}
				else {
					grassblade_lrstest_status(testno, "failed", "Sending Statements Failed. Error " + xhr.status + " " + xhr.statusText + " Response: " + xhr.responseText.substr(0, 100) );
				}
			});
		}, 500);
	}, 500);
}

function grassblade_test_wp_mod_security(context) {
	var url = grassblade_lrstest.siteurl;
	jQuery.ajax(url + "?a=http://gblrs.com").fail(function(xhr, status, error) {
		console.log(xhr, xhr.responseText, xhr.status);

		jQuery.ajax(url + "?a=gblrs.com").fail(function(xhr, status, error) {
			grassblade_change_test_status(context, true);
		})
		.done(function() {
			grassblade_change_test_status(context, false);
		});
	})
	.done(function() {
		grassblade_change_test_status(context, true);
	});
}
function grassblade_test_lrs_mod_security(context) {
	var url = ADL.XAPIWrapper.lrs.endpoint + "about";
	jQuery.ajax(url + "?a=http://gblrs.com").fail(function(xhr, status, error) {
		console.log(xhr, xhr.responseText, xhr.status);

		jQuery.ajax(url + "?a=gblrs.com").fail(function(xhr, status, error) {
			grassblade_change_test_status(context, true);
		})
		.done(function() {
			grassblade_change_test_status(context, false);
		});
	})
	.done(function() {
		grassblade_change_test_status(context, true);
	});
}
function grassblade_lrstest_check_triggers(testno) {
		var data = {
			"action" : "lrstest",
			"check" : "triggers"
		};
		jQuery.post(gb_data.ajax_url, data)
		.done(function( data ) {

			if( typeof data == "object" && data.triggers ) {
				var timestamp = ADL.XAPIWrapper.lrs.timestamp;
				var result = grassblade_lrstest_verify_triggers( data.triggers );

				if(result.status)
				grassblade_lrstest_status(testno, "connected");
				else
				grassblade_lrstest_status(testno, "failed");

				jQuery.each(Object.keys(result.verbs_status), function(i, verb) {
					setTimeout(function() {
					if( result.verbs_status[verb] ) {
						jQuery("#lrstest" + testno + " .verb_" + verb).delay( i * 500 ).addClass("passed").removeClass("failed");
					}
					else{
						jQuery("#lrstest" + testno + " .verb_" + verb).delay( i * 500 ).addClass("failed").removeClass("passed");
					}
					}, i * 500);
				});
			}
			else
			{
				grassblade_lrstest_status(3, "failed", "Failed. Could not check triggers");
			}
		})

}
function grassblade_lrstest_verify_triggers( triggers ) {
	var timestamp = ADL.XAPIWrapper.lrs.timestamp;

	var checks = [
		{"name": "attempted", "verb_id" : "http://adlnet.gov/expapi/verbs/attempted", 	"f": 0,	"conditions" 	: {"action" : "grassblade_xapi_track", "grassblade_trigger" : 1} },
		{"name": "passed" 	, "verb_id" : "http://adlnet.gov/expapi/verbs/passed", 	"f": 1,	"conditions"  	: {"action" : "grassblade_completion_tracking", "grassblade_completion_tracking" : 1, "grassblade_trigger" : 1} },
		{"name": "failed" 	, "verb_id" : "http://adlnet.gov/expapi/verbs/failed", 	"f": 1,	"conditions"  	: {"action" : "grassblade_completion_tracking", "grassblade_completion_tracking" : 1, "grassblade_trigger" : 1} },
		{"name": "completed", "verb_id" : "http://adlnet.gov/expapi/verbs/completed", 	"f": 1,	"conditions"  	: {"action" : "grassblade_completion_tracking", "grassblade_completion_tracking" : 1, "grassblade_trigger" : 1} }
	];

	var statuses = [];
	var test_status = true;
	var successful_triggers = {};
	var failed_triggers = {};
	jQuery.each(checks, function(i, check) {
		var verb_id = check.verb_id;
		var conditions = check.conditions;
		var success = true;
		if( typeof triggers[verb_id] == "object" && triggers[verb_id]["time"] >= timestamp )
		jQuery.each(conditions, function(condition_key, condition_value) {
			if(triggers[verb_id][condition_key] != condition_value)
				success = false;
		});
		else
		success = false;

		if( !success && check["f"] )
			test_status = false;

		statuses[check.name] = success;
	});

	return {"status" : test_status, "verbs_status": statuses};
}

function grassblade_test_build_statement(verb, verb_id) {
	return {
		"actor": ADL.XAPIWrapper.lrs.actor,
		"object": {"id" : ADL.XAPIWrapper.lrs.activityId, "objectType" : "Activity", "definition" : { "name" : {"en-US": "LRS Connection Test"} }},
		"verb": {"id": verb_id, "display" : {"en-US" : verb }}
	};
}

function grassblade_test_lightbox_show(context) {
	var title = jQuery(context).closest("[data-test-name],.sub-test,.lrs-test").children(".test-title").html();
	jQuery(".grassblade_test_lightbox .test-title").html(title);
	jQuery(".grassblade_test_lightbox .test-info").html(jQuery(context).html());
	jQuery(".grassblade_test_lightbox").slideDown();

}
function grassblade_test_lightbox_close() {
	jQuery(".grassblade_test_lightbox").slideUp();
	return false;
}

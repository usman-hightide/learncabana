function run_completion_tests(el){
	grassblade_reset_tests();

	jQuery(".completions-tests-container").show();
	var content_id = jQuery("#xapi_content_selector").val();

	if ( !content_id ) {
		alert("Please select a content.");
		return;
	}

	jQuery(".grassblade_lrstest").slideDown(1500);
	setTimeout(function() {
		is_completion_tracking_enabled(content_id);
	}, 1000);
}

function run_reset_learner_progress(el) {
	// get selected xapi_content_id and user_id
	var content_id = jQuery("#xapi_content_selector").val();
	var user_id = jQuery("#user_id").val() * 1;

	if( !content_id ) {
		alert("Please select a content.");
		return;
	}

	// send a request to the WordPress
	var data = {
		"content_id" : content_id,
		"user_id" : user_id
	};

	var label = jQuery(el).html();
	jQuery(el).html("Please wait...");
	jQuery.post(ajaxurl + "?action=reset_learner_progress" , data)
	.done(function( response ) {
		if( response.status ) {
			jQuery(el).html("Reset Successful");
		}
		else {
			jQuery(el).html("Reset Failed");
		}

		setTimeout(function() {
			jQuery(el).html(label);
		}, 4000);
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		jQuery(el).html("Reset Failed");
	});
}

function is_completion_tracking_enabled(content_id = 0){
	if(content_id == 0)
		content_id = jQuery(".gb_select2_xapi_content").val();

	is_completion_by_module_enabled = 0;
	grassblade_lrstest_status(1, "testing");
	jQuery.getJSON( ajaxurl,{"action":"lrstest", "check":"check_completion_tracking", "content_id": content_id},function( response ) {

		if(typeof response == "object"){
			if(response.completion_tracking == true){
				activity_id 		 = response.activity_id;
				original_activity_id = response.original_activity_id;

				var message = "Enabled";

				if(response.completion_by_module == true){
					message += " | Completion on Module Completion: Enabled"
					is_completion_by_module_enabled = 1;
				}

				jQuery("#lrstest1").find(".message").html(`<pre>${JSON.stringify(response, undefined, 2)}</pre>`)

				grassblade_lrstest_status(1, "passed", message);
				run_other_completion_tests();
			}else
			grassblade_lrstest_status(1, "failed");

			if(typeof response.edit_url != "undefined"){
				edit_page_url = response.edit_url;
				jQuery("#lrstest1").find(".edit_page_button").attr("href", edit_page_url)
			}
		}
	});
}

function run_other_completion_tests() {
	contents_with_same_activity_id();
	content_added_and_completion();

	if( !jQuery("#lrstest4.dont-auto-reset").find(".passed,.connected,.failed").length )
	check_lms_integration();

	gb_check_lrs_version().then((version) => {
		if( version ) {
			// Run Other LRS Related Tests
			if( !jQuery("#lrstest5.dont-auto-reset").find(".passed,.connected,.failed").length )
			grassblade_check_triggers_in_lrs();

			statement_tests();
			error_log_test();
		}
	});
	run_registrations_test();
}

async function gb_check_lrs_version() {

	if(typeof window.lrs_version == "string" && window.lrs_version.length > 0)
		return window.lrs_version;

	var lrsurl   	 = ADL.XAPIWrapper.lrs.endpoint.replace("xAPI/","");
	var req_url  	 = lrsurl+"api/v1/version/check";

	var response = await jQuery.getJSON(req_url, {});
	var version = ( typeof response.data == "object" && typeof response.data.version == "string" ) ? response.data.version : false;
	window.lrs_version = version;

	return window.lrs_version;
}

function contents_with_same_activity_id(){
	var testno = 2;
	var context = jQuery("#lrstest" + testno);
	var content_id = jQuery(".gb_select2_xapi_content").val();
	var data = {
		"action" 	 : "lrstest",
		"check" 	 : 'multiple_content',
		"content_id" : content_id,
	};
	grassblade_lrstest_status(testno, "testing");
	jQuery.getJSON( ajaxurl, data, function( response ) {
		console.error(response);
		var s 		= ( typeof response == "object" && response.status) ? "passed" : "failed";
		var message = (typeof response == "object" && response.message)? response.message:'';
		grassblade_lrstest_status(testno, s);
		jQuery(context).find(".message").html(message);

		if(s == "failed")
			jQuery(context).find(".results-found ol").html(message);
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		var s = false;
		grassblade_change_test_status(context, s);
	});
}

function content_added_and_completion(){
	testno = 3;
	var context = jQuery("#lrstest" + testno);
	var content_id = jQuery(".gb_select2_xapi_content").val();
	var args = {
		"action" 	 : "lrstest",
		"check" 	 : "content_added_on_and_completion",
		"content_id" : content_id,
		'user_id' 	 : jQuery("#user_id").val() * 1
	};
	grassblade_lrstest_status(testno, "testing");
	jQuery.getJSON( ajaxurl, args, function( response ) {
		console.error(response);
		var s 		= ( typeof response == "object" && response.status) ? "passed" : "failed";
		var message = (typeof response == "object" && response.message != '')? response.message:'';
		grassblade_lrstest_status(testno, s);
		jQuery(context).find(".message").html(message);
		jQuery(context).find(".results-found ol").html(message);

	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		var s = false;
		grassblade_change_test_status(context, s);
	});
}

function check_lms_integration(){
	var testno = 4;
	var context = jQuery("#lrstest" + testno);
	var data = {
		"action" : "lrstest",
		"check" : "lms_check"
	};
	grassblade_lrstest_status(testno, "testing");
	jQuery.post(ajaxurl, data)
	.done(function( response ) {
		console.error(response);
		var s 		= ( typeof response == "object" && response.status) ? "passed" : "failed";
		var message = (typeof response == "object" && response.message)? response.message:"";
		grassblade_lrstest_status(testno, s);
		jQuery(context).find(".message").html(message);
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		var s = false;
		grassblade_change_test_status(context, s);
	});
}

function statement_tests(){
	var testno = 6;
	grassblade_lrstest_subtests_start(testno);
}
function grassblade_test_current_activity_statement(context){
	var check = 'current_activity_statement';
	var content_id = jQuery(".gb_select2_xapi_content").val();
	run_statement_tests(context, check, content_id);
}
function grassblade_test_current_user_activity_statement(context){
	var check = 'current_user_activity_statement';
	var content_id = jQuery(".gb_select2_xapi_content").val();
	var user_id = jQuery("#user_id").val() * 1;
	run_statement_tests(context, check, content_id, user_id);
}
function grassblade_test_original_activity_statement(context){
	var check = 'original_activity_statement';
	var content_id = jQuery(".gb_select2_xapi_content").val();
	var user_id = jQuery("#user_id").val() * 1;
	run_statement_tests(context, check, content_id);
}
function grassblade_test_original_user_activity_statement(context){
	var check = 'original_user_activity_statement';
	var content_id = jQuery(".gb_select2_xapi_content").val();
	var user_id = jQuery("#user_id").val() * 1;
	run_statement_tests(context, check, content_id, user_id);
}
function run_statement_tests(context, check, content_id, user_id = ''){
	content_id = jQuery(".gb_select2_xapi_content").val();
	var data = {
		"action" 	 : "lrstest",
		"check" 	 : check,
		"user_id" 	 : user_id,
		"content_id" : content_id,
	};

	jQuery.post(ajaxurl, data)
	.done(function( response ) {
		console.error(response);
		s = ( typeof response == "object" && response.status ) ? true :false;
		var message = (typeof response == "object" && response.message)? response.message:"";

		var statements = (typeof response.statement != "undefined") ? response.statement : "";
		if(statements.length == 1){
			jQuery(context).find(".statement div").html(`<pre>${JSON.stringify(statements, undefined, 2)}</pre>`);
		}
		else if(check == 'current_user_activity_statement' && statements != ''){
			jQuery(context).find(".statement div").html('');
			var trs = ''
			jQuery.each(statements, function(k,v){

				jQuery(context).find(".statement div").append(`<pre>${JSON.stringify(v, undefined, 2)}</pre>`);

				var statement_id = (typeof v.id != "undefined") ? v.id : '';
				var timestamp 	 = (typeof v.timestamp != "undefined") ? v.timestamp : "";
				if (typeof v.verb != "object" || v.verb == null )
				var verb = "undefined";
				else if( typeof v.verb.display["en-US"] == "string" )
				var verb = v.verb.display["en-US"];
				else if( typeof v.verb.display == "object" )
				var verb = Object.values(v.verb.display)[0];
				else
				var verb = v.verb.id;

				var score = '';
				if(typeof v.result != "undefined" && typeof v.result.score != "undefined"){
					score += (typeof v.result.score.scaled != "undefined") ? " Scaled: " + v.result.score.scaled : "";
					score += (typeof v.result.score.min != "undefined") ? " Min: " + v.result.score.min : "";
					score += (typeof v.result.score.max != "undefined") ? " Max: " + v.result.score.max : "";
					score += (typeof v.result.score.raw != "undefined") ? " Raw: " + v.result.score.raw : "";
				}
				trs += `
					<tr>
						<td>${timestamp}</td>
						<td>${verb}</td>
						<td>${score}</td>
						<td><button class="button-lrstest" onClick="re_run_triggers('${statement_id}', jQuery(this))" data-statement='${JSON.stringify(v)}'>Re-Trigger Completion</button> <span class="trigger_response"></span></td>
					</tr>
				`
			})
			message += `
			<div class="re_run_triggers">
				<table class="grassblade_table">
					<thead>
						<th>Timestamp</th>
						<th>Verb</th>
						<th>Score</th>
						<th>Action</th>
					</thead>
					<tbody>
						${trs}
					</tbody>
				</table>
			</div>`;
		}

		grassblade_change_test_status(context, s, message);
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		var s = false;
		grassblade_change_test_status(context, s);
	});
}

function error_log_test(){
	var testno  = 7;
	var context = jQuery("#lrstest" + testno);
	content_id = jQuery(".gb_select2_xapi_content").val();
	var data = {
		"action" 	 : "lrstest",
		"check" 	 : "error_logs",
		"user_id" 	 : jQuery("#user_id").val(),
		"content_id" : content_id,
	};

	gb_check_lrs_version().then((version) => {
		if( !version || gb_version_compare(version, "2.13") < 0 ) {
			var message = "<p style='text-align:center; color:red;'>Your GrassBlade LRS (v"+version+") is not supported for this test. Please update GrassBlade LRS to the latest version.</p>";
			grassblade_lrstest_status(testno, "Failed");
			jQuery(context).find(".message").html(message);
			grassblade_change_test_status(context, false);
			return;
		}
		else
		{
			jQuery.post(ajaxurl, data)
			.done(function( response ) {
				console.error(response);
				s = ( typeof response == "object" && response.status ) ? true :false;
				message = ( typeof response == "object" && response.message ) ? response.message :"";
				var status  = status_o = "failed";
				var lrsurl   = ADL.XAPIWrapper.lrs.endpoint.replace("xAPI/","");

				if( typeof response.data == "object" && response.data.length > 0 ) {
					message  += "<h3>Error Logs for Current Activity ID</h3>";
					jQuery.each(response.data, function(k,v){
						var bg_color  = ( v.status ==  1 ) ? "gb_bg_green" : ( v.status ==  2 ) ? "gb_bg_yellow" : "gb_bg_red";
						message 	 += `<p class=${bg_color}><a style='color: #6e6262; float:right; font-size:13px;' href='${lrsurl}/ErrorLogs/view/${v.id}' target='_blank'>${v.created}</a><br>${v.error_msg}</p>`;
						status 		  = (v.status == 1) ? "passed" : status;
					})
				}
				else
				if( typeof response.data_original_activity_id == "object" && response.data_original_activity_id.length > 0 ) {
					message  += "<h3>Error Logs for Original Activity ID</h3>";
					jQuery.each(response.data_original_activity_id, function(k,v){
						var bg_color  = ( v.status ==  1 ) ? "gb_bg_green" : ( v.status ==  2 ) ? "gb_bg_yellow" : "gb_bg_red";
						message 	 += `<p class=${bg_color}><a style='color: #6e6262; float:right; font-size:13px;' href='${lrsurl}/ErrorLogs/view/${v.id}' target='_blank'>${v.created}</a><br>${v.error_msg}</p>`;
						status_o 		  = (v.status == 1) ? "passed" : status_o;
					})

					if( status_o == "passed" && typeof response.args.original_activity_id == "string" ) {
						var edit = ' (<a target="_blank" href="' + wp_admin_url + 'post.php?action=edit&post=' + content_id + '">Edit</a>)';
						message += "<div class='failed'>If none of the completions are working on this content, try changing your content's Activity ID to: " + response.args.original_activity_id + edit + "</div>";
					}
				}
				grassblade_lrstest_status(testno, status);
				jQuery(context).find(".message").html(message);
				//grassblade_change_test_status(context, s, message);
			})
			.fail(function(xhr, status, error) {
				console.log(xhr, status, error);
				var s = false;
				grassblade_change_test_status(context, s);
			});
		  }
	});
}

function error_log_test2(){

	var testno  = 7;
	var context = jQuery("#lrstest" + testno);

	var data = {
		"auth" 				: ADL.XAPIWrapper.lrs.auth,
		"trigger_url"		: ajaxurl + "?action=grassblade_completion_tracking",
		"agent_id" 			: agent_id,
		"activity_id"   	: activity_id,
		"module_completion" : is_completion_by_module_enabled,
	};

	var message = '<div>';
	var status  = "failed";

	grassblade_lrstest_status(testno, "testing");
	var lrsurl   = ADL.XAPIWrapper.lrs.endpoint.replace("xAPI/","");
	var req_url  = lrsurl+"api/v1/error_log/get";
	var response = '';
	jQuery.getJSON(req_url, data, function(response){
		if(typeof response != "undefined" && response.length !== 0){
			console.error(response);
			message  += "<h3>Error Logs for Current Activity ID</h3>";
			jQuery.each(response, function(k,v){
				var bg_color  = ( v.status ==  1 ) ? "gb_bg_green" : ( v.status ==  2 ) ? "gb_bg_yellow" : "gb_bg_red";
				message 	 += `<p class=${bg_color}><a style='color: #6e6262; float:right; font-size:13px;' href='${lrsurl}/ErrorLogs/view/${v.id}' target='_blank'>${v.created}</a><br>${v.error_msg}</p>`;
				status 		  = (v.status == 1) ? "passed" : status;
			})
		}
		message += "</div>";
		grassblade_lrstest_status(testno, status);
		jQuery(context).find(".message").html(message);
	})

	if(response.length === 0 && typeof original_activity_id != "undefined" && original_activity_id != activity_id ){
		data.activity_id = original_activity_id;
		jQuery.getJSON(req_url, data, function(result){
			var message = '<div>';
			var status  = "failed";
			console.error(result);
			if(typeof result != "undefined" && result.length !== 0){
				message  += "<div><h3>Error Logs for Orignal Activity ID</h3>";
				jQuery.each(result, function(k,v){
					var bg_color  = ( v.status ==  1 ) ? "gb_bg_green" : ( v.status ==  2 ) ? "gb_bg_yellow" : "gb_bg_red";
					message 	 += `<p class=${bg_color}><a style='color: #6e6262; float:right; font-size:13px;' href='${lrsurl}/ErrorLogs/view/${v.id}' target='_blank'>${v.created}</a><br>${v.error_msg}</p>`;
					// status 		  = (v.status == 1) ? "passed" : status;
				})
			}
			message += "</div>";
			// grassblade_lrstest_status(testno, status); // For original activity ID, chaning test status is no required.
			jQuery(context).find(".message").html(message);
		})
	}
}

// check revision activity ID and match with current and original, then check the statements.
function grassblade_test_revision_activity_id_test(context){
	var content_id = jQuery(".gb_select2_xapi_content").val();
	var args = {
		"action" 	 : "lrstest",
		"check" 	 : 'revision_activity_id_test',
		"content_id" : content_id,
	};
	jQuery(context).find(".statement div").html('');
	var message = '';
	var statements = {};
	var s = true;
	jQuery.getJSON( ajaxurl, args, function( response ) {
		console.error(response);
		jQuery.each(response, function(k,v){
			message += ( typeof v.message != "undefined" ) ? v.message + "<br>" : "";
			if(message == '')
				message = "No unique activity ID found in revisions."

			s = ( typeof v == "object" && v.status) ? false : true;
			statements[k] = ( typeof v.statement != "undefined" ) ? v.statement : "";
		})

		jQuery.each(statements, function(k,v){
			if(v != "" && typeof JSON.stringify(v, undefined, 2) != "undefined" )
				jQuery(context).find(".statement div").append(`<pre><h4>Activity ID: ${k}</h4>'${JSON.stringify(v, undefined, 2)}'</pre>`);
		})

		grassblade_change_test_status(context, s, message);
	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		var s = false;
		grassblade_change_test_status(context, s);
	});
}

function run_registrations_test(registration_id){
	var testno = 9;
	var context = jQuery("#lrstest" + testno);
	var data = {
		"action" : "lrstest",
		"check" : "registrations_test",
		"content_id" : jQuery(".gb_select2_xapi_content").val(),
		"user_id" : jQuery("#user_id").val(),
		"set_current" : registration_id
	};
	grassblade_lrstest_status(testno, "testing");
	jQuery.post(ajaxurl, data)
	.done(function( response ) {
		console.error(response);
		var s = ( typeof response == "object" && response.status) ? "passed" : "failed";
		var message = (typeof response == "object" && response.message)? response.message:'';
		grassblade_lrstest_status(testno, s);
		var html = '';
		if( typeof response.data == "object" ){
			html = '<table class="grassblade_table" style="margin:auto;"><thead><tr><th>Registration</th><th>Generated On</th><th>Reset By</th><th>Status</th><th>Action</th></tr></thead><tbody>';
			jQuery.each(response.data, function(k,v){
				var is_current = ( v.is_latest == 1 ) ? "Current" : `<button class="button-lrstest" onClick="run_registrations_test('${v.registration}', jQuery(this))" data-registration='${JSON.stringify(v)}'>Set Current</button>`;
				var is_fixed = ( v.is_fixed == 1 ) ? "Fixed" : "";
				html += `<tr>
							<td>${v.registration} </td>
							<td>${v.generated} ${is_fixed} </td>
							<td>${v.reset_by}</td>
							<td>${v.status}</td>
							<td class="">
								${is_current}
								<a href="${v.launch_url}" target="_blank">Launch</a>
								<span></span>
							</td>
						</tr>`;
			});
			html += '</tbody></table>';
		}
		jQuery(context).find(".message").html(message);
		jQuery(context).find(".lrstest9_results").html(html);

	})
	.fail(function(xhr, status, error) {
		console.log(xhr, status, error);
		var s = false;
		grassblade_change_test_status(context, s);
	});
}

function re_run_triggers(statement_id, context = ''){

	jQuery(context).next(".trigger_response").html("Processing...");
	gb_check_lrs_version().then(function(version) {
		if(gb_version_compare(version, "2.13") < 0) {
			jQuery(context).next(".trigger_response").html("Upgrade GrassBlade LRS to v2.13+ to re-run triggers.");
			return;
		}

		var data = {
			"auth" 		   : ADL.XAPIWrapper.lrs.auth,
			"statement_id" : statement_id
		};
		var url = ADL.XAPIWrapper.lrs.endpoint.replace("xAPI/","api/v1/triggers/run");
		jQuery.getJSON(url, data, function (response) {
			console.error(response);
			var message = (response.success) ? "Success" : "Failed"
			jQuery(context).next(".trigger_response").html(message);
		})
		.fail(function(xhr, status, error) {
			console.log(xhr, status, error);
			var s = false;
			grassblade_change_test_status(context, s);
		});
	});
}
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
		jQuery("#lrstest" + id + " .status").html(message);
		//jQuery("#lrstest" + id ).removeClass("failed").removeClass("passed");
		grassblade_lrstest_anim();

		break;

		case "connected":
		message = message? message:"Connected";

		jQuery("#lrstest" + id + " .lrstest-diagram").removeClass("connecting").removeClass("failed").addClass("connected");
		jQuery("#lrstest" + id + " .status").html(message);
		break;

		case "failed":
		message = message? message:"Test Failed";

		jQuery("#lrstest" + id + " .lrstest-diagram").removeClass("connecting");
		jQuery("#lrstest" + id + " .lrstest-diagram").addClass("failed");
		jQuery("#lrstest" + id + " .status").html(message);
		//jQuery("#lrstest" + id ).addClass("failed");
		break;

		case "passed":
			message = message? message:"Passed";
			jQuery("#lrstest" + id + " .lrstest-diagram").removeClass("connecting");
			jQuery("#lrstest" + id + " .lrstest-diagram").addClass("connected");
			jQuery("#lrstest" + id + " .status").html(message);
		//	jQuery("#lrstest" + id ).addClass("passed");
		break;

		case "message":
		if( message )
		jQuery("#lrstest" + id + " .status").html(message);
		break;
	}
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
function grassblade_reset_tests() {
	jQuery(".lrs-test,.lrs-test [data-test-no],.sub-test").each(function(i, test) {
		if( !jQuery(test).hasClass("dont-auto-reset") && jQuery(test).closest(".lrs-test.dont-auto-reset").length == 0 )
        grassblade_reset_test( test );
	});
}
function grassblade_reset_test(context) {
	jQuery(context).removeClass("failed").removeClass("passed");
	jQuery(context).children(".dashicons:not(.no-change)").removeClass("dashicons-yes").removeClass("dashicons-no").addClass("dashicons-minus");

	if( jQuery(context).find(".message").length )
	jQuery(context).find(".message").html('');

	if( jQuery(context).hasClass("lrs-test") ) { //Top Level
		if( jQuery(context).children(".lrstest-diagram").length )
		jQuery(context).children(".lrstest-diagram").removeClass('connected').removeClass('failed');

		jQuery(context).find(".status").html("");
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
			jQuery(context).find(".dashicons").removeClass("dashicons-minus").removeClass("dashicons-no").addClass("dashicons-yes");
			status_msg = (status_msg == "")? " Status: Passed":"Status: " + status_msg;
			jQuery(context).children(".response").html( status_msg );
		}
		else {
			jQuery(context).removeClass("passed").addClass("failed");
			jQuery(context).find(".dashicons").removeClass("dashicons-minus").removeClass("dashicons-yes").addClass("dashicons-no");
			status_msg = (status_msg == "")? "Status: Failed":"Status: " + status_msg;
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
function grassblade_check_triggers_in_lrs() {
	var testno = 5;
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
			ADL.XAPIWrapper.sendStatements(statements, function(xhr, responseData) {
				if(xhr.status == 200 || xhr.status == 204)
				{
					grassblade_lrstest_status(testno, "message", "Statements Sent. Checking Triggers...");
					setTimeout(function() {
						grassblade_lrstest_check_triggers(testno);
					}, 5000);
				}
				else
				{
					grassblade_lrstest_status(testno, "failed", "Sending Statements Failed. Error " + xhr.status + " " + xhr.statusText + " Response: " + xhr.responseText.substr(0, 100) );
				}
			});
		}, 500);
	}, 500);
}
function grassblade_lrstest_check_triggers(testno) {
		var data = {
			"action" : "lrstest",
			"check" : "triggers"
		};
		jQuery.post(ajaxurl, data)
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

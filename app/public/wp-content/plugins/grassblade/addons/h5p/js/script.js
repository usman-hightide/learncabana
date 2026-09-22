H5P.externalDispatcher.on('xAPI', function(event) {
// Statement is available at: event.data.statement
var statement =  event.data.statement;

if(ADL.XAPIWrapper.lrs.actor != undefined && typeof ADL.XAPIWrapper.lrs.actor == "string")
statement.actor = JSON.parse(ADL.XAPIWrapper.lrs.actor);

if(typeof statement.actor != "object" || typeof statement.actor.actor != "undefined" || typeof statement.object != "object"  || typeof statement.verb != "object" || typeof statement.verb.id != "string" ||  statement.verb.id == "http://adlnet.gov/expapi/verbs/interacted")
	return;
//Check if statement actor, verb and object is present or not.

// Send the statement using xAPI Wrapper code you added earlier.
statement.id = ADL.ruuid();

var is_completion_statement = gb_is_completion_statement(statement);

if(ADL.XAPIWrapper.lrs.endpoint != "http://localhost:8000/xapi/") {
    setTimeout(() => ADL.XAPIWrapper.sendStatement(statement), 200);
}

if(is_completion_statement) {
    if(typeof statement.context == "undefined")
        statement.context = {};
    statement.context.registration = ADL.XAPIWrapper.lrs.registration;

    gb_send_completion_trigger(statement, () => gb_announce_h5p_completion(statement) );
}

});

function gb_announce_h5p_completion(statement) {
    var targetWindow = window.parent;
    var completion_content_data = {"statement" : statement};
    if (typeof(window.parent.call_grassblade_get_completion) == 'function') {
        targetWindow.call_grassblade_get_completion(completion_content_data);
    } else {
        targetWindow.postMessage(completion_content_data, targetWindow.origin);
    }
}

function gb_is_completion_statement(statement){
    if (typeof(statement) != "undefined") {
        if ( ("verb" in statement) && ("object" in statement) ) {
            var verb_id = statement.verb.id;
            var completed_array = ["http://adlnet.gov/expapi/verbs/completed","http://adlnet.gov/expapi/verbs/passed","http://adlnet.gov/expapi/verbs/failed"];
            if( typeof(statement.verb.id) != "undefined" && verb_id !== null && gb_array_includes(completed_array,verb_id) ) {
                return true;
            }
        }
    } // end of if type of undefined
    return false;
}

function gb_array_includes(container, value) {
    var returnValue = false;
    var pos = container.indexOf(value);
    if (pos >= 0) {
        returnValue = true;
    }
    return returnValue;
}

function gb_send_completion_trigger(statement, callback) {

    if(typeof statement.actor.mbox == "string")
    var agent_id = statement.actor.mbox.replace("mailto:","");
    else if(typeof statement.actor.mbox_sha1sum == "string")
    var agent_id = statement.actor.mbox_sha1sum;
    else if(typeof statement.actor.openid == "string")
    var agent_id = statement.actor.openid;
    else if(typeof statement.actor.account == "object" && typeof statement.actor.account.homePage == "string" && typeof statement.actor.account.name == "string")
    var agent_id = statement.actor.account.homePage + "/" + statement.actor.account.name;
    else
        return;

    var data = {
        "action": "grassblade_completion_tracking",
        "grassblade_trigger": 1,
        "grassblade_completion_tracking": 1,
        "objectid": statement.object.id,
        "agent_id": agent_id,
        "statement": JSON.stringify(statement)
    }
    var ajax_url = H5PIntegration.siteUrl + "/wp-admin/admin-ajax.php";
    H5P.jQuery.post(ajax_url, data).done( () => callback() );
}
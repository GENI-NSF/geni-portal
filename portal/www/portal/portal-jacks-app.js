//----------------------------------------------------------------------      
// Copyright (c) 2011-2014 Raytheon BBN Technologies                          
//                                                                            
// Permission is hereby granted, free of charge, to any person obtaining      
// a copy of this software and/or hardware specification (the "Work") to      
// deal in the Work without restriction, including without limitation the     
// rights to use, copy, modify, merge, publish, distribute, sublicense,       
// and/or sell copies of the Work, and to permit persons to whom the Work     
// is furnished to do so, subject to the following conditions:                
//                                                                            
// The above copyright notice and this permission notice shall be             
// included in all copies or substantial portions of the Work.                
//                                                                            
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS        
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF                 
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND                      
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT                
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,               
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,         
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS         
// IN THE WORK.                                                               
//----------------------------------------------------------------------      

// A set of functions to handle events coming from the Jacks App (JA)
// and responding asynchronously to the Jacks App

var jacks_app_input = {};
var jacks_app_output = {};

// Callback for when JA is ready
function portal_jacks_app_ready(ja, ja_input, ja_output) {
    jacks_app_input = ja_input;
    jacks_app_output = ja_output;

    // Register embedding page (EP) event handlers from JA
    jacks_app_output.on(ja.ADD_EVENT_TYPE, ep_on_add);
    jacks_app_output.on(ja.DELETE_EVENT_TYPE, ep_on_delete);
    jacks_app_output.on(ja.MANIFEST_EVENT_TYPE, ep_on_manifest);
    jacks_app_output.on(ja.RENEW_EVENT_TYPE, ep_on_renew);
    jacks_app_output.on(ja.RESTART_EVENT_TYPE, ep_on_restart);
    jacks_app_output.on(ja.STATUS_EVENT_TYPE, ep_on_status);
}

function success_callback(responseTxt, statusTxt, xhr, am_id, slice_id, client_data) {
    // console.log("ResponseText = " + responseTxt);
    // console.log("statusText = " + statusTxt);
    // console.log("XHR = " + xhr);
    event_type = client_data.event_type;
    response_event = {code:0, value:responseTxt, output:statusTxt, 
		      am_id:am_id, slice_id:slice_id, client_data:client_data};
    jacks_app_input.trigger(event_type, response_event);
}

function error_callback(xhr, textStatus, errorThrown, am_id, slice_id, client_data) {
    console.log("XHR = " + xhr);
    console.log("ResponseText = " + textStatus);
    console.log("errorThrown = " + errorThrown);
    event_type = client_data.event_type;
    response_event = {code:xhr.status, value:null, output:errorThrown,
		      am_id:am_id, slice_id:slice_id, client_data:client_data};
    jacks_app_input.trigger(event_type, response_event);
}

// Handle the add (i.e. add resources)  request. 
// Redirect to the add resouces page
function ep_on_add(event) {
    console.log("ep_on_add");
    var slice_id = event.slice_id;
    var new_url = "slice-add-resources-jacks.php?slice_id=" + slice_id;
    window.location.replace(new_url);
}

// Handle the delete request. 
// Make an AJAX call to invoke the AM delete call
// Then call the appropriate callback to the JA with the result.
function ep_on_delete(event) {
    console.log("ep_on_delete");
    var am_id = event.am_id;
    var slice_id = event.slice_id;
    var client_data = event.client_data;
    client_data.event_type = event.name;
    $.getJSON("deletesliver.php",
              { am_id: am_id, slice_id: slice_id },
              function(rt, st, xhr) {
                  success_callback(rt, st, xhr, am_id, slice_id, client_data);
              })
    .fail(function(xhr, ts, et) {
	error_callback(xhr, ts, et, am_id, slice_id, client_data);
    });
}

// Handle the manifest status. 
// Make an AJAX call to invoke the AM manifest call
// Then call the appropriate callback to the JA with the result.
function ep_on_manifest(event) {
    console.log("ep_on_manifest");
    var am_id = event.am_id;
    var slice_id = event.slice_id;
    var client_data = event.client_data;
    client_data.event_type = event.name;
    $.get("jacks-app-details.php",
          { am_id:am_id, slice_id:slice_id },
          function(rt, st, xhr) {
              success_callback(rt, st, xhr, am_id, slice_id, client_data);
          })
    .fail(function(xhr, ts, et) {
	error_callback(xhr, ts, et, am_id, slice_id, client_data);
    });
}

// Handle the renew status. 
// Make an AJAX call to invoke the AM renew call
// Then call the appropriate callback to the JA with the result.
function ep_on_renew(event) {
    console.log("ep_on_renew");
    var am_id = event.am_id;
    var slice_id = event.slice_id;
    var expiration_time = event.expiration_time;
    var client_data = event.client_data;
    client_data.event_type = event.name;
    $.get("renewsliver.php",
          { am_id:am_id, slice_id:slice_id, sliver_expiration:expiration_time },
          function(rt, st, xhr) {
              success_callback(rt, st, xhr, am_id, slice_id, client_data);
          })
    .fail(function(xhr, ts, et) {
	error_callback(xhr, ts, et, am_id, slice_id, client_data);
    });
}

// Handle the restart request
// Make an AJAX call to invoke the AM POA geni_restart call
// Then call the appropriate callback to the JA with the result.
function ep_on_restart(event) {
    console.log("ep_on_restart");
    var am_id = event.am_id;
    var slice_id = event.slice_id;
    var client_data = event.client_data;
    client_data.event_type = event.name;
    $.get("restartsliver.php",
          { am_id : am_id, slice_id:slice_id },
          function(rt, st, xhr) {
              success_callback(rt, st, xhr, am_id, slice_id, client_data);
          })
    .fail(function(xhr, ts, et) {
	error_callback(xhr, ts, et, am_id, slice_id, client_data);
    });
}

// Handle the status status. 
// Make an AJAX call to invoke the AM status call
// Then call the appropriate callback to the JA with the result.
function ep_on_status(event) {
    console.log("ep_on_status");
    var am_id = event.am_id;
    var slice_id = event.slice_id;
    var client_data = event.client_data;
    client_data.event_type = event.name;
    $.getJSON("amstatus.php",
              { am_id: am_id, slice_id: slice_id },
              function(rt, st, xhr) {
                  success_callback(rt, st, xhr, am_id, slice_id, client_data);
              })
    .fail(function(xhr, ts, et) {
	error_callback(xhr, ts, et, am_id, slice_id, client_data);
    });
}



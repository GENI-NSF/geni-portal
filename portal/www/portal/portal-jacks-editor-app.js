//----------------------------------------------------------------------      
// Copyright (c) 2011-2015 Raytheon BBN Technologies                          
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

// A set of functions to handle events coming from the Jacks Editor App (JE)
// and responding asynchronously to the Jacks Editor App

var jacks_editor = {};
var jacks_editor_app_input = {};
var jacks_editor_app_output = {};

var portal_jacks_editor_app_verbose = false;

// Log messages to console if 'verbose' is set
function debug(msg) {
    if (portal_jacks_editor_app_verbose)
	console.log(msg);
};

// Callback for when JE is ready
function portal_jacks_editor_app_ready(je, je_input, je_output) {
    jacks_editor = je;
    jacks_editor_app_input = je_input;
    jacks_editor_app_output = je_output;

    // Register embedding page (EP) event handlers from JE
    jacks_editor_app_output.on(je.DOWNLOAD_EVENT_TYPE, ep_on_download);    
    jacks_editor_app_output.on(je.LOOKUP_EVENT_TYPE, ep_on_lookup);    
    jacks_editor_app_output.on(je.RESERVE_EVENT_TYPE, ep_on_reserve);
    jacks_editor_app_output.on(je.UPLOAD_EVENT_TYPE, ep_on_upload);
    je.updateStatus("Jacks Editor ready.");
};

function pe_success_callback(responseTxt, statusTxt, xhr, am_id, slice_id, client_data) {
    // debug("ResponseText = " + responseTxt);
    // debug("statusText = " + statusTxt);
    // debug("XHR = " + xhr);
    event_type = client_data.event_type;
    response_event = {code:0, value:responseTxt, output:statusTxt, 
		      am_id:am_id, slice_id:slice_id, client_data:client_data};
    jacks_editor_app_input.trigger(event_type, response_event);
};

function pe_error_callback(xhr, textStatus, errorThrown, am_id, slice_id, client_data) {
    debug("XHR = " + xhr);
    debug("ResponseText = " + textStatus);
    debug("errorThrown = " + errorThrown);
    event_type = client_data.event_type;
    response_event = {code:xhr.status, value:null, output:errorThrown,
		      am_id:am_id, slice_id:slice_id, client_data:client_data};
    jacks_editor_app_input.trigger(event_type, response_event);
};

// Handle the lookup for an RSpec by id
function ep_on_lookup(event) {
    var rspec_id = event.rspec_id;
    debug("ep_on_lookup " + rspec_id);
    client_data = {id : rspec_id};
    slice_id = "";
    am_id = 0;

    $.get("rspecview.php",
	 {id : rspec_id},
              function(rt, st, xhr) {
		  var rspec = xhr.responseText;
		  jacks_editor_app_input.trigger(event.name,
						 {code : 0,
							 rspec : rspec});
              })
    .fail(function(xhr, ts, et) {
	pe_error_callback(xhr, ts, et, am_id, slice_id, client_data);
    });

};

// Handle upload request (turn a URL into the corresponding rspec text
function ep_on_upload(event) {
    client_data = {};
    slice_id = "";
    am_id = 0;

    var url = event.url;
    $.get("upload-file.php", 
	  {url : url}, 
              function(rt, st, xhr) {
		  var rspec = xhr.responseText;
		  jacks_editor_app_input.trigger(event.name,
						 {code : 0,
							 rspec : rspec});
              })
    .fail(function(xhr, ts, et) {
	pe_error_callback(xhr, ts, et, am_id, slice_id, client_data);
	});
};

// Handle the reserve request
// Make an AJAX call to invoke the AM createsliver call
// Then call the appropriate callback to the JE with the result.
function ep_on_reserve(event) {
    debug("ep_on_reserve");
    var am_id = event.am_id;
    var rspec = event.rspec;
    var slice_id = event.slice_id;

    var create_sliver_url = "createsliver.php?am_id=" + am_id + "&slice_id=" + slice_id + "&rspec_jacks=" + rspec + "&background=1";

    $.get(create_sliver_url,
	  {},
	  function(rt, st, xhr) {
	      debug("Return from createsliver.php");
	      monitor_reservation(rt, event);
	  })
    .fail(function(xhr, ts, et) {
	    debug("Fail return from createsliver.php");
	});
};

// Poll the invocation 'stop' file to see when the invocation is complete. Then tell the jacks-editor-app
// that the reservation is completed.
function monitor_reservation(rt, event)
{
    var orig_rt = rt;
    url_parts = rt.split('?')[1].split('&');
    url_args = rt.split('?')[1];
    var checkstop_url = "get_omni_invocation_data.php?" + url_args + "&request=stop&raw=false";
    $.getJSON(checkstop_url, 
	  {},
	  function(rt, st, xhr) {
	      //	      console.log("Return from get_omni_invocation.php");
	      if(rt.code != 0) {
		  setTimeout(function() {
			  monitor_reservation(orig_rt, event);
		      }, 5000);
	      } else {
		  //		  console.log("OMNI Invocation Complete");
		  event.code = 0;
		  jacks_editor_app_input.trigger(event.name, event);
		  // console.log("rt = " + rt);
		  invocation_details_url = "sliceresource.php?" + url_args;
		  msg = "Reservation details " + "<a href='" + 
		      invocation_details_url + "'>Here</a>";;
		  jacks_editor.updateStatus(msg);
		  if(jacks_editor.jacks_viewer != null) {
		      jacks_editor.jacks_viewer.updateStatus(msg);
		  }
	      }
	  })
    .fail(function(xhr, ts, et) {
	    console.log("Fail return from get_omni_invocation.php");
	});
}

// Handle the save request to save an rspec to local file system
function ep_on_download(event) {
    debug("ep_on_download");
    var rspec = event.rspec;
    var rspec_download_url = "rspecdownload.php?rspec=" + rspec;
    window.location.replace(rspec_download_url);
    jacks_editor_app_input.trigger(event.name,  {code : 0 });
};





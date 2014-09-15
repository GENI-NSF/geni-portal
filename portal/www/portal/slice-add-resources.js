//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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

/* do things when RSpec is uploaded by user (i.e. not chosen from list) */
function fileupload_onchange()
{
    var user_rspec_file_input = document.getElementById("file_select");
    var user_rspec_file = user_rspec_file_input.files[0];
    validate_rspec_file(user_rspec_file, true, handle_validation_results);

    // change RSpec dropdown menu back to 'Choose RSpec'
    clear_other_inputs('#file_select');


}

function validate_rspec_file(rspec, is_filename, callback)
{
    var formData = new FormData();
    if(is_filename) {
	formData.append("user_rspec_file", rspec);
    } else {
	formData.append("user_rspec_raw", rspec);
    }
    var client = new XMLHttpRequest();
    client.open("post", "rspecuploadparser.php", true);
    client.addEventListener("load", callback);
    client.send(formData);
}

function handle_validation_results_no_jacks(evt)
{
    handle_validation_results_base(evt, false);
}

function handle_validation_results(evt)
{
    handle_validation_results_base(evt, true);
}

/* once uploaded, change info */
function handle_validation_results_base(evt, update_jacks)
{
    var client = evt.target;
    if (client.readyState == 4 && client.status == 200)
    {
	// parse JSON message
	var jsonResponse = JSON.parse(client.responseText);

	handle_rspec_validation_results(jsonResponse);

	handle_rspec_update(jsonResponse, jsonResponse.rspec, update_jacks);

    }
}

function handle_rspec_validation_results(jsonResponse)
{
    var agg_chooser_val = $('#agg_chooser').val();
    var aggregate_chosen = agg_chooser_val != "";

    // if valid, change around attributes depending on stitch/bound
    if(jsonResponse.valid) {
	$('#valid_rspec').val('1');
	if(jsonResponse.stitch) {
	    set_attributes_for_stitching();
	    enable_reserve_resources();
	}
	else if(jsonResponse.bound) {
	    set_attributes_for_bound();
	    enable_reserve_resources();
	}
	else {
	    set_attributes_for_unbound();
	    if (aggregate_chosen) {
		enable_reserve_resources();
	    } else {
		disable_reserve_resources();
	    }
	}
    }
    // if invalid, set back to unbound
    else {
	$('#valid_rspec').val('0');
	set_attributes_for_unbound();
	disable_reserve_resources();
    }
        
}

function handle_rspec_update(jsonResponse, rspec, updateJacks)
{
    $('#current_rspec_text').val(rspec);

    if(!jacksEditorApp_isHidden && updateJacks) {
	jacksEditorApp.jacksInput.trigger('change-topology',
					  [{rspec: rspec}]);
    }

    // Update the message string
    // Update the reserve resources button
    console.log("handle_rspec_update: " + jsonResponse);
    $('#rspec_status_text').html(jsonResponse.message);

}

/* enable/disable 'Reserve Resources' button */
function disable_reserve_resources()
{
    $('#rspec_submit_button').attr('disabled', 'disabled');
}

function enable_reserve_resources()
{
    $('#rspec_submit_button').removeAttr('disabled');
}

/* Functions to do things when stitching/bound RSpecs are selected/deselected */

/* do things when stitchable (and therefore bound) Rspec */
function set_attributes_for_stitching()
{
    // disable AMs
    $('#agg_chooser').val('Stitchable RSpec');
    $('#agg_chooser').attr('disabled', 'disabled');
    $('#aggregate_message').html("You selected a <b>stitchable</b> RSpec, so aggregates will be specified from the RSpec.");
    $('#bound_rspec').val('1');
    $('#stitch_rspec').val('1');
}

/* do things when bound but not stitchable RSpec */
function set_attributes_for_bound()
{
    $('#agg_chooser').val('Bound RSpec');
    $('#agg_chooser').attr('disabled', 'disabled');
    // FIXME: Comment these 2 lines out when the above 2 lines are uncommented
    //    $('#agg_chooser').val(am_on_page_load);
    //    $('#agg_chooser').removeAttr('disabled');
    $('#aggregate_message').html("You selected a <b>bound</b> RSpec.");
    $('#bound_rspec').val('1');
    $('#stitch_rspec').val('0');
}

/* do things when unbound RSpec */
function set_attributes_for_unbound()
{
    $('#agg_chooser').val(am_on_page_load);
    $('#agg_chooser').removeAttr('disabled');
    $('#aggregate_message').html("");
    $('#bound_rspec').val('0');
    $('#stitch_rspec').val('0');
}

/* save previously chosen AM when AM changes */
function am_onchange()
{
    am_on_page_load = $('#agg_chooser').val();
    bound_rspec = $('#bound_rspec').val();
    valid_rspec = $('#valid_rspec').val();
    current_rspec = $('#current_rspec_text').val();

    if(current_rspec != "" && valid_rspec != "0"  &&
       (am_on_page_load != "" || bound_rspec != "0")) {
	enable_reserve_resources();
    } else {
	disable_reserve_resources();
    }

}

/* do things when RSpec is chosen from list (i.e. not uploaded) */
function rspec_onchange()
{
    var rspec_opt = $('#rspec_select').val();

    //    console.log("IN RSPEC_ON_CHANGE");
    //    if (rspec_opt == 'upload') {
    //        $('#paste_rspec').hide(500);
    ///        $('#upload_rspec').show(500);
    //    } else if (rspec_opt == 'paste') {
    //        $('#paste_rspec').show(500);
    //        $('#upload_rspec').hide(500);
    //    } else {
    //        $('#paste_rspec').hide(500);
    //        $('#upload_rspec').hide(500);
    //    }

    var agg_chooser = $('#agg_chooser');
    var rspec_chooser = $('#rspec_select');

    var selected_index = document.getElementById('rspec_select').selectedIndex;
    var selected_element = rspec_chooser.children()[selected_index];

    rspec_id = selected_element.value;
    $.get("rspecview.php", {id : rspec_id},
	  function(rt, st, xhr) {
	      var rspec = xhr.responseText;
	      validate_rspec_file(rspec, false, handle_validation_results);
	  })
	.fail(function(xhr, ts, et) {
		console.log("Error loading rspec : " + rspec_id);
	    })

    // Clear the "rspec_selection" file chooser
    clear_other_inputs('#rspec_select');
}

var jacksEditorApp_isHidden = true;
var jacksEditorApp = null;

/* Make sure Jacks Editor App exists */
function assureJacksEditorApp() {
    if (jacksEditorApp == null) {
	jacksEditorApp = new JacksEditorApp('#jacks-editor-pane',
					'#jacks-editor-status',
					'#jacks-editor-buttons',
					jacks_slice_ams,
					jacks_all_ams,
					jacks_all_rspecs,
					jacks_slice_info,
					jacks_user_info,
					jacks_enable_buttons,
				    jacks_editor_app_ready,
				    jacks_fetch_topology_callback);
    }
}

function jacks_editor_app_ready(je, je_input, je_output) {
  console.log("JEAR : JacksEditorApp ready");
  $('#rspec_status_text').text("");
};

// The callback when we've received the current rspec from Jacks
// If downloading, download in place
// Otherwise, treat as a new RSpec
function jacks_fetch_topology_callback(rspecs) {
    //  console.log("RSPECS = " + rspecs + " " + rspecs.length);
  var rspec = rspecs[0].rspec;
  if(jacksEditorApp.downloadingRspec) {
      var rspec_download_url = "rspecdownload.php?rspec=" + rspec;
      window.location.replace(rspec_download_url);
      jacksEditorApp.downloadingRspec = false;
  } else {
      // Handle new rspec but don't update Jacks (we just got it from Jacks)
      validate_rspec_file(rspec, false, handle_validation_results_no_jacks);
  }
}


/** Hide/Show the editor buttons **/
/* And hide the jacks editor itself **/
function do_hide_editor()
{
    if(jacksEditorApp == null) return;
    $('#show_jacks_editor_button').show();
    $('#hide_jacks_editor_button').hide();
    do_hide_editor_elements();
}

function do_hide_editor_elements()
{
    console.log("Hiding editor");
    $('#jacks-editor-status').hide();
    $('#jacks-editor-pane').hide();
    $('#jacks-editor-buttons').hide();
    $('#jacks-editor-app').hide();
    $('#grab_editor_topology_button').attr('disabled', true);
    jacksEditorApp_isHidden = true;
}

/** If the editor doesn't exist, create it before showing */
function do_show_editor()
{
    assureJacksEditorApp();
    $('#show_jacks_editor_button').hide();
    $('#hide_jacks_editor_button').show();
    console.log("Showing editor");
    do_show_editor_elements();
}

function do_show_editor_elements()
{
    //    $('#jacks-editor-status').show();
    $('#jacks-editor-pane').show();
    //    $('#jacks-editor-buttons').show();
    $('#jacks-editor-app').show();
    $('#grab_editor_topology_button').removeAttr('disabled');
    jacksEditorApp_isHidden = false;
    rspec = $('#current_rspec_text').val();
    jacksEditorApp.jacksInput.trigger('change-topology',
				      [{rspec : rspec}]);
}

function grab_paste_onchange()
{
    console.log("Grabbing paste");
    var rspec = $('#paste_select').val();
    validate_rspec_file(rspec, false, handle_validation_results);
    clear_other_inputs('#paste_select');
}

function urlupload_onchange()
{
    console.log("URLUPLOAD");
    var url = $('#url_select').val();
    $.get("upload-file.php", 
	  {url : url}, 
              function(rt, st, xhr) {
		  var rspec = xhr.responseText;
		  validate_rspec_file(rspec, false, handle_validation_results);
              })
    .fail(function(xhr, ts, et) {
	    console.log("Failed uploading URL: " + url);
	});
    clear_other_inputs("#url_select");
}

// Clear all other inputs other than most recent one
function clear_other_inputs(current_input)
{
    if(current_input != '#rspec_select')
	$('#rspec_select').val('0');
    if(current_input != '#file_select')
	$('#file_select').val(null);
    if(current_input != '#url_select')
	$('#url_select').val("");
    if(current_input != '#paste_select')
	$('#paste_select').val('');
}

// Download current rspec from Jacks to browser's Download directory
function do_rspec_download()
{
    jacksEditorApp.downloadingRspec = true;
    jacksEditorApp.jacksInput.trigger('fetch-topology');
}

// Grab current topology from Jacks editor
function do_grab_editor_topology()
{
    jacksEditorApp.downloadingRspec = false;
    jacksEditorApp.jacksInput.trigger('fetch-topology');
}

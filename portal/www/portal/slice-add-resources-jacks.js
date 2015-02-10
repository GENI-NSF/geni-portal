//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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
    //    var agg_chooser_val = $('#agg_chooser').val();
    //    var aggregate_chosen = agg_chooser_val != "";

    // if valid, change around attributes depending on stitch/bound
    if(jsonResponse.valid) {
	$('#valid_rspec').val('1');
	if(jsonResponse.partially_bound) {
	    set_attributes_for_partially_bound();
	    disable_reserve_resources();
	} 
	else if(jsonResponse.stitch) {
	    set_attributes_for_stitching();
	    enable_reserve_resources();
	}
	else if(jsonResponse.bound) {
	    set_attributes_for_bound();
	    enable_reserve_resources();
	}
	else {
	    set_attributes_for_unbound();
	    disable_reserve_resources();
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
    if(jsonResponse.valid) {
	$('#current_rspec_text').val(rspec);

	if(!jacksEditorApp_isHidden && updateJacks) {
	    set_jacks_topology(rspec);
	}
    }

    // enable the download button
    $('#download_rspec_button').removeAttr('disabled');

    // Update the message string
    // Update the reserve resources button
    //    console.log("handle_rspec_update: " + jsonResponse);
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
    //    $('#agg_chooser').val('Stitchable RSpec');
    //    $('#agg_chooser').attr('disabled', 'disabled');
    //    $('#aggregate_message').html("You selected a <b>stitchable</b> RSpec, so aggregates will be specified from the RSpec.");
    $('#bound_rspec').val('1');
    $('#stitch_rspec').val('1');
    $('#partially_bound_notice').attr('hidden', 'hidden');
}

/* do things when bound but not stitchable RSpec */
function set_attributes_for_bound()
{
    //    $('#agg_chooser').val('Bound RSpec');
    //    $('#agg_chooser').attr('disabled', 'disabled');
    // FIXME: Comment these 2 lines out when the above 2 lines are uncommented
    //    $('#agg_chooser').val(am_on_page_load);
    //    $('#agg_chooser').removeAttr('disabled');
    //    $('#aggregate_message').html("You selected a <b>bound</b> RSpec.");
    $('#bound_rspec').val('1');
    $('#stitch_rspec').val('0');
    $('#partially_bound_notice').attr('hidden', 'hidden');
}

/* do things when partially bound RSpec */
function set_attributes_for_partially_bound()
{
    //    $('#aggregate_message').html("You selected a <b>partially bound</b> RSpec.");
    //    $('#agg_chooser').attr('disabled', 'disabled');
    $('#partially_bound_rspec').val('1');
    $('#bound_rspec').val('0');
    $('#stitch_rspec').val('0');
    $('#partially_bound_notice').removeAttr('hidden');
}

/* do things when unbound RSpec */
function set_attributes_for_unbound()
{
    //    $('#agg_chooser').val(am_on_page_load);
    //    $('#agg_chooser').removeAttr('disabled');
    $('#aggregate_message').html("");
    $('#bound_rspec').val('0');
    $('#stitch_rspec').val('0');
    $('#partially_bound_notice').attr('hidden', 'hidden');
}

/* save previously chosen AM when AM changes */
function am_onchange()
{
    //    am_on_page_load = $('#agg_chooser').val();
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

    //    var agg_chooser = $('#agg_chooser');
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

    // If RSPEC is unchosen, disable reserve resources
    if (rspec_opt == "") {
	disable_reserve_resources();
    }
}

var jacksEditorApp_isHidden = true;
var jacksEditorApp = null;

/* Make sure Jacks Editor App exists */
function assureJacksEditorApp(initial_rspec) {
    if (jacksEditorApp == null) {
	canvasOptions=jacksContext.canvasOptions;
	constraints = jacksContext.constraints;
	// If no constraints provided, use defaults and construct aggregate array
	if (canvasOptions == null) {
	    canvasOptions = getDefaultCanvasOptions(); // Get default constraints
	    var aggregates = [];
	    $.each(jacks_all_ams, function(index, value) {
		    am_id = index;
		    agg_id = value.urn;
		    agg_name = value.name;
		    aggregates.push({id:agg_id, name: agg_name});
		});
	    canvasOptions.aggregates = aggregates;
	}

	jacksEditorApp = new JacksEditorApp('#jacks-editor-pane',
					'#jacks-editor-status',
					'#jacks-editor-buttons',
					jacks_slice_ams,
					jacks_all_ams,
					jacks_all_rspecs,
					jacks_slice_info,
					jacks_user_info,
					jacks_enable_buttons,
					canvasOptions,
					constraints,
					jacks_editor_app_ready,
				        jacks_fetch_topology_callback,
					jacks_modified_topology_callback);
	jacksEditorApp.initialRSpec = initial_rspec;
    }
}

function jacks_editor_app_ready(je, je_input, je_output) {
    //  console.log("JEAR : JacksEditorApp ready");
  $('#rspec_status_text').text("");

  if (je.initialRSpec != null) {
      set_jacks_topology(je.initialRSpec);
  }
};

// The callback when we've received the current rspec from Jacks
// If downloading, download in place
// If submitting, call validateSubmit
// Otherwise, treat as a new RSpec
function jacks_fetch_topology_callback(rspecs) {
    //  console.log("RSPECS = " + rspecs + " " + rspecs.length);
  var rspec = rspecs[0].rspec;
  if(jacksEditorApp.downloadingRspec) {
      jacksEditorApp.downloadingRspec = false;
      $.post("saverspectoserver.php", {rspec : rspec},
	    function(rt, st, xhr) {
		//		console.log("SUCCESS");
		var replace_url = "rspecdownload.php?tempfile=" + rt + "&slice_name=" + jacksEditorApp.sliceName;
		window.location.replace(replace_url);
	    })
	  .fail(function(xhr, ts, et) {
		  //		  console.log("FAILURE");
              alert("An error occurred. Please notify portal-help@geni.net.");
	      });
  } else if (jacksEditorApp.submittingRspec) {
      jacksEditorApp.submittingRspec = false;
      $('#current_rspec_text').val(rspec);
      validateSubmit();
  } else {
      // Handle new rspec but don't update Jacks (we just got it from Jacks)
      validate_rspec_file(rspec, false, handle_validation_results_no_jacks);
      clear_other_inputs("");
  }
}

// The callback from Jacks when topology has been modified
function jacks_modified_topology_callback(data)
{
    jacksEditorApp.currentTopology = data;

    //    console.log("MOD = " + data);
    rspec = data.rspec;

    $('#current_rspec_text').val(rspec);

    // id, client_id, aggregate_id, site_name
    nodes = data.nodes;

    // id, client_id
    links = data.links;

    // id, name, urn 
    // Note: site.name = node.site_name, node.aggregate_id = site.urn
    sites = data.sites;

    // RSpec is bound IFF every node has an aggregate_id, or every site has a urn
    // call validate_rspec_file if we've changed from bound 
    // to either partially bound or bound
    validate_rspec_file(rspec, false, handle_validation_results_no_jacks);
}


/** Hide/Show the editor buttons **/

// With saving editor state
function do_hide_editor()
{
    do_hide_editor_internal(true);
}

// Without saving editor state
function do_discard_editor()
{
    do_hide_editor_internal(false);
}

function do_hide_editor_internal(grab_topology)
{
    if(jacksEditorApp == null) return;

    if (grab_topology) {
	// Set new value of current RSPEC
	do_grab_editor_topology();
    } else {
	// Revert to previous value of current RSPEC
	var rspec = $('#current_rspec_text').val();
	set_jacks_topology(rspec);
    }

    $('#show_jacks_editor_button').show();
    $('#hide_jacks_editor_button').hide();
    $('#discard_jacks_editor_button').hide();
    do_hide_editor_elements();
}


/* And hide the jacks editor itself **/
function do_hide_editor_elements()
{
    // console.log("Hiding editor");
    $('#jacks-editor-pane').hide();
    $('#jacks-editor-status').hide();
    $('#jacks-editor-buttons').hide();
    $('#jacks-editor-app').hide();
    $('#grab_editor_topology_button').attr('disabled', true);
    jacksEditorApp_isHidden = true;
}

/** If the editor doesn't exist, create it before showing */
function do_show_editor(initial_rspec)
{
    if (initial_rspec === undefined) initial_rspec = null;

    assureJacksEditorApp(initial_rspec);
    $('#show_jacks_editor_button').hide();
    $('#hide_jacks_editor_button').show();
    $('#discard_jacks_editor_button').show();
    // console.log("Showing editor");
    do_show_editor_elements();
}

function do_show_editor_elements()
{
    $('#jacks-editor-status').hide();
    $('#jacks-editor-buttons').hide();
    //    $('#jacks-editor-status').show();
    $('#jacks-editor-pane').show();
    //    $('#jacks-editor-buttons').show();
    $('#jacks-editor-app').show();
    $('#grab_editor_topology_button').removeAttr('disabled');
    jacksEditorApp_isHidden = false;
}

function set_jacks_topology(rspec)
{
    if(rspec == "") rspec = "<rspec></rspec>";
    jacksEditorApp.jacksInput.trigger('change-topology',
				      [{rspec: rspec}]);
}


function grab_paste_onchange()
{
    // console.log("Grabbing paste");
    var rspec = $('#paste_select').val();
    validate_rspec_file(rspec, false, handle_validation_results);
    clear_other_inputs('#paste_select');
}

function urlupload_onchange()
{
    // console.log("URLUPLOAD");
    var url = $('#url_select').val().trim();
    $.get("upload-file.php", 
	  {url : url}, 
              function(rt, st, xhr) {
		  var rspec = xhr.responseText;
		  validate_rspec_file(rspec, false, handle_validation_results);
              })
    .fail(function(xhr, ts, et) {
	    //	    console.log("Failed uploading URL: " + url);
	    jsonResponse = {"valid" : false, "message" : "<b style='color:red;'>ERROR: </b> " + et};
	    handle_rspec_update(jsonResponse, "", false);
	});
    clear_other_inputs("#url_select");
}

// Clear all other inputs other than most recent one
function clear_other_inputs(current_input)
{
    if(current_input != '#paste_select')
	$('#paste_select').val('');
    if(current_input != '#rspec_select')
	$('#rspec_select').val('0');
    if(current_input != '#file_select')
	$('#file_select').val(null);
    if(current_input != '#url_select')
	$('#url_select').val("");
}

// Download current rspec from Jacks to browser's Download directory
function do_rspec_download()
{
    if (jacksEditorApp == null) {
	var rspec = $('#current_rspec_text').val();
	var rspec_download_url = "rspecdownload.php?rspec=" + rspec;
	window.location.replace(rspec_download_url);
    } else {
	jacksEditorApp.downloadingRspec = true;
	jacksEditorApp.submittingRspec = false;
	jacksEditorApp.jacksInput.trigger('fetch-topology');
    }
}

// Invoke a new full-size editor in a new window
// Pass along current rspec as a POST argument
// by creating a form at the end of the document body (not nested)
// If true, return to slice-add-resources-jacks.js
// with current topology
function do_editor_expand(restore)
{
    var current_rspec = jacksEditorApp.currentTopology.rspec;
    $("#current_editor_rspec").val(rspec);
    var editor_url = "jacks-editor-app-expanded.php?slice_id=" + 
	jacks_slice_id;

    if (restore == true)
	editor_url = "slice-add-resources-jacks.php?slice_id=" + 
	    jacks_slice_id;

    var $form=$(document.createElement('form')).css({display:'none'}).attr("method","POST").attr("action",editor_url);
    var $input=$(document.createElement('input')).attr('name','current_editor_rspec').val(current_rspec);
    $form.append($input);
    $("body").append($form);
    $form.submit();
}

// Take every selected node and make a copy of it, sending a new
// rspec containing these copied nodes to Jacks
function do_selection_duplicate(include_links)
{
    var all_node_names = [];
    var num_topology_nodes = jacksEditorApp.currentTopology.nodes.length;
    for(var i = 0; i < num_topology_nodes; i++) {
	var node_name = jacksEditorApp.currentTopology.nodes[i].client_id;
	all_node_names.push(node_name);
    }

    var added_nodes = false;
    var rspec = $('#current_rspec_text').val();
    var doc = jQuery.parseXML(rspec);
    var rspec_root = $(doc).find('rspec')[0];
    var num_selected_nodes = jacksEditorApp.selectedNodes.length;

    for(var i = 0; i < num_selected_nodes; i++) {

	added_nodes = true;

	var selected_node = jacksEditorApp.selectedNodes[i];
	var selected_node_id = selected_node.key;
	var selected_node_name = selected_node.name;
	var selected_node_component_manager_id = "";

	// Find the node object in the current topology
	var current_topology = jacksEditorApp.currentTopology;
	var current_topology_nodes = current_topology['nodes'];
	var selected_topology_node = null;
	var num_current_topology_nodes = current_topology_nodes.length;
	for(var j = 0; j < num_current_topology_nodes; j++) {
	    var topology_node = current_topology_nodes[j];
	    if (topology_node.id == selected_node_id) {
		selected_topology_node = topology_node;
		break;
	    }
	}

	// Find the site object in the current topology to which the node is assigned
	var site_name = selected_topology_node.site_name;
	var selected_topology_node_site = null;
	var current_topology_sites  = current_topology['sites'];
	var num_current_topology_sites = current_topology_sites.length;
	for (var j = 0; j < num_current_topology_sites; j++) {
	    var topology_site = current_topology_sites[j];
	    if (topology_site.name == site_name) {
		selected_topology_node_site = topology_site;
	    }
	}

	// New name is from old name plus "-$UNIQUE_COUNTER", 
	// skipping over any existing nodes with that manufactured name
	var new_node_name = construct_new_node_name(selected_node_name, all_node_names);
	all_node_names.push(new_node_name);

	var rspec_nodes = $(rspec_root).find('node');
	var num_rspec_nodes = rspec_nodes.length;
	for(var j = 0; j < num_rspec_nodes; j++) {
	    var rspec_node = rspec_nodes[j];
	    if ($(rspec_node).attr('client_id') == selected_node_name) {
		var site_elements = $(rspec_node).find('site');
		var site_element = null;
		if (site_elements.length > 0) site_element = site_elements[0];
		if ($(rspec_node).attr('component_manager_id') == selected_node_component_manager_id ||
		    (site_element != null && $(site_element).attr('id') == selected_topology_node_site.id)) {
		    selected_rspec_node = rspec_node;
		    break;
		}
	    }
	}


	// Make clone of node, change its client_id and add to list of nodes
	var cloned_rspec_node = $(selected_rspec_node).clone()[0];
	$(cloned_rspec_node).attr('client_id', new_node_name);

	// Remove any IP addresses that were cloned
	$(cloned_rspec_node).find('ip').remove();

	// Add new node into rspec
	rspec_root.appendChild(cloned_rspec_node);

	if (include_links) {
	    // If we're including links, we need to rename all the interfaces on the cloned node
	    // And add them to the link associated with the original node's interface
	    var interfaces = $(cloned_rspec_node).find('interface');
	    var num_interfaces = interfaces.length;

	    var links = $(doc).find('link');
	    var num_links = links.length;

	    // For each interface, find the link to which it belongs
	    // Rename the interface and then add an interface ref to the link
	    for(var iface_index = 0; iface_index < num_interfaces; iface_index++) {
		var interface = interfaces[iface_index];
		var interface_name = $(interface).attr('client_id');

		var link_for_interface = null;
		for(var link_index = 0; link_index < num_links; link_index++) {
		    var link = links[link_index];
		    var interface_refs = $(link).find('interface_ref');
		    var num_interface_refs = interface_refs.length;
		    for(var iface_ref_index = 0; iface_ref_index < num_interface_refs; iface_ref_index++) {
			var interface_ref = interface_refs[iface_ref_index];
			if ($(interface_ref).attr('client_id') == interface_name) {
			    link_for_interface = link;
			    break;
			}
		    }
		}

		if (link_for_interface != null) {
		    // Make a new name for the interface
		    var new_interface_name = new_node_name + ":if" + 
			iface_index;

		    // Add a new interface ref to the link
		    var new_interface_ref = doc.createElement('interface_ref');
		    $(new_interface_ref).attr('client_id', new_interface_name);
		    link_for_interface.appendChild(new_interface_ref);

		    // Rename the interface
		    $(interface).attr('client_id', new_interface_name);
		}
	    }
	    
	} else {
	    // If we're not including links, we need to eliminate the interfaces from the cloned node
	    $(cloned_rspec_node).find('interface').remove();
	}
    }

    if (added_nodes) {
	var new_rspec = (new XMLSerializer()).serializeToString(doc);
	// Remove xmlns="" which Firefox puts into new elements 
	// but Jacks can't handle
	cleaned_rspec = new_rspec.replace(/xmlns=""/g, '');
	set_jacks_topology(cleaned_rspec);
    }

}

// Construct a new node name by appending -X to the existing one
// Increase jacksEditorApp.nodeCounter until we find an X for which there is no such node
function construct_new_node_name(orig_node_name, all_node_names) {
    var new_node_name = "";
    while(true)  {
	new_node_name = orig_node_name + "-" + jacksEditorApp.nodeCounter;
	jacksEditorApp.nodeCounter = jacksEditorApp.nodeCounter + 1;
	if (all_node_names.indexOf(new_node_name) == -1) break;
    }
    return new_node_name;

}

// Perform auto IP assignment on topologies
// Go through all links. Label that 10.10.i.*;
//   Go through all interfaces on that link. Label that 10.10.i.j;
function do_auto_ip_assignment()
{
    var rspec = $('#current_rspec_text').val();
    var doc = jQuery.parseXML(rspec);
    var links = $(doc).find('link');
    var num_links = links.length;
    var changed_ips = (links.length > 0);

    if (num_links > 255) {
    	alert("Can't perform AUTO_IP assignment with > 255 links ");
    	return;
    }

    // Make a hash table of all interfaces by client_id
    var interfaces_by_client_id = [];
    var interfaces = $(doc).find('interface');
    var num_interfaces = interfaces.length;

    for(var iface_index = 0; iface_index < num_interfaces; iface_index++) {
	var interface = interfaces[iface_index];
	var client_id = interface.getAttribute('client_id');
	interfaces_by_client_id[client_id] = interface;
    }

    for(var link_index = 0; link_index < num_links; link_index++) {
	var iface_ref_elts = links[link_index].getElementsByTagName('interface_ref');
	var num_iface_ref_elts = iface_ref_elts.length;

	if (num_iface_ref_elts > 255) {
	    alert("Can't perform AUTO_IP assignment with " + 
		  "> 255 interfaces per link ");
	    return;
	}


	for(var iface_index = 0; iface_index < num_iface_ref_elts; iface_index++) {
	    var iface_name = iface_ref_elts[iface_index].getAttribute('client_id');
	    var interface = interfaces_by_client_id[iface_name];
	    var ip_elt = doc.createElement('ip');
	    var address = "10.10." + (link_index + 1) + "." + (iface_index + 1);
	    var netmask = "255.255.255.0";
	    ip_elt.setAttribute('address', address);
	    ip_elt.setAttribute('netmask', netmask);
	    interface.appendChild(ip_elt);
	}
    }

    if (changed_ips) {
	var new_rspec = (new XMLSerializer()).serializeToString(doc);
	// Remove xmlns="" which Firefox puts into new elements 
	// but Jacks can't handle
	cleaned_rspec = new_rspec.replace(/xmlns=""/g, '');
	set_jacks_topology(cleaned_rspec);
    }
}

// Grab current topology from Jacks editor and submit if valid
function do_grab_editor_topology_and_submit()
{
    jacksEditorApp.downloadingRspec = false;
    jacksEditorApp.submittingRspec = true;
    jacksEditorApp.jacksInput.trigger('fetch-topology');
}

// Grab current topology from Jacks editor
function do_grab_editor_topology()
{
    jacksEditorApp.downloadingRspec = false;
    jacksEditorApp.submittingRspec = false;
    jacksEditorApp.jacksInput.trigger('fetch-topology');
}

// Routine to hide/show the various rspec choice mechanisms
function enable_rspec_selection_mode_portal() { enable_rspec_selection_mode("PORTAL"); }
function enable_rspec_selection_mode_file() { enable_rspec_selection_mode("FILE"); }
function enable_rspec_selection_mode_url() { enable_rspec_selection_mode("URL"); }
function enable_rspec_selection_mode_textbox() { enable_rspec_selection_mode("TEXTBOX"); }
function enable_rspec_selection_mode_jacks() { enable_rspec_selection_mode("JACKS"); }

function enable_rspec_selection_mode(selected_mode)
{
    if(selected_mode == "PORTAL")
	$('#rspec_portal_row').show();
    else if (selected_mode == "FILE")
	$('#rspec_file_row').show();
    else if (selected_mode == "URL")
	$('#rspec_url_row').show();
    else if (selected_mode == "TEXTBOX")
	$('#rspec_paste_row').show();
    else if (selected_mode == "JACKS")
	$('#rspec_jacks_row').show();

    if(selected_mode != "PORTAL")
	$('#rspec_portal_row').hide();
    if (selected_mode != "FILE")
	$('#rspec_file_row').hide();
    if (selected_mode != "URL")
	$('#rspec_url_row').hide();
    if (selected_mode != "TEXTBOX")
	$('#rspec_paste_row').hide();
    if (selected_mode != "JACKS")
	$('#rspec_jacks_row').hide();

}

function validateSubmit()
{
  f1 = document.getElementById("f1");
  rspec = document.getElementById("rspec_select");
  //  am = document.getElementById("agg_chooser");
  rspec2 = document.getElementById("file_select");

  current_rspec_text = $('#current_rspec_text').val();
  is_bound = $('#bound_rspec').val();

  //  console.log("validateSubmit.rspec = " + current_rspec_text);
  //  console.log("validateSubmit.bound = " + is_bound);
  
  if ((current_rspec_text != '') && is_bound) {
    f1.submit();
    return true;
  } else if (current_rspec_text != '') {
    alert("Please select an Aggregate.");
    return false;
  } else {
    alert ("Please select a Resource Specification (RSpec).");
    return false;
  }
}


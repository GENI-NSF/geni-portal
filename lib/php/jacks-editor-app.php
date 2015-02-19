<?php
//----------------------------------------------------------------------
// Copyright (c) 2014-2015 Raytheon BBN Technologies
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

function build_jacks_editor()
{
  $output = "<script src='jacks-editor-app.js'></script>";
  $output .= "<div id='jacks-editor-status'><p>Starting Jacks Editor...</p></div>";
  $output .= "<div id='jacks-editor-pane' class='jacks'></div>";

  $output .= "<div id='jacks-editor-buttons'>";
  $output .= "</div>";

  return $output;
}

function setup_jacks_editor_slice_context()
{

  global $user;
  global $slice_id;
  global $slice_name;
  global $STANDARD_JACKS_CONTEXT_LOCATION;
  global $jacksContext;
  global $slice_ams;
  global $slice_urn;
  global $slice_expiration;
  global $all_ams;
  global $all_compute_ams;
  global $all_rspecs;
  global $am_ids;

  $STANDARD_JACKS_CONTEXT_LOCATION = "/etc/geni-ch/jacks-context.json";

  $jacksContext = array("canvasOptions" => null, "constraints" => array());
  if (file_exists($STANDARD_JACKS_CONTEXT_LOCATION)) {
    $jacksContext = json_decode(file_get_contents($STANDARD_JACKS_CONTEXT_LOCATION));
  } 

  if (! isset($all_ams)) {
    $am_list = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
    $all_ams = array();
    $all_compute_ams = array();
    foreach ($am_list as $am) 
      {
	$single_am = array();
	$service_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
	$single_am['name'] = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
	$single_am['url'] = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
	$single_am['urn'] = $am[SR_TABLE_FIELDNAME::SERVICE_URN];
	$all_ams[$service_id] = $single_am;

	if (array_key_exists(SERVICE_ATTRIBUTE_TAG, $am) &&
	    array_key_exists(SERVICE_ATTRIBUTE_AM_CAT, 
			     $am[SERVICE_ATTRIBUTE_TAG]) && 
	    strpos(
		   $am[SERVICE_ATTRIBUTE_TAG][SERVICE_ATTRIBUTE_AM_CAT],
		   SERVICE_ATTRIBUTE_COMPUTE_CAT) 
	    !== FALSE) 
	  {
	    $all_compute_ams[$service_id] = $single_am;
	  }
      }   
  }
}

function cmp2($a,$b) {
  return strcmp(strtolower($a['name']),strtolower($b['name']));
}

function show_rspec_chooser($user) {
  $all_rmd = fetchRSpecMetaData($user);
  usort($all_rmd,"cmp2");
  print "<select name=\"rspec_id\" id=\"rspec_select\""
    . " onchange=\"rspec_onchange()\""
    . ">\n";
  echo '<option value="" title="Choose RSpec" selected="selected" bound="0" stitch="0">Choose RSpec...</option>';
  echo '<option value="PRIVATE" disabled>---Private RSpecs---</option>';
  foreach ($all_rmd as $rmd) {
    if ($rmd['visibility']==="private") {
      $rid = $rmd['id'];
      $rname = $rmd['name'];
      $rdesc = $rmd['description'];
      //    error_log("BOUND = " . $rmd['bound']);
      $bound = 0;
      $stitch = 0;
      if ($rmd['bound'] == 't') {
        $bound = 1;
      }
      if ($rmd['stitch'] == 't') {
        $stitch = 1;
      }
      //    error_log("BOUND = " . $enable_agg_chooser);
      print "<option value='$rid' title='$rdesc' bound='$bound' stitch='$stitch'>$rname</option>\n";
    }
  }
  echo '<option value="PUBLIC" disabled>---Public RSpecs---</option>';
  foreach ($all_rmd as $rmd) {
    if ($rmd['visibility']==="public") {
      $rid = $rmd['id'];
      $rname = $rmd['name'];
      $rdesc = $rmd['description'];
      //    error_log("BOUND = " . $rmd['bound']);
      $bound = 0;
      $stitch = 0;
      if ($rmd['bound'] == 't') {
        $bound = 1;
      }
      if ($rmd['stitch'] == 't') {
        $stitch = 1;
      }
      //    error_log("BOUND = " . $enable_agg_chooser);
      print "<option value='$rid' title='$rdesc' bound='$bound' stitch='$stitch'>$rname</option>\n";
    }
  }
  
  //  print "<option value=\"paste\" title=\"Paste your own RSpec\">Paste</option>\n";
  //  print "<option value=\"upload\" title=\"Upload an RSpec\">Upload</option>\n";
  print "</select>\n";

 // print "<br>or <a href=\"rspecupload.php\">upload your own RSpec to the above list</a>.";
//  print " or <button onClick=\"window.location='rspecupload.php'\">";
//  print "upload your own RSpec</button>.";
  // RSpec entry area
  print '<span id="paste_rspec" style="display:none;vertical-align:top;">'
    . PHP_EOL;
  print '<label for="paste_rspec2">Resource Specification (RSpec):</label>' . PHP_EOL;
  print "<textarea id=\"paste_rspec2\" name=\"rspec\" rows=\"10\" cols=\"40\""
    //. " style=\"display: none;\""
    . "></textarea>\n";
  print '</span>' . PHP_EOL;

  // RSpec upload
  print '<span id="upload_rspec" style="display:none;">'
    . PHP_EOL;
  print '<label for="rspec_file">Resource Specification (RSpec) File:</label>' . PHP_EOL;
  print '<input type="file" name="rspec_file" id="rspec_file" />' . PHP_EOL;
  print '</span>' . PHP_EOL;
  
  //print "</p>";
}


function setup_jacks_editor_app_controls($enable_expansion)
{

  global $user;

  print '<form id="f1" action="createsliver.php" method="post" enctype="multipart/form-data">';

  print "<table>";

  print "<tr>";
  print "<th rowspan='3'>Choose RSpec</th>";
  print '<td>';
  print '<input type="radio" name="rspec_select" id="portal_radio_select" checked="checked" onclick="enable_rspec_selection_mode_portal()" />';
  print '<b class="rb_label">Portal</b>';

  print '<input type="radio" name="rspec_select" id="file_radio_select" onclick="enable_rspec_selection_mode_file()" />';
  print '<b class="rb_label">File</b>';

  print '<input type="radio" name="rspec_select" id="url_radio_select" onclick="enable_rspec_selection_mode_url()" />';
  print '<b class="rb_label">URL</b>';

  print '<input type="radio" name="rspec_select" id="textbox_radio_select" onclick="enable_rspec_selection_mode_textbox()" />';
  print '<b class="rb_label">Text Box</b>';

  print '</td></tr>';
  print '<tr id="rspec_portal_row" ><td><b>Select existing: </b>';
  show_rspec_chooser($user);
  print "</td></tr>";
  print '<tr id = "rspec_file_row" hidden="hidden"><td>';
  print "<b>Select from file: </b><input type='file' name='file_select' id='file_select' onchange='fileupload_onchange()'/>";
  // upload message: get this from slice-add-resources-jacks.js 
  // calling rspecuploadparser.php
  print "<div id='upload_message' style='display:block;'></div>";
  print "</td></tr>";
  print '<tr id="rspec_url_row" hidden="hidden"><td>';
  print "<b>Load from URL: </b>";
  print '<button type="button" name="url_grab_button" id="url_grab_button" onClick="urlupload_onchange()"  >Select</button>';
  print "<input type='input' name='url_select' id='url_select' onchange='urlupload_onchange()' />";
  print "</td></tr>";
  print '<tr id="rspec_paste_row" hidden="hidden"><td>';
  print '<b>Paste Rspec: </b>';
  print '<button type="button" name="paste_grab_button" id="paste_grab_button" onClick="grab_paste_onchange()">Select</button>';
  print '<textarea cols="60" rows="4" name="paste_select" id="paste_select"></textarea>';
  print "</td></tr>";
  print '<tr id="rspec_jacks_row" hidden="hidden"><td>';
  print '<b>Select from Editor: </b><button id="grab_editor_topology_button" type="button"onClick="do_grab_editor_topology()">Select</button>';
  print "</td></tr>";
  print "<tr><td>";
  print '<b><p id="rspec_status_text" /></b>';
  print "</td></tr>";
  
  print "<tr>";
  print "<th rowspan='1'>Save RSpec</th>";
  print "<td>";
  print "<b>Download RSpec: </b>";
  print '<button type="button" disabled="disabled" id="download_rspec_button" onClick="do_rspec_download()">Download</button>';
  print "</td></tr>";
  
  print "<tr>";
  print "<th rowspan='1'>Editor Ops</th>";
  print "<td>";
  if ($enable_expansion) {
    print '<button type="button" id="expand_editor_button" onClick="do_editor_expand(false)">Expand</button>';
  } else {
    print '<button type="button" id="restore_editor_button" onClick="do_editor_expand(true)">Back</button>';
  }
  print '<button type="button" id="duplicate_nodes_links_button" onClick="do_selection_duplicate(true)">Duplicate Nodes/Links</button>';
  print '<button type="button" id="duplicate_nodes_only_button" onClick="do_selection_duplicate(false)">Duplicate Nodes only</button>';
  print '<button type="button" id="auto_ip_button" onClick="do_auto_ip_assignment()">Auto IP</button>';
  print "</td></tr>";

  //print "<tr><th>Choose Aggregate</th><td>";
  //show_am_chooser();
  //print "</td></tr>";
  print "</table>";

}

function show_am_chooser() {
  $all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  print '<select name="am_id" id="agg_chooser" onchange="am_onchange()">\n';
  echo '<option value="" title = "Choose an Aggregate">Choose an Aggregate...</option>';
  foreach ($all_aggs as $agg) {
    $aggid = $agg['id'];
    $aggname = $agg['service_name'];
    $aggdesc = $agg['service_description'];
    print "<option value=\"$aggid\" title=\"$aggdesc\">$aggname</option>\n";
  }

  // FIXME: Bound RSpecs not implemented yet
  //  echo '<option disabled value="stitch" title="Stitchable RSpec">Stitchable RSpec</option>'; 
  //  echo '<option disabled value="bound" title="Bound RSpec">Bound RSpec</option>'; 
  print "</select>\n";
  
  // Display message to user about stitching/bound RSpecs
  print "<div id='aggregate_message' style='display:block;'></div>";
}


?>

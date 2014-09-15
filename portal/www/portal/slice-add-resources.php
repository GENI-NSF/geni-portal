<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologiesc
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

require_once("user.php");
require_once("header.php");
require_once('util.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");
require_once 'geni_syslog.php';

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

  echo '<option disabled value="stitch" title="Stitchable RSpec">Stitchable RSpec</option>'; 
  // FIXME: Bound RSpecs not implemented yet
  echo '<option disabled value="bound" title="Bound RSpec">Bound RSpec</option>'; 
  print "</select>\n";
  
  // Display message to user about stitching/bound RSpecs
  print "<div id='aggregate_message' style='display:block;'></div>";
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$mydir = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
add_js_script($mydir . '/slice-add-resources.js');

$slice_id = "None";
$slice_name = "None";
include("tool-lookupids.php");

if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

if (!$user->isAllowed(SA_ACTION::ADD_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}
$keys = $user->sshKeys();

show_header('GENI Portal: Add Resources to Slice', $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
?>
<script>
function validateSubmit()
{
  f1 = document.getElementById("f1");
  rspec = document.getElementById("rspec_select");
  am = document.getElementById("agg_chooser");
  rspec2 = document.getElementById("file_select");

  current_rspec_text = $('#current_rspec_tet').val();
  is_bound = $('#bound_rspec').val();

  console.log("validateSubmit.rspec = " + current_rspec_text);
  console.log("validateSubmit.bound = " + is_bound);
  
  if ((current_rspec_text != '') && (am.value || is_bound)) {
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
</script>

<?php include "tabs.js"; ?>

<?php
print "<h1>Add Resources to GENI Slice " . "<i>" . $slice_name . "</i>" . "</h1>\n";

// Put up a warning to upload SSH keys, if not done yet.
if (count($keys) == 0) {
  // No ssh keys are present.
  print "<p class='warn'>No ssh keys have been uploaded. ";
  print ("Please <button onClick=\"window.location='uploadsshkey.php'\">"
         . "Upload an SSH key</button> or <button " .
	 "onClick=\"window.location='generatesshkey.php'\">Generate and "
	 . "Download an SSH keypair</button> to enable logon to nodes.</p>\n");
}

?>

  <div id='tablist'>
		<ul class='tabs'>
			<li><a href='#addresources' title="Add Resources">Add Resources</a></li>
			<li style="border-right: none"><a href='#rspecs' title="Manage Resource Specifications">Manage RSpecs</a></li>
		</ul>
  </div>

<?php

  // BEGIN the tabContent class
  // this makes a fixed height box with scrolling for overflow
  echo "<div class='tabContent'>";

// BEGIN add resources tab
echo "<div id='addresources'>";
//print "<h2>Manage Resource Specifications (RSpecs)</h2>\n";
//print "<p><button onClick=\"window.location='rspecs.php'\">"
//    . "View Available RSpecs</button> \n";
//print "<button onClick=\"window.location='rspecupload.php'\">"
//    . "Upload New RSpec</button></p>\n";

print "<h2>Add Resources</h2>\n";
print "<p>To add resources you need to choose a Resource Specification file (RSpec).</p>";

if (! isset($all_ams)) {
  $am_list = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $all_ams = array();
  foreach ($am_list as $am) 
  {
    $single_am = array();
    $service_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
    $single_am['name'] = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
    $single_am['url'] = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
    $single_am['urn'] = $am[SR_TABLE_FIELDNAME::SERVICE_URN];
    $all_ams[$service_id] = $single_am;
  }   
}

$slice_ams = array();
$all_rspecs = fetchRSpecMetaData($user);

// JACKS-APP STUFF //
include("jacks-editor-app.php");
print "<table id='jacks-editor-app'>";
print "<tr><td><div id='jacks-editor-app-container'>";
print build_jacks_editor();
print "</div></td></tr></table>";
?>

<link rel="stylesheet" type="text/css" href="jacks-app.css" />
<link rel="stylesheet" type="text/css" href="jacks-editor-app.css" />
<link rel="stylesheet" type="text/css" href="slice-add-resources.css" />
<script src="//www.emulab.net/protogeni/jacks-stable/js/jacks"></script>
<script src="portal-jacks-editor-app.js"></script>
<script>

  var jacks_slice_ams = <?php echo json_encode($slice_ams) ?>;
  var jacks_all_ams = <?php echo json_encode($all_ams) ?>;
  var jacks_all_rspecs = <?php echo json_encode($all_rspecs) ?>;

  var jacks_slice_id = <?php echo json_encode($slice_id) ?>;
  var jacks_slice_name = <?php echo json_encode($slice_name) ?>;

  var jacks_slice_info = {slice_id : jacks_slice_id, 
			  slice_name : jacks_slice_name};

  var jacks_user_name = <?php echo json_encode($user->username) ?>;
  var jacks_user_urn = <?php echo json_encode($user->urn) ?>;
  var jacks_user_id = <?php echo json_encode($user->account_id) ?>;

  var jacks_user_info = {user_name : jacks_user_name,
			 user_urn : jacks_user_urn,
			 user_id : jacks_user_id};

  var jacks_enable_buttons = false;

  do_hide_editor_elements();

</script>

<?php

print '<form id="f1" action="createsliver.php" method="post" enctype="multipart/form-data">';

print "<table>";

print "<tr>";
print "<th rowspan='1' >Graphical Editor</th>";
print "<td>";
print '<button type="button" name="show_jacks_editor_button" id="show_jacks_editor_button" onClick="do_show_editor()">Show Editor</button>';
print '<button type="button" name="hide_jacks_editor_button" id="hide_jacks_editor_button" hidden="hidden" onClick="do_hide_editor()">Hide Editor</button>';
print "</td></tr>";

print "<tr>";
print "<th rowspan='6'>Choose RSpec</th>";
print "<td><b>Select existing: </b>";
show_rspec_chooser($user);
print "</td></tr>";
print "<tr><td>";
print "<b>Select from file: </b><input type='file' name='file_select' id='file_select' onchange='fileupload_onchange()'/>";
// upload message: get this from slice-add-resources.js 
// calling rspecuploadparser.php
print "<div id='upload_message' style='display:block;'></div>";
print "</td></tr>";
print "<tr><td>";
print "<b>Select from URL: </b>";
print '<button type="button" name="url_grab_button" id="url_grab_button" onClick="urlupload_onchange()"  >Select</button>';
print "<input type='input' name='url_select' id='url_select' onchange='urlupload_onchange()' />";
print "</td></tr>";
print "<tr><td>";
print '<b>Paste Rspec: </b>';
print '<button type="button" name="paste_grab_button" id="paste_grab_button" onClick="grab_paste_onchange()">Select</button>';
print '<textarea cols="60" rows="4" name="paste_select" id="paste_select"></textarea>';
print "</td></tr>";
print "<tr><td>";
print '<b>Select from Editor: </b><button id="grab_editor_topology_button" type="button" disabled="true" onClick="do_grab_editor_topology()">Select</button>';
print "</td></tr>";
print "<tr><td>";
print '<b><p id="rspec_status_text" /></b>';
print "</td></tr>";

print "<tr>";
print "<th rowspan='1'>Save RSpec</th>";
print "<td>";
print "<b>Download RSpec: </b>";
print '<button type="button" onClick="do_rspec_download()">Download</button>';
print "</td></tr>";

print "<tr><th>Choose Aggregate</th><td>";
show_am_chooser();
print "</td></tr>";
print "</table>";

if ($am_ids == null) {
  $am_id = "null";
}
?>
<script>
var am_id = <?php echo $am_id ?>;
if (am_id && $('#agg_chooser option[value="'+am_id+'"]').length > 0) {
  $('#agg_chooser').val(am_id); 
}
</script>
<script>
// keep record of which aggregate was set on page load
$( document ).ready(function() {
    am_on_page_load = $('#agg_chooser').val();
});
</script>

<?php
print '<input type="hidden" name="slice_id" value="' . $slice_id . '"/>';
print '<input type="hidden" name="current_rspec_text" id="current_rspec_text" value="" />';

// by default, assume RSpec is not bound or stitchable (0), but if a bound or
// stitchable RSpec is selected, change this value (to 1) via slice-add-resources.js
print '<input type="hidden" name="valid_rspec" id="valid_rspec" value="0"/>';
print '<input type="hidden" name="bound_rspec" id="bound_rspec" value="0"/>';
print '<input type="hidden" name="stitch_rspec" id="stitch_rspec" value="0"/>';
print '</form>';

?>
<?php

print "<p><b>Note:</b> Use the 'Manage RSpecs' tab to add a permanent RSpec; use 'Choose RSpec' options to temporarily upload an RSpec for this reservation only.</p>";

print ("<p><button id='rspec_submit_button' disabled='disabled' onClick=\"");
print ("validateSubmit();\">"
       . "<b>Reserve Resources</b></button>\n");
print "<button onClick=\"history.back(-1)\">Cancel</button>\n";
print '</p>';

// END add resources tab
echo "</div>";

// BEGIN rspecs tab
echo "<div id='rspecs'>";
/*----------------------------------------------------------------------
 * RSpecs
 *----------------------------------------------------------------------
 */
if (!$in_lockdown_mode) {
  include("tool-rspecs.php");
}
// END rspecs tab
echo "</div>";

// END the tabContent class
  echo "</div>";

include("footer.php");
?>

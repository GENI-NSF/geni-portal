<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologiesc
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
require_once("settings.php");
require_once 'geni_syslog.php';

$current_rspec = "";
if (array_key_exists('current_editor_rspec', $_POST)) {
    $current_rspec = $_POST['current_editor_rspec'];
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$mydir = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
add_js_script($mydir . '/slice-add-resources-jacks.js');

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
print "<p>To add resources you need to draw or choose a Resource Specification (RSpec).</p>";

$slice_ams = array();
$all_rspecs = fetchRSpecMetaData($user);

// JACKS-APP STUFF //
include("jacks-editor-app.php");
setup_jacks_editor_slice_context();
?>

<link rel="stylesheet" type="text/css" href="jacks-editor-app.css" />
<link rel="stylesheet" type="text/css" href="slice-add-resources-jacks.css" />
<script src="<?php echo $jacks_stable_url;?>"></script>

<?php
print "<table id='jacks-editor-app'>";
print "<tr><td><div id='jacks-editor-app-container'>";
print build_jacks_editor();
print "</div></td></tr></table>";
?>

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
  var jacks_current_rspec = <?php echo json_encode($current_rspec) ?>;
  var jacksContext = <?php echo json_encode($jacksContext) ?>;

  do_show_editor(jacks_current_rspec);

</script>

<?php

setup_jacks_editor_app_controls(True);


if ($am_ids == null) {
  $am_id = "null";
}
?>
<script>

enable_rspec_selection_mode_portal();
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
// stitchable RSpec is selected, change this value (to 1) via slice-add-resources-jacks.js
print '<input type="hidden" name="valid_rspec" id="valid_rspec" value="0"/>';
print '<input type="hidden" name="bound_rspec" id="bound_rspec" value="0"/>';
print '<input type="hidden" name="partially_bound_rspec" id="partially_bound_rspec" value="0"/>';
print '<input type="hidden" name="stitch_rspec" id="stitch_rspec" value="0"/>';
print '</form>';

?>
<?php

print "<p><b>Note:</b> Use the 'Manage RSpecs' tab to add a permanent RSpec.</p>";
print '<p><b>Note:</b> You need to bind a request to a specific GENI site before reserving resources, you can do this in the graphical pane by clicking on the "Site X" icon.</p>';
print '<p><b>Note:</b> You can only add resources at aggregates where you do not yet have a reservation.</p>';

print "<p id='partially_bound_notice' hidden='hidden'><b>Note:</b> 'Partially bound' RSpecs are RSpecs that bind some resources to specific aggregates, but not all. RSpecs must either not assign resources to any specific aggregates, or assign all resources to specific aggregates.</p>";

print ("<p><button id='rspec_submit_button' disabled='disabled' onClick=\"");
print ("do_grab_editor_topology_and_submit();\">"
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

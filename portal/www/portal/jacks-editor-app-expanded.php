<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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
require_once("settings.php");
require_once("aggstatus.php");

// error_log("POST = " . print_r($_POST, true));

$current_rspec = "";
if (array_key_exists('current_editor_rspec', $_POST)) {
    $current_rspec = $_POST['current_editor_rspec'];
    //    error_log("CURRENT_RSPEC = " . $current_rspec);
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$slice_id = "None";
$slice_name = "None";
$slice_ams = array();
$all_rspecs = fetchRSpecMetaData($user);
include("tool-lookupids.php");



show_header('GENI Portal: Add Resources to Slice', true, false);

echo '<script src="/secure/jacks-lib.js"></script>';
echo '<script src="/secure/slice-add-resources-jacks.js"></script>';


// JACKS-APP STUFF //
include("jacks-editor-app.php");
setup_jacks_editor_slice_context();

$AM_STATUS_LOCATION = $AM_STATUS_MON_FILE;
$am_status = array("fake_urn" => "fake_status");
if (file_exists($AM_STATUS_LOCATION)) {
  $am_status = json_decode(file_get_contents($AM_STATUS_LOCATION));
}

?>


<link rel="stylesheet" type="text/css" href="jacks-editor-app.css" />
<link rel="stylesheet" type="text/css" href="slice-add-resources-jacks.css" />
<script src="<?php echo $jacks_stable_url;?>"></script>

<?php

echo "<table id='jacks-editor-app'><tr><th>Add Resources to GENI Slice $slice_name</th></tr>";
print "<tr><td><div id='jacks-editor-app-container' style='width:100%; height:700px;'>";
print build_jacks_editor();
print "</div></td></tr></table>";
print "<script src='portal-jacks-editor-app.js'></script>";

?>

<script>
  var slice_id = <?php echo json_encode($slice_id); ?>;
  var slice_name = <?php echo json_encode($slice_name); ?>;

  // AMs that the Portal says there are resources at.
  var jacks_slice_ams = <?php echo json_encode($slice_ams) ?>;
  var jacks_all_ams = <?php echo json_encode($all_ams) ?>;
  var jacks_all_compute_ams = <?php echo json_encode($all_compute_ams) ?>;
  var jacks_am_status = <?php echo json_encode($am_status) ?>;
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

  var jacks_enable_buttons = true;
  var jacks_current_rspec = <?php echo json_encode($current_rspec) ?>;
  var jacksContext = <?php echo json_encode($jacksContext) ?>;

$(document).ready(function() {
  do_show_editor(jacks_current_rspec);
  });

</script>

<?php

setup_jacks_editor_app_controls(False);

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
print '<p><b>Note:</b> If you would like a Layer 2 link between sites, set the Link Type to "Stitched Ethernet".<br/>';
print 'However, if you would like a Layer 2 link connecting only ExoGENI sites, instead set the Link Type to "(any)" and pick any two ExoGENI aggregates except "ExoGENI ExoSM".</p>';

print "<p id='partially_bound_notice' hidden='hidden'><b>Note:</b> 'Partially bound' RSpecs are RSpecs that bind some resources to specific aggregates, but not all. RSpecs must either not assign resources to any specific aggregates, or assign all resources to specific aggregates.</p>";

print ("<p><button id='rspec_submit_button' disabled='disabled' onClick=\"");
print ("do_grab_editor_topology_and_submit();\">"
       . "<b>Reserve Resources</b></button>\n");
print "<button onClick=\"history.back(-1)\">Cancel</button>\n";
print '</p>';

?>
<!--
The height setting below breaks things on Chrome for Aaron, but not Marshall. But it is what allows the pane to grow vertically.
  -->
<script>
$(document).ready(function() {
    $('#jacks-editor-status').hide();
    var pane = $("#jacks-editor-pane")[0];
    pane.style.height = "95%";
    pane.style.width = "100%";
  });
</script>
<?php

print "</div></td></tr></tbody></table>";

echo '</div>';

include("footer.php");

?>

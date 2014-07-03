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

function cmp($a,$b) {
  return strcmp(strtolower($a['name']),strtolower($b['name']));
}

function show_rspec_chooser($user) {
  $all_rmd = fetchRSpecMetaData($user);
  usort($all_rmd,"cmp");
  print "<select name=\"rspec_id\" id=\"rspec_select\""
    . " onchange=\"rspec_onchange()\""
    . ">\n";
  echo '<option value="" title="Choose RSpec" selected="selected">Choose RSpec...</option>';
  echo '<option value="PRIVATE" disabled>---Private RSpecs---</option>';
  foreach ($all_rmd as $rmd) {
    if ($rmd['visibility']==="private") {
      $rid = $rmd['id'];
      $rname = $rmd['name'];
      $rdesc = $rmd['description'];
      //    error_log("BOUND = " . $rmd['bound']);
      $enable_agg_chooser=1;
      //UNCOMMENT NEXT LINE WHEN WE WANT BOUND RSPEC TO BE HANDLED CORRECTLY AGAIN
      if ($rmd['bound'] == 't') {
        $enable_agg_chooser = 0;
      }
      //    error_log("BOUND = " . $enable_agg_chooser);
      print "<option value='$rid' title='$rdesc' bound='$enable_agg_chooser'>$rname</option>\n";
    }
  }
  echo '<option value="PUBLIC" disabled>---Public RSpecs---</option>';
  foreach ($all_rmd as $rmd) {
    if ($rmd['visibility']==="public") {
      $rid = $rmd['id'];
      $rname = $rmd['name'];
      $rdesc = $rmd['description'];
      //    error_log("BOUND = " . $rmd['bound']);
      $enable_agg_chooser=1;
      //UNCOMMENT NEXT LINE WHEN WE WANT BOUND RSPEC TO BE HANDLED CORRECTLY AGAIN
      if ($rmd['bound'] == 't') {
        $enable_agg_chooser = 0;
      }
      //    error_log("BOUND = " . $enable_agg_chooser);
      print "<option value='$rid' title='$rdesc' bound='$enable_agg_chooser'>$rname</option>\n";
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
  print '<select name="am_id" id="agg_chooser">\n';
  echo '<option value="" title = "Choose an Aggregate">Choose an Aggregate...</option>';
  foreach ($all_aggs as $agg) {
    $aggid = $agg['id'];
    $aggname = $agg['service_name'];
    $aggdesc = $agg['service_description'];
    print "<option value=\"$aggid\" title=\"$aggdesc\">$aggname</option>\n";
  }
  // for bound RSpecs
  echo '<option disabled value="bound" title="Bound RSpec (AM chosen in RSpec)">Bound RSpec (AM chosen in RSpec)</option>'; 
  print "</select>\n";
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

show_header('GENI Portal: Slices', $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
?>
<script>
function validateSubmit()
{
  f1 = document.getElementById("f1");
  rspec = document.getElementById("rspec_select");
  am = document.getElementById("agg_chooser");
  rspec2 = document.getElementById("rspec_selection");
  
  if (rspec.value && am.value) {
    f1.submit();
    return true;
  } else if (rspec2.value && am.value) {
    f1.submit();
    return true;
  } else if (rspec.value) {
    alert("Please select an Aggregate.");
    return false;
  }
  alert ("Please select a Resource Specification (RSpec).");
  return false;
}
</script>

<?php
print "<h1>Add resources to GENI Slice: " . "<i>" . $slice_name . "</i>" . "</h1>\n";

// Put up a warning to upload SSH keys, if not done yet.
if (count($keys) == 0) {
  // No ssh keys are present.
  print "<p class='warn'>No ssh keys have been uploaded. ";
  print ("Please <button onClick=\"window.location='uploadsshkey.php'\">"
         . "Upload an SSH key</button> or <button " .
	 "onClick=\"window.location='generatesshkey.php'\">Generate and "
	 . "Download an SSH keypair</button> to enable logon to nodes.</p>\n");
}

print "<h2>Manage Resource Specifications (RSpecs)</h2>\n";
print "<p><button onClick=\"window.location='rspecs.php'\">"
    . "View Available RSpecs</button> \n";
print "<button onClick=\"window.location='rspecupload.php'\">"
    . "Upload New RSpec</button></p>\n";

print "<h2>Add Resources</h2>\n";
print "<p>To add resources you need to choose a Resource Specification file (RSpec).</p>";

print '<form id="f1" action="sliceresource.php" method="post" enctype="multipart/form-data">';

print "<table>";
print "<tr><th rowspan='2'>Choose RSpec</th>";
print "<td><b>Select existing: </b>";
show_rspec_chooser($user);
print "</td></tr>";
print "<tr><td>";
print "<b>Select from file: </b><input type='file' name='rspec_selection' id='rspec_selection' /><br><small><i>RSpecs uploaded from 'Select from file' are for this reservation only and will not be saved by the portal.</i></small>";
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
<?php
print '<input type="hidden" name="slice_id" value="' . $slice_id . '"/>';
// stitching: by default, assume RSpec is not bound (0), but if a bound
//      RSpec is selected, change this value (to 1) via slice-add-resources.js
print '<input type="hidden" name="bound_rspec" id="bound_rspec" value="0"/>';
print '</form>';

print ("<p><button onClick=\"");
print ("validateSubmit();\">"
       . "<b>Reserve Resources</b></button>\n");
print "<button onClick=\"history.back(-1)\">Cancel</button>\n";
print '</p>';

include("footer.php");
?>

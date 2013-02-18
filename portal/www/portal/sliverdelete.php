<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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

require_once("settings.php");
require_once('portal.php');
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
require_once("print-text-helpers.php");
require_once("logging_client.php");
$user = geni_loadUser();
if (! $user->isActive()) {
  relative_redirect('home.php');
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
unset($slice);
include("tool-lookupids.php");

if (! isset($slice)) {
  no_slice_error();
}

if (!$user->isAllowed(SA_ACTION::DELETE_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

// Takes an arg am_id which may have multiple values. Each is treated
// as the ID from the DB of an AM which should be queried
// If no such arg is given, then query the DB and query all registered AMs

if (! isset($ams) || is_null($ams)) {
  // Didnt get an array of AMs
  if (! isset($am) || is_null($am)) {
    // Nor a single am
    $ams = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  } else {
    $ams = array();
    $ams[] = $am;
  }
}


$header = "Deleting resources on slice: $slice_name";


show_header('GENI Portal: Slices',  $TAB_SLICES);
include("tool-breadcrumbs.php");

?>

<script src="amstatus.js"></script>
<script>
var slice= "<?php echo $slice_id ?>";
var am_id= "<?php echo $am_id ?>";

$(document).ready(build_delete_table);
</script>



<?php

print "<h2>$header</h2>\n";
print "<div class='resources' id='prettyxml'>";
print "<p id='delete' style='display:block;'><i>Deleting resources...</i></p>";

print "<p id='summary' style='display:none;'><i>Have attempted to delete resources at <span id='attempted'>0</span> of <span id='total'>0</span> aggregate.</i></p>";

print "<div id='delsliverlabel' style='display:none;'>Deleted resources at:</div>";
// print "<div id='delsliverlabel' style='display:none;'>Deleted resources at <span id='success'>0</span> aggregate:</div>";
print "<div id='deletesliver'><ul id='deletesliver'></ul></div>";	

print "<div id='delerrorlabel' style='display:none;'>No resources deleted at:</div>";
// print "<div id='delerrorlabel' style='display:none;'>No resources deleted at <span id='fail'>0</span> aggregate:</div>";
print "<div id='deleteerror'><ul id='deleteerror'></ul></div>";
print "</div>\n";

print "<hr/>";
print "<a href='slices.php'>Back to All slices</a>";
print "<br/>";
print "<a href='slice.php?slice_id=$slice_id'>Back to Slice $slice_name</a>";
include("footer.php");

?>




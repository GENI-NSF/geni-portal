<?php
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
?>
<?php
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
require_once("print-text-helpers.php");
$user = geni_loadUser();
if (! $user->isActive()) {
  relative_redirect("home.php");
}
$renewed_slice = false;
$renew_slice = false;
$renew_sliver = false;
?>
<?php
function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

function no_time_error() {
  relative_redirect("error-text.php?error=" . urlencode("No new sliver expiration time specified."));
  //  header('HTTP/1.1 404 Not Found');
//  print 'No expiration time specified.';
  exit();
}

if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}

include("tool-lookupids.php");

if (! isset($slice)) {
  no_slice_error();
} else {
  $old_slice_expiration = dateUIFormat($slice[SA_ARGUMENT::EXPIRATION]);
}
if (! isset($project)) {
  print "No project id specified.";
  exit();
} 

if (!$user->isAllowed(SA_ACTION::RENEW_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

if (array_key_exists('renew', $_GET)) {
  $renew = $_GET['renew'];
  if ($renew == 'slice'){
     $renew_slice = true;
     $renew_sliver = false;
  } elseif  ($renew == 'slice_sliver'){
     $renew_slice = true;
     $renew_sliver = true;
  } elseif  ($renew == 'sliver'){
     $renew_slice = false;
     $renew_sliver = true;
  }
}
if (array_key_exists('sliver_expiration', $_GET)
    && $_GET['sliver_expiration']) {
  // what we got asked for
  $desired_expiration = $_GET['sliver_expiration'];
  $desired_obj = new DateTime($desired_expiration);
  //if you try to renew past the project expiration, limit it
  $project_expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
  if ($project_expiration and $desired_obj > new DateTime($project_expiration)) {  
    $desired_expiration = $project_expiration;
  } elseif ($renew_sliver and !$renew_slice and $desired_obj > new DateTime($slice[SA_ARGUMENT::EXPIRATION])) {
      // If you try to renew past the slice expiration, limit it
      $desired_expiration = $slice[SA_ARGUMENT::EXPIRATION];
  } else {
    // If you didn't specify a time, use min of end of day, slice expiration, project expiration
    $desired_array = date_parse($desired_expiration);
    if ($desired_array["hour"] == 0 and $desired_array["minute"] == 0 and $desired_array["second"] == 0 and $desired_array["fraction"] == 0) {
      $sliceexp_array = date_parse($slice[SA_ARGUMENT::EXPIRATION]);
      if ($project_expiration) {
	$projexp_array = date_parse($project_expiration);
      }
      if ($renew_sliver and ! $renew_slice and $desired_array["year"] == $sliceexp_array["year"] and $desired_array["month"] == $sliceexp_array["month"] and $desired_array["day"] == $sliceexp_array["day"]) {
	$desired_expiration = $slice[SA_ARGUMENT::EXPIRATION];
      } elseif ($project_expiration and $desired_array["year"] == $projexp_array["year"] and $desired_array["month"] == $projexp_array["month"] and $desired_array["day"] == $projexp_array["day"]) {
	$desired_expiration = $project_expiration;
      } else {
	// renew for the end of the day
	$desired_expiration = $desired_expiration . " 23:59:59";
      }
    }
  }

  // what to send to the AM(s)
  $rfc3339_expiration = rfc3339Format($desired_expiration);
  // what to display to the user
  $ui_expiration = dateUIFormat($desired_expiration);
} else {
  no_time_error();
}

if ($renew_slice){
   $res = renew_slice($sa_url, $user, $slice_id, $desired_expiration);

   //error_log("Renew Slice output = " . $res);

   if (!$res) {
     $res = "FAILed to renew slice (requested $desired_expiration, was $old_slice_expiration)";
     $slice_expiration = $old_slice_expiration;
   } else {
     $renewed_slice = true;
     // get the new slice expiration
     $res = "Renewed slice (requested $desired_expiration, was $old_slice_expiration)";
     unset($slice);
     $slice = lookup_slice($sa_url, $user, $slice_id);
     $slice_expiration = dateUIFormat($slice[SA_ARGUMENT::EXPIRATION]);
     
   }
   $res = $res . " - slice expiration is now: <b>$slice_expiration</b>\n";
}

if ($renew_sliver and (($renew_slice and $renewed_slice) or !$renew_slice)) {
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
}

$header = "Renewing ";
if ($renew_slice and $renew_sliver) {
  $header = $header . "Slice and Resources";
} elseif ($renew_slice) {
  $header = $header . "Slice";
} else {
  $header = $header . "Resources";
}
$header = $header . " on: $slice_name";

show_header('GENI Portal: Slices',  $TAB_SLICES);
include("tool-breadcrumbs.php");
if (! isset($am_id) or is_null($am_id)) {
  $am_id = "";
}
?>

<script src="amstatus.js"></script>
<script>
var slice= "<?php echo $slice_id ?>";
var am_id= <?php echo json_encode($am_ids) ?>;
var sliver_expiration= "<?php echo $rfc3339_expiration ?>";

<?php
if ($renew_sliver and (($renew_slice and $renewed_slice) or !$renew_slice)) {
?>
   $(document).ready(build_renew_table);
<?php
}
?>
</script>

<?php
print "<h2>$header</h2>\n";
print "<div class='resources' id='prettyxml'>";
if ($renew_slice){
   print "<p id='slicerenew'>Renewed slice until: $slice_expiration</p>";
}
if ($renew_sliver){
   print "<p id='renew' style='display:block;'><i>Renewing resources...</i></p>";	

   print "<p id='renewsummary' style='display:none;'><i>Issued renew resources at <span id='attempted'>0</span> of <span id='total'>0</span> aggregate.</i></p>";

   print "<div id='renewsliverlabel' style='display:none;'>Renewed resources at:</div>";
   print "<div id='renewsliver'><ul id='renewsliver'></ul></div>";	

   print "<div id='renewerrorlabel' style='display:none;'>No resources renewed at:</div>";
   print "<div id='renewerror'><ul id='renewerror'></ul></div>";
}
print "</div>\n";


print "<hr/>";
print "<p><a href='slices.php'>Back to All slices</a>";
print "<br/>";
print "<a href='slice.php?slice_id=$slice_id'>Back to Slice <i>$slice_name</i></a></p>";

include("footer.php");

?>

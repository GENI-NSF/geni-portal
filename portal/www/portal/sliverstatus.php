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
?>
<?php
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
$user = geni_loadUser();
if (! $user->privSlice() || ! $user->isActive()) {
  relative_redirect('home.php');
}
?>
<?php
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

if (! isset($ams) || is_null($ams) || count($ams) <= 0) {
  error_log("Found no AMs!");
  $slivers_output = "No AMs registered.";
} else {
  $slivers_output = "";
  // Get the slice credential from the SA
  $slice_credential = get_slice_credential($sa_url, $slice_id, $user->account_id);
  
  // Get the slice URN via the SA
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
  //$slice_name = $slice[SA_ARGUMENT::SLICE_NAME];

  foreach ($ams as $am) {
    if (is_array($am)) {
      if (array_key_exists(SR_TABLE_FIELDNAME::SERVICE_URL, $am)) {
	$am_url = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
      } else {
	error_log("Malformed array of AM URLs?");
	continue;
      }
    } else {
      $am_url = $am;
    }
 
    // Call sliver status at the AM
    $sliver_output = sliver_status($am_url, $user, $slice_credential,
				   $slice_urn);
    error_log("SliverStatus output = " . $sliver_output);
    $slivers_output = $slivers_output . $sliver_output . "\n";
  }
}
$header = "Status of Slivers on slice: $slice_name";
$text = $slivers_output;
include("print-text.php");

//relative_redirect('slices');

?>

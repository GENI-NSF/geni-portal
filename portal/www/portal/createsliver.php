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
require_once('portal.php');
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("am_map.php");
require_once("sa_client.php");
require_once("print-text-helpers.php");
require_once("logging_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}
function no_rspec_error() {
  header('HTTP/1.1 404 Not Found');
  if (array_key_exists("rspec_id", $_REQUEST)) {
    $rspec_id = $_REQUEST['rspec_id'];
    print "Invalid rspec id \"$rspec_id\" specified.";
  } else {
    print 'No rspec id specified.';
  }
  exit();
}
function no_am_error() {
  header('HTTP/1.1 404 Not Found');
  if (array_key_exists("am_id", $_REQUEST)) {
    $am_id = $_REQUEST['am_id'];
    print "Invalid aggregate manager id \"$am_id\" specified.";
  } else {
    print 'No aggregate manager id specified.';
  }
  exit();
}

if (! count($_REQUEST)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
unset($slice);
unset($rspec);
unset($am);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

if(!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

if (! isset($rspec) || is_null($rspec)) {
  //  no_rspec_error();
  $rspec = fetchRSpecById(1);
}
if (! isset($am) || is_null($am)) {
  no_am_error();
}

// Get an AM
$am_url = $am[SR_ARGUMENT::SERVICE_URL];
$AM_name = am_name($am_url);
// error_log("AM_URL = " . $am_url);

//$result = get_version($am_url, $user);
// error_log("VERSION = " . $result);

// Get the slice credential from the SA
$slice_credential = get_slice_credential($sa_url, $user, $slice_id);

// Get the slice URN via the SA
$slice_urn = $slice[SA_ARGUMENT::SLICE_URN];

// Retrieve a canned RSpec
$rspec_file = writeDataToTempFile($rspec);

// Call create sliver at the AM
$retVal = create_sliver($am_url, $user, $slice_credential,
                               $slice_urn, $rspec_file);
unlink($rspec_file);
error_log("CreateSliver output = " . print_r($retVal, TRUE));

$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
						$slice['project_id']);
$slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
					$slice['slice_id']);
$log_attributes = array_merge($project_attributes, $slice_attributes);
log_event($log_url, Portal::getInstance(),
	  "Added resources to slice " . $slice_name,
          $log_attributes, $slice['owner_id']);


$header = "Created Sliver on slice: $slice_name";

if ( count($retVal) == 2 ) {
   $msg = $retVal[0];
   $obj = $retVal[1];
} else {
   $msg = $retVal;
   $obj = "";
}

unset($slice2);
$slice2 = lookup_slice($sa_url, $user, $slice_id);
$slice_expiration_db = $slice2[SA_ARGUMENT::EXPIRATION];
$slice_expiration = dateUIFormat($slice_expiration_db);


// Set headers for xml
header("Cache-Control: public");
header("Content-Type: text/xml");
//$obj2 = trim($obj);
if ($obj != "" ) {
   $obj2 = print_rspec_pretty($obj);
   print $obj2; 
} else {
   print "<i>";
   print $msg;
   print "</i>"; 
}

?>

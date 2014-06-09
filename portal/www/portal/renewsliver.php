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
require_once('portal.php');
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("am_map.php");
require_once("json_util.php");
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
unset($slice);
unset($am);
include("tool-lookupids.php");

if (! isset($slice)) {
  no_slice_error();
}

if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

if (!$user->isAllowed(SA_ACTION::RENEW_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

if (array_key_exists('sliver_expiration', $_GET)) {
  // what we got asked for
  $desired_expiration = $_GET['sliver_expiration'];
  $desired_obj = new DateTime($desired_expiration);
  if ($desired_obj > new DateTime($slice[SA_ARGUMENT::EXPIRATION])) {
    // If you try to renew past teh slice expiration, limit it
    $desired_expiration = $slice[SA_ARGUMENT::EXPIRATION];
  } else {
    // If you didn't specify a time, use min of end of day, slice expiration
    $desired_array = date_parse($desired_expiration);
    if ($desired_array["hour"] == 0 and $desired_array["minute"] == 0 and $desired_array["second"] == 0 and $desired_array["fraction"]== 0) {
      $sliceexp_array = date_parse($slice[SA_ARGUMENT::EXPIRATION]);
      if ($desired_array["year"] == $sliceexp_array["year"] and $desired_array["month"] == $sliceexp_array["month"] and $desired_array["day"] == $sliceexp_array["day"]) {
	$desired_expiration = $slice[SA_ARGUMENT::EXPIRATION];
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

// Get an AM
$am_url = $am[SR_ARGUMENT::SERVICE_URL];
$AM_name = am_name($am_url);

// Get the slice credential from the SA
$slice_credential = get_slice_credential($sa_url, $user, $slice_id);
  
// Get the slice URN via the SA
$slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
error_log("Renew slice urn = $slice_urn");


$am_urls = array();
if (! isset($ams) || is_null($ams) || count($ams) <= 0) {
  error_log("Found no AMs!");
  $slivers_output = "No AMs registered.";
} else {
  $slivers_output = "";
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
    $am_urls[] = $am_url; 
  }
  error_log("SLIVER_RENEW AM_URL = " . $am_url);
  
  // Call renew sliver at the AM
  $retVal = renew_sliver($am_urls, $user, $slice_credential,
			 $slice_urn, $rfc3339_expiration, $slice_id);
  //error_log("RenewSliver output = " . $retVal);
}

$s = array();
$f = array();
$obj = array();
$obj[] = $s;
$obj[] = $f;

if ( count($retVal) == 2 ) {
   $msg = $retVal[0];
   $obj1 = $retVal[1];
   $obj = array();
   foreach ($obj1 as $list) {
      $tmpList = array();
      foreach ($list as $am) {
         $tmpList[] = am_name($am);
	 }	 
      $obj[] = $tmpList;
      }   	      	    
} else {
   $msg = $retVal;
}

$success = $obj[0];
$fail = $obj[1];

if (count($success)) {
  $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
  $project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
						  $slice['project_id']);
  $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
						$slice['slice_id']);
  $log_attributes = array_merge($project_attributes, $slice_attributes);
  if (count($am_urls) == 1) {
    log_event($log_url, $user,
	      "Renewed resources from slice " . $slice_name . " at " . $AM_name,
	      $log_attributes);
  } else {
    log_event($log_url, $user,
	      "Renewed resources from slice " . $slice_name,
	      $log_attributes);
  }
}

unset($slice2);
$slice2 = lookup_slice($sa_url, $user, $slice_id);
$slice_expiration_db = $slice2[SA_ARGUMENT::EXPIRATION];
$slice_expiration = dateUIFormat($slice_expiration_db);

//if terminated obj[1] is failed nodes; so on terminated want to append the AM to $obj[1]
//error_log("RenewSliver msg = " . $msg . " obj " . print_r($obj, true));
  if(preg_match("/".AM_CLIENT_TIMED_OUT_MSG."/", $msg) == 1) {
    $rrht = $obj[1];			      
    $rrht[] = am_name($am_url);
    $obj[1] = $rrht;
}

// Set headers for download
header("Cache-Control: public");
header("Content-Type: application/json");

/* json_encode accepts JSON_PRETTY_PRINT in PHP 5.4, but
 * we've got 5.3. Use a third-party utility instead.
 */
if ($obj != "" ) {
   print json_indent(json_encode($obj));
} else {
   print json_indent($msg);
}
?>

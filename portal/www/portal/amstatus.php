<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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

require_once("header.php");
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
require_once("am_map.php");
require_once("json_util.php");

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

if (!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

include("query-sliverstatus.php");

function get_sliver_status_err( $msg,  $status_array ) {
  /* Sample input */
  /*  Slice urn:publicid:IDN+sergyar:AMtest+slice+test1 expires on 2012-07-07 18:21:41 UTC
Failed to get SliverStatus on urn:publicid:IDN+sergyar:AMtest+slice+test1 at AM https://localhost:8001/: [Errno 111] Connection refused

Failed to get SliverStatus on urn:publicid:IDN+sergyar:AMtest+slice+test1 at AM https://www.pgeni3.gpolab.bbn.com:12369/protogeni/xmlrpc/am/2.0: No slice or aggregate here
Returned status of slivers on 0 of 2 possible aggregates.
*/

  $succ=array();
  $fail=array();
  $lines = preg_split ('/$\R?^/m', $msg);
  $num_errs = 0;
  foreach ($lines as $line){  
    if (preg_match("/^Returned status of slivers on (\d+) of (\d+) possible aggregates.$/",$line, $succ)){
      $n = (int) $succ[1];
      $m = (int) $succ[2];
    } elseif (preg_match("/^Failed to get SliverStatus on urn\:publicid\:IDN\+(\w+)\:(\w+)\+slice\+(\w+) at AM ([^[:space:]]*): (.*)$/",$line,$fail)) {
      $num_errs = $num_errs+1;
      $agg = $fail[4];
      $err = $fail[5];
      $am_url = $agg; // FIXME is this always return a URL
      if ( $status_array[ am_id( $am_url ) ] ) {
      	$status_array[ am_id( $am_url ) ]['geni_error'] = $err;
      } else {       
      	$status_array[ am_id( $am_url ) ] = Array();
      	$status_array[ am_id( $am_url ) ]['url'] = $am_url;
      }
    }
  }

  $retVal = Array();
  $retVal[] = $status_array;
  $retVal[] = $m;

  return $retVal;
}



$status_array = Array();

$no_resource_msg = "no resources";

foreach ($obj as $am_url => $am_status) {
 $status_item = Array();
 // AM url
 $status_item['url'] = $am_url;
 // AM name	     
 $status_item['am_name'] = am_name($am_url);
 if ($am_status) {
    // AM status
    $geni_status = $am_status['geni_status'];
    $geni_status = strtolower( $geni_status );
    $status_item['geni_status'] = $geni_status;
    // slice URN
    $status_item['slice_urn'] = $am_status['geni_urn'];
    // Resources
    $geni_resources = $am_status['geni_resources'];
    foreach ($geni_resources as $rsc){
      $resource_item = Array();
      if ( array_key_exists("pg_manifest", $rsc) ){
	$pg_rsc = $rsc["pg_manifest"];
	$pg_name = $pg_rsc["name"];
	if ($pg_name == "rspec"){
	  continue;
	}
	$pg_attr = $pg_rsc["attributes"];
	$rsc_urn = $pg_attr["client_id"];
	$resource_item['geni_urn'] = $rsc_urn;
      } else {
	$resource_item['geni_urn'] = $rsc['geni_urn'];
      }
      $resource_item['geni_status'] = $rsc['geni_status'];
      $resource_item['geni_error'] = $rsc['geni_error'];
      
      if (!array_key_exists("resources", $status_item )) {
         $status_item['resources'] = Array();
      }

      $status_item['resources'][] = $resource_item;
  }

  } else {
    $status_item['geni_status'] = $no_resource_msg;
 }
 $status_array[am_id( $am_url )] = $status_item ; //append this to the end of the list
}


$retVal = get_sliver_status_err( $msg, $status_array );
$status_array = $retVal[0];
$max_ams = $retVal[1];


// Set headers for xml
header("Cache-Control: public");
header("Content-Type: application/json");
print json_encode($status_array);

?>

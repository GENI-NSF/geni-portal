<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
require_once("query-sliverstatus.php");
include("status_constants.php");



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

function get_sliver_status( $obj,  $status_array, $goodret ) {

$GENI_MESSAGES_REV = array( 
    		       STATUS_MSG::GENI_CONFIGURING => STATUS_INDEX::GENI_CONFIGURING,
		       STATUS_MSG::GENI_READY => STATUS_INDEX::GENI_READY,
		       STATUS_MSG::GENI_FAILED => STATUS_INDEX::GENI_FAILED,
  		       STATUS_MSG::GENI_UNKNOWN => STATUS_INDEX::GENI_UNKNOWN,
  		       STATUS_MSG::GENI_NO_RESOURCES => STATUS_INDEX::GENI_NO_RESOURCES,
  		       STATUS_MSG::GENI_PENDING => STATUS_INDEX::GENI_PENDING,
		       STATUS_MSG::GENI_BUSY => STATUS_INDEX::GENI_BUSY);

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
       if (array_key_exists($geni_status, $GENI_MESSAGES_REV)) {
	 $status_item['status_code'] = $GENI_MESSAGES_REV[ $geni_status ]; //STATUS_INDEX::GENI_READY; //FIXME
       } else {
	 error_log("Unrecognized status message '$geni_status' from AM $am_url");
	 $status_item['status_code'] = STATUS_INDEX::GENI_UNKNOWN;
       }
       if (array_key_exists("geni_expires", $am_status )) {
	 // DCN (ION/MAX)
	 $geni_expires = $am_status['geni_expires'];
       } elseif (array_key_exists("pg_expires", $am_status )) {
	 // InstaGENI / ProtoGENI
	 $geni_expires = $am_status['pg_expires'];
       } elseif (array_key_exists("foam_expires", $am_status )) {
	 // FOAM / OESS
	 $geni_expires = $am_status['foam_expires'];
       } elseif (array_key_exists("pl_expires", $am_status )) {
	 $geni_expires = $am_status['pl_expires'];
       } elseif (array_key_exists("sfa_expires", $am_status )) {
	 $geni_expires = $am_status['sfa_expires'];
       } elseif (array_key_exists("geni_resources", $am_status ) and (count($am_status['geni_resources'])>=1)) {
	 if (array_key_exists("orca_expires", $am_status['geni_resources'][0]) ) {
	   // ExoGENI
	   // this assumes all resources have the same expiration time
	   // this may cease to be the case in AM API v3 and later
	   // Better would be to loop over resources and take the minimum expiration
	   $geni_expires = $am_status['geni_resources'][0]['orca_expires'];
	 } elseif (array_key_exists("geni_expires", $am_status['geni_resources'][0]) ) {
	   // GRAM
	   // this assumes all resources have the same expiration time
	   // this may cease to be the case in AM API v3 and later
	   // Better would be to loop over resources and take the minimum expiration
	   $geni_expires = $am_status['geni_resources'][0]['geni_expires'];
	 }
       } else {
              $geni_expires = 'unknown';
       }
       // If the expiration doesn't have TZ set, make it explicitly UTC (Z=Zulu)
       if ($geni_expires != 'unknown') {
	 $date_details = date_parse($geni_expires);
         if(!(array_key_exists('tz_id', $date_details) || array_key_exists('zone', $date_details))) {
	   $geni_expires = $geni_expires . "Z";
	 }
       }
       $status_item['geni_expires'] = $geni_expires;	     
       // slice URN
       $status_item['slice_urn'] = $am_status['geni_urn'];
       // Resources
       $geni_resources = $am_status['geni_resources'];
       foreach ($geni_resources as $rsc){
           $resource_item = Array();
	   $resource_item['geni_urn'] = $rsc['geni_urn'];
      	   $resource_item['geni_status'] = $rsc['geni_status'];
      	   $resource_item['geni_error'] = $rsc['geni_error'];
      
	   if (!array_key_exists("resources", $status_item )) {
           $status_item['resources'] = Array();
      	   }
      	   $status_item['resources'][] = $resource_item;
       }
    } else if ($goodret == TRUE) {
       $status_item['geni_status'] = STATUS_MSG::GENI_NO_RESOURCES; 
       $status_item['status_code'] = STATUS_INDEX::GENI_NO_RESOURCES; 
    } else {
       $status_item['geni_status'] = STATUS_MSG::GENI_FAILED; 
       $status_item['status_code'] = STATUS_INDEX::GENI_FAILED; 

    }   
    $status_array[am_id( $am_url )] = $status_item ; //append this to the end of the list
    }
    return $status_array;
}


//THIS FUNCTION DOESN'T CURRENTLY DO ANYTHING
function get_sliver_status_err( $msg,  $status_array ) {
  /* Sample input */
  /*  Slice urn:publicid:IDN+sergyar:AMtest+slice+test1 expires on 2012-07-07 18:21:41 UTC
Failed to get SliverStatus on urn:publicid:IDN+sergyar:AMtest+slice+test1 at AM https://localhost:8001/: [Errno 111] Connection refused

Failed to get SliverStatus on urn:publicid:IDN+sergyar:AMtest+slice+test1 at AM https://www.pgeni3.gpolab.bbn.com:12369/protogeni/xmlrpc/am/2.0: No slice or aggregate here
Returned status of slivers on 0 of 2 possible aggregates.
*/
  $busy_resource_msg = "<<<busy>>>";
  $succ=array();
  $fail=array();
  $lines = preg_split ('/$\R?^/m', $msg);
  $num_errs = 0;
  $m = 0;

  foreach ($lines as $line){  
    if (preg_match("/^Returned status of slivers on (\d+) of (\d+) possible aggregates.$/",$line, $succ)){
      $n = (int) $succ[1];
      $m = (int) $succ[2];
    } elseif (preg_match("/^Failed to get SliverStatus on urn\:publicid\:IDN\+(\w+)\:(\w+)\+slice\+(\w+) at AM ([^[:space:]]*): (.*)$/",$line,$fail)) {
      $num_errs = $num_errs+1;
      $agg = $fail[4];
      $err = $fail[5];
 // FIXME We don't have these variables here
      //$id = am_id( $am_url );
      //if ( $status_array[ $id ] ) {
      	//$status_array[ $id ]['geni_error'] = $err;
//      } else {       
 //     	$status_array[ $id ] = Array();
  //    	$status_array[ $id ]['url'] = $am_url;
     // }

      // if geni_error indicates that the AM is busy
      //if (($status_array[ $id ]['status_code'] == STATUS_INDEX::GENI_NO_RESOURCES) && (strpos($err,'busy') !== false)) {
        //$status_array[ $id ]['geni_status'] = $busy_resource_msg;		      
        //$status_array[ $id ]['status_code'] = STATUS_INDEX::GENI_BUSY; 
//      }
    }
  }

  $retVal = $status_array;
  return $retVal;
}

function getInfoFromSliverStatusPG( $obj, $status_array ){
    $loginInfo = Array();
    $pgKeyList = Array();

    foreach ($obj as $am_url => $am_item) {
        $status_item = Array();
    	// AM url
    	$status_item['url'] = $am_url;
    	// AM name	     
    	$status_item['am_name'] = am_name($am_url);

    	if (!$am_item){
      	// "ERROR: empty sliver status!"
	   continue;
    	}
    	if (! array_key_exists("users", $am_item )){
      	//    "ERROR: No 'users' key in sliver status!"
	      continue;
    	}
    	if (! array_key_exists('geni_resources', $am_item)){
      	// "ERROR: Sliver Status lists no resources"
	   continue;
    	}

    	$status_array[am_id( $am_url )]['login_info'] = array();

    	foreach ($am_item['users'] as $userDict) {
      		if (! array_key_exists('login',$userDict)){
      		// "User entry had no 'login' key"
      		      continue;
      		}		      //    } elseif (preg_match("/^Failed to get SliverStatus on urn*$/",$line,$fail)) {
      		$pgKeyList[$userDict['login']] = array();
      		if (! array_key_exists('keys',$userDict)) {
        	continue;
      		}
    	}
    	foreach ($userDict['keys'] as $k) {
      	#XXX nriga Keep track of keys, in the future we can verify what key goes with
      	# which private key
      	  	$pgKeyList[$userDict['login']][] = $k['key'];
    	}

   }
   return $status_array; 
}

// Close the session here to allow multiple AJAX requests to run
// concurrently. If the session is left open, it holds the session
// lock, causing AJAX requests to run serially.
// DO NOT DO ANY SESSION MANIPULATION AFTER THIS POINT!
session_write_close();

// querying the AMs for sliver status info
$statRet = query_sliverstatus( $user, $ams, $sa_url, $slice, $slice_id );
$msg = $statRet[0];
$obj = $statRet[1];
$good = $statRet[2];

$status_array = Array();

if (count($obj)>0) {
   // fill in sliver status info for each agg
   $status_array = get_sliver_status( $obj, $status_array, $good );
   // fill in sliver status errors for each agg 
   $status_array = getInfoFromSliverStatusPG( $obj, $status_array );
   // fill in sliver status errors for each agg
   //$retVal = get_sliver_status_err( $msg, $status_array );

   //$status_array = $retVal;
} 

// Set headers for xml
header("Cache-Control: public");
header("Content-Type: application/json");
print json_encode($status_array);

?>

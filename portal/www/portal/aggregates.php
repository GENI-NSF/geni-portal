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

require_once("settings.php");
require_once("user.php");

function get_am_array( $all_aggs ) {
  $am_array = Array();
  foreach ($all_aggs as $agg) {
    $aggid = $agg['id']; 
    $aggname = $agg['service_name'];
    $aggurl = $agg['service_url'];
    $am_array[ $aggid ] = Array();
    $am_array[ $aggid ]['name']= $aggname;
    $am_array[ $aggid ]['url']= $aggurl;
  }
  return $am_array;
}


// May be 1 or more am_id arguments. Instantiate them all, if many given
// To give many, name the arg am_id[]

if (array_key_exists("am_id", $_REQUEST)) {
  $am_id = $_REQUEST['am_id'];
  if (is_array($am_id)) {
    $am_ids = $am_id;
    foreach ($am_ids as $am_id) {
      $tmp_am = get_service_by_id($am_id);
      if ($tmp_am && $tmp_am['url']) {
         error_log(": tmp_am['url']" . $tmp_am['url']);
         $ams[] = $tmp_am;
      }	 
    }
    $am_id = $am_ids[0];
    $am = $ams[0];
  } elseif (!$am_id) {
    error_log(": null am_id from REQUEST");
    $ams = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);	
    $am = $ams[0];
  } else {
    $am = get_service_by_id($am_id);
    $ams[] = $am;
  }

  if (is_null($am)) {
    if ($am_id != '') {
      error_log(": invalid am_id $am_id from REQUEST");
      $am_id = null;
      $ams = Array();
    }
  }
  $all_aggs = $ams;
} else {
  $ams = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
}

$agg_array = get_am_array($ams);

// Set headers for json
header("Cache-Control: public");
header("Content-Type: application/json");
print json_encode($agg_array);

?>

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

function get_am_array() {
  $am_array = Array();
  $all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
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

$agg_array = get_am_array();

//if (is_null($agg_array)) {
//  relative_redirect('home.php');
//} else {
//  error_log("AGG_ARRAY: " . print_r($agg_array));
  // Set headers for xml
  header("Cache-Control: public");
  header("Content-Type: application/json");
  //print $rspec;
//  print "START\n";
  print json_encode($agg_array);
//  print "END\n";
//}

?>

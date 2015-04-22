<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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
require_once("query-details.php");
require_once("print-text-helpers.php");
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


// Close the session here to allow multiple AJAX requests to run
// concurrently. If the session is left open, it holds the session
// lock, causing AJAX requests to run serially.
// DO NOT DO ANY SESSION MANIPULATION AFTER THIS POINT!
session_write_close();

// querying the AMs for sliver details info
$statRet = query_details( $user, $ams, $sa_url, $slice, $slice_id );
$msg = $statRet[0];
$obj = $statRet[1];
// $good = $statRet[2];

$status_array = Array();

if (count($obj)>0) {
// if (isset($obj) && $obj && is_array($obj)) {
   // fill in sliver details for each agg 
  if(preg_match("/".AM_CLIENT_TIMED_OUT_MSG."/", $msg) == 1) {
    print "<i>".AM_CLIENT_TIMED_OUT_MSG." at aggregate ".am_name(key($obj))." </i><br/>\n";
  }
  $filterToAM = True;

  print_rspec( $obj, True, $filterToAM );
    // Get the rspec in xml format without HTML clutter
    $xmlRspec = get_rspec_xml( $obj, False, $filterToAM );

    /*
    if ($xmlRspec && $xmlRspec != "null") {
      print "<div id='jacksContainer-".hash('ripemd160', am_name(key($obj)))."' class='jacks resources' style='background-color: white'></div>";

      print "<script>
              $(document).ready(function() {
              var xml = \"$xmlRspec\";
                thisInstance = new window.Jacks({
                  mode: 'viewer',
                  source: 'rspec',
                  size: { x: 756, y: 400},
                  show: {
                  	rspec: false,
                  	version: false
                  },
                  nodeSelect: false,
                  root: '#jacksContainer-".hash('ripemd160', am_name(key($obj)))."',
                  readyCallback: function (input, output) {
                    input.trigger('change-topology',
                                  [{ rspec: xml }]);
                  }
                });
              });
            </script>";
    }
    */
  
  $am_urls = array_keys($obj);
  $am_url = $am_urls[0];
  $am_id = 0; // *** We need to change the obj URL to am_id
  // $ams is an array of array info: service_cert, service_name, 
  // service_attributes, service_cert_contents, id, service_url, 
  // service_urn, service_type, serrvice_description
  // So look up the am_id from the am_url
  foreach($ams as $am) {
    if($am[SR_TABLE_FIELDNAME::SERVICE_URL] == $am_url) {
      $am_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
      break;
    }
  }
  print '<div class="rawRSpec" id="rawrspec_' . $am_id . '" style="display: none;">';
  //  error_log("OBJ = " . $am_url . " " . $am_id);
  //  error_log("AMS = " . print_r($ams, true));
  //  error_log("GET = " . print_r($_GET, true));
  print_rspec( $obj, False, $filterToAM );
  print '</div>';
} else {
  // FIXME: $obj might be an error message?
  print "<i>No resources found.</i><br/>\n";
}

if (isset($msg) && $msg && $msg != '') {
  error_log("ListResources message: " . $msg);
}

// Set headers for xml
// header("Cache-Control: public");
// header("Content-Type: application/html");
// print json_encode($status_array);

?>

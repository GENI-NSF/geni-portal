<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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

  $rspec_xml = get_rspec_xml( $obj, False, $filterToAM );
  // Huh? get_rspec_xml returns the string "null" if there is no manifest?
  if ($rspec_xml == "null") {
    print "<rspec></rspec>";
  } else {
    print $rspec_xml;
  }
}

if (isset($msg) && $msg && $msg != '') {
  error_log("ListResources message: " . $msg);
}

?>

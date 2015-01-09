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

require_once("user.php");
require_once("header.php");
require_once('util.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
$slice = "None";
$slice_name = "None";

include("tool-lookupids.php");

if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

if (! isset($slice_id)) {
  /* How did they get here without a slice id? */
  relative_redirect('home.php');
}

if (!$user->isAllowed(SA_ACTION::DELETE_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

if (isset($slice) && $slice != "None") {
  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
}

/* Set up defaults, override if AM is specified. */
$am_name = null;
$confirm_msg = 'Delete all reserved resources at all aggregates?';
$edit_url = "sliverdelete.php?slice_id=$slice_id";
if (isset($am_id) && $am_id) {
  if (count($ams) > 1) {
    $confirm_msg = "Delete all reserved resources at the following ".count($ams)." aggregates?<br /><br />";
    for ($i = 0; $i < count($ams); $i++) {
      $edit_url = $edit_url."&am_id[]=".$am_ids[$i];
      $am_name = $ams[$i][SR_ARGUMENT::SERVICE_NAME];
      $confirm_msg = $confirm_msg."<b>$am_name</b><br />";
    }
    $confirm_msg = $confirm_msg."<br />";
  }
  else {
    $edit_url = "sliverdelete.php?slice_id=$slice_id&am_id=$am_id";
    $am_name = $ams[0][SR_ARGUMENT::SERVICE_NAME];
    $confirm_msg = "Delete all reserved resources at $am_name?";
  }
}

show_header('GENI Portal: Slices', $TAB_SLICES);
print "<h2>Delete resources from GENI Slice: " . "<i>" . $slice_name . "</i>" . "</h2>\n";
print "<p class='warn'>$confirm_msg Otherwise click <b>Cancel</b>.</p>\n";
print "<p><button onclick=\"window.location='$edit_url'\"><b>Delete Resources</b></button> \n";
if (array_key_exists('HTTP_REFERER', $_SERVER)) {
  print "<button onclick=\"history.back(-1)\">Cancel</button></p>\n";
} else {
  $cancel_url = "slice.php?slice_id=$slice_id";
  print "<button onclick=\"window.location='$cancel_url'\">Cancel</button>\n";
}

include("footer.php");
?>

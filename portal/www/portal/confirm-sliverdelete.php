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

require_once("user.php");
require_once("header.php");
require_once('util.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive() || ! $user->privSlice()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Slices', $TAB_SLICES);
$slice = "None";
$slice_name = "None";

include("tool-lookupids.php");

if (isset($slice) && $slice != "None") {
  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
}

print "<h1>Delete Resources from GENI Slice: " . $slice_name . "</h1>\n";

print "<p>Delete all reserved Resources?</p>\n";
print "<p>Otherwise click 'Cancel'.</p>\n";
print "<br/>\n";

$cancel_url = "slice.php?slice_id=$slice_id";
$edit_url = "sliverdelete.php?slice_id=$slice_id";
print "<button onclick=\"window.location='$edit_url'\"><b>Delete Slivers</b></button>\n";
//print "<button onclick=\"window.location='$cancel_url'\">Cancel</button>\n";
print "<button onclick=\"history.back(-1)\">Cancel</button>\n";


include("footer.php");
?>

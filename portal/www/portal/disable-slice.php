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

require_once("user.php");
require_once("header.php");
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('sa_constants.php');
require_once('sa_client.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$slice_id = "None";
$slice_name = "None";
include("tool-lookupids.php");
show_header('GENI Portal: slices', $TAB_SLICES);
include("tool-breadcrumbs.php");

print "<h1>DISABLE GENI Slice: " . "<i>" . $slice_name . "</i>" .  "</h1>\n";
// FIXME: What does happen when you delete a slice?
print "<b>Warning</b>: This operation is not reversible. Running slices will not be removed, but you will no longer be able to renew slices or use the GENI portal to modify them.<br/><br/>\n";
$edit_url = 'do-disable-slice.php?slice_id='.$slice_id;
print "<button onClick=\"window.location='".$edit_url."'\"><b>Disable Slice</b></button>   \n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/><br/>\n";

include("footer.php");
?>

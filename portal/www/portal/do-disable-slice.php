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

include("tool-lookupids.php");

if (isset($slice) && ! is_null($slice)) {
  // FIXME: Do anything to slices first? Members?
  $result = "Disable Slice Not Implemented";
  error_log("Disable Slice not implemented");
  //  $result = delete_slice($sa_url, $slice_id);
  //  if (! $result) {
  //    error_log("Failed to Disable slice $slice_id: $result");
  //  }
} else {
  error_log("Didnt find to disable slice $slice_id");
}
// FIXME: remove the slice from the DB
// Invalidate credentials?
// FIXME
$_SESSION['lastmessage'] = "Asked to disable slice $slice_name - NOT IMPLEMENTED";

show_header('GENI Portal: Slices', $TAB_SLICES);
relative_redirect('slices.php');

include("footer.php");
?>

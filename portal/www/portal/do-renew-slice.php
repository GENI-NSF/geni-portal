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
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive() || ! $user->privSlice()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");
if (! isset($slice)) {
  relative_redirect("home.php");
}
if (array_key_exists("slice_expiration", $_REQUEST)) {
  $req_exp = $_REQUEST["slice_expiration"];
} else {
  relative_redirect('slice.php?slice_id='.$slice_id);
}

if (isset($slice)) {
  $old_slice_expiration = $slice[SA_ARGUMENT::EXPIRATION];
}

$res = renew_slice($sa_url, $slice_id, $req_exp);
// call sa_client renew_slice($slice_id, $expiration)

//error_log("Renew Slice output = " . $res);

if ($res) {
  // get the new slice expiration
  $res = "Renewed slice (requested $req_exp, was $old_slice_expiration)";
  unset($slice);
  $slice = lookup_slice($sa_url, $slice_id);
  $slice_expiration = $slice[SA_ARGUMENT::EXPIRATION];
} else {
  $res = "FAILed to renew slice (requested $req_exp, was $old_slice_expiration)";
}

$res = $res . " - slice expiration is now: $slice_expiration\n";

$header = "Renewed Slice $slice_name";
$text = $res;
include("print-text.php");
?>

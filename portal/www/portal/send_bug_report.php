<?php
//----------------------------------------------------------------------
// Copyright (c) 2014 Raytheon BBN Technologies
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
require_once('portal.php');
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("am_map.php");
require_once("sa_client.php");
require_once("logging_client.php");
require_once("header.php");

/*
    send_bug_report.php
    
    Purpose: Sends bug report of omni invocation files to e-mail(s) specified
    
    Accepts:
        Required:
            invocation_id: unique ID for an omni invocation (e.g. qlj7KS)
            invocation_user: user ID (e.g. bujcich)
            slice_id: slice ID related to invocation
            to: receiver's e-mail address
        Optional:
            cc: additional receivers' e-mail addresses

*/

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

function no_invocation_id_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No omni invocation id and/or user ID specified.';
  exit();
}

// redirect if no attributes passed in
if (! count($_REQUEST)) {
  no_slice_error();
}

// set user ID and invocation
if(array_key_exists("invocation_id", $_REQUEST) && 
        array_key_exists("invocation_user", $_REQUEST)) {
    $invocation_user = $_REQUEST['invocation_user'];
    $invocation_id = $_REQUEST['invocation_id'];
}
else {
    no_invocation_id_error();
}

// set slice information
unset($slice);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

// redirect if slice has expired
if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

// redirect if user isn't allowed to look up slice
if(!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

// By this point, user is allowed to access slice and slice exists, so proceed

$omni_invocation_dir = get_invocation_dir_name($invocation_user, $invocation_id);

$excluded_files_list = array(
    "$omni_invocation_dir/cert", 
    "$omni_invocation_dir/cred",
    "$omni_invocation_dir/key"
);

$zip_name = "$omni_invocation_dir/omni-invocation-bug-report-$invocation_user-$invocation_id.zip";

// FIXME: Don't call this just yet - further testing needed
//$retVal = zip_dir_files($zip_name, $omni_invocation_dir, $excluded_files_list);

// if zip file, check that contents > 0 bytes, get contents, and use as attachment

// delete zip file (at some point)

// generate e-mail

// set message to display on next page load

// relative redirect back to results page




?>

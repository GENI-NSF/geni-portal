<?php
//----------------------------------------------------------------------
// Copyright (c) 2016 Raytheon BBN Technologies
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

/*
 *   This script is meant to be called via AJAX to update the SSH keys
 * on the given slice. It requires a slice id (UUID) as an argument
 * via either POST or GET.
 */
require_once("settings.php");
require_once("user.php");
require_once("sa_client.php");

/**
 * Exit with an error message. Return a JSON struct to the client
 * that includes a relevant error message.
 */
function error_exit($message)
{
  header('HTTP/1.1 500 Internal Server Error');
  header('Content-Type: application/json; charset=UTF-8');
  error_log("do-update-keys error: $message");
  die(json_encode(array('code' => 1,
                        'value' => NULL,
                        'output' => $message)));
}

$user = geni_loadUser();
if (!isset($user)) {
  error_exit('No user');
}

if (array_key_exists("slice_id", $_REQUEST)) {
  $slice_id = $_REQUEST['slice_id'];
} else {
  error_exit('No slice id specified.');
}

if (! uuid_is_valid($slice_id)) {
  error_exit('Invalid slice id specified.');
}

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  // TODO: Fix the criteria below
  if (!isset($sa_url)) {
    // TODO: How to return an error code to the AJAX client?
    error_exit('No available Slice Authority.');
  }
}

$slice_urn = get_slice_urn($sa_url, $user, $slice_id);
if (! $slice_urn) {
  error_exit('Slice not found.');
}

$members_to_add = array();
$members_to_change = array();
$members_to_remove = array();
$omni_result = update_user_keys_on_slivers($sa_url, $user, $slice_id,
                                           $slice_urn,
                                           $members_to_add, $members_to_change,
                                           $members_to_remove);

// TODO: How to return success to the AJAX client?
//       What does update_user_keys_on_slivers return to tell us? Anything?
//       What does the $omni_result look like?
$result = array('code' => 0,
                'value' => $omni_result,
                'output' => NULL);
header('Content-Type: application/json');
print json_encode($result);
?>

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

require_once("settings.php");
require_once("user.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$key_id = NULL;
if (array_key_exists('id', $_GET)) {
  $key_id = $_GET['id'];
}

if (is_null($key_id)) {
  error_log("delete ssh key: No key id available");
  relative_redirect('home.php');
}
error_log("delete ssh key: key id = $key_id");

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$result = delete_ssh_key($ma_url, $user, $user->account_id, $key_id);
if (is_array($result))
  $result = "Error: " . $result[RESPONSE_ARGUMENT::OUTPUT];
else
  $result = "Succeeded";
error_log("delete ssh key result = " . print_r($result, true));

$_SESSION['lastmessage'] = "Deleted SSH Key $key_id: $result";

relative_redirect('profile.php');
?>

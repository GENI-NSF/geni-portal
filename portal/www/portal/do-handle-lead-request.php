<?php
//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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
require_once("db_utils.php");
require_once("ma_constants.php");
require_once("ma_client.php");
require_once("util.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

if (array_key_exists('request_id', $_REQUEST) && array_key_exists('new_status', $_REQUEST) && array_key_exists('user_uid', $_REQUEST)) {
  $request_id = $_REQUEST['request_id'];
  $new_status = $_REQUEST['new_status'];
  $user_uid = $_REQUEST['user_uid'];
  $reason = $_REQUEST['reason'];
  $approver = $user->prettyName();
  handle_lead_request($request_id, $new_status, $approver, $user_uid, $reason);
} else if (array_key_exists('request_id', $_REQUEST) && array_key_exists('notes', $_REQUEST)) {
    add_request_note($_REQUEST['request_id'], $_REQUEST['notes']);
  } else {
    print "Failed to handle request due to incomplete information.";
  }


function add_request_note($request_id, $new_note) {
  global $user;
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $conn = portal_conn();
  if ($new_status == "approved") {
    add_member_privilege($ma_url, $user, $user_uid, "PROJECT_LEAD");
  }
  $sql = "UPDATE lead_request set "
  . "notes = "   . $conn->quote($new_note, 'text')
  . "where id = "  . $conn->quote($request_id, 'text');
  $db_response = db_execute_statement($sql, "update lead request note for request id#: " . $request_id);
  $db_error = $db_response[RESPONSE_ARGUMENT::OUTPUT];
  print $db_error == "" ? "response successfully stored " : "response could not be stored because of db error: " . $db_error;
}

function handle_lead_request($request_id, $new_status, $approver, $user_uid, $reason) {
  global $user;
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $conn = portal_conn();
  if ($new_status == "approved") {
    add_member_privilege($ma_url, $user, $user_uid, "PROJECT_LEAD");
  }
  $sql = "UPDATE lead_request set "
  . "status = "   . $conn->quote($new_status, 'text')  .  ", "
  . "reason = "   . $conn->quote($reason, 'text')  .  ", "
  . "approver = " . $conn->quote($approver, 'text')
  . "where id = "  . $conn->quote($request_id, 'text');
  $db_response = db_execute_statement($sql, "update lead request id#:" . $request_id);
  $db_error = $db_response[RESPONSE_ARGUMENT::OUTPUT];
  print $db_error == "" ? "response successfully stored " : "response could not be stored because of db error: " . $db_error;
}

?>

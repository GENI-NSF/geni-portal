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
include_once('/etc/geni-ch/settings.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  exit();
}

// This admin functionality is for OPERATORS only
if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

// Handle the HTTP request to figure out which LEAD request we're dealing with 
if (array_key_exists('request_id', $_REQUEST) && array_key_exists('new_status', $_REQUEST) && array_key_exists('user_uid', $_REQUEST)) {
  $request_id = $_REQUEST['request_id'];
  $new_status = $_REQUEST['new_status'];
  $user_uid = $_REQUEST['user_uid'];
  if (array_key_exists('reason', $_REQUEST)) {
    $reason = $_REQUEST['reason'];
  } else {
    $reason = "No reason";
  }
  $approver = $user->prettyName();
  handle_lead_request($request_id, $new_status, $approver, $user_uid, $reason, $user);
} else {
  if (array_key_exists('request_id', $_REQUEST) && array_key_exists('notes', $_REQUEST)) {
    add_request_note($_REQUEST['request_id'], $_REQUEST['notes']);
  } else {
    print "Failed to handle request due to incomplete information.";
  }
}

// Update the lead_request db row identified by $request_id with $new_note
function add_request_note($request_id, $new_note) 
{
  $conn = portal_conn();
  $sql = "UPDATE lead_request set "
  . "notes = "   . $conn->quote($new_note, 'text')
  . "where id = "  . $conn->quote($request_id, 'text');
  $db_response = db_execute_statement($sql, "update lead request note for request id#: " . $request_id);
  $db_error = $db_response[RESPONSE_ARGUMENT::OUTPUT];
  if($db_error == ""){
    print "Response successfully stored";
  } else {
    print "DB error: " . $db_error;
    error_log("DB error when adding note to lead request table: " . $db_error);
  }
}

// Update the lead_request db row identified by $request_id with $new_status, 
// $approver, $user_uid, and $reason
function handle_lead_request($request_id, $new_status, $approver, $user_uid, $reason, $signer) 
{
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $conn = portal_conn();
  if ($new_status == "approved") {
    $response = add_member_privilege($ma_url, $signer, $user_uid, "PROJECT_LEAD");
    if (!$response) {
      error_log("User $user_uid already a project lead, cannot be made a project lead");
    } else {
      send_approved_mail(geni_load_user_by_member_id($user_uid), $reason, $approver);
    }
  }
  $sql = "UPDATE lead_request set "
  . "status = "   . $conn->quote($new_status, 'text')  .  ", "
  . "reason = "   . $conn->quote($reason, 'text')  .  ", "
  . "approver = " . $conn->quote($approver, 'text')
  . "where id = " . $conn->quote($request_id, 'text');
  $db_response = db_execute_statement($sql, "Update lead request id#:" . $request_id);
  $db_error = $db_response[RESPONSE_ARGUMENT::OUTPUT];
  if($db_error == ""){
    print "Response successfully stored";
  } else {
    print "DB error: " . $db_error;
    error_log("DB error when updating lead request table: " . $db_error);
  }
}

// Send email to admins about the fact that $new_lead was approved because of $reason
function send_approved_mail($new_lead, $reason, $approver) 
{
  global $portal_admin_email;
  $pretty_name = $new_lead->prettyName();
  $body = "$pretty_name approved to be project lead by $approver. \r\n";
  $body .= "Approved because: " . $reason . "\r\n";
  $body .= "Their username: " . $new_lead->username . "\r\n";
  $body .= "Their email: " . $new_lead->email() . "\r\n";
  $body .= "Their reason: " . $new_lead->reason() . "\r\n";
  $body .= "Their link: " . $new_lead->url() . "\r\n";
  $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
  $headers .= "Content-Transfer-Encoding: 8bit\r\n";
  $to = $portal_admin_email;
  $subject = "Approved project lead request";
  mail($to, $subject, $body, $headers);
}

?>

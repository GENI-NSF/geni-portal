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
require_once('pa_constants.php');
require_once('sa_constants.php');
require_once('pa_client.php');
require_once('sa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');


// Check the selections from the handle-project-request are valid
// If so, add the approved members, resolve the requests and 
// send emails (positive or negative) to the requestors.


$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}

if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}

// error_log("REQUEST = " . print_r($_REQUEST, true));

$project_id = $_REQUEST['project_id'];
unset($_REQUEST['project_id']);

$project_name = $_REQUEST['project_name'];
unset($_REQUEST['project_name']);

$selections = $_REQUEST;

// error_log("SELECTIONS = " . print_r($selections, true));

$project_details = lookup_project($pa_url, $user, $project_id);
$project_name = $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
$lead_id = $project_details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];

$lead_name = lookup_member_names($ma_url, $user, array($lead_id));
$lead_name = $lead_name[$lead_id];

$selections = $_REQUEST;

$num_members_added = 0;
$num_members_rejected = 0;

foreach($selections as $select_id => $attribs) {
  $attribs_parts = explode(',', $attribs);
  $role = $attribs_parts[0];
  $member_id = $attribs_parts[1];
  $request_id = $attribs_parts[2];
  $email_address = $attribs_parts[3];
  // error_log("Email " . $email_address . " Attribs " . print_r($attribs, true));
  $resolution_status = RQ_REQUEST_STATUS::APPROVED;
  $resolution_status_label = "approved";
  $resolution_description = "";
  $email_subject = "Request " . print_r($request_id, true) . 
    " to join project " . $project_name;
  if ($role <= 0) {
    // This is a 'do not add' selection
    // Send rejection letter 
    $num_members_rejected = $num_members_rejected + 1;
    $resolution_description = "Request rejected";
    $resolution_status_label = "rejected";
    $resolution_status = RQ_REQUEST_STATUS::REJECTED;
  } else {
    $num_members_added = $num_members_added + 1;
    $resolution_description = "Added as " . $CS_ATTRIBUTE_TYPE_NAME[$role];
    // This is an 'add' selection
    // Add member 
    add_project_member($pa_url, $user, $project_id, $member_id, $role);
    // and send acceptance letter
  }
  // Resolve pending request
  resolve_pending_request($pa_url, $user, $request_id, 
			  $resolution_status, $resolution_description);

  // Send acceptance/rejection letter
  $email_message  = "Your request to join project " . $project_name . 
    " has been " . $resolution_status_label;
  mail($email_address, $email_subject, $email_message);

}


$_SESSION['lastmessage'] = "Added $num_members_added members; Rejected $num_members_rejected members"; 

relative_redirect("project.php?project_id=".$project_id); 

?>


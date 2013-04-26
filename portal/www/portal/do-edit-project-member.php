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
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');


// Check the selections from the edit-project-member table pull-downs
// Validate that there must always be exaclty one lead.
// Create the various calls to the PA:
//    change_lead
//    add_project_member
//    remove_project_member
//    change_member_role
// Return success or failure depending on results from these calls   


$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}

// Ensure that the new roles maintains a single project lead
// Additionally, one can't remove one's self as a member of a project
//   (someone has to do it for you).
function validate_project_member_requests($project_members_by_role, $selections)
{
  global $user;

  //  error_log("PMBR = " . print_r($project_members_by_role, True));
  //  error_log("SELS = " . print_r($selections, True));

  $excluding_self = False;
  foreach($selections as $member_id => $sel) {
    if($user->account_id == $member_id && 
       $sel == 0 && 
       array_key_exists($member_id, $project_members_by_role)) {
      $excluding_self = True;
      break;
    }
  }
  if ($excluding_self) {
    return array('success' => False, 'text' => "Cannot remove self from project");
  }

  // Count the number of people that are slated to be lead
  $lead_count = 0;
  foreach($selections as $member_id => $sel) {
    if ($sel == CS_ATTRIBUTE_TYPE::LEAD) { // Changing to or maintaining a lead
      $lead_count += 1;
    }
  }
  // See if there are any current members they are trying to change to lead
  // See if there are any non-members they are trying to add as lead
  // Total number of leads must be exactly 1
  if ($lead_count == 1) {
    $message = '';
    $success = True;
  } else {
    $message = "Number of leads for project must be exactly 1.";
    $success = False;
  }
    
  //  error_log("SUCCESS = $success, TEXT = $message");
  return array('success' => $success, 'text' => $message);
}

function modify_project_membership($project_id, $member_id, $selection_id, 
				 $project_members_by_role, $is_member)
{
  global $pa_url;
  global $user;
  //   error_log("MSM = " . $member_id . " " . $selection_id . " " . $is_member);

  if($is_member) {
    $role = $project_members_by_role[$member_id];
    if ($selection_id == 0) {
      // Remove this member from this project
      remove_project_member($pa_url, $user, $project_id, $member_id);
    } else if ($selection_id != $role) {
      // Change the role of this member in this project
      change_member_role($pa_url, $user, $project_id, $member_id, $selection_id);
    }
  } else {
    if ($selection_id > 0) {
      // Add this member to this project
      add_project_member($pa_url, $user, $project_id, $member_id, $selection_id);
    }
  }
}

$project_id = $_REQUEST['project_id'];
unset($_REQUEST['project_id']);
$project_members = get_project_members($pa_url, $user, $project_id);
$project_members_by_role = array();
foreach($project_members as $project_member) {
  $project_member_id = $project_member['member_id'];
  $project_member_role = $project_member['role'];
  $project_members_by_role[$project_member_id] = $project_member_role;
}
$selections = $_REQUEST;

$validation_result = validate_project_member_requests($project_members_by_role, $selections);
$success = $validation_result['success'];
if($success) {
  foreach($selections as $member_id => $selection_id) {
    $is_member = array_key_exists($member_id, $project_members_by_role);
    modify_project_membership($project_id, $member_id, $selection_id, 
			    $project_members_by_role, 
			    $is_member);
  }
  $_SESSION['lastmessage'] = "Project membership successfully changed.";
} else {
  $result = $validation_result['text'];
  $_SESSION['lasterror'] = $result;
}

relative_redirect("project.php?project_id=".$project_id);

?>


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
require_once("util.php");
require_once("sa_client.php");
require_once("pa_client.php");
require_once('cs_constants.php');
require_once('sr_constants.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  exit();
}

// This admin functionality is for OPERATORS only
if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);

// Handle the request, determine which action to perform
if (array_key_exists('action', $_REQUEST)) {
  $action = $_REQUEST['action'];
  if ($action == "remove"){
    if (array_key_exists('project_id', $_REQUEST) && array_key_exists('member_id', $_REQUEST)) {
      $project_id = $_REQUEST['project_id'];
      $member_id = $_REQUEST['member_id'];
      $project_members = get_project_members($sa_url, $user, $project_id);
      $project_members_by_role = array();
      foreach($project_members as $project_member) {
        $project_member_id = $project_member['member_id'];
        $project_member_role = $project_member['role'];
        $project_members_by_role[$project_member_id] = $project_member_role;
      }
      remove_member_from_project_and_slices($member_id, $project_id, $project_members_by_role, $user, $sa_url);
    } else {
      print "Insufficient information given to remove user";
      exit();
    }
  }
} else {
  print "No action requested, exiting";
  exit();
}

// Remove member given by $member_id from project given by $project_id, removing them from slices on that project too  
function remove_member_from_project_and_slices($member_id, $project_id, $project_members_by_role, $signer, $sa_url)
{
  if ($project_members_by_role[$member_id] == CS_ATTRIBUTE_TYPE::LEAD) {
    print "Cannot remove project lead from a project";
    return;
  }
  // Get the project slices and memberships
  $slice_members = get_slice_members_for_project($sa_url, $signer, $project_id);
  foreach($slice_members as $slice_member) {
    $removed_lead = false;
    $slice_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID];
    $slice_member_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    $slice_member_role = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
    if ($slice_member_id == $member_id) {
      error_log("Removing " . $slice_member_id . " from slice " . $slice_id . " " . $slice_member_role);
      if ($slice_member_role == CS_ATTRIBUTE_TYPE::LEAD) {
        $removed_lead = true;
      }
      remove_slice_member($sa_url, $signer, $slice_id, $slice_member_id);
    }
    if($removed_lead) {
      error_log("Removed slice lead : " . $slice_id . " " . $member_id);
      add_project_lead_as_slice_lead($slice_id, $project_members_by_role, $slice_members, $signer, $sa_url);
    }
  }

  error_log("Removing " . $member_id . " from project " . $project_id);
  remove_project_member($sa_url, $signer, $project_id, $member_id);
  print "Successfully removed member.";
}

// If project lead is already a member, make into lead, otherwise add to the slice as a lead
function add_project_lead_as_slice_lead($slice_id, $project_members_by_role, $slice_members, $signer, $sa_url)
{
  $project_lead = null;
  foreach($project_members_by_role as $pm => $pm_role) {
    if($pm_role == CS_ATTRIBUTE_TYPE::LEAD) {
      $project_lead = $pm;
      break;
    }
  }
  $project_lead_is_slice_member = false;
  foreach($slice_members as $slice_member) {
    $slice_member_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    if ($slice_member_id == $project_lead) {
      $project_lead_is_slice_member = true;
      break;
    }
  }
  if($project_lead_is_slice_member) {
    error_log("Changing " . $project_lead . " to lead of slice " . $slice_id);
    change_slice_member_role($sa_url, $signer, $slice_id, $project_lead, CS_ATTRIBUTE_TYPE::LEAD);
  } else {
    error_log("Adding " . $project_lead . " to lead of slice " . $slice_id);
    add_slice_member($sa_url, $signer, $slice_id, $project_lead, CS_ATTRIBUTE_TYPE::LEAD);
  }
}

?>
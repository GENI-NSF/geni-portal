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

// Client-side interface to GENI Clearinghouse Project Authority (PA)
// Consists of these methods:
//   project_id <= create_project(pa_url, project_name, lead_id, lead_email, purpose)
//   delete_project(pa_url, project_id);
//   project_ids <= get_projects(pa_url);
//   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
//   update_project(pa_url, project_name, project_id, project_email, project_purpose, expiration);
//   change_lead(pa_url, project_id, previous_lead_id, new_lead_id);
//   add_project_member(pa_url, project_id, member_id, role)
//   remove_project_member(pa_url, project_id, member_id)
//   change_member_role(pa_url, project_id, member_id, role)
//   get_project_members(pa_url, project_id, role=null) // null => Any
//   get_projects_for_member(pa_url, member_id, is_member, role=null)

require_once('pa_constants.php');
require_once('message_handler.php');

// Create a project with given name, lead_id (UUID of lead member), email to contact on all 
// matters related to project, and documentation purpose of project
function create_project($pa_url, $signer, $project_name, $lead_id, $project_purpose)
{
  $create_project_message['operation'] = 'create_project';
  $create_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $create_project_message[PA_ARGUMENT::LEAD_ID] = $lead_id;
  $create_project_message[PA_ARGUMENT::PROJECT_PURPOSE] = $project_purpose;

  // error_log("CP.args = " . print_r($create_project_message, true) . " " . $create_project_message);

  // FIXME: Disallow if project_name already taken!

  $project_id = put_message($pa_url, $create_project_message, 
			    $signer->certificate(), $signer->privateKey());
  return $project_id;
}

// Delete given projectt of given ID
function delete_project($pa_url, $signer, $project_id)
{
  $delete_project_message['operation'] = 'delete_project';
  $delete_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $result = put_message($pa_url, $delete_project_message, 
			$signer->certificate(), $signer->privateKey());
  return $result;
}

// return list of project ids
function get_projects($pa_url, $signer)
{
  $get_projects_message['operation'] = 'get_projects';
  $project_ids = put_message($pa_url, $get_projects_message, 
			     $signer->certificate(), $signer->privateKey());
  return $project_ids;
}

// return list of project ids
function get_projects_by_lead($pa_url, $signer, $lead_id)
{
  //  error_log("GPBL.start " . $lead_id . " " . time());
  $get_projects_message['operation'] = 'get_projects';
  $get_projects_message[PA_ARGUMENT::LEAD_ID] = $lead_id;
  $project_ids = put_message($pa_url, $get_projects_message, 
			     $signer->certificate(), $signer->privateKey());
  //  error_log("GPBL.end " . $lead_id . " " . time());
  return $project_ids;
}

// Return project details
function lookup_projects($pa_url, $signer, $lead_id=null)
{
  $lookup_projects['operation'] = 'lookup_projects';
  if( $lead_id <> null) {
    $lookup_projects[PA_ARGUMENT::LEAD_ID] = $lead_id;
  }
  $projects = put_message($pa_url, $lookup_projects, 
			  $signer->certificate(), $signer->privateKey());
  return $projects;
}

// Return project details
function lookup_project($pa_url, $signer, $project_id)
{
  if (! is_object($signer)) {
    throw new InvalidArgumentException('Null signer');
  }
  $cert = $signer->certificate();
  $key = $signer->privateKey();
  //  error_log("LP.start " . $project_id . " " . time());
  $lookup_project_message['operation'] = 'lookup_project';
  $lookup_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $details = put_message($pa_url, $lookup_project_message,
			 $cert, $key, 
			 $signer->certificate(), $signer->privateKey());
  //  error_log("LP.end " . $project_id . " " . time());
  // FIXME: Could be >1?
  return $details;
}

// Return project details
function lookup_project_by_name($pa_url, $signer, $project_name)
{
  if (! is_object($signer)) {
    throw new InvalidArgumentException('Null signer');
  }
  $cert = $signer->certificate();
  $key = $signer->privateKey();
  //  error_log("LP.start " . $project_name . " " . time());
  $lookup_project_message['operation'] = 'lookup_project';
  $lookup_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $details = put_message($pa_url, $lookup_project_message,
			 $cert, $key, 
			 $signer->certificate(), $signer->privateKey());
  //  error_log("LP.end " . $project_id . " " . time());
  // FIXME: Could be >1?
  return $details;
}

// FIXME: lookup_projects_member(pa_url, member_id, is_member, role)
// FIXME: lookup_projects_ids(pa_url, project_ids_list)

function update_project($pa_url, $signer, $project_id, $project_name,
        $project_purpose, $expiration)
{
  $update_project_message['operation'] = 'update_project';
  $update_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $update_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $update_project_message[PA_ARGUMENT::PROJECT_PURPOSE] = $project_purpose;
  $update_project_message[PA_ARGUMENT::EXPIRATION] = $expiration;
  $results = put_message($pa_url, $update_project_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

function change_lead($pa_url, $signer, $project_id, $prev_lead_id, $new_lead_id)
{
  $change_lead_message['operation'] = 'change_lead';
  $change_lead_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $change_lead_message[PA_ARGUMENT::PREVIOUS_LEAD_ID] = $prev_lead_id;
  $change_lead_message[PA_ARGUMENT::LEAD_ID] = $new_lead_id;
  $results = put_message($pa_url, $change_lead_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Add a member of given role to given project
// return code/value/output triple
function add_project_member($pa_url, $signer, $project_id, $member_id, $role)
{
  $add_project_member_message['operation'] = 'add_project_member';
  $add_project_member_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $add_project_member_message[PA_ARGUMENT::MEMBER_ID] = $member_id;
  $add_project_member_message[PA_ARGUMENT::ROLE_TYPE] = $role;
  $results = put_message($pa_url, $add_project_member_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Remove a member from given project 
function remove_project_member($pa_url, $signer, $project_id, $member_id)
{
  $remove_project_member_message['operation'] = 'remove_project_member';
  $remove_project_member_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $remove_project_member_message[PA_ARGUMENT::MEMBER_ID] = $member_id;
  $results = put_message($pa_url, $remove_project_member_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Change role of given member in given project
function change_member_role($pa_url, $signer, $project_id, $member_id, $role) 
{
  $change_member_role_message['operation'] = 'change_member_role';
  $change_member_role_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $change_member_role_message[PA_ARGUMENT::MEMBER_ID] = $member_id;
  $change_member_role_message[PA_ARGUMENT::ROLE_TYPE] = $role;
  $results = put_message($pa_url, $change_member_role_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Return list of member ID's and roles associated with given project
// If role is provided, filter to members of given role
function get_project_members($pa_url, $signer, $project_id, $role=null) 
{
  $get_project_members_message['operation'] = 'get_project_members';
  $get_project_members_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $get_project_members_message[PA_ARGUMENT::ROLE_TYPE] = $role;
  $results = put_message($pa_url, $get_project_members_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Return list of project ID's for given member_id
// If is_member is true, return projects for which member is a member
// If is_member is false, return projects for which member is NOT a member
// If role is provided, filter on projects 
//    for which member has given role (is_member = true)
//    for which member does NOT have given role (is_member = false)
function get_projects_for_member($pa_url, $signer, $member_id, $is_member, $role=null)
{
  if (! is_object($signer)) {
    throw new InvalidArgumentException('Null signer');
  }
  $cert = $signer->certificate();
  $key = $signer->privateKey();
  $get_projects_message['operation'] = 'get_projects_for_member';
  $get_projects_message[PA_ARGUMENT::MEMBER_ID] = $member_id;
  $get_projects_message[PA_ARGUMENT::IS_MEMBER] = $is_member;
  $get_projects_message[PA_ARGUMENT::ROLE_TYPE] = $role;
  $results = put_message($pa_url, $get_projects_message,
			 $cert, $key, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}


?>

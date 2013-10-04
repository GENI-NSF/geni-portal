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
//   project_id <= create_project(sa_url, project_name, lead_id, lead_email, purpose, expiration);
//   project_ids <= get_projects(sa_url);
//   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
//   update_project(sa_url, project_name, project_id, project_email, project_purpose, expiration);
//   change_lead(sa_url, project_id, previous_lead_id, new_lead_id);
//   get_project_members(sa_url, project_id, role=null) // null => Any
//   get_projects_for_member(sa_url, member_id, is_member, role=null)
//   lookup_project_details(sa_url, project_uuids)
//   modify_project_membership(sa_url, signer, project_id, 
//			 members_to_add, members_to_change_role, members_to_remove)
//   add_project_member(sa_url, project_id, member_id, role)
//   remove_project_member(sa_url, project_id, member_id)
//   change_member_role(sa_url, project_id, member_id, role)
//   lookup_project_attributes(sa_url, project_id)
//   add_project_attribute(sa_url, signer, project_id, name, value)


require_once('pa_constants.php');
require_once('message_handler.php');

// A cache of a user's detailed info indexed by member_id
if(!isset($project_cache)) {
  //  error_log("SETTING PROJECT_CACHE");
  $project_cache = array();
}

// Create a project with given name, lead_id (UUID of lead member), email to contact on all 
// matters related to project, and documentation purpose of project
function create_project($sa_url, $signer, $project_name, $lead_id, $project_purpose, $expiration)
{
  include_once('irods_utils.php');
  $create_project_message['operation'] = 'create_project';
  $create_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $create_project_message[PA_ARGUMENT::LEAD_ID] = $lead_id;
  $create_project_message[PA_ARGUMENT::PROJECT_PURPOSE] = $project_purpose;
  // Normalize expiration to an empty string, allowing the arg
  // to be NULL, 0, etc.
  if (! $expiration) {
    $expiration = "";
  }
  $create_project_message[PA_ARGUMENT::EXPIRATION] = $expiration;

  // error_log("CP.args = " . print_r($create_project_message, true) . " " . $create_project_message);

  // FIXME: Disallow if project_name already taken!

  $project_id = put_message($sa_url, $create_project_message, 
			    $signer->certificate(), $signer->privateKey());

  /****   iRODS Support ****/
  // All new projects get an irods group
  $created = irods_create_group($project_id, $project_name, $signer);
  if ($created === -1) {
    error_log("FAILED to create iRODS group for new project $project_name");
  }
  /**** End of iRODS Support ***/

  return $project_id;
}

// return list of project ids
function get_projects($sa_url, $signer)
{
  $get_projects_message['operation'] = 'get_projects';
  $project_ids = put_message($sa_url, $get_projects_message, 
			     $signer->certificate(), $signer->privateKey());
  return $project_ids;
}

// return list of project ids
function get_projects_by_lead($sa_url, $signer, $lead_id)
{
  //  error_log("GPBL.start " . $lead_id . " " . time());
  $get_projects_message['operation'] = 'get_projects';
  $get_projects_message[PA_ARGUMENT::LEAD_ID] = $lead_id;
  $project_ids = put_message($sa_url, $get_projects_message, 
			     $signer->certificate(), $signer->privateKey());
  //  error_log("GPBL.end " . $lead_id . " " . time());
  return $project_ids;
}

// Return project details
function lookup_projects($sa_url, $signer, $lead_id=null)
{
  $lookup_projects['operation'] = 'lookup_projects';
  if( $lead_id <> null) {
    $lookup_projects[PA_ARGUMENT::LEAD_ID] = $lead_id;
  }
  $projects = put_message($sa_url, $lookup_projects, 
			  $signer->certificate(), $signer->privateKey());
  return $projects;
}

// Return project details
function lookup_project($sa_url, $signer, $project_id)
{
  global $project_cache;
  if (! is_object($signer)) {
    throw new InvalidArgumentException('Null signer');
  }

  if (array_key_exists($project_id, $project_cache)) {
    //    error_log("CACHE HIT lookup_project " . $project_id);
    return $project_cache[$project_id];
  }
  $cert = $signer->certificate();
  $key = $signer->privateKey();
  //  error_log("LP.start " . $project_id . " " . time());
  $lookup_project_message['operation'] = 'lookup_project';
  $lookup_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $details = put_message($sa_url, $lookup_project_message,
			 $cert, $key, 
			 $signer->certificate(), $signer->privateKey());
  //  error_log("LP.end " . $project_id . " " . time());
  // FIXME: Could be >1?
  $project_cache[$project_id] = $details;
  
  return $details;
}

// Return project details
function lookup_project_by_name($sa_url, $signer, $project_name)
{
  if (! is_object($signer)) {
    throw new InvalidArgumentException('Null signer');
  }
  $cert = $signer->certificate();
  $key = $signer->privateKey();
  //  error_log("LP.start " . $project_name . " " . time());
  $lookup_project_message['operation'] = 'lookup_project';
  $lookup_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $details = put_message($sa_url, $lookup_project_message,
			 $cert, $key, 
			 $signer->certificate(), $signer->privateKey());
  //  error_log("LP.end " . $project_id . " " . time());
  // FIXME: Could be >1?
  return $details;
}

// FIXME: lookup_projects_member(sa_url, member_id, is_member, role)
// FIXME: lookup_projects_ids(sa_url, project_ids_list)

function update_project($sa_url, $signer, $project_id, $project_name,
        $project_purpose, $expiration)
{
  $update_project_message['operation'] = 'update_project';
  $update_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $update_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $update_project_message[PA_ARGUMENT::PROJECT_PURPOSE] = $project_purpose;
  $update_project_message[PA_ARGUMENT::EXPIRATION] = $expiration;
  $results = put_message($sa_url, $update_project_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Modify project membership according to given lists to add/change_role/remove
// $members_to_add and $members_to_change role are both
//     dictionaries of {member_id => role, ....}
// $members_to_delete is a list of member_ids
function modify_project_membership($sa_url, $signer, $project_id, 
				 $members_to_add, 
				 $members_to_change_role, 
				 $members_to_remove)
{
  include_once('irods_utils.php');
  $modify_project_membership_msg['operation'] = 'modify_project_membership';
  $modify_project_membership_msg[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $modify_project_membership_msg[SA_ARGUMENT::MEMBERS_TO_ADD] = $members_to_add;
  $modify_project_membership_msg[SA_ARGUMENT::MEMBERS_TO_CHANGE_ROLE] = $members_to_change_role;
  $modify_project_membership_msg[SA_ARGUMENT::MEMBERS_TO_REMOVE] = $members_to_remove;
  $result = put_message($sa_url, $modify_project_membership_msg,
                       $signer->certificate(), $signer->privateKey());

  /****   iRODS Support ****/
  // Whenever we add/remove members from a project, do same for the matching irods group
  irods_modify_group_members($project_id, $members_to_add, $members_to_remove, $signer, $result);
  /****   End of iRODS Support ****/

  return $result;  
}

// Modify project lead, make previous into an admin
// Assumes lead is already a member
function change_lead($sa_url, $signer, $project_id, $prev_lead_id, $new_lead_id)
{
  $members_to_change = array($prev_lead_id => CS_ATTRIBUTE_TYPE::ADMIN, 
			     $new_lead_id => CS_ATTRIBUTE_TYPE::LEAD);
  $result = modify_project_membership($sa_url, $signer, $project_id,
				      array(), $members_to_change, array());
  return $result;
}

// Add a member of given role to given project
function add_project_member($sa_url, $signer, $project_id, $member_id, $role)
{
  $member_roles = array($member_id => $role);
  $result = modify_project_membership($sa_url, $signer, $project_id, 
				    $member_roles, array(), array());
  return $result;
}

// Remove a member from given project
function remove_project_member($sa_url, $signer, $project_id, $member_id)
{
  $member_to_remove = array($member_id);
  $result = modify_project_membership($sa_url, $signer, $project_id, 
				    array(), array(), $member_to_remove);
  return $result;
}

// Change role of given member in given project
function change_member_role($sa_url, $signer, $project_id, $member_id, $role)
{
  $member_roles = array($member_id => $role);
  $result = modify_project_membership($sa_url, $signer, $project_id, 
				    array(), $member_roles, array());
  return $result;
}

// Return list of member ID's and roles associated with given project
// If role is provided, filter to members of given role
function get_project_members($sa_url, $signer, $project_id, $role=null) 
{
  $get_project_members_message['operation'] = 'get_project_members';
  $get_project_members_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $get_project_members_message[PA_ARGUMENT::ROLE_TYPE] = $role;
  $results = put_message($sa_url, $get_project_members_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Return list of project ID's for given member_id
// If is_member is true, return projects for which member is a member
// If is_member is false, return projects for which member is NOT a member
// If role is provided, filter on projects 
//    for which member has given role (is_member = true)
//    for which member does NOT have given role (is_member = false)
function get_projects_for_member($sa_url, $signer, $member_id, $is_member, $role=null)
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
  $results = put_message($sa_url, $get_projects_message,
			 $cert, $key, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

function lookup_project_details($sa_url, $signer, $project_uuids)
{
  $cert = $signer->certificate;
  $key = $signer->privateKey();
  $get_projects_message['operation'] = 'lookup_project_details';
  $get_projects_message[PA_ARGUMENT::PROJECT_UUIDS] = $project_uuids;
  $results = put_message($sa_url, $get_projects_message,
			 $cert, $key, 
			 $signer->certificate(), $signer->privateKey());
			 
  $results2 = array();
  foreach ($results as $project) {
  	  $results2[ $project['project_id'] ] = $project;
  }			 
  return $results2;
}

// Routines to invite and accept invitations for members to projects

// Generate an invitation for a (not yet identified) member
// to join a project
// return the invitation ID and expiration, `
function invite_member($sa_url, $signer, $project_id, $role)
{
  $invite_member_message['operation'] = 'invite_member';
  $invite_member_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $invite_member_message[PA_ARGUMENT::ROLE_TYPE] = $role;
  $result = put_message($sa_url, $invite_member_message, 
		       $signer->certificate(), $signer->privateKey());
  return $result;
}

// Accept an invitation
function accept_invitation($sa_url, $signer, $invitation_id)
{
  global $user;
  $accept_invitation_message['operation'] = 'accept_invitation';
  $accept_invitation_message[PA_ARGUMENT::INVITATION_ID] = $invitation_id;
  $accept_invitation_message[PA_ARGUMENT::MEMBER_ID] = $user->account_id;
  $result = put_message($sa_url, $accept_invitation_message, 
			$signer->certificate(), $signer->privateKey());
  return $result;
}

// Look up all attributes of a given project
function lookup_project_attributes($sa_url, $signer, $project_id)
{
  global $user;
  $lookup_project_attributes_message['operation'] = 'lookup_project_attributes';
  $lookup_project_attributes_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $results = put_message($sa_url, $lookup_project_attributes_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Add attribute (name/value pair) to a given project
function add_project_attribute($sa_url, $signer, $project_id, $name, $value)
{
  global $user;
  $add_project_attribute_message['operation'] = 'add_project_attribute';
  $add_project_attribute_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $add_project_attribute_message[PA_ATTRIBUTE::NAME] = $name;
  $add_project_attribute_message[PA_ATTRIBUTE::VALUE] = $value;
  $results = put_message($sa_url, $add_project_attribute_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}

// Remove attribute (name) from a given project
function remove_project_attribute($sa_url, $signer, $project_id, $name)
{
  global $user;
  $remove_project_attribute_message['operation'] = 'remove_project_attribute';
  $remove_project_attribute_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $remove_project_attribute_message[PA_ATTRIBUTE::NAME] = $name;
  $results = put_message($sa_url, $remove_project_attribute_message, 
			 $signer->certificate(), $signer->privateKey());
  return $results;
}


?>

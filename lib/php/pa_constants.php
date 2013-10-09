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

/* Set of constants for managing calls and data records for 
 * GENI Clearinghouse Project Authority
 */

/* Set of arguments in calls to PA interface */
class PA_ARGUMENT 
{
  const PROJECT_ID = "project_id";
  const PROJECT_NAME = "project_name";
  const LEAD_ID = "lead_id";
  const PREVIOUS_LEAD_ID = "previous_lead_id";
  const PROJECT_EMAIL = "project_email";
  const PROJECT_PURPOSE  = "project_purpose";
  const MEMBER_ID = "member_id";
  const IS_MEMBER = "is_member";
  const ROLE_TYPE = "role_type";
  const CREATION = "creation";
  const EXPIRATION = "expiration";
  const PROJECT_UUIDS = "project_uuids";
  const INVITATION_ID = "invitation_id";
  const MEMBERS_TO_ADD = "members_to_add";
  const MEMBERS_TO_CHANGE_ROLE = "members_to_change_role";
  const MEMBERS_TO_REMOVE = "members_to_remove";
}

/* Name of table containing per-project info */
$PA_PROJECT_TABLENAME = "pa_project";

class PA_PROJECT_TABLE_FIELDNAME 
{
  const NAME = "name";
  const PROJECT_ID = "project_id";
  const PROJECT_NAME = "project_name";
  const LEAD_ID = "lead_id";
  const PROJECT_EMAIL = "project_email";
  const PROJECT_PURPOSE = "project_purpose";
  const CREATION = "creation";
  const EXPIRATION = "expiration";
  const EXPIRED = "expired";
}

/* Name of table containing project membership info */
$PA_PROJECT_MEMBER_TABLENAME = "pa_project_member";

class PA_PROJECT_MEMBER_TABLE_FIELDNAME
{
  const PROJECT_ID = "project_id";
  const MEMBER_ID = "member_id";
  const ROLE = "role";
}

/* Name of table containing pending project membership requests */
$PA_PROJECT_MEMBER_REQUEST_TABLENAME = 'pa_project_member_request';


/* PA Actions on which privileges are enabled/disabled */
/* Should match the set of privileges in the cs_action table */
class PA_ACTION {
  const CREATE_PROJECT = 'create_project';
  const GET_PROJECTS = 'get_projects';
  const GET_PROJECT_BY_LEAD = 'get_project_by_lead';
  const LOOKUP_PROJECT = 'lookup_project';
  const LOOKUP_PROJECTS = 'lookup_projects';
  const UPDATE_PROJECT = 'update_project';
  const CHANGE_LEAD = 'change_lead';
  const ADD_PROJECT_MEMBER = 'add_project_member';
  const REMOVE_PROJECT_MEMBER = 'remove_project_member';
  const CHANGE_MEMBER_ROLE = 'change_member_role';
  const GET_PROJECT_MEMBERS = 'get_project_members';
  const GET_PROJECTS_FOR_MEMBER= 'get_projects_for_member';
  const LOOKUP_PROJECT_DETAILS= 'lookup_project_details';
  const LOOKUP_PROJECT_ATTRIBUTES= 'lookup_project_attributes';
  const ADD_PROJECT_ATTRIBUTE= 'add_project_attribute';
  const INVITE_MEMBER = 'invite_member';
  const ACCEPT_INVITATION = 'accept_invitation';
}

// Table of PA project member invitations
$PA_PROJECT_MEMBER_INVITATION_TABLENAME = "pa_project_member_invitation";

class PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME {
  const INVITE_ID = "invite_id";
  const PROJECT_ID = "project_id";
  const ROLE = "role";
  const EXPIRATION = "expiration";
}

// How long to project invitaations have before they expire?
$project_default_invitation_expiration_hours = 72;

// Per the AM API V3:
// We want the project name to go without change into the authority part of the URN
// so do not use characters that get translated between URN and publicid. So:
// not empty
// no whitespace
// no / : + ; ' ? # %
// no leading/trailing whitespace
// collapse consecutive whitespace
// Additionally, ProtoGENI has a 32character sub-authority limit. So
// limit project names to 32 characters.
// We should also be excluding control characters.
// Technically we could then allow all kinds of unicode
// characters. But is that really necessary? For now, restrict project
// names pretty heavily
function is_valid_project_name($project_name)
{
  //  $pattern = '/^[^\/\:\+\;\'\?\#\% ]+$/';
  $pattern = '/^[a-zA-Z0-9][a-zA-Z0-9-_]{0,31}$/';
  return preg_match($pattern, $project_name);
}

/* Name of table containing project attributes */
$PA_PROJECT_ATTRIBUTE_TABLENAME = "pa_project_attribute";

class PA_ATTRIBUTE {
  const PROJECT_ID = "project_id";
  const NAME = "name";
  const VALUE = "value";
}

/* Add projects/tools here as necessary */
class PA_ATTRIBUTE_NAME {
  const ENABLE_WIMAX = "enable_wimax";
  const IRODS_GROUP_NAME = "irods_group_name";
}


?>

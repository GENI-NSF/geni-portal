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
 * GENI Clearinghouse Slice Authority
 */

/* Set of arguments to calls SA interface */
class SA_ARGUMENT
{
  const PROJECT_ID = "project_id";
  const PROJECT_NAME = "project_name";
  const SLICE_NAME = "slice_name";
  const SLICE_ID = "slice_id";
  const SLICE_URN = "slice_urn";
  const OWNER_ID = "owner_id";
  const SLICE_DESCRIPTION = "slice_description";
  const EXPIRATION = "expiration";
  const CREATION = "creation";
  const EXP_CERT = "experimenter_certificate";
  const SLICE_CREDENTIAL = "slice_credential";
  const USER_CREDENTIAL = "user_credential";
  const SLICE_EMAIL = "slice_email";
  const MEMBER_ID = "member_id";
  const IS_MEMBER = "is_member";
  const ROLE_TYPE = "role_type";
  const SLICE_UUIDS = "slice_uuids";
  const PROJECT_UUIDS = "project_uuids";
  const ALLOW_EXPIRED = "allow_expired";
  const MEMBERS_TO_ADD = "members_to_add";
  const MEMBERS_TO_CHANGE_ROLE = "members_to_change_role";
  const MEMBERS_TO_REMOVE = "members_to_remove";
}

/* Name of table containing slice info */
$SA_SLICE_TABLENAME = "sa_slice";

/* Fields in SA slice table */
class SA_SLICE_TABLE_FIELDNAME {
  const SLICE_ID = "slice_id";
  const SLICE_NAME = "slice_name";
  const PROJECT_ID = "project_id";
  const EXPIRATION = "expiration";
  const OWNER_ID = "owner_id";
  const SLICE_URN = "slice_urn";
  const SLICE_EMAIL = "slice_email";
  const CERTIFICATE = "certificate";
  const CREATION = "creation";
  const SLICE_DESCRIPTION = "slice_description";
  const EXPIRED = "expired";
}

/* Name of table containing slice membership info */
$SA_SLICE_MEMBER_TABLENAME = "sa_slice_member";

class SA_SLICE_MEMBER_TABLE_FIELDNAME
{
  const SLICE_ID = "slice_id";
  const MEMBER_ID = "member_id";
  const ROLE = "role";
}

/* Name of table containing pending slice membership requests */
$SA_SLICE_MEMBER_REQUEST_TABLENAME = 'sa_slice_member_request';

/* SA Actions on which privileges are enabled/disabled */
/* Should match the set of privileges in the cs_action table */
class SA_ACTION {
  const CREATE_SLICE = 'create_slice';
  const DELETE_SLICE = 'delete_slice';
  const LOOKUP_SLICE = 'lookup_slice';
  const LOOKUP_SLICES = 'lookup_slices';
  const LOOKUP_SLICE_IDS = 'lookup_slice_ids';
  const GET_SLICE_CREDENTIAL = 'get_slice_credential';
  const GET_USER_CREDENTIAL = 'get_user_credential';
  const ADD_SLIVERS = 'add_slivers';
  const DELETE_SLIVERS = 'delete_slivers';
  const RENEW_SLICE = 'renew_slice';
  const ADD_SLICE_MEMBER = 'add_slice_member';
  const REMOVE_SLICE_MEMBER = 'remove_slice_member';
  const CHANGE_SLICE_MEMBER_ROLE = 'change_slice_member_role';
  const GET_SLICE_MEMBERS = 'get_slice_members';
  const GET_SLICES_FOR_MEMBER =  'get_slices_for_member';
  const LOOKUP_SLICES_BY_IDS = 'lookup_slices_by_ids';
  const GET_SLICE_MEMBERS_FOR_PROJECT = 'get_slice_members_for_project';
  const LOOKUP_SLICE_BY_URN = 'lookup_slice_by_urn';
  const LOOKUP_SLICE_DETAILS = 'lookup_slice_details';
  const GET_SLICES_FOR_PROJECTS = 'get_slices_for_projects';

  // These aren't SA functions but AM functions on a slice
  const LIST_RESOURCES = 'list_resources';
}

// Per the AM API V3:
// A slice name must
// Have length < 19
// Consist of only alphanumerics and hyphens
// Not begin with a hyphen
function is_valid_slice_name($slice_name)
{
  $pattern = '/^[a-zA-Z0-9][-a-zA-Z0-9]{0,18}$/';
  return preg_match($pattern, $slice_name);
}



?>

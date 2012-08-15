<?php

namespace Slice_Authority;

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

/**
 * GENI Clearinghouse Slice Authority (SA) controller interface
 * The SA maintains a list of slices, their details and members
 * and provides access to creating, looking up, updating and renewing slices.
 * In addition, provides access to slice and user credentials for interacting
 * with slices within the AM API.
 * <br><br>
 * Supports these methods:
<ul>
<li>   slice_credental <= get_slice_credential(slice_id, experimenter_cert) </li>
<li>   user_credential <= get_user_credential(experimenter_cert) </li>
<li>   slice_id <= create_slice(slice_name, project_id, project_name, owner_id, description) </li>
<li>   [ids] <= lookup_slice_ids(project_id, [owner_id]) </li>
<li>   [id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) <= lookup_slices(project_id, owner_id) </li>
<li>   [id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) <= lookup_slice(slice_id) </li>
<li>   [id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) <= lookup_slice_by_urn(slice_urn) </li>
<li>   success <= renew_slice(slice_id, expiration) </li>
<li>   success <= add_slice_member(slice_id, member_id, role_type) </li>
<li>   success <= remove_slice_member(slice_id, member_id, role_type) </li>
<li>   success <= change_slice_member_role(slice_id, member_id, role_type) </li>
<li>   [member_id, role} <= get_slice_members(slice_id, role_type=null) // null => Any </li>
<li>   [slice_id, member_id, role] <= get_slice_members_for_project(project_id, role_type=null) // null => Any </li>
<li>   [id] <=get_slices_for_member(member_id, is_member, role=null) </li>
</ul>
 */
class Slice_Authority {

/**
 * Create a slice credential and return it 
 *
 * @param dict $args_dict 
 Dictionary containing name/value pairs:
<ul>
    <li>"operation" : name of this method ("get_slice_credential")</li>
    <li>"signer" : UUID of signer (asserter) of method/argument set</li>
    <li>"slice_id" : ID of slice for which to return credential</li>
    <li>"experimenter_cert" : Certificate of experimenter for whom to generate slice credential</li>
</ul>
 * @return slice_credential Slice credential for given experimenter and slice
 */
function get_slice_credential($args_dict)
{
}

/**
 * Create a user credential and return it 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
 <ul>
  <li>"operation" : name of this method ("get_user_credential")</li></li>
    <li>"signer" : UUID of signer (asserter) of method/argument set</li>
    <li>"experimenter_cert" : Certificate of experimenter for whom to generate slice credential</li>
 </ul>
 * @return user_credential User credential for given experimenter 
 */
function get_user_credential($args_dict)
{
}

/**
 * Create a slice for given project, name, urn, owner_id 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("create_slice")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument </li>
   <li>"slice_name" : name of slice to be created</li>
   <li>"project_id" : ID of project to which to associate slice</li>
   <li>"project_name" : name of project to which to associate slice</li>
   <li>"owner_id" : ID of owner of slice</li>
   <li>"description" : Description associated with slice</li>
</ul>
 * @return boolean Success / Failure
 */
function create_slice($args_dict)
{
}

/**
 * *** NOT IMPLEMENTED! ***
 * Make slice 'disabled' (that is, one can no longer allocate resources to it)
 * Whether this actually deletes the slice is implementation and poicy dependent.
 * <br><br>
 * Will return an error if this call is made when there are active slivers
 * on the given slice.
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("renew_slice")</li>
   <li>"signer" : UUID of signer (asserter) of method</li>
   <li>"slice_id" : ID of slice</li>
</ul>
 * @return boolean Success/Failure
 */
function disable_slice($args_dict)
{
}

/**
 * Renew slice of given ID with given new expiration time
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("renew_slice")</li>
   <li>"signer" : UUID of signer (asserter) of method</li>
   <li>"slice_id" : ID of slice</li>
   <li>"expiration : new expiration time of slice</li>
</ul>
 * @return boolean Success/Failure
 */
function renew_slice($args_dict)
{
}

/**
 * Add a member of given role to given slice
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("add_slice_member")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"slice_id" : ID of slice to be modified</li>
   <li>"member_id : ID of member to be associated with given slice</li>
   <li>"role_type" : role of member within slice</li>
</ul>
 * @return boolean Success/Failure
 */
function add_slice_member($args_dict)
{
}

/**
 * Remove a member from given slice
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("remove_slice_member")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"slice_id" : ID of slice to be modified</li>
   <li>"member_id : ID of member to be disassociated with given slice</li>
</ul>
 * @return boolean Success/Failure
 */
function remove_slice_member($args_dict)
{
}

/**
 * Change role of given member in given slice
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("change_member_role")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"slice_id" : ID of slice to be modified</li>
   <li>"member_id : ID of member whose role within slice is to be modified</li>
   <li>"role_type" : role to be associated with given member</li>
</ul>
 * @return dict ["code" => error_code, "value" => result, "output" =>error_info]
 */
function change_slice_member_role($args_dict)
{
}

/**
 * Lookup slice id's by project_id, owner_id and/or slice_name
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_slice_ids")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument </li>
   <li>"slice_name" : name of slice to be created [optional]</li>
   <li>"project_id" : ID of project to which to associate slice [optional]</li>
   <li>"owner_id" : ID of owner of slice [optional]</li>
</ul>
 * @return array List of slice ID's matching given criteria
 */
function lookup_slice_ids($args_dict)
{
}

/**
 * Lookup slice id's by project_id, owner_id and/or slice_name
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_slices")</li>
   <li>"signer" : UUID of signer (asserter) of method</li>
   <li>"project_id" : ID of project to which to associate slice [optional]</li>
   <li>"owner_id" : ID of owner of slice [optional]</li>
</ul>
 * @return array List of slice info tuples (id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) matching given criteria
 */
function lookup_slices($args_dict)
{
}

/**
 * Lookup slice info by slice id
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_slice")</li>
   <li>"signer" : UUID of signer (asserter) of method</li>
   <li>"slice_id" : ID of slice</li>
</ul>
 * @return dict Slice info tuple (id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) for given slice
 */
function lookup_slice($args_dict)
{
}

/**
 * Lookup slice info by slice URN
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_slice_by_urn")</li>
   <li>"signer" : UUID of signer (asserter) of method</li>
   <li>"slice_urn" : URN of slice</li>
</ul>
 * @return dict Slice info tuple (id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) for given slice
 */
function lookup_slice_by_urn($args_dict)
{
}

/**
 * Return list of member ID's and roles associated with given slice
 * If role is provided, filter to members of given role
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_slice_members")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"slice_id" : ID of slice to be modifiedb</li>
   <li>"role_type" : role to be associated with given member [optional]</li>
</ul>
 * @return array List of (member_id, role) tuples for members of given slice
 */
function get_slice_members($args)
{
}

/**
 * Return list of member ID's and roles associated with given slice
 * If role is provided, filter to members of given role
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_slice_members")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project of slices to be queried</li>
   <li>"role_type" : role to be associated with given member [optional]</li>
</ul>
 * @return array List of (slice_id, member_id, role) tuples for members of given slice
 */
function get_slice_members_for_project($args_dict)
{
}

/**
 * Return list of slice ID's for given member_id
 * 
 * Optionally indicate sense of 'is member' or 'is not member'
 * If is_member is true, return slices for which member is a member
 * If is_member is false, return slices for which member is NOT a member
 * 
 * Optionally indicate role type of member
 * If role is provided, filter on slices 
 *    for which member has given role (is_member = true)
 *    for which member does NOT have given role (is_member = false)
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_slices_for_member")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about which slices are being queried </li>
   <li>"is_member" : determines sense of 'member_id' query match [optional]</li>
   <li>"role_type" : role associated with given member [optional]</li>
</ul>
 * @return array List of ID's of given slices
 */
function get_slices_for_member($args_dict)
{
}

/**
 * Get the version of the API of this particular service provider
 * @param dict $args_dict Dictionary containing 'operation' and 'signer' arguments'
 * @return number Version of API of this particular service provider
 */
function get_version($args_dict)
{
}

}

?>
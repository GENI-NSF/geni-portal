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

/**
 * Create a slice credential and return it 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_slice_credential")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "slice_id" : ID of slice for which to return credential
 *   "experimenter_cert" : Certificate of experimenter for whom to generate slice credential
 * Return:
 *   Slice credential for given experimenter and slice
 */
function get_slice_credential($args)
{
}

/**
 * Create a user credential and return it 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_user_credential")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "experimenter_cert" : Certificate of experimenter for whom to generate slice credential
 * Return:
 *   User credential for given experimenter 
 */
function get_user_credential($args_dict)
{
}

/**
 * Create a slice for given project, name, urn, owner_id 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("create_slice")
 *   "signer" : UUID of signer (asserter) of method/argument 
 *   "slice_name" : name of slice to be created
 *   "project_id" : ID of project to which to associate slice
 *   "project_name" : name of project to which to associate slice
 *   "owner_id" : ID of owner of slice
 *   "description" : Description associated with slice
 * Return:
 *   Success / Failure
 */
function create_slice($args_dict)
{
}

/**
 * Lookup slice id's by project_id, owner_id and/or slice_name
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_slice_ids")
 *   "signer" : UUID of signer (asserter) of method/argument 
 *   "slice_name" : name of slice to be created [optional]
 *   "project_id" : ID of project to which to associate slice [optional]
 *   "owner_id" : ID of owner of slice [optional]
 * Return:
 *   List of slice ID's matching given criteria
 */
function lookup_slice_ids($args_dict)
{
}

/**
 * Lookup slice id's by project_id, owner_id and/or slice_name
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_slices")
 *   "signer" : UUID of signer (asserter) of method
 *   "project_id" : ID of project to which to associate slice [optional]
 *   "owner_id" : ID of owner of slice [optional]
 * Return:
 *   List of slice info tuples (id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) matching given criteria
 */
function lookup_slices($args_dict)
{
}

/**
 * Lookup slice info by slice id
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_slice")
 *   "signer" : UUID of signer (asserter) of method
 *   "slice_id" : ID of slice
 * Return:
 *   Slice info tuple (id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) for given slice
 */
function lookup_slice($args_dict)
{
}

/**
 * Lookup slice info by slice URN
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_slice_by_urn")
 *   "signer" : UUID of signer (asserter) of method
 *   "slice_urn" : URN of slice
 * Return:
 *   Slice info tuple (id, slice_name, project_id, expiration, creation, owner_id, slice_description, slice_email, slice_urn) for given slice
 */
function lookup_slice_by_urn($args_dict)
{
}

/**
 * Renew slice of given ID with given new expiration time
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("renew_slice")
 *   "signer" : UUID of signer (asserter) of method
 *   "slice_id" : ID of slice
 *   "expiration : new expiration time of slice
 * Return:
 *   Success / Failure
 */
function renew_slice($args, $message)
{

}

/**
 * Add a member of given role to given slice
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("add_slice_member")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "slice_id" : ID of slice to be modified
 *   "member_id : ID of member to be associated with given slice
 *   "role_type" : role of member within slice
 * Return:
 *   Success/Failure
 */
function add_slice_member($args_dict)
{
}

/**
 * Remove a member from given slice
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("remove_slice_member")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "slice_id" : ID of slice to be modified
 *   "member_id : ID of member to be disassociated with given slice
 * Return:
 *   Success/Failure
 */
function remove_slice_member($args_dict)
{
}

/**
 * Change role of given member in given slice
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("change_member_role")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "slice_id" : ID of slice to be modified
 *   "member_id : ID of member whose role within slice is to be modified
 *   "role_type" : role to be associated with given member
 * Return:
 *   Success/Failure
 */
function change_slice_member_role($args_dict)
{
}

/**
 * Return list of member ID's and roles associated with given slice
 * If role is provided, filter to members of given role
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_slice_members")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "slice_id" : ID of slice to be modifiedb
 *   "role_type" : role to be associated with given member [optional]
 * Return:
 *   List of (member_id, role) tuples for members of given slice
 */
function get_slice_members($args)
{
}

/**
 * Return list of member ID's and roles associated with given slice
 * If role is provided, filter to members of given role
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_slice_members")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project of slices to be queried
 *   "role_type" : role to be associated with given member [optional]
 * Return:
 *   List of (slice_id, member_id, role) tuples for members of given slice
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
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_slices_for_member")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about which slices are being queried 
 *   "is_member" : determines sense of 'member_id' query match [optional]
 *   "role_type" : role associated with given member [optional]
 * Return:
 *   ID's of given slices
 */
function get_slices_for_member($args_dict)
{
}


?>
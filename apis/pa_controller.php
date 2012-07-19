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
 * GENI Clearinghouse Project Authority (PA) controller interface
 * The PA maintains a list of projects, their details and members and provides access
 * to creating, looking up, updating, deleting projects.
 * 
 * Supports these methods:
 *   project_id <= create_project(pa_url, project_name, lead_id, lead_email, purpose)
 *   delete_project(pa_url, project_id);
 *   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
 *   update_project(pa_url, project_id, project_email, project_purpose);
 *   change_lead(pa_url, project_id, previous_lead_id, new_lead_id); *
 *   add_project_member(pa_url, project_id, member_id, role)
 *   remove_project_member(pa_url, project_id, member_id)
 *   change_member_role(pa_url, project_id, member_id, role)
 *   get_project_members(pa_url, project_id, role=null) // null => Any
 *   get_projects_for_member(pa_url, member_id, is_member, role=null)
 **/

/**
 * Create and register new project within clearinghouse
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("create_project");
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_name" : name of project being created
 *   "lead_id" : UUID of project lead
 *   "project_purpose" : description of project purpose
 * Return:
 *   Success/Fail
 *   
 */
function create_project($args_dict)
{
}

/**
 * Delete given project of given ID
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("delete_project");
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project to be deleted
 * Return:
 *   Success/Fail
 *   
 */
function delete_project($args_dict)
{
}

/**
 * Return list of all project ID's, optionally limited by lead_id 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_projects")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "lead_id" : ID of lead of projects to be provided [optional]
 * Return:
 *  List of project IDs associated with given lead ID (if provided)
 *   
 */
function get_projects($args_dict)
{
}

/**
 * Return list of all projects and data. 
 * Optionally, filtered by lead_id if provided
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_projects")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "lead_id" : ID of lead of projects to be provided [optional]
 * Return:
 *  List of project ID, project name, lead_id, project_email, creation time 
 *     an project purpose for projects of given lead ID (if provided)
 *   
 */
function lookup_projects($args_dict)
{
}


/**
 * Return information about project with given ID or name
 * Optionally, filtered by lead_id if provided
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_project")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   [One of these two is required:]
 *   "project_id" : ID of project to be provided 
 *   "project_name" : name of project to be provided 
 *   "lead_id" : ID of lead of project [optional]
 * Return:
 *   Project ID, project name, lead_id, project_email, creation time 
 *     and project purpose for projects of given lead ID (if provided)
 *   
 */
function lookup_project($args_dict)
{
}

/**
 * Update details of given project 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("update_project")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project to be modified
 *   "project_name" : new name of project
 *   "project_purpose" : new purpose of project
 * Return:
 *   Success/Failure
 */
function update_project($args_dict)
{
}

/**
 * Update lead of given project
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("change_lead")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project to be modified
 *   "previous_lead_id" : ID of previous project lead
 *   "lead_id" : ID of new project lead
 * Return:
 *   Success/Failure
 */
function change_lead($args_dict)
{
}

/**
 * Add a member of given role to given project
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("add_project_member")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project to be modified
 *   "member_id : ID of member to be associated with given project
 *   "role_type" : role of member within project
 * Return:
 *   Success/Failure
 */
function add_project_member($args_dict)
{
}

/**
 * Remove a member from given project 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("remove_project_member")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project to be modified
 *   "member_id : ID of member to be disassociated with given project
 * Return:
 *   Success/Failure
 */
function remove_project_member($args_dict)
{
}

/**
 * Change role of given member in given project
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("change_member_role")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project to be modified
 *   "member_id : ID of member whose role within project is to be modified
 *   "role_type" : role to be associated with given member
 * Return:
 *   Success/Failure
 */
function change_member_role($args_dict)
{
}

/**
 * Return list of member ID's and roles associated with given project
 * If role is provided, filter to members of given role
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_project_members")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "project_id" : ID of project to be modified
 *   "role_type" : role to be associated with given member [optional]
 * Return:
 *   List of (member_id, role) tuples for members of given project
 */
function get_project_members($args_dict)
{
  
}

/**
 * Return list of project ID's for given member_id
 * 
 * Optionally indicate sense of 'is member' or 'is not member'
 * If is_member is true, return projects for which member is a member
 * If is_member is false, return projects for which member is NOT a member
 * 
 * Optionally indicate role type of member
 * If role is provided, filter on projects 
 *    for which member has given role (is_member = true)
 *    for which member does NOT have given role (is_member = false)
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_projects_for_member")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about which projects are being queried 
 *   "is_member" : determines sense of 'member_id' query match [optional]
 *   "role_type" : role associated with given member [optional]
 * Return:
 *   ID's of given projects
 */
function get_projects_for_member($args_dict)
{
}

?>

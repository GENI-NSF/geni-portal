<?php

namespace Project_Authority;

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
 * <br><br>
 * Supports these methods:
<ul>
<li>   project_id <= create_project(project_name, lead_id, lead_email, purpose) </li>
<li>   delete_project(project_id); </li>
<li>   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id); </li>
<li>   update_project(project_id, project_email, project_purpose); </li>
<li>   change_lead(project_id, previous_lead_id, new_lead_id);  </li>
<li>   add_project_member(project_id, member_id, role) </li>
<li>   remove_project_member(project_id, member_id) </li>
<li>   change_member_role(project_id, member_id, role) </li>
<li>   get_project_members(project_id, role=null) // null => Any </li>
<li>   get_projects_for_member(member_id, is_member, role=null) </li>
</ul>
 **/
class Project_Authority {

/**
 * Create and register new project within clearinghouse
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("create_project")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_name" : name of project being created</li>
   <li>"lead_id" : UUID of project lead</li>
   <li>"project_purpose" : description of project purpose</li>
</ul>
 * @return boolean Success/Failure
 *   
 */
function create_project($args_dict)
{
}

/**
 * Delete given project of given ID
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("delete_project")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project to be deleted</li>
</ul>
 * @return boolean Success/Failure
 *   
 */
function delete_project($args_dict)
{
}

/**
 * Update details of given project 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("update_project")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project to be modified</li>
   <li>"project_name" : new name of project</li>
   <li>"project_purpose" : new purpose of project</li>
</ul>
 * @return boolean Success/Failure
 */
function update_project($args_dict)
{
}

/**
 * Update lead of given project
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("change_lead")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project to be modified</li>
   <li>"previous_lead_id" : ID of previous project lead</li>
   <li>"lead_id" : ID of new project lead</li>
</ul>
 * @return boolean Success/Failure
 */
function change_lead($args_dict)
{
}

/**
 * Add a member of given role to given project
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("add_project_member")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project to be modified</li>
   <li>"member_id : ID of member to be associated with given project</li>
   <li>"role_type" : role of member within project</li>
</ul>
 * @return boolean Success/Failure
 */
function add_project_member($args_dict)
{
}

/**
 * Remove a member from given project 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("remove_project_member")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project to be modified</li>
   <li>"member_id : ID of member to be disassociated with given project</li>
</ul>
 * @return boolean Success/Failure
 */
function remove_project_member($args_dict)
{
}

/**
 * Change role of given member in given project
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("change_member_role")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project to be modified</li>
   <li>"member_id : ID of member whose role within project is to be modified</li>
   <li>"role_type" : role to be associated with given member</li>
</ul>
 * @return boolean Success/Failure
 */
function change_member_role($args_dict)
{
}

/**
 * Return list of all project ID's, optionally limited by lead_id 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_projects")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"lead_id" : ID of lead of projects to be provided [optional]</li>
</ul>
 * @return array List of project IDs associated with given lead ID (if provided)
 *   
 */
function get_projects($args_dict)
{
}

/**
 * Return list of all projects and data. 
 * Optionally, filtered by lead_id if provided
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_projects")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"lead_id" : ID of lead of projects to be provided [optional]</li>
</ul>
 * @return array List of project ID, project name, lead_id, project_email, creation time and project purpose for projects of given lead ID (if provided)
 *   
 */
function lookup_projects($args_dict)
{
}


/**
 * Return information about project with given ID or name
 * Optionally, filtered by lead_id if provided
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_project")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"lead_id" : ID of lead of project [optional]</li>
   <li>"project_id" : ID of project to be provided [</li>
   <li>"project_name" : name of project to be provided </li>
   <li> NOTE: At least one of preceding two tags is required </li>
</ul>
 * @return dict Project ID, project name, lead_id, project_email, creation time 
 *     and project purpose for projects of given lead ID (if provided)
 *   
 */
function lookup_project($args_dict)
{
}

/**
 * Return list of member ID's and roles associated with given project
 * If role is provided, filter to members of given role
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_project_members")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"project_id" : ID of project to be modified</li>
   <li>"role_type" : role to be associated with given member [optional]</li>
</ul>
 * @return array List of (member_id, role) tuples for members of given project
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
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_projects_for_member")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about which projects are being queried </li>
   <li>"is_member" : determines sense of 'member_id' query match [optional]</li>
   <li>"role_type" : role associated with given member [optional]</li>
</ul>
 * @return arrary List of ID's of given projects
 */
function get_projects_for_member($args_dict)
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

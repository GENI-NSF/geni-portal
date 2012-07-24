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

namespace Member_Authority;


/**
 * GENI Clearinghouse Member Authority (MA) controller interface
 * The MA maintains a list of role relationships between members and other entities (projects, slices)
 * or general contexts 
 * That is, a person (member) can have a role with respect to a particular slice or project
 *   in which case the context_type is project or slice and the context_id is the UUID of that slice or
 *      project
 * Alternatively, the person (member) can have a role with respect to a cotnext type that has no
 *     specific context id, such as being the admin of membership records or the auditor of logs
 * <br><br>
 * Supports these methods:
<ul>
<li>   add_attribute(ma_url, member_id, role_type, context_type, context_id); </li>
<li>   remove_attribute(ma_url, member_id, role_type, context_type, context_id); </li>
<li>   update_role(ma_url, member_id, role_type, context_type, context_id); </li>
<li>   lookup_attributes(ma_url, member_id); </li>
<li>   register_ssh_key(ma_url, member_id, filename, description, ssh_key); </li>
<li>   lookup_ssh_keys(ma_url, member_id); </li>
</ul>
 */
class Member_Authority {

/**
 * Add attribute to a given principal (member) as having given role in given context.
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("add_attribute")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about whom assertion is made</li>
   <li>"role_type" : type of role asserted about member in context</li>
   <li>"context_type" : type of context in which role is asserted for member</li>
   <li>"context_id" : ID of context in which role is asserted for member</li>
</ul>
 * @return boolean Success/Failure
 */
function add_attribute($args_dict)
{
}

/**
 * Remove attribute from a given principal (member) as having given role in given context.
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("remove_attribute")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about whom assertion is made</li>
   <li>"role_type" : type of role asserted about member in context</li>
   <li>"context_type" : type of context in which role is asserted for member</li>
   <li>"context_id" : ID of context in which role is asserted for member</li>
</ul>
 * @return boolean Success/Failure
 */
function remove_attribute($args_dict)
{
}

/**
 * Update assertion of role of member in given context
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("update_role")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about whom assertion is made</li>
   <li>"role_type" : type of role asserted about member in context</li>
   <li>"context_type" : type of context in which role is asserted for member</li>
   <li>"context_id" : ID of context in which role is asserted for member</li>
</ul>
 * @return boolean Success/Failure
 */
function update_role($args_dict)
{
}

/**
 * Query all attributes (role, context_type, context) for given member
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_attributes")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about whom query is made</li>
</ul>
 * @return array List of (role, context_type, context) of all attributes of member
 */
function lookup_attributes($args_dict)
{
}

/**
 * Register SSH public key with given user
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("register_ssh_key")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about whom SSH key is to be registered</li>
   <li>"ssh_filename" : filename containing public SSH key</li>
   <li>"ssh_description" : Description of given SSH key </li>
   <li>"ssh_key" : SSH public key value</li>
</ul>
 * @return boolean Success/Fail
 *   
 */
function register_ssh_key($args_dict)
{
}

/**
 * Return all SSH keys associated with given member
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_ssh_keys")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about whom SSH key is to be registered</li>
</ul>
 * @return array List of SSH key info (account_id, filename, description, public_key) for given member
 *   
 */
function lookup_ssh_keys($args_dict)
{
}

}

?>

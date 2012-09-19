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
 * The MA maintains a set of members and their UUIDs and their attributes and associated query mechanisms.
 * The MA maintains a set of SSL keys and certs, both 'inside' (created) and 'outside' (uploaded) for given users.
 * Additionally, the MA maintains a mapping of members to the client tools (e.g. the GENI Portal) that the member has authorized to speak on his/her behalf.
 * Finally, the MA maintains a set of SSH keys for a given member for passing to resources as needed.
 * <br><br>
 * Supports these methods:
<ul>
<li>register_ssh_key(member_id, ssh_filename, ssh_description, ssh_public_key, [ssh_private_key]);</li>
<li>lookup_ssh_keys(member_id);</li>
<li>update_ssh_key(member_id, ssh_key_id, ssh_filename, ssh_description)</li>
<li>delete_ssh_key(member_id, ssh_key_id)</li>
<li>lookup_keys_and_certs(member_id);</li>
<li>create_account(attributes)</li>
<li>ma_list_clients()</li>
<li>ma_list_authorized_clients(member_id)</li>
<li>ma_authorize_client(member_id, client_urn, authorize_sense)</li>
<li>lookup_members(attributes) </li>
<li>lookup_member_by_id(member_id)</li>
<li>add_member_privilege(member_id, privilege_id)</li>
<li>revoke_member_privilege(member_id, privilege_id)</li>
</ul>
 */
class Member_Authority {

/**
 * Register SSH public key with given user
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("register_ssh_key")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"member_id" : ID of member about whom SSH key is to be registered</li>
   <li>"ssh_filename" : filename containing public SSH key (upload case)</li>
   <li>"ssh_description" : Description of given SSH key </li>
   <li>"ssh_public_key" : SSH public key value</li>
   <li>"ssh_private_key" : SSH private key value (optional: generate key pair case)</li>
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
 * @return array List of SSH key info (member_id, filename, description, public_key, private_key) for given member
 *   
 */
function lookup_ssh_keys($args_dict)
{
}

/**
 * Update key pair associated with member
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("update_ssh_key")</li>
   <li>"member_id" : ID of member about whom to update SSH key</li>
   <li>"ssh_key_id" : ID of SSH key pair for member
   <li>"ssh_filename" : filename containing public SSH key </li>
   <li>"ssh_description" : New description of SSH key pair for member
</ul>
 * @return boolean Success/Failure
 */
function update_ssh_key($args_dict)
{
}

/**
 * Remove key pair associated with member
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("delete_ssh_key")</li>
   <li>"member_id" : ID of member about whom to delete ssh key pair</li>
   <li>"ssh_key_id" : ID of SSH key pair for member
</ul>
 * @return boolean Success/Failure
 */
function delete_ssh_key($args_dict)
{
}

/**
 * Lookup inside keys/certs associated with a user UUID.
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("lookup_keys_and_certs")</li>
   <li>"member_id" : ID of member about whom inside keys and certs are desired</li>
</ul>
 * @return Dictionary containing a private key and certificate for given member
 */
function lookup_keys_and_certs($args_dict)
{
}

/**
 * Create new user account with given attributes.
 *    Required attributes: email_address, first_name, last_name, telephone_number
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("create_account")</li>
   <li>"attributes" : Dictionary of attributes (required keys: email_address, first_name, last_name, telephone_number) of member account to be created.</li>
</ul>
 * @return UUID of newly created member
 */
function create_account($args_dict)
{
}

/**
 * Get all client tools registered with the MA as potentially authorized for use by members
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("ma_list_clients")</li>
</ul>
 * @return List of (name => URN) pairs of registered tools
 */
function ma_list_clients($args_dict)
{
}

/**
 * Get all client tools registered with the MA for given user
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("ma_list_authorized_clients")</li>
   <li>"member_id" : UUID of given member for whom to return registered client tools</li>
</ul>
 * @return List of (name => URN) pairs of registered tools for given member
 */
function ma_list_authorized_clients($args_dict)
{
}

/**
 * Authorize/Deauthorize given tool for use by given member
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("ma_authorize_client")</li>
   <li>"member_id" : UUID of given member for whom to return registered client tools</li>
   <li>"client_urn" : URN of given client tool</li>
   <li>"authorize_sense" : True for authorize, False for deauthorize</li>
</ul>
 * @return boolean Success/Failure
 */
function ma_authorize_client($args_dict)
{
}

/**
 * Return list of members satisfying the 'and' of a provided set of name/value attributes
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("looukp_members")</li>
   <li>"attributes" : Dictionary of name/value pairs the 'and' of which is applied to query for registered members</li>
</ul>
 * @return List of UUIDs of members registered with MA satisfying attributes
 */
function lookup_members($args_dict)
{
}

/**
 * Return name/value attribute information about given member by UUID
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("looukp_member_by_id")</li>
   <li>"member_id" : UUID of member about whom information is requested</li>
</ul>
 * @return Dictionary of name/value pairs associated with member
 */
function lookup_member_by_id($args_dict)
{
}

/**
 * Return Add new privilege to given member
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("add_member_privilege")</li>
   <li>"member_id" : UUID of member about whom to add privilege</li>
   <li>"privilege_id" : Type of privilege added to member</li>
</ul>
 * @return boolean Success/Failure
 */
function add_member_privilege($args_dict)
{
}

/**
 * Return Revoke privilege to given member
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("revoke_member_privilege")</li>
   <li>"member_id" : UUID of member about whom to remove privilege</li>
   <li>"privilege_id" : Type of privilege removed from member</li>
</ul>
 * @return boolean Success/Failure
 */
function revoke_member_privilege($args_dict)
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


?>

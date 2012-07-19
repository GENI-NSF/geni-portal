<?php


/**
 * GENI Clearinghouse Member Authority (MA) controller interface
 * The MA maintains a list of role relationships between members and other entities (projects, slices)
 * or general contexts 
 * That is, a person (member) can have a role with respect to a particular slice or project
 *   in which case the context_type is project or slice and the context_id is the UUID of that slice or
 *      project
 * Alternatively, the person (member) can have a role with respect to a cotnext type that has no
 *     specific context id, such as being the admin of membership records or the auditor of logs
 *
 * 
 * Supports these methods:
 *   add_attribute(ma_url, member_id, role_type, context_type, context_id);
 *   remove_attribute(ma_url, member_id, role_type, context_type, context_id);
 *   update_role(ma_url, member_id, role_type, context_type, context_id);
 *   lookup_attributes(ma_url, member_id);
 *   register_ssh_key(ma_url, member_id, filename, description, ssh_key);
 *   lookup_ssh_keys(ma_url, member_id);
 */


/**
 * Add attribute to a given principal (member) as having given role in given context.
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("add_attribute")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about whom assertion is made
 *   "role_type" : type of role asserted about member in context
 *   "context_type" : type of context in which role is asserted for member
 *   "context_id" : ID of context in which role is asserted for member
 * Return:
 *   Success/Failure
 */
function add_attribute($args_dict)
{
}

/**
 * Remove attribute from a given principal (member) as having given role in given context.
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("remove_attribute")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about whom assertion is made
 *   "role_type" : type of role asserted about member in context
 *   "context_type" : type of context in which role is asserted for member
 *   "context_id" : ID of context in which role is asserted for member
 * Return:
 *   Success/Failure
 */
function remove_attribute($args_dict)
{
}

/**
 * Update assertion of role of member in given context
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("update_role")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about whom assertion is made
 *   "role_type" : type of role asserted about member in context
 *   "context_type" : type of context in which role is asserted for member
 *   "context_id" : ID of context in which role is asserted for member
 * Return:
 *   Success/Failure
 */
function update_role($args_dict)
{
}

/**
 * Query all attributes (role, context_type, context) for given member
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_attributes")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about whom query is made
 * Return:
 *   List of (role, context_type, context) of all attributes of member
 */
function lookup_attributes($args_dict)
{
}

/**
 * Register SSH public key with given user
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("register_ssh_key");
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about whom SSH key is to be registered
 *   "ssh_filename" : filename containing public SSH key
 *   "ssh_description" : Description of given SSH key 
 *   "ssh_key" : SSH public key value
 * Return:
 *   Success/Fail
 *   
 */
function register_ssh_key($args_dict)
{
}

/**
 * Return all SSH keys associated with given member
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("lookup_ssh_keys");
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "member_id" : ID of member about whom SSH key is to be registered
 * Return:
 *   List of SSH key info (account_id, filename, description, public_key)
 *      for given member
 *   
 */
function lookup_ssh_keys($args)
{
}

?>

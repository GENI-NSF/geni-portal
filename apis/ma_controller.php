<?php

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

/**
 * Get the version of the API of this particular service provider
 * @param dict $args_dict Dictionary containing 'operation' and 'signer' arguments'
 * @return number Version of API of this particular service provider
 */
function get_version($args_dict)
{
}


/** New methods 
lookup_account
request_account
modify_acocunt
delete_account
approve_account
deny_account
get_certificate
get_private_key
get_private_ssh_key

download inside SSL key pair
upload SSL key pair
download SSL key pair

upload public SSH key
download private SSH key
download public SSH key

Perhaps CS sits behind MA and has different API?
Only used by SA and MA?
submit_CSR (certificate signing request)
*/

}

?>

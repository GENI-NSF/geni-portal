<?php

namespace Credential_Store;

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
 * GENI Clearinghouse Credential Store (CS) controller interface
 * The Credential Store allows for storing of two kinds of credentials
 *    Attributes (signed assertions that principal P has 
 *         attribute A, possibly in context C)
 *    Policies (signed statements that principals with attribute A possibly in 
 *         context X have a given privilege)
 * 
 * Supports 4 'write' interfaces:
 * id <= create_assertion(principal, attribute, context_type, context)
 * id <= create_policy(attribute, context_type, privilege)
 * renew_assertion(id)
 * delete_policy(id);
 * 
 * Supports 4 'read' interfaces:
 * assertions <= query_assertions(principal, context_type, context)
 * policies <= query_policies();
 * success/failure => request_authorization(principal, action, 
 *      context_type, context)
 * permissions <= get_permissions(principal)
 **/


/** Create an assertion and store in CS
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("create_assertion")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
   <li>"principal" : UUID of principal about whom assertion is made</li>
   <li>"attribute" : id/index of attribute type</li>
   <li>"context_type" :  type of context in which assertion holds</li>
   <li>"context" : UUID of context (if any) for which assertion holds</li>
</ul>
 * @return string ID of assertion
 */
function create_assertion($args_dict)
{
}

/**
 * Create a policy and store in CS
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("create_policy")</li>
    <li>"signer" : UUID of signer (asserter) of policy</li>
    <li>"attribute" : id/index of attribute type</li>
    <li>"context_type" : type of context in which attribute holds</li>
    <li>"privilege" " id/index of privilege type</li>
</ul>
 * @return string ID of policy
 */
function create_policy($args_dict)
{
}

/**
 * Renew a given assertion with given ID
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("renew_assertion")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
   <li>"id" : ID of assertion to be renewed</li>
</ul>
 * @return boolean Success / Failure
 */
function renew_assertion($args_dict)
{
}

/**
 * Delete a given assertion with given ID
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("delete_assertion")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
   <li>"id" - ID of assertion to be deleted</li>
</ul>
 * @return boolean Success / Failure
 */
function delete_assertion($args_dict)
{
}

/**
 * Delete a given policy of given ID
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("delete_policy")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
   <li>"id" : ID of assertion to be renewed</li>
</ul>
 * @return boolean Success / Failure
 */
function delete_policy($args_dict)
{
}

/**
 * Return a list of assertions for a given principal 
 *   possibly in a given context
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("query_assertions")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
   <li>"principal" : UUID of principal</li>
   <li>"context_type" : type of context </li>
   <li>"context" : UUID of context (if any)n</li>
</ul>
 * @return array List of assertions matching given query
 */
function query_assertions($args_dict)
{
}

/**
 * Return a list of all policies in credential store
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("query_policies")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
</ul>
 * @return array List of all policies in credential store
 */
function query_policies($args_dict)
{
}

/**
 * Return whether a given principal is allowed to take a given 
 * action in a given context.
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("query_policies")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
   <li>"principal" : UUID of principal about whom authorization is requested</li>
   <li>"action" : name of action for which authorization is requested</li>
   <li>"context_type" : context type about which authorization is requested</li>
   <li>"context" " context_id about  which authorization is requested [optional]</li>
</ul>
 */
function request_authorization($args_dict)
{
}

/**
 * Get the permissions (allowed actions) for a given principal
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("query_policies")</li>
   <li>"signer" : UUID of signer (asserter) of assertion</li>
   <li>"principal" : UUID of principal about which actions are being requested</li>
</ul>
 * @return array List of action, context_type, context_id tuples for which principal has authorization
 */
function get_permissions($args)
{
}


?>


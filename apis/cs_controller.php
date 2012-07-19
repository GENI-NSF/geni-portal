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


/* Create an assertion and store in CS
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("create_assertion")
 *   "signer" : UUID of signer (asserter) of assertion
 *   "principal" : UUID of principal about whom assertion is made
 *   "attribute" : id/index of attribute type
 *   "context_type" :  type of context in which assertion holds
 *   "context" : UUID of context (if any) for which assertion holds
 * Return: ID of assertion
 */
function create_assertion($args_dict)
{
}

/* Create a policy and store in CS
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("create_policy")
 *    "signer" : UUID of signer (asserter) of policy
 *    "attribute" : id/index of attribute type
 *    "context_type" : type of context in which attribute holds
 *    "privilege" " id/index of privilege type
 * Return: ID of policy
 */
function create_policy($args_dict)
{
}

/*
 * Renew a given assertion with given ID
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("renew_assertion")
 *   "signer" : UUID of signer (asserter) of assertion
 *   "id" : ID of assertion to be renewed
 * Return : Success/failure
 */
function renew_assertion($args_dict)
{
}

/*
 * Delete a given assertion with given ID
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("delete_assertion")
 *   "signer" : UUID of signer (asserter) of assertion
 *   "id" - ID of assertion to be deleted
 * Return : Success/failure
 */
function delete_assertion($args_dict)
{
}

/*
 * Delete a given policy of given ID
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("delete_policy")
 *   "signer" : UUID of signer (asserter) of assertion
 *   "id" : ID of assertion to be renewed
 * Return : Success/failure
 */
function delete_policy($args_dict)
{
}

/*
 * Return a list of assertions for a given principal 
 *   possibly in a given context
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("query_assertions")
 *   "signer" : UUID of signer (asserter) of assertion
 *   "principal" : UUID of principal
 *   "context_type" : type of context 
 *   "context" : UUID of context (if any)n
 * Return : List of assertions matching given query
 */
function query_assertions($args_dict)
{
}

/*
 * Return a list of all policies in credential store
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("query_policies")
 *   "signer" : UUID of signer (asserter) of assertion
 * Return : List of all policies in credential store
 */
function query_policies($args_dict)
{
  // TODO - Should there be arguments? Do I know what subset I want
  global $CS_POLICY_TABLENAME;
  $sql = "select * from " . $CS_POLICY_TABLENAME;
  $rows = db_fetch_rows($sql);
  return $rows;
}

/*
 * Return whether a given principal is allowed to take a given 
 * action in a given context.
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("query_policies")
 *   "signer" : UUID of signer (asserter) of assertion
 *   "principal" : UUID of principal about whom authorization is requested
 *   "action" : name of action for which authorization is requested
 *   "context_type" : context type about which authorization is requested
 *   "context" " context_id about  which authorization is requested [optional]
 */
function request_authorization($args_dict)
{
}

/**
 * Get the permissions (allowed actions) for a given principal
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("query_policies")
 *   "signer" : UUID of signer (asserter) of assertion
 *   "principal" : UUID of principal about which actions are being requested
 * Return:
 *   list of action, context_type, context_id tuples for which principal has
 *     authorization
 */
function get_permissions($args)
{
}


?>


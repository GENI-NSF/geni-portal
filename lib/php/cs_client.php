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
 * Client side interface of GENI Clearinghouse Credential Store (CS)
 * Consists of these methods:
 *
 * Supports 4 'write' interfaces:
 * id <= create_assertion(signer, principal, attribute, context_type, context)
 * id <= create_policy(signer, attribute, context_type, privilege)
 * renew_assertion(id)
 * delete_assertion(assertion_id)
 * delete_policy(id);
 * 
 * Supports 3 'read' interfaces:
 * assertions <= query_assertions(principal, context_type, context)
 * policies <= query_policies();
 * success/failure => request_authorization(principal, action, 
 *      context_type, context)
 *
 */

require_once('cs_constants.php');
require_once('message_handler.php');
require_once('permission_manager.php');

// A cache of a principal's permissions indexed by ID
if(!isset($permission_cache)) {
  //  error_log("SETTING PERMISSION_CACHE");
  $permission_cache = array();

}



function create_assertion($cs_url, $msg_signer, $signer, $principal, $attribute, $context_type, $context)
{
  $create_assertion_message['operation'] = 'create_assertion';
  $create_assertion_message[CS_ARGUMENT::SIGNER] = $signer;
  $create_assertion_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = $attribute;
  $create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $create_assertion_message[CS_ARGUMENT::CONTEXT] = $context;
  $result = put_message($cs_url, $create_assertion_message,
			$msg_signer->certificate(), $msg_signer->privateKey());
  return $result;
}

function create_policy($cs_url, $msg_signer, $signer, $attribute, $context_type, $privilege)
{
  $create_policy_message['operation'] = 'create_policy';
  $create_policy_message[CS_ARGUMENT::SIGNER] = $signer;
  $create_policy_message[CS_ARGUMENT::ATTRIBUTE] = $attribute;
  $create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $create_policy_message[CS_ARGUMENT::PRIVILEGE] = $privilege;
  $result = put_message($cs_url, $create_policy_message,
			$msg_signer->certificate(), $msg_signer->privateKey());
  return $result;
}

function renew_assertion($cs_url, $signer, $assertion_id)
{
  $renew_assertion_message['operation'] = 'renew_assertion';
  $renew_assertion_message[CS_ARGUMENT::ID] = $assertion_id;
  $result = put_message($cs_url, $renew_assertion_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

function delete_assertion($cs_url, $signer, $assertion_id)
{
  $delete_assertion_message['operation'] = 'delete_assertion';
  $delete_assertion_message[CS_ARGUMENT::ID] = $assertion_id;
  $result = put_message($cs_url, $delete_assertion_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

function delete_policy($cs_url, $signer, $policy_id)
{
  $delete_policy_message['operation'] = 'delete_policy';
  $delete_policy_message[CS_ARGUMENT::ID] = $policy_id;
  $result = put_message($cs_url, $delete_policy_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

function query_assertions($cs_url, $signer, $principal, $context_type, $context)
{
  $query_assertions_message['operation'] = 'query_assertions';
  $query_assertions_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $query_assertions_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $query_assertions_message[CS_ARGUMENT::CONTEXT] = $context;
  $result = put_message($cs_url, $query_assertions_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

function query_policies($cs_url, $signer)
{
  $query_policies_message['operation'] = 'query_policies';
  $result = put_message($cs_url, $query_policies_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

function request_authorization($cs_url, $signer, $principal, $action, $context_type, $context)
{
  $request_authorization_message['operation'] = 'request_authorization';
  $request_authorization_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $request_authorization_message[CS_ARGUMENT::ACTION] = $action;
  $request_authorization_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $request_authorization_message[CS_ARGUMENT::CONTEXT] = $context;
  $result = put_message($cs_url, $request_authorization_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

function get_attributes($cs_url, $signer, $principal, $context_type, $context)
{
  $get_attributes_message['operation'] = 'get_attributes';
  $get_attributes_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $get_attributes_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $get_attributes_message[CS_ARGUMENT::CONTEXT] = $context;
  //  error_log("GA.message = " . print_r($get_attributes_message, true));
  $result = put_message($cs_url, $get_attributes_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

function get_permissions($cs_url, $signer, $principal)
{
  global $permission_cache;

  if (array_key_exists($principal, $permission_cache)) {
    //    error_log("CACHE HIT get_permissions : " . $principal);
    return $permission_cache[$principal];
}

  $get_permissions_message['operation'] = 'get_permissions';
  $get_permissions_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $result = put_message($cs_url, $get_permissions_message,
			$signer->certificate(), $signer->privateKey());
  //  error_log("GP = " . $result . "  " . print_r($result, true));
  // Reconstruct the Permission Manager from across the wire (it gets returned as an array)
  $pm = new PermissionManager();
  $pm->allowed_actions_no_context = $result['allowed_actions_no_context'];
  $pm->allowed_actions_in_context = $result['allowed_actions_in_context'];
  //  error_log("GP.pm = " . $pm);
  $permission_cache[$principal] = $pm;
  return $pm;
}

// Get list of members and their roles with respect to a particular context
// Returns rows with 'principal' (as account_id) and 'name' (as role)
function get_members($cs_url, $signer, $context_type, $context_id)
{
  $get_members_message['operation'] = 'get_members';
  $get_members_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $get_members_message[CS_ARGUMENT::CONTEXT] = $context_id;
  $result = put_message($cs_url, $get_members_message,
			$signer->certificate(), $signer->privateKey());
  return $result;
}

?>

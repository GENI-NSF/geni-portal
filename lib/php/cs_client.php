<?php
/**
 * Client side interface of GENI Clearinghouse Credential Store (CS)
 * Consists of these methods:
 *
 * Supports 4 'write' interfaces:
 * id <= create_assertion(signer, principal, attribute, context_type, context)
 * id <= create_policy(signer, attribute, context_type, privilege)
 * renew_assertion(id)
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

function create_assertion($cs_url, $signer, $principal, $attribute, $context_type, $context)
{
  $create_assertion_message['operation'] = 'create_assertion';
  $create_assertion_message[CS_ARGUMENT::SIGNER] = $signer;
  $create_assertion_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = $attribute;
  $create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $create_assertion_message[CS_ARGUMENT::CONTEXT] = $context;
  $result = put_message($cs_url, $create_assertion_message);
  return $result;
}

function create_policy($cs_url, $signer, $attribute, $context_type, $privilege)
{
  $create_policy_message['operation'] = 'create_policy';
  $create_policy_message[CS_ARGUMENT::SIGNER] = $signer;
  $create_policy_message[CS_ARGUMENT::ATTRIBUTE] = $attribute;
  $create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $create_policy_message[CS_ARGUMENT::PRIVILEGE] = $privilege;
  $result = put_message($cs_url, $create_policy_message);
  return $result;
}

function renew_assertion($cs_url, $assertion_id)
{
  $renew_assertion_message['operation'] = 'renew_assertion';
  $renew_assertion_message[CS_ARGUMENT::ID] = $assertion_id;
  $result = put_message($cs_url, $renew_assertion_message);
  return $result;
}

function delete_policy($cs_url, $policy_id)
{
  $delete_policy_message['operation'] = 'delete_policy';
  $delete_policy_message[CS_ARGUMENT::ID] = $policy_id;
  $result = put_message($cs_url, $delete_policy_message);
  return $result;
}

function query_assertions($cs_url, $principal, $context_type, $context)
{
  $query_assertions_message['operation'] = 'query_assertions';
  $query_assertions_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $query_assertions_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $query_assertions_message[CS_ARGUMENT::CONTEXT] = $context;
  $result = put_message($cs_url, $query_assertions_message);
  return $result;
}

function query_policies($cs_url)
{
  $query_policies_message['operation'] = 'query_policies';
  $result = put_message($cs_url, $query_policies_message);
  return $result;
}

function request_authorization($cs_url, $principal, $action, $context_type, $context)
{
  $request_authorization_message['operation'] = 'request_authorization';
  $request_authorization_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $request_authorization_message[CS_ARGUMENT::ACTION] = $action;
  $request_authorization_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $request_authorization_message[CS_ARGUMENT::CONTEXT] = $context;
  $result = put_message($cs_url, $request_authorization_message);
  return $result;
}

function get_permissions($cs_url, $principal)
{
  $get_permissions_message['operation'] = 'get_permissions';
  $get_permissions_message[CS_ARGUMENT::PRINCIPAL] = $principal;
  $result = put_message($cs_url, $get_permissions_message);
  //  error_log("GP = " . $result . "  " . print_r($result, true));
  // Reconstruct the Permission Manager from across the wire (it gets returned as an array)
  $pm = new PermissionManager();
  $pm->allowed_actions_no_context = $result['allowed_actions_no_context'];
  $pm->allowed_actions_in_context = $result['allowed_actions_in_context'];
  //  error_log("GP.pm = " . $pm);
  return $pm;
}

// Get list of members and their roles with respect to a particular context
function get_members($cs_url, $context_type, $context_id)
{
  $get_members_message['operation'] = 'get_members';
  $get_members_message[CS_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $get_members_message[CS_ARGUMENT::CONTEXT] = $context_id;
  $result = put_message($cs_url, $get_members_message);
  return $result;
}

?>

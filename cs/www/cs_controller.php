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

$prev_name = session_id('CS-SESSION');

require_once('message_handler.php');
require_once('db_utils.php');
require_once('signer.php');
require_once('cs_constants.php');
require_once('response_format.php');
require_once('permission_manager.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('logging_client.php');

/**
 * GENI Clearinghouse Credential Store (CS) controller interface
 * The Credential Store allows for storing of two kinds of credentials
 *    Attributes (signed assertions that principal P has 
 *         attribute A, possibly in context C)
 *    Policies (signed statements that principals with attribute A possibly in 
 *         context X have a given privilege)
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
 **/

$sr_url = get_sr_url();
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);

/* Create an assertion and store in CS
 * Args: 
 *   signer - UUID of signer (asserter) of assertion
 *   principal - UUID of principal about whom assertion is made
 *   attribute - id/index of attribute type
 *   context_type - type of context in which assertion holds
 *   context - UUID of context (if any) for which assertion holds
 * Return: ID of assertion
 */
function create_assertion($args)
{
  global $CS_ASSERTION_TABLENAME;
  $signer = $args[CS_ARGUMENT::SIGNER];
  $principal = $args[CS_ARGUMENT::PRINCIPAL];
  $attribute = $args[CS_ARGUMENT::ATTRIBUTE];
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $context = '0';
  if (is_context_type_specific($context_type)) {
    $context = $args[CS_ARGUMENT::CONTEXT];
  }

  //  error_log("CA.args = " . print_r($args, true));

  // Expire in 30 days
  $expiration = get_future_date(30);

  $assertion_cert = create_assertion_cert($signer, $principal, 
					  $attribute, $context_type, $context,
					  $expiration);
  $context_field_clause = "";
  $context_value_clause = "";
  $conn = db_conn();
  if (is_context_type_specific($context_type)) {
    $context_field_clause = CS_ASSERTION_TABLE_FIELDNAME::CONTEXT . ", ";
    $context_value_clause = $conn->quote($context, 'text') . ", ";
  }

  // *** TEMP TESTING
  $signer_value = $conn->quote($signer, 'text');
  if ($signer == null) {
    $signer_value = "null";
  }
  // *** END TEMP TESTING

  $sql = "INSERT INTO " . $CS_ASSERTION_TABLENAME . "(" 
    . CS_ASSERTION_TABLE_FIELDNAME::SIGNER . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . $context_field_clause 
    . CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ASSERTION_CERT . ") VALUES ( "
    . $signer_value . ", "
    . $conn->quote($principal, 'text') . ", "
    . $conn->quote($attribute, 'integer') . ", "
    . $conn->quote($context_type, 'integer') . ", "
    . $context_value_clause 
    . $conn->quote(db_date_format($expiration), 'timestamp') . ", "
    . $conn->quote($assertion_cert, 'text') . ") ";

  //  error_log("CS.create sql = " . $sql);

  $result = db_execute_statement($sql);

  /*
  // Log the creation
  if ($signer != null) {
    global $log_url;
    global $mysigner;
    $attributes = get_attribute_for_context($context_type, $context);
    log_event($log_url, $mysigner, "Created assertion ATTRIB=" . 
	      $attribute .
	      " PRINC=" . $principal,
	      $attributes, $signer);
  }
  */

  //  error_log("CS.create.result = " . print_r($result, true));
  return $result;
}

/* Create a policy and store in CS
 * Args:
 *    signer - UUID of signer (asserter) of policy
 *    attribute - id/index of attribute type
 *    context_type - type of context in which attribute holds
 *    privilege - id/index of privilege type
 * Return: ID of policy
 */
function create_policy($args)
{
  global $CS_POLICY_TABLENAME;
  $signer = $args[CS_ARGUMENT::SIGNER];
  $attribute = $args[CS_ARGUMENT::ATTRIBUTE];
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $privilege = $args[CS_ARGUMENT::PRIVILEGE];

  $policy_cert = create_policy_cert($signer,  
				    $attribute, $context_type, $privilege);
  $conn = db_conn();
  $sql = "INSERT INTO " . $CS_POLICY_TABLENAME . "(" 
    . CS_POLICY_TABLE_FIELDNAME::SIGNER . ", "
    . CS_POLICY_TABLE_FIELDNAME::ATTRIBUTE . ", "
    . CS_POLICY_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . CS_POLICY_TABLE_FIELDNAME::PRIVILEGE . ", "
    . CS_POLICY_TABLE_FIELDNAME::POLICY_CERT . ") VALUES ( "
    . $conn->quote($signer, 'text') . ", "
    . $conn->quote($attribute, 'integer') . ", "
    . $conn->quote($context_type, 'integer') . ", "
    . $conn->quote($privilege, 'integer') . ", "
    . $conn->quote($policy_cert, 'text') . ") ";
  $result = db_execute_statement($sql);

  // Log the creation
  if ($signer != null) {
    global $log_url;
    global $mysigner;
    $attributes = get_attribute_for_context($context_type, null);
    log_event($log_url, $mysigner, "Created policy ATTRIB=" . $attribute . 
	      " PRIV=" . $privilege, 
	      $attributes, $signer);
  }

  return $result;
}

/*
 * Renew a given assertion with given ID
 * Args:
 *   ID - ID of assertion to be renewed
 * Return : Success/failure
 */
function renew_assertion($args)
{
  global $CS_ASSERTION_TABLENAME;
  $id = $args[CS_ARGUMENT::ID];
  $expiration = get_future_date(20);
  $conn = db_conn();
  $sql = "update " . $CS_ASSERTION_TABLENAME . " SET " 
    . CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION . " = "
    . $conn->quote(db_date_format($expiration), 'timestamp')
    . " WHERE " . CS_ASSERTION_TABLE_FIELDNAME::ID . " = " . $conn->quote($id, 'integer');
  $result = db_execute_statement($sql);
  return $result;
}

/*
 * Delete a given assertion with given ID
 * Args:
 *   ID - ID of assertion to be deleted
 * Return : Success/failure
 */
function delete_assertion($args)
{
  global $CS_ASSERTION_TABLENAME;
  $id = $args[CS_ARGUMENT::ID];
  $conn = db_conn();
  $sql = "delete from " . $CS_ASSERTION_TABLENAME 
    . " WHERE " . CS_ASSERTION_TABLE_FIELDNAME::ID . " = " . $conn->quote($id, 'integer');
  $result = db_execute_statement($sql);
  return $result;
}

/*
 * Delete a given policy of given ID
 * Args:
 *   ID - ID of policy to be deleted
 * Return : Success/failure
 */
function delete_policy($args)
{
  global $CS_POLICY_TABLENAME;
  $id = $args[CS_ARGUMENT::ID];
  $conn = db_conn();
  $sql = "delete from " . $CS_POLICY_TABLENAME 
    . " WHERE " . CS_POLICY_TABLE_FIELDNAME::ID . " = " . $conn->quote($id, 'integer');
  $result = db_execute_statement($sql);
  return $result;
}

/*
 * Return a list of assertions for a given principal 
 *   possibly in a given context
 * Args: 
 *   principal - UUID of principal
 *   context_type - type of context 
 *   context - UUID of context (if any)n
 * Return : List of assertions matching given query
 */
function query_assertions($args)
{
  global $CS_ASSERTION_TABLENAME;
  $principal = $args[CS_ARGUMENT::PRINCIPAL];
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $context = null;
  if (is_context_type_specific($context_type)) {
    $context = $args[CS_ARGUMENT::CONTEXT];
  }
  $conn = db_conn();
  $sql = ("select * from " . $CS_ASSERTION_TABLENAME . " WHERE "
          . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . " = "
          . $conn->quote($principal, 'text')
          . " AND "
          . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . " = "
          . $conn->quote($context_type, 'integer'));
  if (is_context_type_specific($context_type)) {
    $sql .= (" AND " . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT
            . " = " . $conn->quote($context, 'text'));
  }
  geni_syslog(GENI_SYSLOG_PREFIX::CS, $sql);
  $rows = db_fetch_rows($sql);
  return $rows;
}

/*
 * Return a list of all policies in credential store
 * Args: none
 * Return : List of all policies in credential store
 */
function query_policies($args)
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
 */
function request_authorization($args)
{
  $principal = $args[CS_ARGUMENT::PRINCIPAL];
  $action = $args[CS_ARGUMENT::ACTION];
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $conn = db_conn();
  if(!is_context_type_specific($context_type)) {
    $context_clause = "";
    $context = "";
  } else {
    $context = $args[CS_ARGUMENT::CONTEXT];
    $context_clause = "cs_assertion.context = " . $conn->quote($context, 'text') . " and ";
  }
  $sql = "select * from cs_action, cs_policy, cs_assertion where "
    . "cs_assertion.principal = " .  $conn->quote($principal, 'text') . " and "
    . "cs_assertion.context_type = cs_action.context_type and "
    . "cs_policy.context_type = cs_action.context_type and "
    . "cs_action.context_type = " . $conn->quote($context_type, 'integer') . " and "
    . $context_clause
    . "cs_assertion.attribute = cs_policy.attribute and "
    . "cs_action.privilege = cs_policy.privilege and "
    . "cs_action.name = " . $conn->quote($action, 'text');

  //  error_log("CS.Request_authorization.sql = " . $sql);

  $rows = db_fetch_rows($sql, "CS_AUTH");
  $code = $rows[RESPONSE_ARGUMENT::CODE];
  $rows = $rows[RESPONSE_ARGUMENT::VALUE];
  $authorized = false;
  if ($code == RESPONSE_ERROR::NONE && count($rows) > 0) {
    $authorized = true;
  } elseif (is_operator($principal, $action, $context_type)) {
    $authorized = true;
  }
  //    error_log("SUCCESS = " . $result . " ROWS " . count($rows));
  //    error_log("ROWS = " . print_r($rows, true));
  if ($authorized) {
    return generate_response(RESPONSE_ERROR::NONE, true, '');
  }  else {
    $msg = "No authorization for action " . $action . " for "
      . $principal . " in context " . $context_type . ' ' . $context;
    return generate_response(RESPONSE_ERROR::NONE, false, $msg);
  }
}

function get_permissions($args)
{
  $principal = $args[CS_ARGUMENT::PRINCIPAL];

  // cs_assertion : id, signer, principal, attribute, context_type, context, expiration
  // cs_policy    : id, signer, attribute, context_type, privilege, policy_cert
  // cs_action : id, name, privilege, context_type
  $conn = db_conn();
  $sql = "select " 
    . "cs_action.name, cs_assertion.context_type, cs_assertion.context"
    . " from cs_assertion, cs_policy, cs_action"
    . " where "
    . " cs_assertion.principal = " . $conn->quote($principal, 'text')
    . " and cs_assertion.attribute = cs_policy.attribute"
    . " and cs_assertion.context_type = cs_policy.context_type"
    . " and cs_action.privilege = cs_policy.privilege"
    . " and cs_action.context_type = cs_policy.context_type";
  //  error_log("SQL = " . $sql);
  $rows = db_fetch_rows($sql);
  //  error_log("ROWS = " . print_r($rows, true));
  if ($rows[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $rows = $rows[RESPONSE_ARGUMENT::VALUE];
    $permission_manager = compute_permission_manager($rows);
    //    error_log("CS.get_permissions " . $permission_manager);
    $result = generate_response(RESPONSE_ERROR::NONE, $permission_manager, null);
  } else {
    $result = $rows;
  }
  return $result;
}

function get_members($args)
{
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $context_id = $args[CS_ARGUMENT::CONTEXT];
  $conn = db_conn();
  $sql = "select cs_assertion.principal, cs_attribute.name" 
    . " from cs_attribute, cs_assertion " 
    . " where "
    . " cs_assertion.context_type = " . $conn->quote($context_type, 'integer')
    . " and cs_assertion.context = " . $conn->quote($context_id, 'text')
    . " and cs_assertion.attribute = cs_attribute.id";
  //  error_log("get_members.sql = " . $sql);
  $rows = db_fetch_rows($sql);
  return $rows;
}

function get_attributes($args)
{
  global $CS_ATTRIBUTE_TABLENAME;
  global $CS_ASSERTION_TABLENAME;
  $principal = $args[CS_ARGUMENT::PRINCIPAL];
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $context_id = $args[CS_ARGUMENT::CONTEXT];
  $conn = db_conn();
  $context_clause = "";
  if($context_id <> null) {
    $context_clause = " AND " . $CS_ASSERTION_TABLENAME . "." . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT
      . " = " . $conn->quote($context_id, 'text');
  }

  //  error_log("ARGS = " . print_r($args, true));
  $sql = "select " 
    . $CS_ATTRIBUTE_TABLENAME . "." . CS_ATTRIBUTE_TABLE_FIELDNAME::NAME
    . ", " . $CS_ASSERTION_TABLENAME . "." . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT
    . " FROM " . $CS_ASSERTION_TABLENAME . ", " . $CS_ATTRIBUTE_TABLENAME
    . " WHERE " 
    . $CS_ASSERTION_TABLENAME . "." . CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE .  " = " 
    . $CS_ATTRIBUTE_TABLENAME . "." . CS_ATTRIBUTE_TABLE_FIELDNAME::ID
    . " AND " . $CS_ASSERTION_TABLENAME . "." . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL 
    . " = " . $conn->quote($principal, 'text')
    . " AND " . $CS_ASSERTION_TABLENAME . "." . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE
    . " = " . $conn->quote($context_type, 'integer')
    . $context_clause;

  //  error_log("GA.sql = " . $sql);
  $attribs = db_fetch_rows($sql);
  return $attribs;

}


function create_assertion_cert($signer, $principal, 
			       $attribute, $context_type, $context, 
			       $expiration)
{
  // *** TODO
  return null;
}

function create_policy_cert($signer, 
			    $attribute, $context_type, $privilege)
{
  // *** TODO
  return null;
}

function is_operator($principal, $action, $context_type)
{
  $conn = db_conn();
  $sql = "select * from cs_action, cs_policy, cs_assertion where "
    . "cs_assertion.principal = " .  $conn->quote($principal, 'text') . " and "
    . "cs_assertion.context_type = cs_action.context_type and "
    . "cs_policy.context_type = cs_action.context_type and "
    . "cs_action.context_type = " . $conn->quote($context_type, 'integer') . " and "
    . "cs_assertion.attribute = cs_policy.attribute and "
    . "cs_assertion.attribute = " . $conn->quote(CS_ATTRIBUTE_TYPE::OPERATOR, 'integer') . " and "
    . "cs_action.privilege = cs_policy.privilege and "
    . "cs_action.name = " . $conn->quote($action, 'text');

  error_log("CS.Request_authorization.sql = " . $sql);

  $rows = db_fetch_rows($sql, "CS_AUTH");
  $code = $rows[RESPONSE_ARGUMENT::CODE];
  $rows = $rows[RESPONSE_ARGUMENT::VALUE];
  error_log("CS.is_operator got response $code");
  if ($code == RESPONSE_ERROR::NONE) {
    error_log("CS.is_operator response error is none");
  } else {
    error_log("CS.is_operator response error is ERROR");
  }
  error_log("CS.is_operator got row count " . count($rows));
  return ($code == RESPONSE_ERROR::NONE && count($rows) > 0);
}


class CSGuardFactory implements GuardFactory
{

  public function createGuards($message) {
    return array(new TrueGuard());
  }
  
}

/*
 * I am the CS. I should be authorizing my own calls, no?
 */
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$mycertfile = '/usr/share/geni-ch/cs/cs-cert.pem';
$mykeyfile = '/usr/share/geni-ch/cs/cs-key.pem';
$mysigner = new Signer($mycertfile, $mykeyfile);
$guard_factory = new CSGuardFactory();
handle_message("CS", $cs_url, default_cacerts(),
	       $mysigner->certificate(), $mysigner->privateKey(), $guard_factory);
?>


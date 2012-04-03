<?php

require_once('message_handler.php');
require_once('db_utils.php');
require_once('cs_constants.php');

/**
 * GENI Clearinghouse Credential Store (CS) controller interface
 * The Credential Store allows for storing of two kinds of credentials
 *    Attributes (signed assertions that principal P has 
 *         attribute A, possibly in context C)
 *    Policies (signed statements that principals with attribute A possibly in 
 * 
 * Supports 4 'write' interfaces:
 * id <= create_assertion(signer, principal, attribute, context_type, context)
 * id <= create_policy(signer, attribute, context_type, action)
 * renew_assertion(id)
 * delete_policy(id);
 * 
 * Supports 2 'read' interfaces:
 * assertions <= query_assertions(principal, context_type, context)
 * policies <= query_policies();
 **/


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
  if ($context_type != CS_CONTEXT_TYPE::NONE) {
    $context = $args[CS_ARGUMENT::CONTEXT];
  }

  // Expire in 30 days
  $expiration = new DateTime();
  $expiration->add(new DateInterval('P30D'));

  $assertion_cert = create_assertion_cert($signer, $principal, 
					  $attribute, $context_type, $context,
					  $expiration);
  $context_field_clause = "";
  $context_value_clause = "";
  if ($context_type != CS_CONTEXT_TYPE::NONE) {
    $context_field_clause = CS_ASSERTION_TABLE_FIELDNAME::CONTEXT . ", ";
    $context_value_clause = "'" . $context . "', ";
  }

  $sql = "INSERT INTO " . $CS_ASSERTION_TABLENAME . "(" 
    . CS_ASSERTION_TABLE_FIELDNAME::SIGNER . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . $context_field_clause 
    . CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ASSERTION_CERT . ") VALUES ( "
    . "'" . $signer . "', "
    . "'" . $principal . "', "
    . "'" . $attribute . "', "
    . "'" . $context_type . "', "
    . $context_value_clause 
    . "'" . $expiration->format('Y-m-d H:i:s') . "', "
    . "'" . $assertion_cert . "') ";
  $result = db_execute_statement($sql);
  return result;
}

/* Create a policy and store in CS
 * Args:
 *    signer - UUID of signer (asserter) of policy
 *    attribute - id/index of attribute type
 *    context_type - type of context in which attribute holds
 *    action - id/index of action type
 * Return: ID of policy
 */
function create_policy($args)
{
  global $CS_POLICY_TABLENAME;
  $signer = $args[CS_ARGUMENT::SIGNER];
  $attribute = $args[CS_ARGUMENT::ATTRIBUTE];
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $action = $args[CS_ARGUMENT::ACTION];

  $policy_cert = create_policy_cert($signer,  
				    $attribute, $context_type, $action);
  $sql = "INSERT INTO " . $CS_POLICY_TABLENAME . "(" 
    . CS_POLICY_TABLE_FIELDNAME::SIGNER . ", "
    . CS_POLICY_TABLE_FIELDNAME::ATTRIBUTE . ", "
    . CS_POLICY_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . CS_POLICY_TABLE_FIELDNAME::ACTION . ", "
    . CS_POLICY_TABLE_FIELDNAME::POLICY_CERT . ") VALUES ( "
    . "'" . $signer . "', "
    . "" . $attribute . ", "
    . "" . $context_type . ", "
    . "" . $action . ", "    
    . "'" . $policy_cert . "') ";
  $result = db_execute_statement($sql);
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
  $expiration = new DateTime();
  $expiration->add(new DateInterval('P20D')); // 20 days increment
  $sql = "update " . $CS_ASSERTION_TABLENAME . " SET " 
    . CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION . " = '" 
    . $expiration->format('Y-m-d H:i:s') . "'"
    . " WHERE " . CS_ASSERTION_TABLE_FIELDNAME::ID . " = '" . $id . "'";
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
  $sql = "delete from " . $CS_POLICY_TABLENAME 
    . " WHERE " . CS_POLICY_TABLE_FIELDNAME::ID . " = '" . $id . "'";
  $result = db_execute_statement($sql);
  return $result;
}

/*
 * Return a list of assertions for a given principal 
 *   possibly in a given context
 * Args: 
 *   principal - UUID of principal
 *   context_type - type of context (0 = NONE, 1 = PROJECT, 2 = SLICE, 3 = SLIVER)
 *   context - UUID of context (if any)n
 * Return : List of assertions matching given query
 */
function query_assertions($args)
{
  global $CS_ASSERTION_TABLENAME;
  $principal = $args[CS_ARGUMENT::PRINCIPAL];
  if ($principal == -1) { // For testing
    $sql = "select * from " . $CS_ASSERTION_TABLENAME;
  } else {
    $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
    $context = null;
    if ($context_type != CS_CONTEXT_TYPE::NONE) {
      $context = $args[CS_ARGUMENT::CONTEXT];
    }
    if ($context_type == CS_CONTEXT_TYPE::NONE) {
      $sql = "select * from " . $CS_ASSERTION_TABLENAME . " WHERE " 
	. CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . " = '" . $principal . "'";
    } else {
      $sql = "select * from " . $CS_ASSERTION_TABLENAME . " WHERE " 
	. CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . " = '" . $principal 
	. "' AND " 
	. CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . " = '" . $context_type
	. "' AND "
	. CS_ASSERTION_TABLE_FIELDNAME::CONTEXT . " = '" . $context;
    }
  }
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

function create_assertion_cert($signer, $principal, 
			       $attribute, $context_type, $context, 
			       $expiration)
{
  // *** TODO
  return null;
}

function create_policy_cert($signer, 
			    $attribute, $context_type, $action)
{
  // *** TODO
  return null;
}

handle_message("CS");

?>


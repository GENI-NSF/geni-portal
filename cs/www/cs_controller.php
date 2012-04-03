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
  $signer = $args[CS_ARGUMENTS::SIGNER];
  $principal = $args[CS_ARGUMENTS::PRINCIPAL];
  $attribute = $args[CS_ARGUMENTS::ATTRIBUTE];
  $context_type = $args[CS_ARGUMENTS::CONTEXT_TYPE];
  $context = $args[CS_ARGUMENTS::CONTEXT];

  $assertion_cert = create_assertion_cert($signer, $principal, 
					  $attribute, $context_type, $context);
  $sql = "INSERT INTO " . CS_ASSERTION_TABLENAME . "(" 
    . CS_ASSERTION_TABLE_FIELDNAME::SIGNER . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ASSERTION_CERT . ") VALUES ( "
    . "'" . $signer . "', "
    . "'" . $principal . "', "
    . "'" . $attribute . "', "
    . "'" . $context_type . "', "
    . "'" . $context . "', "
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
  $signer = $args[CS_ARGUMENTS::SIGNER];
  $attribute = $args[CS_ARGUMENTS::ATTRIBUTE];
  $context_type = $args[CS_ARGUMENTS::CONTEXT_TYPE];
  $action = $args[CS_ARGUMENTS::ACTION];

  // Expire in 30 days
  $expiration = new DateTime();
  $exipration->add(new DateInterval('P30D');

  $assertion_cert = create_assertion_cert($signer, $principal, 
					  $attribute, $context_type, $context);
  $sql = "INSERT INTO " . CS_ASSERTION_TABLENAME . "(" 
    . CS_ASSERTION_TABLE_FIELDNAME::SIGNER . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::ACTION . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::POLICY_CERT . ") VALUES ( "
    . "'" . $signer . "', "
    . "'" . $attribute . "', "
    . "'" . $context_type . "', "
    . "'" . $action . "', "    
    . "'" . $expiration . "', "
    . "'" . $policy_cert . "') ";
  $result = db_execute_statement($sql);
  return result;
}

/*
 * Renew a given assertion with given ID
 * Args:
 *   ID - ID of assertion to be renewed
 *   renewal_time - time to which the assertion is to be renewed 
 *          (absolute, not relative)
 * Return : Success/failure
 */
function renew_assertion($args)
{
  $id = $args[CS_ARGUMENT::ID];
  $renewal_time = $args[CS_ARGUMENT::RENEWAL_TIME];
  $sql = "update " . CS_ASSERTION_TABLENAME . " SET " 
    . CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION . " = '" . $renewal_time . "'"
    . " WHERE " . CS_ASSERTION_TABLE_FIELDNAME::ID . " = '" $id . "'";
  $result = $db_execute_statement($sql);
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
  $id = $args[CS_ARGUMENT::ID];
  $sql = "delete from " . CS_POLICY_TABLENAME . 
    . " WHERE " . CS_POLICY_TABLE_FIELDNAME::ID . " = '" $id . "'";
  $result = $db_execute_statement($sql);
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
  $principal = $args[CS_ARGUMENT::PRINCIPAL];
  $context_type = $args[CS_ARGUMENT::CONTEXT_TYPE];
  $context = $args[CS_ARGUMENT::CONTEXT];
  if ($principal == -1) { // For testing
    $sql = "select * from " . $CS_ASSERTION_TABLENAME;
  } else if ($context_type == CS_CONTEXT_TYPE::NONE) {
    $sql = "select * from " . $CS_ASSERTION_TABLENAME . " WHERE " 
      . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . " = '" . $principal . "'";
  } else {
    $sql = "select * from " + $CS_CREDENTIAL_TABLENAME . " WHERE " 
      . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . " = '" . $principal 
      . "' AND " 
      . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . " = '" . $context_type
      . "' AND "
      . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT . " = '" . $context;
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
  $sql = "select * from " + $CS_POLICY_TABLENAME;
  $rows = db_fetch_rows($sql);
  return rows;
}

function create_assertion_cert($signer, $principal, 
			       $attribute, $context_type, $context)
{
  // *** TODO
  return null;
}


handle_message("CS");

?>


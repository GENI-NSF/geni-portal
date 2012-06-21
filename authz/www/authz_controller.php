<?php

$prev_name = session_id('AUTHZ-SESSION');

require_once('message_handler.php');
require_once('db_utils.php');
require_once('cs_constants.php');

/**
 * GENI Clearinghouse Authorization Service (AUTHZ) controller interface
 * The Authorization Service determines whether a given principal
 * has a given privilege to take a given action in a given context.
 *
 * The Credential Store contains attributes and policies.
 *    Attributes map principals to attributes possibly in contexts
 *    Policies map attributes to privileges.
 * The AuthZ service maps these privileges to specific actions.
 *    This map is contained in private database tables
 *
 * Supports 1 'read' interfaces:
 * success/failure <= request_authorization(principal, action, 
 *     context_type, context);
 **/

function request_authorization($principal, $action, $context_type, $context)
{
  $result = 1;
  return $result;
}

handle_message("AUTHZ");

?>

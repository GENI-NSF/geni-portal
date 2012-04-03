<?php

require_once('util.php');
require_once('cs_constants.php');

function dump_all_assertions_and_policies()
{
  $query_assertions_message['operation'] = 'query_assertions';
  $query_assertions_message['principal'] = '-1';
  $assertion_rows = put_message($cs_url, $query_assertions_message];
  for($assertion_rows as $assertion) {
    error_log("ASSERT " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::ID] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::SIGNER] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::ASSERTION_CERT]);
  }
  $query_policies_message['operation'] = 'query_policies';
  $policy_rows = put_message($cs_url, $query_policies_message];
  for($policy_rows as $policy) {
    error_log("POLICY " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ID] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDAME::SIGNER] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ATTRIBUTE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::CONTEXT_TYPE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ACTION] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::POLICY_CERT]);
  }

}

error_log("cs TEST\n");

/* Could be HTTP_HOST or SERVER_NAME */
$http_host = $_SERVER['HTTP_HOST'];
$cs_url = "https://" . $http_host . "/cs/cs_controller.php";

// create_assertion(signer, principal, attribute, context_type, context)
// create_policy(signer, attribute, context_type, action)
// renew_assertion(id, time)
// delete_policy(id)
// query_assertions(principal, context_type, context)
// query_policies()

$create_assertion_message['operation'] = 'create_assertion';
$create_assertion_message[CS_ARGUMENT::SIGNER] = '200';
$create_assertion_message[CS_ARGUMENT::PRINCIPAL] = '300';
$create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = '7';
$create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = '0';
$result = put_message($cs_url, $create_assertion_message);
dump_all_assertions_and_policies($result);

$create_assertion_message[CS_ARGUMENT::SIGNER] = '200';
$create_assertion_message[CS_ARGUMENT::PRINCIPAL] = '300';
$create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = '8';
$create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = '1';
$create_assertion_message[CS_ARGUMENT::CONTEXT] = '400';
$result = put_message($cs_url, $create_assertion_message);
dump_all_assertions_and_policies();

$create_assertion_message['operation'] = 'create_policy';
$create_policy_message[CS_ARGUMENT::SIGNER] = '200';
$create_policy_message[CS_ARGUMENT::ATTRIBUTE] = '2';
$create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = '0';
$result = put_message($cs_url, $create_policy_message);
dump_all_assertions_and_policies();

$create_policy_message[CS_ARGUMENT::SIGNER] = '200';
$create_policy_message[CS_ARGUMENT::ATTRIBUTE] = '12';
$create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = '2';
$create_policy_message[CS_ARGUMENT::CONTEXT] = '500';
$result = put_message($cs_url, $create_policy_message);
dump_all_assertions_and_policies();

$renew_attribute_message['operaton'] = 'renew_attribute';
$renew_attribute_message['id'] = '1';
$result = put_message($cs_url, $renew_attribute_message);
dump_all_assertions_and_policies();

$delete_policy_message['operaton'] = 'renew_policy';
$delete_policy_message['id'] = '2';
$result = put_message($cs_url, $delete_policy_message);
dump_all_assertions_and_policies();


relative_redirect('home');
?>

<?php

require_once('util.php');
require_once('cs_constants.php');

error_log("cs TEST\n");

/* Could be HTTP_HOST or SERVER_NAME */
$http_host = $_SERVER['HTTP_HOST'];
$cs_url = "https://" . $http_host . "/cs/cs_controller.php";

function dump_all_assertions_and_policies()
{
  global $cs_url;
  $query_assertions_message['operation'] = 'query_assertions';
  $query_assertions_message['principal'] = '-1';
  $assertion_rows = put_message($cs_url, $query_assertions_message);
  foreach($assertion_rows as $assertion) {
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
  $policy_rows = put_message($cs_url, $query_policies_message);
  foreach($policy_rows as $policy) {
    error_log("POLICY " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ID] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::SIGNER] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ATTRIBUTE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::CONTEXT_TYPE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ACTION] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::POLICY_CERT]);
  }

}

// create_assertion(signer, principal, attribute, context_type, context)
// create_policy(signer, attribute, context_type, action)
// renew_assertion(id, time)
// delete_policy(id)
// query_assertions(principal, context_type, context)
// query_policies()

$create_assertion_message['operation'] = 'create_assertion';
$create_assertion_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_assertion_message[CS_ARGUMENT::PRINCIPAL] = '33333333333333333333333333333333';
$create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::ADMIN;
$create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = '0';
$result = put_message($cs_url, $create_assertion_message);
dump_all_assertions_and_policies($result);

$create_assertion_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_assertion_message[CS_ARGUMENT::PRINCIPAL] = '33333333333333333333333333333333';
$create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::PROJECT_AUDITOR;
$create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::PROJECT;;
$create_assertion_message[CS_ARGUMENT::CONTEXT] = '44444444444444444444444444444444';
$result = put_message($cs_url, $create_assertion_message);
dump_all_assertions_and_policies();

$create_policy_message['operation'] = 'create_policy';
$create_policy_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_policy_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::ADMIN;
$create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::NONE;
$create_policy_message[CS_ARGUMENT::ACTION] = CS_ACTION_TYPE::PROJECT_CREATE;
$result = put_message($cs_url, $create_policy_message);
dump_all_assertions_and_policies();

$create_policy_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_policy_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::SLICE_LEAD;
$create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::SLICE;
$create_policy_message[CS_ARGUMENT::CONTEXT] = '55555555555555555555555555555555';
$create_policy_message[CS_ARGUMENT::ACTION] = CS_ACTION_TYPE::SLICE_WRITE;
$result = put_message($cs_url, $create_policy_message);
dump_all_assertions_and_policies();

$renew_assertion_message['operation'] = 'renew_assertion';
$renew_assertion_message['id'] = '1';
$result = put_message($cs_url, $renew_assertion_message);
dump_all_assertions_and_policies();

$delete_policy_message['operation'] = 'delete_policy';
$delete_policy_message['id'] = '2';
$result = put_message($cs_url, $delete_policy_message);
dump_all_assertions_and_policies();


relative_redirect('home');
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
	      $policy[CS_POLICY_TABLE_FIELDNAME::PRIVILEGE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::POLICY_CERT]);
  }

}

$create_assertion_message['operation'] = 'create_assertion';
$create_assertion_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_assertion_message[CS_ARGUMENT::PRINCIPAL] = '33333333333333333333333333333333';
$create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::ADMIN;
$create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = '0';
$result = put_message($cs_url, $create_assertion_message);
error_log("RES(1) = " . $result);
dump_all_assertions_and_policies($result);

$create_assertion_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_assertion_message[CS_ARGUMENT::PRINCIPAL] = '33333333333333333333333333333333';
$create_assertion_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::PROJECT_LEAD;
$create_assertion_message[CS_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::PROJECT;
$create_assertion_message[CS_ARGUMENT::CONTEXT] = '44444444444444444444444444444444';
$result = put_message($cs_url, $create_assertion_message);
error_log("RES(2) = " . $result);
dump_all_assertions_and_policies();

$create_policy_message['operation'] = 'create_policy';
$create_policy_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_policy_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::ADMIN;
$create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::NONE;
$create_policy_message[CS_ARGUMENT::PRIVILEGE] = CS_PRIVILEGE_TYPE::WRITE;
$result = put_message($cs_url, $create_policy_message);
error_log("RES(3) = " . $result);
dump_all_assertions_and_policies();

$create_policy_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_policy_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::PROJECT_LEAD;
$create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::PROJECT;
$create_policy_message[CS_ARGUMENT::PRIVILEGE] = CS_PRIVILEGE_TYPE::WRITE;
$result = put_message($cs_url, $create_policy_message);
error_log("RES(4) = " . $result);
dump_all_assertions_and_policies();

$create_policy_message[CS_ARGUMENT::SIGNER] = '22222222222222222222222222222222';
$create_policy_message[CS_ARGUMENT::ATTRIBUTE] = CS_ATTRIBUTE_TYPE::PROJECT_LEAD;
$create_policy_message[CS_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::PROJECT;
$create_policy_message[CS_ARGUMENT::PRIVILEGE] = CS_PRIVILEGE_TYPE::WRITE;
$result = put_message($cs_url, $create_policy_message);
error_log("RES(5) = " . $result);
dump_all_assertions_and_policies();

$request_authorization_message['operation'] = 'request_authorization';
$request_authorization_message['principal'] = '33333333333333333333333333333333';
$request_authorization_message['action'] = 'create_assertion';
$request_authorization_message['context_type'] = CS_CONTEXT_TYPE::NONE;
$result = put_message($cs_url, $request_authorization_message);
error_log("Auth(1) = " . $result);

$request_authorization_message['operation'] = 'request_authorization';
$request_authorization_message['principal'] = '33333333333333333333333333333334';
$request_authorization_message['action'] = 'create_assertion';
$request_authorization_message['context_type'] = CS_CONTEXT_TYPE::NONE;
$result = put_message($cs_url, $request_authorization_message);
error_log("Auth(2) = " . $result);

$request_authorization_message['operation'] = 'request_authorization';
$request_authorization_message['principal'] = '33333333333333333333333333333333';
$request_authorization_message['action'] = 'create_slice';
$request_authorization_message['context_type'] = CS_CONTEXT_TYPE::PROJECT;
$request_authorization_message['context'] = '44444444444444444444444444444444';
$result = put_message($cs_url, $request_authorization_message);
error_log("Auth(3) = " . $result);

$request_authorization_message['operation'] = 'request_authorization';
$request_authorization_message['principal'] = '33333333333333333333333333333334';
$request_authorization_message['action'] = 'create_slice';
$request_authorization_message['context_type'] = CS_CONTEXT_TYPE::PROJECT;
$request_authorization_message['context'] = '44444444444444444444444444444444';
$result = put_message($cs_url, $request_authorization_message);
error_log("Auth(4) = " . $result);

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

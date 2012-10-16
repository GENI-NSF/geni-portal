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

// Generic controller methods for request management
// This file is to be 'required' by a real controller (sa, pa, ma, etc)
// once the $REQUEST_TABLENAME variable is set in that file

require_once('response_format.php');
require_once('rq_constants.php');
require_once('db_utils.php');

// Create a request of given content
// request_details is a dictionary of attributes, to be turned a string when stored/retrieved from database
function create_request($args)
{
  $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];
  $requestor = $args[RQ_ARGUMENTS::REQUESTOR];
  $request_type = $args[RQ_ARGUMENTS::REQUEST_TYPE];
  $request_text = $args[RQ_ARGUMENTS::REQUEST_TEXT];
  $request_details = $args[RQ_ARGUMENTS::REQUEST_DETAILS];
  $now = new DateTime(null, new DateTimeZone('UTC')); 

  global $REQUEST_TABLENAME;

  $conn = db_conn();
  $sql = "INSERT INTO " . $REQUEST_TABLENAME
    . "(" 
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::CREATION_TIMESTAMP . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TEXT . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::REQUEST_DETAILS . ") "
    . "VALUES ("
    . RQ_REQUEST_STATUS::PENDING . ", "
    . $conn->quote($context_type, 'integer') . ", "
    . $conn->quote($context_id, 'text') . ", "
    . $conn->quote($requestor, 'text') . ", "
    . $conn->quote(db_date_format($now), 'timestamp') . ", "
    . $conn->quote($request_type, 'integer') . ", "
    . $conn->quote($request_text, 'text') . ", "
    . $conn->quote($request_details, 'text') . ") ";

  //  error_log("create_request.sql = " . $sql);

  $result = db_execute_statement($sql);

  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $lastval_sql = "select lastval()";
    $result = db_fetch_row($lastval_sql);
    $lastval = $result[RESPONSE_ARGUMENT::VALUE]['lastval'];
    $result = generate_response(RESPONSE_ERROR::NONE, $lastval, '');
  }

  return $result;
}

// Set disposition of pending request to APPROVED, REJECTED or CANCELED
// Approve given request (setting status, timestamp, approver)
function resolve_pending_request($args)
{
  $request_id = $args[RQ_ARGUMENTS::REQUEST_ID];
  $resolution_status = $args[RQ_ARGUMENTS::RESOLUTION_STATUS];
  $resolver = $args[RQ_ARGUMENTS::RESOLVER];
  $resolution_description = $args[RQ_ARGUMENTS::RESOLUTION_DESCRIPTION];
  $now = new DateTime(null, new DateTimeZone('UTC'));

  global $REQUEST_TABLENAME;
  $conn = db_conn();

  $sql = "UPDATE " . $REQUEST_TABLENAME
    . " SET " 
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . $conn->quote($resolution_status, 'integer') . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLUTION_DESCRIPTION . " = " . $conn-quote($resolution_description, 'text') . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLUTION_TIMESTAMP . " = " . $conn->quote(db_date_format($now), 'timestamp') . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLVER . " = " . $conn->quote($resolver , 'text')
    . " WHERE " 
    . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $conn->quote($request_id, 'integer');

  //  error_log("resolve_pending_request.sql = " . $sql);

  $result = db_execute_statement($sql);

  return $result;
}

// Get list of requests for given context
function get_requests_for_context($args)
{
  $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];

  global $REQUEST_TABLENAME;
  $conn = db_conn();
  $sql = "SELECT * from " . $REQUEST_TABLENAME 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $conn->quote($context_type, 'integer')
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = " . $conn->quote($context_id, 'text');

  //  error_log("get_requests_for_context.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
}

// Get list of requests made by given user
// Optionally, limit by given context type
function get_requests_by_user($args)
{
  $account_id = $args[RQ_ARGUMENTS::ACCOUNT_ID];
  $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];

  global $REQUEST_TABLENAME;
  $conn = db_conn();

  $sql = "SELECT * from " . $REQUEST_TABLENAME 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR . " = " . $conn->quote($account_id, 'text')
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $conn->quote($context_type, 'integer')
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = " . $conn->quote($context_id, 'text');

  //  error_log("get_requests_by_user.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
}

// Get list of requests pending for given user
// Optionally, limit by given context
function get_pending_requests_for_user($args)
{
  $account_id = $args[RQ_ARGUMENTS::ACCOUNT_ID];
  $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];

  global $REQUEST_TABLENAME;

  $user_for_context_query = user_context_query($account_id);

  $sql = "SELECT * from " . $REQUEST_TABLENAME 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . RQ_REQUEST_STATUS::PENDING
    . " AND "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " IN (" 
    . $user_for_context_query . ")";

  //  error_log("get_pending_requests_for_user.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
}

// Get number of pending requests for a given user
// Optionally, limit by given context
function get_number_of_pending_requests_for_user($args)
{
  $account_id = $args[RQ_ARGUMENTS::ACCOUNT_ID];
  $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];

  global $REQUEST_TABLENAME;
  $conn = db_conn();

  $user_for_context_query = 
    RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " IN (" . 
    user_context_query($account_id) . ")";
  if ($context_id != null) {
    $user_for_context_query = 
      RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = " . $conn->quote($context_id, 'text');
  }

  $sql = "select count(*) from " . $REQUEST_TABLENAME
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . RQ_REQUEST_STATUS::PENDING
    . " AND "
    . $user_for_context_query;
  //  error_log("get_number_requests_for_user.sql = " . $sql);
  $result = db_fetch_row($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $result = generate_response(RESPONSE_ERROR::NONE, $result[RESPONSE_ARGUMENT::VALUE]['count'], '');
  }
  return $result;

}

// Get request info for a single request id
function get_request_by_id($args)
{
  $request_id = $args[RQ_ARGUMENTS::REQUEST_ID];
  global $REQUEST_TABLENAME;
  $conn = db_conn();

  $sql = "SELECT * from " . $REQUEST_TABLENAME 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $conn->quote($request_id, 'integer');
  //  error_log("get_requests_by_id.sql = " . $sql);
  $result = db_fetch_row($sql);
  return $result;

}

?>

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

// Generic controller methods for request management, based on context_type (project or slice)

require_once('response_format.php');
require_once('rq_constants.php');
require_once('rq_utils.php');
require_once('db_utils.php');

// Create a request of given type by the given requestor on the given context
// request_details is a dictionary of attributes, to be turned into a string when stored/retrieved from database
// On success, return a triple with the last request_id
function create_request($args)
{
  $context_type = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_TYPE, $args)) {
    $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  }
  $context_id = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_ID, $args)) {
    $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];
  }
  if (! isset($context_id) or is_null($context_id)) {
    return generate_response(RESPONSE_ERROR::ARGS, '', 'No context_id given');
  }

  $requestor = null;
  if (array_key_exists(RQ_ARGUMENTS::REQUESTOR, $args)) {
    $requestor = $args[RQ_ARGUMENTS::REQUESTOR];
  }
  $request_type = null; // see rq_constants.RQ_REQUEST_TYPE
  if (array_key_exists(RQ_ARGUMENTS::REQUEST_TYPE, $args)) {
    $request_type = $args[RQ_ARGUMENTS::REQUEST_TYPE];
  }
  $request_text = null;
  if (array_key_exists(RQ_ARGUMENTS::REQUEST_TEXT, $args)) {
    $request_text = $args[RQ_ARGUMENTS::REQUEST_TEXT];
  }
  $request_details = null;
  if (array_key_exists(RQ_ARGUMENTS::REQUEST_DETAILS, $args)) {
    $request_details = $args[RQ_ARGUMENTS::REQUEST_DETAILS];
  }

  $now = new DateTime(null, new DateTimeZone('UTC')); 

  $conn = db_conn();
  $sql = "INSERT INTO " . get_request_tablename($context_type)
    . " (" 
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

// Set disposition of pending request to APPROVED, REJECTED or CANCELED (see rq_constants.RQ_REQUEST_STATUS)
// This is how you approve a given request (setting status, timestamp, approver)
// return standard triple
function resolve_pending_request($args)
{
  $request_id = null;
  if (array_key_exists(RQ_ARGUMENTS::REQUEST_ID, $args)) {
    $request_id = $args[RQ_ARGUMENTS::REQUEST_ID];
  }
  if (! isset($request_id) or is_null($request_id)) {
    return generate_response(RESPONSE_ERROR::ARGS, '', 'No request_id given');
  }

  $context_type = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_TYPE, $args)) {
    $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  }
  $resolution_status = null;
  if (array_key_exists(RQ_ARGUMENTS::RESOLUTION_STATUS, $args)) {
    $resolution_status = $args[RQ_ARGUMENTS::RESOLUTION_STATUS];
  }
  $resolver = null;
  if (array_key_exists(RQ_ARGUMENTS::RESOLVER, $args)) {
    $resolver = $args[RQ_ARGUMENTS::RESOLVER];
  }
  $resolution_description = null;
  if (array_key_exists(RQ_ARGUMENTS::RESOLUTION_DESCRIPTION, $args)) {
    $resolution_description = $args[RQ_ARGUMENTS::RESOLUTION_DESCRIPTION];
  }

  $now = new DateTime(null, new DateTimeZone('UTC'));

  $conn = db_conn();

  $sql = "UPDATE " . get_request_tablename($context_type) 
    . " SET " 
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . $conn->quote($resolution_status, 'integer') . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLUTION_DESCRIPTION . " = " . $conn->quote($resolution_description, 'text') . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLUTION_TIMESTAMP . " = " . $conn->quote(db_date_format($now), 'timestamp') . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLVER . " = " . $conn->quote($resolver , 'text')
    . " WHERE " 
    . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $conn->quote($request_id, 'integer');

  //  error_log("resolve_pending_request.sql = " . $sql);

  $result = db_execute_statement($sql);

  return $result;
}

// Get list of requests for given context
// Allow an optional status to limit to pending requests
// Return standard triple, value is list of rows
function get_requests_for_context($args)
{
  $context_type = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_TYPE, $args)) {
    $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  }
  $context_id = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_ID, $args)) {
    $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];
  }
  if (! isset($context_id) or is_null($context_id)) {
    return generate_response(RESPONSE_ERROR::ARGS, '', 'No context_id given');
  }

  $status = null;
  if (array_key_exists(RQ_ARGUMENTS::RESOLUTION_STATUS, $args)) {
    $status = $args[RQ_ARGUMENTS::RESOLUTION_STATUS];
  }

  $conn = db_conn();
  $sql = "SELECT * from " . get_request_tablename($context_type)
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $conn->quote($context_type, 'integer')
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = " . $conn->quote($context_id, 'text');

  if (isset($status) and ! is_null($status)) {
    $sql = $sql
      . " AND " 
      . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . $conn->quote($status, 'integer');
  }

  //  error_log("get_requests_for_context.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
}

// Get list of requests made by given user (account_id)
// Optionally, limit by given context or context type (the context the user asked to join, e.g.)
// Optionally limit by status
// FIXME: optionally limit by timestamp for when the request was made
function get_requests_by_user($args)
{
  $account_id = null;
  if (array_key_exists(RQ_ARGUMENTS::ACCOUNT_ID, $args)) {
    $account_id = $args[RQ_ARGUMENTS::ACCOUNT_ID];
  }
  if (! isset($account_id) or is_null($account_id)) {
    return generate_response(RESPONSE_ERROR::ARGS, '', 'No account_id given');
  }

  $context_type = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_TYPE, $args)) {
    $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  }
  $context_id = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_ID, $args)) {
    $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];
  }

  $status = null;
  if (array_key_exists(RQ_ARGUMENTS::RESOLUTION_STATUS, $args)) {
    $status = $args[RQ_ARGUMENTS::RESOLUTION_STATUS];
  }

  $conn = db_conn();

  $sql = "SELECT * from " . get_request_tablename($context_type)
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR . " = " . $conn->quote($account_id, 'text');
  if (isset($context_type) and ! is_null($context_type)) {
    $sql = $sql
      . " AND " 
      . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $conn->quote($context_type, 'integer');
  }
  if (isset($context_id) and ! is_null($context_id)) {
    $sql = $sql
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = " . $conn->quote($context_id, 'text');
  }

  if (isset($status) and ! is_null($status)) {
    $sql = $sql
      . " AND " 
      . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . $conn->quote($status, 'integer');
  }

  //  error_log("get_requests_by_user.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
}

// Get list of requests pending which the given user can handle (account is that of a lead/admin)
// Optionally, limit by given context (i.e. given user is lead/admin of this project/slice and show requests for that project/slice)
// return the code/value/output triple, where value is an array of rows
// FIXME: Could make the status an option that defaults to pending
function get_pending_requests_for_user($args)
{
  $account_id = null;
  if (array_key_exists(RQ_ARGUMENTS::ACCOUNT_ID, $args)) {
    $account_id = $args[RQ_ARGUMENTS::ACCOUNT_ID];
  }
  if (! isset($account_id) or is_null($account_id)) {
    return generate_response(RESPONSE_ERROR::ARGS, '', 'No account_id given');
  }
  // FIXME: Context type is ignored!? That may be OK given that this is implemented in 
  // a specific controller. But if so, remove it?
  $context_type = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_TYPE, $args)) {
    $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  }
  $context_id = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_ID, $args)) {
    $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];
  }

  $conn = db_conn();

  $user_for_context_query = '';
  if ($context_id != null) {
    // Limit to given context
    $user_for_context_query = 
      RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = " . $conn->quote($context_id, 'text') . ' AND ';
  }

  // Limit to contexts where this account has privileges to make changes
  // Note that this is in ADDITION to limiting to a specific context if provided.
  $user_for_context_query =  $user_for_context_query . 
    RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " IN (" . 
    user_context_query($account_id, $context_type) . ")";

  $sql = "SELECT * from " . get_request_tablename($context_type) 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . RQ_REQUEST_STATUS::PENDING
    . " AND "
    . $user_for_context_query;

  //  error_log("get_pending_requests_for_user.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
}

// Get number of pending requests for a given user to handle. That is, requests that
// are within a context that this user has lead/admin privileges over.
// Optionally, limit by given context.
// Return the standard triple, with the value being the count of pending requests on success
// FIXME: Could make the status an option that defaults to pending
function get_number_of_pending_requests_for_user($args)
{
  $account_id = null;
  if (array_key_exists(RQ_ARGUMENTS::ACCOUNT_ID, $args)) {
    $account_id = $args[RQ_ARGUMENTS::ACCOUNT_ID];
  }
  if (! isset($account_id) or is_null($account_id)) {
    return generate_response(RESPONSE_ERROR::ARGS, '', 'No account_id given');
  }

  // FIXME: Context type is ignored!? That may be OK given that this is implemented in 
  // a specific controller. But if so, remove it?
  $context_type = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_TYPE, $args)) {
    $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  }
  $context_id = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_ID, $args)) {
    $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];
  }

  $conn = db_conn();

  $user_for_context_query = '';
  if ($context_id != null) {
    // Limit to given context
    $user_for_context_query = 
      RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = " . $conn->quote($context_id, 'text') . ' AND ';
  }

  // Limit to contexts where this account has privileges to make changes
  // Note that this is in ADDITION to limiting to a specific context if provided.
  $user_for_context_query = $user_for_context_query . 
    RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " IN (" . 
    user_context_query($account_id, $context_type) . ")";

  $sql = "select count(*) from " . get_request_tablename($CONTEXT_TYPE)
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
// return standard triple
function get_request_by_id($args)
{
  $context_type = null;
  if (array_key_exists(RQ_ARGUMENTS::CONTEXT_TYPE, $args)) {
    $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  }
  $request_id = null;
  if (array_key_exists(RQ_ARGUMENTS::REQUEST_ID, $args)) {
    $request_id = $args[RQ_ARGUMENTS::REQUEST_ID];
  }
  if (! isset($request_id) or is_null($request_id)) {
    return generate_response(RESPONSE_ERROR::ARGS, '', 'No request ID given');
  }

  $conn = db_conn();

  $sql = "SELECT * from " . get_request_tablename($context_type)
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $conn->quote($request_id, 'integer');
  //  error_log("get_requests_by_id.sql = " . $sql);
  $result = db_fetch_row($sql);
  return $result;

}

?>

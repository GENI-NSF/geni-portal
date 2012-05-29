<?php

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
  $now = new DateTime();

  global $REQUEST_TABLENAME;

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
    . REQUEST_STATUS::PENDING . ", "
    . "" . $context_type . ", "
    . "'" . $context_id . "', "
    . "'" . $requestor . "', "
    . "'" . db_date_format($now) . "', "
    . "" . $request_type . ", "
    . "'" . $request_text . "', "
    . "'" . $request_details . "') ";

  error_log("create_request.sql = " . $sql);

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
  $now = new DateTime();

  global $REQUEST_TABLENAME;

  $sql = "UPDATE " . $REQUEST_TABLENAME
    . " SET " 
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . $resolution_status . ", "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLUTION_DESCRIPTION . " = '" . $resolution_description . "', "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLUTION_TIMESTAMP . " = '" . db_date_format($now) . "', "
    . RQ_REQUEST_TABLE_FIELDNAME::RESOLVER . " = '" . $resolver . "'"
    . " WHERE " 
    . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $request_id;

  error_log("resolve_pending_request.sql = " . $sql);

  $result = db_execute_statement($sql);

  return $result;
}

// Get list of requests for given context
function get_requests_for_context($args)
{
  $context_type = $args[RQ_ARGUMENTS::CONTEXT_TYPE];
  $context_id = $args[RQ_ARGUMENTS::CONTEXT_ID];

  global $REQUEST_TABLENAME;

  $sql = "SELECT * from " . $REQUEST_TABLENAME 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $context_type
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = '" . $context_id . "'";

  error_log("get_requests_for_context.sql = " . $sql);
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

  $sql = "SELECT * from " . $REQUEST_TABLENAME 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR . " = '" . $account_id . "'"
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $context_type
    . " AND " 
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = '" . $context_id . "'";

  error_log("get_requests_by_user.sql = " . $sql);
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
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . REQUEST_STATUS::PENDING
    . " AND "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " IN (" 
    . $user_for_context_query . ")";

  error_log("get_pending_requests_for_user.sql = " . $sql);
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

  $user_for_context_query = user_context_query($account_id);

  $sql = "select count(*) from " . $REQUEST_TABLENAME
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::STATUS . " = " . REQUEST_STATUS::PENDING
    . " AND "
    . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " IN (" 
    . $user_for_context_query . ")";
  error_log("get_number_requests_for_user.sql = " . $sql);
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

  $sql = "SELECT * from " . $REQUEST_TABLENAME 
    . " WHERE "
    . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $request_id;
  error_log("get_requests_by_id.sql = " . $sql);
  $result = db_fetch_row($sql);
  return $result;

}

?>

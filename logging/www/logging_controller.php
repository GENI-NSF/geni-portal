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

$prev_name = session_id('LOGGING-SESSION');

require_once('message_handler.php');
require_once('logging_constants.php');
require_once('db_utils.php');
require_once('response_format.php');

function log_event($args)
{

  global $LOGGING_TABLENAME;
  global $LOGGING_CONTEXT_TABLENAME;

  $event_time = new DateTime();
  $event_time->setTimestamp($args[LOGGING_ARGUMENT::EVENT_TIME]);
  $user_id = $args[LOGGING_ARGUMENT::USER_ID];
  $contexts = $args[LOGGING_ARGUMENT::CONTEXTS];
  $message = $args[LOGGING_ARGUMENT::MESSAGE];

  //  error_log("ET = " . print_r($event_time, true));

  $sql = "insert into " . $LOGGING_TABLENAME 
    . "(" 
    . LOGGING_TABLE_FIELDNAME::EVENT_TIME . ", "
    . LOGGING_TABLE_FIELDNAME::USER_ID . ", "
    . LOGGING_TABLE_FIELDNAME::MESSAGE 
    . " ) VALUES " 
    . "("
    . "'" . db_date_format($event_time) . "', "
    . "'" . $user_id . "', "
    . "'" . $message . "')";

  //  error_log("LOG.SQL = " . $sql);

  $result = db_execute_statement($sql);

  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    //    error_log("INSERT RESULT = " . print_r($result, true));
    $lastval_sql = "select lastval()";
    $result = db_fetch_row($lastval_sql);
    $lastval = $result[RESPONSE_ARGUMENT::VALUE]['lastval'];
    //    error_log("LASTVAL = " . print_r($result, true));
    foreach ($contexts as $context) {
      $context_type = $context[LOGGING_ARGUMENT::CONTEXT_TYPE];
      $context_id = $context[LOGGING_ARGUMENT::CONTEXT_ID];
      $insert_sql = "insert into " . $LOGGING_CONTEXT_TABLENAME 
	. " ("
	. LOGGING_CONTEXT_TABLE_FIELDNAME::ID . ", "
	. LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
	. LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_ID . ") "
	. " VALUES ("
	. $lastval . ", "
	. $context_type  . ", "
	. "'" . $context_id . "')";
      //      error_log("INSERT.SQL = " . $insert_sql);
      $insert_result = db_execute_statement($insert_sql);
	
    }
  }

  return $result;
}

function get_log_entries_by_author($args)
{
  global $LOGGING_TABLENAME;
  global $LOGGING_CONTEXT_TABLENAME;
  $since = new DateTime();
  $since->setTimestamp($args[LOGGING_ARGUMENT::EVENT_TIME]);
  $user_id = $args[LOGGING_ARGUMENT::USER_ID];

  $sql = "select  " 
    . LOGGING_TABLE_FIELDNAME::EVENT_TIME . ", "
    . LOGGING_TABLE_FIELDNAME::USER_ID . ", "
    . LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_ID . ", "
    . LOGGING_TABLE_FIELDNAME::MESSAGE 
    . " FROM " . $LOGGING_TABLENAME . ", " . $LOGGING_CONTEXT_TABLENAME
    . " WHERE " . LOGGING_TABLE_FIELDNAME::EVENT_TIME . " > '" . db_date_format($since) . "'"
    . " AND " . $LOGGING_TABLENAME . "." . LOGGING_TABLE_FIELDNAME::ID . " = " 
    .           $LOGGING_CONTEXT_TABLENAME . "." . LOGGING_CONTEXT_TABLE_FIELDNAME::ID 
    . " AND " . LOGGING_TABLE_FIELDNAME::USER_ID . " = '" . $user_id . "'"
    . " ORDER BY " . LOGGING_TABLE_FIELDNAME::EVENT_TIME . " DESC";
    
  //  error_log("LOG.SQL = " . $sql);

  $rows = db_fetch_rows($sql);

  return $rows;
    
}

function get_log_entries_for_context($args)
{
  global $LOGGING_TABLENAME;;
  global $LOGGING_CONTEXT_TABLENAME;
  $context_type = $args[LOGGING_ARGUMENT::CONTEXT_TYPE];
  $context_id = $args[LOGGING_ARGUMENT::CONTEXT_ID];
  $since = new DateTime();
  $since->setTimestamp($args[LOGGING_ARGUMENT::EVENT_TIME]);

  $sql = "select  " 
    . LOGGING_TABLE_FIELDNAME::EVENT_TIME . ", "
    . LOGGING_TABLE_FIELDNAME::USER_ID . ", "
    . LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_ID . ", "
    . LOGGING_TABLE_FIELDNAME::MESSAGE 
    . " FROM " . $LOGGING_TABLENAME . ", " . $LOGGING_CONTEXT_TABLENAME
    . " WHERE " . LOGGING_TABLE_FIELDNAME::EVENT_TIME . " > '" . db_date_format($since) . "'"
    . " AND " . $LOGGING_TABLENAME . "." . LOGGING_TABLE_FIELDNAME::ID . " = " 
    .           $LOGGING_CONTEXT_TABLENAME . "." . LOGGING_CONTEXT_TABLE_FIELDNAME::ID 
    . " AND " . LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $context_type
    . " AND " . LOGGING_CONTEXT_TABLE_FIELDNAME::CONTEXT_ID . " = '" . $context_id . "'"
    . " ORDER BY " . LOGGING_TABLE_FIELDNAME::EVENT_TIME . " DESC";
  //  error_log("LOG.SQL = " . $sql);

  $rows = db_fetch_rows($sql);

  return $rows;
    
}

handle_message("LOG");

?>

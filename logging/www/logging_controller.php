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
require_once('signer.php');
require_once('logging_constants.php');
require_once('db_utils.php');
require_once('response_format.php');
require_once('sr_constants.php');
require_once('sr_client.php');

$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);

// Register log event
// Return ID of event or error code
function log_event($args, $message)
{

  //  error_log("LE : " . print_r($message, true));

  global $LOGGING_TABLENAME;
  global $LOGGING_ATTRIBUTE_TABLENAME;

  $event_time = new DateTime();
  $event_time->setTimestamp($args[LOGGING_ARGUMENT::EVENT_TIME]);
  $user_id = $args[LOGGING_ARGUMENT::USER_ID];
  $message = $args[LOGGING_ARGUMENT::MESSAGE];
  $attributes = $args[LOGGING_ARGUMENT::ATTRIBUTES];

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
    $lastval_result = db_fetch_row($lastval_sql);
    $lastval = $lastval_result[RESPONSE_ARGUMENT::VALUE]['lastval'];
    //    error_log("LASTVAL = " . print_r($result, true));
    foreach ($attributes as $attribute_name => $attribute_value) {
      $insert_sql = "insert into " . $LOGGING_ATTRIBUTE_TABLENAME 
	. " ("
	. LOGGING_ATTRIBUTE_TABLE_FIELDNAME::EVENT_ID . ", "
	. LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME . ", "
	. LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE . ") "
	. " VALUES ("
	. $lastval . ", "
	. "'" . $attribute_name  . "', "
	. "'" . $attribute_value . "')";
      //      error_log("INSERT.SQL = " . $insert_sql);
      $insert_result = db_execute_statement($insert_sql);
    }

    $result = generate_response(RESPONSE_ARGUMENT::CODE, $lastval, '');
  }

  return $result;
}

function get_log_entries_by_author($args)
{
  global $LOGGING_TABLENAME;
  $since = new DateTime();
  $since->setTimestamp($args[LOGGING_ARGUMENT::EARLIEST_TIME]);
  $user_id = $args[LOGGING_ARGUMENT::USER_ID];

  //  error_log("GLEBA : " . print_r($args, true));

  $sql = "select  " 
    . LOGGING_TABLE_FIELDNAME::ID . ", "
    . LOGGING_TABLE_FIELDNAME::EVENT_TIME . ", "
    . LOGGING_TABLE_FIELDNAME::USER_ID . ", "
    . LOGGING_TABLE_FIELDNAME::MESSAGE 
    . " FROM " . $LOGGING_TABLENAME 
    . " WHERE " . LOGGING_TABLE_FIELDNAME::EVENT_TIME . " > '" . db_date_format($since) . "'"
    . " AND " . LOGGING_TABLE_FIELDNAME::USER_ID . " = '" . $user_id . "'"
    . " ORDER BY " . LOGGING_TABLE_FIELDNAME::EVENT_TIME . " DESC";
    
  //  error_log("LOG.SQL = " . $sql);

  $rows = db_fetch_rows($sql);

  return $rows;
    
}

function get_log_entries_by_attributes($args)
{

  global $LOGGING_TABLENAME;
  global $LOGGING_ATTRIBUTE_TABLENAME;
  $attribute_sets = $args[LOGGING_ARGUMENT::ATTRIBUTE_SETS];
  $since = new DateTime();
  $since->setTimestamp($args[LOGGING_ARGUMENT::EARLIEST_TIME]);

  $attribute_set_sql = "";
  foreach($attribute_sets as $attributes) {
    $attributes_sql = 
      compute_attributes_sql($attributes, 
			     $LOGGING_ATTRIBUTE_TABLENAME, 
			     LOGGING_ATTRIBUTE_TABLE_FIELDNAME::EVENT_ID,
			     LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME,
			     LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE);
    if($attribute_set_sql != "") {
      $attribute_set_sql = $attribute_set_sql . " UNION ";
    }
    $attribute_set_sql = $attribute_set_sql . $attributes_sql;
  }

  //  error_log("AS_SQL = " . print_r($attribute_set_sql, true));

  $sql = "select " 
    . LOGGING_TABLE_FIELDNAME::ID . ", "
    . LOGGING_TABLE_FIELDNAME::EVENT_TIME . ", "
    . LOGGING_TABLE_FIELDNAME::USER_ID . ", "
    . LOGGING_TABLE_FIELDNAME::MESSAGE 
    . " FROM " . $LOGGING_TABLENAME 
    . " WHERE " .  LOGGING_TABLE_FIELDNAME::ID . " IN  (" 
    . $attribute_set_sql . ")";
  //  error_log("LOG.SQL = " . $sql);

  $rows = db_fetch_rows($sql);

  return $rows;
    
}

function get_attributes_for_log_entry($args)
{
  $event_id = $args[LOGGING_ARGUMENT::EVENT_ID];

  global $LOGGING_ATTRIBUTE_TABLENAME;
  $sql = "select " .
    LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME . ", " .
    LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE . 
    " FROM " . $LOGGING_ATTRIBUTE_TABLENAME .
    " WHERE " . LOGGING_ATTRIBUTE_TABLE_FIELDNAME::EVENT_ID . 
    " = " . $event_id;

  $rows = db_fetch_rows($sql);

  $result = $rows;
  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $attributes = array();
    foreach($rows[RESPONSE_ARGUMENT::VALUE] as $row) {
      //      error_log("ROW = " . print_r($row, true));
      $key = $row[LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME];
      $value = $row[LOGGING_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE];
      $attributes[$key] = $value;
    }
    $result = generate_response(RESPONSE_ERROR::NONE, $attributes, '');
  }

  return $result;

}

function get_version($args)
{
  return generate_response(RESPONSE_ERROR::NONE, "1.0", "");
}

class LoggingGuardFactory implements GuardFactory
{

  public function createGuards($message) {
    return array(new TrueGuard());
  }
  
}

$mycertfile = '/usr/share/geni-ch/logging/logging-cert.pem';
$mykeyfile = '/usr/share/geni-ch/logging/logging-key.pem';
$mysigner = new Signer($mycertfile, $mykeyfile);
$guard_factory = new LoggingGuardFactory("LOG", $cs_url);
handle_message("LOG", $cs_url, default_cacerts(),
	       $mysigner->certificate(), $mysigner->privateKey(), $guard_factory);
?>

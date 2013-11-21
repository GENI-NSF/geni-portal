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

require_once('response_format.php');

/*
 * Include local host settings.
 *
 * FIXME: parameterize file location
 */
include_once('/etc/geni-ch/settings.php');

/**
 * Set of functions to help server side message handles to access (read/write) database
 */

//----------------------------------------------------------------------
// Database utility functions
//----------------------------------------------------------------------

//--------------------------------------------------
// Import the database library
//--------------------------------------------------
require_once 'MDB2.php';

// Singleton global DB connection instance
$portal_db = null;

//--------------------------------------------------
// Create the database connection.
//--------------------------------------------------
function portal_conn()
{
  // This is here only for backward compatibility.
  return db_conn();
}

function db_conn()
{
  global $portal_db;
  global $db_dsn;
  if (! isset($db_dsn)) {
    $db_dsn = 'pgsql://portal:portal@localhost/portal';
  }
  $db_options = array('debug' => 5,
                      'result_buffering' => false,
                      );
  if ($portal_db == null) {
    $portal_db =& MDB2::singleton($db_dsn, $db_options);
  }
  if (PEAR::isError($portal_db)) {
    error_log("DB ERROR: Error connecting: " . $portal_db);
    die("Error connecting: " . $portal_db);
  }
  return $portal_db;
}

function db_execute_statement($stmt, $msg = "", $rollback_on_error = false)
{
  //  error_log('db_execute_statement ' . $stmt);
  $conn = db_conn();
  $code = RESPONSE_ERROR::NONE;
  
  $result = $conn->exec($stmt);
  $value = $result;
  $output = null;
  
  if (PEAR::isError($result)) {
    $code = RESPONSE_ERROR::DATABASE;
    $output = $result;
    $value = null;
    if ($rollback_on_error) {
      $conn->rollbackTransaction();
    }
  }
  return generate_database_response($code, $value, $output);
}

function log_db_error($error_result, $query, $msg)
{
  if ($msg) {
    $log_msg .= "DB ERROR $msg: \"";
  } else {
    $log_msg = "DB ERROR: \"";
  }
  $log_msg .= MDB2::errorMessage($error_result);
  $log_msg .= "\" for query \"$query\"";
  error_log($log_msg);
}

function db_fetch_rows($query, $msg = "")
{
  //  error_log('db_fetch_rows ' . $query);
  $conn = db_conn();

  $code = RESPONSE_ERROR::NONE;
  $resultset = $conn->query($query);
  $value = $resultset;
  $output = null;
  
  $rows = array();
  if (PEAR::isError($resultset)) {
    $code = RESPONSE_ERROR::DATABASE;
    $value = null;
    $output = $resultset;
    log_db_error($resultset, $query, $msg);
  } else {
    while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
      $rows[] = $row;
    }
    $value = $rows;
  }

  $result = generate_database_response($code, $value, $output);
  
  return $result;
}


function db_fetch_row($query, $msg = "")
{
  //  error_log('db_fetch_rows ' . $query);
  $conn = db_conn();

  $code = RESPONSE_ERROR::NONE;

  $resultset = $conn->query($query);
  if (PEAR::isError($resultset) || MDB2::isError($resultset)) {
    log_db_error($resultset, $query, $msg);
    return generate_database_response(RESPONSE_ERROR::DATABASE, null, $resultset);
  }
  if (MDB2::isError($resultset->numRows())) {
    if (strpos($resultset->numRows()->getUserInfo(), "method not implemented") < 0) {
      // pgsql doesnt do numrows
      error_log("DB ERROR: " . $msg . ": '" . $resultset->numRows()->errorMessage() . "', details: '" . 
		$resultset->numRows()->getUserInfo() . "', doing query: '" . $query . "'<br/>\n");
      return generate_database_response(RESPONSE_ERROR::DATABASE, null, $resultset);
    }
  }
  $nr = $resultset->numRows(); 
  if (is_int($nr) && $nr == 0) {
    return generate_database_response($code, null, null);
  } else {
    $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
    //print "result has " . count($row) . " rows<br/>\n";
    if (! isset($row) || count($row) == 0) {
      //print "empty row<br/>\n";
      return generate_database_response($code, null, null);
    }
    return generate_database_response($code, $row, null);
  }
}

$DATE_FORMAT = 'Y-m-d H:i:s';
date_default_timezone_set('UTC');
$UTC = new DateTimeZone('UTC');

// Get date N days in future from now - in UTC
function get_future_date($num_days, $num_hours = 0)
{
  $dt = new DateTime(null, new DateTimeZone('UTC'));
  $dt->add(new DateInterval('P' . $num_days . 'DT' . $num_hours . 'H'));
  return $dt;
}

// Get format for date for inserting into database:
// Convert to UTC first
function db_date_format($date)
{
  global $DATE_FORMAT;
  $utcdate = $date;
  $utcdate = $utcdate->setTimezone(new DateTimeZone('UTC'));
  return $utcdate->format($DATE_FORMAT);
}

// Add a quote to an argument in database-specific manner
function quotify($str)
{
  $conn = portal_conn();
  return $conn->quote($str);
}

// Pull out only the 'userinfo' field from error
function generate_database_response($code, $value, $output)
{
  if ($code == RESPONSE_ERROR::NONE) {
    return generate_response($code, $value, $output);
  } else 
    {
      $userinfo = $output->getUserInfo();

      return generate_response($code, $value, $userinfo);
    }
}

// For selecting key values (ID's) from a table of name/value pairs
// matching each value pair in a given dictionary
//
 // Compute statement:
// select event_id from 
//  $attribute_tablename lea1, .... // For each entry
// where lea1.$key_fieldname = lea2.$key_fieldname ... // For each post-first entry
// and leai.$attribute_name_field = $attribute_name_field 
// and lea1.$attribute_value_field = $attribute_value_field
// If the length of the attributes is null, we match anything with attributes
function compute_attributes_sql($attributes, 
				$attribute_tablename, 
				$key_fieldname,
				$attribute_name_field,
				$attribute_value_field)
{

  // If the match attributes is empty, match anything with ANY attributes
  // But not anything with NO attributes
  if (count($attributes) == 0) {
    return "select $key_fieldname from $attribute_tablename";
  }

  $from_clause = "";
  for($i = 1; $i <= count($attributes); $i = $i + 1) {
    if ($i > 1) { $from_clause = $from_clause . ", "; }
    $from_clause = $from_clause . " " . 
      $attribute_tablename . " lea" . $i;
  }

  $link_event_ids_clause = "";
  if(count($attributes) > 1) {
    for($i = 2; $i <= count($attributes); $i = $i + 1) {
      if ($i > 2) {
	$link_event_ids_clause = $link_event_ids_clause . " AND ";
      }
      $link_event_ids_clause = $link_event_ids_clause . " " . 
	"lea1." . $key_fieldname . " = " .
	"lea" . $i . "." . $key_fieldname;
    }
    $link_event_ids_clause = $link_event_ids_clause . " AND " ;
  }

  $match_clause = "";
  $match_count = 1;
  $conn = db_conn();
  foreach($attributes as $key => $value) {
    //    error_log("ATT : " . print_r($attributes, true));
    if ($match_clause != "") {
      $match_clause = $match_clause . " AND ";
    }
    $match_clause = $match_clause . 
      "lea" . $match_count . "." . 
      $attribute_name_field . 
      " = " . $conn->quote($key, 'text') .
      " AND " .
      "lea" . $match_count . "." . 
      $attribute_value_field . 
      " = " . $conn->quote($value, 'text');
    $match_count = $match_count + 1;
  }

  $sql = "select " . "lea1." . $key_fieldname .
    " from " . $from_clause . 
    " where " . $link_event_ids_clause . " " . 
    $match_clause;

  return $sql;
    
}

/**
 * Convert a db boolean into a PHP boolean.
 */
function convert_boolean($db_value) {
  /* A boolean column is returned from PostgreSQL as a single
   * character. There is a way to push the datatype interpretation into
   * MDB2, but it's not particularly well documented. Even a source dive
   * doesn't reveal the format of the setResultType array.
   *
   * For now, do a brute force convert. But note: try to catch
   * a different database implementation by watching for values that
   * are not either "f" or "t".
   */
  if (($db_value === false) || ($db_value === "f") || ($db_value === 0)) {
    return false;
  } else if (($db_value === true) || ($db_value === "t") || ($db_value === 1)) {
    return true;
  } else {
    throw new Exception("Unknown value for DB boolean: "
            . print_r($db_value));
  }
}

/**
 * Convert list of string entities (e.g. UUID's)
 * into SQL list ('A', 'B', 'C')
 */
function convert_list($list)
{
  $list_image = "";
  foreach ($list as $elt) {
    if ($list_image != "") $list_image = $list_image . ", ";
    $list_image = $list_image . quotify($elt, 'text');
  }
  return "(" . $list_image . ")";
}

?>

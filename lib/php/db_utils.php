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
    error_log("db_dsn not set, using default");
    $db_dsn = 'pgsql://portal:portal@localhost/portal';
  } else {
    error_log("db_dsn already set: $db_dsn");
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
    
    error_log("DB ERROR " . $msg . ": " . MDB2::errorMessage());
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
    error_log("DB ERROR: " . $msg . ": '" . MDB2::errorMessage() . "', details: '" . 
	      $resultset->getUserInfo() . "', doing query: '" . $query . "'<br/>\n");
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

// Get date N days in future from now
function get_future_date($num_days, $num_hours = 0)
{
  $dt = new DateTime();
  $dt->add(new DateInterval('P' . $num_days . 'DT' . $num_hours . 'H'));
  return $dt;
}

// Get format for date for inserting into database 
function db_date_format($date)
{
  global $DATE_FORMAT;
  return $date->format($DATE_FORMAT);
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


?>

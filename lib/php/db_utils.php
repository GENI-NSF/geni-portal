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
  global $portal_db;
  $db_dsn = 'pgsql://portal:portal@localhost/portal';
  $db_options = array('debug' => 5,
                      'result_buffering' => false,
                      );
  if ($portal_db == null) {
    $portal_db =& MDB2::singleton($db_dsn, $db_options);
  }
  if (PEAR::isError($portal_db)) {
    die("Error connecting: " . $portal_db->getMessage());
  }
  return $portal_db;
}

function db_execute_statement($stmt, $msg = "", $rollback_on_error = false)
{
  //  error_log('db_execute_statement ' . $stmt);
  $conn = portal_conn();
  $result = $conn->exec($stmt);
  if (PEAR::isError($result)) {
    if ($rollback_on_error) {
      $conn->rollbackTransaction();
    }
    die("error " . $msg . ": " . $result->getMessage());
  }
  // TODO : Close the connection?
  return $result;
}

function db_fetch_rows($query, $msg = "")
{
  //  error_log('db_fetch_rows ' . $query);
  $conn = portal_conn();
  $resultset = $conn->query($query);
  if (PEAR::isError($resultset)) {
    die("error " . $msg . ": " . $resultset->getMessage());
  }
  $rows = array();
  while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    $rows[] = $row;
  }
  // TODO : Close the connection?
  return $rows;
}

function db_fetch_row($query, $msg = "")
{
  //  error_log('db_fetch_rows ' . $query);
  $conn = portal_conn();
  $resultset = $conn->query($query);
  if (PEAR::isError($resultset) || MDB2::isError($resultset)) {
    die("error " . $msg . ": '" . $resultset->getMessage() . "', doing query: '" . $query . "'<br/>\n");
  }
  if (MDB2::isError($resultset->numRows())) {
    print("error " . $msg . ": '" . $resultset->numRows()->getMessage() . "', doing query: '" . $query . "'<br/>\n");
    return null;
  }
  if ($resultset->numRows() == 0) {
    return null;
  } else {
    $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
    // TODO : Close the connection?
    return $row;
  }
}

?>

<?php


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
  if (PEAR::isError($resultset)) {
    die("error " . $msg . ": " . $resultset->getMessage());
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

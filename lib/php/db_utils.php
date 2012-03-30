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

//--------------------------------------------------
// Create the database connection.
//--------------------------------------------------
function portal_conn()
{
  $db_dsn = 'pgsql://portal:portal@localhost/portal';
  $db_options = array('debug' => 5,
                      'result_buffering' => false,
                      );
  $portal_db =& MDB2::singleton($db_dsn, $db_options);
  if (PEAR::isError($portal_db)) {
    die("Error connecting: " . $portal_db->getMessage());
  }
  return $portal_db;
}




function db_execute_statement($stmt)
{
  error_log('db_execute_statement ' . $stmt);
  $conn = portal_conn();
  $result = $conn->exec($stmt);
  return $result;
}

function db_fetch_rows($query)
{
  error_log('db_fetch_rows ' . $query);
  $conn = portal_conn();
  $resultset = $conn->query($query);
  $rows = array();
  while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    $rows[] = $row;
  }
  return $rows;
}

?>

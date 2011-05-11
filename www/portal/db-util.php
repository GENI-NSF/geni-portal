<?php

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

function loadAccount($account_id) {
  print "in db-util loadAccount<br/>";
  $conn = portal_conn();
  $sql = "SELECT * FROM account WHERE account_id = "
  . $conn->quote($account_id, 'text')
  . ";";
  print "Query = $sql<br/>";
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on identity id select: " . $resultset->getMessage());
  }
  $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
  return $row;
}
?>
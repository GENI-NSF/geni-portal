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
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT * FROM account WHERE account_id = "
  . $conn->quote($account_id, 'text')
  . ";";
  /* print "Query = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on loadAccount select: " . $resultset->getMessage());
  }
  $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
  // TODO: close the connection?
  return $row;
}

function loadIdentityAttributes($identity_id) {
  $conn = portal_conn();
  $sql = "SELECT * FROM identity_attribute WHERE identity_id = "
  . $conn->quote($identity_id, 'integer')
  . ";";
  /* print "Query = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on loadIdentityAttributes select: " . $resultset->getMessage());
  }
  //$rows = $resultset->fetchAll(MDB2_FETCHMODE_ASSOC);
  $value = array();
  while ($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    // Append to the array
    $value[] = $row;
  }
  // TODO: close the connection?
    /* foreach ($value as $var => $value) { */
    /*   print "geni_loadUser row $var = $value<br/>"; */
    /* } */
    /* print "returning $value<br/>"; */
    /* print "num val attrs = " . count($value) . "<br/>"; */
  return $value;
}
?>
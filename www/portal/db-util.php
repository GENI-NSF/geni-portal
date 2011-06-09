<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
?>
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

function db_create_slice($account_id, $slice_id, $name)
{
  $conn = portal_conn();
  $expires = new DateTime();
  $expires->add(new DateInterval('P30D'));

  $my_tx = $conn->beginTransaction();
  $sql = "INSERT INTO slice (slice_id, name, expiration) VALUES ("
    . $conn->quote($slice_id, 'text')
    . ', ' . $conn->quote($name, 'text')
    . ', ' . $conn->quote($expires->format('Y-m-d H:i:s'), 'timestamp')
    . ');';
  /* print "command = $sql<br/>"; */
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    $my_tx = $conn->rollback();
    die("error on slice insert: " . $result->getMessage());
  }
  $sql = "INSERT INTO account_slice (account_id, slice_id) VALUES ("
    . $conn->quote($account_id, 'text')
    . ', ' . $conn->quote($slice_id, 'text')
    . ');';
  /* print "command 2 = $sql<br/>"; */
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    $my_tx = $conn->rollback();
    die("error on account_slice insert: " . $result->getMessage());
  }
  $my_tx = $conn->commit();
}

function fetch_slices($account_id)
{
  $conn = portal_conn();
  $sql = "SELECT slice.slice_id, slice.name, slice.expiration"
    . " FROM slice, account_slice"
    . " WHERE account_slice.account_id = "
    . $conn->quote($account_id, 'text')
    . " AND slice.slice_id = account_slice.slice_id"
    . ";";
  // print "Query = $sql<br/>";
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch_slices select: " . $resultset->getMessage());
  }
  $value = array();
  while ($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    // Append to the array
    $value[] = $row;
  }
  // TODO: close the connection?
  return $value;
}

function fetch_slice($slice_id)
{
  $conn = portal_conn();
  $sql = "SELECT slice.slice_id, slice.name, slice.expiration"
    . " FROM slice"
    . " WHERE slice.slice_id = "
    . $conn->quote($slice_id, 'text')
    . ";";
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch_slice select: " . $resultset->getMessage());
  }
  // There should be one and only one slice with this id
  $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
  return $row;
}

function db_add_public_key($account_id, $public_key, $description)
{
  $conn = portal_conn();

  $sql = "INSERT INTO public_key "
    . "(account_id, public_key, description) VALUES ("
    . $conn->quote($account_id, 'text')
    . ', ' . $conn->quote($public_key, 'text')
    . ', ' . $conn->quote($description, 'text')
    . ');';
  print "command = $sql<br/>";
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on public key insert: " . $result->getMessage());
  }
}

?>

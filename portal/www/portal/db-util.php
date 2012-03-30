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
require_once 'util.php';
require_once 'db_utils.php';

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

function loadAccountPrivileges($account_id) {
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT privilege FROM account_privilege WHERE account_id = "
  . $conn->quote($account_id, 'text')
  . ";";
  /* print "Query = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on loadAccount select: " . $resultset->getMessage());
  }
  $privs = array();
  while ($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    $privs[] = $row["privilege"];
  }
  // TODO: close the connection?
  return $privs;
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
  $urn = "urn:publicid:IDN+geni:gpo:portal+slice+" . $name;

  $my_tx = $conn->beginTransaction();
  $sql = "INSERT INTO slice (slice_id, name, expiration, owner, urn) VALUES ("
    . $conn->quote($slice_id, 'text')
    . ', ' . $conn->quote($name, 'text')
    . ', ' . $conn->quote($expires->format('Y-m-d H:i:s'), 'timestamp')
    . ', ' . $conn->quote($account_id, 'text')
    . ', ' . $conn->quote($urn, 'text')
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
  $sql = "SELECT slice.* FROM slice, account_slice"
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
  $sql = "SELECT * FROM slice"
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

function fetch_slice_by_name($name)
{
  $conn = portal_conn();
  $sql = "SELECT * FROM slice"
    . " WHERE slice.name = "
    . $conn->quote($name, 'text')
    . ";";
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch_slice_by_name select: " . $resultset->getMessage());
  }
  if ($resultset->numRows() == 0) {
    return NULL;
  } else {
    // There should be one and only one slice with this name
    $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
    return $row;
  }
}

function db_add_public_key($account_id, $public_key, $filename, $description)
{
  $conn = portal_conn();
  $sql = "INSERT INTO public_key "
    . "(account_id, public_key, filename, description) VALUES ("
    . $conn->quote($account_id, 'text')
    . ', ' . $conn->quote($public_key, 'text')
    . ', ' . $conn->quote($filename, 'text')
    . ', ' . $conn->quote($description, 'text')
    . ');';
  /* print "command = $sql<br/>"; */
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on public key insert: " . $result->getMessage());
  }
}

function db_add_key_cert($account_id, $certificate)
{
  $conn = portal_conn();
  $sql = "UPDATE public_key"
    . " SET certificate = "
    . $conn->quote($certificate, 'text')
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text')
    . ';';
  /* print "command = $sql<br/>"; */
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on certificate insert: " . $result->getMessage());
  }
}

function db_add_inside_key_cert($account_id, $certificate, $key)
{
  $conn = portal_conn();
  $sql = "INSERT into inside_key (CERTIFICATE, PRIVATE_KEY, ACCOUNT_ID) values ("
    . $conn->quote($certificate, 'text')
    . ', '
    . $conn->quote($key, 'text')
    . ', '
    . $conn->quote($account_id, 'text')
    . ');';
/*  print "command = $sql<br/>";  */
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on inside key/certificate insert: " . $result->getMessage());
  }
}

function db_fetch_public_key($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT *"
    . " FROM public_key "
    . " WHERE public_key.account_id = "
    . $conn->quote($account_id, 'text')
    . ';';
  /* print "command = $sql<br/>\n"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch public key: " . $resultset->getMessage());
  }
  return $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
}

function db_fetch_inside_private_key_cert($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT private_key, certificate"
    . " FROM inside_key "
    . " WHERE inside_key.account_id = "
    . $conn->quote($account_id, 'text')
    . ';';
  /* print "command = $sql<br/>\n"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch public key: " . $resultset->getMessage());
  }
  return $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
}

function db_fetch_user_by_username($username)
{
  $conn = portal_conn();

  $sql = "SELECT *"
    . " FROM account "
    . " WHERE username = "
    . $conn->quote($username, 'text')
    . ';';
  /* print "command = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch user by username: " . $resultset->getMessage());
  }
  return $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
}

function fetch_abac_fingerprint($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_fingerprint"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text')
    . ';';
  /* print "command = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch abac fingerprint: " . $resultset->getMessage());
  }
  $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
  return $row["abac_fingerprint"];
}

function fetch_abac_id($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_id"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text')
    . ';';
  /* print "command = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch abac id: " . $resultset->getMessage());
  }
  $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
  return $row["abac_id"];
}

function fetch_abac_key($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_key"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text')
    . ';';
  /* print "command = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on fetch abac key: " . $resultset->getMessage());
  }
  $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
  return $row["abac_key"];
}

function approve_account($account_id)
{
  $conn = portal_conn();

  $sql = "UPDATE account"
    . " SET status = 'active'"
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text')
    . ';';
  /* print "command = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on update account active: " . $resultset->getMessage());
  }

  $sql = "INSERT INTO account_privilege"
    . " VALUES("
    . $conn->quote($account_id, 'text')
    . ", 'slice'"
    . ');';
  /* print "command = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on insert slice privilege: " . $resultset->getMessage());
  }


  $row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC);
  return $row["abac_key"];
}

function requestedAccounts() {
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT * FROM requested_account;";
  /* print "Query = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on loadAccount select: " . $resultset->getMessage());
  }
  $value = array();
  while ($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    $value[] = $row;
  }
  // TODO: close the connection?
  return $value;
}

function loadIdentitiesByAccountId($account_id) {
  $conn = portal_conn();
  $sql = "SELECT * FROM identity WHERE account_id = "
  . $conn->quote($account_id, 'text')
  . ";";
  /* print "Query = $sql<br/>"; */
  $resultset = $conn->query($sql);
  if (PEAR::isError($resultset)) {
    die("error on loadIdentityAttributes select: " . $resultset->getMessage());
  }
  $value = array();
  while ($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    // Append to the array
    $value[] = $row;
  }
  // TODO: close the connection?
  return $value;
}

function storeAbacAssertion($assertion,
                            $issuer_fingerprint,
                            $issuer_role,
                            $subject_fingerprint,
                            $expiration) {
  $conn = portal_conn();
  $base64_assertion = base64_encode($assertion);
  $sql = "INSERT INTO abac_assertion ("
    . "issuer, issuer_role, subject, expiration, credential"
    . ") VALUES ("
    . $conn->quote($issuer_fingerprint, 'text')
    . ', ' . $conn->quote($issuer_role, 'text')
    . ', ' . $conn->quote($subject_fingerprint, 'text')
    . ', ' . $conn->quote($expiration->format('Y-m-d H:i:s'), 'timestamp')
    . ', ' . $conn->quote($base64_assertion, 'text')
    . ');';
  /* print "command = $sql<br/>"; */
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on abac assertion insert: " . $result->getMessage());
  }
}

?>

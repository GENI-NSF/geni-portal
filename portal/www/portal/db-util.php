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

function loadAccount($account_id) 
{
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT * FROM account WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql);
  return $row;
}

function loadAccountPrivileges($account_id) {
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT privilege FROM account_privilege WHERE account_id = "
    . $conn->quote($account_id, 'text');
  /* print "Query = $sql<br/>"; */
  $rows = db_fetch_rows($sql);
  $privs = array();
  foreach ($rows as $row) {
    $privs[] = $row["privilege"];
  }
  return $privs;
}

function loadIdentityAttributes($identity_id) {
  $conn = portal_conn();
  $sql = "SELECT * FROM identity_attribute WHERE identity_id = "
    . $conn->quote($identity_id, 'integer');
  /* print "Query = $sql<br/>"; */
  $value = db_fetch_rows($sql);
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
    . ')';
  /* print "command = $sql<br/>"; */
  $result = db_execute_statement($sql, "slice insert", true);

  $sql = "INSERT INTO account_slice (account_id, slice_id) VALUES ("
    . $conn->quote($account_id, 'text')
    . ', ' . $conn->quote($slice_id, 'text')
    . ')';
  /* print "command 2 = $sql<br/>"; */
  $result = db_execute_statement($sql, "account_slice insert", true);
  $my_tx = $conn->commit();
}

function fetch_slices($account_id)
{
  $conn = portal_conn();
  $sql = "SELECT slice.* FROM slice, account_slice"
    . " WHERE account_slice.account_id = "
    . $conn->quote($account_id, 'text')
    . " AND slice.slice_id = account_slice.slice_id";
// print "Query = $sql<br/>";
$value = db_fetch_rows($sql);
return $value;
}

function fetch_slice($slice_id)
{
  $conn = portal_conn();
  $sql = "SELECT * FROM slice"
    . " WHERE slice.slice_id = "
    . $conn->quote($slice_id, 'text');
  $row = db_fetch_row($sql);
  return $row;
}

function fetch_slice_by_name($name)
{
  $conn = portal_conn();
  $sql = "SELECT * FROM slice"
    . " WHERE slice.name = "
    . $conn->quote($name, 'text');
  $row = db_fetch_row($sql, "fetch_slice_by_name");
  return $row;
}

function db_add_public_key($account_id, $public_key, $filename, $description)
{
  $conn = portal_conn();
  $sql = "INSERT INTO public_key "
    . "(account_id, public_key, filename, description) VALUES ("
    . $conn->quote($account_id, 'text')
    . ', ' . $conn->quote($public_key, 'text')
    . ', ' . $conn->quote($filename, 'text')
    . ', ' . $conn->quote($description, 'text');
  /* print "command = $sql<br/>"; */
  $result = db_execute_statement($sql, "public key insert");
  return $result;
}

function db_add_key_cert($account_id, $certificate)
{
  $conn = portal_conn();
  $sql = "UPDATE public_key"
    . " SET certificate = "
    . $conn->quote($certificate, 'text')
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  /* print "command = $sql<br/>"; */
  $result = db_execute_statement($sql, "certificate insert");
  return $result;
}

function db_add_inside_key_cert($account_id, $certificate, $key)
{
  $conn = portal_conn();
  $sql = "INSERT into inside_key (CERTIFICATE, PRIVATE_KEY, ACCOUNT_ID) values ("
    . $conn->quote($certificate, 'text')
    . ', '
    . $conn->quote($key, 'text')
    . ', '
    . $conn->quote($account_id, 'text');
  /*  print "command = $sql<br/>";  */
  $result = db_execute_statement($sql, "inside key/certificaste");
  return $result;
}

function db_fetch_public_key($account_id)
{
  $conn = portal_conn();
  $sql = "SELECT *"
    . " FROM public_key "
    . " WHERE public_key.account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql, "fetch public key");
  return $row;
}

function db_fetch_inside_private_key_cert($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT private_key, certificate"
    . " FROM inside_key "
    . " WHERE inside_key.account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql, "fetch inside private key");
  return $row;
}

function db_fetch_user_by_username($username)
{
  $conn = portal_conn();

  $sql = "SELECT *"
    . " FROM account "
    . " WHERE username = "
    . $conn->quote($username, 'text');
  $row = db_fetch_row($sql, "fetch user by username");
  return $row;
}

function fetch_abac_fingerprint($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_fingerprint"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql, "fetch abac fingerprint");
  return $row["abac_fingerprint"];
}

function fetch_abac_id($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_id"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql, "fetch abac id");
  return $row["abac_id"];
}

function fetch_abac_key($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_key"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql, "fetch abac key");
  return $row["abac_key"];
}

function approve_account($account_id)
{
  $conn = portal_conn();

  $sql = "UPDATE account"
    . " SET status = 'active'"
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  /* print "command = $sql<br/>"; */
  $result = db_execute_statement($sql, "update account active");

  $sql = "INSERT INTO account_privilege"
    . " VALUES("
    . $conn->quote($account_id, 'text')
    . ", 'slice'"
    . ')';
  /* print "command = $sql<br/>"; */
  $row = db_fetch_row($sql, "approve account");
  return $row["abac_key"];
}

function requestedAccounts() {
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT * FROM requested_account";
  /* print "Query = $sql<br/>"; */
  $value = db_fetch_rows($sql, "loadAccount select");
  return $value;
}

function loadIdentitiesByAccountId($account_id) {
  $conn = portal_conn();
  $sql = "SELECT * FROM identity WHERE account_id = "
    . $conn->quote($account_id, 'text');
  /* print "Query = $sql<br/>"; */
  $value = db_fetch_rows($sql, "loadIdentityAttributes select");
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
    . ')';
  /* print "command = $sql<br/>"; */
  $result = db_execute_statement($sql, "abac assertion insert");
  return $result;
}

?>

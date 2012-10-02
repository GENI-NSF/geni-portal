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
require_once 'response_format.php';

function loadAccount($account_id) 
{
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT * FROM account WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql);
  return $row[RESPONSE_ARGUMENT::VALUE];
}

// Get just the account status for this account id, to see if it has changed since the cached value
function loadAccountStatus($account_id)
{
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT status FROM account WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql);
  return $row[RESPONSE_ARGUMENT::VALUE];
}

/*
function loadAccountPrivileges($account_id) {
  // print "in db-util loadAccount<br/>"; 
  $conn = portal_conn();
  $sql = "SELECT privilege FROM account_privilege WHERE account_id = "
    . $conn->quote($account_id, 'text');
// print "Query = $sql<br/>"; 
  $result = db_fetch_rows($sql);
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  $privs = array();
  foreach ($rows as $row) {
    $privs[] = $row["privilege"];
  }
  return $privs;
}
*/

function loadIdentityAttributes($identity_id) {
  $conn = portal_conn();
  $sql = "SELECT * FROM identity_attribute WHERE identity_id = "
    . $conn->quote($identity_id, 'integer');
  /* print "Query = $sql<br/>"; */
  $value = db_fetch_rows($sql);
  return $value[RESPONSE_ARGUMENT::VALUE];
}

function db_create_slice($account_id, $slice_id, $name)
{
  $conn = portal_conn();
  $expires = get_future_date(30);
  $urn = "urn:publicid:IDN+geni:gpo:portal+slice+" . $name;

  $my_tx = $conn->beginTransaction();
  $sql = "INSERT INTO slice (slice_id, name, expiration, owner, urn) VALUES ("
    . $conn->quote($slice_id, 'text')
    . ', ' . $conn->quote($name, 'text')
    . ', ' . $conn->quote(db_date_format($expires), 'timestamp')
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
  // FIXME: Check for errors
  return $value[RESPONSE_ARGUMENT::VALUE];
}

function fetch_slice($slice_id)
{
  $conn = portal_conn();
  $sql = "SELECT * FROM slice"
    . " WHERE slice.slice_id = "
    . $conn->quote($slice_id, 'text');
  $row = db_fetch_row($sql);
  // FIXME: Check for errors
  return $row[RESPONSE_ARGUMENT::VALUE];
}

function fetch_slice_by_name($name)
{
  $conn = portal_conn();
  $sql = "SELECT * FROM slice"
    . " WHERE slice.name = "
    . $conn->quote($name, 'text');
  $row = db_fetch_row($sql, "fetch_slice_by_name");
  // FIXME: Check for errors
  return $row[RESPONSE_ARGUMENT::VALUE];
}

function db_add_outside_key_cert($account_id, $certificate, $key)
{
  $conn = portal_conn();
  $sql = "INSERT INTO outside_key (certificate, private_key, account_id)"
    . " values ("
    . $conn->quote($certificate, 'text')
    . ', '
    . $conn->quote($key, 'text')
    . ', '
    . $conn->quote($account_id, 'text')
    . ")";
  /*  print "command = $sql<br/>";  */
  $result = db_execute_statement($sql, "outside key/certificate");
  // FIXME: Check for errors
  return $result[RESPONSE_ARGUMENT::VALUE];
}

function db_fetch_outside_private_key_cert($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT private_key, certificate"
    . " FROM outside_key "
    . " WHERE outside_key.account_id = "
    . $conn->quote($account_id, 'text');
  $row = db_fetch_row($sql, "fetch outside private key");
  // FIXME: Check for errors
  return $row[RESPONSE_ARGUMENT::VALUE];
}

function db_fetch_user_by_username($username)
{
  $conn = portal_conn();

  $sql = "SELECT *"
    . " FROM account "
    . " WHERE username = "
    . $conn->quote($username, 'text');
  $row = db_fetch_row($sql, "fetch user by username");
  // FIXME: Check for errors
  return $row[RESPONSE_ARGUMENT::VALUE];
}

function fetch_abac_fingerprint($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_fingerprint"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $result = db_fetch_row($sql, "fetch abac fingerprint");
  // FIXME: Check for errors
  $row = $result[RESPONSE_ARGUMENT::VALUE];
  return $row["abac_fingerprint"];
}

function fetch_abac_id($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_id"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $result = db_fetch_row($sql, "fetch abac id");
  // FIXME: Check for errors
  $row = $result[RESPONSE_ARGUMENT::VALUE];
  return $row["abac_id"];
}

function fetch_abac_key($account_id)
{
  $conn = portal_conn();

  $sql = "SELECT abac_key"
    . " FROM abac "
    . " WHERE account_id = "
    . $conn->quote($account_id, 'text');
  $result = db_fetch_row($sql, "fetch abac key");
  $row = $result[RESPONSE_ARGUMENT::VALUE];  // FIXME: Check for errors
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

  /* $sql = "INSERT INTO account_privilege" */
  /*   . " VALUES(" */
  /*   . $conn->quote($account_id, 'text') */
  /*   . ", 'slice'" */
  /*   . ')'; */
  /* /\* print "command = $sql<br/>"; *\/ */
  /* $result = db_fetch_row($sql, "approve account"); */
  $row = $result[RESPONSE_ARGUMENT::VALUE];  // FIXME: Check for errors
  return $row["abac_key"];
}

function requestedAccounts() {
  /* print "in db-util loadAccount<br/>"; */
  $conn = portal_conn();
  $sql = "SELECT * FROM requested_account";
  /* print "Query = $sql<br/>"; */
  $value = db_fetch_rows($sql, "loadAccount select");
  return $value[RESPONSE_ARGUMENT::VALUE];  // FIXME: Check for errors
}

function loadIdentitiesByAccountId($account_id) {
  $conn = portal_conn();
  $sql = "SELECT * FROM identity WHERE account_id = "
    . $conn->quote($account_id, 'text');
  /* print "Query = $sql<br/>"; */
  $value = db_fetch_rows($sql, "loadIdentityAttributes select");
  return $value[RESPONSE_ARGUMENT::VALUE];
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
    . ', ' . $conn->quote(db_date_format($expiration), 'timestamp')
    . ', ' . $conn->quote($base64_assertion, 'text')
    . ')';
  /* print "command = $sql<br/>"; */
  $result = db_execute_statement($sql, "abac assertion insert");
  return $result[RESPONSE_ARGUMENT::VALUE];
}

function fetchRSpecById($id) {
  $conn = portal_conn();
  $sql = "SELECT rspec.rspec FROM rspec where rspec.id = "
    . $conn->quote($id, 'integer');
  /* print "Query = $sql<br/>"; */
  $result = db_fetch_row($sql, "fetchRSpecById($id)");
  $row = $result[RESPONSE_ARGUMENT::VALUE];
  return $row['rspec'];
}

/**
 * Get RSpec metadata for all public RSpecs
 * and all private RSpecs owned by the current user.
 */
function fetchRSpecMetaData($user) {
  $conn = portal_conn();
  $sql = "SELECT id, name, description FROM rspec";
  $sql .= " where visibility = 'public'";
  /* print "Query = $sql<br/>"; */
  $rows = db_fetch_rows($sql, "fetchRSpecMetaData");
  $public_rspecs = $rows[RESPONSE_ARGUMENT::VALUE];
  $sql = "SELECT id, name, description FROM rspec";
  $sql .= " where owner_id = ";
  $sql .= $conn->quote($user->account_id, 'text');
  $sql .= " AND visibility = 'private'";
  $rows = db_fetch_rows($sql, "fetchRSpecMetaData");
  $private_rspecs = $rows[RESPONSE_ARGUMENT::VALUE];
  // List private RSpecs first.
  return array_merge($private_rspecs, $public_rspecs);
}

function db_add_rspec($user, $name, $description, $rspec, $schema,
        $schema_version, $visibility)
{
  $conn = portal_conn();
  $sql = "INSERT INTO rspec";
  $sql .= " (name, description, rspec, schema, schema_version";
  $sql .= ", owner_id, visibility)";
  $sql .= " VALUES (";
  $sql .= $conn->quote($name, 'text');
  $sql .= ", " . $conn->quote($description, 'text');
  $sql .= ", " . $conn->quote($rspec, 'text');
  $sql .= ", " . $conn->quote($schema, 'text');
  $sql .= ", " . $conn->quote($schema_version, 'text');
  $sql .= ", " . $conn->quote($user->account_id, 'text');
  $sql .= ", " . $conn->quote($visibility, 'text');
  $sql .= ")";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
  $result = db_execute_statement($sql, "db_add_rspec");
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $msg = "db_add_rspec: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
    return false;
  }
  return $result[RESPONSE_ARGUMENT::VALUE];
}
?>
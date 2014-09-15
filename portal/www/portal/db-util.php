<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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
require_once 'speaksforcred.php';

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

function fetchRSpec($id) {
  $conn = portal_conn();
  $sql = "SELECT * FROM rspec where rspec.id = "
    . $conn->quote($id, 'integer');
  /* print "Query = $sql<br/>"; */
  $result = db_fetch_row($sql, "fetchRSpec($id)");
  $row = $result[RESPONSE_ARGUMENT::VALUE];
  return $row;
}

function updateRSpec($id, $name, $desc, $vis, &$error_msg) {
  if ($vis !== 'public' && $vis !== 'private') {
    $error_msg = "Invalid RSpec visibility: $vis."
      . " Must be 'public' or 'private'.";
    return false;
  }

  $conn = portal_conn();
  $sql = 'UPDATE rspec'
    . ' SET name = '
    . $conn->quote($name, 'text')
    . ', description = '
    . $conn->quote($desc, 'text')
    . ', visibility = '
    . $conn->quote($vis, 'text')
    . ' WHERE id = '
    . $conn->quote($id, 'integer');
  $result = db_execute_statement($sql, "update rspec");
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $error_msg = "Database error: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $error_msg);
    error_log($error_msg);
    return false;
  } else {
    return true;
  }
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

function fetchRSpecNameById($id) {
  $conn = portal_conn();
  $sql = "SELECT rspec.name FROM rspec where rspec.id = "
    . $conn->quote($id, 'integer');
  /* print "Query = $sql<br/>"; */
  $result = db_fetch_row($sql, "fetchRSpecById($id)");
  $row = $result[RESPONSE_ARGUMENT::VALUE];
  return $row['name'];
}

/**
 * Get RSpec metadata for all public RSpecs
 * and all private RSpecs owned by the current user.
 */
function fetchRSpecMetaData($user) {
  $metadata_columns = ("id, name, description, visibility, bound, owner_id"
                       . ", owner_name, owner_email, stitch");
  $conn = portal_conn();
  $sql = "SELECT $metadata_columns FROM rspec";
  $sql .= " where visibility = 'public'";
  /* print "Query = $sql<br/>"; */
  $rows = db_fetch_rows($sql, "fetchRSpecMetaData");
  $public_rspecs = $rows[RESPONSE_ARGUMENT::VALUE];
  $sql = "SELECT $metadata_columns FROM rspec";
  $sql .= " where owner_id = ";
  $sql .= $conn->quote($user->account_id, 'text');
  $sql .= " AND visibility = 'private'";
  $sql .= " ORDER BY name";
  $rows = db_fetch_rows($sql, "fetchRSpecMetaData");
  $private_rspecs = $rows[RESPONSE_ARGUMENT::VALUE];
  // List private RSpecs first.
  return array_merge($private_rspecs, $public_rspecs);
}

function db_add_rspec($user, $name, $description, $rspec, $schema,
		      $schema_version, $visibility, $is_bound, $is_stitch, $am_urns)
{
  if (! isset($description) or is_null($description) or $description == '') {
    $msg = "Description missing for RSpec '$name'";
    error_log($msg);
    relative_redirect('error-text.php' . "?error=" . urlencode($msg));
    return false;
  }
  if (! isset($name) or is_null($name) or $name == '') {
    $msg = "Name missing for RSpec with description '$description'";
    error_log($msg);
    relative_redirect('error-text.php' . "?error=" . urlencode($msg));
    return false;
  }
  $conn = portal_conn();
  $sql = "INSERT INTO rspec";
  $sql .= " (name, description, rspec, schema, schema_version, owner_id";
  $sql .= ", owner_name, owner_email, visibility, bound, stitch, am_urns)";
  $sql .= " VALUES (";
  $sql .= $conn->quote($name, 'text');
  $sql .= ", " . $conn->quote($description, 'text');
  $sql .= ", " . $conn->quote($rspec, 'text');
  $sql .= ", " . $conn->quote($schema, 'text');
  $sql .= ", " . $conn->quote($schema_version, 'text');
  $sql .= ", " . $conn->quote($user->account_id, 'text');
  $sql .= ", " . $conn->quote($user->prettyName(), 'text');
  $sql .= ", " . $conn->quote($user->email(), 'text');
  $sql .= ", " . $conn->quote($visibility, 'text');
  $sql .= ", " . $conn->quote($is_bound, 'boolean');
  $sql .= ", " . $conn->quote($is_stitch, 'boolean');
  $sql .= ", " . $conn->quote($am_urns, 'text');
  $sql .= ")";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
  //  error_log($sql);
  $result = db_execute_statement($sql, "db_add_rspec");
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $msg = "db_add_rspec: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
    error_log($msg);
    return false;
  }
  return $result[RESPONSE_ARGUMENT::VALUE];
}

function db_update_rspec($rspec_id, $user, $name, $description,
                         $rspec, $schema,
                         $schema_version, $visibility, $is_bound,
                         $is_stitch, $am_urns, $uploaded_rspec)
{
  if (! isset($description) or is_null($description) or $description == '') {
    $msg = "Description missing for RSpec '$name'";
    error_log($msg);
    relative_redirect('error-text.php' . "?error=" . urlencode($msg));
    return false;
  }
  if (! isset($name) or is_null($name) or $name == '') {
    $msg = "Name missing for RSpec with description '$description'";
    error_log($msg);
    relative_redirect('error-text.php' . "?error=" . urlencode($msg));
    return false;
  }
  $conn = portal_conn();
  $sql = "UPDATE rspec SET ";
  $sql .= "name = " . $conn->quote($name, 'text');
  $sql .= ", description = " . $conn->quote($description, 'text');
  $sql .= ", owner_id = " . $conn->quote($user->account_id, 'text');
  $sql .= ", owner_name = " . $conn->quote($user->prettyName(), 'text');
  $sql .= ", owner_email = " . $conn->quote($user->email(), 'text');
  $sql .= ", visibility = " . $conn->quote($visibility, 'text');
  if($uploaded_rspec) {
    $sql .= ", rspec = " . $conn->quote($rspec, 'text');
    $sql .= ", schema = " . $conn->quote($schema, 'text');
    $sql .= ", schema_version = " . $conn->quote($schema_version, 'text');
    $sql .= ", bound = " . $conn->quote($is_bound, 'boolean');
    $sql .= ", stitch = " . $conn->quote($is_stitch, 'boolean');
    $sql .= ", am_urns = " . $conn->quote($am_urns, 'text');
  }
  $sql .= " where id = " . $rspec_id;
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
  //  error_log($sql);                                                          
  $result = db_execute_statement($sql, "db_update_rspec");
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $msg = "db_add_rspec: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
    error_log($msg);
    return false;
  }
  return $result[RESPONSE_ARGUMENT::VALUE];
}

function deleteRSpecById($id, $user)
{
  $conn = portal_conn();
  // check that you are the owner before deleting

  $conn = portal_conn();
  $sql = "SELECT id FROM rspec WHERE id =";
  $sql .= $conn->quote($id, 'integer');
  $sql .= " AND owner_id =";
  $sql .= $conn->quote($user->account_id, 'text');
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
  //  error_log($sql);
  $result = db_fetch_rows($sql, "deleteRSpecById");
  $owned_rspec = $result[RESPONSE_ARGUMENT::VALUE];
  if (count($owned_rspec) == 0) {
    $msg = "deleteRSpecById: Can not delete rspec. User didn't create rspec.";
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
    error_log($msg);
    return false;
  }

  // now delete rspec
  $sql = "DELETE FROM rspec WHERE id = ";
  $sql .= $conn->quote($id, 'integer');
  $result = db_execute_statement($sql, "deleteRSpecById");
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $msg = "deleteRSpecById: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
    return false;
  } else {
    return true;
  }
}

function public_rspec_name_exists($name)
{
  $conn = portal_conn();
  $qname = $conn->quote($name, 'text');
  $visibility = $conn->quote('public', 'text');
  $sql = "SELECT count(*) FROM rspec WHERE";
  $sql .= " visibility = $visibility";
  $sql .= " AND upper(name) = upper($qname)";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
  //  error_log($sql);
  $result = db_fetch_row($sql, "rspec_name_exists");
  $value = $result[RESPONSE_ARGUMENT::VALUE];
  $count = $value['count'];
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL,
              'public_rspec_name_exists value = ' . print_r($count, true));
  return $count != 0;
}

function private_rspec_name_exists($user, $name)
{
  $conn = portal_conn();
  $qname = $conn->quote($name, 'text');
  $visibility = $conn->quote('private', 'text');
  $owner_id = $conn->quote($user->account_id, 'text');
  $sql = "SELECT count(*) FROM rspec WHERE";
  $sql .= " owner_id = $owner_id";
  $sql .= " AND visibility = $visibility";
  $sql .= " AND upper(name) = upper($qname)";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
  //  error_log($sql);
  $result = db_fetch_row($sql, "private_rspec_name_exists");
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL,
              'private_rspec_name_exists result = ' . print_r($result, true));
  $value = $result[RESPONSE_ARGUMENT::VALUE];
  $count = $value['count'];
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL,
              'private_rspec_name_exists value = ' . print_r($count, true));
  return $count != 0;
}

/**
 * Determine if an rspec exists with the given name.
 */
function rspec_name_exists($user, $visibility, $name)
{
  if ($visibility == 'public') {
    return public_rspec_name_exists($name);
  } else {
    return private_rspec_name_exists($user, $name);
  }
}

function record_last_seen($user, $request_uri)
{
  $conn = portal_conn();
  $q_request_uri = $conn->quote($request_uri, 'text');
  $q_member_id = $conn->quote($user->account_id, 'text');
  $sql = "UPDATE last_seen SET";
  $sql .= " request_uri = " . $q_request_uri;
  $sql .= ", ts = now()";
  $sql .= " WHERE member_id = " . $q_member_id;
  $result = db_execute_statement($sql, "record_last_seen update");
  // geni_syslog("UPDATE result = " . print_r($result, true));
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $msg = "record_last_seen update: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
    error_log($msg);
    return false;
  } elseif ($result[RESPONSE_ARGUMENT::VALUE] == 0) {
    // There was nothing to update, so do the insert
    $sql = "INSERT INTO last_seen (member_id, request_uri)";
    $sql .= " VALUES ($q_member_id, $q_request_uri)";
    //geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
    $result = db_execute_statement($sql, "record_last_seen insert");
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      $msg = "record_last_seen insert: " . $result[RESPONSE_ARGUMENT::OUTPUT];
      geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
      error_log($msg);
      return false;
    }
    return true;
  } else {
    // The update succeeded, return true
    return true;
  }
}

function store_speaks_for($token, $cred, $signer_urn, $expires) {
  $conn = portal_conn();
  $q_cred = $conn->quote($cred, 'text');
  $q_expires = $conn->quote($expires, 'timestamp');
  $q_token = $conn->quote($token, 'text');
  $q_signer_urn = $conn->quote($signer_urn, 'text');

  $sql = "UPDATE speaks_for SET";
  $sql .= " cred = " . $q_cred;
  $sql .= ", upload_ts = now() at time zone 'utc'";
  $sql .= ", expires_ts = " . $q_expires;
  $sql .= " WHERE token = " . $q_token;
  $sql .= "   AND signer_urn = " . $q_signer_urn;
  $result = db_execute_statement($sql, "store_speaks_for update");
  // geni_syslog("UPDATE result = " . print_r($result, true));
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $msg = "store_speaks_for update: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
    error_log($msg);
    return false;
  } elseif ($result[RESPONSE_ARGUMENT::VALUE] == 0) {
    // There was nothing to update, so do the insert
    $sql = "INSERT INTO speaks_for";
    $sql .= ' (cred, upload_ts, expires_ts, token, signer_urn)';
    $sql .= " VALUES ($q_cred, now() at time zone 'utc', $q_expires,";
    $sql .= "         $q_token, $q_signer_urn)";
    //geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $sql);
    $result = db_execute_statement($sql, "record_last_seen insert");
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      $msg = "store_speaks_for insert: " . $result[RESPONSE_ARGUMENT::OUTPUT];
      geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
      error_log($msg);
      return false;
    }
    return true;
  } else {
    // The update succeeded, return true
    return true;
  }
}

function fetch_speaks_for($token, &$expires) {
  $expires = null;
  $conn = portal_conn();
  $q_token = $conn->quote($token, 'text');
  $sql = 'SELECT signer_urn, expires_ts, cred FROM speaks_for';
  $sql .= ' WHERE token = ' . $q_token;
  /* print "Query = $sql<br/>"; */
  $result = db_fetch_row($sql, "fetch_speaks_for");
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      $msg = "fetch_speaks_for: " . $result[RESPONSE_ARGUMENT::OUTPUT];
      geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
      error_log($msg);
      return false;
  } elseif (is_null($result[RESPONSE_ARGUMENT::VALUE])) {
    // No credential in DB
    return null;
  } else {
    $row = $result[RESPONSE_ARGUMENT::VALUE];
    $signer_urn = $row['signer_urn'];
    $expires = $row['expires_ts'];
    $xml_cred = $row['cred'];
    $result = SpeaksForCredential::fromInfo($xml_cred, $expires, $signer_urn);
    return $result;
  }
}

function delete_speaks_for($token) {
  $conn = portal_conn();
  $q_token = $conn->quote($token, 'text');
  $sql = 'DELETE FROM speaks_for';
  $sql .= ' WHERE token = ' . $q_token;
  /* print "Query = $sql<br/>"; */
  $result = db_execute_statement($sql, "delete_speaks_for");
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      $msg = "delete_speaks_for: " . $result[RESPONSE_ARGUMENT::OUTPUT];
      geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
      error_log($msg);
      return FALSE;
  } else {
    return TRUE;
  }
}
?>

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

$prev_name = session_id('MA-SESSION');

require_once('message_handler.php');
require_once('db_utils.php');
require_once('file_utils.php');
require_once('ma_constants.php');
require_once('response_format.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('geni_syslog.php');
require_once('signer.php');
require_once('cs_constants.php');
require_once('cs_client.php');
require_once('cert_utils.php');
require_once('ma_utils.php');
require_once('logging_client.php');

$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);

$ma_cert_file = '/usr/share/geni-ch/ma/ma-cert.pem';
$ma_key_file = '/usr/share/geni-ch/ma/ma-key.pem';
$ma_signer = new Signer($ma_cert_file, $ma_key_file);


/**
 * GENI Clearinghouse Member Authority (MA) controller interface
 *
 * The MA maintains a set of members and their UUIDs and their attributes and associated query mechanisms.
 * The MA maintains a set of SSL keys and certs, both 'inside' (created) and 'outside' (uploaded) for given users.
 * Additionally, the MA maintains a mapping of members to the client tools (e.g. the GENI Portal) that the member has authorized to speak on his/her behalf.
 * Finally, the MA maintains a set of SSH keys for a given member for passing to resources as needed.
 * 
 * Supports these methods:
 *   register_ssh_key(member_id, filename, description, ssh_key);
 *   lookup_ssh_keys(member_id);
 *   update_ssh_key(member_id, ssh_key_id, ssh_filename, ssh_description)
 *   delete_ssh_key(member_id, ssh_key_id)
 *   lookup_keys_and_certs(member_id);
 *   create_account(attributes)
 *          email_address, first_name, last_name, telephone_number
 *   ma_list_clients()
 *          (name => URN) dictionary
 *   ma_list_authorized_clients(member_id)
 *          (name => URN) dictionary
 *   ma_authorize_client(member_id, client_urn, authorize_sense)
 *   lookup_members(attributes) 
 *   lookup_member_by_id(member_id)
 *   add_member_privilege(member_id, privilege_id)
 *   revoke_member_privilege(member_id, privilege_id)
 */


// *** Temporary part of moving member management into MA domain
function get_member_ids($args, $message)
{
  global $MA_MEMBER_TABLENAME;
  $log_msg = "get_member_ids()";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $sql = "select " . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID;
  $sql .= " from $MA_MEMBER_TABLENAME";
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $result;
  }
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  $ids = array();
  foreach($rows as $row) {
    $id = $row[MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    $ids[] = $id;
  }
  return generate_response(RESPONSE_ERROR::NONE, $ids, null);
}

function register_ssh_key($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  global $ma_signer;
  global $log_url;
  $conn = db_conn();
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $signer_id = $message->signerUuid(); // FIXME: get name. For now, use URN
  $client_urn = $message->signerUrn();
  $ssh_filename = $args[MA_ARGUMENT::SSH_FILENAME];
  $ssh_description = $args[MA_ARGUMENT::SSH_DESCRIPTION];
  $ssh_public_key = $args[MA_ARGUMENT::SSH_PUBLIC_KEY];
  if (parse_urn($client_urn, $auth, $type, $name)) {
    $client_urn = $auth . "." . $name;
  }
  $log_msg = "$client_urn Registering SSH key \"$ssh_description\" for $member_id";
  $private_key_field = '';
  $private_key_value = '';
  if (array_key_exists(MA_ARGUMENT::SSH_PRIVATE_KEY, $args)) {
    $ssh_private_key = $args[MA_ARGUMENT::SSH_PRIVATE_KEY];
    $private_key_field = ", " . MA_SSH_KEY_TABLE_FIELDNAME::PRIVATE_KEY;
    $private_key_value = ", " . $conn->quote($ssh_private_key, 'text');
    $log_msg .= " with private key";
  }
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
  $log_msg = "$client_urn Registering SSH key \"$ssh_description\"";
  log_event($log_url, $ma_signer, $log_msg, $attributes, $signer_id);
  $sql = ("insert into " . $MA_SSH_KEY_TABLENAME
          . " ( " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . ", " . MA_SSH_KEY_TABLE_FIELDNAME::FILENAME
          . ", " . MA_SSH_KEY_TABLE_FIELDNAME::DESCRIPTION
          . ", " . MA_SSH_KEY_TABLE_FIELDNAME::PUBLIC_KEY
          . $private_key_field
          . ")  VALUES ("
          . $conn->quote($member_id, 'text')
          . ", " . $conn->quote($ssh_filename, 'text')
          . ", " . $conn->quote($ssh_description, 'text')
          . ", " . $conn->quote($ssh_public_key, 'text')
          . $private_key_value
          . ")");
  $result = db_execute_statement($sql);
  return $result;
}

function lookup_ssh_keys($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  //  error_log("LOOKUP_SSH_KEYS " . print_r($args, true));
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $log_msg = "Looking up SSH keys for $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $conn = db_conn();
  $sql = ("select * from " . $MA_SSH_KEY_TABLENAME
          . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text'));
  $rows = db_fetch_rows($sql);
  //  error_log("LOOKUP_SSH_KEYS " . print_r($rows, true));
  return $rows;
}

function update_ssh_key($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $signer_id = $message->signerUuid();
  $client_urn = $message->signerUrn();
  $ssh_key_id = $args[MA_ARGUMENT::SSH_KEY_ID];
  $filename = NULL;
  $description = NULL;
  if (array_key_exists(MA_ARGUMENT::SSH_FILENAME, $args)) {
    $filename = $args[MA_ARGUMENT::SSH_FILENAME];
  }
  if (array_key_exists(MA_ARGUMENT::SSH_DESCRIPTION, $args)) {
    $description = $args[MA_ARGUMENT::SSH_DESCRIPTION];
  }
  if (! ($filename || $description)) {
    return generate_response(RESPONSE_ERROR::NONE, false, "");
  }
  if (parse_urn($client_urn, $auth, $type, $name)) {
    $client_urn = $auth . "." . $name;
  }
  $log_msg = "$client_urn Updating SSH key $ssh_key_id for $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $conn = db_conn();
  $sql = "update " . $MA_SSH_KEY_TABLENAME . " SET ";
  if ($filename) {
    $sql = ($sql . MA_SSH_KEY_TABLE_FIELDNAME::FILENAME
            . " = " . $conn->quote($filename, 'text'));
  }
  if ($filename && $description) {
    $sql = $sql . ", ";
  }
  if ($description) {
    $sql = ($sql . MA_SSH_KEY_TABLE_FIELDNAME::DESCRIPTION
            . " = " . $conn->quote($description, 'text'));
  }
  $sql = ($sql . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text')
          . " AND " . MA_SSH_KEY_TABLE_FIELDNAME::ID
          . " = " . $conn->quote($ssh_key_id, 'integer'));
  $rows = db_execute_statement($sql);
  if ($rows[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $rows;
  }

  // Return the updated ssh key
  $sql = ("select * from " . $MA_SSH_KEY_TABLENAME
          . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text')
          . " AND " . MA_SSH_KEY_TABLE_FIELDNAME::ID
          . " = " . $conn->quote($ssh_key_id, 'integer'));
  $rows = db_fetch_rows($sql);
  //  error_log("LOOKUP_SSH_KEYS " . print_r($rows, true));
  return $rows;
}

function delete_ssh_key($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  global $ma_signer;
  global $log_url;
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $signer_id = $message->signerUuid();
  $client_urn = $message->signerUrn();
  $ssh_key_id = $args[MA_ARGUMENT::SSH_KEY_ID];
  if (parse_urn($client_urn, $auth, $type, $name)) {
    $client_urn = $auth . "." . $name;
  }
  $log_msg = "$client_urn Deleting SSH key $ssh_key_id for $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
  $log_msg = "$client_urn Deleting SSH key $ssh_key_id";
  log_event($log_url, $ma_signer, $log_msg, $attributes, $signer_id);
  $conn = db_conn();
  $sql = ("delete from " . $MA_SSH_KEY_TABLENAME
          . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text')
          . " AND " . MA_SSH_KEY_TABLE_FIELDNAME::ID
          . " = " . $conn->quote($ssh_key_id, 'integer'));
  $rows = db_execute_statement($sql);
  if ($rows[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $rows;
  } else {
    return generate_response(RESPONSE_ERROR::NONE, true, "");
  }
}


function lookup_keys_and_certs($args, $message)
{
  global $MA_INSIDE_KEY_TABLENAME;
  $client_urn = $message->signerUrn();
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $log_msg = "$client_urn looking up inside cert/key for $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $conn = db_conn();
  $sql = "select " 
    . MA_INSIDE_KEY_TABLE_FIELDNAME::PRIVATE_KEY . ", "
    . MA_INSIDE_KEY_TABLE_FIELDNAME::CERTIFICATE 
    . " FROM " . $MA_INSIDE_KEY_TABLENAME
    . " WHERE " 
    . MA_INSIDE_KEY_TABLE_FIELDNAME::MEMBER_ID
    . " = " . $conn->quote($member_id, 'text')
    . " and " . MA_INSIDE_KEY_TABLE_FIELDNAME::CLIENT_URN
    . " = " . $conn->quote($client_urn, 'text');
  //  error_log("LKAC.sql = " . $sql);
  $row = db_fetch_row($sql);
  return $row;
}


/**
 *
 */
function create_account($args, $message)
{
  global $cs_url;
  global $log_url;
  global $ma_signer;
  // Is this a valid signer?

  // Are all the required keys present?
  if (! array_key_exists(MA_ARGUMENT::ATTRIBUTES, $args)) {
    $msg = ("Required parameter " . MA_ARGUMENT::ATTRIBUTES
            . " does not exist.");
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }
  $required_keys = array(MA_ATTRIBUTE_NAME::EMAIL_ADDRESS);
  if (! verify_keys($args[MA_ARGUMENT::ATTRIBUTES], $required_keys, $missing)) {
    // Error: some required keys are missing.
    $msg = "Some required attributes are missing:";
    foreach ($missing as $req) {
      $msg .= " " . $req;
    }
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }

  $email_address = "<no email address found>";
  foreach ($args[MA_ARGUMENT::ATTRIBUTES] as $attr) {
    //    error_log("Create_account has attr " . $attr[MA_ATTRIBUTE::NAME] . " = " . $attr[MA_ATTRIBUTE::VALUE]);
    if ($attr[MA_ATTRIBUTE::NAME] === MA_ATTRIBUTE_NAME::EMAIL_ADDRESS) {
      $email_address = $attr[MA_ATTRIBUTE::VALUE];
      break;
    }
  }
  $attributes = $args[MA_ARGUMENT::ATTRIBUTES];
  $username = derive_username($email_address);
  //  error_log("derived username $username");
  $username_attr = array(MA_ATTRIBUTE::NAME => MA_ATTRIBUTE_NAME::USERNAME,
          MA_ATTRIBUTE::VALUE => $username,
          MA_ATTRIBUTE::SELF_ASSERTED => FALSE);
  $attributes[] = $username_attr;
  $attributes[] = make_member_urn_attribute($ma_signer, $username);
  global $MA_MEMBER_TABLENAME;
  global $MA_MEMBER_ATTRIBUTE_TABLENAME;
  $conn = db_conn();
  $member_id = make_uuid();
  $sql = "insert into " . $MA_MEMBER_TABLENAME
    . " ( " . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID
    . ")  VALUES ("
    . $conn->quote($member_id, 'text')
    . ")";
  $result = db_execute_statement($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    // An error occurred. Return the error result.
    return $result;
  }
  $log_msg = "Creating new user $member_id for email $email_address";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $log_msg = "Activated GENI account for $email_address";
  $logattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
  log_event($log_url, $ma_signer, $log_msg, $logattributes, $message->signerUuid());
  foreach ($attributes as $attr) {
    $attr_value = $attr[MA_ATTRIBUTE::VALUE];
    // Note: Use empty() instead of is_null() because it catches
    // values that the DB Conn will convert to NULL.
    if (empty($attr_value)) {
      continue;
    }
    $attr_name = $attr[MA_ATTRIBUTE::NAME];
    $attr_self_asserted = $attr[MA_ATTRIBUTE::SELF_ASSERTED];
    $sql = "insert into " . $MA_MEMBER_ATTRIBUTE_TABLENAME
    . " ( " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID
    . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME
    . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
    . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED
    . ")  VALUES ("
    . $conn->quote($member_id, 'text')
    . ", " . $conn->quote($attr_name, 'text')
    . ", " . $conn->quote($attr_value, 'text')
    . ", " . $conn->quote($attr_self_asserted, 'boolean')
    . ")";
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      // An error occurred. Return the error result.
      return $result;
    }
    $log_msg = "new user $member_id has";
    $log_msg .= ($attr_self_asserted ? " self-asserted" : "");
    $log_msg .= " attribute $attr_name = \"$attr_value\"";
    geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  }
  mail_account_request($member_id);
  $result = generate_response(RESPONSE_ERROR::NONE, $member_id, "");
  return $result;
}

/**
 * Return list of all clients registered with MA as (name => URN) dictionary
 */
function ma_list_clients($args, $message)
{
  global $MA_CLIENT_TABLENAME;
  $sql = "select " . MA_CLIENT_TABLE_FIELDNAME::CLIENT_NAME . ", " . 
    MA_CLIENT_TABLE_FIELDNAME::CLIENT_URN . 
    " from " . $MA_CLIENT_TABLENAME;
  $log_msg = "listing all clients";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $rows = db_fetch_rows($sql);
  $result = $rows;
  //  error_log("ROWS = " . print_r($rows, true));
  if ($rows[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $result = array();
    $rows = $rows[RESPONSE_ARGUMENT::VALUE];
    foreach($rows as $row) {
      $client_name = $row[MA_CLIENT_TABLE_FIELDNAME::CLIENT_NAME];
      $client_urn = $row[MA_CLIENT_TABLE_FIELDNAME::CLIENT_URN];
      $result[$client_name] = $client_urn;
    }
  }

  //  error_log("MLC.RESULT= " . print_r($result, true));

  return generate_response(RESPONSE_ERROR::NONE, $result, '');
       
}

/**
 * Return array of URNs of all tools 
 * for which a given user (by ID) has authorized use
 * and has generated inside keys
 */
function ma_list_authorized_clients($args, $message)
{

  global $MA_INSIDE_KEY_TABLENAME;

  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $conn = db_conn();

  //  error_log("MLAC.ARGS = " . print_r($args, true));

  global $MA_INSIDE_KEY_TABlENAME;
  $sql = "select " . MA_INSIDE_KEY_TABLE_FIELDNAME::CLIENT_URN .
    " from " . $MA_INSIDE_KEY_TABLENAME . 
    " where " . MA_INSIDE_KEY_TABLE_FIELDNAME::MEMBER_ID . " = " . $conn->quote($member_id, 'text');
  $log_msg = "listing authorized clients for $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $rows = db_fetch_rows($sql);
  $result = $rows;
  //  error_log("ROWS = " . print_r($rows, true));
  if ($rows[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $result = array();
    $rows = $rows[RESPONSE_ARGUMENT::VALUE];
    foreach($rows as $row) {
      $client_urn = $row[MA_INSIDE_KEY_TABLE_FIELDNAME::CLIENT_URN];
      $result[] = $client_urn;
    }
  }

  //  error_log("MLAC.RESULT= " . print_r($result, true));

  return generate_response(RESPONSE_ERROR::NONE, $result, '');
}

/**
 * Authorize (or deauthorize) a given tool for a given user
 * if 'authorize_sense' is true, authorize (add line to ma_inside_key table)
 * Otherwise, deauthorize (remove line from ma_inside_key table).
 */
function ma_authorize_client($args, $message)
{
  global $ma_cert_file;
  global $ma_key_file;
  global $MA_INSIDE_KEY_TABLENAME;
  global $ma_signer;
  global $log_url;

  //  error_log("MAC.ARGS = " . print_r($args, true));

  $signer_id = $message->signerUuid();
  $signer_urn = $message->signerUrn();
  // FIXME: I'd like the prettyName, but that code is in ma_client. For now, use URN
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $client_urn = $args[MA_ARGUMENT::CLIENT_URN];
  $authorize_sense = $args[MA_ARGUMENT::AUTHORIZE_SENSE];
  $authorize_sense = ($authorize_sense != "false");

  $conn = db_conn();
  if ($authorize_sense) {
    // Add member to ma_inside_key table
    make_inside_cert_key($member_id, $client_urn, $ma_cert_file,
            $ma_key_file, $cert, $key);
    $sql = ("insert into " . $MA_INSIDE_KEY_TABLENAME
            . " (" . MA_INSIDE_KEY_TABLE_FIELDNAME::MEMBER_ID
            . ", " . MA_INSIDE_KEY_TABLE_FIELDNAME::CLIENT_URN
            . ", " . MA_INSIDE_KEY_TABLE_FIELDNAME::CERTIFICATE
            . ", " . MA_INSIDE_KEY_TABLE_FIELDNAME::PRIVATE_KEY
            . ") values ("
            . $conn->quote($member_id, 'text')
            . ", " . $conn->quote($client_urn, 'text')
            . ", " . $conn->quote($cert, 'text')
            . ", " . $conn->quote($key, 'text')
            . ")");
    if (parse_urn($signer_urn, $auth, $type, $name)) {
      $signer_urn = $auth . "." . $name;
    }
    if (parse_urn($client_urn, $auth, $type, $name)) {
      $client_urn = $auth . "." . $name;
    }
    $log_msg = "$signer_urn authorizing client $client_urn for $member_id";
    geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
    // FIXME: Email portal_dev_admin?
    $log_msg = "$signer_urn authorizing client $client_urn";
    $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
    log_event($log_url, $ma_signer, $log_msg, $attributes, $signer_id);
    $result = db_execute_statement($sql);
  } else {
    // Remove member from ma_inside_key table
    $sql = "delete from " . $MA_INSIDE_KEY_TABLENAME . 
      " where " . MA_INSIDE_KEY_TABLE_FIELDNAME::MEMBER_ID . "= " . $conn->quote($member_id, 'text') .
      " and " . MA_INSIDE_KEY_TABLE_FIELDNAME::CLIENT_URN . " = " . $conn->quote($client_urn, 'text');
    if (parse_urn($signer_urn, $auth, $type, $name)) {
      $signer_urn = $auth . "." . $name;
    }
    if (parse_urn($client_urn, $auth, $type, $name)) {
      $client_urn = $auth . "." . $name;
    }
    $log_msg = "$signer_urn deauthorizing client $client_urn for $member_id";
    geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
    // FIXME: Email portal_dev_admin?
    $log_msg = "$signer_urn deauthorizing client $client_urn";
    $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
    log_event($log_url, $ma_signer, $log_msg, $attributes, $signer_id);
    error_log($log_msg);
    $result = db_execute_statement($sql);
  }

  return $result;
}


function lookup_members($args, $message)
{
  global $MA_MEMBER_ATTRIBUTE_TABLENAME;
  // Is this a valid signer?

  // Are all the required keys present?
  if (! array_key_exists(MA_ARGUMENT::ATTRIBUTES, $args)) {
    $msg = ("Required parameter " . MA_ARGUMENT::ATTRIBUTES
            . " does not exist.");
    error_log($msg);
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }

  $lookup_attrs = $args[MA_ARGUMENT::ATTRIBUTES];
  $member_ids = NULL;
  $conn = db_conn();
  // TODO: Use a prepared statement
  foreach ($lookup_attrs as $attr) {
    // FIXME: validate the attr name against client attributes
    $attr_name = $attr[MA_ATTRIBUTE::NAME];
    $attr_value = $attr[MA_ATTRIBUTE::VALUE];
    $log_msg = "lookup_members with $attr_name = \"$attr_value\"";
    error_log($log_msg);
    geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
    $sql = "select " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID
    . " from " . $MA_MEMBER_ATTRIBUTE_TABLENAME
    . " where " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME
    . " = " . $conn->quote($attr_name, 'text')
    . " and " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
    . " = " . $conn->quote($attr_value, 'text');
    $rows = db_fetch_rows($sql);
    if ($rows[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      error_log("Error querying for members: " . $rows[RESPONSE_ARGUMENT::OUTPUT]);
    }
    // Convert $rows to an array of member_ids
    $ids = array();
    foreach ($rows[RESPONSE_ARGUMENT::VALUE] as $row) {
      $ids[] = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID];
      //      error_log("Added to ids");
    }
    // intersect it with the existing member_ids
    if (is_null($member_ids)) {
      $member_ids = $ids;
    } else {
      $member_ids = array_intersect($member_ids, $ids);
    }
  }
  $msg = "lookup_members found member ids: " . implode(", ", $member_ids);
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $msg);
  error_log($msg);
  $result = array();
  foreach ($member_ids as $member_id) {
    $result[] = get_member_info($member_id);
  }
  return generate_response(RESPONSE_ERROR::NONE, $result, "");
}

function lookup_member_by_id($args, $message)
{
  global $MA_MEMBER_TABLENAME;
  // Is this a valid signer?

  // Are all the required keys present?
  if (! array_key_exists(MA_ARGUMENT::MEMBER_ID, $args)) {
    $msg = ("Required parameter " . MA_ARGUMENT::MEMBER_ID
            . " does not exist.");
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }
  $conn = db_conn();
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $log_msg = "looking up member by id $member_id";
  error_log($log_msg);
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);

  // Make sure this is a valid member ID
  $sql = ("select " . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID
          . " from " . $MA_MEMBER_TABLENAME
          . " where " . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text'));
  $response = db_fetch_row($sql);
  if ($response[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    //    error_log("got result error");
    return $response;
  } else {
    //    error_log("returning member data");
    return generate_response(RESPONSE_ERROR::NONE,
            get_member_info($member_id), "");
  }
}

function add_member_privilege($args, $message)
{
  global $cs_url;
  global $log_url;
  global $ma_signer;
  global $MA_MEMBER_PRIVILEGE_TABLENAME;

  $signer_id = $message->signerUuid();
  $signer_urn = $message->signerUrn();
  // FIXME: I'd like the prettyName, but that code is in ma_client. For now, URN

  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $privilege_id = $args[MA_ARGUMENT::PRIVILEGE_ID];
  $priv_name = "<other>";
  if ($privilege_id === MA_PRIVILEGE::PROJECT_LEAD) {
    $priv_name = "Project Lead";
  }
  if (parse_urn($signer_urn, $auth, $type, $name)) {
    $signer_urn = $auth . "." . $name;
  }
  $log_msg = "$signer_urn adding privilege \"$priv_name\" to member $member_id";
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
  $log_msg = "$signer_urn adding privilege \"$priv_name\"";
  log_event($log_url, $ma_signer, $log_msg, $attributes, $signer_id);
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $conn = db_conn();
  $sql = ("insert into " . $MA_MEMBER_PRIVILEGE_TABLENAME
          . " ( " . MA_MEMBER_PRIVILEGE_TABLE_FIELDNAME::MEMBER_ID
          . ", " . MA_MEMBER_PRIVILEGE_TABLE_FIELDNAME::PRIVILEGE_ID
          . ")  VALUES ("
          . $conn->quote($member_id, 'text')
          . ", " . $conn->quote($privilege_id, 'integer')
          . ")");
  $result = db_execute_statement($sql);

  // FIXME: At some point there will be other privileges here and we'll want to send
  // mail in more cases. Also, we are not including who took the action, which is key.

  if ($privilege_id === MA_PRIVILEGE::PROJECT_LEAD) {
    assert_project_lead($cs_url, $ma_signer, $member_id);
    mail_new_project_lead($member_id);
  }
  return $result;
}

function revoke_member_privilege($args, $message)
{
  global $MA_MEMBER_PRIVILEGE_TABLENAME;
  global $ma_signer;
  global $log_url;

  $signer_id = $message->signerUuid();
  $signer_urn = $message->signerUrn();
  // FIXME: I'd like the prettyName, but that code is in ma_client. For now, URN
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $privilege_id = $args[MA_ARGUMENT::PRIVILEGE_ID];
  $priv_name = "<other>";
  if ($privilege_id === MA_PRIVILEGE::PROJECT_LEAD) {
    $priv_name = "Project Lead";
  }

  if (parse_urn($signer_urn, $auth, $type, $name)) {
    $signer_urn = $auth . "." . $name;
  }
  $log_msg = "$signer_urn revoking privilege \"$priv_name\" from member $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
  $log_msg = "$signer_urn revoking privilege \"$priv_name\"";
  log_event($log_url, $ma_signer, $log_msg, $attributes, $signer_id);
  // FIXME: Email portal_dev_admin?
  $conn = db_conn();
  $sql = ("delete from " . $MA_MEMBER_PRIVILEGE_TABLENAME
          . " where "
          . MA_MEMBER_PRIVILEGE_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text')
          . " and "
          . MA_MEMBER_PRIVILEGE_TABLE_FIELDNAME::PRIVILEGE_ID
          . " = " . $conn->quote($privilege_id, 'integer'));
  $result = db_execute_statement($sql);
  return $result;
}

/**
 * This is more of a demonstration guard than anything else.
 * It really isn't an appropriate test, but gets the point
 * across that a user can't call certain methods, but an
 * authority could.
 */
class SignerAuthorityGuard implements Guard
{
  public function __construct($message) {
    $this->message = $message;
  }

  public function evaluate() {
    return (strpos($this->message->signerUrn(), '+authority+') !== FALSE);
  }
}


class MAGuardFactory implements GuardFactory
{
  public function __construct() {
  }

  // FIXME: Key functions are unguarded. 

  public function createGuards($message) {
    $parsed_message = $message->parse();
    $action = $parsed_message[0];
    $params = $parsed_message[1];
    $result = array();
    $result[] = new SignedMessageGuard($message);
    // As more guards accumulate, make this table-driven.
    if ($action === 'lookup_ssh_keys') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'register_ssh_key') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'delete_ssh_key') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'update_ssh_key') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'get_member_ids') {
      $result[] = new SignerAuthorityGuard($message);
    } elseif ($action === 'lookup_keys_and_certs') {
      $result[] = new SignerAuthorityGuard($message);
    }
    return $result;
  }
}


$guard_factory = new MAGuardFactory();
handle_message("MA", $cs_url, default_cacerts(),
        $ma_signer->certificate(), $ma_signer->privateKey(), $guard_factory);
?>

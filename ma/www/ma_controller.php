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
 *   lookup_public_ssh_keys(member_id);
 *   lookup_private_ssh_keys(member_id);
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
 *   lookup_member_details(member_uuids)
 *   lookup_member_by_email(member_emails)
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



/* Add attribute name/value pair to member
   Requires member_id, name, value, self asserted
*/
function add_member_attribute($args)
{

  global $MA_MEMBER_ATTRIBUTE_TABLENAME;

  if (! array_key_exists(MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID, $args) or
      $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID] == '') {
    error_log("Missing member_id arg to add_member_attributes");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Member ID is missing");
  }
  
  if (! array_key_exists(MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME, $args) or
      $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME] == '') {
    error_log("Missing name arg to add_member_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Name is missing");
  }
  if (! array_key_exists(MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE, $args) or
      $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE] == '') {
    error_log("Missing value arg to add_member_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Value is missing");
  }
  if (! array_key_exists(MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED, $args) or
      $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED] == '') {
    error_log("Missing value self_asserted to add_member_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Self asserted is missing");
  }
  
  $conn = db_conn();
  
  // define variables
  $member_id = $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID];
  $name = $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME];
  $value = $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE];
  $self_asserted = $args[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED];
  
  // insert
  $sql = ("insert into " . $MA_MEMBER_ATTRIBUTE_TABLENAME
          . " ( " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID
          . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME
          . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
          . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED
          . ")  VALUES ("
          . $conn->quote($member_id, 'text')
          . ", " . $conn->quote($name, 'text')
          . ", " . $conn->quote($value, 'text')
          . ", " . $conn->quote($self_asserted, 'text')
          . ")");
  $result = db_execute_statement($sql);
  return $result;

}





function register_ssh_key($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  global $ma_signer;
  global $log_url;
  $conn = db_conn();
  $member_id = null;
  if (array_key_exists(MA_ARGUMENT::MEMBER_ID, $args)) {
    $member_id = $args[MA_ARGUMENT::MEMBER_ID];
    if (! uuid_is_valid($member_id)) {
      error_log("member_id invalid in register_ssh_key: " . $member_id);
      return generate_response(RESPONSE_ERROR::ARGS, null, "Member ID invalid");
    }
  }
  if (! isset($member_id) or is_null($member_id) or $member_id == '') {
    error_log("Missing member ID to register_ssh_key");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Member ID missing");
  }
  $signer_id = $message->signerUuid(); // FIXME: get name. For now, use URN
  $client_urn = $message->signerUrn();

  $ssh_filename = '';
  if (array_key_exists(MA_ARGUMENT::SSH_FILENAME, $args)) {
    $ssh_filename = $args[MA_ARGUMENT::SSH_FILENAME];
  }

  $ssh_description = '';
  if (array_key_exists(MA_ARGUMENT::SSH_DESCRIPTION, $args)) {
    $ssh_description = $args[MA_ARGUMENT::SSH_DESCRIPTION];
  }
  $ssh_public_key = null;
  if (array_key_exists(MA_ARGUMENT::SSH_PUBLIC_KEY, $args)) {
    $ssh_public_key = $args[MA_ARGUMENT::SSH_PUBLIC_KEY];
  }
  if (! isset($ssh_public_key) or is_null($ssh_public_key) or $ssh_public_key == '') {
    error_log("Missing SSH public key to register_ssh_key");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "SSH Public key missing");
  }
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

// Get all SSH key information EXCEPT private key
function lookup_public_ssh_keys($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  //  error_log("LOOKUP_PUBLIC_SSH_KEYS " . print_r($args, true));
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $log_msg = "Looking up SSH keys for $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $conn = db_conn();
  $fields = MA_SSH_KEY_TABLE_FIELDNAME::ID . ", " . 
    MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID . ", " .
    MA_SSH_KEY_TABLE_FIELDNAME::FILENAME . ", " .
    MA_SSH_KEY_TABLE_FIELDNAME::DESCRIPTION . ", " .
    MA_SSH_KEY_TABLE_FIELDNAME::PUBLIC_KEY;
  $sql = ("select " . $fields . " from " . $MA_SSH_KEY_TABLENAME
          . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text'));
  $rows = db_fetch_rows($sql);
  //  error_log("LOOKUP_PUBLIC_SSH_KEYS " . print_r($rows, true));
  return $rows;
}

// Get all SSH key information INCLUDING private key
function lookup_private_ssh_keys($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  //  error_log("LOOKUP_PRIVATE_SSH_KEYS " . print_r($args, true));
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $log_msg = "Looking up SSH keys for $member_id";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  $conn = db_conn();
  $sql = ("select * from " . $MA_SSH_KEY_TABLENAME
          . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text'));
  $rows = db_fetch_rows($sql);
  //  error_log("LOOKUP_PRIVATE_SSH_KEYS " . print_r($rows, true));
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
  //  error_log("UPDATE_SSH_KEYS " . print_r($rows, true));
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
    //    $log_msg = "lookup_members with $attr_name = \"$attr_value\"";
    //    error_log($log_msg);
    //    geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
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
  //  $msg = "lookup_members found member ids: " . implode(", ", $member_ids);
  //  geni_syslog(GENI_SYSLOG_PREFIX::MA, $msg);
  //  error_log($msg);
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
  //  $log_msg = "looking up member by id $member_id";
  //  error_log($log_msg);
  //  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);

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
  } else if ($privilege_id === MA_PRIVILEGE::OPERATOR) {
    $priv_name = "Operator";
  }
  if (parse_urn($signer_urn, $auth, $type, $name)) {
    $signer_urn = $auth . "." . $name;
  }
  $log_signer = get_member_id_log_name($signer_id);
  if (! isset($log_signer) or is_null($log_signer) or $log_signer == '') {
    $log_signer = $signer_urn;
  }
  $log_member = get_member_id_log_name($member_id);
  if (! isset($log_member) or is_null($log_member) or $log_member == '') {
    $log_member = $member_id;
  }
  $log_msg = "$log_signer adding privilege \"$priv_name\" to member $log_member";
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
  log_event($log_url, $ma_signer, $log_msg, $attributes, $signer_id);
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  error_log($log_msg);
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
  } else if ($privilege_id === MA_PRIVILEGE::OPERATOR) {
    assert_operator($cs_url, $ma_signer, $member_id);
  }
  return $result;
}

function revoke_member_privilege($args, $message)
{
  global $MA_MEMBER_PRIVILEGE_TABLENAME;
  global $ma_signer;
  global $log_url;
  global $cs_url;

  $signer_id = $message->signerUuid();
  $signer_urn = $message->signerUrn();
  // FIXME: I'd like the prettyName, but that code is in ma_client. For now, URN
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $privilege_id = $args[MA_ARGUMENT::PRIVILEGE_ID];
  $priv_name = "<other>";
  if ($privilege_id === MA_PRIVILEGE::PROJECT_LEAD) {
    $priv_name = "Project Lead";
  } else if ($privilege_id === MA_PRIVILEGE::OPERATOR) {
    $priv_name = "Operator";
  }

  if (parse_urn($signer_urn, $auth, $type, $name)) {
    $signer_urn = $auth . "." . $name;
  }
  $log_signer = get_member_id_log_name($signer_id);
  if (! isset($log_signer) or is_null($log_signer) or $log_signer == '') {
    $log_signer = $signer_urn;
  }
  $log_member = get_member_id_log_name($member_id);
  if (! isset($log_member) or is_null($log_member) or $log_member == '') {
    $log_member = $member_id;
  }
  $log_msg = "$log_signer revoking privilege \"$priv_name\" from member $log_member";
  geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
  error_log($log_msg);
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
  $log_msg = "$log_signer revoking privilege \"$priv_name\"";
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

  // Need to reverse the assert_project_lead / assert_operator calls
  if ($privilege_id === MA_PRIVILEGE::PROJECT_LEAD) {
    $attribute = CS_ATTRIBUTE_TYPE::LEAD;
    $context_type = CS_CONTEXT_TYPE::RESOURCE;
    $context = NULL;
    $assert_id = NULL;
    $assertions = query_assertions($cs_url, $ma_signer, $member_id, $context_type, $context);
    // each is a row containing all columns of cs_assertion
    // id, signer, principal, attribute, context_Type, context, expiration, asserion_cert
    // We want the row, if any, where attribute is $attribute 
    if (isset($assertions) and is_array($assertions)) {
      foreach ($assertions as $row) {
	if (array_key_exists(CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE, $row) and $row[CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE] == $attribute and array_key_exists(CS_ASSERTION_TABLE_FIELDNAME::ID, $row)) {
	  $assert_id = $row[CS_ASSERTION_TABLE_FIELDNAME::ID];
	}
      }
    }
    if (! is_null($assert_id)) {
      delete_assertion($cs_url, $ma_signer, $assert_id);
    }
    //    assert_project_lead($cs_url, $ma_signer, $member_id);

    // FIXME: Send mail?
    //    mail_new_project_lead($member_id);
  } else if ($privilege_id === MA_PRIVILEGE::OPERATOR) {
    $attribute = CS_ATTRIBUTE_TYPE::OPERATOR;
    $context_types = array(CS_CONTEXT_TYPE::PROJECT,
			   CS_CONTEXT_TYPE::SLICE,
			   CS_CONTEXT_TYPE::RESOURCE,
			   CS_CONTEXT_TYPE::SERVICE,
			   CS_CONTEXT_TYPE::MEMBER);
    $context = NULL;
    $assert_id = NULL;
    foreach ($context_types as $context_type) {
      $assertions = query_assertions($cs_url, $ma_signer, $member_id, $context_type, $context);
      // each is a row containing all columns of cs_assertion
      // id, signer, principal, attribute, context_Type, context, expiration, asserion_cert
      // We want the row, if any, where attribute is $attribute 
      if (isset($assertions) and is_array($assertions)) {
	foreach ($assertions as $row) {
	  if (array_key_exists(CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE, $row) and $row[CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE] == $attribute and array_key_exists(CS_ASSERTION_TABLE_FIELDNAME::ID, $row)) {
	    $assert_id = $row[CS_ASSERTION_TABLE_FIELDNAME::ID];
	  }
	}
      }
      if (! is_null($assert_id)) {
	delete_assertion($cs_url, $ma_signer, $assert_id);
      }
    }
    //    assert_operator($cs_url, $ma_signer, $member_id);
  }

  return $result;
}

function lookup_member_details($args)
{
  //  error_log("LMD");
  $member_uuids = $args[MA_ARGUMENT::MEMBER_UUIDS];
  //  error_log("LMD.member_uuids = " . print_r($member_uuids, true));
  $info = get_member_info($member_uuids, True); 
  return generate_response(RESPONSE_ERROR::NONE, $info, "");
}

// For each ID in list
// Return the displayName if available
// Otherwise, return first_name + "  + last_name
// Otherwise return "UNKNOWN" 
// Return "NONE" also for members that don't exist
function lookup_member_names($args)
{
  global $MA_MEMBER_ATTRIBUTE_TABLENAME;
  //  error_log("LMN");
  $member_uuids = $args[MA_ARGUMENT::MEMBER_UUIDS];
  //  error_log("LMN.member_uuids = " . print_r($member_uuids, true));

  $info = array();
  foreach($member_uuids as $member_id) {
    $info[$member_id] = array();
  }

  $sql = "select "
    . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID . ", " 
    . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME . ", " 
    . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE 
    . " FROM " . $MA_MEMBER_ATTRIBUTE_TABLENAME
    . " WHERE " 
    . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID 
    . " IN " . convert_list($member_uuids)
    . " AND "
    . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME . " IN " 
    . "('" . MA_ATTRIBUTE_NAME::DISPLAY_NAME . "', '"
    . MA_ATTRIBUTE_NAME::FIRST_NAME . "', '"
    . MA_ATTRIBUTE_NAME::LAST_NAME . "', '"
    . MA_ATTRIBUTE_NAME::EMAIL_ADDRESS . "')";
  //  error_LOG("SQL = " . print_r($sql, true));
  $result = db_fetch_rows($sql);

  foreach($result['value'] as $row) {
    $member_id = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID];
    $attr_name = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME];
    $attr_value = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE];
    $info[$member_id][$attr_name] = $attr_value;
  }

  $names = array();
  foreach($info as $member_id => $attribs) {
    $name = "NONE";
    if (array_key_exists(MA_ATTRIBUTE_NAME::DISPLAY_NAME, $attribs)) {
      $name = $attribs[MA_ATTRIBUTE_NAME::DISPLAY_NAME];
    } else if (array_key_exists(MA_ATTRIBUTE_NAME::FIRST_NAME, $attribs) &&
	       array_key_exists(MA_ATTRIBUTE_NAME::LAST_NAME, $attribs)) {
      $name = $attribs[MA_ATTRIBUTE_NAME::FIRST_NAME] . " " . $attribs[MA_ATTRIBUTE_NAME::LAST_NAME];
    } else if (array_key_exists(MA_ATTRIBUTE_NAME::EMAIL_ADDRESS, $attribs)) {
      $name = $attribs[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
    }
    $names[$member_id] = $name;
  }

  //  error_log("NAME_INFO = " . print_r($result, true));
  //  error_log("NAME_INFO = " . print_r($info, true));
  //  error_log("NAME_INFO = " . print_r($names, true));
  return generate_response(RESPONSE_ERROR::NONE, $names, "");
}

/*
 * For each email in list, return list of member UUID's with that email
 * return dictionary [email => list_of_UUIDs]
 */
function lookup_members_by_email($args, $message)
{
  global $MA_MEMBER_ATTRIBUTE_TABLENAME;
  $email_args = $args[MA_ARGUMENT::MEMBER_EMAILS];
  $emails = "";
  foreach($email_args as $email) {
    $email = trim($email);
    if ($email == "") {
      continue;
    }
    if ($emails != "") $emails = $emails . ", ";
    $emails = $emails . "'" . strtolower($email) . "'";
  }
  $dict = array();

  if ($emails !== "") {
    $sql = "select " . MA_ATTRIBUTE::MEMBER_ID . ", " . MA_ATTRIBUTE::VALUE . 
      " from " . $MA_MEMBER_ATTRIBUTE_TABLENAME . 
      " where " . MA_ATTRIBUTE::NAME . " = '" . MA_ATTRIBUTE_NAME::EMAIL_ADDRESS . "'" . 
      " and lower(" . MA_ATTRIBUTE::VALUE . ") in (" . $emails . ")";
    $rows = db_fetch_rows($sql);
    //  error_log($rows);
    if ($rows[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $rows;

    $rows = $rows[RESPONSE_ARGUMENT::VALUE];
    //  error_log("ROWS = " . print_r($rows, true));
  } else {
    //    error_log("No emails to lookup in lookup_members_by_email");
    $rows = array();
  }
  foreach ($rows as $row) {
    $email = $row[MA_ATTRIBUTE::VALUE];
    $member_id = $row[MA_ATTRIBUTE::MEMBER_ID];
    if (!array_key_exists($email, $dict)) 
      $dict[$email] = array();
    $dict[$email][] = $member_id;
  }

  return generate_response(RESPONSE_ERROR::NONE, $dict, "");

}



/**
 * Create a certificate for use with GENI tools outside the
 * GENI portal.
 */
function ma_create_certificate($args, $message)
{
  global $ma_cert_file;
  global $ma_key_file;
  // Need member_id
  // Optional CSR
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  if (key_exists(MA_ARGUMENT::CSR, $args)) {
    $csr = $args[MA_ARGUMENT::CSR];
    $csr_file = writeDataToTempFile($csr);
    $private_key = NULL;
  } else {
    make_csr($member_id, $csr_file, $private_key_file);
    $private_key = file_get_contents($private_key_file);
  }
  // Sign the CSR
  $info = get_member_info($member_id);
  $email = get_member_attribute($info, MA_ATTRIBUTE_NAME::EMAIL_ADDRESS);
  $urn = get_member_attribute($info, MA_ATTRIBUTE_NAME::URN);
  sign_csr($csr_file, $member_id, $email, $urn, $ma_cert_file, $ma_key_file,
          $cert_chain);
  // Store the certificate and private_key in the database
  $result = ma_store_outside_cert($member_id, $cert_chain, $private_key);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $result;
  }
  // Return the cert_chain and the private key if one was generated
  $value = array(MA_ARGUMENT::CERTIFICATE => $cert_chain);
  if (! is_null($private_key)) {
    $value[MA_ARGUMENT::PRIVATE_KEY] = $private_key;
  }
  return generate_response(RESPONSE_ERROR::NONE, $value, "");
}

function ma_lookup_certificate($args, $message)
{
  if (! key_exists(MA_ARGUMENT::MEMBER_ID, $args)) {
    $msg = "Required argument " . MA_ARGUMENT::MEMBER_ID . " missing.";
    return generate_response(RESPONSE_ERROR::ARGS, null, $msg);
  }
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $result = ma_fetch_outside_cert($member_id);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    // An error occurred. The error was logged by ma_fetch_outside_cert
    // Return an error
    $msg = "A database error occurred. See the logs for details.";
    return generate_response(RESPONSE_ERROR::DATABASE, null, $msg);
  } else {
    return $result;
  }
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
    $ret = (strpos($this->message->signerUrn(), '+authority+') !== FALSE);
    if (! $ret) {
      $parsed_message = $this->message->parse();
      $action = $parsed_message[0];
      $log_msg = "AuthZ Denied: Signer is not authority: " . $this->message->signerUrn() . " cannot call " . $action;
      geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
    }
    return $ret;
  }
}

class SignerIsOperatorGuard implements Guard
{
  public function __construct($message, $cs_url, $signer) {
    $this->message = $message;
    $this->cs_url = $cs_url;
    $this->signer = $signer;
  }

  public function evaluate() {
    $assertions = query_assertions($this->cs_url, $this->signer, $this->message->signerUuid(),
            CS_CONTEXT_TYPE::MEMBER, NULL);
    foreach ($assertions as $assertion) {
      if ($assertion[CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE] == CS_ATTRIBUTE_TYPE::OPERATOR) {
        return true;
      }
    }
    $parsed_message = $this->message->parse();
    $action = $parsed_message[0];
    $log_msg = "AuthZ Denied: Signer is not operator: " . $this->message->signerUrn() . " cannot call " . $action;
    geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
    return false;
  }
}

class SignerKmGuard implements Guard
{
  public function __construct($message) {
    $this->message = $message;
  }

  public function evaluate() {
    $result = (strpos($this->message->signerUrn(), '+authority+km') !== FALSE);
    if (! $result) {
      $parsed_message = $this->message->parse();
      $action = $parsed_message[0];
      $log_msg = "AuthZ Denied: Signer is not the KM: " . $this->message->signerUrn() . " cannot call " . $action;
      geni_syslog(GENI_SYSLOG_PREFIX::MA, $log_msg);
    }
    return $result;
  }
}


class MAGuardFactory implements GuardFactory
{
  public function __construct() {
  }

  // FIXME: Key functions are unguarded. 
  // get_member_ids
  //     This gets all member_ids. Maybe just require caller has a valid member_id?
  // lookup_members  Privacy concern!
  // lookup_member_by_id    Privacy concern!
  // lookup_member_details    Privacy concern!
  // lookup_member_names      Privacy concern!

  // Protecting those lookups is harder. If the member is in the same project as you, then you can look them up. 
  // Plus operators and the person themselves. Maybe the guard can only check
  // that the user is valid?

  // Plus all the request methods?

  public function createGuards($message) {
    $parsed_message = $message->parse();
    $action = $parsed_message[0];
    $params = $parsed_message[1];
    $result = array();
    $result[] = new SignedMessageGuard($message);
    // As more guards accumulate, make this table-driven.
    if ($action === 'lookup_public_ssh_keys') {
      // Anyone can get the public SSH keys
      $result [] = new TrueGuard();
    } elseif ($action == 'lookup_private_ssh_keys') {
      // Only the user themselves (not even authorities or operators) can
      // get the private SSH keys
      $result [] =  new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'register_ssh_key') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'delete_ssh_key') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'update_ssh_key') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'lookup_keys_and_certs') {
      $result[] = new SignerAuthorityGuard($message);
    } elseif ($action === 'ma_create_certificate') {
      // Only accept from the KM
      $result[] = new SignerKmGuard($message);
    } elseif ($action === 'create_account') {
      // Only accept from the KM
      $result[] = new SignerKmGuard($message);
    } elseif ($action === 'ma_authorize_client') {
      // Only accept from the KM
      $result[] = new SignerKmGuard($message);
    } elseif ($action === 'ma_list_clients') {
      // Only accept from the KM
      $result[] = new SignerKmGuard($message);
    } elseif ($action === 'ma_list_authorized_clients') {
      // Only accept from the KM
      $result[] = new SignerKmGuard($message);
    } elseif ($action === 'ma_lookup_certificate') {
      $guards[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
      $guards[] = new SignerKmGuard($message);
      // Accept from the KM or the user
      $result[] = new OrGuard($guards);
    } elseif ($action === 'add_member_privilege') {
      global $cs_url;
      global $ma_signer;
      // Allow operator or signed by an authority
      $guards[] = new SignerIsOperatorGuard($message, $cs_url, $ma_signer);
      $guards[] = new SignerAuthorityGuard($message);
      $result[] = new OrGuard($guards);
    } elseif ($action === 'revoke_member_privilege') {
      global $cs_url;
      global $ma_signer;
      // Allow operator or signed by an authority
      $guards[] = new SignerIsOperatorGuard($message, $cs_url, $ma_signer);
      $guards[] = new SignerAuthorityGuard($message);
      $result[] = new OrGuard($guards);
    } else {
      // FIXME: Deny access at all?
      error_log("MA function unguarded: " . $action);
      geni_syslog(GENI_SYSLOG_PREFIX::MA, "MA function unguarded: " . $action);
    }
    return $result;
  }
}


$guard_factory = new MAGuardFactory();
handle_message("MA", $cs_url, default_cacerts(),
        $ma_signer->certificate(), $ma_signer->privateKey(), $guard_factory);
?>

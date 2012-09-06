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

$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);

$ma_cert_file = '/usr/share/geni-ch/ma/ma-cert.pem';
$ma_key_file = '/usr/share/geni-ch/ma/ma-key.pem';
$ma_signer = new Signer($ma_cert_file, $ma_key_file);


/**
 * GENI Clearinghouse Member Authority (MA) controller interface
 * The MA maintains a list of role relationships between members and other entities (projects, slices)
 * or general contexts 
 * That is, a person (member) can have a role with respect to a particular slice or project
 *   in which case the context_type is project or slice and the context_id is the UUID of that slice or
 *      project
 * Alternatively, the person (member) can have a role with respect to a cotnext type that has no
 *     specific context id, such as being the admin of membership records or the auditor of logs

 * 
 * Supports these methods:
 *   get_member_ids(ma_url)
 *   register_ssh_key(ma_url, member_id, filename, description, ssh_key);
 *   lookup_ssh_keys(ma_url, member_id);
 *   lookup_keys_and_certs(ma_url, member_id);
 */


// *** Temporary part of moving member management into MA domain
function get_member_ids($args, $message)
{
  $sql = "Select account_id from account";
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $result;
  }
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  $ids = array();
  foreach($rows as $row) {
    $id = $row['account_id'];
    $ids[] = $id;
  }
  return generate_response(RESPONSE_ERROR::NONE, $ids, null);
}

function register_ssh_key($args, $message)
{
  global $MA_SSH_KEY_TABLENAME;
  $conn = db_conn();
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $ssh_filename = $args[MA_ARGUMENT::SSH_FILENAME];
  $ssh_description = $args[MA_ARGUMENT::SSH_DESCRIPTION];
  $ssh_public_key = $args[MA_ARGUMENT::SSH_PUBLIC_KEY];
  if (array_key_exists(MA_ARGUMENT::SSH_PRIVATE_KEY, $args)) {
    $ssh_private_key = $args[MA_ARGUMENT::SSH_PRIVATE_KEY];
    $private_key_field = ", " . MA_SSH_KEY_TABLE_FIELDNAME::PRIVATE_KEY;
    $private_key_value = ", " . $conn->quote($ssh_private_key, 'text');
  }
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
  $conn = db_conn();
  $sql = ("select * from " . $MA_SSH_KEY_TABLENAME
          . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text'));
  $rows = db_fetch_rows($sql);
  //  error_log("LOOKUP_SSH_KEYS " . print_r($rows, true));
  return $rows;
}

function lookup_keys_and_certs($args, $message)
{
  global $MA_INSIDE_KEY_TABLENAME;
  $client_urn = $message->signerUrn();
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
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
  global $ma_signer;
  // Is this a valid signer?

  // Are all the required keys present?
  if (! array_key_exists(MA_ARGUMENT::ATTRIBUTES, $args)) {
    $msg = ("Required parameter " . MA_ARGUMENT::ATTRIBUTES
            . " does not exist.");
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }
  $required_keys = array(MA_ATTRIBUTE_NAME::EMAIL_ADDRESS,
          MA_ATTRIBUTE_NAME::FIRST_NAME,
          MA_ATTRIBUTE_NAME::LAST_NAME,
          MA_ATTRIBUTE_NAME::TELEPHONE_NUMBER);
  if (! verify_keys($args[MA_ARGUMENT::ATTRIBUTES], $required_keys, $missing)) {
    // Error: some required keys are missing.
    // FIXME: Signal an error.
    // return NULL;
    $msg = "Some required attributes are missing:";
    foreach ($missing as $req) {
      $msg .= " " . $req;
    }
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }

  $email_address = "<no email address found>";
  foreach ($args[MA_ARGUMENT::ATTRIBUTES] as $attr) {
    if ($attr[MA_ATTRIBUTE::NAME] === MA_ATTRIBUTE_NAME::EMAIL_ADDRESS) {
      $email_address = $attr[MA_ATTRIBUTE::VALUE];
      break;
    }
  }
  $attributes = $args[MA_ARGUMENT::ATTRIBUTES];
  geni_syslog(GENI_SYSLOG_PREFIX::MA,
          "Creating new account for email " . $email_address, LOG_INFO);
  $username = derive_username($email_address);
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
  foreach ($attributes as $attr) {
    $attr_value = $attr[MA_ATTRIBUTE::VALUE];
    // Note: Use empty() instead of is_null() because it catches
    // values that the DB Conn will convert to NULL.
    if (empty($attr_value)) {
      continue;
    }
    $sql = "insert into " . $MA_MEMBER_ATTRIBUTE_TABLENAME
    . " ( " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID
    . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME
    . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
    . ", " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::SELF_ASSERTED
    . ")  VALUES ("
    . $conn->quote($member_id, 'text')
    . ", " . $conn->quote($attr[MA_ATTRIBUTE::NAME], 'text')
    . ", " . $conn->quote($attr_value, 'text')
    . ", " . $conn->quote($attr[MA_ATTRIBUTE::SELF_ASSERTED], 'boolean')
    . ")";
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      // An error occurred. Return the error result.
      return $result;
    }
  }
  // FIXME: Temporarily make all members project leads.
  assert_project_lead($cs_url, $ma_signer, $member_id);
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

  //  error_log("MLAC.ARGS = " . print_r($args, true));

  global $MA_INSIDE_KEY_TABlENAME;
  $sql = "select " . MA_INSIDE_KEY_TABLE_FIELDNAME::CLIENT_URN .
    " from " . $MA_INSIDE_KEY_TABLENAME . 
    " where " . MA_INSIDE_KEY_TABLE_FIELDNAME::MEMBER_ID . " = '$member_id'";

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

  //  error_log("MAC.ARGS = " . print_r($args, true));

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
    $result = db_execute_statement($sql);
  } else {
    // Remove member from ma_inside_key table
    $sql = "delete from " . $MA_INSIDE_KEY_TABLENAME . 
      " where " . MA_INSIDE_KEY_TABLE_FIELDNAME::MEMBER_ID . "= '$member_id'" .
      " and " . MA_INSIDE_KEY_TABLE_FIELDNAME::CLIENT_URN . " = '$client_urn'";
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
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }

  $lookup_attrs = $args[MA_ARGUMENT::ATTRIBUTES];
  $member_ids = NULL;
  $conn = db_conn();
  // TODO: Use a prepared statement
  foreach ($lookup_attrs as $attr) {
    // FIXME: validate the attr name against client attributes
    $sql = "select " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID
    . " from " . $MA_MEMBER_ATTRIBUTE_TABLENAME
    . " where " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::NAME
    . " = " . $conn->quote($attr[MA_ATTRIBUTE::NAME], 'text')
    . " and " . MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::VALUE
    . " = " . $conn->quote($attr[MA_ATTRIBUTE::VALUE], 'text');
    $rows = db_fetch_rows($sql);
    // Convert $rows to an array of member_ids
    $ids = array();
    foreach ($rows[RESPONSE_ARGUMENT::VALUE] as $row) {
      $ids[] = $row[MA_MEMBER_ATTRIBUTE_TABLE_FIELDNAME::MEMBER_ID];
    }
    // intersect it with the existing member_ids
    if (is_null($member_ids)) {
      $member_ids = $ids;
    } else {
      $member_ids = array_intersect($member_ids, $ids);
    }
  }
  geni_syslog(GENI_SYSLOG_PREFIX::MA,
          "lookup_member found member ids: " . implode(", ", $member_ids));
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
  $sql = ("select " . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID
          . " from " . $MA_MEMBER_TABLENAME
          . " where " . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID
          . " = " . $conn->quote($member_id, 'text'));
  $response = db_fetch_row($sql);
  if ($response[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $response;
  } else {
    return generate_response(RESPONSE_ERROR::NONE,
            get_member_info($member_id), "");
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
    return (strpos($this->message->signerUrn(), '+authority+') !== FALSE);
  }
}


class MAGuardFactory implements GuardFactory
{
  public function __construct() {
  }

  public function createGuards($message) {
    $parsed_message = $message->parse();
    $action = $parsed_message[0];
    $params = $parsed_message[1];
    $result = array();
    // As more guards accumulate, make this table-driven.
    if ($action === 'lookup_ssh_keys') {
      $result[] = new SignerUuidParameterGuard($message, MA_ARGUMENT::MEMBER_ID);
    } elseif ($action === 'register_ssh_key') {
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

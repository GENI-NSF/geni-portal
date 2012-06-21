<?php

$prev_name = session_id('MA-SESSION');

require_once('message_handler.php');
require_once('db_utils.php');
require_once('file_utils.php');
require_once('ma_constants.php');
require_once('response_format.php');

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
 *   add_attribute(ma_url, member_id, role_type, context_type, context_id);
 *   remove_attribute(ma_url, member_id, role_type, context_type, context_id);
 *   update_role(ma_url, member_id, role_type, context_type, context_id);
 *   lookup_attributes(ma_url, member_id);
 *   get_member_ids(ma_url)
 *   register_ssh_key(ma_url, member_id, filename, description, ssh_key);
 *   lookup_ssh_keys(ma_url, member_id);
 */


/* Add a new attribute for a given member */
function add_attribute($args)
{
  global $MA_MEMBER_TABLENAME;

  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $role_type = $args[MA_ARGUMENT::ROLE_TYPE];
  $context_type = $args[MA_ARGUMENT::CONTEXT_TYPE];
  $context_id = $args[MA_ARGUMENT::CONTEXT_ID];

  if ($context_id == null) {
    $sql = "INSERT INTO "  
    . $MA_MEMBER_TABLENAME
    . "( " 
    . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . MA_MEMBER_TABLE_FIELDNAME::ROLE_TYPE . ", "
    . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_TYPE . ") "
    . " VALUES (" 
    . "'" . $member_id . "', "
      . $role_type . ", "
      . $context_type . ")";
  } else {
    $sql = "INSERT INTO "  
    . $MA_MEMBER_TABLENAME
    . "( " 
    . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . MA_MEMBER_TABLE_FIELDNAME::ROLE_TYPE . ", "
    . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_ID . ") "
    . " VALUES (" 
    . "'" . $member_id . "', "
    . $role_type . ", "
    . $context_type . ", "
    . "'" . $context_id . "')";
  }
  //  error_log("INSERT.SQL = " . $sql);

  $result = db_execute_statement($sql);

  return $result[RESPONSE_ARGUMENT::VALUE];
}

function remove_attribute($args)
{
  global $MA_MEMBER_TABLENAME;

  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  // Allow role_type to be left out, meaning all attributes are removed for the member/context
  $roleclause = "";
  if (array_key_exists(MA_ARGUMENT::ROLE_TYPE, $args) && isset($args[MA_ARGUMENT::ROLE_TYPE]) && ! is_null($args[MA_ARGUMENT::ROLE_TYPE]) && $args[MA_ARGUMENT::ROLE_TYPE] != '') {
    $roleclause = MA_MEMBER_TABLE_FIELDNAME::ROLE_TYPE . " = " . $args[MA_ARGUMENT::ROLE_TYPE] . " AND ";
  }
  $context_type = $args[MA_ARGUMENT::CONTEXT_TYPE];
  $context_id = $args[MA_ARGUMENT::CONTEXT_ID];

  if ($context_id == null) {
    $sql = "DELETE FROM " 
      . $MA_MEMBER_TABLENAME
      . " WHERE " 
      . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '" . $member_id . "' AND "
      . $roleclause
      . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $context_type;
  } else {
    $sql = "DELETE FROM " 
      . $MA_MEMBER_TABLENAME
      . " WHERE " 
      . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '" . $member_id . "' AND "
      . $roleclause
      . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $context_type . " AND "
      . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_ID . " = '" . $context_id . "'";
  }

  //  error_log("DELETE.SQL = " . $sql);

  $result = db_execute_statement($sql);
  return $result[RESPONSE_ARGUMENT::VALUE];
}

function update_role($args)
{
  global $MA_MEMBER_TABLENAME;

  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $role_type = $args[MA_ARGUMENT::ROLE_TYPE];
  $context_type = $args[MA_ARGUMENT::CONTEXT_TYPE];
  $context_id = $args[MA_ARGUMENT::CONTEXT_ID];
  
  if ($context_id == null) {
    $sql = "UPDATE " 
      . $MA_MEMBER_TABLENAME 
      . " SET " . MA_MEMBER_TABLE_FIELDNAME::ROLE_TYPE . " = " . $role_type
      . " WHERE " 
      . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '" . $member_id . "' AND "
      . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $context_type;
  } else {
    $sql = "UPDATE " 
      . $MA_MEMBER_TABLENAME 
      . " SET " . MA_MEMBER_TABLE_FIELDNAME::ROLE_TYPE . " = " . $role_type
      . " WHERE " 
      . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '" . $member_id . "' AND "
      . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_TYPE . " = " . $context_type . " AND "
      . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_ID . " = '" . $context_id . "' ";
  }

  // error_log("UPDATE.SQL = " . $sql);

  $result = db_execute_statement($sql);
  return $result[RESPONSE_ARGUMENT::VALUE];
}

function lookup_attributes($args)
{
  global $MA_MEMBER_TABLENAME;

  $member_id = $args[MA_ARGUMENT::MEMBER_ID];

  $sql = "SELECT " 
    . MA_MEMBER_TABLE_FIELDNAME::ROLE_TYPE . ", "
    . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . MA_MEMBER_TABLE_FIELDNAME::CONTEXT_ID. " "
    . " FROM " 
    . $MA_MEMBER_TABLENAME
    . " WHERE " . MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '" . $member_id . "'";

  //  error_log("QUERY.sql = " . $sql);

  $result = db_fetch_rows($sql);
  // $attribs = $result[RESPONSE_ARGUMENT::VALUE];
  //  error_log("QUERY.attribs = " . print_r($attribs, true));
  return $result;
    
}

// *** Temporary part of moving member management into MA domain
function get_member_ids($args)
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

function register_ssh_key($args)
{
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $ssh_filename = $args[MA_ARGUMENT::SSH_FILENAME];
  $ssh_description = $args[MA_ARGUMENT::SSH_DESCRIPTION];
  $ssh_key = $args[MA_ARGUMENT::SSH_KEY];

  global $MA_SSH_KEY_TABLENAME;
  $sql = "insert into " . $MA_SSH_KEY_TABLENAME
    . " ( "  
    . MA_SSH_KEY_TABLE_FIELDNAME::ACCOUNT_ID . ", "
    . MA_SSH_KEY_TABLE_FIELDNAME::FILENAME . ", "
    . MA_SSH_KEY_TABLE_FIELDNAME::DESCRIPTION . ", "
    . MA_SSH_KEY_TABLE_FIELDNAME::PUBLIC_KEY . ")  VALUES ("
    . "'" . $member_id . "', "
    . "'" . $ssh_filename . "', "
    . "'" . $ssh_description . "', "
    . "'" . $ssh_key . "')";
  $result = db_execute_statement($sql);
  return $result;

}

function lookup_ssh_keys($args)
{
  global $MA_SSH_KEY_TABLENAME;
  //  error_log("LOOKUP_SSH_KEYS " . print_r($args, true));
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  $sql = "select * from " . $MA_SSH_KEY_TABLENAME 
    . " WHERE " . MA_SSH_KEY_TABLE_FIELDNAME::ACCOUNT_ID . " = '" 
    . $member_id . "'";
  $rows = db_fetch_rows($sql);
  //  error_log("LOOKUP_SSH_KEYS " . print_r($rows, true));
  return $rows;
}

function lookup_keys_and_certs($args)
{
  $member_id = $args[MA_ARGUMENT::MEMBER_ID];
  global $MA_INSIDE_KEY_TABLENAME;
  $sql = "select " 
    . MA_INSIDE_KEY_TABLE_FIELDNAME::PRIVATE_KEY . ", "
    . MA_INSIDE_KEY_TABLE_FIELDNAME::CERTIFICATE 
    . " FROM " . $MA_INSIDE_KEY_TABLENAME
    . " WHERE " 
    . MA_INSIDE_KEY_TABLE_FIELDNAME::ACCOUNT_ID 
    . "= '" . $member_id . "'";
  error_log("LKAC.sql = " . $sql);
  $row = db_fetch_row($sql);
  return $row;
}

handle_message("MA");


?>

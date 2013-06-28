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

// Client-side interface to GENI Clearinghouse Member Authority (MA)

require_once('ma_constants.php');
require_once('message_handler.php');

// A cache of a user's detailed info indexed by member_id
if(!isset($member_cache)) {
  //  error_log("SETTING MEMBER_CACHE");
  $member_cache = array();
  $member_by_attribute_cache = array(); // Only for single attribute lookups
}

// Get list of all member_ids in repository
function get_member_ids($ma_url, $signer)
{
  $get_member_ids_message['operation'] = 'get_member_ids';
  $result = put_message($ma_url, $get_member_ids_message,
          $signer->certificate(), $signer->privateKey());
  return $result;
}

// Associate SSH public key with user
function register_ssh_key($ma_url, $signer, $member_id, $filename,
        $description, $ssh_public_key, $ssh_private_key = NULL)
{
  $register_ssh_key_message['operation'] = 'register_ssh_key';
  $register_ssh_key_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $register_ssh_key_message[MA_ARGUMENT::SSH_FILENAME] = $filename;
  $register_ssh_key_message[MA_ARGUMENT::SSH_DESCRIPTION] = $description;
  $register_ssh_key_message[MA_ARGUMENT::SSH_PUBLIC_KEY] = $ssh_public_key;
  if (! is_null($ssh_private_key)) {
    $register_ssh_key_message[MA_ARGUMENT::SSH_PRIVATE_KEY] = $ssh_private_key;
  }
  $result = put_message($ma_url, $register_ssh_key_message,
          $signer->certificate(), $signer->privateKey());
  return $result;
}

// Lookup public SSH keys associated with user
function lookup_public_ssh_keys($ma_url, $signer, $member_id)
{
  $lookup_ssh_keys_message['operation'] = 'lookup_public_ssh_keys';
  $lookup_ssh_keys_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $ssh_keys = put_message($ma_url, $lookup_ssh_keys_message,
          $signer->certificate(), $signer->privateKey());
  return $ssh_keys;
}

// Lookup private SSH keys associated with user
function lookup_private_ssh_keys($ma_url, $signer, $member_id)
{
  $lookup_ssh_keys_message['operation'] = 'lookup_private_ssh_keys';
  $lookup_ssh_keys_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $ssh_keys = put_message($ma_url, $lookup_ssh_keys_message,
          $signer->certificate(), $signer->privateKey());
  return $ssh_keys;
}

// Lookup a single SSH key by id
function lookup_public_ssh_key($ma_url, $signer, $member_id, $ssh_key_id)
{
  $keys = lookup_publc_ssh_keys($ma_url, $signer, $member_id);
  foreach ($keys as $key) {
    if ($key[MA_SSH_KEY_TABLE_FIELDNAME::ID] === $ssh_key_id) {
      return $key;
    }
  }
  // No key found, return NULL
  return NULL;
}

function update_ssh_key($ma_url, $signer, $member_id, $ssh_key_id,
        $filename, $description)
{
  $msg['operation'] = 'update_ssh_key';
  $msg[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $msg[MA_ARGUMENT::SSH_KEY_ID] = $ssh_key_id;
  if ($filename) {
    $msg[MA_ARGUMENT::SSH_FILENAME] = $filename;
  }
  if ($description) {
    $msg[MA_ARGUMENT::SSH_DESCRIPTION] = $description;
  }
  $ssh_key = put_message($ma_url, $msg,
          $signer->certificate(), $signer->privateKey());
  return $ssh_key;
}

function delete_ssh_key($ma_url, $signer, $member_id, $ssh_key_id)
{
  $msg['operation'] = 'delete_ssh_key';
  $msg[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $msg[MA_ARGUMENT::SSH_KEY_ID] = $ssh_key_id;
  $result = put_message($ma_url, $msg,
          $signer->certificate(), $signer->privateKey());
  return $result;
}

// Lookup inside keys/certs associated with a user UUID
function lookup_keys_and_certs($ma_url, $signer, $member_uuid)
{
  $lookup_keys_and_certs_message['operation'] = 'lookup_keys_and_certs';
  $lookup_keys_and_certs_message[MA_ARGUMENT::MEMBER_ID] = $member_uuid;
  $keys_and_certs = put_message($ma_url, $lookup_keys_and_certs_message,
          $signer->certificate(), $signer->privateKey());
  return $keys_and_certs;
}

function ma_create_account($ma_url, $signer, $attrs,
        $self_asserted_attrs)
{
  $all_attrs = array();
  foreach (array_keys($attrs) as $attr_name) {
    $all_attrs[] = array(MA_ATTRIBUTE::NAME => $attr_name,
            MA_ATTRIBUTE::VALUE => $attrs[$attr_name],
            MA_ATTRIBUTE::SELF_ASSERTED => FALSE);
  }
  foreach (array_keys($self_asserted_attrs) as $attr_name) {
    $all_attrs[] = array(MA_ATTRIBUTE::NAME => $attr_name,
            MA_ATTRIBUTE::VALUE => $self_asserted_attrs[$attr_name],
            MA_ATTRIBUTE::SELF_ASSERTED => TRUE);
  }
  $msg['operation'] = 'create_account';
  $msg[MA_ARGUMENT::ATTRIBUTES] = $all_attrs;
  $result = put_message($ma_url, $msg,
          $signer->certificate(), $signer->privateKey());
  return $result;
}

class Member {
  function __construct() {
  }
  function init_from_record($record) {
    $this->member_id = $record[MA_ARGUMENT::MEMBER_ID];
    $attrs = $record[MA_ARGUMENT::ATTRIBUTES];
    foreach ($attrs as $attr) {
      $aname = $attr[MA_ATTRIBUTE::NAME];
      $aval = $attr[MA_ATTRIBUTE::VALUE];
      $this->{$aname} = $aval;
    }
  }
  function prettyName() {
    if (isset($this->displayName)) {
      return $this->displayName;
    } elseif (isset($this->first_name, $this->last_name)) {
      return $this->first_name . " " . $this->last_name;
    } else {
      return $this->eppn;
    }
  }
}

function ma_lookup_members($ma_url, $signer, $lookup_attrs)
{
  global $member_cache;
  global $member_by_attribute_cache;

  $cache_key = '';
  if (count($lookup_attrs) == 1) {
    $keys = array_keys($lookup_attrs);
    $attr_key = $keys[0];
    $attr_value = $lookup_attrs[$attr_key];
    $cache_key = $attr_key . "." . $attr_value;
      if (array_key_exists($cache_key, $member_by_attribute_cache)) {
	//	error_log("CACHE HIT lookup_members : " . $cache_key);
	return $member_by_attribute_cache[$cache_key];
      }
  }
  $attrs = array();
  foreach (array_keys($lookup_attrs) as $attr_name) {
    $attrs[] = array(MA_ATTRIBUTE::NAME => $attr_name,
            MA_ATTRIBUTE::VALUE => $lookup_attrs[$attr_name]);
  }
  $msg['operation'] = 'lookup_members';
  $msg[MA_ARGUMENT::ATTRIBUTES] = $attrs;
  $members = put_message($ma_url, $msg,
          $signer->certificate(), $signer->privateKey());
  // Somegtimes we get the whole record, not just value, 
  // depending on the controller
  if (array_key_exists(RESPONSE_ARGUMENT::CODE, $members)) {
    if ($members[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return array();
    $members = $members[RESPONSE_ARGUMENT::VALUE];
  }
  $result = array();
  foreach ($members as $member_info) {
    $member = new Member();
    $member->init_from_record($member_info);
    $member_id = $member_info[MA_ARGUMENT::MEMBER_ID];
    $member_cache[$member_id] = $member;
    $result[] = $member;
  }

  if (count($lookup_attrs) == 1) {
    $member_by_attribute_cache[$cache_key] = $result;
  }
  return $result;
}

function ma_list_clients($ma_url, $signer)
{
  $list_clients_message['operation'] = "ma_list_clients";
  $result = put_message($ma_url, 
			 $list_clients_message, 
			 $signer->certificate(), 
			 $signer->privateKey());
  return $result;
}

function ma_list_authorized_clients($ma_url, $signer, $member_id)
{
  $list_authorized_clients_message['operation'] = "ma_list_authorized_clients";
  $list_authorized_clients_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $result = put_message($ma_url, 
			 $list_authorized_clients_message, 
			 $signer->certificate(), 
			 $signer->privateKey());
  return $result;
}

function ma_authorize_client($ma_url, $signer, $member_id, $client_urn,
			     $authorize_sense)
{
  //  error_log("MAAC = " . print_r($authorize_sense, true));

  $authorize_client_message['operation'] = "ma_authorize_client";
  $authorize_client_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $authorize_client_message[MA_ARGUMENT::CLIENT_URN] = $client_urn;
  $authorize_client_message[MA_ARGUMENT::AUTHORIZE_SENSE] = $authorize_sense;
  $result = put_message($ma_url, 
			 $authorize_client_message, 
			 $signer->certificate(), 
			 $signer->privateKey());

  //  error_log("MAAC.result = " . print_r($result, true));

  return $result;
}

// Use ma_lookup_members interface
function ma_lookup_member_id($ma_url, $signer, $member_id_key, $member_id_value)
{

  $lookup_attrs[$member_id_key] = $member_id_value;
  $result = ma_lookup_members($ma_url, $signer, $lookup_attrs);

  //  error_log("MALI.RES = " . print_r($result, true));
  return $result;
}

function ma_lookup_member_by_id($ma_url, $signer, $member_id)
{
  global $member_cache;
  if (array_key_exists($member_id, $member_cache)) {
    //    error_log("CACHE HIT lookup_member_by_id: " . $member_id);
    return $member_cache[$member_id];
  }
  $msg['operation'] = 'lookup_member_by_id';
  $msg[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $result = put_message($ma_url, $msg,
          $signer->certificate(), $signer->privateKey());
  // Somegtimes we get the whole record, not just value, 
  // depending on the controller
  if(array_key_exists(RESPONSE_ARGUMENT::CODE, $result)) {
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return null;
    $result = $result[RESPONSE_ARGUMENT::VALUE];
  }
  $member = new Member();
  $member->init_from_record($result);
  $member_cache[$member_id]=$member;
  return $member;
}

function ma_create_certificate($ma_url, $signer, $member_id, $csr=NULL)
{
  $cert = NULL;
  $private_key = NULL;
  $msg['operation'] = 'ma_create_certificate';
  $msg[MA_ARGUMENT::MEMBER_ID] = $member_id;
  if (isset($csr) && (! is_null($csr))) {
    $msg[MA_ARGUMENT::CSR] = $csr;
  }
  $result = put_message($ma_url, $msg,
          $signer->certificate(), $signer->privateKey());
  return $result;
}

function ma_lookup_certificate($ma_url, $signer, $member_id)
{
  $msg['operation'] = 'ma_lookup_certificate';
  $msg[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $result = put_message($ma_url, $msg,
          $signer->certificate(), $signer->privateKey());
  return $result;
}

// Lookup all details all members whose ID's are specified
function lookup_member_details($ma_url, $signer, $member_uuids)
{
  $msg['operation'] = 'lookup_member_details';
  $msg[MA_ARGUMENT::MEMBER_UUIDS] = $member_uuids;
  $result = put_message($ma_url, $msg, 
			$signer->certificate(), $signer->privateKey());
  return $result;
}


// Lookup the display name for all member_ids in a given set of 
// rows, where the member_id is selected by given field name
// Do not include the given signer in the query but add in the response
// If there is no member other than the signer, don't make the query
function lookup_member_names_for_rows($ma_url, $signer, $rows, $field)
{
  $member_uuids = array();
  foreach($rows as $row) {
    $member_id = $row[$field];
    if($member_id == $signer->account_id || in_array($member_id, $member_uuids)) 
      continue;
    $member_uuids[] = $member_id;
  }
  $names_by_id = array();
  $result = generate_response(RESPONSE_ERROR::NONE, $names_by_id, '');
  if (count($member_uuids) > 0) {
    $names_by_id = lookup_member_names($ma_url, $signer, $member_uuids);
  }
  $names_by_id[$signer->account_id] = $signer->prettyName();
  //  error_log('RESULT = ' . print_r($names_by_id, true));
  return $names_by_id;
}

// Lookup the 'display name' for all members whose ID's are specified
function lookup_member_names($ma_url, $signer, $member_uuids)
{
  $msg['operation'] = 'lookup_member_names';
  $msg[MA_ARGUMENT::MEMBER_UUIDS] = $member_uuids;
  $result = put_message($ma_url, $msg, 
			$signer->certificate(), $signer->privateKey());
  return $result;
}

// Lookup all members with given email
// Return dictionary email => [member_ids]*
function lookup_members_by_email($ma_url, $signer, $member_emails)
{
  $msg['operation'] = 'lookup_members_by_email';
  $msg[MA_ARGUMENT::MEMBER_EMAILS] = $member_emails;
  $result = put_message($ma_url, $msg, $signer->certificate, $signer->privateKey());
  return $result;
}



?>

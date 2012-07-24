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
// Consists of these methods:
//   add_attribute(ma_url, member_id, role_type, context_type, context_id);
//   remove_attribute(ma_url, member_id, role_type, context_type, context_id);
//   update_role(ma_url, member_id, role_type, context_type, context_id);
//   lookup_attributes(ma_url, member_id);

require_once('ma_constants.php');
require_once('message_handler.php');

//  Create an attribute with given role, context_type and context_id (possibly null)
function add_attribute($ma_url, $member_id, $role_type, $context_type, $context_id)
{
  $add_attribute_message['operation'] = 'add_attribute';
  $add_attribute_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $add_attribute_message[MA_ARGUMENT::ROLE_TYPE] = $role_type;
  $add_attribute_message[MA_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $add_attribute_message[MA_ARGUMENT::CONTEXT_ID] = $context_id;
  $result = put_message($ma_url, $add_attribute_message);
  return $result;
}

// Remove an attribute for given member of given role_type, context_type, context_id
// role_type may be null, in which case all attributes for this member on this context
// will be removed
function remove_attribute($ma_url, $member_id, $role_type, $context_type, $context_id)
{
  $remove_attribute_message['operation'] = 'remove_attribute';
  $remove_attribute_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $remove_attribute_message[MA_ARGUMENT::ROLE_TYPE] = $role_type;
  $remove_attribute_message[MA_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $remove_attribute_message[MA_ARGUMENT::CONTEXT_ID] = $context_id;
  $result = put_message($ma_url, $remove_attribute_message);
  return $result;
}

// Update role of given attribute
function update_role($ma_url, $member_id, $role_type, $context_type, $context_id)
{
  $update_role_message['operation'] = 'update_role';
  $update_role_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $update_role_message[MA_ARGUMENT::ROLE_TYPE] = $role_type;
  $update_role_message[MA_ARGUMENT::CONTEXT_TYPE] = $context_type;
  $update_role_message[MA_ARGUMENT::CONTEXT_ID] = $context_id;
  $result = put_message($ma_url, $update_role_message);
  return $result;
}

function lookup_attributes($ma_url, $member_id)
{
  $lookup_attributes_message['operation'] = 'lookup_attributes';
  $lookup_attributes_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $attributes = put_message($ma_url, $lookup_attributes_message);
  return $attributes;
}

// Get list of all member_ids in repository
function get_member_ids($ma_url)
{
  $get_member_ids_message['operation'] = 'get_member_ids';
  $result = put_message($ma_url, $get_member_ids_message);
  return $result;
}

// Associate SSH public key with user
function register_ssh_key($ma_url, $member_id, $filename, $description, 
			  $ssh_key)
{
  $register_ssh_key_message['operation'] = 'register_ssh_key';
  $register_ssh_key_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $register_ssh_key_message[MA_ARGUMENT::SSH_FILENAME] = $filename;
  $register_ssh_key_message[MA_ARGUMENT::SSH_DESCRIPTION] = $description;
  $register_ssh_key_message[MA_ARGUMENT::SSH_KEY] = $ssh_key;
  $result = put_message($ma_url, $register_ssh_key_message);
  return $result;
}

// Lookup SSH keys associated with user
function lookup_ssh_keys($ma_url, $member_id)
{
  $lookup_ssh_keys_message['operation'] = 'lookup_ssh_keys';
  $lookup_ssh_keys_message[MA_ARGUMENT::MEMBER_ID] = $member_id;
  $ssh_keys = put_message($ma_url, $lookup_ssh_keys_message);
  return $ssh_keys;
}

// Lookup inside keys/certs associated with a user UUID
function lookup_keys_and_certs($ma_url, $member_uuid)
{
  $lookup_keys_and_certs_message['operation'] = 'lookup_keys_and_certs';
  $lookup_keys_and_certs_message[MA_ARGUMENT::MEMBER_ID] = $member_uuid;
  $keys_and_certs = put_message($ma_url, $lookup_keys_and_certs_message);
}


?>

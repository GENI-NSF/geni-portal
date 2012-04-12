<?php
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


?>

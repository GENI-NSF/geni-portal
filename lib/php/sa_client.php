<?php

// Client-side interface to GENI Clearinghouse Slice Authority (SA)
//
// Consists of these methods:
//   get_slice_credential(slice_id, user_id)
//   slice_id <= create_slice(project_id, slice_name, urn, owner_id);
//   slice_ids <= lookup_slices(project_id);
//   slice_details <= lookup_slice(slice_id);
//   renew_slice(slice_id);

require_once('sa_constants.php');

/* Create a slice crede3ntial for given SLICE ID and user */
function get_slice_credential($sa_url, $slice_id, $user_id)
{
  $row = db_fetch_inside_private_key_cert($user_id);
  $pretty_row = print_r($row, true);
  error_log("SAClient: row = $pretty_row");
  $cert = $row['certificate'];
  $message['operation'] = 'create_slice_credential';
  $message['slice_name'] = $slice_id;
  $message['experimenter_certificate'] = $cert;
  $result = put_message($sa_url, $message);
  return $result['slice_credential'];
}

/* Create a new slice record in database, return slice_id */
function create_slice($sa_url, $project_id, $slice_name, $slice_urn, $owner_id)
{
  $create_slice_message['operation'] = 'create_slice';
  $create_slice_message[SA_ARGUMENT::PROJECT_ID] = $project_id;
  $create_slice_message[SA_ARGUMENT::SLICE_NAME] = $slice_name;
  $create_slice_message[SA_ARGUMENT::SLICE_URN] = $slice_urn;
  $create_slice_message[SA_ARGUMENT::OWNER_ID] = $owner_id;
  $slice_id = put_message($sa_url, $create_slice_message);
  return $slice_id;
}

/* Lookup slice ids for given project */
function lookup_slices($sa_url, $project_id)
{
  $lookup_slices_message['operation'] = 'lookup_slices';
  $lookup_slices_message[SA_ARGUMENT::PROJECT_ID] = $project_id;
  $slice_ids = put_message($sa_url, $lookup_slices_message);
  return $slice_ids;
}

/* lookup details of slice of given id */
function lookup_slice($sa_url, $slice_id)
{
  $lookup_slice_message['operation'] = 'lookup_slice';
  $lookup_slice_message[SA_ARGUMENT::SLICE_ID] = $slice_id;
  $slice = put_message($sa_url, $lookup_slice_message);
  return $slice;
}

/* Renew slice of given id */
function renew_slice($sa_url, $slice_id)
{
  $renew_slice_message['operation'] = 'renew_slice';
  $renew_slice_message[SA_ARGUMENT::SLICE_ID] = $slice_id;
  $result = put_message($sa_url, $renew_slice_message);
  return $result;
}

?>

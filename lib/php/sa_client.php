<?php

// Routines to help clients of the slice authority

function get_slice_credential($sa_url, $slice_id, $user)
{
  $row = db_fetch_inside_private_key_cert($user->account_id);
  $pretty_row = print_r($row, true);
  error_log("SAClient: row = $pretty_row");
  $cert = $row['certificate'];
  $message['operation'] = 'create_slice_credential';
  $message['slice_name'] = $slice_id;
  $message['experimenter_certificate'] = $cert;
  $result = put_message($sa_url, $message);
  return $result['slice_credential'];
}

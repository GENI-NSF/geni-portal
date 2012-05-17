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

// Client-side interface to GENI Clearinghouse Slice Authority (SA)
//
// Consists of these methods:
//   get_slice_credential(slice_id, user_id)
//   slice_id <= create_slice(project_id, project_name, slice_name, owner_id);
//   slice_ids <= lookup_slices(project_id);
//   slice_details <= lookup_slice(slice_id);
//   slice_details <= lookup_slice_by_urn(slice_urn);
//   renew_slice(slice_id, expiration, owner_id);

require_once('sa_constants.php');

/* Create a slice credential for given SLICE ID and user */
function get_slice_credential($sa_url, $signer, $slice_id, $cert=NULL)
{
  $signer_cert = $signer->certificate();
  $signer_key = $signer->privateKey();

  if (is_null($cert)) {
    $cert = $signer_cert;
  }

  if (! isset($cert) || is_null($cert) || $cert == "") {
    error_log("Cannot get_slice_cred without a user cert");
    return null;
  }
  $message['operation'] = 'get_slice_credential';
  $message[SA_ARGUMENT::SLICE_ID] = $slice_id;
  $message[SA_ARGUMENT::EXP_CERT] = $cert;

  $result = put_message($sa_url, $message, $signer_cert, $signer_key);
  return $result['slice_credential'];
}

/* Create a new slice record in database, return slice_id */
function create_slice($sa_url, $signer, $project_id, $project_name, $slice_name,
                      $owner_id)
{
  $create_slice_message['operation'] = 'create_slice';
  $create_slice_message[SA_ARGUMENT::PROJECT_ID] = $project_id;
  $create_slice_message[SA_ARGUMENT::PROJECT_NAME] = $project_name;
  $create_slice_message[SA_ARGUMENT::SLICE_NAME] = $slice_name;
  $create_slice_message[SA_ARGUMENT::OWNER_ID] = $owner_id;
  $slice = put_message($sa_url, $create_slice_message,
                       $signer->certificate(), $signer->privateKey());
  return $slice;
}

/* Lookup slice ids for given project */
function lookup_slice_ids($sa_url, $signer, $project_id)
{
  $lookup_slice_ids_message['operation'] = 'lookup_slice_ids';
  $lookup_slice_ids_message[SA_ARGUMENT::PROJECT_ID] = $project_id;
  $slice_ids = put_message($sa_url, $lookup_slice_ids_message,
                           $signer->certificate(), $signer->privateKey());
  return $slice_ids;
}

/* Lookup slice ids for given project and owner */
/* function lookup_slice_ids_by_project_and_owner($sa_url, $project_id, $owner_id) */
/* { */
/*   $lookup_slice_ids_message['operation'] = 'lookup_slice_ids'; */
/*   $lookup_slice_ids_message[SA_ARGUMENT::PROJECT_ID] = $project_id; */
/*   $lookup_slice_ids_message[SA_ARGUMENT::OWNER_ID] = $owner_id; */
/*   $slice_ids = put_message($sa_url, $lookup_slice_ids_message); */
/*   return $slice_ids; */
/* } */

/* Lookup slice ids for given owner */
/* function lookup_slice_ids_by_owner($sa_url, $owner_id) */
/* { */
/*   $lookup_slice_ids_message['operation'] = 'lookup_slice_ids'; */
/*   $lookup_slice_ids_message[SA_ARGUMENT::OWNER_ID] = $owner_id; */
/*   $slice_ids = put_message($sa_url, $lookup_slice_ids_message); */
/*   return $slice_ids; */
/* } */

/* lookup slice ids by slice name, project ID */
/* function lookup_slices_by_project_and_name($sa_url, $project_id, $slice_name) */
/* { */
/*   $lookup_slice_ids_message['operation'] = 'lookup_slice_ids'; */
/*   $lookup_slice_ids_message[SA_ARGUMENT::PROJECT_ID] = $project_id; */
/*   $lookup_slice_ids_message[SA_ARGUMENT::SLICE_NAME] = $slice_name; */
/*   $slice = put_message($sa_url, $lookup_slice_ids_message); */
/*   return $slice_ids; */
/* } */

/* lookup a set of slices by name, project_id, owner */
function lookup_slices($sa_url, $signer, $project_id, $owner_id)
{
  $signer_cert = $signer->certificate();
  $signer_key = $signer->privateKey();
  $lookup_slices_message['operation'] = 'lookup_slices';
  $lookup_slices_message[SA_ARGUMENT::PROJECT_ID] = $project_id;
  $lookup_slices_message[SA_ARGUMENT::OWNER_ID] = $owner_id;
  $slices = put_message($sa_url, $lookup_slices_message, $signer_cert,
                        $signer_key);
  return $slices;
}

/* lookup details of slice of given id */
// Return array(id, name, project_id, expiration, owner_id, urn)
function lookup_slice($sa_url, $signer, $slice_id)
{
  //  error_log("LS.start " . $slice_id . " " . time());
  $lookup_slice_message['operation'] = 'lookup_slice';
  $lookup_slice_message[SA_ARGUMENT::SLICE_ID] = $slice_id;
  $slice = put_message($sa_url, $lookup_slice_message,
                       $signer->certificate(), $signer->privateKey());
  //  error_log("LS.end " . $slice_id . " " . time());
  return $slice;
}

/* lookup details of slice of given slice URN */
// Return array(id, name, project_id, expiration, owner_id, urn)
function lookup_slice_by_urn($sa_url, $slice_urn)
{
  //  error_log("LS.start " . $slice_id . " " . time());
  $lookup_slice_message['operation'] = 'lookup_slice_by_urn';
  $lookup_slice_message[SA_ARGUMENT::SLICE_URN] = $slice_urn;
  $slice = put_message($sa_url, $lookup_slice_message);
  //  error_log("LS.end " . $slice_id . " " . time());
  return $slice;
}

/* Renew slice of given id */
function renew_slice($sa_url, $slice_id, $expiration, $owner_id)
{
  $renew_slice_message['operation'] = 'renew_slice';
  $renew_slice_message[SA_ARGUMENT::SLICE_ID] = $slice_id;
  $renew_slice_message[SA_ARGUMENT::EXPIRATION] = $expiration;
  $renew_slice_message[SA_ARGUMENT::OWNER_ID] = $owner_id;
  $result = put_message($sa_url, $renew_slice_message);
  return $result;
}

?>

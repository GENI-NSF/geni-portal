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

require_once("message_handler.php");
require_once('sa_constants.php');
require_once('file_utils.php');
require_once('db_utils.php');
require_once('sa_utils.php');
require_once("sa_settings.php");

/* Create a slice credential and return it */
function get_slice_credential($args)
{
  /* site settings */
  global $sa_authority_cert;
  global $sa_authority_private_key;
  global $sa_gcf_include_path;

  /* Extract method arguments. */
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $experimenter_cert = $args[SA_ARGUMENT::EXP_CERT];

  /* Locate relevant info about the slice. */
  $slice_row = fetch_slice_by_id($slice_id);
  $slice_cert = $slice_row[SA_SLICE_TABLE_FIELDNAME::CERTIFICATE];
  $expiration = strtotime($slice_row[SA_SLICE_TABLE_FIELDNAME::EXPIRATION]);

  $slice_cred = create_slice_credential($slice_cert,
                                        $experimenter_cert,
                                        $expiration,
                                        $sa_authority_cert,
                                        $sa_authority_private_key);
  $result = array(SA_ARGUMENT::SLICE_CREDENTIAL => $slice_cred);
  return $result;
}

/* Create a slice for given project, name, urn, owner_id */
function create_slice($args)
{
  global $SA_SLICE_TABLENAME;
  global $sa_slice_cert_life_days;
  global $sa_authority_cert;
  global $sa_authority_private_key;

  $slice_name = $args[SA_ARGUMENT::SLICE_NAME];
  $project_id = $args[SA_ARGUMENT::PROJECT_ID];
  $owner_id = $args[SA_ARGUMENT::OWNER_ID];
  $slice_id = make_uuid();

  $slice_email = 'slice-' . $slice_name . '@sa.example.com';
  $slice_cert = create_slice_certificate($slice_name, $slice_email,
                                         $slice_id, $sa_slice_cert_life_days,
                                         $sa_authority_cert,
                                         $sa_authority_private_key);

  $slice_urn = slice_urn_from_cert($slice_cert);

  $expiration = get_future_date(30); // 30 days increment

  $conn = db_conn();
  $sql = "INSERT INTO " 
    . $SA_SLICE_TABLENAME 
    . " ( "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::CERTIFICATE . ") "
    . " VALUES (" 
    . $conn->quote($slice_id, 'text') . ", "
    . $conn->quote($slice_name, 'text') . ", "
    . $conn->quote($project_id, 'text') . ", "
    . $conn->quote($slice_urn, 'text') . ", "
    . $conn->quote(db_date_format($expiration), 'timestamp') . ", "
    . $conn->quote($owner_id, 'text') . ", "
    . $conn->quote($slice_email, 'text') . ", "
    . $conn->quote($slice_cert, 'text') . ") ";
 
  $db_result = db_execute_statement($sql);

  // Return the standard info about the slice.
  return lookup_slice(array(SA_ARGUMENT::SLICE_ID => $slice_id));
}

function lookup_slices($args)
{
  global $SA_SLICE_TABLENAME;
  if (array_key_exists(SA_ARGUMENT::PROJECT_ID, $args)) {
    $project_id = $args[SA_ARGUMENT::PROJECT_ID];
    //    error_log("Got pid $project_id\n");
  }
  if (array_key_exists(SA_ARGUMENT::OWNER_ID, $args)) {
    $owner_id = $args[SA_ARGUMENT::OWNER_ID];
    //    error_log("Got oid $owner_id\n");
  }
  if (array_key_exists(SA_ARGUMENT::SLICE_NAME, $args)) {
    $slice_name = $args[SA_ARGUMENT::SLICE_NAME];
  }

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE true=true ";
  if (isset($project_id)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID .
      " = '" . $project_id . "'";
  }
  if (isset($owner_id)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::OWNER_ID .
      " = '" . $owner_id . "'";
  }
  if (isset($slice_name)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME .
      " = '" . $slice_name . "'";
  }
  $sql = $sql . " ORDER BY " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . 
    ", " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID;

  //  error_log("LOOKUP_SLICES.SQL = " . $sql);
  $rows = db_fetch_rows($sql);
  //  error_log("LOOKUP_SLICES.ROWS = " . print_r($rows, true));
  $slice_ids = array();
  foreach ($rows as $row) {
    //    error_log("LOOKUP_SLICES.ROW = " . print_r($row, true));
    $slice_id = $row[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    //    error_log("LOOKUP_SLICES.SID = " . print_r($slice_id, true));
    $slice_ids[] = $slice_id;
  }
  //  error_log("LOOKUP_SLICES.SLICE_IDS = " . print_r($slice_ids, true));
  return $slice_ids;
}

function lookup_slice($args)
{
  // FIXME: use sa_utils::fetch_slice_by_id and then
  // filter columns before returning (we don't need everything!)

  global $SA_SLICE_TABLENAME;

  $slice_id = $args[SA_ARGUMENT::SLICE_ID];

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " = '" . $slice_id . "'";
  //  error_log("LOOKUP_SLICE.SQL = " . $sql);
  $row = db_fetch_row($sql);
  // error_log("LOOKUP_SLICE.ROW = " . print_r($row, true));
  return $row;
}

function renew_slice($args)
{
  global $SA_SLICE_TABLENAME;
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];

  $expiration = get_future_date(20);// 20 days increment

  $sql = "UPDATE " . $SA_SLICE_TABLENAME 
    . " SET " . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . " = '"
    . db_date_format($expiration) . "'"
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . " = '" . $slice_id  . "'";

  //  error_log("RENEW.sql = " . $sql);

  $result = db_execute_statement($sql);
  return $result;

}



handle_message("SA");

?>
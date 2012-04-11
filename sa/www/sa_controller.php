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

/* Create a slice credential and return it */
function create_slice_credential($args)
{
  /* Extract method arguments. */
  $pretty_args = print_r($args, true);
  error_log("SA CSC: args = $pretty_args");
  $slice_name = $args['slice_name'];
  $exp_cert = $args['experimenter_certificate'];
  error_log("SA CSC: exp_cert = $exp_cert");

  /* Info for settings file. */
  error_log('SA FIXME: hardcoded path to gcf install');
  $portal_gcf_dir = '/usr/share/geni-ch/portal/gcf';
  $portal_gcf_cfg_dir = '/usr/share/geni-ch/portal/gcf.d';

  $cert_file = tempnam(sys_get_temp_dir(), 'sa-');
  file_put_contents($cert_file, $exp_cert);

  // Run slicecred.py and return it as the content.
  $cmd_array = array($portal_gcf_dir . '/src/slicecred.py',
                     $portal_gcf_cfg_dir . '/gcf.ini',
                     $slice_name,
                     $portal_gcf_cfg_dir . '/ch-key.pem',
                     $portal_gcf_cfg_dir . '/ch-cert.pem',
                     $cert_file
                     );
  $command = implode(" ", $cmd_array);
  //  error_log("SA CSC: command = $command");
  $result = exec($command, $output, $status);
  //print_r($output);

  // Clean up, clean up
  unlink($cert_file);

  /* The slice credential is printed to stdout, which is captured in
     $output as an array of lines. Crunch them all together in a
     single string, separated by newlines.
  */
  $slice_cred = implode("\n", $output);
  $result = array('slice_credential' => $slice_cred);
  return $result;
}

/* Create a slice for given project, name, urn, owner_id */
function create_slice($args)
{
  global $SA_SLICE_TABLENAME;

  $slice_name = $args[SA_ARGUMENT::SLICE_NAME];
  $project_id = $args[SA_ARGUMENT::PROJECT_ID];
  $slice_urn = $args[SA_ARGUMENT::SLICE_URN];
  $owner_id = $args[SA_ARGUMENT::OWNER_ID];
  $slice_id = make_uuid();

  $expiration = get_future_date(30); // 30 days increment

  $sql = "INSERT INTO " 
    . $SA_SLICE_TABLENAME 
    . " ( "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ") "
    . " VALUES (" 
    . "'" . $slice_id . "', "
    . "'" . $slice_name . "', "
    . "'" . $project_id . "', "
    . "'" . $slice_urn . "', "
    . "'" . db_date_format($expiration) . "', "
    . "'" . $owner_id . "') ";
 
  //  error_log("SA.INSERT sql = " . $sql);
  $db_result = db_execute_statement($sql);

  // Return the standard info about the slice.
  return lookup_slice(array(SA_ARGUMENT::SLICE_ID => $slice_id));
}

function lookup_slices($args)
{
  global $SA_SLICE_TABLENAME;
  $project_id = $args[SA_ARGUMENT::PROJECT_ID];

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID
    . " = '" . $project_id . "'";
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
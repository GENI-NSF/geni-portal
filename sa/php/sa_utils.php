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

//--------------------------------------------------
// Utilities for the slice authority
//--------------------------------------------------

require_once("sa_settings.php");

/**
 * Create a slice certificate and return it.
 *
 * The result is a string containing the PEM encoded certificate.
 */
function create_slice_certificate($slice_name, $slice_email, $slice_uuid,
                                  $cert_life_days, $auth_cert_file,
                                  $auth_key_file)
{
  global $sa_mkslicecert_prog;
  global $sa_gcf_include_path;

  // Run slicecred.py and return it as the content.
  $cmd_array = array($sa_mkslicecert_prog,
                     '--gcfpath',
                     $sa_gcf_include_path,
                     $auth_cert_file,
                     $auth_key_file,
                     $slice_name,
                     $cert_life_days,
                     $slice_email,
                     $slice_uuid);
  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);

  /* The slice certificate is printed to stdout, which is captured in
     $output as an array of lines. Crunch them all together in a
     single string, separated by newlines.
  */
  $slice_cert = implode("\n", $output);
  return $slice_cert;
}

/**
 * Create a slice credential and return it.
 *
 * The result is a string containing the signed credential (XML).
 */
function create_slice_credential($slice_cert, $experimenter_cert, $expiration,
                                 $auth_cert_file, $auth_key_file)
{
  global $sa_mkslicecred_prog;
  global $sa_gcf_include_path;

  /* Write the slice and experimenter cert to a temp files. */
  $slice_cert_file = writeDataToTempFile($slice_cert, "sa-");
  $experimenter_cert_file = writeDataToTempFile($experimenter_cert, "sa-");

  /* Run mkslicecred. */
  $cmd_array = array($sa_mkslicecred_prog,
                     '--gcfpath',
                     $sa_gcf_include_path,
                     $auth_cert_file,
                     $auth_key_file,
                     $slice_cert_file,
                     $experimenter_cert_file,
                     date("c", $expiration));
  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);

  /* Clean up temp files */
  unlink($slice_cert_file);
  unlink($experimenter_cert_file);

  /* The slice credential is printed to stdout, which is captured in
     $output as an array of lines. Crunch them all together in a
     single string, separated by newlines.
  */
  $slice_cred = implode("\n", $output);
  return $slice_cred;
}

function fetch_slice_by_id($slice_id)
{
  global $SA_SLICE_TABLENAME;

  $conn = db_conn();
  $sql = ("SELECT * FROM $SA_SLICE_TABLENAME WHERE "
          . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
          . " = "
          . $conn->quote($slice_id, 'text'));
  $row = db_fetch_row($sql);
  return $row;
}


?>

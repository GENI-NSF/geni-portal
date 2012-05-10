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
require_once("cert_utils.php");

/**
 * Create a slice certificate and return it.
 *
 * The result is a string containing the PEM encoded certificate.
 */
function create_slice_certificate($project_name, $slice_name, $slice_email,
                                  $slice_uuid, $cert_life_days,
                                  $signer_cert_file, $signer_key_file)
{
  $cert = file_get_contents($signer_cert_file);
  $signer_urn = urn_from_cert($cert);
  if (parse_urn($signer_urn, $authority, $type, $name)) {
    $project_authority = "$authority:$project_name";
    $slice_urn = make_urn($project_authority, 'slice', $slice_name);
  } else {
    error_log("Error parsing signer URN.");
    return null;
  }

  if (make_cert_and_key($slice_uuid, $slice_email, $slice_urn,
                        $signer_cert_file, $signer_key_file,
                        &$cert, &$key, $temp_prefix="geni-")) {
    return $cert;
  } else {
    error_log("Error creating certificate and key.");
    return null;
  }
}

/**
 * Create a slice credential and return it.
 *
 * The result is a string containing the signed credential (XML).
 */
function create_slice_credential($slice_cert, $experimenter_cert, $expiration,
                                 $auth_cert_file, $auth_key_file)
{
  global $sa_mkcred_prog;
  global $sa_gcf_include_path;
  global $sa_trusted_roots;

  /* Write the slice and experimenter cert to a temp files. */
  $slice_cert_file = writeDataToTempFile($slice_cert, "sa-");
  $experimenter_cert_file = writeDataToTempFile($experimenter_cert, "sa-");

  /* Run mkcred. */
  $cmd_array = array($sa_mkcred_prog,
		     'slice',
                     '--gcfpath',
                     $sa_gcf_include_path,
                     $auth_cert_file,
                     $auth_key_file,
                     $slice_cert_file,
                     $experimenter_cert_file,
                     date("c", $expiration));
  foreach ($sa_trusted_roots as $root) {
    $cmd_array[] = '-r';
    $cmd_array[] = "$root";
  }

  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);
  if ($status) {
    error_log( "create_slice_credential(): mkcred failed with status (".$status.") and output: ".print_r($output) );
  }

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

/**
 * Create a user credential and return it.
 *
 * The result is a string containing the signed credential (XML).
 */
function create_user_credential($experimenter_cert, $expiration,
                                 $auth_cert_file, $auth_key_file)
{
  global $sa_mkcred_prog;
  global $sa_gcf_include_path;
  global $sa_trusted_roots;

  /* Write the slice and experimenter cert to a temp files. */
  $experimenter_cert_file = writeDataToTempFile($experimenter_cert, "sa-");

  /* Run mkcred. */
  $cmd_array = array($sa_mkcred_prog,
		     'user',
                     '--gcfpath',
                     $sa_gcf_include_path,
                     $auth_cert_file,
                     $auth_key_file,
                     $experimenter_cert_file,
                     $experimenter_cert_file,
                     date("c", $expiration));
  foreach ($sa_trusted_roots as $root) {
    $cmd_array[] = '-r';
    $cmd_array[] = "$root";
  }

  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);
  if ($status) {
    error_log( "create_user_credential(): mkcred failed with status (".$status.") and output: ".print_r($output) );
  }
  /* Clean up temp files */
  unlink($experimenter_cert_file);

  /* The user credential is printed to stdout, which is captured in
     $output as an array of lines. Crunch them all together in a
     single string, separated by newlines.
  */
  $user_cred = implode("\n", $output);
  return $user_cred;
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
  if (isset($row) && is_array($row)) {
    if (array_key_exists(RESPONSE_ARGUMENT::CODE, $row) && ($row[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) && array_key_exists(RESPONSE_ARGUMENT::VALUE, $row)) {
      $slice = $row[RESPONSE_ARGUMENT::VALUE];
      return $slice;
    } else if (array_key_exists(RESPONSE_ARGUMENT::CODE, $row)) {
      error_log("fetch_slice_by_id got result code " . $row[RESPONSE_ARGUMENT::CODE] . ", output: " . $row[RESPONSE_ARGUMENT::OUTPUT]);
    } else {
      error_log("fetch_Slice_by_id got malformed return");
    }
  }
  return $row;
}
?>

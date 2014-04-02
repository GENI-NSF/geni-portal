<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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

require_once('sr_constants.php');
require_once('sr_client.php');
require_once('signer.php');
require_once('db_utils.php');
require_once('file_utils.php');

$sr_url = get_sr_url();
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

// error_log("MA = " . print_r($ma_url, true));

// FIXME: Parameterize these paths
$km_certfile = "/usr/share/geni-ch/km/km-cert.pem";
$km_keyfile = "/usr/share/geni-ch/km/km-key.pem";
$km_signer = new Signer($km_certfile, $km_keyfile);

// $mycert = file_get_contents($mycertfile);
// $mykey = file_get_contents($mykeyfile);
// error_log("CERT = $mycert");
// error_log("KEY = $mykey");

/**
 * Return a dictionary of attribute->value pairs
 * that were pre-asserted about the given eppn.
 */
function get_asserted_attributes($eppn) {
  $table_name = "km_asserted_attribute";
  $conn = db_conn();
  $sql = ("select * from " . $table_name
          . " where LOWER(eppn) "
          . " = LOWER(" . $conn->quote($eppn, 'text') . ")");
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    $db_error = $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::MA,
            ("Database error: $db_error"));
    geni_syslog(GENI_SYSLOG_PREFIX::MA, "Query was: " . $sql);
    // return an empty array because we couldn't load any attributes.
    return array();
  }
  // SUCCESS -- create the return value from the db results
  $value = array();
  foreach ($result[RESPONSE_ARGUMENT::VALUE] as $row) {
    $value[$row['name']] = $row['value'];
  }
  return $value;
}

/**
 * Set a passphrase on the member's private key.
 *
 * Probably to support the speaks-for signing tool.
 *
 * @param $in_key the clear key (no passphrase)
 * @param $passphrase the passphrase to set on the key
 * @param $out_key reference to variable where the
 *                 passphrase-protected key should be stored on
 *                 return.
 */
function set_key_passphrase($in_key, $passphrase, &$out_key) {
  // Execute the openssl command to set the passphrase:
  //  openssl rsa -des3 -in $tmpin.key -out $tmpout.key
  // More:
  //  Use "-passout file:$pp_file"
  //   Where $pp_file is a temp file containing the desired passphrase
  // Then read the resulting key from tmp file

  // Write in_key to a tmp file
  // Write passphrase to a tmp file.
  // Grab a tmp file for writing out_key

  /* $out_key will contain the passphrase protected key.
   * Initialize it to NULL to avoid returning garbage.
   */
  $out_key = NULL;
  $in_key_file = writeDataToTempFile($in_key, "passphrase-in-");
  $passphrase_file = writeDataToTempFile($passphrase, "passphrase-");
  $out_key_file = writeDataToTempFile('', "passphrase-out-");

  $cmd_array = array('openssl', 'rsa', '-des3',
                     '-in', $in_key_file,
                     '-out', $out_key_file,
                     '-passout', 'file:' . $passphrase_file);
  $command = implode(" ", $cmd_array);
  //error_log("COMMAND = " . $command);

  // openssl rsa -des3 -in /tmp/passphrase-in-4prbom
  //         -out /tmp/passphrase-out-snVSxl
  //         -passout file:/tmp/passphrase-3rU1XQ

  exec($command, $rsa_output, $rsa_status);
  //error_log("openssl rsa status was $rsa_status");
  if ($rsa_status == 0) {
    $out_key = file_get_contents($out_key_file);
    $result = TRUE;
  } else {
    // openssl command failed.
    // XXX Signal Error
    error_log("openssl command failed with status $rsa_status");
    $result = FALSE;
  }
  //  unlink($in_key_file);
  //  unlink($passphrase_file);
  //  unlink($out_key_file);
  return $result;
}

/**
 * Store the given private key in the database for the given member.
 *
 */
function store_private_key($member_id, $private_key) {
}

?>

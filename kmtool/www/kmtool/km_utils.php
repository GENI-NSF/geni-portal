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

require_once('sr_constants.php');
require_once('sr_client.php');
require_once('signer.php');
require_once('db_utils.php');

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
?>

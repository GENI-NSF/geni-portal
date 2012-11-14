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

/**
 * Home of GENI key management tool
 */

require_once('km_utils.php');
require_once('ma_client.php');

$member_id_key = 'eppn';
$member_id_value = null;
$members = array();
$member = null;
$member_id = null;
if (array_key_exists($member_id_key, $_SERVER)) {
  $member_id_value = $_SERVER[$member_id_key];
  $members = ma_lookup_member_id($ma_url, $km_signer,
				 $member_id_key, $member_id_value);
} else if (array_key_exists("member_id", $_REQUEST)) {
  $member_id = $_REQUEST["member_id"];
} else {
  error_log("No member_id_key $member_id_key given to kmhome");
}

if (count($members) > 0 && ! isset($member_id)) {
  $member = $members[0];
  $member_id = $member->member_id;
} else if (! isset($member_id)) {
  error_log("kmhome: No members found for member_id $member_id_value");
}

$username = '*** Undefined ***';
if (array_key_exists('displayName', $_SERVER)) {
  $username = $_SERVER['displayName'];
} else if (array_key_exists('sn', $_SERVER) && array_key_exists('givenName', $_SERVER)){
  $username = $_SERVER['givenName'] . " " . $_SERVER['sn'];
} else if (array_key_exists('eppn', $_SERVER)) {
  $username = $_SERVER['eppn'];
} else if (array_key_exists("username", $_REQUEST)) {
  $username = $_REQUEST["username"];
}

$generate_key = "generate";
if (key_exists($generate_key, $_REQUEST)) {
  // User has asked to generate a cert/key.
  $result = ma_create_certificate($ma_url, $km_signer, $member_id);
  $cert_filename = "geni.pem";
  // Set headers for download
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Disposition: attachment; filename=$cert_filename");
  header("Content-Type: application/pem");
  header("Content-Transfer-Encoding: binary");
  if (key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)) {
    print $result[MA_ARGUMENT::PRIVATE_KEY];
  }
  print $result[MA_ARGUMENT::CERTIFICATE];
  return;
}

// If invoked with a ?redirect=url argument, grab that
// argument and go there from the 'continue' button
$redirect_key = "redirect";

//$redirect_address = "home.php";
$redirect_address = "";

if(array_key_exists($redirect_key, $_GET)) {
  $redirect_address = $_GET[$redirect_key];
}


if (isset($member_id)) {
}

include('kmheader.php');
print "<h2>GENI Certificate Generation Tool</h2><br>\n";
include("tool-showmessage.php");

if (! isset($member_id)) {
  print "You must first activate your GENI account <a href=\"kmactivate.php\">here</a>.<br\>\n";
  include("footer.php");
  return;
}

print "You can either create your own private key or download a" .
        "certificate and private key.<br/><br/>";
print "<h2>Generate a certificate signing request with an existing private key:</h2>\n";
print "<verbatim>openssl req -out CSR.csr -key privateKey.key -new</verbatim><br/>\n";
print "<h2>Generate a certificate signing request and new private key:</h2>\n";
print "<verbatim>openssl req -out CSR.csr -new -newkey rsa:2048 -nodes -keyout privateKey.key</verbatim><br/>\n";

// Generate button
print "<form name=\"generate\" action=\"kmcert.php\" method=\"post\">\n";
print "<input type=\"hidden\" name=\"generate\" value=\"y\"/>";
print "<input type=\"submit\" name=\"submit\" value=\"Generate Certificate and Key\"/>";
print "</form>\n";

// Include this only if the redirect address is a web address
if (! empty($redirect_address)) {
  print"<button onclick=\"window.location='" .
    $redirect_address . "'" . "\"<b>Continue</b></button> back to your " .
    "Clearinghouse tool.<br/>";
}

include("footer.php");
?>
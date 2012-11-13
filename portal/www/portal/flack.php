<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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

require_once('file_utils.php');
require_once('user.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('db-util.php');

// Utilities to generate page for embedding flack in the portal for a given slice

const FLACK_1_FILENAME = "flackportal-1.html";
const FLACK_2_FILENAME = "flackportal-2.html";
const FLACK_3_FILENAME = "flackportal-3.html";
const URL_PREAMBLE = "flack.swf?securitypreset=1&loadallmanagers=1&";
// FIXME: Should not be hard-coded
const SA_URN = "urn:publicid:IDN+geni:gpo:portal+authority+sa";

// FIXME: Allow pgch to run on diff host from portal, configurable port
$http_host = $_SERVER['HTTP_HOST'];
$sa_ch_port = 8443;
$SA_URL = "https://$http_host:$sa_ch_port/";
$CH_URL = "https://$http_host:$sa_ch_port/";

// If have slice_urn then call generate_flack_page and print result
// else if have slice_id then get slice_urn and call generate_flack_page
// else error
if (!isset($user)) {
  $user = geni_loadUser();
}
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
unset($slice);
//unset($slice_urn);
include("tool-lookupids.php");
if (isset($slice)) {
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
}

if (! isset($slice_urn)) {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}
$keys = $user->sshKeys();
if (count($keys) == 0) {
  relative_redirect("error-text.php?error=" . urlencode("No SSH keys " .
	"have been uploaded. Please <a href='uploadsshkey.php'>" .
        "Upload an SSH key</a> or <a href='generatesshkey.php'>Generate and Download an " .
        "SSH keypair</a> to enable access to nodes."));
}

print generate_flack_page($slice_urn);
exit();

// Generate flack pages given all parameters
// and return contents of generated page
function generate_flack_page_internal($slice_urn, $ch_url, $sa_url, 
				      $user_cert, $user_key, $am_root_cert_bundle)
{

  $slice_urn = urlencode($slice_urn);
  $sa_urn = urlencode(SA_URN);

  $filename = "/tmp/" . make_uuid() . ".html";
  $content_1 = file_get_contents(FLACK_1_FILENAME);
  $content_2 = file_get_contents(FLACK_2_FILENAME);
  $content_3 = file_get_contents(FLACK_3_FILENAME);

  $set_commands = 'setServerCert("' . $am_root_cert_bundle . '");' . "\n" 
    . 'setClientKey("' . $user_key . '");' . "\n"
    . 'setClientCert("' . $user_cert . '");' . "\n";

  $url_params = "sliceurn=$slice_urn&saurl=$sa_url&saurn=" . $sa_urn. "&churl=$ch_url";

  $content = $content_1 . $set_commands . $content_2 . '"' . URL_PREAMBLE . $url_params . $content_3;
  file_put_contents($filename, $content);
  return $content;
}

// Take cert text and make it into a javascript acceptable strint
// by adding "\" onto the end of each line
function prepare_cert_for_javascript($cert)
{
  $new_cert = "";
  $lines = explode("\n", $cert);
  $first = true;
  foreach($lines as $line) {
    if ($line == null or $line == "") continue;
    if(!$first) { $new_cert = $new_cert . "\\\n"; } 
    $first = false; 
    $new_cert = $new_cert . $line;
    //    error_log("NC = " . $new_cert);
    //    error_log("LINE = " . $line);
  }
  return $new_cert;
}

function generate_flack_page($slice_urn)
{
  if (! isset($user)) {
    $user = geni_loadUser();
  }
  $sr_url = get_sr_url();
  $am_services = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $ca_services = get_services_of_type(SR_SERVICE_TYPE::CERTIFICATE_AUTHORITY);

  //  error_log("AMs = " . print_r($am_services, true));
  //  error_log("CA = " . print_r($ca_service, true));

  // Get user private inside key and cert
  $user_cert = $user->certificate();
  $user_key = $user->privateKey();

  // Compute bundle of AM and CA certs
  $am_root_cert_bundle = "";
  foreach($ca_services as $ca_service) {
    $ca_cert = $ca_service[SR_TABLE_FIELDNAME::SERVICE_CERT_CONTENTS];
    $am_root_cert_bundle = $am_root_cert_bundle . $ca_cert;
  }

  $user_cert = prepare_cert_for_javascript($user_cert);
  $user_key = prepare_cert_for_javascript($user_key);
  $am_root_cert_bundle = prepare_cert_for_javascript($am_root_cert_bundle);

  global $SA_URL;
  global $CH_URL;
  $content = generate_flack_page_internal($slice_urn, $CH_URL, $SA_URL, $user_cert, $user_key, 
					  $am_root_cert_bundle);

  return $content;
}

// $content = generate_flack_page_internal('111', '222', '333', '444', '555', '666');
// print $content;

?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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
require_once('settings.php');

// Utilities to generate page for embedding flack in the portal for a given slice

const FLACK_1_FILENAME = "flackportal-1.html";
const FLACK_2_FILENAME = "flackportal-2.html";
const FLACK_22_FILENAME = "flackportal-22.html";
const FLACK_3_FILENAME = "flackportal-3.html";
//$URL_PREAMBLE = $flack_url . "?securitypreset=1&loadallmanagers=1&";
//$URL_PREAMBLE2 = $flack_url;
// FIXME: Should not be hard-coded
const SA_URN = "urn:publicid:IDN+geni:gpo:portal+authority+sa";

$pgchs = get_services_of_type(SR_SERVICE_TYPE::PGCH);
if (count( $pgchs ) != 1) {
    error_log("flack must have exactly one PGCH service defined");
    return("Should be exactly one PGCH url.");
} else {
    $pgch = $pgchs[0];
    $PGCH_URL = $pgch[SR_TABLE_FIELDNAME::SERVICE_URL];	
}

$SA_URL = $PGCH_URL;
$CH_URL = $PGCH_URL;

$sa_list = get_services_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
if (count($sa_list) != 1) {
    error_log("flack must have exactly one SA service defined");
    return("Should be exactly one SA.");
} else {
  $sa = $sa_list[0];
  $SA_URN = $sa[SR_TABLE_FIELDNAME::SERVICE_URN];
}


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

if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('slices.php');
}

$keys = $user->sshKeys();
if (count($keys) == 0) {
  relative_redirect("error-text.php?error=" . urlencode("No SSH keys " .
	"have been uploaded. Please <a href='uploadsshkey.php'>" .
        "Upload an SSH key</a> or <a href='generatesshkey.php'>Generate and Download an " .
        "SSH keypair</a> to enable access to nodes."));
}

//print generate_flack_page($slice_urn);
//exit();

// Generate flack pages given all parameters
// and return contents of generated page
function generate_flack_page_internal($slice_urn, $ch_url, $sa_url, 
				      $user_cert, $user_key, $am_root_cert_bundle)
{
  global $flack_url;
  $slice_urn = urlencode($slice_urn);
  $sa_urn = urlencode(SA_URN);

  $content_1 = file_get_contents(FLACK_1_FILENAME);
  $content_2 = file_get_contents(FLACK_2_FILENAME);
  $content_22 = file_get_contents(FLACK_22_FILENAME);
  $content_3 = file_get_contents(FLACK_3_FILENAME);

  $set_commands = 'setServerCert("' . $am_root_cert_bundle . '");' . "\n" 
    . 'setClientKey("' . $user_key . '");' . "\n"
    . 'setClientCert("' . $user_cert . '");' . "\n";
  //  $keycert = '          flashvars.keycert = "' . $user_key . "\\n" . $user_cert . '";' . "\n"
  //    . '          flashvars.keypassphrase = "";' . "\n";
  $savars = '          flashvars.saurl = "' . urlencode($sa_url) . '";' . "\n"
    . '          flashvars.saurn = "' . $sa_urn . '";' . "\n";
  $slicevar = '          flashvars.sliceurn = "' . $slice_urn . '";' . "\n";
  $chvar = '          flashvars.churl = "' . urlencode($ch_url) . '";' . "\n";
  $url_params = "sliceurn=$slice_urn&saurl=$sa_url&saurn=" . $sa_urn. "&churl=$ch_url";

  $content = $content_1 . $set_commands . $content_2 . $savars . $slicevar . $chvar . $content_22 . '"' . $flack_url . $content_3;
  //  $content = $content_1 . $set_commands . $content_2 . $savars . $slicevar . $chvar . $content_22 . '"' . URL_PREAMBLE2 . $content_3;
  //  $content = $content_1 . $set_commands . $content_2 . '"' . URL_PREAMBLE . $url_params . $content_3;
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


$ca_services = get_services_of_type(SR_SERVICE_TYPE::CERTIFICATE_AUTHORITY);
$am_root_cert_bundle = "";
foreach($ca_services as $ca_service) {
  $ca_cert = $ca_service[SR_TABLE_FIELDNAME::SERVICE_CERT_CONTENTS];
  $am_root_cert_bundle = $am_root_cert_bundle . $ca_cert;
}

$sa_url_parameter = $SA_URL;
$sa_urn_parameter = $SA_URN;
$ch_url_parameter = $CH_URL;
$slice_urn_parameter = $slice_urn;
$client_key_parameter = $user->privateKey();
$client_cert_parameter = $user->certificate();
$server_cert_parameter = $am_root_cert_bundle;

/*----------------------------------------------------------------------
 * Presentation below here
 *----------------------------------------------------------------------*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>Flack</title>
    <meta name="google" value="notranslate" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" style="text/css" href="flack.css">

    <script type="text/javascript"
       src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js">
    </script>
    <script type="text/javascript"
            src="https://code.jquery.com/jquery-2.0.3.min.js">
    </script>
  </head>
  <body>

    <div id="flashContent">
      <p>
        To view this page ensure that Adobe Flash Player version
        11.1.0 or greater is installed.
      </p>
      <a href='http://www.adobe.com/go/getflashplayer'>
        <img src='https://www.adobe.com/images/shared/download_buttons/get_flash_player.gif' alt='Get Adobe Flash player' />
      </a>
    </div>

    <div id="socketPool">
      <p>Could not load the flash SocketPool.</p>
    </div>

    <noscript>
      <p>Flack requires that JavaScript be turned on~</p>
    </noscript>

    <script type="text/javascript"
            src="https://www.emulab.net/protogeni/flack-stable/loader.js">
    </script>

    <!-- Put flack in portal mode always. -->
    <script>isPortal=1;</script>
    <script type="text/plain" id="sa-url-parameter">
      <?php echo $sa_url_parameter;?>
    </script>
    <script type="text/plain" id="sa-urn-parameter">
      <?php echo $sa_urn_parameter;?>
    </script>
    <script type="text/plain" id="ch-url-parameter">
      <?php echo $ch_url_parameter;?>
    </script>
    <script type="text/plain" id="slice-urn-parameter">
      <?php echo $slice_urn_parameter;?>
    </script>
    <script type="text/plain" id="client-key-parameter">
      <?php echo $client_key_parameter;?>
    </script>
    <script type="text/plain" id="client-cert-parameter">
      <?php echo $client_cert_parameter;?>
    </script>
    <script type="text/plain" id="server-cert-parameter">
      <?php echo $server_cert_parameter;?>
    </script>
  </body>
</html>

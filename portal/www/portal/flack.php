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

require_once('user.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('settings.php');

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

/* Get the SA and CH URLs. These are really the PGCH URLs because
 * Flack speaks the ProtoGENI Slice Authority and Clearinghouse APIs.
 *
 * If Flack is changed to support the Common Federation API these URLs
 * will change.
 */
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

/* Get the SA URN */
$sa_list = get_services_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
if (count($sa_list) != 1) {
    error_log("flack must have exactly one SA service defined");
    return("Should be exactly one SA.");
} else {
  $sa = $sa_list[0];
  $SA_URN = $sa[SR_TABLE_FIELDNAME::SERVICE_URN];
}

/* Get the root certs, which are the signers of the SSL certs that the
 * aggregates use to speak SSL. */
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
$server_cert_parameter = $am_root_cert_bundle;

/*
 * NOTE: Flack must use the inside cert & key. Flack does not know how
 * to do speaks-for, so cannot use the portal cert/key to talk to PGCH
 * or aggregates with a speaks-for credential.
 *
 * Use the inside cert and key here.
 */
$client_key_parameter = $user->insidePrivateKey();
$client_cert_parameter = $user->insideCertificate();

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
    <script type="text/plain" id="sa-url-parameter"><?php echo $sa_url_parameter;?></script>
    <script type="text/plain" id="sa-urn-parameter"><?php echo $sa_urn_parameter;?></script>
    <script type="text/plain" id="ch-url-parameter"><?php echo $ch_url_parameter;?></script>
    <script type="text/plain" id="slice-urn-parameter"><?php echo $slice_urn_parameter;?></script>
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

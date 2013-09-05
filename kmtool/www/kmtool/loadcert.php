<?php
//----------------------------------------------------------------------
// Copyright (c) 2013 Raytheon BBN Technologies
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
 * Load the user's cert/key into the browser for the signing tool.
 */

require_once('km_utils.php');
require_once('ma_client.php');

$member_id_key = 'eppn';
$member_id_value = null;
$members = array();
$member = null;
$member_id = null;
$authorized_tools_for_user = array();
if (array_key_exists($member_id_key, $_SERVER)) {
  $member_id_value = $_SERVER[$member_id_key];
  $members = ma_lookup_member_id($ma_url, $km_signer,
				 $member_id_key, $member_id_value);
} else {
  error_log("No member_id_key $member_id_key given to loadcert.php");
}

if (count($members) > 0) {
  $member = $members[0];
  $member_id = $member->member_id;
}

$certificate = NULL;
$private_key = NULL;
$result = ma_lookup_certificate($ma_url, $km_signer, $member_id);
if (key_exists(MA_ARGUMENT::CERTIFICATE, $result)) {
  $certificate = $result[MA_ARGUMENT::CERTIFICATE];
}
if (key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)) {
  $private_key = $result[MA_ARGUMENT::PRIVATE_KEY];
}

/*----------------------------------------------------------------------
 * Display happens below here.
 *----------------------------------------------------------------------
 */

include('kmheader.php');
print "<h2>GENI Certificate Loader</h2>\n";
include("tool-showmessage.php");
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js">
</script>
<script src="https://tabletop.gpolab.bbn.com/xml-signer/geni-auth.js">
</script>
<script type="text/plain" id="certificate"><?php echo $certificate;?></script>
<script type="text/plain" id="privatekey"><?php echo $private_key;?></script>
<script>
function initialize()
{
  var userPrivateKey = document.getElementById('privatekey').innerHTML;
  var userCert = document.getElementById('certificate').innerHTML;
  genilib.sendCertificate(userPrivateKey + "\n" + userCert);
}
$(document).ready(initialize);
</script>

<?php
  include("kmfooter.php");
?>

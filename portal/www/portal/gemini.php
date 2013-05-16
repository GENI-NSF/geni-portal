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

/* Redirect the experimenter to GEMINI with the given slice as
 * context if appropriate.
 */

/* TODO:
 *
 *  - put the gemini URL in the service registry
 *  - put the gemini certificate in the service registry
 */

/* GEMINI Constants */
const GEMINI_SLICE_URN = 'slice_urn';
const GEMINI_USER_CERTIFICATE = 'cert';
const GEMINI_USER_PRIVATE_KEY = 'private_keys';
const GEMINI_USER_PASSPHRASE = 'pass';

require_once('user.php');
require_once('ma_client.php');
require_once('header.php');
require_once('smime.php');

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

/* TODO put this in the service registry */
$gemini_url = 'https://genidesktop.netlab.uky.edu/dev/logon/clearinghouse/logon_entry.php';

/* HTML form input name for passing data blob to GEMINI */
$gemini_input_name = 'encoded_dict';

/* TODO put this in the service registry */
$gemini_cert_file = '/usr/share/geni-ch/sr/certs/genidesktop.netlab.uky.edu.pem';

/* Load the certificate into memory */
$gemini_cert = file_get_contents($gemini_cert_file);

/* How long to wait before auto-submitting the form post to GEMINI,
 * in seconds.
 */
$gemini_post_delay_seconds = 20;


if (!isset($user)) {
  $user = geni_loadUser();
}
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

unset($slice);
include("tool-lookupids.php");

if (isset($slice)) {
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
}

$gemini_info = array();
if (isset($slice)) {
  $gemini_info[GEMINI_SLICE_URN] = $slice[SA_ARGUMENT::SLICE_URN];
}

/* Get the user's outside cert/key
 *   note: the user may not have a private key, depending on how they
 *         generated their certificate. There's nothing we can do to
 *         help with that.
 */
$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
if (key_exists(MA_ARGUMENT::CERTIFICATE, $result)) {
  $gemini_info[GEMINI_USER_CERTIFICATE] = $result[MA_ARGUMENT::CERTIFICATE];
  $user_cert .= $result[MA_ARGUMENT::CERTIFICATE];
}
if (key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)) {
  $private_keys = array($result[MA_ARGUMENT::PRIVATE_KEY]);
  $gemini_info[GEMINI_USER_PRIVATE_KEYS] = $private_keys;
}
/* passphrase is always empty for now. No passphrase is specified at
 * certificate creation time.
 */
$gemini_info[GEMINI_USER_PASSPHRASE] = "";

/* Convert data to JSON and encrypt it for the destination. */
$gemini_json = json_encode($gemini_info);

/* Sign the data with the portal certificate (Is that correct?) */
$gemini_signed = $gemini_json;

/* Encrypt the signed data for the GEMINI SSL certificate */
$gemini_blob = smime_encrypt($gemini_signed, $gemini_cert);

/* Finally, JSON encode the result so it can travel via JavaScript */
$gemini_blob = json_encode($gemini_blob);

/* Send the data to the browser to be auto-posted. */

show_header('GENI Portal: Slices', $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
?>



<form id="gemini" action="<?php echo $gemini_url;?>" method="post">
<input id="blob" type="hidden" name="<?php echo $gemini_input_name;?>" value="">
</form>
<script type="text/javascript">
document.write("Hello World!");
var blob = '<?php echo $gemini_blob;?>';
$('#blob').val(blob);
setTimeout(function() { $('#gemini').submit(); },
           <?php echo $gemini_post_delay_seconds * 1000 ?>);
</script>

<?php
include("footer.php");
?>

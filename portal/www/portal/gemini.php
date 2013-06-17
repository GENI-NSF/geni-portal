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
const GEMINI_USER_PRIVATE_KEYS = 'private_keys';
const GEMINI_USER_PASSPHRASE = 'pass';
const GEMINI_USER_PROJECT_NAMES = 'project_names';
const GEMINI_USER_SSH_KEYS = 'ssh_keys';
const GEMINI_SSH_PUBLIC_KEY = 'public_key';
const GEMINI_SSH_PRIVATE_KEY = 'private_key';

require_once('user.php');
require_once('ma_client.php');
require_once('header.php');
require_once('smime.php');
require_once('portal.php');

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

/* TODO put this in the service registry */
$gemini_url = 'https://genidesktop.netlab.uky.edu/stable/logon/clearinghouse/logon_entry.php';

/* HTML form input name for passing data blob to GEMINI */
$gemini_input_name = 'encoded_dict';

/* TODO put this in the service registry */
$gemini_cert_file = '/usr/share/geni-ch/sr/certs/genidesktop.netlab.uky.edu.pem';

/* Load the certificate into memory */
$gemini_cert = file_get_contents($gemini_cert_file);

/* How long to wait before auto-submitting the form post to GEMINI,
 * in seconds. Set this high if you want to inspect the page before
 * the auto-submit happens.
 */
$gemini_post_delay_seconds = 0;

/* function project_is expired
    Checks to see whether project has expired
    Returns false if not expired, true if expired
 */
function project_is_expired($proj) {
  return convert_boolean($proj[PA_PROJECT_TABLE_FIELDNAME::EXPIRED]);
}

// Store any warnings here for display at the top of the page.
$warnings = array();

if (!isset($user)) {
  $user = geni_loadUser();
}
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$cert = ma_lookup_certificate($ma_url, $user, $user->account_id);
if (is_null($cert)) {
  // warn that no cert has been generated
  $warnings[] = '<p class="warn">No certificate has been generated.'
        . ' You must <a href="kmcert.php?close=1" target="_blank">'
        . 'generate a certificate'
        . '</a>.'
        . '</p>';
}

unset($slice);
include("tool-lookupids.php");

if (isset($slice)) {
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
}

/* Sign the outbound message with the portal cert/key */
$portal = Portal::getInstance();
$portal_cert = $portal->certificate();
$portal_key = $portal->privateKey();

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
  // no longer used
  //$user_cert .= $result[MA_ARGUMENT::CERTIFICATE];
}
if (key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)) {
  $private_keys = array($result[MA_ARGUMENT::PRIVATE_KEY]);
  $gemini_info[GEMINI_USER_PRIVATE_KEYS] = $private_keys;
}
/* passphrase is always empty for now. No passphrase is specified at
 * certificate creation time.
 */
$gemini_info[GEMINI_USER_PASSPHRASE] = "";

/* Get project info of projects that have not expired and include them */
$projects_not_expired = array();
$project_ids = get_projects_for_member($pa_url, $user, $user->account_id, true);
if (count($project_ids) > 0) {
  $projects = lookup_project_details($pa_url, $user, $project_ids);
  foreach ($projects as $proj) {
    if(!project_is_expired($proj)) {
      $projects_not_expired[] = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    }
  }
}
$gemini_info[GEMINI_USER_PROJECT_NAMES] = $projects_not_expired;

/* ------------------------------------------------------------
 * Send SSH keys
 * ------------------------------------------------------------
 */
$ssh_keys = lookup_ssh_keys($ma_url, $user, $user->account_id);
$gemini_ssh_keys = array();
foreach ($ssh_keys as $ssh_key) {
  $public_key = $ssh_key[MA_SSH_KEY_TABLE_FIELDNAME::PUBLIC_KEY];
  $private_key = $ssh_key[MA_SSH_KEY_TABLE_FIELDNAME::PRIVATE_KEY];
  /* Public key is always there, pass it along. */
  $this_key[GEMINI_SSH_PUBLIC_KEY] = $public_key;
  /* Only include private key if it exists. */
  if ($private_key) {
    $this_key[GEMINI_SSH_PRIVATE_KEY] = $private_key;
  }
  $gemini_ssh_keys[] = $this_key;
}
$gemini_info[GEMINI_USER_SSH_KEYS] = $gemini_ssh_keys;

/* Convert data to JSON and encrypt it for the destination. */
$gemini_json = json_encode($gemini_info);

/* Sign the data with the portal certificate (Is that correct?) */
$gemini_signed = smime_sign_message($gemini_json, $portal_cert, $portal_key);

/* Encrypt the signed data for the GEMINI SSL certificate */
$gemini_blob = smime_encrypt($gemini_signed, $gemini_cert);

/* Finally, JSON encode the result so it can travel via JavaScript */
$gemini_blob = json_encode($gemini_blob);

/* Send the data to the browser to be auto-posted. */

show_header('GENI Portal: Slices', $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
?>

<?php
if (count($warnings)) {
  foreach ($warnings as $warning) {
    echo $warning;
  }
} else {
?>
<form id="gemini" action="<?php echo $gemini_url;?>" method="post">
<input id="blob" type="hidden" name="<?php echo $gemini_input_name;?>" value="">
</form>
<script type="text/javascript">
document.write("Redirecting to GENI Desktop...");
var blob = '<?php echo $gemini_blob;?>';
$('#blob').val(blob);
setTimeout(function() { $('#gemini').submit(); },
           <?php echo $gemini_post_delay_seconds * 1000 ?>);
</script>
<?php } ?>

<?php
include("footer.php");
?>

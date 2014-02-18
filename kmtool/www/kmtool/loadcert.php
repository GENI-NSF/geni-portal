<?php
//----------------------------------------------------------------------
// Copyright (c) 2013-2014 Raytheon BBN Technologies
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

/* Constants for the passphrase form */
$PASSPHRASE_1 = 'passphrase1';
$PASSPHRASE_2 = 'passphrase2';

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

/*
 * Now we're done with the optional setting of the passphrase, so
 * continue by retrieving the key and cert.
 */
$certificate = NULL;
$private_key = NULL;
$result = ma_lookup_certificate($ma_url, $km_signer, $member_id);
if (isset($result) && key_exists(MA_ARGUMENT::CERTIFICATE, $result)) {
  $certificate = $result[MA_ARGUMENT::CERTIFICATE];
}
if (isset($result) && key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)) {
  $private_key = $result[MA_ARGUMENT::PRIVATE_KEY];
}

/* If no certificate, we're doing to pass off to another page to help
   the user create a certificate. Put a note in the session that the
   user is in the process of loading a certificate into the signing
   tool so that the certificate creation process can redirect back to
   here.
 */
session_start();
$_SESSION['xml-signer']='loadcert.php';
session_write_close();


/*
 * We may be receiving a passphrase via POST. If so, use that passphrase
 * to encrypt the private key before proceeding.
 */
$method = $_SERVER['REQUEST_METHOD'];
error_log("Got request method '$method'");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  error_log("Processing put request");
  error_log("_REQUEST = " . print_r($_REQUEST, true));
  $passphrase1 = NULL;
  $passphrase2 = NULL;
  if (array_key_exists($PASSPHRASE_1, $_REQUEST)) {
    $passphrase1 = $_REQUEST[$PASSPHRASE_1];
  }
  if (array_key_exists($PASSPHRASE_2, $_REQUEST)) {
    $passphrase2 = $_REQUEST[$PASSPHRASE_2];
  }
  if ($passphrase1 && $passphrase2 && $passphrase1 == $passphrase2) {
    set_key_passphrase($private_key, $passphrase1, $new_key);
    error_log("new_key = $new_key");
    /*
     * NOTE: the newly protected key is *not* store in the database!
     *
     * There is concern in the experimenter support group about people
     * remembering their passphrases. We use the passphrase protected
     * key only for storing in the browser via the genilib JavaScript
     * API. If a user forgets their password, they can delete the key
     * via the signing tool, then create a new passphrase protected
     * key. This seems to be the right balance for the start of
     * speaks-for, but we may need to rethink it as we move forward.
     */
    $private_key = $new_key;
  } else {
    // XXX should really alert the user to the issue, but this works
    // for now to reprompt them if the two do not match.
    error_log("would not set passphrase. pp1=$passphrase1&pp2=$passphrase2");
  }
}


/* URL for creating a certificate. */
$server_name = $_SERVER['SERVER_NAME'];
$create_url = "https://$server_name/secure/kmcert.php?close=1";

/*
 * Does the private key have a passphrase? We can tell by
 * looking for line like "^Proc-Type: 4,ENCRYPTED$"
 *
 * To add a passphrase:
 *
 *    openssl rsa -des3 -in open.key -out encrypted.key
 */
if ($private_key) {
  $add_passphrase = true;
  $passphrase_re = "/Proc-Type: 4,ENCRYPTED/";
  $match_result = preg_match($passphrase_re, $private_key);
  if ($match_result === 0) {
    /* Key is not passphrase protected. Help the user add a passphrase. */
    error_log("No passphrase on private key");
    $add_passphrase = TRUE;
  } else if ($match_result === FALSE) {
    /* An error occured. Log it. A warning has probably already been
     * printed by preg_match.
     */
    error_log("Error checking private key against regex '$passphrase_re'");
    /* XXX What do we do from here? */
  } else if ($match_result === 1) {
    /* Success, already encrypted. */
    $add_passphrase = FALSE;
  }
}

/* XXX FIXME: put the signing tool host and URL in a config file. */
if (! isset($genilib_trusted_host)) {
  $genilib_trusted_host = 'https://ch.geni.net:8444';
  if (array_key_exists('SERVER_NAME', $_SERVER)) {
    $server_name = $_SERVER['SERVER_NAME'];
    $portal_prefix = 'portal-';
    // Handle development hosts via their naming conventions.
    // Currently named "portal-XX" and "ch-XX" where XX are the
    // developer's initials.
    if (strpos($server_name, $portal_prefix) === 0) {
      // server name starts with 'portal-'. Replace 'portal-' with 'ch-'
      // for name of ch host.
      $ch_name = 'ch-' . substr($server_name, strlen($portal_prefix));
      $genilib_trusted_host = 'https://' . $ch_name . ':8444';
    }
  }
}
if (! isset($genilib_trusted_path)) {
  $genilib_trusted_path = '/xml-signer/index.html';
}
$auth_svc_js = $genilib_trusted_host . '/xml-signer/geni-auth.js';

if (is_null($certificate)) {
  /* No certificate so redirect to the create/download page. */
  header("Location: $create_url");
  exit;
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
<script src="<?php echo $auth_svc_js;?>">
</script>
<script type="text/plain" id="certificate"><?php echo $certificate;?></script>
<script type="text/plain" id="privatekey"><?php echo $private_key;?></script>
<script type="text/javascript">
/* Override the genilib defaults for our trusted host. */
genilib.trustedHost = '<?php echo $genilib_trusted_host;?>';
genilib.trustedPath = '<?php echo $genilib_trusted_path;?>';
</script>
<script type="text/javascript" src="loadcert.js"></script>

<?php if (is_null($private_key) && $certificate) { ?>
<h2>Instructions</h2>
<p>
You must paste your certificate and your private key into the signing tool.
You can <a href="#" onClick="window.open('<?php print $create_url?>')">
download your certificate</a> if you do not have a copy already. You
must use the private key that you used to create your certificate.
</p>
<form onsubmit="return false;">
   <input id="showtool"
          type="submit"
          value="Show signing tool"/>
</form>
<?php } else if (is_null($certificate)) { ?>
<p>
You do not have a GENI certificate yet. Before you can use the signing
and authorization tool you must
<a href="<?php print $create_url?>">create a certificate</a>.
<?php } else if ($add_passphrase) { ?>
<h2>Add a passphrase</h2>
<p>
Your private key does not have a passphrase, but requires one to work
with the signing tool. Please add a passphrase to your private key below.
</p>
<p>
<i>Note: The GPO Clearinghouse does not keep a copy of your passphrase.
   You will need to remember this passphrase in order to use the signing
   tool now and in the future. Please take steps now to record or
   remember this passphrase.</i>
</p>
<form method="post">
   <label class="input">Passphrase:
   <input name="<?php echo $PASSPHRASE_1;?>" type="password"/></label>
   <br/>
   <label class="input">Re-type passphrase:
   <input name="<?php echo $PASSPHRASE_2;?>" type="password"/></label>
   <br/>
   <button type="submit">Set passphrase</input>
</form>
<?php } else { ?>
<form onsubmit="return false;">
   <input id="loadcert"
          type="submit"
          value="Load my certificate"/>
</form>
<?php } /* end if ($add_passphrase) */ ?>
<br/>
<?php
  include("kmfooter.php");
?>

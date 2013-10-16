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

/*
 * Request a speaks-for credential from the user.
 */
require_once 'header.php';
require_once 'portal.php';
require_once 'cert_utils.php';
require_once 'user.php';
require_once 'db-util.php';

$portal = Portal::getInstance();
$toolcert = $portal->certificate();
$toolurn = pem_cert_geni_urn($toolcert);

/* XXX FIXME: put the signing tool host and URL in a config file. */
if (! isset($genilib_trusted_host)) {
  $genilib_trusted_host = 'https://ch.geni.net';
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
      $genilib_trusted_host = 'https://' . $ch_name;
    }
  }
}
if (! isset($genilib_trusted_path)) {
  $genilib_trusted_path = '/xml-signer/index.html';
}
$auth_svc_js = $genilib_trusted_host . '/xml-signer/geni-auth.js';





$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$cred = fetch_speaks_for($user, $expires);
if ($cred === false) {
  // A database error occurred
  $cred_info = '<i>DB Error fetching credential</i><br/>';
} elseif (is_null($cred)) {
  $cred_info = '<i>No credential in DB</i><br/>';
} else {
  $cred_info = "<i>Credential expires $expires</i><br/>";
}


/*------------------------------------------------------------
 * Page display starts here
 *------------------------------------------------------------
 */
show_header('GENI Portal: Authorization');
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js">
</script>
<script src="<?php echo $auth_svc_js;?>"></script>
<script type="text/plain" id="toolurn"><?php echo $toolurn;?></script>
<script type="text/plain" id="toolcert"><?php echo $toolcert;?></script>
<script type="text/javascript">
/* Override the genilib defaults for our trusted host. */
genilib.trustedHost = '<?php echo $genilib_trusted_host;?>';
genilib.trustedPath = '<?php echo $genilib_trusted_path;?>';
</script>
<script type="text/javascript" src="speaks-for.js"></script>

<?php
  /*
   * Note the 'onsubmit="return false;"' attribute on the form. It's
   * important that the form not actually submit itself, otherwise the
   * inter-window communication can't happen because the parent window
   * has disappeared.
   */
?>
<div>
<?php echo $cred_info; ?>
</div>

<form onsubmit="return false;">
   <input id="authorize"
          type="submit"
          value="Click here to authorize"/>
</form>
<?php
  /* This div is for debugging only. */
?>
<div>
  <pre id="cred"/>
</div>

<?php
include 'footer.php';
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2013-2015 Raytheon BBN Technologies
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
$ma_url = 'https://portal.geni.net/secure/loadcert.php';
$ma_name = 'GPO Member Authority';

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

/* Establish a local MA if appropriate. */
if (array_key_exists('SERVER_NAME', $_SERVER)) {
  $server_name = $_SERVER['SERVER_NAME'];
  if (strpos($server_name, 'gpolab.bbn.com')) {
    /* This is a GPO lab host, so configure a local MA. */
    $ma_url = "https://$server_name/secure/loadcert.php";
    $ma_name = "$server_name Member Authority";
  }
}


$key_token = NULL;
if (array_key_exists('AUTH_TYPE', $_SERVER)
    && strcmp($_SERVER['AUTH_TYPE'], 'shibboleth') == 0) {
  /* Shibboleth authentication is present. Look for EPPN. */
  if (array_key_exists('eppn', $_SERVER)) {
    /* Our key token is the EPPN with shibboleth authentication. */
    $key_token = $_SERVER['eppn'];
  }
}

/* Bail out because no key token was found. */
if (is_null($key_token)) {
  header('Unauthorized', true, 401);
  exit();
}

$cred = fetch_speaks_for($key_token, $expires);
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
show_header('GENI Portal: Authorization', '', FALSE);
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js">
</script>
<script src="<?php echo $auth_svc_js;?>"></script>
<script type="text/plain" id="toolurn"><?php echo $toolurn;?></script>
<script type="text/plain" id="toolcert"><?php echo $toolcert;?></script>
<script type="text/plain" id="ma_url"><?php echo $ma_url;?></script>
<script type="text/plain" id="ma_name"><?php echo $ma_name;?></script>
<script type="text/javascript">
/* Override the genilib defaults for our trusted host. */
genilib.trustedHost = '<?php echo $genilib_trusted_host;?>';
genilib.trustedPath = '<?php echo $genilib_trusted_path;?>';
</script>
<script type="text/javascript" src="speaks-for.js"></script>
<link rel="stylesheet" type="text/css" href="speaks-for.css" />

<?php
  /*
   * Note the 'onsubmit="return false;"' attribute on the form. It's
   * important that the form not actually submit itself, otherwise the
   * inter-window communication can't happen because the parent window
   * has disappeared.
   */
?>
<div hidden>
<?php echo $cred_info; ?>
</div>

<h2>Portal Authorization</h2>
  <p>
  The GENI Experimenter Portal requires your authorization in order
  to act on your behalf. This requires that you sign a credential
  authorizing the portal to speak for you when interacting with GENI
  services.
  </p>
  <p>
  Without authorization the portal can do very little to help you use
  GENI.
  </p>

<form onsubmit="return false;">
   <center>
   <input id="authorize"
          type="submit"
          value="Authorize the portal"/>
   </center>
</form>

  <div class="windowOpen hidden">
    <div class="alert alert-success">
      <h5 class="panel-title">Please continue in popup window.</h5>
    </div>
  </div>


<?php
  /* This div is for debugging only. */
?>
<div>
  <pre id="cred" hidden/>
</div>

<?php
include 'footer.php';
?>

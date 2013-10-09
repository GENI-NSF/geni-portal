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

/*
 * XXX FIXME: put the authorization service URL in a config file.
 */
$genilib_trusted_host = 'https://ch.geni.net';
$genilib_trusted_path = '/xml-signer/index.html';
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

<script src="<?php echo $auth_svc_js;?>"></script>
<script type="text/plain" id="toolcert"><?php echo $toolcert;?></script>
<script>
var portal = {};
portal.authorize = function()
{
  var tool_urn = '<?php echo $toolurn;?>';
  var tool_cert = document.getElementById('toolcert').innerHTML;
  genilib.trustedHost = '<?php echo $genilib_trusted_host;?>';
  genilib.trustedPath = '<?php echo $genilib_trusted_path;?>';
  genilib.authorize(tool_urn, tool_cert, portal.authZResponse);
  return false;
}
portal.authZResponse = function(speaks_for_cred)
{
  // Called if the user authorizes us in the signing tool
  alert('Response available from genilib.authorize');
  $("#cred").text(speaks_for_cred).html();
  var jqxhr = $.post('speaks-for-upload.php', speaks_for_cred);
  jqxhr.done(function(data, textStatus, jqxhr) {
      alert('got result: ' + textStatus);
    })
  .fail(function(data, textStatus, jqxhr) {
      alert('got fail result: ' + textStatus);
    });
}
portal.initialize = function()
{
  /* Add a click callback to the "authorize" button. */
  $('#authorize').click(portal.authorize);
}
$(document).ready(portal.initialize);
</script>

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

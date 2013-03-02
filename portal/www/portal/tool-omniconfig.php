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
?>

<?php
require_once("user.php");
require_once("header.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

// Does the user have an outside certificate?
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
$has_certificate = ! is_null($result);
// FIXME: hardcoded paths
$create_url = "https://" . $_SERVER['SERVER_NAME'] . "/secure/kmcert.php?close=1";
$download_url = "https://" . $_SERVER['SERVER_NAME'] . "/secure/kmcert.php?close=1";

?>


<h1>Omni command line resource reservation tool</h1>
<div id="omni22">
<i>Note: these instructions are for omni 2.2 or higher.
<a id="want21" href="#">See instructions for older versions</a></i>
<p>
Download and use a template omni_config file for use with the
<a href="http://trac.gpolab.bbn.com/gcf/wiki">Omni</a> command line resource
reservation tool.
<br/>
<ol>
  <li>Download this <a href='portal_omni_config.php'>omni_config</a> and save it to a file named <code>portal_omni_config</code>.</li>


<?php
if ($has_certificate) {
?>
  <li> <a href="<?php print $download_url; ?>" target="_blank">Download your certificate</a>, noting the path.</li>
<?php
} else {
?>
  <li> <a href="<?php print $download_url; ?>" target="_blank">Create and download your certificate</a>, noting the path.</li>
<?php
}
?>


  <li> Edit the <code>portal_omni_config</code> to correct:
    <ol>
      <li>the certificate path,</li>
      <li>the path to the SSL private key used to generate your certificate, and </li>
      <li> the path to your SSH public key to use for node logon.</li>
    </ol>
  </li>
  <li> When running omni:
    <ol>
      <li> Specify the path to the <code>omni_config</code>, specify the project name, and the slice name or URN.  For example:
        <ul><li><code>omni.py -c portal_omni_config --project &lt;project name&gt; sliverstatus &lt;slice name or URN&gt;</code></li></ul>
      </li>
    </ol>
  </li>
</ol>
<p/>
</div>

<div id="omni21">
<i>Note: these instructions are for omni 2.1 or lower.
<a id="want22" href="#">See instructions for newer versions</a></i>
<p>
Download and use a template omni_config file for use with the
<a href="http://trac.gpolab.bbn.com/gcf/wiki">Omni</a> command line resource
reservation tool.
<br/>
<ol>
  <li>Download this <a href='portal_omni_config.php?version=2.1'>omni_config</a> and save it to a file named <code>portal_omni_config</code>.</li>


<?php
if ($has_certificate) {
?>
  <li> <a href="<?php print $download_url; ?>" target="_blank">Download your certificate</a>, noting the path.</li>
<?php
} else {
?>
  <li> <a href="<?php print $download_url; ?>" target="_blank">Create and download your certificate</a>, noting the path.</li>
<?php
}
?>


  <li> Edit the <code>portal_omni_config</code> to correct:
    <ol>
      <li>the certificate path,</li>
      <li>the path to the SSL private key used to generate your certificate, and </li>
      <li> the path to your SSH public key to use for node logon.</li>
    </ol>
  </li>
  <li> When running omni:
    <ol>
      <li> Specify the path to the <code>omni_config</code>, specify the project name, and the full slice URN.  For example:
        <ul><li><code>omni.py -c portal_omni_config --project &lt;project name&gt; sliverstatus &lt;slice URN&gt;</code></li></ul>
      </li>
      <li> Use the full slice URN when naming your slice, not just the slice name.</li>
    </ol>
  </li>
</ol>
<p/>
</div>
<script>
$('#want21').click(function() {
  $('#omni21').show('slow');
  $('#omni22').hide('fast');
});
$('#want22').click(function() {
  $('#omni22').show('slow');
  $('#omni21').hide('fast');
});
/* hide omni 2.1 by default */
$('#omni21').hide();
</script>


<?php
include("footer.php");
?>

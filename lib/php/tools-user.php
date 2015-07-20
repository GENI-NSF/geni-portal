<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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
require_once("cert_utils.php");
require_once("rq_client.php");
require_once("settings.php");
require_once("user-preferences.php");

?>

<?php
function js_delete_ssh_key() {
  /*
   *    * A javascript function to confirm the delete.
   */
  echo <<< END
  <script type="text/javascript" src="tools-user.js"></script>
  <script type="text/javascript">
function deleteSshKey(dest){
  var r=confirm("Are you sure you want to delete this ssh key?");
    if (r==true) {
      window.location = dest;
  }
}
</script>
END;
}
?>

<h1>Profile</h1>

  <div id='tablist'>
		<ul class='tabs'>
			<li><a href='#accountsummary'>Account Summary</a></li>
			<li><a href='#ssh'>SSH Keys</a></li>
			<li><a href='#ssl'>SSL</a></li>
			<li><a href='#omni'>Configure <code>omni</code></a></li>
			<li><a href='#rspecs' title="Resource Specifications">RSpecs</a></li>
			<li><a href='#tools'>Tools</a></li>
			<li><a href='#outstandingrequests'>Outstanding Requests</a></li>
      <li style="border-right: none"><a href='#preferences'>Portal Preferences</a></li>
		</ul>
  </div>
		
<?php

  // BEGIN the tabContent class
  // this makes a fixed height box with scrolling for overflow
  echo "<div class='tabContent'>";

?>

<?php
/*----------------------------------------------------------------------
 * SSH key management
 *----------------------------------------------------------------------
 */
// BEGIN SSH tab
echo "<div id='ssh'>";
print "<h2>SSH Keys</h2>\n";
$keys = $user->sshKeys();

$disable_ssh_keys = "";
if ($in_lockdown_mode) $disable_ssh_keys = "disabled";

if (count($keys) == 0)
  {
    // No ssh keys are present.
    print "<p>No SSH keys have been uploaded. ";
    print "SSH keys are required to log in to reserved compute resources.
      You have two options:</p>\n";
    $generate_btn = "<button $disable_ssh_keys
      onClick=\"window.location='generatesshkey.php'\">
      generate and download an SSH keypair</button>";
    print '<ol type="i">';
    print "<li>$generate_btn";
    print "The private key (but not the passphrase that protects it) might
      be shared with other GENI entities. If you choose this option do not
      reuse this key pair outside of GENI, or</li>\n";
    print "<li><button $disable_ssh_keys
      onClick=\"window.location='uploadsshkey.php'\">
      upload an SSH public key</button>, if you have one you want to use.
      If you only choose this option then some GENI tools might not
      work properly</p>\n";
    print "</ol>\n";
    print "<p>If you're not sure what to do, choose $generate_btn</p>\n";

  }
else
  {
    $download_pkey_url = relative_url('downloadsshkey.php?');
    $download_putty_url = relative_url('downloadputtykey.php?');
    $download_public_key_url = relative_url('downloadsshpublickey.php?');
    $edit_sshkey_url = relative_url('sshkeyedit.php?');
    $delete_sshkey_url = relative_url('deletesshkey.php?');
    js_delete_ssh_key();  // javascript for delete key confirmation
    print "\n<div class='tablecontainer'><table>\n";
    print "<tr><th>Name</th><th>Description</th><th>Public Key</th><th>Private Key</th>"
      . "<th>PuTTY</th>"
      . "<th>Edit</th><th>Delete</th></tr>\n";
    foreach ($keys as $key) {
      // generate key's fingerprint
        $fingerprint_key = NULL;
        $fingerprint_key = $key['public_key'];
        // write key to temp file
        $fingerprint_key_filename = tempnam(sys_get_temp_dir(), 'fingerprint');
        $fingerprint_key_file = fopen($fingerprint_key_filename, "w");
        fwrite($fingerprint_key_file, $fingerprint_key);
        fclose($fingerprint_key_file);
        // get fingerprint
        $cmd_array = array('/usr/bin/ssh-keygen',
                         '-lf',
                         $fingerprint_key_filename,
                         );
        $command = implode(" ", $cmd_array);
        $result = exec($command, $output, $status);
        $fingerprint_array = explode(' ', $result);
        $fingerprint = $fingerprint_array[1]; // store fingerprint
        unlink($fingerprint_key_filename);
      
      $args['id'] = $key['id'];
      $query = http_build_query($args);
      if (is_null($key['private_key'])) {
        $pkey_cell = 'N/A';
	$putty_cell = "N/A";
      } else {
        $pkey_cell = ("<button onClick=\"window.location='"
                . $download_pkey_url . $query
                . "'\">Download Private Key</button>");
        $putty_cell = ("<button onClick=\"window.location='"
                . $download_putty_url . $query
                . "'\">Download PuTTY Key</button>");
      }
      $public_key_download_cell = ("<button $disable_ssh_keys onClick=\"window.location='"
                . $download_public_key_url . $query
                . "'\">Download Public Key</button>");
      $edit_cell = ("<button $disable_ssh_keys onClick=\"window.location='"
                . $edit_sshkey_url . $query
                . "'\">Edit</button>");
      $delete_cell = ("<button $disable_ssh_keys onClick=\"deleteSshKey('"
                . $delete_sshkey_url . $query
                . "')\">Delete</button>");
      print "<tr>"
      . "<td>" . htmlentities($key['filename']) . "<br><small>" . $fingerprint . "</small>" . "</td>"
      . "<td>" . htmlentities($key['description']) . "</td>"
      . '<td>' . $public_key_download_cell . '</td>'
      . '<td>' . $pkey_cell . '</td>'
      . '<td>' . $putty_cell . '</td>'
      . '<td>' . $edit_cell . '</td>'
      . '<td>' . $delete_cell . '</td>'
      . "</tr>\n";
    }
    print "</table></div>\n";
    print "<p>On Linux and Mac systems and for most Windows SSH clients (not PuTTY), do:";
    print "<ul>";
    print "<li>Download your private key.</li>";
    print "<li>On Windows, just point your SSH client (not PuTTY) to the downloaded private key.</li>";
    print "<li>On Linux and Mac, open a terminal.</li>";
    print "<ul>";
    print "<li>Store your key under ~/.ssh/ :";
    print "<ul>";
    print "<li>If the directory does not exist, create it:</li>";
    print "<pre>mkdir ~/.ssh</pre>";
    print "<li>Move the key to ~/.ssh/ :</li>";
    print "<pre>mv ~/Downloads/id_geni_ssh_rsa ~/.ssh/</pre>";
    print "<li>Change the file permissions:";
    print "<pre>chmod 0600 ~/.ssh/id_geni_ssh_rsa</pre>";
    print "</ul>";
    print "<li>Your SSH command will be something like:</li>";
    print "<pre>ssh -i ~/.ssh/id_geni_ssh_rsa [username]@[hostname]</pre>";
    print "</ul>";
    print "</ul>";
    print "<p>";
    print "<p>For PuTTY users:";
    print "<ul>";
    print "<li>Download PuTTY key.";
    print "<li>In PuTTY, create a new session that uses the 'username', 'hostname' and 'port' for the resources you have reserved.</li>";
    print "<li>Under the authentication menu, point the key field to the downloaded PuTTY key file.</li>";
    print "</ul>";
    /*
    print "<p><b>Note</b>: You will need your SSH private key on your local machine. </p>\n<p>If you generated your SSH keypair on this portal and have not already done so, be sure to:</p>
     <ol>
     <li>Download your SSH key.</li>
     <li>After you download your key, be sure to set local permissions on that file appropriately. On Linux and Mac, do <pre>chmod 0600 [path-to-SSH-private-key]</pre></li>
     <li>When you invoke SSH to log in to reserved resources, you will need to remember the path to that file.</li>
     <li>Your SSH command will be something like: <pre>ssh -i path-to-SSH-key-you-downloaded [username]@[hostname]</pre>\n";
    print "</ol>\n";
    */
    print "<p><button $disable_ssh_keys onClick=\"window.location='uploadsshkey.php'\">Upload another SSH public key</button></p>\n";
  }

// END SSH tab
echo "</div>";

/*----------------------------------------------------------------------
 * SSL Cert
 *----------------------------------------------------------------------
 */
// BEGIN SSL tab
echo "<div id='ssl'>";
print "<h2>SSL Certificate</h2>\n";
print "<p>";
if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!'");
  }
}

$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
$expiration_key = 'expiration';
$has_certificate = False;
$has_key = False;
$expired = False;
$expiration = NULL;
if (! is_null($result)) {
  $has_certificate = True;
  $has_key = array_key_exists(MA_ARGUMENT::PRIVATE_KEY, $result);
  if (array_key_exists($expiration_key, $result)) {
    $expiration = $result[$expiration_key];
    $now = new DateTime('now', new DateTimeZone("UTC"));
    $expired = ($expiration < $now);
  }
}

$kmcert_url = "kmcert.php?close=1";
$button1_label = 'Create an SSL certificate';
if (! $has_certificate) {
  /* No certificate, so show the create button. */
  print "<button onClick=\"window.open('$kmcert_url')\">";
  print $button1_label;
  print "</button>";
  print "</p>";
} else if ($expired) {
  /* Have an expired certificate, just renew it. */
  print 'Your SSL certificate has expired. Please';
  print ' <a href="kmcert.php?close=1&renew=1" target="_blank">';
  print 'renew your SSL certificate</a> now';
  print '</p>';
} else {
  /* Have a current certificate */
  if ($has_key) {
    $button1_label = 'Download your SSL certificate and key';
  } else if ($has_certificate) {
    $button1_label = 'Download your SSL certificate';
  }
  print "<button onClick=\"window.open('$kmcert_url')\">";
  print $button1_label;
  print "</button>";
  print "</p>";

  // Display a renew link
  print '<p>';
  if ($expiration) {
    print 'Your SSL certificate expires on ';
    print dateUIFormat($expiration);
    print '.';
  }
  print ' You can <a href="kmcert.php?close=1&renew=1" target="_blank">';
  print 'renew your SSL certificate</a> at any time.';
  print '</p>';
}

// END SSL tab
echo "</div>";

// BEGIN outstand requests tab
echo "<div id='outstandingrequests'>";
print "<h2>Outstanding Requests</h2>";

// Show outstanding requests BY this user
if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
    error_log("Found no SA in SR!'");
  }
}
if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!'");
  }
}

// This next block of code pretends to handle requests to join a slice, but we don't do that
// It also claims to handle profile modification requests. But those are handled separately.

// FIXME: Show outstanding project lead requests. See code in tools-admin.php

$preqs = get_requests_by_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null, RQ_REQUEST_STATUS::PENDING);
//$sreqs = get_requests_by_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::SLICE, null, RQ_REQUEST_STATUS::PENDING);
//$reqs = array_merge($preqs, $sreqs);
$reqs = $preqs;
if (isset($reqs) && count($reqs) > 0) {
  print "Found " . count($reqs) . " outstanding request(s) by you:<br/>\n";
  print "<div class='tablecontainer'><table>\n";
  // Could add the lead and purpose?
  print "<tr><th>Request Type</th><th>Project</th><th>Request Created</th><th>Request Reason</th><th>Cancel Request?</th></tr>\n";
  $REQ_TYPE_NAMES = array();
  $REQ_TYPE_NAMES[] = 'Join';
  $REQ_TYPE_NAMES[] = 'Update Attributes';
  foreach ($reqs as $request) {
    $name = "";
    //error_log(print_r($request, true));
    $typestr = $REQ_TYPE_NAMES[$request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE]] . " " . $CS_CONTEXT_TYPE_NAME[$request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE]];
    if ($request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE] == CS_CONTEXT_TYPE::PROJECT) {
      //error_log("looking up project " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);
      $project = lookup_project($sa_url, $user, $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);
      $name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $cancel_url="cancel-join-project.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
      //    } elseif ($request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE] == CS_CONTEXT_TYPE::SLICE) {
      //      $slice = lookup_slice($sa_url, $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);
      //      $name = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
      //      $cancel_url="cancel-join-slice.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
//    } else {
//      $name = "";
//      $cancel_url="cancel-account-mod.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
    }

    $cancel_button = "<button style=\"\" onClick=\"window.location='" . $cancel_url . "'\"><b>Cancel Request</b></button>";
    $reason = $request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TEXT];
    $req_date_db = $request[RQ_REQUEST_TABLE_FIELDNAME::CREATION_TIMESTAMP];
    $req_date = dateUIFormat($req_date_db);
    print "<tr><td>$typestr</td><td>$name</td><td>$req_date</td><td>$reason</td><td>$cancel_button</td></tr>\n";
  }
  print "</table></div>\n";
  print "<br/>\n";
} else {
  print "<p><i>No outstanding requests to join projects.</i></p>\n";
}

// END outstanding requests tab
echo "</div>";

// BEGIN account summary tab
echo "<div id='accountsummary'>";
$disable_account_details = "";
if($in_lockdown_mode) {
  $disable_account_details = "disabled";
}

/* Determine OpenID URL */
$protocol = "http";
if (array_key_exists('HTTPS', $_SERVER)) {
  $protocol = "https";
}
$host  = $_SERVER['SERVER_NAME'];
$openid_url = ("$protocol://$host/server/server.php/idpage?user="
               . $user->username);


print "<h2>Account Summary</h2>\n";
// Show username, email, affiliation, IdP, urn, prettyName, maybe project count and slice count
// Put this in a nice table
print "<div class='tablecontainer'><table>\n";
print "<tr><th>Name</th><td>" . $user->prettyName() . "</td></tr>\n";
print "<tr><th>Email</th><td>" . $user->email() . "</td></tr>\n";
print "<tr><th>GENI Username</th><td>" . $user->username . "</td></tr>\n";
print "<tr><th>GENI OpenID URL</th><td>" . $openid_url . "</td></tr>\n";
print "<tr><th>GENI URN</th><td>" . $user->urn() . "</td></tr>\n";
print "<tr><th>Home Institution</th><td>" . $user->idp_url . "</td></tr>\n";
print "<tr><th>Affiliation</th><td>" . $user->affiliation . "</td></tr>\n";
if ($user->phone() != "")
  print "<tr><th>Telephone Number</th><td>" . $user->phone() . "</td></tr>\n";
// FIXME: Project count? Slice count?
// FIXME: Other attributes?
// FIXME: Permissions
print "</table></div>\n";
print "<p><button $disable_account_details onClick=\"window.location='modify.php'\">Modify user supplied account details </button> (e.g. to become a Project Lead).</p>";

$sfcred = $user->speaksForCred();
if ($sfcred) {
  $sf_expires = $sfcred->expires();
?>
  <p>Portal authorization expires: <?php echo $sf_expires; ?><br/>
    <a onclick='deauthorizePortal()'>Deauthorize the portal</a>
  </p>
<?php
} else if ($user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS,
                            CS_CONTEXT_TYPE::MEMBER, null)) {
  /* If user is an operator... show the authorize link. */
  /* This is really just for debugging/development at this time. */
?>
  <p><a href='speaks-for.php'>Authorize the portal</a></p>
<?php
}

// END account summary tab
echo "</div>";

//print "<h1>My Stuff</h1>\n";

// BEGIN rspecs tab
echo "<div id='rspecs'>";
/*----------------------------------------------------------------------
 * RSpecs
 *----------------------------------------------------------------------
 */
if (!$in_lockdown_mode) {
  include("tool-rspecs.php");
}
// END rspecs tab
echo "</div>";
?>


<?php
/*----------------------------------------------------------------------
 * SSL Cert management
 *----------------------------------------------------------------------
 */

// BEGIN omni tab
echo "<div id='omni'>";

// Does the user have an outside certificate?
$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
$has_certificate = ! is_null($result);
// FIXME: hardcoded paths
$create_url = "https://" . $_SERVER['SERVER_NAME'] . "/secure/kmcert.php?close=1";
$download_url = "https://" . $_SERVER['SERVER_NAME'] . "/secure/kmcert.php?close=1";
?>

<h2>Configure <code>omni</code></h2>
<p><a href='http://trac.gpolab.bbn.com/gcf/wiki/Omni'><code>omni</code></a> is a command line tool intended for experienced users. 
</p>

<h3>Option 1: Automatic <code>omni</code> configuration</h3>
<p>To automatically configure <code>omni</code>, use the <a href='http://trac.gpolab.bbn.com/gcf/wiki/OmniConfigure/Automatic'><code>omni-configure</code></a> script distributed with <code>omni</code> as described below.</p>
  <ol>
    <li>
In order to use <code>omni</code> you will need to generate an SSL certificate. <br/>
<?php if (!$has_certificate): ?>

<button onClick="window.open('<?php print $create_url?>')">Generate an SSL certificate</button>.
<?php else: ?>
<b>Good! You have already generated an SSL certificate.</b>
<?php endif; ?>

    </li>
    <li>Download your customized <code>omni</code> configuration data and save it in the default location (<code>~/Downloads/omni.bundle</code>):<br/>
    		 <button onClick="window.location='omni-bundle.php'">Download your omni data</button>
    </li>
    <li>Run the following command in a terminal to generate a configuration file for omni (<code>omni_config</code>): <pre>omni-configure</pre></li>
    <li>Test your setup by running the following command in a terminal: <pre>omni -a gpo-ig getversion</pre>
    The output should look similar to this <a href='http://trac.gpolab.bbn.com/gcf/attachment/wiki/OmniConfigure/Automatic/getversion.out'>example output</a>.
</li>
  </ol>

  <table id='tip'>
    <tr>
       <td rowspan=3><img id='tipimg' src="/images/Symbols-Tips-icon-clear.png" width="75" height="75" alt="Tip"></td>
       <td><b>Tip</b> Make sure you are running <b>omni 2.8.1</b> or newer.</td>
    </tr>
       <tr><td>To determine the version of an existing <code>omni</code> installation, run:
	            <pre>omni --version</pre>
       </td></tr>
        <tr><td>If necessary, <a href="http://trac.gpolab.bbn.com/gcf/wiki#GettingStarted" target='_blank'>download</a> and <a href="http://trac.gpolab.bbn.com/gcf/wiki/QuickStart" target='_blank'>install</a> the latest version of <code>omni</code>.</td></tr>

  </table>

<p>Complete <a href='http://trac.gpolab.bbn.com/gcf/wiki/OmniConfigure/Automatic'><code>omni-configure</code> instructions</a> are available.</p>

<h3>Option 2: Manual <code>omni</code> configuration</h3>
<p><a href='tool-omniconfig.php'>Download and customize a template <code>omni</code> configuration file</a>.</p>

<?php
// END omni tab
echo "</div>";
?>

<!--
<table>
<tr><th>Tool</th><th>Description</th><th>Configuration File</th></tr>
<tr>
  <td><a href='http://trac.gpolab.bbn.com/gcf/wiki'>Omni</a></td>
  <td>command line resource allocation tool</td>
  <td><a href='tool-omniconfig.php'>Get omni_config</a></td>
</tr>
<tr>
  <td>omni_configure</td>
  <td>omni configuration tool</td>
  <td><a href='omni-bundle.php'>Get omni-bundle.zip</a></td>
</tr>
</table>
-->
<?php
// BEGIN tools tab
echo "<div id='tools'>";

$disable_authorize_tools = "";
if($in_lockdown_mode) {
  $disable_authorize_tools = "disabled";
}

/* Only show the tools authorization page if we're not in full
 * speaks-for mode.
 */
if (!$speaks_for_enabled) {
  print "<p><button $disable_authorize_tools onClick=\"window.location='kmhome.php'\">Authorize or De-authorize tools</button> to act on your behalf.</p>";
}

print '<h2>iRODS</h2>';
$irodsdisabled="disabled";
if (! isset($disable_irods) or $user->hasAttribute('enable_irods'))
  $irodsdisabled = "";
print "<p><button onClick=\"window.location='irods.php'\" $irodsdisabled><b>Create iRODS Account</b></button></p>\n";
// END tools tab
echo "</div>";

  // END the tabContent class
  echo "</div>";

?>
<script type="text/javascript">
  $(document).ready(function(){
    $("#showmapcheck").change(function(){
      $("#saveprefs").prop("disabled", false);
    });
  });
  function save_preferences(user_urn) {
    params = {user_urn: user_urn, show_map: $('#showmapcheck').prop("checked")};
    $.post("do-update-user-preferences", params, function(data){
      alert(data);
      $("#saveprefs").prop("disabled", true);
    });
  }
</script>

<?php 
echo "<div id='#preferences'>";
echo "<h2>Portal preferences</h2>";
$checked = '';
if(get_preference($user->urn(), 'show_map') == "true"){
  $checked = "checked";
} 
echo "Show map on homepage? <input id='showmapcheck' type='checkbox' value='showmap' $checked/><br><br>";
echo "<button disabled onclick='save_preferences(\"{$user->urn()}\")' id='saveprefs'>Save preferences</button>";
echo "</div>";

include "tabs.js"; 

?>

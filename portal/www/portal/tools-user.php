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
require_once("cert_utils.php");
require_once("rq_client.php");
require_once("settings.php");
?>

<?php
function js_delete_ssh_key() {
  /*
   *    * A javascript function to confirm the delete.
   */
  echo <<< END
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

<?php include "tabs.js"; ?>

  <div id='tablist'>
		<ul class='tabs'>
			<li><a href='#accountsummary'>Account Summary</a></li>
			<li><a href='#ssh'>SSH Keys</a></li>
			<li><a href='#ssl'>SSL</a></li>
			<li><a href='#omni'>Configure <code>omni</code></a></li>
			<li><a href='#rspecs' title="Resource Specifications">RSpecs</a></li>
			<li><a href='#tools'>Tools</a></li>
			<li style="border-right: none"><a href='#outstandingrequests'>Outstanding Requests</a></li>
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
    print "SSH keys are required to log in to reserved compute resources.</p>\n";
    print "<p>You can <button $disable_ssh_keys onClick=\"window.location='generatesshkey.php'\">generate and download an SSH keypair</button> ";
    print "or <button $disable_ssh_keys onClick=\"window.location='uploadsshkey.php'\">upload an SSH public key</button>, if you have one you want to use.</p>\n";
    print "<p>If you're not sure what to do, choose 'Generate'.</p>\n";

  }
else
  {
    $download_pkey_url = relative_url('downloadsshkey.php?');
    $download_public_key_url = relative_url('downloadsshpublickey.php?');
    $edit_sshkey_url = relative_url('sshkeyedit.php?');
    $delete_sshkey_url = relative_url('deletesshkey.php?');
    js_delete_ssh_key();  // javascript for delete key confirmation
    print "\n<table>\n";
    print "<tr><th>Name</th><th>Description</th><th>Public Key</th><th>Private Key</th>"
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
      } else {
        $pkey_cell = ("<button onClick=\"window.location='"
                . $download_pkey_url . $query
                . "'\">Download Private Key</button>");
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
      . '<td>' . $edit_cell . '</td>'
      . '<td>' . $delete_cell . '</td>'
      . "</tr>\n";
    }
    print "</table>\n";
    print "<p><b>Note</b>: You will need your SSH private key on your local machine. </p>\n<p>If you generated your SSH keypair on this portal and have not already done so, be sure to:</p>
     <ol>
     <li>Download your SSH key.</li>
     <li>After you download your key, be sure to set local permissions on that file appropriately. On Linux and Mac, do <pre>chmod 0600 [path-to-SSH-private-key]</pre></li>
     <li>When you invoke SSH to log in to reserved resources, you will need to remember the path to that file.</li>
     <li>Your SSH command will be something like: <pre>ssh -i path-to-SSH-key-you-downloaded [username]@[hostname]</pre>\n";
    print "</ol>\n";
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
$has_certificate = False;
$has_key = False;
if (! is_null($result)) {
  $has_certificate = True;
  $has_key = array_key_exists(MA_ARGUMENT::PRIVATE_KEY, $result);
}

$kmcert_url = "kmcert.php?close=1";
print "<button onClick=\"window.open('$kmcert_url')\">";
if (! $has_certificate) {
  print "Generate an SSL certificate";
} else {
  if ($has_key) {
    print "Download your Portal generated SSL certificate and key";
  } else {
    print "Download your Portal signed SSL certificate";
  }
}
print "</button>";
print "</p>";
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

// FIXME: Also show rejected requests?
$preqs = get_requests_by_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null, RQ_REQUEST_STATUS::PENDING);
$sreqs = get_requests_by_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::SLICE, null, RQ_REQUEST_STATUS::PENDING);
$reqs = array_merge($preqs, $sreqs);
if (isset($reqs) && count($reqs) > 0) {
  print "Found " . count($reqs) . " outstanding request(s) by you:<br/>\n";
  print "<table>\n";
  // Could add the lead and purpose?
  print "<tr><th>Request Type</th><th>Project/Slice</th><th>Request Created</th><th>Request Reason</th><th>Cancel Request?</th></tr>\n";
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
    } elseif ($request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE] == CS_CONTEXT_TYPE::SLICE) {
      $slice = lookup_slice($sa_url, $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);
      $name = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
      $cancel_url="cancel-join-slice.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
    } else {
      $name = "";
      $cancel_url="cancel-account-mod.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
    }

    $cancel_button = "<button style=\"\" onClick=\"window.location='" . $cancel_url . "'\"><b>Cancel Request</b></button>";
    $reason = $request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TEXT];
    $req_date_db = $request[RQ_REQUEST_TABLE_FIELDNAME::CREATION_TIMESTAMP];
    $req_date = dateUIFormat($req_date_db);
    print "<tr><td>$typestr</td><td>$name</td><td>$req_date</td><td>$reason</td><td>$cancel_button</td></tr>\n";
  }
  print "</table>\n";
  print "<br/>\n";
} else {
  print "<p><i>No outstanding requests to join projects or slices or change your profile.</i></p>\n";
}

// END outstanding requests tab
echo "</div>";

// BEGIN account summary tab
echo "<div id='accountsummary'>";
$disable_account_details = "";
if($in_lockdown_mode) {
  $disable_account_details = "disabled";
}

print "<h2>Account Summary</h2>\n";
// Show username, email, affiliation, IdP, urn, prettyName, maybe project count and slice count
// Put this in a nice table
print "<table>\n";
print "<tr><th>Name</th><td>" . $user->prettyName() . "</td></tr>\n";
print "<tr><th>Email</th><td>" . $user->email() . "</td></tr>\n";
print "<tr><th>GENI Username</th><td>" . $user->username . "</td></tr>\n";
print "<tr><th>GENI URN</th><td>" . $user->urn() . "</td></tr>\n";
print "<tr><th>Home Institution</th><td>" . $user->idp_url . "</td></tr>\n";
print "<tr><th>Affiliation</th><td>" . $user->affiliation . "</td></tr>\n";
if ($user->phone() != "")
  print "<tr><th>Telephone Number</th><td>" . $user->phone() . "</td></tr>\n";
// FIXME: Project count? Slice count?
// FIXME: Other attributes?
// FIXME: Permissions
print "</table>\n";
print "<p><button $disable_account_details onClick=\"window.location='modify.php'\">Modify user supplied account details </button> (e.g. to become a Project Lead).</p>";

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
<p>To configure <code>omni</code>, use the <a href='http://trac.gpolab.bbn.com/gcf/wiki/OmniConfigure/Automatic'><code>omni-configure</code></a> script distributed with <code>omni</code> as described below.</p>
  <ol>
    <li>
In order to use <code>omni</code> or other command line tools you will need to generate an SSL certificate. <br/>
<?php if (!$has_certificate): ?>

<button onClick="window.open('<?php print $create_url?>')">Generate an SSL certificate</button>.
<?php else: ?>
<b>Good! You have already generated an SSL certificate.</b>
<?php endif; ?>

    </li>
    <li>Download your customized <code>omni</code> configuration data and save it in the default location (<code>~/Downloads/omni-bundle.zip</code>):<br/>
    		 <button onClick="window.location='omni-bundle.php'">Download your omni data</button>
    </li>
    <li>Generate an <code>omni_config</code> by running the following command in a terminal: <pre>omni-configure</pre></li>
    <li>Test your setup by running the following command in a terminal: <pre>omni -a ig-gpo getversion</pre>
    The output should look similar to this <a href='http://trac.gpolab.bbn.com/gcf/attachment/wiki/OmniConfigure/Automatic/getversion.out'>example output</a>.
</li>
  </ol>

  <table id='tip'>
    <tr>
       <td rowspan=3><img id='tipimg' src="http://groups.geni.net/geni/attachment/wiki/GENIExperimenter/Tutorials/Graphics/Symbols-Tips-icon-clear.png?format=raw" width="75" height="75" alt="Tip"></td>
       <td><b>Tip</b> Make sure you are running <b>omni 2.3.1</b> or later.</td>
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
/*----------------------------------------------------------------------
 * ABAC (if enabled)
 *----------------------------------------------------------------------
 */
if ($portal_enable_abac)
  {
    print '<h2>ABAC</h2>\n';
    print "<button onClick=\"window.location='abac-id.php'\">Download your ABAC ID</button><br/>\n";
    print "<button onClick=\"window.location='abac-key.php'\">Download your ABAC private key</button>\n";
  }
$disable_authorize_tools = "";
if($in_lockdown_mode) {
  $disable_authorize_tools = "disabled";
}

print "<p><button $disable_authorize_tools onClick=\"window.location='kmhome.php'\">Authorize or De-authorize tools</button> to act on your behalf.</p>";


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

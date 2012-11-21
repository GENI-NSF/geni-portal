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
?>
<h1>About Me</h1>
<?php
/*----------------------------------------------------------------------
 * SSH key management
 *----------------------------------------------------------------------
 */
print "<h2>SSH Keys</h2>\n";
$keys = $user->sshKeys();
if (count($keys) == 0)
  {
    // No ssh keys are present.
    print "No SSH keys have been uploaded. ";
    print "SSH keys are required to log in to reserved compute resources.<br/><br/>\n";
    print "You can <button onClick=\"window.location='generatesshkey.php'\">generate and download an SSH keypair</button>";
    print "or <button onClick=\"window.location='uploadsshkey.php'\">upload an SSH public key</button>, if you have one you want to use.<br/>\n";
    print "If you're not sure what to do, choose 'Generate'.<br/>\n";

  }
else
  {
    $download_pkey_url = relative_url('downloadsshkey.php?');
    $edit_sshkey_url = relative_url('sshkeyedit.php?');
    print "\n<table>\n";
    print "<tr><th>Name</th><th>Description</th><th>Private Key</th>"
          . "<th>Edit</th></tr>\n";
    foreach ($keys as $key) {
      $args['id'] = $key['id'];
      $query = http_build_query($args);
      if (is_null($key['private_key'])) {
        $pkey_cell = 'N/A';
      } else {
        $pkey_cell = ("<button onClick=\"window.location='"
                . $download_pkey_url . $query
                . "'\">Download Private Key</button>");
      }
      $edit_cell = ("<button onClick=\"window.location='"
                . $edit_sshkey_url . $query
                . "'\">Edit</button>");
      print "<tr>"
      . "<td>" . htmlentities($key['filename']) . "</td>"
      . "<td>" . htmlentities($key['description']) . "</td>"
      . '<td>' . $pkey_cell . '</td>'
      . '<td>' . $edit_cell . '</td>'
      . "</tr>\n";
    }
    print "</table>\n";
    print "<i>Note</i>: You will need your SSH private key on your local machine. <br/>\nIf you generated your SSH keypair on this portal and have not already done so, be sure to Download your SSH key. <br/>\nAfter you download your key, be sure to set local permissions on that file appropriately. On Linux and Mac, do \"chmod 0600 <i>[path-to-SSH-private-key]</i>\". <br/>\nWhen you invoke SSH to log in to reserved resources, you will need to remember the path to that file. <br/>Your SSH command will be something like: \"ssh -i <i>path-to-SSH-key-you-downloaded</i> <i>[username]@[hostname]</i>\".<br/>\n";
    print "<br/>\n";
    print "<button onClick=\"window.location='uploadsshkey.php'\">Upload another SSH public key</button>\n";
  }
?>

<h2>Edit Account Details</h2>
<button onClick="window.location='modify.php'">Modify user supplied account details </button> (e.g. to become
a Project Lead).<br/>
<br/>
<button onClick="window.location='kmhome.php'">Authorize or De-authorize tools</button> to act on your behalf.<br/>
<h2>Outstanding Requests</h2>
<?php
// Show outstanding requests BY this user
if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
  if (! isset($pa_url) || is_null($pa_url) || $pa_url == '') {
    error_log("Found no PA in SR!'");
  }
}

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
$preqs = get_requests_by_user($pa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null, RQ_REQUEST_STATUS::PENDING);
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
      $project = lookup_project($pa_url, $user, $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);
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
  print "<i>No outstanding requests to join projects or slices or change your profile.</i><br/>\n";
}

print "<h2>Account Summary</h2>\n";
// Show username, email, affiliation, IdP, urn, prettyName, maybe project count and slice count
// Put this in a nice table
print "<table>\n";
print "<tr><th>Name</th><td>" . $user->prettyName() . "</td></tr>\n";
print "<tr><th>Email</th><td>" . $user->email() . "</td></tr>\n";
print "<tr><th>Home Institution</th><td>" . $user->idp_url . "</td></tr>\n";
print "<tr><th>Affiliation</th><td>" . $user->affiliation . "</td></tr>\n";
print "<tr><th>GENI URN</th><td>" . $user->urn() . "</td></tr>\n";
print "<tr><th>GENI Username</th><td>" . $user->username . "</td></tr>\n";
// FIXME: Project count? Slice count?
// FIXME: Other attributes?
// FIXME: Permissions
print "</table>\n";

print "<h1>My Stuff</h1>\n";

/*----------------------------------------------------------------------
 * RSpecs
 *----------------------------------------------------------------------
 */
print "<h2>Manage RSpecs</h2>\n";

print "<button onClick=\"window.location='rspecs.php'\">"
  . "Manage RSpecs</button>\n";
?>

<?php
/*----------------------------------------------------------------------
 * SSL key management
 *----------------------------------------------------------------------
 */

// Does the user have an outside certificate?
$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
$has_certificate = ! is_null($result);
// FIXME: hardcoded paths
$create_url = "https://" . $_SERVER['SERVER_NAME'] . "/secure/kmcert.php";
$download_url = "https://" . $_SERVER['SERVER_NAME'] . "/secure/kmcert.php";
?>

<h2>Command line tools</h2>
For <i>Advanced</i> users:
<?php if ($has_certificate): ?>
<a href="<?php print $download_url?>">download your SSL certificate</a>
<?php else: ?>
<a href="<?php print $create_url?>">create an SSL certificate</a>
<?php endif; ?>
in order to use other GENI tools.<br/><br/>

<table>
<tr><th>Tool</th><th>Description</th><th>Configuration File</th></tr>
<tr>
  <td><a href='http://trac.gpolab.bbn.com/gcf/wiki'>Omni</a></td>
  <td>command line resource allocation tool</td>
  <td><a href='tool-omniconfig.php'>Get omni_config</a></td>
</tr>
</table>

<?php
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
?>

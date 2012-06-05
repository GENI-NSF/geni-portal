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
<h1>User Tools</h1>
<?php
/*----------------------------------------------------------------------
 * SSH key management
 *----------------------------------------------------------------------
 */
print "<h2>SSH Keys</h2>\n";
$keys = fetchSshKeys($user->account_id);
if (count($keys) == 0)
  {
    // No ssh keys are present.
    print "No SSH keys have been uploaded. ";
    print "SSH keys are used to log in to many reserved compute resources.<br/><br/>\n";
    print "You can <button onClick=\"window.location='generatesshkey.php'\">generate and download an SSH keypair</button>";
    print "or <button onClick=\"window.location='uploadsshkey.php'\">upload an SSH public key</button>.\n";
  }
else
  {
    print "\n<table>\n";
    print "<tr><th>Name</th><th>Description</th></tr>\n";
    foreach ($keys as $key)
      {
        print "<tr>"
          . "<td>" . htmlentities($key['filename']) . "</td>"
          . "<td>" . htmlentities($key['description']) . "</td>"
          . "</tr>\n";
	// FIXME: Way to delete a key?
      }
    print "</table>\n";
    print "<br/>\n";
    print "<button onClick=\"window.location='uploadsshkey.php'\">Upload another SSH public key</button>\n";
  }


/*----------------------------------------------------------------------
 * SSL key management
 *----------------------------------------------------------------------
 */
print "<h2>Keys and Certificates for command line tools</h2>\n";
print ("For <i>Advanced</i> users: download an SSL certificate and private key,"
       . " in order to use other GENI tools, such as Omni.<br/><br/>\n");
$keyrow = db_fetch_outside_private_key_cert($user->account_id);
if ($keyrow) {
  require_once("am_client.php");
  // must double backslash things in the omni_config here....
  $omni_config = get_template_omni_config($user);
  $omni_config = str_replace("\n", "\\n", $omni_config);
  $configalert = "Here is a template Omni config file.\\nTo use this:\\n\\t1. Save it to a file named portal_omni_config.\\n\\t2. Download your certificate, noting the path.\\n\\t3. Edit the portal_omni_config to correct \\n\\t\\t(a) the certificate path, \\n\\t\\t(b) the path to the SSL private key used to generate your certificate, and \\n\\t\\t(c) the path to your SSH public key to use for node logon.\\n\\t4. When running omni: \\n\\t\\ta) Do: omni -c portal_omni_config --slicecred <path to downloaded slice credential> ... to specify the path to this omni config and your downloaded slice credential\\n\\t\\tb) Use the full slice URN when naming your slice, not just the slice name\\n\\n$omni_config\\n";

  print "\n<table>\n";
  print "<tr><th>Certificate</th><th>Owner URN</th><th>Omni Config</th></tr>\n";
  $download_url = relative_url("certificate.php");
  $urn = urn_from_cert($keyrow['certificate']);

  print "<tr>"
    . "<td><button onClick=\"window.location='" . $download_url . "'\">Download Certificate</button></td>"
    . "<td>$urn</td>"
    . "<td><button onClick=\"JavaScript:alert('$configalert')\">Get Omni Config</button></td>"
    . "</tr>\n";
  print "</table>\n";
  // FIXME: Way to delete a key?
} else {
  print "<button onClick=\"window.location='downloadkeycert.php'\">"
    . "Download certificate and key</button>\n";
}


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

<h2>Edit Account Details</h2>
Modify user supplied account details <button onClick="window.location='modify.php'">here</button><br/>

<h2>Outstanding Requests</h2>
<?php
// Show outstanding requests for this user
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

$reqs = get_pending_requests_for_user($pa_url, $user, $user->account_id);
if (isset($reqs) && count($reqs) > 0) {
  print "Found " . count($reqs) . " outstanding requests for you:<br/>\n";
  print "<table>\n";
  print "<tr><th>Request Type</th><th>Project/Slice</th><th>Request Created</th><th>Request Reason</th><th>Cancel Request?</th></tr>\n";
  foreach ($reqs as $request) {
    $name = "";
    $typestr = $REQ_TYPE_NAMES[$request['request_type']] . " " . $CS_CONTEXT_TYPE_NAMES[$request['context']-1];
    if ($request['context'] == CS_CONTEXT_TYPE::PROJECT) {
      error_log("looking up project " . $request['context_id']);
      $project = lookup_project($pa_url, $request['context_id']);
      $name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $cancel_url="cancel-join-project.php?request_id=" . $request['id'];
    } elseif ($request['context'] == CS_CONTEXT_TYPE::SLICE) {
      $slice = lookup_slice($sa_url, $request['context_id']);
      $name = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
      $cancel_url="cancel-join-slice.php?request_id=" . $request['id'];
    } else {
      $name = "";
      $cancel_url="cancel-account-mod.php?request_id=" . $request['id'];
    }

    $cancel_button = "<button style=\"\" onClick=\"window.location='" . $cancel_url . "'\"><b>Cancel Request</b></button>";
    $reason = $request['request_text'];
    $req_date = $request['creation_timestamp'];
    print "<tr><td>$typestr</td><td>$name</td><td>$req_date</td><td>$reason</td><td>$cancel_button</td></tr>\n";
  }
  print "</table>\n";
  print "<br/>\n";
} else {
  print "<i>No outstanding requests to join projects or slices or change your profile.</i><br/>\n";
}


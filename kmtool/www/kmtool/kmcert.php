<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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
 * Home of GENI key management tool
 */

require_once('km_utils.php');
require_once('ma_client.php');

$member_id_key = 'eppn';
$member_id_value = null;
$members = array();
$member = null;
$member_id = null;
if (array_key_exists($member_id_key, $_SERVER)) {
  $member_id_value = $_SERVER[$member_id_key];
  $members = ma_lookup_member_id($ma_url, $km_signer,
				 $member_id_key, $member_id_value);
} else if (array_key_exists("member_id", $_REQUEST)) {
  $member_id = $_REQUEST["member_id"];
} else {
  error_log("No member_id_key $member_id_key given to kmcert");
}

if (count($members) > 0 && ! isset($member_id)) {
  $member = $members[0];
  $member_id = $member->member_id;
} else if (! isset($member_id)) {
  error_log("kmcert: No members found for member_id $member_id_value");
}

$username = '*** Undefined ***';
if (array_key_exists('displayName', $_SERVER)) {
  $username = $_SERVER['displayName'];
} else if (array_key_exists('sn', $_SERVER) && array_key_exists('givenName', $_SERVER)){
  $username = $_SERVER['givenName'] . " " . $_SERVER['sn'];
} else if (array_key_exists('eppn', $_SERVER)) {
  $username = $_SERVER['eppn'];
} else if (array_key_exists("username", $_REQUEST)) {
  $username = $_REQUEST["username"];
}

function show_close_button() {
  if (key_exists("close", $_REQUEST)) {
    print "<br/>\n";
    print "<button onclick=\"window.close();return false;\"><b>Close</b></button>\n";
  }
}

function download_cert($ma_url, $km_signer, $member) {
  $member_id = $member->member_id;
  $username = $member->username;
  $result = ma_lookup_certificate($ma_url, $km_signer, $member_id);
  $cert_filename = "geni-$username.pem";
  // Set headers for download
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Disposition: attachment; filename=$cert_filename");
  header("Content-Type: application/pem");
  header("Content-Transfer-Encoding: binary");
  if (key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)) {
    print $result[MA_ARGUMENT::PRIVATE_KEY];
    print "\n";
  }
  print $result[MA_ARGUMENT::CERTIFICATE];
}

function generate_cert($ma_url, $km_signer, $member_id, $csr=NULL) {
  $result = ma_create_certificate($ma_url, $km_signer, $member_id, $csr);
}

function handle_upload($ma_url, $km_signer, $member_id, &$error) {
  // Get the uploaded CSR
  if (array_key_exists('csrfile', $_FILES)) {
    $errorcode = $_FILES['csrfile']['error'];
    if ($errorcode != 0) {
      // An error occurred with the upload.
      if ($errorcode == UPLOAD_ERR_NO_FILE) {
        $error = "No file was uploaded.";
      } else {
        $error = "Unknown upload error (code = $errorcode).";
      }
      return false;
    } else {
      /* A file was uploaded. Do a rudimentary test to see if it is
       * a CSR. If not, explain.
       */
      $cmd_array = array('/usr/bin/openssl',
              'req',
              '-noout',
              '-in',
              $_FILES["csrfile"]["tmp_name"],
      );
      $command = implode(" ", $cmd_array);
      $result = exec($command, $output, $status);
      if ($status != 0) {
        $fname = $_FILES['file']['name'];
        $error = "File $fname is not a valid certificate signing request.";
        return false;
      } else {
        // HERE EVERYTHING LOOKS GOOD, SO PROCESS THE CSR
        // LOAD THE CONTENTS OF THE FILE AND PASS ALONG TO generate_cert()
        $csr = file_get_contents($_FILES["csrfile"]["tmp_name"]);
        if ($csr === false) {
          // Something went wrong loading the uploaded csr
          $error = "Unable to read uploaded file contents.";
          return false;
        }
        generate_cert($ma_url, $km_signer, $member_id, $csr);
        return true;
      }
    }
  } else {
    $error = "No file uploaded.";
    return false;
  }
}

$generate_key = "generate";
$upload_key = "upload";
$download_key = "download";
$close_key = key_exists("close", $_REQUEST) ? "close" : "noclose";

if (key_exists($generate_key, $_REQUEST)) {
  // User has asked to generate a cert/key.
  generate_cert($ma_url, $km_signer, $member_id);
}
if (key_exists($upload_key, $_REQUEST)) {
  $status = handle_upload($ma_url, $km_signer, $member_id, $error);
}
if (key_exists($download_key, $_REQUEST)) {
  download_cert($ma_url, $km_signer, $member);
  return;
}

// If invoked with a ?redirect=url argument, grab that
// argument and go there from the 'continue' button
$redirect_key = "redirect";

//$redirect_address = "home.php";
$redirect_address = "";

if(array_key_exists($redirect_key, $_GET)) {
  $redirect_address = $_GET[$redirect_key];
}

// Set up the error display
if (isset($error)) {
  $_SESSION['lasterror'] = $error;
  unset($error);
}

include('kmheader.php');
print "<h2>GENI Certificate Management</h2>\n";
include("tool-showmessage.php");

if (! isset($member_id)) {
  print "You must first activate your GENI account <a href=\"kmactivate.php\">here</a>.<br\>\n";
  include("footer.php");
  return;
}

$result = ma_lookup_certificate($ma_url, $km_signer, $member_id);
if (! is_null($result)) {
  // User has an outside cert. Show the download screen.
  if (key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)
          && $result[MA_ARGUMENT::PRIVATE_KEY]) {
    $key_msg = " and Private Key";
  } else {
    $key_msg = "";
  }
?>
<h4>Download Your Certificate<?php print $key_msg;?>:</h4>
<form name="download" action="kmcert.php" method="post">
<input type="hidden" name="<?php print $download_key ?>" value="y"/>
<input type="submit" name="submit" value="Download Certificate<?php print $key_msg;?>"/>
</form>
<?php
  show_close_button();
  include("footer.php");
  return;
}

?>

<p>In order to use some GENI tools (like
<a href="http://trac.gpolab.bbn.com/gcf/wiki/Omni">omni</a>) you need two objects: a user SSL certificate and a private key. 
</p><p>
There are two options for creating these:
<ol>
<li>Have the objects generated for you <b>(easiest, least secure)</b> </li>
<li>Have the certificate generated for you based on a private key you have locally <b>(hardest, most secure)</b> </li>
</ol>
</p>
<h2>Option 1: Have the objects generated for you </h2>

<p><b>If in doubt, use this option.</b></p>

<form name="generate" action="kmcert.php" method="post">
<input type="hidden" name="<?php print $generate_key;?>" value="y"/>
<input type="hidden" name="<?php print $close_key; ?>" value="1"/>
<input type="submit" name="submit" value="Generate Combined Certificate and Key File"/>
</form>
<hr>

<h2>Option 2. Have the certificate generated for you based on a private key you have locally </h2>
<p>There are two variations on this option, only do one of them.</p>
<ul>
	<li>Option 2a: Create a private key, then upload a certificate signing request (CSR)</li>
<p><b>For the most security, use this option.</b></p>
	<ul>
		<li>
Run the following command in a terminal window on a Mac or Linux host. When prompted, enter the same PEM pass phrase twice.
This will generate two files: <code>CSR.csr</code> and <code>geni-ssl-private.key</code>.
Store <code>geni-ssl-private.key</code> where you'll remember it ($HOME/.ssl, $HOME/.ssh).
Upload <code>CSR.csr</code> in the form below.
<br/>
<pre>openssl req -out CSR.csr -new -newkey rsa:2048 -keyout geni-ssl-private.key -batch</pre>
<h4>Now upload the file CSR.csr below:</h4>
<form name="upload" action="kmcert.php" method="post" enctype="multipart/form-data">
<label for="csrfile">Certificate Signing Request File:</label>
<input type="file" name="csrfile" id="csrfile"/>
<br/>
<input type="hidden" name="<?php print $upload_key; ?>" value="y"/>
<input type="hidden" name="<?php print $close_key; ?>" value="1"/>
<input type="submit" name="submit" value="Create Certificate"/>
</form>
	<br/>

		</li>
	</ul>
	<li>Option 2b: Reuse an existing private key, then upload a certificate signing request (CSR) </li>
	<br/>
	<ul>
		<li>
Run the following command in a terminal window on a Mac or Linux host. When prompted, enter the pass phrase for the private key. 
This will generate a file named <code>CSR.csr</code>.
Upload <code>CSR.csr</code> in the form below.
<pre>openssl req -out CSR.csr -new -key &lt;YourPrivateKey&gt; -batch</pre>
<h4>Now upload the file CSR.csr below:</h4>
<form name="upload" action="kmcert.php" method="post" enctype="multipart/form-data">
<label for="csrfile">Certificate Signing Request File:</label>
<input type="file" name="csrfile" id="csrfile"/>
<br/>
<input type="hidden" name="<?php print $upload_key; ?>" value="y"/>
<input type="hidden" name="<?php print $close_key; ?>" value="1"/>
<input type="submit" name="submit" value="Create Certificate"/>
</form>
		</li>
	</ul>
</ul>

<p>
Remember, in order to use these, you will need to keep track of the downloaded certificate, the private key and the passphrase for the key.  
</p>
<?php
show_close_button();

// Include this only if the redirect address is a web address
if (! empty($redirect_address)) {
  print"<button onclick=\"window.location='" .
    $redirect_address . "'" . "\"<b>Continue</b></button> back to your " .
    "Clearinghouse tool.<br/>";
}

include("footer.php");
?>

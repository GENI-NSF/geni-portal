<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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

require_once("settings.php");
require_once("user.php");
require_once("header.php");
require_once("cert_utils.php");
require_once("ma_client.php");
require_once("sr_client.php");

// FIXME: JS to validate PW at client

function show_form($error_msg)
{
  print "<h1>Generate an SSH private key</h1>\n";
  print "<p>SSH keys are required to log in to reserved compute resources. On this page, you can generate a new SSH key pair.</p>\n";

  if ($error_msg) {
    print "<div id=\"error-message\""
      . " style=\"background: #dddddd;font-weight: bold\">\n"
      . $error_msg
      . "</div>\n";
  }
  print "<form action=\"generatesshkey.php\" method=\"post\">\n";
  print "<p>Please supply a new passphrase to protect your SSH private key (minimum 5 characters).</p>\n";
  print "<p><label for=\"password\">Passphrase:</label>\n";
  print "<input type=\"password\" name=\"password\"/></p>\n";
  print "<p><label for=\"password2\">Confirm Passphrase:</label>\n";
  print "<input type=\"password\" name=\"password2\"/>\n";
  print "</p>\n";
  print "<p><input type=\"submit\" name=\"submit\" value=\"Generate SSH private key\"/></p>\n";
  print "</form>\n";
  print "<p>If you already have an SSH key pair ";
  print "that you want to use, you can instead <button onClick=\"window.location='uploadsshkey.php'\">upload an SSH public key</button>.</p>\n";
  print "<p>If you're not sure what to do, use this page to generate a new key pair.</p>\n";

  //  print "<i>Note</i>: You will need your SSH private key on your local machine. <br/>\nAfter generating your SSH keypair, be sure to Download your SSH key. <br/>\nAfter you download your key, be sure to set local permissions on that file appropriately. On Linux and Mac, do \"chmod 0600 <i>[path-to-SSH-private-key-you-downloaded]</i>\". <br/>\nWhen you invoke SSH to log in to reserved resources, you will need to remember the path to that file. <br/>Your SSH command will be something like: \"ssh -i <i>path-to-SSH-key-you-downloaded</i> <i>[username]@[hostname]</i>\".<br/>\n";
}

function validate_password($password, &$error_msg)
{
  $min_length = 5;
  if (strlen($password) < $min_length) {
    $error_msg = "Invalid passphrase: must be at least $min_length characters.";
    return False;
  }
  // Disallow bad chars
  // gives you ASCII chars from 32  to 127: really less than all printable
  $pw2 = filter_var($password,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_NO_ENCODE_QUOTES);
  if ($pw2 != $password) {
    error_log("Cleaned SSH key PW $pw2 not same as input - rejecting");
    $error_msg = "Invalid passphrase: Use only ASCII printable characters";
    return False;
  }
  return True;
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

/* Decide whether to show the form. */
$show_form = True;
$error_msg = null;
$password = "";
$password2 = "";
if (array_key_exists('password', $_POST)) {
  $password = $_POST['password'];
}
if (array_key_exists('password2', $_POST)) {
  $password2 = $_POST['password2'];
}
if ($password != "" && validate_password($password, $error_msg)) {
  if ($password == $password2) {
    $show_form = False;
  } else {
    $error_msg = "Passphrase entries don't match.";
  }
}

if ($show_form) {
  // Display the form and exit
  $GENI_TITLE = "Generate SSH keypair";
  $load_user = TRUE;
  show_header('GENI Portal: Profile', $TAB_PROFILE, $load_user);
  include("tool-breadcrumbs.php");
  show_form($error_msg);
  include("footer.php");
  exit;
}

/*------------------------*/
/* Generate a new ssh key */
/*------------------------*/
/* Instead of "www-data@host", use the username as the comment. */
$comment = $user->username;
$privatekeyfile = tempnam(sys_get_temp_dir(), 'ssh');
/* delete the file so ssh-keygen doesn't complain about overwrite. */
unlink($privatekeyfile);
$publickeyfile = $privatekeyfile . ".pub"; /* per the ssh-keygen man page */
// single quote the password
$cleanpw = escapeshellarg($password);
$command = "/usr/bin/ssh-keygen -t rsa -f $privatekeyfile -N $cleanpw -q -C \"$comment\"";
$result = exec($command, $output, $status);
if ($status != 0) {
	/* Error! */
	error_log("Error generating ssh key; command = $command");
	error_log("Error generating ssh key; status = $status");
	error_log("Error generating ssh key; output = " . print_r($output, TRUE));
        // clean up temp files
        if (file_exists($privatekeyfile)) { unlink($privatekeyfile); }
        if (file_exists($publickeyfile)) { unlink($publickeyfile); }
	$GENI_TITLE = "Generate SSH keypair";
	$load_user = TRUE;
	show_header('GENI Portal: Profile', $TAB_PROFILE, $load_user);
	include("tool-breadcrumbs.php");
	print '<h1>An error occurred while generating your SSH keypair.</h1>';
	include("footer.php");
	exit();
}

/* ssh keys (public and private) were successfully generated. Store them in the database. */
$private_key = file_get_contents($privatekeyfile);
$public_key = file_get_contents($publickeyfile);
unlink($privatekeyfile);
unlink($publickeyfile);
/* This is the name of the file on the experimenter's machine. */
$filename = "id_geni_ssh_rsa";
$description = "Generated SSH keypair";
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$result = register_ssh_key($ma_url, $user, $user->account_id, $filename, $description,
        $public_key, $private_key);
if (is_array($result) && array_key_exists(RESPONSE_ARGUMENT::CODE, $result) && $result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
  error_log("Failed to register SSH key for account " . $user->account_id . " from file $filename: " . $result);
  $_SESSION['lasterror'] = "ERROR Generating SSH keypair";
  relative_redirect('profile.php');
}
if (True) {
  $_SESSION['lastmessage'] = "Generated SSH keypair - now download the private key";
  relative_redirect('profile.php');
} else {
  // Set headers for download
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: application/pem");
  header("Content-Transfer-Encoding: binary");
  print $private_key;
}
?>

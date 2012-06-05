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

require_once("settings.php");
require_once("user.php");
require_once("header.php");
require_once("cert_utils.php");

// FIXME: JS to validate PW at client

function show_form($error_msg)
{
  print "<br/>\n";
  print "<b>Generate an SSH private key</b><br/>\n";
  if ($error_msg) {
    print "<div id=\"error-message\""
      . " style=\"background: #dddddd;font-weight: bold\">\n"
      . $error_msg
      . "</div>\n";
  }
  print "<form action=\"generatesshkey.php\" method=\"post\">\n";
  print "<label for=\"password\">Password:</label>\n";
  print "<input type=\"password\" name=\"password\"/><br/>\n";
  print "<label for=\"password2\">Confirm Password:</label>\n";
  print "<input type=\"password\" name=\"password2\"/>\n";
  print "<br/>\n";
  print "<input type=\"submit\" name=\"submit\" value=\"Generate SSH private key\"/>\n";
  print "</form>\n";
}

function validate_password($password, &$error_msg)
{
  $min_length = 5;
  if (strlen($password) < $min_length) {
    $error_msg = "Invalid password: must be at least $min_length characters.";
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
    $error_msg = "Password entries don't match.";
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

/* Generate a new ssh key */
$privatekeyfile = tempnam(sys_get_temp_dir(), 'ssh');
/* delete the file so ssh-keygen doesn't complain about overwrite. */
unlink($privatekeyfile);
$publickeyfile = $privatekeyfile . ".pub"; /* per the ssh-keygen man page */
$command = "/usr/bin/ssh-keygen -t rsa -f $privatekeyfile -N $password -q";
$result = exec($command, $output, $status);
if ($status != 0) {
	/* Error! */
	error_log("Error generating ssh key; command = $command");
	error_log("Error generating ssh key; status = $status");
	error_log("Error generating ssh key; output = " . print_r($output, TRUE));
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
/* This is the name of the file on the experimenter's machine. */
$filename = "id_geni_ssh_rsa";
insertSshKey($user->account_id, $public_key, $filename, "Generated SSH keypair", $private_key);

if (True) {
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
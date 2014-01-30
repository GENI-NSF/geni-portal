<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$error = NULL;
if (array_key_exists('file', $_FILES)) {
  $errorcode = $_FILES['file']['error'];
  if ($errorcode != 0) {
    // An error occurred with the upload.
    if ($errorcode == UPLOAD_ERR_NO_FILE) {
      $error = "No file was uploaded.";
    } else {
      $error = "Unknown upload error (code = $errorcode).";
    }
  } else {
    /* A file was uploaded. Do a rudimentary test to see if it isn't
     * an ssh key. This test is not great, but it's all I could find.
     */
    $cmd_array = array('/usr/bin/ssh-keygen',
                       '-lf',
                       $_FILES["file"]["tmp_name"],
                       );
    $command = implode(" ", $cmd_array);
    $result = exec($command, $output, $status);
    if ($status != 0) {
      $fname = $_FILES['file']['name'];
      $error = "File $fname is not a valid SSH public key.";
    }
  }
}

if ($error != NULL || count($_POST) == 0) {
  // Display the form and exit
  show_header('GENI Portal: Profile', $TAB_PROFILE, 0); // 0=Don't load user to show header
  include("tool-breadcrumbs.php");
  print("<h1>Upload SSH Public Key</h1>\n");
  if ($error != NULL) {
    echo "<div id=\"error-message\""
      . " style=\"background: #dddddd;font-weight: bold\">\n";
    echo "$error";
    echo "</div>\n";
  }
  include('uploadsshkey.html');
  
  // show button only if there are no SSH keys on file
  // don't let user generate public/private keypair if
  // they already have 1+ public keys on file
  $keys = $user->sshKeys();
  if (count($keys) == 0) {
    echo "<p>If you don't have an SSH keypair or want to create a new SSH keypair, <button onClick=\"window.location='generatesshkey.php'\">generate and download an SSH keypair</button>.</p>";
  }
  
  include("footer.php");
  exit;
}

// The public key is in $_FILES["file"]["tmp_name"]
$contents = file_get_contents($_FILES["file"]["tmp_name"]);
$filename = $_FILES["file"]["name"];
$description = NULL;
if (array_key_exists("description", $_POST)) {
  $description = $_POST["description"];
}
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
register_ssh_key($ma_url, $user, $user->account_id, $filename, $description,
        $contents);

$_SESSION['lastmessage'] = "Uploaded SSH public key from " . $_FILES["file"]["name"];

relative_redirect('profile');
?>
Your key was uploaded.<br/>
<a href="profile.php">My Profile page</a>

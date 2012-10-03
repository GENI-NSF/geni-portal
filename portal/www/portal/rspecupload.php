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

require_once("settings.php");
require_once("user.php");
require_once("header.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';


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
    /*
     * Upload was successful, do some basic checks on the contents.
     */
    /* TODO: do some sort of check on the rspec.
     * Is it valid XML? Does it pass "rspeclint"?
     * Is it a request RSpec (not ad or manifest)?
     */
  }
}

if ($error != NULL || count($_POST) == 0) {
  // Display the form and exit
  show_header('GENI Portal: Profile', $TAB_PROFILE, 0); // 0=Don't load user to show header
  include("tool-breadcrumbs.php");
  include("tool-showmessage.php");
  print("<h2>Upload experiment RSpec</h2>\n");
  if ($error != NULL) {
    echo "<div id=\"error-message\""
      . " style=\"background: #dddddd;font-weight: bold\">\n";
    echo "$error";
    echo "</div>\n";
  }
  echo '<form action="rspecupload.php" method="post" enctype="multipart/form-data">';
  echo '  <label for="file">RSpec File:</label>';
  echo '  <input type="file" name="file" id="file" />';
  echo '  <br/><br/>';
  echo '  <label for="file">Short Name:</label>';
  echo '  <input type="text" name="name"/> - Required';
  echo '  <br/><br/>';
  echo '  <input type="radio" name="group1" value="public" checked> public';
  echo '  <input type="radio" name="group1" value="private"> private';
  echo '  <br/><br/>';
  echo '  <label for="file">Description:</label>';
  echo '  <input type="text" name="description"/> - Required';
  echo '  <br/><br/>';
  echo '  <input type="submit" name="submit" value="Upload"/>';
  echo '  <input type="hidden" name="referer" value="' . $_SERVER['HTTP_REFERER'] . '"/>';
  echo '</form>';
  echo '<br/>';
  include("footer.php");
  exit;
}

// The rspec is in $_FILES["file"]["tmp_name"]
$contents = file_get_contents($_FILES["file"]["tmp_name"]);
$filename = $_FILES["file"]["name"];
$description = NULL;
$name = $_POST["name"];
$visibility = $_POST["group1"];
$description = $_POST["description"];

// FIXME: Need a utility that determines schema and version
// from the RSpec itself.
$schema = "GENI";
$schema_version = "3";

geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "Calling db_add_rspec");
$result = db_add_rspec($user, $name, $description, $contents,
        $schema, $schema_version, $visibility);
geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "db_add_rspec: " . print_r($result, true));
//error_log("db_add_rspec: " . print_r($result, true));
// FIXME: check result
if (! $result) {
  $_SESSION['lasterror'] = "ERROR. Failed to upload RSpec " . $name;
} else {
  $_SESSION['lastmessage'] = "Uploaded RSpec " . $name;
}

// redirect to referer if available.
if (array_key_exists('referer', $_POST)) {
  header("Location: " . $_POST['referer']);
  exit;
} else {
  relative_redirect('profile');
}
?>

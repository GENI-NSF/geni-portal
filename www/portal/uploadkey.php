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
function create_cert()
{
  // Invoke gen-certs to create a new certificate. Return the
  // resulting certificate.
  //
  // What to use for username in cert URN? We should store the URN
  // in the account, or at least the username. It can be extracted
  // from the eppn, for instance, or the email address. Or it can
  // be entered by the administrator when the account is approved.
  //
}
?>
<?php
if ($_FILES["file"]["error"] > 0) {
  echo "Error: " . $_FILES["file"]["error"] . "<br />";
} else {
  echo "Upload: " . $_FILES["file"]["name"] . "<br />";
  echo "Type: " . $_FILES["file"]["type"] . "<br />";
  echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
  echo "Stored in: " . $_FILES["file"]["tmp_name"];
}

// The public key is in $_FILES["file"]["tmp_name"]
$contents = file_get_contents($_FILES["file"]["tmp_name"]);
require_once("user.php");
$user = geni_loadUser();
$description = NULL;
echo "<h1>GET</h1>";
var_dump($_GET);
echo "<h1>POST</h1>";
var_dump($_POST);
if (array_key_exists("description", $_POST)) {
  echo "Description exists in POST<br/>";
  $description = $_POST["description"];
}
echo "Passing description: $description<br/>";
db_add_public_key($user->account_id, $contents, $description)

// Automatically create a certificate (?)

// Insert into public_key table
//
// Account id is from $user
// Public key is from $_FILES["file"]["tmp_name"]
// Description is from form upload
// Certificate is from create_cert()
//
// use db_add_public_key($account_id, $public_key, $description, $certificate)

?>
Your key was uploaded.<br/>
<a href="home.php">Home page</a>
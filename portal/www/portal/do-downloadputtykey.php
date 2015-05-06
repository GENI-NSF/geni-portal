<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

// File to download putty version of private SSH key
// Invoked by a POST with keys
//    key_id : ID of SSH key
//    passphrase : passphrase of private key

// Note: Requires that putty be installed on server 
//   (apt-get install putty or yum install putty)
//
// Once we have a private SSH key (RSA or DSA format)
// we run:
// echo $PASSPHRASE | puttygen $PRIVATE_KEY -o $PUTTY_PRIVATE_KEY

require_once("settings.php");
require_once("user.php");

// error_log("POST = " . print_r($_POST, true));

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$key_id = NULL;
if (array_key_exists('key_id', $_POST)) {
  $key_id = $_POST['key_id'];
}

$passphrase = NULL;
if (array_key_exists('passphrase', $_POST)) {
  $passphrase = $_POST['passphrase'];
}

if (is_null($key_id) || is_null($passphrase)) {
  relative_redirect('home.php');
}

$keys = $user->sshKeys();
$private_key = NULL;
$filename = NULL;
foreach ($keys as $key) {
  if ($key['id'] == $key_id) {
    $private_key = $key['private_key'];
    $filename = $key['filename'];
  }
}

if (is_null($private_key)) {
  relative_redirect('home.php');
} else {
  $private_key_filename =tempnam("/tmp", "private-");
  $putty_key_filename = tempnam("/tmp", "putty-");

  // Write the private key
  file_put_contents($private_key_filename, $private_key);

  // Run the comand to generate the puttygen command
  $cmd = "echo $passphrase | puttygen $private_key_filename -o $putty_key_filename";
  //  error_log("CMD = " . $cmd);
  system($cmd);
  
  // Read the putty key
  $putty_key = file_get_contents($putty_key_filename);

  // Delete the file with the private key
  unlink($private_key_filename);
  // Delete the file with the putty key
  unlink($putty_key_filename);

  //  error_log("PRIV_FILE = " . $private_key_filename);
  //  error_log("PUTTY_FILE = " . $putty_key_filename);
  //  error_log("PRIV= " . $private_key);
  //  error_log("PUTTY= " . $putty_key);

  if($putty_key == NULL || strlen($putty_key) == 0) {
    relative_redirect("error-text.php?error=" . urlencode("Invalid passphrase"));
  }
  
// Set headers for download
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Disposition: attachment; filename=$filename.ppk");
  header("Content-Type: application/pem");
  header("Content-Transfer-Encoding: binary");
  print $putty_key;
}
?>

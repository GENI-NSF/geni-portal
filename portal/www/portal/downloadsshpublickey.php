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

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$key_id = NULL;
if (array_key_exists('id', $_GET)) {
  $key_id = $_GET['id'];
}

if (is_null($key_id)) {
  relative_redirect('home.php');
}

$keys = $user->sshKeys();
$public_key = NULL;
$filename = NULL;
foreach ($keys as $key) {
  if ($key['id'] == $key_id) {
    $public_key = $key['public_key'];
    $filename = $key['filename'];
    if (strtolower(substr($filename, -strlen(".pub"))) !== strtolower(".pub")) {
      $filename .= ".pub"; // append '.pub' since this isn't done automatically
    }
  }
}

if (is_null($public_key)) {
  relative_redirect('home.php');
} else {
// Set headers for download
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: application/pem");
  header("Content-Transfer-Encoding: binary");
  print $public_key;
}
?>

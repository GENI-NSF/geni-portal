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

require_once("settings.php");
require_once("user.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$rspec_id = NULL;
if (array_key_exists('id', $_GET)) {
  $rspec_id = $_GET['id'];
}

$strip_comments = NULL;
if (array_key_exists('strip_comments', $_GET)) {
  $strip_comments = $_GET['strip_comments'];
}

if (is_null($rspec_id)) {
  relative_redirect('home.php');
}

/* $rspec is the database record */
$rspec = fetchRSpec($rspec_id);

if (is_null($rspec)) {
  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
  exit();
}

$owner_id = $rspec['owner_id'];
$visibility = $rspec['visibility'];
if ($visibility != 'public' && $owner_id != $user->account_id) {
  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
  exit();
}

// Set headers for xml
header("Cache-Control: public");
header("Content-Type: text/xml");
$result = $rspec['rspec'];
if ($strip_comments) {
  $result = strip_comments($result);
}
print $result;

function strip_comments($rspec) {
    return preg_replace('/<!--(.*)-->/Uis', '', $rspec);
}

?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2014 Raytheon BBN Technologies
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

/*
 * Enables browser download of RSpec from Jacks. RSpec is uploaded to
 * the server and then immediately downloaded via JavaScript browser
 * redirection.
 */

require_once("settings.php");
require_once("user.php");

$user = geni_loadUser();
if (!isset($user) || ! $user->isActive()) {
  error_log("Unauthorized user in saverspectoserver.");
  header('Unauthorized', true, 401);
  exit();
}

if(!array_key_exists('rspec', $_POST)) {
  error_log("No RSpec in POST for saverspectoserver for " . $user->email());
  header('Bad Request', true, 400);
  exit();
}

/* Is the uploaded RSpec valid? */
$rspec = $_POST['rspec'];
$dom_document = new DOMDocument();
$is_valid_xml = $dom_document->loadXML($rspec);
if ($is_valid_xml === false) {
  /* The parse failed. */
  error_log("RSpec not parseable in saverspectoserver for " . $user->email());
  header('Not Acceptable', true, 406);
  exit();
}

/*
 * At this point the data is parseable as XML and the user is
 * known. Log the activity, store the data, and return the filename
 * for retrieval.
 */
error_log("Saving RSpec to server for " . $user->email());
$tempfile = tempnam('/tmp', 'saverspectoserver');
if ($tempfile === FALSE) {
  /* tempnam failed. bummer. */
  error_log("tempnam failed in saverspectoserver for " . $user->email());
  header('Internal Server Error', true, 500);
  exit();
}
$nbytes = file_put_contents($tempfile, $rspec);
if ($nbytes === FALSE) {
  /* file_put_contents failed. bummer. */
  error_log("file_put_contents failed in saverspectoserver for "
            . $user->email());
  header('Internal Server Error', true, 500);
  exit();
}
error_log("Saved $nbytes bytes of RSpec to server in $tempfile for "
          . $user->email());

/*
 * FIXME: Don't return the actual filename. Return a token that can be
 * converted to a filename somehow.
 */
print $tempfile;
return;
?>

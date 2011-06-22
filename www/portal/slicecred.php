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
require_once("settings.php");
require_once("user.php");
$user = geni_loadUser();
if (! $user->privSlice()) {
  exit();
}
?>
<?php
function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
if (array_key_exists('id', $_GET)) {
  $slice_id = $_GET['id'];
} else {
  no_slice_error();
}
// Look up the slice...
$slice = fetch_slice($slice_id);

// TODO: Does the $user have permissions on this $slice?
// TODO: Pass expiration to slicecred.py


$key = db_fetch_public_key($user->account_id);
if (! $key) {
  relative_redirect("home.php");
}
$cert_file = tempnam(sys_get_temp_dir(), 'portal');
file_put_contents($cert_file, $key['certificate']);

// Run slicecred.py and return it as the content.
$cmd_array = array($portal_gcf_dir . '/src/slicecred.py',
                   $portal_gcf_cfg_dir . '/gcf.ini',
                   $slice['name'],
                   $portal_gcf_cfg_dir . '/ch-key.pem',
                   $portal_gcf_cfg_dir . '/ch-cert.pem',
                   $cert_file
                   );
$command = implode(" ", $cmd_array);
$result = exec($command, $output, $status);
//print_r($output);

// Clean up, clean up
//unlink($cert_file);

$file = $slice['name'] . "-cred.xml";
// Set headers for download
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$file");
header("Content-Type: text/xml");
header("Content-Transfer-Encoding: binary");
print implode("\n", $output);
?>

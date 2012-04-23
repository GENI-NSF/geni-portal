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
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// Look up the key...
$public_key = db_fetch_public_key($user->account_id);
if (! $public_key) {
  relative_redirect("home.php");
}

if ($public_key['certificate'] == NULL) {
  // Write the public key to a file
  $key_file = tempnam(sys_get_temp_dir(), 'portal');
  file_put_contents($key_file, $public_key['public_key']);

  // Run gen-certs.py and return it as the content.
  $cmd_array = array($portal_gcf_dir . '/src/gen-certs.py',
                     '-f',
                     $portal_gcf_cfg_dir . '/gcf.ini',
                     '--notAll',
                     '-d',
                     '/tmp',
                     '-u',
                     $user->username,
                     '--pubkey',
                     $key_file,
                     '--exp'
                     );
  $command = implode(" ", $cmd_array);
  $result = exec($command, $output, $status);
  /* print_r($output); */
  // The cert is on disk, read the file and store it in the db.
  $cert_file = '/tmp/' . $user->username . "-cert.pem";
  $contents = file_get_contents($cert_file);
  db_add_key_cert($user->account_id, $contents);
  // Delete the temporary key file
  unlink($key_file);
  // Delete the cert file
  unlink($cert_file);
} else {
  $contents = $public_key['certificate'];
}

$filename = $public_key['filename'];
if (! isset($filename) || is_null($filename) || trim($filename) == '') {
  $pn = $user->prettyName();
  $filename_base=str_replace(' ', '', $pn);
} else {
  $f_pi = pathinfo($filename);
  $filename_base = $f_pi['filename'];
}
$filename = $filename_base . "-cert.pem";

// Set headers for download
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$filename");
header("Content-Type: application/pem");
header("Content-Transfer-Encoding: binary");
print $contents;
?>

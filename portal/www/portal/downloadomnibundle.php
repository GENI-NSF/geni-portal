<?php
//----------------------------------------------------------------------
// Copyright (c) 2013-2016 Raytheon BBN Technologies
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
require_once("am_client.php");
require_once("ma_client.php");

$user = geni_loadUser();
if (!isset($user)) {
  relative_redirect('home.php');
}

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);

$default_project = null;
$dest_file = 'omni.bundle';
if (array_key_exists('project', $_REQUEST)) {
  $default_project = $_REQUEST['project'];
  $dest_file = $_REQUEST['file'];
}

$projects_info= get_project_info_for_member($sa_url, $user, 
						$user->account_id);
// error_log("PROJECTS = " . print_r($projects_info, true));


// Add ssh keys to zip
function add_ssh_keys_to_zip($keys, $zip) {
  $filenames = array();

  // Sort key_info to do those with no private key first, so we can rename
  // those with a private key if necessary afterwards
  if (count($keys) > 1) {
    $ki2 = array();
    foreach ($keys as $key_info) {
      $sshkey_priv = $key_info[MA_SSH_KEY_TABLE_FIELDNAME::PRIVATE_KEY];
      if (! isset($sshkey_priv)) {
	$ki2[] = $key_info;
      }
    }
    foreach ($keys as $key_info) {
      $sshkey_priv = $key_info[MA_SSH_KEY_TABLE_FIELDNAME::PRIVATE_KEY];
      if (! in_array($key_info, $ki2)) {
	$ki2[] = $key_info;
      }
    }
    $keys = $ki2;
  }

  foreach ($keys as $key_info) {
    $ssh_filename = $key_info[MA_SSH_KEY_TABLE_FIELDNAME::FILENAME];
    $sshkey_pub = $key_info[MA_SSH_KEY_TABLE_FIELDNAME::PUBLIC_KEY];
    $sshkey_priv = $key_info[MA_SSH_KEY_TABLE_FIELDNAME::PRIVATE_KEY];
    if (isset($sshkey_priv)) {
      // A private key exists. This is a generated key, so
      // filename is for private key.

      // Ticket 1063: If there is already a keyfile in the zip with this name,
      // use a different name.
      // Note we rename only where we have both parts of the keypair.
      while (in_array($ssh_filename, $filenames) or in_array("$ssh_filename.pub", $filenames)) {
	$ssh_filename .= "1";
      }
      $zip->addFromString("ssh/private/$ssh_filename", $sshkey_priv);
      $filenames[] = $ssh_filename;
      // append .pub for the public key
      $ssh_filename .= ".pub";
    }
    // Always add the public key
    if (! isset($sshkey_priv) and in_array($ssh_filename, $filenames)) {
      // We've already put a key in the bundle with this filename
      // But we don't have the private key, and if we rename it,
      // it won't match the private key. We either skip downloading this key,
      // or include it and break the earlier keypair
      error_log($_SERVER['eppn'] . ": omni bundle problem: 2 SSH keys with filename $ssh_filename");
    }
    $zip->addFromString("ssh/public/$ssh_filename", $sshkey_pub);
    $filenames[] = $ssh_filename;
  }
}


// Get the user's ssh keys
$keys = $user->sshKeys();

// Get the user's outside cert/key and pack them
// into a single "file" for inclusion in the zip bundle.
$result = ma_lookup_certificate($ma_url, $user, $user->account_id);
$geni_cert_pem = "";
if (key_exists(MA_ARGUMENT::PRIVATE_KEY, $result)) {
  $geni_cert_pem .= $result[MA_ARGUMENT::PRIVATE_KEY];
  $geni_cert_pem .= "\n";
}
if (key_exists(MA_ARGUMENT::CERTIFICATE, $result)) {
  $geni_cert_pem .= $result[MA_ARGUMENT::CERTIFICATE];
}

/* For the template omni config, omni version must be 2.3.1
 * because the bundle feature in omni_configure.py did not
 * exist in earlier version. */
$omni_version = "2.3.1";
$omni_config = get_template_omni_config($user, $omni_version, $default_project);

$omni_version = "2.5";
$omni_config_chapi = get_template_omni_config($user, $omni_version, $default_project);

$zip = new ZipArchive();
$filename = tempnam(sys_get_temp_dir(), 'omnibundle');

$genilib_info = array('user_urn' => $user->urn,
		   'projects' => $projects_info);
$genilib_info_json = json_encode($genilib_info);

// Zip will open and overwrite the file, rather than try to read it.
$zip->open($filename, ZipArchive::OVERWRITE);
$zip->addFromString('omni_config', $omni_config);
$zip->addFromString('omni_config_chapi', $omni_config_chapi);
$zip->addFromString('geni_cert.pem', $geni_cert_pem);
$zip->addFromString('genilib.json', $genilib_info_json);
add_ssh_keys_to_zip($keys, $zip);
$zip->close();

/* Load the contents of the zip into memory. */
$zip_bundle = file_get_contents($filename);
/* Delete the temp file. */
unlink($filename);

$_SESSION['lastmessage'] = "Downloaded '$dest_file'";

// Set headers for download
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$dest_file");
header("Content-Type: application/zip");
echo $zip_bundle;
?>

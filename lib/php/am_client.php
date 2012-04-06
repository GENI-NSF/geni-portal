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

// Routines for clients speaking to an aggregate maanger

require_once('file_utils.php');

/*
 * Writes the user's ssh keys to files for use in the omni config file.
 *
 * Returns an array of temporary filenames. The caller is responsible
 * for deleting (unlink) these files.
 */
function write_ssh_keys($user)
{
  $result = array();
  $ssh_keys = fetchSshKeys($user->account_id);
  foreach ($ssh_keys as $key_info)
    {
      $key = $key_info['public_key'];
      $tmp_file = writeDataToTempFile($key);
      $result[] = $tmp_file;
    }
  return $result;
}


// Generic invocation of omni function 
// Args:
//    $am_url: URL of AM to which to connect
//    $user : Structure with user information (for creating temporary files)
//    $args: list of arguments (including the method itself) included
function invoke_omni_function($am_url, $user, $args)
{
    $username = $user->username;

    /* Write key and credential files */
    $row = db_fetch_inside_private_key_cert($user->account_id);
    $cert = $row['certificate'];
    $private_key = $row['private_key'];
    $cert_file = '/tmp/' . $username . "-cert.pem";
    $key_file = '/tmp/' . $username . "-key.pem";
    $omni_file = '/tmp/' . $username . "-omni.ini";

    file_put_contents($cert_file, $cert);
    file_put_contents($key_file, $private_key);

    $ssh_key_files = write_ssh_keys($user);
    $all_key_files = implode(',', $ssh_key_files);

    /* Create OMNI config file */
    $omni_config = "[omni]\n"
      . "default_cf = my_gcf\n"
      . "users = $username\n"
      . "[my_gcf]\n"
      . "type=gcf\n"
      . "authority=geni:gpo:portal\n"
      . "ch=https://localhost:8000\n"
      . "cert=$cert_file\n"
      . "key=$key_file\n"
      . "[$username]\n"
      . "urn=urn:publicid:IDN+geni:gpo:portal+user+$username\n"
      . "keys=$all_key_files\n";
    file_put_contents($omni_file, $omni_config);

    /* Call OMNI */
    global $portal_gcf_dir;
    $cmd_array = array($portal_gcf_dir . '/src/omni.py',
		       '-c',
		       $omni_file,
		       '-a',
		       $am_url, 
		       '--api-version',
		       '2');
    for($i = 0; $i < count($args); $i++) {
      $cmd_array[] = $args[$i];
    }
    $command = implode(" ", $cmd_array);

     error_log("COMMAND = " . $command);
     $handle = popen($command . " 2>&1", "r");
     $output= '';
     $read = fread($handle, 1024);
     while($read != null) {
       if ($read != null)
	 $output = $output . $read;
       $read = fread($handle, 1024);
     }
     pclose($handle);
  
     //     error_log("OUTPUT:" . $output);

     unlink($cert_file);
     unlink($key_file);
     unlink($omni_file);
     foreach ($ssh_key_files as $tmpfile) {
       unlink($tmpfile);
     }
     return $output;
}

// Get version of AM API at given AM
function get_version($am_url, $user)
{
  $args = array('getversion');
  $output = invoke_omni_function($am_url, $user, $args);
  return $output;
}


// List resources available at an aggregate
function list_resources($am_url, $user)
{
  $args = array('listresources');
  $output = invoke_omni_function($am_url, $user, $args);
  return $output;
}

// Create a sliver on a given AM with given rspec
function create_sliver($am_url, $user, $slice_credential, $slice_name, $rspec_filename)
{
  $slice_credential_filename = '/tmp/' . $user->username . ".slicecredential";
  file_put_contents($slice_credential_filename, $slice_credential);
  //  $user_credential_filename = '/tmp/' . $user->username . ".usercredential";
  //  file_put_contents($slice_credential_filename, $user_credential);
  $args = array("--slicecredfile", 
		$slice_credential_filename, 
		//		"--usercredfile", 
		//		$user_cred_filename, 
		'createsliver',
		$slice_name, 
		$rspec_filename);
  $output = invoke_omni_function($am_url, $user, $args);
  unlink($slice_credential_filename);
  return $output;
}



?>

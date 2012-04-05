<?php

// Routines for clients speaking to an aggregate maanger

// Generic invocation of omni function 
// Args:
//    $am_url: URL of AM to which to connect
//    $user : Structure with user information (for creating temporary files)
//    $args: list of arguments (including the method itself) included
function invoke_omni_function($am_url, $user, $args)
{
    /* Write key and credential files */
    $row = db_fetch_inside_private_key_cert($user->account_id);
    $cert = $row['certificate'];
    $private_key = $row['private_key'];
    $cert_file = '/tmp/' . $user->username . "-cert.pem";
    $key_file = '/tmp/' . $user->username . "-key.pem";	
    $omni_file = '/tmp/' . $user->username . "-omni.ini";

    file_put_contents($cert_file, $cert);
    file_put_contents($key_file, $private_key);

    /* Create OMNI config file */
    $omni_config = "[omni]\n"
    . "default_cf = my_gcf\n"
    . "[my_gcf]\n"
    . "type=gcf\n"
    . "authority=geni:gpo:portal\n"
    . "ch=https://localhost:8000\n"
    . "cert=" . $cert_file . "\n"
    . "key=" . $key_file . "\n";
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

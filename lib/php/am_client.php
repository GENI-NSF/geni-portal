<?php

// Routines for clients speaking to an aggregate maanger

function invoke_omni_function($am_url, $user, $omni_method)
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
		       '2',
		       $omni_method
		       );
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

function get_version($am_url, $user)
{
  $output = invoke_omni_function($am_url, $user, "getversion");
  return $output;
}

function list_resources($am_url, $user)
{
  $output = invoke_omni_function($am_url, $user, "listresources");
  return $output;
}



?>

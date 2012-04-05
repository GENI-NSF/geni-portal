<?php

// Routines for clients speaking to an aggregate maanger

function get_version($am_url, $user)
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
		       "getversion",
		       );
     $command = implode(" ", $cmd_array);
     //     error_log("COMMAND = " . $command);
     $result = exec($command, $output, $status);
     //     error_log("OUTPUT:" . $output);
     //     error_log("COUNT(OUTPUT):" . count($output));
     //     foreach($output as $line) {
     //       error_log("LINE:" . $line);
     //     }
     //     error_log("STATUS:" . $status);
     //     error_log("RESULT" . $result);
//     print_r($output);  
//     print_r($result);
//     print "RESULT = " . $result . "\n";
//     print "STATUS = " . $status . "\n";
     unlink($cert_file);
     unlink($key_file);
     unlink($omni_file);
     return $result;
}

function list_resources($am_url, $user)
{
  // We're going to call omni to do this 
  $result = omni_list_resources($am_url, $user);
  return $result;
}

function omni_list_resources($am_url, $user)
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
		       '-o', 
		       '--api-version',
		       '2',
		       '-t', 
		       'GENI',
		       '3',
		       'listresources'
		       );
     $command = implode(" ", $cmd_array);
     $result = exec($command, $output, $status);
//     print_r($output);  
//     print_r($result);
//     print "RESULT = " . $result . "\n";
//     print "STATUS = " . $status . "\n";
     unlink($cert_file);
     unlink($key_file);
     unlink($omni_file);

}



?>

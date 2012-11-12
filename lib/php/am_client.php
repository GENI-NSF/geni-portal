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
require_once 'geni_syslog.php';
require_once 'logging_client.php';
require_once 'sr_client.php';
require_once 'portal.php';


function log_action($op, $user, $agg, $slice = NULL, $rspec = NULL)
{
  $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
  $user_id = $user->account_id;
  if (! is_array($agg)) {
    $aggs[] = $agg;
  } else {
    $aggs = $agg;
  }
  foreach ($aggs as $am) {
    $attributes['aggregate'] = $am;
    $msg = "$op at $am";
    if ($slice) {
      $msg .= " on $slice";
      $attributes['slice'] = $slice;
    }
    if ($rspec) {
      $attributes['rspec'] = $rspec;
    }
    $result = log_event($log_url, Portal::getInstance(), $msg, $attributes, $user_id);
  }
}

/*
 * Writes the user's ssh keys to files for use in the omni config file.
 *
 * Returns an array of temporary filenames. The caller is responsible
 * for deleting (unlink) these files.
 */
function write_ssh_keys($user)
{
  $result = array();
  $ssh_keys = $user->sshKeys();
  foreach ($ssh_keys as $key_info)
    {
      $key = $key_info['public_key'];
      $tmp_file = writeDataToTempFile($key);
      $result[] = $tmp_file;
    }
  return $result;
}

function get_template_omni_config($user)
{
    /* Create OMNI config file */
    $username = $user->username;
    $urn = $user->urn();

    // Add shortcuts for all known AMs?
    // Note this makes the config long in the extreme case....
    require_once("sr_client.php");
    require_once("sr_constants.php");
    $ams = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
    $nicknames = "";
    foreach ($ams as $am) {
      $name = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
      $url = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
      if (! isset($name) || is_null($name) || trim($name) == '') {
	continue;
      }
      // skip AMs running on localhost as they aren't accessible anywhere else
      if (strpos($url, '://localhost')!==false ) {
	continue;
      }

      $name = str_replace(' ', '-', $name);
      $name = str_replace(',', '', $name);
      $name = str_replace('=', '', $name);
      $nicknames = $nicknames . $name . "=," . $url . "\n";
    }

    $http_host = $_SERVER['HTTP_HOST'];
    $tmpAuth = explode(".", $http_host, 2);
    $authority = $tmpAuth[0];
    $sa_ch_port = 8443;
    $SA_URL = "$http_host:$sa_ch_port";
    $CH_URL = "$http_host:$sa_ch_port";

    $omni_config = "[omni]\n"
      . "default_cf = portal\n"
      . "users = $username\n"
      . "\n"
      . "[portal]\n"
      . "type=pg\n"
      . "ch=https://$CH_URL\n"
      . "sa=https://$SA_URL\n"
      . "cert=/PATH/TO/YOUR/CERTIFICATE/AS/DOWNLOADED/FROM/PORTAL-cert.pem\n"
      . "key=/PATH/TO/YOUR/PRIVATE/SSL/KEY.pem\n"
      . "\n"
      . "[$username]\n"
      . "urn=$urn\n"
      . "keys=/PATH/TO/SSH/PUBLIC/KEY.pub\n";

    $omni_config = $omni_config
      . "\n"
      . "[aggregate_nicknames]\n"
      . $nicknames;

    return $omni_config;
}

/**
 * Create a temporary omni config file for $user and return the file
 * name.
 *
 * N.B. the caller is responsible for removing the file (via unlink()).
 */
function write_omni_config($user)
{
    $username = $user->username;

    /* Write key and credential files. */
    $cert = $user->certificate();
    $cert_file = writeDataToTempFile($cert, "$username-cert-");
    $private_key = $user->privateKey();
    $key_file = writeDataToTempFile($private_key, "$username-key-");

    /* Write ssh keys to tmp files. */
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

    $omni_file = writeDataToTempFile($omni_config, "$username-omni-");

    $result = array($omni_file, $cert_file, $key_file);
    foreach ($ssh_key_files as $f) {
      $result[] = $f;
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
    
    
    $aggregates = "aggregates=";
    $first=True;
    if (is_array($am_url)){
      foreach ($am_url as $single){
	if (! isset($single) || is_null($single) || $single == '') {
	  error_log("am_client cannot invoke Omni with invalid AM URL");
	  return("Invalid AM URL");
	}
	if ($first){
	  $first=False;
	} else {
	  $aggregates = $aggregates.", ";	  
	}
	$aggregates = $aggregates.$single;
      }
      $aggregates = $aggregates."\n";
    }elseif (! isset($am_url) || is_null($am_url) || $am_url == '') {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }

    /* Write key and credential files */
    $cert = $user->certificate();
    $private_key = $user->privateKey();
    $cert_file = '/tmp/' . $username . "-cert.pem";
    $key_file = '/tmp/' . $username . "-key.pem";
    $omni_file = '/tmp/' . $username . "-omni.ini";
    $tmp_version_cache = tempnam(sys_get_temp_dir(),
            'omniVersionCache');

    file_put_contents($cert_file, $cert);
    file_put_contents($key_file, $private_key);

    $ssh_key_files = write_ssh_keys($user);
    $all_key_files = implode(',', $ssh_key_files);

    /* Create OMNI config file */
    $omni_config = "[omni]\n"
      . "default_cf = my_gcf\n"
      . "users = $username\n";
    if (is_array($am_url)){
      $omni_config = $omni_config.$aggregates."\n";
    }
    $omni_config = $omni_config
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

    /*    $cmd_array = array($portal_gcf_dir . '/src/omni.py', */
    $cmd_array = array($portal_gcf_dir . '/src/omni_php.py',
		       '-c',
		       $omni_file,
		       '-l',
		       $portal_gcf_dir . '/src/logging.conf',
		       '--logoutput /tmp/omni.log',
		       '--api-version',
		       '2',
            "--GetVersionCacheName",
            $tmp_version_cache);

    if (!is_array($am_url)){
      $cmd_array[]='-a';
      $cmd_array[]=$am_url;
    }

    for($i = 0; $i < count($args); $i++) {
      $cmd_array[] = $args[$i];
    }
    $command = implode(" ", $cmd_array);

     error_log("am_client invoke_omni_function COMMAND = " . $command);
     $handle = popen($command . " 2>&1", "r");
     $output= '';
     $read = fread($handle, 1024);
     while($read != null) {
       if ($read != null)
	 $output = $output . $read;
       $read = fread($handle, 1024);
     }
     pclose($handle);
  
     unlink($cert_file);
     unlink($key_file);
     unlink($omni_file);
     unlink($tmp_version_cache);
     foreach ($ssh_key_files as $tmpfile) {
       unlink($tmpfile);
     }

     $output2 = json_decode($output, True);
     if (is_null($output2)) {
       error_log("am_client invoke_omni_function:"
               . "JSON result is not parseable: \"$output\"");
     }
     return $output2;
}

// Get version of AM API at given AM
function get_version($am_url, $user)
{
  if (! isset($am_url) || is_null($am_url) ){
    if (!(is_array($am_url) || $am_url != '')) {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }
  }

  $member_id = $user->account_id;
  $msg = "User $member_id calling GetVersion at $am_url";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
  log_action("GetVersion", $user, $am_url);
  $args = array('getversion');
  $output = invoke_omni_function($am_url, $user, $args);
  return $output;
}


// List resources available at an aggregate
function list_resources($am_url, $user)
{
  if (! isset($am_url) || is_null($am_url) ){
    if (!(is_array($am_url) || $am_url != '')) {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }
  }

  $member_id = $user->account_id;
  $msg = "User $member_id calling ListResources at $am_url";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
  log_action("ListResources", $user, $am_url);
  $args = array('-t', 'GENI', '3', 'listresources');
  $output = invoke_omni_function($am_url, $user, $args);
  return $output;
}

// list resources at an AM
function list_resources_on_slice($am_url, $user, $slice_credential, $slice_urn)
{
  if (! isset($am_url) || is_null($am_url) ){
    if (!(is_array($am_url) || $am_url != '')) {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }
  }

  if (! isset($slice_credential) || is_null($slice_credential) || $slice_credential == '') {
    error_log("am_client cannot act on a slice without a credential");
    return("Missing slice credential");
  }

  $member_id = $user->account_id;
  $msg = "User $member_id calling ListResources at $am_url on $slice_urn";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
  log_action("ListResources", $user, $am_url, $slice_urn);
  $slice_credential_filename = '/tmp/' . $user->username . ".slicecredential";
  file_put_contents($slice_credential_filename, $slice_credential);
  $args = array("--slicecredfile",
		$slice_credential_filename,
		'-t',
		'GENI',
		'3',
		'listresources',
		$slice_urn);
  $output = invoke_omni_function($am_url, $user, $args);
  unlink($slice_credential_filename);
  return $output;
}


// renewsliver at an AM
function renew_sliver($am_url, $user, $slice_credential, $slice_urn, $time)
{
  if (! isset($am_url) || is_null($am_url) ){
    if (!(is_array($am_url) || $am_url != '')) {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }
  }

  if (! isset($slice_credential) || is_null($slice_credential) || $slice_credential == '') {
    error_log("am_client cannot act on a slice without a credential");
    return("Missing slice credential");
  }

  $member_id = $user->account_id;
  $msg = "User $member_id calling RenewSliver at $am_url on $slice_urn";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
  log_action("RenewSliver", $user, $am_url, $slice_urn);
  $slice_credential_filename = '/tmp/' . $user->username . ".slicecredential";
  file_put_contents($slice_credential_filename, $slice_credential);
  $args = array("--slicecredfile",
		$slice_credential_filename,
		'renewsliver',
		$slice_urn,
		$time);
  $output = invoke_omni_function($am_url, $user, $args);
  // FIXME: Note that this AM still has resources
  unlink($slice_credential_filename);
  return $output;
}


// Create a sliver on a given AM with given rspec
function create_sliver($am_url, $user, $slice_credential, $slice_urn,
                       $rspec_filename)
{
  if (! isset($am_url) || is_null($am_url) ){
    if (!(is_array($am_url) || $am_url != '')) {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }
  }

  if (! isset($slice_credential) || is_null($slice_credential) || $slice_credential == '') {
    error_log("am_client cannot act on a slice without a credential");
    return("Missing slice credential");
  }

  $member_id = $user->account_id;
  $msg = "User $member_id calling CreateSliver at $am_url on $slice_urn";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
  $rspec = file_get_contents($rspec_filename);
  log_action("CreateSliver", $user, $am_url, $slice_urn, $rspec);
  $slice_credential_filename = writeDataToTempFile($slice_credential);
  $args = array("--slicecredfile", 
		$slice_credential_filename, 
		'createsliver',
		$slice_urn,
		$rspec_filename);
  // FIXME: Note that this AM has resources
  // slice_id, am_url or ID, duration?
  $output = invoke_omni_function($am_url, $user, $args);
  unlink($slice_credential_filename);
  return $output;
}

// Get sliver status at an AM
function sliver_status($am_url, $user, $slice_credential, $slice_urn)
{
  if (! isset($am_url) || is_null($am_url) ){
    if (!(is_array($am_url) || $am_url != '')) {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }
  }

  if (! isset($slice_credential) || is_null($slice_credential) || $slice_credential == '') {
    error_log("am_client cannot act on a slice without a credential");
    return("Missing slice credential");
  }

  // Skip the log message, it's too detailed
  // $member_id = $user->account_id;
  // $msg = "User $member_id calling SliverStatus at $am_url on $slice_urn";
  // geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
  // log_action("SliverStatus", $user, $am_url, $slice_urn);
  $slice_credential_filename = '/tmp/' . $user->username . ".slicecredential";
  file_put_contents($slice_credential_filename, $slice_credential);
  $args = array("--slicecredfile",
		$slice_credential_filename,
		'sliverstatus',
		$slice_urn);
  $output = invoke_omni_function($am_url, $user, $args);
  unlink($slice_credential_filename);
  return $output;
}

// Delete a sliver at an AM
function delete_sliver($am_url, $user, $slice_credential, $slice_urn)
{
  if (! isset($am_url) || is_null($am_url) ){
    if (!(is_array($am_url) || $am_url != '')) {
      error_log("am_client cannot invoke Omni without an AM URL");
      return("Missing AM URL");
    }
  }

  if (! isset($slice_credential) || is_null($slice_credential) || $slice_credential == '') {
    error_log("am_client cannot act on a slice without a credential");
    return("Missing slice credential");
  }

  $member_id = $user->account_id;
  $msg = "User $member_id calling DeleteSliver at $am_url on $slice_urn";
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, $msg);
  log_action("DeleteSliver", $user, $am_url, $slice_urn);
  $slice_credential_filename = '/tmp/' . $user->username . ".slicecredential";
  file_put_contents($slice_credential_filename, $slice_credential);
  $args = array("--slicecredfile",
		$slice_credential_filename,
		'deletesliver',
		$slice_urn);
  // Note that this AM no longer has resources
  $output = invoke_omni_function($am_url, $user, $args);
  unlink($slice_credential_filename);
  return $output;
}

function ready_to_login($am_url, $user, $slice_cred, $slice_urn)
{
  global $portal_gcf_dir;

  $tmp_files = write_omni_config($user);
  $omni_config = $tmp_files[0];

  $slice_cred_file = writeDataToTempFile($slice_cred);
  $tmp_files[] = $slice_cred_file;

  $cmd_array = array($portal_gcf_dir . '/examples/readyToLogin.py',
                     '-c', $omni_config,
                     '-a', $am_url,
                     '--slicecredfile', $slice_cred_file,
                     $slice_urn);
  $command = implode(" ", $cmd_array);

  error_log("COMMAND = " . $command);
  putenv("PYTHONPATH=$portal_gcf_dir/src");
  $handle = popen($command . " 2>&1", "r");
  $output= '';
  $read = fread($handle, 1024);
  while($read != null) {
    if ($read != null)
      $output = $output . $read;
    $read = fread($handle, 1024);
  }
  pclose($handle);

  /* Now delete all the tmp files. */
  foreach ($tmp_files as $f) {
    unlink($f);
  }

  return $output;
}

?>

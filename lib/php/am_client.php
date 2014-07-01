<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
require_once 'sr_constants.php';
require_once 'portal.php';
require_once("pa_client.php");
require_once("pa_constants.php");
require_once('cert_utils.php');
require_once('cs_constants.php');

//Constants defined for proc_open
//String used for msg to return - in some UI - this is the message that is displayed
define("AM_CLIENT_TIMED_OUT_MSG", "Operation timed out", true);
//how long to wait before to time out omni process (in seconds) - try 12 minutes
define("AM_CLIENT_OMNI_KILL_TIME", 720);
//if want to test early omni termination
//define("AM_CLIENT_OMNI_KILL_TIME", 1);

function log_action($op, $user, $agg, $slice = NULL, $rspec = NULL, $slice_id = NULL)
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
      $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
					$slice_id);
      $attributes = array_merge($attributes, $slice_attributes);
    }
    if ($rspec) {
      $attributes['rspec'] = $rspec;
    }
    $result = log_event($log_url, $user, $msg, $attributes);
  }
}

/*
 * Writes the user's ssh keys to files for use in the omni config file.
 *
 * Returns an array of temporary filenames. The caller is responsible
 * for deleting (unlink) these files.
 */
function write_ssh_keys($for_user, $as_user, $dir)
{
  $result = array();

  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $ssh_keys = lookup_public_ssh_keys($ma_url, $as_user, $for_user->account_id);

  foreach ($ssh_keys as $key_info)
    {
      $key = $key_info['public_key'];
      $tmp_file = writeDataToTempDir($dir, $key, "ssh-key-" . $for_user->username);
      $result[] = $tmp_file;
    }
  return $result;
}

function get_template_omni_config($user, $version, $default_project=null)
{
  $legal_versions = array("2.3.1","2.5");
  if (! in_array($version, $legal_versions)) {
    /* If $version is not understood, default to omni 2.5. */
    $version = "2.5";
  }

    /* Create OMNI config file */
    $username = $user->username;
    $urn = $user->urn();
    // Get the authority from the user's URN
    parse_urn($urn, $authority, $type, $name);

    $pgchs = get_services_of_type(SR_SERVICE_TYPE::PGCH);
    if (count( $pgchs ) != 1) {
          error_log("am_client must have exactly one PGCH service defined to generate an omni_config");
	  return("Should be exactly one PGCH url.");
    } else {
        $pgch = $pgchs[0];
      	$PGCH_URL = $pgch[SR_TABLE_FIELDNAME::SERVICE_URL];	
    }

    $omni_config = '# This omni configuration file is for use with omni version ';
    $omni_config .= $version . ' or higher';
    $omni_config .= "\n";
    $omni_config .= "[omni]\n";

    if ($version == "2.5") {
      $omni_config .= "default_cf = portal_chapi\n";
    }
   
    if ($version == "2.3.1") {
      $omni_config .= "default_cf = portal\n";
    }

    $omni_config .= "# 'users' is a comma separated list of users which should be added to a slice.\n"
      . "# Each user is defined in a separate section below.\n"
      . "users = $username\n";
    if ($version == "2.5") {
    $omni_config .= "# Over-ride the commandline setting of --useSliceMembers to force it True\n"
      . "useslicemembers = True\n";
    }

     $omni_config = $omni_config		
      . "# 'default_project' is the name of the project that will be assumed\n"
      . "# unless '--project' is specified on the command line.\n"
      . "# Uncomment only one of the following lines if you want to use this feature\n";

    if (! isset($sa_url)) {
       $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);	
    }
    if (! isset($ma_url)) {
       $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);	
    }
    $projects = get_projects_for_member($sa_url, $user, $user->account_id, true);	
    if (count($projects) > 0 && is_null($default_project)) {
      $p0 = $projects[0];
      $default_project = $p0[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    }
    foreach ($projects as $project_id) {
      $project = lookup_project($sa_url, $user, $project_id);
      $proj_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      if ($proj_name == $default_project) {
        $omni_config .= "default_project = $proj_name\n";
      } else {
        $omni_config .= "#default_project = $proj_name\n";
      }
    }

    $omni_config .= "\n"
      . "[portal_chapi]\n"
      . "# For use with the Uniform Federation API\n"
      . "# NOTE: Only works with Omni 2.5 or newer\n"
      . "type = chapi\n"
      . "# Authority part of the control framework's URN\n"
      . "authority=$authority\n"
      . "# Where the CH API server's Clearinghouse service is listening.\n"
      . "# This will be used to find the MA and SA\n"
      . "ch=https://$authority:8444/CH\n"
      . "# Optionally you may explicitly specify where the MA and SA are\n"
      . "#  running, in which case the Clearinghouse service is not used\n"
      . "#  to find them\n"
      . "ma = $ma_url\n"
      . "sa = $sa_url\n"
      . "cert = /PATH/TO/YOUR/CERTIFICATE/AS/DOWNLOADED/FROM/PORTAL/geni-$username.pem\n"
      . "key = /PATH/TO/YOUR/CERTIFICATE/AS/DOWNLOADED/FROM/PORTAL/geni-$username.pem\n"
      . "# For debugging\n"
      . "verbose=false\n"
      . "\n";

    $omni_config .= "\n"
      . "[portal]\n"
      . "type = pgch\n"
      . "authority=$authority\n"
      . "ch = $PGCH_URL\n"
      . "sa = $PGCH_URL\n"
      . "cert = /PATH/TO/YOUR/CERTIFICATE/AS/DOWNLOADED/FROM/PORTAL/geni-$username.pem\n"
      . "key = /PATH/TO/YOUR/CERTIFICATE/AS/DOWNLOADED/FROM/PORTAL/geni-$username.pem\n"
      . "\n";

    $omni_config .= "[$username]\n"
      . "urn = $urn\n"
      . "# 'keys' is a comma separated list of ssh public keys which should be added to this user's account.\n"
      . "keys = /PATH/TO/SSH/PUBLIC/KEY.pub\n";

    $omni_config = $omni_config
      . "\n";

    return $omni_config;
}

// Lookup any attributes of aggregate associated with given AM URL
// Return null if no attribute for that name defined
function lookup_attribute($am_url, $attr_name)
{
  $services = get_services();
  $am_service = null;
  foreach($services as $service) {
    if(array_key_exists(SR_ARGUMENT::SERVICE_URL, $service) && 
       $service[SR_ARGUMENT::SERVICE_URL] == $am_url) {
      $am_service = $service;
      break;
    }
  }
  if($am_service)
    return lookup_service_attribute($am_service, $attr_name);
  else
    return null;
}

// Generic invocation of omni function 
// Args:
//    $am_url: URL of AM to which to connect
//    $user : Structure with user information (for creating temporary files)
//    $args: list of arguments (including the method itself) included
function invoke_omni_function($am_url, $user, $args, $slice_users=array())
{
  $file_manager = new FileManager(); // Hold onto all allocated filenames

  // We seem to get $am_url sometimes as a string, sometimes as an array
  // Should always talk to single AM
  if(!is_string($am_url) && is_array($am_url)) { $am_url = $am_url[0]; }

  // Does the given URL handle speaks-for?
  $handles_speaks_for = 
    lookup_attribute($am_url, SERVICE_ATTRIBUTE_SPEAKS_FOR) == 't';

  /*
    If an aggregate doesn't handle speaks-for, 
    we use the inside cert and key of the user
    If an aggregate DOES handle speaks-for and the
    user has a speaks-for credential, 
    portal's cert and key and pass along the geni_speaking_for option
   */
  $speaks_for_invocation = false;
  $cert = $user->insideCertificate();
  $private_key = $user->insidePrivateKey();
  $speaks_for_cred = $user->speaksForCred();

  if ($handles_speaks_for and $speaks_for_cred) {
      $speaks_for_invocation = true;
      $cert = $user->certificate();
      $private_key = $user->privateKey();
    }

    $username = $user->username;
    $urn = $user->urn();
    // Get the authority from the user's URN
    parse_urn($urn, $authority, $type, $name);
    
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
    
    /* Create a directory to store all temp files, including logs and error
       messages. Let the prefix be the username. A "session" is each time
       invoke_omni_function() is called.
    
       Returns something like: /tmp/myuser-RKvQ1Z
    */
    $omni_session_dir = createTempDir($username);
    if(is_null($omni_session_dir)) {
        // FIXME: What to do if directory can't be created?
        error_log("Could not create temporary directory for omni session: $omni_session_dir");
    }

    /* Write key and credential files */
    $tmp_version_cache = "$omni_session_dir/omniVersionCache";
    $tmp_agg_cache = "$omni_session_dir/omniAggCache";
    $file_manager->add($tmp_version_cache);
    $file_manager->add($tmp_agg_cache);

    $cert_file = writeDataToTempDir($omni_session_dir, $cert, "cert");
    $file_manager->add($cert_file);
    $key_file = writeDataToTempDir($omni_session_dir, $private_key, "key");
    $file_manager->add($key_file);

    $slice_users = $slice_users + array($user);
    $username_array = array();
    foreach ($slice_users as $slice_user){
    	   $username_array[] = $slice_user->username;
    }

    /* Create OMNI config file */

    if (! isset($sa_url)) {
       $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);	
    }
    if (! isset($ma_url)) {
       $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);	
    }

    $omni_config = "[omni]\n"
      . "default_cf = my_chapi\n"
      . "users = "
      . implode(", ", $username_array)
      . "\n";
    if (is_array($am_url)){
      $omni_config = $omni_config.$aggregates."\n";
    }

    // FIXME: If SR had AM nicknames, we could write a nickname to the
    // omni_config here. Or all known nicknames in the SR. That's
    // likely better than relying on the shared agg nick cache. For
    // now, copy a fixed file to a temp place (avoiding 1 omni
    // downloading a new copy while another reads, or 2 readers
    // conflicting somehow)
    global $portal_gcf_dir;
    if (!copy($portal_gcf_dir . '/agg_nick_cache.base', $tmp_agg_cache)) {
      error_log("Failed to copy Agg Nick Cache from " . $portal_gcf_dir . '/agg_nick_cache.base to ' . $tmp_agg_cache);
    }

    // FIXME: Get the /CH URL from a portal/www/portal/settings.php entry?

    $omni_config = $omni_config
      . "[my_chapi]\n"
      . "type=chapi\n"
      . "authority=$authority\n"
      . "ch=https://$authority:8444/CH\n"
      . "sa=$sa_url\n"
      . "ma=$ma_url\n"
      . "cert=$cert_file\n"
      . "key=$key_file\n";

    $all_ssh_key_files = array();
    foreach ($slice_users as $slice_user){
       $slice_username = $slice_user->username;
       $slice_urn = $slice_user->urn();	
       $ssh_key_files = write_ssh_keys($slice_user, $user, $omni_session_dir);
       $all_ssh_key_files = array_merge($all_ssh_key_files, $ssh_key_files);
       $all_key_files = implode(',', $ssh_key_files);
       $omni_config = $omni_config
             . "[$slice_username]\n"
             . "urn=$slice_urn\n"
      	     . "keys=$all_key_files\n";
    }

    foreach($all_ssh_key_files as $ssh_key_file) {
      $file_manager->add($ssh_key_file);
    }

    $omni_file = writeDataToTempDir($omni_session_dir, $omni_config, "omni-ini");
    $file_manager->add($omni_file);

    /* Call OMNI */

    $omni_log_file = "$omni_session_dir/omni-log";
    $omni_stderr_file = "$omni_session_dir/omni-stderr";
    $file_manager->add($omni_stderr_file);

    /*    $cmd_array = array($portal_gcf_dir . '/src/omni.py', */
    $cmd_array = array($portal_gcf_dir . '/src/stitcher_php.py',
		       '-c',
		       $omni_file,
		       //		       '--debug',
		       '-l',
		       $portal_gcf_dir . '/src/logging.conf',
		       '--logoutput', $omni_log_file,
		       '--api-version',
		       '2',
		       "--GetVersionCacheName",
		       $tmp_version_cache,
		       "--ForceUseAggNickCache", // Do not try to
		       //                           download the AM nickname definitions
		       "--AggNickCacheName", // Use an empty file that
		       //                       defines no AM nicknames
		       $tmp_agg_cache);

    $descriptor_spec = array(
                         // stdin is a pipe that the child will read from
                         0 => array("pipe", "r"),
                         // stdout is a pipe that the child will write to
                         1 => array("pipe", "w"),
                          // stderr is a file to write to
                         2 => array("file", $omni_stderr_file, "a"));

    /* stitcher.py: specify fileDir */
    $cmd_array[] = '--fileDir';
    $cmd_array[] = $omni_session_dir;

    if (!is_array($am_url)){
      $cmd_array[]='-a';
      $cmd_array[]=$am_url;
    }

    if ($speaks_for_invocation) {
      $cmd_array[] = "--speaksfor=" . $user->urn;
      $speaks_for_cred_filename = writeDataToTempDir($omni_session_dir, 
                $speaks_for_cred->credential(), "sfcred");
      $file_manager->add($speaks_for_cred_filename);
      $cmd_array[] = "--cred=" . $speaks_for_cred_filename;
    }

    for($i = 0; $i < count($args); $i++) {
      $cmd_array[] = $args[$i];
    }
    $command = implode(" ", $cmd_array);

     error_log("am_client invoke_omni_function COMMAND = " . $command);
     $handle = proc_open($command, $descriptor_spec, $pipes);

     stream_set_blocking($pipes[1], 0);
     // 1 MB
     $bufsiz = 1024 * 1024;
     $output= '';
     $outchunk = null;

     //time to terminate omni process
     $now = time();
     $kill_time = $now + AM_CLIENT_OMNI_KILL_TIME;

     while ($outchunk !== FALSE && ! feof($pipes[1]) && $now < $kill_time) {
       $outchunk = fread($pipes[1], $bufsiz);
       if ($outchunk != null && $outchunk !== FALSE) {
	 $output = $output . $outchunk;
         $usleep = 0;
       } else {
         // 0.25 seconds
         $usleep = 250000;
       }
       // If we got data, don't sleep, see if there's more ($usleep = 0)
       // If no data, sleep for a little while then check again.
       usleep($usleep);
       $now = time();
     }
     // Catch any final output after timeout
     $outchunk = fread($pipes[1], $bufsiz);
     if ($outchunk != null && $outchunk !== FALSE) {
       $output = $output . $outchunk;
     }

     //fclose($pipes[0]);
     //fclose($pipes[1]);
     //proc_close($handle);

     $status = proc_get_status($handle);
     if (!$status['running']) {
        fclose($pipes[0]);
     	fclose($pipes[1]);
	$return_value = $status['exitcode'];
	proc_close($handle);
     }  else {
    // Still running, terminate it.
    // See https://bugs.php.net/bug.php?id=39992, for problems
    // terminating child processes and a workaround involving posix_setpgid()
	fclose($pipes[0]);
	fclose($pipes[1]);
	$term_result = proc_terminate($handle);
	// Omni is taking too long to respond so
	// assign Timeout error message to output and this message may show up in UI
	//msg constant defined above
	$output = AM_CLIENT_TIMED_OUT_MSG;
     }

     /*
     unlink($cert_file);
     unlink($key_file);
     unlink($omni_file);
     unlink($tmp_version_cache);
     unlink($tmp_agg_cache);
     foreach ($all_ssh_key_files as $tmpfile) {
       unlink($tmpfile);
     }
     if ($speaks_for_invocation) {
       unlink($speaks_for_cred_filename);
     }
     */

     // Good for debugging but verbose
     //     error_log("am_client output " .  print_r($output, True));

     $output2 = json_decode($output, True);
     if (is_null($output2)) {
       error_log("am_client invoke_omni_function:"
               . "JSON result is not parseable: \"$output\"");
       // this is probably a traceback from python
       // return it as a string
       return $output;
     }
     /* Delete the log file only if the decoded output is an array and
        its length is 2 and the second value (index 1) is boolean true
        (not null or empty string).
      */
     /*if (is_array($output2) && count($output2) == 2 && $output2[1]) {
       unlink($omni_log_file);
     }*/
          error_log("Returning output2 : " . print_r($output2, True));
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
  log_action("Called GetVersion", $user, $am_url);
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
  log_action("Called ListResources", $user, $am_url);
  $args = array('-t', 'GENI', '3', 'listresources');
  $output = invoke_omni_function($am_url, $user, $args);
  return $output;
}

// list resources at an AM
function list_resources_on_slice($am_url, $user, $slice_credential, $slice_urn, $slice_id = NULL)
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
  // Don't actually do this - it is simply too verbose I think
  //  log_action("Called ListResources", $user, $am_url, $slice_urn, NULL, $slice_id);
  $slice_credential_filename = writeDataToTempFile($slice_credential, $user->username . "-cred-");
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
function renew_sliver($am_url, $user, $slice_credential, $slice_urn, $time, $slice_id = NULL)
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
  // Don't actually do this as the caller logs when this succeeds
  //  log_action("Called RenewSliver", $user, $am_url, $slice_urn, NULL, $slice_id);
  $slice_credential_filename = writeDataToTempFile($slice_credential, $user->username . "-cred-");
  $args = array("--slicecredfile",
		$slice_credential_filename,
		"--alap",
		'renewsliver',
		$slice_urn,
		$time);
  $output = invoke_omni_function($am_url, $user, $args);
  // FIXME: Note that this AM still has resources
  unlink($slice_credential_filename);
  return $output;
}


// Create a sliver on a given AM with given rspec
function create_sliver($am_url, $user, $slice_users, $slice_credential, $slice_urn,
                       $rspec_filename, $slice_id)
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
  // Don't log this: We already log from the caller if the allocation is successful
  //  log_action("Called CreateSliver", $user, $am_url, $slice_urn, $rspec, $slice_id);
  $slice_credential_filename = writeDataToTempFile($slice_credential, $user->username . "-cred-");
  $args = array("--slicecredfile", 
		$slice_credential_filename, 
		'createsliver',
		$slice_urn,
		$rspec_filename);
  // FIXME: Note that this AM has resources
  // slice_id, am_url or ID, duration?
  $output = invoke_omni_function($am_url, $user, $args, $slice_users);
  // FIXME: temporarily comment this out for stitching
  // unlink($slice_credential_filename);
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
  // log_action("Called SliverStatus", $user, $am_url, $slice_urn, NULL, $slice_id);
  $slice_credential_filename = writeDataToTempFile($slice_credential, $user->username . "-cred-");
  $args = array("--slicecredfile",
		$slice_credential_filename,
		'sliverstatus',
		$slice_urn);
  $output = invoke_omni_function($am_url, $user, $args);
  unlink($slice_credential_filename);
  return $output;
}

// Delete a sliver at an AM
function delete_sliver($am_url, $user, $slice_credential, $slice_urn, $slice_id = NULL)
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
  // Caller logs if the delete appeared successful, so don't bother doing this
  //  log_action("Called DeleteSliver", $user, $am_url, $slice_urn, NULL, $slice_id);
  $slice_credential_filename = writeDataToTempFile($slice_credential, $user->username . "-cred-");
  $args = array("--slicecredfile",
		$slice_credential_filename,
		'deletesliver',
		$slice_urn);
  // Note that this AM no longer has resources
  $output = invoke_omni_function($am_url, $user, $args);
  unlink($slice_credential_filename);
  return $output;
}

?>

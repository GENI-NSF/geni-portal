<?php
//----------------------------------------------------------------------
// Copyright (c) 2015-2016 Raytheon BBN Technologies
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

// Support for wireless operations for enabling/disabling projects
//   in ORBIT/Wireless/WiMAX testbeds and 
//   synchronizing project/member information between GENI and ORBIT
// 
//  wireless_operations?operation=sync&project_name=X
//    synchronize project data for project X between GENI and ORBIT
//
//  wireless_operations?operation=enable&project_name=X&project_id=XID
//    enable project X for wireless operations
//
//  wireless_operations?operation=disable&project_name=X&project_id=XID
//    disable project X for wireless operations
// 

?>

<?php


require_once('user.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("pa_client.php");
require_once("pa_constants.php");
require_once("response_format.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);


// Invoke geni-sync-wireless tool on given project
function sync_object($object_type, $object_name)
{
  # Should only provide error information on stderr: put stdout to syslog
  $cmd = "geni-sync-wireless $object_type $object_name";
  error_log("SYNC(cmd) " . $cmd);
  $descriptors = array(0 => array("pipe", "r",), 
		 1 => array("pipe", "w"), 
		 2 => array("pipe", "w"));
  $process = proc_open($cmd, $descriptors, $pipes);
  $std_output = stream_get_contents($pipes[1]); # Should be empty
  $err_output = stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $proc_value = proc_close($process);
  $full_output = $std_output . $err_output;

  foreach(split("\n", $full_output) as $line) {
    if(strlen(trim($line)) == 0) continue;
    error_log("SYNC(output) " . $line);
  }

  if ($proc_value != RESPONSE_ERROR::NONE)
    error_log("WIRELESS SYNC error: $proc_value");

  return $proc_value;
}

// Synch GENI and ORBIT project/group and member/user data for given project
function perform_wireless_sync($project_name) 
{
  $ret_code = sync_object("--project", $project_name);
  return generate_response($ret_code, "", array());
}

function perform_wireless_sync_for_user($username)
{
  $ret_code = sync_object("--user", $username);
  return generate_response($ret_code, "", array());
}

// Enable project for wireless by adding enable_wimax attribute
function perform_wireless_enable($sa_url, $user, $project_id, $project_name)
{
  $result = add_project_attribute($sa_url, $user, $project_id,
				  PA_ATTRIBUTE_NAME::ENABLE_WIMAX, 
				  $user->account_id);

  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $result;

  $ret_code = sync_object("--project", $project_name);
  return generate_response($ret_code, "", array());
}

// Disble project for wireless by removing enable_wimax attribute
function perform_wireless_disable($sa_url, $user, $project_id, $project_name)
{
  $success = remove_project_attribute($sa_url, $user, $project_id,
				     PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
  $error_code = 0;
  if (!$success) $error_code = RESPONSE_ERROR::DATABASE;

  $result = generate_response($error_code, "", array());
  return $result;
}

if(
   !array_key_exists('operation', $_GET) || 
   !array_key_exists('project_id', $_GET) ||
   !array_key_exists('project_name', $_GET))
    {
      $result = generate_response(RESPONSE_ERROR::ARGS,
				  "No operation or project_id or project_name specified to wireless_operations", 
				  array());
    } else {
  $operation = $_GET['operation'];
  $project_id = $_GET['project_id'];
  $project_name = $_GET['project_name'];
  if ($operation == 'sync') {
    $result = perform_wireless_sync($project_name);
  } else if ($operation == 'enable') {
    $result = perform_wireless_enable($sa_url, $user, $project_id, $project_name);
  } else if ($operation== 'disable') {
    $result = perform_wireless_disable($sa_url, $user, $project_id, $project_name);
  } else {
    $result = generate_response(RESPONSE_ERROR::ARGS,
				"Unsupported wireless operation: " + $_GET['operation'], 
				array());
  }
}

// Return JSON encoding of result
header("Cache-Control: public");
header("Content-Type: application/json");
print json_encode($result);

?>

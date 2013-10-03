<?php
//----------------------------------------------------------------------
// Copyright (c) 2013 Raytheon BBN Technologies
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

include_once('/etc/geni-ch/settings.php');

/*
  Functions for interacting with the iRODS REST interfaces
*/

class PermFailException extends Exception{}

// This is just the default - otherwise it comes from the SR
// Test server
$irods_url = 'https://iren-web.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';

// Production server
// $irods_url = 'https://geni-gimi.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';

/* TODO put these in the service registry or similar */
$irods_host = "irods_hostname"; // FIXME
$irods_port = 1247; // FIXME: Always right?
$irods_resource = "demoResc"; // FIXME: Always right?
$default_zone = "tempZone";

// Get the irods server cert for smime purposes
$irods_cert = null;
$irods_svrs = get_services_of_type(SR_SERVICE_TYPE::IRODS);
if (isset($irods_svrs) && ! is_null($irods_svrs) && is_array($irods_svrs) && count($irods_svrs) > 0) {
  $irod = $irods_svrs[0];
  $irods_url = $irod[SR_TABLE_FIELDNAME::SERVICE_URL];
  $irods_cert = $irod[SR_TABLE_FIELDNAME::SERVICE_CERT];
}

/* Get this from /etc/geni-ch/settings.php */
if (! isset($portal_irods_user) || is_null($portal_irods_user)) {
  $portal_irods_user = 'rods'; // FIXME: Testing value
  $portal_irods_pw = 'rods'; // FIXME: Testing value
}

// Do a BasicAuth protected get of the given URL
function doGET($url, $user, $password, $serverroot=null) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  if (false and ! is_null($serverroot)) { // FIXME: while cert is expired, must do this....
    curl_setopt($ch, CURLOPT_CAINFO, $serverroot);
  } else {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // FIXME: iren-web is using a self signed cert at the moment
  }
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // FIXME: The iRODS cert says just 'iRODS' so can't ensure we are talking to the right host

  /* // For debugging */
  /* curl_setopt($ch, CURLOPT_VERBOSE, True); */
  /* curl_setopt($ch, CURLOPT_HEADER, True); */
  /* $errorFile = fopen("/tmp/wimax-curl-get-errors.log", 'a'); */
  /* curl_setopt($ch, CURLOPT_STDERR, $errorFile); */
  /* // End of debugging stuff */

  // Now do it
  $result = curl_exec($ch);
  $meta = curl_getinfo($ch);
  $error = curl_error($ch);
  curl_close($ch);

  /* // More debugging stuff */
  /* fflush($errorFile); */
  /* fclose($errorFile); */
  /* // End of debugging stuff */

  if ($result === false) {
    error_log("GET of " . $url . " failed (no result): " . $error);
    $code = "";
    $perm = false;
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      $code = "HTTP error " . $meta['http_code'] . ": ";
      //      if ($meta['http_code'] < 200 || $meta['http_code'] > 299)
      if ($meta['http_code'] == 0) 
	$perm = true;
    }
    if ($perm)
      throw new PermFailException("GET " . $url . " failed: " . $code . $error);

    throw new Exception("GET " . $url . " failed: " . $code . $error);
  } else {
    // FIXME: Comment this out when ready
    //    error_log("GET of " . $url . " result: " . print_r($result, true));
  }
  if ($meta === false) {
    error_log("GET of " . $url . " error (no meta): " . $error);
    $code = "";
    throw new Exception("GET " . $url . " failed: " . $code . $error);
  } else {
    //error_log("GET meta: " . print_r($meta, true));
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      if ($meta["http_code"] != 200) {
	error_log("GET of " . $url . " got error return code " . $meta["http_code"]);
	// code ??? means user not found - raise a different exception?
	// then if I don't get that and don't get the real result I show the error 
	// and don't try to do the PUT?

	// Code 401 - Authorization error - seems common...

	$codestr = "HTTP Error " . $meta["http_code"];
	if (is_null($error) || $error === "") {
	  $error = $codestr . ": \"" . $result . '"';
	} else {
	  $error = $codestr . ": \"" . $error . '"';
	}
	if ($meta["http_code"] == 401) {
	  throw new PermFailException($error);
	} else {
	  throw new Exception($error);
	}
	//	throw new PermFailException($error);
      }
    }
  }
  if (! is_null($error) && $error != '')
    error_log("GET of " . $url . " error: " . print_r($error, true));
  return $result;
}

function doPUT($url, $user, $password, $data, $content_type="application/json", $serverroot=null) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  $headers = array();
  $headers[] = "Content-Type: " . $content_type;
  $headers[] = "Content-Length: " . strlen($data);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  if (false and ! is_null($serverroot)) { // FIXME: while server cert is expired....
    curl_setopt($ch, CURLOPT_CAINFO, $serverroot);
  } else {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // FIXME: iren-web is using a self signed cert at the moment
  }
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // FIXME: The iRODS cert says just 'iRODS' so can't ensure we are talking to the right host

  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

  // Now do it
  $result = curl_exec($ch);
  $meta = curl_getinfo($ch);
  $error = curl_error($ch);
  curl_close($ch);

  if ($result === false) {
    error_log("PUT to " . $url . " failed (no result): " . $error);
    $code = "";
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      $code = "HTTP error " . $meta['http_code'] . ": ";
    }
    throw new Exception("PUT to " . $url . " failed: " . $code . $error);
  } else {
    // FIXME: Comment this out when ready
    //    error_log("PUT to " . $url . " result: " . print_r($result, true));
  }
  if ($meta === false) {
    error_log("PUT to " . $url . " error (no meta): " . $error);
    $code = "";
    throw new Exception("PUT to " . $url . " failed: " . $code . $error);
  } else {
    //error_log("PUT to " . $url . " meta: " . print_r($meta, true));
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      if ($meta["http_code"] != 200) {
	error_log("PUT got error return code " . $meta["http_code"]);
	$codestr = "HTTP Error " . $meta["http_code"];
	if (is_null($error) || $error === "") {
	  $error = $codestr . ": \"" . $result . '"';
	} else {
	  $error = $codestr . ": \"" . $error . '"';
	}
	throw new Exception($error);
      }
    }
  }
  if (! is_null($error) && $error != '')
    error_log("PUT to " . $url . " error: " . print_r($error, true));
  return $result;
}

function base_username($user) {
  $userPrefix = "geni-"; // FIXME: Make this an HRN but with hyphens? No prefix?
  return $userPrefix . $user->username;
}

// Create a unique username from the given base, where latest is what we tried that failed
function derive_username($baseusername, $latestname=null) {
  $ind = 1;
  if (! is_null($latestname)) {
    $ind = intval(substr($latestname, strlen($baseusername)));
    $ind = $ind + 1;
  }
  if ($ind > 20) {
    error_log($ind . " usernames like " . $baseusername . "? Really?");
  }
  return $baseusername . $ind;
}

function group_name($project_name) {
  return "geni-" . $project_name;
}

/* iRods Constants */
const IRODS_USER_NAME = 'userName';
const IRODS_USER_PASSWORD = 'tempPassword';
const IRODS_USER_DN = 'distinguishedName';
const IRODS_ADD_RESPONSE_DESCRIPTION = 'userAddActionResponse';
const IRODS_ADD_RESPONSE_CODE = 'userAddActionResponseNumericCode';
const IRODS_MESSAGE = 'message';
const IRODS_GET_USER_URI = '/user/'; // to query for a user
const IRODS_PUT_USER_URI = '/user'; // to create a user
const IRODS_PUT_USER_GROUP_URI = '/user_group/user'; // to add a user to a group
const IRODS_SEND_JSON = '?contentType=application/json';
const IRODS_CREATE_TIME = 'createTime';
const IRODS_ZONE = "zone";
const IRODS_USERDN = "userDN";
const IRODS_URL = "webAccessURL";
const IRODS_ENV = "irodsEnv";
const IRODS_GROUP = "userGroup"; // group name

/*
0 - Success (user can log in with that username/password)
1 - Username is taken
2 - Temporary error, try again
3 - S/MIME signature invalid
4 - Failed to decrypt S/MIME message
5 - Failed to parse message
6 - Attributes missing
7 - Internal Error
*/

/* iRods error codes */
class IRODS_ERROR
{
  const SUCCESS = 0;
  const USERNAME_TAKEN = 1;
  const TRY_AGAIN = 2;
  const SMIME_SIG = 3;
  const SMIME_DECRYPT = 4;
  const PARSE = 5;
  const ATTRIBUTE_MISSING = 6;
  const INTERNAL_ERROR = 7;
  const GROUP_EXISTS = 8;
  const NO_SUCH_GROUP = 9;
  const NO_SUCH_USER = 10;
  const USER_IN_GROUP = 11;
  const USER_NOT_IN_GROUP = 12;
}

$IRODS_ERROR_NAMES = array("Success",
			   "Username taken / exists",
			   "Temporary error - try again",
			   "S/MIME Signature invalid",
			   "Failed to decrypt S/MIME message",
			   "Failed to parse message",
			   "Attribute(s) missing",
			   "Internal error",
			   "Group (already) exists",
			   "No such group",
			   "No such user",
			   "User (already) in group",
			   "User not in group");

function irods_create_group($project_id, $project_name, $lead_id, $user) {
  // Note this function must bail if project_id is not a project but an error of some kind
  error_log("irods asked to create group for project $project_name with id $project_id");
  if ($project_id == "-1" || ! uuid_is_valid($project_id)) {
    error_log("but that's not a valid project ID. Nothing to do. $project_id");
    return;
  }

  // FIXME: How come I can't rely on this from top of file?

  // This is just the default - otherwise it comes from the SR
  // Test server
  $irods_url = 'https://iren-web.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';
  
  // Production server
  // $irods_url = 'https://geni-gimi.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';
  
  /* TODO put these in the service registry or similar */
  $irods_host = "irods_hostname"; // FIXME
  $irods_port = 1247; // FIXME: Always right?
  $irods_resource = "demoResc"; // FIXME: Always right?
  $default_zone = "tempZone";
  
  // Get the irods server cert for smime purposes
  $irods_cert = null;
  $irods_svrs = get_services_of_type(SR_SERVICE_TYPE::IRODS);
  if (isset($irods_svrs) && ! is_null($irods_svrs) && is_array($irods_svrs) && count($irods_svrs) > 0) {
    $irod = $irods_svrs[0];
    $irods_url = $irod[SR_TABLE_FIELDNAME::SERVICE_URL];
    $irods_cert = $irod[SR_TABLE_FIELDNAME::SERVICE_CERT];
  }
  
  /* Get this from /etc/geni-ch/settings.php */
  if (! isset($portal_irods_user) || is_null($portal_irods_user)) {
    $portal_irods_user = 'rods'; // FIXME: Testing value
    $portal_irods_pw = 'rods'; // FIXME: Testing value
  }

  // must get project name and then groupname
  $group_name = group_name($project_name);
  addToGroup($project_id, $group_name, $lead_id, $user);
}

function irods_modify_group_members($project_id, $members_to_add, $members_to_remove, $user, $result) {
  error_log("irods asked to modify group members for project $project_id");
  // Note this function must bail if result suggests an error of some kind
  //  $result is a triple
  if (isset($result) and is_array($result) and array_key_exists(RESPONSE_ARGUMENT::CODE, $result) and $result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    error_log("Result of modify_membership suggests an error. Nothing to do. Got result: " . print_r($result, true));
    return;
  }
  if ((! isset($members_to_add) or ! is_array($members_to_add) or count($members_to_add) == 0) and (! isset($members_to_remove) or ! is_array($members_to_remove) or count($members_to_remove) == 0)) {
    error_log("0 members to add or remove. nothing to do.");
    return;
  }

  if (! isset($sa_url)) {
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
    if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
      error_log("Found no SA in SR!'");
    }
  }

  // must get project name and then groupname
  $project = lookup_project($sa_url, $user, $project_id);
  $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  $group_name = group_name($project_name);

// $members_to_add and $members_to_change role are both
//     dictionaries of {member_id => role, ....}
  if (isset($members_to_add)) {
    foreach (array_keys($members_to_add) as $member_id) {
      addToGroup($project_id, $group_name, $member_id, $user);
    }
  }

  if (isset($members_to_remove)) {
    foreach ($members_to_remove as $member_id) {
      removeFromGroup($project_id, $group_name, $member_id, $user);
    }
  }
}

function addToGroup($project_id, $group_name, $member_id, $user) {
  // must get member username
  $member = geni_load_user_by_member_id($member_id);
  $username = base_username($member);
  // then call the new REST API, checking the return for the error codes. 0 or 11 counts as success.
  // On 9, do createGroup
  // On 10, maybe email someone? Just log?
  // Log errors
  error_log("addToGroup $group_name member $member_id with username $username");

  // PUT to <base>/user_group/user
  // JSON like this: {"userName":"test2","userGroup":"jargonTestUg","zone":"test1"}
  // Watch for exceptions. For now if it works it returns a JSON that says Status:OK

  // FIXME: How come I can't rely on this from top of file?

  // This is just the default - otherwise it comes from the SR
  // Test server
  $irods_url = 'https://iren-web.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';
  
  // Production server
  // $irods_url = 'https://geni-gimi.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';
  
  /* TODO put these in the service registry or similar */
  $irods_host = "irods_hostname"; // FIXME
  $irods_port = 1247; // FIXME: Always right?
  $irods_resource = "demoResc"; // FIXME: Always right?
  $default_zone = "tempZone";
  
  // Get the irods server cert for smime purposes
  $irods_cert = null;
  $irods_svrs = get_services_of_type(SR_SERVICE_TYPE::IRODS);
  if (isset($irods_svrs) && ! is_null($irods_svrs) && is_array($irods_svrs) && count($irods_svrs) > 0) {
    $irod = $irods_svrs[0];
    $irods_url = $irod[SR_TABLE_FIELDNAME::SERVICE_URL];
    $irods_cert = $irod[SR_TABLE_FIELDNAME::SERVICE_CERT];
  }
  
  /* Get this from /etc/geni-ch/settings.php */
  if (! isset($portal_irods_user) || is_null($portal_irods_user)) {
    $portal_irods_user = 'rods'; // FIXME: Testing value
    $portal_irods_pw = 'rods'; // FIXME: Testing value
  }

  $irods_info = array();
  $irods_info[IRODS_USER_NAME] = $username;
  $irods_info[IRODS_GROUP] = $group_name;
  $irods_info[IRODS_ZONE] = $default_zone;

  // Note: in PHP 5.4, use JSON_UNESCAPED_SLASHES.
  //   we have PHP 5.3, so we have to remove those manually.
  $irods_json = json_encode($irods_info);
  $irods_json = str_replace('\\/','/', $irods_json);
  
  // FIXME: Take this out when ready
  error_log("Trying to add member to iRODS group with values: " . $irods_json);

  ///* Sign the data with the portal certificate (Is that correct?) */
  //$irods_signed = smime_sign_message($irods_json, $portal_cert, $portal_key);
  
  ///* Encrypt the signed data for the iRODS SSL certificate */
  //$irods_blob = smime_encrypt($irods_signed, $irods_cert);
  
  try {
    $addstruct = doPUT($irods_url . IRODS_PUT_USER_GROUP_URI . IRODS_SEND_JSON, $portal_irods_user, $portal_irods_pw, $irods_json, "application/json", $irods_cert);

    // look for (\r or \n or \r\n){2} and move past that
    preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $addstruct, $m);
    if (! array_key_exists(2, $m)) {
      error_log("Malformed PUT result to iRODS - error? Got: " . $addstruct);
      throw new Exception("Failed to add member to iRODS group - server error: " . $addstruct);
    }

    // FIXME: Comment this out when ready
    error_log("PUT result content: " . $m[2]);
    
    $addjson = json_decode($m[2], true);
    // FIXME: Parse the json return
    error_log("add user to group result: " . print_r($addjson, true));
  } catch (Exception $e) {
    error_log("Error doing iRODS put to add member to group: " . $e->getMessage());
  }
}

function removeFromGroup($project_id, $group_name, $member_id, $user) {
  // must get member username
  $member = geni_load_user_by_member_id($member_id);
  $username = base_username($member);
  // then call the new REST API, checking the return for the error codes. 0 or 12 counts as success.
  // On 9, do createGroup
  // Log errors
  error_log("removeFromGroup $group_name member $member_id with username $username");

  // FIXME: How come I can't rely on this from top of file?

  // This is just the default - otherwise it comes from the SR
  // Test server
  $irods_url = 'https://iren-web.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';
  
  // Production server
  // $irods_url = 'https://geni-gimi.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';
  
  /* TODO put these in the service registry or similar */
  $irods_host = "irods_hostname"; // FIXME
  $irods_port = 1247; // FIXME: Always right?
  $irods_resource = "demoResc"; // FIXME: Always right?
  $default_zone = "tempZone";
  
  // Get the irods server cert for smime purposes
  $irods_cert = null;
  $irods_svrs = get_services_of_type(SR_SERVICE_TYPE::IRODS);
  if (isset($irods_svrs) && ! is_null($irods_svrs) && is_array($irods_svrs) && count($irods_svrs) > 0) {
    $irod = $irods_svrs[0];
    $irods_url = $irod[SR_TABLE_FIELDNAME::SERVICE_URL];
    $irods_cert = $irod[SR_TABLE_FIELDNAME::SERVICE_CERT];
  }
  
  /* Get this from /etc/geni-ch/settings.php */
  if (! isset($portal_irods_user) || is_null($portal_irods_user)) {
    $portal_irods_user = 'rods'; // FIXME: Testing value
    $portal_irods_pw = 'rods'; // FIXME: Testing value
  }

}

?>

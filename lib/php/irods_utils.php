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
include_once('settings.php');

/*
  Functions for interacting with the iRODS REST interfaces
*/

// A call to the REST APIs permanently failed
class PermFailException extends Exception{}

/* *** Set up some basic variables for accessing iRODS *** */
// This is just the default - otherwise it comes from the SR
// Test server
$irods_url = 'http://iren-web.renci.org:8080/irods-rest-0.0.1-SNAPSHOT/rest';
//$irods_url = 'https://iren-web.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';

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
  if (is_null($irod[SR_TABLE_FIELDNAME::SERVICE_URL]) or trim($irod[SR_TABLE_FIELDNAME::SERVICE_URL]) == '') {
    error_log("null or empty irods_url from DB");
  } else {
    $irods_url = $irod[SR_TABLE_FIELDNAME::SERVICE_URL];
    $irods_cert = $irod[SR_TABLE_FIELDNAME::SERVICE_CERT];
  }
}

/* Get this from /etc/geni-ch/settings.php */
if (! isset($portal_irods_user) || is_null($portal_irods_user)) {
  $portal_irods_user = 'rods'; // FIXME: Testing value
  $portal_irods_pw = 'rods'; // FIXME: Testing value
}

// Do a BasicAuth protected get or delete or put of the given URL, json data
function doRESTCall($url, $user, $password, $op="GET", $data="", $content_type="application/json", $serverroot=null) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);

  if ($op == "DELETE") {
    // To tell the server that I know it is sending JSON result
    $headers = array();
    $headers[] = "Accept: " . "application/json";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, 1);
  } elseif ($op == "PUT") {
    $headers = array();
    $headers[] = "Content-Type: " . $content_type;
    $headers[] = "Content-Length: " . strlen($data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, 1);
  }

  curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  if (! is_null($serverroot)) { // FIXME: If iRODS cert remains expired, make this if (false and...
    curl_setopt($ch, CURLOPT_CAINFO, $serverroot);
  } else {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // FIXME: iren-web is using a self signed cert at the moment
  }
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // FIXME: The iRODS cert says just 'iRODS' so can't ensure we are talking to the right host

  if ($op == "DELETE") {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
  } elseif ($op == "PUT") {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  }

  /* // For debugging */
  /* curl_setopt($ch, CURLOPT_VERBOSE, True); */
  /* curl_setopt($ch, CURLOPT_HEADER, True); */
  /* $fname = "/tmp/wimax-curl-$op-errors.log"; */
  /* $errorFile = fopen($fname, 'a'); */
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
    error_log("$op of " . $url . " failed (no result): " . $error);
    $code = "";
    $perm = false;
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      $code = "HTTP error " . $meta['http_code'] . ": ";
      //      if ($meta['http_code'] < 200 || $meta['http_code'] > 299)
      if ($meta['http_code'] == 0) 
	$perm = true;
    }
    if ($perm)
      throw new PermFailException("$op " . $url . " failed: " . $code . $error);

    throw new Exception("$op " . $url . " failed: " . $code . $error);
  } else {
    //    error_log("$op of " . $url . " result: " . print_r($result, true));
  }
  if ($meta === false) {
    error_log("$op of " . $url . " error (no meta): " . $error);
    $code = "";
    throw new Exception("$op " . $url . " failed: " . $code . $error);
  } else {
    //error_log("$op meta: " . print_r($meta, true));
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      if ($meta["http_code"] != 200) {
	error_log("$op of " . $url . " got error return code " . $meta["http_code"]);

	$codestr = "HTTP Error " . $meta["http_code"];
	if (is_null($error) || $error === "") {
	  $error = $codestr . ": \"" . $result . '"';
	} else {
	  $error = $codestr . ": \"" . $error . '"';
	}
	// Code 401 - Authorization error - seems common...
	// Unfortunately this is also a symptom of the iRODS server underneath the REST interface being down
	if ($meta["http_code"] == 401) {
	  throw new PermFailException("Failed to contact iRODS server (is it down?): $error");
	} else {
	  throw new Exception($error);
	}
	//	throw new PermFailException($error);
      }
    }
  }
  if (! is_null($error) && $error != '')
    error_log("$op of " . $url . " error: " . print_r($error, true));
  return $result;
}

// What are usernames usually?
function base_username($user) {
  $userPrefix = "geni-"; // FIXME: Make this an HRN but with hyphens? No prefix?
  if (isset($user->ma_member->irods_username)) {
    return $user->ma_member->irods_username;
  }
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

// What is the iRODS group name, given the project name?
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
const IRODS_REMOVE_USER_URI1 = '/user_group/'; // to remove user from group. next is groupname
const IRODS_REMOVE_USER_URI2 = '/user/'; // comes after groupname, before username
const IRODS_PUT_GROUP_URI = '/user_group'; // to create a group
const IRODS_SEND_JSON = '?contentType=application/json';
const IRODS_CREATE_TIME = 'createTime';
const IRODS_ZONE = "zone";
const IRODS_USERDN = "userDN";
const IRODS_URL = "webAccessURL";
const IRODS_ENV = "irodsEnv";
const IRODS_GROUP = "userGroup"; // group name
const IRODS_GROUP_NEW = "userGroupName"; // group name
// Next 2 possible values for STATUS field
const IRODS_STATUS_ERROR = "ERROR"; // command failed
const IRODS_STATUS_SUCCESS = "OK"; // command succeeded

// the name of the detailed status field
const IRODS_USER_GROUP_COMMAND_STATUS = "userGroupCommandStatus"; // details on what happened with adding a group

// possible errors for the detailed status field. success is as before
const IRODS_STATUS_BAD_GROUP = "INVALID_GROUP"; // group doesn't exist
const IRODS_STATUS_DUPLICATE_GROUP = "DUPLICATE_GROUP";
const IRODS_STATUS_DUPLICATE_USER = "DUPLICATE_USER";
const IRODS_STATUS_BAD_USER = "INVALID_USER";


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

/* iRODS error codes using in the GET/PUT for creating users */
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

// Create an iRODS group for the project with the given ID, name. $user is who signs any CH messages
function irods_create_group($project_id, $project_name, $user) {
  // Note this function must bail if project_id is not a project but an error of some kind
  error_log("iRODS: creating group for project $project_name with id $project_id");
  if (! isset($project_id) || $project_id == "-1" || ! uuid_is_valid($project_id)) {
    error_log("irods_create_group: not a valid project ID. Nothing to do. $project_id");
    return -1;
  }
  if (! isset($project_name) || is_null($project_name) || $project_name === '') {
    error_log("irods_create_group: not a valid project name. Nothing to do. $project_id, $project_name");
    return -1;
  }

  global $disable_irods;
  if (isset($disable_irods)) {
    error_log("irodsCreateGroup: disable_irods was set. Doing nothing.");
    return -1;
  }

  // If pa_project_attribute has the irods_group_name attribute, then return 1
  if (! isset($sa_url)) {
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
    if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
      error_log("iRODS Found no SA in SR!'");
    }
  }

  $project_attributes = lookup_project_attributes($sa_url, $user, $project_id);
  $group_name = null;
  $att_group_name = null;
  foreach($project_attributes as $attribute) {
    if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::IRODS_GROUP_NAME) {
      $group_name = $attribute[PA_ATTRIBUTE::VALUE];
      $att_group_name = $group_name;
      break;
    }
  }
  if (! is_null($group_name)) {
    error_log("irodsCreateGroup: local attribute says group $group_name already exists for project $project_id");
    return 1; // group already existed
  }

  global $irods_url;
  global $default_zone;
  global $irods_cert;
  global $portal_irods_user;
  global $portal_irods_pw;

  // must get project name and then groupname
  $group_name = group_name($project_name);

  $irods_info = array();
  $irods_info[IRODS_GROUP_NEW] = $group_name;
  $irods_info[IRODS_ZONE] = $default_zone;

  // Note: in PHP 5.4, use JSON_UNESCAPED_SLASHES.
  //   we have PHP 5.3, so we have to remove those manually.
  $irods_json = json_encode($irods_info);
  $irods_json = str_replace('\\/','/', $irods_json);
  
  //  error_log("Trying to add group to iRODS with values: " . $irods_json);

  ///* Sign the data with the portal certificate (Is that correct?) */
  //$irods_signed = smime_sign_message($irods_json, $portal_cert, $portal_key);
  
  ///* Encrypt the signed data for the iRODS SSL certificate */
  //$irods_blob = smime_encrypt($irods_signed, $irods_cert);
  
  $created = -1; // Was the group created? -1=error, 0=success, 1=group was already there
  try {
    $addstruct = doRESTCall($irods_url . IRODS_PUT_GROUP_URI . IRODS_SEND_JSON, $portal_irods_user, $portal_irods_pw, "PUT", $irods_json, "application/json", $irods_cert);

    // look for (\r or \n or \r\n){2} and move past that
    preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $addstruct, $m);
    if (! array_key_exists(2, $m)) {
      error_log("irods createGroup: Malformed PUT result to iRODS - error? Got: " . $addstruct);
      throw new Exception("Failed to add iRODS group - server error: " . $addstruct);
    }

    //    error_log("PUT result content: " . $m[2]);
    
    $addjson = json_decode($m[2], true);
    //    error_log("add group result: " . print_r($addjson, true));

    if (is_array($addjson)) {
      $status = null;
      $msg = null;
      $groupCmdStatus = null;
      if (array_key_exists("status", $addjson)) {
	$status = $addjson["status"];
	// Return 0 if added the group, 1 if group existed, -1 on error
	if ($status == IRODS_STATUS_ERROR) {
	  $created = -1;
	} elseif ($status == IRODS_STATUS_SUCCESS) {
	  $created = 0;
	}
      }
      if (array_key_exists("message", $addjson)) {
	$msg = $addjson["message"];
      }
      if (array_key_exists(IRODS_USER_GROUP_COMMAND_STATUS, $addjson)) {
	$groupCmdStatus = $addjson[IRODS_USER_GROUP_COMMAND_STATUS];
	if ($groupCmdStatus == IRODS_STATUS_DUPLICATE_GROUP) {
	  $created = 1;
	  error_log("iRODS group $group_name already existed");
	} elseif ($groupCmdStatus != IRODS_STATUS_SUCCESS) {
	  error_log("iRODS failed to create group $group_name: $groupCmdStatus: '$msg'");
	}
      } elseif ($created !== 0) {
	error_log("iRODS failed to create group $group_name: '$msg'");
      }
    } else {
      error_log("iRODS: malformed return from createGroup: " . print_r($addjson, true));
      $created = -1;
    }
  } catch (Exception $e) {
    error_log("Error doing iRODS put to add group: " . $e->getMessage());
    $created = -1;
  }

  if ($created === 1) {
    if (! isset($att_group_name)) {
      // irods says the group exists, but our local attribute does not. Set it.
      add_project_attribute($sa_url, $user, $project_id, PA_ATTRIBUTE_NAME::IRODS_GROUP_NAME, $group_name);
    }
  }

  if ($created === 0) {
    // Save in local DB that we created the iRODS group
    // Remove first ensures no duplicate rows
    if (isset($att_group_name)) {
      remove_project_attribute($sa_url, $user, $project_id, PA_ATTRIBUTE_NAME::IRODS_GROUP_NAME);
    }
    add_project_attribute($sa_url, $user, $project_id, PA_ATTRIBUTE_NAME::IRODS_GROUP_NAME, $group_name);

    // Bootstrapping: for previously existing project, there may be other members of the project to add
    // Rely on the fact that we can move on if the user doesn't exist
    // Do this block only if we actually created the irods group just now

    $members = get_project_members($sa_url, $user, $project_id);
    // for each member of the project
    foreach ($members as $m) {
      $added = addToGroup($project_id, $group_name, $m[MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID], $user);
      /* if ($added === -1) { */
      /* 	error_log("Couldn't add member " . $m[MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID] . " to new irods group $group_name: probably they don't have an irods account yet."); */
      /* } */
    }
  }

  return $created;
}

// Modify iRODS group members for the group matching this project. Add/Remove members as listed.
// This is to be called from the pa_client modify_project_membership function
function irods_modify_group_members($project_id, $members_to_add, $members_to_remove, $user, $result) {
  //  error_log("irods asked to modify group members for project $project_id");
  // Note this function must bail if result suggests an error of some kind
  //  $result is a triple
  if (isset($result) and is_array($result) and array_key_exists(RESPONSE_ARGUMENT::CODE, $result) and $result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    error_log("iRODS: Result of modify_membership suggests an error. Nothing to do. Got result: " . print_r($result, true));
    return;
  }
  if ((! isset($members_to_add) or ! is_array($members_to_add) or count($members_to_add) == 0) and (! isset($members_to_remove) or ! is_array($members_to_remove) or count($members_to_remove) == 0)) {
    error_log("iRODS: 0 members to add or remove. nothing to do.");
    return;
  }

  if (! isset($project_id) || $project_id == "-1" || ! uuid_is_valid($project_id)) {
    error_log("irods_modify_group_members: not a valid project ID. Nothing to do. $project_id");
    return;
  }

  global $disable_irods;
  if (isset($disable_irods)) {
    error_log("irodsModifyGroupMembers: disable_irods was set. Doing nothing.");
    return -1;
  }

  if (! isset($sa_url)) {
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
    if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
      error_log("iRODS Found no SA in SR!'");
    }
  }

  // must get project name and then groupname
  $project = lookup_project($sa_url, $user, $project_id);
  $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  $group_name = group_name($project_name);

  // $members_to_add is a dictionary of {member_id => role, ....}
  if (isset($members_to_add)) {
    foreach (array_keys($members_to_add) as $member_id) {
      $added = addToGroup($project_id, $group_name, $member_id, $user);
    }
  }

  if (isset($members_to_remove)) {
    foreach ($members_to_remove as $member_id) {
      $removed = removeFromGroup($project_id, $group_name, $member_id, $user);
    }
  }
}

// Add the given member to the given project's group with the given name
function addToGroup($project_id, $group_name, $member_id, $user) {
  if (! isset($project_id) || $project_id == "-1" || ! uuid_is_valid($project_id)) {
    error_log("irods addToGroup: not a valid project ID. Nothing to do. $project_id");
    return -1;
  }
  if (! isset($group_name) || is_null($group_name) || $group_name === '') {
    error_log("irods addToGroup: not a valid group name. Nothing to do. $project_id, $group_name");
    return -1;
  }
  if (! isset($member_id) || $member_id == "-1" || ! uuid_is_valid($member_id)) {
    error_log("irods addToGroup: not a valid member ID. Nothing to do. $member_id");
    return -1;
  }

  global $disable_irods;
  if (isset($disable_irods)) {
    error_log("irods addToGroup: disable_irods was set. Doing nothing.");
    return -1;
  }

  // must get member username
  $member = geni_load_user_by_member_id($member_id);

  // Bail early if the local attribute says the user does not yet have an account
  if (! isset($member->ma_member->irods_username)) {
    error_log("iRODS addToGroup local attribute says member $member_id does not yet have an iRODS account. Cannot add to group $group_nme");
    return -1;
  }

  $username = base_username($member);
  error_log("iRODS addToGroup $group_name member $member_id with username $username");

  global $irods_url;
  global $default_zone;
  global $portal_irods_user;
  global $portal_irods_pw;
  global $irods_cert;

  $irods_info = array();
  $irods_info[IRODS_USER_NAME] = $username;
  $irods_info[IRODS_GROUP] = $group_name;
  $irods_info[IRODS_ZONE] = $default_zone;

  // Note: in PHP 5.4, use JSON_UNESCAPED_SLASHES.
  //   we have PHP 5.3, so we have to remove those manually.
  $irods_json = json_encode($irods_info);
  $irods_json = str_replace('\\/','/', $irods_json);
  
  //  error_log("Trying to add member to iRODS group with values: " . $irods_json);

  ///* Sign the data with the portal certificate (Is that correct?) */
  //$irods_signed = smime_sign_message($irods_json, $portal_cert, $portal_key);
  
  ///* Encrypt the signed data for the iRODS SSL certificate */
  //$irods_blob = smime_encrypt($irods_signed, $irods_cert);
  
  $added = -1; // Was the user added to the group? -1=Error, 0=Success, 1=Member already in group
  try {
    $addstruct = doRESTCall($irods_url . IRODS_PUT_USER_GROUP_URI . IRODS_SEND_JSON, $portal_irods_user, $portal_irods_pw, "PUT", $irods_json, "application/json", $irods_cert);

    // look for (\r or \n or \r\n){2} and move past that
    preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $addstruct, $m);
    if (! array_key_exists(2, $m)) {
      error_log("iRODS addToGroup Malformed PUT result to iRODS - error? Got: " . $addstruct);
      throw new Exception("Failed to add member to iRODS group - server error: " . $addstruct);
    }

    //    error_log("PUT result content: " . $m[2]);
    
    $addjson = json_decode($m[2], true);
    //    error_log("add user to group result: " . print_r($addjson, true));

    if (is_array($addjson)) {
      $status = null;
      $msg = null;
      $groupCmdStatus = null;
      if (array_key_exists("status", $addjson)) {
	$status = $addjson["status"];
	// Return 0 if added the user, 1 if user already in the group, -1 on error
	if ($status == IRODS_STATUS_ERROR) {
	  $added = -1;
	} elseif ($status == IRODS_STATUS_SUCCESS) {
	  $added = 0;
	}
      }
      if (array_key_exists("message", $addjson)) {
	$msg = $addjson["message"];
      }
      
      if (array_key_exists(IRODS_USER_GROUP_COMMAND_STATUS, $addjson)) {
	$groupCmdStatus = $addjson[IRODS_USER_GROUP_COMMAND_STATUS];
	if ($groupCmdStatus == IRODS_STATUS_DUPLICATE_USER) {
	  $added = 1;
	  error_log("iRODS user $username already in group $group_name");
	} elseif ($groupCmdStatus != IRODS_STATUS_SUCCESS) {
	  if ($groupCmdStatus === IRODS_STATUS_BAD_USER) {
	    error_log("iRODS: user $username has no iRODS account yet. Cannot add to group $group_name. ($groupCmdStatus: '$msg')");
	    // FIXME: Email someone?
	  } elseif ($groupCmdStatus === IRODS_STATUS_BAD_GROUP) {
	    // If it is INVALID_GROUP then we still need to do createGroup. I don't think that should happen. But in case...
	    error_log("iRODS: group $group_name doesn't exist yet, so cannot add user $username. Try to create the group... ($groupCmdStatus: '$msg')");
	    if (! isset($sa_url)) {
	      $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
	      if (! isset($sa_url) || is_null($sa_url) || $sa_url == '') {
		error_log("iRODS Found no SA in SR!'");
	      }
	    }
	    $project = lookup_project($sa_url, $user, $project_id);
	    $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	    $groupCreated = irods_create_group($project_id, $project_name, $user);
	    if ($groupCreated != -1) {
	      $added = 0;
	    }
	  } else {
	    error_log("iRODS failed to add user $username to group $group_name: $groupCmdStatus: '$msg'");
	  }
	}
      } elseif ($added !== 0) {
	error_log("iRODS failed to add user $username to group $group_name: '$msg'");
      }
    } else {
      $added = -1;
      error_log("iRODS: malformed return from addUserToGroup: " . print_r($addjson, true));
    }
  } catch (Exception $e) {
    error_log("Error doing iRODS put to add member to group: " . $e->getMessage());
    $added = -1;
  }

  // Return 0 if added the user, 1 if user already in the group, -1 on error
  return $added;
}

// Remove the given member from the given project with the given group name
function removeFromGroup($project_id, $group_name, $member_id, $user) {
  if (! isset($project_id) || $project_id == "-1" || ! uuid_is_valid($project_id)) {
    error_log("iRODS removeFromGroup: not a valid project ID. Nothing to do. $project_id");
    return -1;
  }
  if (! isset($group_name) || is_null($group_name) || $group_name === '') {
    error_log("iRODS removeFromGroup: not a valid group name. Nothing to do. $project_id, $group_name");
    return -1;
  }
  if (! isset($member_id) || $member_id == "-1" || ! uuid_is_valid($member_id)) {
    error_log("iRODS removeFromGroup: not a valid member ID. Nothing to do. $member_id");
    return -1;
  }

  global $disable_irods;
  if (isset($disable_irods)) {
    error_log("irods removeFromGroup: disable_irods was set. Doing nothing.");
    return -1;
  }

  // must get member username
  $member = geni_load_user_by_member_id($member_id);
  $username = base_username($member);
  error_log("iRODS removeFromGroup $group_name member $member_id with username $username");

  global $irods_url;
  global $portal_irods_user;
  global $portal_irods_pw;
  global $irods_cert;

  $removed = -1; // -1=Error, 0=Success, 1=Already gone

  try {
    // Note the method is called doRESTCall, but the op arg tells the real method
    $rmstruct = doRESTCall($irods_url . IRODS_REMOVE_USER_URI1 . $group_name . IRODS_REMOVE_USER_URI2 . $username, $portal_irods_user, $portal_irods_pw, "DELETE", "", "", $irods_cert);

    // look for (\r or \n or \r\n){2} and move past that
    preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $rmstruct, $m);
    if (! array_key_exists(2, $m)) {
      error_log("irods removeFromGroup: Malformed DELETE result from iRODS - error? Got: " . $rmstruct);
      throw new Exception("Failed to remove member from iRODS group - server error: " . $rmstruct);
    }

    //    error_log("DELETE result content: " . $m[2]);
    
    $rmjson = json_decode($m[2], true);
    // Parse the json return
    //    error_log("remove user from group result: " . print_r($rmjson, true));

    if (is_array($rmjson)) {
      $status = null;
      $msg = null;
      $groupCmdStatus = null;
      if (array_key_exists("status", $rmjson)) {
	$status = $rmjson["status"];
	// Return true if 0 if removed the user, 1 if user wasnt in the group, -1 on error
	if ($status == IRODS_STATUS_ERROR) {
	  $removed = -1;
	} elseif ($status == IRODS_STATUS_SUCCESS) {
	  $removed = 0;
	}
      }
      if (array_key_exists("message", $rmjson)) {
	$msg = $rmjson["message"];
	//	error_log("removeFromGroup result: '$msg'");
      }
      if (array_key_exists(IRODS_USER_GROUP_COMMAND_STATUS, $rmjson)) {
	$groupCmdStatus = $rmjson[IRODS_USER_GROUP_COMMAND_STATUS];
	if ($groupCmdStatus != IRODS_STATUS_SUCCESS) {
	  if ($groupCmdStatus === IRODS_STATUS_BAD_USER) {
	    error_log("iRODS: user $username was not in group $group_name to delete. ($groupCmdStatus: '$msg')");
	  } else {
	    error_log("iRODS failed to remove $username from group $group_name: $groupCmdStatus: '$msg'");
	  }
	}
      } elseif ($removed !== 0) {
	error_log("iRODS failed to remove user $username from group $group_name: '$msg'");
      }
    } else {
      $removed = -1;
      error_log("iRODS malformed return from removeUserFromGroup: " . print_r($rmjson, true));
    }
  } catch (Exception $e) {
    error_log("Error doing iRODS delete to remove member from group: " . $e->getMessage());
    $removed = -1;
  }

  // Return 0 if removed the user, 1 if user already not in the group, -1 on error
  return $removed;
}

// This function is not used currently as we don't want to delete user's data
// It might be useful for debugging though?
// Delete the iRODS group for the given project with the given name
function removeGroup($project_id, $group_name, $user) {
  if (! isset($project_id) || $project_id == "-1" || ! uuid_is_valid($project_id)) {
    error_log("iRODS removeGroup: not a valid project ID. Nothing to do. $project_id");
    return -1;
  }
  if (! isset($group_name) || is_null($group_name) || $group_name === '') {
    error_log("iRODS removeGroup: not a valid group name. Nothing to do. $project_id, $group_name");
    return -1;
  }

  error_log("iRODS removeGroup $group_name");

  global $irods_url;
  global $portal_irods_user;
  global $portal_irods_pw;
  global $irods_cert;

  $removed = -1; // -1=Error, 0=Success, 1=Already gone

  try {
    $rmstruct = doRESTCall($irods_url . IRODS_REMOVE_USER_URI1 . $group_name, $portal_irods_user, $portal_irods_pw, "DELETE", "", "", $irods_cert);

    // look for (\r or \n or \r\n){2} and move past that
    preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $rmstruct, $m);
    if (! array_key_exists(2, $m)) {
      error_log("iRODS removeGroup: Malformed DELETE result from iRODS - error? Got: " . $rmstruct);
      throw new Exception("Failed to remove iRODS group - server error: " . $rmstruct);
    }

    // FIXME: Comment this out when ready
    error_log("DELETE result content: " . $m[2]);
    
    $rmjson = json_decode($m[2], true);
    // FIXME: Comment this out when ready
    error_log("remove group result: " . print_r($rmjson, true));

    if (is_array($rmjson)) {
      $status = null;
      $msg = null;
      $groupCmdStatus = null;
      if (array_key_exists("status", $rmjson)) {
	$status = $rmjson["status"];
	// Return true = 0 if removed the group, -1 on error
	if ($status == IRODS_STATUS_ERROR) {
	  $removed = -1;
	} elseif ($status == IRODS_STATUS_SUCCESS) {
	  $removed = 0;
	}
      }
      if (array_key_exists("message", $rmjson)) {
	$msg = $rmjson["message"];
	//	error_log("removeGroup result: '$msg'");
      }
      // Mike C says delete when the group doesn't exist returns SUCCESS
      if (array_key_exists(IRODS_USER_GROUP_COMMAND_STATUS, $rmjson)) {
	$groupCmdStatus = $rmjson[IRODS_USER_GROUP_COMMAND_STATUS];
	if ($groupCmdStatus != IRODS_STATUS_SUCCESS) {
	  if ($groupCmdStatus === IRODS_STATUS_BAD_GROUP) {
	    error_log("iRODS: group $group_name not there to delete. ($groupCmdStatus: '$msg')");
	    $removed = 1;
	  } else {
	    error_log("iRODS failed to remove group $group_name: $groupCmdStatus: '$msg'");
	  }
	}
      } elseif ($removed === -1) {
	error_log("iRODS failed to remove group $group_name: '$msg'");
      }
    } else {
      $removed = -1;
      error_log("iRODS malformed return from removeGroup: " . print_r($rmjson, true));
    }
  } catch (Exception $e) {
    error_log("Error doing iRODS delete to remove group: " . $e->getMessage());
    $removed = -1;
  }

  // Return 0 if removed the group, -1 on error, 1 if no such group
  return $removed;
}

?>

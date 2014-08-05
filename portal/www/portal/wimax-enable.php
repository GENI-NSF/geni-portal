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
require_once("user.php");
require_once("header.php");
require_once("am_client.php");
require_once("ma_client.php");
require_once("sr_client.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// 6/2014: Open WiMAX to all.
// If user isn't supposed to see the wimax stuff at all, stop now
if (False && ! $user->hasAttribute('enable_wimax_button')) {
  relative_redirect('home.php');
}

// FIXME: hard-coded url for Rutgers ORBIT
// See tickets #772, #773, #1045
$old_wimax_server_url = "https://www.orbit-lab.org/userupload/save"; // Ticket #771
//$wimax_server_base_url = "https://www.orbit-lab.org/login/"; // Sept, 2013
$wimax_server_base_url = "https://www.orbit-lab.org/remoteAcc/"; // May, 2014

$wimax_server_url = $wimax_server_base_url . "save";
$wimax_server_deluser_url = $wimax_server_base_url . "deleteUser";
$wimax_server_delgroup_url = $wimax_server_base_url . "deleteProject";
$wimax_server_changeadmin_url = $wimax_server_base_url . "changeLeader";
$wimax_server_changegroup_url = $wimax_server_base_url . "changeProject";

// Does the server allow a wimax group lead to change their project?
$pi_can_change_project = False;

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);

/**
As of 6/25/14, known error codes:
  ERROR1 = 'ERROR 1: UID and OU and DC match'
Means portal tried to add a user that already exists. Handled explicitly.
  ERROR2 = 'ERROR 2: UID and DC match but OU is different'
Means trying to change project. Shouldn't happen, but code checks for this
  ERROR3 = 'ERROR 3: UID matches but DC and OU are different'
Member username exists from different authority. Code tries to pick a different username.
  ERROR4 = 'ERROR 4: UID and OU match but DC is different'
Seems to imply that group ou must be unique for both local and portal created groups? Huh?
FIXME FIXME
  ERROR5 = 'ERROR 5: User DN not known:'
Handled explicitly when trying to deleteUser, changeLeader, changeProject
  ERROR6 = 'ERROR 6: Cannot delete user: User is a admin for'
Handled explicity in deleteUser
  ERROR7 = 'ERROR 7: Project DN not known:'
Handled explicitly in deleteProject, changeLeader, changeProject
  ERROR8 = 'ERROR 8: Project not deleted because it contains admin(s):'
Handled explicitly in deleteProject
  ERROR9 = 'ERROR 9: Cannot move users: different DCs'
Theoretically could happen from changeProject if I'm trying to move a user not created
by the portal to a different project. Shouldn't happen.
  ERROR10 = 'ERROR 10: Missing OU LDIF entry'
Malformed LDIF
  ERROR11 = 'ERROR 11: Missing groupname attribute in OU entry'
Malformed LDIF
  ERROR12 = 'ERROR 12: Missing objectClass attribute (organizationalUnit/organizationalRole/organizationalUnit) for'
Malformed LDIF
  ERROR20 = 'ERROR 20: Group exists'
Tried to create a group that already exists.
FIXME: Mostly handled below, but perhaps could be better.
  ERROR21 = 'ERROR 21: Missing PI mail:'
Malformed LDIF. Note all users must have an email address.
  ERROR22 = 'ERROR 22: Missing PI sshpublickey:'
Malformed LDIF. Note we explicitly require users have an SSH key.

  ERROR30 = 'ERROR 30: Missing username (UID)'
Malformed LDIF.
  ERROR31 = 'ERROR 31: Organization does not exist for this user. Missing organization LDIF entry'
FIXME: I need to handle this (see comments below). This means I tried to add a user to a group that doesn't exist.
  ERROR32 = 'ERROR 32: Missing user mail:'
Malformed LDIF. Not all portal users must have an email address.
  ERROR33 = 'ERROR 33: Missing user sshpublickey:'
Malformed LDIF. Note we explicitly require users have an SSH key.
**/

/* function project_is expired
    Checks to see whether project has expired
    Returns false if not expired, true if expired
 */
function project_is_expired($proj) {
  return convert_boolean($proj[PA_PROJECT_TABLE_FIELDNAME::EXPIRED]);
}

/* function check_membership_of_project
    Checks to see if the supplied project ID is found
    in user's list of projects that they're a member of
    Returns true if found; false if not found
*/
function check_membership_of_project($ids, $my_id) {
  foreach($ids as $id) {
    if($id == $my_id) {
      return true;
    }
  }
  return false;
}

// If have groupname, use it
// elIf enabled, use project_name
// else use geni-project_name
// elif have g
function get_group_name($project_name, $project_attributes) {
  $enabled = false;
  $savedname = null;
  foreach($project_attributes as $attribute) {
    if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
      $enabled = true;
    } else if ($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME) {
      $savedname = $attribute[PA_ATTRIBUTE::VALUE];
    }
  }
  if (! is_null($savedname)) {
    return $savedname;
  } else if ($enabled) {
    return $project_name;
  } else {
    return "geni-" . $project_name;
  }
}

function get_project_dn($ldif_group_name) {
  return "ou=$ldif_group_name,dc=ch,dc=geni,dc=net";
}

function get_ldif_for_project($ldif_group_name, $ldif_project_description) {
  return "# LDIF for a project\n"
    . "dn: " . get_project_dn($ldif_group_name) . "\n"
    . "description: $ldif_project_description\n"
    . "ou: $ldif_group_name\n"
    . "objectclass: top\n"
    . "objectclass: organizationalUnit\n";
}

function get_user_dn($ldif_user_username, $ldif_user_groupname) {
  return "uid=$ldif_user_username,ou=$ldif_user_groupname,dc=ch,dc=geni,dc=net";
}

function get_ldif_for_project_lead($ldif_group_name, $ldif_lead_username, $ldif_lead_groupname) {
  return "\n# LDIF for the project lead\n"
    . "dn: cn=admin,ou=$ldif_group_name,dc=ch,dc=geni,dc=net\n"
    . "cn: admin\n"
    . "objectclass: top\n"
    . "objectclass: organizationalRole\n"
    . "roleoccupant: " . get_user_dn($ldif_lead_username, $ldif_lead_groupname) . "\n";
}

function get_ldif_for_user_string($ldif_user_username, $ldif_user_groupname, $ldif_user_pretty_name, $ldif_user_given_name, $ldif_user_email, $ldif_user_sn, $user, $ma_url, $ldif_project_description, $comment) {
  $ldif_string = "# LDIF for user ($comment)\n"
    . "dn: " . get_user_dn($ldif_user_username,$ldif_user_groupname) . "\n"
    . "cn: $ldif_user_pretty_name\n"
    . "givenname: $ldif_user_given_name\n"
    . "mail: $ldif_user_email\n"
    . "sn: $ldif_user_sn\n";
  
  if(strtolower($_SERVER['eppn']) != $user->eppn) {
    $ssh_public_keys = lookup_public_ssh_keys($ma_url, Portal::getInstance(), $user->account_id);
  } else {
    $ssh_public_keys = lookup_public_ssh_keys($ma_url, $user, $user->account_id);
  }
  $number_keys = count($ssh_public_keys);
  if($number_keys > 0) {
    for($i = 0; $i < $number_keys; $i++) {
      if ($i == 0)
	$ldif_string .= "sshpublickey: " . $ssh_public_keys[$i]['public_key'] . "\n";
      else
	$ldif_string .= "sshpublickey" . ($i + 1) . ": " . $ssh_public_keys[$i]['public_key'] . "\n";
    }
  }
  
  $ldif_string .= "uid: $ldif_user_username\n"
    . "o: $ldif_project_description\n"
    . "objectclass: top\n"
    . "objectclass: person\n"
    . "objectclass: posixAccount\n"
    . "objectclass: shadowAccount\n"
    . "objectclass: inetOrgPerson\n"
    . "objectclass: organizationalPerson\n"
    . "objectclass: hostObject\n"
    . "objectclass: ldapPublicKey\n";
  return $ldif_string;
}

function my_curl_get($argDict, $url) {
  $ch = curl_init();
  if (isset($argDict) and ! is_null($argDict) and is_array($argDict) and count($argDict) > 0) {
    $url = $url . "?";
    $queryStr = "";
    foreach (array_keys($argDict) as $key) {
      if (count($queryStr) !== 0) {
	$queryStr = $queryStr . "&";
      }
      $queryStr = $queryStr . urlencode($key) . "=" . urlencode($argDict[$key]);
    }
    $url = $url . $queryStr;
    //    $url = $url . htmlentities($queryStr);
  }
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  // FIXME: Change to true or remove line to set to true when CA issue fixed
  // Error message: SSL certificate problem, verify that the CA cert is OK. 
  // Details:\nerror:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  //curl_setopt($ch, CURLOPT_CAPATH, "/etc/ssl/certs");

  $result = curl_exec($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($error) {
    error_log("wimax-enable curl get_message error: $error");
    $result .= $error;
  }
  return trim($result);
}

function my_curl_put($arrayToPost, $url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  // FIXME: Change to true or remove line to set to true when CA issue fixed
  // Error message: SSL certificate problem, verify that the CA cert is OK. 
  // Details:\nerror:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  //curl_setopt($ch, CURLOPT_CAPATH, "/etc/ssl/certs");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayToPost);

  $result = curl_exec($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($error) {
    error_log("wimax-enable curl put_message error: $error");
  }
  return trim($result);
}

// What is the base username?
// This is where we prepend 'geni-' if we want to do so for all usernames
function gen_username_base($user) {
  return "geni-" . $user->username;
}

// Create a new unique username - we add a counter to the end of the base username
// Only go to 99 - then return null indicating we give up
function gen_new_username($oldUsername, $usernameBase) {
  $unLen = strlen($usernameBase);
  if ($oldUsername == $usernameBase) {
    return $usernameBase . 1;
  }
  $i = substr($oldUsername, $unLen);
  if ($i == 99) {
    return null;
  }
  $i = $i + 1;
  return $usernameBase . $i;
}

function wimax_delete_user($ldif_user_username, $ldif_user_groupname) {
  global $wimax_server_deluser_url;
  // https://www.orbit-lab.org/login/deleteUser?user=<userDN>
  // Cannot do this if user is admin of any group
  // Do some checks?
  $res = my_curl_get(array("user" => get_user_dn($ldif_user_username, $ldif_user_groupname)), $wimax_server_deluser_url);
  error_log("Deleting WiMAX user " . $ldif_user_username . " at " . $wimax_server_deluser_url . ": " . $res);
  // What errors come back?
  if (strpos($res, "404 Not Found")) {
    error_log("wimax-enable curl get deleteUser: Page $wimax_server_deluser_url Not Found");
    //    return false;
    return "Internal Error: WiMAX server not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 5: User DN not known")) !== false) {
    error_log("wimax-enable curl get deleteUser: Error deleting user $ldif_user_username in group $ldif_user_groupname - user not known: $res");
    // Treat as success
    return true;
  } else if (strpos(strtolower($res), strtolower("ERROR 6: User is a admin for")) !== false) {
    error_log("wimax-enable curl get deleteUser: Error deleting user $ldif_user_username - user is an admin: $res");
    // FIXME: Return the project they are admin for?
    // You need to make someone else the admin of that project first. Or maybe delete that project
    //    return false;
    return "Internal Error: $res";
  } else if (strpos(strtolower($res), strtolower("ERROR 6: Cannot delete user: User is a admin for")) !== false) {
    error_log("wimax-enable curl get deleteUser: Error deleting user $ldif_user_username - user is an admin: $res");
    // FIXME: Return the project they are admin for?
    // You need to make someone else the admin of that project first. Or maybe delete that project
    //    return false;
    return "Internal Error: $res";
  } else if (strpos(strtolower($res), strtolower("User dn not correct")) !== false) {
    error_log("wimax-enable curl get deleteUser: Error deleting user $ldif_user_username in group $ldif_user_groupname: $res");
    return "Internal Error: $res";
  } else if (strpos(strtolower($res), strtolower("Operation timed out")) !== false) {
    error_log("wimax-enable curl get deleteUser: Error deleting user $ldif_user_username: $res");
    return "Internal Error: $res";
  }

  return true;
}

function wimax_delete_group($ldif_group_name) {
  global $wimax_server_delgroup_url;
  // https://www.orbit-lab.org/login/deleteProject?project=<userDN>
  // deletes all members too
  // stops if any members are admins
  $res = my_curl_get(array("project" => get_project_dn($ldif_group_name)), $wimax_server_delgroup_url);
  error_log("Deleting WiMAX group " . $ldif_group_name . " at " . $wimax_server_delgroup_url . ": " . $res);
  // What errors come back?
  if (strpos($res, "404 Not Found")) {
    error_log("wimax-enable curl get deleteGroup: Page $wimax_server_delgroup_url Not Found");
    //    return false;
    return "Internal Error: WiMAX server not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 7: Project DN not known")) !== false) {
    error_log("wimax-enable curl get deleteGroup: Error deleting group $ldif_group_name - project not known: $res");
    // Treat as success
    return True;
  } else if (strpos(strtolower($res), strtolower("ERROR 8: Project not deleted because it contains admin")) !== false) {
    error_log("wimax-enable curl get deleteGroup: Error deleting group $ldif_group_name - project contains an admin: $res");
    // FIXME: Return that admin DN? Delete that admin DN?
    // Need to move that admin to another group or change the admin for the group they are an admin for, then you can delete them, so only then will the delete group succeed
    //    return false;
    return "Internal Error: $res";
  } else if (strpos(strtolower($res), strtolower("User dn not correct")) !== false) {
    error_log("wimax-enable curl get deleteProject: Error deleting project $ldif_group_name: $res");
    return "Internal Error: $res";
  } else if (strpos(strtolower($res), strtolower("Operation timed out")) !== false) {
    error_log("wimax-enable curl get deleteProject: Error deleting project $ldif_group_name: $res");
    return "Internal Error: $res";
  }
  return true;
}

function wimax_make_group_admin($ldif_group_name, $ldif_user_username, $ldif_user_groupname) {
  global $wimax_server_changeadmin_url;
  // https://www.orbit-lab.org/login/changeLeader?user=<userDN>&project=<projectDN>
  // deletes all members too
  // stops if any members are admins
  $res = my_curl_get(array("user" => get_user_dn($ldif_user_username, $ldif_user_groupname), "project" => get_project_dn($ldif_group_name)), $wimax_server_changeadmin_url);
  error_log("Changing admin for WiMAX group " . $ldif_group_name . " to $ldif_user_username at " . $wimax_server_changeadmin_url . ": " . $res);
  // What errors come back?
  if (strpos($res, "404 Not Found")) {
    error_log("wimax-enable curl get changeLeader: Page $wimax_server_changeadmin_url Not Found");
    //    return false;
    return "Internal Error: WiMAX server not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 5: User DN not known")) !== false) {
    error_log("wimax-enable curl get changeLeader: Error changing group $ldif_group_name lead to $ldif_user_username: New Lead not a known user: $res");
    //    return false;
    return "Internal Error: WiMAX User $ldif_user_username not known";
  } else if (strpos(strtolower($res), strtolower("ERROR 7: Project DN not known")) !== false) {
    error_log("wimax-enable curl get changeLeader: Error changing group $ldif_group_name lead to $ldif_user_username: Project not known: $res");
    //    return false;
    return "Internal Error: WiMAX group $ldif_group_name not known";
  } else if (strpos(strtolower($res), strtolower("Operation timed out")) !== false) {
    error_log("wimax-enable curl get changeLeader: Error changing group $ldif_group_name lead to $ldif_user_username: $res");
    return "Internal Error: $res";
  }
  return true;
}

function wimax_change_group($ldif_group_name, $ldif_user_username, $ldif_user_groupname) {
  global $wimax_server_changegroup_url;
  // https://www.orbit-lab.org/login/changeProject?user=<userDN>&project=<projectDN>
  $res = my_curl_get(array("user" => get_user_dn($ldif_user_username, $ldif_user_groupname), "project" => get_project_dn($ldif_group_name)), $wimax_server_changegroup_url);
  error_log("Changing WiMAX group to " . $ldif_group_name . " for $ldif_user_username at " . $wimax_server_changegroup_url . ": " . $res);
  // What errors come back?
  if (strpos($res, "404 Not Found")) {
    error_log("wimax-enable curl get changeGroup: Page $wimax_server_changegroup_url Not Found");
    //    return false;
    return "Internal Error: WiMAX server not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 5: User DN not known")) !== false) {
    error_log("wimax-enable curl get changeGroup: Error changing to group $ldif_group_name for $ldif_user_username: Not a known user: $res");
    //    return false;
    return "Internal Error: WiMAX user $ldif_user_username not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 7: Project DN not known")) !== false) {
    error_log("wimax-enable curl get changeGroup: Error changing to group $ldif_group_name for $ldif_user_username: Project not known: $res");
    //    return false;
    return "Internal Error: WiMAX group $ldif_group_name not found";
  } else if (strpos(strtolower($res), strtolower("Operation timed out")) !== false) {
    error_log("wimax-enable curl get changeGroup: Error changing to group $ldif_group_name for $ldif_user_username: $res");
    return "Internal Error: $res";
  }
  // Technically, this error could be returned. But I don't see how this could could cause this. It would mean that the user's 
  // existing group is a different DC - and yet the user was found successfully.
  // ERROR 9: Cannot move users: different DCs
  return true;
}

// Enable the given member in the given existing wimax group
// Use this when project lead changed and the new lead is not wimax enabled
// FIXME: User better have at least one SSH key! 
// count($user->sshKeys()) > 0
// FIXME: return 0 on success, -1 on error
function add_member_to_group($member, $member_id, $project_id, $group_name, $project_name, $ma_url, $project_desc, $wimax_server_url) {
  if (isset($member->ma_member->wimax_username)) {
    $new_member_username = $member->ma_member->wimax_username;
  } else {
    $new_member_username = gen_username_base($member);
  }

  // Construct prettyname, given name, email, sn
  $prettyName = $member->prettyName();

  $ldif_user_email = $member->email();

  // Not all users have a given name or sn
  $ldif_user_given_name = '';
  if (isset($member->first_name) and ! is_null($member->first_name)) {
    $ldif_user_given_name = $member->first_name;
  } else {
    // Must be non empty. user-username? Or else user->email()
    $ldif_user_given_name = $ldif_user_email;
  }

  $ldif_user_sn = '';
  if (isset($member->last_name) and ! is_null($member->last_name)) {
    $ldif_user_sn = $member->last_name;
  }

  $ldif_string = get_ldif_for_user_string($new_member_username, $group_name, $prettyName, $ldif_user_given_name, $ldif_user_email, $ldif_user_sn, $member, $ma_url, $project_desc, "project member"); 
  $postdata = array("ldif" => $ldif_string);
  $result = my_curl_put($postdata, $wimax_server_url);
  //      error_log("At $wimax_server_url send ldif $ldif_string and got result $result");
  if (strpos($result, "404 Not Found")) {
    error_log("wimax-enable curl put_message error: Page $wimax_server_url Not Found");
  } else if (strpos(strtolower($result), strtolower("ERROR 3: UID matches but DC and OU are different")) !== false) {
    // This implies that our portal member's username
    // already exists on ORBIT already. We can handle this error on our
    // side by generating a different username and trying to resubmit the
    // information again.
    error_log("WiMAX already has an account under username " . $new_member_username . " but not through the portal. Result: " . $result);
    // FIXME - don't handle this here
    return -1;
  }

  // FIXME: Handle:
  //  ERROR31 = 'ERROR 31: Organization does not exist for this user. Missing organization LDIF entry'
  // This means that the local db thought the group exists, but wimax thinks it does not.
  // Update local state to say the group does not exist and send the ldif to create it and try again
  // But careful - who else thinks they are in this group? Who should be the group lead?
  // Maybe use the sync function to update local state and go back to the wimax page with an error message?

  if (strpos(strtolower($result), strtolower("ERROR 1: UID and OU and DC match")) !== false) {
    // This implies that there's an error with our
    // portal trying to resend the exact same information that it had done
    // at a previous time. That is, this user already has a WiMAX account under the given project name
    error_log($prettyName . " already enabled for WiMAX in project " . $project_name . " (group $group_name). Result was: " . $result);
    // Remove any existing attribute for enabling wimax - we are changing the project we are enabled for
    remove_member_attribute($ma_url, Portal::getInstance(), $member_id, 'enable_wimax');
    remove_member_attribute($ma_url, Portal::getInstance(), $member_id, 'wimax_username');

    // add user as someone using WiMAX for given project
    add_member_attribute($ma_url, Portal::getInstance(), $member_id, 'enable_wimax', $project_id, 'f');

    // If we enabled wimax under a variant of the username, record that
    add_member_attribute($ma_url, Portal::getInstance(), $member_id, 'wimax_username', $new_member_username, 'f');

    error_log($prettyName . " was already enabled for WiMAX in project " . $project_name . " (group $group_name) with username " . $new_member_username . " according to the WiMAX server - updated our DB");
  } else if (strpos(strtolower($result), strtolower("ERROR 3: UID matches but DC and OU are different")) !== false) {
    // This implies that our portal member's username
    // already exists on ORBIT already. We can handle this error on our
    // side by generating a different username and trying to resubmit the
    // information again.
    error_log("WiMAX already has an account under username " . $new_member_username . " but not through the portal. Couldn't find a username username. Result: " . $result);
    // Not trying to handle this here, though we could
    // FIXME FIXME: return error of some kind
    return -1;
  } else if (strpos(strtolower($result), strtolower("ERROR 2: UID and DC match but OU is different")) !== false) {
    // This is trying to change the project for a person. Supposedly this should never happen as the service
    // supports this now.
    error_log("Unexpected error changing WiMAX project for " . $prettyName . " to project " . $project_name . " (group $group_name): " . $result);
    // FIXME FIXME - return error
    return -1;
  } else if (strpos(strtolower($result), 'success') !== false) {
    // FIXME: Was this user enabled for wimax before? And for that project, is this user the lead?
    // If so, should that wimax project no longer be wimax enabled? Do I need to send new LDIF?
    // Remove any existing attribute for enabling wimax - we are changing the project we are enabled for
    remove_member_attribute($ma_url, Portal::getInstance(), $member_id, 'enable_wimax');
    remove_member_attribute($ma_url, Portal::getInstance(), $member_id, 'wimax_username');

    // add user as someone using WiMAX for given project
    add_member_attribute($ma_url, Portal::getInstance(), $member_id, 'enable_wimax', $project_id, 'f');

    // If we enabled wimax under a variant of the username, record that
    add_member_attribute($ma_url, Portal::getInstance(), $member_id, 'wimax_username', $new_member_username, 'f');

    error_log($prettyName . " enabled for WiMAX in group " . $group_name . " with username " . $new_member_username);
    return 0;
  } else {
    $is_error = True;
    error_log("Sent ldif $ldif_string");
    error_log("Unknown Error enabling WiMAX for " . $prettyName . " in project " . $project_name . " (group $group_name): " . $result);
    // FIXME FIXME
    return -1;
  }
} // end addMemberToGroup

// Basic data setup stuff
$is_error = False; // Did the requested action result in an error
$result_string = ""; // Message to show user about result

// Get User
$ldif_user_group_id = null;
$user_is_wimax_enabled = False;
$ldif_user_groupname = null;
$ldif_user_projname = null;

if(isset($user->ma_member->wimax_username)) {
  $ldif_user_username = $user->ma_member->wimax_username;
} else {
  $ldif_user_username = gen_username_base($user);
}
$ldif_user_pretty_name = $user->prettyName();
$ldif_user_email = $user->email();

// Not all users have a given name or sn
$ldif_user_given_name = '';
if (array_key_exists('givenName', $user->attributes) and isset($user->attributes['givenName']) and ! is_null($user->attributes['givenName'])) {
  $ldif_user_given_name = $user->attributes['givenName'];
} else {
  // Must be non empty. user-username? Or else user->email()
  $ldif_user_given_name = $user->email();
}

$ldif_user_sn = '';
if (array_key_exists('sn', $user->attributes) and isset($user->attributes['sn']) and ! is_null($user->attributes['sn'])) {
  $ldif_user_sn = $user->attributes['sn'];
}

$project_ids = get_projects_for_member($sa_url, $user, $user->account_id, true);
$num_projects = count($project_ids);

if (isset($user->ma_member->enable_wimax)) {
  $ldif_user_group_id = $user->ma_member->enable_wimax;
  $user_is_wimax_enabled = True;
  //  error_log("User object says $ldif_user_pretty_name is wimax enabled in project " . $ldif_user_group_id);
  // If this user is not in the project, then delete the user
  if(!(check_membership_of_project($project_ids, $ldif_user_group_id))) {
    error_log("wimax-enable: $ldif_user_pretty_name lists WiMAX group $ldif_user_group_id, but the user is not in that project. Delete the WiMAX account");

    $user_group_project_info = lookup_project($sa_url, $user, $ldif_user_group_id);
    $user_group_project_attrs = lookup_project_attributes($sa_url, $user, $ldif_user_group_id);
    $ldif_user_projname = $user_group_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $ldif_user_groupname = get_group_name($ldif_user_projname, $user_group_project_attrs);
    
    $res = wimax_delete_user($ldif_user_username, $ldif_user_groupname);
    if ($res === true) {
      // Change relevant MA attribute, local vars
      remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
      remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
      $user->ma_member->enable_wimax = False;
      $user_is_wimax_enabled = False;
      $ldif_user_projname = null;
      $ldif_user_groupname = null;
      $ldif_user_group_id = null;
    } else {
      // WiMAX refused to let us delete the account so it doesn't match what we have!
      // FIXME
      error_log("wimax failed to delete user $ldif_user_username, so left MA attributes alone");
    }
  } else { // users group is one of their projects
    //    error_log("user is a member of the project which they list as their group");
    $user_group_project_info = lookup_project($sa_url, $user, $ldif_user_group_id);
    $user_group_project_attrs = lookup_project_attributes($sa_url, $user, $ldif_user_group_id);
    $ldif_user_projname = $user_group_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $ldif_user_groupname = get_group_name($ldif_user_projname, $user_group_project_attrs);
    //    error_log("users group is project $ldif_user_groupname");
    if (project_is_expired($user_group_project_info)) {
      error_log("User $ldif_user_username's group's project $ldif_user_projname is expired - must delete the group $ldif_user_groupname!");
      // delete group
      $old_group = $ldif_user_groupname;
      $res = wimax_delete_group($ldif_user_groupname);
      if (true === $res) {
	// mark project as not wimax enabled
	remove_project_attribute($sa_url, $user, $ldif_user_group_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	remove_project_attribute($sa_url, $user, $ldif_user_group_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	
	// mark user as not wimax enabled
	remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	$user->ma_member->enable_wimax = False;
	$user_is_wimax_enabled = False;
	$ldif_user_projname = null;
	$ldif_user_groupname = null;
	$ldif_user_group_id = null;
      } else {
	// Failed to delete WiMAX user whose portal project is expired
	// FIXME FIXME
	// Could go back to the wimax-enable page with an internal error message, using $res?
	error_log("wimax failed to delete group $ldif_user_groupname, so left project and MA attributes alone");
      }
      
      // Also delete the user
      // Note that this call to the wimax server may give an error that there is no such user - harmless
      $res = wimax_delete_user($ldif_user_username, $old_group);
      // Change relevant MA attribute, local vars
      remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
      remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
      $user->ma_member->enable_wimax = False;
      $user_is_wimax_enabled = False;
      $old_group = null;
      $ldif_user_projname = null;
      $ldif_user_groupname = null;
      $ldif_user_group_id = null;
    } else { // project not expired
      //      error_log("project not expired");
      $project_attributes = lookup_project_attributes($sa_url, $user, $ldif_user_group_id);
      $enabled = false;
      foreach($project_attributes as $attribute) {
	if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
	  $enabled = true;
	  $ldif_user_group_project_group_admin = $attribute[PA_ATTRIBUTE::VALUE];
	  // Is the listed admin also the lead of the project?
	  // If not, must fix!
	  if ($user_group_project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] !== $ldif_user_group_project_group_admin) {
	    error_log("wimax: user " . $user->prettyName() . "'s group " . $ldif_user_groupname . "'s admin is not same as the matching project's lead: " . $user_group_project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] . "!=" . $ldif_user_group_project_group_admin);
	    $ldif_project_lead_id = $user_group_project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
	    $ldif_project_id = $ldif_user_group_id;
	    $ldif_project_name = $ldif_user_projname;
	    $ldif_group_name = $ldif_user_groupname;
	    $project_lead_username = "";
	    $project_lead_groupname = "";
	    $project_lead_projname = "";

	    // get username from member ID
	    // get member attribute
	    $lead = geni_load_user_by_member_id($ldif_project_lead_id);
	    $didAdd = False;
	    if (! isset($lead->ma_member->enable_wimax)) {
	      error_log("FIXME FIXME: Project $ldif_project_name has lead " . $lead->prettyName() . " who is not yet wimax enabled");
	      // FIXME FIXME
	      // $is_error = True;
	      // $return_string = $lead->prettyName() . " needs a WiMAX account. Then reload this page to make them admin of the WiMAX group for project $ldif_project_name";
	      // Options here include:
	      // delete the group
	      // Auto enable the lead in this group
	      // Leave the old lead as lead of this group, but presumably show a reasonable message
	      // Try option 2.
	      if (count(lookup_public_ssh_keys($ma_url, $user, $ldif_project_lead_id)) > 0) {
		$res = add_member_to_group($lead, $ldif_project_lead_id, $ldif_project_id, $ldif_group_name, $ldif_project_name, $ma_url, $user_group_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE], $wimax_server_url);
		if ($res === 0) {
		  error_log("Auto added project $ldif_project_name " . $lead->prettyName() . " WiMAX account in that project.");
		  $didAdd = True;
		} else {
		  // FIXME: Now what?
		  error_log("FIXME: Could not create account for this lead. Delete the group?");
		}
	      } else {
		error_log("FIXME: New project lead has no SSH keys. Can't enable their WiMAX account.");
	      }
	    }

	    $lead = geni_load_user_by_member_id($ldif_project_lead_id);
	    if (! isset($lead->ma_member->enable_wimax)) {
	      if ($didAdd) {
		error_log("FIXME: Auto added project lead still has no wimax account");
	      }
	      // Else we complained about this above already
	    } else {
	      if (isset($lead->ma_member->wimax_username)) {
		$project_lead_username = $lead->ma_member->wimax_username;
	      } else {
		$project_lead_username = gen_username_base($lead);
	      }
	      $project_lead_group_id = $lead->ma_member->enable_wimax;
	      $lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	      $lead_project_attributes = lookup_project_attributes($sa_url, $user, $project_lead_group_id);
	      $project_lead_projname = $lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	      $project_lead_groupname = get_group_name($project_lead_projname, $lead_project_attributes);

	      error_log("Changing admin for wimax group $ldif_group_name to $project_lead_username");

	      $res = wimax_make_group_admin($ldif_group_name, $project_lead_username, $project_lead_groupname);
	      if (true === $res) {
		// Change relevant PA attribute, local vars
		remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
		add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $ldif_project_lead_id);
		$ldif_project_group_admin_id = $ldif_project_lead_id;
	      } else {
		// Failed to change lead. This might happen if that user has not created their wimax account yet.
		error_log("Failed to change WiMAX group admin for project $ldif_project_name to $project_lead_username: $res");
		// FIXME FIXME - Use $res
		// $is_error = True;
		// $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $ldif_group_name WiMAX group";
	      }
	    }
	    // Done with case where the project's lead is not the admin ID
	  } // end of block to handle project lead is not group admin
	  break; // out of loop over project attributes
	} // end of if this is the enable wimax attribute on this project
      }  // end of loop over project attributes

      if (! $enabled) {
	error_log("User $ldif_user_username says their group is $ldif_user_groupname, which is a project ($ldif_user_projname) that says it is not enabled. Delete the user (User DN not known errors are normal)");
	$res = wimax_delete_user($ldif_user_username, $ldif_user_groupname);
	if (true === $res) {
	  // Change relevant MA attribute, local vars
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
	  $ldif_user_groupname = null;
	  $ldif_user_projname = null;
	  $ldif_user_group_id = null;
	} else {
	  // FIXME: WiMAX has the user, we want them deleted.
	  // FIXME FIXME Use $res
	  error_log("Wimax failed to delete user $ldif_user_username so left MA attributes alone");
	}
      } // end of block to handle not expired but not enabled project
    } // end of if/else for project expired
  } // end of if/else for users group is one of their projects
} else { // user not enabled
  if ($num_projects == 0) {
    $_SESSION['lasterror'] = 'You are not a member of any projects.';
    relative_redirect('wimax-enable.php');
  }
}

// End of basic checks common to both pages

/* PAGE 2 */
/* if user has submited form */
if (array_key_exists('project_id', $_REQUEST))
{

  // Now stuff for the project they asked about
  $user_is_group_admin = False; // Does this user admin this projects wimax group
  $user_is_project_lead = False; // Does this user lead this project
  $project_is_user_group = False; // Does this user belong to this group

  $ldif_project_id = $_REQUEST['project_id'];
  if ($ldif_project_id == $ldif_user_group_id) {
    $project_is_user_group = True;
    //    error_log("DEBUG: Users group is requested project: $ldif_project_id");
  }
  if(!(check_membership_of_project($project_ids, $ldif_project_id))) {
    error_log("User $ldif_user_username specified a project $ldif_project_id that they do not belong to");
    $_SESSION['lasterror'] = 'You are not a member of that project.';
    relative_redirect('wimax-enable.php');
  }
  $project_info = lookup_project($sa_url, $user, $ldif_project_id);
  $project_name = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  $project_attributes = lookup_project_attributes($sa_url, $user, $ldif_project_id);

  // Define basic vars for use in constructing LDIF
  $ldif_project_name = $project_name;
  $ldif_group_name = get_group_name($project_name, $project_attributes);
  $ldif_project_description = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];

  $ldif_project_lead_id = $project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
  if($ldif_project_lead_id == $user->account_id) {
    $user_is_project_lead = True;
    //    error_log("DEBUG: User is lead of requested project $project_name");
  }

  $project_enabled = FALSE; // Is this project wimax enabled
  $ldif_project_group_admin_id = null;
  foreach($project_attributes as $attribute) {
    if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
      $project_enabled = True;
      //      error_log("DEBUG: $project_name is wimax enabled as $ldif_group_name");
      $ldif_project_group_admin_id = $attribute[PA_ATTRIBUTE::VALUE];
      if ($ldif_project_group_admin_id == $user->account_id) {
	$user_is_group_admin = True;
	//	error_log("DEBUG: User is the wimax group admin");
      }
      break;
    }
  }

  /*
  if ($project_enabled) {
    error_log("DEBUG: Project $project_name is enabled as $ldif_group_name");
  } else {
    error_log("Project $project_name is NOT WiMAX enabled");
  }
  error_log("DEBUG: user_lead: $user_is_project_lead. user_admin: $user_is_group_admin");
  */

  if ($project_enabled) {
    // If expired then delete group, return error
    if (project_is_expired($project_info)) {
      error_log("Requested project $project_name is enabled and expired - must delete WiMAX group $ldif_group_name");
      $res = wimax_delete_group($ldif_group_name);
      if (true === $res) {
	// mark project as not wimax enabled
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	$project_enabled = False;

	// If this is the group for this user, disable this user
	if ($ldif_user_group_id == $ldif_project_id) {
	  // mark user as not wimax enabled
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
	  $ldif_user_projname = null;
	  $ldif_user_groupname = null;
	  $ldif_user_group_id = null;
	}
      } else {
	error_log("Failed to delete WiMAX group for expired project $project_name (group $ldif_group_name): $res");
	// FIXME: And now do what? Use $res
      }
      // Return Error
      $_SESSION['lasterror'] = "Project $project_name is expired";
      relative_redirect('wimax-enable.php');
    } // end check enabled project is expired

    // If group admin != lead then change wimax group admin and continue, resetting vars as necessary
    if($ldif_project_lead_id !== $ldif_project_group_admin_id) {
      error_log("Project $project_name has diff lead ($ldif_project_lead_id) than wimax group $ldif_group_name ($ldif_project_group_admin_id)");
      // Look up the user who is the lead on this project. Get their wimax username, wimax group ID, name of that project
      $project_lead_username = "";
      $project_lead_projname = "";
      $project_lead_groupname = "";

      // get username from member ID
      // get member attribute
      $lead = geni_load_user_by_member_id($ldif_project_lead_id);
      $didAdd = False;
      if (! isset($lead->ma_member->enable_wimax)) {
	error_log("Project $project_name has lead " . $lead->prettyName() . " who is not yet wimax enabled");
	// FIXME FIXME
	// $is_error = True;
	// $return_string = $lead->prettyName() . " needs a WiMAX account. Then reload this page to make them admin of the WiMAX group for project $project_name";
	if (count(lookup_public_ssh_keys($ma_url, $user, $ldif_project_lead_id)) > 0) {
	  $res = add_member_to_group($lead, $ldif_project_lead_id, $ldif_project_id, $ldif_group_name, $project_name, $ma_url, $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE], $wimax_server_url);
	  if ($res === 0) {
	    error_log("Auto added project $project_name " . $lead->prettyName() . " WiMAX account in that project.");
	    $didAdd = True;
	  } else {
	    // FIXME: Now what?
	    error_log("FIXME: Could not create account for this lead. Delete the group?");
	  }
	} else {
	  error_log("New project lead has 0 SSH keys, cannot create their WiMAX account.");
	}
      }
      $lead = geni_load_user_by_member_id($ldif_project_lead_id);
      if (! isset($lead->ma_member->enable_wimax)) {
	if ($didAdd) {
	  error_log("FIXME: Auto added project lead still has no wimax account");
	}
	// Else we complained about this above already
      } else {
	if (isset($lead->ma_member->wimax_username)) {
	  $project_lead_username = $lead->ma_member->wimax_username;
	} else {
	  $project_lead_username = gen_username_base($lead);
	}
	$project_lead_group_id = $lead->ma_member->enable_wimax;
	$lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	$lead_project_attrs = lookup_project_attributes($sa_url, $user, $project_lead_group_id);
	$project_lead_projname = $lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	$project_lead_groupname = get_group_name($project_lead_projname, $lead_project_attrs);

	error_log("Changing admin for wimax group $ldif_group_name (project $project_name) to $project_lead_username");
      
	$res = wimax_make_group_admin($ldif_group_name, $project_lead_username, $project_lead_groupname);
	if (true === $res) {
	  // Change relevant PA attribute, local vars
	  remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	  add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $ldif_project_lead_id);
	  $ldif_project_group_admin_id = $ldif_project_lead_id;
	} else {
	  // Failed to change lead. This might happen if that user has not created their wimax account yet.
	  error_log("Failed to change WiMAX group admin for project $project_name (group $ldif_group_name) to $project_lead_username: $res");
	  // FIXME FIXME - Use $res
	  // $is_error = True;
	  // $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $ldif_group_name WiMAX group";
	}
      }
      // Done with case where the project's lead is not the admin ID
    } // end of block to handle project lead is not group admin
  } else if (project_is_expired($project_info)) {
    // return error
    $_SESSION['lasterror'] = "Project $project_name is expired - it cannot be WiMAX enabled";
    relative_redirect('wimax-enable.php');
  }

  // Now handle the request

  $enable_user = False;
  $enable_project = False;
  if ($user_is_project_lead) {
    if ($project_enabled) {
      // If the user leads the requested project and it is enabled, then only action is to delete it
      error_log("$ldif_user_username is lead of $project_name: delete the WiMAX group $ldif_group_name");
      $res = wimax_delete_group($ldif_group_name);
      if (true === $res) {
	// mark project as not wimax enabled
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	$project_enabled = False;

	// If this is the group for this user, disable this user
	if ($ldif_user_group_id == $ldif_project_id) {

	  // FIXME: Need a separate call to wimax_delete_user? Shouldnt but do I?

	  // mark user as not wimax enabled
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
	  $ldif_user_projname = null;
	  $ldif_user_groupname = null;
	  $ldif_user_group_id = null;
	}
	// return success of some kind
	$result_string = "<p>Disabled project $project_name for WiMAX.</p>";
      } else {
	// return error of some kind
	$is_error = True;
	$result_string = "<p><b>Failed</b> to disable project $project_name for WiMAX: $res</p>"; // FIXME: What should user do?
      }
    } else {
      // Project is not enabled. We want to enable it

      // if you already have a group, then just enable this project
      if ($user_is_wimax_enabled) {
	// Enable this project
	error_log("Project $project_name is not yet WiMAX enabled, but user $ldif_user_username is. Just enable the project");
	$enable_project = True;
      } else {
	// enable this group and join it
	error_log("Project $project_name is not yet WiMAX enabled, nor user $ldif_user_username. Enable project and put user in project as group admin");
	$ldif_user_projname = $ldif_project_name;
	$ldif_user_groupname = $ldif_group_name;
	$enable_project = True;
	$enable_user = True;
      }
    }
  } else {
    // user does not lead this project
    if (! $project_enabled) {
      // return error - non project lead cannot enable project
      error_log("wimax-enable: $ldif_user_username picked non enabled project $project_name?!");
      $_SESSION['lasterror'] = 'Project $project_name is not WiMAX enabled. The project lead must enable it for WiMAX first.';
      relative_redirect('wimax-enable.php');
    } else {
      // User is member of this project and the project is enabled

      // if this is your group then delete the user
      if ($ldif_user_group_id == $ldif_project_id) {
	error_log("Project $project_name is enabled as $ldif_group_name and is the users group. So delete user $ldif_user_username");
	// Delete user, fix attributes
	$res = wimax_delete_user($ldif_user_username, $ldif_user_groupname);
	if (true === $res) {
	  // Change relevant MA attribute, local vars
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
	  $ldif_user_projname = null;
	  $ldif_user_groupname = null;
	  $ldif_user_group_id = null;
	  // Return some success
	  $result_string = "<p>Disabled your WiMAX account with username '$ldif_user_username'</p>";
	} else {
	  // Return some error
	  $is_error = True;
	  $result_string = "<p><b>Failed</b> to disable your WiMAX account with username '$ldif_user_username': $res</p>"; // FIXME: What should user do?
	}
      } else {
	// join this group
	error_log("Project $project_name is enabled and is not the users group. So enable user $ldif_user_username in group $ldif_group_name");
	$enable_user = True;
	$ldif_user_projname = $ldif_project_name;
	$ldif_user_groupname = $ldif_group_name;
      }
    }
  }
    
  if ($enable_user or $enable_project) {

    if (! isset($ldif_user_groupname)) {
      error_log("No ldif_user_groupname!");
    }
    
    if (! isset($ldif_user_username)) {
      error_log("No ldif_user_username!");
    }
    
    if (! isset($ldif_group_name)) {
      error_log("No ldif_group_name!");
    }
    
    if ($enable_user) {
      // check that user has at least 1 SSH key
      $keys = $user->sshKeys();
      if (count($keys) == 0) {
	$_SESSION['lasterror'] = 'You have not uploaded any SSH keys.';
	relative_redirect('wimax-enable.php');
      }
    }
  
    $usernameTaken = True;
    
    while ($usernameTaken) {
      $usernameTaken = False;
      $ldif_string = "";
      if ($enable_project) {
	$ldif_string = get_ldif_for_project($ldif_group_name, $ldif_project_description);
	
	$ldif_string .= "\n" . get_ldif_for_project_lead($ldif_group_name, $ldif_user_username, $ldif_user_groupname);
	if ($enable_user) {
	  $ldif_string .= "\n";
	}
      }
      if ($enable_user) {
	// Could use the new changeProject URL here instead....
	$ldif_string .= get_ldif_for_user_string($ldif_user_username, $ldif_user_groupname, $ldif_user_pretty_name, $ldif_user_given_name, $ldif_user_email, $ldif_user_sn, $user, $ma_url, $ldif_project_description, "project member"); 
      }
      
      // SEND LDIF
      
      $postdata = array("ldif" => $ldif_string);
      $result = my_curl_put($postdata, $wimax_server_url);
      //      error_log("At $wimax_server_url send ldif $ldif_string and got result $result");
      if (strpos($result, "404 Not Found")) {
	error_log("wimax-enable curl put_message error: Page $wimax_server_url Not Found");
      } else if (strpos(strtolower($result), strtolower("ERROR 3: UID matches but DC and OU are different")) !== false) {
	// This implies that our portal member's username
	// already exists on ORBIT already. We can handle this error on our
	// side by generating a different username and trying to resubmit the
	// information again.
	error_log("WiMAX already has an account under username " . $ldif_user_username . " but not through the portal. Result: " . $result);
	$new_ldif_user_username = gen_new_username($ldif_user_username, gen_username_base($user));
	if (is_null($new_ldif_user_username)) {
	  break; // out of while usernameTaken
	} else {
	  $ldif_user_username = $new_ldif_user_username;
	  error_log(" ... trying new username " . $ldif_user_username);
	  $usernameTaken = True;
	}
      }

      // FIXME: Handle:
      //  ERROR31 = 'ERROR 31: Organization does not exist for this user. Missing organization LDIF entry'
      // This means that the local db thought the group exists, but wimax thinks it does not.
      // Update local state to say the group does not exist and send the ldif to create it and try again
      // But careful - who else thinks they are in this group? Who should be the group lead?
      // Maybe use the sync function to update local state and go back to the wimax page with an error message?

    } // end of while loop to retry on username taken
    
    // CHECK REPLY FROM SENDER
    
    /* if response was successful:
       add member_attribute to user
       name: enable_wimax
       value: <project_id>
        if enabling project
	add project_attribute to project
	name: enable_wimax
	value: project lead's account_id
    */
    if (strpos(strtolower($result), strtolower("ERROR 1: UID and OU and DC match")) !== false) {
      // This implies that there's an error with our
      // portal trying to resend the exact same information that it had done
      // at a previous time. That is, this user already has a WiMAX account under the given project name
      $result_string = "<p><b>WiMAX (already) enabled</b></p>\n<p>You already have a WiMAX account for username '$ldif_user_username' in project '$project_name'.</p>";
      $result_string = $result_string . "<p>Check your email ({$user->email()}) for more information.</p>";
      error_log($user->prettyName() . " already enabled for WiMAX in project " . $project_name . " (group $ldif_group_name). Result was: " . $result);

      // Get our DB in sync
      if ($enable_user) {
	// Remove any existing attribute for enabling wimax - we are changing the project we are enabled for
	remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	
	// add user as someone using WiMAX for given project
	add_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax', $ldif_project_id, 't');
	
	// If we enabled wimax under a variant of the username, record that
	add_member_attribute($ma_url, $user, $user->account_id, 'wimax_username', $ldif_user_username, 't');
	
	error_log($user->prettyName() . " was already enabled for WiMAX in project " . $project_name . " (group $ldif_group_name) with username " . $ldif_user_username . " according to the WiMAX server - updated our DB");
      }
      
      // if user is the project lead and the project is not enabled, enable the project for WiMAX
      if($enable_project and $ldif_project_lead_id == $user->account_id and ! $project_enabled) {
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $user->account_id);
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME, $ldif_group_name);
	error_log($user->prettyName() . " enabled project " . $project_name . " for WiMAX use in our DB (group $ldif_group_name; was already enabled in their system)");
      }

    } else if (strpos(strtolower($result), strtolower("ERROR 20: Group exists")) !== false) {
      //  ERROR20 = 'ERROR 20: Group exists'
      // This means that the project/group already exists
      // If we were just trying to create the group, then just change the group admin to be this user (the lead)
      // If we were also creating the user, add the user to the group and then change the admin to this user
      if (! $enable_user) {
	error_log("Failed to create group $ldif_group_name cause it already exists.");
	// This is mostly success. But we don't know who Orbit thinks is the lead of the group.
	// For now, assume it is who we think it is
	// Then treat this as success
	// if user is the project lead and the project is not enabled, enable the project for WiMAX
	if($enable_project and $ldif_project_lead_id == $user->account_id and ! $project_enabled) {
	  // Change the group admin to this user since that's what we expected
	  $res = wimax_make_group_admin($ldif_group_name, $ldif_user_username, $ldif_user_groupname);
	  if (true === $res) {
	    error_log("Changed group $ldif_group_name admin to $project_lead_username");
	  } else {
	    // Failed to change lead. This might happen if that user has not created their wimax account yet.
	    error_log("FIXME: Failed to change WiMAX group admin for project $proj_name (group $proj_group_name) to $project_lead_username but assuming success: $res");
	    // Maybe this means the group already had that lead?
	    // Assume success
	    // FIXME: If we see this, figure out if we are handling it correctly.
	  }

	  remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	  add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $user->account_id);
	  remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	  add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME, $ldif_group_name);
	  error_log($user->prettyName() . " (re)enabled project " . $project_name . " for WiMAX use as group $ldif_group_name");
	}
	$result_string = "<p>WiMAX already enabled in project '$project_name'. Your WiMAX username remains '$ldif_user_username'.</p>";
	$result_string .= "<p>Note that you are responsible for all WiMAX actions by members of your project.</p>";

      } else {
	error_log("Failed to add user to new group $ldif_group_name: Got Error 20 (group exists).");
	// We tried to enable the user and create the group at once.
	// But the group exists. Presumably with a different admin.
	// Add the member and change the admin to this user
	$didAdd = False; // If this remains false, we'll delete the group
	$res = add_member_to_group($user, $ldif_project_lead_id, $ldif_project_id, $ldif_group_name, $project_name, $ma_url, $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE], $wimax_server_url);
	if ($res === 0) {
	  error_log("Added " . $user->prettyName() . " WiMAX account in project $project_name.");
	  $didAdd = True;
	  $ldif_user_group_id = $ldif_project_id;
	  $ldif_user_groupname = $ldif_group_name;
	} else {
	  // FIXME: Now what?
	  error_log("FIXME: Could not create account for this user in pre-existing group.");
	  $is_error = True;
	  error_log("WiMAX already has group $ldif_group_name, and failed to add user to the existing group.");
	  $result_string = "<p><b>Error (from $wimax_server_url):</b> Could not add you to WiMAX group for project $project_name. Contact <a mailto:'help@geni.net'>GENI Help</a></p>";
	  $result_string = $result_string . "<p>Debug information:</p>";
	  $result_string = $result_string . "<p>Result: $res</p>";
	}
	$lead = geni_load_user_by_member_id($ldif_project_lead_id);
	if (! isset($lead->ma_member->enable_wimax)) {
	  if ($didAdd) {
	    error_log("FIXME: Auto added project lead still has no wimax account");
	    $didAdd = False;
	    $is_error = True;
	    error_log("WiMAX already has group $ldif_group_name, and had error adding user to the existing group.");
	    $result_string = "<p><b>Error (from $wimax_server_url):</b> Could not add you to WiMAX group for project $project_name. Contact <a mailto:'help@geni.net'>GENI Help</a></p>";
	    $result_string = $result_string . "<p>Debug information:</p>";
	    $result_string = $result_string . "<p>Result: $res</p>";
	    $result_string = $result_string . "<p>Group: $ldif_group_name</p>";
	    $result_string = $result_string . "<p>User: $ldif_user_username</p>";
	  }
	  // Else we complained about this above already
	} else {
	  if (isset($lead->ma_member->wimax_username)) {
	    $project_lead_username =$lead->ma_member->wimax_username;
	  } else {
	    $project_lead_username = gen_username_base($lead);
	  }
	  $ldif_user_username = $project_lead_username;
	  $project_lead_group_id = $lead->ma_member->enable_wimax;
	  $lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	  $lead_project_attrs = lookup_project_attributes($sa_url, $user, $project_lead_group_id);
	  $project_lead_groupname = get_group_name($lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME], $lead_project_attrs);

	  $res = wimax_make_group_admin($ldif_group_name, $project_lead_username, $project_lead_groupname);
	  if (true === $res) {
	    // Change relevant PA attribute, local vars
	    remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	    add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $ldif_project_lead_id);
	    $result_string = "<p><b>Success</b>: WiMAX already enabled in project '$project_name', but you are now group lead.</p>";
	    $result_string = $result_string . "<p>Your WiMAX username is '$ldif_user_username' for WiMAX group '$ldif_group_name'. Check your email ({$user->email()}) for login information.</p>";
	    $result_string .= "<p>Note that you are responsible for all WiMAX actions by members of your project.</p>";
	  } else {
	    // Failed to change lead. This might happen if that user has not created their wimax account yet.
	    error_log("Failed to change WiMAX group admin for project $proj_name (group $proj_group_name) to $project_lead_username: $res");
	    $didAdd = False;
	    $is_error = True;
	    $result_string = "<p><b>Error (from $wimax_server_url):</b> Created your WiMAX account using username $ldif_user_username in group $ldif_group_name, but failed to make you lead of the group. Contact <a mailto:'help@geni.net'>GENI Help</a></p>";
	    $result_string = $result_string . "<p>Debug information:</p>";
	    $result_string = $result_string . "<p>Result: $res</p>";
	    $result_string = $result_string . "<p>Group: $ldif_group_name</p>";
	    $result_string = $result_string . "<p>User: $ldif_user_username</p>";
	    $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $ldif_group_name WiMAX group";
	  }
	}
	if (! $didAdd) {
	  // All that didn't work.
	  // FIXME FIXME FIXME
	  error_log("FIXME: Unhandled error 20!");
	}
      }
    } else if (strpos(strtolower($result), strtolower("ERROR 3: UID matches but DC and OU are different")) !== false) {
      // This implies that our portal member's username
      // already exists on ORBIT already. We can handle this error on our
      // side by generating a different username and trying to resubmit the
      // information again.
      // And that is what we do above - so if we got here, we couldn't find a variation that was not already taken.
      $is_error = True;
      error_log("WiMAX already has an account under username " . $ldif_user_username . " but not through the portal. Couldn't find a username username. Result: " . $result);
      $result_string = "<p><b>Error (from $wimax_server_url):</b> Could not find a username for you that doesn't already exist. Contact <a mailto:'help@geni.net'>GENI Help</a></p>";
      $result_string = $result_string . "<p>Debug information:</p>";
      $result_string = $result_string . "<p>Result: $result</p>";
      $result_string = $result_string . "<blockquote><pre>$ldif_string</pre></blockquote>";
    } else if (strpos(strtolower($result), strtolower("ERROR 2: UID and DC match but OU is different")) !== false) {
      // This is trying to change the project for a person. Supposedly this should never happen as the service
      // supports this now.
      $is_error = True;
      $result_string = "<p><b>Error trying to change WiMAX project for '$ldif_user_username' to '$ldif_project_name': $result</b></p>";
      $result_string = $result_string .  "<p>Debug information:</p>";
      $result_string = $result_string .  "<blockquote><pre>$ldif_string</pre></blockquote>";
      error_log("Unexpected error changing WiMAX project for " . $user->prettyName() . " to project " . $project_name . " (group $ldif_group_name): " . $result);
    } else if (strpos(strtolower($result), 'success') !== false) {
      // FIXME: Was this user enabled for wimax before? And for that project, is this user the lead?
      // If so, should that wimax project no longer be wimax enabled? Do I need to send new LDIF?
      
      if ($enable_user) {
	// Remove any existing attribute for enabling wimax - we are changing the project we are enabled for
	remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	
	// add user as someone using WiMAX for given project
	add_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax', $ldif_project_id, 't');
	
	// If we enabled wimax under a variant of the username, record that
	add_member_attribute($ma_url, $user, $user->account_id, 'wimax_username', $ldif_user_username, 't');
	
	error_log($user->prettyName() . " enabled for WiMAX in group " . $ldif_group_name . " with username " . $ldif_user_username);
      }
      
      // if user is the project lead and the project is not enabled, enable the project for WiMAX
      if($enable_project and $ldif_project_lead_id == $user->account_id and ! $project_enabled) {
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $user->account_id);
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME, $ldif_group_name);
	error_log($user->prettyName() . " enabled project " . $project_name . " for WiMAX use as group $ldif_group_name");
      }

      if ($enable_user and ! $enable_project) {
	$result_string = "<p><b>Success</b>: You have requested your account and/or changed your WiMAX project.</p>";
	$result_string = $result_string . "<p>Your WiMAX username is '$ldif_user_username' for project '$project_name'. Check your email ({$user->email()}) for login information.</p>";
      } else if ($enable_user and $enable_project) {
	$result_string = "<p><b>Success</b>: You have enabled WiMAX in project '$project_name' and requested your account and/or changed your WiMAX project.</p>";
	$result_string = $result_string . "<p>Your WiMAX username is '$ldif_user_username' for WiMAX group '$ldif_group_name'. Check your email ({$user->email()}) for login information.</p>";
	$result_string .= "<p>Note that you are responsible for all WiMAX actions by members of your project.</p>";
      } else if (! $enable_user and $enable_project) {
	$result_string = "<p><b>Success</b>: You have enabled WiMAX in project '$project_name'. Your WiMAX username remains '$ldif_user_username'.</p>";
	$result_string .= "<p>Note that you are responsible for all WiMAX actions by members of your project.</p>";
      }
      
    } else {
  
      $is_error = True;
      $result_string = "<p><b>Error (from $wimax_server_url):</b> $result</p>";
      $result_string = $result_string . "<p>Debug information:</p>";
      $result_string = $result_string . "<blockquote><pre>$ldif_string</pre></blockquote>";
      error_log("Unknown Error enabling WiMAX for " . $user->prettyName() . " in project " . $project_name . " (group $ldif_group_name): " . $result);
      
    }
  } // end of block to only enable user/group if needed
  
  // Now display the page with the result for the user

  show_header('GENI Portal: WiMAX Setup', $TAB_PROFILE);
  include('tool-breadcrumbs.php');
  include("tool-showmessage.php");

  echo "<h1>WiMAX</h1>";
  // FIXME: Show errors differently somehow?
  if ($is_error) {
    echo "<h2>Error</h2>";
  }
  echo $result_string;

  if (! $is_error and ($enable_user or $enable_project)) {
    echo "<a href='https://geni.orbit-lab.org'><p style='width:150px; margin:0 auto'><img src='/images/orbit_banner.png' alt='Orbit Lab'></p><p style='width:300px; margin:5px auto 0'>Use GENI Orbit WiMAX resources.</p></a>";
  }

  // include link to main WiMAX page
  echo "<p><a href='wimax-enable.php'>Back to main WiMAX page</a></p>";

}

/* PAGE 1 */
/* user needs to select project (initial screen) */

else { // Page 1: User picking a project

  $warnings = array();
  $keys = $user->sshKeys();
  if ($num_projects > 0) {
    // If there's more than 1 project, we need the project names for
    // a default project chooser.
    $projects = lookup_project_details($sa_url, $user, $project_ids);
  }
  $is_project_lead = $user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null);

  if ($num_projects == 0) {
    // warn that the user has no projects
    $warn = '<p class="warn">You are not a member of any projects.'
          . ' No project can be chosen unless you';
    if ($is_project_lead) {
      $warn .=  ' <button onClick="window.location=\'edit-project.php\'"><b>create a project</b></button> or';
    }
    $warn .= ' <button onClick="window.location=\'join-project.php\'"><b>join a project</b></button>.</p>';
    $warnings[] = $warn;
  }

  if (count($keys) == 0) {
    // warn that no ssh keys are present.
    $warnings[] = '<p class="warn">No SSH keys have been uploaded. '
          . 'Please <button onClick="window.location=\'uploadsshkey.php\'">'
           . 'Upload an SSH key</button> or <button'
           . ' onClick="window.location=\'generatesshkey.php\'">Generate and'
           . ' Download an SSH keypair</button> to enable logon to nodes.'
          . '</p>';
  }

  $projects_lead = array();
  $projects_non_lead = array();
  $projects_non_lead_disabled = array();
  $projects_admin = array();
  $projects_lead_count = 0;
  $projects_non_lead_count = 0;
  $projects_non_lead_disabled_count = 0;
  $is_group_admin = False; // is this user admin for any group

  // if user is member of 1+ projects and has 1+ SSH keys
  if ($num_projects >= 1 && count($keys) >= 1) {

// Code outline:
/*
Get user's projects (expired or not)
 -- see $projects
  Get ID, lead_id, name, description, group_admin_id if any, expired?
*/
    foreach ($projects as $proj) {
      $proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_attributes = lookup_project_attributes($sa_url, $user, 
						      $proj_id);
      $proj_enabled = False;
      $proj["enabled"] = False;
      $proj_admin_id = null;
      $proj["admin_id"] = null;
      $proj["lead_name"] = null;
      $proj_lead_id = $proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
      $proj_name = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $proj_group_name = get_group_name($proj_name, $project_attributes);
      $proj["group_name"] = $proj_group_name;
      $proj_desc = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
      $proj["desc"] = $proj_desc;
      $proj_expired = project_is_expired($proj);
      foreach($project_attributes as $attribute) {
	if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
	  $proj_enabled = True;
	  $proj_admin_id = $attribute[PA_ATTRIBUTE::VALUE];
	  $proj["enabled"] = True;
	  $proj["admin_id"] = $proj_admin_id;
	  break;
	}
      }
  /*
  Is enabled?
     Is expired? Delete group and keep going (to next project)
     Is listed group admin not project lead?
       Call change group admin function and keep going
     Find all members whose wimax-enable attribute lists this projects ID
       If that member is not a member of this project, then call delete-user and keep going
       - Need a get_members_with_attribute function
  Is expired?
     continue to next project

  If user->account_id==lead_id
    Add to projects-I-lead
  else if group_admin_id is set
    Add to projects-I-dont-lead-that-are-enabled
  else add to projects-not-mine-not-enabled
*/
      if ($proj_enabled) {
	// Error checks
	if ($proj_expired) {
	  // delete group and keep going to next project
	  error_log("Project $proj_name is wimax enabled but expired - delete");
	  $res = wimax_delete_group($proj_group_name);
	  if (true === $res) {
	    // mark project as not wimax enabled
	    remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	    remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	    $proj_enabled = False;

	    // If this is the group for this user, disable this user
	    if (isset($ldif_user_group_id) and $ldif_user_group_id == $proj_id) {
	      // mark user as not wimax enabled
	      remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	      remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	      $user->ma_member->enable_wimax = False;
	      $user_is_wimax_enabled = False;
	      $ldif_user_groupname = null;
	      $ldif_user_group_id = null;
	    }
	    // return success of some kind
	    $warnings[] = "<p>Disabled project $proj_name for WiMAX.</p>";
	  } else {
	    // return error of some kind
	    //$is_error = True;
	    $warnings[] = "<p><b>Failed</b> to disable project $proj_name for WiMAX: $res</p>"; // FIXME: What should user do?
	  }
	  continue; // don't use this project
	} // end of block to handle project expired and enabled

	if ($proj_admin_id != $proj_lead_id) {
	  // change group admin and keep going to next project
	  error_log("Project $proj_name is wimax enabled with group admin that is no longer the project lead - change admin");
	  // get username from member ID
	  // get member attribute
	  $lead = geni_load_user_by_member_id($proj_lead_id);
	  $didAdd = False;
	  $proj["lead_name"] = $lead->prettyName();
	  if (! isset($lead->ma_member->enable_wimax)) {
	    error_log("Project $proj_name has lead " . $lead->prettyName() . " who is not yet wimax enabled");
	    if (count(lookup_public_ssh_keys($ma_url, $user, $proj_lead_id)) > 0) {
	      $res = add_member_to_group($lead, $proj_lead_id, $proj_id, $proj_group_name, $proj_name, $ma_url, $proj_desc, $wimax_server_url);
	      if ($res === 0) {
		error_log("Auto added project $proj_name " . $lead->prettyName() . " WiMAX account in that project.");
		$didAdd = True;
	      } else {
		// FIXME: Now what?
		error_log("FIXME: Could not create account for this lead. Delete the group?");
	      }
	    } else {
	      error_log("New project lead has 0 SSH keys, cannot create their WiMAX account.");
	    }
	  }
	  $lead = geni_load_user_by_member_id($proj_lead_id);
	  if (! isset($lead->ma_member->enable_wimax)) {
	    if ($didAdd) {
	      error_log("FIXME: Auto added project lead still has no wimax account");
	    }
	    // Else we complained about this above already
	  } else {
	    if (isset($lead->ma_member->wimax_username)) {
	      $project_lead_username =$lead->ma_member->wimax_username;
	    } else {
	      $project_lead_username = gen_username_base($lead);
	    }
	    $project_lead_group_id = $lead->ma_member->enable_wimax;
	    $lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	    $lead_project_attrs = lookup_project_attributes($sa_url, $user, $project_lead_group_id);
	    $project_lead_groupname = get_group_name($lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME], $lead_project_attrs);
	    
	    $res = wimax_make_group_admin($proj_group_name, $project_lead_username, $project_lead_groupname);
	    if (true === $res) {
	      // Change relevant PA attribute, local vars
	      remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	      add_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $proj_lead_id);
	      $proj_admin_id = $proj_lead_id;
	      $proj["admin_id"] = $proj_lead_id;
	    } else {
	      // Failed to change lead. This might happen if that user has not created their wimax account yet.
	      error_log("Failed to change WiMAX group admin for project $proj_name (group $proj_group_name) to $project_lead_username: $res");
	      // FIXME FIXME - Use $res
	      // $is_error = True;
	      // $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $proj_group_name WiMAX group";
	    }
	  }
	} // end of block to handle group has wrong admin

	// get all members with a wimax-enable attribute that lists this project ID

	$members_of_group = ma_lookup_members_by_identifying($ma_url, $user, 
							     '_GENI_ENABLE_WIMAX', $proj_id);
	//	$members_of_group = ma_lookup_members($ma_url, $user, array("enable_wimax" => $proj_id));
	$members_of_proj = get_project_members($sa_url, $user, $proj_id);
	// for each:
	foreach ($members_of_group as $member) {
	  $member_id = $member->member_id;


	  // Confirm the member is not disabled
	  $member_enabled = True;
	  if (property_exists($member, "member_enabled") and $member->member_enabled == "n") {
	    $member_enabled = False;
	    $member_prettyname = $member->prettyName();
	    error_log("wimax-enable: Member $member_prettyname listed as member of wimax group for $proj_name, but the member is disabled. Remove them.");
	  }

	  // Make sure the member is still in this CH project
	  $found = false;
	  foreach ($members_of_proj as $mp) {
	    if ($member_id == $mp['member_id']) {
	      $found = True;
	      break; // out of loop over members of project
	    }
	  }	  
	  //   if that member_id is not a member of this project, call delete-user and continue to next member
	  if (! $found) {
	    $member_prettyname = $member->prettyName();
	    error_log("wimax-enable: Member $member_prettyname lists $proj_name as their group, but they are not a member - delete wimax account");
	  }

	  // The member is disabled or not in the project - remove their wimax account
	  if (! $found || ! $member_enabled) {
	    if (isset($member->wimax_username)) {
	      $member_username = $member->wimax_username;
	    } else {
	      $mu = new GeniUser();
	      $member_username = gen_username_base($mu->ini_from_member($member));
	    }
	    error_log("wimax-enable: Member $member_prettyname lists $proj_name (group $proj_group_name) as their group, but they are not a member - delete wimax account");
	    $res = wimax_delete_user($member_username, $proj_group_name);
	    if (true === $res) {
	      // Change relevant MA attribute
	      remove_member_attribute($ma_url, $user, $member_id, 'enable_wimax');
	      remove_member_attribute($ma_url, $user, $member_id, 'wimax_username');
	    } else {
	      // FIXME: WiMAX has the user, we want them deleted.
	      // FIXME FIXME Use $res
	      error_log("wimax failed to delete user $member_username so left MA attributes alone");
	    }
	  }
	} // done looping over members who claim this proj_id as their group

	if ($proj_admin_id == $user->account_id) {
	  // Add to list of projects I admin
	  $projects_admin[] = $proj;
	  $is_group_admin = True; // this user is admin for at leat one group
	}

	if ($proj_lead_id !== $user->account_id) {
	  // add to list of not my projects that are enabled
	  $projects_non_lead[] = $proj;
	  $projects_non_lead_count++;
	}

      } // done with checks on enabled projects

      if ($proj_expired) {
	// Project is not enabled we think, so don't add it to our lists
	continue;
      }

      if ($proj_lead_id == $user->account_id) {
	// add to list of projects I lead
	$projects_lead[] = $proj;
	$projects_lead_count++;
      } else if (! $proj_enabled) {
	// add to list of project I do not lead that are not enabled
	$projects_non_lead_disabled[] = $proj;
	$projects_non_lead_disabled_count++;
      }

      // get this users username, project name, and a list of the projects this user is admin for and their project names

      // Now group projects into 3 categories
      // 1. get a list of project this user leads that are not expired
      // For each, are they wimax enabled, is this the users current wimax group
      // 2. List of projects you belong to that are enabled and not expired
      // For each: is this your wimax group, name, lead name?
      // 3. Other projects not expired:
      // For each: name, lead name
      

    } // end of loop over projects
  } // end of block to work with projects only if user has projects and keys

  // Now show the page

  show_header('GENI Portal: WiMAX Setup', $TAB_PROFILE);
  include('tool-breadcrumbs.php');
  include("tool-showmessage.php");

  echo "<h1>WiMAX</h1>\n";
  foreach ($warnings as $warning) {
    echo $warning;
  }
  
  // if user is member of 1+ projects and has 1+ SSH keys
  if ($num_projects >= 1 && count($keys) >= 1) {

/*
  Page should look like this:
Status
  You have a WiMAX account with username <name> in project <pname>.
  You are the WiMAX group admin for these projects of yours: <list of project names>

Projects I Lead
P1 WiMAX Enabled (group_admin_id set)   Your WiMAX Group (user_group_id==project_id)
     Actions (debug: radio: Disable and delete member accounts, including yours)
P2  WiMAX Enabled NOT Your group
     Actions: (debug: radio: Disable and delete member accounts)
P3/8 NOT WiMAX Enabled and you have a group (different) (user_group_id is set and !=project_id)
     Actions: (radio: WiMAX Enable)
P9 NOT WiMAX Enabled, IHaveNoGroup (user_group_id not set / empty)
    Actions (radio: WiMAX Enable and Make this my WiMAX group)

Projects I Belong To That Are Enabled
P4 Your WiMAX Group (user_group_id==project_id)
   Actions (debug: radio: Delete your member account)
P5
   Actions (radio: Change to this WiMAX Group)
P6
   Actions (radio: Change to this WiMAX Group)

Projects I Belong To That Are NOT Enabled
P7
   Actions (none)
*/

    echo "<h2>Your Status</h2>";

    // If you have a project:
    if (isset($ldif_user_groupname)) {
      echo "<p>You have elected to use WiMAX on project " 
        . "<a href='project.php?project_id=" 
        . $ldif_user_group_id 
        . "'>" . $ldif_user_projname . "</a> with username '$ldif_user_username'. </p>";
      echo "<a href='https://geni.orbit-lab.org'><p style='width:150px; margin:0 auto'><img src='/images/orbit_banner.png' alt='Orbit Lab'></p><p style='width:300px; margin:5px auto 0'>Use GENI Orbit WiMAX resources.</p></a>";
      if (count($projects_admin) > 0) {
	echo "<p>You are the WiMAX group admin for these projects that you lead: ";
	$cnt = 0;
	foreach ($projects_admin as $p) {
	  if ($cnt > 0) {
	    echo ", ";
	  }
	  $cnt++;
	  echo $p[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	}
	echo ".</p>";
      }
    } else {
      echo "<p>You have not enabled WiMAX on any of your projects. ";
      echo "Please select a project below.<br>";
    }
      // if you are admin of a group:

    // start a form
    echo '<form id="f1" action="wimax-enable.php" method="get">';
    
    // for projects I lead, allow for enabling of WiMAX
    if($projects_lead_count > 0) {
      echo "<h2>Enable WiMAX for Projects You Lead</h2>";
      echo "<p><i>Note that enabling a project for WiMAX means that all of the project's members can request WiMAX resources for that project, and the project lead is ultimately responsible for the actions of members.</i></p>";
      if ($is_group_admin) {
	echo "<p>As a WiMAX group admin, you cannot change your WiMAX group without first making someone else lead of your project or expiring the project or disabling it for WiMAX use. You can however enable other projects that you lead for WiMAX use.</p>";
      }
      echo "<p>You can <b>enable or disable WiMAX resources</b> and/or <b>request WiMAX login information</b> for the following projects that you lead:</p>";
      
      echo "<table>";
      echo "<tr><th>Project Name</th><th>Project Lead</th><th>Purpose</th><th>Enable/Disable WiMAX for Project</th></tr>";
      $lead_names = lookup_member_names_for_rows($ma_url, $user, $projects_lead, 
					     PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
      foreach($projects_lead as $proj) {
        $lead_id = $proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
	$proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
	$proj_name = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	$proj_group_name = $proj["group_name"];
        $lead_name = $lead_names[$lead_id];
        echo "<tr>";
        echo "<td><a href='project.php?project_id=$proj_id'>{$proj_name}</a></td>";
        echo "<td>$lead_name</td>";
        echo "<td>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]}</td>";
        echo "<td>";
        $enabled = $proj["enabled"]; // Is project WiMAX enabled in our DB?

	// See case 6 below
	if ($enabled and ! isset($ldif_user_group_id)) {
	  // Project enabled but lead has no group
	  // Could be the project lead changed and old lead is the admin. In this case right thing
	  // is to create this user as member of that group and then change admin
	  // Else we have real data sync problem, and we need to know what orbit has and make ours match. And then
	  // If it still makes no sense, delete it all? Or what?
	  // Need util functions to create user and changeAdmin. 
	  // We should prefer to create users&groups to fix the problem rather than delete.
	  // So check who we think admin is, and then create user and optionally change admin. If createUser fails,
	  // then we need to know what group orbit things the user is in if they exist and try to match that possibly.
	  // And if changeAdmin fails then similarly

	  // FIXME!!
	  // Check what orbit has and sync state.
	  // Check if admin we list is diff. Then maybe I just need to create the user and change admin
	  // If admin is same, then maybe create the user in that group?


	  // Project lead is not enabled. Lets assume this is the result of a lead change.
	  // Add the lead to this group and make them the admin. If that fails, delete the group.
	  $didAdd = False; // If this remains false, we'll delete the group
	  error_log("Project $proj_name has lead " . $user->prettyName() . " who is not yet wimax enabled - try to enable and make them group admin");
	  if (count($user->sshKeys()) > 0) {
	    $res = add_member_to_group($user, $lead_id, $proj_id, $proj_group_name, $proj_name, $ma_url, $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE], $wimax_server_url);
	    if ($res === 0) {
	      error_log("Auto project $proj_name lead " . $user->prettyName() . " WiMAX account in that project.");
	      $didAdd = True;
	      $ldif_user_group_id = $proj_id;
	      $ldif_user_groupname = $proj_group_name;
	    } else {
	      // FIXME: Now what?
	      error_log("FIXME: Could not create account for this lead. Delete the group.");
	    }
	  } else {
	    error_log("New project lead has 0 SSH keys, cannot create their WiMAX account. Must delete the group $proj_group_name");
	  }
	  $lead = geni_load_user_by_member_id($lead_id);
	  if (! isset($lead->ma_member->enable_wimax)) {
	    if ($didAdd) {
	      error_log("FIXME: Auto added project lead still has no wimax account");
	      $didAdd = False;
	    }
	    // Else we complained about this above already
	  } else {
	    if (isset($lead->ma_member->wimax_username)) {
	      $project_lead_username =$lead->ma_member->wimax_username;
	    } else {
	      $project_lead_username = gen_username_base($lead);
	    }
	    $ldif_user_username = $project_lead_username;
	    $project_lead_group_id = $lead->ma_member->enable_wimax;
	    $lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	    $lead_project_attrs = lookup_project_attributes($sa_url, $user, $project_lead_group_id);
	    $project_lead_groupname = get_group_name($lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME], $lead_project_attrs);

	    $res = wimax_make_group_admin($proj_group_name, $project_lead_username, $project_lead_groupname);
	    if (true === $res) {
	      // Change relevant PA attribute, local vars
	      remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	      add_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $lead_id);
	      $proj_admin_id = $lead_id;
	      $proj["admin_id"] = $lead_id;
	    } else {
	      // Failed to change lead. This might happen if that user has not created their wimax account yet.
	      error_log("Failed to change WiMAX group admin for project $proj_name (group $proj_group_name) to $project_lead_username: $res");
	      $didAdd = False;
	      // FIXME FIXME - Use $res
	      // $is_error = True;
	      // $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $proj_group_name WiMAX group";
	    }
	  }

	  if (! $didAdd) {
	    // Old code:
	    // Lead has no WiMAX group then their project cannot be enabled
	    error_log("Project enabled but lead has no group? Delete Wimax Group " . $proj_group_name);
	    $res = wimax_delete_group($proj_group_name);
	    if (true === $res) {
	      // mark project as not wimax enabled
	      remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	      remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::WIMAX_GROUP_NAME);
	      $enabled = False;
	    } else {
	      // Failed to delete WiMAX group whose portal LEAD has no WiMAX group
	      // FIXME FIXME
	      // Could go back to the wimax-enable page with an internal error message, using $res?
	      error_log("Wimax failed to delete group $proj_group_name whose portal lead is not wimax enabled so left project attributes alone");
	    }
	  }
	}

	// See case 5 below
	if (! $enabled and isset($ldif_user_group_id) and $ldif_user_group_id==$proj_id) {
	  error_log("Proj " . $proj_name . " not enabled but lead lists that proj_id as their WiMAX group - delete user $ldif_user_username");

	  // Could create the group with this user as admin. But it's strange that we are in this state. It suggests something broke earlier. We probably
	  // want to get the orbit state, sync up with what they have. Cause that might fix it. If not, crete the group or delete the user.
	  // We should prefer to create the group rather than delete the user (who might be doing real work)

	  $res = wimax_delete_user($ldif_user_username, $proj_group_name);
	  if (true === $res) {
	    // Change relevant MA attribute
	    remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	    remove_member_attribute($ma_url, $user, $user->account_id, 'wimax_username');
	    $ldif_user_group_id = null;
	  } else {
	    // FIXME: WiMAX has the user, we want them deleted.
	    // FIXME FIXME Use $res
	    error_log("Failed to delete wimax user $ldif_user_username who listed project $proj_name as their group but the group ($proj_group_name) is not listed as wimax enabled, so left ma_member_attributes alone");
	  }
	}

	// Now write buttons/actions.
	// Case 1
	if ($enabled and isset($ldif_user_group_id) and $ldif_user_group_id === $proj_id) {
	  // Label this: Project is WiMAX enabled and this is your WiMAX group
	  echo "<b>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]} is enabled for WiMAX and is your WiMAX project</b><br/>";
	  //     Actions (debug: radio: Disable and delete member accounts, including yours)
	  echo "<input type='radio' name='project_id' value='" . $proj_id . "'> Disable project for WiMAX and delete member WiMAX accounts, including yours";
	}
	// case 2
	else if ($enabled and isset($ldif_user_group_id) and $ldif_user_group_id !== $proj_id) {
	  // Label this: Project is WiMAX enabled but is not your WiMAX group
	  echo "<b>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]} is enabled for WiMAX but is not your WiMAX project</b><br/>";
	  // Action: (debug: radio: Disable and delete member accounts)
	  echo "<input type='radio' name='project_id' value='" . $proj_id . "'> Disable project for WiMAX and delete member WiMAX accounts";
	}
	// case 3
	else if (! $enabled and isset($ldif_user_group_id) and $ldif_user_group_id !== $proj_id) {
	  // Label this: Enable this project for WiMAX (this is not your WiMAX group)
	  // Action: WiMAX Enable
          echo "<input type='radio' name='project_id' value='" . $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]
             . "' > Enable project {$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]} for WiMAX";
	}
	// case 4
	else if (! $enabled and ! isset($ldif_user_group_id)) {
	  // Label this: Enable this project for WiMAX and request a login
	  // Action: WiMAX Enabled and Request Login
          echo "<input type='radio' name='project_id' value='" . $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]
	    . "' > Enable project {$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]} for WiMAX and select it as your WiMAX project";
	}
	// Error cases follow
	// case 5
	// Note we specifically test for this above and try to resolve it by deleting the user
	else if (! $enabled and isset($ldif_user_group_id) and $ldif_user_group_id === $proj_id) {
	  // FIXME FIXME
	  // Error. Portal DB thinks this project is the WiMAX group for this user, but that this project is not WiMAX enabled.
	  // Internal state problem in portal DB.
	  // If we got here, we failed to fix the problem above
	  // FIXME: Try a different solution than is done above
	  error_log("Portal DB says project $proj_name is the WiMAX Group for this user, but that the project has no WiMAX group (not WiMAX enabled)");
	  // FIXME
	  // Ask Orbit
	  // Update our state to match orbit and do case 1-4 as appropriate. 
	  // If orbit agrees with this mismatch:
	  // create the group with this user as admin
	  echo "Internal error: Cannot enable this project";
	} 
	// case 6
	// Note we specifically test for this above and try to resolve it by deleting the group
	else if ($enabled and ! isset($ldif_user_group_id)) {
	  // FIXME FIXME
	  // Project says it is WiMAX enabled (group exists), but this user - the lead of that project - says they are not WiMAX enabled yet
	  error_log("Portal DB says project $proj_name is a WiMAX group ($proj_group_name), but the project lead does not have a WiMAX account");
	  // Who does the project in our DB say is the wimax group admin? Maybe the project lead changed.
	  if ($proj["admin_id"] !== $user->account_id) {
	    error_log("Portal DB says project admin is '" . $proj["admin_id"] . "', which is not this user (the project lead)");
	    // FIXME
	    // Ask Orbit and update our state to match
	    // If now in case 1-4, handle that
	    // If orbit agrees:
	    // create the user in this group
	    // do changeLead to make this user the admin of the wimax group
	  } else {
	    error_log("Portal DB says this user is the project WiMAX admin");
	    // FIXME
	    // Ask orbit and update our state to match
	    // Do case 1-4 as approp
	    // If orbit agrees:
	    // create the user in this group
	  }
	  echo "Internal error: Cannot create your WiMAX account in this project";
	} else {
	  // Other unknown case
	  error_log("huh? Got case in projects I lead that shouldn't happen. enabled=$enabled, ldif_user_group_id=$ldif_user_group_id, proj_id=$proj_id. Proj name: $proj_name, Proj group name: $proj_group_name");
	  // FIXME: See ticket #1058
	  echo "Unknown internal error";
	}
        echo "</td>";
        echo "</tr>";
      }
      
      echo "</table>";
    } // done with table of projects for which user is lead

    // For projects I belong to that are enabled
    if($projects_non_lead_count > 0) {
      echo "<h2>Request WiMAX Login Information</h2>";
      $disabled = "";
      if (! $is_group_admin) {
	echo "<p>You can <b>request WiMAX login information</b> for the following projects:</p>";
      } else {
	$disabled = "disabled";
	echo "<p>You cannot change your WiMAX project as you are a WiMAX group admin. Projects include:</p>";
      }
      
      echo "<table>";
      echo "<tr><th>Project Name</th><th>Project Lead</th><th>Purpose</th><th>Request Login Info</th></tr>";
      $lead_names = lookup_member_names_for_rows($ma_url, $user, $projects_non_lead, 
						 PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
      foreach($projects_non_lead as $proj) {
	$proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
        echo "<tr>";
        echo "<td><a href='project.php?project_id={$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]}'>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}</a></td>";
        $lead_id = $proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
        $lead_name = $lead_names[$lead_id];
        echo "<td>$lead_name</td>";
        echo "<td>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]}</td>";
        // determine which project has been requested
        echo "<td>";
	// Case 1: This is your project
	if (isset($ldif_user_group_id) and $ldif_user_group_id == $proj_id) {
	  echo $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . " is your WiMAX project</b>";
	  if (!$is_group_admin) {
	    //   Actions (debug: radio: Delete your member account)
	    echo "<br/><input type='radio' name='project_id' value='$proj_id'> Delete your WiMAX account";
	  } else {
	    // label, no action
	  }
	}
	// Case 2: This is not your project
	else {
	  //   Actions (radio: Change to this WiMAX Group)
	  echo "<input type='radio' name='project_id' value='$proj_id' $disabled> Use WiMAX in this project";
	}
        echo "</td>";
        echo "</tr>";
      }
      
      echo "</table>";
    } // end of block for projects you belong to but are not lead of

    // Put up the button
    // only show button for these two cases
    if (($projects_lead_count > 0) || ($projects_non_lead_count > 0)) {
      echo "<p><button onClick=\"document.getElementById('f1').submit();\">Submit</button></p>";
    }

    // end form
    echo "</form>";
    
    // For projects I belong to that are not enabled
    if ($projects_non_lead_disabled_count > 0) {
      echo "<h2>Projects Not Enabled</h2>";
      echo "<p>You are a member of the following projects that do not have WiMAX enabled. ";
      if (! $is_group_admin) {
	echo "Please contact your project lead if you would like to use WiMAX on any one of these projects:";
      }
      echo "</p>";
      echo "<ul>";
      $lead_names = lookup_member_names_for_rows($ma_url, $user, $projects_non_lead_disabled, 
					     PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
      foreach($projects_non_lead_disabled as $proj) {
        echo "<li><a href='project.php?project_id={$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]}'>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}</a> ";
        $lead_id = $proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
        $lead_name = $lead_names[$lead_id];
        echo "(project lead: $lead_name)</li>";
      }
      echo "</ul>";
    } // end of not enabled not your projects

  } // end of block to only do this if user has projects and keys

} // end of page 1 handling

include("footer.php");
?>

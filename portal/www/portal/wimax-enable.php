<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2013 Raytheon BBN Technologies
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

// If user isn't supposed to see the wimax stuff at all, stop now
if (! $user->hasAttribute('enable_wimax_button')) {
  relative_redirect('home.php');
}

// FIXME: hard-coded url for Rutgers ORBIT
// See tickets #772, #773
$old_wimax_server_url = "https://www.orbit-lab.org/userupload/save"; // Ticket #771
$wimax_server_base_url = "https://www.orbit-lab.org/login/"; // Sept, 2013

$wimax_server_url = $wimax_server_base_url . "save";
$wimax_server_deluser_url = $wimax_server_base_url . "deleteUser";
$wimax_server_delgroup_url = $wimax_server_base_url . "deleteProject";
$wimax_server_changeadmin_url = $wimax_server_base_url . "changeLeader";
$wimax_server_changegroup_url = $wimax_server_base_url . "changeProject";

// Does the server allow a wimax group lead to change their project?
$pi_can_change_project = False;

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);

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

function get_name_of_project($project_id, $user, $sa_url) {
  $project_info = lookup_project($sa_url, $user, $project_id);
  if (! is_null($project_info) and is_array($project_info) and array_key_exists($project_info, PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME)) {
    return $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  } else {
    return "";
  }
}

function get_project_dn($ldif_project_name) {
  return "ou=$ldif_project_name,dc=ch,dc=geni,dc=net";
}

function get_ldif_for_project($ldif_project_name, $ldif_project_description) {
  return "# LDIF for a project\n"
    . "dn: " . get_project_dn($ldif_project_name) . "\n"
    . "description: $ldif_project_description\n"
    . "ou: $ldif_project_name\n"
    . "objectclass: top\n"
    . "objectclass: organizationalUnit\n";
}

function get_user_dn($ldif_user_username, $ldif_user_groupname) {
  return "uid=$ldif_user_username,ou=$ldif_user_groupname,dc=ch,dc=geni,dc=net";
}

function get_ldif_for_project_lead($ldif_project_name, $ldif_lead_username, $ldif_lead_groupname) {
  return "\n# LDIF for the project lead\n"
    . "dn: cn=admin,ou=$ldif_project_name,dc=ch,dc=geni,dc=net\n"
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
  
  $ssh_public_keys = lookup_public_ssh_keys($ma_url, $user, $user->account_id);
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
  return $user->username;
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
  } else if (strpos(strtolower($res), strtolower("ERROR 5: User DN not known")) != false) {
    error_log("wimax-enable curl get deleteUser: Error deleting user $ldif_user_username - user not known: $res");
    // Treat as success
    return true;
  } else if (strpos(strtolower($res), strtolower("ERROR 6: User is a admin for")) != false) {
    error_log("wimax-enable curl get deleteUser: Error deleting user $ldif_user_username - user is an admin: $res");
    // FIXME: Return the project they are admin for?
    // You need to make someone else the admin of that project first. Or maybe delete that project
    //    return false;
    return "Internal Error: $res";
  }

  return true;
}

function wimax_delete_group($ldif_project_name) {
  global $wimax_server_delgroup_url;
  // https://www.orbit-lab.org/login/deleteProject?project=<userDN>
  // deletes all members too
  // stops if any members are admins
  $res = my_curl_get(array("project" => get_project_dn($ldif_project_name)), $wimax_server_delgroup_url);
  error_log("Deleting WiMAX group " . $ldif_project_name . " at " . $wimax_server_delgroup_url . ": " . $res);
  // What errors come back?
  if (strpos($res, "404 Not Found")) {
    error_log("wimax-enable curl get deleteGroup: Page $wimax_server_delgroup_url Not Found");
    //    return false;
    return "Internal Error: WiMAX server not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 7: Project DN not known")) != false) {
    error_log("wimax-enable curl get deleteGroup: Error deleting group $ldif_project_name - project not known: $res");
    // Treat as success
    return True;
  } else if (strpos(strtolower($res), strtolower("ERROR 8: Project not deleted because it contains admin")) != false) {
    error_log("wimax-enable curl get deleteGroup: Error deleting group $ldif_project_name - project contains an admin: $res");
    // FIXME: Return that admin DN? Delete that admin DN?
    // Need to move that admin to another group or change the admin for the group they are an admin for, then you can delete them, so only then will the delete group succeed
    //    return false;
    return "Internal Error: $res";
  }
  return true;
}

function wimax_make_group_admin($ldif_project_name, $ldif_user_username, $ldif_user_groupname) {
  global $wimax_server_changeadmin_url;
  // https://www.orbit-lab.org/login/changeLeader?user=<userDN>&project=<projectDN>
  // deletes all members too
  // stops if any members are admins
  $res = my_curl_get(array("user" => get_user_dn($ldif_user_username, $ldif_user_groupname), "project" => get_project_dn($ldif_project_name)), $wimax_server_changeadmin_url);
  error_log("Changing admin for WiMAX group " . $ldif_project_name . " to $ldif_user_username at " . $wimax_server_changeadmin_url . ": " . $res);
  // What errors come back?
  if (strpos($res, "404 Not Found")) {
    error_log("wimax-enable curl get changeLeader: Page $wimax_server_changeadmin_url Not Found");
    //    return false;
    return "Internal Error: WiMAX server not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 5: User DN not known")) != false) {
    error_log("wimax-enable curl get changeLeader: Error changing group $ldif_project_name lead to $ldif_user_username: New Lead not a known user: $res");
    //    return false;
    return "Internal Error: WiMAX User $ldif_user_username not known";
  } else if (strpos(strtolower($res), strtolower("ERROR 7: Project DN not known")) != false) {
    error_log("wimax-enable curl get changeLeader: Error changing group $ldif_project_name lead to $ldif_user_username: Project not known: $res");
    //    return false;
    return "Internal Error: WiMAX group $ldif_project_name not known";
  }
  return true;
}

function wimax_change_group($ldif_project_name, $ldif_user_username, $ldif_user_groupname) {
  global $wimax_server_changegroup_url;
  // https://www.orbit-lab.org/login/changeProject?user=<userDN>&project=<projectDN>
  $res = my_curl_get(array("user" => get_user_dn($ldif_user_username, $ldif_user_groupname), "project" => get_project_dn($ldif_project_name)), $wimax_server_changegroup_url);
  error_log("Changing WiMAX group to " . $ldif_project_name . " for $ldif_user_username at " . $wimax_server_changegroup_url . ": " . $res);
  // What errors come back?
  if (strpos($res, "404 Not Found")) {
    error_log("wimax-enable curl get changeGroup: Page $wimax_server_changegroup_url Not Found");
    //    return false;
    return "Internal Error: WiMAX server not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 5: User DN not known")) != false) {
    error_log("wimax-enable curl get changeGroup: Error changing to group $ldif_project_name for $ldif_user_username: Not a known user: $res");
    //    return false;
    return "Internal Error: WiMAX user $ldif_user_username not found";
  } else if (strpos(strtolower($res), strtolower("ERROR 7: Project DN not known")) != false) {
    error_log("wimax-enable curl get changeGroup: Error changing to group $ldif_project_name fpr $ldif_user_username: Project not known: $res");
    //    return false;
    return "Internal Error: WiMAX group $ldif_project_name not found";
  }
  return true;
}


// Basic data setup stuff
$is_error = False; // Did the requested action result in an error
$result_string = ""; // Message to show user about result

// Get User
$ldif_user_group_id = null;
$user_is_wimax_enabled = False;
$ldif_user_groupname = null;

if(isset($user->ma_member->wimax_username)) {
  $ldif_user_username = $user->ma_member->wimax_username;
} else {
  $ldif_user_username = gen_username_base($user);
}
$ldif_user_pretty_name = $user->prettyName();
$ldif_user_given_name = $user->givenName;
$ldif_user_email = $user->mail;
$ldif_user_sn = $user->sn;

$project_ids = get_projects_for_member($sa_url, $user, $user->account_id, true);
$num_projects = count($project_ids);

if (isset($user->ma_member->enable_wimax)) {
  $ldif_user_group_id = $user->ma_member->enable_wimax;
  $user_is_wimax_enabled = True;
  error_log("User object says $ldif_user_pretty_name is wimax enabled in project " . $ldif_user_group_id);
  // If this user is not in the project, then delete the user
  if(!(check_membership_of_project($project_ids, $ldif_user_group_id))) {
    error_log("wimax-enable: $ldif_user_pretty_name lists WiMAX group $ldif_user_group_id, but the user is not in that project. Delete the WiMAX account");
    $res = wimax_delete_user($ldif_user_username, $ldif_user_groupname);
    if ($res === true) {
      // Change relevant MA attribute, local vars
      remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
      $user->ma_member->enable_wimax = False;
      $user_is_wimax_enabled = False;
      $ldif_user_groupname = null;
      $ldif_user_group_id = null;
    } else {
      // WiMAX refused to let us delete the account so it doesn't match what we have!
      // FIXME
    }
  } else { // users group is one of their projects
    error_log("user is a member of the project which they list as their group");
    $user_group_project_info = lookup_project($sa_url, $user, $ldif_user_group_id);
    $ldif_user_groupname = $user_group_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    error_log("users group is project $ldif_user_groupname");
    if (project_is_expired($user_group_project_info)) {
      error_log("User $ldif_user_username's group's project $ldif_user_groupname is expired - must delete the group!");
      // delete group
      $res = wimax_delete_group($ldif_user_groupname);
      if (true === $res) {
	// mark project as not wimax enabled
	remove_project_attribute($sa_url, $user, $ldif_user_group_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	
	// mark user as not wimax enabled
	remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	$user->ma_member->enable_wimax = False;
	$user_is_wimax_enabled = False;
	$ldif_user_groupname = null;
	$ldif_user_group_id = null;
      } else {
	// Failed to delete WiMAX user whose portal project is expired
	// FIXME FIXME
	// Could go back to the wimax-enable page with an internal error message, using $res?
      }
      
      // Also delete the user
      $res = wimax_delete_user($ldif_user_username, $ldif_user_groupname);
      // Change relevant MA attribute, local vars
      remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
      $user->ma_member->enable_wimax = False;
      $user_is_wimax_enabled = False;
      $ldif_user_groupname = null;
      $ldif_user_group_id = null;
    } else { // project not expired
      error_log("project not expired");
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
	    $ldif_project_name = $ldif_user_groupname;
	    $project_lead_username = "";
	    $project_lead_groupname = "";
	    
	    // get username from member ID
	    // get member attribute
	    $lead = geni_load_user_by_member_id($ldif_project_lead_id);
	    if (! isset($lead->ma_member->enable_wimax)) {
	      error_log("Project $ldif_project_name has lead " . $lead->prettyName() . " who is not yet wimax enabled");
	      // FIXME FIXME
	      // $is_error = True;
	      // $return_string = $lead->prettyName() . " needs a WiMAX account. Then reload this page to make them admin of the WiMAX group for project $ldif_project_name";
	    } else {
	      $project_lead_username = $lead->ma_member->wimax_username;
	      $project_lead_group_id = $lead->ma_member->enable_wimax;
	      $lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	      $project_lead_groupname = $lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	      
	      error_log("Changing admin for wimax group $ldif_project_name to $project_lead_username");
	      
	      $res = wimax_make_group_admin($ldif_project_name, $project_lead_username, $project_lead_groupname);
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
		// $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $ldif_project_name WiMAX group";
	      }
	    }
	    // Done with case where the project's lead is not the admin ID
	  } // end of block to handle project lead is not group admin
	}
	break;
      }  // end of loop over project attributes

      if (! $enabled) {
	error_log("User $ldif_user_username says their group $ldif_user_groupname is a project that says it is not enabled");
	$res = wimax_delete_user($ldif_user_username, $ldif_user_groupname);
	if (true === $res) {
	  // Change relevant MA attribute, local vars
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
	  $ldif_user_groupname = null;
	  $ldif_user_group_id = null;
	} else {
	  // FIXME: WiMAX has the user, we want them deleted.
	  // FIXME FIXME Use $res
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
    error_log("DEBUG: Users group is requested preojct: $ldif_project_id");
  }
  if(!(check_membership_of_project($project_ids, $ldif_project_id))) {
    error_log("User $ldif_user_username specified a project $ldif_project_id that they do not belong to");
    $_SESSION['lasterror'] = 'You are not a member of that project.';
    relative_redirect('wimax-enable.php');
  }
  $project_info = lookup_project($sa_url, $user, $ldif_project_id);

  // Define basic vars for use in constructing LDIF
  $ldif_project_name = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  $ldif_project_description = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];

  $ldif_project_lead_id = $project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
  if($ldif_project_lead_id == $user->account_id) {
    $user_is_project_lead = True;
    error_log("DEBUG: User is lead of requested project $ldif_project_name");
  }

  $project_attributes = lookup_project_attributes($sa_url, $user, $ldif_project_id);
  $project_enabled = FALSE; // Is this project wimax enabled
  $ldif_project_group_admin_id = null;
  foreach($project_attributes as $attribute) {
    if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
      $project_enabled = True;
      error_log("DEBUG: $ldif_project_name is wimax enabled");
      $ldif_project_group_admin_id = $attribute[PA_ATTRIBUTE::VALUE];
      if ($ldif_project_group_admin_id == $user->account_id) {
	$user_is_group_admin = True;
	error_log("DEBUG: User is the wimax group admin");
      }
      break;
    }
  }

  if ($project_enabled) {
    error_log("DEBUG: Project $ldif_project_name is enabled");
  } else {
    error_log("Project $ldif_project_name is NOT WiMAX enabled");
  }
  error_log("DEBUG: user_lead: $user_is_project_lead. user_admin: $user_is_group_admin");

  if ($project_enabled) {
    // If expired then delete group, return error
    if (project_is_expired($project_info)) {
      error_log("Requested project $ldif_project_name is enabled and expired - must delete WiMAX group");
      $res = wimax_delete_group($ldif_project_name);
      if (true === $res) {
	// mark project as not wimax enabled
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	
	// If this is the group for this user, disable this user
	if ($ldif_user_group_id == $ldif_project_id) {
	  // mark user as not wimax enabled
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
	  $ldif_user_groupname = null;
	  $ldif_user_group_id = null;
	}
      } else {
	error_log("Failed to delete WiMAX group for expired project $ldif_project_name: $res");
	// FIXME: And now do what? Use $res
      }
      // Return Error
      $_SESSION['lasterror'] = "Project $ldif_project_name is expired";
      relative_redirect('wimax-enable.php');
    } // end check enabled project is expired

    // If group admin != lead then change wimax group admin and continue, resetting vars as necessary
    if($ldif_project_lead_id !== $ldif_project_group_admin_id) {
      error_log("Project $ldif_project_name has diff lead ($ldif_project_lead_id) than wimax group ($ldif_project_group_admin_id)");
      // Look up the user who is the lead on this project. Get their wimax username, wimax group ID, name of that project
      $project_lead_username = "";
      $project_lead_groupname = "";

      // get username from member ID
      // get member attribute
      $lead = geni_load_user_by_member_id($ldif_project_lead_id);
      if (! isset($lead->ma_member->enable_wimax)) {
	error_log("Project $ldif_project_name has lead " . $lead->prettyName() . " who is not yet wimax enabled");
	// FIXME FIXME
	// $is_error = True;
	// $return_string = $lead->prettyName() . " needs a WiMAX account. Then reload this page to make them admin of the WiMAX group for project $ldif_project_name";
      } else {
	$project_lead_username = $lead->ma_member->wimax_username;
	$project_lead_group_id = $lead->ma_member->enable_wimax;
	$lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	$project_lead_groupname = $lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];

	error_log("Changing admin for wimax group $ldif_project_name to $project_lead_username");
      
	$res = wimax_make_group_admin($ldif_project_name, $project_lead_username, $project_lead_groupname);
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
	  // $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $ldif_project_name WiMAX group";
	}
      }
      // Done with case where the project's lead is not the admin ID
    } // end of block to handle project lead is not group admin
  } else if (project_is_expired($project_info)) {
    // return error
    $_SESSION['lasterror'] = "Project $ldif_project_name is expired - it cannot be WiMAX enabled";
    relative_redirect('wimax-enable.php');
  }

  // Now handle the request

  $enable_user = False;
  $enable_project = False;
  if ($user_is_project_lead) {
    if ($project_enabled) {
      // If the user leads the requested project and it is enabled, then only action is to delete it
      error_log("$ldif_user_username is lead of $ldif_project_name: delete the WiMAX group");
      $res = wimax_delete_group($ldif_project_name);
      if (true === $res) {
	// mark project as not wimax enabled
	remove_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	
	// If this is the group for this user, disable this user
	if ($ldif_user_group_id == $ldif_project_id) {
	  // mark user as not wimax enabled
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
	  $ldif_user_groupname = null;
	  $ldif_user_group_id = null;
	}
	// return success of some kind
	$result_string = "<p>Disabled project $ldif_project_name for WiMAX</p>";
      } else {
	// return error of some kind
	$is_error = True;
	$result_string = "<p><b>Failed</b> to disable project $ldif_project_name for WiMAX: $res</p>"; // FIXME: What should user do?
      }
    } else {
      // Project is not enabled. We want to enable it

      // if you already have a group, then just enable this project
      if ($user_is_wimax_enabled) {
	// FIXME: Enable this project
	error_log("Project $ldif_project_name is not yet WiMAX enabled, but user $ldif_user_username is. Just enable the project");
	$enable_project = True;
      } else {
	// FIXME: enable this group and join it
	error_log("Project $ldif_project_name is not yet WiMAX enabled, not user $ldif_user_username. Enable project and put user in project as group admin");
	$ldif_user_groupname = $ldif_project_name;
	$enable_project = True;
	$enable_user = True;
      }
    }
  } else {
    // user does not lead this project
    if (! $project_enabled) {
      // return error - non project lead cannot enable project
      error_log("wimax-enable: $ldif_user_username picked non enabled project $ldif_project_name?!");
      $_SESSION['lasterror'] = 'Project $ldif_project_name is not WiMAX enabled. The project lead must enable it for WiMAX first.';
      relative_redirect('wimax-enable.php');
    } else {
      // User is member of this project and the project is enabled

      // if this is your group then delete the user
      if ($ldif_user_group_id == $ldif_project_id) {
	error_log("Project $ldif_project_name is enabled and is the users group. So delete user $ldif_user_username");
	// Delete user, fix attributes
	$res = wimax_delete_user($ldif_user_username, $ldif_user_groupname);
	if (true === $res) {
	  // Change relevant MA attribute, local vars
	  remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	  $user->ma_member->enable_wimax = False;
	  $user_is_wimax_enabled = False;
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
	error_log("Project $ldif_project_name is enabled and is not the users group. So enable user $ldif_user_username in that group");
	$enable_user = True;
	$ldif_user_groupname = $ldif_project_name;
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
    
    if (! isset($ldif_project_name)) {
      error_log("No ldif_project_name!");
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
	$ldif_string = get_ldif_for_project($ldif_project_name, $ldif_project_description);
	
	$ldif_string .= "\n" . get_ldif_for_project_lead($ldif_project_name, $ldif_user_username, $ldif_user_groupname);
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
	  break;
	} else {
	  $ldif_user_username = $new_ldif_user_username;
	  error_log(" ... trying new username " . $ldif_user_username);
	  $usernameTaken = True;
	}
      }
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
      $result_string = "<p><b>WiMAX (already) enabled</b></p>\n<p>You already have a WiMAX account for username '$ldif_user_username' in project '$ldif_project_name'.</p>";
      $result_string = $result_string . "<p>Check your email ({$user->mail}) for more information.</p>";
      error_log($user->prettyName() . " already enabled for WiMAX in project " . $ldif_project_name . ". Result was: " . $result);
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
      error_log("Unexpected error changing WiMAX project for " . $user->prettyName() . " to project " . $ldif_project_name . ": " . $result);
    } else if (strpos(strtolower($result), 'success') !== false) {
      // FIXME: Was this user enabled for wimax before? And for that project, is this user the lead?
      // If so, should that wimax project no longer be wimax enabled? Do I need to send new LDIF?
      
      // Remove any existing attribute for enabling wimax - we are changing the project we are enabled for
      remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
      
      // add user as someone using WiMAX for given project
      add_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax', $ldif_project_id, 't');
      
      // If we enabled wimax under a variant of the username, record that
      add_member_attribute($ma_url, $user, $user->account_id, 'wimax_username', $ldif_user_username, 't');
      
      error_log($user->prettyName() . " enabled for WiMAX in project " . $ldif_project_name . " with username " . $ldif_user_username);
      
      // if user is the project lead and the project is not enabled, enable the project for WiMAX
      if($ldif_project_lead_id == $user->account_id and ! $project_enabled) {
	add_project_attribute($sa_url, $user, $ldif_project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $user->account_id);
	error_log($user->prettyName() . " enabled project " . $ldif_project_name . " for WiMAX use");
      }
      
      $result_string = "<p><b>Success</b>: You have enabled and/or requested your account and/or changed your WiMAX project.</p>";
      $result_string = $result_string . "<p>Your WiMAX username is '$ldif_user_username' for project '$ldif_project_name'. Check your email ({$user->mail}) for login information.</p>";

    } else {
  
      $is_error = True;
      $result_string = "<p><b>Error (from $wimax_server_url):</b> $result</p>";
      $result_string = $result_string . "<p>Debug information:</p>";
      $result_string = $result_string . "<blockquote><pre>$ldif_string</pre></blockquote>";
      error_log("Unknown Error enabling WiMAX for " . $user->prettyName() . " in project " . $ldif_project_name . ": " . $result);
      
    }
  } // end of block to only enable user/group if needed
  
  // Now display the page with the result for the user

  show_header('GENI Portal: WiMAX Setup', $TAB_PROFILE);
  include("tool-showmessage.php");

  echo "<h1>WiMAX</h1>";
  // FIXME: Show errors differently somehow?
  if ($is_error) {
    echo "<h2>Error</h2>";
  }
  echo $result_string;
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
      $proj_desc = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
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
       FIXME: Need a get_members_with_attribute function
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
	  $res = wimax_delete_group($proj_name);
	  if (true === $res) {
	    // mark project as not wimax enabled
	    remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	
	    // If this is the group for this user, disable this user
	    if (isset($ldif_user_group_id) and $ldif_user_group_id == $proj_id) {
	      // mark user as not wimax enabled
	      remove_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax');
	      $user->ma_member->enable_wimax = False;
	      $user_is_wimax_enabled = False;
	      $ldif_user_groupname = null;
	      $ldif_user_group_id = null;
	    }
	    // return success of some kind
	    $warnings[] = "<p>Disabled project $ldif_project_name for WiMAX</p>";
	  } else {
	    // return error of some kind
	    //$is_error = True;
	    $warnings[] = "<p><b>Failed</b> to disable project $ldif_project_name for WiMAX: $res</p>"; // FIXME: What should user do?
	  }
	  continue; // don't use this project
	} // end of block to handle project expired

	if ($proj_admin_id != $proj_lead_id) {
	  // change group admin and keep going to next project
	  error_log("Project $proj_name is wimax enabled with group admin that is no longer the project lead - change admin");
	  // get username from member ID
	  // get member attribute
	  $lead = geni_load_user_by_member_id($proj_lead_id);
	  if (! isset($lead->ma_member->enable_wimax)) {
	    error_log("Project $proj_name has lead " . $lead->prettyName() . " who is not yet wimax enabled");
	    // FIXME FIXME
	    // $is_error = True;
	    // $return_string = $lead->prettyName() . " needs a WiMAX account. Then reload this page to make them admin of the WiMAX group for project $ldif_project_name";
	  }
	  $proj["lead_name"] = $lead->prettyName();
	  $project_lead_username = $lead->ma_member->wimax_username;
	  $project_lead_group_id = $lead->ma_member->enable_wimax;
	  $lead_project_info = lookup_project($sa_url, $user, $project_lead_group_id);
	  $project_lead_groupname = $lead_project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	  
	  $res = wimax_make_group_admin($proj_name, $project_lead_username, $project_lead_groupname);
	  if (true === $res) {
	    // Change relevant PA attribute, local vars
	    remove_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX);
	    add_project_attribute($sa_url, $user, $proj_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, $proj_lead_id);
	    $proj_admin_id = $proj_lead_id;
	    $proj["admin_id"] = $proj_lead_id;
	  } else {
	    // Failed to change lead. This might happen if that user has not created their wimax account yet.
	    error_log("Failed to change WiMAX group admin for project $proj_name to $project_lead_username: $res");
	    // FIXME FIXME - Use $res
	    // $is_error = True;
	    // $return_string = "Failed to make " . $lead->prettyName() . " the admin of the $ldif_project_name WiMAX group";
	  }
	} // end of block to handle group has wrong admin

	// get all members with a wimax-enable attribute that lists this project ID
	$members_of_group = ma_lookup_members($ma_url, $user, array("enable_wimax" => $proj_id));
	$members_of_proj = get_project_members($sa_url, $user, $proj_id);
	// for each:
	foreach ($members_of_group as $member) {
	  $member_id = $member->member_id;
	  $found = false;
	  foreach ($members_of_proj as $mp) {
	    if ($member_id == $mp['member_id']) {
	      $found = True;
	      break;
	    }
	  }	  
	  //   if that member_id is not a member of this project, call delete-user and continue to next member
	  if (! $found) {
	    $member_prettyname = $member->prettyName();
	    if (isset($member->wimax_username)) {
	      $member_username = $member->wimax_username;
	    } else {
	      $mu = new GeniUser();
	      $member_username = gen_username_base($mu->ini_from_member($member));
	    }
	    error_log("wimax-enable: Member $member_prettyname lists $proj_name as their group, but they are not a member - delete wimax account");
	    $res = wimax_delete_user($member_username, $proj_name);
	    if (true === $res) {
	      // Change relevant MA attribute
	      remove_member_attribute($ma_url, $user, $member_id, 'enable_wimax');
	    } else {
	      // FIXME: WiMAX has the user, we want them deleted.
	      // FIXME FIXME Use $res
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
        . "'>" . $ldif_user_groupname . "</a> with username '$ldif_user_username'. ";
      if (count($projects_admin) > 0) {
	echo "<p>You are the WiMAX group admin for these projects that you lead: ";
	$cnt = 0;
	foreach ($projects_admin as $p) {
	  if ($cnt > 0) {
	    echo ", ";
	  }
	  $cnt++;
	  echo $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
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
        echo "<tr>";
        echo "<td><a href='project.php?project_id=$proj_id'>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}</a></td>";
        $lead_id = $proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
	$proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
        $lead_name = $lead_names[$lead_id];
        echo "<td>$lead_name</td>";
        echo "<td>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]}</td>";
        echo "<td>";
        $enabled = $proj["enabled"];

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
	  echo "<b>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]} is enabled for WiMAX but is not your WiMAX project</b>";
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
	} else {
	  // Other logical cases can't happen: proj enabled but lead is not wimax enabled,
	  // or project not enbled and lead is and is enabled for this project
	  error_log("huh? Got case in projects I lead that shouldn't happen. enabled=$enabled, ldif_user_group_id=$ldif_user_group_id, proj_id=$proj_id. Proj name: $proj_name");
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
	// FIXME: Put in cases here, with <input> elements
	// Case 1: This is your project
	if (isset($ldif_user_group_id) and $ldif_user_group_id == $proj_id) {
	  echo $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . " is your WiMAX project</b>";
	  if (!$is_group_admin) {
	    //   Actions (debug: radio: Delete your member account)
	    echo "<input type='radio' name='project_id' value='$proj_id'> Delete your WiMAX account";
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

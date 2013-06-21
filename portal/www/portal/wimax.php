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
show_header('GENI Portal: WiMAX Setup', $TAB_PROFILE);
include("tool-showmessage.php");

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

/* PAGE 2 */
/* if user has submited form */
// NOTE: Implicitly, if no sites are selected, user gets bounced
//    back to page they started from since 'site[]' doesn't exist
if (array_key_exists('project', $_REQUEST)
    && array_key_exists('sites', $_REQUEST)
)
{

  echo "<h1>Enable WiMAX Resources (Build LDIF file)</h1>";
  
  // Verification that data is okay
  
  // Step 1: check that user is member of at least one project
  $project_ids = get_projects_for_member($sa_url, $user, $user->account_id, true);
  $num_projects = count($project_ids);
  if (count($project_ids) == 0) {
    $_SESSION['lasterror'] = 'You are not a member of any projects.';
    relative_redirect('wimax.php');
  }
  
  // Step 2: check that user is a member of the project they specify
  if(!(check_membership_of_project($project_ids, $_REQUEST['project']))) {
    $_SESSION['lasterror'] = 'You are not a member of the project that you specified.';
    relative_redirect('wimax.php');
  }
  
  // Step 3: TODO: check that project has WiMAX enabled
  
  // If user hasn't been redirected yet, safe to continue
  
  // get site info (that was sent)
  $sites = $_REQUEST['sites'];
  $sites_attributes = array();
  foreach($sites as $site_id) {
    $sites_attributes[] = get_service_by_id($site_id);
  }
  
  
  // get project info (that was sent)
  $project_info = lookup_project($sa_url, $user, $_REQUEST['project']);
  
  // get information about project lead
  $project_lead_info = ma_lookup_member_by_id($ma_url, $user, $project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]);
  
  // get members' contact information and put into an array
  $project_members = get_project_members($sa_url, $user, $_REQUEST['project']);
    // create array to store members' info in
    $project_members_array = array();
    foreach($project_members as $project_member) {
      $project_member_id = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
      $project_members_array[] = ma_lookup_member_by_id($ma_url, $user, $project_member_id);
    }
  
  // define variables here to be used in LDIF string
  $project_name = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  $project_description = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
  $project_lead_username = $project_lead_info->username;
  $username = $user->username;
  $email = $user->mail;
  $pretty_name = $user->givenName . " " . $user->sn;
  $given_name = $user->givenName;
  $sn = $user->sn;
  
  $ldif_string = "# LDIF for a project\n"
    . "dn: ou=$project_name,dc=ch,dc=geni,dc=net\n"
    . "description: $project_description\n"
    . "ou: $project_name\n"
    . "objectclass: top\n"
    . "objectclass: organizationalUnit\n";
  
  $ldif_string .= "\n# LDIF for the project lead\n"
    . "dn: cn=admin,ou=$project_name,dc=ch,dc=geni,dc=net\n"
    . "cn: admin\n"
    . "objectclass: top\n"
    . "objectclass: organizationalRole\n"
    . "roleoccupant: uid=$project_lead_username,ou=$project_name,dc=ch,dc=geni,dc=net\n";
  
  /*
  $ldif_string .= "\n# LDIF for the project members group\n"
    . "dn: cn=$project_name,ou=$project_name,dc=ch,dc=geni,dc=net\n"
    . "cn: $project_name\n";
    foreach($project_members_usernames as $memberuid) {
      $ldif_string .= "memberuid: $memberuid\n";
    }
  
  $ldif_string .= "\n# LDIF for the project admins group\n"
    . "dn: cn=$project_name" . "-admin,ou=$project_name,dc=ch,dc=geni,dc=net\n"
    . "cn: $project_name" . "-admin\n"
    . "memberuid: $project_lead_username\n"
    . "objectclass: top\n"
    . "objectclass: posixGroup\n";
  */
  
  // add info about each project member
  foreach($project_members_array as $member) {
  
    // header
    $ldif_string .= "\n# LDIF for a user\n"
      . "dn: uid={$member->username},ou=$project_name,dc=ch,dc=geni,dc=net\n"
      . "cn: {$member->first_name} {$member->last_name}\n"
      . "givenname: {$member->first_name}\n"
      . "email: {$member->email_address}\n"
      . "sn: {$member->last_name}\n";
      
    /* 
    // ssh keys (don't remove; to be used later on)
    $ssh_public_keys = lookup_ssh_keys($ma_url, $user, $member->member_id);

    echo "ACCOUNT ID: " . $user->account_id . "\n";

    $number_keys = count($ssh_public_keys);
    if($number_keys > 0) {
      for($i = 0; $i < $number_keys; $i++) {
        // display as one greater than ith entry in array
        // i.e., start with sshpublickey1 stored in position 0, etc.
        $ldif_string .= "sshpublickey" . ($i + 1) . ": " . $ssh_public_keys[$i]['public_key'] . "\n";
      }
    }
    */
    
    // other information
    $ldif_string .= "uid: {$member->username}\n"
      . "o: $project_description\n"
      . "objectclass: top\n"
      . "objectclass: person\n"
      . "objectclass: posixAccount\n"
      . "objectclass: shadowAccount\n"
      . "objectclass: inetOrgPerson\n"
      . "objectclass: organizationalPerson\n"
      . "objectclass: hostObject\n"
      . "objectclass: ldapPublicKey\n";
    
  }
  
    /*
    // grab public keys and add to ldif string as appropriate
    $ssh_public_keys = lookup_ssh_keys($ma_url, $user, $user->account_id);
    $number_keys = count($ssh_public_keys);
    
    if($number_keys == 1) {
      $ldif_string .= "sshpublickey: {$ssh_public_keys[0]['public_key']}\n";
    }
    else {
      for($i = 0; $i < $number_keys; $i++) {
        // display as one greater than ith entry in array
        // i.e., start with sshpublickey1 stored in position 0, etc.
        $ldif_string .= "sshpublickey" . ($i + 1) . ": " . $ssh_public_keys[$i]['public_key'] . "\n";
      }
    }
    
    */
    

  
  
  // display sites chosen
  echo "<p>The WiMAX site(s) chosen: </p>\n";
  echo "<ul>\n";
  foreach($sites_attributes as $site) {
    echo "<li><b>" . $site[SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION] . " (" . $site[SR_TABLE_FIELDNAME::SERVICE_NAME] . ")</b>, sending to the URL " . $site[SR_TABLE_FIELDNAME::SERVICE_URL] . "</li>\n";
  }
  echo "</ul>\n";
  
  // display LDIF (to be changed in the future)
  echo "<p>The LDIF file to be sent is: </p>";
  echo "<blockquote><pre>$ldif_string</pre></blockquote>";

  /* // debug info
  echo "<p><b>The var_dump of user is:</b> </p>";
  var_dump($user);
  
  
  echo "<p><b>The var_dump of ssh public keys is:</b> </p>";
  var_dump($ssh_public_keys);
  
  echo "<p><b>The var_dump of REQUEST is:</b> </p>";
  var_dump($_REQUEST);  
  
  echo "<p><b>The var_dump of project_info is:</b> </p>";
  var_dump($project_info);   
  
  echo "<p><b>The var_dump of project_lead_info is:</b> </p>";
  var_dump($project_lead_info);
  
  echo "<p><b>The var_dump of project_members is:</b> </p>";
  var_dump($project_members);

  echo "<p><b>The var_dump of project_members_usernames is:</b> </p>";
  var_dump($project_members_usernames);
  
  echo "<p><b>The var_dump of sites is:</b> </p>";
  var_dump($sites); */

}

/* PAGE 1 */
/* user needs to select project (initial screen) */
else {

  // TODO: Verify that at least one site exists (otherwise this is pointless)

  $warnings = array();
  $keys = $user->sshKeys();
  $cert = ma_lookup_certificate($ma_url, $user, $user->account_id);
  $project_ids = get_projects_for_member($sa_url, $user, $user->account_id, true);
  $num_projects = count($project_ids);
  if (count($project_ids) > 0) {
    // If there's more than 1 project, we need the project names for
    // a default project chooser.
    $projects = lookup_project_details($sa_url, $user, $project_ids);
  }
  $is_project_lead = $user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null);

  if (is_null($cert)) {
    // warn that no cert has been generated
    $warnings[] = '<p class="warn">No certificate has been generated.'
          . ' You must <a href="kmcert.php?close=1" target="_blank">'
          . 'generate a certificate'
          . '</a>.'
          . '</p>';
  }
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


  echo "<h1>Enable WiMAX Resources</h1>\n";

  foreach ($warnings as $warning) {
    echo $warning;
  }
  
  // if user is member of 1+ projects and has 1+ SSH keys
  if ($num_projects >= 1 && count($keys) >= 1) {
  
    // check if user belongs to any WiMAX-enabled projects that haven't expired
    
      // get project list
      // for each project,
        // check if 'enable_wimax' field is in a project attributes table?
        // if not expired, then add to array
    
    // if yes (array is not empty), display projects
    
    
    // if no, display message "None of your project leads have enabled WiMAX on their projects."
  
  
    // FIXME: change method from GET to POST when done (GET used for debugging)
    echo '<form id="f1" action="wimax.php" method="get">';
    echo "<p>Choose a project: \n";
    echo '<select name="project">\n';
    foreach ($projects as $proj) {
      // show only projects that have not expired
      if(!project_is_expired($proj)) {
        $proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
        $proj_name = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
        echo "<option value=\"$proj_id\" title=\"$proj_name\">$proj_name</option>\n";
      }
    }
    echo '</select>';
    echo "</p>\n";
    
    echo "<p>Sites that have previously been enabled:</p>\n";
    // query member attributes to find sites that have been enabled    
    
    
    //var_dump($user);
    
    // FIXME: Currently, sites are stored in comma-separated list in value field
    //    of key "enable-wimax" in ma_member_attribute. This is a temporary
    //    workaround until a better design can be implemented.
    $sites_enabled = explode(",", $user->ma_member->enable_wimax);
    
    

    /*
    echo "<p>sites enabled: <b>\n";
    var_dump($sites_enabled);
    echo "</b></p>\n";
        
    echo "<p>enable-wimax: <b>\n";
    var_dump($user->ma_member->enable_wimax);
    echo "</b></p>\n";
    */    

    
    echo "<p>Choose a site:</p>\n";
    
    // query service registry to find sites
    $sites = get_services_of_type(SR_SERVICE_TYPE::WIMAX_SITE);
    echo "<blockquote>\n";
    foreach($sites as $site) {
      echo "  <input type=\"checkbox\" name=\"sites[]\" value=\"" . $site[SR_TABLE_FIELDNAME::SERVICE_ID] . "\" /> " . $site[SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION] . " <br/> \n";
    }
    echo "</blockquote>\n";
    
    
    // old info (when info gathered from array)
    /* echo "<select name=\"site\">\n";
    foreach($sites as $site) {
      echo "<option value=\"{$site[site_id]}\" title=\"{$site[site_id]}\"> {$site[site_name]} ({$site[site_location]})</option>\n";
    }
    echo '</select>';
    echo "</p>\n";*/
    
    echo " <button onClick=\"document.getElementById('f1').submit();\">  <b>Generate LDIF file</b></button>\n";
    echo "</form>\n";
    echo "</p>\n";
    
    // There are multiple projects.
  } else {
    // No projects (warnings will have already been displayed)
  }

  

}






/*
echo "<h1>Request WiMAX Resources</h1>";
foreach ($warnings as $warning) {
  echo $warning;
}

echo "<h2>Choose WiMAX resource</h2>";

echo "<h2>Choose project</h2>";

echo "<h2>Data to be sent in LDIF</h2>";

echo ("<ul>
    <li>Name: <b>{$user->ma_member->first_name} {$user->ma_member->last_name}</b></li>
    <li>email: <b>{$user->ma_member->email_address}</b></li>
    <li>username: <b>{$user->username}</b></li>
    <li>project (single)</li>
    <li>project lead</li>
    <li>project email </li>
    </ul>");

echo "<h2>var_dump of user</h2><p>";
var_dump($user);
echo "</p>";
*/

include("footer.php");
?>

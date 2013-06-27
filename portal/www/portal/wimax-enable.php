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
    relative_redirect('wimax-enable.php');
  }
  
  // Step 2: check that user is a member of the project they specify
  if(!(check_membership_of_project($project_ids, $_REQUEST['project']))) {
    $_SESSION['lasterror'] = 'You are not a member of the project that you specified.';
    relative_redirect('wimax-enable.php');
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
  $pretty_name = $user->prettyName();
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
  
  // add info about project lead
  
    // header
    $ldif_string .= "\n# LDIF for user (project lead)\n"
      . "dn: uid=$username,ou=$project_name,dc=ch,dc=geni,dc=net\n"
      . "cn: $pretty_name\n"
      . "givenname: $given_name\n"
      . "email: $email\n"
      . "sn: $sn\n";
      
    // ssh keys
    $ssh_public_keys = lookup_ssh_keys($ma_url, $user, $user->account_id);
    $number_keys = count($ssh_public_keys);
    if($number_keys > 0) {
      for($i = 0; $i < $number_keys; $i++) {
        // display as one greater than ith entry in array
        // i.e., start with sshpublickey1 stored in position 0, etc.
        $ldif_string .= "sshpublickey" . ($i + 1) . ": " . $ssh_public_keys[$i]['public_key'] . "\n";
      }
    }
    
    // other information
    $ldif_string .= "uid: $username\n"
      . "o: $project_description\n"
      . "objectclass: top\n"
      . "objectclass: person\n"
      . "objectclass: posixAccount\n"
      . "objectclass: shadowAccount\n"
      . "objectclass: inetOrgPerson\n"
      . "objectclass: organizationalPerson\n"
      . "objectclass: hostObject\n"
      . "objectclass: ldapPublicKey\n";

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


  echo "<h1>Request WiMAX Resources</h1>\n";

  foreach ($warnings as $warning) {
    echo $warning;
  }
  
  // if user is member of 1+ projects and has 1+ SSH keys
  if ($num_projects >= 1 && count($keys) >= 1) {
  
    // find out if member has chosen WiMAX project and if so, which one
    echo "<h2>Your Status</h2>";
    
    // if chosen one
    
    echo "<p>You have enabled WiMAX on project (project). Your WiMAX-enabled project cannot change.</p>";
    
    // if not chosen one
    echo "<p>You have not enabled WiMAX on any of your projects. Please select a project below.<br><b>Note:</b> Once you select a project, you cannot change it.</p>";
    
  
    // get list of all non-expired projects
    // separate into 1) projects I lead and 2) projects I don't lead
    $projects_lead = array();
    $projects_non_lead = array();
    $projects_lead_count = 0;
    $projects_non_lead_count = 0;
    foreach ($projects as $proj) {
      if(!project_is_expired($proj)) {
        // if user is the project lead
        if($proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] == $user->account_id) {
          $projects_lead[] = $proj;
          $projects_lead_count++;
        }
        // if user is not the project lead
        else {
          $projects_non_lead[] = $proj;
          $projects_non_lead_count++;
        }
      }
    }
    
    // for projects I lead, allow for enabling of WiMAX
    if($projects_lead_count > 0) {
      echo "<h2>Enable WiMAX for Projects You Lead</h2>";
      echo "<p>You can <b>enable WiMAX resources</b> and <b>request WiMAX login information</b> for the following projects that you lead:</p>";
      
      echo "<table>";
      echo "<tr><th>Project Name</th><th>Project Lead</th><th>Purpose</th><th>Enable WiMAX for Project</th></tr>";
      $lead_names = lookup_member_names_for_rows($ma_url, $user, $projects_lead, 
					     PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
      foreach($projects_lead as $proj) {
        echo "<tr>";
        echo "<td><a href='project.php?project_id={$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]}'>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}</a></td>";
        $lead_id = $proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
        $lead_name = $lead_names[$lead_id];
        echo "<td>$lead_name</td>";
        echo "<td>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]}</td>";
        // query if project has been enabled yet
        echo "<td>";
        $project_attributes = lookup_project_attributes($sa_url, $user, 
                $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]);
        // if "enable_wimax" name field is there, then already enabled
        $enabled = 0;
        foreach($project_attributes as $attribute) {
          if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
            $enabled = 1;
          }
        }
        // display different buttons if enabled or not
        if($enabled) {
          echo "Already enabled";
        }
        else {
          echo "<button onClick=\"document.getElementById('f1').submit();\"><b>Enable for this project</b></button>";
        }
        echo "</td>";
        echo "</tr>";
      }
      
      echo "</table>";
      
    }
    
    
    // for projects I don't lead, request login for projects that do have it enabled
    if($projects_non_lead_count > 0) {
      echo "<h2>Request WiMAX Login Information</h2>";
      echo "<p>You can <b>request WiMAX login information</b> for the following projects:</p>";
    
      echo "<table>";
      echo "<tr><th>Project Name</th><th>Project Lead</th><th>Purpose</th><th>Request Login Info</th></tr>";
      $lead_names = lookup_member_names_for_rows($ma_url, $user, $projects_non_lead, 
					     PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
      foreach($projects_non_lead as $proj) {
        echo "<tr>";
        echo "<td><a href='project.php?project_id={$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]}'>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}</a></td>";
        $lead_id = $proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
        $lead_name = $lead_names[$lead_id];
        echo "<td>$lead_name</td>";
        echo "<td>{$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]}</td>";
        // query if project has been enabled yet
        echo "<td>";
        $project_attributes = lookup_project_attributes($sa_url, $user, 
                $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]);
        // if "enable_wimax" name field is there, then already enabled
        $enabled = 0;
        foreach($project_attributes as $attribute) {
          if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
            $enabled = 1;
          }
        }
        // display different buttons if enabled or not
        if($enabled) {
          echo "<button onClick=\"document.getElementById('f1').submit();\"><b>Request WiMAX Login</b></button>";
        }
        else {
          echo "Project not enabled for WiMAX";
        }
        echo "</td>";
        echo "</tr>";
      }
      
      echo "</table>";
    
    }
    
    /*echo "<p>projects:</p>";
    var_dump($projects);
    
    echo "<p>lead:</p>";
    var_dump($projects_lead);
    
    echo "<p>non-lead:</p>";
    var_dump($projects_non_lead);*/
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
    /*
    // check if user belongs to any WiMAX-enabled projects that haven't expired
    
      // get project list
      // for each project,
        // check if 'enable_wimax' field is in a project attributes table?
        // if not expired, then add to array
    
    // if yes (array is not empty), display projects
    
    
    // if no, display message "None of your project leads have enabled WiMAX on their projects."
  
  
    // FIXME: change method from GET to POST when done (GET used for debugging)
    echo '<form id="f1" action="wimax-enable.php" method="get">';
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
    

    
    echo "<p>Choose a site:</p>\n";
    
    // query service registry to find sites
    $sites = get_services_of_type(SR_SERVICE_TYPE::WIMAX_SITE);
    echo "<blockquote>\n";
    foreach($sites as $site) {
      echo "  <input type=\"checkbox\" name=\"sites[]\" value=\"" . $site[SR_TABLE_FIELDNAME::SERVICE_ID] . "\" /> " . $site[SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION] . " <br/> \n";
    }
    echo "</blockquote>\n";
    
    echo " <button onClick=\"document.getElementById('f1').submit();\">  <b>Generate LDIF file</b></button>\n";
    echo "</form>\n";
    echo "</p>\n";
    
    // There are multiple projects.
    */
    
  } else {
    // No projects (warnings will have already been displayed)
  }


}







include("footer.php");
?>

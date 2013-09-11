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
if (array_key_exists('project_id', $_REQUEST))
{

  echo "<h1>WiMAX</h1>";
  
  $project_id = $_REQUEST['project_id'];
  
  // Some verification
  
  // Step 1: check that user is member of at least one project
  $project_ids = get_projects_for_member($sa_url, $user, $user->account_id, true);
  $num_projects = count($project_ids);
  if (count($project_ids) == 0) {
    $_SESSION['lasterror'] = 'You are not a member of any projects.';
    relative_redirect('wimax-enable.php');
  }
  
  // Step 2: check that user has at least 1 SSH key
  $keys = $user->sshKeys();
  if (count($keys) == 0) {
    $_SESSION['lasterror'] = 'You have not uploaded any SSH keys.';
    relative_redirect('wimax-enable.php');
  }
  
  // Step 3: check that user is a member of the project they specify
  if(!(check_membership_of_project($project_ids, $project_id))) {
    $_SESSION['lasterror'] = 'You are not a member of the project that you specified.';
    relative_redirect('wimax-enable.php');
  }
  
  /*
  
    Program logic in brief:
  
    if you are project lead of $_REQUEST['project_id']
      send full LDIF
      
    if you are not project lead of $_REQUEST['project_id']
      verify that project has WiMAX enabled
        if so, send partial LDIF
        else, display error and redirect
  
  */
  
  $project_info = lookup_project($sa_url, $user, $project_id);
  
  // if you're the project lead of the project, enable WiMAX
  if($project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] == $user->account_id) {
    
    // PREPARE FULL LDIF
    
    $ldif_project_name = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $ldif_project_description = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    $ldif_user_username = $user->username;
    $ldif_user_pretty_name = $user->prettyName();
    $ldif_user_given_name = $user->givenName;
    $ldif_user_email = $user->mail;
    $ldif_user_sn = $user->sn;
  
    $ldif_string = "";
    
    $ldif_string .= "# LDIF for a project\n"
      . "dn: ou=$ldif_project_name,dc=ch,dc=geni,dc=net\n"
      . "description: $ldif_project_description\n"
      . "ou: $ldif_project_name\n"
      . "objectclass: top\n"
      . "objectclass: organizationalUnit\n";
    
    $ldif_string .= "\n# LDIF for the project lead\n"
      . "dn: cn=admin,ou=$ldif_project_name,dc=ch,dc=geni,dc=net\n"
      . "cn: admin\n"
      . "objectclass: top\n"
      . "objectclass: organizationalRole\n"
      . "roleoccupant: uid=$ldif_user_username,ou=$ldif_project_name,dc=ch,dc=geni,dc=net\n";
    
    $ldif_string .= "\n# LDIF for user (project lead)\n"
      . "dn: uid=$ldif_user_username,ou=$ldif_project_name,dc=ch,dc=geni,dc=net\n"
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
  
  }
  
  // if you're not the project lead, determine if project is even allowed to request WiMAX resources
  else {
  
    $project_attributes = lookup_project_attributes($sa_url, $user, $project_id);
    $enabled = 0;
    foreach($project_attributes as $attribute) {
      if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
        $enabled = 1;
      }
    }
    
    // WiMAX has been enabled, so good to go
    if($enabled) {
    
      // PREPARE PARTIAL LDIF

      $ldif_project_name = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $ldif_project_description = $project_info[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
      $ldif_user_username = $user->username;
      $ldif_user_pretty_name = $user->prettyName();
      $ldif_user_given_name = $user->givenName;
      $ldif_user_email = $user->mail;
      $ldif_user_sn = $user->sn;
    
      $ldif_string = "";
      
      $ldif_string .= "# LDIF for user (member of project)\n"
        . "dn: uid=$ldif_user_username,ou=$ldif_project_name,dc=ch,dc=geni,dc=net\n"
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
    
    }
    
    // WiMAX hasn't been enabled, so this is an error; redirect
    else {
      $_SESSION['lasterror'] = 'Project specified is not enabled for WiMAX';
      relative_redirect('wimax-enable.php');
    }

  
  }
  
  // SEND LDIF
  // FIXME: hard-coded url for Rutgers ORBIT
  $url = "https://www.orbit-lab.org/userupload/save";

  $postdata = array("ldif" => $ldif_string);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  // FIXME: Change to true or remove line to set to true when CA issue fixed
  // Error message: SSL certificate problem, verify that the CA cert is OK. 
  // Details:\nerror:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  //curl_setopt($ch, CURLOPT_CAPATH, "/etc/ssl/certs");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
  $result = curl_exec($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($error) {
    error_log("put_message error: $error");
  }
  $result = trim($result);
  if (strpos($result, "404 Not Found")) {
    error_log("put_message error: Page $url Not Found");
  }

  // debug
  //echo "<p>The generated LDIF:</p>";
  //echo "<blockquote><pre>$ldif_string</pre></blockquote>";
  //echo "<p>The cURL result was: $result</p>";
  
  // CHECK REPLY FROM SENDER
  
  /*
    Some error messages from their side:
    
      Operation failed - You trying to upload user for organization 
      that does not egist. Missing organization LDIF entry
      
      Operation failed - Username bujcich alerady exist
      
      Operation failed - undefined method `[]' for nil:NilClass
  
  */
  
  // Assume unsuccessful unless reply indicates otherwise
  $success = 0;
  
  /* if response was successful:
        add member_attribute to user
          name: enable_wimax
          value: <project_id>
        if enabling project
          add project_attribute to project
            name: enable_wimax
            value: foo
  */
  if (strpos(strtolower($result), 'success') !== false) {
    // add yourself as someone using WiMAX
    add_member_attribute($ma_url, $user, $user->account_id, 'enable_wimax', $project_id, 't');
    
    // if you're the project lead, enable the project for WiMAX
    if($project_info[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] == $user->account_id) {
      add_project_attribute($sa_url, $user, $project_id, PA_ATTRIBUTE_NAME::ENABLE_WIMAX, 'foo');
    }
  
    echo "<p><b>Success</b>: You have enabled and/or requested your account. Check {$user->mail} for login information.</p>";
    error_log($user->prettyName() . " enabled for WiMAX in project " . $ldif_project_name);
  }
  
  else {
  
    echo "<p><b>Error (from $url):</b> $result</p>";
    echo "<p>Debug information:</p>";
    echo "<blockquote><pre>$ldif_string</pre></blockquote>";
    error_log("Error enabling WiMAX for " . $user->prettyName() . " in project " . $ldif_project_name . ": " . $result);
    
  }
  
  

  
  
  
  


}

/* PAGE 1 */
/* user needs to select project (initial screen) */
else {

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

  echo "<h1>WiMAX</h1>\n";
  foreach ($warnings as $warning) {
    echo $warning;
  }
  
  // if user is member of 1+ projects and has 1+ SSH keys
  if ($num_projects >= 1 && count($keys) >= 1) {
  
    echo "<h2>Your Status</h2>";
    
    // find out if member has chosen WiMAX project and if so, which one
    $already_enabled = 0;
    $my_project = "";
    if(isset($user->ma_member->enable_wimax)) {
      $already_enabled = 1;
      $my_project = $user->ma_member->enable_wimax;
    }
    
    // disable radio buttons on form
    $disabled = "";
    if($already_enabled) {
      $disabled = "disabled";
    }
    
    // if enabled, display info and which project
    if($already_enabled) {
      $project_attributes = lookup_project($sa_url, $user, 
                $user->ma_member->enable_wimax);
      $selected_project_name = $project_attributes[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      echo "<p>You have enabled WiMAX on project " 
        . "<a href='project.php?project_id=" 
        . $user->ma_member->enable_wimax 
        . "'>" . $selected_project_name 
        . "</a>. <b>Your WiMAX-enabled project cannot change.</b></p>";
    }
    // if not, warn user that they can only select one project
    else {
      echo "<p>You have not enabled WiMAX on any of your projects. ";
      echo "Please select a project below.<br>";
      echo "<b>Note:</b> Once you select a project, you cannot change it.</p>";
    }
    
    // get list of all non-expired projects
    //  separate into 
    //    1) projects I lead
    //    2) projects I don't lead that have WiMAX enabled
    //    3) projects I don't lead that don't have WiMAX enabled
    
    $projects_lead = array();
    $projects_non_lead = array();
    $projects_non_lead_disabled = array();
    $projects_lead_count = 0;
    $projects_non_lead_count = 0;
    $projects_non_lead_disabled_count = 0;
    foreach ($projects as $proj) {
      if(!project_is_expired($proj)) {
        // if user is the project lead
        if($proj[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] == $user->account_id) {
          $projects_lead[] = $proj;
          $projects_lead_count++;
        }
        // if user is not the project lead
        else {
        
          // determine if the project has WiMAX enabled
          $project_attributes = lookup_project_attributes($sa_url, $user, 
                $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]);
          $enabled = 0;
          foreach($project_attributes as $attribute) {
            if($attribute[PA_ATTRIBUTE::NAME] == PA_ATTRIBUTE_NAME::ENABLE_WIMAX) {
              $enabled = 1;
            }
          }
          // if WiMAX has been enabled
          if($enabled) {
            $projects_non_lead[] = $proj;
            $projects_non_lead_count++;
          }
          // otherwise, WiMAX hasn't been enabled
          else {
            $projects_non_lead_disabled[] = $proj;
            $projects_non_lead_disabled_count++;
          }

        }
      }
    }
    
    // start a form
    echo '<form id="f1" action="wimax-enable.php" method="get">';
    
    // for projects I lead, allow for enabling of WiMAX
    if($projects_lead_count > 0) {
      echo "<h2>Enable WiMAX for Projects You Lead</h2>";
      echo "<p><i>Note that enabling a project for WiMAX means that all of the project's members can request WiMAX resources for that project, and the project lead is ultimately responsible for the actions of members.</i></p><p>You can <b>enable WiMAX resources</b> and <b>request WiMAX login information</b> for the following projects that you lead:</p>";
      
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
          echo "<b>Enabled on project {$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}</b>";
        }
        else {
          echo "<input type='radio' name='project_id' value='" . $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]
             . "' $disabled> Enable for project {$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}";
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
        // determine which project has been requested
        echo "<td>";
        if($my_project == $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]) {
          echo "<b>Requested on project {$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}</b>";
        }
        else {
          echo "<input type='radio' name='project_id' value='" . $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID]
             . "' $disabled> Request WiMAX login for project {$proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]}";
        }
        echo "</td>";
        echo "</tr>";
      }
      
      echo "</table>";
    
    }
    
    // only show button for these two cases
    if(($projects_lead_count > 0) || ($projects_non_lead_count > 0)) {
      echo "<p><button $disabled onClick=\"document.getElementById('f1').submit();\">Submit</button></p>";
    }
    
    // end form
    echo "</form>";
    
    // for projects I don't lead and don't have it enabled, list them
    if($projects_non_lead_disabled_count > 0) {
      echo "<h2>Projects Not Enabled</h2>";
      echo "<p>You are a member of the following projects that do not have WiMAX enabled. Please contact your project lead if you would like to use WiMAX on any one of these projects:</p>";
    
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
    
    
    }
    

    
  } 
  
  else {
    // No projects (warnings will have already been displayed)
  }


}


include("footer.php");
?>

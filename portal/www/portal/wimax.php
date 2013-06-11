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
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: WiMAX Setup', $TAB_PROFILE);
include("tool-showmessage.php");

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);


/* function project_is expired
    Checks to see whether project has expired
    Returns false if not expired, true if expired
 */
function project_is_expired($proj) {
  return convert_boolean($proj[PA_PROJECT_TABLE_FIELDNAME::EXPIRED]);
}

/* check if user is submitting information or hasn't started */
if (array_key_exists('id', $_REQUEST)
        && array_key_exists('name', $_REQUEST)
        && array_key_exists('description', $_REQUEST))
{

  echo "<h1>Request WiMAX Resources (Build LDIF file)</h1>";
  
  // define variables here to be used in ldif string
  $project_name = "bensproject1";
  $project_description = "Ben's Test Project 1";
  $username = $user->username;
  $email = $user->mail;
  $pretty_name = $user->givenName . " " . $user->sn;
  $given_name = $user->givenName;
  $sn = $user->sn;
  
  $ldif_string = "# LDIF for a project\n";
    $ldif_string .= "dn: ou=$project_name,dc=ch,dc=geni,dc=net\n";
    $ldif_string .= "description: $project_description\n";
    $ldif_string .= "ou: $project_name\n";
    $ldif_string .= "objectclass: top\n";
    $ldif_string .= "objectclass: organizationalUnit\n";
  
  $ldif_string .= "\n# LDIF for the project lead\n";
    $ldif_string .= "dn: cn=admin,ou=$project_name,dc=ch,dc=geni,dc=net\n";
    $ldif_string .= "cn: admin\n";
    $ldif_string .= "objectclass: top\n";
    $ldif_string .= "objectclass: organizationalRole\n";
    $ldif_string .= "roleoccupant: FIXME\n";
  
  $ldif_string .= "\n# LDIF for the project members group\n";
    $ldif_string .= "dn: cn=$project_name,ou=$project_name,dc=ch,dc=geni,dc=net\n";
    $ldif_string .= "cn: $project_name\n";
    $ldif_string .= "# FIXME: Need memberuid entries?\n";
  
  $ldif_string .= "\n# LDIF for the project admins group\n";
  
  $ldif_string .= "\n# LDIF for the user\n";
    $ldif_string .= "dn: uid=$username,ou=$project_name,dc=ch,dc=geni,dc=net\n";
    $ldif_string .= "cn: $pretty_name\n";
    $ldif_string .= "givenname: $given_name\n";
    $ldif_string .= "email: $email\n";
    $ldif_string .= "sn: $sn\n";
    
    // grab public keys and add to ldif string as appropriate
    $ssh_public_keys = lookup_ssh_keys($ma_url, $user, $user->account_id);
    $number_keys = count($ssh_public_keys);
    
    if($number_keys == 1) {
      $ldif_string .= "sshpublickey: {$ssh_public_keys[0][public_key]}\n";
    }
    else {
      for($i = 0; $i < $number_keys; $i++) {
        // display as one greater than ith entry in array
        // i.e., start with sshpublickey1 stored in position 0, etc.
        $ldif_string .= "sshpublickey" . ($i + 1) . ": " . $ssh_public_keys[$i][public_key] . "\n";
      }
    }
    
    $ldif_string .= "uid: $username\n";
    $ldif_string .= "o: $project_description\n";
    $ldif_string .= "objectclass: top\n";
    $ldif_string .= "objectclass: person\n";
    $ldif_string .= "objectclass: posixAccount\n";
    $ldif_string .= "objectclass: shadowAccount\n";
    $ldif_string .= "objectclass: inetOrgPerson\n";
    $ldif_string .= "objectclass: organizationalPerson\n";
    $ldif_string .= "objectclass: hostObject\n";
    $ldif_string .= "objectclass: ldapPublicKey\n";

  
  
  
  
  echo "<p>The LDIF file to be sent is: </p>";
  echo "<blockquote><pre>$ldif_string</pre></blockquote>";

  echo "<p>The var_dump of user is: </p>";
  var_dump($user);
  
  
  echo "<p>The var_dump of ssh public keys is: </p>";
  var_dump($ssh_public_keys);

}
/* user needs to select project (initial screen) */
else {

  echo "<h1>Request WiMAX Resources</h1>";


}







/* copied from omni-bundle.php
$warnings = array();
$keys = $user->sshKeys();
$cert = ma_lookup_certificate($ma_url, $user, $user->account_id);
$project_ids = get_projects_for_member($pa_url, $user, $user->account_id, true);
$num_projects = count($project_ids);
if (count($project_ids) > 0) {
  // If there's more than 1 project, we need the project names for
  // a default project chooser.
  $projects = lookup_project_details($pa_url, $user, $project_ids);
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
        . ' No default project can be chosen unless you';
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
end copy of omni-bundle.php */





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

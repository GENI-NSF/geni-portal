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
?>
<?php
require_once("settings.php");
require_once("user.php");
require_once("am_client.php");
require_once("ma_client.php");
require_once("header.php");

$user = geni_loadUser();
if (!isset($user)) {
  relative_redirect('home.php');
}

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);


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

if (count($keys) == 0) {
  // warn that no ssh keys are present.
  $warnings[] = '<p class="warn">No ssh keys have been uploaded.'
        . 'Please <button onClick="window.location=\'uploadsshkey.php\'">'
         . 'Upload an SSH key</button> or <button'
         . ' onClick="window.location=\'generatesshkey.php\'">Generate and'
         . ' Download an SSH keypair</button> to enable logon to nodes.'
        . '</p>';
}
if (is_null($cert)) {
  // warn that no cert has been generated
  $warnings[] = '<p class="warn">No certificate has been generated.'
        . ' Please <a href="kmcert.php?close=1" target="_blank">'
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


/**
 * START OF OUTPUT IS HERE
 */
show_header('GENI Portal: omni bundle', $TAB_PROFILE);
include("tool-breadcrumbs.php");
echo '<h1>Download omni bundle</h1>';
foreach ($warnings as $warning) {
  echo $warning;
}
?>

Instructions:
<ol>
<?php
if ($num_projects > 1) {
?>
<li>Choose a project below as your default omni project.</li>
<?php
}
?>
<li>Click "Download omni bundle"</li>
<li>Run "omni_configure.py &lt;location of bundle&gt;"</li>
</ol>

<form id="f1" action="downloadomnibundle.php" method="post">

<?php
if ($num_projects > 1) {
  echo 'Choose project as omni default:';
  echo '<select name="project">\n';
  foreach ($projects as $proj) {
    $proj_id = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $proj_name = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $proj_desc = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    echo "<option value=\"$proj_name\" title=\"$proj_desc\">$proj_name</option>\n";
  }
  echo '</select>';
  // There are multiple projects. Put up a chooser for the default project.
} else if ($num_projects == 1) {
  // Put the default project in a hidden variable
  $proj = $projects[$project_ids[0]];
  $proj_name = $proj[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  echo "<input type=\"hidden\" name=\"project\" value=\"$proj_name\"/>\n";
} else {
  // No projects, so no default project
}
?>
</form>
<button onClick="document.getElementById('f1').submit();">
  <b>Download omni bundle</b>
</button>
<button onClick="history.back(-1)">Cancel</button>
<br/><br/>

<?php
include("footer.php");
?>

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
require_once('util.php');
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('logging_client.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Projects', $TAB_PROJECTS);

$project_id = "None";
$project = null;
$project_name = "None";
$email = "";
$purpose = "";
$leademail = "";
$leadname = "";

$result = "";
if (array_key_exists("result", $_GET)) {
  $result = $_GET['result'];
  if (! is_null($result) && $result != '') {
    $result = " (" . $result . ")";
  }
}

include("tool-lookupids.php");
include("tool-breadcrumbs.php");

if (! is_null($project) && $project != "None") {
  $email = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL];
  $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
  $leadid = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
  if (uuid_is_valid($leadid)) {
    $lead = geni_loadUser($leadid);
    $leademail = $lead->email();
    $leadname = $lead->prettyName();
  } else {
    error_log("project.php: Invalid lead id from DB for project $project_name");
  }
}

// *** This code should fill in the "Project members" table below, 
// *** once we know how to link with identity_attribute
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$members = get_members($cs_url, CS_CONTEXT_TYPE::PROJECT, $project_id);
//error_log("members = " . print_r($members, true));

print "<h1>GENI Project: " . $project_name . "$result</h1>\n";
$edit_url = 'edit-project.php?project_id='.$project_id;
print "<table><tr>\n";
print "<td><button onClick=\"window.location='$edit_url'\"><b>Edit Project</b></button></td>\n";
print "<td><button onClick=\"window.location='disable-project.php?project_id=$project_id'\"><b>Disable Project</b></button></td>\n";
/* Only show create slice link if user has appropriate privilege. */
if ($user->privSlice()) {
  if (isset($project_id)) {
    print "<td><button onClick=\"window.location='";
    print relative_url("createslice?project_id=$project_id'");
    print "\"><b>Create a new slice</b></button></td>\n";
  }
}
print "</tr></table>\n";

print "<h2>Project Details</h2>\n";
print "<table border=\"1\">\n";
print "<tr><td><b>Name</b></td><td>$project_name</td></tr>\n";
print "<tr><td><b>Lead</b></td><td><a href=\"project-member.php?project_id=$project_id&member_id=$leadid\">$leadname</a></td></tr>\n";
print "<tr><td><b>Project purpose</b></td><td>$purpose</td></tr>\n";
print "<tr><td><b>Project email</b></td><td><a href=\"mailto:$email\">$email</a></td></tr>\n";
print "</table>\n";
print "<br/>\n";
print "&nbsp;<a href=\"mailto:$leademail\">Contact the project leader</a><br/>\n";
?>
<h2>Project slices:</h2>
<?php
include("tool-slices.php");
?>
<br/>
<h2>Project members</h2>
<table border="1">
<tr><th>Project Member</th><th>Roles</th></tr>
<?php

  foreach($members as $member) {
     $member_id = $member['principal'];
     $member_user = geni_loadUser($member_id);
     $member_name = $member_user->prettyName();
     $member_role = $member['name'];
     //     error_log("ACC = " . $member_id . " ROLE = " . $member_role);
   print "<tr><td><a href=\"project-member.php?project_id=" . $project_id . "&member_id=$member_id\">$member_name</a></td><td>$member_role</td></tr>\n";
  }
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
//   print "<tr><td><a href=\"project-member.php?project_id=" . $project_id . "&member_id=$leadid\">$leadname</a></td><td>Lead</td></tr>\n";
?>
</table>

<?php
if ($user->privAdmin()) {
  print "Approve/invite new project members<br/>\n";
}
?>

<h2>Recent Project Actions</h2>
<table border="1">
<tr><th>Time</th><th>Message</th>
<?php
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$entries = get_log_entries_for_context($log_url, CS_CONTEXT_TYPE::PROJECT, $project_id);
if (is_array($entries)) {
  foreach($entries as $entry) {
    $message = $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
    $time = $entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME];
    //    error_log("ENTRY = " . print_r($entry, true));
    print "<tr><td>$time</td><td>$message</td></tr>\n";
  }
}
?>
</table>
<br/><br/>


<?php
include("footer.php");
?>

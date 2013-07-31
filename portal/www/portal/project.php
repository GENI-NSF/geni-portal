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
require_once('portal.php');
require_once('util.php');
require_once('pa_constants.php');
require_once('sa_constants.php');
require_once('pa_client.php');
require_once('rq_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('logging_client.php');

// String to disable button or other active element
$disabled = "disabled = " . '"' . "disabled" . '"'; 

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}

// For comparing member records by role (low roles come before high roles)
function compare_members_by_role($mem1, $mem2)
{
  $role1 = $mem1[PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
  $role2 = $mem2[PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
  if ($role1 < $role2)
    return -1;
  else if ($role1 > $role2) 
    return 1;
  else return 0;
  
}

$project_id = "None";
$project = null;
$project_name = "None";
$email = "";
$creation = "";
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
if (! is_null($project) && $project != "None") {
  $email = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL];
  $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
  $creation_db = $project[PA_PROJECT_TABLE_FIELDNAME::CREATION];
  $creation = dateUIFormat($creation_db);
  $expiration_db = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
  if ($expiration_db) {
    $expiration = dateUIFormat($expiration_db);
  } else {
    $expiration = "<i>None</i>";
  }
  $leadid = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
  if (uuid_is_valid($leadid)) {
    $lead = $user->fetchMember($leadid);
    $leademail = $lead->email();
    $leadname = $lead->prettyName();
  } else {
    error_log("project.php: Invalid project lead id from DB for project $project_name");
  }
} else {
  $_SESSION['lasterror'] = "No project specified for project page";
  relative_redirect('projects.php');
}

// Fill in members of project member table
$members = get_project_members($sa_url, $user, $project_id);
$member_names = lookup_member_names_for_rows($ma_url, $user, $members, 
					     MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID);
//error_log("members = " . print_r($members, true));
$num_members = count($members);

$reqs = null;

if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  $reqs = get_pending_requests_for_user($sa_url, $user, $user->account_id, 
					CS_CONTEXT_TYPE::PROJECT, $project_id);
}

$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$entries = get_log_entries_for_context($log_url, Portal::getInstance(),
				       CS_CONTEXT_TYPE::PROJECT, $project_id);

show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-breadcrumbs.php");
include("tool-showmessage.php");
print "<h1>GENI Project: " . "<i>" . $project_name . "</i>" . "$result</h1>\n";
$edit_url = 'edit-project.php?project_id='.$project_id;
$edit_project_members_url = 'edit-project-member.php?project_id='.$project_id;

print "<table>\n";
print "<tr><th>Project Actions</th></tr>\n";
print "<tr>\n";
/* Edit Project */
/* Only show create slice link if user has appropriate privilege. */
if (isset($project_id)) {
  print "<td>";
  $putBut = False;
  if ($user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
    $putBut = True;
    /* Create a new slice*/
    print "<button onClick=\"window.location='";
    print relative_url("createslice?project_id=$project_id'");
    print "\"><b>Create Slice</b></button>";
  }
  if ($user->isAllowed(PA_ACTION::UPDATE_PROJECT, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
    $putBut = True;
    print "<button onClick=\"window.location='$edit_url'\"><b>Edit</b></button>";
  }
  if (! $putBut) {
    /* Put in an empty table cell if no slice privilege. */
    print "<i>None: no privileges.</i>";
  }
  print "</td>\n";
} else {
	/* Put in an empty table cell if no project. */
	print "<td><i>None: no project.</i></td>";
}
print "</tr></table>\n";

if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  if (isset($reqs) && ! is_null($reqs) && count($reqs) >= 1) {
    print "<h3>Approve new project members</h3>\n";
    print "<table>\n";
    print "<tr><th>Requestor</th><th>Request Created</th><th>Handle</th></tr>\n";
    foreach ($reqs as $request) {
      $requestor = $user->fetchMember($request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR]);
      $created_db = $request[RQ_REQUEST_TABLE_FIELDNAME::CREATION_TIMESTAMP];
      $created = dateUIFormat($created_db);
      $handle_button = "<button style=\"\" onClick=\"window.location='handle-project-request.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . "'\"><b>Handle Request</b></button>";
      print "<tr><td>" . $requestor->prettyName() . "</td><td>$created</td><td>$handle_button</td></tr>\n";
    }
    print "</table><br/>\n";
  }
}

print "<table>\n";
print "<tr><th colspan='2'>Project Identifiers (public)</th></tr>\n";
print "<tr><td class='label'><b>Name</b></td><td>$project_name</td></tr>\n";
print "<tr><td class='label'><b>Purpose</b></td><td>$purpose ";
print "\n";
print "</td></tr>\n";
print "<tr><td class='label'><b>Expiration</b></td><td>$expiration</td></tr>\n";
print "<tr><td class='label'><b>Creation</b></td><td>$creation</td></tr>\n";
print "<tr><th colspan='2'>Contact Information</th></tr>\n";
print "<tr><td class='label'><b>Project e-mail</b></td><td><a href=\"mailto:$email\">$email</a></td></tr>\n";
print "<tr><td class='label'><b>Project Lead</b></td><td><a href=\"project-member.php?project_id=$project_id&member_id=$leadid\">$leadname</a> <a href=\"mailto:$leademail\">e-mail</a></td></tr>\n";
print "</table>\n";

// FIXME: If user is not a member of the project, don't show the tool-slices stuff - it will get
// a permission error on lookup_slices

?>
<h2>Project slices:</h2>
<?php
include("tool-slices.php");
include("tool-expired-slices.php");
?>

<h2>Project members</h2>

<?php

if ($num_members==1) {
   print "<p><i>There is <b>1</b> member in this project.</i></p>";
} else {
  print "<p><i>There are <b>".$num_members."</b> members in this project.</i></p>";
}
?>
<table>
<tr><th>Project Member</th><th>Role</th>
<?php

print "</tr>\n";

usort($members, 'compare_members_by_role');

// Find current users role in this project
$my_role = CS_ATTRIBUTE_TYPE::AUDITOR;
  foreach($members as $member) {
     $member_id = $member['member_id'];
     if ($user->account_id == $member_id) {
       $my_role = $member['role'];
       break;
     }
  }

// Write each row in the project member table
  foreach($members as $member) {
     $member_id = $member['member_id'];
     $member_name = $member_names[$member_id];
     // FIXME: It'd be nice to add email address here - but we're
     //     currently not looking that up, for efficiency
     //     $member_email = $member_user->email();
     $member_role_index = $member['role'];
     $member_role = $CS_ATTRIBUTE_TYPE_NAME[$member_role_index];
     //     error_log("ACC = " . $member_id . " ROLE = " . $member_role);
     print "<tr><td><a href=\"project-member.php?project_id="
     . $project_id
       . "&member_id=$member_id\">$member_name</a></td><td>$member_role</td>";

     print "</tr>\n";
  }
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
//   print "<tr><td><a href=\"project-member.php?project_id=" . $project_id . "&member_id=$leadid\">$leadname</a></td><td>Project Lead</td></tr>\n";
?>
</table>

<?php

$edit_members_disabled = "";
if (!$user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  $edit_members_disabled = $disabled;
}
echo "<p><button $edit_members_disabled onClick=\"window.location='$edit_project_members_url'\"><b>Edit Current Project Membership</b></button></p>";


if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  $upload_project_members_url = "upload-project-members.php?project_id=".$project_id;
  print "<h3>Add new project members</h3>";
  print "<p><button onClick=\"window.location='$upload_project_members_url'\"><b>Bulk Add New Members</b></button>";

  //  print "<br/><h3>Invite new project members</h3>\n";
  print " <button onClick=\"window.location='";
  print relative_url("invite-to-project.php?project_id=$project_id'");
  print "\"><b>Invite New Members</b></button></p>\n";
  
  if (! isset($reqs) || is_null($reqs) || count($reqs) < 1) {
    print "<div class='announce'><p>No outstanding project join requests.</p></div>\n";
  }
}
?>

<h2>Recent Project Actions</h2>
<table>
<tr><th>Time</th><th>Message</th><th>Member</th>
<?php

if (is_array($entries)) {
  usort($entries, 'compare_log_entries');
  $entry_member_names = lookup_member_names_for_rows($ma_url, $user, $entries, 
					      LOGGING_TABLE_FIELDNAME::USER_ID);
  foreach($entries as $entry) {
    $message = $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
    $time = dateUIFormat($entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME]);
    $member_id = $entry[LOGGING_TABLE_FIELDNAME::USER_ID];
    $member_name = $entry_member_names[$member_id];
    //    error_log("ENTRY = " . print_r($entry, true));
    print "<tr><td>$time</td><td>$message</td><td><a href=\"project-member.php?project_id=" . $project_id . "&member_id=$member_id\">$member_name</a></td></tr>\n";
  }
}
?>
</table>

<?php
$disable_add_note = "";
if ($in_lockdown_mode) $disable_add_note = $disabled;
$addnote_url = 'add-project-note.php?project_id='.$project_id;
print "<p><button $disable_add_note onClick=\"window.location='$addnote_url'\"><b>Add Note</b></button></p>\n";
include("footer.php");
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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
require_once('logging_constants.php');
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

function compare_last_names($mem1,$mem2)
{
  $parts1 = explode(" ",$mem1);
  $name1 = array_pop($parts1);
  $parts2 = explode(" ",$mem2);
  $name2 = array_pop($parts2);
  return strcmp($name1,$name2);
}

$project_id = "None";
$project = null;
$project_name = "None";
$creation = "";
$purpose = "";
$leademail = "";
$leadname = "";
$expired = False;

$result = "";
if (array_key_exists("result", $_GET)) {
  $result = $_GET['result'];
  if (! is_null($result) && $result != '') {
    $result = " (" . $result . ")";
  }
}

include("tool-lookupids.php");
if (! is_null($project) && $project != "None") {
  $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
  $creation_db = $project[PA_PROJECT_TABLE_FIELDNAME::CREATION];
  $creation = dateUIFormat($creation_db);
  $expiration_db = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
  $project_urn = $project['project_urn'];
  $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
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
  relative_redirect('dashboard.php#projects');
}

// Fill in members of project member table
$members = get_project_members($sa_url, $user, $project_id, null, $project_urn);

/*------------------------------------------------------------
 * Does this user have privileges on this project?
 *
 * If not, redirect to home page.
 *------------------------------------------------------------
 */
$user_is_project_member = false;
foreach ($members as $m) {
  if ($user->account_id == $m[MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID]) {
    $user_is_project_member = true;
    break;
  }
}
if (! $user_is_project_member) {
  $_SESSION['lasterror'] = ('User has no privileges to view project '
                              . $project_name);
  relative_redirect('home.php');
}

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

$actdisabled = '';
if ($expired === True) {
  $actdisabled = $disabled;
}

show_header('GENI Portal: Projects', $TAB_PROJECTS, true, true);

?>
<script src='cards.js'></script>

<div class='nav2'>
  <ul class='tabs'>
    <li><a class='tab' data-tabindex=1 href='#slices'>Slices</a></li>
    <li><a class='tab' data-tabindex=2 href='#members'>Members</a></li>
    <li><a class='tab' data-tabindex=3 href='#info'>Info</a></li>
    <li><a class='tab' data-tabindex=4 href='#logs'>Logs</a></li>
  </ul>
</div>

<?php
include("tool-showmessage.php");

print "<div class='card' id='projectactionsbar' style=' padding: 10px 20px;'>";
print "<h3 style='display:inline;'>Project: $project_name $result</h3>\n";
if ($expired === True) {
  print "<p class='warn'>This project is expired!</p>\n";
}
$edit_url = 'edit-project.php?project_id='.$project_id;
$edit_project_members_url = 'edit-project-member.php?project_id='.$project_id;

if (isset($project_id)) {
  if ($user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
    print "<button onClick=\"window.location='";
    print relative_url("createslice.php?project_id=$project_id'");
    print "\"$actdisabled style='margin: 0px 15px;'><b>Create Slice</b></button>";
  }
  if ($user->isAllowed(PA_ACTION::UPDATE_PROJECT, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
    print "<button onClick=\"window.location='$edit_url'\" style='margin-right: 15px;'><b>Edit Project</b></button>";
  }
  print "</td>\n";
} 

if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  if (isset($reqs) && ! is_null($reqs) && count($reqs) >= 1) {
    $num_reqs = count($reqs);
    $req_word = $num_reqs == 1 ? "request" : "requests";
    print "<h6 style='display: inline;'>$num_reqs new project join $req_word</h3>\n";
    $handle_button = "<button style=\"margin: 0px 15px;\" onClick=\"window.location='handle-project-request.php?project_id=" . $project_id . "'\"$actdisabled><b>Handle $req_word</b></button>";
    print "$handle_button";
    // print "<div class='tablecontainer'><table>\n";
    // print "<tr><th>Requestor</th><th>Request Created</th><th>Handle</th></tr>\n";
    // foreach ($reqs as $request) {
    //   $requestor = $user->fetchMember($request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR]);
    //   $created_db = $request[RQ_REQUEST_TABLE_FIELDNAME::CREATION_TIMESTAMP];
    //   $created = dateUIFormat($created_db);
    //   $handle_button = "<button style=\"\" onClick=\"window.location='handle-project-request.php?request_id=" . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . "'\"$actdisabled><b>Handle Request</b></button>";
    //   print "<tr><td>" . $requestor->prettyName() . "</td><td>$created</td><td>$handle_button</td></tr>\n";
    // }
    // print "</table></div>\n";
  }
}
print "</div>";

print "<div class='card' id='info'>";
print "<div class='tablecontainer'><table>";
print "<tr><th colspan='2'>Project Info</th></tr>\n";
print "<tr><td class='label'><b>Name</b></td><td>$project_name</td></tr>\n";
$purpose = $purpose == "" ? "<i>No project purpose</i>" : $purpose;
print "<tr><td class='label'><b>Purpose</b></td><td>$purpose ";
print "\n";
print "</td></tr>\n";
print "<tr><td class='label'><b>Expiration</b></td><td>$expiration</td></tr>\n";
print "<tr><td class='label'><b>Creation</b></td><td>$creation</td></tr>\n";
print "<tr><td class='label'><b>URN</b></td><td>$project_urn</td></tr>\n";
print "<tr><td class='label'><b>Project Lead</b></td><td><a href=\"project-member.php?project_id=$project_id&member_id=$leadid\">$leadname</a> <a href=\"mailto:$leademail\">e-mail</a></td></tr>\n";
print "</table></div>\n";
print "</div>";

// FIXME: If user is not a member of the project, don't show the tool-slices stuff - it will get
// a permission error on lookup_slices

?>
<div class='card' id='slices'>
<h2>Project Slices:</h2>
<?php
include("tool-slices.php");
include("tool-expired-slices.php");
?>
</div>

<div class='card' id='members'>
<h2>Project Members</h2>

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
// Sort alphabetically by role

$member_lists = array();
$member_lists[1] = array();
$member_lists[2] = array();
$member_lists[3] = array();
$member_lists[4] = array();

  foreach($members as $member) {
     $member_id = $member['member_id'];
     $member_name = $member_names[$member_id];
     $member_ids[$member_name] = $member_id;
     // FIXME: It'd be nice to add email address here - but we're
     //     currently not looking that up, for efficiency
     //     $member_email = $member_user->email();
     $member_role_index = $member['role'];
     $member_lists[$member_role_index][] = $member_name;
  }

foreach ($member_lists as $member_role_index => $member_names) {
  usort($member_names, 'compare_last_names');
  foreach ($member_names as $member_name) {
    $member_role = $CS_ATTRIBUTE_TYPE_NAME[$member_role_index];
    $member_id = $member_ids[$member_name];
    //     error_log("ACC = " . $member_id . " ROLE = " . $member_role);
    print "<tr><td><a href=\"project-member.php?project_id="
      . $project_id
      . "&member_id=$member_id\">$member_name</a></td><td>$member_role</td>";
    
    print "</tr>\n";
  }
}
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
//   print "<tr><td><a href=\"project-member.php?project_id=" . $project_id . "&member_id=$leadid\">$leadname</a></td><td>Project Lead</td></tr>\n";
?>
</table>

<?php

$edit_members_disabled = "";
if (!$user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id) || $expired) {
  $edit_members_disabled = $disabled;
}
echo "<p><button $edit_members_disabled onClick=\"window.location='$edit_project_members_url'\"><b>Edit Current Project Membership</b></button></p>";


if ($user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  $upload_project_members_url = "upload-project-members.php?project_id=".$project_id;
  print "<h3>Add New Project Members</h3>";
  print "<p><button onClick=\"window.location='$upload_project_members_url'\"$actdisabled><b>Bulk Add New Members</b></button>";

  //  print "<br/><h3>Invite new project members</h3>\n";
  print " <button onClick=\"window.location='";
  print relative_url("invite-to-project.php?project_id=$project_id'");
  print "\"$actdisabled><b>Invite New Members</b></button></p>\n";
  
  if (! isset($reqs) || is_null($reqs) || count($reqs) < 1) {
    print "<div class='announce'><p>No outstanding project join requests.</p></div>\n";
  }
}
?>
</div>



<div class='card' id='logs'>
<h2>Recent Project Actions</h2>
<p>Showing logs for the last 
<select onchange="getLogs(this.value);">
  <option value="24">day</option>
  <option value="48">2 days</option>
  <option value="72">3 days</option>
  <option value="168">week</option>
</select>
</p>
<script type="text/javascript">
  $(document).ready(function(){ getLogs(24); });
  function getLogs(hours){
    $.get("do-get-logs.php?hours="+hours+"&project_id="+<?php echo "\"" . $project_id . "\""; ?>, function(data) {
      $('#log_table').html(data);
    });
  }
</script>
<div class='tablecontainer'>
	<table id="log_table"></table>
</div>
</div>
<?php
include("footer.php");
?>

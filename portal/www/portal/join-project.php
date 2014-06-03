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
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once("pa_client.php");
require_once("cs_constants.php");

function project_name_compare($p1, $p2)
{
  $pn1 = $p1[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]; 
  $pn2 = $p2[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]; 
  if ($pn1 == $pn2) {
    return 0;
  } else {
    return ($pn1 < $pn2) ? -1 : 1;
  }
}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");

$mpids = get_projects_for_member($sa_url, $user, $user->account_id, false);

// Filter out projects for which this user has not already requested to join (nothing pending)
$rs = get_requests_by_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null, RQ_REQUEST_STATUS::PENDING);
$rpids = array();
foreach ($rs as $request) {
  $rpids[] = $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID];
}

$pids = array_diff($mpids, $rpids);
show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-breadcrumbs.php");

// Join a project
// Get list of all projects you are not in or already requested to join
// Produce a table of projects you could join
// project name, project lead, project description, Button to Join

print "<h1>Join a Project</h1>\n";

print "<p>All GENI actions must be taken in the context of a
  project. On this page, you can request to join a project.</p>";

print "<p><b>You should only request to join a project if the project
 lead knows you, as the  project lead is taking responsibility for
 your actions. Abuse of this functionality may result in revocation
 of your GENI account.</b></p>";

print "<p>Once the project lead makes a decision about your request you
 will be notified through email. Once you are a member of a project,
 you can create a slice, or request to join an existing slice.";

// FIXME: Replace these 2 calls with 1 call that gets the project details the first time

if (! isset($pids) || is_null($pids) || count($pids) < 1) {
  print "<p><i>There are no more projects for you to join.</i></p>\n";
  if (count($rpids) > 0) {
    print "<p>You have " . count($rpids) . " open <a href='projects.php'>request(s) to join a project</a>.</p>";
  }

} else {

  print "<h2>Select a project to join</h2>\n";
  print "<p><i>Please do not try to join arbitrary projects. Abuse of
   this functionality may result in revocation of your GENI account.
   </i></p>";
   
  /* datatables.net (for sortable/searchable tables) */
  echo '<script type="text/javascript">';
  echo '$(document).ready( function () {';
  echo '  $(\'#projects\').DataTable({paging: false});';
  echo '} );';
  echo '</script>';
   
  print "<table id=\"projects\" class=\"display\">\n";
  print "<thead>\n";
  print "<tr><th>Project</th><th>Purpose</th><th>Project Lead</th><th>Join</th></tr>\n";
  $jointhis_url = "join-this-project.php?project_id=";
  $project_details = lookup_project_details($sa_url, $user, $pids);
  usort($project_details, "project_name_compare");
  //  error_log("PROJ_DETAILS = " . print_r($project_details, true));

  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $member_names = lookup_member_names_for_rows($ma_url, $user, 
					       $project_details, 
					       PA_PROJECT_TABLE_FIELDNAME::LEAD_ID);
  //  error_log("MEMBER_DETAILS = " . print_r($member_names, true));

  print "</thead><tbody>\n";

  foreach ($project_details as $project) {
    //    $project = lookup_project($sa_url, $user, $project_id);
    $expired = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRED];
    if (convert_boolean($expired)) continue;
    print "<tr><td>";
    print $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    print "</td><td>";
    print $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    print "</td><td>";
    $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    $leadname = $member_names[$lead_id];
    print $leadname;
    $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    print "</td><td><button onClick=\"window.location='" . $jointhis_url . $project_id . "'\"><b>Join</b></button></td></tr>\n";
  }
  print "</tbody>\n";
  print "</table>\n";
}

print "<p>If you didn't see a project in which you want to work, you can: \n";
// If the user can create a project, show the Create Project Button
if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
  print "<button onClick=\"window.location='edit-project.php'\"><b>Create a New Project</b></button>\n";
} else {
// Else, Show button to invite someone to create you a project
  print "<button onClick=\"window.location='ask-for-project.php'\"><b>Ask For a New Project</b></button>\n";
}
print "</p>\n";
print "<p><input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/></p>\n";

include("footer.php");
?>

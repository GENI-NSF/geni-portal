<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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
show_header('GENI Portal: Projects');

include("tool-breadcrumbs.php");

// Load this page's javascript
echo '<script src="join-project.js"></script>';

// Join a project
// Get list of all projects you are not in or already requested to join
// Produce a table of projects you could join
// project name, project lead, project description, Button to Join
?>
<h1>Join a Project</h1>

<p>
You must belong to a GENI Project in order to create or join slices
and run experiments.  On this page, you can request to join a project.
</p>

<p><b>
You should request to join a project only if the project lead knows you,
as he or she is taking responsibility for your actions. Attempts to join
projects whose leads you do not know may result in the revocation of your
GENI account.
</b></p>

<p>
After the project lead makes a decision about your request, you will be
notified by email. Once you are a member of a project, you can create or
request to join a slice.
</p>

<?php
// FIXME: Replace these 2 calls with 1 call that gets the project details the first time

if (! isset($pids) || is_null($pids) || count($pids) < 1) {
  print "<p><i>There are no more projects for you to join.</i></p>\n";
  if (count($rpids) > 0) {
    print "<p>You have " . count($rpids) . " open <a href='dashboard.php#projects'>request(s) to join a project</a>.</p>";
  }

} else {

?>

<section id="findform">
<form>
Enter a project name: <input id="findname" type="text"/>
<button id="findbtn" type="submit">Join</button>
<div id="finderror">&nbsp;</div>
</section>
</form>

<?php
  print "<h2>GENI Projects</h2>\n";
  print "<table id=\"projects\" class=\"display\">\n";
  print "<thead>\n";
  print "<tr><th>Project Purpose</th><th>Project Lead</th></tr>\n";
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
    $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    if (! $purpose) continue;
    $lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    $leadname = $member_names[$lead_id];
    print "<tr><td>$purpose</td><td>$leadname</td></tr>\n";
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

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
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once('rq_client.php');
require_once("sa_client.php");
require_once("cs_client.php");
require_once('tool-projects.php');

if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}
if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}

$projects = get_projects_for_member($pa_url, $user->account_id, true);
// error_log("PROJECTS = " . print_r($projects, true));
$num_projects = count($projects);



print "<h2>My Projects</h2>\n";
if ($user->isAllowed('create_project', CS_CONTEXT_TYPE::RESOURCE, null)) {
  if ($num_projects==0) {
    print "<p class='instruction'>You are not a member of any projects.  You need to create or join a project.</p>";
  }
  print "<button onClick=\"window.location='edit-project.php'\"><b>Create New Project</b></button>\n";
  print "<button onClick=\"window.location='join-project.php'\"><b>Join a Project</b></button><br/>\n";
  print "<br/>\n";
} else {
  if ($num_projects==0) {
    print "<p class='instruction'>You are not a member of any projects. Please join an existing project.</p>";
  }
  print "<button onClick=\"window.location='join-project.php'\"><b>Join a Project</b></button><br/>\n";
  print "<button onClick=\"window.location='ask-for-project.php'\"><b>Ask Someone to Create a Project</b></button><br/>\n";
}

// Show outstanding project requests for this user
$reqs = get_pending_requests_for_user($pa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null);
if (isset($reqs) && count($reqs) > 0) {
  print "Found " . count($reqs) . " outstanding project join requests for you:<br/>\n";
  print "<table>\n";
  print "<tr><th>Project Name</th><th>Project Lead</th><th>Request Created</th><th>Requestor</th><th>Handle Request</th></tr>\n";
  foreach ($reqs as $request) {
    // Print it out
    $project = lookup_project($pa_url, $request['context_id']);
    $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    $reason = $request['request_text'];
    $req_date = $request['creation_timestamp'];
    $lead = geni_loadUser($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]);
    $lead_name = $lead->prettyName();
    $requestor = geni_loadUser($request[RQ_ARGUMENTS::REQUESTOR]);
    $requestor_name = $requestor->prettyName();
    $handle_url="handle-project-request.php?request_id=" . $request['id']; // ***
    $handle_button = "<button style=\"\" onClick=\"window.location='" . $handle_url . "'\"><b>Handle Request</b></button>";
    print "<tr><td><a href=\"project.php?$project_id\">$project_name</a></td><td>$lead_name</td><td>$req_date</td><td>$requestor_name</td><td>$handle_button</td></tr>\n";
  }
  print "</table>\n";
  print "<br/><br/>\n";
} else {
  print "<i>No outstanding project join requests.</i><br/><br/>\n";
}

if (count($projects) > 0) {
  print "Found " . count($projects) . " project(s) for you:<br/>\n";
  print "\n<table>\n";
  print ("<tr><th>Name</th><th>Project Lead</th><th>Project E-mail</th><th>Purpose</th><th>Slice Count</th><th>Create Slice</th></tr>\n");

  // name, lead_id, email, purpose
  foreach ($projects as $project_id) {
    if (! uuid_is_valid($project_id)) {
      error_log("tool-projects got invalid project_id from all get_projects_by_lead");
      continue;
    }
    $project = lookup_project($pa_url, $project_id);
    //    error_log("project = " . print_r($project, true));

    $handle_req_str = "";
    if (true || $user->isAllowed('add_project_member', CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $reqcnt = get_number_of_pending_requests_for_user($pa_url, $user, $user->account_id, 
							CS_CONTEXT_TYPE::PROJECT, $project_id);
      //      error_log("REQCNT " . print_r($reqcnt, true) . " " . $project_id);
      $handle_req_str = "(<b>$reqcnt</b> Open Join Request(s)) ";
      if ($reqcnt == 0) {
	$handle_req_str = "";
      }
    }

    //    error_log("Before load user " . time());
    $lead = geni_loadUser($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]);
    //    error_log("After load user " . time());
    $slice_ids = lookup_slice_ids($sa_url, $user, $project_id);
    //<button style="width:65;height:65" onClick="window.location='http://www.javascriptkit.com'"><b>Home</b></button>
    // http://www.javascriptkit.com/howto/button.shtml
    $create_slice_link = "<button style=\"\" onClick=\"window.location='" . "createslice.php?project_id=$project_id" . "'\"><b>Create Slice</b></button>";
    if(!$user->isAllowed('create_slice', CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $create_slice_link = "";
    }
    print ("<tr><td> <a href=\"project.php?project_id=$project_id\">" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . 
	   "</a> $handle_req_str</td><td> <a href=\"project-member.php?project_id=$project_id&member_id=" .
	   $lead->account_id . "\">" . $lead->prettyName() . "</a> </td><td> " .
	   "<a href=\"mailto:" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . 
	   "\">" . 
	   $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . 
	   "</a> </td><td> " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE] . 
	   " </td><td align=\"center\"> " . count($slice_ids) . " </td><td> " .
	   $create_slice_link . "</td></tr>\n");
    // FIXME: Button to invite people to the project?
  }
  print "</table>\n";
} else {
  print "<i> No projects.</i><br/>\n";
}
print "<br/>\n";


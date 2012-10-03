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

$projects = get_projects_for_member($pa_url, $user, $user->account_id, true);
// error_log("PROJECTS = " . print_r($projects, true));
$num_projects = count($projects);

print "<h2>My Projects</h2>\n";
if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
  if ($num_projects==0) {
    print "<p class='instruction'>";
    print "Congratulations! Your GENI Portal account is now active.<br/><br/>";
    print "You have been made a 'Project Lead', meaning you can create GENI Projects, 
    as well as create slices in projects and reserve resources.<br/><br/>";
    print "A project is a group of people and their research, led by a
    single responsible individual - the project lead. See the <a href=\"glossary.html\">Glossary</a>.</p>\n";
    print "<p class='warn'>";
    print "You are not a member of any projects.  You need to Create or Join a Project.";
    print "</p>";
  }
  print "<button onClick=\"window.location='edit-project.php'\"><b>Create New Project</b></button>\n";
  print "<button onClick=\"window.location='join-project.php'\"><b>Join a Project</b></button><br/>\n";
  print "<br/>\n";
} else {
  if ($num_projects==0) {
    print "<p class='instruction'>Congratulations! Your GENI Portal account is now active.<br/><br/>";
    print "Note that your account is not a 'Project Lead' account, 
     meaning you must join a project created by someone else, 
     before you can create slices or use GENI resources.<br/><br/>";
    print "A project is a group of people and their research, led by a
    single responsible individual - the project lead. See the <a href=\"glossary.html\">Glossary</a>.</p>\n";
    print "<p class='warn'>";
    print "You are not a member of any projects. Please Join an existing Project.</p>";
  }
  print "<button onClick=\"window.location='join-project.php'\"><b>Join a Project</b></button><br/>\n";
  print "<button onClick=\"window.location='ask-for-project.php'\"><b>Ask Someone to Create a Project</b></button><br/>\n";
  print "<button onClick=\"window.location='modify.php'\"><b>Ask to be a Project Lead</b></button><br/>\n";
}

// Show outstanding project requests for this user
$reqs = get_pending_requests_for_user($pa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, null);
if (isset($reqs) && count($reqs) > 0) {
  print "Found " . count($reqs) . " outstanding project join requests for you:<br/>\n";
  print "<table>\n";
  print "<tr><th>Project Name</th><th>Project Lead</th><th>Request Created</th><th>Requestor</th><th>Handle Request</th></tr>\n";
  foreach ($reqs as $request) {
    // Print it out
    $project = lookup_project($pa_url, $user, $request['context_id']);
    $project_id = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $purpose = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
    $reason = $request['request_text'];
    $req_date = $request['creation_timestamp'];
    $lead = $user->fetchMember($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]);
    $lead_name = $lead->prettyName();
    $requestor = $user->fetchMember($request[RQ_ARGUMENTS::REQUESTOR]);
    $requestor_name = $requestor->prettyName();
    $handle_url="handle-project-request.php?request_id=" . $request['id']; // ***
    $handle_button = "<button style=\"\" onClick=\"window.location='" . $handle_url . "'\"><b>Handle Request</b></button>";
    print "<tr><td><a href=\"project.php?$project_id\">$project_name</a></td><td>$lead_name</td><td>$req_date</td><td>$requestor_name</td><td>$handle_button</td></tr>\n";
  }
  print "</table>\n";
  print "<br/><br/>\n";
} else {
  print "<div class='announce'>No outstanding project join requests.</div><br/><br/>\n";
}

if (count($projects) > 0) {
  print "\n<table>\n";
  print ("<tr><th>Project Name</th><th>Project Lead</th><th>Purpose</th><th>Slice Count</th><th>Create Slice</th></tr>\n");

  // name, lead_id, purpose
  foreach ($projects as $project_id) {
    if (! uuid_is_valid($project_id)) {
      error_log("tool-projects got invalid project_id from all get_projects_by_lead");
      continue;
    }
    $project = lookup_project($pa_url, $user, $project_id);
    //    error_log("project = " . print_r($project, true));

    $handle_req_str = "";
    if (true || $user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $reqcnt = get_number_of_pending_requests_for_user($pa_url, $user, $user->account_id, 
							CS_CONTEXT_TYPE::PROJECT, $project_id);
      //      error_log("REQCNT " . print_r($reqcnt, true) . " " . $project_id);
      $handle_req_str = "(<b>$reqcnt</b> Open Join Request(s)) ";
      if ($reqcnt == 0) {
	$handle_req_str = "";
      }
    }

    //    error_log("Before load user " . time());
    $lead = $user->fetchMember($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]);
    //    error_log("After load user " . time());
    $slice_ids = lookup_slice_ids($sa_url, $user, $project_id);
    //<button style="width:65;height:65" onClick="window.location='http://www.javascriptkit.com'"><b>Home</b></button>
    // http://www.javascriptkit.com/howto/button.shtml
    $create_slice_link = "<button style=\"\" onClick=\"window.location='" . "createslice.php?project_id=$project_id" . "'\"><b>Create Slice</b></button>";
    if(!$user->isAllowed(SA_ACTION::CREATE_SLICE, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $create_slice_link = "";
    }
    print ("<tr><td> <a href=\"project.php?project_id=$project_id\">" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . 
	   "</a> $handle_req_str</td><td> <a href=\"project-member.php?project_id=$project_id&member_id=" .
	   $lead->account_id . "\">" . $lead->prettyName() . "</a> </td> " .
	   "<td> " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE] . 
	   " </td><td align=\"center\"> " . count($slice_ids) . " </td><td> " .
	   $create_slice_link . "</td></tr>\n");
    // FIXME: Button to invite people to the project?
  }
  print "</table>\n";
} else {
  print "<i> No projects.</i><br/>\n";
}
print "<br/>\n";


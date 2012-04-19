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
require_once("sa_client.php");

if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}
if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}

$project_ids = get_projects_by_lead($pa_url, $user->account_id);

if (count($project_ids) > 0) {
  print "Found " . count($project_ids) . " project(s) for you:<br/>\n";
  print "\n<table border=\"1\">\n";
  print ("<tr><th>Name</th><th>Lead</th><th>Email</th><th>Purpose</th><th>Slice Count</th><th>Create Slice</th></tr>\n");

  // name, lead_id, email, purpose
  foreach ($project_ids as $project_id) {
    if (! uuid_is_valid($project_id)) {
      error_log("tool-projects got invalid project_id from all get_projects_by_lead");
      continue;
    }
    $project = lookup_project($pa_url, $project_id);
    //    error_log("Before load user " . time());
    $lead = geni_loadUser($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]);
    //    error_log("After load user " . time());
    $slice_ids = lookup_slices($sa_url, $project_id);
    //<button style="width:65;height:65" onClick="window.location='http://www.javascriptkit.com'"><b>Home</b></button>
    // http://www.javascriptkit.com/howto/button.shtml
    $create_slice_link = "<button style=\"\" onClick=\"window.location='" . "createslice.php?project_id=$project_id" . "'\"><b>Create Slice</b></button>";
    if(!$user->isAllowed('create_slice', CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      $create_slice_link = "";
    }
    print ("<tr><td> <a href=\"project.php?project_id=$project_id\">" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . 
	   "</a> </td><td> <a href=\"project-member.php?project_id=$project_id&member_id=" .
	   $lead->account_id . "\">" . $lead->prettyName() . "</a> </td><td> " .
	   "<a href=\"mailto:" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . 
	   "\">" . 
	   $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . 
	   "</a> </td><td> " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE] . 
	   " </td><td align=\"center\"> " . count($slice_ids) . " </td><td> " .
	   $create_slice_link . "</td></tr>\n");
  }
  print "</table>\n";
} else {
  print "<i> No projects.</i><br/>\n";
}
print "<br/>\n";

if ($user->isAllowed('create_project', CS_CONTEXT_TYPE::RESOURCE, null)) {
  print "<button onClick=\"window.location='edit-project.php'\"><b>Create New Project</b></button>\n";
}

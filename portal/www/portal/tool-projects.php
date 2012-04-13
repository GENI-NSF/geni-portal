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
print "Got " . count($project_ids) . " projects for you<br/>\n";
if (count($project_ids) > 0) {
  print "\n<table border=\"1\">\n";
  print ("<tr><th>Name</th><th>Lead</th><th>Email</th><th>Purpose</th><th>Slice Count</th><th>Create Slice</th></tr>\n");

  // name, lead_id, email, purpose
  foreach ($project_ids as $project_id) {
    $project = lookup_project($pa_url, $project_id);
    $lead = geni_loadUser($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]);
    $slice_ids = lookup_slices($sa_url, $project_id);
    print ("<tr><td> <a href=\"project.php?id=$project_id\">" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . 
	   "</a> </td><td> <a href=\"project-member.php?id=$project_id&member=" .
	   $lead->account_id . "\">" . $lead->prettyName() . "</a> </td><td> " .
	   "<a href=\"mailto:" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . 
	   "\">" . 
	   $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . 
	   "</a> </td><td> " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE] . 
	   " </td><td> " . count($slice_ids) . " </td><td> " .
	   "<a href=\"createslice.php?project_id=$project_id\">Create Slice</a></td></tr>\n");
  }
  print "</table>\n";
} else {
  print "<i> No projects.</i><br/>\n";
}
print "<br/>\n";
if (true) {
  print "<a href=\"edit-project.php\">Create New Project</a><br/>\n";
}

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
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}
require_once("pa_client.php");
show_header('GENI Portal: Projects', $TAB_PROJECTS);
$user = geni_loadUser();
$project = "new";
$isnew = true;
if (array_key_exists("id", $_GET)) {
  // FIXME: Use filters to validate input
  $project_id = $_GET['id'];
  $isnew = false;
  $project = lookup_project($pa_url, $project_id);
  print "<h1>EDIT GENI Project: " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . "</h1>\n";
} else {
  $project_id = "new";
  print "<h1>NEW GENI Project</h1>\n";
}
?>
<form method="POST" action="do-edit-project.php">
<?php
  if (! $isnew) {
    print "<input type=\"hidden\" name=\"id\" value=\"$project_id\"/>\n";
  }
$fields = array(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL, PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE);
$field_labels = array("Name", "Email", "Purpose");
$ind = -1;
foreach ($fields as $field) {
  $ind = $ind + 1;
  print "<b>" . $field_labels[$ind] . "</b>: <input type=\"text\" name=\"$field\" ";
  if (! $isnew) {
    print "value=\"" . $project[$field] . "\"";
  }
  print "/><br/>\n";
}
print "<br/>\n";

// FIXME: Is project email user settable? A PA generated alias for the project lead's email? Just the project lead's email?

print "<h2>Project Policy Defaults</h2>\n";
print "FIXME: Per project policy defaults go here.<br/>\n";
print "Slice Membership policy: Project members get <b>User</b> rights on all project slices.<br/><br/>\n";

if ($isnew) {
  print "Provide a comma-separate list of email addresses of people to invite to your project:<br/>\n";
  print "<input type=\"textarea\" name=\"invites\"/>\n";
} else {
  print "<h3>Project members</h3>\n";
  print "<table border=\"1\">\n";
  // FIXME: loop over members retrieved from the DB
  // FIXME each of these is editable, an action, etc
  print "<tr><th>Project Member</th><th>Roles</th><th>Permissions</th><th>Delete?</th><th>Send Message</th></tr>\n";
  print "<tr><td><a href=\"project-member.php?id=$project_id&member=joe\">Joe</a></td><td>Lead</td><td>All</td><td><a href=\"do-delete-project-member.php?id=$project_id&member=joe\">Delete</a></td><td><mailto=\"\">Email Joe</a></td></tr>\n";
  print "<tr><td><a href=\"project-member.php?id=$project_id&member=sam\">Sam</a></td><td>Member</td><td>Write</td><td><a href=\"do-delete-project-member.php?id=$project_id&member=sam\">Delete</a></td><td><mailto=\"\">Email Sam</a></td></tr>\n";
  print "</table>\n";
}
print "<br/>\n";
print "<b>Project Leader</b><br/>\n";
print "There is exactly one project leader for each project. Project leaders are ultimately responsible for all activity in all slices in their project, and may be contacted by GENI operations in the event of a problem.<br/><br/>\n";
if ($isnew) {
  print "You will be the leader of your new project.<br/>\n";
  print "<input type=\"hidden\" name=\"newlead\" value=\"" . $user->account_id . "\"/>\n";
} else {
  print "Project leader is: <b>Joe</b><br/>\n";
  print "To transfer project leaders, enter email of proposed new project leader to ask them to take over:<br/>\n";
  print "<input type=\"text\" name=\"newlead\"/><br/>\n";
}
print "<input type=\"submit\" value=\"";
if ($isnew) {
  print "Register\"/>\n";
} else {
  print "Edit\"/>\n";
}
print "</form>\n";

include("footer.php");
?>

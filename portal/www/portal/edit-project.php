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
require_once("pa_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");
show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-breadcrumbs.php");
if (! isset($project)) {
  $project = "new";
  $isnew = true;
  print "<h1>NEW GENI Project</h1>\n";
} else {
  $isnew = false;
  $leadid = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
  if (! uuid_is_valid($leadid)) {
    error_log("edit-project: invalid leadid from DB for project $project_id");
    exit();
  }
  $lead = $user->fetchMember($leadid);
  $leadname = $lead->prettyName();
  $leademail = $lead->email();
  print "<h1>EDIT GENI Project: " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . "</h1>\n";
}
?>
<form method="POST" action="do-edit-project.php">
<table>
<?php
  if (! $isnew) {
    print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";
  }
$fields = array(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL, PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE);
$field_labels = array("Project Name", "Email", "Purpose");
$ind = -1;
foreach ($fields as $field) {
  $ind = $ind + 1;
  if ($isnew && $field_labels[$ind] == "Email") {
    continue;
  }
  print "<tr><td><b>" . $field_labels[$ind] . "</b></td><td><input type=\"text\" name=\"$field\" ";
  if (! $isnew) {
    print "value=\"" . $project[$field] . "\"";
  }
  if ($field_labels[$ind] == "Email") {
    print "disabled=\"disabled\"";
  }
  print "/>";
  if ($isnew && $field_labels[$ind] == "Project Name") {
    print " - Required";
  }
  print "</td></tr>\n";
}
print "</table>\n";
print "<b>Note: Project name is public</b><br/>\n";
print "<br/>\n";

/* print "<h2>Project Policy Defaults</h2>\n"; */
/* print "FIXME: Per project policy defaults go here.<br/>\n"; */
/* print "Slice Membership policy: Project members get <b>User</b> rights on all project slices.<br/><br/>\n"; */

if ($isnew) {
  // FIXME: Either drop this or refactor invite-to-project.php
  /* print "<p style=\"color: grey\">\n"; */
  /* print "Provide a comma-separated list of email addresses of people to invite to your project:<br/>\n"; */
  /* print "<input type=\"textarea\" name=\"invites\" disabled=\"disabled\"/>\n"; */
  /* print "</p>\n"; */
} else {
  print "<h3>Project members</h3>\n";
  print "<table>\n";
  // FIXME: loop over members retrieved from the DB
  // FIXME each of these is editable, an action, etc
  print "<tr><th>Project Member</th><th>Roles</th><th>Permissions</th><th>Delete?</th><th>Send Message</th></tr>\n";
  print "<tr><td><a href=\"project-member.php?project_id=$project_id&member_id=" . $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] . "\">$leadname</a></td><td>Project Lead</td><td>All</td><td><button onClick=\"window.location='do-delete-project-member.php?project_id=$project_id&member_id=$leadid'\"><b>Delete</b></button></td><td><a href=\"mailto:$leademail\">Email $leadname</a></td></tr>\n";
  print "</table>\n";

  print "<button onClick=\"window.location='";
  $inv_url= relative_url("invite-to-project.php?project_id=$project_id'");
  print $inv_url;
  print "\"><a href='" . $inv_url . "'><b>Invite New Project Members</b></a></button><br/>\n";
}
print "<br/>\n";

print "<b>Project Lead</b><br/>\n";
print "There is exactly one project lead for each project. Project leads are ultimately responsible for all activity in all slices in their project, and may be contacted by GENI operations in the event of a problem.<br/><br/>\n";
if ($isnew) {
  print "You will be the project lead on your new project.<br/>\n";
  print "<input type=\"hidden\" name=\"newlead\" value=\"" . $user->account_id . "\"/>\n";
} else {
  print "Project lead is: <b>$leadname</b><br/>\n";
  print "<p style=\"color: grey\">\n";
  print "To transfer project leads, enter email of proposed new project leads to ask them to take over:<br/>\n";
  print "<input type=\"text\" name=\"newlead\" disabled=\"disabled\"/></p><br/>\n";
}
print "<input type=\"submit\" value=\"";
if ($isnew) {
  print "Register\"/>\n";
} else {
  print "Edit\"/>\n";
}
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print "</form>\n";

include("footer.php");
?>

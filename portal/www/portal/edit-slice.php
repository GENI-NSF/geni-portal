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
require_once('util.php');
require_once("sr_client.php");
require_once("sr_constants.php");
require_once('pa_constants.php');
require_once('pa_client.php');
require_once("sa_client.php");
require_once("sa_constants.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive() || ! $user->privSlice()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Slices', $TAB_SLICES);
$isnew = false;
$slice_id = "None";
$slice_name = "None";
unset($slice);
include("tool-lookupids.php");
if (isset($slice)) {
  /* $pretty_result = print_r($slice, true); */
  /* error_log("fetch_slice result: $pretty_result\n"); */
  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  //  error_log("slice_name result: $slice_name\n");
  $slice_expiration = $slice[SA_ARGUMENT::EXPIRATION];
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
  //  error_log("slice_urn result: $slice_urn\n");
  $slice_email = "not.yet.supported@example.com";
  $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
  $owner = geni_loadUser($slice_owner_id);
  $slice_owner_name = $owner->prettyName();
  $owner_email = $owner->email();

  //  error_log("slice_project_id result: $slice_project_id\n");
  /* error_log("project result: $project\n"); */
  $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  //  error_log("slice_project_name result: $slice_project_name\n");
  $proj_url = 'project.php?project_id='.$slice_project_id;
  $slice_own_url = 'slice-member.php?member_id='.$slice_owner_id . "&slice_id=" . $slice_id;
} else {
  error_log("No slice to edit");
  relative_redirect("home.php");
}

print "<h1>EDIT GENI Slice: " . $slice_name ."</h1>\n";
print "<table border=\"1\">\n";
print "<form method=\"POST\" action=\"do-edit-slice.php?slice_id=$slice_id\">\n";
print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
// print "<tr><th>Name</th><th>Value</th></tr>\n";
print "<tr><td><b>Slice Name <a href='#warn'>*</a> </b></td><td>$slice_name</td></tr>\n";
print "<tr><td><b>Member of Project<a href='#warn'>*</a> </b></td><td><a href=$proj_url>$slice_project_name</a></td></tr>\n";
print "<tr><td><b>Slice URN</b></td><td>$slice_urn</td></tr>\n";
print "<tr><td><b>Slice UUID</b></td><td>$slice_id</td></tr>\n";
print "<tr><td><b>Slice e-mail</b></td><td><a href='mailto:$slice_email'>e-mail</a></td></tr>\n";
print "<tr><td><b>Slice Owner</b></td><td><a href=$slice_own_url>$slice_owner_name</a> <a href='mailto:$owner_email'>e-mail</a></td></tr>\n";
print "<tr><td><b>Slice Expiration</b></td><td>$slice_expiration</td></tr>\n";
print "</table>\n";
print "<b id='warn'>* Warning: Slice and project names are public</b><br/>\n";

print "<h2>Slice Policy Defaults</h2>\n";
print "FIXME: Per slice policy defaults go here.<br/>\n";
print "Slice Membership policy: Slice members get <b>User</b> rights on all project slices.<br/><br/>\n";

if ($isnew) {
  print "Provide a comma-separated list of email addresses of people to invite to your slice:<br/>\n";
  print "<input type=\"textarea\" name=\"invites\" disabled=\"disabled\"/>\n";
} else {
  print "<h2>Slice members</h3>\n";
  print "<table border=\"1\">\n";
  // FIXME: loop over members retrieved from the DB
  // FIXME each of these is editable, an action, etc
  print "<tr><th>Slice Member</th><th>Roles</th><th>Permissions</th><th>Delete?</th><th>Send Message</th></tr>\n";
  print "<tr><td><a href=\"slice-member.php?slice_id=$slice_id&member_id=$slice_owner_id\">$slice_owner_name</a></td><td>Owner</td><td>All</td><td><a href=\"do-delete-slice-member.php?slice_id=$slice_id&member_id=$slice_owner_id\">Delete</a></td><td><mailto=\"$owner_email\">Email $slice_owner_name</a></td></tr>\n";
  print "<tr><td><a href=\"slice-member.php?slice_id=$slice_id&member_id=sam\">Sam</a></td><td>Member</td><td>Write</td><td><a href=\"do-delete-slice-member.php?slice_id=$slice_id&member_id=sam\">Delete</a></td><td><mailto=\"\">Email Sam</a></td></tr>\n";
  print "</table>\n";
}
print "<br/>\n";
print "<b>Slice Owner</b><br/>\n";
print "There is exactly one slice owner for each slice. Slice owners are ultimately responsible for all activity in all slices in their slice, and may be contacted by GENI operations in the event of a problem.<br/><br/>\n";
if ($isnew) {
  print "You will be the owner of your new slice.<br/>\n";
  print "<input type=\"hidden\" name=\"newlead\" value=\"" . $user->account_id . "\"/>\n";
} else {
  print "Slice owner is: <b>$slice_owner_name</b><br/>\n";
  print "To transfer slice leaders, enter email of proposed new slice leader to ask them to take over:<br/>\n";
  print "<input type=\"text\" name=\"newlead\"/><br/>\n";
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

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

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Projects', $TAB_PROJECTS);

$project = "None";
$member = "None";
include("tool-lookupids.php");
if ($project == "None") {
  print "<h2>Error: Couldn't find project</h2>";
  include("footer.php");
  exit();
}
if ($member == "None") {
  print "<h2>Error: Couldn't find member</h2>";
  include("footer.php");
  exit();
}
print "<h1>GENI Project: " . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . ", Member: " . $member->prettyName() . "</h1>\n";

// FIXME: Retrieve info from DB
print "<br/>\n";

print "<form method=\"POST\" action=\"do-edit-project-member.php\">\n";
print "<b>Project Permissions</b><br/><br/>\n";
print "<b>Name</b>: " . $member->prettyName() . "<br/>\n";
print "<input type=\"hidden\" name=\"project_id\" value=\"" . $project_id . "\"/>\n";
print "<input type=\"hidden\" name=\"member_id\" value=\"" . $member_id . "\"/>\n";
$fields = array("Role", "Permissions");
$roleperms = array("Lead", "Write", "Permissions");
// FIXME: Query this user's role & permissions from DB, put in $roleperms with keys from $fields
$rolevals = array("Admin", "Member", "Auditor");
$permvals = array("Read", "Write", "Delegate");
$sliceroles = array("Slice", "Role", "Delegate");
// FIXME: Query this users slices & slice role/delegate from DB into $slices
$slices = array(array("Slice"=>"Slice1", "Role"=>"Owner", "Delegate"=>"1"), array("Slice"=>"Slice2", "Role"=>"Member", "Delegate"=>"0"));
$slicerolevals = array ("Owner", "Member", "Auditor");
print "<b>Role</b>: <select name=\"role\">\n";
foreach ($rolevals as $role) {
  print "<option value=\"" . $role . "\"";
  if ($role == $roleperms["Role"]) {
    print " selected=\"selected\"";
  }
  print ">" . $role . "</option>\n";
}
print "</select>\n<br/>\n";

print "<b>Permissions</b>:<br/>\n";
foreach ($permvals as $perm) {
  // FIXME: Indent these
  print "\t\t$perm <input type=\"checkbox\" name=\"Permissions\" value=\"" . $perm . "\"";
  if (strpos($roleperms["Permissions"], $perm) && strpos($roleperms["Permissions"], $perm) >= 0) {
    print "checked=\"yes\"";
  }
  print "/><br/>\n";
}

print "<br/><br/>\n";

// Handle slices
print "<b>Slice Permissions</b><br/>\n";
print "<table border=\"1\">\n";
print "<tr><th>Slice</th><th>Role</th><th>Delegatable</th><th>Remove?</th></tr>\n";
foreach ($slices as $slice) {
  print "<tr><td>" . $slice["Slice"] . "</td>";
  print "<td><select name=\"role\">\n";
  foreach ($slicerolevals as $role) {
    // FIXME: slice-role value name is funny here
    // Separate form?
    print "<option value=\"" . $slice . "-" . $role . "\"";
    if ($role == $slice["Role"]) {
      print " selected=\"selected\"";
    }
    print ">" . $role . "</option>\n";
  }
  print "</select>\n</td>\n";
  print "<td><input type=\"checkbox\" name=\"Delegate\" ";
  if ($slice["Delegate"]) {
    print "checked=\"yes\"";
  }
  print "/></td>\n";

  print "<td><input type=\"checkbox\" name=\"Remove\"/></td>\n";
  print "</tr>\n";
}
print "</table>\n";

print "<input type=\"submit\" value=\"Edit\"/>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print "</form>\n";
include("footer.php");
?>

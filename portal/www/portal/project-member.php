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
require_once("sa_client.php");
require_once("sa_constants.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Projects', $TAB_PROJECTS);

$project = "None";
$project_name = "None";
$member = "None";
$member_name = "None";
include("tool-lookupids.php");
include("tool-breadcrumbs.php");
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
print "<h1>GENI Project: " . $project_name . ", Member: " . $member_name . "</h1>\n";

// error_log("PID = " . print_r($project_id, true));

// FIXME: Retrieve info from DB
print "<br/>\n";

/*
print "<form style=\"color: grey\" method=\"POST\" action=\"do-edit-project-member.php\">\n";
print "<b>Project Permissions</b><br/><br/>\n";
print "<b>Name</b>: " . $member_name . "<br/>\n";
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
*/

$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
// $project_attribs = get_attributes($cs_url, $user, $member_id, CS_CONTEXT_TYPE::PROJECT, $project_id);
//error_log("SA = " .  print_r($project_attributes, true));
//error_log("PID = " . print_r($project_id, true));
//error_log("PID = " . $project_id);
$project_members = get_project_members($pa_url, $user, $project_id, null);
//$slice_attribs = get_attributes($cs_url, $user, $member_id, CS_CONTEXT_TYPE::SLICE, null);
$slice_members = get_slice_members_for_project($sa_url, $user, $project_id, null);
// error_log("SLICE_MEMBERS = " . print_r($slice_members, true));
//error_log("SA = " .  print_r($slice_attributes, true));

print("<br>\n");
print("<b>Project Roles</b>");
print("\n<table>\n");
print ("<tr><th>Project</th><th>Role</th></tr>");
/*
foreach($project_attribs as $attrib) {
  $project_id = $attrib[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT];
  $project_link = "<a href=\"project.php?project_id=$project_id\">" . $project_name . "</a>";
  $role = $attrib[CS_ATTRIBUTE_TABLE_FIELDNAME::NAME];
  print("<tr><td>$project_link</td><td>$role</td></tr>\n");
}
*/
foreach($project_members as  $project_member) {
  //  error_log("PM = " . print_r($project_member, true));
  $project_link = "<a href=\"project.php?project_id=$project_id\">" . $project_name . "</a>";
  $member_id = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
  $role_index = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
  $role = $CS_ATTRIBUTE_TYPE_NAME[$role_index];
  print("<tr><td>$project_link</td><td>$role</td></tr>\n");
}
print("</table>\n\n");
print("<br>\n");

print("<br>\n");
print("<b>Slice Roles</b>");
if (! is_null($slice_members) && count($slice_members) > 0) {
  print("\n<table>\n");
  print ("<tr><th>Slice</th><th>Role</th></tr>");
  $slices = lookup_slices($sa_url, $user, $project_id, null);
  //error_log("SLICES = " . print_r($slices, true));
  //error_log("ATTRIBS = " . print_r($slice_attribs, true));
  /*
  foreach($slice_members as $slice_member) {
    $slice_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID];
    $slice_name = null;
    foreach($slices as $slice) {
      if($slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID] == $slice_id) {
	$slice_name = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
	break;
      }
    }
    if ($slice_name == null) { continue; }
    $slice_link = "<a href=\"slice.php?slice_id=$slice_id\">" . $slice_name . "</a>";
    $role_index = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
    $role = $CS_ATTRIBUTE_TYPE_NAME[$role_index];
    print("<tr><td>$slice_link</td><td>$role</td></tr>\n");
  }
  */
  foreach($slice_members as $slice_member) {
    //    error_log("SM = " . print_r($slice_member, true));
    $slice_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID];
    $slice_name = null;
    foreach($slices as $slice) {
      //      error_log("SL = " . print_r($slice, true));
      if($slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID] == $slice_id) {
	$slice_name = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
	break;
      }
    }
    if ($slice_name == null) { continue; }
    $member_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    $role_index = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
    $role = $CS_ATTRIBUTE_TYPE_NAME[$role_index];
    $slice_link = "<a href=\"slice.php?slice_id=$slice_id\">" . $slice_name . "</a>";
    print("<tr><td>$slice_link</td><td>$role</td></tr>\n");
  }
  print("</table>\n\n");
} else {
  print "<br/>\n<i>Not in any slices.</i><br/>\n";
}
/*

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
print "<table>\n";
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

*/

include("footer.php");
?>

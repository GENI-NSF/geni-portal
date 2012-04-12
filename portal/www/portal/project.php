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
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
show_header('GENI Portal: Projects', $TAB_PROJECTS);
$user = geni_loadUser();
$project = "None";
if (array_key_exists("id", $_GET)) {
  $project_id = $_GET['id'];
}
$result = "";
if (array_key_exists("result", $_GET)) {
  $result = $_GET['result'];
  if (! is_null($result) && $result != '') {
    $result = " (" . $result . ")";
  }
}
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
$details = lookup_project($pa_url, $project_id);
$name = $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
$email = $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL];
$purpose = $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
$leadid = $details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
$lead = geni_loadUser($leadid);

print "<h1>GENI Project: " . $name . "$result</h1>\n";
$edit_url = 'edit-project.php?id='.$project_id;
print "<b>Name</b>: $name<br/>\n";
// look up lead name
$leadname = $lead->prettyName();
print "<b>Warning: Project name is public</b><br/>\n";
print "<b>Lead</b>: $leadname<br/>\n";
print "<b>Project purpose</b>: $purpose<br/>";
print "<b>Project email</b>: <a href=\"mailto:\">$email</a><br/>\n";
?>
<b>Other static info</b>: etc<br/>
<?php
print '<a href='.$edit_url.'>Edit Project</a><br/>';
?>
<h2>Project slices:</h2>
<?php
include("tool-slices.php");
?>
<br/>
<?php
if ($user->privAdmin()) {
  print "Approve new project members<br/>\n";
  print "?Invite new project member?<br/>\n";
}
?>

<h2>Project members</h2>
<table border="1">
<tr><th>Project Member</th><th>Roles</th></tr>
<?php
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
   print "<tr><td><a href=\"project-member.php?id=" . $project_id . "&member=joe\">Joe</a></td><td>Lead</td></tr>\n";
?>
</table>

<?php
  print "<br/><a href=\"mailto:\">Contact the project leader</a><br/>\n";
?>

<h2>Recent Project Actions</h2>
[stuff goes here...]<br/><br/>


<?php
if ($user->privAdmin()) {
  print "<a href=\"delete-project.php?id=" . $project_id . "\">Delete Project " . $project_id . "</a><br/>\n";
}
include("footer.php");
?>

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
require_once("sa_constants.php");
require_once("sa_client.php");
if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}
if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}
show_header('GENI Portal: Slices', $TAB_SLICES);
$user = geni_loadUser();
$slice = "<None>";
if (array_key_exists("id", $_GET)) {
  $slice = $_GET['id'];
  $slice_item = lookup_slice($sa_url, $slice);
  //$pretty_result = print_r($slice_item, true);
  //error_log("fetch_slice result: $pretty_result\n");

  $name = $slice_item[SA_ARGUMENT::SLICE_NAME];
  $slice_expiration = $slice_item[SA_ARGUMENT::EXPIRATION];
  $slice_urn = $slice_item[SA_ARGUMENT::SLICE_URN];
  // FIX ME: not supported yet  $slice_email = $slice_item[SA_ARGUMENT::SLICE_EMAIL];
  $slice_email = "not.yet.supported@example.com";
  $slice_owner_id = $slice_item[SA_ARGUMENT::OWNER_ID];
  $owner = geni_loadUser($slice_owner_id);
  $slice_owner_name = $owner->prettyName();
  $owner_email = $owner->email();


  $slice_project_id = $slice_item[SA_ARGUMENT::PROJECT_ID];
  //error_log("slice_project_id result: $slice_project_id\n");
  $project_details = lookup_project($pa_url, $slice_project_id);
  //error_log("pa_url result: $pa_url\n");
  //error_log("project_details result: $project_details\n");
  $slice_project_name = $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  //error_log("slice_project_name result: $slice_project_name\n");
}

$edit_url = 'edit-slice.php?id='.$slice;
$add_url = 'slice-add-resources.php?id='.$slice;
$res_url = 'sliceresource.php?id='.$slice;
$proj_url = 'project.php?id='.$slice_project_id;
$slice_own_url = 'slice-member.php?id='.$slice_owner_id;
$slicecred_url = "slicecred.php?id=".$slice;

print "<h1>GENI Slice: " . $name ." </h1>\n";
print "<table border=\"1\">\n";
// print "<tr><th>Name </th><th>Value</th></tr>\n";
print "<tr><td><b>Slice Name <a href='#warn'>*</a> </b></td><td>$name</td></tr>\n";
print "<tr><td><b>Member of Project <a href='#warn'>*</a> </b></td><td><a href=$proj_url>$slice_project_name</a></td></tr>\n";
print "<tr><td><b>Slice URN</b></td><td>$slice_urn</td></tr>\n";
print "<tr><td><b>Slice UUID</b></td><td>$slice</td></tr>\n";
print "<tr><td><b>Slice e-mail</b></td><td><a href='mailto:$slice_email'>e-mail</a></td></tr>\n";
print "<tr><td><b>Slice Owner</b></td><td><a href=$slice_own_url>$slice_owner_name</a> <a href='mailto:$owner_email'>e-mail</a></td></tr>\n";
print "<tr><td><b>Slice Expiration</b></td><td>$slice_expiration</td></tr>\n";
print "</table>\n";

print "<b id='warn'>* Warning: Slice and project names are public</b><br/>\n";
print "<br/>\n";

print "<h2>Slice Actions</h2>\n";

if ($user->privSlice()) {
  print "<a href=$slicecred_url>Download Slice Cred</a>";
  print "<br/>";
  print "<a href=$edit_url>Edit Slice</a>";
  print "<br/>";
  print "<a href='$add_url'>Add Resources</a>";
  print "<br/>";
}
if ($user->privAdmin()) {
  print "<a href=\"delete-slice.php?id=" . $slice . "\">Delete Slice " . $name. "</a>\n";
  print "<br/>";
  print "<a href=\"shutdown-slice.php?id=" . $slice . "\">Shutdown Slice " . $name. "</a>\n";
  print "<br/>";
}
?>
<br/>
<form method='POST' action='do-renew.php'>
<b>Date to renew until</b>: <input type='text' name='Renew' 
<?php
  print "value=\"$slice_expiration\"/><br/>\n";
  print "<input type=\"hidden\" name=\"id\" value=\"$slice\"/><br/>\n";
?>
<input type='submit' name= 'Renew' value='Renew'/>
</form>

<h2>Slice members</h2>
<table border="1">
<tr><th>Slice Member</th><th>Roles</th></tr>
<?php
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
   print "<tr><td><a href=\"slice-member.php?id=" . $slice . "&member=$slice_owner_id\">$slice_owner_name</a></td><td>Owner</td></tr>\n";
?>
</table>

<?php
if ($user->privAdmin()) {
  print "Approve new slice members<br/>\n";
  print "?Invite new slice member?<br/>\n";
}
?>

<h2>Recent Slice Actions</h2>
[stuff goes here...]<br/><br/>

<?php
include("footer.php");
?>

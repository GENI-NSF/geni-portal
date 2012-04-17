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
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Slices', $TAB_SLICES);
unset($slice);
include("tool-lookupids.php");
if (isset($slice)) {
  //$pretty_result = print_r($slice, true);
  //error_log("fetch_slice result: $pretty_result\n");

  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  $slice_expiration = $slice[SA_ARGUMENT::EXPIRATION];
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
  // FIX ME: not supported yet  $slice_email = $slice[SA_ARGUMENT::SLICE_EMAIL];
  $slice_email = "not.yet.supported@example.com";
  $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
  $owner = geni_loadUser($slice_owner_id);
  $slice_owner_name = $owner->prettyName();
  $owner_email = $owner->email();

  $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  //error_log("slice_project_name result: $slice_project_name\n");
} else {
  print "Unable to load slice<br/>\n";
  include("footer.php");
  exit();
}

$edit_url = 'edit-slice.php?slice_id='.$slice_id;
$add_url = 'slice-add-resources.php?slice_id='.$slice_id;
$res_url = 'sliceresource.php?slice_id='.$slice_id;
$proj_url = 'project.php?project_id='.$slice_project_id;
$slice_own_url = 'slice-member.php?member_id='.$slice_owner_id . "&slice_id=" . $slice_id;
$slicecred_url = "slicecred.php?slice_id=".$slice_id;


$status_url = 'sliverstatus.php?slice_id='.$slice_id;
$listres_url = 'listresources.php?slice_id='.$slice_id;

print "<h1>GENI Slice: " . $slice_name ." </h1>\n";
print "<table border=\"1\">\n";
// print "<tr><th>Name </th><th>Value</th></tr>\n";
print "<tr><td><b>Slice Name (public) </b></td><td>$slice_name</td></tr>\n";
print "<tr><td><b>Member of Project (public) </b></td><td><a href=$proj_url>$slice_project_name</a></td></tr>\n";
print "<tr><td><b>Slice URN</b></td><td>$slice_urn</td></tr>\n";
print "<tr><td><b>Slice UUID</b></td><td>$slice_id</td></tr>\n";
print "<tr><td><b>Slice e-mail</b></td><td><a href='mailto:$slice_email'>e-mail</a></td></tr>\n";
print "<tr><td><b>Slice Owner</b></td><td><a href=$slice_own_url>$slice_owner_name</a> <a href='mailto:$owner_email'>e-mail</a></td></tr>\n";
print "<tr><td><b>Slice Expiration</b></td><td>$slice_expiration</td></tr>\n";
print "</table>\n";

print "<b id='warn'>Warning: Slice and project names are public</b><br/>\n";
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

print "<a href=$status_url>Sliver Status</a>";
print "<br/>";
print "<a href=$listres_url>ListResources</a> -- not working yet";
print "<br/>";

if ($user->privAdmin()) {
  print "<a href=\"delete-slice.php?slice_id=" . $slice_id . "\">Disable Slice " . $slice_name. "</a> -- not working yet\n";
  print "<br/>";
  print "<a href=\"confirm-sliverdelete.php?slice_id=" . $slice_id . "\">Delete Sliver " . $slice_name. "</a>\n";
  print "<br/>";
  print "<a href=\"shutdown-slice.php?slice_id=" . $slice_id . "\">Shutdown Slice " . $slice_name. "</a> -- not working yet\n";
  print "<br/>";
}
?>
<br/>
<?php
 //print "<form method='POST' action=\"do-renew.php?id=$slice\">";
print "<form method='GET' action=\"do-renew.php\">";
print "<b>Date to renew until</b>: <input type='text' name='slice_expiration'";
print "value=\"$slice_expiration\"/><br/>\n";
print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/><br/>\n";
?>
<input type='submit' name= 'Renew' value='Renew'/>
</form>

<h2>Slice members</h2>
<table border="1">
<tr><th>Slice Member</th><th>Roles</th></tr>
<?php
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
   print "<tr><td><a href=\"slice-member.php?slice_id=" . $slice_id . "&member_id=$slice_owner_id\">$slice_owner_name</a></td><td>Owner</td></tr>\n";
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

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
require_once('logging_client.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Slices', $TAB_SLICES);
unset($slice);
include("tool-lookupids.php");
include("tool-breadcrumbs.php");
if (isset($slice)) {
  //$pretty_result = print_r($slice, true);
  //error_log("fetch_slice result: $pretty_result\n");

  //  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  $slice_desc = "NOT IMPLEMENTED"; //$slice[SA_ARGUMENT::SLICE_DESCRIPTION];
  $slice_creation = "1-2-3456"; //$slice[SA_ARGUMENT::SLICE_CREATION];
  $slice_expiration = $slice[SA_ARGUMENT::EXPIRATION];
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
  $slice_email = $slice[SA_ARGUMENT::SLICE_EMAIL];
  $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
  $owner = geni_loadUser($slice_owner_id);
  $slice_owner_name = $owner->prettyName();
  $owner_email = $owner->email();

  $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  //error_log("slice project_name result: $project_name\n");
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
$flack_url = "flack.php?slice_id=".$slice_id;


$status_url = 'sliverstatus.php?slice_id='.$slice_id;
$listres_url = 'listresources.php?slice_id='.$slice_id;

print "<h1>GENI Slice: " . $slice_name ." </h1>\n";
//print "<h2>Slice Actions</h2>\n";

print "<table><tr>\n";
if ($user->privSlice()) {
  print "<td><button onClick=\"window.location='$add_url'\"><b>Add Resources</b></button></td>\n";
}
print "<td><button onClick=\"window.location='$status_url'\"><b>Sliver Status</b></button></td>\n";
print "<td><button onClick=\"window.location='$listres_url'\"><b>List Resources</b></button></td>\n";
// -- not working yet";
if ($user->privSlice()) {
  print "<td><button onClick=\"window.location='confirm-sliverdelete.php?slice_id=" . $slice_id . "'\">Delete Slivers for " . $slice_name. "</button></td>\n";

  print "<td><form method='GET' action=\"do-renew.php\">";
  print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
  print "<input type='submit' name= 'Renew' value='Renew Slivers'/>\n";
  print " until ";
  print "<input type='text' name='slice_expiration'";
  print "value=\"$slice_expiration\"/>\n";
  print "</form></td>\n";

  print "<td><form method='GET' action=\"do-renew-slice.php\">";
  print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
  print "<input type='submit' name= 'Renew' value='Renew Slice'/>\n";
  print " until ";
  print "<input type='text' name='slice_expiration'";
  print "value=\"$slice_expiration\"/>\n";
  print "</form></td>\n";

  print "<td><button onClick=\"window.location='$edit_url'\">Edit Slice</button></td>\n";
  print "<td><button onClick=\"window.location='$slicecred_url'\">Download Slice Cred</button></td>\n";
  print "<td><button title=\"not working yet\" onClick=\"window.location='disable-slice.php?slice_id=" . $slice_id . "'\">Disable Slice " . $slice_name. "</button></td>\n";
  print "<td><button title=\"not working yet\" onClick=\"window.location='shutdown-slice.php?slice_id=" . $slice_id . "'\">Shutdown Slice " . $slice_name. "</button></td>\n";
  print "<td><button onClick=\"window.location='$flack_url'\"><image width=\"40\" src=\"http://groups.geni.net/geni/attachment/wiki/ProtoGENIFlashClient/pgfc-screenshot.jpg?format=raw\"/><br/>Launch Flack</button></td>\n";

}
print "</tr></table>\n";


/*   print "<h2>Slice Operational Monitoring</h2>\n"; */
/* print "<table>\n"; */
/* print "<tr><td><b>Slice data</b></td><td><a href='https://gmoc-db.grnoc.iu.edu/protected-openid/index.pl?method=slice_details;slice=".$slice_urn."'>Slice $slice_name</a></td></tr>\n"; */
/* print "</table>\n"; */



print "<table>\n";
print "<tr><th colspan='4'>Slice Identifiers (public)</th></tr>\n";
print "<tr><td><b>Name</b></td><td>$slice_name</td><td><b>Project</b></td><td><a href=$proj_url>$project_name</a></td></tr>\n";
print "<tr><td class='deemphasize'><b>URN</b></td><td  class='deemphasize' colspan='4'>$slice_urn</td></tr>\n";
print "<tr><td><b>Creation</b></td><td colspan='3'>$slice_creation</td></tr>\n";
print "<tr><td><b>Description (public) </b></td><td colspan='3'>$slice_desc</td></tr>\n";

/* print "<tr><td rowspan='2'><b>Slice Identifier</b></td> <td><b>URN</b></td><td>$slice_urn</td></tr>\n"; */
/* print "<tr><td class='deemphasize'><b>UUID</b></td><td class='deemphasize'>$slice_id</td></tr>\n"; */
print "<tr><th colspan='4'>Contact Information</th></tr>\n";
print ("<tr><td><b>e-mail</b></td><td colspan='3'><a href='mailto:$slice_email'>" . "$slice_email</a></td></tr>\n");
print "<tr><td><b>Owner</b></td><td colspan='3'><a href=$slice_own_url>$slice_owner_name</a> <a href='mailto:$owner_email'>e-mail</a></td></tr>\n";
print "<tr><td><b>Expiration</b></td><td colspan='4'>\n";
print "<form method='GET' action=\"do-renew-slice.php\">";
print "<input type='text' name='slice_expiration'";
print "value=\"$slice_expiration\"/>\n";
print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>  \n";
?>
<input type='submit' name= 'Renew' value='Renew Slice'/>
</form></td></tr>
<?php
  print "</table>\n";
?>





<h2>Slice members</h2>
<table>
<tr><th>Slice Member</th><th>Roles</th></tr>
<?php
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
   print "<tr><td><a href=\"slice-member.php?slice_id=" . $slice_id . "&member_id=$slice_owner_id\">$slice_owner_name</a></td><td>Owner</td></tr>\n";
?>
</table>

<?php
  //if ($user->privAdmin()) {
  // FIXME: What is the right thing to check here?
if ($user->privSlice()) {
  print "Approve new slice members<br/>\n";
  print "?Invite new slice member?<br/>\n";
}
?>

<h2>Recent Slice Actions</h2>
<table>
<tr><th>Time</th><th>Message</th><th>Member</th>
<?php
  $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
  $entries = get_log_entries_for_context($log_url, CS_CONTEXT_TYPE::SLICE, $slice_id);
  foreach($entries as $entry) {
    $message = $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
    $time = $entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME];
    $member_id = $entry[LOGGING_TABLE_FIELDNAME::USER_ID];
    $member = geni_loadUser($member_id);
    $member_name = $member->prettyName();
    //    error_log("ENTRY = " . print_r($entry, true));
    print "<tr><td>$time</td><td>$message</td><td><a href=\"slice-member.php?slice_id=" . $slice_id . "&member_id=$member_id\">$member_name</a></td></tr>\n";
  }
?>
</table>
<br/><br/>

<?php
include("footer.php");
?>

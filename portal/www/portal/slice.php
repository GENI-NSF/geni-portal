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

// A Single Slice

require_once("user.php");
require_once("header.php");
require_once("portal.php");
require_once('util.php');
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");
require_once('logging_client.php');
require_once('am_map.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
unset($slice);
include("tool-lookupids.php");

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}

if (isset($slice)) {
  //  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  //  error_log("SLICE  = " . print_r($slice, true));
  $slice_desc = $slice[SA_ARGUMENT::SLICE_DESCRIPTION];
  $slice_creation_db = $slice[SA_ARGUMENT::CREATION];
  $slice_creation = dateUIFormat($slice_creation_db);
  $slice_expiration_db = $slice[SA_ARGUMENT::EXPIRATION];
  $slice_expiration = dateUIFormat($slice_expiration_db);
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
  $slice_email = $slice[SA_ARGUMENT::SLICE_EMAIL];
  $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
  $owner = $user->fetchMember($slice_owner_id);
  $slice_owner_name = $owner->prettyName();
  $owner_email = $owner->email();

  $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  //error_log("slice project_name result: $project_name\n");
  // Fill in members of slice member table
  $members = get_slice_members($sa_url, $user, $slice_id);
}

show_header('GENI Portal: Slices', $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");


if (! isset($slice)) {
  print "Unable to load slice<br/>\n";
  include("footer.php");
  exit();
}

?>


<?php

$edit_url = 'edit-slice.php?slice_id='.$slice_id;
$add_url = 'slice-add-resources.php?slice_id='.$slice_id;
$res_url = 'sliceresource.php?slice_id='.$slice_id;
$proj_url = 'project.php?project_id='.$slice_project_id;
$slice_own_url = 'slice-member.php?member_id='.$slice_owner_id . "&slice_id=" . $slice_id;
$slicecred_url = "slicecred.php?slice_id=".$slice_id;
$flack_url = "flack.php?slice_id=".$slice_id;

$status_url = 'sliverstatus.php?slice_id='.$slice_id;
$listres_url = 'listresources.php?slice_id='.$slice_id;
$addnote_url = 'add-slice-note.php?slice_id='.$slice_id;

// String to disable button or other active element
$disabled = "disabled = " . '"' . "disabled" . '"'; 

$add_slivers_privilege = $user->isAllowed(SA_ACTION::ADD_SLIVERS,
				    CS_CONTEXT_TYPE::SLICE, $slice_id);
$add_slivers_disabled = "";
if(!$add_slivers_privilege) { $add_slivers_disabled = $disabled; }

// String to disable button or other active element
$disabled = "disabled = " . '"' . "disabled" . '"'; 

$delete_slivers_privilege = $user->isAllowed(SA_ACTION::DELETE_SLIVERS,
				    CS_CONTEXT_TYPE::SLICE, $slice_id);
$delete_slivers_disabled = "";
if(!$delete_slivers_privilege) { $delete_slivers_disabled = $disabled; }

$renew_slice_privilege = $user->isAllowed(SA_ACTION::RENEW_SLICE,
				    CS_CONTEXT_TYPE::SLICE, $slice_id);
$renew_disabled = "";
if(!$renew_slice_privilege) { $renew_disabled = $disabled; }

$lookup_slice_privilege = $user->isAllowed(SA_ACTION::LOOKUP_SLICE, 
				    CS_CONTEXT_TYPE::SLICE, $slice_id);

?>

<!-- This belongs in the header, probably -->
<script>
var slice= "<?php echo $slice_id ?>";
var renew_slice_privilege= "<?php echo $renew_slice_privilege?>";
var slice_expiration= "<?php echo $slice_expiration?>";
var sliver_expiration= "NOT IMPLEMENTED YET";
var delete_slivers_disabled= "<?php echo $delete_slivers_disabled ?>";
var slice_status= "";
var slice_name= "<?php echo $slice_name?>";


</script>
<script src="amstatus.js"></script>
<script>
$(document).ready(build_agg_table_on_slicepg);
</script>
<?php 
print "<h1>GENI Slice: " . $slice_name . " </h1>\n";

print "<table>\n";
print "<tr><th>Slice Actions</th><th>Renew</th></tr>\n";

/* Slice Actions */
print "<tr><td rowspan='2'>\n";
print "<button onClick=\"window.location='$add_url'\" $add_slivers_disabled ><b>Add Resources</b></button>\n";

print "<button onClick=\"window.location='$status_url'\"><b>Resource Status</b></button>\n";
print "<button title='Login info, etc' onClick=\"window.location='$listres_url'\"><b>Details</b></button>\n";
print "<button  $add_slivers_disabled onClick=\"window.location='$addnote_url'\"><b>Add Note</b></button>\n";

print "<button onClick=\"window.location='confirm-sliverdelete.php?slice_id=" . $slice_id . "'\" $delete_slivers_disabled><b>Delete Resources</b></button>\n";
print "</td>\n";

/* Renew */
if($renew_slice_privilege) {
  print "<td>\n";
  print "<form method='GET' action=\"do-renew-slice.php\">";
  print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
  print "<input class='date' type='text' name='slice_expiration'";
  print "value=\"$slice_expiration\"/>\n";
  print "<input type='submit' name= 'Renew' value='Renew Slice'/>\n";
  print "</form>\n";
} else {
  print "$slice_expiration";
}
print "</td></tr>\n";


print "<tr><td>\n";
if ($renew_slice_privilege) {
  print "<form method='GET' action=\"do-renew.php\">";
  print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
  print "<input class='date' type='text' name='sliver_expiration'";
  print "value=\"$slice_expiration\"/>\n";
  print "<input type='submit' name= 'Renew' value='Renew Resource Reservations'/>\n";
  print "</form>\n";
} else {
  print "$slice_expiration";
}
print "</td></tr>\n";

print "<tr><th>Tools</th><th>Ops Mgmt</th></tr>\n";
/* Tools */
print "<tr><td>\n";
/* print "To use a command line tool:<br/>"; */
print "<button $add_slivers_disabled onClick=\"window.open('$flack_url')\"><image width=\"40\" src=\"http://groups.geni.net/geni/attachment/wiki/ProtoGENIFlashClient/pgfc-screenshot.jpg?format=raw\"/><br/><b>Launch Flack</b> </button>\n";
print "<button disabled='disabled' onClick=\"window.location='$slicecred_url'\" $add_slivers_disabled><b>Download Slice Credential</b>  </button>\n";
print "<button disabled='disabled'><b>Download GUSH Config</b></button>\n";
print "</td>\n";

/* Ops Management */
print "<td>\n";
print "<button title=\"not working yet\" disabled=\"disabled\" onClick=\"window.location='disable-slice.php?slice_id=" . $slice_id . "'\"><b>Disable Slice</b></button>\n";
print "<button title=\"not working yet\" disabled=\"disabled\" onClick=\"window.location='shutdown-slice.php?slice_id=" . $slice_id . "'\"><b>Shutdown Slice</b></button>\n";
print "</td></tr>\n";

print "</table>\n";

/* print "<h2>Slice Operational Monitoring</h2>\n"; */
/* print "<table>\n"; */
/* print "<tr><td><b>Slice data</b></td><td><a href='https://gmoc-db.grnoc.iu.edu/protected-openid/index.pl?method=slice_details;slice=".$slice_urn."'>Slice $slice_name</a></td></tr>\n"; */
/* print "</table>\n"; */


print "<br/>Confused? Look at the <a href='help.php'>Portal Help</a> or <a href='glossary.html'>GENI Glossary</a>.<br/>";

// ----
// Now show slice / sliver status

//include("query-sliverstatus.php");

print "<h2>Slice Status</h2>\n";

  $slice_status='';

  print "<div id='status_table_div'/>\n";
  print "<table>\n";
  //  print "<tr><th>Status</th><th colspan='2'>Slice</th><th>Creation</th><th>Expiration</th><th>Actions</th></tr>\n";
  print "<tr><th>Status</th><th colspan='4'>Slice</th></tr>\n";


  /* Slice Info */
  print "<tr>";
  print "<td class='$slice_status'>$slice_status</td>";
  print "<td colspan='4'>$slice_name</td>";
  print "</tr>\n";

  print "</table>\n";
  print "</div>\n";
// --- End of Slice and Sliver Status table

print "<br/>\n";

// Slice Identifers table
print "<table>\n";
print "<tr><th colspan='2'>Slice Identifiers (public)</th></tr>\n";
print "<tr><td class='label'><b>Name</b></td><td>$slice_name</td></tr>\n";
print "<tr><td class='label'><b>Project</b></td><td><a href=$proj_url>$project_name</a></td></tr>\n";
print "<tr><td class='label deemphasize'><b>URN</b></td><td  class='deemphasize'>$slice_urn</td></tr>\n";
print "<tr><td class='label'><b>Creation</b></td><td>$slice_creation</td></tr>\n";
print "<tr><td class='label'><b>Description</b></td><td>$slice_desc ";
echo "<button disabled=\"disabled\" onClick=\"window.location='$edit_url'\"><b>Edit</b></button>";
print "</td></tr>\n";
print "<tr><th colspan='2'>Contact Information</th></tr>\n";
print ("<tr><td class='label'><b>Slice e-mail</b></td><td><a href='mailto:$slice_email'>" . "$slice_email</a></td></tr>\n");
print "<tr><td class='label'><b>Slice Owner</b></td><td><a href=$slice_own_url>$slice_owner_name</a> <a href='mailto:$owner_email'>e-mail</a></td></tr>\n";
print "</table>\n";
// ---

print "<h2>Slice members</h2>";
$edit_members_disabled = "";
if ($user->isAllowed(SA_ACTION::ADD_SLICE_MEMBER, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  $edit_members_disabled = $disabled;
}
echo "<button $edit_members_disabled onClick=\"window.location='$edit_url'\"><b>Edit</b></button>";
?>

<table>
	<tr>
		<th>Slice Member</th>
		<th>Roles</th>
	</tr>
	<?php
foreach($members as $member) {



  $member_id = $member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
  $member_user = $user->fetchMember($member_id);
  //  error_log("MEMBER = " . print_r($member_user, true));
  $member_name = $member_user->prettyName();
  $member_role_index = $member[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
  $member_role = $CS_ATTRIBUTE_TYPE_NAME[$member_role_index];
  print "<tr><td><a href=\"slice-member.php?slice_id=" . $slice_id . 
    "&member_id=$member_id\">$member_name</a></td>" . 
    "<td>$member_role</td></tr>\n";
}
	?>
</table>

<h2>Recent Slice Actions</h2>
<table>
	<tr>
		<th>Time</th>
		<th>Message</th>
		<th>Member</th>
		<?php
		$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
                $entries = get_log_entries_for_context($log_url, Portal::getInstance(),
						       CS_CONTEXT_TYPE::SLICE, $slice_id);
                usort($entries, 'compare_log_entries');
		foreach($entries as $entry) {
		  $message = $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
		  $time = dateUIFormat($entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME]);
		  $member_id = $entry[LOGGING_TABLE_FIELDNAME::USER_ID];
		  $member = $user->fetchMember($member_id);
		  $member_name = $member->prettyName();
		  //    error_log("ENTRY = " . print_r($entry, true));
		  print "<tr><td>$time</td><td>$message</td><td><a href=\"slice-member.php?slice_id=" . $slice_id . "&member_id=$member_id\">$member_name</a></td></tr>\n";
  }
?>

</table>
<br />
<br />

<?php
include("footer.php");
?>

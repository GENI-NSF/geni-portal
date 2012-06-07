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
show_header('GENI Portal: Slices', $TAB_SLICES);
unset($slice);
include("tool-lookupids.php");
include("tool-breadcrumbs.php");

if (isset($slice)) {
  //  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  //  error_log("SLICE  = " . print_r($slice, true));
  $slice_desc = $slice[SA_ARGUMENT::SLICE_DESCRIPTION];
  $slice_creation = $slice[SA_ARGUMENT::CREATION];
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

print "<h1>GENI Slice: " . $slice_name . " </h1>\n";

print "<table>\n";
if ($user->privSlice()) {
  $slice_col='4';
} else {
  $slice_col='2';
}

print "<tr><th>Slice Actions</th><th>Renew</th></tr>\n";

/* Slice Actions */
print "<tr><td rowspan='2'>\n";
if ($user->privSlice()) {
  print "<button onClick=\"window.location='$add_url'\"><b>Add Slivers</b></button>\n";
} else {
  // FIXME: Print something that shows what you can't do
}
print "<button onClick=\"window.location='$status_url'\"><b>Sliver Status</b></button>\n";
print "<button onClick=\"window.location='$listres_url'\"><b>Manifest</b></button>\n";
if ($user->privSlice()) {
  print "<button onClick=\"window.location='confirm-sliverdelete.php?slice_id=" . $slice_id . "'\"><b>Delete Slivers</b></button>\n";
} else {
  // FIXME: PRint something
}
print "</td>\n";

/* Renew */
print "<td>\n";
if ($user->privSlice()) {
  print "<form method='GET' action=\"do-renew-slice.php\">";
  print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
  print "<input class='date' type='text' name='slice_expiration'";
  print "value=\"$slice_expiration\"/>\n";
  print "<input type='submit' name= 'Renew' value='Renew Slice'/>\n";
  print "</form>\n";
} else {
  print "$slice_expiration";
  // fIXME Say something about what you can't do
}
print "</td></tr>\n";


print "<tr><td>\n";
if ($user->privSlice()) {
  print "<form method='GET' action=\"do-renew.php\">";
  print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
  print "<input class='date' type='text' name='slice_expiration'";
  print "value=\"$slice_expiration\"/>\n";
  print "<input type='submit' name= 'Renew' value='Renew Slivers'/>\n";
  print "</form>\n";
} else {
  print "$sliver_expiration";
  // FIXME: Print something that you don't get to renew?
}
print "</td></tr>\n";

if ($user->privSlice()) {
  print "<tr><th>Other Tools</th><th>Ops Mgmt</th></tr>\n";
  /* Other Tools */
  print "<tr><td>\n";
  /* print "To use a command line tool:<br/>"; */
  print "<button onClick=\"window.location='$slicecred_url'\"><b>Download Credentials for Omni</b></button>\n";
  print "<button onClick=\"window.open('$flack_url')\"><image width=\"40\" src=\"http://groups.geni.net/geni/attachment/wiki/ProtoGENIFlashClient/pgfc-screenshot.jpg?format=raw\"/><br/><b>Launch Flack</b></button>\n";
  print "<button disabled='disabled'><b>Download GUSH Config</b></button>\n";
  print "</td>\n";

  /* Ops Management */
  print "<td>\n";
  print "<button title=\"not working yet\" disabled=\"disabled\" onClick=\"window.location='disable-slice.php?slice_id=" . $slice_id . "'\"><b>Disable Slice</b></button>\n";
  print "<button title=\"not working yet\" disabled=\"disabled\" onClick=\"window.location='shutdown-slice.php?slice_id=" . $slice_id . "'\"><b>Shutdown Slice</b></button>\n";
  print "</td></tr>\n";
} else {
  // FIXME: Print something that shows what you don't get here?
}

print "</table>\n";

/*   print "<h2>Slice Operational Monitoring</h2>\n"; */
/* print "<table>\n"; */
/* print "<tr><td><b>Slice data</b></td><td><a href='https://gmoc-db.grnoc.iu.edu/protected-openid/index.pl?method=slice_details;slice=".$slice_urn."'>Slice $slice_name</a></td></tr>\n"; */
/* print "</table>\n"; */

// ----
// Now show slice / sliver status

include("query-sliverstatus.php");

print "<h2>Slice Status</h2>\n";
if (!(isset($msg) and isset($obj))) {
  print "<p><i>Failed to determine status of resources.</i></p>";  
} else {
  $slice_status='';
  print "<table>\n";
  print "<tr><th>Status</th><th colspan='2'>Slice</th><th>Creation</th><th>Expiration</th><th>Actions</th></tr>\n";
  /* Slice Info */
  print "<tr>";
  print "<td class='$slice_status'>$slice_status</td>";
  print "<td colspan='2'>$slice_name</td>";
  print "<td>$slice_creation</td>";

  if ($user->privSlice()) {
    print "<td><form method='GET' action=\"do-renew-slice.php\">";
    print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
    print "<input class='date' type='text' name='slice_expiration'";
    print "value=\"$slice_expiration\"/>\n";
    print "<input type='submit' name= 'Renew' value='Renew'/>\n";
    print "</form></td>\n";
  } else {
    print "<td>$slice_expiration</td>";
    // FIXME: Print something about what you can't do
  }

  print "<td>";
  if ($user->privSlice()) {
    print "<button onClick=\"window.location='$add_url'\"><b>Add Slivers</b></button>\n";
  } else {
    // FIXME: Print something about what you can't do
  }
  print "<button onClick=\"window.location='$status_url'\"><b>Sliver Status</b></button>\n";
  print "<button onClick=\"window.location='$listres_url'\"><b>Manifest</b></button>\n";
  if ($user->privSlice()) {
    print "<button onClick=\"window.location='confirm-sliverdelete.php?slice_id=" . $slice_id . "'\"><b>Delete Slivers</b></button>\n";
  } else {
    // FIXME: Print something about what you can't do
  }

  print "</td>";
  print "</tr>\n";

  /* Sliver Info */
  $first = True;
  $aggs = array_keys( $obj );
  $displayed_aggs = 0;
  foreach ($aggs as $agg){
    $agg_obj = $obj[$agg];
    /* ignore aggregates which returned nothing */
    if (!is_array($agg_obj)){
      continue;
    }
    $displayed_aggs++;
    if ($first){
      print "<tr>";
      print "<th class='notapply'>";
      print "</th><th>Status</th><th>Aggregate</th>";
      print "<th>Creation</th>";
      print "<th>Expiration</th></tr>\n";
      $first = False;
    }
    $sliver_status=$agg_obj['geni_status'];
    $sliver_creation='NOT IMPLEMENTED YET';
    $sliver_expiration='NOT IMPLEMENTED YET';
    print "<tr>";
    print "<td class='notapply'></td>";
    print "<td class='$sliver_status'>$sliver_status</td>";
    $agg_name = am_name($agg);
    print "<td>$agg_name</td>";
    print "<td>$sliver_creation</td>";
    
    if ($user->privSlice()) {
      print "<td><form method='GET' action=\"do-renew.php\">";
      print "<input type=\"hidden\" name=\"slice_id\" value=\"$slice_id\"/>\n";
      print "<input class='date' type='text' name='slice_expiration'";
      print "value=\"$slice_expiration\"/>\n";
      print "<input type='submit' name= 'Renew' value='Renew'/>\n";
      print "</form></td>\n";
    } else {
      print "<td>$sliver_expiration</td>";
      // FIXME: Print something about what you can't do
    }

    print "</tr>";
  }

  if ($displayed_aggs == 0) {
    /* No resources detected. Say so. */
    print "<tr><td class='notapply'/><td colspan='5'><i>No resources detected.</i></td></tr>";
  }

  print "</table>\n";
}
// --- End of Slice and Sliver Status table

print "<br/>\n";

// Slice Identifers table
print "<table>\n";
print "<tr><th colspan='2'>Slice Identifiers (public)</th></tr>\n";
print "<tr><td class='label'><b>Name</b></td><td>$slice_name</td></tr>\n";
print "<tr><td class='label'><b>Project</b></td><td><a href=$proj_url>$project_name</a></td></tr>\n";
print "<tr><td class='label deemphasize'><b>URN</b></td><td  class='deemphasize' colspan='4'>$slice_urn</td></tr>\n";
print "<tr><td class='label'><b>Creation</b></td><td colspan='3'>$slice_creation</td></tr>\n";
print "<tr><td class='label'><b>Description</b></td><td colspan='3'>$slice_desc ";
echo "<button disabled=\"disabled\" onClick=\"window.location='$edit_url'\"><b>Edit</b></button>";
print "</td></tr>\n";
print "<tr><th colspan='2'>Contact Information</th></tr>\n";
print ("<tr><td class='label'><b>Slice e-mail</b></td><td><a href='mailto:$slice_email'>" . "$slice_email</a></td></tr>\n");
print "<tr><td class='label'><b>Slice Owner</b></td><td><a href=$slice_own_url>$slice_owner_name</a> <a href='mailto:$owner_email'>e-mail</a></td></tr>\n";
print "</table>\n";
// ---

/* print "<table>\n"; */
/* if ($user->privSlice()) { */
/*   print "<tr><th colspan='4'>Manage Slice</th></tr>\n"; */
/*   print "<tr>"; */
/*   print "<td colspan='2'>To use a command line tool:<br/><button onClick=\"window.location='$slicecred_url'\">Download Slice Credential</button></td>\n"; */
/*   print "<td colspan='2'><button onClick=\"window.location='$flack_url'\"><image width=\"40\" src=\"http://groups.geni.net/geni/attachment/wiki/ProtoGENIFlashClient/pgfc-screenshot.jpg?format=raw\"/><br/>Launch Flack</button></td>\n"; */
/*   print "</tr>"; */

/* print "<tr><th colspan='4'>Operator Management</th></tr>\n"; */
/* print "<tr>"; */

/* print "<td colspan='2'><button title=\"not working yet\" onClick=\"window.location='disable-slice.php?slice_id=" . $slice_id . "'\">Disable Slice</button></td>\n"; */
/* print "<td colspan='2'><button title=\"not working yet\" onClick=\"window.location='shutdown-slice.php?slice_id=" . $slice_id . "'\">Shutdown Slice</button></td>\n"; */
/* print "</tr>"; */
/* } */

/* print "</table>\n"; */


print "<h2>Slice members</h2>";
echo "<button onClick=\"window.location='$edit_url'\"><b>Edit</b></button>";
?>

<table>
	<tr>
		<th>Slice Member</th>
		<th>Roles</th>
	</tr>
	<?php
	// FIXME: See project-member.php. Replace all that with a table or 2 here?
	print "<tr><td><a href=\"slice-member.php?slice_id=" . $slice_id . "&member_id=$slice_owner_id\">$slice_owner_name</a></td><td>Slice Owner</td></tr>\n";
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
<br />
<br />

<?php
include("footer.php");
?>

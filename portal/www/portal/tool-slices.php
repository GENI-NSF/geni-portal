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
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("sa_client.php");
require_once("pa_client.php");
require_once("util.php");

// String to disable button or other active element
$disabled = "disabled = " . '"' . "disabled" . '"'; 

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}
if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}

// This gets all projects of which the user is a member
if (isset($project_id) && uuid_is_valid($project_id)) {
  $slices = lookup_slices($sa_url, $user, $project_id, $user->account_id);
} else {
  $slices = lookup_slices($sa_url, $user, null, $user->account_id);
}
if (count($slices) > 0) {
  print "\n<table>\n";
  print ("<tr><th>Slice Name</th>");
  print ("<th>Project</th>");
  print ("<th>Slice Expiration</th>");
  print ("<th>Slice Owner</th>"
         . "<th>Actions</th>");
  if ($portal_enable_abac) {
    print "<th>ABAC Credential</th>";
  }
  print "</tr>\n";

  $base_url = relative_url("slicecred.php?");
  $slice_base_url = relative_url("slice.php?");
  $listres_base_url = relative_url("listresources.php?");
  $resource_base_url = relative_url("slice-add-resources.php?");
  $delete_sliver_base_url = relative_url("confirm-sliverdelete.php?");
  $sliver_status_base_url = relative_url("sliverstatus.php?");
  $abac_url = relative_url("sliceabac.php?");
  $flack_url = relative_url("flack.php?");

  foreach ($slices as $slice) {
    $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    // FIXME: Add PROJECT_ID, OWNER_ID
    if (! uuid_is_valid($slice_id)) {
      error_log("tool-slices: invalid slice_id from lookup_slices");
      continue;
    }
    $args['slice_id'] = $slice_id;
    $query = http_build_query($args);
    $query = $query;
    $slicecred_url = $base_url . $query;
    $slice_url = $slice_base_url . $query;
    $sliceresource_url = $resource_base_url . $query;
    $delete_sliver_url = $delete_sliver_base_url . $query;
    $sliver_status_url = $sliver_status_base_url . $query;
    $sliceabac_url = $abac_url . $query;
    $sliceflack_url = $flack_url . $query;
    $listres_url = $listres_base_url . $query;
    $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
    $expiration_db = $slice[SA_ARGUMENT::EXPIRATION];
    $expiration = dateUIFormat($expiration_db);
    $slice_project_id = $slice[SA_ARGUMENT::PROJECT_ID];

    // Determine privileges to this slice for this user
    $add_slivers_privilege = $user->isAllowed(SA_ACTION::ADD_SLIVERS,
					      CS_CONTEXT_TYPE::SLICE, 
					      $slice_id);
    $add_slivers_disabled = "";
    if(!$add_slivers_privilege) { $add_slivers_disabled = $disabled; }
    
    $delete_slivers_privilege = $user->isAllowed(SA_ACTION::DELETE_SLIVERS,
						 CS_CONTEXT_TYPE::SLICE, 
						 $slice_id);
    $delete_slivers_disabled = "";
    if(!$delete_slivers_privilege) { $delete_slivers_disabled = $disabled; }

    $renew_slice_privilege = $user->isAllowed(SA_ACTION::RENEW_SLICE,
					      CS_CONTEXT_TYPE::SLICE, 
					      $slice_id);
    $renew_disabled = "";
    if(!$renew_slice_privilege) { $renew_disabled = $disabled; }

    $lookup_slice_privilege = $user->isAllowed(SA_ACTION::LOOKUP_SLICE, 
					       CS_CONTEXT_TYPE::SLICE, 
					       $slice_id);

    // Lookup the project for this project ID
    $project = lookup_project($pa_url, $user, $slice_project_id);

    $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
    $slice_owner_name = $user->fetchMember($slice_owner_id)->prettyName();
    print "<tr>"
      . ("<td><a href=\"$slice_url\">" . htmlentities($slice_name)
         . "</a></td>");
    print "<td><a href=\"project.php?project_id=$slice_project_id\">" . htmlentities($slice_project_name) . "</a></td>";
    print "<td>" . htmlentities($expiration) . "</td>";
    print "<td><a href=\"slice-member.php?slice_id=$slice_id&member_id=$slice_owner_id\">" . htmlentities($slice_owner_name) . "</a></td>";
    print ("<td><button $add_slivers_disabled onClick=\"window.location='$sliceresource_url'\"><b>Add Resources</b></button>");
    print ("<button onClick=\"window.location='$sliver_status_url'\"><b>Resource Status</b></button>");
    // FIXME: List Resources
    print ("<button onClick=\"window.location='$listres_url'\"><b>Manifest</b></button>");
    print ("<button $delete_slivers_disabled onClick=\"window.location='$delete_sliver_url'\"><b>Delete Resources</b></button>");
  print "<button $add_slivers_disabled onClick=\"window.open('$sliceflack_url')\"><image width=\"40\" src=\"http://groups.geni.net/geni/attachment/wiki/ProtoGENIFlashClient/pgfc-screenshot.jpg?format=raw\"/><br/>Launch Flack</button></td>\n";
    if ($portal_enable_abac) {
      print "<td><button onClick=\"window.location='$sliceabac_url'\"><b>Get ABAC Credential</b></button></td>";
    }
    print "</tr>\n";
  }
  print "</table>\n";
} else {
  if (isset($project_id) && uuid_is_valid($project_id)) {
    print "<i>You do not have access to any slices in this project.</i><br/>\n";
  } else {
    print "<i>You do not have access to any slices.</i><br/>\n";
  }
}

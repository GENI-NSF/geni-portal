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

if (! isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
}
if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}

// FIXME: This looks up slices OWNED by this user
if (isset($project_id) && uuid_is_valid($project_id)) {
  $slice_ids = lookup_slices_by_project_and_owner($sa_url, $project_id, $user->account_id);
} else {
  $slice_ids = lookup_slices_by_owner($sa_url, $user->account_id);
}
if (count($slice_ids) > 0) {
  print "\n<table border=\"1\">\n";
  print ("<tr><th>Name</th><th>Expiration</th><th>URN</th>"
	 . "<th>Project</th><th>Owner</th>"
         . "<th>Credential</th><th>Resources</th><th>Sliver Status</th>"
         . "<th>Delete Sliver</th>");
  if ($portal_enable_abac) {
    print "<th>ABAC Credential</th></tr>\n";
  }
  $base_url = relative_url("slicecred.php?");
  $slice_base_url = relative_url("slice.php?");
  $resource_base_url = relative_url("sliceresource.php?");
  $delete_sliver_base_url = relative_url("sliverdelete.php?");
  $sliver_status_base_url = relative_url("sliverstatus.php?");
  $abac_url = relative_url("sliceabac.php?");

  foreach ($slice_ids as $slice_id) {
    // FIXME: Add PROJECT_ID, OWNER_ID
    if (! uuid_is_valid($slice_id)) {
      error_log("tool-slices: invalid slice_id from lookup_slices");
      continue;
    }
    $slice = lookup_slice($sa_url, $slice_id);
    $slice_id = $slice[SA_ARGUMENT::SLICE_ID];
    $args['slice_id'] = $slice_id;
    $query = http_build_query($args);
    $slicecred_url = $base_url . $query;
    $slice_url = $slice_base_url . $query;
    $sliceresource_url = $resource_base_url . $query;
    $delete_sliver_url = $delete_sliver_base_url . $query;
    $sliver_status_url = $sliver_status_base_url . $query;
    $sliceabac_url = $abac_url . $query;
    $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
    $expiration = $slice[SA_ARGUMENT::EXPIRATION];
    $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
    $slice_project_id = $slice[SA_ARGUMENT::PROJECT_ID];
    $project = lookup_project($pa_url, $slice_project_id);
    $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
    $slice_owner_name = geni_loadUser($slice_owner_id)->prettyName();
    print "<tr>"
      . ("<td><a href=\"$slice_url\">" . htmlentities($slice_name)
         . "</a></td>")
      . "<td>" . htmlentities($expiration) . "</td>"
      . "<td>" . htmlentities($slice_urn) . "</td>"
      . "<td><a href=\"project.php?id=$slice_project_id\">" . htmlentities($slice_project_name) . "</a></td>"
      . "<td>" . htmlentities($slice_owner_name) . "</td>"
      . ("<td><a href=\"$slicecred_url\">Get Credential</a></td>")
      . ("<td><a href=\"$sliceresource_url\">Get Resources</a></td>")
      . ("<td><a href=\"$sliver_status_url\">Sliver Status</a></td>")
      . ("<td><a href=\"$delete_sliver_url\">Delete Sliver</a></td>");
    if ($portal_enable_abac) {
      print "<td><a href=\"$sliceabac_url\">Get ABAC Credential</a></td>";
    }
    print "</tr>\n";
  }
  print "</table>\n";
} else {
  print "<i>No slices.</i><br/>\n";
}

/* Only show create slice link if user has appropriate privilege. */
if ($user->privSlice()) {
  if (isset($project_id)) {
    print "<a href=\"";
    print relative_url("createslice?project_id=$project_id");
    print "\">Create a new slice</a><br/>\n";
  }
}

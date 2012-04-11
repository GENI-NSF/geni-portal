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
require_once("sa_client.php");
require_once("sa_constants.php");

show_header('GENI Portal: Slices', $TAB_SLICES);
$user = geni_loadUser();
?>
<h1>Existing Slices</h1>
<?php


// FIXME: hardcoding project to 'geni'!
$project_id = 'geni';


$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$slice_ids = lookup_slices($sa_url, $project_id);
if (count($slice_ids) > 0) {
  print "\n<table border=\"1\">\n";
  print ("<tr><th>Name</th><th>Expiration</th><th>URN</th>"
         . "<th>Credential</th><th>Resources</th><th>Delete Sliver</th>");
  if ($portal_enable_abac) {
    print "<th>ABAC Credential</th></tr>\n";
  }
  $base_url = relative_url("slicecred.php?");
  $resource_base_url = relative_url("sliceresource.php?");
  $delete_sliver_base_url = relative_url("sliverdelete.php?");
  $abac_url = relative_url("sliceabac.php?");

  foreach ($slice_ids as $slice_id) {
    $slice = lookup_slice($sa_url, $slice_id);
    $slice_id = $slice[SA_ARGUMENT::SLICE_ID];
    $args['id'] = $slice_id;
    $query = http_build_query($args);
    $slicecred_url = $base_url . $query;
    $sliceresource_url = $resource_base_url . $query;
    $delete_sliver_url = $delete_sliver_base_url . $query;
    $sliceabac_url = $abac_url . $query;
    $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
    $expiration = $slice[SA_ARGUMENT::EXPIRATION];
    $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
    print "<tr>"
      . ("<td><a href=\"$sliceresource_url\">" . htmlentities($slice_name)
         . "</a></td>")
      . "<td>" . htmlentities($expiration) . "</td>"
      . "<td>" . htmlentities($slice_urn) . "</td>"
      . ("<td><a href=\"$slicecred_url\">Get Credential</a></td>")
      . ("<td><a href=\"$sliceresource_url\">Get Resources</a></td>")
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
  print "<a href=\""
    . relative_url("createslice")
    . "\">Create a new slice</a>";
}
?>
<?php
include("footer.php");
?>

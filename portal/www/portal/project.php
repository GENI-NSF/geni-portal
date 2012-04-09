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
show_header('GENI Portal: Projects', $TAB_PROJECTS);
$user = geni_loadUser();
$project = "<None>";
if (array_key_exists("id", $_GET)) {
  $project = $_GET['id'];
}
print "<h1>GENI Project: " . $project . "</h1>\n";
$edit_url = 'edit-project.php?id='.$project;
?>
<b>Name</b>: Foo<br/>
<b>Warning: Project name is public</b><br/>
<b>Lead</b>: Joe<br/>
<b>Project purpose</b>: do stuff<br/>
<b>Project email</b>: <a href="mailto:">project21@geni.net</a><br/>
<b>Other static info</b>: etc<br/>
<?php
print '<a href='.$edit_url.'>Edit Project</a><br/>';
?>
<h2>Project slices:</h2>
<?php
$slices = fetch_slices($user->account_id);
if (count($slices) > 0) {
  print "\n<table border=\"1\">\n";
  print "<tr><th>Name</th><th>Expiration</th><th>URN</th><th>Credential</th><th>Resources</th>";
  if ($portal_enable_abac) {
    print "<th>ABAC Credential</th></tr>\n";
  }
  $base_url = relative_url("slicecred.php?");
  $resource_base_url = relative_url("sliceresource.php?");
  $slice_base_url = relative_url("slice.php?");
  $abac_url = relative_url("sliceabac.php?");
  foreach ($slices as $slice) {
    $slice_id = $slice['slice_id'];
    $args['id'] = $slice_id;
    $query = http_build_query($args);
    $slicecred_url = $base_url . $query;
    $sliceresource_url = $resource_base_url . $query;
    $slice_url = $slice_base_url . $query;
    $sliceabac_url = $abac_url . $query;
    $slice_name = $slice['name'];
    print "<tr>"
      . ("<td><a href=\"$slice_url\">$slice_name</a></td>")
      . "<td>" . htmlentities($slice['expiration']) . "</td>"
      . "<td>" . htmlentities($slice['urn']) . "</td>"
      . ("<td><a href=\"$slicecred_url\">Get Credential</a></td>")
      . ("<td><a href=\"$sliceresource_url\">Get Resources</a></td>");
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
    . "\">Create a new slice</a><br/>\n";
}
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
<tr><td><a href="/project-member.php?id=joe">Joe</a></td><td>Lead</td></tr>
</table>

<?php
  print "<br/><a href=\"mailto:\">Contact the project leader</a><br/>\n";
?>

<h2>Recent Project Actions</h2>
[stuff goes here...]<br/><br/>


<?php
if ($user->privAdmin()) {
  print "<a href=\"delete-project.php?id=" . $project . "\">Delete Project " . $project . "</a><br/>\n";
}
include("footer.php");
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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

require_once("settings.php");
require_once("user.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';

$all_rspecs = fetchRSpecMetaData($user);
$my_rspecs = array();
$public_rspecs = array();
$me = $user->account_id;

function cmp($a,$b) {
  return strcmp(strtolower($a['name']),strtolower($b['name']));
}

// Generate a list of my RSpecs and a list of public RSpecs
foreach ($all_rspecs as $rspec) {
  $owner = $rspec['owner_id'];
  if ($owner == $me) {
    $my_rspecs[] = $rspec;
  } else {
    $public_rspecs[] = $rspec;
  }
}

/* Sort the rspecs by name */
usort($my_rspecs,"cmp");
usort($public_rspecs,"cmp");

/* Display starts here. */
print("<h2>Manage Resource Specifications (RSpecs)</h2>\n");
print("<p>From this page you can ");
print '<a href="rspecupload.php">upload a new RSpec</a>';
print ", ";
print '<a href="#privateRSpecs">manage RSpecs</a>';
print " you have uploaded to the portal, and ";
print '<a href="#publicRSpecs">view RSpecs</a>';
print " that other users have publicly shared.</p>";
print "<p>Currently you can not edit existing RSpecs, if you would like to change one ";
print "of your existing RSpecs please delete it and upload a new version.</p>";
/* Show the table of existing private RSpecs. */
print '<a name="privateRSpecs"></a>';
print "<h3>My Private RSpecs</h3>\n";
rspec_table_header();
foreach ($my_rspecs as $rspec) {
  if ($rspec['visibility'] === "private")
    display_rspec($rspec);
}
rspec_table_footer();

/* Show the table of existing public but editable RSpecs. */
print "<h3>My Public RSpecs</h3>\n";
rspec_table_header();
foreach ($my_rspecs as $rspec) {
  if ($rspec['visibility'] === "public")
    display_rspec($rspec);
}
rspec_table_footer();

print '<a name="publicRSpecs"></a>';
print("<h3>Public RSpecs that other users have shared</h3>\n");

/* Show the table of public RSpecs. */
rspec_table_header(True);
foreach ($public_rspecs as $rspec) {
  display_rspec($rspec, True);
}
rspec_table_footer();

//include("footer.php");

/* ---------- */
function rspec_table_header($public=False) {
  print "<table>\n";
  if ($public) {
     $columns = array("Name", "Description", "Visibility", "View",
          "Download");
  } else {
     $columns = array("Name", "Description", "Visibility", "Edit", "View",
     	  "Download", "Delete");
  }
  print "<tr>";
  foreach ($columns as $c) {
    print "<th>$c</th>";
  }
  print "</tr>\n";
}
function display_rspec($rspec, $public=False) {
  // Customize these with the RSpec id.
  $id = $rspec['id'];
  if (! $public){
      $edit_btn = '<button disabled="disabled">Edit</button>';
  } 
  $view_url = "rspecview.php?id=$id";
  $view_btn = ("<button onClick=\"window.location='$view_url'\">View</button>");
  $download_url = "rspecdownload.php?id=$id";
  $download_btn = "<button onClick=\"window.location='$download_url'\">Download</button>";
  if (! $public){
     $delete_url = "rspecdelete.php?id=$id";
     $delete_btn = "<button onClick=\"window.location='$delete_url'\">Delete</button>";
  }
  if ($public) {
    $columns = array($rspec['name'],
          $rspec['description'],
          $rspec['visibility'],
          $view_btn,
          $download_btn);
  } else {
    $columns = array($rspec['name'],
          $rspec['description'],
          $rspec['visibility'],
          $edit_btn,
          $view_btn,
          $download_btn,
          $delete_btn);
  }
  print "<tr>";
  foreach ($columns as $c) {
    print "<td>$c</td>";
  }
  print "</tr>\n";
}
function rspec_table_footer() {
  print "</table>\n";
}
?>

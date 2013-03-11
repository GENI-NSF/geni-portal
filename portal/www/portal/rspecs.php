<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
require_once("header.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';


$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$all_rspecs = fetchRSpecMetaData($user);
$my_rspecs = array();
$public_rspecs = array();
$me = $user->account_id;

// Generate a list of my RSpecs and a list of public RSpecs
foreach ($all_rspecs as $rspec) {
  $owner = $rspec['owner_id'];
  if ($owner == $me) {
    $my_rspecs[] = $rspec;
  } else {
    $public_rspecs[] = $rspec;
  }
}


/* Display starts here. */
show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
print("<h2>Manage My Resource Specifications</h2>\n");
print "You can ";
print "<button onClick=\"window.location='rspecupload.php'\">"
    . "upload a new resource specification (RSpec)</button>\n";
print " or edit your existing RSpecs.";

/* Show the table of existing RSpecs. */
rspec_table_header();
foreach ($my_rspecs as $rspec) {
  display_rspec($rspec);
}
rspec_table_footer();

print("<h2>View Public Resource Specifications</h2>\n");
print "You can view or download existing public Resource Specifications (RSpecs).";

/* Show the table of public RSpecs. */
rspec_table_header(True);
foreach ($public_rspecs as $rspec) {
  display_rspec($rspec, True);
}
rspec_table_footer();

include("footer.php");
exit();

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

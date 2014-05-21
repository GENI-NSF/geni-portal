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
$my_public = array();
$my_private = array();
$public_rspecs = array();
$public_owners = array();
$me = $user->account_id;

function cmp($a,$b) {
  return strcmp(strtolower($a['name']),strtolower($b['name']));
}

// Generate a list of my RSpecs and a list of public RSpecs
foreach ($all_rspecs as $rspec) {
  $owner = $rspec['owner_id'];
  if ($owner == $me) {
    if ($rspec['visibility'] === 'private') {
      $my_private[] = $rspec;
    } else if ($rspec['visibility'] === 'public') {
      $my_public[] = $rspec;
    }
  } else {
    $public_rspecs[] = $rspec;
    $public_owners[] = $owner;
  }
}

/* Sort the rspecs by name */
usort($my_private,"cmp");
usort($my_public,"cmp");
usort($public_rspecs,"cmp");
$public_owners = array_unique($public_owners);

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
/* Find member info for public rspecs */
$details = lookup_member_details($ma_url, $user, $public_owners);
$owners = array();
foreach ($details as $member_id => $record) {
  $member = new Member($member_id);
  $member->init_from_record($record);
  $owners[$member_id] = $member;
}

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
if (count($my_private) > 0) {
  rspec_table_header();
  foreach ($my_private as $rspec) {
    display_rspec($rspec, $owners);
  }
  rspec_table_footer();
} else {
  print "<p><i>None</i></p>\n";
}
/* Show the table of existing public but editable RSpecs. */
print "<h3>My Public RSpecs</h3>\n";
if (count($my_public) > 0) {
  rspec_table_header();
  foreach ($my_public as $rspec) {
    display_rspec($rspec, $owners);
  }
  rspec_table_footer();
} else {
  print "<p><i>None</i></p>\n";
}

print '<a name="publicRSpecs"></a>';
print("<h3>Public RSpecs that other users have shared</h3>\n");

/* Show the table of public RSpecs. */
rspec_table_header(True);
foreach ($public_rspecs as $rspec) {
  display_rspec($rspec, $owners, True);
}
rspec_table_footer();

//include("footer.php");

/* ---------- */
function rspec_table_header($public=False) {
  print "<table>\n";
  if ($public) {
     $columns = array("Name", "Description", "Owner", "&nbsp;", "&nbsp;");
  } else {
     $columns = array("Name", "Description", "&nbsp;", "&nbsp;", "&nbsp;",
                      "&nbsp;");
  }
  print "<tr>";
  foreach ($columns as $c) {
    print "<th>$c</th>";
  }
  print "</tr>\n";
}
function display_rspec($rspec, $owners, $public=False) {
  // Customize these with the RSpec id.
  $id = $rspec['id'];
  $view_url = "rspecview.php?id=$id";
  $view_btn = ("<button onClick=\"window.location='$view_url'\">View</button>");
  $download_url = "rspecdownload.php?id=$id";
  $download_btn = "<button onClick=\"window.location='$download_url'\">Download</button>";
  if (! $public){
    $edit_url = "rspecedit.php?id=$id";
    $edit_btn = "<button onClick=\"window.location='$edit_url'\">Edit</button>";
    $delete_url = "rspecdelete.php?id=$id";
    $delete_btn = "<button onClick=\"window.location='$delete_url'\">Delete</button>";
  }
  if ($public) {
    $owner_id = $rspec['owner_id'];
    if (array_key_exists($owner_id, $owners)) {
      $owner = $owners[$owner_id];
      $mailto = ('<a href="mailto:' . $owner->email_address . '">'
                 . $owner->prettyName() . '</a>');
    } else {
      /* Some rspecs have no owner because they were pre-loaded at the
         advent of the portal. Mark these as owned by GENI. */
      $mailto = '<a href="mailto:help@geni.net">help@geni.net</a>';
    }
    $columns = array($rspec['name'],
          $rspec['description'],
          $mailto,
          $view_btn,
          $download_btn);
  } else {
    $columns = array($rspec['name'],
          $rspec['description'],
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

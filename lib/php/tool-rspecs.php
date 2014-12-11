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

if (! isset($all_ams)) {
  $am_list = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $all_ams = array();
  foreach ($am_list as $am) 
  {
    $single_am = array();
    $service_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
    $single_am['name'] = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
    $single_am['url'] = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
    $single_am['urn'] = $am[SR_TABLE_FIELDNAME::SERVICE_URN];
    $all_ams[$service_id] = $single_am;
  }   
}

// error_log("ALL_AMS = " . print_r($all_ams, true));

?>

<script type="text/javascript">
  var allAms = <?php echo json_encode($all_ams); ?>;
</script>

<?php



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
$owners = ma_lookup($ma_url, $user, $public_owners);

/* Display starts here. */
include('tool-rspecs.js');
print '<script src="https://www.emulab.net/protogeni/jacks-stable/js/jacks"></script>';
print "<div id='jacksEditorContainer' class='jacks' style='background-color: white; display:none;'></div>";
print "<div id='jacksContainer' class='jacks' style='background-color: white; display:none;'></div>";
print("<h2>Manage Resource Specifications (RSpecs)</h2>\n");
print("<p>From this page you can ");
print '<b><a href="rspecupload.php">upload a new RSpec</a></b>';
print ", ";
print '<b>manage RSpecs</b>';
print " you have uploaded to the portal, and ";
print '<b>view RSpecs</b>';
print " that other users have publicly shared.</p>";
/* Show the table of existing private RSpecs. */
print '<a name="privateRSpecs"></a>';
print "<h3>My Private RSpecs</h3>\n";
if (count($my_private) > 0) {
  rspec_table_header("my_private_rspecs", True);
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
  rspec_table_header("my_public_rspecs", True);
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
rspec_table_header("public_rspecs", True, True);
foreach ($public_rspecs as $rspec) {
  display_rspec($rspec, $owners, True);
}
rspec_table_footer();

//include("footer.php");

/* ---------- */
function rspec_table_header($table_id, $searchable=False, $public=False) {
  if($searchable) {
      /* datatables.net (for sortable/searchable tables) */
      echo '<script type="text/javascript">';
      echo '$(document).ready( function () {';
      echo '  $(\'#' . $table_id . '\').DataTable({paging: false});';
      echo '} );';
      echo '</script>';
  }

  print "<table id='$table_id'><thead>\n";
  if ($public) {
     $columns = array("Name &#x2191;&#x2193;", "Description &#x2191;&#x2193;",
                    "Owner &#x2191;&#x2193;", "&nbsp;", "&nbsp;");
  } else {
     $columns = array("Name &#x2191;&#x2193;", "Description &#x2191;&#x2193;", 
                    "&nbsp;", "&nbsp;", "&nbsp;", "&nbsp;", "&nbsp;");
  }
  print "<tr>";
  foreach ($columns as $c) {
    print "<th>$c</th>";
  }
  print "</tr></thead><tbody>\n";
}

/**
 * Find owner information that is external to the RSpec record. In
 * June, 2014 we are transitioning to storing owner information
 * directly in the RSpec record. This function will no longer be
 * needed once that transition is complete.
 *
 * Delete this after August, 2014 if it is no longer needed. That is,
 * if all the records in the RSpec table have owner_name and
 * owner_email set.
 */
function rspec_owner_info($rspec, $owners, &$addr, &$pretty_name) {
  // Clear out the pass-by-reference values
  $addr = NULL;
  $pretty_name = NULL;
  $owner_id = $rspec['owner_id'];
  if (array_key_exists($owner_id, $owners)) {
    $owner = $owners[$owner_id];
    if (isset($owner->email_address)) {
      $addr = $owner->email_address;
    }
    $pretty_name = $owner->prettyName();
  } else {
    /* Some rspecs have no owner because they were pre-loaded at the
       advent of the portal. Mark these as owned by GENI. */
    $addr = 'help@geni.net';
    $pretty_name = 'help@geni.net';
  }
  if (! $addr) {
    /* If owner email is not available, use help */
    $addr = 'help@geni.net';
    $pretty_name = 'help@geni.net';
  }
  // Return value is not important at this time.
  return NULL;
}

function display_rspec($rspec, $owners, $public=False) {

  // Customize these with the RSpec id.
  $id = $rspec['id'];
  $name = $rspec['name'];
  
  $view_url = "rspecview.php?id=$id";
  $view_btn = ("<button onClick=\"showViewerContainer($id, '$name')\" title='view'>View</button>");
  $download_url = "rspecdownload.php?id=$id";
  $download_btn = "<button onClick=\"window.location='$download_url'\" title='Download'>Download</button>";
  if ($public) {
    $addr = $rspec['owner_email'];
    $pretty_name = $rspec['owner_name'];
    if (! ($pretty_name && $addr)) {
      /* Either owner name or email is not set in the rspec table, access
         user info via the member authority.*/
      rspec_owner_info($rspec, $owners, $addr, $pretty_name);
    }

    $rspec_name = $rspec['name'];
    $subject = 'About GENI Portal rspec: ' . $rspec['name'];
    $addr = $addr . '?subject=' . $subject;
    $mailto = '<a href="mailto:' . $addr . '">' . $pretty_name . '</a>';
    $columns = array($rspec['name'],
          $rspec['description'],
          $mailto,
          $view_btn,
          $download_btn);
  } else {
    $sn = $rspec['name'];
    $desc = $rspec['description'];
    $visibility = $rspec['visibility'];
    $edit_query = array('rspec_id' => $id,
                        'group1' => $visibility,
                        'description' => $desc,
                        'name' => $sn);
    global $all_ams;
    $jacks_btn = ("<button onClick=\"showEditorContainer($id, '$name')\" title='jacks_view'>Edit RSpec</button>");
    $edit_url = "rspecupload.php?" . http_build_query($edit_query);
    $edit_btn = "<button onClick=\"window.location='$edit_url'\" title='Edit'>Edit Info</button>";
    $delete_url = "rspecdelete.php?id=$id";
    $delete_btn = "<button onClick=\"window.location='$delete_url'\" title='Delete'>Delete</button>";
    $columns = array($rspec['name'],
          $rspec['description'],
          $edit_btn,
	  $jacks_btn,
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
  print "</tbody></table>\n";
}
?>

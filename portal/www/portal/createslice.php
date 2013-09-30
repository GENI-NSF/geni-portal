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

// Form for creating a slice. Submit to self.

require_once("settings.php");
require_once("db-util.php");
require_once("file_utils.php");
require_once("util.php");
require_once("user.php");
require_once('pa_constants.php');
require_once('pa_client.php');
require_once("sr_constants.php");
require_once("sr_client.php");
require_once("sa_client.php");
require_once('sa_constants.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// error_log("REQ = " . print_r($_REQUEST, true));

$slice_name = NULL;
$project_id = NULL;
$message = NULL;
$slice_description = '';
include("tool-lookupids.php");
if (array_key_exists("slice_name", $_REQUEST)) {
  $slice_name = $_REQUEST['slice_name'];
}
if (array_key_exists("slice_description", $_REQUEST)) {
  $slice_description = $_REQUEST['slice_description'];
}

if (is_null($project_id) || $project_id == '') {
  error_log("createslice: invalid project_id from GET");
  relative_redirect("home.php");
}

if (!is_null($slice_name) && ($slice_name != '') && !is_valid_slice_name($slice_name)) {
  error_log("createslice: invalid slice name from GET: " . $slice_name);
  $_SESSION['lasterror'] = "Invalid slice name '$slice_name': Use only alphanumeric plus hyphen (no leading hyphen), and at most 19 characters.";
  $slice_name = NULL;
  //  relative_redirect("home.php");
}

function sa_create_slice($user, $slice_name, $project_id, $project_name, $description='')
{
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  $owner_id = $user->account_id;
  $result = create_slice($sa_url, $user, $project_id, $project_name,
                         $slice_name, $owner_id, $description);
  return $result;
}

// Do we have all the required params?
if ($slice_name) {
  // Create the slice...
  $result = sa_create_slice($user, $slice_name, $project_id, $project_name, $slice_description);
  if (! $result) {
    error_log("Create Slice failed for slice $slice_name");
    $_SESSION['lasterror'] = "Slice creation failed for slice $slice_name";
    relative_redirecto('home.php');
  } else {
    /* $pretty_result = print_r($result, true); */
    /* error_log("sa_create_slice result: $pretty_result\n"); */
 
    // Redirect to this slice's page now...
    $slice_id = $result[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    
    $_SESSION['lastmessage'] = "Created slice $slice_name";
    
    relative_redirect('slice.php?slice_id='.$slice_id);
  }
} // else if came from the createslice page then print an error or something

// If here, present the form
require_once("header.php");
show_header('GENI Portal: Slices', '');
if ($message) {
  // It would be nice to put this in red...
  print "<i>" . $message . "</i>\n";
}
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
print "<h1>Create New Slice</h1>\n";
print "<p>A GENI slice is a container for reserving and managing a set of GENI resources.</p>\n";
print '<form method="GET" action="createslice">';
print "\n";
print "<input type='hidden' name='project_id' value='$project_id'/>";
print "\n";
print "<table>";
print "<tr><th>Project name</th><td><b>$project_name</b></td></tr>\n";
print '<tr><th>Slice name</th>';
print "\n";
print '<td><input type="text" name="slice_name"/> -- Required</td>'; // FIXME: Note legal characters, length?
print "</tr>\n";
print '<tr><th>Slice description</th>';
print "\n";
print "<td><input type='text' name='slice_description' value='$slice_description'/></td>";
print "</tr></table>\n";
print '<p><b>Note</b>: Slice names must not contain whitespace. Use at most 19 alphanumeric characters or hyphen (no leading hyphen) : "a-zA-Z0-9-".</b></p>';
echo '<p><b>Note: Slice names are public and must be unique across the GENI Clearinghouse</b></p>';
print '<p><input type="submit" value="Create slice"/>';
print "\n";
print "<input type=\"button\" value=\"Cancel\" onClick=\"history.back(-1)\"/></p>\n";
print '</form>';
print "\n";
?>
<?php
include("footer.php");
?>

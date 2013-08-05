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

// Form for adding a note on a slice. Submit to self.

require_once("settings.php");
require_once('portal.php');
require_once("util.php");
require_once("user.php");
require_once("sr_constants.php");
require_once("sr_client.php");
require_once("sa_client.php");
require_once("logging_client.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$slice_name = NULL;
$project_id = NULL;
$message = NULL;
include("tool-lookupids.php");
if (array_key_exists("note", $_REQUEST)) {
  $note = $_REQUEST["note"];

  if (! is_null($note) && trim($note) != "") {
    $note = trim($note);
    // Log the note
    if (! isset($log_url)) {
      $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
    }
    $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, $slice_id);
    log_event($log_url, Portal::getInstance(), "Note on slice $slice_name: " . $note, 
	      $attributes, $user->account_id);

    require_once("header.php");
    show_header('GENI Portal: Slices', '');

    include("tool-breadcrumbs.php");
    print "<h1>Added Note on Slice <i>$slice_name</i></h1>\n";
    print "<table><tr><th>Slice</th><td>$slice_name</td></tr>\n";
    print "<tr><th>Note</th><td>$note</td></tr></table>\n";
    include("footer.php");
    exit(0);
  }
}

require_once("header.php");
show_header('GENI Portal: Slices', '');
include("tool-breadcrumbs.php");
print "<h1>Add Note on Slice <i>$slice_name</i></h1>\n";
print "<p>Add a note about what you are doing in this slice.</p>\n";
print "<table><tr><th>Slice</th><td>$slice_name</td></tr>\n";
print '<form method="GET" action="add-slice-note.php">';
print "<input type='hidden' name='slice_id' value='$slice_id'/>";
print "<tr><th>Note</th><td><textarea cols='80' rows='4' name=\"note\"></textarea/td></tr></table>\n";
print '<p><input type="submit" value="Create slice note"/>';
print "\n";
print "<input type=\"button\" value=\"Cancel\" onClick=\"history.back(-1)\"/></p>\n";
print '</form>';
include("footer.php");


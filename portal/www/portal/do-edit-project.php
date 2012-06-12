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
require_once('util.php');
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Projects', $TAB_PROJECTS);

$isnew = true;
$name = "";
$purpose = "";
$lead_id = $user->account_id;
$newlead = $lead_id;
unset($project);
include("tool-lookupids.php");
if (isset($project)) {
  $isnew = false;
}

if (array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, $_REQUEST)) {
  // FIXME validate inputs
  $name = $_REQUEST[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
}
if (array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE, $_REQUEST)) {
  // FIXME validate inputs
  $purpose = $_REQUEST[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
}

if (array_key_exists("newlead", $_REQUEST)) {
  // FIXME validate inputs
  $newlead = $_REQUEST['newlead'];
  if (is_null($newlead) || $newlead == '' || ! uuid_is_valid($newlead)) {
    $newlead = $lead_id;
  }
}
//print "ID=$project_id, Name=$name, Purpose=$purpose, newlead=$newlead.<br/>\n";

// FIXME: If got a newlead diff from in DB, then send a message to them to accept it

$result = null;
if ($isnew) {
  // Re-check authorization?
  // Auto?
  // Ensure project name is unique?!
  $project_id = create_project($pa_url, $name, $lead_id, $purpose);
  if ($project_id == "-1" || ! uuid_is_valid($project_id)) {
    error_log("do-edit-project create_project got project_id $project_id");
    $result = "Error";
  } else {
    $result = "New";
  }
  //  print "Created project, got ID: $project_id<.br/>\n";
  // Return on error?
} else {
  //  error_log("about to update project");

  // FIXME: Diff new vals from old?

  $result = update_project($pa_url, $project_id, $name, $purpose);
  if ($result == '') {
    error_log("update_project failed? empty...");
  } else {
    $result = "updated";
  }

  if ($project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] != $newlead) {
    $result2 = change_lead($pa_url, $project_id, $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID], $newlead);
    if ($result2 == '') {
      $result = $result . "; Project Lead change failed? empty...?";
    } else {
      $result = $result . "; Project Lead change: $result2";
    }
  }
  //  print "Edited project $project, got result: $result.<br/>\n";
  // Return on error?
}

$_SESSION['lastmessage'] = "Edited project $project_name: $result";
relative_redirect('project.php?project_id='.$project_id . "&result=" . $result);

include("footer.php");
?>

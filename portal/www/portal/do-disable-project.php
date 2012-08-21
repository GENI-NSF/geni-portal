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
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('pa_constants.php');
require_once('pa_client.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-lookupids.php");

if (isset($project) && ! is_null($project)) {
  // FIXME: Do anything to slices first? Members?
  $result = delete_project($pa_url, $user, $project_id);
  if (! $result) {
    error_log("Failed to Disable project $project_id: $result");
  }
} else {
  error_log("Didnt find to disable project $project_id");
}
// FIXME: remove the project from the DB
// Invalidate credentials?
// Remove slices from the DB?
// FIXME

$_SESSION['lastmessage'] = "Asked to disable project $project_name - NOT IMPLEMENTED";

relative_redirect('projects.php');

include("footer.php");
?>

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
show_header('GENI Portal: Projects', $TAB_PROJECTS);
$user = geni_loadUser();
$project = "None";
$isnew = true;
$name = "";
$email = "";
$purpose = "";
$lead = $user->account_id;
$newlead = "";
if (array_key_exists("id", $_REQUEST)) {
  // FIXME validate inputs
  $project = $_REQUEST['id'];
  $isnew = false;
}
if (array_key_exists("Name", $_REQUEST)) {
  // FIXME validate inputs
  $name = $_REQUEST['Name'];
}
if (array_key_exists("Purpose", $_REQUEST)) {
  // FIXME validate inputs
  $purpose = $_REQUEST['Purpose'];
}
if (array_key_exists("newlead", $_REQUEST)) {
  // FIXME validate inputs
  $newlead = $_REQUEST['newlead'];
}
print "ID=$project, Name=$name, Purpose=$purpose, newlead=$newlead.<br/>\n";

// FIXME: If got a newlead diff from in DB, then send a message to them to accept it

$sr_url = get_sr_url();
print "SR: $sr_url<br/>\n";
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
print "PA: $pa_url\n";
if (! isset($pa_url) || $pa_url==null) {
  print "Got no PA!<br/>\n";
  $http_host = $_SERVER['HTTP_HOST'];
  $pa_url = "https://" . $http_host . "/pa/pa_controller.php";
  $result = register_service(SR_SERVICE_TYPE::PROJECT_AUTHORITY, $pa_url);
  print "Registered PA $pa_url: $result<br/>\n";
}
if ($isnew) {
  // Re-check authorization?
  // Auto?
  $project = create_project($pa_url, $name, $lead, $email, $purpose);
  print "Created project, got ID: $project<.br/>\n";
  // Return on error?
} else {
  $result = update_project($pa_url, $project, $name, $newlead, $email, $purpose);
  print "Edited project $project, got result: $result.<br/>\n";
  // Return on error?
}

//relative_redirect('project.php?id='.$project);

include("footer.php");
?>

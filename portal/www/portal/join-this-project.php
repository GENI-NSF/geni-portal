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
require("logging_constants.php");
require("logging_client.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once("pa_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");

// Send request to join this particular project

// Error if we don't have a project object
if (! isset($project) || is_null($project)) {
  error_log("No project set?");
  if (isset($project_id)) {
    show_header('GENI Portal: Projects', $TAB_PROJECTS);
    include("tool-breadcrumbs.php");
    print "<h2>Error Joining a project</h2>\n";
    print "Unknown project ID $project_id<br/>\n";
    print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
    include("footer.php");
    exit();
  } else {
    /* error_log("doing redirect when _REQUEST: "); */
    /* foreach (array_keys($_REQUEST) as $key) { */
    /*   error_log("   [" . $key . "] = " . $_REQUEST[$key]); */
    /* } */
    relative_redirect('join-project.php');
  }
}

show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-breadcrumbs.php");

$lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
$lead = geni_loadUser($lead_id);
$leadname = $lead->prettyName();

print "<h2>Join Project $project_name</h2>\n";
$error = null;
$message = null;
if (array_key_exists("message", $_REQUEST)) {
  $message = $_REQUEST["message"];
}

if (isset($message) && ! is_null($message) && (!isset($error) || is_null($error))) {
  // FIXME: Record the new request
  //  $request_id = create_request(CS_CONTEXT_TYPE::PROJECT, $project_id, RQ_REQUEST_TYPE::JOIN, $message);
  $request_id = '12345';

  // FIXME: sub handle-project-request.php with handle-project-request.php?project_id=$project_id&member_id=$user->account_id&request_id=$request_id
  $ind = strpos($message, "handle-project-request.php");
  $message = substr_replace($message, "handle-project-request.php?project_id=" . $project_id . "&member_id=" . $user->account_id . "&request_id=" . $request_id, $ind, strlen("handle-project-request.php"));

  // Log the request
  // contexts include project and member
  $context[LOGGING_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::PROJECT;
  $context[LOGGING_ARGUMENT::CONTEXT_ID] = $project_id;
  $context2[LOGGING_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::MEMBER;
  $context2[LOGGING_ARGUMENT::CONTEXT_ID] = $user->account_id;

  if (! isset($log_url)) {
    $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
    if (! isset($log_url) || is_null($log_url) || $log_url == '') {
      error_log("Found no Log Service in SR!'");
    }
  }

  $name = $user->prettyName();
  if (isset($log_url)) {
    log_event($log_url, "$name requested to join project $project_name", array($context, $context2), $user->account_id);
  }

  // Send the email
  $email = $user->email();
  mail($lead->prettyName() . "<" . $lead->email() . ">",
       "Join GENI project $project_name",
       $message,
       "Reply-To: $email" . "\r\n" . "From: $name <$email>");

  // Put up a page saying we sent the request
  print "<br/>\n";
  print "<b>Sent</b> request to join GENI project $project_name to $leadname.<br/><br/>\n";
  $lines = explode("\r\n", $message);
  print "<b>Message</b>: <br/>\n";
  foreach ($lines as $line) {
    print "$line<br/>\n";
  }
  include("footer.php");
  exit();
}

print "All GENI actions must be taken in the context of a project.<br/>\n";
print "On this page, you can request to join the project $project_name.<br/><br/> " 
  . "The project leader ($leadname) will be sent an email, to approve or deny your request.\n";
print "That email will have a link to a page where the leader can act on your request.\n";
print "When the project leader ($leadname) acts on your request, you will get an email " .
"notifying you whether your request was approved.\n";
print "Once approved, you can create a slice, or request to join an existing slice.<br/>\n";

print "<br/>\n";

//- Show info on the project, lead
print "<b>Project $project_name details</b>:<br/>\n";
print "<table>\n";
print "<tr><th>Project</th><th>Purpose</th><th>Lead</th></tr>\n";
print "<tr><td>";
print $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
print "</td><td>";
print $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
print "</td><td>";
print $leadname;
print "</td></tr>\n";
print "</table><br/>\n";

// Start form
//- Show text area for justification of your request
//- Show button in form: Click to Request to join this project
//- Emails the Project lead

if (isset($error) && ! is_null($error)) {
  print $error;
}

print "<form action=\"join-this-project.php?project_id=$project_id\">\n";
print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";
print "<b>Project join request message</b>:<br/>\n";
print "<textarea name='message' cols='60' rows='10'>Can I join project $project_name?
I think I need to do GENI research in your project.
I am a student in your lab.
To handle my request, go to the GENI Portal here: 
https://illyrica.gpolab.bbn.com/secure/handle-project-request.php

Thank you,\n";
print $user->prettyName();
print "</textarea><br/>\n";

print "<button type=\"submit\" value=\"submit\"><b>Send Join Request</b></button>\n";

print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print "</form>\n";

include("footer.php");
?>

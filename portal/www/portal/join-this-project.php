<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
require_once('portal.php');
require("logging_constants.php");
require("logging_client.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once('rq_client.php');
require_once('rq_constants.php');
require_once("pa_constants.php");
require_once("pa_client.php");
require_once("cs_constants.php");
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

$lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
$lead = $user->fetchMember($lead_id);
$leadname = $lead->prettyName();

// Get all the admins for this project, so we can email them as well
$admin_emails = array();

// FIXME: For now, we can't send emails to project admins
//
// $admins = get_project_members($sa_url, $user, $project_id, CS_ATTRIBUTE_TYPE::ADMIN);
//
//if ($admins and count($admins) > 0) {
//  foreach ($admins as $admin_res) {
//    $admin = $user->fetchMember($admin_res[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID]);
//    $admin_emails[] = $admin->prettyEmailAddress();
//    //    error_log("Adding admin " . $admin->prettyName());
//  }
//}
//

$error = null;
$message = null;
if (array_key_exists("message", $_REQUEST)) {
  $message = $_REQUEST["message"];
}

// Get the sa_url for accessing request information
if (!isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  if (!isset($sa_url) || is_null($sa_url) || $sa_url == '') {
    error_log("Found no Slice Authority Service");
  }
}

// confirm member is not already in this project
$pids = get_projects_for_member($sa_url, $user, $user->account_id, true);
if (isset($pids) && ! is_null($pids) && in_array($project_id, $pids)) {
  error_log($user->prettyName() . " already in project " . $project_id);
  $_SESSION['lasterror'] = "You are already in this project.";
  relative_redirect("project.php?project_id=$project_id");
}

// confirm member has not already requested to join this project
$rpids = get_requests_by_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, $project_id, RQ_REQUEST_STATUS::PENDING);
if (in_array($project_id, $rpids)) {
  error_log($user->prettyName() . " already requested to join project " . $project_id);
  $_SESSION['lasterror'] = "You already requested to join that project.";
  relative_redirect('home.php');
}

if (isset($message) && ! is_null($message) && (!isset($error) || is_null($error))) {
  $request_id = create_request($sa_url, $user, CS_CONTEXT_TYPE::PROJECT, $project_id, RQ_REQUEST_TYPE::JOIN, $message);

  // FIXME: sub handle-project-request.php with handle-project-request.php?project_id=$project_id&member_id=$user->account_id&request_id=$request_id
  //  $ind = strpos($message, "handle-project-request.php");
  $hostname = $_SERVER['SERVER_NAME'];
  $message .= "To handle my request, go to the GENI Portal here:
https://$hostname/secure/handle-project-request.php?project_id=$project_id&member_id=" . $user->account_id . "&request_id=$request_id

Remember that when you approve this request, you agree to take
responsibility for my use of GENI resources within the project. 
You should not approve unsolicited requests to join your project.

Thank you,\n" . $user->prettyName() . "\n";

//  $message = substr_replace($message, "handle-project-request.php?project_id=" . $project_id . "&member_id=" . $user->account_id . "&request_id=" . $request_id, $ind, strlen("handle-project-request.php"));

  // Log the request
  // contexts include project and member
  $project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT,
						  $project_id);
  $member_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER,
						  $user->account_id);
  $attributes = array_merge($project_attributes, $member_attributes);

  if (! isset($log_url)) {
    $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
    if (! isset($log_url) || is_null($log_url) || $log_url == '') {
      error_log("Found no Log Service in SR!'");
    }
  }

  $name = $user->prettyName();
  if (isset($log_url)) {
    log_event($log_url, Portal::getInstance(),
	      "$name requested to join project $project_name", 
	      $attributes);
  }

  // Send the email
  $prettyEmail = $user->prettyEmailAddress();
  $email = $user->email();
  if (count($admin_emails) > 0) {
    //error_log("Got admin_emails " . print_r($admin_emails, True));
    $cc = "Cc: " . implode(", ", $admin_emails) . "\r\n";
  } else {
    $cc = ""; // FIXME: Include portal-dev-admin?
  }
  $headers = "Reply-To: $email" . "\r\n" . $cc . "From: \"$name (via the GENI Portal)\" <www-data@gpolab.bbn.com>\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
  
  mail($lead->prettyEmailAddress(),
       "Request to Join GENI project $project_name",
       $message, $headers);
       
  // We could supply the -f arg to make bounces go back to this portal user,
  // but we probably want to know if the lead's email address is bouncing.
       //       "-f $email");

  // Put up a page saying we sent the request
  show_header('GENI Portal: Projects', $TAB_PROJECTS);
  include("tool-breadcrumbs.php");
  print "<h1>Join Project <i>$project_name</i></h1>\n";
  
  print "<p>\n";
  print "<b>Sent</b> request to join GENI project <b>$project_name</b> to <b>$leadname</b>.</p>\n";
  $lines = explode("\r\n", $message);
  print "<p><b>Message</b>: </p><pre style='margin-left:80px;'>\n";
  foreach ($lines as $line) {
    print "$line\n";
  }
  print "</pre>";
  include("footer.php");
  exit();
  
}

show_header('GENI Portal: Projects', $TAB_PROJECTS);
include("tool-breadcrumbs.php");
print "<h1>Join Project <i>$project_name</i></h1>\n";

print "<p>All GENI actions must be taken in the context of a project. \n";
print "On this page, you can request to join the project <i>$project_name</i>.</p> " 
  . "<p>The project lead ($leadname) will be sent an email, to approve or deny your request. \n";
print "That email will have a link to a page where the project lead can act on your request. \n";
print "When the project lead ($leadname) acts on your request, you will get an email " .
"notifying you whether your request was approved.\n";
print "Once approved, you can create a slice, or request to join an existing slice.</p>\n";
print "<p>When you ask to join a project, you are requesting that the project lead take responsibility for your use of GENI resources within that project. You should only request to join a project if the project lead knows who you are and wishes for you to join his or her project.</p>\n";

//- Show info on the project, lead
print "<h3>Project <i>$project_name</i> details:</h3>\n";
print "<table>\n";
print "<tr><th>Project</th><th>Purpose</th><th>Project Lead</th></tr>\n";
print "<tr><td>";
print $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
print "</td><td>";
print $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
print "</td><td>";
print $leadname;
print "</td></tr>\n";
// FIXME: Could add Project creation date?
print "</table>\n";

// Start form
//- Show text area for justification of your request
//- Show button in form: Click to Request to join this project
//- Emails the Project lead

if (isset($error) && ! is_null($error)) {
  print $error;
}

print "<form action=\"join-this-project.php?project_id=$project_id\">\n";
print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";
print "<h3>Project join request message:</h3>\n";
$hostname = $_SERVER['SERVER_NAME'];
print "<p><textarea name='message' cols='60' rows='5'>May I join GENI project '$project_name'?
I think I need to do GENI research in your project.\n</textarea></p>\n";
print "<h3>Message footer:</h3>\n";
print "<p><i>Remember that when you approve this request, you agree to take responsibility for my use of GENI resources within the project. You should not approve unsolicited requests to join your project.</i></p>\n";
print "<p><i>To handle my request, go to the GENI Portal here: <br/>
https://$hostname/secure/handle-project-request.php<br/>
<br/>
Thank you,<br/>\n";
print $user->prettyName();
print "</i></p>\n";

print "<p><button type=\"submit\" value=\"submit\"><b>Send Join Request</b></button>\n";

print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/></p>\n";
print "</form>\n";

include("footer.php");
?>

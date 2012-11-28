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
require_once('portal.php');
require("logging_constants.php");
require("logging_client.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once("pa_client.php");
require_once('rq_client.php');
require_once("cs_constants.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");

// Handle a single project request
// This is the page the PI is pointed to via email
// Show details on the requestor
// Show details on the project
// Show text explaining what you are doing.
// provide drop-down of Role
// Provide text explaining different roles
// Provide text box of reason
// 3 buttons: 'Accept, Deny' Cancel (put off handling)

// The email from the PI supplied project_id, member_id, request_id

// Get the pa_url for accessing request information
if (!isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
  if (!isset($pa_url) || is_null($pa_url) || $pa_url == '') {
    error_log("Found no Project Authority Service");
  }
}

if (array_key_exists("request_id", $_REQUEST)) {
  $request_id = $_REQUEST["request_id"];
  $request = get_request_by_id($pa_url, $user, $request_id);
} else {
  error_log("handle-project-request got no request_id");
}

// Confirm this user is a project/lead admin in general. The problem is that you can't be a lead without 
// a specific project
// if (! $user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, null)) {
//   error_log("User not allowed to handle project requests");
//   relative_redirect("home.php");
// }

if (! isset($request) || is_null($request)) {
  error_log("No request from request_id");
  if (isset($member_id) && isset($project_id)) {
    if (! $user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      error_log("User not allowed to handle project requests on this project");
      relative_redirect("home.php");
    }

    // Get requests for the given member_id on the given project
    $reqs = get_requests_by_user($pa_url, $user, $member_id, CS_CONTEXT_TYPE::PROJECT, $project_id, RQ_REQUEST_STATUS::PENDING);
    if (isset($reqs) && count($reqs) > 0) {
      if (count($reqs) > 1) {
	error_log("handle-p-reqs: Got " . count($reqs) . " pending requests on same project for same member");
      }
      $request = $reqs[0];
      $request_id = $request['id'];
    } else {
      error_log("handle-p-reqs: no pending reqs for this project, user");
    }
  } else {
    error_log("handle-p-req: And no member id plus project_id. Fail");
  }
  if (! isset($request) || is_null($request)) {
    show_header('GENI Portal: Projects', $TAB_PROJECTS);
    include("tool-breadcrumbs.php");
    print "<h2>Error handling project request</h2>\n";
    if (isset($request_id)) {
      print "Unknown request ID $request_id<br/>\n";
    }
    if (isset($member_id) && isset($project_id)) {
      print "No outstanding requests by member ";
      if (isset($member)) {
	print $member->prettyName();
      } else {
	print $member_id;
      }
      print " to join project ";
      if (isset($project_name)) {
	print $project_name;
      } else {
	print $project_id;
      }
      print "<br/>\n";
    } else {
      print "No member specified to look up that way.<br/>\n";
    }

    print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
    include("footer.php");
    exit();
  }
}

// So we have the request object
	/* id SERIAL, */
	/* context INT,  */
	/* context_id UUID, */
	/* request_text VARCHAR,  */
	/* request_type INT, -- 0 = JOIN, 1 = UPDATE_ATTRIBUTES, 2 = .... [That's all for now] */
	/* request_details VARCHAR, -- I suggest this is a JSON string with a dictionary of requested attributes for the case of a user wanting a change to his attributes */
	/* requestor UUID, */
	/* status INT, -- 0 = PENDING, 1 = APPROVED, 2 = CANCELED, 3 = REJECTED */
	/* creation_timestamp DATETIME, */
	/* resolver UUID, */
	/* resolution_timestamp DATETIME, */
	/* resolution_description VARCHAR */

if (isset($member)) {
  // That is the requestor. But make sure
  if ($member->account_id != $request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR]) {
    error_log("handle-p-reg got member_id != request's requestor. Member " . $member->account_id . " != " . $request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR] . " for request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID]);
  }
}
$member_id = $request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR];
$member = $user->fetchMember($member_id);
$member_name = $member->prettyName();

if ($request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE] != RQ_REQUEST_TYPE::JOIN) {
  error_log("handle-p-req: Non join request in request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . ": " . $request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE]);
  show_header('GENI Portal: Projects', $TAB_PROJECTS);
  include("tool-breadcrumbs.php");
  print "<h2>Error handling project request</h2>\n";
  print "Request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " is not a join request, but a " . $request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE] . "<br/>\n";
  // FIXME: Print other request details
  print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
  include("footer.php");
  exit();
}

if ($request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE] != CS_CONTEXT_TYPE::PROJECT) {
  error_log("handle-p-req: Not a project, but " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE]);
  show_header('GENI Portal: Projects', $TAB_PROJECTS);
  include("tool-breadcrumbs.php");
  print "<h2>Error handling project request</h2>\n";
  print "Request not a project request, but " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE] . "<br/>\n";
  // FIXME: Print other request details
  print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
  include("footer.php");
  exit();
}

if (isset($project_id) && $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID] != $project_id) {
  error_log("handle-p-req: Request project != given project: " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID] . " != " . $project_id);
}
$project_id = $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID];
$project = lookup_project($pa_url, $user, $project_id);
$project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
$lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
$lead = $user->fetchMember($lead_id);
$leadname = $lead->prettyName();
$request_id = $request[RQ_REQUEST_TABLE_FIELDNAME::ID];

// Validate this user has authorization to change membership on this project
if (! $user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  error_log("handle-p-req: User " . $user->prettyName() . " not authorized to add members to project $project_id");
  show_header('GENI Portal: Projects', $TAB_PROJECTS);
  include("tool-breadcrumbs.php");
  print "<h2>Error handling project request</h2>\n";
  print "You are not authorized to handle project requests for project $project_name<br/>\n";
  print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
  include("footer.php");
  exit();
}

// FIXME: Confirm the request is PENDING?
$status = $request[RQ_REQUEST_TABLE_FIELDNAME::STATUS];
$status_name = "Pending";
if ($status == RQ_REQUEST_STATUS::APPROVED) {
  $status_name = "Approved";
} else if ($status == RQ_REQUEST_STATUS::CANCELLED) {
  $status_name = "Cancelled";
} else if ($status == RQ_REQUEST_STATUS::REJECTED) {
  $status_name = "REJECTED";
}

// Be sure the project request is still outstanding
if (isset($status) && ! is_null($status) && $status != RQ_REQUEST_STATUS::PENDING) {
  error_log("handle-p-req: request $request_id by $member_name on $project_name not pending but $status_name");
  $_SESSION['lasterror'] = "Project request by $member_name on project $project_name not pending, but $status_name";
  relative_redirect("project.php?project_id=$project_id");
}

// Basic inputs validated

// Now: was this a form submission (e.g. trying to handle the request?)
// FIXME: Validate those inputs
// submit, reason, role
$reason = null;
$role = null;
$error = null;
if (array_key_exists('submit', $_REQUEST)) {
  $submit = $_REQUEST['submit'];
  if ($submit == 'approve') {
    error_log("handle-p-req: request is being approved");
  } elseif ($submit != 'reject') {
    error_log("handle-p-req: huh? what is a submit value of $submit?");
    // Pretend we got no submittal
    $submit = null;
  } else {
    error_log("handle-p-req: request is being denied");
  }
  if (array_key_exists('reason', $_REQUEST)) {
    $reason = $_REQUEST['reason'];
  } else {
    error_log("handle-p-req got no reason");
    $reason = "";
  }
  if (array_key_exists('role', $_REQUEST)) {
    $role = intval($_REQUEST['role']);
  } else {
    error_log("handle-p-req got no role: default to member");
    $role = CS_ATTRIBUTE_TYPE::MEMBER;
  }
}

// OK, inputs validated

// Handle form submission
if (isset($submit)) {
  if ($submit == 'approve') {
    // call pa add member
    $addres = add_project_member($pa_url, $user, $project_id, $member_id, $role);
    // FIXME: Check result

    $appres = resolve_pending_request($pa_url, $user, $request_id, RQ_REQUEST_STATUS::APPROVED, $reason);
    // FIXME: Check result

    // log this
    /* $project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT,  */
    /* 						    $project_id); */
    /* $member_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, */
    /* 						    $member_id); */
    /* $attributes = array_merge($project_attributes, $member_attributes); */
    $rolestr = $CS_ATTRIBUTE_TYPE_NAME[$role];
    /* $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE); */
    /* log_event($log_url, Portal::getInstance(), */
    /* 	      "Added $member_name to project $project_name as $rolestr ", $attributes, */
    /*   $user->account_id); */
    error_log("handle-p-req added $member_name to project $project_name with role $rolestr");
  
    // Email the member
    $email = $user->email();
    $name = $user->prettyName();
    $hostname = $_SERVER['HTTP_HOST'];
    $message = "Your request to join GENI project '$project_name' was accepted!
You have been added to the project with role $rolestr.

To start using this project at the GENI portal, visit this page: 
https://$hostname/secure/project.php?project_id=$project_id

";
    if (isset($reason) && $reason != '') {
    $message = $message . "Reason:
$reason

";
    }

    $message = $message . "Thank you,
$name\n";

    mail($member_name . "<" . $member->email() . ">",
       "Added to GENI project $project_name",
       $message,
       "Reply-To: $email" . "\r\n" . "From: $name <$email>");

    $_SESSION['lastmessage'] = "Added $member_name to project $project_name as $rolestr";

    // FIXME: Put up a page
    relative_redirect('project.php?project_id=' . $project_id);

  } else {
    $appres = resolve_pending_request($pa_url, $user, $request_id, RQ_REQUEST_STATUS::REJECTED, $reason);
    // FIXME : check result

    error_log("handle-p-req denied $member_name membership in $project_name");
  // FIXME: Email the member
    $email = $user->email();
    $name = $user->prettyName();
    $message = "Your request to join GENI project $project_name was denied.

Reason:
$reason

Thank you,
$name\n";
    mail($member_name . "<" . $member->email() . ">",
       "Request to join GENI project $project_name denied",
       $message,
       "Reply-To: $email" . "\r\n" . "From: $name <$email>");

    $_SESSION['lastmessage'] = "Rejected $member_name from project $project_name";

    // FIXME: Put up a page
    relative_redirect('project.php?project_id=' . $project_id);
  }
}

show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-breadcrumbs.php");

print "<h2>Handle Project Join Request</h2>\n";
print "Handle Request to join project $project_name:<br/>\n";
print "<br/>\n";

print "On this page, you can handle the request by $member_name to join project $project_name.<br/>\n";
print "You can accept or deny their request, or cancel (put off handling this request).<br/>\n";
print "If you accept or deny the request, give a reason justifying your decision (e.g. 'You are not in my class.' or 'Student in Section B.').<br/>\n";
print "If you accept the request, you must specify what role this member will have on the project.\n";

// FIXME: Explain different roles: who gets to add members? create slices? work on a slice?

print "<br/><br/>\n";

print "<b>Requestor</b>: <br/>\n";
// Show details on the requestor: name, email, institution
// FIXME: Add $member->idp_url as Identity Provider?
print "<table><tr><th>Requester</th><th>Email</th><th>Affiliation</th></tr><tr><td>" . $member->prettyName() . "</td><td>" . $member->email() . "</td><td>" . $member->affiliation . "</td></tr></table>\n";

print "<b>Project</b>: <br/>\n";
// Show details on the project: name, purpose, lead
print "<table><tr><th>Project</th><th>Purpose</th><th>Lead</th></tr><tr><td>$project_name</td><td>" . $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE] . "</td><td>$leadname</td></tr></table>\n";

print "<b>Request Explanation</b>: <br/>\n";
print "<textarea disabled='disabled' cols='60'>" . $request['request_text'] . "</textarea>\n";

print "<br/><br/>\n";

print "<form action='handle-project-request.php'>\n";
print "<input type='hidden' name='request_id' value='$request_id'>\n";

// provide drop-down of Role
print "<b>Project Role</b>: <br/>\n";
print "<input type='radio' name='role' value='" . CS_ATTRIBUTE_TYPE::ADMIN . "'> Admin (can add/remove members)<br/>\n";
print "<input type='radio' name='role' value='" . CS_ATTRIBUTE_TYPE::MEMBER . "' checked> Member (default)<br/>\n";
print "<input type='radio' name='role' value='" . CS_ATTRIBUTE_TYPE::AUDITOR . "'> Auditor (read only)<br/>\n";
print "<br/>\n";
// Provide text box of reason
print "<b>Response Explanation</b>: <br/>\n";
print "<textarea name='reason' cols='60' rows='2'></textarea><br/>\n";
print "<br/>\n";

// 3 buttons: 'Accept, Deny' Cancel (put off handling)

// Buttons go to:
//	approve_request(request_id, resolution_description)
//	reject_request(request_id, resolution_description)
print "<button type=\"submit\" name='submit' value=\"approve\"><b>Approve Join Request</b></button>\n";
print "<button type=\"submit\" name='submit' value=\"reject\"><b>Deny Join Request</b></button>\n";


print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print "</form>\n";

include("footer.php");
?>

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
require_once('cs_constants.php');
require_once('ma_client.php');
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");
require_once('rq_client.php');
require_once('rq_constants.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");


// Handle project join requests
// This is the page the PI is pointed to via email
// Show details on the requestor
// Show details on the project
// Show text explaining what you are doing.
// provide drop-down of Role
// Provide text explaining different roles
// Provide text box of reason
// 3 buttons: Accept, Deny, Cancel (put off handling)

// The email from the PI supplied project_id, member_id, request_id
// Other links only provide the request ID
// Try to allow this page to handle multiple requests at once

// Get the sa_url for accessing request information
if (!isset($sa_url)) {
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  if (!isset($sa_url) || is_null($sa_url) || $sa_url == '') {
    error_log("Found no Slice Authority Service");
  }
}

// Get the ma_url for accessing member information
if (!isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (!isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no Member Authority Service");
  }
}

// We should have (at least one) request_id
if (array_key_exists("request_id", $_REQUEST)) {
  $request_id = $_REQUEST["request_id"];
  if (is_array($request_id)) {
    //    error_log("handle-p-r got array of request ids");
    $request_ids = $request_id;
    $project_id = NULL;
    foreach ($request_ids as $request_id) {
      $nr = get_request_by_id($sa_url, $user, $request_id, CS_CONTEXT_TYPE::PROJECT);
      if (! isset($nr) || is_null($nr)) {
	error_log("handle-p-r skipping unknown request id " . $request_id);
	continue;
      }
      if (is_null($project_id)) {
	$project_id = $nr[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID];
      } elseif ($project_id != $nr[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]) {
	error_log("handle-p-r got requests for 2+ different projects. Skipping 2nd+ projects so skipping request " . $request_id);
	continue;
      }
      $requests[] = $nr;
    }
    error_log("handle-p-r: Got " . count($requests) . " requests from request ids");
    $request = $requests[0];
    $request_id = $request_ids[0];
  } else {
    //    error_log("handle-p-r got 1 request ID: " . print_r($_REQUEST, True));
    $request = get_request_by_id($sa_url, $user, $request_id, CS_CONTEXT_TYPE::PROJECT);
    $requests = array($request);
    $request_ids = array($request_id);
    $project_id = $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID];
  }
}

// And the request_id should refer to a current request
// And the user should be allowed to add a project member
if (! isset($request) || is_null($request)) {
  //  error_log("handle-p-req: No request from request_ids");
  if(isset($project_id)) {
    if (! $user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      error_log("User " . $user->prettyName() . " not allowed to handle project requests on this project: " . $project_id);
      relative_redirect("home.php");
    }

    // Get requests that this member can handle on the given project
    $requests = get_pending_requests_for_user($sa_url, $user, $user->account_id, CS_CONTEXT_TYPE::PROJECT, $project_id);
    if (isset($requests) && count($requests) > 0) {
      error_log("handle-p-req got just a project ID. Got " . count($requests) . " request(s) for this project");
      $request = $requests[0];
      $request_id = $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
      foreach ($requests as $r) {
	$request_ids[] = $r[RQ_REQUEST_TABLE_FIELDNAME::ID];
      }
    } else {
      error_log("handle-p-reqs: no pending reqs for this project, user");
    }
  } else {
    error_log("handle-p-req: No request or project_id specified. Fail");
  }
}
if (! isset($request) || is_null($request)) {
  show_header('GENI Portal: Projects', $TAB_PROJECTS);
  include("tool-breadcrumbs.php");
  print "<h2>Error handling project request</h2>\n";
  if (isset($request_id)) {
    print "Unknown request ID(s) " . print_r($request_id) . "<br/>\n";
  }
  if (isset($project_id)) {
    print "No outstanding requests for project ";
    if (isset($project_name)) {
      print $project_name;
    } else {
      print $project_id;
    }
    print "<br/>\n";
  } else {
    print "No project specified either.<br/>\n";
  }
  
  print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
  include("footer.php");
  exit();
  
}

$newrs = array();
$rcnt = count($requests);
foreach ($requests as $request) {
  // Make sure request is still pending
  if ($request['status'] != RQ_REQUEST_STATUS::PENDING) {
    $status = "rejected";
    if ($request['status'] == RQ_REQUEST_STATUS::APPROVED) {
      $status = "approved";
    } elseif ($request['status'] == RQ_REQUEST_STATUS::CANCELLED) {
      $status = "cancelled";
    }
    error_log ("handle-project-request: request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " is no longer pending. It is " . $status);
    if ($rcnt == 1) {
      relative_redirect('error-text.php?error=' . urlencode("Request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " was already " . $status));
    }
    continue;
  }
  // Only join requests
  if ($request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE] != RQ_REQUEST_TYPE::JOIN) {
    error_log("handle-p-req: Non join request in request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . ": " . $request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE]);
    if ($rcnt == 1) {
      show_header('GENI Portal: Projects', $TAB_PROJECTS);
      include("tool-breadcrumbs.php");
      print "<h2>Error handling project request</h2>\n";
      print "Request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " is not a join request, but a " . $request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TYPE] . "<br/>\n";
      // FIXME: Print other request details
      print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
      include("footer.php");
      exit();
    }
    continue;
  }

  if ($request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE] != CS_CONTEXT_TYPE::PROJECT) {
    error_log("handle-p-req: Not a project request, but " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE]);
    if ($rcnt == 1) {
      show_header('GENI Portal: Projects', $TAB_PROJECTS);
      include("tool-breadcrumbs.php");
      print "<h2>Error handling project request</h2>\n";
      print "Request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " not a project join request, but " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_TYPE] . "<br/>\n";
      // FIXME: Print other request details
      print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
      include("footer.php");
      exit();
    }
    continue;
  }

  // This shouldn't happen because of above checks...
  if (isset($project_id) && $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID] != $project_id) {
    error_log("handle-p-req: Request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " project != given project: " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID] . " != " . $project_id);
    continue;
  }

  // If the member for this request is already a member of the given project, then cancel this request
  $members = get_project_members($sa_url, $user, $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);

  $user_is_project_member = false;
  foreach ($members as $m) {
    if ($request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR] == $m[MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID]) {
      $user_is_project_member = true;
      break;
    }
  }
  if ($user_is_project_member) {
    error_log("handle-p-req canceling open request for member to join a project they are already in. Request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " for member " . $request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR] . " to join project " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);
    resolve_pending_request($sa_url, $user, CS_CONTEXT_TYPE::PROJECT, 
					 $request[RQ_REQUEST_TABLE_FIELDNAME::ID], RQ_REQUEST_STATUS::CANCELLED, "User already in this project");
    continue;
  }

  // If we already have in newrs a request by the same member to join the same project, then cancel this request
  $dupid = NULL;
  foreach ($newrs as $newr) {
    if (($newr[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR] == $request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR]) && 
	($newr[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID] == $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID])) {
      error_log("handle-p-req canceling duplicate request " . $request[RQ_REQUEST_TABLE_FIELDNAME::ID] . " == older request " . $newr[RQ_REQUEST_TABLE_FIELDNAME::ID] . " for member " . $request[RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR] . " to join project " . $request[RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID]);
      $dupid = $newr[RQ_REQUEST_TABLE_FIELDNAME::ID];
      break;
    }
  }
  if (! is_null($dupid)) {
    resolve_pending_request($sa_url, $user, CS_CONTEXT_TYPE::PROJECT, 
			    $request[RQ_REQUEST_TABLE_FIELDNAME::ID], RQ_REQUEST_STATUS::CANCELLED, "Duplicate of request " . $dupid);
    continue;
  }
  $newrs[] = $request;
}
$requests = $newrs;
if (count($requests) == 0) {
  relative_redirect('error-text.php?error=' . urlencode("All requests are invalid: only pending project join requests are handled here."));
}

$project = lookup_project($sa_url, $user, $project_id);
$project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];

if (! $user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
  error_log("User " . $user->prettyName() . " not allowed to handle project requests on this project " . $project_name);
  relative_redirect("home.php");
}

// OK, inputs validated

// At this point, we should be able to bring up the table of all pending
// requests (or only that one if that is what is asked for)

// Get ID's of all member to be added by pending request for this project
$all_requestors = array();
foreach($requests as $request) {
  $member_id = $request['requestor'];
  if(!array_key_exists($member_id, $all_requestors))
    $all_requestors[] = $member_id;
}

// error_log("REQUESTS = " . print_r($requests, true));
// error_log("ALL REQUESTORS = " . print_r($all_requestors, true));

$member_details = lookup_member_details($ma_url, $user, $all_requestors);
// error_log("MEMBERS = " . print_r($member_details, true));
$member_names = lookup_member_names_for_rows($ma_url, $user, 
					     $member_details, 
					     MA_MEMBER_TABLE_FIELDNAME::MEMBER_ID);
// error_log("MEMBER_NAMES = " . print_r($member_names, true));

// FIXME: Allow per request deny text from here?

show_header('GENI Portal: Projects', $TAB_PROJECTS);
include("tool-breadcrumbs.php");

print "<h2>Handle Project Request: ". $project_name . "</h2>";
print "<p> The following request(s) have been made to join your
project. You may approve (add the member) or deny each request.</p>
<p>You should only add people to your project that you
know. Remember that when you approve a project join request, you agree
that the project lead will be held responsible for all GENI actions this person
takes in this project.</p>";

print '<form method="POST" action="do-handle-project-request.php">';
print '<table>';
print '<tr><th>Candidate Name</th><th>Candidate Email</th>';
print '<th>Request Text</th>';
// <th>Can Add <br/>Immediately?</th>
print '<th>Action</th></tr>';
print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";
print "<input type=\"hidden\" name=\"project_name\" value=\"$project_name\"/>\n";


function get_attribute_named($member_detail, $attribute_name)
{
  if (array_key_exists($attribute_name, $member_detail)) {
    return $member_detail[$attribute_name];
  } 
  return "";
}


function compute_actions_for_member($member_id, $request_id, $email)
{
  global $CS_ATTRIBUTE_TYPE_NAME;

  $actions = "";
  $actions = $actions . "<option value=-1,$member_id,$request_id,$email>Deny</option>";
  $actions = $actions . "<option selected value=0,$member_id,$request_id,$email>Ignore (no action)</option>";
  foreach($CS_ATTRIBUTE_TYPE_NAME as $role_index => $role_label) {
    $selected = "";
    if ($role_index == CS_ATTRIBUTE_TYPE::LEAD || 
	$role_index == CS_ATTRIBUTE_TYPE::OPERATOR) 
      continue;
    $action = "<option $selected value=$role_index,$member_id,$request_id,$email>Add as $role_label</option>";
    $actions = $actions . $action;
  }
  return $actions;
}

foreach($requests as $request) {
  $member_id = $request['requestor'];
  $member_detail = $member_details[$member_id];
  $member_name = $member_names[$member_id];
  $request_id = $request[RQ_REQUEST_TABLE_FIELDNAME::ID];
  $request_text = $request[RQ_REQUEST_TABLE_FIELDNAME::REQUEST_TEXT];

  $email= get_attribute_named($member_detail, 'email_address');
  $actions = compute_actions_for_member($member_id, $request_id, $email);
  $select_actions = "<select name=\"$request_id\">$actions</select>";
  print "<tr><td>$member_name</td><td>$email</td>";
  print "<td>$request_text</td>";
  print "<td>$select_actions</td></tr>";
  //  error_log("REQ = " . print_r($request, true));
}
print '</table>';
print "<br/>\n";
print "<p><i>Optional</i>: Custom message to send to all new project members</p>\n";
print "<p><textarea name='yesmessage' cols='50' rows='4'></textarea></p>\n";
print "<p><i>Optional</i>: Custom message to send to all members whose request you are rejecting (e.g. explaining why)</p>\n";
print "<p><textarea name='nomessage' cols='50' rows='4'></textarea></p>\n";
print "<input type=\"submit\" value=\"Handle Requests\"/>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print '</form>';

include("footer.php");
?>

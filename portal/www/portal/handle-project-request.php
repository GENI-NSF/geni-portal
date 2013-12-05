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
require_once('cs_constants.php');
require_once('ma_client.php');
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

// We should have a request_id
if (array_key_exists("request_id", $_REQUEST)) {
  $request_id = $_REQUEST["request_id"];
  $request = get_request_by_id($sa_url, $user, $request_id, CS_CONTEXT_TYPE::PROJECT);
  $project_id = $request['context_id'];
  $requests = array($request);
} else {
  error_log("handle-project-request got no request_id");
}

// And the request_id should refer to a current request
// And the user should be allowed to add a project member
if (! isset($request) || is_null($request)) {
  error_log("No request from request_id");
  if (isset($member_id) && isset($project_id)) {
    if (! $user->isAllowed(PA_ACTION::ADD_PROJECT_MEMBER, CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      error_log("User not allowed to handle project requests on this project");
      relative_redirect("home.php");
    }

    // Get requests for the given member_id on the given project
    $reqs = get_requests_by_user($sa_url, $user, $member_id, CS_CONTEXT_TYPE::PROJECT, $project_id, RQ_REQUEST_STATUS::PENDING);
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

// If the request isn't provided, grab all pending
// Get requests for the given member_id on the given project
if(!isset($requests)) {
  $requests = get_requests_by_user($sa_url, $user, $member_id, 
				   CS_CONTEXT_TYPE::PROJECT, 
				   $project_id, RQ_REQUEST_STATUS::PENDING);
  error_log("Resetting requests");
  if (count($requests) == 0) {
    error_log("No pending reuqests for this project, user");
    relative_redirect('home.php');
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
$project = lookup_project($sa_url, $user, $project_id);
$project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
$lead_id = $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
$lead = $user->fetchMember($lead_id);
$leadname = $lead->prettyName();
$request_id = $request[RQ_REQUEST_TABLE_FIELDNAME::ID];

// At this point, we should be able to bring up the table of all pending
// requests (or only that one if that is what is asked for)

show_header('GENI Portal: Projects', $TAB_PROJECTS);
include("tool-breadcrumbs.php");

$project_details = lookup_project($sa_url, $user, $project_id);
// error_log("PD = " . print_r($project_details, true));
$project_name = $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];

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
// <th>Can Add <br/>Immediately?</th>
print '<th>Action</th></tr>';
print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";
print "<input type=\"hidden\" name=\"project_name\" value=\"$project_name\"/>\n";

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


function get_attribute_named($member_detail, $attribute_name)
{
  if (array_key_exists($attribute_name, $member_detail)) {
    return $member_detail[$attribute_name];
  } 
  return "";
}

// OK, inputs validated

// Handle form submission
if (isset($submit)) {
  if ($submit == 'approve') {
    // call pa add member
    $addres = add_project_member($sa_url, $user, $project_id, $member_id, $role);
    // FIXME: Check result

    $appres = resolve_pending_request($sa_url, $user, CS_CONTEXT_TYPE::PROJECT,
				      $request_id, RQ_REQUEST_STATUS::APPROVED, $reason);
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
    $hostname = $_SERVER['SERVER_NAME'];
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

    mail($member_name . " <" . $member->email() . ">",
       "Added to GENI project $project_name",
       $message,
       "Reply-To: $email" . "\r\n" . "From: $name <$email>");

    $_SESSION['lastmessage'] = "Added $member_name to project $project_name as $rolestr";

    // FIXME: Put up a page
    relative_redirect('project.php?project_id=' . $project_id);

    } 
    
    else {
      $appres = resolve_pending_request($sa_url, $user, CS_CONTEXT_TYPE::PROJECT,
				        $request_id, RQ_REQUEST_STATUS::REJECTED, $reason);
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
    

function compute_actions_for_member($member_id, $request_id, $email)
{
  global $CS_ATTRIBUTE_TYPE_NAME;

  $actions = "";
  $actions = $actions . "<option value=0,$member_id,$request_id,$email>Do not add</option>";
  foreach($CS_ATTRIBUTE_TYPE_NAME as $role_index => $role_label) {
    $selected = "";
    if ($role_index == CS_ATTRIBUTE_TYPE::LEAD || 
	$role_index == CS_ATTRIBUTE_TYPE::OPERATOR) 
      continue;
    if ($role_index == CS_ATTRIBUTE_TYPE::MEMBER)
      $selected = "selected";
    $action = "<option $selected value=$role_index,$member_id,$request_id,$email>Add as $role_label</option>";
    $actions = $actions . $action;

  }
  return $actions;
}

foreach($requests as $request) {
  $member_id = $request['requestor'];
  $member_detail = $member_details[$member_id];
  //  $member_name = compute_display_name($member_detail);
  $member_name = $member_names[$member_id];
  $request_id = $request['id'];

  $email= get_attribute_named($member_detail, 'email_address');
  $actions = compute_actions_for_member($member_id, $request_id, $email);
  $select_actions = "<select name=\"$request_id\">$actions</select>";
  print "<tr><td>$member_name</td><td>$email<td>$select_actions</td></tr>";
  //  error_log("REQ = " . print_r($request, true));
}
print '</table>';
print "<br/>\n";
print "<input type=\"submit\" value=\"Handle Requests\"/>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print '</form>';

include("footer.php");
?>

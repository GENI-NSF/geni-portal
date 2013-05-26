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

// Get the pa_url for accessing request information
if (!isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
  if (!isset($pa_url) || is_null($pa_url) || $pa_url == '') {
    error_log("Found no Project Authority Service");
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
  $request = get_request_by_id($pa_url, $user, $request_id);
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
  }
}

// If the request isn't provided, grab all pending
// Get requests for the given member_id on the given project
if(!isset($requests)) {
  $requests = get_requests_by_user($pa_url, $user, $member_id, 
				   CS_CONTEXT_TYPE::PROJECT, 
				   $project_id, RQ_REQUEST_STATUS::PENDING);
  error_log("Resetting requests");
  if (count($requests) == 0) {
    error_log("No pending reuqests for this project, user");
    relative_redirect('home.php');
  }
}

// At this point, we should be able to bring up the table of all pending
// requests (or only that one if that is what is asked for)

show_header('GENI Portal: Projects', $TAB_PROJECTS);
include("tool-breadcrumbs.php");

$project_details = lookup_project($pa_url, $user, $project_id);
// error_log("PD = " . print_r($project_details, true));
$project_name = $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];

print "<h2>Handle Project Request: ". $project_name . "</h2>";

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

//error_log("REQUESTS = " . print_r($requests, true));
//error_log("ALL REQUESTORS = " . print_r($all_requestors, true));

$member_details = lookup_member_details($ma_url, $user, $all_requestors);
// error_log("MEMBERS = " . print_r($member_details, true));

function get_attribute_named($member_detail, $attribute_name)
{
  $attribs = $member_detail['attributes'];
  foreach($attribs as $attrib) {
    $attrib_name = $attrib['name'];
    $attrib_value = $attrib['value'];
    if($attrib_name == $attribute_name)
      return $attrib_value;
  }
  return "";
}

function compute_actions_for_member($member_id, $request_id)
{
  global $CS_ATTRIBUTE_TYPE_NAME;

  $actions = "";
  $actions = $actions . "<option value=0,$member_id,$request_id>Do not add</option>";
  foreach($CS_ATTRIBUTE_TYPE_NAME as $role_index => $role_label) {
    $selected = "";
    if ($role_index == CS_ATTRIBUTE_TYPE::LEAD || 
	$role_index == CS_ATTRIBUTE_TYPE::OPERATOR) 
      continue;
    if ($role_index == CS_ATTRIBUTE_TYPE::MEMBER)
      $selected = "selected";
    $action = "<option $selected value=$role_index,$member_id,$request_id>Add as $role_label</option>";
    $actions = $actions . $action;
  }
  return $actions;
}

foreach($requests as $request) {
  $member_id = $request['requestor'];
  $member_detail = $member_details[$member_id];
  $request_id = $request['id'];
  $name = get_attribute_named($member_detail, 'displayName');
  $email= get_attribute_named($member_detail, 'email_address');
  $actions = compute_actions_for_member($member_id, $request_id);
  $select_actions = "<select name=\"$email\">$actions</select>";
  print "<tr><td>$name</td><td>$email<td>$select_actions</td></tr>";
  //  error_log("REQ = " . print_r($request, true));
}
print '</table>';
print "<br/>\n";
print "<input type=\"submit\" value=\"Handle Requests\"/>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print '</form>';

include("footer.php");
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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
require_once("db_utils.php");
require_once("ma_constants.php");
require_once("ma_client.php");
require_once("util.php");

// This page is for OPERATORS only
if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}
?>

<h1>Administrator Tools</h1>

<script>
function failed_request(){
  $("#loading").hide();
  alert("Failed to perform action. Check the server logs for more info.");
}

function send_ajax_request(url, params) {
  $("#loading").show();
  $("#resultsbox").hide();
  $.post(url, params, function(data) {
    $("#loading").hide();
    $("#resultsbox").html(data);
    $("#resultsbox").show();
  }).fail(failed_request);
}

function expand_info(button){
  $($(button).parents()[1]).next().show(); 
  $(button).hide();
  $(button).next().show();
}

function hide_info(button){
  $($(button).parents()[1]).next().hide();
  $(button).hide();
  $(button).prev().show();
}

function disable_user(name, member_urn){
  if(confirm("Are you sure you want to disable " + name + "?")) {
    params = {action: "disable", member_urn: member_urn};
    send_ajax_request("do-user-admin.php", params);
  }
}

function deny_request(button, requester_uuid, request_id){
  old_row = $($(button).parents()[2]).html();
  $($(button).parents()[2]).html("<td colspan='6' style='text-align: center;'>" +
                                 "<i>Request denied&nbsp;</i>" +
                                 "<button class='undo'>undo</button>" + 
                                 "<button class='confirmdeny'>hide</button></td>");
  $(".undo").click(function(){
    send_lead_request_response(requester_uuid, request_id, "open", "");
    $($(this).parents()[1]).html(old_row);
  });
  $(".confirmdeny").click(function(){
    $($(this).parents()[1]).hide();
  });
  send_lead_request_response(requester_uuid, request_id, "denied", "");
}

function approve_request(button, requester_uuid, request_id) {
  reason = prompt("Why did you accept? (will be mailed to admins)");
  if (reason) {
    $($(button).parents()[1]).hide();
    send_lead_request_response(requester_uuid, request_id, "approved", reason);
  }
}

function send_lead_request_response(requester_uuid, request_id, status, reason) {
  params = {request_id: request_id, new_status: status, user_uid: requester_uuid, reason: reason};
  send_ajax_request("do-handle-lead-request.php", params);
}

function save_note(request_id) {
  note = $("#notebox" + request_id).val();
  if (note){
    params = {request_id: request_id, notes: note};
    send_ajax_request("do-handle-lead-request.php", params);
  }
}

function remove_from_project(member_id, project_id){
  params = {member_id: member_id, project_id: project_id, action: "remove"};
  send_ajax_request("do-user-admin.php", params);
}

$(document).ready(function(){
  $(".moreinfo").hide();
  $('#usersearchform').submit(function(e) {
    $("#loading").show();
    e.preventDefault();
    params = ($(this).serialize());
    $.get( "do-user-search.php?" + params, function(data) {
      $("#loading").hide();
      $("#usersearchresults").html(data);
    }).fail(failed_request);
  });
  $('#slicesearchform').submit(function(e) {
    $("#loading").show();
    e.preventDefault();
    params = ($(this).serialize());
    $.get( "do-slice-search.php?" + params, function(data) {
      $("#loading").hide();
      $("#slicesearchresults").html(data);
    }).fail(failed_request);
  });
});
</script>

<div id='tablist'>
  <ul class='tabs'>
    <li><a href='#leadrequests'>Lead Requests</a></li>
    <li><a href='#usersearch'>User Search</a></li>
    <li style="border-right: none"><a href='#slicesearch'>Slice Search</a></li>
  </ul>
</div>
<div id ='loading' style='display: none;'><h2 style="border: 0px; text-align: center;">Loading...</h2></div>
<div style='text-align:center; font-weight: bold;' id='resultsbox'></div>


<div id='leadrequests'>
<h2>Open lead requests</h2>

<?php

// Find open lead requests and display table with information about the requesters
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$conn = portal_conn();

$sql = "SELECT *"
. " FROM lead_request WHERE status='open'";
$rows = db_fetch_rows($sql, "fetch all lead requests for admin page");
$lead_requests = $rows[RESPONSE_ARGUMENT::VALUE];
$requester_uuids = array();
foreach ($lead_requests as $lead_request) {
  $requester_uuids[] = $lead_request['requester_uuid'];
}

if ($requester_uuids) {
  $requester_details = lookup_member_details($ma_url, $user, $requester_uuids);
}

print "<table><tr><th>Name</th><th>Link</th><th>Requested At</th><th>Email</th><th>Admin Notes</th><th>Actions</th></tr>";

$open_requests = 0;

foreach ($lead_requests as $lead_request) {
  $requester_uuid = $lead_request['requester_uuid'];
  $notes = $lead_request['notes'] == "" ? "None" : $lead_request['notes'];
  $timestamp = dateUIFormat($lead_request['request_ts']);
  $request_id = $lead_request['id'];
  $details = $requester_details[$requester_uuid];
  make_user_info_rows($details, $requester_uuid, $request_id, $notes, $timestamp);
  $open_requests++;
}

// Returns what's between the '@' and the "." in a user's affiliation. Likely their school/ institution
function get_school($affiliation)
{
  if ($affiliation == NULL){
    return "";
  }
  $tmp = explode("@", $affiliation);
  if (count($tmp) == 1) {
    return $affiliation;
  }
  $tmp = explode(".", $tmp[1]);
  return $tmp[0];
}

// Populate and print 2 rows for a user: one with basic information and approve/deny button,
// a second which is default hidden and contains more information 
function make_user_info_rows($details, $user_id, $request_id, $notes, $timestamp)
{
  global $portal_admin_email;
  $username = $details[MA_ATTRIBUTE_NAME::USERNAME];
  $member = new Member($user_id);
  $member->init_from_record($details);
  $name = $member->prettyName();
  $email = $details[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
  $url = $details[MA_ATTRIBUTE_NAME::URL];
  $link = $url == "" ? "None" : "<a href='$url'>$url</a>"; 
  $mailto_link = "<a href='mailto:" . $email . "?Subject=Geni%20Project%20Lead%20Request'>" . $email . "</a>"; 
  print "<tr><td>$name ($username)</td><td>$link</td><td>$timestamp<td>$mailto_link</td>";
  print "<td id='notescontainer'>";
  print "<textarea rows='5' cols='40' id='notebox$request_id'>$notes</textarea><br>";
  print "<button id='savenote' onclick='save_note(\"$request_id\");'>Save note</button></td>";
  print "<td><button onclick='approve_request(this, \"$user_id\", \"$request_id\");'>Approve</button>";
  print "<a href='mailto:$email?cc=$portal_admin_email&subject=GENI%20Project%20Lead%20Request'>";
  print "<button onclick='deny_request(this, \"$user_id\", \"$request_id\");'>Deny</button></a>";
  print "<button onclick='expand_info(this);'>More info</button>";
  print "<button class='hideinfo' onclick='hide_info(this);' style='display:none;'>Close</button></td></tr>";
  $affiliation = $details[MA_ATTRIBUTE_NAME::AFFILIATION];
  $reason = $details[MA_ATTRIBUTE_NAME::REASON];
  $reference = $details[MA_ATTRIBUTE_NAME::REFERENCE];
  $url = $details[MA_ATTRIBUTE_NAME::URL];
  $link = "<a href='" . $url . "'>" . $url . "</a>"; 
  $info = "<b>Affiliation: </b>" . ($affiliation != "" ? $affiliation : "None")  . "<br>" .
          "<b>Reason:      </b>" . ($reason      != "" ? $reason      : "None")  . "<br>" .
          "<b>Reference:   </b>" . ($reference   != "" ? $reference   : "None")  . "<br>" .
          "<a target= 'blank' href = 'http://lmgtfy.com/?q=" . $name . "+" . get_school($affiliation) . "'>LMGTFY (beta)</a>";
  print "<tr class='moreinfo'><td colspan='6'>$info</td></tr>";
}

if ($open_requests == 0) {
  print "<td colspan='6' style='text-align: center;'><i>No open lead requests.</i></td>";
}

?>

</tbody></table></div>

<div id='usersearch'>
  <h2>Find a GENI user:</h2>
  <form id="usersearchform">
    Search users:
    <input type="search" class='searchbox' name="term" placeholder="enter search term ...">
        <input type="submit" value='search'><br>
    by: <input type="radio" name="search_type" value="email" checked>email
        <input type="radio" name="search_type" value="username">username
        <input type="radio" name="search_type" value="lastname">lastname
  </form>
  <div id="usersearchresults"></div>
</div>

<div id='slicesearch'>
  <h2>Find a slice:</h2>
  <form id="slicesearchform">
    Search slices:
    <input type="search" class='searchbox' name="term" placeholder="enter search term ..." size="45">
        <input type="submit" value='search'><br>
     by: <input type="radio" name="search_type" value="owner_email" checked>owner email
         <input type="radio" name="search_type" value="urn">urn
  </form>
  <div id="slicesearchresults"></div>
</div>

<?php include "tabs.js"; ?>

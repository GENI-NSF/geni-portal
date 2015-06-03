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

if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}
?>

<h1>Administrator Tools</h1>

<p>This page is intentionally not blank.</p>
<script>
  function expand_info(button){
    $($(button).parents()[1]).next().fadeIn(500); 
  }
  function hide_info(button){
    $($(button).parents()[1]).fadeOut(500);
  }
  function deny_request(button, requester_uuid, request_id){
    old_row = $($(button).parents()[2]).html();
    $($(button).parents()[2]).html("<td colspan='4' style='text-align: center;'><i>Request denied&nbsp;</i><button class='undo'>undo</button><button class='confirmdeny'>hide</button></td>");
    $(".undo").click(function(){
      send_lead_request_response(requester_uuid, request_id, "open");
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
      $($(button).parents()[2]).hide();
      send_lead_request_response(requester_uuid, request_id, "approved", reason);
    }
  }

  function send_lead_request_response(requester_uuid, request_id, status, reason) {
    params = {request_id: request_id, new_status: status, user_uid: requester_uuid, reason: reason };
    $.post( "do-handle-lead-request.php", params, function(data) {
      console.log(data);
    });
  }

  function save_note(request_id) {
    note = $("#notebox").val();
    if (note){
      console.log("note_to_send isssssss "+ note);
      params = {request_id: request_id, notes: note};
      $.post( "do-handle-lead-request.php", params, function(data) {
        console.log(data);
      });
    }
  }

  $(document).ready(function(){
    $(".moreinfo").hide();
  });
</script>

<h2>Open lead requests</h2>

<?php
function get_school($affiliation){
  $tmp = explode("@", $affiliation);
  $tmp = explode(".", $tmp[1]);
  $school = $tmp[0];
  return $school == NULL ? "" : $school;  
}

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$conn = portal_conn();

$sql = "SELECT *"
. " FROM lead_request";
$rows = db_fetch_rows($sql, "fetch all lead requests for admin page");
$lead_requests = $rows[RESPONSE_ARGUMENT::VALUE];
$requester_uuids = array();
foreach ($lead_requests as $lead_request) {
  $requester_uuids[] = $lead_request['requester_uuid'];
}

$requester_details = lookup_member_details($ma_url, $user, $requester_uuids); 

print "<table><tr><th>Name</th><th>Requested At</th><th>Email</th><th>Admin Notes (click to edit)</th><th>Actions</th></tr>";

$open_requests = 0;

foreach ($lead_requests as $lead_request) {
  if ($lead_request['status'] == "open") {
    $requester_uuid = $lead_request['requester_uuid'];
    $notes = $lead_request['notes'] == "" ? "None" : $lead_request['notes'];
    $request_id = $lead_request['id'];
    $details = $requester_details[$requester_uuid];
    $username = $details[MA_ATTRIBUTE_NAME::USERNAME];
    $name = $details[MA_ATTRIBUTE_NAME::FIRST_NAME] . " " . $details[MA_ATTRIBUTE_NAME::LAST_NAME]
             . " (" . $username . ")";
    $email = $details[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
    $timestamp = dateUIFormat($lead_request['request_ts']);
    $mailto_link = "<a href='mailto:" . $email . "?Subject=Geni%20Project%20Lead%20Request'>" . $email . "</a>"; 
    print "<tr><td>$name</td><td>$timestamp<td>$mailto_link</td>";
    print "<td id='notescontainer'><textarea id='notebox'>$notes</textarea><br><button id='savenote' onclick='save_note(\"$request_id\");'>Save note</button></td>";
    print "<td><button onclick='approve_request(this, \"$requester_uuid\", \"$request_id\");'>Approve</button>";
    print "<a href='mailto:$email?cc=ch-admins@geni.net&subject=GENI%20Project%20Lead%20Request'>";
    print "<button onclick='deny_request(this, \"$requester_uuid\", \"$request_id\");'>Deny</button></a>";
    print "<button onclick='expand_info(this);'>More info</button></td></tr>";
    $affiliation = $details[MA_ATTRIBUTE_NAME::AFFILIATION];
    $reference = $details[MA_ATTRIBUTE_NAME::REFERENCE];
    $reason = $details[MA_ATTRIBUTE_NAME::REASON];
    $url = $details[MA_ATTRIBUTE_NAME::URL];
    $link = "<a href='" . $url . "'>" . $url . "</a>"; 
    $info = "<b>Affiliation: </b>" . ($affiliation != "" ? $affiliation : "None")  . "<br>" .
            "<b>Reason:      </b>" . ($reason      != "" ? $reason      : "None")  . "<br>" .
            "<b>Reference:   </b>" . ($reference   != "" ? $reference   : "None")  . "<br>" .
            "<b>Link:        </b>" . ($url         != "" ? $link        : "None")  . "<br>" . 
            "<a target= 'blank' href = 'http://lmgtfy.com/?q=" . $details[MA_ATTRIBUTE_NAME::FIRST_NAME] . "+" .
                                                 $details[MA_ATTRIBUTE_NAME::LAST_NAME]  . "+" . 
                                                 get_school($affiliation) . "'>LMGTFY (beta)</a>";
    print "<tr class='moreinfo'><td colspan='4'>$info</td>";
    print "<td><button class='hideinfo' onclick='hide_info(this);'>close</button></td><tr>";
    $open_requests++;
  }
}

if ($open_requests == 0) {
  print "<td colspan='5'><i>$open_requests open lead requests.</i></td>";
}

?>

</tbody></table>

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

<script>
$(document).ready(function(){
  $(".moreinfo").hide();
  $(".expandinfo").click(function(){
    $($(this).parents()[1]).next().fadeIn(500); 
  });
  $(".hideinfo").click(function(){
    $($(this).parents()[1]).fadeOut(500);
  });
});
</script>


<h1>Administrator Tools</h1>

<p>This page is intentionally not blank.</p>
<h2>Open lead requests</h2>

<?php

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

print "<table><tr><th>name</th><th>requested at</th><th>email</th><th>actions</th></tr>";
foreach ($lead_requests as $lead_request) {
  $requester_uuid = $lead_request['requester_uuid'];
  $details = $requester_details[$requester_uuid];
  $name = $details[MA_ATTRIBUTE_NAME::FIRST_NAME] . " " . $details[MA_ATTRIBUTE_NAME::LAST_NAME]
           . " (" . $details[MA_ATTRIBUTE_NAME::USERNAME] . ")";
  $email = $details[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
  $timestamp = dateUIFormat($lead_request['request_ts']);
  $mailto_link = "<a href='mailto:" . $email . "'>" . $email . "</a>"; 
  print "<tr><td>$name</td><td>$timestamp<td>$mailto_link</td>";
  print "<td><button>approve</button><button>deny</button><button class='expandinfo'>more info</button></tr>";
  $affiliation = $details[MA_ATTRIBUTE_NAME::AFFILIATION];
  $reference = $details[MA_ATTRIBUTE_NAME::REFERENCE];
  $reason = $details[MA_ATTRIBUTE_NAME::REASON];
  $url = $details[MA_ATTRIBUTE_NAME::URL];
  $link = "<a href='" . $url . "'>" . $url . "</a>"; 
  $info = "<b>affiliation: </b>" . ($affiliation != "" ? $affiliation : "NONE")  . "<br>" .
          "<b>reason:      </b>" . ($reason      != "" ? $reason      : "NONE")  . "<br>" .
          "<b>reference:   </b>" . ($reference   != "" ? $reference   : "NONE")  . "<br>" .
          "<b>link:        </b>" . ($url         != "" ? $link        : "NONE")  . "<br>";
  print "<tr class='moreinfo'><td colspan='2'>$info</td>";
  print "<td><button class='hideinfo'>close</button></td><tr>";
}
?>

</tbody></table>

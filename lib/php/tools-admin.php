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

if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

function store_lead_request($urn, $email) {
  $conn = portal_conn();
  $sql = "INSERT into lead_request"
  . " (requester_urn, requester_email) "
  . "values (" . $conn->quote($urn, 'text') . ", "
  .              $conn->quote($email, 'text') . ")";
  db_execute_statement($sql, "insert lead request", true);
}
store_lead_request($user->urn(), $user->email());
?>

<h1>Administrator Tools</h1>

<p>This page is intentionally not blank.</p>
<h2>Open lead requests</h2>
<?php 

$conn = portal_conn();
$sql = "SELECT *"
. " FROM lead_request";
$row = db_fetch_rows($sql, "fetch all lead requests for admin page");
$lead_requests = $row[RESPONSE_ARGUMENT::VALUE];

print "<table><tr><th>username</th><th>requested at</th><th>email</th><th>actions</th></tr>";
foreach ($lead_requests as $request) {
	$username = $request['requester_urn'];
  $timestamp = $request['request_ts'];
  $mailto_link = "<p>No contact info?</p>";
  if (array_key_exists('requester_email', $request)) {
	 $mailto_link = "<a href='mailto:" . $request['requester_email'] . "'>" . $request['requester_email'] . "</a>"; 
  }
  print "<tr><td>$username</td><td>$timestamp<td>$mailto_link</td><td>\o_0/</tr>";
}
?>

</tbody></table>
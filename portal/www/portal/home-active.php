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
?>
<?php
//----------------------------------------------------------------------
// This is sub-content, a part of the home page (home.php).
//----------------------------------------------------------------------

// Notes:
// $user should be bound to the current user

require_once("util.php");
include("services.php");
?>
<div style="width:479px; font-size:80%; text-align:center; float:right; margin:5px 5px 5px 5px;">
  <h2>GENI Resources</h2>
  <img src="/images/staticmap.png" alt="MAP" width="479" height="265"
       style="border:3px solid #000000" />
</div>
<?php
  // Actions / approvals required 
   if ($user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
//  include("tools-admin.php");
}
?>

<?php
if (! $user->portalIsAuthorized()) {
  $km_url = get_first_service_of_type(SR_SERVICE_TYPE::KEY_MANAGER);
  $params['redirect'] = selfURL();
  $query = http_build_query($params);
  $km_url = $km_url . "?" . $query;
  print "<h2>Portal authorization</h2>\n";
  print "The GENI Portal is not authorized by you as a client tool. If you would like";
  print " the GENI Portal to help you manage your projects and slices, you can";
  print " <a href=\"$km_url\">authorize the portal</a> to do so.";
  print "<br/>";
  return 0;
}

// List of my projects
include("tool-projects.php");

// List of my slices
unset($project_id);
unset($project);
print "<h2>My Slices</h2>\n";
include("tool-slices.php");





// Table with GENI wide or per user messages, plus a GENI map
// FIXME: We need a table of messages: account_id, datetime, message
// Then a query by account_id ordered by time
// Do messages timeout? Get deleted by being displayed once?
// Or must the users explicitly delete each one?
?>

<h3>GENI Messages</h3>
<table>
<?php
   // FIXME: foreach project or slice where user is admin or lead, get log entries for that context
   // and forach slice where user is ad
require_once('logging_client.php');
require_once('sr_constants.php');
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$entries = get_log_entries_for_context($log_url, Portal::getInstance(), 
				       CS_CONTEXT_TYPE::MEMBER, $user->account_id);
$new_entries = get_log_entries_by_author($log_url, Portal::getInstance(), $user->account_id);
$entries = array_merge($entries, $new_entries);

$messages = array();
$logs = array();
if (is_array($entries) && count($entries) > 0) {
  foreach($entries as $entry) {
    $msg = $entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME] . $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
    if (!in_array($msg, $messages)) {
      $messages[] = $msg;
      $logs[$msg] = $entry;
    /* } else { */
    /*   error_log("Already in " . $msg); */
    }
  }

  krsort($logs);
  foreach ($logs as $msg => $entry) {
    $rawtime = $entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME];
    $message = $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
    $time = dateUIFormat($rawtime);
    print "<tr><td>$time</td><td>&nbsp;$message</td></tr>\n";
  }
} else {
  print "<tr><td><i>No messages.</i></td></tr>\n";
}

print "</table>";


print "<br/>";

$disable_invite_geni = "";
if ($in_lockdown_mode)
  $disable_invite_geni = "disabled";

print "<button style=\"\" $disable_invite_geni onClick=\"window.location='invite-to-geni.php'\"><b>Invite Someone to GENI</b></button>";

?>
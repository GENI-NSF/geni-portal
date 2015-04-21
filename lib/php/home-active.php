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
?>
<?php
//----------------------------------------------------------------------
// This is sub-content, a part of the home page (home.php).
//----------------------------------------------------------------------

// Notes:
// $user should be bound to the current user

require_once("util.php");
require_once('logging_constants.php');
include("services.php");
require_once("settings.php");
require_once("tool-jfed.php");

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
  print "<p>";
  print "The GENI Portal is not authorized by you as a client tool. If you would like";
  print " the GENI Portal to help you manage your projects and slices, you can";
  print " <a href=\"$km_url\">authorize the portal</a> to do so.";
  print "</p>";
  return 0;
}

include("tool-breadcrumbs.php");

echo "<table style=\"margin-left: 0px;\"><tr><th>Current GENI Clearinghouse Resources</th></tr><tr><td style=\"padding: 0px;margin: 0px\" class='map'>";
include("map.html");
echo "</td></tr></table>";

// List of my projects
include("tool-projects.php");

// List of my slices
unset($project_id);
unset($project);
print "<h2>My Slices</h2>\n";
include("tool-slices.php");
?>

<?php
/* ------------------------------------------------------------
 * Other tools: GENI Desktop (GEMINI), LabWiki (GIMI), WiMAX
 * ------------------------------------------------------------
 */
$gemini_url = relative_url("gemini.php");

  // Code to set up jfed button
if (! isset($jfed_button_start)) {
  $jfedret = get_jfed_strs($user);
  $jfed_script_text = $jfedret[0];
  $jfed_button_start = $jfedret[1];
  $jfed_button_part2 = $jfedret[2];
  if (! is_null($jfed_button_start)) {
    print $jfed_script_text;
  }
}
//  // End of jFed section

  print "<h2>Tools</h2>";
  print "<p>";
  print "<button onClick=\"window.open('$gemini_url')\">";
  print "<b>GENI Desktop</b></button> ";

/* LabWiki */
$labwiki_url = 'http://labwiki.casa.umass.edu';
print "<button onClick=\"window.open('$labwiki_url')\">";
print "<b>LabWiki</b></button> ";

/* // iRODS */
/* if (! isset($disable_irods) or $user->hasAttribute('enable_irods')) { */
/*   print "<button onClick=\"window.location='irods.php'\"><b>Create iRODS Account</b></button> "; */
/* } */

// 6/2014: Open up WiMAX to all
// WiMAX
  if (True || $user->hasAttribute('enable_wimax_button')) {
    $wimax_url = relative_url("wimax-enable.php");
    print "<button onClick=\"window.open('$wimax_url')\">";
    print "<b>Wireless Account Setup</b></button>";
  }

  // Show a jfed button if there wasn't an error generating it
  if (! is_null($jfed_button_start)) {
    print $jfed_button_start . getjFedSliceScript(NULL) . $jfed_button_part2 . "><b>jFed</b></button>";
  }

  // Show cloudlab button
  $cloudlab_url = "https://www.cloudlab.us/login.php";
  $cloudlab_image_url = "https://www.cloudlab.us/img/cloudlab-big.png";
  print "<button style='height:20px;' onClick=\"window.open('$cloudlab_url')\">";
  print "<img src='$cloudlab_image_url' height='100%'/>";
  print "</button>";


  // Show GEE button
  $gee_url = "http://gee-project.org/user";
  print "<button onClick=\"window.open('$gee_url')\">";
  print "<b>GEE</b></button>";

  print "</p>";


?>

<?php
// Table with GENI wide or per user messages, plus a GENI map
// FIXME: We need a table of messages: account_id, datetime, message
// Then a query by account_id ordered by time
// Do messages timeout? Get deleted by being displayed once?
// Or must the users explicitly delete each one?
?>

<h2>GENI Messages</h2>
<table>
<?php
   // FIXME: foreach project or slice where user is admin or lead, get log entries for that context
   // and foreach slice where user is ad
require_once('logging_client.php');
require_once('sr_constants.php');
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$entries = get_log_entries_for_context($log_url, 
				       $user, // Portal::getInstance(), 
				       CS_CONTEXT_TYPE::MEMBER, $user->account_id);
$new_entries = get_log_entries_by_author($log_url, 
					 $user, // Portal::getInstance(), 
					 $user->account_id);
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
    print "<tr><td>$time</td><td>$message</td></tr>\n";
  }
} else {
  print "<tr><td><i>No messages.</i></td></tr>\n";
}

print "</table>";

$disable_invite_geni = "";
if ($in_lockdown_mode)
  $disable_invite_geni = "disabled";

print "<p><button style=\"\" $disable_invite_geni onClick=\"window.location='invite-to-geni.php'\"><b>Invite Someone to GENI</b></button></p>";

?>

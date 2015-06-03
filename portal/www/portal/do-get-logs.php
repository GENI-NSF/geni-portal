<?php
//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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

// Gets logs data for a user, returns it as a nicely formatted table

require_once("settings.php");
require_once("user.php");
require_once('logging_client.php');
require_once('logging_constants.php');
require_once('sr_constants.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);


$num_hours = 24;
if (array_key_exists('hours', $_REQUEST)) {
  $num_hours = (int) $_REQUEST['hours'];
}

// is this for the homepage, projects, or slices?
if (array_key_exists('slice_id', $_REQUEST)) {
  get_slice_log_table($_REQUEST['slice_id']);
} else if (array_key_exists('project_id', $_REQUEST)) {
  get_project_log_table($_REQUEST['project_id']);
} else {
  get_homepage_log_table();
}

function get_slice_log_table($slice_id) {
  global $user;
  global $num_hours;
  global $log_url;
  global $ma_url;

  print "<tr><th>Time</th><th>Message</th><th>Member</th></tr>";
  $entries = get_log_entries_for_context($log_url, $user,
                  CS_CONTEXT_TYPE::SLICE, $slice_id, $num_hours);
  $entry_member_names = lookup_member_names_for_rows($ma_url, $user, $entries, 
                  LOGGING_TABLE_FIELDNAME::USER_ID);
  
  usort($entries, 'compare_log_entries');
  if (is_array($entries) && count($entries) > 0) {
    foreach($entries as $entry) {
      $message = $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
      $time = dateUIFormat($entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME]);
      $member_id = $entry[LOGGING_TABLE_FIELDNAME::USER_ID];
      $member_name = $entry_member_names[$member_id];
      //    error_log("ENTRY = " . print_r($entry, true));
      //      print "<tr><td>$time</td><td>$message</td><td><a href=\"slice-member.php?slice_id=" . $slice_id . "&member_id=$member_id\">$member_name</a></td></tr>\n";
          // FIXME: Want a mailto link
      print "<tr><td>$time</td><td>$message</td><td>$member_name</td></tr>\n";
    }
  } else {
    print "<tr><td></td><td><i>No messages.</i></td><td></td></tr>\n";
  }
}

function get_project_log_table($project_id) {
  global $user;
  global $num_hours;
  global $log_url;
  global $ma_url;

  print "<tr><th>Time</th><th>Message</th><th>Member</th></tr>";
  $entries = get_log_entries_for_context($log_url, 
                 $user, // Portal::getInstance(),
                 CS_CONTEXT_TYPE::PROJECT, $project_id, $num_hours);
  if (is_array($entries) && count($entries) > 0) {
    usort($entries, 'compare_log_entries');
    $entry_member_names = lookup_member_names_for_rows($ma_url, $user, $entries, 
                  LOGGING_TABLE_FIELDNAME::USER_ID);
    foreach($entries as $entry) {
      $message = $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
      $time = dateUIFormat($entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME]);
      $member_id = $entry[LOGGING_TABLE_FIELDNAME::USER_ID];
      $member_name = $entry_member_names[$member_id];
      //    error_log("ENTRY = " . print_r($entry, true));
      // If the MA or other authority took the action, then there is no name and no user so don't show the project-member page
      if ($member_name == "NONE") {
        print "<tr><td>$time</td><td>$message</td><td>$member_name</td></tr>\n";
      } else {
        print "<tr><td>$time</td><td>$message</td><td><a href=\"project-member.php?project_id=" . $project_id . "&member_id=$member_id\">$member_name</a></td></tr>\n";
      }
    }
  }	else {
    print "<tr><td></td><td><i>No messages.</i></td><td></td></tr>\n";
  }
}

function get_homepage_log_table(){
  global $user;
  global $num_hours;
  global $log_url;

  $entries = get_log_entries_for_context($log_url, 
                 $user, // Portal::getInstance(), 
                 CS_CONTEXT_TYPE::MEMBER, $user->account_id, $num_hours);
  $new_entries = get_log_entries_by_author($log_url, 
             $user, // Portal::getInstance(), 
             $user->account_id, $num_hours);
  $entries = array_merge($entries, $new_entries);

  $messages = array();
  $logs = array();
  if (is_array($entries) && count($entries) > 0) {
    foreach($entries as $entry) {
      $msg = $entry[LOGGING_TABLE_FIELDNAME::EVENT_TIME] . $entry[LOGGING_TABLE_FIELDNAME::MESSAGE];
      if (!in_array($msg, $messages)) {
        $messages[] = $msg;
        $logs[$msg] = $entry;
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
    print "<tr><td></td><td><i>No messages.</i></td><td></td></tr>\n";
  }
}

?>

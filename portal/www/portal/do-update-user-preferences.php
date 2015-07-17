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

require_once("user.php");
require_once("db_utils.php");
require_once("ma_constants.php");
require_once("ma_client.php");
require_once("util.php");
include_once('/etc/geni-ch/settings.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  exit();
}

if (array_key_exists('user_urn', $_REQUEST)) {
  set_preferences($_REQUEST['user_urn'], $_REQUEST);
}

function set_preferences($user_urn, $preferences) {
  $actual_prefs = array('show_map'); 
  $conn = portal_conn();
  foreach ($preferences as $pref_name => $pref_value) {
    if (in_array($pref_name, $actual_prefs)) {
      print "setting preferences for user $user_urn $pref_name to value $pref_value";
      $sql = "UPDATE user_preferences set "
      . "$pref_name = "   . $conn->quote($pref_value, 'text')
      . "where user_urn = " . $conn->quote($user_urn, 'text');

      $sql = "INSERT INTO user_preferences (user_urn, $pref_name) " 
          .  "VALUES(1, "A", 19) ON DUPLICATE KEY UPDATE    
name=VALUES(name), age=VALUES(age)"
      $db_response = db_execute_statement($sql, "Update user preferences");
      $db_error = $db_response[RESPONSE_ARGUMENT::OUTPUT];
      if($db_error == ""){
        print "Response successfully stored";
      } else {
        print "DB error: " . $db_error;
        error_log("DB error when updating user_preferences table: " . $db_error);
      }
    }
  }
}

?>
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

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  exit();
}

function get_preference($user_urn, $preference) {
  $actual_prefs = array('show_map'); 
  $conn = portal_conn();
  if (in_array($preference, $actual_prefs)) {
    $sql = "SELECT * from user_preferences "
    . "where user_urn = " . $conn->quote($user_urn, 'text')
    . "and preference_name = " . $conn->quote($preference, 'text');
    $db_res = db_fetch_row($sql);
    $db_response = db_fetch_row($sql, "Get user preference");

    $db_error = $db_response[RESPONSE_ARGUMENT::OUTPUT];
    if($db_error != "") { // TODO: What do we do here
      error_log("DB error when getting row from user_preferences table: " . $db_error);
      return true;
    } else {
      return $db_response[RESPONSE_ARGUMENT::VALUE]['preference_value'];
    }
  }
}

?>
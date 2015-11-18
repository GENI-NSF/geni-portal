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

// Map of preference names to choices for that pref. The first element in the array
// of choices is the default value.
$possible_prefs = array(
  "homepage_tab" => array("Slices", "Projects", "Logs", "Map"),
  "homepage_view" => array("cards", "table"),
  "slice_view" => array("Resources", "Aggregates", "Map")
);

function get_preference($user_urn, $preference) {
  global $possible_prefs;
  $conn = portal_conn();
  if (array_key_exists($preference, $possible_prefs)) {
    $sql = "SELECT * from user_preferences "
    . "where user_urn = " . $conn->quote($user_urn, 'text')
    . "and preference_name = " . $conn->quote($preference, 'text');
    $db_response = db_fetch_row($sql, "Get user preference");

    $db_error = $db_response[RESPONSE_ARGUMENT::OUTPUT];
    $default_option = $possible_prefs[$preference][0];
    if($db_error != "") { // TODO: What do we do here
      error_log("DB error when getting row from user_preferences table: " . $db_error);
      return $default_option;
    } else {
      if ($db_response[RESPONSE_ARGUMENT::VALUE]['preference_value']) {
        return $db_response[RESPONSE_ARGUMENT::VALUE]['preference_value'];
      } else {
        return $default_option;
      }
    }
  } else {
    error_log("Unknown preference '$preference' requested for user '$user_urn'");
    return "";
  }
}

?>
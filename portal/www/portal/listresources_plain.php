<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive() ) {
  relative_redirect('home.php');
}
?>
<?php

// Takes an arg am_id which may have multiple values. Each is treated
// as the ID from the DB of an AM which should be queried
// If no such arg is given, then query the DB and query all registered AMs

if (! isset($ams) || is_null($ams)) {
  // Didnt get an array of AMs
  if (! isset($am) || is_null($am)) {
    // Nor a single am
    $ams = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  } else {
    $ams = array();
    $ams[] = $am;
  }
}

if (! isset($ams) || is_null($ams) || count($ams) <= 0) {
  error_log("Found no AMs!");
  $slivers_output = "No AMs registered.";
} else {
  $slivers_output = "";

  foreach ($ams as $am) {
    if (is_array($am)) {
      if (array_key_exists(SR_TABLE_FIELDNAME::SERVICE_URL, $am)) {
	$am_url = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
      } else {
	error_log("Malformed array of AM URLs?");
	continue;
      }
    } else {
      $am_url = $am;
    }

    $result = list_resources($am_url, $user);
    // error_log("VERSION = " . $result);
    
    
    error_log("ListResources output = " . $result);
    $slivers_output = $slivers_output . $result . "\n";
  }
}

$header = "ListResources";
$text = $slivers_output;

require_once("header.php");
show_header('GENI Portal: Debug',  $TAB_DEBUG);
print "<h2>$header</h2>\n";
$text2 = explode("\n",$text);
foreach ($text2 as $line_num => $line) {
    echo htmlspecialchars($line) . "<br />\n";
}

print "\n";
include("footer.php");

?>

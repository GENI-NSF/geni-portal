<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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

// error_log('$_GET = ' . print_r($_GET, true));


 require_once("header.php");

// If referer is ?register? then include the 0 to not load the user
$referer_key = 'HTTP_REFERER';
$referer = "";
if (key_exists($referer_key, $_SERVER)) {
  $referer = $_SERVER[$referer_key];
}

$system_error = false;
$load_user_on_show_header = false;
if (key_exists("system_error", $_GET)) {
  $system_error = true;
  $load_user_on_show_header = false;
}
//error_log("ET.SYSTEM_ERROR = " . print_r($system_error, true));

if (strpos($referer, 'register') !== false or strpos($referer, 'activate') !== false) {
  show_header('GENI Portal: Home',  $TAB_SLICES, false);
} else {
  show_header('GENI Portal: Home',  $TAB_SLICES, $load_user_on_show_header);
}
$header = "Error";
print "<h1>$header</h1>\n";
// print "Project name: <b>$slice_project_name</b><br/>\n";

if (key_exists("error", $_GET)) {
  $error_text = urldecode($_GET["error"]);
//  error_log("ET = " . $error_text);
  if ($system_error) {
    $error_text = htmlentities($error_text);
  }
  echo "<p class='warn'>" . $error_text . "</p><br/>\n";
} else {
  // error_log('$_SERVER = ' . print_r($_SERVER, true));
  
  foreach ($_GET as $line_num => $line) {
    //  error_log("LINE_NUM " . $line_num);
    $text = str_replace('_', ' ', htmlspecialchars(urldecode($line_num)));
    echo $text . "<br />\n";
  }
}

print "<br/>\n";
print "<form method=\"GET\" action=\"back\">";
print "\n";
print "<input type=\"button\" value=\"Back\" onClick=\"history.back(-1)\"/>\n";
print "\n";
print "</form>";
print "\n";

include("footer.php");
?>

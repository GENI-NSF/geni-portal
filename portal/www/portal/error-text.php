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

error_log('$_GET = ' . print_r($_GET, true));


 require_once("header.php");
$referer_key = 'HTTP_REFERER';
$referer = "";
if (key_exists($referer_key, $_SERVER)) {
  $referer = $_SERVER[$referer_key];
}
error_log("SERVER: " . print_r($_SERVER,true)); 
$system_error = false;
if (key_exists("system_error", $_GET)) {
  $system_error = true;
}

show_header('GENI Portal: Home',  $TAB_SLICES, false);
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
$email_text=gmdate("Y-m-d H:i");
$email_text .= "%0D%0A";
$email_text .= $error_text;
$email_text .= "%0D%0A";
$email_text .= "HTTP REFERER: " . $referer;
$email_text .= "%0D%0A";
$email_text .= "%0D%0A";
$email_text .= "User email: " . $_SERVER['mail'] ;
$email_text .= "%0D%0A";
$email_text .= "User eppn: " . $_SERVER['eppn'] ;
$email_text .= "%0D%0A";
$email_text .= "%0D%0A";
$email_text .= "User questions or comments (please add): ";

print "<a href='mailto:portal-help@geni.net?subject=Portal Error&body=$email_text'>Need help? Report a problem?</a>";
print "<br />";
print "<br/>\n";
print "<form method=\"GET\" action=\"back\">";
print "\n";
print "<input type=\"button\" value=\"Back\" onClick=\"history.back(-1)\"/>\n";
print "\n";
print "</form>";
print "\n";

include("footer.php");
?>

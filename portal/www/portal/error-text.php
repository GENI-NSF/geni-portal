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
require_once("header.php");
show_header('GENI Portal: Home',  $TAB_SLICES);
$header = "Error";
print "<h2>$header</h2>\n";
// print "Project name: <b>$slice_project_name</b><br/>\n";

// error_log('$_GET = ' . print_r($_GET, true));

// error_log('$_SERVER = ' . print_r($_SERVER, true));

foreach ($_GET as $line_num => $line) {
  //  error_log("LINE_NUM " . $line_num);
  //  error_log("LINE " . $line);
  $text = str_replace('_', ' ', htmlspecialchars($line_num));
  echo $text . "<br />\n";
}

print "\n";
print "<form method=\"GET\" action=\"back\">";
print "\n";
$http_referer = $_SERVER['HTTP_REFERER'];
//error_log("HTTP_REFERER = " . $http_referer);
print "<input type=\"button\" value=\"Back\" onClick=\"history.back(-1)\"/>\n";
print "\n";
print "</form>";
print "\n";

include("footer.php");
?>

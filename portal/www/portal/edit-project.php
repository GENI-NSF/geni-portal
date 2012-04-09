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

require_once("user.php");
require_once("header.php");
show_header('GENI Portal: Projects', $TAB_PROJECTS);
$user = geni_loadUser();
$project = "<new>";
$isnew = true;
if (array_key_exists("id", $_GET)) {
  $project = $_GET['id'];
  $isnew = false;
  print "<h1>EDIT GENI Project: " . $project . "</h1>\n";
} else {
  print "<h1>NEW GENI Project</h1>\n";
}
?>
<form method="POST" action="do-edit-project.php">
<?php
  if (! $isnew) {
    print "<input type=\"hidden\" name=\"id\" value=\"$project\"/>\n";
  }
$fields = array("Name", "Purpose");
foreach ($fields as $field) {
  print "<b>$field</b>: <input type=\"text\" name=\"$field\" ";
  if (! $isnew) {
    $v = "foo$field"; // FIXME: pull from DB
  }
  print "value=\"$v\"/><br/>\n";
}
print "<br/><br/>\n";
print "FIXME: Per project policy defaults go here.<br/><br/>\n";
if ($isnew) {
  print "Provide a comma-separate list of email addresses of people to invite to your project:<br/>\n";
  print "<input type=\"textarea\" name=\"invites\"/>\n";
} else {
  print "<h3>Project members</h3>\n";
  print "<table border=\"1\">\n";
  print "<tr><th>Project Member</th><th>Roles</th></tr>\n";
  print "<tr><td><a href=\"/project-member.php?id=joe\">Joe</a></td><td>Lead</td></tr>\n";
  print "</table>\n";
}
print "<br/>\n";
if (! $isnew) {
  print "Enter email of proposed new project leader to invite them:<br/>\n";
  print "<input type=\"text\" name=\"newlead\"/><br/>\n";
}
print "<input type=\"submit\" value=\"";
if ($isnew) {
  print "Register\"/>\n";
} else {
  print "Edit\"/>\n";
}
print "</form>\n";

include("footer.php");
?>

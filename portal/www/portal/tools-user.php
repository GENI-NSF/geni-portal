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
require_once("user.php");
?>
<h1>User Tools</h1>
<h2>Public Key</h2>
<?php
$key = db_fetch_public_key($user->account_id);
if ($key) {
  print "\n<table border=\"1\">\n";
  print "<tr><th>Name</th> <th>Description</th> <th>Certificate</th></tr>\n";
  $download_url = relative_url("certificate.php");
  print "<tr>"
    . "<td>" . htmlentities($key['filename']) . "</td>"
    . "<td>" . htmlentities($key['description']) . "</td>"
    . "<td><a href=\"" . $download_url . "\">Download Certificate</a></td>"
    . "</tr>\n";
  print "</table>\n";
} else {
  print "<a href=\"uploadkey.php\">Please upload a public key</a>\n";
}
?>
<h2>ABAC</h2>
<?php
print "<a href=\"abac-id.php\">Download your ABAC ID</a><br/>\n";
print "<a href=\"abac-key.php\">Download your ABAC private key</a>\n";
?>

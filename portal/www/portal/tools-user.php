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
<?php
/*----------------------------------------------------------------------
 * SSH key management
 *----------------------------------------------------------------------
 */
print "<h2>SSH Keys</h2>\n";
$keys = fetchSshKeys($user->account_id);
if (count($keys) == 0)
  {
    // No ssh keys are present.
    print "No ssh keys have been uploaded. ";
    print "Please <button onClick=\"window.location='uploadsshkey.php'\">Upload an SSH key</button>.\n";
  }
else
  {
    print "\n<table border=\"1\">\n";
    print "<tr><th>Name</th><th>Description</th></tr>\n";
    foreach ($keys as $key)
      {
        print "<tr>"
          . "<td>" . htmlentities($key['filename']) . "</td>"
          . "<td>" . htmlentities($key['description']) . "</td>"
          . "</tr>\n";
      }
    print "</table>\n";
    print "<br/>\n";
    print "<button onClick=\"window.location='uploadsshkey.php'\">Upload another SSH key</button>.\n";
  }


/*----------------------------------------------------------------------
 * SSL key management
 *----------------------------------------------------------------------
 */
print "<h2>Keys and Certificates for command line tools</h2>\n";
$key = db_fetch_public_key($user->account_id);
if ($key) {
  print "\n<table border=\"1\">\n";
  print "<tr><th>Name</th> <th>Description</th> <th>Certificate</th></tr>\n";
  $download_url = relative_url("certificate.php");
  print "<tr>"
    . "<td>" . htmlentities($key['filename']) . "</td>"
    . "<td>" . htmlentities($key['description']) . "</td>"
    . "<td><button onClick=\"window.location='" . $download_url . "'\">Download Certificate</button></td>"
    . "</tr>\n";
  print "</table>\n";
} else {
  print "<button onClick=\"window.location='uploadkey.php'\">Please upload a public key</button>\n";
}


/*----------------------------------------------------------------------
 * ABAC (if enabled)
 *----------------------------------------------------------------------
 */
if ($portal_enable_abac)
  {
    print '<h2>ABAC</h2>\n';
    print "<button onClick=\"window.location='abac-id.php'\">Download your ABAC ID</button><br/>\n";
    print "<button onClick=\"window.location='abac-key.php'\">Download your ABAC private key</button>\n";
  }
?>

<h2>Edit Account Details</h2>
Modify user supplied account details <button onClick="window.location='modify.php'">here</button><br/>

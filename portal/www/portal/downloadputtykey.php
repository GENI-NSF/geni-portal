<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

// File to download putty version of private SSH key
// Note: Requires that putty be installed on server 
//   (apt-get install putty or yum install putty)
//
// Once we have a private SSH key (RSA or DSA format)
// we run:
// puttygen $PRIVATE_KEY -o $PUTTY_PRIVATE_KEY

require_once("settings.php");
require_once("user.php");
require_once("header.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$key_id = NULL;
if (array_key_exists('id', $_GET)) {
  $key_id = $_GET['id'];
}

if (is_null($key_id)) {
  relative_redirect('home.php');
}

$GENI_TITLE = "Download PuTTY Key";
$load_user = TRUE;
show_header('GENI Portal: Profile', $load_user);
include("tool-breadcrumbs.php");

print "<h1>Download PuTTY Key</h1>";
print "<p>To download your SSH private key in PuTTY for Windows format, enter your SSH key passphrase (as entered on the Portal when you generated your SSH keys).</p>\n";
print "<form action=\"do-downloadputtykey.php\" method=\"post\">\n";
print "<p><label for=\"passphrase\">Passphrase:</label>\n";
print "<input type=\"password\" name=\"passphrase\">\n";
print "<input type=\"submit\" name=\"submit\" value=\"Download PuTTY key\"></p>";
print "<input type=\"hidden\" name=\"key_id\" value=\"$key_id\"/>\n";
print "</form>";

print "<button onClick=\"history.back(-1)\">Cancel</button>";
print "<button onClick=\"window.location='home.php'\">Return to Portal Home</button>";
print "<button onClick=\"window.location='profile.php#ssh'\">Jump to Profile page SSH Tab</button>";
include("footer.php");

?>

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
require_once("header.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");


/*----------------------------------------------------------------------
 * SSL key management
 *----------------------------------------------------------------------
 */

print "<h1>Omni command line resource reservation tool</h1>\n";

print "<p>";
$configalert = "Download and use a template omni_config file for use with the <a href='http://trac.gpolab.bbn.com/gcf/wiki'>Omni</a> command line resource reservation tool.\n"
	     . "<br/>\n" 
	     . "<ol>\n"
	     . "<li>Download this <a href='portal_omni_config.php'>omni_config</a> and save it to a file named <code>portal_omni_config</code>.</li>\n" 
	     . "<li> Download your certificate, noting the path.</li>\n" 
	     . "<li> Edit the <code>portal_omni_config</code> to correct: </li>\n" 
	     . "<ol>\n" 
	     . "<li>the certificate path,</li>\n" 
	     . "<li>the path to the SSL private key used to generate your certificate, and </li> \n" 
	     . "<li> the path to your SSH public key to use for node logon.</li>\n" 
	     . "</ol>\n" 
	     . "<li> When running omni: </li>\n" 
	     . "<ol>\n" 
	     . "<li> Specify the path to the <code>omni_config</code>, specify the project name, and the full slice URN.  For example: </li>\n"
	     . "<ul><li><code>omni -c portal_omni_config --project <project name> sliverstatus <slice URN></code></li></ul>\n" 
	     . "<li> Use the full slice URN when naming your slice, not just the slice name.</li>\n" 
	     . "</ol>\n" 
	     . "</ol>\n" 
	     . "\n"; 

print "$configalert\n";
print "</p>";
include("footer.php");

?>

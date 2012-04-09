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
?>
<?php
//----------------------------------------------------------------------
// This is sub-content, a part of the home page (home.php).
//----------------------------------------------------------------------

// Notes:
// $user should be bound to the current user
?>
<center>
Welcome, 
<?php
print $user->prettyName();
?>
!
</center>
<?php
  // Actions / approvals required 
if ($user->privAdmin()) {
  include("tools-admin.php");
}
  // messages for you or all
print "<h2>GENI Messages</h2>\n<br/>\n";
print "GENI is really rocking today!\n";

  // GENI map?
print "<h3>GENI Map<h3>\n<br/>\n";
print "<a href=\"http://groups.geni.net/geni/wiki/ProtoGENIFlashClient\"><image width=\"25%\" src=\"http://groups.geni.net/geni/attachment/wiki/ProtoGENIFlashClient/pgfc-screenshot.jpg?format=raw\"/></a>\n";

  // List of my projects
  print "<h2>My Projects</h2>\n";
?>
<ul>
<li><a href="project.php?id=MyProject">My Project</a><br/></li>
<li><a href="project.php?id=MyOtherProject">My Other Project</a><br/></li>
</ul>
<br>
<?php

  // List of my slices
  print "<h2>My Slices</h2>\n";
  include("tool-slices.php");

  // Download outside cert & regen certs, or upload key
include("tools-user.php");

  // Links to wiki, help, tutorials
?>
<h2>GENI Help</h2>
<ul>
<li><a href="http://groups.geni.net/geni/wiki">GENI Wiki</a></li>
<li>Other links here</li>
</ul>

<?php
//print "<hr/>\n";
//include("tools-slice.php");
//print "<hr/>\n";
?>

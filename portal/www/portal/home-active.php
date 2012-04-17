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

// Table with GENI wide or per user messages, plus a GENI map
// FIXME: We need a table of messages: account_id, datetime, message
// Then a query by account_id ordered by time
// Do messages timeout? Get deleted by being displayed once?
// Or must the users explicitly delete each one?
?>
<table width="50%">
<tr><th width="25%"><h3>GENI Messages</h3></th>
<!-- <th width="25%"><h3>GENI Map</h3></th> -->
</tr>
<tr><td>
<ul>
<li>Friday, 4/6/12: You've been added to Project: <a href="project.php?id=MyProject">MyProject</a></li><br/>
<li>Tuesday, March 12, 2012: GENI is really rocking today!</li>
</ul>
</td>
<!-- 
<td align="center">
<a href="http://groups.geni.net/geni/wiki/ProtoGENIFlashClient"><image width="50%" src="http://groups.geni.net/geni/attachment/wiki/ProtoGENIFlashClient/pgfc-screenshot.jpg?format=raw"/></a>
</td>
-->
</tr></table>

<?php
// List of my projects
print "<h2>My Projects</h2>\n";
include("tool-projects.php");
?>

<br>

<?php
// List of my slices
unset($project_id);
unset($project);
print "<h2>My Slices</h2>\n";
include("tool-slices.php");

?>

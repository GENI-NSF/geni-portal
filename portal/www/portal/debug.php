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

require_once("user.php");
require_once("header.php");
$user = geni_loadUser();
show_header('GENI Portal: Debug', $TAB_DEBUG);
?>
<div id="debug-body">
<h2>RQ</h2>
<?php
print "<a href=\"request_test.php\">Request Test</a>\n";
?>
<h2>FLACK</h2>
<?php
print "<a href=\"flack_test.php\">Flack Test</a>\n";
?>
<h2>DB</h2>
<?php
print "<a href=\"db_error_test.php\">Database Error Test</a>\n";
?>
<h2>LOG</h2>
<?php
print "<a href=\"logging_test.php\">Logging Test</a>\n";
?>
<h2>SR</h2>
<?php
print "<a href=\"sr_controller_test.php\">Service Registry Test</a>\n";
?>
<h2>AuthZ</h2>
<?php
print "<a href=\"permission_manager_test.php\">Permission Manager Test</a>\n";
?>
<h2>PA</h2>
<?php
print "<a href=\"pa_controller_test.php\">Project Authority Test</a>\n";
?>
<h2>SA</h2>
<?php
print "<a href=\"sa_controller_test.php\">Slice Authority Test</a>\n";
?>
<h2>MA</h2>
<?php
print "<a href=\"ma_controller_test.php\">Member Authority Test</a>\n";
?>
<h2>CS</h2>
<?php
print "<a href=\"cs_controller_test.php\">Credential Store Test</a>\n";
?>

<h2>AM</h2>
<?php
print "<a href=\"getversion.php\">GetVersion on AM</a>\n";
?>
<br/>
<?php
print "<a href=\"listresources_plain.php\">ListResources on AM (no slice)</a>\n";
?>
<h2>Cache</h2>
<?php
print "<a href=\"debug_clearcache.php\">Clear cache</a>\n";
?>

</div><!-- debug-body -->
<br/>
<?php
include("footer.php");
?>

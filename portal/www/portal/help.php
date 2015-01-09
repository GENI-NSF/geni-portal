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

require_once("user.php");
require_once("header.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
skip_km_authorization();
show_header('GENI Portal: Help', $TAB_HELP);
include("tool-breadcrumbs.php");
$hostname = $_SERVER['SERVER_NAME'];
// Links to wiki, help, tutorials
?>
<h1>GENI Help</h1>
<ul>
<li><a href="http://groups.geni.net/geni/wiki">GENI Wiki</a> -- GENI documentation</li>
<li><a href="http://gmoc.grnoc.iu.edu/gmoc/index/support.html">GENI Meta-Operations Center (GMOC)</a> -- Create and search trouble tickets.  Calendar of planned and unplanned outages.</li>
<li><a href="http://groups.geni.net/geni/wiki/GENIGlossary">GENI Glossary</a></li>
<li><a href="https://
<?php
print "$hostname";
?>
/login-help.php">GENI Accounts and Portal Login Issues</a></li>
  <li><a href="mailto:portal-help@geni.net">Email Portal Help</a> for questions, comments or problems using this website.</li>
  <li><a href="mailto:help@geni.net">Email GENI Help</a> for questions or issues with GENI resources, designing experiments, or other general GENI issues.</li>
</ul>
<?php
include("footer.php");
?>

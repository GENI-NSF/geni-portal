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

function show_header($title)
{
  global $extra_js;

  echo '<!DOCTYPE HTML>';
  echo '<html>';
  echo '<head>';
  echo '<title>';
  echo $title;
  echo '</title>';
  /* Stylesheet(s) */
  echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
  echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';

  /* Close the "head" */
  echo '</head>';
  echo '<body>';

  echo '<div id="header" class="landingpage">';

  echo '<div id="geni">';
  echo '<img src="/images/geni.png" alt="GENI"/>';
  echo '</div>';

  echo '<div id="welcome">';
  echo '<h1> Welcome to GENI </h1>';
  echo '<div id="desc">';
  echo '<a href="http://www.geni.net">GENI</a> is a new, nationwide suite of infrastructure supporting ';
  echo '"at scale" research in networking, distributed systems, security, and novel applications. ';
  echo 'It is supported by the <a href="http://www.nsf.gov/">National Science Foundation</a>, ';
  echo 'and available without charge for research and classroom use.';
  echo '</div>';
  echo '</div>';

  echo '<div id="loginHelpdiv">';
  echo '<a href="secure/home.php">';
  echo '  <img id="usegeni" src="/images/UseGENI.png" alt="Use GENI"/>';
  echo '</a>';
  echo '</div>';

  echo '</div>';
  echo '<hr/>';


  echo '<div id="content"  class="landingpage">';
}

show_header("Troubleshooting logging into GENI")
?>

<h1> Logging into GENI </h1>

<p> 
GENI is freely available for research and educational purposes. To get
started, simply log in and activate your account.<br/>

GENI allows users to log in using their existing accounts via our partnership in the <a href="http://www.incommonfederation.org">InCommon</a> federation. 
<ul>
  <li>If you are affiliated with a US college or university that is a
  <a
  href="http://www.incommon.org/federation/info/all-entities.html">member
  of the InCommon federation</a>, simply login
  using your usual username and password to activate
  your GENI account.
  </li><li>If you are not affiliated with an InCommon federated
  institution, you may <a href="mailto:portal-help@geni.net">request a GENI-only login</a>.</li>
</ul>
</p>

<h1> Troubleshooting logging into GENI </h1>
<h2>If you don't know if you are a member of an InCommon federated institution...</h2>
<ul>
<li>
... check this list of <a href="http://www.incommon.org/federation/info/all-entities.html">members of the InCommon federation</a>.
</li>
</ul>


<h2 id='requestGENIAccount''>If you are a member of an InCommon federated institution...</h2>
<ul>
<li>
<b>... and you don't have an account at your home institution</b>, contact your local IT department or computer help desk to inquire about getting a login.
</li>
<li>
<b>... and you have an account, but can't log in</b>. Your login and password are managed at your local instition.  Contact your local IT department or computer help desk for assistance.
</li>
</ul>


<h2>If you are NOT a member of an InCommon federated institution...</h2>

<ul>
<li>
<b>... and don't have a login or can't login</b>, contact <a
href="mailto:portal-help@geni.net">GENI help</a> and request a GENI-only login. 
</li>
</ul>

<h2>For all other issues...</h2>
<ul>
<li>
... contact <a href="mailto:portal-help@geni.net">GENI help</a>. Please describe your problem and the date and time you observed the issue.
</li>
</ul>

<!-- THIS SHOULD BE IN A COMMON FOOTER FILE --> 
</div>
<hr/>
<div id="footer"  class="landingpage">
  <small><i><a href="http://www.geni.net/">GENI</a> is sponsored by the
  <a href="http://www.nsf.gov/"><img
  src="https://www.nsf.gov/images/logos/nsf1.gif" alt="NSF Logo"
  height="25" width="25"> National Science Foundation</a></i></small>
</div>
</body>
</html>

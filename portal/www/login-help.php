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

function show_last_message() {
  $message_key = 'lastmessage';
  session_start();
  if (isset($_SESSION[$message_key])) {
    $last_message = $_SESSION[$message_key];
    unset($_SESSION[$message_key]);
  }
  session_write_close();
  if (isset($last_message)) {
    echo "<center><p class='instruction'>$last_message</p></center>";
  }
}



  global $extra_js;

  echo '<!DOCTYPE HTML>';
  echo '<html>';
  echo '<head>';
  echo '<title>';
  echo "Troubleshooting logging into GENI";
  echo '</title>';
  /* Stylesheet(s) */
  echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
  echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';
  echo '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|PT+Serif:400,400italic|Droid+Sans+Mono" rel="stylesheet" type="text/css">';
  /* Close the "head" */
  echo '</head>';
  echo '<body>';

  echo '<div id="welcome">';

  echo '<div id="welcome-left"><img src="/images/geni.png" alt="GENI"/></div>';
  echo '<div id="welcome-right">';
    echo '<div id="welcome-right-top">';
    show_last_message();
  echo '<h1> Welcome to GENI </h1>';
  echo '<a href="http://www.geni.net">GENI</a> is a new, nationwide suite of infrastructure supporting ';
  echo '"at scale" research in networking, distributed systems, security, and novel applications. ';
  echo 'It is supported by the <a href="http://www.nsf.gov/">National Science Foundation</a>, ';
  echo 'and available without charge for research and classroom use.';

?>

  <a href='secure/home.php'>
    <img src="/images/UseGENI.png" id="usegeni" alt="Use GENI"/>
  </a>

<h2> Logging into GENI </h2>

<p> 
GENI is freely available for research and educational purposes. To get
started, simply log in and activate your account.</p>

<p>
GENI allows users to log in using their existing accounts via our partnership in the <a href="http://www.incommonfederation.org">InCommon</a> federation. 
<ul>
  <li>If you are affiliated with a US college or university that is a
  <a
  href="http://www.incommon.org/federation/info/all-entities.html">member
  of the InCommon federation</a>, simply login
  using your usual username and password to activate
  your GENI account.
  </li><li>If you are not affiliated with an InCommon federated
  institution, you may <a href="https://shib-idp.geni.net/geni/request.html">request a GENI-only login</a>.</li>
</ul>
</p>

<h2> Troubleshooting logging into GENI </h2>
<h3>If you don't know if you are a member of an InCommon federated institution...</h3>
<ul>
<li>
... check the list of <a href="http://www.incommon.org/federation/info/all-entities.html">members of the InCommon federation</a>.
</li>
</ul>


<h3 id='requestGENIAccount''>If you are a member of an InCommon federated institution...</h3>
<ul>
<li>
<b>... and you don't have an account at your home institution</b>, contact your local IT department or computer help desk to inquire about getting a login.
</li>
<li>
<b>... and you have an account, but can't log in</b>. Your login and password are managed at your local institution.  Contact your local IT department or computer help desk for assistance.
</li>
</ul>


<h3>If you are NOT a member of an InCommon federated institution...</h3>

<ul>
<li>
<b>... and don't have a login or can't login</b>,
  <a href="https://shib-idp.geni.net/geni/request.html">request a GENI-only login</a>.
</li>
</ul>

<h3>If your account is with the GENI Identity Provider but you have forgotten your password...</h3>
<ul>
<li>
... Go <a href="https://shib-idp.geni.net/geni/request.html">here</a> and submit an account request using the same username and email address that you used last time, provide a new password, and be sure to check the 'Password Change Request' box.
</li>
</ul>

<h3>For all other issues...</h3>
<ul>
<li>
... contact <a href="mailto:help@geni.net">GENI help</a>. Please describe your problem and the date and time you observed the issue.
</li>
</ul>

<!-- THIS SHOULD BE IN A COMMON FOOTER FILE --> 
</div>
</div>
</div>
<div id="footer">
<div id="footer-left">
  <!-- <a href="https://portal.geni.net">GENI Portal Home</a><br>
  <a href="http://www.geni.net">GENI Home</a><br>
  <a href="http://groups.geni.net/geni">GENI Wiki</a> -->
</div>
<div id="footer-right">
  Copyright &copy; 2013 BBN Technologies<br>
  All Rights Reserved - NSF Award CNS-0714770<br>
  <a href="http://www.geni.net/">GENI</a> is sponsored by the <a href="http://www.nsf.gov/"><img src="/common/nsf1.gif" alt="NSF Logo" height="16" width="16"> National Science Foundation</a>
</div>
</div>
</body>
</html>

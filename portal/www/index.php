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
  echo '<a href="http://www.geni.net">GENI</a> is a <a
  href="http://www.nsf.gov/">National Science Foundation</a> funded freely available virtual testbed supporting computer networking and science research and education.';
  echo '</div>';
  echo '</div>';

  //  echo '<div id="loginHelpdiv">';
  //  echo '<a id ="loginHelp" href="login-help.php">Get Help</a>';
  //  echo '</div>';

  echo '</div>';

  echo '<hr/>';

  echo '<div id="landingcontent" class="landingpage">';
}

show_header("Welcome to GENI")
?>
<div id='blank'>

<img id="incommon-logo" src="common/InC_Participant.png"/>


</div>
<div id='main'>

  <a href='secure/home.php'>
    <img id='usegeni' src="/images/UseGENI.png" alt="Use GENI"/>
  </a>


<!-- 
<h2>GENI News</h2>
<ul>
<li>
 Recent activity pulled from the log table?
</li><li>
<ul><li>
   New project created at 1:23pm
</li><li>
   New slice created at 2:05am
</li></ul>
</li>
</ul> 
-->

<h2>Find out more about using GENI</h2>
<ul><li>
   <a href="http://groups.geni.net/geni/wiki/GENIExperimenterWelcome">Information for GENI experimenters</a>
</li><li>
  <a href="http://groups.geni.net/geni/wiki/UnderstandingGENI">Understanding GENI</a> (includes a list of available resources)
</li><li>
  See where GENI resources are located using <a href="http://protogeni.net/flack">Flack</a>, a graphical GENI resource reservation tool
</li><li>
  Get <a href="mailto:help@geni.net">help</a> using GENI
</li>
</ul>


<!-- THIS SHOULD BE IN A COMMON FOOTER FILE --> 
</div>

<div id='map'>
  <img src="/images/staticmap.png" alt="MAP" width="479" height="265"
       style="border:3px solid #000000" />
  <div class='legend'>These are some of the many resources being used in GENI experiments across the country.</div>
</div>
</div>

<hr/>

<div id="footer" class="landingpage">
  <small><i><a href="http://www.geni.net/">GENI</a> is sponsored by the
  <a href="http://www.nsf.gov/"><img
  src="http://www.nsf.gov/images/logos/nsf1.gif" alt="NSF Logo"
  height="25" width="25"> National Science Foundation</a></i></small>
</div>

</body>
</html>

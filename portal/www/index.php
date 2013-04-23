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

  //  echo '<div id="loginHelpdiv">';
  //  echo '<a id ="loginHelp" href="login-help.php">Get Help</a>';
  //  echo '</div>';

  echo '</div>';

  echo '<hr/>';

  echo '<div id="landingcontent" class="landingpage">';
}

show_header("Welcome to GENI");

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

?>
<div id='blank'>&nbsp;
</div>
<div id='main'>

<?php show_last_message();?>
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
   <a href="http://www.geni.net/experiment">Information for GENI experimenters</a>
</li><li>
  <a
  href="http://groups.geni.net/geni/wiki/GENIBibliography">Published research that used GENI resources</a>
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
  src="https://www.nsf.gov/images/logos/nsf1.gif" alt="NSF Logo"
  height="25" width="25"> National Science Foundation</a></i></small>
</div>

</body>
</html>

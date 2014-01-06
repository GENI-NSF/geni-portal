<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
  echo "Welcome to the GENI Experimenter Portal";
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
  echo '</div>';





?>


<div id="welcome-right-left">
  <a href='secure/home.php' title='Login to the GENI Experimenter Portal''>
    <img src="/images/UseGENI.png" id="usegeni" alt="Use GENI"/>
  </a>
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
</div>

<div id="welcome-right-right">
<?php include "common/map/map-small.html"; ?>
<p><i>These are some of the many resources being used in GENI experiments across the country.</i></p>
</div>








</div>

</div>

<!-- THIS SHOULD BE IN A COMMON FOOTER FILE --> 
<div id="footer">
<div id="footer-left">
  <!-- <a href="https://portal.geni.net">GENI Portal Home</a><br>
  <a href="http://www.geni.net">GENI Home</a><br>
  <a href="http://groups.geni.net/geni">GENI Wiki</a> -->
</div>
<div id="footer-right">
  GENI Portal<br/>
  Copyright &copy; 2013 BBN Technologies<br>
  All Rights Reserved - NSF Award CNS-0714770<br>
  <a href="http://www.geni.net/">GENI</a> is sponsored by the <a href="http://www.nsf.gov/"><img src="/common/nsf1.gif" alt="NSF Logo" height="16" width="16"> National Science Foundation</a>
</div>
</div>

</body>
</html>

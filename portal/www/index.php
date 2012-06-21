<?php
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
  echo '<h1> Welcome to the GENI Portal </h1>';
  echo '<div id="desc">';
  echo 'The GENI Portal is the main interface to <a href="http://www.geni.net">GENI</a>, an <a href="http://www.nsf.gov/">NSF</a> funded virtual testbed supporting computer networking research.';
  echo '</div>';
  echo '</div>';

  echo '<div id="loginHelpdiv">';
  echo '<a id ="loginHelp" href="login-help.php">Get Help</a>';
  echo '</div>';

  echo '</div>';

  echo '<hr/>';

  echo '<div id="landingcontent" class="landingpage">';
}

show_header("Welcome to the GENI Portal")
?>
<div id='main'>

  <a href='secure/home.php'>
    <img id='usegeni' src="/images/UseGENI.png" alt="Use GENI"/>
  </a>


  <h1>InCommon Affiliation</h1>

<img id="incommon-logo" src="common/InC_Participant.png"/>

<p>
If you are affiliated with a US college or university that is a <a href="http://www.incommon.org/federation/info/all-entities.html">member of the InCommon federation</a>, <a href="secure/home.php">login</a> using your InCommon Single Sign On username and password and register for a GENI account.
</p>

<p>
Not sure you're affiliated? <a href="http://www.incommon.org/federation/info/all-entities.html">Find Your Organization</a>
</p>

<p>
Not affiliated? <a href="mailto:help@geni.net">Request a Single Sign On Login</a>
</p>


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
  <small><a href="http://www.geni.net/">GENI</a> is sponsored by the <a href="http://www.nsf.gov/">NSF</a></small>
  <!-- put the copyright off to the right -->
  <div style="float:right;">
  <small>GENI Portal Copyright 2012, BBN Technologies</small>
  </div>
</div>

</body>
</html>

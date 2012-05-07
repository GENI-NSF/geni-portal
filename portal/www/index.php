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
  echo '<div id="header">';
  echo '<table>';
  echo '<tr><td>';
  echo '<img src="/images/geni.png" alt="GENI"/>';
  echo '</td><td>';
  echo '<a href="index.php"><img src="/images/portal.png" alt="Portal"/></a>';
  echo '</td><td>';
  echo '<div id="loginButtons">';
  echo '<button onClick="window.location.href=\'secure/home.php\'"><b>Login</b></button>';
  echo '<br/>';
  echo '<a href="login-help.php"><b>Get help logging in</b></a>';
  echo '</td>';
  echo '</table>';
  echo '</div>';
  echo '</div>';
  echo '<hr/>';
  echo '<div id="content">';
}

show_header("Welcome to the GENI Portal")
?>


<h1> Welcome to the GENI Portal </h1>

<p>
  The <b>GENI Portal</b> is the main interface to <a href="http://www.geni.net">GENI</a>, an <a href="http://www.nsf.gov/">NSF</a> funded virtual testbed supporting computer networking research and innovation.
</p><p>
  <b>Become a GENI Experimenter</b>: The GENI Portal supports single sign on through the <a href="http://www.incommonfederation.org">InCommon</a> federation. If you are affiliated with a US college or university that is a <a href="http://www.incommon.org/federation/info/all-entities.html">member of the InCommon federation</a>, <a href="secure/home.php">request a GENI account</a> using your InCommon single sign on username and password.  If you are not affiliated with an InCommon federated institution, <a href="mailto:help@geni.net">request a single sign on login for the GENI Portal</a>.
</p>
<p>
  <b>About GENI</b>: More Information about using GENI can be found on the <a href="http://groups.geni.net/geni">GENI wiki</a>.
</p>


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

<h2>Find out more about using GENI</h2>
<ul><li>
   <a href="http://groups.geni.net/geni/wiki/GENIExperimenterWelcome">Information for GENI experimenters</a>
</li><li>
  <a href="http://groups.geni.net/geni/wiki/UnderstandingGENI">Understanding GENI</a> (includes a list of available resources)
</li><li>
  See where GENI resources are located using Flack, a graphical GENI resource reservation tool
</li><li>
  Get <a href="mailto:help@geni.net">help</a> using GENI
</li>
</ul>


<!-- THIS SHOULD BE IN A COMMON FOOTER FILE --> 
</div>
<div id="footer">
  <hr/>
  <small><i><a href="http://www.geni.net/">GENI</a> is sponsored by the <a href="http://www.nsf.gov/">NSF</a></i></small>
  <!-- put the copyright off to the right -->
  <div style="float:right;">
  <small><i>GENI Portal Copyright 2012, BBN Technologies</i></small>
  </div>
</div>
</body>
</html>

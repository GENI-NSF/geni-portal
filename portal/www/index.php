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
  echo '<img src="/images/portal.png" alt="Portal"/>';
  echo '</td><td>';
  echo '<div id="loginButtons">';
  echo "<button type='button' onClick=\"window.location=\'secure/home.php\'\"><b>Login</b></button>";
  echo '<br/>';
  echo '<a href="login-help.php"><b>Get help logging in</b></a>';
  echo '</td>';
  echo '</table>';
  echo '</div>';
  echo '</div>';
  echo '<hr/>';
  echo '<div id="content">';
}

show_header()
?>


<h1> Welcome to the GENI Portal </h1>

<p>
  The <b>GENI Portal</b> is the main interface to <a href="http://www.geni.net">GENI</a>, an NSF funded virtual testbed supporting computer networking research and innovation.
</p><p>
  <b>Become a GENI Experimenter</b>: The GENI Portal supports single sign on through the <a href="http://www.incommonfederation.org">InCommon</a> federation. If you are affiliated with a US college or university that is a <a href="http://www.incommon.org/federation/info/all-entities.html">member of the InCommon federation</a>, <a href="secure/home.php">request a GENI account</a> using your InCommon single sign on username and password.  If you are not affiliated with an InCommon federated institution, <a href="">request a GENI account</a>.
</p>
<p>
  <b>About GENI</b>: More Information about using GENI can be found on the <a href="http://groups.geni.net/geni">GENI wiki</a>.
</p>


<h2>Announcements</h2>
<ul>
<li>
 * recent activity pulled from the log table ???
</li><li>
<ul><li>
   * New project created at 1:23pm
</li><li>
   * New slice created at 2:05am
</li></ul>
</li>
</ul>


<h2> Misc </h2>

<ul>
<li>
 + CHIP: total number of active accounts/slices for some period
   * plot of new experimenters as a function of month
</li>

<li>
 Links about GENI resources
<ul><li>
 + Help/tutorial links as seen on the deeper pages
<ul><li>
   + What resources are available
</li><li>
   + Links to tutorial pages on GENI wiki
</li><li>
   + Link to Flack (in it's public mode)
</li></ul>
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

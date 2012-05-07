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

show_header("Troubleshooting logging into the GENI Portal")
?>

<h1> Troubleshooting logging into the GENI Portal </h1>

<h2>If you don't know if you are a member of an InCommon federated institution...</h2>
<ul>
<li>
... check this list of <a href="http://www.incommon.org/federation/info/all-entities.html">members of the InCommon federation</a>.
</li>
</ul>


<h2 id='requestGENIAccount''>If you are a member of an InCommon federated institution...</h2>
<ul>
<li>
<b>... and you don't have a Single Sign On login through InCommon</b>, contact your local IT department or computer help desk to inquire about getting a login.
</li><li>
<b>... and you have a Single Sign On login through InCommon, but can't log in</b>. Your login and password are managed at your local instition.  Contact your local IT department or computer help desk for assistance.
</li>
</ul>



<h2>If you are a NOT member of an InCommon federated institution...</h2>

<ul>
<li>
<b>... and don't have a login or can't login</b>, contact <a href="mailto:help@geni.net">GENI help</a> and request a  Single Sign On login to the GENI portal. 
</li>
</ul>

<h2>For all other issues...</h2>
<ul>
<li>
... contact <a href="mailto:help@geni.net">GENI help</a>. Please describe your problem and the date and time you observed the issue.
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

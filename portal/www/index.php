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
  echo '<img src="/images/geni.png" alt="GENI"/>';
  echo '<img src="/images/portal.png" alt="Portal"/>';
  echo '</div>';
  echo '<div id="content">';
}

show_header()
?>


<h1> Welcome to the GENI Portal </h1>
<br/>
<button onClick="window.location='secure/home.php'"><b>Login</b></button>
<br/>
<!-- <button onClick="window.location=''"><b>Get An Account</b></button>
<br/>-->

<h2>Getting an account</h2>
<p>
  If you are affiliated with an United States College or University which is a <a href="http://www.incommon.org/federation/info/all-entities.html">member of the InCommon federation</a>, you can <a href="secure/home.php">login</a> and request your account be generated.  Otherwise, <a href="">request an account.</a>
</p>

<h2>About GENI</h2>
<p>
<a href="http://www.geni.net">GENI webpage</a> is an NSF funded virtual testbed. Information about using GENI can be found on the <a href="http://groups.geni.net/geni">GENI wiki</a>.
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

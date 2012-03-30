<?php
require_once("user.php");
$GENI_TITLE = "GENI Portal Home";
include("header.php");
?>
<!-- HTML code borrowed from Trac for a menu bar -->
<!--
<div id="mainnav" class="nav">
  <ul>
    <li class="active first">
      <a accesskey="1" href="/syseng/wiki">Home</a>
    </li>
    <li>
      <a accesskey="2" href="/syseng/timeline">Projects</a>
    </li>
    <li>
      <a accesskey="3" href="/syseng/roadmap">Slices</a>
    </li>
    <li>
      <a href="/syseng/report">Admin</a>
    </li>
  </ul>
</div>
-->

<div id="home-body">
<?php
$user = geni_loadUser();
if (is_null($user)) {
  // TODO: Handle unknown state
  print "Unable to load user record.<br/>";
} else {
  if ($user->isRequested()) {
    include("home-requested.php");
  } else if ($user->isDisabled()) {
    print "User $user->eppn has been disabled.";
  } else if ($user->isActive()) {
    include("home-active.php");
    // Uncomment below if you want jquery tabs example
    //include("home-active-tabs.php");
  } else {
    // TODO: Handle unknown state
    print "Unknown account state: $user->status<br/>";
  }
}
?>
</div>
<br/>
<?php
include("footer.php");
?>

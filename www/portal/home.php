<?php
require_once("user.php");
$GENI_TITLE = "GENI Portal Home";
include("header.php");
?>
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

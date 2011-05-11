<?php
require_once("user.php");
$GENI_TITLE = "GENI Portal Home";
include("header.php");
?>
<br/><br/>
<center>
Welcome
<?php
if (array_key_exists('givenName', $_SERVER)) {
  $first = $_SERVER['givenName'];
  echo "$first";
}
?>
!
</center>

<?php
$user = geni_loadUser();
if (is_null($user)) {
  echo "User is null.<br/>";
} else {
  echo "User exists.<br/>";
  if ($user->isRequested()) {
    print "User $user->account_id has been requested.";
  } else if ($user->isDisabled()) {
    print "User $user->account_id has been disabled.";
  } else if ($user->isActive()) {
    print "User $user->account_id is active.";
  }
}
?>

<br/>
<h2 align = "center"> You have successfully logged in via Shibboleth</h2>
<?php
$array = $_SERVER;
foreach ($array as $var => $value) {
    print "$var = $value<br/>";
    }
?>
<?php
include("footer.php");
?>

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
$eppn = $_SERVER['eppn'];
$user = geni_loadUser($eppn);
if (is_null($user)) {
  echo "User is null<br/>";
} else if ($user->isValid()) {
  echo "User exists and is valid.<br/>";
} else {
  echo "User exists and is not valid.<br/>";
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

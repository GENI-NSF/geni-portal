<?php
//----------------------------------------------------------------------
// This is sub-content, a part of the home page (home.php).
//----------------------------------------------------------------------

// Notes:
// $user should be bound to the current user
?>
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

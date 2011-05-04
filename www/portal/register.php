<?php
include("header.php");
?>

<br/><br/>
<h2> Registration Page </h2>
<form action="do-register.php">

First name:
<input type="text" name="firstname"
<?php
if (array_key_exists('givenName', $_SERVER)) {
  $first = $_SERVER['givenName'];
  echo "value=\"$first\" disabled=\"disabled\"";
}
?>
/>
<br/>

Last name:
<input type="text" name="lastname"
<?php
if (array_key_exists('sn', $_SERVER)) {
  $last = $_SERVER['sn'];
  echo "value=\"$last\" disabled=\"disabled\"";
}
?>
/>
<br/>

EMail:
<input type="text" name="email"
<?php
if (array_key_exists('mail', $_SERVER)) {
  $mail = $_SERVER['mail'];
  echo "value=\"$mail\" disabled=\"disabled\"";
}
?>
/>
<br/>

<input type="submit" value="Register"/>
</form>

<?php
include("footer.php");
?>

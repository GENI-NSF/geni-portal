<?php
include("header.php");
?>

<?php
// Local functions
function shib_input($shib_name, $pretty_name)
{
  print $pretty_name . ": ";
  print "<input type=\"text\" name=\"$shib_name\"";
  if (array_key_exists($shib_name, $_SERVER)) {
    $value = $_SERVER[$shib_name];
    echo "value=\"$value\" disabled=\"yes\"";
  }
  print "/><br/>";
}
?>

<br/><br/>
<h2> Registration Page </h2>
<form method="POST" action="do-register.php">
<?php
  shib_input('givenName', 'First name');
  shib_input('sn', 'Last name');
  shib_input('mail', 'EMail');
  shib_input('telephoneNumber', 'Telephone');
?>
<br/>
<input type="submit" value="Register"/>
</form>
<?php
include("footer.php");
?>

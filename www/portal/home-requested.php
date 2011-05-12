<?php
//----------------------------------------------------------------------
// This is sub-content, a part of the home page (home.php).
//----------------------------------------------------------------------

// Notes:
// $user should be bound to the current user
?>
<h2>Account requested</h2>
Your account is awaiting administrative approval.
You will receive an email when an administrator approves
or rejects your account.

Please check back again later.
<hr/>
<h3>Debug</h3>
<?php
$array = $user->attributes;
foreach ($user->attributes as $attr) {
  $name = $attr['name'];
  $value = $attr['value'];
  print "$name = $value<br/>";
  /* foreach ($attr as $var => $value) { */
  /*   print "attribute: $var = $value<br/>"; */
  /* } */
}
?>
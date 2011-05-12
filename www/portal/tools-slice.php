<?php
require_once("user.php");
if (! $user->privSlice()) {
  exit();
}
?>
<h1>Existing Slices</h1>
<i>Table goes here</i><br/>
<a href="
<?php
print relative_url("createslice");
?>
">Create a new slice</a>

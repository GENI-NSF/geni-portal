<?php
require_once("user.php");
if (! $user->privAdmin()) {
  exit();
}
?>
<h1>Admin Tools</h1>

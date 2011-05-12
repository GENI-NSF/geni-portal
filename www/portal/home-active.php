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
print $user->prettyName();
?>
!
</center>
<?php
include("tools-slice.php");
include("tools-admin.php");
?>

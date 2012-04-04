<?php
require_once("user.php");
require_once("header.php");
show_header('GENI Portal: Debug', $TAB_DEBUG);
?>
<div id="debug-body">
<?php
$user = geni_loadUser();
?>
<h2>SR</h2>
<?php
print "<a href=\"sr_controller_test.php\">Service Registry Test</a>\n";
?>
<h2>CS</h2>
<?php
print "<a href=\"cs_controller_test.php\">Credential Store Test</a>\n";
?>
</div><!-- debug-body -->
<br/>
<?php
include("footer.php");
?>

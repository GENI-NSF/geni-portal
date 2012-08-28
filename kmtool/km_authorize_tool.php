<?php

require_once('util.php');

$sense = $_GET["authorize_sense"];
$toolname = $_GET["authorize_toolname"];
$toolurl  = $_GET["authorize_toolurl"];
$tooluser = $_GET["authorize_user"];
$redirect_address = $_GET['redirect_address'];

$sense_text = "authorized";
if ($sense == "false") { $sense_text = "deauthorized"; }

error_log("ARGS = " . print_r($_GET, true));

print "Tool $toolname has been $sense_text for user $tooluser<br><br>\n";

print "<button onclick=\"window.location='" . 
    "kmhome.php?redirect=$redirect_address" . "'\">Back</button>";

?>

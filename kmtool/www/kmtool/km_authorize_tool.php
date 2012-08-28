<?php

require_once('util.php');
require_once('km_utils.php');
require_once('ma_client.php');

$sense = $_GET["authorize_sense"];
$toolname = $_GET["authorize_toolname"];
$toolurn  = $_GET["authorize_toolurn"];
$toolusername = $_GET["authorize_username"];
$tooluserid = $_GET["authorize_userid"];
$redirect_address = $_GET['redirect_address'];

$sense_text = "authorized";
if ($sense == "false") { $sense_text = "deauthorized"; }

// error_log("ARGS = " . print_r($_GET, true));


$result = ma_authorize_client($ma_url, $km_signer, $tooluserid, $toolurn, $sense);

if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
  print "Tool $toolname has been $sense_text for user $toolusername<br><br>\n";
}

print "<button onclick=\"window.location='" . 
    "kmhome.php?redirect=$redirect_address" . "'\">Back</button>";

?>

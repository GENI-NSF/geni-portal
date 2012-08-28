<?php

require_once('sr_constants.php');
require_once('sr_client.php');
require_once('signer.php');

$sr_url = get_sr_url();
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

// error_log("MA = " . print_r($ma_url, true));

$km_certfile = "/usr/share/geni-ch/km/km-cert.pem";
$km_keyfile = "/usr/share/geni-ch/km/km-key.pem";
$km_signer = new Signer($km_certfile, $km_keyfile);

// $mycert = file_get_contents($mycertfile);
// $mykey = file_get_contents($mykeyfile);
// error_log("CERT = $mycert");
// error_log("KEY = $mykey");




?>

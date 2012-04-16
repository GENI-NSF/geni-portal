<?php

require_once('util.php');
require_once('cs_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');

error_log("PM TEST");

$t_start = time();

// Get URL of Credential Store
$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);

$id = '25818eb0-1721-456f-a3e2-c91ce3867083'; // mbrinn
$ps = get_permissions($cs_url, $id);
error_log("PS = " . print_r($ps, true));

sleep(5);
$t_end = time();
error_log("NOW = " . $t_end . " WAS " . $t_start . " DIFF " . ($t_end - $t_start));

relative_redirect('debug');

?>

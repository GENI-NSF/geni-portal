<?php

require_once('util.php');
require_once('cs_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('user.php');

error_log("PM TEST");

$user = geni_loadUser();
$result = $user->isAllowed('create_project', CS_CONTEXT_TYPE::RESOURCE, null);
error_log("R1 = " . print_r($result, true));

$result2 = $user->isAllowed('create_foo', CS_CONTEXT_TYPE::MEMBER, null);
error_log("R2= " . print_r($result2, true));

relative_redirect('debug');

?>

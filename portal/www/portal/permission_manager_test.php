<?php

require('permission_manager.php');
require('util.php');

global $ALL_ACTION_SPECS;

$id = '33333333333333333333333333333333';
//error_log("AAS = " . print_r($ALL_ACTION_SPECS, true));
$ps = compute_permission_set($id);
error_log("PS = " . print_r($ps, true));

relative_redirect('debug');

?>

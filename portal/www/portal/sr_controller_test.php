<?php

require_once('util.php');
require_once('sr_constants.php');
require_once('sr_client.php');

function dump_rows($rows)
{
  global $SR_SERVICE_TYPE_NAMES;
  $count = count($rows);
  $index = 0;
  foreach ($rows as $row) {  
    $service_type_index = $row[SR_ARGUMENT::SERVICE_TYPE];
    $service_type_name = $SR_SERVICE_TYPE_NAMES[$service_type_index];
    $row_image =  $service_type_name . ' ' . $row[SR_ARGUMENT::SERVICE_URL];
    error_log("row[" . $index . "] = " . $row_image);
    $index = $index + 1;
  }
}

error_log("SR TEST\n");

$sr_url = get_sr_url();
$rows = get_services();
dump_rows($rows);

$rows = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
dump_rows($rows);

$result = register_service(SR_SERVICE_TYPE::LOGGING_SERVICE, 'http://foo.bar');
$rows = get_services();
dump_rows($rows);

$result = remove_service(SR_SERVICE_TYPE::LOGGING_SERVICE, 'http://foo.bar');
$rows = get_services();
dump_rows($rows);

relative_redirect('home');
?>

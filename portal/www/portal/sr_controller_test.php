<?php

require_once('util.php');
require_once('sr_constants.php');

function dump_rows($rows)
{
  global $SR_SERVICE_TYPE_NAMES;
  $count = count($rows);
  $index = 0;
  $row = $rows[$index];
  foreach ($rows as $row) {  
    $service_type_index = $row[SR_ARGUMENT::SERVICE_TYPE];
    $service_type_name = $SR_SERVICE_TYPE_NAMES[$service_type_index];
    $row_image =  $service_type_name . ' ' . $row[SR_ARGUMENT::SERVICE_URL];
    error_log("row[" . $index . "] = " . $row_image);
    $index = $index + 1;
  }
}

error_log("SR TEST\n");

/* Could be HTTP_HOST or SERVER_NAME */
$http_host = $_SERVER['HTTP_HOST'];
$sr_url = "https://" . $http_host . "/sr/sr_controller.php";

$get_services_message['operation'] = 'get_services';
$result = put_message($sr_url, $get_services_message);
dump_rows($result);

$message['operation'] = 'get_services_of_type';
$message[SR_ARGUMENT::SERVICE_TYPE] = SR_SERVICE_TYPE::AGGREGATE_MANAGER;
$result = put_message($sr_url, $message);
dump_rows($result);

$message['operation'] = 'register_service';
$message[SR_ARGUMENT::SERVICE_TYPE] = SR_SERVICE_TYPE::LOGGING_SERVICE;
$message[SR_ARGUMENT::SERVICE_URL] = 'http://foo.bar';
$result = put_message($sr_url, $message);
$result = put_message($sr_url, $get_services_message);
dump_rows($result);

$message['operation'] = 'remove_service';
$result = put_message($sr_url, $message);
$result = put_message($sr_url, $get_services_message);
dump_rows($result);

relative_redirect('home');
?>

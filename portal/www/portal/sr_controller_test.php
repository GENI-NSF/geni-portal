<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

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

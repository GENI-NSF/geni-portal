<?php

   require_once('util.php');

   error_log("SR TEST\n");

   /* Could be HTTP_HOST or SERVER_NAME */
   $http_host = $_SERVER['HTTP_HOST'];
   $sr_url = "https://" . $http_host . "/sr/sr_controller.php";
   $message['operation'] = 'get_services';
   $message['service_type'] = '0';
   $result = put_message($sr_url, $message);
   error_log("result = " . $result);
   relative_redirect('home');
?>
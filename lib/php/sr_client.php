<?php

// Routines to help clients of the service registry

// Return all services in registry
function get_services()
{

  $sr_url = get_sr_url();
  $get_services_message['operation'] = 'get_services';
  $result = put_message($sr_url, $get_services_message);
  return $result;
}

// Return all services in registry
function get_services_of_type($service_type)
{

  $sr_url = get_sr_url();
  $get_services_message['operation'] = 'get_services_of_type';
  $get_services_message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $result = put_message($sr_url, $get_services_message);
  return $result;
}

// Get first registered service of given service type
function get_first_service_of_type($service_type)
{
  /** Get the singleton SR (service registry) and ask for all services of given type **/
  $sr_url = get_sr_url();
  $message['operation'] = 'get_services_of_type';
  $message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $result = put_message($sr_url, $message);

  error_log("SR_URL: " . $sr_url);
  error_log("GFSOT: " . $result);
  error_log("ROW: " . $result[0]);

  /** Grab the first SA (eventually this will be selected from a user menu **/
  $row = $result[0];
  $url = $row[SR_TABLE_FIELDNAME::SERVICE_URL];
  return $url;
}

// Regisgter service of given type and URL with registry
function register_service($service_type, $service_url)
{
  $sr_url = get_sr_url();
  $message['operation'] = 'register_service';
  $message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $message[SR_ARGUMENT::SERVICE_URL] = $service_url;
  $result = put_message($sr_url, $message);
  return $result;
}

// Remove given service of type and url from registry
function remove_service($service_type, $service_url)
{
  $sr_url = get_sr_url();
  $message['operation'] = 'remove_service';
  $message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $message[SR_ARGUMENT::SERVICE_URL] = $service_url;
  $result = put_message($sr_url, $message);
  return $result;
}


?>

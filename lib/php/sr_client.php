<?php

// Routines to help clients of the service registry

function get_first_service_of_type($service_type)
{
  /** Get the singleton SR (service registry) and ask for all services of given type **/
  $sr_url = get_sr_url();
  $message['operation'] = 'get_services_of_type';
  $message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $result = put_message($sr_url, $message);

  /** Grab the first SA (eventually this will be selected from a user menu **/
  $row = $result[0];
  $url = $row[SR_TABLE_FIELDNAME::SERVICE_URL];
  return $url;
}


?>

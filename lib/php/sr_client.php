<?php

// Routines to help clients of the service registry

require_once('message_handler.php');

const HARDCODED = false;
const CACHED = true;
const SERVICE_REGISTRY_CACHE_TAG = 'service_registry_cache';

if (CACHED) {
  $services_cached = false;
  if (!isset($_SESSION)) { session_start(); $_SESSION = array(); }
  if (!array_key_exists(SERVICE_REGISTRY_CACHE_TAG, $_SESSION)) {
    $services = get_services();
    $_SESSION[SERVICE_REGISTRY_CACHE_TAG] = $services;
  }
  $services_cached = true;
}

$HARDCODED_SERVICES[] = 
  array(
	array(SR_TABLE_FIELDNAME::SERVICE_TYPE => SR_SERVICE_TYPE::SLICE_AUTHORITY, 
	      SR_TABLE_FIELDNAME::SERVICE_URL => "https://marilac.gpolab.bbn.com/sa/sa_controller.php"),
	array(SR_TABLE_FIELDNAME::SERVICE_TYPE => SR_SERVICE_TYPE::PROJECT_AUTHORITY, 
	      SR_TABLE_FIELDNAME::SERVICE_URL => "https://marilac.gpolab.bbn.com/pa/pa_controller.php"),
	array(SR_TABLE_FIELDNAME::SERVICE_TYPE => SR_SERVICE_TYPE::MEMBER_AUTHORITY, 
	      SR_TABLE_FIELDNAME::SERVICE_URL => "https://marilac.gpolab.bbn.com/ma/ma_controller.php"),
	array(SR_TABLE_FIELDNAME::SERVICE_TYPE => SR_SERVICE_TYPE::LOGGING_SERVICE, 
	      SR_TABLE_FIELDNAME::SERVICE_URL => "https://marilac.gpolab.bbn.com/logging/logging_controller.php"),
	array(SR_TABLE_FIELDNAME::SERVICE_TYPE => SR_SERVICE_TYPE::CREDENTIAL_STORE, 
	      SR_TABLE_FIELDNAME::SERVICE_URL => "https://marilac.gpolab.bbn.com/cs/cs_controller.php")
	);


// Return all services in registry
function get_services()
{
  if (HARDCODED) {
    global $HARDCODED_SERVICES;
    return $HARDCODED_SERVICES;
  }
  global $services_cached;
  if ($services_cached) {
    $services = $_SESSION[SERVICE_REGISTRY_CACHE_TAG];
    return services;
  } 
  $sr_url = get_sr_url();
  $get_services_message['operation'] = 'get_services';
  $result = put_message($sr_url, $get_services_message);
  return $result;
}

// Return all services in registry
function get_services_of_type($service_type)
{
  global $services_cached;
  if ($services_cached) {
    $services = $_SESSION[SERVICE_REGISTRY_CACHE_TAG];
    $sot = array();
    foreach($services as $service) {
      if ($service[SR_TABLE_FIELDNAME::SERVICE_TYPE] == $service_type) {
	$sot[] = $service;
      }
    }
    return $sot;
  }
  $sr_url = get_sr_url();
  $get_services_message['operation'] = 'get_services_of_type';
  $get_services_message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $result = put_message($sr_url, $get_services_message);
  return $result;
}

// Get first registered service of given service type
function get_first_service_of_type($service_type)
{
  if (HARDCODED) {
    switch($service_type) {
    case SR_SERVICE_TYPE::SLICE_AUTHORITY:
      return "https://marilac.gpolab.bbn.com/sa/sa_controller.php";
    case SR_SERVICE_TYPE::PROJECT_AUTHORITY:
      return "https://marilac.gpolab.bbn.com/pa/pa_controller.php";
    case SR_SERVICE_TYPE::MEMBER_AUTHORITY:
      return "https://marilac.gpolab.bbn.com/ma/ma_controller.php";
    case SR_SERVICE_TYPE::LOGGING_SERVICE:
      return "https://marilac.gpolab.bbn.com/logging/logging_controller.php";
    case SR_SERVICE_TYPE::CREDENTIAL_STORE:
      return "https://marilac.gpolab.bbn.com/cs/cs_controller.php";
    }
  }
  global $services_cached;
  if ($services_cached) {
    $sot = get_services_of_type($service_type);
    return $sot[0][SR_TABLE_FIELDNAME::SERVICE_URL];
  }
  /** Get the singleton SR (service registry) and ask for all services of given type **/
  $sr_url = get_sr_url();
  $message['operation'] = 'get_services_of_type';
  $message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $result = put_message($sr_url, $message);
  if (! isset($result) || is_null($result) || count($result) <= 0) {
    global $SR_SERVICE_TYPE_NAMES;
    error_log("Found 0 services of type " . $SR_SERVICE_TYPE_NAMES[$service_type]);
    return null;
  }

  //    error_log("SR_URL: " . $sr_url);
  //    error_log("ST: " . $service_type);
  //    error_log("GFSOT: " . $result);
  //    error_log("ROW: " . $result[0]);

  /** Grab the first SA (eventually this will be selected from a user menu **/
  $row = $result[0];
  $url = $row[SR_TABLE_FIELDNAME::SERVICE_URL];
  return $url;
}

// Return the service with the given id, or NULL if no service has the
// given id.
function get_service_by_id($service_id)
{
  global $services_cached;
  if ($services_cached) {
    $services = $_SESSION[SERVICE_REGISTRY_CACHE_TAG];
    foreach($services as $service) {
      if ($service[SR_TABLE_FIELDNAME::SERVICE_ID] == $service_id) {
        return $service;
      }
    }
  }
  $sr_url = get_sr_url();
  $message['operation'] = 'get_service_by_id';
  $message[SR_ARGUMENT::SERVICE_ID] = $service_id;
  $result = put_message($sr_url, $message);
  if (count($result) == 0) {
    // No service found
    return null;
  } else {
    // return the lone service.
    return $result[0];
  }
}

// Regisgter service of given type and URL with registry
function register_service($service_type, $service_url)
{
  $sr_url = get_sr_url();
  $message['operation'] = 'register_service';
  $message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $message[SR_ARGUMENT::SERVICE_URL] = $service_url;
  $result = put_message($sr_url, $message);

  // Refresh cache
  global $services_cached;
  if($services_cached) {
    $services_cached = false;
    $services = get_services();
    $_SESSION[SERVICE_REGISTRY_CACHE_TAG] = $services;
    $services_cached = true;
  }
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

  // Refresh cache
  global $services_cached;
  if($services_cached) {
    $services_cached = false;
    $services = get_services();
    $_SESSION[SERVICE_REGISTRY_CACHE_TAG] = $services;
    $services_cached = true;
  }

  return $result;
}


?>

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

// Routines to help clients of the service registry

require_once('message_handler.php');

const CACHED = true;
const SERVICE_REGISTRY_CACHE_TAG = 'service_registry_cache';

if (CACHED) {
  $services_cached = false;
  if (!isset($_SESSION)) {
    // FIXME: Loading the session may mean deserializing users.
    // So that would mean we need the GeniUser defined.
    // But that means requiring user.php - which is not in the local dir
    // And worse, user.php in turn requires cs_client,
    // And since cs_controller includes this file, we have doubly defined methods.
    //require_once("/var/www/secure/user.php"); /// starts the session after defining GeniUser class

    // FIXME: This gives a permission denied error sometimes. Buy why? See ticket #118
    session_start();
    //$_SESSION = array();
  }
  if (!array_key_exists(SERVICE_REGISTRY_CACHE_TAG, $_SESSION)) {
    $services = get_services();
    //    error_log("Calling get services " . print_r($services, true)); reset($services);
    $_SESSION[SERVICE_REGISTRY_CACHE_TAG] = $services;
  }
  //  error_log("Caching services: " . print_r($_SESSION[SERVICE_REGISTRY_CACHE_TAG], true)); reset($_SESSION[SERVICE_REGISTRY_CACHE_TAG]);
  $services_cached = true;
}


// Return all services in registry
function get_services()
{
  global $services_cached;
  if ($services_cached) {
    $services = $_SESSION[SERVICE_REGISTRY_CACHE_TAG];
    return $services;
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
    if (isset($services) && ! is_null($services) && is_array($services)) {
      foreach($services as $service) {
	if ($service[SR_TABLE_FIELDNAME::SERVICE_TYPE] == $service_type) {
	  $sot[] = $service;
	}
      }
    } else {
      error_log("get_services_of_type: Caching services but cache had non array?");
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
  global $services_cached;
  global $SR_SERVICE_TYPE_NAMES;
  if ($services_cached) {
    $sot = get_services_of_type($service_type);
    if (isset($sot) && ! is_null($sot) && is_array($sot) && count($sot) > 0) {
      return $sot[0][SR_TABLE_FIELDNAME::SERVICE_URL];
    } else {
      error_log("Got back 0 cached services of type " . $SR_SERVICE_TYPE_NAMES[$service_type]);
      return null;
    }
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

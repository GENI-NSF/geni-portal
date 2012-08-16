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

require_once('sr_constants.php');
require_once('session_cache.php');

const SERVICE_REGISTRY_CACHE_TAG = 'service_registry_cache';
const SERVICE_REGISTRY_CACHE_TIMEOUT = 30;

// Return all services in registry
function get_services()
{
  $sr_url = get_sr_url();
  $services = session_cache_lookup(SERVICE_REGISTRY_CACHE_TAG, SERVICE_REGISTRY_CACHE_TIMEOUT, $sr_url, 'get_services', null);
  return $services;
}

// Return all services in registry of given type
function get_services_of_type($service_type)
{
  $services = get_services();
  $sot = array();
  foreach($services as $service) {
    if($service[SR_TABLE_FIELDNAME::SERVICE_TYPE] == $service_type){
      $sot[] = $service;
    }
  }
  return $sot;
}

// Get URL of first registered service of given service type
function get_first_service_of_type($service_type)
{
  global $SR_SERVICE_TYPE_NAMES;
  $sot = get_services_of_type($service_type);
  if (isset($sot) && ! is_null($sot) && is_array($sot) && count($sot) > 0) {
    return $sot[0][SR_TABLE_FIELDNAME::SERVICE_URL];
  } else {
    error_log("Got back 0 cached services of type " . $SR_SERVICE_TYPE_NAMES[$service_type]);
    return null;
  }
}

// Return the service with the given id, or NULL if no service has the
// given id.
function get_service_by_id($service_id)
{
  $services = get_services();
  foreach($services as $service) {
    if ($service[SR_TABLE_FIELDNAME::SERVICE_ID] == $service_id) {
      return $service;
    }
  }
  return null;
}

// Lookup services by sets of attributes ("OR OF ANDS"): Any one set must
// match entirely
function get_services_by_attributes($attribute_sets)
{
  $sr_url = get_sr_url();
  $message['operation'] = 'get_services_by_attributes';
  $message[SR_ARGUMENT::SERVICE_ATTRIBUTE_SETS] = $attribute_sets;
  $result = put_message($sr_url, $message);
  return $result;
}

function get_attributes_for_service($service_id)
{
  $sr_url = get_sr_url();
  $message['operation'] = 'get_attributes_for_service';
  $message[SR_ARGUMENT::SERVICE_ID] = $service_id;
  $result = put_message($sr_url, $message);
  return $result;
}

// Register service of given type and URL with registry
function register_service($service_type, $service_url, $service_cert, 
			  $service_name, $service_description, 
			  $service_attributes)
{
  $sr_url = get_sr_url();
  $message['operation'] = 'register_service';
  $message[SR_ARGUMENT::SERVICE_TYPE] = $service_type;
  $message[SR_ARGUMENT::SERVICE_URL] = $service_url;
  $message[SR_ARGUMENT::SERVICE_CERT] = $service_cert;
  $message[SR_ARGUMENT::SERVICE_NAME] = $service_name;
  $message[SR_ARGUMENT::SERVICE_DESCRIPTION] = $service_description;
  $message[SR_ARGUMENT::SERVICE_ATTRIBUTES] = $service_attributes;
  $result = put_message($sr_url, $message);

  // Refresh cache
  session_cache_flush(SERVICE_REGISTRY_CACHE_TAG);
  
  return $result;
}

// Remove given service of given ID from registry
function remove_service($service_id)
{
  $sr_url = get_sr_url();
  $message['operation'] = 'remove_service';
  $message[SR_ARGUMENT::SERVICE_ID] = $service_id;
  $result = put_message($sr_url, $message);

  // Refresh cache
  session_cache_flush(SERVICE_REGISTRY_CACHE_TAG);

  return $result;
}


?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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
require_once('client_utils.php');
require_once('chapi.php');

const SERVICE_REGISTRY_CACHE_TAG = 'service_registry_cache';

/**
 * Translation table mapping CHAPI fields to legacy SR fields.
 */
$SRCHAPI2PORTAL =
  array('_GENI_SERVICE_ID' => SR_TABLE_FIELDNAME::SERVICE_ID,
        'SERVICE_TYPE' => SR_TABLE_FIELDNAME::SERVICE_TYPE,
        'SERVICE_URL' => SR_TABLE_FIELDNAME::SERVICE_URL,
        'SERVICE_URN' => SR_TABLE_FIELDNAME::SERVICE_URN,
        '_GENI_SERVICE_CERT_FILENAME' => SR_TABLE_FIELDNAME::SERVICE_CERT,
        'SERVICE_CERT' => SR_TABLE_FIELDNAME::SERVICE_CERT_CONTENTS,
        'SERVICE_NAME' => SR_TABLE_FIELDNAME::SERVICE_NAME,
        'SERVICE_DESCRIPTION' => SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION,
	'_GENI_SERVICE_ATTRIBUTES' => SERVICE_ATTRIBUTE_TAG,
        'SERVICE_TYPE' => SR_TABLE_FIELDNAME::SERVICE_TYPE,
        '_GENI_SERVICE_SHORT_NAME' => SR_ARGUMENT::SERVICE_SHORT_NAME);

/**
 * Convert chapi services to legacy services.
 * This is for backward compatibility.
 */
function service_chapi2portal($row) {
  global $SRCHAPI2PORTAL;
  $converted_row = convert_row($row, $SRCHAPI2PORTAL);
  return $converted_row;
}

// Return all services in registry
function get_services()
{
  $cached = get_session_cached(SERVICE_REGISTRY_CACHE_TAG);
  if (count($cached) > 0) {
    // If something is cached, return it.
    return $cached;
  }
  // Nothing in the cache, fetch the services.
  $sr_url = get_sr_url();
  $client = XMLRPCClient::get_client($sr_url);
  $services = $client->get_services();
  $converted_services = array();
  foreach ($services as $service) {
    $converted_service = service_chapi2portal($service);
    // Some services are in SR but invisible to portal
    if (excluded_service($converted_service)) continue; 
    $converted_services[] = $converted_service;
  }
  set_session_cached(SERVICE_REGISTRY_CACHE_TAG, $converted_services);
  return $converted_services;
}

// Is this service (in SR) excluded from portal
function excluded_service($service)
{
  // 10-19-2015. Exclude VTS Aggregates from Portal (though they are in SR).
  //  error_log("SERVICE = " . print_r($service, true));
  if (($service[SR_TABLE_FIELDNAME::SERVICE_TYPE] == 
       SR_SERVICE_TYPE::AGGREGATE_MANAGER) &&
      (lookup_service_attribute($service, SERVICE_ATTRIBUTE_AM_TYPE) == 
       SERVICE_ATTRIBUTE_VTS_AM)) {
    //    error_log("Excluding VTS");
    return true;
  }
  return false;
}

/**
 * Compare aggregates for sorting.
 *
 * Compare by service_name.
 */
function agg_cmp($a, $b) {
  return strcmp($a[SR_TABLE_FIELDNAME::SERVICE_NAME],
                $b[SR_TABLE_FIELDNAME::SERVICE_NAME]);
}

// Return all services in registry of given type
function get_services_of_type($service_type)
{
  $all_services = get_services();
  $services = select_services($all_services, $service_type);
  if ($service_type === SR_SERVICE_TYPE::AGGREGATE_MANAGER) {
    // Sort the aggregates alphabetically by name
    usort($services, "agg_cmp");
  }
  return $services;
}

// Get URL of first registered service of given service type
function get_first_service_of_type($service_type)
{
  global $SR_SERVICE_TYPE_NAMES;
  
  $sot = get_services_of_type($service_type);
  if (isset($sot) && is_array($sot) && count($sot) > 0) {
    $ans = $sot[0][SR_TABLE_FIELDNAME::SERVICE_URL];
    return $ans;
  } else {
    error_log("Got back 0 services of type "
              . $SR_SERVICE_TYPE_NAMES[$service_type]);
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

// Return all aggregates
function get_aggregates()
{
  $sr_url = get_sr_url();
  $client = XMLRPCClient::get_client($sr_url);
  $fields = array('SERVICE_URN', 'SERVICE_URL','SERVICE_NAME','SERVICE_DESCRIPTION', 'SERVICE_CERT');  
  $options = array('filter' => $fields); 
  $services = array();
  $ags = $client->call(SR_XMLRPC_API::LOOKUP_AGGREGATES, $options);
  if ($ags) { 
    foreach ($ags as &$el) {
      $el[SR_TABLE_FIELDNAME::SERVICE_TYPE]=SR_SERVICE_TYPE::AGGREGATE_MANAGER;
    }
    $services = $ags;
  }
  return $services;
}

// Return all MAs
//CHAPI: new
function get_member_authorities()
{
  $sr_url = get_sr_url();
  $client = XMLRPCClient::get_client($sr_url);
  $fields = array('SERVICE_URN', 'SERVICE_URL','SERVICE_NAME','SERVICE_DESCRIPTION', 'SERVICE_CERT'); 
  $options = array('filter' => $fields); 
  $services = array();
  $mas = $client->call(SR_XMLRPC_API::LOOKUP_MEMBER_AUTHORITIES, $options);
  if ($mas) {
    foreach ($mas as &$el) {
      $el[SR_TABLE_FIELDNAME::SERVICE_TYPE]=SR_SERVICE_TYPE::MEMBER_AUTHORITY;
    }
    $services = $mas; 
  }
  return $services;
}

// Return all slice authorities
//CHAPI: new
function get_slice_authorities()
{
  $sr_url = get_sr_url();
  $client = XMLRPCClient::get_client($sr_url);
  $fields = array('SERVICE_URN', 'SERVICE_URL','SERVICE_NAME','SERVICE_DESCRIPTION', 'SERVICE_CERT');
  $options = array('filter' => $fields); 
  $services = array();
  $sas = $client->call(SR_XMLRPC_API::LOOKUP_SLICE_AUTHORITIES, $options);
  if ($sas) {
    foreach ($sas as &$el) {
      $el[SR_TABLE_FIELDNAME::SERVICE_TYPE]=SR_SERVICE_TYPE::SLICE_AUTHORITY;
    }
    $services = $sas;
  }
  return $services;
}

// Return the authorities for given URNs
//CHAPI: new
function lookup_authorities_for_urns($urns)
{
  $client = XMLRPCClient::get_client(get_sr_url());
  $urls = $client->lookup_authorities_for_urns($urns);
  return $urls;
}

// Return the trust roots for this CH
//CHAPI: new
function get_trust_roots()
{
  $client = XMLRPCClient::get_client(get_sr_url());
  $certs = $client->_get_trust_roots(); // _ prefix means raw return value
  return $certs;
}


// Helper function to select only services of type
// from a complete list of services
// That is, if you call 'get_services', you can call this
// with the result instead of subsequent calls to 'get_services_of_type'
function select_services($services, $service_type)
{
  $selected = array();
  foreach ($services as $service) {
    if($service[SR_TABLE_FIELDNAME::SERVICE_TYPE] == $service_type) {
      $selected[] = $service;
    }
  }
  return $selected;
}

// Return attribute value of givewn name for given service. 
// Return null if no attribute of that name defined
function lookup_service_attribute($service, $name)
{
  if(!array_key_exists(SERVICE_ATTRIBUTE_TAG, $service))
    return null;
  $attributes = $service[SERVICE_ATTRIBUTE_TAG];
  if(!array_key_exists($name, $attributes))
    return null;
  return $attributes[$name];
			
}

?>

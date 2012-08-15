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

$prev_name = session_id('SR-SESSION');

require_once('message_handler.php');
require_once('db_utils.php');
require_once('sr_constants.php');
require_once('response_format.php');

/**
 * GENI Clearinghouse Service Registry (SR) controller interface
 * The Service Registry maintains a list of services registered
 * with the clearinghouse, and their type, URL and certificate (signed
 * by the SR itself.
 * 
 * Supports 4 interfaces:
 * get_services
 * get_services_of_type
 * register_service
 * remove_service	      
 *
 **/

/* 
   Load certificate contents for a given service row
 */
function load_certificate_contents_for_service($service)
{
  $cert_filename = $service[SR_TABLE_FIELDNAME::SERVICE_CERT];
  //  error_log("Reading " . $cert_filename);
  $cert_file_contents = null;
  if($cert_filename != null) {
    $cert_file_contents = file_get_contents($cert_filename);
  }
  $service[SR_TABLE_FIELDNAME::SERVICE_CERT_CONTENTS] = $cert_file_contents;
  return $service;
}

/* 
   Load certificate contents for each of a list of service rows
 */
function load_certificate_contents_for_services($services)
{
  //  error_log("LCCFS.PRE " . print_r($services, true));
  if ($services[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $rows = $services[RESPONSE_ARGUMENT::VALUE];
    $updated_rows = array();
    foreach($rows as $row) {
      $updated_row = load_certificate_contents_for_service($row);
      $updated_rows[] = $updated_row;
    }
    $services[RESPONSE_ARGUMENT::VALUE] = $updated_rows;
  }
  //  error_log("LCCFS.POST " . print_r($services, true));
  return $services;
}

/* Get all services currently registered with SR 
 * Args: None
 * Return: List of services
 */
function get_services($args)
{
  global $SR_TABLENAME;
  // error_log("listing all services");

  $query = "SELECT * FROM " . $SR_TABLENAME;
  // error_log("SR.GS QUERY = " . $query);
  $result = db_fetch_rows($query, "SR.get_services");
  //  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  //  error_log("GS.PRE " . print_r($result, true));
  $result = load_certificate_contents_for_services($result);
  //  error_log("GS.POST " . print_r($result, true));
  return $result;
}

/* Get all services of given type currently registered with SR 
 * Args: Service type
 * Return: List of services of given type
 */
function get_services_of_type($args)
{
  global $SR_TABLENAME;
  $service_type = $args[SR_ARGUMENT::SERVICE_TYPE]; 
  // error_log("listing services of type " . $service_type);

  $query = "SELECT * FROM " . $SR_TABLENAME . " WHERE " . 
    SR_TABLE_FIELDNAME::SERVICE_TYPE . 
    " = '" . $service_type . "'";
  //   error_log("SR.GSOT QUERY = " . $query);
  $result = db_fetch_rows($query, "SR.get_services_of_type");
  //  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  // error_log("ROWS = " . $rows);
  //  error_log("SR.GSOT RESULT = " . print_r($result, true));
  $result = load_certificate_contents_for_services($result);
  return $result;
}

/* Get the service with the given id.
 * Args: Service id
 * Return: List of services with that id or an empty list of no services match.
 */
function get_service_by_id($args)
{
  global $SR_TABLENAME;
  $service_id = $args[SR_ARGUMENT::SERVICE_ID];
  $id_column = SR_TABLE_FIELDNAME::SERVICE_ID;
  $conn = db_conn();
  $query = "SELECT * FROM $SR_TABLENAME WHERE"
    . " $id_column = " . $conn->quote($service_id, 'integer');
  $result = db_fetch_rows($query, "SR.get_service_by_id");
  $result = load_certificate_contents_for_services($result);
  return $result;
}

/*
 * Register service of given type and given URL
 * *** TO DO: Create certificate (and key pair) for service
 * Args: Service Type, Service URL
 * Return : Success/failure
 */
function register_service($args)
{
  global $SR_TABLENAME;
  $service_type = $args[SR_ARGUMENT::SERVICE_TYPE];
  $service_url = $args[SR_ARGUMENT::SERVICE_URL];
  // error_log("register service $service_type $service_url");
  $stmt = "INSERT INTO " . $SR_TABLENAME . "(" . 
    SR_TABLE_FIELDNAME::SERVICE_TYPE . ", " . 
    SR_TABLE_FIELDNAME::SERVICE_URL . ") VALUES (" . 
    "'" . $service_type . "'" . 
    ", ". 
    "'" . $service_url . "')";
  // error_log("SR.RegisterService STMT = " . $stmt);
  $result = db_execute_statement($stmt, "SR.register_service");
  return $result;
}
/*
 * Remove a service of given type and given URL from SR 
 * Args: Service Type, Service URL
 * Return : Success/failure
 */
function remove_service($args)
{
  global $SR_TABLENAME;
  $service_type = $args[SR_ARGUMENT::SERVICE_TYPE];
  $service_url = $args[SR_ARGUMENT::SERVICE_URL];
  // error_log("remove service $service_type $service_url");
  $stmt = "DELETE FROM " . $SR_TABLENAME . " WHERE " . 
    SR_TABLE_FIELDNAME::SERVICE_TYPE . " = '" . 
    $service_type . "' " . 
    " AND " . 
    SR_TABLE_FIELDNAME::SERVICE_URL . " = '" .
    $service_url . "'";
  // error_log("SR.RemoveService STMT = " . $stmt);
  $result = db_execute_statement($stmt, "SR.remove_service");
  return $result;
}

/* Note: when the time comes and we need the CS here,
 * this is a call to self to locate the CS, since
 * this is the service registry.
 */
$cs_url = NULL;
$mycert = file_get_contents('/usr/share/geni-ch/sr/sr-cert.pem');
$mykey = file_get_contents('/usr/share/geni-ch/sr/sr-key.pem');
$guard_factory = NULL;
handle_message("SR", $cs_url, default_cacerts(),
        $mycert, $mykey, $guard_factory);

?>


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
 * Supports these modify interfaces:
 * register_service
 * remove_service	      
 *
 * Supports 4 query interfaces:
 * get_services
 * get_services_of_type
 * get_services_by_id
 * get_services_by_attributes
 * get_attributes_for_service
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

/**
 * Get all services matching attribute sets
 * on a "OR OF ANDS" basis
 * Args : atribute_sets
 * Return : List of services matching given attribute sets criteria
 */
function get_services_by_attributes($args)
{
  global $SR_TABLENAME;
  global $SR_ATTRIBUTE_TABLENAME;
  $attribute_sets = $args[SR_ARGUMENT::SERVICE_ATTRIBUTE_SETS];

  // Two questions:
  // What if the list of attribute sets is empty?
  // What if any given attribute set is empty?
  // 
  // The point is that neither case generates good SQL
  // 
  // This is an "OR" of "AND"s.
  // So an empty attribute set should match everything
  // But an empty list of attribute sets should match nothing

  // If it is an empty list of attribute sets, we don't match anything
  // This is an "OR" of "ANDs".
  if (count($attribute_sets) == 0) {
    return generate_response(RESPONSE_ERROR::NONE, array(), '');
  }

  $attribute_set_sql = "";
  foreach($attribute_sets as $attributes) {
    $attributes_sql = 
      compute_attributes_sql($attributes,
			     $SR_ATTRIBUTE_TABLENAME,
			     SR_ATTRIBUTE_TABLE_FIELDNAME::SERVICE_ID,
			     SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME,
			     SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE);
    if($attribute_set_sql != "") {
      $attribute_set_sql = $attribute_set_sql . " UNION ";
    }
    $attribute_set_sql = $attribute_set_sql . $attributes_sql;
  }

  //  error_log("AS = " . print_r($attribute_sets, true));
  //  error_log("AS_SQL = " . print_r($attribute_set_sql, true));

  $sql = "select " 
    . SR_TABLE_FIELDNAME::SERVICE_ID . ", "
    . SR_TABLE_FIELDNAME::SERVICE_TYPE . ", "
    . SR_TABLE_FIELDNAME::SERVICE_URL . ", "
    . SR_TABLE_FIELDNAME::SERVICE_CERT . ", "
    . SR_TABLE_FIELDNAME::SERVICE_NAME . ", "
    . SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION 
    . " FROM " . $SR_TABLENAME 
    . " WHERE " .  SR_TABLE_FIELDNAME::SERVICE_ID . " IN  (" 
    . $attribute_set_sql . ")";
  //  error_log("SR.SQL = " . $sql);

  $rows = db_fetch_rows($sql);

  return $rows;

}

/**
 * Get all attributes assocaited with given service_id
 * Args: service_id
 * Return : List of name/value attributes associated with given service
 */
function get_attributes_for_service($args)
{
  //  error_log("ARGS = " . print_r($args, true));
  $service_id = $args[SR_ARGUMENT::SERVICE_ID];

  global $SR_ATTRIBUTE_TABLENAME;
  $sql = "select " .
    SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME . ", " .
    SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE . 
    " FROM " . $SR_ATTRIBUTE_TABLENAME .
    " WHERE " . SR_ATTRIBUTE_TABLE_FIELDNAME::SERVICE_ID . 
    " = " . $service_id;

  $rows = db_fetch_rows($sql);
  $result = $rows;
  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $attributes = array();;
    foreach($rows[RESPONSE_ARGUMENT::VALUE] as $row) {
      $key = $row[SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME];
      $value = $row[SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE];
      $attributes[$key] = $value;
    }
    $result = generate_response(RESPONSE_ERROR::NONE, $attributes, '');
  }

  return $result;
}

/*
 * Register service of given type and given URL
 * Args: Service Type, Service URL
 * Return : ID of registered service or error code
 */
function register_service($args)
{
  global $SR_TABLENAME;
  global $SR_ATTRIBUTE_TABLENAME;

  $service_type = $args[SR_ARGUMENT::SERVICE_TYPE];
  $service_url = $args[SR_ARGUMENT::SERVICE_URL];
  $service_cert = $args[SR_ARGUMENT::SERVICE_CERT];
  $service_name = $args[SR_ARGUMENT::SERVICE_NAME];
  $service_description = $args[SR_ARGUMENT::SERVICE_DESCRIPTION];
  $service_attributes = $args[SR_ARGUMENT::SERVICE_ATTRIBUTES];
  // error_log("register service $service_type $service_url");
  $stmt = "INSERT INTO " . $SR_TABLENAME . "(" . 
    SR_TABLE_FIELDNAME::SERVICE_TYPE . ", " . 
    SR_TABLE_FIELDNAME::SERVICE_URL . ", " . 
    SR_TABLE_FIELDNAME::SERVICE_CERT . ", " . 
    SR_TABLE_FIELDNAME::SERVICE_NAME . ", " . 
    SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION .
    ") VALUES (" . 
    "'" . $service_type . "'" . 
    ", ". 
    "'" . $service_url . "'" . 
    ", ". 
    "'" . $service_cert . "'" . 
    ", ". 
    "'" . $service_name . "'" . 
    ", ". 
    "'" . $service_description . "'" . 
    ")";
  // error_log("SR.RegisterService STMT = " . $stmt);
  $result = db_execute_statement($stmt, "SR.register_service");

  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $lastval_sql = "select lastval()";
    $lastval_result = db_fetch_row($lastval_sql);
    $lastval = $lastval_result[RESPONSE_ARGUMENT::VALUE]['lastval'];
    foreach($service_attributes as $attribute_name => $attribute_value) {
      $insert_sql = "insert into " . $SR_ATTRIBUTE_TABLENAME
	. " ("
	. SR_ATTRIBUTE_TABLE_FIELDNAME::SERVICE_ID . ", "
	. SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_NAME . ", "
	. SR_ATTRIBUTE_TABLE_FIELDNAME::ATTRIBUTE_VALUE . ") "
	. " VALUES ("
	. $lastval . ", "
	. "'" . $attribute_name . "', "
	. "'" . $attribute_value . "'"
	. ")";
      $insert_result = db_execute_statement($insert_sql);
    }

    $result = generate_response(RESPONSE_ARGUMENT::CODE, $lastval, '');
  }
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
  global $SR_ATTRIBUTE_TABLENAME;

  $service_id = $args[SR_ARGUMENT::SERVICE_ID];
  // error_log("remove service $service_id");
  $stmt = "DELETE FROM " . $SR_TABLENAME . " WHERE " . 
    SR_TABLE_FIELDNAME::SERVICE_ID . " = " . $service_id;
  // error_log("SR.RemoveService STMT = " . $stmt);
  $result = db_execute_statement($stmt, "SR.remove_service");

  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $delete_attribute_sql = "DELETE FROM " . $SR_ATTRIBUTE_TABLENAME . 
      " WHERE " . 
      SR_ATTRIBUTE_TABLE_FIELDNAME::SERVICE_ID . " = " . $service_id;
    // error_log("remove service attribute : $delete_attribute_sql");
    $delete_attribute_result = 
      db_execute_statement($delete_attribute_sql, 
			   "SR.remove_service_attribute");
  }
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


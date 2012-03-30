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
<?php

/* Set of known services types for services within GENI CH SR */
class SR_SERVICE_TYPE
{
	const AGGREGATE_MANAGER = 0;
	const SLICE_AUTHORITY = 1;
	const PROJECT_AUTHORITY = 2;
	const MEMBER_AUTHORITY = 2;
	const AUTHORIZATION_SERVICE = 3;
	const LOGGING_SERVICE = 4;
	const CREDENTIAL_STORE = 5;
}

/* Set of arguments in calls to the SR interface */
class SR_ARGUMENT
{
	const SERVICE_URL = "service_url";
	const SERVICE_TYPE = "service_type";
}

/* Name of table to which the SR persists/retrieves model state */
const SR_TABLENAME = "service_registry";

/* SR table has the following fields */
class SR_TABLE_FIELDNAME {
      const SERVICE_TYPE = "service_type";
      const SERVICE_URL = "service_url";
      const SERVICE_CERT = "service_cert";
}

/* Get all services currently registered with SR 
 * Args: None
 * Return: List of services
 */
function get_services($args)
{
	error_log("listing services of type $service_type");

	$query = "SELECT * FROM $SR_TABLENAME";
	error_log("SR.GS QUERY = $query);
	$rows = db_fetch_rows($query);
	return $rows;
}

/* Get all services of given type currently registered with SR 
 * Args: Service type
 * Return: List of services of given type
 */
function get_services_of_type($args)
{
	$service_type = $args[SR_ARGUMENT::SERVICE_TYPE];
	error_log("listing services of type $service_type");

	$query = "SELECT * FROM $SR_TABLENAME WHERE " . 
	       SR_TABLE_FIELDNAME.SERVICE_TYPE . 
	       " = '" . $service_type . "'";
	error_log("SR.GSOT QUERY = $query);
	$rows = db_fetch_rows($query);
	return $rows;
}

/*
 * Register service of given type and given URL
 * *** TO DO: Create certificate (and key pair) for service
 * Args: Service Type, Service URL
 * Return : Success/failure
 */
function register_service($args)
{
	service_type = $args[SR_ARGUMENT::SERVICE_TYPE];
	service_url = $args[SR_ARGUMENT::SERVICE_URL];
	error_log("register service $service_type $service_url");
	$stmt = "INSERT INTO $SR_TABLENAME (" . 
	      SR_TABLE_FIELDNAME.SERVICE_TYPE . ", " . 
	      SR_TABLE_FIELDNAME.SERVICE_URL . ") VALUES (" . 
	      "'" . $service_type . "'" . 
	      ", ". 
	      "'" . $service_url . ")";
	error_log("SR.RegisterService STMT = $stmt);
	$result = db_execute_statement($stmt);
	return $result;

/*
 * Remove a service of given type and given URL from SR 
 * Args: Service Type, Service URL
 * Return : Success/failure
 */
function remove_service($args)
{
	service_type = $args[SR_ARGUMENT::SERVICE_TYPE];
	service_url = $args[SR_ARGUMENT::SERVICE_URL];
	error_log("remove service $service_type $service_url");
	$stmt = "REMOVE FROM $SR_TABLENAME WHERE " . 
	      SR_TABLE_FIELDNAME.SERVICE_TYPE . " = '" . 
	      $service_type + "' " . 
	      " AND " . 
	      SR_TABLE_FIELDNAME.SERVICE_URL . " = '" + 
	      $service_url + "'";
	error_log("SR.RemoveService STMT = $stmt);
	$result = db_execute_statement($stmt);
	return $result;
}

>

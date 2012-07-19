<?php

/**
 * GENI Clearinghouse Service Registry (SR) controller interface
 * The Service Registry maintains a list of services registered
 * with the clearinghouse, and their type, URL and certificate (signed
 * by the SR itself.
 * 
 * Supports these query interfaces:
 * get_services
 * get_services_of_type
 * get_service_by_id
 *
 **/

/**
 * Get all services currently registered with SR 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_services")
 *   "signer" : UUID of signer (asserter) of method/argument set
 * Return:
 *   List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description)
 *   
 */
function get_services($args_dict)
{
}

/**
 * Get all services of given type currently registered with SR 
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_services_of_type")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "service_type" : Type of service requested
 * Return:
 *   List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) of given type
 *   
 */
function get_services_of_type($args_dict)
{
}

/**
 * Get the service with the given id.
 *
 * Args: Dictionary containing name/value pairs:
 *   "operation" : name of this method ("get_service_by_id")
 *   "signer" : UUID of signer (asserter) of method/argument set
 *   "service_id" : ID of given service
 * Return:
 *   List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) of given ID
 *   
 */
function get_service_by_id($args_dict)
{
}

?>


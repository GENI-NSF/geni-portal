<?php

namespace Service_Registry;

/**
 * GENI Clearinghouse Service Registry (SR) controller interface
 * The Service Registry maintains a list of services registered
 * with the clearinghouse, and their type, URL and certificate (signed
 * by the SR itself).
 * <br><br>
 * Supports these query interfaces:
 <ul>
 <li>services <= get_services()</li>
 <li>services <= get_services_of_type(service_type)</li>
 <li>services <= get_service_by_id(service_id)</li>
</ul>
 **/
class Service_Registry {

/**
 * Get all services currently registered with SR 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_services")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
</ulL
 * @return array  List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description)
 *   
 */
function get_services($args_dict)
{
}

/**
 * Get all services of given type currently registered with SR 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_services_of_type")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"service_type" : Type of service requested</li>
</ul>
 * @return array List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) of given type
 *   
 */
function get_services_of_type($args_dict)
{
}

/**
 * Get the service with the given id.
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_service_by_id")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"service_id" : ID of given service</li>
</ul>
 * @return array List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) of given ID
 *   
 */
function get_service_by_id($args_dict)
{
}

/**
 * Get the service with the given id.
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_service_by_id")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"service_id" : ID of given service</li>
</ul>
 * @return array List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) of given ID
 *   
 */
function get_service_by_id($args_dict)
{
}

/**
 * *** NOT IMPLEMENTED! ***
 * Get the service with the given id.
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_service_by_id")</li>
   <li>"signer" : UUID of signer (asserter) of method/argument set</li>
   <li>"attributes" : Dictionary of name/value pairs, all of which must match a given service to be returned</li>
</ul>
 * @return array List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) matching all of given set of attributes
 *   
 */
function get_service_by_attributes($args_dict)
{
}

/**
 * Get the version of the API of this particular service provider
 * @param dict $args_dict Dictionary containing 'operation' and 'signer' arguments'
 * @return number Version of API of this particular service provider
 */
function get_version($args_dict)
{
}

}

?>


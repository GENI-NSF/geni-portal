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

namespace Service_Registry;

/**
 * GENI Clearinghouse Service Registry (SR) controller interface
 * <br><br>
 * The Service Registry maintains a list of services registered
 * with the clearinghouse, and their type, URL and certificate (signed
 * by the SR itself).
 * <br><br>
 * Supports these modification interfaces:
 <ul>
 <li>success <= register_service(service_type, service_url, attributes)</li>
 <li>success <= remove_service(service_id)
 </ul>
 * <br><br>
 * Supports these query interfaces (where 'service' represents the tuple [id service_type service_url service_cert service_cert_contents service_name service_description]):
 <ul>
 <li>[service]* <= get_services()</li>
 <li>[service]* <= get_services_of_type(service_type)</li>
 <li>[service]* <= get_service_by_id(service_id)</li>
 <li>[service]* <= get_services_by_attributes(attribute_sets)</li>
 <li>[attribute_name attribute_value]* <= get_attributes_for_service(service_id)</li>
</ul>
 **/
class Service_Registry {

/**
 * Get all services currently registered with SR 
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_services")</li>
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
   <li>"service_id" : ID of given service</li>
</ul>
 * @return array List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) of given ID
 *   
 */
function get_service_by_id($args_dict)
{
}

/**
 * Get the service that match one of a list of name/value attribute sets
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("get_service_by_id")</li>
   <li>"attribute_sets" : List of dictionaries of name/value pairs, the entries of one of which must all match a given service to be returned. ("OR" OF "ANDS")</li>
</ul>
 * @return array List of service info tuples (id, service_type, service_url, service_cert, service_cert_contents, service_name, service_description) matching all of the requested attributes in one of the given sets of attributes
 *   
 */
function get_service_by_attributes($args_dict)
{
}

/**
 * Register a new service with the Service Registry
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("register_service")</li>
   <li>"service_type" : type of service (e.g. AM, PA, SA, etc.) </li>
   <li>"service_name" : name of service</li>
   <li>"service_url" : URL associated with service</li>
   <li>"service_cert" : name of file containing service certificate</li>
   <li>"service_description" : description of service </li>
   <li>"attributes" : Dictionary of name/value pairs associated with service (for querying) </li>
 </ul>
 * @return int ID of service registered or error code
 */
function register_service($args_dict)
{
}

/**
 * Remove a given service from the Service Registry
 *
 * @param dict $args_dict Dictionary containing name/value pairs:
<ul>
   <li>"operation" : name of this method ("remove_service")</li>
   <li>"service_id" : ID of service to be removed</li>
</ul>
* @return boolean Success/Failure
 */
function remove_service($args_dict)
{
}

/**
 * Get the version of the API of this particular service provider
 * @param dict $args_dict Dictionary containing 'operation' argument
 * @return number Version of API of this particular service provider
 */
function get_version($args_dict)
{
}

}

?>


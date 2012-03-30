<?php
/*----------------------------------------------------------------------
 * Copyright (c) 2012 Raytheon BBN Technologies
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and/or hardware specification (the "Work") to
 * deal in the Work without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Work, and to permit persons to whom the Work
 * is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Work.
 *
 * THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
 * IN THE WORK.
 *----------------------------------------------------------------------*/

require_once("RestUtils.php");
require_once("db.php");

// Requires package "php-xml-serializer"
require_once 'XML/Serializer.php';


/* All known service types. Sync this with the database schema. */
$service_types = array('credential-store',
                       'aggregate-manager',
                       'slice-authority');

/*
 * To find out what subdir the user requested look at
 * $_SERVER["PATH_INFO"]
 */

function encodeResult($data, $svc_type, $svc_array)
{
  if($data->getHttpAccept() == 'json')
    {
      RestUtils::sendResponse(200, json_encode($svc_array),
                              'application/json');
    }
  else /* if ($data->getHttpAccept() == 'xml') */
    {
      // using the XML_SERIALIZER Pear Package
      $options = array
        (
         //         'indent' => '     ',
         //         'addDecl' => false,
         //'rootName' => $fc->getAction(),
         //         'rootName' => null,
         XML_SERIALIZER_OPTION_RETURN_RESULT => true,
         // Do not replace xml entities
         //         XML_SERIALIZER_OPTION_ENTITIES => XML_SERIALIZER_ENTITIES_NONE,
         //         XML_SERIALIZER_OPTION_DEFAULT_TAG => null  // 'credentials'
         );
      $serializer = new XML_Serializer($options);

      RestUtils::sendResponse(200, $serializer->serialize($svc_array),
                              'application/xml');
    }
}

function handleGet($data)
{
  global $service_types;
  $path = explode('/', $_SERVER["PATH_INFO"]);
  $svc_type = $path[1];
  if (in_array($svc_type, $service_types))
    {
      $svc_array = db_fetch_services($svc_type);
      encodeResult($data, $svc_type, $svc_array);
    }
  else
    {
      /* Unknown service type. */
      RestUtils::sendResponse(400,
                              "Bad Request: Unknown service type $svc_type",
                              "application/text");
    }
}

/*----------------------------------------------------------------------
 * Processing starts here.
 *----------------------------------------------------------------------*/

$data = RestUtils::processRequest();

switch($data->getMethod())
  {
    // this is a request for all users, not one in particular
  case 'get':
    handleGet($data);
    break;

  default:
    RestUtils::sendResponse(405); // Method not supported
    break;
  }
?>

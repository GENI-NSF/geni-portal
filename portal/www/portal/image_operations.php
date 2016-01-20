<?php
//----------------------------------------------------------------------
// Copyright (c) 2015-2016 Raytheon BBN Technologies
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
?>
<?php

// Support for image operations at IG/PG racks:
// 
//   image_operations.php?am_id=am_id&operation=listimages : 
//       Invoke listimages API to list images by current user
//       Return list of image ID / image  URN
// 
//   image_operations.php?operation=deleteimage?image_urn=image_urn
//       Invoke deleteimage API to delete given image
//       Return succes / failure
//
//   image_operations.php?am_id=am_id&operation=createimage&project_name=project_name&slice_name=slice_name&image_name=image_name&sliver_id=sliver_id&public=public
//       Invoke createimage API to create image on given sliver 
//       Return image ID and URN
//  

?>

<?php


require_once('user.php');
require_once('sr_constants.php');
require_once("response_format.php");
require_once("am_client.php");
require_once("tool-lookupids.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// Required args for different image operations
$REQUIRED_IMAGE_OPERATION_ARGS = array("listimages" => array("am_id"),
				       "createimage" => 
				       array("am_id", 
					     "project_name","slice_name", 
					     "image_name", 
					     "sliver_id", "public"),
				       "deleteimage" =>
				       array("am_id", "image_urn"));

// Return list of missing arguments in $_GET for given operation
function check_required_args($operation)
{
  global $REQUIRED_IMAGE_OPERATION_ARGS;
  $missing_args = array();
  foreach($REQUIRED_IMAGE_OPERATION_ARGS[$operation] as $arg) {
    if(!array_key_exists($arg, $_GET)) 
      $missing_args[] = $arg;
  }
  return $missing_args;
}


// Return error response with given message
function error_response($msg, $code)
{
  return array(RESPONSE_ARGUMENT::CODE => $code,
	       RESPONSE_ARGUMENT::OUTPUT => $msg,
	       RESPONSE_ARGUMENT::VALUE => null);
}

function am_response($code, $value)
{
  return array(RESPONSE_ARGUMENT::CODE => $code,
	       RESPONSE_ARGUMENT::OUTPUT => "",
	       RESPONSE_ARGUMENT::VALUE => $value);
}

// Perform list operation. Check args and dispatch
function perform_list_operation()
{
  global $REQUIRED_IMAGE_OPERATION_ARGS, $am, $user;

  $operation = null;
  $missing_args = array();
  if (!array_key_exists('operation', $_GET)) {
    return error_response("Missing required argument: operation",
			  RESPONSE_ERROR::ARGS);
  }
  $operation = $_GET['operation'];
  if (!array_key_exists($operation, $REQUIRED_IMAGE_OPERATION_ARGS))
    return error_response("Unsupported operation: " + $operation,
			  RESPONSE_ERROR::ARGS);
  $missing_args = check_required_args($operation);
  if(count($missing_args) > 0) {
    return error_response("Missing required arguments: " .
			  join(", ", $missing_args),
			  RESPONSE_ERROR::ARGS);
  }
  if($am == null)
    return error_response("Invalid AM provided",
			  RESPONSE_ERROR::ARGS);
  $am_url = $am[SR_ARGUMENT::SERVICE_URL];
  if($operation == 'listimages') {
    $response = invoke_omni_function($am_url, $user, array('listimages'));
    $response = $response[1][$am_url];
    $output = am_response($response['code']['geni_code'], $response['value']);
  } else if ($operation == 'createimage') {
    $response = invoke_omni_function($am_url, $user, 
				   array('createimage',
					 $_GET['slice_name'],
					 $_GET['image_name'],
					 $_GET['public'],
					 '--project', $_GET['project_name'],
					 '-u', $_GET['sliver_id']));
    $response = $response[1];
  } else if ($operation == 'deleteimage') {
    $urn = $_GET['image_urn'];
    $args = array('deleteimage', $urn);
    $response = invoke_omni_function($am_url, $user, $args);
    $response = $response[1][$am_url];
  }

  $code = $response['code']['geni_code'];
  if ($code == 0)
    $output = am_response($code, $response['value']);
  else
    $output = error_response($response['output'], $code);
  return $output;
}

$result = perform_list_operation();

header("Cache-Control: public");
header("Content-Type: application/json");
print json_encode($result);

?>

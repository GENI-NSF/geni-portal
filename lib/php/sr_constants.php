<?php

/* Set of constants for managine calls and data records for 
 * GENI Clearinghouse Service Registry
 */

/* Set of known services types for services within GENI CH SR */
/* We store/retrieve by index into this array, but print the strings */
$SR_SERVICE_TYPE_NAMES = array("AGGREGATE_MANAGER", 
				      "SLICE_AUTHORITY", 
				      "PROJECT_AUTHORITY", 
				      "MEMBER_AUTHORITY",
				      "AUTHORIZATION_SERVICE",
				      "LOGGING_SERVICE",
				      "CREDENTIAL_STORE");

class SR_SERVICE_TYPE
{
  const AGGREGATE_MANAGER = 0;
  const SLICE_AUTHORITY = 1;
  const PROJECT_AUTHORITY = 2;
  const MEMBER_AUTHORITY = 3;
  const AUTHORIZATION_SERVICE = 4;
  const LOGGING_SERVICE = 5;
  const CREDENTIAL_STORE = 6;
}

/* Set of arguments in calls to the SR interface */
class SR_ARGUMENT
{
  const SERVICE_URL = "service_url";
  const SERVICE_TYPE = "service_type";
}

/* Name of table to which the SR persists/retrieves model state */
$SR_TABLENAME = "service_registry";

/* SR table has the following fields */
class SR_TABLE_FIELDNAME {
  const SERVICE_TYPE = "service_type";
  const SERVICE_URL = "service_url";
  const SERVICE_CERT = "service_cert";
}

/* Get name of singleton service registry (SR) instance */
function get_sr_url()
{
  /* Could be HTTP_HOST or SERVER_NAME */
  $http_host = $_SERVER['HTTP_HOST'];
  $sr_url = "https://" . $http_host . "/sr/sr_controller.php";
  return $sr_url;
}

?>

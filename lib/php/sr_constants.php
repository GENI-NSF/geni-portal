<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

/* Set of constants for managing calls and data records for 
 * GENI Clearinghouse Service Registry
 */

/*
 * Include local host settings.
 *
 * FIXME: parameterize file location
 */
include_once('/etc/geni-ch/settings.php');


/* Set of known services types for services within GENI CH SR */
/* We store/retrieve by index into this array, but print the strings */
$SR_SERVICE_TYPE_NAMES = array("AGGREGATE_MANAGER", 
			       "SLICE_AUTHORITY", 
			       "PROJECT_AUTHORITY", 
			       "MEMBER_AUTHORITY",
			       "AUTHORIZATION_SERVICE",
			       "LOGGING_SERVICE",
			       "CREDENTIAL_STORE", 
			       "CERTIFICATE_AUTHORITY",
			       "KEY_MANAGER",
			       "PGCH",
			       "WIMAX_SITE",
			       "iRODS"
			       );

class SR_SERVICE_TYPE
{
  const AGGREGATE_MANAGER = 0;
  const SLICE_AUTHORITY = 1;
  const PROJECT_AUTHORITY = 2;
  const MEMBER_AUTHORITY = 3;
  const AUTHORIZATION_SERVICE = 4;
  const LOGGING_SERVICE = 5;
  const CREDENTIAL_STORE = 6;
  const CERTIFICATE_AUTHORITY = 7;
  const KEY_MANAGER = 8;
  const PGCH = 9;
  const WIMAX_SITE = 10;
  const IRODS = 11;
}

/* Set of arguments in calls to the SR interface */
class SR_ARGUMENT
{
  const SERVICE_ID = "service_id";
  const SERVICE_URL = "service_url";
  const SERVICE_URN = "service_urn";
  const SERVICE_TYPE = "service_type";
  const SERVICE_NAME = "service_name";
  const SERVICE_DESCRIPTION = "service_description";
  const SERVICE_CERT = "service_cert";
  const SERVICE_ATTRIBUTES = "service_attributes";
  const SERVICE_ATTRIBUTE_SETS = "service_attribute_sets";
}

/* Name of table to which the SR persists/retrieves model state */
$SR_TABLENAME = "service_registry";

/* SR table has the following fields */
class SR_TABLE_FIELDNAME {
  const SERVICE_ID = "id";
  const SERVICE_TYPE = "service_type";
  const SERVICE_URL = "service_url";
  const SERVICE_CERT = "service_cert";
  const SERVICE_CERT_CONTENTS = "service_cert_contents";
  const SERVICE_NAME = "service_name";
  const SERVICE_DESCRIPTION = "service_description";
  const SERVICE_URN = "service_urn";
}

/* Name of table which holds SR name/value attributes */
$SR_ATTRIBUTE_TABLENAME = "service_registry_attribute";

/* SR attribute table has the following fields */
class SR_ATTRIBUTE_TABLE_FIELDNAME {
  const SERVICE_ID = "service_id";
  const ATTRIBUTE_NAME = "attribute_name";
  const ATTRIBUTE_VALUE = "attribute_value";
}

/* Get name of singleton service registry (SR) instance */
function get_sr_url()
{
  global $service_registry_url;
  if (isset($service_registry_url)) {
    return $service_registry_url;
  } else {
    /* If no setting above, assume this host as SR. */
    $http_host = $_SERVER['SERVER_NAME'];
    $sr_url = "https://" . $http_host . ":8444/SR";
    return $sr_url;
  }
}

class SR_XMLRPC_API {
  const LOOKUP_VERSION = "get_version";
  const LOOKUP_SLICE_AUTHORITIES = "get_slice_authorities";
  const LOOKUP_MEMBER_AUTHORITIES = "get_member_authorities";
  const LOOKUP_AGGREGATES = "get_aggregates";
}

// Tag for looking up attributes in a service record dictionary
const SERVICE_ATTRIBUTE_TAG = 'service_attributes';

// Defined Service Attributes
const SERVICE_ATTRIBUTE_AM_API_VERSION = "AM_API_VERSION";
const SERVICE_ATTRIBUTE_SPEAKS_FOR = "SPEAKS_FOR";

// For HTML Aggregate Displays
const SERVICE_ATTRIBUTE_AM_TYPE = "UI_AM_TYPE";
const SERVICE_ATTRIBUTE_INSTAGENI_AM = "ui_instageni_am";
const SERVICE_ATTRIBUTE_EXOGENI_AM = "ui_exogeni_am";
const SERVICE_ATTRIBUTE_FOAM_AM = "ui_foam_am";
const SERVICE_ATTRIBUTE_OTHER_AM = "ui_other_am";
const SERVICE_ATTRIBUTE_AM_CAT = "UI_AM_CAT";
const SERVICE_ATTRIBUTE_DEV_CAT = "ui_dev_cat";
const SERVICE_ATTRIBUTE_PROD_CAT = "ui_prod_cat";
const SERVICE_ATTRIBUTE_COMPUTE_CAT = "ui_compute_cat";
const SERVICE_ATTRIBUTE_NETWORK_CAT = "ui_network_cat";
const SERVICE_ATTRIBUTE_STITCHABLE_CAT = "ui_stitchable_cat";


?>

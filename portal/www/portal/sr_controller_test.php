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

require_once('util.php');
require_once('sr_constants.php');
require_once('sr_client.php');

function dump_rows($rows)
{
  global $SR_SERVICE_TYPE_NAMES;
  $count = count($rows);
  $index = 0;
  foreach ($rows as $row) {  
    error_log("ROW = " . print_r($row, true));
    $service_id = $row[SR_TABLE_FIELDNAME::SERVICE_ID];
    $service_type_index = $row[SR_TABLE_FIELDNAME::SERVICE_TYPE];
    $service_type_name = $SR_SERVICE_TYPE_NAMES[$service_type_index];
    $row_image =  $service_id . " " . $service_type_name . ' ' . 
      $row[SR_TABLE_FIELDNAME::SERVICE_URL] . " " .
      $row[SR_TABLE_FIELDNAME::SERVICE_NAME] . " " .
      $row[SR_TABLE_FIELDNAME::SERVICE_DESCRIPTION];
    $attributes = get_attributes_for_service($service_id);
    foreach($attributes as $key => $value) {
      error_log("   $key => $value");
    }
    error_log("row[" . $index . "] = " . $row_image);
    $index = $index + 1;
  }
}

error_log("SR TEST\n");

$rows = get_services();
dump_rows($rows);

$rows = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
dump_rows($rows);

// type, url, cert, name, description, attributes

$test_log_attributes['FOO'] = 'BAR';
$test_log_attributes['FOO2'] = 'BAR2';

$service_id = register_service(
			   SR_SERVICE_TYPE::LOGGING_SERVICE, 
			   'http://foo.bar', 'test_log_cert', 
			   'test_log_service', 
			   'test_log_description', 
			   $test_log_attributes
			   );
$rows = get_services();
dump_rows($rows);

$attributes = get_attributes_for_service($service_id);
error_log("ATTRS = " . print_r($attributes, true));

error_log("get_services_by_attributes (full):");
$attribute_sets = array($attributes);
$rows = get_services_by_attributes($attribute_sets);
dump_rows($rows);

error_log("get_services_by_attributes (empty):");
$attribute_sets = array();
$rows = get_services_by_attributes($attribute_sets);
dump_rows($rows);

error_log("get_services_by_attributes (list of 1 empty):");
$attribute_sets = array(array());
$rows = get_services_by_attributes($attribute_sets);
dump_rows($rows);

$result = remove_service($service_id);
$rows = get_services();
dump_rows($rows);

relative_redirect('debug');
?>

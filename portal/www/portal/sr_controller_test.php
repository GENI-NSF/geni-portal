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
    $service_type_index = $row[SR_ARGUMENT::SERVICE_TYPE];
    $service_type_name = $SR_SERVICE_TYPE_NAMES[$service_type_index];
    $row_image =  $service_type_name . ' ' . $row[SR_ARGUMENT::SERVICE_URL];
    error_log("row[" . $index . "] = " . $row_image);
    $index = $index + 1;
  }
}

error_log("SR TEST\n");

$sr_url = get_sr_url();
$rows = get_services();
dump_rows($rows);

$rows = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
dump_rows($rows);

$result = register_service(SR_SERVICE_TYPE::LOGGING_SERVICE, 'http://foo.bar');
$rows = get_services();
dump_rows($rows);

$result = remove_service(SR_SERVICE_TYPE::LOGGING_SERVICE, 'http://foo.bar');
$rows = get_services();
dump_rows($rows);

error_log("About to start get_services loop");
$rows = get_services();
error_log("NUM_ROWS = " . count($rows));
$rows = get_services();
error_log("NUM_ROWS = " . count($rows));
$rows = get_services();
error_log("NUM_ROWS = " . count($rows));
$rows = get_services();
error_log("NUM_ROWS = " . count($rows));
$rows = get_services();
error_log("NUM_ROWS = " . count($rows));

error_log("End of get_services loop");

relative_redirect('debug');
?>

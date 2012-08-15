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
require_once('cs_constants.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('logging_constants.php');
require_once('logging_client.php');
require_once('user.php');

// Services for logging CH events within the GENI Clearinghouse

error_log("LOG TEST\n");

// Get URL of Logging Service
$sr_url = get_sr_url();
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);

error_log("LOG_URL " . $log_url);

$slice_id = '11111111111111111111111111111111';
$project_id = '22222222222222222222222222222222';

$project_attributes['PROJECT'] = $project_id;

$slice_attributes['PROJECT'] = $project_id;
$slice_attributes['SLICE'] = $slice_id;

$me = geni_loadUser()->account_id;

log_event($log_url, 'Project Created', $project_attributes, $me);
log_event($log_url, 'Slice Created', $slice_attributes, $me);

error_log("By AUTHOR");
$rows = get_log_entries_by_author($log_url, $me);
foreach($rows as $row) {
  error_log("LOG: " . print_r($row, true));
}

error_log("By PROJECT");
$rows = get_log_entries_for_context($log_url, CS_CONTEXT_TYPE::PROJECT, $project_id);
foreach($rows as $row) {
  error_log("LOG: " . print_r($row, true));
}

error_log("By SLICE");
$rows = get_log_entries_for_context($log_url, CS_CONTEXT_TYPE::SLICE, $slice_id);
foreach($rows as $row) {
  error_log("LOG: " . print_r($row, true));
}

error_log("By ATTRIBUTE-P");
$attribs1['FOO'] = 'BAR';
$attribs1['BAZ'] = 'BAZ2';
$attribs2['PROJECT'] = $project_id;
$attribute_sets = array($attribs1, $attribs2);
$rows = get_log_entries_by_attributes($log_url, $attribute_sets);
foreach($rows as $row) {
  error_log("LOG: " . print_r($row, true));
}

error_log("By ATTRIBUTE-S");
$attribs1['FOO'] = 'BAR';
$attribs1['BAZ'] = 'BAZ2';
$attribs2['SLICE'] = $slice_id;
$attribute_sets = array($attribs1, $attribs2);
$rows = get_log_entries_by_attributes($log_url, $attribute_sets);
foreach($rows as $row) {
  error_log("LOG: " . print_r($row, true));
}




relative_redirect('debug');

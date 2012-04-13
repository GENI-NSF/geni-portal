<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
require_once('sa_constants.php');
require_once('sa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');

error_log("SA TEST\n");

$sr_url = get_sr_url();
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);

function dump_slice($row)
{
  //  error_log('DS.row = ' . print_r($row, true));
  error_log("   " 
	    . " SLICE_ID " . $row[SA_SLICE_TABLE_FIELDNAME::SLICE_ID]
	    . " PROJECT_ID " . $row[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID]
	    . " SLICE_NAME " . $row[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME]
	    . " EXPIRATION " . $row[SA_SLICE_TABLE_FIELDNAME::EXPIRATION]
	    . " OWNER_ID " . $row[SA_SLICE_TABLE_FIELDNAME::OWNER_ID]
	    . " SLICE_URN " . $row[SA_SLICE_TABLE_FIELDNAME::SLICE_URN]);
}

function dump_slices($project)
{
  global $sa_url;
  $slice_ids = lookup_slices($sa_url, $project);
  //  error_log("DSS.rows = " . print_r($slice_ids, true));
  foreach($slice_ids as $slice_id) {
    $slice = lookup_slice($sa_url, $slice_id);
    dump_slice($slice);
  }
}

$project = '11111111111111111111111111111111';
$owner = '22222222222222222222222222222222';


$slice_info = create_slice($sa_url, $project, 'SSS', $owner);
$slice_info = $slice_info['value'];
//error_log("SLICE_INFO " . print_r($slice_info, true));
$slice_id = $slice_info['slice_id'];
error_log("SLICE_ID = " . $slice_id);
dump_slices($project);
$slice_info2 = create_slice($sa_url, $project, 'TTT', $owner);
$slice_info2 = $slice_info2['value'];
$slice_id2 = $slice_info2['slice_id'];
error_log("SLICE_ID2 = " . $slice_id2);
dump_slices($project);
renew_slice($sa_url, $slice_id2);
dump_slices($project);

relative_redirect('debug');

?>




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
require_once('file_utils.php');
require_once('db_utils.php');
require_once('sa_constants.php');
require_once('sa_client.php');
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('user.php');


error_log("SA TEST\n");

$sr_url = get_sr_url();
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);

$user = geni_loadUser();
$owner = $user->account_id;

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
  global $owner;
  $slices = lookup_slices($sa_url, $project, $owner);
  error_log("DSS.rows = " . print_r($slices, true));
  foreach($slices as $slice) {
    dump_slice($slice);
  }
}

$project_name = make_uuid();
$project = create_project($pa_url, $project_name, $owner, '');
$slice_info = create_slice($sa_url, $project, $project_name, 'SSS', $owner);
error_log("SLICE_INFO " . print_r($slice_info, true));
$slice_id = $slice_info['slice_id'];
error_log("SLICE_ID = " . $slice_id);
dump_slices($project);
$slice_info2 = create_slice($sa_url, $project, $project_name, 'TTT', $owner);
$slice_id2 = $slice_info2['slice_id'];
error_log("SLICE_ID2 = " . $slice_id2);
dump_slices($project);
$now = new DateTime();
$expire = db_date_format($now);
renew_slice($sa_url, $slice_id2, $expire, $owner);
dump_slices($project);

$slice_urn = $slice_info[SA_SLICE_TABLE_FIELDNAME::SLICE_URN];
$slice_info2 = lookup_slice_by_urn($sa_url, $slice_urn);
error_log("LSBU " . $slice_urn);
dump_slice($slice_info2);

delete_project($pa_url, $project);

relative_redirect('debug');

?>




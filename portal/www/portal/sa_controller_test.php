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
require_once('ma_client.php');
require_once('user.php');
require_once('portal.php');


error_log("SA TEST\n");

$sr_url = get_sr_url();
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

$members = get_member_ids($ma_url, Portal::getInstance());
if(count($members) < 3) {
  error_log("Need 3 or more members to run sa_controller_test");
  relative_redirect('debug');
}

$user = geni_loadUser();
$owner = $members[0];
$member1 = $members[1];
$member2 = $members[2];

function dump_rows($rows)
{
  error_log("DUMP_ROWS:");
  foreach($rows as $row) {
    error_log("   " . $row[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID] . " " . 
	      $row[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE]);
  }
}

// Dump a slice_id/role pair
function dump_sids($sids)
{
  error_log("DUMP_SIDS: ");
  foreach($sids as $sid) {
    error_log("   " . $sid[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID] . " " . 
	      $sid[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE]);
  }
}

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

function dump_slices($user, $project)
{
  global $sa_url;
  $slices = lookup_slices($sa_url, $user, $project, $user->account_id);
  error_log("DSS.rows = " . print_r($slices, true));
  foreach($slices as $slice) {
    dump_slice($slice);
  }
}

$project_name = make_uuid();
$project = create_project($pa_url, $user, $project_name, $owner, '', null);
$slice_info = create_slice($sa_url, $user, $project, $project_name, 'SSS',
                           $owner);
error_log("SLICE_INFO " . print_r($slice_info, true));
$slice_id = $slice_info['slice_id'];
error_log("SLICE_ID = " . $slice_id);
dump_slices($user, $project);
$slice_info2 = create_slice($sa_url, $user, $project, $project_name, 'TTT',
                            $owner);
$slice_id2 = $slice_info2['slice_id'];
error_log("SLICE_ID2 = " . $slice_id2);
dump_slices($user, $project);
$now = new DateTime(null, new DateTimeZone('UTC'));
$expire = db_date_format($now);
renew_slice($sa_url, $user, $slice_id2, $expire);
dump_slices($user, $project);

$slice_urn = $slice_info[SA_SLICE_TABLE_FIELDNAME::SLICE_URN];
$slice_info2 = lookup_slice_by_urn($sa_url, $user, $slice_urn);
error_log("LSBU " . $slice_urn);
dump_slice($slice_info2);


$slice_info3 = create_slice($sa_url, $user, $project, $project_name, "SSS3", $user->account_id);
$slice_id3 = $slice_info3[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
$slice_info4 = create_slice($sa_url, $user, $project, $project_name, "SSS4", $user->account_id);
$slice_id4 = $slice_info4[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
$slice_info5 = create_slice($sa_url, $user, $project, $project_name, "SSS5", $user->account_id);
$slice_id5 = $slice_info5[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];


$result = add_slice_member($sa_url, $user, $slice_id3, $member1, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_slice_member($sa_url, $user, $slice_id3, $member2, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_slice_member($sa_url, $user, $slice_id4, $member1, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_slice_member($sa_url, $user, $slice_id5, $member1, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_slice_member($sa_url, $user, $slice_id5, $member2, CS_ATTRIBUTE_TYPE::MEMBER);
$result = remove_slice_member($sa_url, $user, $slice_id5, $member1);
$result = change_slice_member_role($sa_url, $user, $slice_id5, $member2, CS_ATTRIBUTE_TYPE::AUDITOR);
$rows = get_slice_members($sa_url, $user, $slice_id3);
dump_rows($rows);
$rows = get_slice_members($sa_url, $user, $slice_id3, CS_ATTRIBUTE_TYPE::MEMBER);
dump_rows($rows);
$sids = get_slices_for_member($sa_url, $user, $user->account_id, true);
dump_sids($sids);
$sids = get_slices_for_member($sa_url, $user, $user->account_id, false);
dump_sids($sids);
$sids = get_slices_for_member($sa_url, $user, $user->account_id, true, CS_ATTRIBUTE_TYPE::MEMBER);
dump_sids($sids);
$sids = get_slices_for_member($sa_url, $user, $user->account_id, false, CS_ATTRIBUTE_TYPE::AUDITOR);
dump_sids($sids);

delete_project($pa_url, $user, $project);

relative_redirect('debug');

?>




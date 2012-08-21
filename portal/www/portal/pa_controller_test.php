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
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('ma_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('cs_constants.php');
require_once('user.php');
require_once('portal.php');

error_log("PA TEST\n");

$sr_url = get_sr_url();
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$user = geni_loadUser();

function dump_projects()
{
  global $pa_url;
  $project_ids = get_projects($pa_url, $user);
  //  error_log("PROJECT_IDS = " . $project_ids . " " . print_r($project_ids, true));
  foreach($project_ids as $project_id) {
    //    error_log("PROJECT_ID = " . $project_id . " " . print_r($project_id, true));
    $details = lookup_project($pa_url, $user, $project_id);
    error_log("   PROJECT " . $project_id . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]);
  }
}

function dump_rows($rows)
{
  error_log("DUMP_ROWS:");
  foreach($rows as $row) {
    error_log("   " . $row[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID] . " " . 
	      $row[PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE]);
  }
}

function dump_pids($pids)
{
  error_log("DUMP_PIDS:");
  foreach($pids as $pid) {
    error_log("   " . $pid);
  }
}

$members = get_member_ids($ma_url, Portal::getInstance());
if(count($members) < 3) {
  error_log("Need 3 or more members to run pa_controller_test");
  relative_redirect('debug');
}

$lead1 = $members[0];
$lead2 = $members[1];
$lead3 = $members[2];

$project_id1 = create_project($pa_url, $user, "PROJ1", $lead1, 
			      "example@foo.com", "Save the world");
error_log("PID = " . $project_id1);
dump_projects();

$result = update_project($pa_url, $user, $project_id1, "PROJ1-A", 
			 "foo@example.com", "More saving");
$result = change_lead($pa_url, $user, $project_id1, $lead1, $lead2);
//error_log("UPDATE.result = " . $result);
dump_projects();

$project_id2 = create_project($pa_url, $user, 
			      "PROJ2", $lead3, "foo@bar.net", "Waste of time");
//error_log("PID = " . $project_id);
dump_projects();

dump_projects();

$project_id3 = create_project($pa_url, $user, 
			      "PROJ3", $lead1, "foo@bar.net", "Waste of time");
$project_id4 = create_project($pa_url, $user, 
			      "PROJ4", $lead1, "foo@bar.net", "Waste of time");
$project_id5 = create_project($pa_url, $user,
			      "PROJ5", $lead1, "foo@bar.net", "Waste of time");

$result = add_project_member($pa_url, $user, $project_id3, $lead2, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_project_member($pa_url, $user, $project_id3, $lead3, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_project_member($pa_url, $user, $project_id4, $lead2, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_project_member($pa_url, $user, $project_id5, $lead2, CS_ATTRIBUTE_TYPE::MEMBER);
$result = add_project_member($pa_url, $user, $project_id5, $lead3, CS_ATTRIBUTE_TYPE::MEMBER);
$result = remove_project_member($pa_url, $user, $project_id5, $lead2);
$result = change_member_role($pa_url, $user, $project_id5, 
			     $lead3, CS_ATTRIBUTE_TYPE::AUDITOR);
$rows = get_project_members($pa_url, $user, $project_id3);
dump_rows($rows);
$rows = get_project_members($pa_url, $user, $project_id3, CS_ATTRIBUTE_TYPE::MEMBER);
dump_rows($rows);
$pids = get_projects_for_member($pa_url, $user, $lead2, true);
dump_pids($pids);
$pids = get_projects_for_member($pa_url, $user, $lead2, false);
dump_pids($pids);
$pids = get_projects_for_member($pa_url, $user, $lead2, true, CS_ATTRIBUTE_TYPE::MEMBER);
dump_pids($pids);
$pids = get_projects_for_member($pa_url, $user, $lead2, false, CS_ATTRIBUTE_TYPE::AUDITOR);
dump_pids($pids);

delete_project($pa_url, $user, $project_id1);
delete_project($pa_url, $user, $project_id2);
delete_project($pa_url, $user, $project_id3);
delete_project($pa_url, $user, $project_id4);
delete_project($pa_url, $user, $project_id5);

relative_redirect('debug');

?>

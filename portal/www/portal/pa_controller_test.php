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
require_once('sr_constants.php');
require_once('sr_client.php');

error_log("PA TEST\n");

$sr_url = get_sr_url();
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);

function dump_projects()
{
  global $pa_url;
  $project_ids = get_projects($pa_url);
  //  error_log("PROJECT_IDS = " . $project_ids . " " . print_r($project_ids, true));
  foreach($project_ids as $project_id) {
    //    error_log("PROJECT_ID = " . $project_id . " " . print_r($project_id, true));
    $details = lookup_project($pa_url, $project_id);
    error_log("   PROJECT " . $project_id . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL] . " " 
	      . $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE]);
  }
}

$project_id = create_project($pa_url, "PROJ1", "LEAD1", "example@foo.com", "Save the world");
//error_log("PID = " . $project_id);
dump_projects();

$result = update_project($pa_url, $project_id, "PROJ2", "LEAD2", "foo@example.com", "More saving");
//error_log("UPDATE.result = " . $result);
dump_projects();

$project_id2 = create_project($pa_url, "PROJ3", "LEAD3", "foo@bar.net", "Waste of time");
//error_log("PID = " . $project_id);
dump_projects();

$result = delete_project($pa_url, $project_id2);
dump_projects();

relative_redirect('debug');

?>

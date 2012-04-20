<?php
// Client-side interface to GENI Clearinghouse Project Authority (PA)
// Consists of these methods:
//   project_id <= create_project(pa_url, project_name, lead_id, lead_email, purpose)
//   delete_project(pa_url, project_id);
//   project_ids <= get_projects(pa_url);
//   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
//   update_project(pa_url, project_name, project_id, project_email, project_purpose);
//   change_lead(pa_url, project_id, previous_lead_id, new_lead_id);

require_once('pa_constants.php');
require_once('message_handler.php');

// Create a project with given name, lead_id (UUID of lead member), email to contact on all 
// matters related to project, and documentation purpose of project
function create_project($pa_url, $project_name, $lead_id, $project_purpose)
{
  $create_project_message['operation'] = 'create_project';
  $create_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $create_project_message[PA_ARGUMENT::LEAD_ID] = $lead_id;
  $create_project_message[PA_ARGUMENT::PROJECT_PURPOSE] = $project_purpose;

  // error_log("CP.args = " . print_r($create_project_message, true) . " " . $create_project_message);

  // FIXME: Disallow if project_name already taken!

  $project_id = put_message($pa_url, $create_project_message);
  return $project_id;
}

// Delete given projectt of given ID
function delete_project($pa_url, $project_id)
{
  $delete_project_message['operation'] = 'delete_project';
  $delete_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $result = put_message($pa_url, $delete_project_message);
  return $result;
}

function get_projects($pa_url)
{
  $get_projects_message['operation'] = 'get_projects';
  $project_ids = put_message($pa_url, $get_projects_message);
  return $project_ids;
}

function get_projects_by_lead($pa_url, $lead_id)
{
  //  error_log("GPBL.start " . $lead_id . " " . time());
  $get_projects_message['operation'] = 'get_projects';
  $get_projects_message[PA_ARGUMENT::LEAD_ID] = $lead_id;
  $project_ids = put_message($pa_url, $get_projects_message);
  //  error_log("GPBL.end " . $lead_id . " " . time());
  return $project_ids;
}

function lookup_projects($pa_url, $lead_id=null)
{
  $lookup_projects['operation'] = 'lookup_projects';
  if( $lead_id <> null) {
    $lookup_projects[PA_ARGUMENT::LEAD_ID] = $lead_id;
  }
  $projects = put_message($pa_url, $lookup_projects);
  return $projects;
}

function lookup_project($pa_url, $project_id)
{
  //  error_log("LP.start " . $project_id . " " . time());
  $lookup_project_message['operation'] = 'lookup_project';
  $lookup_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $details = put_message($pa_url, $lookup_project_message);
  //  error_log("LP.end " . $project_id . " " . time());
  // FIXME: Could be >1?
  return $details;
}

function update_project($pa_url, $project_id, $project_name, $project_purpose)
{
  $update_project_message['operation'] = 'update_project';
  $update_project_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $update_project_message[PA_ARGUMENT::PROJECT_NAME] = $project_name;
  $update_project_message[PA_ARGUMENT::PROJECT_PURPOSE] = $project_purpose;
  $results = put_message($pa_url, $update_project_message);
  return $results;
}

function change_lead($pa_url, $project_id, $prev_lead_id, $new_lead_id)
{
  $change_lead_message['operation'] = 'change_lead';
  $change_lead_message[PA_ARGUMENT::PROJECT_ID] = $project_id;
  $change_lead_message[PA_ARGUMENT::PREVIOUS_LEAD_ID] = $prev_lead_id;
  $change_lead_message[PA_ARGUMENT::LEAD_ID] = $new_lead_id;
  $results = put_message($pa_url, $change_lead_message);
  return $results;
}

?>

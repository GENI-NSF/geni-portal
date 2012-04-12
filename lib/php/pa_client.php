<?php
// Client-side interface to GENI Clearinghouse Project Authority (PA)
// Consists of these methods:
//   project_id <= create_project(pa_url, project_name, lead_id, lead_email, purpose)
//   delete_project(pa_url, project_id);
//   project_ids <= get_projects(pa_url);
//   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
//   update_project(pa_url, project_name, project_id, lead_id, project_email, project_purpose);

require_once('pa_constants.php');

// Create a project with given name, lead_id (UUID of lead member), email to contact on all 
// matters related to project, and documentation purpose of project
function create_project($pa_url, $project_name, $lead_id, $project_email, $project_purpose)
{
  $create_project_message['operation'] = 'create_project';
  $create_project_message['project_name'] = $project_name;
  $create_project_message['lead_id'] = $lead_id;
  $create_project_message['project_email'] = $project_email;
  $create_project_message['project_purpose'] = $project_purpose;

  // error_log("CP.args = " . print_r($create_project_message, true) . " " . $create_project_message);

  // FIXME: Disallow if project_name already taken!

  $project_id = put_message($pa_url, $create_project_message);
  return $project_id;
}

// Delete given projectt of given ID
function delete_project($pa_url, $project_id)
{
  $delete_project_message['operation'] = 'delete_project';
  $delete_project_message['project_id'] = $project_id;
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
  $get_projects_message['operation'] = 'get_projects';
  $get_projects_message[PA_ARGUMENT::LEAD_ID] = $lead_id;
  $project_ids = put_message($pa_url, $get_projects_message);
  return $project_ids;
}

function lookup_project($pa_url, $project_id)
{
  $lookup_project_message['operation'] = 'lookup_project';
  $lookup_project_message['project_id'] = $project_id;
  $details = put_message($pa_url, $lookup_project_message);
  // FIXME: Could be >1?
  return $details;
}

function update_project($pa_url, $project_id, $project_name, $lead_id, $project_email, $project_purpose)
{
  $update_project_message['operation'] = 'update_project';
  $update_project_message['project_id'] = $project_id;
  $update_project_message['project_name'] = $project_name;
  $update_project_message['lead_id'] = $lead_id;
  $update_project_message['project_email'] = $project_email;
  $update_project_message['project_purpose'] = $project_purpose;
  $results = put_message($pa_url, $update_project_message);
  return $results;
}
?>

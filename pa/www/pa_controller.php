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

require_once('message_handler.php');
require_once('db_utils.php');
require_once('file_utils.php');
require_once('response_format.php');
require_once('pa_constants.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('ma_client.php');
require_once('cs_client.php');

/**
 * GENI Clearinghouse Project Authority (PA) controller interface
 * The PA maintains a list of projects, their details and members and provides access
 * to creating, looking up, updating, deleting projects.
 * 
 * Supports these methods:
 *   project_id <= create_project(pa_url, project_name, lead_id, lead_email, purpose)
 *   delete_project(pa_url, project_id);
 *   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
 *   update_project(pa_url, project_id, project_email, project_purpose);
 *   change_lead(pa_url, project_id, previous_lead_id, new_lead_id); *
 **/

$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

/**
 * Create project of given name, lead_id, email and purpose
 * Return project id of created project
 */
function create_project($args)
{
  global $PA_PROJECT_TABLENAME;
  global $cs_url;

  //  error_log("ARGS = " . print_r($args, true));

  $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  $lead_id = $args[PA_ARGUMENT::LEAD_ID];
  $project_purpose = $args[PA_ARGUMENT::PROJECT_PURPOSE];
  $project_id = make_uuid();

  $permitted = request_authorization($cs_url, $lead_id, 'create_project', 
				     CS_CONTEXT_TYPE::RESOURCE, null);
  //  error_log("PERMITTED = " . $permitted);
  if ($permitted < 1) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted, 
			     "Principal " . $lead_id  . " may not create project");
  } 

  $project_email = 'project-' . $project_name . '@example.com';
  
  $sql = "INSERT INTO " . $PA_PROJECT_TABLENAME 
    . "(" 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . ") " 
    . "VALUES ("
    . "'" . $project_id . "', " 
    . "'" . $project_name . "', " 
    . "'" . $lead_id . "', " 
    . "'" . $project_email . "', " 
    . "'" . $project_purpose . "') ";

  //  error_log("SQL = " . $sql);
  $result = db_execute_statement($sql);

  //  error_log("CREATE " . $result . " " . $sql . " " . $project_id);

  // Create an assertion that this lead is the lead of the project (and has associated privileges)
  global $cs_url;
  $signer = null; // *** FIX ME
  create_assertion($cs_url, $signer, $lead_id, CS_ATTRIBUTE_TYPE::LEAD,
		   CS_CONTEXT_TYPE::PROJECT, $project_id);

  // Associate the lead with the project with role 'lead'
  global $ma_url;
  add_attribute($ma_url, $lead_id, CS_ATTRIBUTE_TYPE::LEAD, CS_CONTEXT_TYPE::PROJECT, $project_id);

  return generate_response(RESPONSE_ERROR::NONE, $project_id, '');
}

/**
 * Delete given project of given ID
 */
function delete_project($args)
{
  global $PA_PROJECT_TABLENAME;
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];

  $sql = "DELETE FROM " . $PA_PROJECT_TABLENAME 
    . " WHERE " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID
    . " = '" . $project_id . "'";

  //  error_log("DELETE.sql = " . $sql);

  $result = db_execute_statement($sql);

  return $result;
}
/* Return list of all project ID's, optionally limited by lead_id */
function get_projects($args)
{
  global $PA_PROJECT_TABLENAME;
  $sql = "select " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID 
    . " FROM " . $PA_PROJECT_TABLENAME;
  if (array_key_exists(PA_ARGUMENT::LEAD_ID, $args)) {
    $sql = $sql . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . 
      " = '" . $args[PA_ARGUMENT::LEAD_ID] . "'";
  }

  $project_ids = array();
  //  error_log("GET_PROJECTS.sql = " . $sql . "\n");

  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $project_id_rows = $result[RESPONSE_ARGUMENT::VALUE];
    foreach($project_id_rows as $project_id_row) {
      $project_id = $project_id_row[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
      $project_ids[] = $project_id;
    }
    return generate_response(RESPONSE_ERROR::NONE, $project_ids, '');
  } else
    return $result;
}


/* Lookup details of given project */
function lookup_project($args)
{
  global $PA_PROJECT_TABLENAME;

  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! isset($project_id) || is_null($project_id) || $project_id == '') {
    error_log("Missing project ID to lookup_project");
    return null;
  }

  $sql = "select "  
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE 
    . " FROM " . $PA_PROJECT_TABLENAME
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID 
    . " = '" . $project_id . "'";

  //  error_log("LOOKUP.sql = " . $sql);

  $row = db_fetch_row($sql);
  return $row;
}

/* Update details of given project */
function update_project($args)
{
  global $PA_PROJECT_TABLENAME;

  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  $project_purpose = $args[PA_ARGUMENT::PROJECT_PURPOSE];

  $sql = "UPDATE " . $PA_PROJECT_TABLENAME 
    . " SET " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . " = '" . $project_name . "', "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . " = '" . $project_purpose . "' "
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID 
    . " = '" . $project_id . "'";

  //  error_log("UPDATE.sql = " . $sql);

  $result = db_execute_statement($sql);
  return $result;
}

/* update lead of given project */
function change_lead($args)
{
  global $PA_PROJECT_TABLENAME;

  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $previous_lead_id = $args[PA_ARGUMENT::PREVIOUS_LEAD_ID];
  $new_lead_id = $args[PA_ARGUMENT::LEAD_ID];

  $sql = "UPDATE " . $PA_PROJECT_TABLENAME
    . " SET " 
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . " = '" . $new_lead_id . "'"
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID
    . " = '" . $project_id . "'";
  //  error_log("CHANGE_LEAD.sql = " . $sql);
  $result = db_execute_statement($sql);

  // *** FIX ME - Delete previous from MA and from CS

  return $result;
}


handle_message("PA");

?>

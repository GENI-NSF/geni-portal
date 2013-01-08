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

$prev_name = session_id('PA-SESSION');

require_once('message_handler.php');
require_once('db_utils.php');
require_once('signer.php');
require_once('file_utils.php');
require_once('response_format.php');
require_once('pa_constants.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('ma_client.php');
require_once('cs_client.php');
require_once('logging_client.php');
require_once('cert_utils.php');

include_once('/etc/geni-ch/settings.php');

/**
 * GENI Clearinghouse Project Authority (PA) controller interface
 * The PA maintains a list of projects, their details and members and provides access
 * to creating, looking up, updating, deleting projects.
 * 
 * Supports these methods:
 *   project_id <= create_project(pa_url, project_name, lead_id, lead_email, purpose, expiration)
 *   delete_project(pa_url, project_id);
 *   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
 *   update_project(pa_url, project_id, project_email, project_purpose, expiration);
 *   change_lead(pa_url, project_id, previous_lead_id, new_lead_id); *
 *   add_project_member(pa_url, project_id, member_id, role)
 *   remove_project_member(pa_url, project_id, member_id)
 *   change_member_role(pa_url, project_id, member_id, role)
 *   get_project_members(pa_url, project_id, role=null) // null => Any
 *   get_projects_for_member(pa_url, member_id, is_member, role=null)
 **/

$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);

function pa_debug($msg)
{
  //  error_log('PA DEBUG: ' . $msg);
}

/*----------------------------------------------------------------------
 * Authorization
 *----------------------------------------------------------------------
 */
class PAGuardFactory implements GuardFactory
{
  private static $context_table
    = array(
            // Action => array(method_name, method_name, ...)
	    'create_project' => array(), // Unguarded
	    'delete_project' => array('project_guard'),
	    'get_projects' => array(), // Unguarded
	    'lookup_projects' => array(), // Unguarded
	    'lookup_project' => array(), // Unguarded
	    'update_project' => array('project_guard'), 
	    'change_lead' => array('project_guard'),
	    'add_project_member' => array('project_guard'),
	    'remove_project_member' => array('project_guard'),
	    'change_member_role' => array('project_guard'),
	    'get_project_members' => array(), // Unguarded
	    'get_projects_for_member' => array(), // Unguarded
	    'create_request' => array(), // Unguarded
	    'resolve_pending_request' => array('project_request_guard'),
	    'get_requests_for_context' => array(), // Unguarded
	    'get_requests_by_user' => array(), // Unguarded
	    'get_pending_requests_for_user' => array(), // Unguarded
	    'get_number_of_pending_requests_for_user' => array(), // Unguarded
	    'get_request_by_id' => array() // Unguarded
            );

  public function __construct($cs_url) {
    $this->cs_url = $cs_url;
  }

  public function project_request_guard($message, $action, $params)
  {
    //    error_log("PA.project_request_guard " . print_r($message, true) . " " . 
    //	      print_r($action, true) . " " . print_r($params, true));
    return new PAProjectRequestGuard($this->cs_url, $message, $action, $params);
  }

  private function project_guard($message, $action, $params) {
    pa_debug("project_guard($message, $action, $params)");
    return new PAContextGuard($this->cs_url, $message, $action,
                              CS_CONTEXT_TYPE::PROJECT,
                              $params[PA_ARGUMENT::PROJECT_ID]);
  }

  public function createGuards($message) {
    $result = array();
    $parsed_message = $message->parse();
    $action = $parsed_message[0];
    $params = $parsed_message[1];
    if (array_key_exists($action, self::$context_table)) {
      foreach (self::$context_table[$action] as $method_name) {
        // How to call a method dynamically
        $meth = array($this, $method_name);
        $result[] = call_user_func($meth, $message, $action, $params);
      }
    } else {
      error_log("PA: No guard producers for action \"$action\"");
    }
    return $result;
  }
}

// A guard to check that the requester to resolve a request (accept, reject) has lead or admin
// privileges on the project in question
class PAProjectRequestGuard implements Guard
{
  public function __construct($cs_url, $message, $action, $params)
  {
    $this->cs_url = $cs_url;
    $this->message = $message;
    $this->action = $action;
    $this->params = $params;
  }

  public function evaluate()
  {
    $signer = $this->message->signerUuid();
    $request_id = $this->params[RQ_ARGUMENTS::REQUEST_ID];
    //    error_log("PARAMS = " . print_r($this->params, true));
    //    error_log("PAProjectRequestGuard.evaluate " . $signer . " " . print_r($request_id, true));
    global $PA_PROJECT_MEMBER_TABLENAME;
    global $REQUEST_TABLENAME;
    $conn = db_conn();
    $sql = "select count(*) from $PA_PROJECT_MEMBER_TABLENAME, $REQUEST_TABLENAME " 
      . " WHERE "
      . " $REQUEST_TABLENAME." . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $conn->quote($request_id, 'text')
      . " AND " 
      . " $REQUEST_TABLENAME." . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = "
      . " $PA_PROJECT_MEMBER_TABLENAME." . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
      . " AND " 
      . " $PA_PROJECT_MEMBER_TABLENAME." . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " . $conn->quote($signer, 'text')
      . " AND "
      . " $PA_PROJECT_MEMBER_TABLENAME." . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
      . " IN (" . $conn->quote(CS_ATTRIBUTE_TYPE::LEAD, 'integer') . ", " . $conn->quote(CS_ATTRIBUTE_TYPE::ADMIN, 'integer') . ")";
    //    error_log("PAProjectRequestGuard.sql = $sql");
    $result = db_fetch_row($sql);
    //    error_log("Result = " . print_r($result, true));
    $allowed = FALSE;
    if($result['code'] == RESPONSE_ERROR::NONE) {
      $result = $result['value']['count'];
      $allowed = $result > 0;
    }

    // If not allowed but requestor is signer and request status is cancelled, then allow it
    if (! $allowed) {
      $resolution_status = null;
      if (array_key_exists(RQ_ARGUMENTS::RESOLUTION_STATUS, $this->params)) {
	$resolution_status = $this->params[RQ_ARGUMENTS::RESOLUTION_STATUS];
	//error_log("not allowed but res_status= $resolution_status");
	if ($resolution_status == RQ_REQUEST_STATUS::CANCELLED) {

	  $sql = "select " . RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR . " FROM $REQUEST_TABLENAME WHERE "
	    . " $REQUEST_TABLENAME." . RQ_REQUEST_TABLE_FIELDNAME::ID . " = " . $conn->quote($request_id, 'text');
	  //error_log("doing sql $sql");
	  $result = db_fetch_row($sql);
	  if ($result['code'] == RESPONSE_ERROR::NONE and $result['value'][RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR] == $signer) {
	    $allowed = true;
	    //} else {
	    //  error_log(print_r($result));
	  }
	}
      }
    }

    //    error_log("Allowed = " . print_r($allowed, true));
    return $allowed;
  }
}



class PAContextGuard implements Guard
{
  function __construct($cs_url, $message, $action, $context_type, $context) {
    $this->cs_url = $cs_url;
    $this->message = $message;
    $this->action = $action;
    $this->context_type = $context_type;
    $this->context = $context;
  }
  /**
   * Return TRUE if the action is authorized, FALSE otherwise.
   */
  function evaluate() {
    global $mysigner;
    pa_debug("MessageHandler requesting authorization:"
             . " for principal=\""
             . print_r($this->message->signerUuid(), TRUE) . "\""
             . "; action=\"" . print_r($this->action, TRUE) . "\""
             . "; context_type=\"" . print_r($this->context_type, TRUE) . "\""
             . "; context=\"" . print_r($this->context, TRUE) . "\"");
    return request_authorization($this->cs_url, $mysigner, 
				 $this->message->signerUuid(),
                                 $this->action, $this->context_type,
                                 $this->context);
  }
}

function valid_expiration($expiration)
{
  $exp_timestamp = strtotime($expiration);
  // Any valid (ie. parseable) date is fine
  return $exp_timestamp !== false;
}

/*----------------------------------------------------------------------
 * API Methods
 *----------------------------------------------------------------------
 */

/**
 * Create project of given name, lead_id, email and purpose
 * Return project id of created project
 */
function create_project($args, $message)
{
  global $PA_PROJECT_TABLENAME;
  global $cs_url;
  global $mysigner;

  //  error_log("ARGS = " . print_r($args, true));

  $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  if (! isset($project_name) or is_null($project_name) or $project_name == '') {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, 
			     "Project name missing");
  }
  if (strpos($project_name, ' ') !== false) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, 
			     "Project name '$project_name' invalid: no spaces allowed.");
  }

  if (!is_valid_project_name($project_name)) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, 
			     "Project name '$project_name' invalid: Avoid /:+;'?#% ");
  }
  $lead_id = $args[PA_ARGUMENT::LEAD_ID];
  $project_purpose = $args[PA_ARGUMENT::PROJECT_PURPOSE];
  if (array_key_exists(PA_ARGUMENT::EXPIRATION, $args)) {
    $expiration = $args[PA_ARGUMENT::EXPIRATION];
  } else {
    $expiration = NULL;
  }
  $project_id = make_uuid();
  $conn = db_conn();

  $exists_sql = "select count(*) from " . $PA_PROJECT_TABLENAME 
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . " = " . $conn->quote($project_name, 'text');
  $exists_response = db_fetch_row($exists_sql);
  $exists = $exists_response[RESPONSE_ARGUMENT::VALUE];
  $exists = $exists['count'];
  if ($exists > 0) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, 
			     "Project of name " . $project_name . " already exists.");
  }


  $permitted = request_authorization($cs_url, $mysigner, $lead_id, 'create_project', 
				     CS_CONTEXT_TYPE::RESOURCE, null);
  //  error_log("PERMITTED = " . $permitted);
  if ($permitted < 1) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted, 
			     "Principal " . $lead_id  . " may not create project");
  } 

  // FIXME: Real project email address: ticket #313
  $project_email = 'project-' . $project_name . '@example.com';
  
  $creation = new DateTime(null, new DateTimeZone('UTC'));
  if ($expiration) {
    if (! valid_expiration($expiration)) {
      return generate_response(RESPONSE_ERROR::ARGS, "", "Invalid date: \"$expiration\"");
    }
    $db_expiration = $conn->quote($expiration, 'text');
  } else {
    $db_expiration = "NULL";
  }

  $sql = "INSERT INTO " . $PA_PROJECT_TABLENAME 
    . "(" 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", " 
    . PA_PROJECT_TABLE_FIELDNAME::CREATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . ") "
    . "VALUES ("
    . $conn->quote($project_id, 'text') . ", " 
    . $conn->quote($project_name, 'text') .", "
    . $conn->quote($lead_id, 'text') . ", " 
    . $conn->quote($project_email, 'text') . ", " 
    . $conn->quote(db_date_format($creation), 'timestamp') . ", "
    . $conn->quote($project_purpose, 'text') . ", "
    . $db_expiration. ") ";

  //  error_log("SQL = " . $sql);
  $result = db_execute_statement($sql);

  //  error_log("CREATE " . $result . " " . $sql . " " . $project_id);

  // Now add the lead as a member of the project
  $addres = add_project_member(array(PA_ARGUMENT::PROJECT_ID => $project_id,
                                     PA_ARGUMENT::MEMBER_ID => $lead_id,
                                     PA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD),
                               $message);
  if (! isset($addres) || is_null($addres) || ! array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) || $addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    error_log("create_project failed to add lead as a project member: " . $addres[RESPONSE_ARGUMENT::CODE] . ": " . $addres[RESPONSE_ARGUMENT::OUTPUT]);
    // FIXME: ROLLBACK?
    return $addres;
  }

  // Log the creation
  global $log_url;
  global $mysigner;
  global $ma_url;
  global $portal_admin_email;
  $signer_id = $message->signerUuid();
  $signer_urn = $message->signerUrn();
  $lead_data = ma_lookup_member_by_id($ma_url, $mysigner, $lead_id);
  $lead_name = $lead_data->prettyName();

  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  $msg = "Created project: $project_name with lead $lead_name";
  if ($lead_id != $signer_id) {
    $msg = $signer_urn . " " . $msg;
  }
  log_event($log_url, $mysigner, $msg, $attributes, $lead_id);
  if (parse_urn($message->signer_urn, $chname, $t, $n)) {
    $msg = $msg . " on CH $chname";
  }
  mail($portal_admin_email, "New GENI CH project created", $msg);

  return generate_response(RESPONSE_ERROR::NONE, $project_id, '');
}

/**
 * Delete given project of given ID
 */
function delete_project($args, $message)
{
  global $PA_PROJECT_TABLENAME;
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];

  $conn = db_conn();
  $sql = "DELETE FROM " . $PA_PROJECT_TABLENAME 
    . " WHERE " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID
    . " = " . $conn->quote($project_id, 'text');

  //  error_log("DELETE.sql = " . $sql);

  $result = db_execute_statement($sql);

  // FIXME: Delete relevant assertions
  // FIXME: What about relevant slices? resources

  // Log the deletion
  global $log_url;
  global $mysigner;
  global $ma_url;
  global $portal_admin_email;
  $signer_id = $message->signerUuid();
  $signer_data = ma_lookup_user_by_id($ma_url, $mysigner, $signer_id);
  $signer_name = $signer_data->prettyName();

  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  $msg = "$signer_name Deleted project: $project_name";
  log_event($log_url, $mysigner, $msg, $attributes, $signer_id);
  mail($portal_admin_email, "GENI CH project deleted", $msg);

  return $result;
}

/* Return list of all project ID's, optionally limited by lead_id */
function get_projects($args)
{
  global $PA_PROJECT_TABLENAME;
  $conn = db_conn();
  $sql = "select " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID 
    . " FROM " . $PA_PROJECT_TABLENAME;
  if (array_key_exists(PA_ARGUMENT::LEAD_ID, $args)) {
    $sql = $sql . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . 
      " = " . $conn->quote($args[PA_ARGUMENT::LEAD_ID], 'text');
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

// Return list of all projects and data. 
// Optionally, filtered by lead_id if provided
function lookup_projects($args)
{
  global $PA_PROJECT_TABLENAME;

  $lead_id = null;
  $lead_clause = "";
  $conn = db_conn();
  //  error_log("LP.args = " . print_r($args, true));
  if(array_key_exists(PA_ARGUMENT::LEAD_ID, $args)) {
    $lead_id = $args[PA_ARGUMENT::LEAD_ID];
    $lead_clause = " WHERE " . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . " = " . $conn->quote($lead_id, 'text');
  }

  $sql = "select "  
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", "
    . PA_PROJECT_TABLE_FIELDNAME::CREATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION
    . " FROM " . $PA_PROJECT_TABLENAME 
    . $lead_clause;

  //  error_log("LookupProjects.sql = " . $sql);
 
  $rows = db_fetch_rows($sql);
  return $rows;

}


/* Lookup details of given project */
function lookup_project($args)
{
  global $PA_PROJECT_TABLENAME;

  if (array_key_exists(PA_ARGUMENT::PROJECT_ID, $args)) {
    $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  }
  if (array_key_exists(PA_ARGUMENT::PROJECT_NAME, $args)) {
    $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  }
  if ((! isset($project_id) || is_null($project_id) || $project_id == '')  && (! isset($project_name) || is_null($project_name) || $project_name == '')) {
    error_log("Missing project ID and project name to lookup_project");
    return null;
  }

  $conn = db_conn();
  if (isset($project_id) && ! is_null($project_id) && $project_id != '') {
    $where = " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID 
      . " = " . $conn->quote($project_id, 'text');
  } else {
    $where = " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME 
      . " = " . $conn->quote($project_name, 'text');
  }

  $sql = "select "  
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", "
    . PA_PROJECT_TABLE_FIELDNAME::CREATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION
    . " FROM " . $PA_PROJECT_TABLENAME
    . $where;

  //  error_log("LOOKUP.sql = " . $sql);

  $row = db_fetch_row($sql);
  return $row;
}

/* Update details of given project */
function update_project($args, $message)
{
  global $PA_PROJECT_TABLENAME;

  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $project_purpose = $args[PA_ARGUMENT::PROJECT_PURPOSE];
  $expiration = $args[PA_ARGUMENT::EXPIRATION];

  $conn = db_conn();
  if ($expiration) {
    if (! valid_expiration($expiration)) {
      return generate_response(RESPONSE_ERROR::ARGS, "", "Invalid date: \"$expiration\"");
    }
    $db_expiration = $conn->quote($expiration, 'text');
  } else {
    $db_expiration = "NULL";
  }
  $sql = "UPDATE " . $PA_PROJECT_TABLENAME 
    . " SET " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . " = " . $conn->quote($project_purpose, 'text')
    . ", " . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . " = " . $db_expiration
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID 
    . " = " . $conn->quote($project_id, 'text');

  //  error_log("UPDATE.sql = " . $sql);

  $result = db_execute_statement($sql);

  global $log_url;
  global $mysigner;
  global $ma_url;
  global $portal_admin_email;
  $signer_id = $message->signerUuid();
  $signer_data = ma_lookup_member_by_id($ma_url, $mysigner, $signer_id);
  $signer_name = $signer_data->prettyName();
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  $msg = "$signer_name Updated project $project_id with purpose $project_purpose";
  log_event($log_url, $mysigner, $msg, $attributes, $signer_id);

  return $result;
}

/* update lead of given project */
function change_lead($args, $message)
{
  global $PA_PROJECT_TABLENAME;

  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $previous_lead_id = $args[PA_ARGUMENT::PREVIOUS_LEAD_ID];
  $new_lead_id = $args[PA_ARGUMENT::LEAD_ID];

  // Check that new person is allowed to be a lead
  $permitted = request_authorization($cs_url, $mysigner, $new_lead_id, 'create_project', 
				     CS_CONTEXT_TYPE::RESOURCE, null);
  //  error_log("PERMITTED = " . $permitted);
  if ($permitted < 1) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted, 
			     "Principal " . $new_lead_id  . " may not lead projects");
  } 

  $conn = db_conn();
  $sql = "UPDATE " . $PA_PROJECT_TABLENAME
    . " SET " 
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . " = " . $conn->quote($new_lead_id, 'text')
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID
    . " = " . $conn->quote($project_id, 'text');
  //  error_log("CHANGE_LEAD.sql = " . $sql);
  $result = db_execute_statement($sql);

  // Now add the lead as a member of the project
  $addres = add_project_member(array(PA_ARGUMENT::PROJECT_ID => $project_id,
                                     PA_ARGUMENT::MEMBER_ID => $new_lead_id,
                                     PA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD),
                               $message);
  if (! isset($addres) || is_null($addres) || ! array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) || $addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    // Lets assume they are already in the project, so we need to change their role only
    $chngres = change_member_role(array(PA_ARGUMENT::PROJECT_ID => $project_id,
                                     PA_ARGUMENT::MEMBER_ID => $new_lead_id,
                                     PA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD),
                               $message);

    // FIXME: Check for chngres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE
  }

  // Make the old lead an admin
  $chngres = change_member_role(array(PA_ARGUMENT::PROJECT_ID => $project_id,
                                     PA_ARGUMENT::MEMBER_ID => $lead_id,
                                     PA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::ADMIN),
                               $message);

  // FIXME: Check for chngres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE

  global $log_url;
  global $mysigner;
  global $ma_url;
  global $portal_admin_email;
  $signer_id = $message->signerUuid();
  $signer_data = ma_lookup_member_by_id($ma_url, $mysigner, $signer_id);
  $signer_name = $signer_data->prettyName();
  $new_lead_data = ma_lookup_member_by_id($ma_url, $mysigner, $new_lead_id);
  $new_lead_name = $new_lead_data->prettyName();

  $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  // FIXME: We'd like to add as a context the old lead, but only 1 member context allowed
  $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $new_lead_id);
  $attributes = array_merge($pattributes, $mattributes);
  $msg = "$signer_name changed project lead for $project_name to $new_lead_name";
  log_event($log_url, $mysigner, $msg, $attributes, $signer_id);
  mail($portal_admin_email, "Changed GENI CH project lead", $msg);

  return $result;
}

// Add a member of given role to given project
// Return code/value/output triple
function add_project_member($args, $message)
{
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  $role = null;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args)) {
    $role = $args[PA_ARGUMENT::ROLE_TYPE];
  }

  global $PA_PROJECT_MEMBER_TABLENAME;
  global $mysigner;

  $conn = db_conn();
  $already_member_sql = "select count(*) from " . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID . " = " . $conn->quote($project_id, 'text')
    . " AND " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " . $conn->quote($member_id, 'text');
  $already_member = db_fetch_row($already_member_sql);
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if (! array_key_exists('value', $already_member) or ! array_key_exists('count', $already_member['value'])) {
    return $already_member;
  }
  $already_member = $already_member['value']['count'] > 0;
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if ($already_member) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Member $member_id is already a member of project $project_id");
  }


  $sql = "INSERT INTO " . $PA_PROJECT_MEMBER_TABLENAME . " ("
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . ") VALUES ("
    . $conn->quote($project_id, 'text') . ", "
    . $conn->quote($member_id, 'text') . ", "
    . $conn->quote($role, 'integer') . ")";
  //  error_log("PA.add project_member.sql = " . $sql);
  $result = db_execute_statement($sql);

  /* FIXME - The signer needs to have a certificate and private key. Who sends this message (below)
   * to the CS? Is the PA the signer?
   */
  $signer_id = $message->signerUuid();

  // If successful, add an assertion of the role's privileges within the CS store
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    create_assertion($cs_url, $mysigner, $signer_id, $member_id, $role, CS_CONTEXT_TYPE::PROJECT, $project_id);
  }

  // Log adding the member
  global $ma_url;
  $member_data = ma_lookup_member_by_id($ma_url, $mysigner, $member_id);
  $signer_data = ma_lookup_member_by_id($ma_url, $mysigner, $signer_id);

  $lookup_project_message = array(PA_ARGUMENT::PROJECT_ID => $project_id);
  $project_data = lookup_project($lookup_project_message);
  if (($project_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
      (array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, 
			$project_data[RESPONSE_ARGUMENT::VALUE]))) 
    {
      global $CS_ATTRIBUTE_TYPE_NAME;
      global $log_url;
      // From /etc/geni-ch/settings.php
      global $portal_admin_email;
  //  error_log("MD = " . print_r($member_data, true));
  //  error_log("PD = " . print_r($project_data, true));
      $project_data = $project_data[RESPONSE_ARGUMENT::VALUE];
      $member_name = $member_data->prettyName();
      $signer_name = $signer_data->prettyName();
      $project_name = $project_data[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      $role_name = $CS_ATTRIBUTE_TYPE_NAME[$role];
      $msg = "$signer_name Added $member_name to Project $project_name in role $role_name";
      if ($signer_name == $member_name) {
	$msg = "$signer_name Added self to Project $project_name in role $role_name";
      }
      $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
      $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
      $attributes = array_merge($pattributes, $mattributes);
      log_event($log_url, $mysigner, $msg, $attributes, $signer_id);
      if (parse_urn($message->signer_urn, $chname, $t, $n)) {
	$msg = $msg . " on CH $chname";
      }
      geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
      mail($portal_admin_email,
	   "New GENI CH project member added",
          $msg);
    }
  return $result;
}

// Remove a member from given project 
function remove_project_member($args, $message)
{
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];

  global $PA_PROJECT_MEMBER_TABLENAME;
  global $mysigner;

  $conn = db_conn();
  $sql = "DELETE FROM " . $PA_PROJECT_MEMBER_TABLENAME 
    . " WHERE " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID  
    . " = " . $conn->quote($project_id, 'text') . " AND "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . "= " . $conn->quote($member_id, 'text');
  error_log("PA.remove project_member.sql = " . $sql);
  $result = db_execute_statement($sql);

  // Delete previous assertions from CS
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $signer_id = $message->signerUuid();

    $membership_assertions = query_assertions($cs_url, $mysigner, 
					      $member_id, CS_CONTEXT_TYPE::PROJECT, $project_id);
    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      delete_assertion($cs_url, $mysigner, $assertion_id);
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }
    global $ma_url;
    $member_data = ma_lookup_member_by_id($ma_url, $mysigner, $member_id);
    $signer_data = ma_lookup_member_by_id($ma_url, $mysigner, $signer_id);
    $lookup_project_message = array(PA_ARGUMENT::PROJECT_ID => $project_id);
    $project_data = lookup_project($lookup_project_message);
    if (($project_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
	(array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, 
			  $project_data[RESPONSE_ARGUMENT::VALUE]))) 
      {
	$project_data = $project_data[RESPONSE_ARGUMENT::VALUE];
	$member_name = $member_data->prettyName();
	$signer_name = $member_data->prettyName();
	$project_name = $project_data[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
	$message = "$signer_name Removed $member_name from Project $project_name";
	$pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
	$mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
	$attributes = array_merge($pattributes, $mattributes);
	log_event($log_url, $mysigner, $message, $attributes, $signer_id);
      }

  }

  return $result;
}

// Change role of given member in given project
function change_member_role($args, $message)
{
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  $role = $args[PA_ARGUMENT::ROLE_TYPE];

  global $PA_PROJECT_MEMBER_TABLENAME;
  global $mysigner;

  $conn = db_conn();
  $sql = "UPDATE " . $PA_PROJECT_MEMBER_TABLENAME
    . " SET " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $conn->quote($role, 'integer')
    . " WHERE " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
    . " = " . $conn->quote($project_id, 'text')
    . " AND " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = " . $conn->quote($member_id, 'text'); 

  error_log("PA.change_member_role.sql = " . $sql);
  $result = db_execute_statement($sql);

  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $signer_id = $message->signerUuid();

    // Remove previous CS assertions about the member in this project
    $membership_assertions = query_assertions($cs_url, $mysigner, $member_id, CS_CONTEXT_TYPE::PROJECT, $project_id);
    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      delete_assertion($cs_url, $mysigner, $assertion_id);
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }

    // Create new assertion for member in this role
    create_assertion($cs_url, $mysigner, $signer_id, $member_id, $role, CS_CONTEXT_TYPE::PROJECT, $project_id);

    // FIXME
    $project_name = $project_id;
    $lookup_project_message = array(PA_ARGUMENT::PROJECT_ID => $project_id);
    $project_data = lookup_project($lookup_project_message);
    if (($project_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
	(array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME, 
			  $project_data[RESPONSE_ARGUMENT::VALUE]))) 
      {
	$project_data = $project_data[RESPONSE_ARGUMENT::VALUE];
	$project_name = $project_data[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
      }

    $signer_id = $message->signerUuid();
    global $ma_url;
    $member_data = ma_lookup_member_by_id($ma_url, $mysigner, $member_id);
    $signer_data = ma_lookup_member_by_id($ma_url, $mysigner, $signer_id);
    $member_name = $member_data->prettyName();
    $signer_name = $signer_data->prettyName();
    $role_name = $CS_ATTRIBUTE_TYPE_NAME[$role];
    $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
    $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
    $attributes = array_merge($pattributes, $mattributes);
    $msg = "$signer_name changed role of $member_name in project $project_name to $role_name";
    log_event($log_url, $mysigner, 
	      $msg, $attributes, $signer_id);

  }

  return $result;
}

// Return list of member ID's and roles associated with given project
// If role is provided, filter to members of given role
function get_project_members($args)
{
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $role = null;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args) && isset($args[PA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[PA_ARGUMENT::ROLE_TYPE];
  }

  global $PA_PROJECT_MEMBER_TABLENAME;

  $conn = db_conn();
  $role_clause = "";
  if ($role != null) {
    $role_clause = 
      " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $conn->quote($role, 'integer');
  }
  $sql = "SELECT " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE
    . " FROM " . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
    . " = " . $conn->quote($project_id, 'text')
    . $role_clause;

  //  error_log("PA.get_project_members.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
  
}

// Return list of project ID's for given member_id
// If is_member is true, return projects for which member is a member
// If is_member is false, return projects for which member is NOT a member
// If role is provided, filter on projects 
//    for which member has given role (is_member = true)
//    for which member does NOT have given role (is_member = false)
function get_projects_for_member($args)
{
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  $is_member = true;
  if (array_key_exists(PA_ARGUMENT::IS_MEMBER, $args) && isset($args[PA_ARGUMENT::IS_MEMBER])) {
    $is_member = $args[PA_ARGUMENT::IS_MEMBER];
  }
  $role = null;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args) && isset($args[PA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[PA_ARGUMENT::ROLE_TYPE];
  }

  global $PA_PROJECT_MEMBER_TABLENAME;

  // select distinct project_id from pa_project_member 
  // where member_id = $member_id

  // select distinct project_id from pa_project_member 
  // where member_id not in (select project_id from pa_project_member 
  //                         where member_id = $member_id)

  // select distinct project_id from pa_project_member 
  // where member_id = $member_id and role = $role

  // select distinct project_id from pa_project_member 
  // where member_id not in (select project_id from pa_project_member 
  //                         where member_id = $member_id and role = $role)

  $conn = db_conn();
  $role_clause = "";
  if ($role != null) {
    $role_clause = " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
      . " = " . $conn->quote($role, 'text');
  }

  if ($is_member) {
    $member_clause = 
      PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
      . " = " . $conn->quote($member_id, 'text') . " " . $role_clause;
  } else {
    $member_clause = 
    PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
      . " NOT IN (SELECT " 
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
      . " FROM " . $PA_PROJECT_MEMBER_TABLENAME 
      . " WHERE " 
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID
      . " = " . $conn->quote($member_id, 'text') . " " . $role_clause . ")";
  }

  $sql = "SELECT DISTINCT " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
    . " FROM " . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE " 
    . $member_clause;

  //  error_log("PA.get_projects_for_member.sql = " . $sql);
  $rows = db_fetch_rows($sql);
  $result = $rows;
  if ($rows[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $ids = array();
    foreach($rows[RESPONSE_ARGUMENT::VALUE] as $row) {
      $id = $row[PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID];
      $ids[] = $id;
    }
    $result = generate_response(RESPONSE_ERROR::NONE, $ids, '');
  }
  return $result;
}

// Include the RQ interface routines
$REQUEST_TABLENAME = $PA_PROJECT_MEMBER_REQUEST_TABLENAME;

// Define the specific function to call to determine what projects a given user can act on.
// (That is, requests about these projects can be handled by this user.)
function user_context_query($account_id)
{
  global $PA_PROJECT_MEMBER_TABLENAME;
  $conn = db_conn();
  return "select " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
    . " FROM " . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
    . " IN (" . $conn->quote(CS_ATTRIBUTE_TYPE::LEAD, 'integer') . ", " . $conn->quote(CS_ATTRIBUTE_TYPE::ADMIN, 'integer') . ")"
    . " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " . $conn->quote($account_id, 'text');
  
}
require_once('rq_controller.php');

// FIXME: Should not be hardcorded
$mycertfile = '/usr/share/geni-ch/pa/pa-cert.pem';
$mykeyfile = '/usr/share/geni-ch/pa/pa-key.pem';
$mysigner = new Signer($mycertfile, $mykeyfile);
$guard_factory = new PAGuardFactory($cs_url);
handle_message("PA", $cs_url, default_cacerts(),
	       $mysigner->certificate(), $mysigner->privateKey(), $guard_factory);

?>

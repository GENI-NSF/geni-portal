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
require_once('logging_client.php');

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
	    /*
	     *** This is the original code from SA CONTROLLER ***
            'get_slice_credential' => array('slice_guard'),
            'get_user_credential' => array(), // Unguarded
            'create_slice' => array('project_guard'),
            'lookup_slice_ids' => array('project_guard'),
            'lookup_slices' => array('lookup_slices_guard'),
            'lookup_slice' => array('slice_guard'),
            'lookup_slice_by_urn' => array(), // Unguarded
            'renew_slice' => array('slice_guard'),
            'add_slice_member' => array('slice_guard'),
            'remove_slice_member' => array('slice_guard'),
            'change_slice_member_role' => array('slice_guard'),
            'get_slice_members' => array('slice_guard'),
            'get_slices_for_member'=> array(), // Unguarded
	    */
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
	    'resolve_pending_request' => array('project_request_guard'), // Unguarded
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
                              $params[SA_ARGUMENT::PROJECT_ID]);
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
    $sql = "select count(*) from $PA_PROJECT_MEMBER_TABLENAME, $REQUEST_TABLENAME " 
      . " WHERE "
      . " $REQUEST_TABLENAME." . RQ_REQUEST_TABLE_FIELDNAME::ID . " = $request_id"
      . " AND " 
      . " $REQUEST_TABLENAME." . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = "
      . " $PA_PROJECT_MEMBER_TABLENAME." . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
      . " AND " 
      . " $PA_PROJECT_MEMBER_TABLENAME." . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '$signer'"
      . " AND "
      . " $PA_PROJECT_MEMBER_TABLENAME." . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
      . " IN (" . CS_ATTRIBUTE_TYPE::LEAD . ", " . CS_ATTRIBUTE_TYPE::ADMIN . ")";
    error_log("PAProjectRequestGuard.sql = $sql");
    //    $result = db_fetch_row($sql);
    error_log("Result = " . print_r($result, true));
    $allowed = FALSE;
    if($result['code'] == RESPONSE_ERROR::NONE) {
      $result = $result['value']['count'];
      $allowed = $result > 0;
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
    pa_debug("MessageHandler requesting authorization:"
             . " for principal=\""
             . print_r($this->message->signerUuid(), TRUE) . "\""
             . "; action=\"" . print_r($this->action, TRUE) . "\""
             . "; context_type=\"" . print_r($this->context_type, TRUE) . "\""
             . "; context=\"" . print_r($this->context, TRUE) . "\"");
    return request_authorization($this->cs_url, $this->message->signerUuid(),
                                 $this->action, $this->context_type,
                                 $this->context);
  }
}


/*----------------------------------------------------------------------
 * API Methods
 *----------------------------------------------------------------------
 */

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

  $exists_sql = "select count(*) from " . $PA_PROJECT_TABLENAME 
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . " = '" . $project_name . "'";
  $exists_response = db_fetch_row($exists_sql);
  $exists = $exists_response[RESPONSE_ARGUMENT::VALUE];
  $exists = $exists['count'];
  if ($exists > 0) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, 
			     "Project of name " . $project_name . " already exists.");
  }


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

  // Now add the lead as a member of the project
  $addres = add_project_member(array(PA_ARGUMENT::PROJECT_ID => $project_id, PA_ARGUMENT::MEMBER_ID => $lead_id, PA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD));
  if (! isset($addres) || is_null($addres) || ! array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) || $addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    error_log("create_project failed to add lead as a project member: " . $addres[RESPONSE_ARGUMENT::CODE] . ": " . $addres[RESPONSE_ARGUMENT::OUTPUT]);
    // FIXME: ROLLBACK?
    return $addres;
  }

  // Log the creation
  global $log_url;
  $context[LOGGING_ARGUMENT::CONTEXT_TYPE] = CS_CONTEXT_TYPE::PROJECT;
  $context[LOGGING_ARGUMENT::CONTEXT_ID] = $project_id;
  log_event($log_url, "Created project: " . $project_name, array($context), $lead_id);

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

// Return list of all projects and data. 
// Optionally, filtered by lead_id if provided
function lookup_projects($args)
{
  global $PA_PROJECT_TABLENAME;

  $lead_id = null;
  $lead_clause = "";
  //  error_log("LP.args = " . print_r($args, true));
  if(array_key_exists(PA_ARGUMENT::LEAD_ID, $args)) {
    $lead_id = $args[PA_ARGUMENT::LEAD_ID];
    $lead_clause = " WHERE " . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . " = '" . $lead_id . "'";
  }

  $sql = "select "  
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE 
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

  if (isset($project_id) && ! is_null($project_id) && $project_id != '') {
    $where = " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID 
      . " = '" . $project_id . "'";
  } else {
    $where = " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME 
      . " = '" . $project_name . "'";
  }

  $sql = "select "  
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE 
    . " FROM " . $PA_PROJECT_TABLENAME
    . $where;

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

// Add a member of given role to given project
function add_project_member($args)
{
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  $role = $args[PA_ARGUMENT::ROLE_TYPE];

  global $PA_PROJECT_MEMBER_TABLENAME;

  $already_member_sql = "select count(*) from " . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID . " = '$project_id'"
    . " AND " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '$member_id'";
  $already_member = db_fetch_row($already_member_sql);
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  $already_member = $already_member['value']['count'] > 0;
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if ($already_member) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Member $member_id is already a member of project $project_id");
  }


  $sql = "INSERT INTO " . $PA_PROJECT_MEMBER_TABLENAME . " ("
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . ") VALUES ("
    . "'" . $project_id . "', "
    . "'" . $member_id . "', "
    . $role . ")";
  error_log("PA.add project_member.sql = " . $sql);
  $result = db_execute_statement($sql);
  return $result;
}

// Remove a member from given project 
function remove_project_member($args)
{
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];

  global $PA_PROJECT_MEMBER_TABLENAME;

  $sql = "DELETE FROM " . $PA_PROJECT_MEMBER_TABLENAME 
    . " WHERE " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID  
    . " = '" . $project_id . "'"  . " AND "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . "= '" . $member_id . "'";
  error_log("PA.remove project_member.sql = " . $sql);
  $result = db_execute_statement($sql);
  return $result;
}

// Change role of given member in given project
function change_member_role($args)
{
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  $role = $args[PA_ARGUMENT::ROLE_TYPE];

  global $PA_PROJECT_MEMBER_TABLENAME;

  $sql = "UPDATE " . $PA_PROJECT_MEMBER_TABLENAME
    . " SET " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $role
    . " WHERE " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
    . " = '" . $project_id . "'" 
    . " AND " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = '" . $member_id . "'"; 

  error_log("PA.change_member_role.sql = " . $sql);
  $result = db_execute_statement($sql);
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

  $role_clause = "";
  if ($role != null) {
    $role_clause = 
      " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $role;
  }
  $sql = "SELECT " 
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE
    . " FROM " . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
    . " = '" . $project_id . "'" 
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
  $is_member = $args[PA_ARGUMENT::IS_MEMBER];
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

  $role_clause = "";
  if ($role != null) {
    $role_clause = " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
      . " = " . $role;
  }

  if ($is_member) {
    $member_clause = 
      PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
      . " = '" . $member_id . "' " . $role_clause;
  } else {
    $member_clause = 
    PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
      . " NOT IN (SELECT " 
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
      . " FROM " . $PA_PROJECT_MEMBER_TABLENAME 
      . " WHERE " 
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID
      . " = '" . $member_id . "' " . $role_clause . ")";
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

// Define the specific function to call to determine what slices are relevant to a given user
// (That is, requests about that slice are for this user)
function user_context_query($account_id)
{
  global $PA_PROJECT_MEMBER_TABLENAME;
  return "select " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID 
    . " FROM " . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
    . " IN (" . CS_ATTRIBUTE_TYPE::LEAD . ", " . CS_ATTRIBUTE_TYPE::ADMIN . ")"
    . " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '" . $account_id . "'";
  
}
require_once('rq_controller.php');

handle_message("PA", $cs_url, NULL, NULL, NULL, new PAGuardFactory($cs_url));

?>

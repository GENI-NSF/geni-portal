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
require_once("sa_client.php");
require_once("sa_constants.php");
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
 *   [project_name, lead_id, project_email, project_purpose] <= lookup_project(project_id);
 *   update_project(pa_url, project_id, project_email, project_purpose, expiration);
 *   get_project_members(pa_url, project_id, role=null) // null => Any
 *   get_projects_for_member(pa_url, member_id, is_member, role=null)
 *   lookup_project_details(pa_url, project_uuids)
 *   invite_member(pa_url, project_id, role, expiration)
 *   accept_invitation(pa_url, invite_id)
 *   modify_project_membership(pa_url, members_to_add, 
 *        members_to_change_role, members_to_remove)
 *
 * *** DEPRECATED ***
 *   change_lead(pa_url, project_id, previous_lead_id, new_lead_id); *
 *   add_project_member(pa_url, project_id, member_id, role)
 *   remove_project_member(pa_url, project_id, member_id)
 *   change_member_role(pa_url, project_id, member_id, role)
 **/

$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);

function pa_debug($msg)
{
  //  error_log('PA DEBUG: ' . $msg);
}

/*----------------------------------------------------------------------
 * Expiration
 *----------------------------------------------------------------------
 */
/**
 * A poor man's expiration. Call this at the start of an API method
 * to expire projects in advance of the call. Eventually we will need
 * a daemon for this.
 *
 * N.B. This is not sufficient for warning emails that a project is
 * going to expire soon. For that, a daemon is necessary.
 */
function pa_expire_projects()
{
  /*
   * Select project ids that should expire.
   * For each id:
   *   Update the DB
   *   Log expire to Logger
   *   Log expiration to geni_syslog
   *
   * Also unexpire those projects that were set to expired 
   * but whose expiration dates have been extended
   */
  pa_expire_projects_internal(True);
  pa_expire_projects_internal(False);
}

// Either expire recently expired ($expire_projects = true)
// or unexpire recently extended expired projects ($expire_projects = false)
function pa_expire_projects_internal($expire_projects)
{

  global $log_url;
  global $mysigner;
  global $PA_PROJECT_TABLENAME;
  $conn = db_conn();
  $now_utc = new DateTime(null, new DateTimeZone('UTC'));

  $time_sense = "<";
  $expired_sense = "NOT";
  $expired_value = "TRUE";
  $expired_label = "Expired";
  if (!$expire_projects) {
    $time_sense = ">";
    $expired_sense = "";
    $expired_value = "FALSE";
    $expired_label = "Unexpired";
  }

  $sql = "SELECT "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID
    . " FROM " . $PA_PROJECT_TABLENAME
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION
    . " " . $time_sense . " " . $conn->quote(db_date_format($now_utc), 'timestamp')
    . " AND " . $expired_sense . " " . PA_PROJECT_TABLE_FIELDNAME::EXPIRED;
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] !== RESPONSE_ERROR::NONE) {
    $msg = "pa_expire_projects error: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
    return;
  }
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  foreach ($rows as $row) {
    $project_id = $row[PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID];
    $project_name = $row[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $lead_id = $row[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    $sql = "UPDATE $PA_PROJECT_TABLENAME"
      . " SET " . PA_PROJECT_TABLE_FIELDNAME::EXPIRED . " = " . $expired_value
      . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . " = "
      . $conn->quote($project_id, 'text');
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] !== RESPONSE_ERROR::NONE) {
      $msg = "Failed to expire project $project_id; "
        . $result[RESPONSE_ARGUMENT::OUTPUT];
      geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
      continue;
    }
    $project_attribute = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT,
            $project_id);
    $attributes = $project_attribute;
    $log_msg = $expired_label . " project " . $project_name;
    log_event($log_url, $mysigner, $log_msg, $attributes, $lead_id);
    $syslog_msg = $expired_label . " project $project_id";
    geni_syslog(GENI_SYSLOG_PREFIX::PA, $syslog_msg);
  }
}

/*----------------------------------------------------------------------
 * Authorization
*----------------------------------------------------------------------
*/
class PAGuardFactory implements GuardFactory
{
  // FIXME: Guard the rest of the methods
  // get_projects: Just a list of project_ids. We have entries in cs_action for this...
  //    Maybe any valid member_id?
  // lookup_project: Have entry in cs_action. Non project members call this to see the project name, etc.
  //    Maybe any valid member_id?
  // lookup_projects: Unused? Remove? Require any valid member_id?

  private static $context_table
    = array(
            // Action => array(method_name, method_name, ...)
	    'create_project' => array(), // Unguarded here, method calls request_authorization. FIXME!!
	    'get_projects' => array(), // Unguarded
	    'lookup_projects' => array(), // Unguarded
	    'lookup_project' => array(), // Unguarded
	    'update_project' => array('project_guard'), 
	    'modify_project_membership' => array('project_guard'), 
	    'change_lead' => array('FalseGuard'),
	    'add_project_member' => array('FalseGuard'),
	    'remove_project_member' => array('FalseGuard'),
	    'remove_project_member_from_slices' => array('FalseGuard'),
	    'change_member_role' => array('FalseGuard'),
	    'get_project_members' => array(), // Unguarded
	    'get_projects_for_member' => array(), // Unguarded
	    'lookup_project_details' => array(), // Unguarded
	    'create_request' => array(), // Unguarded
	    'resolve_pending_request' => array('project_request_guard'),
	    'get_requests_for_context' => array(), // Unguarded
	    'get_requests_by_user' => array(), // Unguarded
	    'get_pending_requests_for_user' => array(), // Unguarded
	    'get_number_of_pending_requests_for_user' => array(), // Unguarded
	    'get_request_by_id' => array(), // Unguarded
	    "invite_member" => array("project_guard"),
	    "accept_invitation" => array() // unguarded
            );

  public function __construct($cs_url) {
    $this->cs_url = $cs_url;
  }

  public function project_request_guard($message, $action, $params)
  {
    //    error_log("PA.project_request_guard " . print_r($message, true) . " " .
    //      print_r($action, true) . " " . print_r($params, true));
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
      // FIXME: Deny access at all?
      error_log("PA: No guard producers for action \"$action\"");
      geni_syslog(GENI_SYSLOG_PREFIX::PA, "Method " . $action . " unguarded!");
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

    if (! $allowed) {
      $msg = "PA denying $signer call to " . $this->action;
      error_log($msg);
      geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
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
    $result = request_authorization($this->cs_url, $mysigner,
				    $this->message->signerUuid(),
				    $this->action, $this->context_type,
				    $this->context);
    //    error_log("PAContextGuard.evaluate.result = " . print_r($result, true));
    $result = $result[RESPONSE_ARGUMENT::VALUE];
    $result_type = gettype($result);
    geni_syslog(GENI_SYSLOG_PREFIX::PA, "PAContextGuard got result of type $result_type");
    geni_syslog(GENI_SYSLOG_PREFIX::PA,
		"PAContextGuard for " . $this->message->signerUuid()
		. " on action " . $this->action
		. " returning " . print_r($result, true));
    return $result;
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
  
  pa_expire_projects();

  //  error_log("ARGS = " . print_r($args, true));

  $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  if (! isset($project_name) or is_null($project_name) or $project_name == '') {
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project name is missing");
  }
  if (strpos($project_name, ' ') !== false) {
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project name '$project_name' is invalid: no spaces allowed.");
  }

  if (!is_valid_project_name($project_name)) {
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project name '$project_name' is invalid: Use at most 32 alphanumerics plus hyphen and underscore; no leading hyphen or underscore.");
  }

  if (! array_key_exists(PA_ARGUMENT::LEAD_ID, $args) or
      $args[PA_ARGUMENT::LEAD_ID] == '') {
    // missing arg
    error_log("Missing lead_id arg to create_project");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Lead ID is missing");
  }
  $lead_id = $args[PA_ARGUMENT::LEAD_ID];
  if (! uuid_is_valid($lead_id)) {
    error_log("lead_id invalid in create_project: " . $lead_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Lead ID is invalid: " . $lead_id);
  }
  $project_purpose = $args[PA_ARGUMENT::PROJECT_PURPOSE];
  if (array_key_exists(PA_ARGUMENT::EXPIRATION, $args)) {
    $expiration = $args[PA_ARGUMENT::EXPIRATION];
  } else {
    $expiration = NULL;
  }

  $conn = db_conn();
  $exists_sql = "select count(*) from " . $PA_PROJECT_TABLENAME
    . " WHERE lower(" . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ") = lower(" . $conn->quote($project_name, 'text') . ")";
  $exists_response = db_fetch_row($exists_sql);
  $exists = $exists_response[RESPONSE_ARGUMENT::VALUE];
  $exists = $exists['count'];
  if ($exists > 0) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null,
            "A project named '" . $project_name . "' already exists.");
  }

  // Ensure that designated lead ID is allowed to be a project lead
  $permitted = request_authorization($cs_url, $mysigner, $lead_id, PA_ACTION::CREATE_PROJECT,
          CS_CONTEXT_TYPE::RESOURCE, null);
  if ($permitted[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $permitted;
  $permitted = $permitted[RESPONSE_ARGUMENT::VALUE];

  //  error_log("PERMITTED = " . $permitted);
  if (! $permitted) {
    $msg = "Principal " . $lead_id . " may not be the lead on a project";
    geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted, $msg);
  }

  // Ensure that caller is allowed to be a project lead, which is prereq for calling this method
  // FIXME: Put this in a guard!
  $permitted = request_authorization($cs_url, $mysigner, $message->signerUuid(), PA_ACTION::CREATE_PROJECT,
          CS_CONTEXT_TYPE::RESOURCE, null);
  if ($permitted[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $permitted;
  $permitted = $permitted[RESPONSE_ARGUMENT::VALUE];

  //  error_log("PERMITTED = " . $permitted);
  if (! $permitted) {
    $msg = "Principal " . $message->signerUuid() . " may not call create_project";
    geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted, $msg);
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

  $project_id = make_uuid();

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

  $ids = array();
  $ids[] = $lead_id;

  $names = lookup_member_names($ma_url, $mysigner, $ids);
  if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $names;
  $names = $names[RESPONSE_ARGUMENT::VALUE];

  $lead_name = $names[$lead_id];

  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  $msg = "Created project: $project_name with lead $lead_name";
  if ($lead_id != $signer_id) {
    $msg = $signer_urn . " " . $msg;
  }
  log_event($log_url, $mysigner, $msg, $attributes, $lead_id);
  geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
  error_log($msg);
  if (parse_urn($message->signer_urn, $chname, $t, $n)) {
    $msg = $msg . " on CH $chname";
  }
  mail($portal_admin_email, "New GENI CH project created", $msg);

  return generate_response(RESPONSE_ERROR::NONE, $project_id, '');
}

/* Return list of all project ID's, optionally limited by lead_id */
function get_projects($args)
{
  global $PA_PROJECT_TABLENAME;

  pa_expire_projects();

  $conn = db_conn();
  $sql = "select "
          . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID
          . " FROM " . $PA_PROJECT_TABLENAME;
  if (array_key_exists(PA_ARGUMENT::LEAD_ID, $args)) {
    $lead_id = $args[PA_ARGUMENT::LEAD_ID];
    if (! uuid_is_valid($lead_id)) {
      error_log("lead_id invalid in get_projects: " . $lead_id);
    } else {
      $sql = $sql . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID .
	" = " . $conn->quote($args[PA_ARGUMENT::LEAD_ID], 'text');
    }
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

  pa_expire_projects();

  $lead_id = null;
  $lead_clause = "";
  $conn = db_conn();
  //  error_log("LP.args = " . print_r($args, true));
  if(array_key_exists(PA_ARGUMENT::LEAD_ID, $args)) {
    $lead_id = $args[PA_ARGUMENT::LEAD_ID];
    if (! uuid_is_valid($lead_id)) {
      error_log("lead_id invalid in lookup_projects: " . $lead_id);
    } else {
      $lead_clause = " WHERE " . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID
	. " = " . $conn->quote($lead_id, 'text');
    }
  }

  $sql = "select "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", "
    . PA_PROJECT_TABLE_FIELDNAME::CREATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRED
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

  pa_expire_projects();

  if (array_key_exists(PA_ARGUMENT::PROJECT_ID, $args)) {
    $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  }
  if (array_key_exists(PA_ARGUMENT::PROJECT_NAME, $args)) {
    $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  }
  if ((! isset($project_id) || is_null($project_id) || $project_id == '' || !uuid_is_valid($project_id))  
      && (! isset($project_name) || is_null($project_name) || $project_name == '')) {
    error_log("Missing project ID and project name to lookup_project");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project ID and project name missing or invalid");
  }

  $conn = db_conn();
  if (isset($project_id) && ! is_null($project_id) && $project_id !=
      '' && uuid_is_valid($project_id)) {
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
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRED
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

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    // missing arg
    error_log("Missing project_id arg to update_project");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is missing");
  }
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in update_project: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is invalid: " . $project_id);
  }
  $project_purpose = $args[PA_ARGUMENT::PROJECT_PURPOSE];

  if (! array_key_exists(PA_ARGUMENT::EXPIRATION, $args)) {
    // missing arg
    error_log("Missing expiration arg to update_project");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Expiration is missing");
  }
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
  $ids = array();
  $ids[] = $signer_id;

  $names = lookup_member_names($ma_url, $mysigner, $ids);
  if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $names;
  $names = $names[RESPONSE_ARGUMENT::VALUE];

  $signer_name = $names[$signer_id];

  // Need to look up the project name
  $lookup_project_message = array(PA_ARGUMENT::PROJECT_ID => $project_id);
  $project_data = lookup_project($lookup_project_message);
  if (($project_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
          (array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME,
                  $project_data[RESPONSE_ARGUMENT::VALUE])))
  {
    $project_data = $project_data[RESPONSE_ARGUMENT::VALUE];
    $project_name = $project_data[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  } else {
    $project_name = $project_id;
  }

  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  $msg = "$signer_name Updated project $project_name with purpose: $project_purpose";
  log_event($log_url, $mysigner, $msg, $attributes, $signer_id);
  geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);

  return $result;
}

// Modify project membership according to given lists of add/change_role/remove
// $members_to_add and $members_to_change are both
//    dicitoaries of (member_id => role, ...)
// $members_to_delete is a list of mebmer_ids
//
// The semantics are as follows:
//    Give an bad argument (ARGS) error if:
//       any member to add is already a member
//       any member_to change role is not a member
//       any member_to_remove is not a member
//    Give a bad argument (ARGS) error if:
//       the change would cause there to be more or less than 1 lead
//
// Additional semantics:
//    If any removed project member was lead of a slice of this project
//    make the project lead into the lead of that slice
//    
function modify_project_membership($args, $message)
{
  global $cs_url;
  global $mysigner;

  // Unpack arguments
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $members_to_add = $args[PA_ARGUMENT::MEMBERS_TO_ADD];
  $members_to_change_role = $args[PA_ARGUMENT::MEMBERS_TO_CHANGE_ROLE];
  $members_to_remove = $args[PA_ARGUMENT::MEMBERS_TO_REMOVE];

  //  error_log("MTA = " . print_r($members_to_add, true));
  //  error_log("MTC = " . print_r($members_to_change_role, true));
  //  error_log("MTR = " . print_r($members_to_remove, true));


  // Get the members of the project by role
  $project_members = get_project_members(array(PA_ARGUMENT::PROJECT_ID => $project_id));
  if ($project_members[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $project_members;
  }
  $project_members = $project_members[RESPONSE_ARGUMENT::VALUE];

  // Determine project lead
  $project_lead = null;
  foreach($project_members as $project_member) {
    $member_id = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    $role = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
    if ($role == CS_ATTRIBUTE_TYPE::LEAD) {
      $project_lead = $member_id;
      break;
    }
  }

  // Must be a project lead, else something is wrong with project
  if($project_lead == null) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Bad project: no lead");
  }

  // First validate the arguments
  // No new members should be already a project member
  if (!already_in_list(array_keys($members_to_add), array_keys($project_members), true)) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Can't add member to a project that already belongs");
  }

  // No new roles for members who aren't project members
  if (!already_in_list(array_keys($members_to_change_role), array_keys($project_members), false)) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Can't change member role for member not in project");
  }

  // Can't remove members who aren't project members
  if (!already_in_list(array_keys($members_to_remove), array_keys($project_members), false)) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Can't remove member from project if not a member");
  }

  $new_project_lead = $project_lead;
  // Count up the total lead changes. Should be zero
  $lead_changes = 0;
  foreach($members_to_add as $member_to_add => $new_role) {
    if($new_role == CS_ATTRIBUTE_TYPE::LEAD) {
      $lead_changes = $lead_changes + 1;
      $new_project_lead = $member_to_add;
    }
  }
  foreach($members_to_change_role as $member_to_change_role => $role) {
    if ($role == CS_ATTRIBUTE_TYPE::LEAD && $member_to_change_role != $project_lead) {
      $lead_changes = $lead_changes + 1;
      $new_project_lead = $member_to_change_role;
    }
    if ($member_to_change_role == $project_lead && $role != CS_ATTRIBUTE_TYPE::LEAD)
      $lead_changes = $lead_changes - 1;
  }

  foreach($members_to_remove as $member_to_remove) {
    if($member_to_remove == $project_lead)
      $lead_changes = $lead_changes - 1;
  }

  if($lead_changes != 0) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Must have exactly one project lead");
  }

  // Can't make someone a project lead if they don't have 
  // create_project capabilities
  //  error_log("NPL = " . $new_project_lead . " PL = " . $project_lead);
  if ($new_project_lead != $project_lead) {
    $permitted = request_authorization($cs_url, $mysigner, $new_project_lead,
				       PA_ACTION::CREATE_PROJECT,
				       CS_CONTEXT_TYPE::RESOURCE, null);
    if($permitted[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $permitted;
    $permitted = $permitted[RESPONSE_ARGUMENT::VALUE];
    if (!$permitted)
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Proposed project lead does not have " . 
			       "sufficient privileges for that role.");
  }
  




  // There is a problem of transactional integrity here
  // The PA keeps its own table of members/roles, and the CS keeps a table of assertions which
  // are essentially redundant. Ultimately, these should be unified and probably kept in the CS
  // and the CS should allow for a bundling (writing multiple adds/deletes at once)
  // 
  // But for now we maintain the two sets of tables, writing the sa_project_member_table atomically
  // while writing to the CS on each transaction.

  // Grab the database connection and start a transaction
  $conn = db_conn();
  $conn->beginTransaction();

  // Set these if there's an error along the way
  $success = True;
  $error_message = "";

  // Add new members
  if ($success) {
    foreach($members_to_add as $member_to_add => $role) {
      $result = add_project_member(array(PA_ARGUMENT::PROJECT_ID => $project_id, 
				       PA_ARGUMENT::MEMBER_ID => $member_to_add,
				       PA_ARGUMENT::ROLE_TYPE => $role), 
				 $message);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	$success = False;
	$error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
      }
    }
  }

  // Change roles of existing members
  if($success) {
    foreach($members_to_change_role as $member_to_change_role => $role) {
      $result = change_member_role(array(PA_ARGUMENT::PROJECT_ID => $project_id,
					 PA_ARGUMENt::MEMBER_ID => $member_to_change_role,
					 PA_ARGUMENT::ROLE_TYPE => $role), 
				    $message);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	$success = False;
	$error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
      }
    }
  }

  // Remove members
  if ($success) {
    foreach($members_to_remove as $member_to_remove) {
      $result = remove_project_member(array(PA_ARGUMENT::PROJECT_ID => $project_id,
					  PA_ARGUMEnt::MEMBER_ID => $member_to_remove), 
				    $message);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	$success = False;
	$error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
      }
    }
  }

  // If all the PA and CS transactions are successful, then commit, otherwise rollback
  if(!$success) {
    // One of the writes failed. Rollback the whole thing
    $conn->rollback();
    return generate_response(RESPONSE_ERROR::DATABASE, null, $error_message);
  } else {
    // All succeeded, commit PA changes and return success
    $conn->commit();
    return generate_response(RESPONSE_ERROR::NONE, null, '');
  }
}



/* update lead of given project */
function change_lead($args, $message)
{
  global $PA_PROJECT_TABLENAME;

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    // missing arg
    error_log("Missing project_id arg to change_lead");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is missing");
  }
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in change_lead: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is invalid: " . $project_id);
  }

  if (! array_key_exists(PA_ARGUMENT::PREVIOUS_LEAD_ID, $args) or
      $args[PA_ARGUMENT::PREVIOUS_LEAD_ID] == '') {
    // missing arg
    error_log("Missing previous_lead_id arg to change_lead");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Previous Lead ID is missing");
  }
  $previous_lead_id = $args[PA_ARGUMENT::PREVIOUS_LEAD_ID];
  if (! uuid_is_valid($previous_lead_id)) {
    error_log("previous_lead_id invalid in change_lead: " . $previous_lead_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Previous Lead ID is invalid: " . $previous_lead_id);
  }

  if (! array_key_exists(PA_ARGUMENT::LEAD_ID, $args) or
      $args[PA_ARGUMENT::LEAD_ID] == '') {
    // missing arg
    error_log("Missing lead_id arg to change_lead");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "New Lead ID is missing");
  }
  $new_lead_id = $args[PA_ARGUMENT::LEAD_ID];
  if (! uuid_is_valid($new_lead_id)) {
    error_log("new_lead_id invalid in change_lead: " . $new_lead_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "New Lead ID is invalid: " . $new_lead_id);
  }

  // Check that new person is allowed to be a lead
  global $cs_url;
  global $mysigner;
  $permitted = request_authorization($cs_url, $mysigner, $new_lead_id, PA_ACTION::CREATE_PROJECT,
          CS_CONTEXT_TYPE::RESOURCE, null);
  if($permitted[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $permitted;
  $permitted = $permitted[RESPONSE_ARGUMENT::VALUE];

  //  error_log("PERMITTED = " . $permitted);
  if (! $permitted) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted,
            "Principal " . $new_lead_id  . " may not lead projects");
  }

  // FIXME: If caller is an Admin on the project, is this allowed? Or
  // should the AuthZ service have caught that already?

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
          PA_ARGUMENT::MEMBER_ID => $previous_lead_id,
          PA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::ADMIN),
          $message);
  if ($chngres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $chngres;

  global $log_url;
  global $mysigner;
  global $ma_url;
  global $portal_admin_email;
  $signer_id = $message->signerUuid();
  $ids = array();
  $ids[] = $signer_id;
  $ids[] = $new_lead_id;
  $ids[] = $previous_lead_id;

  $names = lookup_member_names($ma_url, $mysigner, $ids);
  if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $names;
  $names = $names[RESPONSE_ARGUMENT::VALUE];

  $signer_name = $names[$signer_id];
  $new_lead_name = $names[$new_lead_id];
  $old_lead_name = $names[$previous_lead_id];

  $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  // FIXME: We'd like to add as a context the old lead, but only 1 member context allowed
  // But maybe this is OK, cause the actor here is the old lead?
  $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $new_lead_id);
  $attributes = array_merge($pattributes, $mattributes);

  // Need to look up the project name
  $lookup_project_message = array(PA_ARGUMENT::PROJECT_ID => $project_id);
  $project_data = lookup_project($lookup_project_message);
  if (($project_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
          (array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME,
                  $project_data[RESPONSE_ARGUMENT::VALUE])))
  {
    $project_data = $project_data[RESPONSE_ARGUMENT::VALUE];
    $project_name = $project_data[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  } else {
    $project_name = $project_id;
  }

  $msg = "$signer_name changed project lead for $project_name to $new_lead_name (was $old_lead_name)";
  log_event($log_url, $mysigner, $msg, $attributes, $signer_id);
  geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
  if (parse_urn($message->signer_urn, $chname, $t, $n)) {
    $msg = $msg . " on CH $chname";
  }
  mail($portal_admin_email, "Changed GENI CH project lead", $msg);

  return $result;
}

// Add a member of given role to given project
// Return code/value/output triple
function add_project_member($args, $message)
{

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    // missing arg
    error_log("Missing project_id arg to add_project_member");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is missing");
  }
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in add_project_member: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is invalid: " . $project_id);
  }

  if (! array_key_exists(PA_ARGUMENT::MEMBER_ID, $args) or
      $args[PA_ARGUMENT::MEMBER_ID] == '') {
    // missing arg
    error_log("Missing member_id arg to add_project_member");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is missing");
  }
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  if (! uuid_is_valid($member_id)) {
    error_log("member_id invalid in add_project_member: " . $member_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is invalid: " . $member_id);
  }

  $role = null;
  global $CS_ATTRIBUTE_TYPE_NAME;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args)) {
    $role = $args[PA_ARGUMENT::ROLE_TYPE];
    if (! array_key_exists($role, $CS_ATTRIBUTE_TYPE_NAME)) {
      error_log("role invalid in add_project_member: " . $role);
      return generate_response(RESPONSE_ERROR::ARGS, null,
            "Role is invalid: " . $role);
    }
  } else {
    error_log("Missing role arg to add_project_member");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Role is missing");
  }

  global $PA_PROJECT_MEMBER_TABLENAME;
  global $mysigner;

  $conn = db_conn();

  // Check that this is a valid project ID
  global $PA_PROJECT_TABLENAME;
  $valid_project_sql = "select count(*) from " . $PA_PROJECT_TABLENAME
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . " = "
    . $conn->quote($project_id, 'text');
  $valid_project = db_fetch_row($valid_project_sql);
  if (! array_key_exists('value', $valid_project) or
      is_null($valid_project['value']) or ! array_key_exists('count', $valid_project['value'])) {
    return $valid_project;
  }
  $valid_project = $valid_project['value']['count'] > 0;
  if (!$valid_project) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Project $project_id unknown.");
  }

  // Check if the member is already in the project
  $already_member_sql = "select count(*) from " . $PA_PROJECT_MEMBER_TABLENAME
  . " WHERE "
          . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID . " = " . $conn->quote($project_id, 'text')
          . " AND "
                  . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " . $conn->quote($member_id, 'text');
  $already_member = db_fetch_row($already_member_sql);
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if (! array_key_exists('value', $already_member) or
      is_null($already_member['value']) or ! array_key_exists('count', $already_member['value'])) {
    return $already_member;
  }
  $already_member = $already_member['value']['count'] > 0;
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if ($already_member) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Member $member_id is already a member of project $project_id");
  }


  // Add the member to the project
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
    $ca_result = create_assertion($cs_url, $mysigner, $signer_id, $member_id, $role, 
				  CS_CONTEXT_TYPE::PROJECT, $project_id);
    if($ca_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $ca_result;
  }

  // Log adding the member
  global $ma_url;
  $ids = array();
  $ids[] = $signer_id;
  $ids[] = $member_id;

  $names = lookup_member_names($ma_url, $mysigner, $ids);
  if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $names;
  $names = $names[RESPONSE_ARGUMENT::VALUE];

  $signer_name = $names[$signer_id];
  $member_name = $names[$member_id];

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
    //  error_log("PD = " . print_r($project_data, true));
    $project_data = $project_data[RESPONSE_ARGUMENT::VALUE];
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

// Remove member from slices to which he belongs in given project
// If member is lead of such a slice, replace with project lead
function remove_project_member_from_slices($member_id, $project_id)
{
  global $sa_url;
  global $mysigner;

  // Get slices for member for project
  $slices = lookup_slices($sa_url, $mysigner, $project_id, $member_id);
  if ($slices[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) 
    return $slices;
  $slices = $slices[RESPONSE_ARGUMENT::VALUE];

  // Get roles of member within slices
  $member_slice_roles = get_slices_for_member($sa_url, $mysigner, $member_id, true);
  if ($member_slice_roles[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) 
    return $member_slice_roles;
  $member_slice_roles = $member_slice_roles[RESPONSE_ARGUMENT::VALUE];

  //  error_log("SLICES = " . print_r($slices, true));
  //  error_log("MEMBER_SLICE_ROLES = " . print_r($member_slice_roles, true));

  $slices_to_replace_lead = array();

  // For each project slice, see what role member has.
  foreach($slices as $slice) {
    $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    $project_contains_slice = false;
    foreach($member_slice_roles as $member_slice_role) {
      $member_slice_id = $member_slice_role[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID];
      $role = $member_slice_role[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
      if($slice_id == $member_slice_id) {
	error_log("Need to remove " . $member_id . " from slice " . $slice_id . " role " . $role);
	$rsm_result = remove_slice_member($sa_url, $mysigner, $slice_id, $member_id);
	if ($rsm_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	  return $rsm_result;
	if ($role == CS_ATTRIBUTE_TYPE::LEAD) {
	  error_log("Need to replace lead of slice " . $slice_id);
	  $slices_to_replace_lead[] = $slice_id;
	}
      }
    }
  }

  // If we need to replace some leads, get the project lead
  // and the list of slices to which lead belongs. If doesn't belong, add as lead
  // Otherwise, change role to lead
  if (count($slices_to_replace_lead) > 0) {
    // Get the ID of the project lead
    $project_members_args[PA_ARGUMENT::PROJECT_ID] = $project_id;

    $project_members = get_project_members($project_members_args);
    if ($project_members[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $project_members;
    $project_members = $project_members[RESPONSE_ARGUMENT::VALUE];

    $project_lead_id = null;
    foreach($project_members as $project_member) {
      $role = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
      $member_id = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
      if($role == CS_ATTRIBUTE_TYPE::LEAD) {
	$project_lead_id = $member_id;
	break;
      }
    }

    // Get the ID's of slices in this project for which project lead is member
    $project_lead_slices = lookup_slices($sa_url, $mysigner, $project_id, $project_lead_id);
    if($project_lead_slices[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $proejct_lead_slices;
    $project_lead_slices = $project_lead_slices[RESPONSE_ARGUMENT::VALUE];

    $slices_for_project_lead = array();
    foreach($project_lead_slices as $project_lead_slice) {
      $slice_id = $project_lead_slice[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID];
      $slices_for_project_lead[] = $slice_id;
    }

    foreach($slices_to_replace_lead as $slice_id) {
      $lead_in_slice = in_array($slice_id, $slices_for_project_lead);
      if($lead_in_slice) {
	// If project lead is in slice, change to lead in slice
	$csmr_result = change_slice_member_role($sa_url, $mysigner, $slice_id, $project_lead_id, 
						CS_ATTRIBUTE_TYPE::LEAD);
	if ($csmr_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	  return $csmr_result;
	
      } else {
	// Otherwise add member as lead to slice
	$asm_result = add_slice_member($sa_url, $mysigner, $slice_id, $project_lead_id, 
				       CS_ATTRIBUTE_TYPE::LEAD);
	if ($asm_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	  return $asm_result;
      }
    }
  }
}

// Remove a member from given project
function remove_project_member($args, $message)
{

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    // missing arg
    error_log("Missing project_id arg to remove_project_member");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is missing");
  }
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in remove_project_member: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is invalid: " . $project_id);
  }

  if (! array_key_exists(PA_ARGUMENT::MEMBER_ID, $args) or
      $args[PA_ARGUMENT::MEMBER_ID] == '') {
    // missing arg
    error_log("Missing member_id arg to remove_project_member");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is missing");
  }
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  if (! uuid_is_valid($member_id)) {
    error_log("member_id invalid in remove_project_member: " . $member_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is invalid: " . $member_id);
  }

  global $PA_PROJECT_MEMBER_TABLENAME;
  global $mysigner;
  global $log_url;

  // Stop if this member is the lead or isn't in the project
  $conn = db_conn();
  $sql = "SELECT " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . " from " . 
    $PA_PROJECT_MEMBER_TABLENAME . " where "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID . " = " .
    $conn->quote($project_id, 'text') . " and "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = "
    . $conn->quote($member_id, 'text');
  $result = db_fetch_row($sql);

  // If we got nothing back, then this member isn't in this project
  if (! array_key_exists('value', $result) or is_null($result['value'])
      or !array_key_exists(PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE, $result['value'])) {
    error_log("remove_from_project: member " . $member_id . " not in project " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member " . $member_id . " not in project " . $project_id);
  }

  $role = $result['value'][PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
  if ($role == CS_ATTRIBUTE_TYPE::LEAD) {
    error_log("remove_from_project: member " . $member_id . " is LEAD for project " . $project_id);
    // Return right error message
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Cannot remove LEAD from project: " . $project_id);
  }


  // Remove member from all slices. 
  // If member is lead of a given slice, replace with project lead
  remove_project_member_from_slices($member_id, $project_id);

  // FIXME: Stop if the member is the LEAD on a non-expired slice?

  $sql = "DELETE FROM " . $PA_PROJECT_MEMBER_TABLENAME
  . " WHERE "
          . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
          . " = " . $conn->quote($project_id, 'text') . " AND "
                  . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID
                  . "= " . $conn->quote($member_id, 'text');
  //  error_log("PA.remove project_member.sql = " . $sql);
  $result = db_execute_statement($sql);

  // Delete previous assertions from CS
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $signer_id = $message->signerUuid();

    $membership_assertions = query_assertions($cs_url, $mysigner,
            $member_id, CS_CONTEXT_TYPE::PROJECT, $project_id);
    if($membership_assertions[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $membership_assertions;
    $membership_assertions = $membership_assertions[RESPONSE_ARGUMENT::VALUE];

    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      $da_result = delete_assertion($cs_url, $mysigner, $assertion_id);
      if ($da_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	return $da_result;
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }

    // FIXME: Remove the member from non-expired slices. But what if they are the
    // LEAD of the slice?

    global $ma_url;
    $ids = array();
    $ids[] = $signer_id;
    $ids[] = $member_id;

    $names = lookup_member_names($ma_url, $mysigner, $ids);
    if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $names;
    $names = $names[RESPONSE_ARGUMENT::VALUE];

    $signer_name = $names[$signer_id];
    $member_name = $names[$member_id];

    $lookup_project_message = array(PA_ARGUMENT::PROJECT_ID => $project_id);
    $project_data = lookup_project($lookup_project_message);
    if (($project_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
            (array_key_exists(PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME,
                    $project_data[RESPONSE_ARGUMENT::VALUE])))
    {
      $project_data = $project_data[RESPONSE_ARGUMENT::VALUE];
      $project_name = $project_data[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    } else {
      $project_name = $project_id;
    }

    $message = "$signer_name Removed $member_name from Project $project_name";
    $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
    $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
    $attributes = array_merge($pattributes, $mattributes);
    log_event($log_url, $mysigner, $message, $attributes, $signer_id);
    geni_syslog(GENI_SYSLOG_PREFIX::PA, $message);

  }

  return $result;
}

// Change role of given member in given project
function change_member_role($args, $message)
{

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    // missing arg
    error_log("Missing project_id arg to change_member_role");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is missing");
  }
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in change_member_role: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is invalid: " . $project_id);
  }

  if (! array_key_exists(PA_ARGUMENT::MEMBER_ID, $args) or
      $args[PA_ARGUMENT::MEMBER_ID] == '') {
    // missing arg
    error_log("Missing member_id arg to change_member_role");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is missing");
  }
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  if (! uuid_is_valid($member_id)) {
    error_log("member_id invalid in change_member_role: " . $member_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is invalid: " . $member_id);
  }

  $role = null;
  global $CS_ATTRIBUTE_TYPE_NAME;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args)) {
    $role = $args[PA_ARGUMENT::ROLE_TYPE];
    if (! array_key_exists($role, $CS_ATTRIBUTE_TYPE_NAME)) {
      error_log("role invalid in change_member_role: " . $role);
      return generate_response(RESPONSE_ERROR::ARGS, null,
            "Role is invalid: " . $role);
    }
  } else {
    error_log("Missing role arg to change_member_role");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Role is missing");
  }

  global $PA_PROJECT_MEMBER_TABLENAME;
  global $mysigner;

  $conn = db_conn();

  // Admin cannot change own role
  if ($message->signerUuid() == $member_id) {
    // get member current role
    $sql = "SELECT "
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE
      . " FROM " . $PA_PROJECT_MEMBER_TABLENAME
      . " WHERE "
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
      . " = " . $conn->quote($project_id, 'text')
      . " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " =  " 
      . $conn->quote($member_id, 'text');
    $result = db_fetch_row($sql);
    $allowed = TRUE;
    if($result['code'] == RESPONSE_ERROR::NONE and
       $result['code'][PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE] ==
       CS_ATTRIBUTE_TYPE::ADMIN) {
      error_log("Caller is Admin on project - cannot change own role");
      return generate_response(RESPONSE_ERROR::AUTHORIZATON, null,
			     "Project admin cannot change own role on project");
    }
  }

  $sql = "UPDATE " . $PA_PROJECT_MEMBER_TABLENAME
  . " SET " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $conn->quote($role, 'integer')
  . " WHERE "
          . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
          . " = " . $conn->quote($project_id, 'text')
          . " AND "
                  . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID
                  . " = " . $conn->quote($member_id, 'text');

  //  error_log("PA.change_member_role.sql = " . $sql);
  $result = db_execute_statement($sql);

  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $signer_id = $message->signerUuid();

    // Remove previous CS assertions about the member in this project
    $membership_assertions = query_assertions($cs_url, $mysigner, $member_id, 
					      CS_CONTEXT_TYPE::PROJECT, $project_id);
    if($membership_assertions[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $emembership_assertions;
    $membership_assertions = $membership_assertions[RESPONSE_ARGUMENT::VALUE];

    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      $da_result = delete_assertion($cs_url, $mysigner, $assertion_id);
      if ($da_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	return $da_result;
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }

    // Create new assertion for member in this role
    $ca_result = create_assertion($cs_url, $mysigner, $signer_id, $member_id, $role, 
				  CS_CONTEXT_TYPE::PROJECT, $project_id);
    if ($ca_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $ca_result;

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
    $ids = array();
    $ids[] = $signer_id;
    $ids[] = $member_id;

    $names = lookup_member_names($ma_url, $mysigner, $ids);
    if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $names;
    $names = $names[RESPONSE_ARGUMENT::VALUE];

    $signer_name = $names[$signer_id];
    $member_name = $names[$member_id];
    $role_name = $CS_ATTRIBUTE_TYPE_NAME[$role];
    $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
    $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, $member_id);
    $attributes = array_merge($pattributes, $mattributes);
    $msg = "$signer_name changed role of $member_name in project $project_name to $role_name";
    global $log_url;
    log_event($log_url, $mysigner, $msg, $attributes, $signer_id);
    geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);

  }

  return $result;
}

// Return list of member ID's and roles associated with given project
// If role is provided, filter to members of given role
function get_project_members($args)
{

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    // missing arg
    error_log("Missing project_id arg to get_project_members");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is missing");
  }
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in get_project_members: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Project ID is invalid: " . $project_id);
  }

  $role = null;
  global $CS_ATTRIBUTE_TYPE_NAME;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args) && isset($args[PA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[PA_ARGUMENT::ROLE_TYPE];
    if (! array_key_exists($role, $CS_ATTRIBUTE_TYPE_NAME)) {
      error_log("role invalid in get_project_members: " . $role);
      return generate_response(RESPONSE_ERROR::ARGS, null,
            "Role is invalid: " . $role);
    }
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

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::MEMBER_ID, $args) or
      $args[PA_ARGUMENT::MEMBER_ID] == '') {
    // missing arg
    error_log("Missing member_id arg to get_projects_for_member");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is missing");
  }
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];
  if (! uuid_is_valid($member_id)) {
    error_log("member_id invalid in get_projects_for_member: " . $member_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "Member ID is invalid: " . $member_id);
  }

  $is_member = true;
  if (array_key_exists(PA_ARGUMENT::IS_MEMBER, $args) && isset($args[PA_ARGUMENT::IS_MEMBER])) {
    // FIXME: validate it as a boolean?
    $is_member = $args[PA_ARGUMENT::IS_MEMBER];
  }

  $role = null;
  global $CS_ATTRIBUTE_TYPE_NAME;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args) && isset($args[PA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[PA_ARGUMENT::ROLE_TYPE];
    if (! array_key_exists($role, $CS_ATTRIBUTE_TYPE_NAME)) {
      error_log("role invalid in get_projects_for_member: " . $role);
      return generate_response(RESPONSE_ERROR::ARGS, null,
            "Role is invalid: " . $role);
    }
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

function lookup_project_details($args)
{

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_UUIDS, $args)) {
    error_log("Missing project_uuids arg to lookup_project_details");
    return generate_response(RESPONSE_ERROR::ARGS, null,
            "project_uuids is missing");
  }
  $project_uuids = $args[PA_ARGUMENT::PROJECT_UUIDS];
  $project_uuids_as_sql = convert_list($project_uuids);

  global $PA_PROJECT_TABLENAME;

    $sql = "select "  
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME . ", "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_EMAIL . ", "
    . PA_PROJECT_TABLE_FIELDNAME::CREATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . ", "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRED
    . " FROM " . $PA_PROJECT_TABLENAME 
    . " WHERE " .   PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . " IN " .
      $project_uuids_as_sql;
    //    error_log("lookup_project_details: " . $sql);
    $rows = db_fetch_rows($sql);
    return $rows;

}

// Support for generating and accepting invitations

// Remove expired invitations
function expire_project_invitations()
{
  global $PA_PROJECT_MEMBER_INVITATION_TABLENAME;

  $conn = db_conn();

  $now = new DateTime();

  $sql = "delete from " . $PA_PROJECT_MEMBER_INVITATION_TABLENAME . 
    " where " . PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION . " < " . 
    $conn->quote(db_date_format($now), 'timestamp');

  $result = db_execute_statement($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    error_log("EXPIRE_PROJECT_INVITES ERROR : " . print_r($result, true));

}

// Generate an invitation for a given (unknown) member for a given project/role
// If they hit the accept email link and confirmation button within the expiration window,
// (and have authenticated) they are added to the project
function invite_member($args, $message)
{
  global $project_default_invitation_expiration_hours;
  global $PA_PROJECT_MEMBER_INVITATION_TABLENAME;

  expire_project_invitations();

  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $role = $args[PA_ARGUMENT::ROLE_TYPE];

  $now = new DateTime();
  $expiration = get_future_date(0, $project_default_invitation_expiration_hours);
  $invite_id = make_uuid();

  $conn = db_conn();

  $sql = "insert into " .   $PA_PROJECT_MEMBER_INVITATION_TABLENAME . "(" . 
    PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID . ", " . 
    PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::PROJECT_ID . ", " . 
    PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::ROLE . ", " . 
    PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION . ") VALUES (" . 
    $conn->quote($invite_id, 'text') . ", " . 
    $conn->quote($project_id, 'text') . ", " . 
    $conn->quote($role, 'integer') . ", " . 
    $conn->quote(db_date_format($expiration), 'timestamp') . ") ";

  $result = db_execute_statement($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $result;

  $result = array(PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID => $invite_id,
		  PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION => $expiration);
		  
  return generate_response(RESPONSE_ERROR::NONE, $result, "");

}

function accept_invitation($args, $message)
{

  expire_project_invitations();

  $conn = db_conn();

  $invite_id = $args[PA_ARGUMENT::INVITATION_ID];
  $member_id = $args[PA_ARGUMENT::MEMBER_ID];

  // Grab invite details
  global $PA_PROJECT_MEMBER_INVITATION_TABLENAME;
  $sql = "select " . 
    PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::PROJECT_ID . ", " . 
    PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::ROLE . ", " . 
    PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION . 
    " from " . $PA_PROJECT_MEMBER_INVITATION_TABLENAME .
    " where "   . PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID . " = " . 
    $conn->quote($invite_id, 'text');

  $result = db_fetch_rows($sql);
  if($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $result;
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  error_log("ROWS = " . print_r($rows, true));
  if(count($rows) == 0) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Invitation has been deleted.");
  }

  $row = $rows[0];
  $project_id = $row[PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::PROJECT_ID];
  $role = $row[PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::ROLE];
  $expiration = new DateTime($row[PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION]);

  // If expired, return error
  $now = new DateTime();
  if ($expiration < $now) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Invitation has expired");
  }

  // Otherwise, grab project_id, add to member project
  $addres = add_project_member(array(PA_ARGUMENT::PROJECT_ID => $project_id, 
				     PA_ARGUMENT::MEMBER_ID => $member_id, 
				     PA_ARGUMENT::ROLE_TYPE => $role), 
			       $message);

  if ($addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $addres;

  // Delete the invitation
  $sql = "delete from " . 
    $PA_PROJECT_MEMBER_INVITATION_TABLENAME .
    " where "   . PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID . " = " . 
    $conn->quote($invite_id, 'text');
  $result = db_fetch_rows($sql);
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

// Since the PA calls SA and MA functions, we need to look 
// at the result codes ourselves
function pa_message_result_handler($result) 
{
  //  error_log("PA_MESSAGE_RESULT_HANDLER.result = " . print_r($result, true));
  return $result;
}

require_once('rq_controller.php');

// FIXME: Should not be hardcorded
$mycertfile = '/usr/share/geni-ch/pa/pa-cert.pem';
$mykeyfile = '/usr/share/geni-ch/pa/pa-key.pem';
$mysigner = new Signer($mycertfile, $mykeyfile);
$guard_factory = new PAGuardFactory($cs_url);
$put_message_result_handler = 'pa_message_result_handler';
//error_log("PA_CONTROLLER.PUT_MESSAGE_RESULT_HANDLER = " . $put_message_result_handler);
handle_message("PA", $cs_url, default_cacerts(),
$mysigner->certificate(), $mysigner->privateKey(), $guard_factory);

?>

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

/**
 * GENI Clearinghouse Slice Authority (SA) controller interface
 * The SA maintains both slices and projects (which are groups of slices).
 *
 * It thus presents two different interfaces:
 *   a 'Project Authority' or PA interface
 *   a 'Slice Authority' or SA interface
 *
 * The PA interface supports these methods:
 *   project_id <= create_project(project_name, lead_id, lead_email, 
 *        purpose, expiration)
 *   [project_name, lead_id, project_email, project_purpose] <= 
 *        lookup_project(project_id);
 *   update_project(project_id, project_email, project_purpose, expiration);
 *   get_project_members(project_id, role=null) // null => Any
 *   get_projects_for_member(member_id, is_member, role=null)
 *   lookup_project_details(project_uuids)
 *   invite_member(project_id, role, expiration)
 *   accept_invitation(invite_id)
 *   modify_project_membership(project_id, members_to_add, 
 *        members_to_change_role, members_to_remove)
 *   lookup_project_attributes(project_id)
 *   add_project_attribute(project_id, name, value)
 *
 *
 * The SA interface supports these methods:
 *   get_slice_credential(slice_id, experimenter_cert)
 *   get_user_credential(experimenter_cert)
 *   create_slice(slice_name, project_id, project_name, owner_id, description)
 *   lookup_slice_ids(project_id, owner_id, slice_name
 *   lookup_slices(project_id, member_id)
 *   lookup_slice(slice_id)
 *   lookup_slice_by_urn(slice_urn)
 *   renew_slice(slice_id, expiration)
 *   modify_slice_membership(slice_id, members_to_add, 
 *        members_to_change_role, members_to_remove)
 *  get_slice_members(slice_id, role_type)
 *  get_slice_members_for_project(project_id, role_type)
 *  get_slices_for_member(member_id, is_member, role_type
 *  lookup_slice_details(slice_uuids)
 *  get_slices_for_projects(project_uuids, allow_expired))
 */

$prev_name = session_id('SA-SESSION');

require_once("message_handler.php");
require_once('signer.php');
require_once('file_utils.php');
require_once('db_utils.php');
require_once('sa_utils.php');
require_once("sa_settings.php");
require_once('pa_constants.php');
require_once('sa_constants.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('cs_client.php');
require_once('ma_client.php');
require_once('logging_client.php');
require_once('geni_syslog.php');
require_once('response_format.php');
require_once('rq_controller.php');
require_once('maintenance_mode.php');

$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

function sa_debug($msg)
{
  //  error_log('SA DEBUG: ' . $msg);
}

function valid_expiration($expiration)
{
  $exp_timestamp = strtotime($expiration);
  // Any valid (ie. parseable) date is fine
  return $exp_timestamp !== false;
}

/*----------------------------------------------------------------------
 * Expiration
 *----------------------------------------------------------------------
 */
/**
 * A poor man's expiration. Call this at the start of an API method
 * to expire slices in advance of the call. Eventually we will need
 * a daemon for this.
 *
 * N.B. This is not sufficient for warning emails that a slice is
 * going to expire soon. For that, a daemon is necessary.
 */
function sa_expire_slices()
{
  /*
   * Select slice ids that should expire.
   * For each id:
   *   Update the DB
   *   Log expire to Logger
   *   Log expiration to geni_syslog
   */
  global $log_url;
  global $mysigner;
  global $SA_SLICE_TABLENAME;
  $conn = db_conn();
  $now_utc = new DateTime(null, new DateTimeZone('UTC'));
  $sql = "SELECT "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::EXPIRATION
    . " < " . $conn->quote(db_date_format($now_utc), 'timestamp')
    . " AND NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED;
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] !== RESPONSE_ERROR::NONE) {
    $msg = "sa_expire_slices error: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::SA, $msg);
    return;
  }
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  foreach ($rows as $row) {
    $slice_id = $row[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    $slice_name = $row[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
    $project_id = $row[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
    $owner_id = $row[SA_SLICE_TABLE_FIELDNAME::OWNER_ID];
    $sql = "UPDATE $SA_SLICE_TABLENAME"
      . " SET " . SA_SLICE_TABLE_FIELDNAME::EXPIRED . " = TRUE"
      . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . " = "
      . $conn->quote($slice_id, 'text');
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] !== RESPONSE_ERROR::NONE) {
      $msg = "Failed to expire slice $slice_id: "
        . $result[RESPONSE_ARGUMENT::OUTPUT];
      geni_syslog(GENI_SYSLOG_PREFIX::SA, $msg);
      continue;
    }
    $project_attribute = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT,
						   $project_id);
    $slice_attribute = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE,
						 $slice_id);
    $attributes = array_merge($project_attribute, $slice_attribute);
    $log_msg = "Expired slice " . $slice_name;
    log_event($log_url, $mysigner, $log_msg, $attributes, $owner_id);
    $syslog_msg = "Expired slice $slice_id";
    geni_syslog(GENI_SYSLOG_PREFIX::SA, $syslog_msg);
  }
}

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
    . " " . $time_sense . " " 
    . $conn->quote(db_date_format($now_utc), 'timestamp')
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
class SAGuardFactory implements GuardFactory
{
  private static $context_table
    = array(
            // Action => array(method_name, method_name, ...)
	    // PA methods
	    'create_project' => array(), // Unguarded -- method itself checks that LEAD_ID is a valid project lead
	    'get_projects' => array('TrueGuard'), // Unguarded - by intent
	    'lookup_projects' => array('TrueGuard'), // Unguarded - by intent
	    'lookup_project' => array('TrueGuard'), // Unguarded - by intent
	    'update_project' => array('project_guard'), 
	    'modify_project_membership' => array('project_guard'), 
	    'change_project_lead' => array('FalseGuard'),
	    'add_project_member' => array('FalseGuard'),
	    'remove_project_member' => array('FalseGuard'),
	    'change_member_role' => array('FalseGuard'),
	    // FIXME: This is used when you request to join a project
	    // so we can email the project admins. But we'd like to limit this 
	    // to members of the project
	    'get_project_members' => array(), // want to use project_guard?
	    // Technically this method could be used to query about someone else, but don't allow that
	    'get_projects_for_member' => array('signer_member_guard'),
	    'lookup_project_details' => array('TrueGuard'), // Unguarded - by intent
	    "invite_member" => array("project_guard"),
	    "accept_invitation" => array('TrueGuard'), // unguarded - by intent
	    // FIXME: Does this work?
	    "lookup_project_attributes" => array('project_guard'),
	    // This should be only project lead or admin (or operator)
	    // FIXME: Does this work?
	    "add_project_attribute" => array('project_guard'),
	    "remove_project_attribute" => array('project_guard'),
	    'add_project_lead_to_slices' => array('FalseGuard'),
	    'remove_project_member_from_slices' => array('FalseGuard'),
	    //
	    // SA Methods
            'get_slice_credential' => array('slice_guard'),
	    // FIXME: Should this only be the given user? But here we just have their cert
            'get_user_credential' => array(), // Unguarded
            'create_slice' => array('project_guard'),
            'lookup_slice_ids' => array('project_guard'),
            'lookup_slices' => array('lookup_slices_guard'),
            'lookup_slice' => array('slice_guard'),
	    // FIXME: Should this be the slice_guard? (ie any member of the slice)
	    // But we don't know the slice object here, we just have the URN
            'lookup_slice_by_urn' => array(), // Unguarded
            'renew_slice' => array('slice_guard'),
	    'modify_slice_membership' => array('slice_guard'),
	    'add_slice_member' => array('FalseGuard'),
	    'remove_slice_member' => array('FalseGuard'),
	    'change_slice_member_role' => array('FalseGuard'),
            'get_slice_members' => array('slice_guard'),
            'get_slice_members_for_project' => array('project_guard'),
            'get_slices_for_member'=> array('signer_member_guard'),
	    // FIXME: Should this be the slice guard?
	    // Trick is this takes a list of slice IDs.
	    // This is how a user sees the details of their slices
            'lookup_slice_details' => array(), // Unguarded
	    // FIXME: Allow this if you have the right on all project IDs in the given list?
	    // but all we have is a list of project UUIDs
	    // This is how we get the list of slices for a user
            'get_slices_for_projects' => array(), // Unguarded
	    //
	    // Methods for managing pending requests on projects or slices
	    'create_request' => array('TrueGuard'), // Unguarded - by intent
	    'resolve_pending_request' => array('project_request_guard'), // only called for project requests
	    // FIXME: limit to lead/admin of this context
	    'get_requests_for_context' => array(), // Unguarded
	    // limit to this user (arg is ACCOUNT_ID, should match signer UUID)
	    'get_requests_by_user' => array('signer_account_guard'),
	    // limit to this user (arg ACCOUNT_ID matches signer UUID)
	    'get_pending_requests_for_user' => array('signer_account_guard'),
	    // limit to this user (arg ACCOUNT_ID matches signer UUID
	    'get_number_of_pending_requests_for_user' => array('signer_account_guard'),
	    'get_request_by_id' => array(), // Unguarded
	    //
	    // Internal methods
	    'sa_debug' => array('FalseGuard'),
	    'sa_expire_slices' => array('FalseGuard'),
	    'pa_expire_projects' => array('FalseGuard'),
	    'pa_expire_projects_internal' => array('FalseGuard'),
	    'expire_project_invitations' => array('FalseGuard'),
	    'change_slice_owner' => array('FalseGuard'),
	    'sa_message_result_handler' => array('FalseGuard')
            );

  public function __construct($cs_url) {
    $this->cs_url = $cs_url;
  }

  public function project_request_guard($message, $action, $params)
  {
    //    error_log("PA.project_request_guard " . print_r($message, true) 
    // . " " . print_r($action, true) . " " . print_r($params, true));    
    return new PAProjectRequestGuard($this->cs_url, $message, $action, 
				     $params);	
  }

  private function slice_guard($message, $action, $params) {
    sa_debug("slice_guard($message, $action, $params)");
    return new SAContextGuard($this->cs_url, $message, $action,
                              CS_CONTEXT_TYPE::SLICE,
                              $params[SA_ARGUMENT::SLICE_ID]);
  }

  private function project_guard($message, $action, $params) {
    sa_debug("project_guard($message, $action, $params)");
    return new SAContextGuard($this->cs_url, $message, $action,
                              CS_CONTEXT_TYPE::PROJECT,
                              $params[SA_ARGUMENT::PROJECT_ID]);
  }

  /* Ensure that the signer matches the OWNER parameter. */
  private function signer_owner_guard($message, $action, $params) {
    return new SASignerGuard($this->cs_url, $message, $action, $params, 
			     SA_ARGUMENT::OWNER_ID);
  }

  /* Ensure that the signer matches the MEMBER parameter. */
  private function signer_member_guard($message, $action, $params) {
    return new SASignerGuard($this->cs_url, $message, $action, $params, 
			     SA_ARGUMENT::MEMBER_ID);
  }

  /* Ensure that the signer matches the ACCOUNT_ID parameter. */
  private function signer_account_guard($message, $action, $params) {
    return new SASignerGuard($this->cs_url, $message, $action, $params, 
			     RQ_ARGUMENTS::ACCOUNT_ID);
  }

  private function TrueGuard($message, $action, $params) {
    return new TrueGuard();
  }

  private function FalseGuard($message, $action, $params) {
    return new FalseGuard();
  }

  /**
   * Special function for 'lookup_slices' to handle the case where
   * PROJECT_ID is null.
   */
  private function lookup_slices_guard($message, $action, $params) {
    sa_debug("lookup_slices_guard($message, $action, $params)");
    if (is_null($params[SA_ARGUMENT::PROJECT_ID])) {
      return new TrueGuard();
    } else {
      return $this->project_guard($message, $action, $params);
    }
  }

  public function createGuards($message) {
    $result = array();
    $parsed_message = $message->parse();
    $action = $parsed_message[0];
    $params = $parsed_message[1];
    if (array_key_exists($action, self::$context_table)) {
      if (self::$context_table[$action] == array()) {
	// FIXME: Deny access altogether?
	error_log("SA/PA function unguarded: " . $action);
	geni_syslog(GENI_SYSLOG_PREFIX::SA, "SA/PA function unguarded: " . $action);
      }
      foreach (self::$context_table[$action] as $method_name) {
        // How to call a method dynamically
        $meth = array($this, $method_name);
        $result[] = call_user_func($meth, $message, $action, $params);
      }
    } else {
      error_log("SA/PA function unguarded: " . $action);
      geni_syslog(GENI_SYSLOG_PREFIX::SA, "SA/PA function unguarded: " . $action);
    }

    // Allow another authority to perform actions on behalf of users
    // *** FIXME: Should be replaced with speaks-for logic ***
    if (count($result) > 0) {
      $result[] = new SignerAuthorityGuard($message);
      $result = array(new OrGuard($result));
    }
    return $result;
  }
}

/**
 * This is more of a demonstration guard than anything else.
 * It really isn't an appropriate test, but gets the point
 * across that a user can't call certain methods, but an
 * authority could.
 */
class SignerAuthorityGuard implements Guard
{
  public function __construct($message) {
    $this->message = $message;
  }

  public function evaluate() {
    $result =  (strpos($this->message->signerUrn(), '+authority+') !== FALSE);
    //    error_log("SAG.evaluate : " . print_r($result, true) . " " . 
    //          $this->message->signerUrn());
    return $result;
  }
}


class SAContextGuard implements Guard
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
    sa_debug("MessageHandler requesting authorization:"
             . " for principal=\""
             . print_r($this->message->signerUuid(), TRUE) . "\""
             . "; action=\"" . print_r($this->action, TRUE) . "\""
             . "; context_type=\"" . print_r($this->context_type, TRUE) . "\""
             . "; context=\"" . print_r($this->context, TRUE) . "\"");
    $ra_res = request_authorization($this->cs_url, 
				    $mysigner,
				    $this->message->signerUuid(),
				    $this->action, $this->context_type,
				    $this->context);
    $result = $ra_res[RESPONSE_ARGUMENT::VALUE];
    if ($ra_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      $result = false;
    if (! $result || $result != 1) {
      geni_syslog(GENI_SYSLOG_PREFIX::SA,
		  "SAContextGuard for " . $this->message->signerUuid()
		  . " on action " . $this->action
		  . " returning " . print_r($result, true));
      error_log("SAContextGuard for " . $this->message->signerUuid()
		. " on action " . $this->action
		. " returning " . print_r($result, true));
    }
    return $result;
  }
}

class SASignerGuard implements Guard
{
  function __construct($cs_url, $message, $action, $params, $match_param)
  {
    $this->cs_url = $cs_url;
    $this->message = $message;
    $this->action = $action;
    $this->params = $params;
    $this->match_param = $match_param;
  }
  /**
   * Return TRUE if the signer and the $match_param match, FALSE otherwise.
   */
  function evaluate() {
    $match_param = $this->params[$this->match_param];
    sa_debug("SASignerGuard matching signer against " . $match_param);
    return $this->message->signerUuid() === $match_param;
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
    //    error_log("PAProjectRequestGuard.evaluate " . $signer . " " . 
    //         print_r($request_id, true)); 

    global $PA_PROJECT_MEMBER_TABLENAME;
    global $PA_PROJECT_MEMBER_REQUEST_TABLENAME;
    $conn = db_conn();
    $sql = "select count(*) from $PA_PROJECT_MEMBER_TABLENAME, " 
      . "$PA_PROJECT_MEMBER_REQUEST_TABLENAME "
      . " WHERE "
      . " $PA_PROJECT_MEMBER_REQUEST_TABLENAME." 
      . RQ_REQUEST_TABLE_FIELDNAME::ID 
      . " = " . $conn->quote($request_id, 'text')
      . " AND "
      . " $PA_PROJECT_MEMBER_REQUEST_TABLENAME." 
      . RQ_REQUEST_TABLE_FIELDNAME::CONTEXT_ID . " = "
      . " $PA_PROJECT_MEMBER_TABLENAME." 
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID
      . " AND "
      . " $PA_PROJECT_MEMBER_TABLENAME." 
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
      . " = " . $conn->quote($signer, 'text')
      . " AND "
      . " $PA_PROJECT_MEMBER_TABLENAME." 
      . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE
      . " IN (" . $conn->quote(CS_ATTRIBUTE_TYPE::LEAD, 'integer') 
      . ", " . $conn->quote(CS_ATTRIBUTE_TYPE::ADMIN, 'integer') . ")";
    //    error_log("PAProjectRequestGuard.sql = $sql"); 
    $result = db_fetch_row($sql);
    //    error_log("Result = " . print_r($result, true)); 
    $allowed = FALSE;
    if($result['code'] == RESPONSE_ERROR::NONE) {
      $result = $result['value']['count'];
      $allowed = $result > 0;
    }

    // If not allowed but requestor is signer and request status is 
    // cancelled, then allow it
    if (! $allowed) {
      $resolution_status = null;
      if (array_key_exists(RQ_ARGUMENTS::RESOLUTION_STATUS, $this->params)) {
        $resolution_status = $this->params[RQ_ARGUMENTS::RESOLUTION_STATUS];
        //error_log("not allowed but res_status= $resolution_status"); 
        if ($resolution_status == RQ_REQUEST_STATUS::CANCELLED) {

          $sql = "select " . RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR 
	    . " FROM $PA_PROJECT_MEMBER_REQUEST_TABLENAME WHERE "
	    . " $PA_PROJECT_MEMBER_REQUEST_TABLENAME." 
	    . RQ_REQUEST_TABLE_FIELDNAME::ID 
	    . " = " . $conn->quote($request_id, 'text');
          //error_log("doing sql $sql"); 
          $result = db_fetch_row($sql);
          if ($result['code'] == RESPONSE_ERROR::NONE and 
	      $result['value'][RQ_REQUEST_TABLE_FIELDNAME::REQUESTOR] 
	      == $signer) {
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



/*----------------------------------------------------------------------
 * PA API Methods
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

  $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  if (! isset($project_name) or is_null($project_name) or 
      $project_name == '') 
    {
      return generate_response(RESPONSE_ERROR::ARGS, null,
			       "Project name is missing");
    }
  if (strpos($project_name, ' ') !== false) {
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project name '$project_name' is invalid: no spaces allowed.");
  }

  if (!is_valid_project_name($project_name)) {
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project name '$project_name' is invalid: " . 
			     "Use at most 32 alphanumerics plus hyphen and " . 
			     "underscore; no leading hyphen or underscore.");
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
    . " WHERE lower(" . PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME 
    . ") = lower(" . $conn->quote($project_name, 'text') . ")";
  $exists_response = db_fetch_row($exists_sql);
  $exists = $exists_response[RESPONSE_ARGUMENT::VALUE];
  $exists = $exists['count'];
  if ($exists > 0) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null,
			     "A project named '" . $project_name . "' already exists.");
  }

  // Ensure that designated lead ID is allowed to be a project lead
  $permitted = request_authorization($cs_url, $mysigner, $lead_id, 
				     PA_ACTION::CREATE_PROJECT,
				     CS_CONTEXT_TYPE::RESOURCE, null);
  if ($permitted[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $permitted;
  $permitted = $permitted[RESPONSE_ARGUMENT::VALUE];

  if (! $permitted) {
    // FIXME: Need a syslog for this?
   $msg = "Principal " . $lead_id . " may not be the lead on a project";
   geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);
   return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted,
			     "Principal " . $lead_id  . " may not create project");
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
      return generate_response(RESPONSE_ERROR::ARGS, "", 
			       "Invalid date: \"$expiration\"");
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
				     PA_ARGUMENT::ROLE_TYPE 
				     => CS_ATTRIBUTE_TYPE::LEAD),
			       $message);

  if (! isset($addres) || is_null($addres) || ! 
      array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) || 
      $addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) 
    {
      error_log("create_project failed to add lead as a project member: " 
		. $addres[RESPONSE_ARGUMENT::CODE] 
		. ": " . $addres[RESPONSE_ARGUMENT::OUTPUT]);
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

  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
					  $project_id);
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

  //  error_log("LP.ARGS = " . print_r($args, true));

  pa_expire_projects();

  if (array_key_exists(PA_ARGUMENT::PROJECT_ID, $args)) {
    $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  }
  if (array_key_exists(PA_ARGUMENT::PROJECT_NAME, $args)) {
    $project_name = $args[PA_ARGUMENT::PROJECT_NAME];
  }
  if ((! isset($project_id) || is_null($project_id) || 
       $project_id == '' || !uuid_is_valid($project_id))  
      && (! isset($project_name) || is_null($project_name) || 
	  $project_name == '')) 
    {
      error_log("Missing project ID and project name to lookup_project");
      return generate_response(RESPONSE_ERROR::ARGS, null,
			       "Project ID and name missing or invalid");
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
      return generate_response(RESPONSE_ERROR::ARGS, "", 
			       "Invalid date: \"$expiration\"");
    }
    $db_expiration = $conn->quote($expiration, 'text');
  } else {
    $db_expiration = "NULL";
  }
  $sql = "UPDATE " . $PA_PROJECT_TABLENAME
    . " SET "
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE . " = " 
    . $conn->quote($project_purpose, 'text')
    . ", " . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . " = " . $db_expiration
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID
    . " = " . $conn->quote($project_id, 'text');

  //  error_log("UPDATE.sql = " . $sql);

  $result = db_execute_statement($sql);
  // FIXME: Check that this succeeded!

  // un expire the project if necessary
  $now_utc = new DateTime(null, new DateTimeZone('UTC'));
  $sql2 = "UPDATE " . $PA_PROJECT_TABLENAME
    . " SET "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRED . " = FALSE WHERE " 
    . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . " = " . $conn->quote($project_id, 'text')
    . " AND " . PA_PROJECT_TABLE_FIELDNAME::EXPIRED . " AND ("
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . " is null or "
    . PA_PROJECT_TABLE_FIELDNAME::EXPIRATION . " > " 
    . $conn->quote(db_date_format($now_utc), 'timestamp') . ")";

  $result2 = db_execute_statement($sql2);
  $unexpired = False;
  if ($result2[RESPONSE_ARGUMENT::CODE] === RESPONSE_ERROR::NONE and $result2[RESPONSE_ARGUMENT::VALUE] == 1) {
    $unexpired = True;
  }

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

  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
					  $project_id);
  $msg = "$signer_name Updated project $project_name with purpose: '" . 
    "$project_purpose'";
  if ($unexpired) {
    $msg = $msg . "; project no longer expired";
  }
  log_event($log_url, $mysigner, $msg, $attributes, $signer_id);
  geni_syslog(GENI_SYSLOG_PREFIX::PA, $msg);

  return $result;
}

// Modify project membership according to given lists of add/change_role/remove
// $members_to_add and $members_to_change are both
//    dictionaries of (member_id => role, ...)
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
  $project_members = 
    get_project_members(array(PA_ARGUMENT::PROJECT_ID => $project_id));
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
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Bad project: no lead");
  }

  // First validate the arguments
  // No new members should be already a project member
  if (!already_in_list(array_keys($members_to_add), 
		       array_keys($project_members), true)) 
    {
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Can't add member to a project that " . 
			       "already belongs");
    }

  // No new roles for members who aren't project members
  if (!already_in_list(array_keys($members_to_change_role), 
		       array_keys($project_members), false)) 
    {
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Can't change member role for member " . 
			       "not in project");
    }

  // Can't remove members who aren't project members
  if (!already_in_list(array_keys($members_to_remove), 
		       array_keys($project_members), false)) 
    {
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Can't remove member from project if " . 
			       "not a member");
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
  foreach($members_to_change_role as $member_to_change_role => $role) 
    {
      if ($role == CS_ATTRIBUTE_TYPE::LEAD && 
	  $member_to_change_role != $project_lead) 
	{
	  $lead_changes = $lead_changes + 1;
	  $new_project_lead = $member_to_change_role;
	}
      if ($member_to_change_role == $project_lead && 
	  $role != CS_ATTRIBUTE_TYPE::LEAD)
	$lead_changes = $lead_changes - 1;
    }

  foreach($members_to_remove as $member_to_remove) 
    {
      if($member_to_remove == $project_lead)
	$lead_changes = $lead_changes - 1;
    }

  if($lead_changes != 0) {
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Must have exactly one project lead");
  }

  // This code handles the case wherein we are changing the project lead
  //  error_log("NPL = " . $new_project_lead . " PL = " . $project_lead);
  if ($new_project_lead != $project_lead) {
    // Can't make someone a project lead if they don't have 
    // create_project capabilities
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

    // Change the 'lead_id' field of the project
    $cl_result = change_project_lead($project_id, $new_project_lead);
    if($cl_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $cl_result;

    // If we're changing the lead, make sure new lead is a member
    // of all slices of project
    $aplts = add_project_lead_to_slices($project_id, 
					$new_project_lead, $message);
    if ($aplts[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $aplts;
    
  }



  // Can't remove project lead directly
  // Need to demote them and then remove them
  if (array_key_exists($project_lead, $members_to_remove)) {
    return generate_response(RESPONSE_ERROR::ARGS, null,
			      "Cannot remove lead from project. " . 
			      "Replace first.");
  }
  


  // There is a problem of transactional integrity here
  // The PA keeps its own table of members/roles, and the CS keeps a 
  // table of assertions which are essentially redundant. 
  // Ultimately, these should be unified and probably kept in the CS
  // and the CS should allow for a bundling (writing multiple 
  // adds/deletes at once)
  // 
  // But for now we maintain the two sets of tables, writing the 
  // sa_project_member_table atomically
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
      $add_project_member_args = 
	array(PA_ARGUMENT::PROJECT_ID => $project_id, 
	      PA_ARGUMENT::MEMBER_ID => $member_to_add,
	      PA_ARGUMENT::ROLE_TYPE => $role);
      $result = add_project_member($add_project_member_args, 
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
      $change_member_role_args = 
	array(PA_ARGUMENT::PROJECT_ID => $project_id,
	      PA_ARGUMENt::MEMBER_ID => $member_to_change_role,
	      PA_ARGUMENT::ROLE_TYPE => $role);
      $result = change_member_role($change_member_role_args, 
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
      $remove_project_member_args = 
	array(PA_ARGUMENT::PROJECT_ID => $project_id,
	      PA_ARGUMEnt::MEMBER_ID => $member_to_remove);
      $result = remove_project_member($remove_project_member_args, 
				      $message);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	$success = False;
	$error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
      }
    }
  }

  // If all the PA and CS transactions are successful, 
  // then commit, otherwise rollback
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

/* update lead_id of given project.
*  To be called only by SA/PA local functions */
function change_project_lead($project_id, $new_project_lead)
{
  global $PA_PROJECT_TABLENAME;

  pa_expire_projects();

  $conn = db_conn();
  $sql = "UPDATE " . $PA_PROJECT_TABLENAME
    . " SET "
    . PA_PROJECT_TABLE_FIELDNAME::LEAD_ID . " = " . 
    $conn->quote($new_project_lead, 'text')
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID
    . " = " . $conn->quote($project_id, 'text');
  //  error_log("CHANGE_PROJECT_LEAD.sql = " . $sql);
  $result = db_execute_statement($sql);

  return $result;
}

// Add a member of given role to given project
// Return code/value/output triple
// Internal only method
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
  global $ma_url;

  $conn = db_conn();

  // Check that this is a valid project ID
  global $PA_PROJECT_TABLENAME;
  $valid_project_sql = "select count(*) from " . $PA_PROJECT_TABLENAME
    . " WHERE " . PA_PROJECT_TABLE_FIELDNAME::PROJECT_ID . " = "
    . $conn->quote($project_id, 'text');
  $valid_project = db_fetch_row($valid_project_sql);
  if (! array_key_exists('value', $valid_project) or
      is_null($valid_project['value']) or 
      ! array_key_exists('count', $valid_project['value'])) 
    {
      return $valid_project;
    }
  $valid_project = $valid_project['value']['count'] > 0;
  if (!$valid_project) {
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Project $project_id unknown.");
  }

  // Check if the member is already in the project
  $already_member_sql = "select count(*) from " 
    . $PA_PROJECT_MEMBER_TABLENAME
    . " WHERE "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::PROJECT_ID . " = " 
    . $conn->quote($project_id, 'text')
    . " AND "
    . PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " 
    . $conn->quote($member_id, 'text');
  $already_member = db_fetch_row($already_member_sql);
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if (! array_key_exists('value', $already_member) or
      is_null($already_member['value']) or 
      ! array_key_exists('count', $already_member['value'])) 
    {
      return $already_member;
    }
  $already_member = $already_member['value']['count'] > 0;
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if ($already_member) {
    // get member name
    $ids = array();
    $ids[] = $member_id;
    $names = lookup_member_names($ma_url,$mysigner,$ids);
    if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $names;
    $names = $names[RESPONSE_ARGUMENT::VALUE];
    $member_name = $names[$member_id];
    // get project name
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
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Member $member_name is already a member of " . 
			     "project $project_name");
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

  /* FIXME - The signer needs to have a certificate and private key. 
   * Who sends this message (below) to the CS? Is the PA the signer?
   */
  $signer_id = $message->signerUuid();

  // If successful, add an assertion of the role's privileges 
  // within the CS store
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $ca_result = create_assertion($cs_url, $mysigner, $signer_id, 
				  $member_id, $role, 
				  CS_CONTEXT_TYPE::PROJECT, $project_id);
    if($ca_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $ca_result;
  }

  // Log adding the member
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
      $msg = "$signer_name Added $member_name to Project $project_name " . 
	"in role $role_name";
      if ($signer_name == $member_name) {
	$msg = "$signer_name Added self to Project $project_name in " . 
	  "role $role_name";
      }
      $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
					       $project_id);
      $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, 
					       $member_id);
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

// Add project lead as admin to all current project slices
// internal only method
function add_project_lead_to_slices($project_id, $project_lead_id, $message)
{

  $gsmfp_args = array(SA_ARGUMENT::PROJECT_ID => $project_id);
  $slices_and_members_for_project = 
    get_slice_members_for_project($gsmfp_args, $message);
  if($slices_and_members_for_project[RESPONSE_ARGUMENT::CODE] 
     != RESPONSE_ERROR::NONE)
    return $slices_and_members_for_project;
  $slices_and_members_for_project = 
    $slices_and_members_for_project[RESPONSE_ARGUMENT::VALUE];
  //  error_log("SAMFP = " . print_r($slices_and_members_for_project, true));

  // This is a list of [slice_id, member_id, role] tuples
  // We need to go through this 
  // and find all slices to which member does not belong
  $slice_roles_for_lead = array();
  foreach(  $slices_and_members_for_project as $row) {
    $member_id = $row[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    $role = $row[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
    $slice_id = $row[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    if(!array_key_exists($slice_id, $slice_roles_for_lead))
      $slice_roles_for_lead[$slice_id] = -1;
    if($member_id == $project_lead_id) 
      $slice_roles_for_lead[$slice_id] = $role;
  }

  //  error_log("SRFL = " . print_r($slice_roles_for_lead, true));

  foreach($slice_roles_for_lead as $slice_id => $role) {
    if($role == -1) {
      // This is a slice to which the lead doesn't belong
      $asm_args = array(SA_ARGUMENT::SLICE_ID => $slice_id, 
			SA_ARGUMENT::MEMBER_ID => $project_lead_id,
			SA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::ADMIN);
      $asm_result = add_slice_member($asm_args, $message);
      if ($asm_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	return $asm_result;
    }
  }
}

// Remove member from slices to which he belongs in given project
// If member is lead of such a slice, replace with project lead
// internal only method
function remove_project_member_from_slices($member_id, $project_id, $message)
{
  global $mysigner;

  // Get slices for member for project
  $slices = lookup_slices(array(SA_ARGUMENT::PROJECT_ID => $project_id, 
				SA_ARGUMENT::MEMBER_ID => $member_id),
			  $message);
  if ($slices[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) 
    return $slices;
  $slices = $slices[RESPONSE_ARGUMENT::VALUE];

  // Get roles of member within slices
  $get_slices_for_member_args = 
    array(SA_ARGUMENT::MEMBER_ID => $member_id, 
	  SA_ARGUMENT::IS_MEMBER => true);
  $member_slice_roles = get_slices_for_member($get_slices_for_member_args);
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
      $member_slice_id = 
	$member_slice_role[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID];
      $role = $member_slice_role[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
      if($slice_id == $member_slice_id) {
	error_log("Need to remove " . $member_id . " from slice " . 
		  $slice_id . " role " . $role);
	$remove_slice_member_args = 
	  array(SA_ARGUMENT::SLICE_ID => $slice_id, 
		SA_ARGUMENT::MEMBER_ID => $member_id);
	$rsm_result = remove_slice_member($remove_slice_member_args, $message);
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
  // and the list of slices to which lead belongs. 
  // If doesn't belong, add as lead
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
      $member_id = 
	$project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
      if($role == CS_ATTRIBUTE_TYPE::LEAD) {
	$project_lead_id = $member_id;
	break;
      }
    }

    // Get the ID's of slices in this project for which project lead is member
    $lookup_slices_args = array(SA_ARGUMENT::PROJECT_ID => $project_id, 
				SA_ARGUMENT::MEMBER_ID => $project_lead_id);
    $project_lead_slices = lookup_slices($lookup_slices_args, $message);
    if($project_lead_slices[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $proejct_lead_slices;
    $project_lead_slices = $project_lead_slices[RESPONSE_ARGUMENT::VALUE];

    $slices_for_project_lead = array();
    foreach($project_lead_slices as $project_lead_slice) {
      $slice_id = 
	$project_lead_slice[SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID];
      $slices_for_project_lead[] = $slice_id;
    }

    foreach($slices_to_replace_lead as $slice_id) {
      $lead_in_slice = in_array($slice_id, $slices_for_project_lead);
      if($lead_in_slice) {
	// If project lead is in slice, change to lead in slice
	$csmr_args = 
	  array(SA_ARGUMENT::SLICE_ID => $slice_id,
		SA_ARGUMENT::MEMBER_ID => $project_lead_id,
		SA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD);
	$csmr_result = change_slice_member_role($csmr_args, $message);
	if ($csmr_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	  return $csmr_result;
      } else {
	// Otherwise add member as lead to slice
	$asm_args = 
	  array(SA_ARGUMENT::SLICE_ID => $slice_id,
		SA_ARGUMENT::MEMBER_ID => $project_lead_id,
		SA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD);
	$asm_result = add_slice_member($asm_args);
	if ($asm_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	  return $asm_result;
      }
      // Change the slice owner to the new lead
      $cso_result = change_slice_owner($slice_id, $project_lead_id);
      if($cso_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	return $cso_result;
    }
  }
}


// Remove a member from given project - internal only method
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
  if (! array_key_exists('value', $result) 
      or is_null($result['value'])
      or !array_key_exists(PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE, 
			   $result['value'])) 
    {
      error_log("remove_from_project: member " . $member_id 
		. " not in project " . $project_id);
      return generate_response(RESPONSE_ERROR::ARGS, null,
			       "Member " . $member_id . 
			       " not in project " . $project_id);
    }

  $role = $result['value'][PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
  if ($role == CS_ATTRIBUTE_TYPE::LEAD) {
    error_log("remove_from_project: member " . $member_id 
	      . " is LEAD for project " . $project_id);
    // Return right error message
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Cannot remove LEAD from project: " . $project_id);
  }


  // Remove member from all slices. 
  // If member is lead of a given slice, replace with project lead
  $rpmfs_result = 
    remove_project_member_from_slices($member_id, $project_id, $message);
  if($rpmfs_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $rpmfs_result;

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
					      $member_id, 
					      CS_CONTEXT_TYPE::PROJECT, 
					      $project_id);
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
    $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
					     $project_id);
    $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, 
					     $member_id);
    $attributes = array_merge($pattributes, $mattributes);
    log_event($log_url, $mysigner, $message, $attributes, $signer_id);
    geni_syslog(GENI_SYSLOG_PREFIX::PA, $message);

  }

  return $result;
}

// Change role of given member in given project
// internal only method
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
			       "Project admin cannot change own role " . 
			       "on project");
    }
  }

  $sql = "UPDATE " . $PA_PROJECT_MEMBER_TABLENAME
    . " SET " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
    . " = " . $conn->quote($role, 'integer')
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
					      CS_CONTEXT_TYPE::PROJECT, 
					      $project_id);
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
    $ca_result = create_assertion($cs_url, $mysigner, $signer_id, 
				  $member_id, $role, 
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
    $pattributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
					     $project_id);
    $mattributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, 
					     $member_id);
    $attributes = array_merge($pattributes, $mattributes);
    $msg = "$signer_name changed role of $member_name in " . 
      "project $project_name to $role_name";
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
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args) && 
      isset($args[PA_ARGUMENT::ROLE_TYPE])) 
    {
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
      " AND " . PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE 
      . " = " . $conn->quote($role, 'integer');
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
  if (array_key_exists(PA_ARGUMENT::IS_MEMBER, $args) && 
      isset($args[PA_ARGUMENT::IS_MEMBER])) 
    {
      // FIXME: validate it as a boolean?
      $is_member = $args[PA_ARGUMENT::IS_MEMBER];
    }

  $role = null;
  global $CS_ATTRIBUTE_TYPE_NAME;
  if (array_key_exists(PA_ARGUMENT::ROLE_TYPE, $args) && 
      isset($args[PA_ARGUMENT::ROLE_TYPE])) 
    {
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
      . " = " . $conn->quote($member_id, 'text') . " " . 
      $role_clause . ")";
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
    " where " . PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION 
    . " < " . 
    $conn->quote(db_date_format($now), 'timestamp');

  $result = db_execute_statement($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    error_log("EXPIRE_PROJECT_INVITES ERROR : " . print_r($result, true));

}

// Generate an invitation for a given (unknown) member for a given project/role
// If they hit the accept email link and confirmation button within the 
// expiration window, (and have authenticated) they are added to the project
function invite_member($args, $message)
{
  global $project_default_invitation_expiration_hours;
  global $PA_PROJECT_MEMBER_INVITATION_TABLENAME;

  expire_project_invitations();

  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  $role = $args[PA_ARGUMENT::ROLE_TYPE];

  $now = new DateTime();
  $expiration = get_future_date(0, 
				$project_default_invitation_expiration_hours);
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

  $result = 
    array(PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID 
	  => $invite_id,
	  PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION 
	  => $expiration);
		  
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
    " where "   
    . PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID . " = " . 
    $conn->quote($invite_id, 'text');

  $result = db_fetch_rows($sql);
  if($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $result;
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  //error_log("ROWS = " . print_r($rows, true));
  if(count($rows) == 0) {
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Invitation has been deleted.");
  }

  $row = $rows[0];
  $project_id = $row[PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::PROJECT_ID];
  $role = $row[PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::ROLE];
  $expiration_raw = 
    $row[PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::EXPIRATION];
  $expiration = new DateTime($expiration_raw);

  // If expired, return errornn
  $now = new DateTime();
  if ($expiration < $now) {
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Invitation has expired");
  }

  // Otherwise, grab project_id, add to member project
  $apm_args = array(PA_ARGUMENT::PROJECT_ID => $project_id, 
		    PA_ARGUMENT::MEMBER_ID => $member_id, 
		    PA_ARGUMENT::ROLE_TYPE => $role);
  $addres = add_project_member($apm_args, $message);

  if ($addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $addres;

  // Delete the invitation
  $sql = "delete from " . 
    $PA_PROJECT_MEMBER_INVITATION_TABLENAME .
    " where "   . PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID 
    . " = " . $conn->quote($invite_id, 'text');
  $result = db_fetch_rows($sql);
  return $result;

}


/* Get all attributes of project
 */
function lookup_project_attributes($args)
{

  pa_expire_projects();

  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    // missing arg
    error_log("Missing project_id arg to lookup_project_attributes");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project ID is missing");
  }
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in lookup_project_attributes: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project ID is invalid: " . $project_id);
  }


  global $PA_PROJECT_ATTRIBUTE_TABLENAME;
  $conn = db_conn();
  $query = "SELECT * FROM " . $PA_PROJECT_ATTRIBUTE_TABLENAME . " WHERE " . 
    PA_ATTRIBUTE::PROJECT_ID . 
    " = " . $conn->quote($project_id, 'text');
  $result = db_fetch_rows($query);
  return $result;
}


/* Add attribute name/value pair to project
   Requires project_id, name, value
*/
function add_project_attribute($args)
{

  // verify project ID exists
  if (! array_key_exists(PA_ARGUMENT::PROJECT_ID, $args) or
      $args[PA_ARGUMENT::PROJECT_ID] == '') {
    error_log("Missing project_id arg to add_project_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project ID is missing");
  }
  
  // verify that both name and value pair exist
  // needed since both cannot be null when adding to database
  if (! array_key_exists(PA_ATTRIBUTE::NAME, $args) or
      $args[PA_ATTRIBUTE::NAME] == '') {
    error_log("Missing name arg to add_project_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Name is missing");
  }
  if (! array_key_exists(PA_ATTRIBUTE::VALUE, $args) or
      $args[PA_ATTRIBUTE::VALUE] == '') {
    error_log("Missing value arg to add_project_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Value is missing");
  }
  
  // verify project ID is valid
  $project_id = $args[PA_ARGUMENT::PROJECT_ID];
  if (! uuid_is_valid($project_id)) {
    error_log("project_id invalid in add_project_attribute: " . $project_id);
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project ID is invalid: " . $project_id);
  }

  global $PA_PROJECT_ATTRIBUTE_TABLENAME;
  $conn = db_conn();
  
  // we know name/value pair exists, so define what they are
  $name = $args[PA_ATTRIBUTE::NAME];
  $value = $args[PA_ATTRIBUTE::VALUE];
  
  // insert
  $sql = ("insert into " . $PA_PROJECT_ATTRIBUTE_TABLENAME
          . " ( " . PA_ATTRIBUTE::PROJECT_ID
          . ", " . PA_ATTRIBUTE::NAME
          . ", " . PA_ATTRIBUTE::VALUE
          . ")  VALUES ("
          . $conn->quote($project_id, 'text')
          . ", " . $conn->quote($name, 'text')
          . ", " . $conn->quote($value, 'text')
          . ")");
  $result = db_execute_statement($sql);
  return $result;

}

/* remove attribute name/value pair from project
   Requires project_id, name
*/
function remove_project_attribute($args)
{
  global $PA_PROJECT_ATTRIBUTE_TABLENAME;

  if (! array_key_exists(PA_ATTRIBUTE::PROJECT_ID, $args) or
      $args[PA_ATTRIBUTE::PROJECT_ID] == '') {
    error_log("Missing project_id arg to remove_project_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Project ID is missing");
  }

  if (! array_key_exists(PA_ATTRIBUTE::NAME, $args) or
      $args[PA_ATTRIBUTE::NAME] == '') {
    error_log("Missing name arg to remove_project_attribute");
    return generate_response(RESPONSE_ERROR::ARGS, null,
			     "Name is missing");
  }

  $conn = db_conn();

  // define variables
  $project_id = $args[PA_ATTRIBUTE::PROJECT_ID];
  $name = $args[PA_ATTRIBUTE::NAME];

  // insert
  $sql = ("delete from " . $PA_PROJECT_ATTRIBUTE_TABLENAME . " WHERE "
          . PA_ATTRIBUTE::PROJECT_ID . " = "
          . $conn->quote($project_id, 'text') . " AND " 
          . PA_ATTRIBUTE::NAME . " = "
          . $conn->quote($name, 'text'));
  $result = db_execute_statement($sql);
  return $result;
}


/*----------------------------------------------------------------------
 * SA API Methods
 *----------------------------------------------------------------------
 */

/* Create a slice credential and return it */
function get_slice_credential($args)
{
  /* site settings */
  global $sa_authority_cert;
  global $sa_authority_private_key;
  global $sa_gcf_include_path;

  sa_expire_slices();
  geni_syslog(GENI_SYSLOG_PREFIX::SA, "get_slice_credential()");

  /* Extract method arguments. */
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $experimenter_cert = $args[SA_ARGUMENT::EXP_CERT];

  /* Locate relevant info about the slice. */
  $slice_row = fetch_slice_by_id($slice_id);
  $slice_is_expired_raw = $slice_row[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
  $slice_is_expired = convert_boolean($slice_is_expired_raw);
  if ($slice_is_expired) {
    $msg = "Slice $slice_id is expired.";
    return generate_response(RESPONSE_ERROR::ARGS, '', $msg);
  }
  $slice_cert = $slice_row[SA_SLICE_TABLE_FIELDNAME::CERTIFICATE];
  $expiration = strtotime($slice_row[SA_SLICE_TABLE_FIELDNAME::EXPIRATION]);

  $slice_cred = create_slice_credential($slice_cert,
                                        $experimenter_cert,
                                        $expiration,
                                        $sa_authority_cert,
                                        $sa_authority_private_key);

  geni_syslog(GENI_SYSLOG_PREFIX::SA, "get_slice_credential() " . 
	      "returning $slice_cred");
  $result = array(SA_ARGUMENT::SLICE_CREDENTIAL => $slice_cred);
  return generate_response(RESPONSE_ERROR::NONE, $result, '');
}

/* Create a user credential and return it */
function get_user_credential($args)
{
  /* site settings */
  global $sa_authority_cert;
  global $sa_authority_private_key;
  global $sa_gcf_include_path;

  sa_expire_slices();
  /* Extract method arguments. */
  $experimenter_cert = $args[SA_ARGUMENT::EXP_CERT];

  // FIXME: Refuse to issue this credential if this experimenter cert 
  // is not one of ours

  // FIXME: Parameterize
  $expiration = get_future_date(30)->getTimestamp(); // 30 days increment

  $user_cred = create_user_credential($experimenter_cert,
				      $expiration,
				      $sa_authority_cert,
				      $sa_authority_private_key);

  $result = array(SA_ARGUMENT::USER_CREDENTIAL => $user_cred);
  return generate_response(RESPONSE_ERROR::NONE, $result, '');
}

/**
 * Create a slice for given project, name, urn, owner_id.
 *
 * @param array $args array of key/value pairs
 *  <ul>
 *   <li><b>slice_name</b>: The name of the slice</li>
 *   <li><b>project_id</b>: The id of the parent project</li>
 *   <li><b>project_name</b>: The name of the parent project</li>
 *   <li><b>owner_id</b>: The id of owner (creator) of the slice</li>
 *   <li><b>slice_description</b> (optional): Text description of slice
 *  </ul>
 * @param opaque $message A message abstraction
 * @return string a block describing the slice
 */
function create_slice($args, $message)
{
  global $SA_SLICE_TABLENAME;
  global $sa_slice_cert_life_days;
  global $sa_authority_cert;
  global $sa_authority_private_key;
  global $sa_default_slice_expiration_hours;
  global $cs_url;
  global $mysigner;
  global $in_sundown_mode;
  global $maintenance_sundown_time;

  /* Expire slices */
  sa_expire_slices();

  $slice_name = $args[SA_ARGUMENT::SLICE_NAME];
  $project_id = $args[SA_ARGUMENT::PROJECT_ID];
  $project_name = $args[SA_ARGUMENT::PROJECT_NAME];
  $owner_id = $args[SA_ARGUMENT::OWNER_ID];
  $description = "";
  if (array_key_exists(SA_ARGUMENT::SLICE_DESCRIPTION, $args)) {
    $description = $args[SA_ARGUMENT::SLICE_DESCRIPTION];
  }
  $slice_id = make_uuid();

  if (! isset($project_id) || is_null($project_id) || $project_id == '') {
    error_log("Empty project id to create_slice " . $slice_name);
    geni_syslog(GENI_SYSLOG_PREFIX::SA, "Create slice error: no project id");
    return generate_response(RESPONSE_ERROR::DATABASE, null, 
			     "Cannot create slice without a valid project ID");
  }

  if (! isset($project_name) || is_null($project_name) || 
      $project_name == '') 
    {
      error_log("Empty project name to create_slice " . $slice_name);
      geni_syslog(GENI_SYSLOG_PREFIX::SA, 
		  "Create slice error: no project name");
      return generate_response(RESPONSE_ERROR::DATABASE, null,
			       "Cannot create slice without a " . 
			       "valid project name");
    }

  if (! isset($owner_id) || is_null($owner_id) || $owner_id == '') 
    {
      error_log("Empty owner id to create_slice " . $slice_name);
      geni_syslog(GENI_SYSLOG_PREFIX::SA, "Create slice error: no owner id");
      return generate_response(RESPONSE_ERROR::DATABASE, null, 
			       "Cannot create slice without a valid owner ID");
    }

  if((!isset($slice_name)) || 
     (is_null($slice_name)) || 
     ($slice_name == '') ||
     (!is_valid_slice_name($slice_name)))
    {
      error_log("Illegal slice name $slice_name");
      geni_syslog(GENI_SYSLOG_PREFIX::SA, 
		  "Create slice error: invalid slice name \"$slice_name\"");
      $errmess = 
	"Cannot create slice with invalid slice name $slice_name. " . 
	"Use only alphanumeric plus hyphen (no leading hyphen), " . 
	"and at most 19 characters.";
      return generate_response(RESPONSE_ERROR::DATABASE, null, $errmess);
    }

    $conn = db_conn();

    $exists_sql = "select count(*) from " . $SA_SLICE_TABLENAME 
      . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME 
      . " = " . $conn->quote($slice_name, 'text')
      . " AND " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID 
      . " = " . $conn->quote($project_id, 'text')
      . " AND NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED;
    //  error_log("SQL = " . $exists_sql);
    $exists_response = db_fetch_row($exists_sql);
    //  error_log("Exists " . print_r($exists_response, true));
    $exists = $exists_response[RESPONSE_ARGUMENT::VALUE];
    $exists = $exists['count'];
    if ($exists > 0) {
      geni_syslog(GENI_SYSLOG_PREFIX::SA, 
		  "Create slice error: slice name \"$slice_name\" " . 
		  "already exists in this project.");
      return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, 
			       "Slice of name " . $slice_name 
			       . " already exists in this project.");
    }

    /*
     * A note about slice email. We don't have it yet. In the absence
     * of a slice email, stop making one up in the "example.com"
     * domain. It's obviously fake, and is causing problems at FOAM.
     * For now, set it to null. In the future, when we have the
     * capability, make it real.
     */
    //$slice_email = 'slice-' . $slice_name . '@example.com';
    $slice_email = null;
    $slice_cert = create_slice_certificate($project_name, $slice_name,
					   $slice_email, $slice_id,
					   $sa_slice_cert_life_days,
					   $sa_authority_cert,
					   $sa_authority_private_key);

    $slice_urn = urn_from_cert($slice_cert);

  $expiration = get_future_date(0, $sa_default_slice_expiration_hours);

  // if in sundown mode, limit the expiration to the maintenance sundown time
  if($in_sundown_mode) {
    if ($maintenance_sundown_time === FALSE) {
      error_log("Maintenance sundown time was not parsable?!");
    } else if ($expiration > $maintenance_sundown_time) {
      $expiration = $maintenance_sundown_time;
    }
  }

    $project_details = 
      lookup_project(array(PA_ARGUMENT::PROJECT_ID => $project_id));
    if($project_details[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $project_details;
    $project_details = $project_details[RESPONSE_ARGUMENT::VALUE];
    $expiration = get_future_date(0, $sa_default_slice_expiration_hours);
    $project_expiration = 
      $project_details[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
    if ($project_expiration) {
      // If there is a project expiration, check slice expiration against it.
      $project_expiration = 
	new DateTime($project_details[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION]);
      geni_syslog(GENI_SYSLOG_PREFIX::SA, 
		  "Default slice expiration is " . 
		  $expiration->format(DateTime::RFC3339));
      geni_syslog(GENI_SYSLOG_PREFIX::SA, 
		  "Project expiration is " . 
		  $project_expiration->format(DateTime::RFC3339));
      $now = new DateTime();
      if ($project_expiration < $now) {
	// Project is expired, do not create...
	return generate_response(RESPONSE_ERROR::ARGS, "", 
				 "Project $project_name has expired.");
      } else if ($expiration > $project_expiration) {
	geni_syslog(GENI_SYSLOG_PREFIX::SA, 
		    "Adjusting slice expiration to " . 
		    $project_expiration->format(DateTime::RFC3339));
	$expiration = $project_expiration;
      }
      geni_syslog(GENI_SYSLOG_PREFIX::SA, 
		  "Slice expiration is " . 
		  $expiration->format(DateTime::RFC3339));
    }
    $creation = new DateTime(null, new DateTimeZone('UTC'));

    $sql = "INSERT INTO " 
      . $SA_SLICE_TABLENAME 
      . " ( "
      . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
      . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
      . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
      . SA_SLICE_TABLE_FIELDNAME::SLICE_URN . ", "
      . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
      . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
      . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
      . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
      . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
      . SA_SLICE_TABLE_FIELDNAME::CERTIFICATE . ") "
      . " VALUES (" 
      . $conn->quote($slice_id, 'text') . ", "
      . $conn->quote($slice_name, 'text') . ", "
      . $conn->quote($project_id, 'text') . ", "
      . $conn->quote($slice_urn, 'text') . ", "
      . $conn->quote(db_date_format($creation), 'timestamp') . ", "
      . $conn->quote(db_date_format($expiration), 'timestamp') . ", "
      . $conn->quote($owner_id, 'text') . ", "
      . $conn->quote($description, 'text') . ", "
      . $conn->quote($slice_email, 'text') . ", "
      . $conn->quote($slice_cert, 'text') . ") ";
 
    $db_result = db_execute_statement($sql);

    // Return the standard info about the slice.
    $slice_info = lookup_slice(array(SA_ARGUMENT::SLICE_ID => $slice_id));

    // slice_info is a response triple
    // Check for errors. If any, return slice_info as is
    if ($slice_info[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      error_log("create_slice got error from lookup_slice " . $slice_id 
		. ": " . $slice_info[RESPONSE_ARGUMENT::OUTPUT]);
      return $slice_info;
    }

    // Create an assertion that this owner is the 'lead' of the slice 
    // (and has associated privileges)
    global $cs_url;
    global $mysigner;
    $signer = $message->signerUuid();

    // Now add the lead as a member of the slice
    $asm_args = 
      array(SA_ARGUMENT::SLICE_ID => $slice_id, 
	    SA_ARGUMENT::MEMBER_ID => $owner_id, 
	    SA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD);
    $addres = add_slice_member($asm_args, $message);
    if (! isset($addres) || is_null($addres) || 
	! array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) || 
	$addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) 
      {
	error_log("create_slice failed to add lead as a slice member: " 
		  . $addres[RESPONSE_ARGUMENT::CODE] . ": " 
		  . $addres[RESPONSE_ARGUMENT::OUTPUT]);
	// FIXME: ROLLBACK?
	return $addres;
      }

    // If the user is not the project lead, make the project lead
    // a member of the project with 'ADMIN' priviileges

    // Check if project lead is same as member
    if (!isset($project_details) ||
	is_null($project_details) ||
	!array_key_exists(PA_PROJECT_TABLE_FIELDNAME::LEAD_ID, 
			  $project_details))
      {
	error_log("Error looking up details for project_id $project_id");
	return $project_details;
      }
    //  error_log("PROJECT_DETAILS = " . print_r($project_details, true));
    $project_lead_id = $project_details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    
    // If not, 
    if ($project_lead_id != $owner_id) {

      //    error_log("PL $project_lead_id is not OWNER $owner_id");
      // Create assertion of lead membership
      $ca_res = create_assertion($cs_url, $mysigner, $signer, 
				 $project_lead_id, 
				 CS_ATTRIBUTE_TYPE::ADMIN,
				 CS_CONTEXT_TYPE::SLICE, $slice_id);
      if ($ca_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	return $ca_res;
      

      // add project lead as 'ADMIN' slice member 
      $addres = 
	add_slice_member(array(SA_ARGUMENT::SLICE_ID => $slice_id,
			       SA_ARGUMENT::MEMBER_ID => $project_lead_id,
			       SA_ARGUMENT::ROLE_TYPE =>
			       CS_ATTRIBUTE_TYPE::ADMIN),
			 $message);
      if (!isset($addres) ||
	  is_null($addres) ||
	  !array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) ||
	  $addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	{
	  error_log("Create slice failed to add project lead as " . 
		    "slice member: " .
		    $addres[RESPONSE_ARGUMENT::CODE] . ": " .
		    $addres[RESPONSE_ARGUMENT::OUTPUT]);
	  return $addres;
	}
    }

    // Add all project ADMINs as members of the slice as well
    // get all project admins
    $admins = array();
    $admins_res = get_project_members(array(PA_ARGUMENT::PROJECT_ID => $project_id,
					    PA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::ADMIN),
				      $message);
    if (! isset($admins_res) || is_null($admins_res) || 
	! array_key_exists(RESPONSE_ARGUMENT::CODE, $admins_res) ||
	$admins_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE ||
	! array_key_exists(RESPONSE_ARGUMENT::VALUE, $admins_res)) {
      error_log("Create slice failed to get admins in project: " . 
		$admins_res[RESPONSE_ARGUMENT::CODE] . ": " . 
		$admins_res[RESPONSE_ARGUMENT::OUTPUT]);
    } else {
      $admins = $admins_res[RESPONSE_ARGUMENT::VALUE];
      if (is_null($admins) || count($admins) <= 0) {
	//	error_log("Create slice: No project admins found in project " . $project_name);
	$admins = array();
      }
    }

    // For each admin of the project
    foreach ($admins as $project_admin) {
      $project_admin_id = $project_admin[PA_ARGUMENT::MEMBER_ID];

      // If not the slice owner, then add them
      if ($project_admin_id != $owner_id) {

	//    error_log("PL $project_admin_id is not OWNER $owner_id");
	// Create assertion of admin membership
	$ca_res = create_assertion($cs_url, $mysigner, $signer, 
				   $project_admin_id, 
				   CS_ATTRIBUTE_TYPE::ADMIN,
				   CS_CONTEXT_TYPE::SLICE, $slice_id);
	if ($ca_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	  error_log("Create slice failed to make a project admin a slice admin: " .
		$ca_res[RESPONSE_ARGUMENT::CODE] . ": " . 
		$ca_res[RESPONSE_ARGUMENT::OUTPUT]);
	  continue;
	}

	// add project admin as 'ADMIN' slice member 
	$addres = 
	  add_slice_member(array(SA_ARGUMENT::SLICE_ID => $slice_id,
				 SA_ARGUMENT::MEMBER_ID => $project_admin_id,
				 SA_ARGUMENT::ROLE_TYPE =>
				 CS_ATTRIBUTE_TYPE::ADMIN),
			   $message);
	if (!isset($addres) ||
	    is_null($addres) ||
	    !array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) ||
	    $addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	  {
	    error_log("Create slice failed to add project admin as " . 
		      "slice member: " .
		      $addres[RESPONSE_ARGUMENT::CODE] . ": " .
		      $addres[RESPONSE_ARGUMENT::OUTPUT]);
	    continue;
	  }
      }
    } // end of loop over project admins

    // Log the creation
    global $log_url;
    global $mysigner;
    $project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
						    $project_id);
    $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
						  $slice_id);
    $attributes = array_merge($project_attributes, $slice_attributes);
    log_event($log_url, $mysigner, "Created slice " . $slice_name, 
	      $attributes, $owner_id);
    geni_syslog(GENI_SYSLOG_PREFIX::SA, "Created slice $slice_name for " . 
		"owner $owner_id in project $project_id");

    //  slice_info is already a response_triple from the 
    // lookup_slice call above
    //  error_log("SA.create_slice final return is " . 
    //    print_r($slice_info, true));
    return $slice_info;
}

function lookup_slice_ids($args)
{
  global $SA_SLICE_TABLENAME;
  sa_expire_slices();
  if (array_key_exists(SA_ARGUMENT::PROJECT_ID, $args)) {
    $project_id = $args[SA_ARGUMENT::PROJECT_ID];
    //    error_log("Got pid $project_id\n");
  }
  if (array_key_exists(SA_ARGUMENT::OWNER_ID, $args)) {
    $owner_id = $args[SA_ARGUMENT::OWNER_ID];
    //    error_log("Got oid $owner_id\n");
  }
  if (array_key_exists(SA_ARGUMENT::SLICE_NAME, $args)) {
    $slice_name = $args[SA_ARGUMENT::SLICE_NAME];
  }

  $conn = db_conn();
  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED;
  if (isset($project_id)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID .
      " = " . $conn->quote($project_id, 'text');
  }
  if (isset($owner_id)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::OWNER_ID .
      " = " . $conn->quote($owner_id, 'text');
  }
  if (isset($slice_name)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME .
      " = " . $conn->quote($slice_name, 'text');
  }
  $sql = $sql . " ORDER BY " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . 
    ", " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID;

  //  error_log("LOOKUP_SLICES.SQL = " . $sql);
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    $rows = $result[RESPONSE_ARGUMENT::VALUE];
    //  error_log("LOOKUP_SLICES.ROWS = " . print_r($rows, true));
    $slice_ids = array();
    foreach ($rows as $row) {
      //    error_log("LOOKUP_SLICES.ROW = " . print_r($row, true));
      $slice_id = $row[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
      //    error_log("LOOKUP_SLICES.SID = " . print_r($slice_id, true));
      $slice_ids[] = $slice_id;
    }
    return generate_response(RESPONSE_ERROR::NONE, $slice_ids, '');
  } else
    return $result;
}

function lookup_slices($args, $message)
{
  global $SA_SLICE_TABLENAME;
  global $SA_SLICE_MEMBER_TABLENAME;

  sa_expire_slices();
  $project_id = $args[SA_ARGUMENT::PROJECT_ID];
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];

  /* FIXME: This is where an authorization guard should go. */
  if (is_null($message->signer())) {
    error_log("No signer on SA.lookup_slices."
              . " Proceeding without authorization.");
    /* return generate_response(RESPONSE_ERROR::ARGS, */
    /*                          NULL, */
    /*                          "No message signer UUID available."); */
  }

  $conn = db_conn();

  $project_id_clause = '';
  if ($project_id <> null) {
    $project_id_clause = SA_SLICE_TABLE_FIELDNAME::PROJECT_ID
      . " = " . $conn->quote($project_id, 'text');
  }

  $member_id_clause = '';
  if ($member_id <> null) {
    $member_id_clause = " " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID ." IN " . 
      "(SELECT " . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . 
      " FROM " . $SA_SLICE_MEMBER_TABLENAME . 
      " WHERE " . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = " .
      $conn->quote($member_id, 'text') . ")";
  }

  $where_clause = " WHERE NOT EXPIRED";
  if ($project_id <> null)  {
    $where_clause .= " AND $project_id_clause";
  }
  if ($member_id <> null)  {
    $where_clause .= " AND $member_id_clause";
  }

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRED . ", "
    . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME
    . $where_clause;

  //  error_log("lookup_slices.sql = " . $sql);

  $rows = db_fetch_rows($sql);

  return $rows;
  
}

function lookup_slice($args)
{
  // FIXME: use sa_utils::fetch_slice_by_id and then
  // filter columns before returning (we don't need everything!)

  global $SA_SLICE_TABLENAME;

  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $conn = db_conn();

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN . ", " 
    . SA_SLICE_TABLE_FIELDNAME::EXPIRED 
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " = " . $conn->quote($slice_id, 'text');
  //  error_log("LOOKUP_SLICE.SQL = " . $sql);
  $row = db_fetch_row($sql);
  // error_log("LOOKUP_SLICE.ROW = " . print_r($row, true));
  return $row;
}

function lookup_slice_by_urn($args)
{
  // FIXME: create sa_utils::fetch_slice_by_urn and then
  // filter columns before returning (we don't need everything!)

  global $SA_SLICE_TABLENAME;

  sa_expire_slices();
  $slice_urn = $args[SA_ARGUMENT::SLICE_URN];
  $conn = db_conn();

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRED . ", "
    . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::CERTIFICATE . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_URN
    . " = " . $conn->quote($slice_urn, 'text')
    . " AND NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED;
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] !== RESPONSE_ERROR::NONE) {
    $msg = "SA.lookup_slice_by_urn error: " . 
      $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::SA, $msg);
    return $result;
  }
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  $numrows = count($rows);
  switch ($numrows) {
  case 0:
    // Mimic db_fetch_row, return success, but no results.
    return generate_response(RESPONSE_ERROR::NONE, null, null);
    break;
  case 1:
    return generate_response(RESPONSE_ERROR::NONE, $rows[0], null);
    break;
  default:
    // This is a sanity check case. We should never have more 
    // than one active slice
    // for a given URN. If we do, something is amiss. Log the condition and
    // return an error to the client.
    $msg = "SA.lookup_slice_by_urn error: multiple slices " . 
      "found for URN $slice_urn";
    geni_syslog(GENI_SYSLOG_PREFIX::SA, $msg);
    return generate_response(RESPONSE_ERROR::DATABASE, null, 
			     "Too many slices found.");
    break;
  }
}

function renew_slice($args, $message)
{
  global $SA_SLICE_TABLENAME;
  global $sa_max_slice_renewal_days;
  global $log_url;
  global $mysigner;
  sa_expire_slices();

  if (! key_exists(SA_ARGUMENT::SLICE_ID, $args)) {
    $msg = "Required argument " . SA_ARGUMENT::SLICE_ID . " is missing.";
    return generate_response(RESPONSE_ERROR::ARGS, '', $msg);
  }
  if (! key_exists(SA_ARGUMENT::EXPIRATION, $args)) {
    $msg = "Required argument " . SA_ARGUMENT::EXPIRATION . " is missing.";
    return generate_response(RESPONSE_ERROR::ARGS, '', $msg);
  }

  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $requested = $args[SA_ARGUMENT::EXPIRATION];

  // Is slice expired?
  $slice_row = fetch_slice_by_id($slice_id);
  $slice_is_expired = 
    convert_boolean($slice_row[SA_SLICE_TABLE_FIELDNAME::EXPIRED]);
  if ($slice_is_expired) {
    $msg = "Slice $slice_id is expired.";
    return generate_response(RESPONSE_ERROR::ARGS, '', $msg);
  }

  // error_log("got req $requested");
  try {
    $req_dt = new DateTime($requested);
  } catch (Exception $e) {
    $msg = "Requested expiration time not recognized: $requested";
    return generate_response(RESPONSE_ERROR::ARGS, '', $msg);
  }
  $tz_utc = new DateTimeZone('UTC');
  $req_dt->setTimezone($tz_utc);

  global $in_sundown_mode;
  global $maintenance_sundown_time;
  global $maintenance_sundown_message;

  //  error_log("SUNDOWN " . print_r($in_sundown_mode, true) . " TIME " . print_r($maintenance_sundown_time, true) . " MSG " . print_r($maintenance_sundown_message, true));
  if($in_sundown_mode) {
    if ($maintenance_sundown_time === FALSE) {
      error_log("Maintenance sundown time was not parsable?! Message was " . $maintenance_sundown_message);
    } else if ($maintenance_sundown_time < $req_dt) {
      return generate_response(RESPONSE_ERROR::ARGS, '', $maintenance_sundown_message);
    }
  }

  // Is requested expiration >= current expiration?
  $slice_expiration = $slice_row[SA_SLICE_TABLE_FIELDNAME::EXPIRATION];
  $slice_expiration_dt = new DateTime($slice_expiration, $tz_utc);
  if ($req_dt < $slice_expiration_dt) {
    $msg = "Invalid expiration time \"$requested\". " . 
      "Cannot shorten slice expiration.";
    return generate_response(RESPONSE_ERROR::ARGS, '', $msg);
  }

  // Limit to project expiration
  $project_id = $slice_row[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
  $project_details = 
    lookup_project(array(PA_ARGUMENT::PROJECT_ID => $project_id));
  if($project_details[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $project_details;
  $project_details = $project_details[RESPONSE_ARGUMENT::VALUE];
  $project_expiration = 
    $project_details[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
  if ($project_expiration) {
    $project_expiration_dt = new DateTime($project_expiration);
    if ($req_dt > $project_expiration_dt) {
      $msg = "Requested expiration \"$requested\" exceeds project " . 
	"expiration \"$project_expiration\"";
      return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
    }
  }

  // Limit to max expiration window (next N days)
  // 20 days increment
  $max_expiration_dt = get_future_date($sa_max_slice_renewal_days);
  if ($req_dt > $max_expiration_dt) {
    $max_expiration = $max_expiration_dt->format(DateTime::RFC3339);
    $msg = "Requested expiration \"$requested\" exceeds maximum limit " . 
      "of \"$max_expiration\"";
    return generate_response(RESPONSE_ERROR::ARGS, "", $msg);
  }

  // Requested expiration has passed all tests. Update the slice.
  $expiration = $req_dt->format(DateTime::RFC3339);
  geni_syslog(GENI_SYSLOG_PREFIX::SA, "Renewing slice $slice_id " . 
	      "to $expiration");
  $conn = db_conn();
  $sql = "UPDATE " . $SA_SLICE_TABLENAME 
    . " SET " . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . " = "
    . $conn->quote(db_date_format($req_dt), 'timestamp')
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID 
    . " = " . $conn->quote($slice_id, 'text');

  //  error_log("RENEW.sql = " . $sql);
  $result = db_execute_statement($sql);

  // Log the renewal
  $slice_info = lookup_slice(array(SA_ARGUMENT::SLICE_ID => $slice_id));
  if($slice_info[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
    return $slice_info;
    
    
  $slice_info_modified = $slice_info[RESPONSE_ARGUMENT::VALUE];
  $slice_name = 
    $slice_info_modified[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
  $new_expiration = 
    $slice_info_modified[SA_SLICE_TABLE_FIELDNAME::EXPIRATION];
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, $slice_id);
  log_event($log_url, $mysigner,
	    "Renewed slice $slice_name until $new_expiration",
	    $attributes,
	    $message->signerUuid());
  geni_syslog(GENI_SYSLOG_PREFIX::SA,
	      "Renewed slice $slice_id until $new_expiration");
  return $slice_info;
}

// Modify slice membership according to given lists of add/change_role/remove
// $members_to_add and $members_to_change are both
//    dictionaries of (member_id => role, ...)
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
// Note: This is all done in a transaction, 
//   so the inserts, updates and deletes all happen atomically.
//    
function modify_slice_membership($args, $message)
{
  // Unpack arguments
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $members_to_add = $args[SA_ARGUMENT::MEMBERS_TO_ADD];
  $members_to_change_role = $args[SA_ARGUMENT::MEMBERS_TO_CHANGE_ROLE];
  $members_to_remove = $args[SA_ARGUMENT::MEMBERS_TO_REMOVE];

  //  error_log("MTA = " . print_r($members_to_add, true));
  //  error_log("MTC = " . print_r($members_to_change_role, true));
  //  error_log("MTR = " . print_r($members_to_remove, true));


  // Get the members of the slice by role
  $slice_members = 
    get_slice_members(array(SA_ARGUMENT::SLICE_ID => $slice_id));
  if ($slice_members[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    return $slice_members;
  }
  $slice_members = $slice_members[RESPONSE_ARGUMENT::VALUE];

  // Determine slice lead
  $slice_lead = null;
  $member_change_allowed = false;
  $my_uid = $message->signerUuid();
  foreach($slice_members as $slice_member) {
    $member_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    $role = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE];
    if ($role == CS_ATTRIBUTE_TYPE::LEAD) {
      $slice_lead = $member_id;
      if ($my_uid === $member_id) {
	$member_change_allowed = true;
	break;
      }
    }
    if ($role == CS_ATTRIBUTE_TYPE::ADMIN) {
      if ($my_uid === $member_id) {
	$member_change_allowed = true;
	if (! is_null($slice_lead)) {
	  break;
	}
      }
    }
  }
  if (!$member_change_allowed) {
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, "Only a lead or admin can change slice membership");
  }

  // Must be a slice lead, else something is wrong with slice
  if($slice_lead == null) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Bad slice: no lead");
  }

  // First validate the arguments
  // No new members should be already a slice member
  if (!already_in_list(array_keys($members_to_add), 
		       array_keys($slice_members), true)) 
    {
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Can't add member to a slice to which they " . 
			       "already belong");
    }

  // No new roles for members who aren't slice members
  if (!already_in_list(array_keys($members_to_change_role), 
		       array_keys($slice_members), false)) 
    {
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Can't change member role for member " . 
			       "not in slice");
    }

  // Can't remove members who aren't slice members
  if (!already_in_list(array_keys($members_to_remove), 
		       array_keys($slice_members), false)) 
    {
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Can't remove member from slice if " .  
			       "not a member");
    }

  $new_slice_lead = $slice_lead;
  // Count up the total lead changes. Should be zero
  $lead_changes = 0;
  $new_lead = null;
  foreach($members_to_add as $member_to_add => $new_role) {
    if($new_role == CS_ATTRIBUTE_TYPE::LEAD) {
      $lead_changes = $lead_changes + 1;
      $new_lead = $member_to_add;
    }    
  }
  foreach($members_to_change_role as $member_to_change_role => $role) {
    if ($role == CS_ATTRIBUTE_TYPE::LEAD && $member_to_change_role != $slice_lead) {
      $lead_changes = $lead_changes + 1;
      $new_lead = $member_to_change_role;
    }
    if ($member_to_change_role == $slice_lead && $role != CS_ATTRIBUTE_TYPE::LEAD)
      $lead_changes = $lead_changes - 1;
  }

  foreach($members_to_remove as $member_to_remove) {
    if($member_to_remove == $slice_lead)
      $lead_changes = $lead_changes - 1;
  }

  if($lead_changes != 0) {
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Must have exactly one slice lead");
  }

  // This code handles the case wherein we are changing the slice lead
  if ($new_slice_lead != $slice_lead) {

    // Change the 'owner_id' field of the slice
    $cso_result = change_slice_owner($slice_id, $new_slice_lead);
    if ($cso_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $cso_result;

  }

  // There is a problem of transactional integrity here
  // The SA keeps its own table of members/roles, 
  // and the CS keeps a table of assertions which
  // are essentially redundant. Ultimately, these should be 
  // unified and probably kept in the CS
  // and the CS should allow for a bundling 
  // (writing multiple adds/deletes at once)
  // 
  // But for now we maintain the two sets of tables, 
  // writing the sa_slice_member_table atomically
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
      $asm_args = array(SA_ARGUMENT::SLICE_ID => $slice_id, 
			SA_ARGUMENT::MEMBER_ID => $member_to_add,
			SA_ARGUMENT::ROLE_TYPE => $role);
      $result = add_slice_member($asm_args, $message);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	$success = False;
	$error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
      }
    }
  }

  // Change the slice owner
  if ($success and ! is_null($new_lead)) {
    global $SA_SLICE_TABLENAME;
    $sql = "UPDATE " . $SA_SLICE_TABLENAME
      . " SET "
      . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . " = " . $conn->quote($new_lead, 'text')
      . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
      . " = " . $conn->quote($slice_id, 'text');
  //  error_log("CHANGE_LEAD.sql = " . $sql);
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      $success = False;
      $error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
    }
  }

  // Change roles of existing members
  if($success) {
    foreach($members_to_change_role as $member_to_change_role => $role) {
      $csmr_args = array(SA_ARGUMENT::SLICE_ID => $slice_id,
			 SA_ARGUMENt::MEMBER_ID => $member_to_change_role,
			 SA_ARGUMENT::ROLE_TYPE => $role);
      $result = change_slice_member_role($csmr_args,  $message);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	$success = False;
	$error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
      }
    }
  }

  // Remove members
  if ($success) {
    foreach($members_to_remove as $member_to_remove) {
      $rsm_args = array(SA_ARGUMENT::SLICE_ID => $slice_id,
			SA_ARGUMEnt::MEMBER_ID => $member_to_remove);
      $result = remove_slice_member($rsm_args, $message);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	$success = False;
	$error_message = $result[RESPONSE_ARGUMENT::OUTPUT];
      }
    }
  }

  // If all the SA and CS transactions are successful, 
  // then commit, otherwise rollback
  if(!$success) {
    // One of the writes failed. Rollback the whole thing
    $conn->rollback();
    return generate_response(RESPONSE_ERROR::DATABASE, null, $error_message);
  } else {
    // All succeeded, commit SA changes and return success
    $conn->commit();
    return generate_response(RESPONSE_ERROR::NONE, null, '');
  }

}

/* update owner_id of given slice */
function change_slice_owner($slice_id, $new_slice_lead)
{
  global $SA_SLICE_TABLENAME;

  sa_expire_slices();

  $conn = db_conn();
  $sql = "UPDATE " . $SA_SLICE_TABLENAME
    . " SET "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . " = " . 
    $conn->quote($new_slice_lead, 'text')
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " = " . $conn->quote($slice_id, 'text');
  //  error_log("CHANGE_SLICE_OWNER.sql = " . $sql);
  $result = db_execute_statement($sql);

  return $result;
}



// Add a member of given role to given slice
// internal only method
function add_slice_member($args, $message)
{
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];
  $role = $args[SA_ARGUMENT::ROLE_TYPE];

  global $SA_SLICE_MEMBER_TABLENAME;
  global $mysigner;
  global $ma_url;
  $conn = db_conn();

  $already_member_sql = "select count(*) from " . $SA_SLICE_MEMBER_TABLENAME
    . " WHERE " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . " = " . $conn->quote($slice_id, 'text')
    . " AND " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = " . $conn->quote($member_id, 'text');
  $already_member = db_fetch_row($already_member_sql);
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  $already_member = $already_member['value']['count'] > 0;
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if ($already_member) {
    // get member name
    $ids = array();
    $ids[] = $member_id;
    $names = lookup_member_names($ma_url,$mysigner,$ids);
    if ($names[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $names;
    $names = $names[RESPONSE_ARGUMENT::VALUE];
    $member_name = $names[$member_id];
    $lookup_slice_message = array(SA_ARGUMENT::SLICE_ID => $slice_id);
    $slice_data = lookup_slice($lookup_slice_message);
    $slice_data = $slice_data[RESPONSE_ARGUMENT::VALUE];
    $slice_name = $slice_data[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Member $member_name is already a member " . 
			     "of slice $slice_name");
  }

  $sql = "INSERT INTO " . $SA_SLICE_MEMBER_TABLENAME . " ("
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE . ") VALUES ("
    . $conn->quote($slice_id, 'text') . ", "
    . $conn->quote($member_id, 'text') . ", "
    . $conn->quote($role, 'integer') . ")";
  //  error_log("SA.add slice_member.sql = " . $sql);
  $result = db_execute_statement($sql);

  // If successful, add an assertion to remove the role's 
  // privileges within the CS store
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    /* FIXME - The signer needs to have a certificate and private key. 
     * Who sends this message (below)
     * to the CS? Is the PA the signer?
     */
    $signer = $message->signerUuid();
    $ca_res = create_assertion($cs_url, $mysigner, $signer, $member_id, 
			       $role, CS_CONTEXT_TYPE::SLICE, $slice_id);
    if ($ca_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $ca_res;
  }

  // Log adding the member
  global $ma_url;
  $member_data = ma_lookup_member_by_id($ma_url, $mysigner, $member_id);
  if ($member_data == null) 
    return generate_response(RESPONSE_ERROR::ARGS, null, 
			     "Unknown member " . $member_id);

  $lookup_slice_message = array(SA_ARGUMENT::SLICE_ID => $slice_id);
  $slice_data = lookup_slice($lookup_slice_message);
  if(($slice_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
     (array_key_exists(SA_SLICE_TABLE_FIELDNAME::SLICE_NAME,
                       $slice_data[RESPONSE_ARGUMENT::VALUE])))
    {
      global $CS_ATTRIBUTE_TYPE_NAME;
      global $log_url;
      $slice_data = $slice_data[RESPONSE_ARGUMENT::VALUE];
      $member_name = $member_data->prettyName();
      $slice_name = $slice_data[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
      $project_id = $slice_data[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
      $role_name = $CS_ATTRIBUTE_TYPE_NAME[$role];
      $message = "Added $member_name to Slice $slice_name in role $role_name";
      $project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT,
                                                      $project_id);
      $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE,
                                                    $slice_id);
      $attributes = array_merge($project_attributes, $slice_attributes);
      log_event($log_url, $mysigner, $message, $attributes, $signer);
    }

  return $result;
}

// Remove a member from given slice 
// internal only method
function remove_slice_member($args, $message)
{
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];

  global $SA_SLICE_MEMBER_TABLENAME;
  global $mysigner;
  global $ma_url;
  global $log_url;
  $conn = db_conn();

  $sql = "DELETE FROM " . $SA_SLICE_MEMBER_TABLENAME 
    . " WHERE " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID  
    . " = " . $conn->quote($slice_id, 'text') . " AND "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = " . $conn->quote($member_id, 'text');
  //  error_log("SA.remove slice_member.sql = " . $sql);
  $result = db_execute_statement($sql);

  // Delete previous assertions from CS
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $signer = $message->signerUuid();

    $lookup_slice_message = array(SA_ARGUMENT::SLICE_ID => $slice_id);
    $slice_data = lookup_slice($lookup_slice_message);
    $slice_data = $slice_data[RESPONSE_ARGUMENT::VALUE];
    $slice_name = $slice_data[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
    $member_data = ma_lookup_member_by_id($ma_url, $mysigner, $member_id);
    if ($member_data == null) 
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Unknown member " . $member_id);
    $member_name = $member_data->prettyName();

    $message = "Removed $member_name from Slice $slice_name";
    $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
						  $slice_id);
    $member_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, 
						   $member_id);
    $attributes = array_merge($slice_attributes, $member_attributes);
    log_event($log_url, $mysigner, $message, $attributes, $signer);

    $membership_assertions = query_assertions($cs_url, $mysigner, $member_id, 
					      CS_CONTEXT_TYPE::SLICE, 
					      $slice_id);
    if ($membership_assertions[RESPONSE_ARGUMENT::CODE] != 
	RESPONSE_ERROR::NONE)
      return $membership_assertions;
    $membership_assertions = $membership_assertions[RESPONSE_ARGUMENT::VALUE];
    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      $da_res = delete_assertion($cs_url, $mysigner, $assertion_id);
      if($da_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	return $da_res;
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }
  }

  return $result;
}

// Change role of given member in given slice
// internal only method
function change_slice_member_role($args, $message)
{
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];
  $role = $args[SA_ARGUMENT::ROLE_TYPE];

  global $SA_SLICE_MEMBER_TABLENAME;
  global $mysigner;
  global $ma_url;
  global $log_url;
  $conn = db_conn();

  $sql = "UPDATE " . $SA_SLICE_MEMBER_TABLENAME
    . " SET " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE 
    . " = " . $conn->quote($role, 'integer')
    . " WHERE " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . " = " . $conn->quote($slice_id, 'text')
    . " AND " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = " . $conn->quote($member_id, 'text'); 

  //  error_log("SA.change_member_role.sql = " . $sql);
  $result = db_execute_statement($sql);


  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    global $CS_ATTRIBUTE_TYPE_NAME;
    $signer = $message->signerUuid();

    $lookup_slice_message = array(SA_ARGUMENT::SLICE_ID => $slice_id);
    $slice_data = lookup_slice($lookup_slice_message);
    $slice_data = $slice_data[RESPONSE_ARGUMENT::VALUE];
    $slice_name = $slice_data[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
    $member_data = ma_lookup_member_by_id($ma_url, $mysigner, $member_id);
    if ($member_data == null) 
      return generate_response(RESPONSE_ERROR::ARGS, null, 
			       "Unknown member " . $member_id);

    $member_name = $member_data->prettyName();
    $role_name = $CS_ATTRIBUTE_TYPE_NAME[$role];

    $message = "Changed role of $member_name on Slice $slice_name " . 
      "to $role_name";
    $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
						  $slice_id);
    $member_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER, 
						   $member_id);
    $attributes = array_merge($slice_attributes, $member_attributes);
    log_event($log_url, $mysigner, $message, $attributes, $signer);


    // Remove previous CS assertions about the member in this slice
    $membership_assertions = query_assertions($cs_url, $mysigner, 
					      $member_id, 
					      CS_CONTEXT_TYPE::SLICE, 
					      $slice_id);
    if($membership_assertions[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $membership_assertions;
    $membership_assertions = $membership_assertions[RESPONSE_ARGUMENT::VALUE];
    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      $da_res = delete_assertion($cs_url, $mysigner, $assertion_id);
      if ($da_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
	return $da_res;
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }

    // Create new assertion for member in this role
    $ca_res = create_assertion($cs_url, $mysigner, $signer, $member_id, 
			       $role, CS_CONTEXT_TYPE::SLICE, $slice_id);
    if ($ca_res[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE)
      return $ca_res;
  }

  return $result;
}

// Return list of member ID's and roles associated with given slice
// If role is provided, filter to members of given role
function get_slice_members($args)
{
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $role = null;
  if (array_key_exists(SA_ARGUMENT::ROLE_TYPE, $args) && 
      isset($args[SA_ARGUMENT::ROLE_TYPE])) 
    {
      $role = $args[SA_ARGUMENT::ROLE_TYPE];
    }

  global $SA_SLICE_MEMBER_TABLENAME;
  $conn = db_conn();

  $role_clause = "";
  if ($role != null) {
    $role_clause = 
      " AND " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE 
      . " = " . $conn->quote($role, 'integer');
  }
  $sql = "SELECT " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE
    . " FROM " . $SA_SLICE_MEMBER_TABLENAME
    . " WHERE "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . " = " . $conn->quote($slice_id, 'text')
    . $role_clause;

  //  error_log("SA.get_slice_members.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
  
}

// Return list of member ID's and roles associated with slices 
// of a given project
// If role is provided, filter to members of given role
function get_slice_members_for_project($args)
{
  sa_expire_slices();
  $project_id = $args[SA_ARGUMENT::PROJECT_ID];
  $role = null;
  if (array_key_exists(SA_ARGUMENT::ROLE_TYPE, $args) && 
      isset($args[SA_ARGUMENT::ROLE_TYPE])) 
    {
      $role = $args[SA_ARGUMENT::ROLE_TYPE];
    }

  global $SA_SLICE_MEMBER_TABLENAME;
  global $SA_SLICE_TABLENAME;
  $conn = db_conn();

  $role_clause = "";
  if ($role != null) {
    $role_clause = 
      " AND " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE 
      . " = " . $conn->quote($role, 'integer');
  }
  $sql = "SELECT " 
    . $SA_SLICE_TABLENAME . "." . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE
    . " FROM " . $SA_SLICE_MEMBER_TABLENAME
    . ", " . $SA_SLICE_TABLENAME
    . " WHERE "
    . "NOT " . $SA_SLICE_TABLENAME . "." . SA_SLICE_TABLE_FIELDNAME::EXPIRED
    . " AND " . $SA_SLICE_MEMBER_TABLENAME 
    . "." . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . " = " 
    . $SA_SLICE_TABLENAME . "." . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " AND " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID 
    . " = " . $conn->quote($project_id, 'text')
    . $role_clause;

  //error_log("SA.get_slice_members_for_project.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
  
}

// Return list of slice ID's and roles for given member_id for 
// slices to which member belongs
// If is_member is true, return slices for which member is a member
// If is_member is false, return slices for which member is NOT a member
// If role is provided, filter on slices 
//    for which member has given role (is_member = true)
//    for which member does NOT have given role (is_member = false)
function get_slices_for_member($args)
{
  sa_expire_slices();
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];
  $is_member = $args[SA_ARGUMENT::IS_MEMBER];
  $role = null;
  if (array_key_exists(SA_ARGUMENT::ROLE_TYPE, $args) && 
      isset($args[SA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[SA_ARGUMENT::ROLE_TYPE];
  }

  global $SA_SLICE_MEMBER_TABLENAME;
  $conn = db_conn();

  // select slice_id, role from pa_slice_member
  // where member_id = $member_id

  // select slice_id, role from pa_slice_member 
  // where member_id not in (select slice_id from pa_slice_member 
  //                         where member_id = $member_id)

  // select slice_id, role from pa_slice_member 
  // where member_id = $member_id and role = $role

  // select slice_id, role from pa_slice_member 
  // where member_id not in (select slice_id from pa_slice_member 
  //                         where member_id = $member_id and role = $role)

  $role_clause = "";
  if ($role != null) {
    $role_clause = " AND " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE 
      . " = " . $conn->quote($role, 'integer');
  }
  $member_clause = 
    SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = " . $conn->quote($member_id, 'text') . $role_clause;
  if(!$is_member) {
    $member_clause = 
      SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
      . " NOT IN (SELECT " 
      . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
      . " FROM " . $SA_SLICE_MEMBER_TABLENAME 
      . " WHERE " 
      . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID
      . " = " . $conn->quote($member_id, 'text') . $role_clause . ")";
      
  }

  $sql = "SELECT  " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE
    . " FROM " . $SA_SLICE_MEMBER_TABLENAME
    . " WHERE " 
    . $member_clause;

  //  error_log("SA.get_slices_for_member.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
}

function lookup_slice_details($args)
{
  sa_expire_slices();

  $slice_uuids = $args[SA_ARGUMENT::SLICE_UUIDS];
  $slice_uuids_as_sql = convert_list($slice_uuids);

  global $SA_SLICE_TABLENAME;

  $sql = "select "  
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRED . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME 
    . " WHERE " .   SA_SLICE_TABLE_FIELDNAME::SLICE_ID . " IN " .
    $slice_uuids_as_sql;
  //    error_log("lookup_slice_details: " . $sql);
  $rows = db_fetch_rows($sql);
  return $rows;
}

// Return a dictionary of the list of slices (details) for a given
// set of project uuids, indexed by project UUID
// e.g.. [p1 => [s1_details, s2_details....], p2 => [s3_details, s4_details...]
// Optionally allow slices that have expired
function get_slices_for_projects($args)
{
  $project_uuids = $args[SA_ARGUMENT::PROJECT_UUIDS];
  if (! $project_uuids) {
    // If there are no project UUIDs, return an empty array.
    return generate_response(RESPONSE_ERROR::NONE, array(), '');
  }
  $project_uuids_as_sql = convert_list($project_uuids);

  $allow_expired = $args[SA_ARGUMENT::ALLOW_EXPIRED];
  $expired_clause = "";
  if(!$allow_expired) 
    $expired_clause = "NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED . " AND ";

  sa_expire_slices();

  global $SA_SLICE_TABLENAME;
  $sql = "select "  
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRED . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME 
    . " WHERE " .   $expired_clause
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . " IN " .
    $project_uuids_as_sql;
  $rows = db_fetch_rows($sql);

  //    error_log("sql = ". $sql);
  //    error_log("gs4p details " . print_r($rows, true));

  $result = array();
  foreach($project_uuids as $project_uuid) {
    $result[$project_uuid] = array();
  }

  foreach($rows['value']  as $row) {
    $project_uuid = $row[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
    $result[$project_uuid][] = $row;
  }

  return generate_response(RESPONSE_ERROR::NONE, $result, '');
}

// Since the SA calls CS and MA functions, we need to look 
// at the result codes ourselves
function sa_message_result_handler($result) 
{
  //  error_log("SA_MESSAGE_RESULT_HANDLER.result = " . print_r($result, true));
  return $result;
}




$mycertfile = '/usr/share/geni-ch/sa/sa-cert.pem';
$mykeyfile = '/usr/share/geni-ch/sa/sa-key.pem';
$mysigner = new Signer($mycertfile, $mykeyfile);
$guard_factory = new SAGuardFactory($cs_url);
$put_message_result_handler = 'sa_message_result_handler';
handle_message("SA", $cs_url, default_cacerts(),
               $mysigner->certificate(), $mysigner->privateKey(), 
	       $guard_factory);

?>

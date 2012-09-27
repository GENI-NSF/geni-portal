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
require_once('pa_client.php');
require_once('cs_client.php');
require_once('ma_client.php');
require_once('logging_client.php');
require_once('geni_syslog.php');

$sr_url = get_sr_url();
$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
$pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

function sa_debug($msg)
{
  //  error_log('SA DEBUG: ' . $msg);
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
  $sql = "SELECT "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . " < now()"
    . " AND NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED;
  $result = db_fetch_rows($sql);
  if ($result[RESPONSE_ARGUMENT::CODE] !== RESPONSE_ERROR::NONE) {
    $msg = "sa_expire_slices error: " . $result[RESPONSE_ARGUMENT::OUTPUT];
    geni_syslog(GENI_SYSLOG_PREFIX::SA, $msg);
    return;
  }
  $rows = $result[RESPONSE_ARGUMENT::VALUE];
  $conn = db_conn();
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
    $attributes = array_merge($project_attributes, $slice_attributes);
    $log_msg = "Expired slice " . $slice_name;
    log_event($log_url, $mysigner, $log_msg, $attributes, $owner_id);
    $syslog_msg = "Expired slice $slice_id";
    geni_syslog(GENI_SYSLOG_PREFIX::SA, $syslog_msg);
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
            'get_slice_members_for_project' => array('project_guard'),
            'get_slices_for_member'=> array('signer_member_guard'),
	    'create_request' => array(), // Unguarded
	    'resolve_pending_request' => array(), // Unguarded
	    'get_requests_for_context' => array(), // Unguarded
	    'get_requests_by_user' => array(), // Unguarded
	    'get_pending_requests_for_user' => array(), // Unguarded
	    'get_number_of_pending_requests_for_user' => array(), // Unguarded
	    'get_request_by_id' => array() // Unguarded
            );

  public function __construct($cs_url) {
    $this->cs_url = $cs_url;
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

  /* Ensure that the signer matches the MEMBER parameter. */
  private function signer_owner_guard($message, $action, $params) {
      return new SASignerGuard($this->cs_url, $message, $action, $params, SA_ARGUMENT::OWNER_ID);
  }

  /* Ensure that the signer matches the MEMBER parameter. */
  private function signer_member_guard($message, $action, $params) {
      return new SASignerGuard($this->cs_url, $message, $action, $params, SA_ARGUMENT::MEMBER_ID);
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
      foreach (self::$context_table[$action] as $method_name) {
        // How to call a method dynamically
        $meth = array($this, $method_name);
        $result[] = call_user_func($meth, $message, $action, $params);
      }
    } else {
      error_log("SA: No guard producers for action \"$action\"");
    }
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
    return request_authorization($this->cs_url, 
				 $mysigner, 
				 $this->message->signerUuid(),
                                 $this->action, $this->context_type,
                                 $this->context);
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

/*----------------------------------------------------------------------
 * API Methods
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
  $slice_is_expired = convert_boolean($slice_row[SA_SLICE_TABLE_FIELDNAME::EXPIRED]);
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

  geni_syslog(GENI_SYSLOG_PREFIX::SA, "get_slice_credential() returning $slice_cred");
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

  // FIXME: Refuse to issue this credential if this experimenter cert is not one of ours

  // FIXME: Parametrize
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
    return generate_response(RESPONSE_ERROR::DATABASE, null, "Cannot create slice without a valid project ID");
  }

  if (! isset($project_name) || is_null($project_name) || $project_name == '') {
    error_log("Empty project name to create_slice " . $slice_name);
    geni_syslog(GENI_SYSLOG_PREFIX::SA, "Create slice error: no project name");
    return generate_response(RESPONSE_ERROR::DATABASE, null,
                             "Cannot create slice without a valid project name");
  }

  if (! isset($owner_id) || is_null($owner_id) || $owner_id == '') {
    error_log("Empty owner id to create_slice " . $slice_name);
    geni_syslog(GENI_SYSLOG_PREFIX::SA, "Create slice error: no owner id");
    return generate_response(RESPONSE_ERROR::DATABASE, null, "Cannot create slice without a valid owner ID");
  }

  if((!isset($slice_name)) || 
     (is_null($slice_name)) || 
     ($slice_name == '') ||
     (!is_valid_slice_name($slice_name)))
    {
      error_log("Illegal slice name $slice_name");
      geni_syslog(GENI_SYSLOG_PREFIX::SA, "Create slice error: invalid slice name \"$slice_name\"");
      return generate_response(RESPONSE_ERROR::DATABASE, null, 
			       "Cannot create slice with invalid slice name $slice_name");
    }

  $exists_sql = "select count(*) from " . $SA_SLICE_TABLENAME 
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . " = '" . $slice_name . "'" 
    . " AND " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . " = '" . $project_id . "'"
    . " AND NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED;
  //  error_log("SQL = " . $exists_sql);
  $exists_response = db_fetch_row($exists_sql);
  //  error_log("Exists " . print_r($exists_response, true));
  $exists = $exists_response[RESPONSE_ARGUMENT::VALUE];
  $exists = $exists['count'];
  if ($exists > 0) {
    geni_syslog(GENI_SYSLOG_PREFIX::SA, "Create slice error: slice name \"$slice_name\" already exists in project.");
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, null, 
			     "Slice of name " . $slice_name . " already exists in project.");
  }



  $permitted = request_authorization($cs_url, $mysigner, $owner_id, 'create_slice', 
				     CS_CONTEXT_TYPE::PROJECT, $project_id);
  if ($permitted < 1) {
    geni_syslog(GENI_SYSLOG_PREFIX::SA, "Create slice error: insufficient privileges for owner \"$owner_id\" in project \"$project_id\"");
    return generate_response(RESPONSE_ERROR::AUTHORIZATION, $permitted,
			    "Principal " . $owner_id . " may not create slice in project " . $project_id);
  }

  //  error_log("SA.CS.args = " . print_r($args, true));

  $slice_email = 'slice-' . $slice_name . '@example.com';
  $slice_cert = create_slice_certificate($project_name, $slice_name,
                                         $slice_email, $slice_id,
                                         $sa_slice_cert_life_days,
                                         $sa_authority_cert,
                                         $sa_authority_private_key);

  $slice_urn = urn_from_cert($slice_cert);

  $expiration = get_future_date(0, $sa_default_slice_expiration_hours);
  $creation = new DateTime();

  $conn = db_conn();
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
    error_log("create_slice got error from lookup_slice " . $slice_id . ": " . $slice_info[RESPONSE_ARGUMENT::OUTPUT]);
    return $slice_info;
  }

  // Create an assertion that this owner is the 'lead' of the slice (and has associated privileges)
  global $cs_url;
  global $mysigner;
  $signer = $message->signerUuid();
  create_assertion($cs_url, $mysigner, $signer, $owner_id, CS_ATTRIBUTE_TYPE::LEAD,
		   CS_CONTEXT_TYPE::SLICE, $slice_id);

  // Now add the lead as a member of the slice
  $addres = add_slice_member(array(SA_ARGUMENT::SLICE_ID => $slice_id, SA_ARGUMENT::MEMBER_ID => $owner_id, SA_ARGUMENT::ROLE_TYPE => CS_ATTRIBUTE_TYPE::LEAD), $message);
  if (! isset($addres) || is_null($addres) || ! array_key_exists(RESPONSE_ARGUMENT::CODE, $addres) || $addres[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    error_log("create_slice failed to add lead as a slice member: " . $addres[RESPONSE_ARGUMENT::CODE] . ": " . $addres[RESPONSE_ARGUMENT::OUTPUT]);
    // FIXME: ROLLBACK?
    return $addres;
  }

  // If the user is not the project lead, make the project lead
  // a member of the project with 'ADMIN' priviileges

  // Check if project lead is same as member
  global $pa_url;
  $project_details = lookup_project($pa_url, $mysigner, $project_id);
  if (!isset($project_details) ||
      is_null($project_details) ||
      !array_key_exists(PA_PROJECT_TABLE_FIELDNAME::LEAD_ID, $project_details))
    {
      error_log("Error looking up details for project_id $project_id");
      return $project_details;
    }
  error_log("PROJECT_DETAILS = " . print_r($project_details, true));
  $project_lead_id = $project_details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
    
  // If not, 
  if ($project_lead_id != $owner_id) {

    //    error_log("PL $project_lead_id is not OWNER $owner_id");
    // Create assertion of lead membership
    create_assertion($cs_url, $mysigner, $signer, $project_lead_id, 
		     CS_ATTRIBUTE_TYPE::ADMIN, 
		     CS_CONTEXT_TYPE::SLICE, $slice_id);

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
	error_log("Create slice failed ot add project lead as slice member: " .
		  $addres[RESPONSE_ARGUMENT::CODE] . ": " . 
		  $addres[RESPONSE_ARGUMENT::OUTPUT]);
	return $addres;
    }
    
  }

  // Log the creation
  global $log_url;
  global $mysigner;
  $project_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, 
						  $project_id);
  $slice_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, 
						  $slice_id);
  $attributes = array_merge($project_attributes, $slice_attributes);
  log_event($log_url, $mysigner, "Created slice " . $slice_name, $attributes, $owner_id);
  geni_syslog(GENI_SYSLOG_PREFIX::SA, "Created slice $slice_name for owner $owner_id in project $project_id");



  //  slice_info is already a response_triple from the lookup_slice call above
  //  error_log("SA.create_slice final return is " . print_r($slice_info, true));
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

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE NOT " . SA_SLICE_TABLE_FIELDNAME::EXPIRED;
  if (isset($project_id)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID .
      " = '" . $project_id . "'";
  }
  if (isset($owner_id)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::OWNER_ID .
      " = '" . $owner_id . "'";
  }
  if (isset($slice_name)) {
    $sql = $sql . " and " . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME .
      " = '" . $slice_name . "'";
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
    . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME
    . $where_clause;

  error_log("lookup_slices.sql = " . $sql);

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

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " = '" . $slice_id . "'";
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

  $sql = "SELECT " 
    . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_NAME . ", "
    . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::CREATION . ", "
    . SA_SLICE_TABLE_FIELDNAME::OWNER_ID . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_DESCRIPTION . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_EMAIL . ", "
    . SA_SLICE_TABLE_FIELDNAME::CERTIFICATE . ", "
    . SA_SLICE_TABLE_FIELDNAME::SLICE_URN 
    . " FROM " . $SA_SLICE_TABLENAME
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_URN
    . " = '" . $slice_urn . "'";
  //  error_log("LOOKUP_SLICE.SQL = " . $sql);
  $row = db_fetch_row($sql);
  // error_log("LOOKUP_SLICE.ROW = " . print_r($row, true));
  return $row;
}

function renew_slice($args, $message)
{
  global $SA_SLICE_TABLENAME;
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $requested = $args[SA_ARGUMENT::EXPIRATION];

  // error_log("got req $requested");
  $req_dt = new DateTime($requested);

  // FIXME: Shouldn't this depend on the current expiration?
  $max_expiration = get_future_date(20);// 20 days increment

  if ($req_dt > $max_expiration) {
    //    error_log("req is bigger: " . date_diff($max_expiration, $req_dt)->format('%R%a days'));
    $expiration = $max_expiration;
  } else {
    $expiration = $req_dt;
    //    error_log("max is bigger: " . date_diff($req_dt, $max_expiration)->format('%R%a days'));
  }

  $sql = "UPDATE " . $SA_SLICE_TABLENAME 
    . " SET " . SA_SLICE_TABLE_FIELDNAME::EXPIRATION . " = '"
    . db_date_format($expiration) . "'"
    . " WHERE " . SA_SLICE_TABLE_FIELDNAME::SLICE_ID . " = '" . $slice_id  . "'";
  //  error_log("RENEW.sql = " . $sql);
  $result = db_execute_statement($sql);

  // Log the renewal
  global $log_url;
  global $mysigner;
  $slice_info = lookup_slice(array(SA_ARGUMENT::SLICE_ID => $slice_id));
  $slice_name = $slice_info[RESPONSE_ARGUMENT::VALUE][SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
  $new_expiration = $slice_info[RESPONSE_ARGUMENT::VALUE][SA_SLICE_TABLE_FIELDNAME::EXPIRATION];
  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::SLICE, $slice_id);
  log_event($log_url, $mysigner,
          "Renewed slice $slice_name until $new_expiration",
          $attributes,
          $message->signerUuid());
  geni_syslog(GENI_SYSLOG_PREFIX::SA,
          "Renewed slice $slice_id until $new_expiration");
  return $slice_info;
}

// Add a member of given role to given slice
function add_slice_member($args, $message)
{
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];
  $role = $args[SA_ARGUMENT::ROLE_TYPE];

  global $SA_SLICE_MEMBER_TABLENAME;
  global $mysigner;

  $already_member_sql = "select count(*) from " . $SA_SLICE_MEMBER_TABLENAME
    . " WHERE " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . " = '$slice_id'"
    . " AND " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '$member_id'";
  $already_member = db_fetch_row($already_member_sql);
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  $already_member = $already_member['value']['count'] > 0;
  //  error_log("ALREADY_MEMBER = " . print_r($already_member, true));
  if ($already_member) {
    return generate_response(RESPONSE_ERROR::ARGS, null, "Member $member_id is already a member of slice $slice_id");
  }

  $sql = "INSERT INTO " . $SA_SLICE_MEMBER_TABLENAME . " ("
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE . ") VALUES ("
    . "'" . $slice_id . "', "
    . "'" . $member_id . "', "
    . $role . ")";
  //  error_log("SA.add slice_member.sql = " . $sql);
  $result = db_execute_statement($sql);

  // If successful, add an assertion to remove the role's privileges within the CS store
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    /* FIXME - The signer needs to have a certificate and private key. Who sends this message (below)
     * to the CS? Is the PA the signer?
     */
    $signer = $message->signerUuid();
    create_assertion($cs_url, $mysigner, $signer, $member_id, $role, CS_CONTEXT_TYPE::SLICE, $slice_id);
  }

  // Log adding the member
  global $ma_url;
  $member_data = ma_lookup_member_by_id($ma_url, $mysigner, $member_id);
  $lookup_slice_message = array(SA_ARGUMENT::SLICE_ID => $slice_id);
  $slice_data = lookup_slice($lookup_slice_message);
  if(($slice_data[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) &&
     (array_key_exists(SA_SLICE_TABLE_FIELDNAME::SLICE_NAME,
		       $slice_data[RESPONSE_ARGUMENT::VALUE]))) 
    {
      global $CS_ATTRIBUTE_TYPE_NAME;
      global $log_url;
      $slice_data = $slice_data[RESPONSE_ARGUMENT::VALUE];
      $member_name = $member_data->first_name . " " . $member_data->last_name;
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
function remove_slice_member($args, $message)
{
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];

  global $SA_SLICE_MEMBER_TABLENAME;
  global $mysigner;

  $sql = "DELETE FROM " . $SA_SLICE_MEMBER_TABLENAME 
    . " WHERE " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID  
    . " = '" . $slice_id . "'"  . " AND "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . "= '" . $member_id . "'";
  error_log("SA.remove slice_member.sql = " . $sql);
  $result = db_execute_statement($sql);

  // Delete previous assertions from CS
  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $signer = $message->signerUuid();

    $membership_assertions = query_assertions($cs_url, $mysigner, $member_id, CS_CONTEXT_TYPE::SLICE, $slice_id);
    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      delete_assertion($cs_url, $mysigner, $assertion_id);
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }
  }

  return $result;
}

// Change role of given member in given slice
function change_slice_member_role($args, $message)
{
  sa_expire_slices();
  $slice_id = $args[SA_ARGUMENT::SLICE_ID];
  $member_id = $args[SA_ARGUMENT::MEMBER_ID];
  $role = $args[SA_ARGUMENT::ROLE_TYPE];

  global $SA_SLICE_MEMBER_TABLENAME;
  global $mysigner;

  $sql = "UPDATE " . $SA_SLICE_MEMBER_TABLENAME
    . " SET " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $role
    . " WHERE " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . " = '" . $slice_id . "'" 
    . " AND " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = '" . $member_id . "'"; 

  error_log("SA.change_member_role.sql = " . $sql);
  $result = db_execute_statement($sql);


  if($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    global $cs_url;
    $signer = $message->signerUuid();

    // Remove previous CS assertions about the member in this slice
    $membership_assertions = query_assertions($cs_url, $mysigner, $member_id, CS_CONTEXT_TYPE::SLICE, $slice_id);
    //    error_log("ASSERTIONS = " . print_r($membership_assertions, true));
    foreach($membership_assertions as $membership_assertion) {
      //      error_log("ASSERTION = " . print_r($membership_assertion));
      $assertion_id = $membership_assertion[CS_ASSERTION_TABLE_FIELDNAME::ID];
      //      error_log("ASSERTION_ID = " . print_r($assertion_id));
      delete_assertion($cs_url, $mysigner, $assertion_id);
      //      error_log("DELETING ASSERTION : " . $assertion_id);
    }

    // Create new assertion for member in this role
    create_assertion($cs_url, $mysigner, $signer, $member_id, $role, CS_CONTEXT_TYPE::SLICE, $slice_id);
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
  if (array_key_exists(SA_ARGUMENT::ROLE_TYPE, $args) && isset($args[SA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[SA_ARGUMENT::ROLE_TYPE];
  }

  global $SA_SLICE_MEMBER_TABLENAME;

  $role_clause = "";
  if ($role != null) {
    $role_clause = 
      " AND " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $role;
  }
  $sql = "SELECT " 
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE
    . " FROM " . $SA_SLICE_MEMBER_TABLENAME
    . " WHERE "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . " = '" . $slice_id . "'" 
    . $role_clause;

  //  error_log("SA.get_slice_members.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
  
}

// Return list of member ID's and roles associated with slices of a given project
// If role is provided, filter to members of given role
function get_slice_members_for_project($args)
{
  sa_expire_slices();
  $project_id = $args[SA_ARGUMENT::PROJECT_ID];
  $role = null;
  if (array_key_exists(SA_ARGUMENT::ROLE_TYPE, $args) && isset($args[SA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[SA_ARGUMENT::ROLE_TYPE];
  }

  global $SA_SLICE_MEMBER_TABLENAME;
  global $SA_SLICE_TABLENAME;

  $role_clause = "";
  if ($role != null) {
    $role_clause = 
      " AND " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE . " = " . $role;
  }
  $sql = "SELECT " 
    . $SA_SLICE_TABLENAME . "." . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . ", "
    . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE
    . " FROM " . $SA_SLICE_MEMBER_TABLENAME
    . ", " . $SA_SLICE_TABLENAME
    . " WHERE "
    . "NOT " . $SA_SLICE_TABLENAME . "." . SA_SLICE_TABLE_FIELDNAME::EXPIRED
    . $SA_SLICE_MEMBER_TABLENAME . "." . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID . " = " 
    . $SA_SLICE_TABLENAME . "." . SA_SLICE_TABLE_FIELDNAME::SLICE_ID
    . " AND " . SA_SLICE_TABLE_FIELDNAME::PROJECT_ID 
    . " = '" . $project_id . "'" 
    . $role_clause;

  error_log("SA.get_slice_members_for_project.sql = " . $sql);
  $result = db_fetch_rows($sql);
  return $result;
  
}

// Return list of slice ID's and roles for given member_id for slices to which member belongs
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
  if (array_key_exists(SA_ARGUMENT::ROLE_TYPE, $args) && isset($args[SA_ARGUMENT::ROLE_TYPE])) {
    $role = $args[SA_ARGUMENT::ROLE_TYPE];
  }

  global $SA_SLICE_MEMBER_TABLENAME;

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
      . " = " . $role;
  }
  $member_clause = 
    SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID 
    . " = '" . $member_id . "' " . $role_clause;
  if(!$is_member) {
    $member_clause = 
    SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
      . " NOT IN (SELECT " 
      . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
      . " FROM " . $SA_SLICE_MEMBER_TABLENAME 
      . " WHERE " 
      . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID
      . " = '" . $member_id . "' " . $role_clause . ")";
      
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

// Include the RQ interface routines
$REQUEST_TABLENAME = $SA_SLICE_MEMBER_REQUEST_TABLENAME;

// Define the specific function to call to determine what slices are relevant to a given user
// (That is, requests about that slice are for this user)
function user_context_query($account_id)
{
  global $SA_SLICE_MEMBER_TABLENAME;
  return "select " . SA_SLICE_MEMBER_TABLE_FIELDNAME::SLICE_ID 
    . " FROM " . $SA_SLICE_MEMBER_TABLENAME
    . " WHERE " . SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE 
    . " IN (" . CS_ATTRIBUTE_TYPE::LEAD . ", " . CS_ATTRIBUTE_TYPE::ADMIN . ")"
    . " AND " . SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID . " = '" . $account_id . "'";
  
}
require_once('rq_controller.php');


$mycertfile = '/usr/share/geni-ch/sa/sa-cert.pem';
$mykeyfile = '/usr/share/geni-ch/sa/sa-key.pem';
$mysigner = new Signer($mycertfile, $mykeyfile);
$guard_factory = new SAGuardFactory($cs_url);
handle_message("SA", $cs_url, default_cacerts(),
	       $mysigner->certificate(), $mysigner->privateKey(), $guard_factory);

?>
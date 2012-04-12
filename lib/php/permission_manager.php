<?php

/**
 * Classes to manage permissions of the current user
 * Can be read from database
 * Has all permissions for actions that can be taken by user
 *    context free
 *    in context of a particular entity (by UUID)
 */

require_once('cs_constants.php');
require_once('pa_constants.php');
require_once('sa_constants.php');
require_once('db_utils.php');

// A structure containing information about each method and its context type
// and if it has one, the field of the call that context is contained in
// Also, what attributes are required to allow calling this function
class ActionSpec {
  function __construct($context_type, $context_fieldname, $allowed_attributes) {
    $this->context_type = $context_type;
    $this->context_fieldname = $context_fieldname;
    $this->allowed_attributes = $allowed_attributes;
  }
  public $context_type; // What kind of context is this action regarding?
  public $context_fieldname; // If so, what field of the message can it be found in?
  public $allowed_attributes; // Array of allowed attributes
}

$ALL_ACTION_SPECS = null;
$ADMIN_ATTRIBUTES = 
  array(CS_ATTRIBUTE_TYPE::LEAD, 
	CS_ATTRIBUTE_TYPE::ADMIN);
$WRITE_ATTRIBUTES = 
  array(CS_ATTRIBUTE_TYPE::LEAD, 
	CS_ATTRIBUTE_TYPE::ADMIN,
	CS_ATTRIBUTE_TYPE::MEMBER);
$READ_ATTRIBUTES = 
  array(CS_ATTRIBUTE_TYPE::LEAD, 
	CS_ATTRIBUTE_TYPE::ADMIN, 
	CS_ATTRIBUTE_TYPE::MEMBER, 
	CS_ATTRIBUTE_TYPE::AUDITOR);

if ($ALL_ACTION_SPECS == null) {
  // AM CLIENT METHODS
  $ALL_ACTION_SPECS['get_version'] = 
    new ActionSpec(CS_CONTEXT_TYPE::RESOURCE, null, $READ_ATTRIBUTES);
  $ALL_ACTION_SPECS['list_resources'] = 
    new ActionSpec(CS_CONTEXT_TYPE::RESOURCE, null, $READ_ATTRIBUTES);
  // This isn't called by message passing: It is client side so we need to call 
  // authorize_method_call directly
  $ALL_ACTION_SPECS['create_sliver'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SLICE, null, $WRITE_ATTRIBUTES);
  // MA CLIENT METHODS
  $ALL_ACTION_SPECS['add_attribute'] = 
    new ActionSpec(CS_CONTEXT_TYPE::MEMBER, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['remove_attribute'] = 
    new ActionSpec(CS_CONTEXT_TYPE::MEMBER, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['update_role'] = 
    new ActionSpec(CS_CONTEXT_TYPE::MEMBER, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['lookup_attributes'] = 
    new ActionSpec(CS_CONTEXT_TYPE::MEMBER, null, $ADMIN_ATTRIBUTES);
  // PA CLIENT METHODS
  $ALL_ACTION_SPECS['create_project'] = 
    new ActionSpec(CS_CONTEXT_TYPE::RESOURCE, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['delete_project'] = 
    new ActionSpec(CS_CONTEXT_TYPE::PROJECT, PA_ARGUMENT::PROJECT_ID, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['get_projects'] = 
    new ActionSpec(CS_CONTEXT_TYPE::RESOURCE, null, $READ_ATTRIBUTES);
  $ALL_ACTION_SPECS['lookup_project'] = 
    new ActionSpec(CS_CONTEXT_TYPE::PROJECT, PA_ARGUMENT::PROJECT_ID, $READ_ATTRIBUTES);
  $ALL_ACTION_SPECS['update_project'] = 
    new ActionSpec(CS_CONTEXT_TYPE::PROJECT, PA_ARGUMENT::PROJECT_ID, $ADMIN_ATTRIBUTES);
  // SA CLIENT METHODS
  // This isn't called by message_passing: it is client side so we need to call
  // authorize_method_call directly
  $ALL_ACTION_SPECS['get_slice_credential'] =
    new ActionSpec(CS_CONTEXT_TYPE::RESOURCE, null, $WRITE_ATTRIBUTES);
  $ALL_ACTION_SPECS['create_slice'] = 
    new ActionSpec(CS_CONTEXT_TYPE::PROJECT, SA_ARGUMENT::PROJECT_ID, $WRITE_ATTRIBUTES);
  $ALL_ACTION_SPECS['lookup_slices'] = 
    new ActionSpec(CS_CONTEXT_TYPE::PROJECT, SA_ARGUMENT::PROJECT_ID, $READ_ATTRIBUTES);
  $ALL_ACTION_SPECS['lookup_slice'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SLICE, SA_ARGUMENT::SLICE_ID, $READ_ATTRIBUTES);
  $ALL_ACTION_SPECS['renew_slice'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SLICE, SA_ARGUMENT::SLICE_ID, $WRITE_ATTRIBUTES);
  // SR CLIENT METHODS
  $ALL_ACTION_SPECS['get_services'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SERVICE, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['get_services_of_type'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SERVICE, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['get_first_service_of_type'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SERVICE, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['register_service'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SERVICE, null, $ADMIN_ATTRIBUTES);
  $ALL_ACTION_SPECS['remove_service'] = 
    new ActionSpec(CS_CONTEXT_TYPE::SERVICE, null, $ADMIN_ATTRIBUTES);
}


/**
 * Class containing all the perimissions of an entity, 
 * both those in context and those that are context free
 */
class PermissionSet {
  function __construct($allowed_actions_no_context, $allowed_actions_in_context) {
    $this->allowed_actions_no_context = $allowed_actions_no_context;
    $this->allowed_actions_in_context = $allowed_actions_in_context;
  }

  // List (array) of function names allowed, no context
  public $allowed_actions_no_context; 
  // Dictionary (name => array of context ID's) of function names allowed in particular context
  public $allowed_actions_in_context; 

  function is_allowed($function_name, $context_type, $context_id) {
    $result = false;
    if (is_context_type_specific($context_type)) {
      $result = in_array($function_name, $allowed_actions_no_context);
    } else {
      $contexts = $allowed_actions_context[$function_name];
      if ($contexts != null) {
	$result = in_array($context_id, $contexts);
      }
    }
    return $result;
  }
}

function compute_allowed_actions($attribute, $context_type)
{
  global $ALL_ACTION_SPECS;
  $actions = array();
  foreach($ALL_ACTION_SPECS as $action_name => $action_spec) {
    //    error_log("AN = " . print_r($action_name, true) . " AS = " . print_r($action_spec, true));
    //    error_log("ASAA = " . print_r($action_spec->allowed_attributes, true));
    //    error_log("ASAA : " . $action_name . " " . print_r($action_spec->allowed_attributes, true) . " " . 
    //	      $context_type . " " . $action_spec->context_type . " " . 
    //        in_array($attribute, $action_spec->allowed_attributes));
    if ($context_type == $action_spec->context_type && in_array($attribute, $action_spec->allowed_attributes)) {
      $actions[] = $action_name;
    }
  }
  //  error_log("CAA " . print_r($actions, true) . " " . $attribute);
  return $actions;
}

// Grab all the unexpired attributes for a given person (account id)
// And compute the set of functions that this person is allowed to invoke
function compute_permission_set($account_id)
{
  global $CS_ASSERTION_TABLENAME;

  $sql = "select " 
    . CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE . ", "
    . CS_ASSERTION_TABLE_FIELDNAME::CONTEXT
    . " FROM " . $CS_ASSERTION_TABLENAME
    . " WHERE "
    . CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL . " = '" . $account_id . "' AND "
    . CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION . "> '" . db_date_format(new DateTime()) . "'";
  //  error_log("CPS.sql = " . $sql);
  $rows = db_fetch_rows($sql);
  //  error_log("CPS.rows = " . print_r($rows, true));

  // Initialize the context and context-free arrays
  $allowed_actions_in_context = array();
  $allowed_actions_no_context = array();
  
  // Iterate over every attribute (role) of user
  foreach($rows as $row) {
    $attribute = $row[CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE];
    $context_type = $row[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE];
    $context = $row[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT];

    // Compute all the actions for which this attribute gives rights
    $allowed_actions = compute_allowed_actions($attribute, $context_type);

    //    error_log("LOOP: ATT " . $attribute . " CT " . $context_type . " C " . 
    //	      $context . " AA " . print_r($allowed_actions, true));

    // Set up the array for this specific context (if contextual attribute)
    if (is_context_type_specific($context_type)) {
      if (!array_key_exists($context, $allowed_actions_in_context)) {
	$allowed_actions_for_context = array();
	$allowed_actions_in_context[$context] = $allowed_actions_for_context;
      }
    }

    foreach($allowed_actions as $allowed_action) {
      if (is_context_type_specific($context_type)) {
	$allowed_actions_for_context[] = $allowed_action;
      } else {
	$allowed_actions_no_context[] = $allowed_action;
      }
    }
    if (is_context_type_specific($context_type)) {
      $allowed_actions_in_context[$context] = $allowed_actions_for_context;
    }
    //    error_log("AAIC " . print_r($allowed_actions_in_context, true));
    //    error_log("AAFC " . print_r($allowed_actions_for_context, true));
    //    error_log("AANC " . print_r($allowed_actions_no_context, true));
  }

  $permission_set = new PermissionSet($allowed_actions_no_context, $allowed_actions_in_context);
  return $permission_set;

}

// Does this permission set (from a given user)
// permit a given function name and set of arguments?
function authorize_method_call($permission_set, $function_name, $args)
{
  global $ALL_ACTION_SPECS;

  $action_spec = $ALL_ACTION_SPECS[$function_name];
  if ($action_spec == null) {
    die("No action spec defined for function " . $function_name);
  }
  $context_type = $action_spec->context_type;
  $context = null;
  if(!is_context_type_specific($context_type)) {
    $context_fieldname = $action_spec->context_fieldname;
    $context = $args[$context_fieldname];
    if($context == null) {
      die("No argument of type " . $context_fieldname . " for function call " . $function_name);
    }
  }
  $result = $permission_set->is_allowed($function_name, $context_type, $context);
}

?>

<?php

/**
 * Classes to manage permissions of the current user
 * Provided by CS (get_permissions) call
 * Has all permissions for actions that can be taken by user
 *    context free
 *    in context of a particular entity (by UUID)
 */
class PermissionSet {
  function __construct() {
    $this->allowed_actions_no_context = array();
    $this->allowed_actions_in_context = array();
  }

  // Add a new permission (in context) to permission set
  function add($permission, $context_type, $context)
  {
    if (is_context_type_specific($context_type)) {
      if(!array_key_exists($context, $this->allowed_actions_in_context)) {
	$list_for_context = array();
	} else {
	  $list_for_context = $this->allowed_actions_in_context[$context];
	}
      $list_for_context[] = $permission;
      $this->allowed_actions_in_context[$context] = $list_for_context;
    } else {
      if(!in_array($permission, $this->allowed_actions_no_context)) {
	$this->allowed_actions_no_context[] = $permission;
      }
    }
  }

  // List (array) of function names allowed, no context
  public $allowed_actions_no_context; 
  // Dictionary (name => array of context ID's) of function names allowed in particular context
  public $allowed_actions_in_context; 

  // Is given permission allowed in given context?
 function is_allowed($permission, $context_type, $context_id) {
    $result = false;
    if (is_context_type_specific($context_type)) {
      $result = in_array($permission, $allowed_actions_no_context);
    } else {
      $contexts = $allowed_actions_context[$permission];
      if ($contexts != null) {
	$result = in_array($context_id, $contexts);
      }
    }
    return $result;
  }
}

// Compute permission set from a given set of rows with all permissions selected
// for a given principal
function compute_permission_set($rows)
{
  $ps = new PermissionSet();
  foreach($rows as $row)
    {
      $permission = $row['name'];
      $context_type = $row[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE];
      $context = $row[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT];
      $ps->add($permission, $context_type, $context);
    }
  return $ps;
}


?>

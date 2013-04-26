<?php
//----------------------------------------------------------------------
// Copyright (c) 2013 Raytheon BBN Technologies
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

require_once("user.php");
require_once("header.php");
require_once('util.php');
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once('cs_constants.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
unset($project);
$result = "No changes made";
$error = false;
$errors = "";
$old_lead_id = null;
$old_lead_new_role = null;
$new_lead_id = null;
include("tool-lookupids.php");

if (! isset($project) or is_null($project)) {
  $_SESSION['lasterror'] = "No project specified to edit membership.";
  relative_redirect('home.php');
}
if (! $user->isAllowed(PA_ACTION::REMOVE_PROJECT_MEMBER,
		       CS_CONTEXT_TYPE::PROJECT, $project_id) and 
    ! $user->isAllowed(PA_ACTION::CHANGE_MEMBER_ROLE,
		       CS_CONTEXT_TYPE::PROJECT, $project_id)    ) {
  error_log($user->prettyName() . " not allowed to edit project membership for $project_name");
  relative_redirect("project.php?project_id=$project_id");
}
if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}
$members = array();
foreach (array_keys($_REQUEST) as $input) {
  if ($input == "project_id" or $input == "submit" or $input == "edit") {
    continue;
  }
  $members[] = $input;
}
if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}
$member_names = lookup_member_names($ma_url, $user, $members);
$member_roles = get_project_members($pa_url, $user, $project_id);

$edits = array();
global $CS_ATTRIBUTE_TYPE_NAME;
foreach (array_keys($_REQUEST) as $input) {
  if ($input == "project_id" or $input == "submit" or $input == "edit") {
    continue;
  }
  $value = $_REQUEST[$input];
  $member_name = $member_names[$input];
  error_log("edit_project_membership: Requested $value for $member_name in project $project_name");
  foreach($member_roles as $memberR) {
    if ($memberR['member_id'] == $input) {
     $member_id = $memberR['member_id'];
     $member_role_index = $memberR['role'];
     $member_role = $CS_ATTRIBUTE_TYPE_NAME[$member_role_index];
     break;
    }
  }
  if ($value == "remove") {
    if (! $user->isAllowed(PA_ACTION::REMOVE_PROJECT_MEMBER,
			   CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      error_log($user->prettyName() . " not allowed to remove project members from $project_name");
      $error = true;
      $msg = "Not permitted to remove project members from $project_name";
      if ($errors == "") {
	$errors = $msg;
      } else {
	$errors = $errors . "; " . $msg;
      }
    } else if ($input == $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]) {
      error_log("Cannot remove project lead from the project");
      $error = true;
      $msg = "Cannot remove lead from the project";
      if ($errors == "") {
	$errors = $msg;
      } else {
	$errors = $errors . "; " . $msg;
      }
    } else if ($member_role_index == CS_ATTRIBUTE_TYPE::OPERATOR) {
      error_log("Cannot remove operator from a project");
      $error = true;
      $msg = "Cannot remove operator from the project";
      if ($errors == "") {
	$errors = $msg;
      } else {
	$errors = $errors . "; " . $msg;
      }
    } else {
      $edits[$input] = remove_project_member($pa_url, $user, $project_id,
				      $input);
      /* error_log("Remove $input from project result: " */
      /* 		. print_r($edits[$input], true)); */
      if($edits[$input][RESPONSE_ARGUMENT::CODE] ==
	 RESPONSE_ERROR::NONE) {
	error_log("Removed $member_name from $project_name");
	$msg = "Removed $member_name";
	if ($result == "No changes made") {
	  $result = $msg;
	} else {
	  $result = $result . "; " . $msg;
	} 
      } else {
	$error = true;
	if (array_key_exists(RESPONSE_ARGUMENT::OUTPUT,
			     $edits[$input])) {
	      $output = $edits[$input][RESPONSE_ARGUMENT::OUTPUT];
	}
	error_log("Error Removing $member_name from $project_name. " . $output);
	$msg = "Failed to remove $member_name";
	if ($errors == "") {
	  $errors = $msg;
	} else {
	  $errors = $errors . "; " . $msg;
	} 
	if (! is_null($output)) {
	  $errors = $errors . ": " . $output;
	}
      }
    }
  } else if ($value == CS_ATTRIBUTE_TYPE::OPERATOR) {
    error_log("Cannot make someone an operator from portal");
  } else if ($value == "nochange") {
  } else {
    
    if (! $user->isAllowed(PA_ACTION::CHANGE_MEMBER_ROLE,
			   CS_CONTEXT_TYPE::PROJECT, $project_id)) {
      error_log($user->prettyName() . " not allowed to change project member roles for $project_name");
      $msg = "You are not authorized to change roles in $project_name.";
      $error = true;
      if ($errors == "") {
	$errors = $msg;
      } else {
	$errors = $errors . "; " . $msg;
      } 
      continue;
    } else if ($input == $user->account_id and $member_role_index == CS_ATTRIBUTE_TYPE::ADMIN) {
      // Admins cannot change their own role
      error_log($user->prettyName() . " is an Admin trying to change their own role in project $project_name");
      $msg = "As an Admin, you cannot change your own role.";
      $error = true;
      if ($errors == "") {
	$errors = $msg;
      } else {
	$errors = $errors . "; " . $msg;
      } 
    } else {
      // Do not use this to change the project lead. Instead,
      //			   collect the desired change and find
      //      the new lead, and insist on doing the changes together
      if ($input == $project[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID]) {
	if (! $user->isAllowed(PA_ACTION::CHANGE_LEAD,
			       CS_CONTEXT_TYPE::PROJECT, $project_id)) {
	  error_log($user->prettyName() . " not allowed to change project lead for $project_name");
	  $error = true;
	  $msg = "You are not permitted to change the project lead on this project.";
	  if ($errors == "") {
	    $errors = $msg;
	  } else {
	    $errors = $errors . "; " . $msg;
	  } 
	  continue;
	}
	$old_lead_new_role = $value;
	$old_lead_id = $input;
	if (is_null($new_lead_id)) {
	  continue;
	} else {
	  // Change project lead
	  $res1 = change_lead($pa_url, $user, $project_id, $value, $new_lead_id);
	  /* error_log("Change project $project_name lead from $value to $new_lead_id result: " */
	  /* 	    . print_r($res1, true)); */
	  if($res1[RESPONSE_ARGUMENT::CODE] ==
	     RESPONSE_ERROR::NONE) {
	    error_log("Changed project $project_name lead from $value to $new_lead_id");
	    $msg = "Changed project lead to " . $member_names[$new_lead_id];
	    if ($result == "No changes made") {
	      $result = $msg;
	    } else {
	      $result = $result . "; " . $msg;
	    } 
	  } else {
	    $error = true;
	    if (array_key_exists(RESPONSE_ARGUMENT::OUTPUT,
				 $res1)) {
	      $output = $res1[RESPONSE_ARGUMENT::OUTPUT];
	    }
	    error_log("Error changing project $project_name lead from $value to $new_lead_id: " . $output);
	    $msg = "Failed to change project lead to " . $member_names[$new_lead_id];
	    if ($errors == "") {
	      $errors = $msg;
	    } else {
	      $errors = $errors . "; " . $msg;
	    } 
	    if (! is_null($output)) {
	      $errors = $errors . ": " . $output;
	    }
	    continue;
	  }
	  // Then continue, to change member role
	}
      }
      if ($value == CS_ATTRIBUTE_TYPE::LEAD) {
	if (! $user->isAllowed(PA_ACTION::CHANGE_LEAD,
			       CS_CONTEXT_TYPE::PROJECT, $project_id)) {
	  error_log($user->prettyName() . " not allowed to change project lead for $project_name");
	  $error = true;
	  $msg = "You are not permitted to change the project lead on this project.";
	  if ($errors == "") {
	    $errors = $msg;
	  } else {
	    $errors = $errors . "; " . $msg;
	  } 
	  continue;
	}
	$new_lead_id = $input;
	if (is_null($old_lead_new_role)) {
	  continue;
	} else {
	  // Change project lead
	  $res1 = change_lead($pa_url, $user, $project_id, $old_lead_id, $new_lead_id);
	  error_log("Change project $project_name lead from $old_lead_id to $new_lead_id result: "
		    . print_r($res1, true));
	  if($res1[RESPONSE_ARGUMENT::CODE] ==
	     RESPONSE_ERROR::NONE) {
	    error_log("Changed project $project_name lead from $old_lead_id to $new_lead_id");
	    $msg = "Changed project lead to " . $member_names[$new_lead_id];
	    if ($result == "No changes made") {
	      $result = $msg;
	    } else {
	      $result = $result . "; " . $msg;
	    } 
	  } else {
	    $error = true;
	    if (array_key_exists(RESPONSE_ARGUMENT::OUTPUT,
				 $res1)) {
	      $output = $res1[RESPONSE_ARGUMENT::OUTPUT];
	    }
	    error_log("Error changing project $project_name lead from $value to $new_lead_id: " . $output);
	    $msg = "Failed to change project lead to " . $member_names[$new_lead_id];
	    if ($errors == "") {
	      $errors = $msg;
	    } else {
	      $errors = $errors . "; " . $msg;
	    } 
	    if (! is_null($output)) {
	      $errors = $errors . ": " . $output;
	    }
	    continue;
	  }
	  // Then change member role for the lead
	  $input = $old_lead_id;
	  $member_name = $member_names[$input];
	  $value = $old_lead_new_role;
	}
      }
      $edits[$input] = change_member_role($pa_url, $user, $project_id, $input, $value);
      /* error_log("Change Role for $input to $value result: " */
      /* 		. print_r($edits[$input], true)); */
      if($edits[$input][RESPONSE_ARGUMENT::CODE] ==
	 RESPONSE_ERROR::NONE) {
	error_log("Changed $project_name Role for $member_name to " . $CS_ATTRIBUTE_TYPE_NAME[$value]);
	$msg = "Made $member_name a project " . $CS_ATTRIBUTE_TYPE_NAME[$value];
	if ($result == "No changes made") {
	  $result = $msg;
	} else {
	  $result = $result . "; " . $msg;
	} 
      } else {
	$error = true;
	if (array_key_exists(RESPONSE_ARGUMENT::OUTPUT,
			     $edits[$input])) {
	      $output = $edits[$input][RESPONSE_ARGUMENT::OUTPUT];
	}
	error_log("Error Changing $project_name Role for $member_name to " . $CS_ATTRIBUTE_TYPE_NAME[$value] . ": " . $output);
	$msg = "Failed to change $member_name role to " . $CS_ATTRIBUTE_TYPE_NAME[$value];
	if ($errors == "") {
	  $errors = $msg;
	} else {
	  $errors = $errors . "; " . $msg;
	} 
	if (! is_null($output)) {
	  $errors = $errors . ": " . $output;
	}
      }
    }
  }
}

// If got half of the inputs I need to change project lead, add
// to errors
if ((is_null($old_lead_new_role) and ! is_null($new_lead_id)) or 
    (!is_null($old_lead_new_role) and is_null($new_lead_id))) {
  $error = true;
  $msg = "To change project lead, specify both the new lead and a new role for the current lead";
  error_log("Got only some needed inputs to change project lead for $project_name");
  if ($errors == "") {
    $errors = $msg;
  } else {
    $errors = $errors . "; " . $msg;
  }
}

if ($error) {
  $_SESSION['lasterror'] = "Editing project $project_name members: $errors";
  if ($result != "No changes made") {
    $_SESSION['lastmessage'] = "Edited project $project_name members: $result";
  }
} else {
  $_SESSION['lastmessage'] = "Edited project $project_name members: $result";
}

show_header('GENI Portal: Projects', $TAB_PROJECTS);
relative_redirect('project.php?project_id='.$project_id . "&result=" . $result);

include("footer.php");
?>

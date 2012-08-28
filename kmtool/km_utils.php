<?php

/**
 * Set of utilities and constants to support the KM (Key Management) tool
 */

// List of tools and their URL's
$temp_candidate_tools['GENI PORTAL'] = 'GENI PORTAL URL';
$temp_candidate_tools['DUMMY'] = "DUMMY TOOL URL";

// List of tools for which user has already established keys
$temp_user_approved_tools['DUMMY'] = true;

// error_log("TOOLS = " . print_r($candidate_tools, true));
// error_log("USER_TOOLS = " . print_r($user_approved_tools, true));


function km_check_authorization($user, $tool)
{
  return true;
}

// Return dictionary of 'toolname => toolurl'
// *** To be replaced by call to MA
function get_candidate_tools()
{
  global $temp_candidate_tools;
  return $temp_candidate_tools;
}

// Return list of tools for which user has already established keys
// To be replaced with call to MA
function get_authorized_tools_for_user($user)
{
  global $temp_user_approved_tools;
  return $temp_user_approved_tools;
}

// Authorize a given tool for given user
function authorize_tool_for_user($user, $tool_urn)
{
}

// Authorize a given tool for given user
function deauthorize_tool_for_user($user, $tool_urn)
{
}

?>

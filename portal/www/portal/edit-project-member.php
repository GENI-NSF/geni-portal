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

// Edit the members of a given project

require_once("user.php");
require_once("header.php");
require_once("ma_client.php");
require_once("pa_client.php");
require_once('cs_constants.php');

// Return the pieces required to construct the row
// For a given member: 
//    'member_url': the URL to the member within the project
//    'member_role' : the role of the member in the project
//    'member_actions' : the select menu for actions to 
//         add/remove/change-role for this user within this project
function compute_member_row_elements($member_details, 
				     $all_project_member_names,
				     $project_id,
				     $current_project_members)
{
  $member_id = $member_details[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];

  // Compute the URL of the page to view/edit information about the user
  // in the project context
  $member_name = $all_project_member_names[$member_id];
  $member_url =  "<a href=\"project-member.php?project_id=" . 
    $project_id . "&member_id=" . $member_id . "\">$member_name</a>";

  // Compute the role of the person within the project
  global $CS_ATTRIBUTE_TYPE_NAME;
  $member_role = "NOT MEMBER";
  $is_member = False;
  $member_id = $member_details[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
  foreach($current_project_members as $cm) {
    $cm_member_id = $cm[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
    $cm_role_index = $cm[PA_PROJECT_MEMBER_TABLE_FIELDNAME::ROLE];
    if($cm_member_id == $member_id) {
      $member_role = $CS_ATTRIBUTE_TYPE_NAME[$cm_role_index];
      $member_role_index = $cm_role_index;
      $is_member = True;
      break;
    }
  }

  // Compute the set of actions for this user
  // If a member, can remove or change role
  // If not a member, can add with a given role
  $options = "";
  $options = $options . "<option value=0>Remove from Project</option>";

  foreach($CS_ATTRIBUTE_TYPE_NAME as $role_index => $role_label) {
    if ($role_index == CS_ATTRIBUTE_TYPE::OPERATOR) continue;
    $selected = "";
    if($member_role == $role_label) {
      $label = "No Change";
      $selected = "selected";
    } else {
      $label = "Change to " . $role_label;
    }
    
    $options = $options . "<option $selected value=$role_index>$label</option>";
  }
  $member_actions =  "<select name=\"$member_id\">$options</select>";

  $row_elements = array('member_url' => $member_url,
			'member_role' => $member_role,
			'member_role_index' => $member_role_index,
			'member_actions' => $member_actions);
  return $row_elements;
}

// Compare project member row elements (above)
// Slice Members before non-members
// Lead > Admin > Member > Auditor > Operator
// 
function compare_member_row_elements($ent1, $ent2)
{
  $member_role1 = $ent1['member_role_index'];
  $member_role2 = $ent2['member_role_index'];

  if ($member_role1 < $member_role2)
    return -1;
  else if ($member_role1 == $member_role2)
    return 0;
  else
    return 1;

}

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");

if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}

if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}



show_header('GENI Portal: Projects', $TAB_PROJECTS);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

if($project_id == '' || $project_id == 'none') {
  error_log("PROJECT_ID not set");
}

// Get current list of members

$current_members = get_project_members($pa_url, $user, $project_id);
//foreach($current_members as $cm) {
//  error_log("CM = " . print_r($cm, true));
//}

// Get list of all members of project

$all_project_members = get_project_members($pa_url, $user, $project_id);
$all_project_member_names = lookup_member_names_for_rows($ma_url, $user, $all_project_members, PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID);
$all_project_member_ids = array();
foreach($all_project_members as $apm) {
  //  error_log("APM = " . print_r($apm, true));
  $all_project_member_ids[] = $apm[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
}

$all_project_member_details = lookup_member_details($ma_url, $user, $all_project_member_ids);
//foreach($all_project_member_details as $apmd) {
//  error_log("APMD = " . print_r($apmd, true));
//}

print "<h1>GENI Project: " . $project_name . "</h1>";

?>
<form method="POST" action="do-edit-project-member.php">
<table>
<tr><th>Project Member</th><th>Project Role</th><th>Actions</th></tr>
<?php

print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";


// First capture all the row details for the members
$all_member_row_elements = array();
foreach($all_project_member_details as $apmd) {
  $member_row_elements = compute_member_row_elements($apmd,
						    $all_project_member_names,
						    $project_id,
						    $current_members);
  $all_member_row_elements[] = $member_row_elements;
  
}

// Now sort them by role.
usort($all_member_row_elements, 'compare_member_row_elements');

// Then put them in a table
// Then put them in a table
foreach($all_member_row_elements as $amre) {
  $member_url = $amre['member_url'];
  $member_role = $amre['member_role'];
  $member_actions = $amre['member_actions'];
  print "<tr><td>$member_url</td><td>$member_role</td><td>$member_actions</td></tr>\n";
}

?>
</table>
<?php
$submit_label = "Modify";

print "<br/>\n";
print "<input type=\"submit\" value=\"$submit_label\"/>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
?>

</form>



<?php

include("footer.php");

?>


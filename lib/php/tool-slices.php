<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("sa_client.php");
require_once("pa_client.php");
require_once("util.php");
require_once("proj_slice_member.php");
require_once("tool-jfed.php");
require_once("util.php");
include("services.php");

if(!isset($project_objects) || !isset($slice_objects) || 
   !isset($member_objects) || !isset($project_slice_map)) 
{
  $pid = null;
  if(isset($project_id)) { $pid = $project_id;}
  $retVal  = get_project_slice_member_info($sa_url, $ma_url, $user, 
					   True, $pid);
  $project_objects = $retVal[0];
  $slice_objects = $retVal[1];
  $member_objects = $retVal[2];
  $project_slice_map = $retVal[3];
  $project_activeslice_map = $retVal[4];
}

$my_slice_objects = $slice_objects;

if (isset($project_id)) {
  $my_slice_objects = array();
  foreach($project_slice_map[$project_id] as $slice_id) {
    $my_slice_objects[] = $slice_objects[$slice_id];
  }
}

$expired_slices = array();
$unexpired_slices = array();
foreach($my_slice_objects as $slice) {
  $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
  $expired = $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
  if(convert_boolean($expired)) 
    $expired_slices[$slice_id] = $slice;
  else
    $unexpired_slices[$slice_id] = $slice;
}

$unexpired_slice_owner_names = array();
if (count($unexpired_slices) > 0) {
  $unexpired_slice_owner_names = lookup_member_names_for_rows($ma_url, $user, $unexpired_slices, 
						    SA_SLICE_TABLE_FIELDNAME::OWNER_ID);
}

$expired_slice_owner_names = array();
if (count($expired_slices) > 0) {
  $expired_slice_owner_names = lookup_member_names_for_rows($ma_url, $user, $expired_slices, 
						    SA_SLICE_TABLE_FIELDNAME::OWNER_ID);
}

$my_slice_objects = $unexpired_slices;
$slice_owner_names = $unexpired_slice_owner_names;

if (count($my_slice_objects) > 0) {

  $base_url = relative_url("slicecred.php?");
  $slice_base_url = relative_url("slice.php?");
  $listres_base_url = relative_url("listresources.php?");
  $resource_base_url = relative_url("slice-add-resources-jacks.php?");
  $delete_sliver_base_url = relative_url("confirm-sliverdelete.php?");
  $gemini_base_url = relative_url("gemini.php?");
  $labwiki_base_url = 'http://labwiki.casa.umass.edu/?';

  // Code to set up jfed button
  $jfedret = get_jfed_strs($user);
  $jfed_script_text = $jfedret[0];
  $jfed_button_start = $jfedret[1];
  $jfed_button_part2 = $jfedret[2];
  print $jfed_script_text;
  // End of jFed section

  //separate slices for which $user is lead
  $lead_slices = array();
  $nonlead_slices = array();

  foreach ($my_slice_objects as $slice) {
    if ($slice['owner_id'] === $user->account_id) {
      $lead_slices[] = $slice;
    } else {
      $nonlead_slices[] = $slice;
    } 
  }

  function cmp($a,$b) {
    return strcmp(strtolower($a['slice_name']),strtolower($b['slice_name']));
  }

  usort($lead_slices,"cmp");
  usort($nonlead_slices,"cmp");

  if (count($lead_slices) > 0) {
    print "<h5>Slices I lead</h5>";
    make_slice_table($lead_slices);
  }

  if (count($nonlead_slices) > 0) {
    print "<h5>Slices I don't lead</h5>";
    make_slice_table($nonlead_slices);
  }
} else {
  if (isset($project_id) && uuid_is_valid($project_id)) {
    print "<p><i>You do not have access to any slices in this project.</i></p>";
  } else {
    print "<p><i>You do not have access to any slices.</i></p>";
  }
}

function make_slice_table($slicelist) {
  global $user;
  print "<div class='tablecontainer'>";
  print "<table class='slicetable'>";
  print "<tr><th>Slice Name</th>";
  print "<th>Project</th>";
  print "<th>Slice Lead</th>";
  print "<th>Slice Expiration</th>";
  print "<th>Next Resource Expiration</th>";
  print "<th>Actions</th>";
  print "</tr>";

  foreach ($slicelist as $slice) {
    list_slice($slice,$user);
  }

  print "</table>";
  print "</div>";
}

function list_slice($slice, $user) {
  global $project_objects, $slice_owner_names;
  global $base_url, $slice_base_url, $listres_base_url, $resource_base_url;
  global $delete_sliver_base_url;
  global $gemini_base_url, $labwiki_base_url;
  global $disabled, $jfed_button_start, $jfed_button_part2;
  global $sa_url, $user;

  $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];

  $slice_expired = false;
  if (array_key_exists(SA_SLICE_TABLE_FIELDNAME::EXPIRED, $slice)) {
      $slice_expired = $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
  }

  $disable_buttons_str = "";
  if (isset($slice_expired) && convert_boolean($slice_expired)) {
    $disable_buttons_str = "disabled";
  }

  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  $args['slice_id'] = $slice_id;
  $query = http_build_query($args);
  $slice_url = $slice_base_url . $query;
  print "<tr><td><a href='$slice_url'>$slice_name</a></td>";

  $slice_project_id = $slice[SA_ARGUMENT::PROJECT_ID];                 

  // There's an odd edge case in which a project has expired 
  // but some slice of the project has not. In this case, $project_objects may not
  // contain the project of the slice. If so, list the project using something else.
  if (!array_key_exists($slice_project_id, $project_objects)) {
    $slice_project_name = "-Expired Project-";
  } else {
    $project = $project_objects[$slice_project_id];
    $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  }
  print "<td><a href='project.php?project_id=$slice_project_id'>$slice_project_name</a></td>";

  // Slice owner name
  $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
  $slice_owner_name = $slice_owner_names[$slice_owner_id];
  print "<td>$slice_owner_name</td>";

  // Slice expiration
  $slice_exp_date = $slice[SA_ARGUMENT::EXPIRATION];
  $slice_exp_str = dateUIFormat($slice_exp_date);
  $slice_exp_pretty_str = "in <b>" . get_time_diff_string(get_time_diff($slice_exp_str)) . "</b>";
  $slice_exp_color = get_urgency_color($slice_exp_str);
  $slice_exp_icon = get_urgency_icon($slice_exp_str);

  print "<td><span title='$slice_exp_str'>$slice_exp_pretty_str ";
  print "<i class='material-icons' style='color:$slice_exp_color; font-size: 18px;'>$slice_exp_icon</i></span></td>";

  // Next resource expiration  
  $slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
  if (count($slivers) == 0) {
    $next_exp_str = "";
    $next_exp_pretty_str = "<i>No resources for this slice</i>";
    $next_exp_color = "#5F584E";
    $next_exp_icon = "";
  } else {
    $first_sliver = reset($slivers);
    $next_exp = new DateTime($first_sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
    foreach ($slivers as $sliver) {
      $this_date = new DateTime($sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
      if ($next_exp > $this_date) {
        $next_exp = $this_date;
      }
    }
    $next_exp_str = dateUIFormat($next_exp);
    $next_exp_pretty_str = "in <b>" . get_time_diff_string(get_time_diff($next_exp_str)) . "</b>";
    $next_exp_color = get_urgency_color($next_exp_str);
    $next_exp_icon = get_urgency_icon($next_exp_str);
  }
  print "<td><span title='$next_exp_str'>$next_exp_pretty_str ";
  print "<i class='material-icons' style='color:$next_exp_color; font-size: 18px;'>$next_exp_icon</i></span></td>";

  // Slice actions
  $slicecred_url = $base_url . $query;
  $sliceresource_url = $resource_base_url . $query;
  $delete_sliver_url = $delete_sliver_base_url . $query;
  $listres_url = $listres_base_url . $query;
  $gemini_url = $gemini_base_url . $query;
  $labwiki_url = $labwiki_base_url . $query;

  // Determine privileges to this slice for this user
  $add_slivers_privilege = $user->isAllowed(SA_ACTION::ADD_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id);
  $delete_slivers_privilege = $user->isAllowed(SA_ACTION::DELETE_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id);
  $renew_slice_privilege = $user->isAllowed(SA_ACTION::RENEW_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id);
  $get_slice_credential_privilege = $user->isAllowed(SA_ACTION::GET_SLICE_CREDENTIAL, CS_CONTEXT_TYPE::SLICE, $slice_id);

  print "<td>";
  print "<ul class='selectorcontainer' style='margin: 0px;'><li class='has-sub selector' style='float:none;'>";
  print "<span class='selectorshown'>Actions</span><ul class='submenu'>";
  if ($add_slivers_privilege) {
    print "<li><a onClick=\"window.location='$sliceresource_url'\">Add Resources</a></li>";
  }
  if ($get_slice_credential_privilege) {
    print "<li><a title='Login info, etc' onClick=\"info_set_location('$slice_id', '$listres_url')\">Details</a></li>";
  }
  if ($delete_slivers_privilege) {
    print "<li><a onClick=\"info_set_location('$slice_id', '$delete_sliver_url')\">Delete Resources</a></li>"; 
  }
  if ($add_slivers_privilege) {
    print "<li><a onClick=\"window.open('$gemini_url')\">GENI Desktop</a></li>";  
    print "<li><a onClick=\"window.open('$labwiki_url')\">LabWiki</a></li>";
  }

  // Show a jfed button if there wasn't an error generating it
  if (!is_null($jfed_button_start) && $get_slice_credential_privilege) {
    print "<li>";
    print $jfed_button_start . getjFedSliceScript($slice_urn) . $jfed_button_part2 . ">jFed</button>";
    print "</li>";
  }
  print "</ul></li></ul>";

  print "</td></tr>\n";
}

print "<script src=\"tool-slices.js\"></script>";

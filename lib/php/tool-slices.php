<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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
    print "<h5>Slices I own</h5>";
    make_slice_table($lead_slices);
  }

  if (count($nonlead_slices) > 0) {
    print "<h5>Slices I don't own</h5>";
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
  print "<tr><th>Name</th>";
  print "<th>Project</th>";
  print "<th>Owner</th>";
  print "<th>Expiration</th>";
  print "<th>Next Resource <br> Expiration</th>";
  print "<th>&nbsp;</th>";
  print "</tr>";

  foreach ($slicelist as $slice) {
    list_slice($slice,$user);
  }

  print "</table>";
  print "</div>";
}

function list_slice($slice,$user) {
  global $project_objects, $slice_owner_names;
  global $base_url, $slice_base_url, $listres_base_url, $resource_base_url;
  global $delete_sliver_base_url;
  global $gemini_base_url, $labwiki_base_url;
  global $disabled;
  global $sa_url, $user;
  global $portal_max_slice_renewal_days;

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
  print "<tr class='slicetablerow'><td><a href='$slice_url'>$slice_name</a></td>";

  $slice_project_id = $slice[SA_ARGUMENT::PROJECT_ID];                 

  // There's an odd edge case in which a project has expired 
  // but some slice of the project has not. In this case, $project_objects may not
  // contain the project of the slice. If so, list the project using something else.
  if (!array_key_exists($slice_project_id, $project_objects)) {
    $slice_project_name = "-Expired Project-";
    $project_expiration = "";
  } else {
    $project = $project_objects[$slice_project_id];
    $project_expiration = $project[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION];
    $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  }
  print "<td><a href='project.php?project_id=$slice_project_id'>$slice_project_name</a></td>";

  // Slice owner name
  $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
  $slice_owner_name = $slice_owner_names[$slice_owner_id];
  print "<td>$slice_owner_name</td>";

  // Slice expiration
  $slice_exp_date = $slice[SA_ARGUMENT::EXPIRATION];
  $slice_exp_hours = get_time_diff($slice_exp_date);
  $slice_exp_str = dateUIFormat($slice_exp_date);
  $slice_exp_pretty_str = "In <b>" . get_time_diff_string($slice_exp_hours) . "</b>";
  $slice_exp_color = get_urgency_color($slice_exp_hours);
  $slice_exp_icon = get_urgency_icon($slice_exp_hours);

  print "<td><span title='$slice_exp_str'>$slice_exp_pretty_str ";
  print "<i class='material-icons' style='color:$slice_exp_color; font-size: 18px;'>$slice_exp_icon</i></span></td>";

  // Next resource expiration  
  $slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
  $resource_count = count($slivers);
  if ($resource_count == 0) {
    $resource_exp_str = "";
    $resource_exp_pretty_str = "<i>No resources</i>";
    $resource_exp_color = "#5F584E";
    $resource_exp_icon = "";
    $resource_exp_hours = 1000000;
  } else {
    $first_sliver = reset($slivers);
    $next_exp = new DateTime($first_sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
    foreach ($slivers as $sliver) {
      $this_date = new DateTime($sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_EXPIRATION]);
      if ($next_exp > $this_date) {
        $next_exp = $this_date;
      }
    }
    $resource_exp_str = dateUIFormat($next_exp);
    $resource_exp_hours = get_time_diff($resource_exp_str);
    $resource_exp_pretty_str = "In <b>" . get_time_diff_string($resource_exp_hours) . "</b>";
    $resource_exp_color = get_urgency_color($resource_exp_hours);
    $resource_exp_icon = get_urgency_icon($resource_exp_hours);
  }
  print "<td><span title='$resource_exp_str'>$resource_exp_pretty_str ";
  print "<i class='material-icons' style='color:$resource_exp_color; font-size: 18px;'>$resource_exp_icon</i></span></td>";

  // Slice actions
  $add_url = $resource_base_url . $query;
  $remove_url = $delete_sliver_base_url . $query;
  $listres_url = $listres_base_url . $query;
  $gemini_url = $gemini_base_url . $query;
  $labwiki_url = $labwiki_base_url . $query;

  print "<td style='text-align: center;'>";
  print "<ul class='selectorcontainer slicetableactions' style='margin: 0px;'><li class='has-sub selector' style='float:none;'>";
  print "<span class='selectorshown'>Actions</span><ul class='submenu'>";
  print "<li><a href='$slice_url'>Manage slice</a></li>";

  if ($user->isAllowed(SA_ACTION::ADD_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
    print "<li><a href='$add_url'>Add resources</a></li>";
  }

  $dashboard_max_renewal_days = 7;

  $renewal_days = min($dashboard_max_renewal_days, $portal_max_slice_renewal_days);
  if ($project_expiration) {
    $project_expiration_dt = new DateTime($project_expiration);
    $now_dt = new DateTime();
    $difference = $project_expiration_dt->diff($now_dt);
    $renewal_days = $difference->days;
    $renewal_days = min($renewal_days, $portal_max_slice_renewal_days, $dashboard_max_renewal_days);
  }

  $slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);

  $renewal_hours = 24 * $renewal_days;
  $disable_renewal = "";
  if ($slice_exp_hours > $renewal_hours && $resource_exp_hours > $renewal_hours) {
    $disable_renewal = "class='disabledaction'";
  }

  if ($resource_count > 0) {
    print "<li><a $disable_renewal onclick='renew_slice(\"$slice_id\", $renewal_days, $resource_count, $slice_exp_hours, $resource_exp_hours);'>Renew resources ($renewal_days days)</a></li>";
    if ($user->isAllowed(SA_ACTION::GET_SLICE_CREDENTIAL, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
      print "<li><a onclick='info_set_location(\"$slice_id\", \"$listres_url\")'>Resource details</a></li>";
    }
    if ($user->isAllowed(SA_ACTION::DELETE_SLIVERS, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
      print "<li><a onclick='info_set_location(\"$slice_id\", \"$remove_url\")'>Delete resources</a></li>";
    }
  }

  print "</ul></li></ul>";

  print "</td></tr>\n";
}

print "<script src=\"tool-slices.js\"></script>";

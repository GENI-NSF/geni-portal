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

require_once("user.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("sa_client.php");
require_once("pa_client.php");
require_once("util.php");
require_once("proj_slice_member.php");
include("services.php");

// String to disable button or other active element
$disabled = "disabled = " . '"' . "disabled" . '"'; 

if(!isset($project_objects) || !isset($slice_objects) || 
   !isset($member_objects) || !isset($project_slice_map)) 
{
  $retVal  = get_project_slice_member_info($sa_url, $ma_url, $user, True);
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


//foreach($slice_objects as $so) { error_log("SO = " . print_r($so, true)); }
//error_log("SLICE_OBJECTS = " . print_r($slice_objects, true));
//error_log("MAP = " . print_r($project_slice_map, true));
//error_log("MY_SLICE_OBJECTS = " . print_r($my_slice_objects, true));
//error_log("PROJECT_OBJECTS " . print_r($project_objects, true));

$expired_slices = array();
$unexpired_slices = array();
foreach($my_slice_objects as $slice) {
  // error_log("SLICE = " . print_r($slice, true));
  $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
  $expired = $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
  if($expired == 't') 
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
//error_log("expired_slices ".print_r($expired_slices, true));
//error_log("unexpired_slices ".print_r($unexpired_slices, true));

if (count($my_slice_objects) > 0) {

  print "\n<table>\n";
  print ("<tr><th>Slice Name</th>");
  print ("<th>Project</th>");
  print ("<th>Slice Expiration</th>");
  print ("<th>Slice Owner</th>"
         . "<th>Actions</th>");
  if ($portal_enable_abac) {
    print "<th>ABAC Credential</th>";
  }
  print "</tr>\n";

  $base_url = relative_url("slicecred.php?");
  $slice_base_url = relative_url("slice.php?");
  $listres_base_url = relative_url("listresources.php?");
  $resource_base_url = relative_url("slice-add-resources.php?");
  $delete_sliver_base_url = relative_url("confirm-sliverdelete.php?");
  $sliver_status_base_url = relative_url("sliverstatus.php?");
  $abac_url = relative_url("sliceabac.php?");
  $flack_url = relative_url("flack.php?");
  $gemini_base_url = relative_url("gemini.php?");
  $num_slices = count($my_slice_objects);
  if ($num_slices==1) {
      print "<p><i>You have access to <b>1</b> slice.</i></p>";
  } else {
       print "<p><i>You have access to <b>".$num_slices."</b> slices.</i></p>";
  }

  foreach ($my_slice_objects as $slice) {
    $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
    $slice_expired = 'f';
    //    error_log("SLICE = " . print_r($slice, true));
    if (array_key_exists(SA_SLICE_TABLE_FIELDNAME::EXPIRED, $slice)) {
      $slice_expired = $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED];
    }
    $isSliceExpired = False;
    $disable_buttons_str = "";
    if (isset($slice_expired) && $slice_expired == 't') {
      $isSliceExpired = True;
      $disable_buttons_str = " disabled";
    }
    $args['slice_id'] = $slice_id;
    $query = http_build_query($args);
    $query = $query;
    $slicecred_url = $base_url . $query;
    $slice_url = $slice_base_url . $query;
    $sliceresource_url = $resource_base_url . $query;
    $delete_sliver_url = $delete_sliver_base_url . $query;
    $sliver_status_url = $sliver_status_base_url . $query;
    $sliceabac_url = $abac_url . $query;
    $sliceflack_url = $flack_url . $query;
    $listres_url = $listres_base_url . $query;
    $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
    $expiration_db = $slice[SA_ARGUMENT::EXPIRATION];
    $expiration = dateUIFormat($expiration_db);
    $slice_project_id = $slice[SA_ARGUMENT::PROJECT_ID];
    $gemini_url = $gemini_base_url . $query;

    // Determine privileges to this slice for this user
    $add_slivers_privilege = $user->isAllowed(SA_ACTION::ADD_SLIVERS,
					      CS_CONTEXT_TYPE::SLICE, 
					      $slice_id);
    $add_slivers_disabled = "";
    if(!$add_slivers_privilege or $isSliceExpired) { $add_slivers_disabled = $disabled; }
    
    $delete_slivers_privilege = $user->isAllowed(SA_ACTION::DELETE_SLIVERS,
						 CS_CONTEXT_TYPE::SLICE, 
						 $slice_id);
    $delete_slivers_disabled = "";
    if(!$delete_slivers_privilege or $isSliceExpired) { $delete_slivers_disabled = $disabled; }

    $renew_slice_privilege = $user->isAllowed(SA_ACTION::RENEW_SLICE,
					      CS_CONTEXT_TYPE::SLICE, 
					      $slice_id);
    $renew_disabled = "";
    if(!$renew_slice_privilege) { $renew_disabled = $disabled; }

    $lookup_slice_privilege = $user->isAllowed(SA_ACTION::LOOKUP_SLICE, 
					       CS_CONTEXT_TYPE::SLICE, 
					       $slice_id);

    $get_slice_credential_privilege = $user->isAllowed(SA_ACTION::GET_SLICE_CREDENTIAL, 
						       CS_CONTEXT_TYPE::SLICE, $slice_id);
    $get_slice_credential_disable_buttons = "";
    if(!$get_slice_credential_privilege) {$get_slice_credential_disable_buttons = $disabled; }



					       
    // Lookup the project for this project ID
    $slice_project_id = $slice[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
    $project = $project_objects[ $slice_project_id ];

    $slice_project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
    $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
    $slice_owner_name = $slice_owner_names[$slice_owner_id];
    print "<tr>"
      . ("<td><a href=\"$slice_url\">" . htmlentities($slice_name)
         . "</a></td>");
    print "<td><a href=\"project.php?project_id=$slice_project_id\">" . htmlentities($slice_project_name) . "</a></td>";
    print "<td>" . htmlentities($expiration) . "</td>";
    print "<td><a href=\"slice-member.php?slice_id=$slice_id&member_id=$slice_owner_id\">" . htmlentities($slice_owner_name) . "</a></td>";
    print ("<td><button $add_slivers_disabled onClick=\"window.location='$sliceresource_url'\"><b>Add Resources</b></button>");
    print ("<button onClick=\"window.location='$sliver_status_url'\" $get_slice_credential_disable_buttons><b>Resource Status</b></button>");
    print ("<button title='Login info, etc' onClick=\"window.location='$listres_url'\" $get_slice_credential_disable_buttons><b>Details</b></button>");
    print ("<button $delete_slivers_disabled onClick=\"window.location='$delete_sliver_url'\"><b>Delete Resources</b></button>");
    $hostname = $_SERVER['SERVER_NAME'];
    print "<button $add_slivers_disabled onClick=\"window.open('$sliceflack_url')\"><image width=\"40\" src=\"https://$hostname/images/pgfc-screenshot.jpg\"/><br/>Launch Flack</button>";

      print "<button $add_slivers_disabled onClick=\"window.open('$gemini_url')\" $disable_buttons_str><b>GENI Desktop</b></button>";

    print "</td>";
    if ($portal_enable_abac) {
      print "<td><button onClick=\"window.location='$sliceabac_url'\" $disable_buttons_str><b>Get ABAC Credential</b></button></td>";
    }
    print "</tr>\n";
  }
  print "</table>\n";
} else {
  if (isset($project_id) && uuid_is_valid($project_id)) {
    print "<i>You do not have access to any slices in this project.</i><br/>\n";
  } else {
    print "<i>You do not have access to any slices.</i><br/>\n";
  }
}

<?php
//----------------------------------------------------------------------
// Copyright (c) 2014-2015 Raytheon BBN Technologies
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

function build_jacks_viewer()
{
	$output = "<script src='jacks-app.js'></script>";
  $output .= "<div id='jacks-status'><p>Starting Jacks...</p></div>";
  $output .= "<div id='jacks-status-history'></div>";
  $output .= "<div id='jacks-pane' class='jacks'></div>";

  $output .= "<div id='jacks-buttons'>";
  $output .= "</div>";

  return $output;
}

function setup_jacks_slice_context()
{
  global $user;
  global $slice;
  global $slice_id;
  global $slice_name;
  global $ma_url;
  global $sa_url;
  global $all_ams;
  global $slice_ams;
  global $slice_urn;
  global $slice_expiration;

  unset($slice);
  include("tool-lookupids.php");

  if (! isset($sa_url)) {
    $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  }

  if (! isset($ma_url)) {
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  }

if (isset($slice)) {
  $slice_name = $slice[SA_ARGUMENT::SLICE_NAME];
  //  error_log("SLICE  = " . print_r($slice, true));
  $slice_desc = $slice[SA_ARGUMENT::SLICE_DESCRIPTION];
  $slice_creation_db = $slice[SA_ARGUMENT::CREATION];
  $slice_creation = dateUIFormat($slice_creation_db);
  $slice_expiration_db = $slice[SA_ARGUMENT::EXPIRATION];
  $slice_expiration = dateUIFormat($slice_expiration_db);
  $slice_date_expiration = dateOnlyUIFormat($slice_expiration_db);
  $slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
  $slice_owner_id = $slice[SA_ARGUMENT::OWNER_ID];
  $owner = $user->fetchMember($slice_owner_id);
  $slice_owner_name = $owner->prettyName();
  $owner_email = $owner->email();

  $project_name = $project[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  //error_log("slice project_name result: $project_name\n");
  // Fill in members of slice member table
  $members = get_slice_members($sa_url, $user, $slice_id);
  $member_names = lookup_member_names_for_rows($ma_url, $user, $members, 
					       SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID);

  //find only ams that slice has resources on
  $slivers = lookup_sliver_info_by_slice($sa_url, $user, $slice_urn);
  //find aggregates to be able to return just am_id
  $all_aggs = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $aggs_with_resources = Array();

  //do the comparison and find ams
  foreach($slivers as $sliver)
  {
    foreach($all_aggs as $agg)
    {
       if($sliver[SA_SLIVER_INFO_TABLE_FIELDNAME::SLIVER_INFO_AGGREGATE_URN] == $agg[SR_TABLE_FIELDNAME::SERVICE_URN])
       {
          $aggs_with_resources[] = $agg[SR_TABLE_FIELDNAME::SERVICE_ID];
          break;
       }
    }
  }
  //return unique ids
  $slice_ams = array_unique($aggs_with_resources, SORT_REGULAR);

} else {
  print "Unable to load slice<br/>\n";
  $_SESSION['lasterror'] = "Unable to load slice";
  relative_redirect("home.php");
  exit();
}

if (! isset($all_ams)) {
  $am_list = get_services_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
  $all_ams = array();
  foreach ($am_list as $am) 
  {
    $single_am = array();
    $service_id = $am[SR_TABLE_FIELDNAME::SERVICE_ID];
    $single_am['name'] = $am[SR_TABLE_FIELDNAME::SERVICE_NAME];
    $single_am['url'] = $am[SR_TABLE_FIELDNAME::SERVICE_URL];
    $single_am['urn'] = $am[SR_TABLE_FIELDNAME::SERVICE_URN];
    $all_ams[$service_id] = $single_am;
  }   
}

}
?>

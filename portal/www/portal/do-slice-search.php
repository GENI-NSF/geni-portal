<?php
//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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
require_once("db_utils.php");
require_once("ma_client.php");
require_once("sa_client.php");
require_once("pa_client.php");
require_once("cs_constants.php");
require_once("util.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  exit();
}

// This search functionality is for OPERATORS only
if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

// Handle the request, determine if a search can be performed and which to be performed
if (array_key_exists('term', $_REQUEST)) {
  $term = $_REQUEST['term'];
  if (array_key_exists('search_type', $_REQUEST)) {
    $search_type = $_REQUEST['search_type'];
  } else {
    $search_type = "urn";
  }
  make_results_table(search_for_slices($term, $search_type, $user, $ma_url, $sa_url), $user, $ma_url, $sa_url);
} else {
  print "Couldn't complete empty search.";
}


// Returns an array of slice detail objects from the SA for slices which match
// the $term for the $search_type
function search_for_slices($term, $search_type, $signer, $ma_url, $sa_url) 
{
  if ($search_type == "urn") {
    $results = lookup_slice_by_urn($sa_url, $signer, $term);
    return lookup_slice_details($sa_url, $signer, array($results[0]));
  } else {
    if ($search_type == "owner_email") {
      $email_results = lookup_members_by_email($ma_url, $signer, array($term));
      $member_ids_arr = $email_results[$term];
      $member_id = $member_ids_arr[0];
      $slices = get_slices_for_member($sa_url, $signer, $member_id, true); 
      $slice_ids = array();
      foreach ($slices as $slice) {
        if($slice[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE] == CS_ATTRIBUTE_TYPE::LEAD
          && $slice[SA_SLICE_TABLE_FIELDNAME::EXPIRED] != 1){
          $slice_ids [] = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID]; 
        }
      }
      return lookup_slice_details($sa_url, $signer, $slice_ids);
    } else {
      print "Searching by $search_type not yet implemented";
    }
  }
}

// Prints a pair of rows for each slice in $slices with information about that slice
function make_results_table($slices, $signer, $ma_url, $sa_url) 
{
  print "<h3>Results</h3>";
  if (count($slices) == 0) {
    print "<p>No results found. (warning: no partial matches!)</p>";
  } else {
    print "<table><tr><th>Slice name</th><th>Owner name</th><th>Owner email</th><th>Expiration</th><th>Actions</th></tr>";
    foreach ($slices as $slice) {
      $name = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME];
      $expiration  = dateUIFormat($slice[SA_SLICE_TABLE_FIELDNAME::EXPIRATION]);
      $owner_id = $slice[SA_SLICE_TABLE_FIELDNAME::OWNER_ID];
      $owner_detail_list = lookup_member_details($ma_url, $signer, array($owner_id));
      $owner_details = $owner_detail_list[$owner_id];
      $member = new Member($owner_id);
      $member->init_from_record($owner_details);
      $owner_name = $member -> prettyName();
      $owner_email = $owner_details[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
      $mailto_link = "<a href='mailto:$owner_email'>$owner_email</a>";

      print "<tr><td>$name</td><td>$owner_name</td><td>$mailto_link</td><td>$expiration</td>";
      print "<td><button onclick='expand_info(this);'>More info</button>";
      print "<button class='hideinfo' onclick='hide_info(this);' style='display:none;'>Close</button></td></tr>";

      $project_info = get_project_info($slice, $signer, $ma_url, $sa_url);
      $aggregate_info = get_aggregate_info($slice, $signer, $sa_url);
      $member_info = get_member_info($slice, $signer, $ma_url, $sa_url);

      print "<tr style='display:none'><td style='vertical-align:top'>$project_info</td>";
      print "<td colspan='2' style='vertical-align:top'>$aggregate_info</td>";
      print "<td colspan='2' style='vertical-align:top'>$member_info</td> </tr>";
    }
    print "</table>";
  }
}

// Returns details about the parent project for $slice
function get_project_info($slice, $signer, $ma_url, $sa_url) 
{
  $project_id = $slice[SA_SLICE_TABLE_FIELDNAME::PROJECT_ID];
  $project_details = lookup_project($sa_url, $signer, $project_id);
  $project_name = $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
  $project_expiration = dateUIFormat($project_details[PA_PROJECT_TABLE_FIELDNAME::EXPIRATION]);
  $project_description = $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_PURPOSE];
  $project_lead_id = $project_details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];
  $project_lead_detail_list = lookup_member_details($ma_url, $signer, array($project_lead_id));
  $project_lead_details = $project_lead_detail_list[$project_lead_id];
  $member = new Member($project_lead_id);
  $member->init_from_record($project_lead_details);
  $project_lead_name = $member -> prettyName();
  $project_lead_email = $project_lead_details[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
  $mailto_link = "<a href='mailto:$project_lead_email'>$project_lead_email</a>";
  $project_lead_number = $project_lead_details[MA_ATTRIBUTE_NAME::TELEPHONE_NUMBER];  
  $project_info = "<b style='text-decoration: underline;'>Parent project</b><br>";
  $project_info .= "<b>Name: </b>$project_name<br>" .
                   "<b>Expiration: </b>$project_expiration<br>" .
                   "<b>Description: </b>$project_description<br>" .
                   "<b>Lead Name: </b>$project_lead_name<br>" . 
                   "<b>Lead Email: </b>$mailto_link<br>" . 
                   "<b>Lead Number: </b>$project_lead_number";
  return $project_info;
}

// Returns details about the aggregates where $slice has resources
function get_aggregate_info($slice, $signer, $sa_url) 
{
  $aggregates = aggregates_in_slice($sa_url, $signer, $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_URN]);
  $aggregate_info = "<b style='text-decoration: underline;'>Aggregates</b><br>";
  foreach ($aggregates as $aggregate) {
    $aggregate_info .= $aggregate['service_name'] . ": " . $aggregate['service_description'] . "<br>";
  }
  if (count($aggregates) == 0) {
    $aggregate_info .= "<i>No aggregates for this slice</i>";
  }
  return $aggregate_info;
}

// Returns details about the members of $slice
function get_member_info($slice, $signer, $ma_url, $sa_url)
{
  $slice_id = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
  $slice_members = get_slice_members($sa_url, $signer, $slice_id);
  $member_ids = array();
  foreach ($slice_members as $slice_member) {
    if($slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::ROLE] == CS_ATTRIBUTE_TYPE::LEAD){
      $slice_lead_id = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID]; 
    }
    $member_ids[] = $slice_member[SA_SLICE_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
  }
  $member_names = lookup_member_names($ma_url, $signer, $member_ids);
  $member_info = "<b style='text-decoration: underline;'>Members</b><br>";
  $member_info .= "Lead: " . $member_names[$slice_lead_id] . "<br>";
  foreach ($member_names as $member_id => $member_name) {
    if($member_id != $slice_lead_id){
      $member_info .= $member_name . "<br>";
    }
  }
  return $member_info;
}

?>

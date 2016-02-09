<?php
//----------------------------------------------------------------------
// Copyright (c) 2015-2016 Raytheon BBN Technologies
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
require_once("util.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  exit();
}

// This search functionality is for OPERATORS only
if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

// Handle the request, determine if a search can be performed and which to do
if (array_key_exists('term', $_REQUEST)) {
  $term = $_REQUEST['term'];
  if (array_key_exists('search_type', $_REQUEST)) {
    $search_type = $_REQUEST['search_type'];
  } else {
    $search_type = "username";
  }
  make_result_table(search_for_users($term, $search_type, $user, $ma_url), $user, $ma_url);
} else {
  print "Couldn't complete empty search.";
}

// Returns an array of uids who match the $term for the $search_type
function search_for_users($term, $search_type, $signer, $ma_url)
{
  if ($search_type == "email") {
    $results = lookup_members_by_email($ma_url, $signer, array($term));
    if(array_key_exists($term, $results)) {
      return $results[$term];
    } else {
      return array();
    }
  } else {
    if($search_type == "lastname") {
      $searchkey = "MEMBER_LASTNAME";
    } else {
      $searchkey = "MEMBER_USERNAME";
    }
    $results = ma_lookup_members_by_identifying($ma_url, $signer, $searchkey, $term);
    $ids = array();
    foreach ($results as $member) {
      $ids[] = $member->member_id;
    }
    return $ids;
  }
}

// Prints a pair of table rows for each user in $user_ids with their information 
function make_result_table($user_ids, $signer, $ma_url)
{
  print "<h3>Results</h3>";
  if (count($user_ids) == 0) {
    print "<p>No results found. (warning: no partial matches!)</p>";
  } else {
    print "<table><tr><th>Name</th><th>Username</th><th>Email</th><th>URN</th><th>UUID</th><th>Actions</th></tr>";
    $requester_details = lookup_member_details($ma_url, $signer, $user_ids); 
    foreach ($user_ids as $user_id) {
      $user_details = $requester_details[$user_id];  
      $username = $user_details[MA_ATTRIBUTE_NAME::USERNAME];
      $member = new Member($user_id);
      $member->init_from_record($user_details);
      $name = $member -> prettyName();
      $email = $user_details[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
      $urn = $user_details[MA_ATTRIBUTE_NAME::URN];
      $mail_to = "<a href='mailto:$email'>$email</a>";
      print "<tr><td>$name</td><td>$username</td><td>$mail_to</td><td>$urn</td><td>$user_id</td>";
      print "<td><button onclick='expand_info(this);'>More info</button>";
      print "<button class='hideinfo' onclick='hide_info(this);' style='display:none;'>Close</button></td></tr>";
      $user_profile_info = get_user_profile_info($user_details, $name, $user_id);
      $user_project_info = get_user_project_info($user_id, $name, $signer);
      $user_slice_info = get_user_slice_info($user_id, $name, $signer);
      print "<tr style='display:none'>";
      print "<td colspan='3' style='vertical-align:top'>$user_profile_info</td>";
      print "<td style='vertical-align: top;'>$user_project_info</td>";
      print "<td style='vertical-align: top;'>$user_slice_info</td>";
      print "<td style='vertical-align: top;'><button onclick='disable_user(\"$name\", \"$urn\");'>Disable user</button></tr>";                    
    }
    print "</table>";
  }
}

// Returns a table entry with information about $name's profile
function get_user_profile_info($user_details, $name, $user_id)
{
  $affiliation = $user_details[MA_ATTRIBUTE_NAME::AFFILIATION];
  $reference = $user_details[MA_ATTRIBUTE_NAME::REFERENCE];
  $reason = $user_details[MA_ATTRIBUTE_NAME::REASON];
  $url = $user_details[MA_ATTRIBUTE_NAME::URL];
  $link = "<a href='$url'>$url</a>"; 
  $phone = $user_details[MA_ATTRIBUTE_NAME::TELEPHONE_NUMBER];
  $user_profile_info = "<b style='text-decoration: underline;'>$name's profile</b><br>";
  $user_profile_info .= "<b>Affiliation: </b>" . ($affiliation != "" ? $affiliation : "None")  . "<br>" .
                        "<b>Reason:      </b>" . ($reason      != "" ? $reason      : "None")  . "<br>" .
                        "<b>Reference:   </b>" . ($reference   != "" ? $reference   : "None")  . "<br>" .
                        "<b>Link:        </b>" . ($url         != "" ? $link        : "None")  . "<br>" .
                        "<b>Phone:       </b>" . ($phone       != "" ? $phone       : "None")  . "<br>";
  return $user_profile_info;
}

// Returns a table entry with information about $name's slices
function get_user_slice_info($user_id, $name, $signer)
{
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  $slices = get_slices_for_member($sa_url, $signer, $user_id, true); 
  $slice_data = "<b style='text-decoration: underline;'>$name's slices</b><br>";
  $slice_ids = array();
  foreach ($slices as $slice) {
    $slice_ids [] = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID]; 
  }
  $slice_info = lookup_slice_details($sa_url, $signer, $slice_ids);
  $active_slice_count = 0;
  foreach ($slice_info as $slice_urn => $slice_details) {
    if ($slice_details['expired'] != 1){
      $slice_data .= "<b>Slice name: </b>" . $slice_details[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME] . "<br>";
      $slice_data .= "<b>Slice URN: </b>$slice_urn<br>";
      $owner = $slice_details[SA_SLICE_TABLE_FIELDNAME::OWNER_ID] == $user_id ? "yes" : "no";
      $slice_data .= "<b>Owner? </b>$owner<hr style='height: 1px; background-color: #5F584E; margin: 3px'>";
      $active_slice_count++;
    }
  }
  if ($active_slice_count == 0) {
    $slice_data .= "<i>user has no slices</i>";
  }
  return $slice_data;
}

// Returns a table entry with information about $name's projects
function get_user_project_info($user_id, $name, $signer)
{
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  $projects = get_projects_for_member($sa_url, $signer, $user_id, true);
  $project_info = lookup_project_details($sa_url, $signer, $projects);
  $project_data = "<b style='text-decoration: underline;'>$name's projects</b><br>";
  foreach ($project_info as $project_id => $project_details) {
    if ($project_details['expired'] != 1) {
      $project_data .= "<b>Project name: </b>" . $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . "<br>";
      $project_data .= $project_details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] == $user_id ? "<b>$name is lead on this project</b><br>" : "";
      $project_data .= "<button onclick='remove_from_project(\"$user_id\", \"$project_id\");'>Remove</button>";
      $project_data .= "<hr style='height: 1px; background-color: #5F584E; margin: 3px'>";
    }
  }
  if (count($project_info) == 0) {
    $project_data .= "<i>user has no projects</i>";
  }
  return $project_data;
}

?>

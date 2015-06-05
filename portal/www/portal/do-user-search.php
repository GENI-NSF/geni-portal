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
require_once("util.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (!$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, null)) {
  exit();
}

$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
if (array_key_exists('term', $_REQUEST)) {
  $term = $_REQUEST['term'];
  if (array_key_exists('search_type', $_REQUEST)){
    $search_type = $_REQUEST['search_type'];
  } else {
    $search_type = "username";
  }
  print_results(search_for_users($term, $search_type));
} else {
  print "Couldn't complete empty search.";
}

function search_for_users($term, $search_type) {
  global $user;
  global $ma_url;
  if ($search_type == "email") {
    $results = lookup_members_by_email($ma_url, $user, array($term));
    return $results[$term];
  } else {
    if($search_type == "lastname"){
      $searchkey = "MEMBER_LASTNAME";
    } else {
      $searchkey = "MEMBER_USERNAME";
    }
    $results = ma_lookup_members_by_identifying($ma_url, $user, $searchkey, $term);
    $ids = array();
    foreach ($results as $member) {
      $ids[] = $member->member_id;
    }
    return $ids;
  }
}

function print_results($user_ids) {
  global $user;
  global $ma_url; 
  print "<h3>Results</h3>";
  if (count($user_ids) == 0) {
    print "<p>No results found. (warning: no partial matches!)</p>";
  } else {
    print "<table><tr><th>name</th><th>Username</th><th>Email</th><th>UUID</th><th>URN</th><th>Actions</th></tr>";
    $requester_details = lookup_member_details($ma_url, $user, $user_ids); 
    foreach ($user_ids as $user_id) {
      $details = $requester_details[$user_id];  
      $username = $details[MA_ATTRIBUTE_NAME::USERNAME];
      $member = new Member($user_id);
      $member->init_from_record($details);
      $name = $member -> prettyName();
      $email = $details[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS];
      $urn = $details[MA_ATTRIBUTE_NAME::URN];
      $mail_to = "<a href='mailto:$email'>$email</a>";
      print "<tr><td>$name</td><td>$username</td><td>$mail_to</td><td>$user_id</td><td>$urn</td>";
      print "<td><button onclick='expand_info(this);'>More info</button></td></tr>";
      $user_profile_info = get_user_profile_info($details, $name);
      $user_project_info = get_user_project_info($user_id, $name);
      $user_slice_info = get_user_slice_info($user_id, $name);
      print "<tr style='display:none'>";
      print "<td colspan='3' style='vertical-align:top'>$user_profile_info</td>";
      print "<td style='vertical-align: top;'>$user_project_info</td>";
      print "<td style='vertical-align: top;'>$user_slice_info</td>";                    
      print "<td><button class='hideinfo' onclick='hide_info(this);'>close</button></td></tr>";
    }
    print "</table>";
  }
}

function get_user_profile_info($details, $name){
  $affiliation = $details[MA_ATTRIBUTE_NAME::AFFILIATION];
  $reference = $details[MA_ATTRIBUTE_NAME::REFERENCE];
  $reason = $details[MA_ATTRIBUTE_NAME::REASON];
  $url = $details[MA_ATTRIBUTE_NAME::URL];
  $phone = $details[MA_ATTRIBUTE_NAME::TELEPHONE_NUMBER];
  $link = "<a href='" . $url . "'>" . $url . "</a>"; 
  $user_profile_info = "<b style='text-decoration: underline;'>$name's profile</b><br>";
  $user_profile_info .= "<b>Affiliation: </b>" . ($affiliation != "" ? $affiliation : "None")  . "<br>" .
          "<b>Reason:      </b>" . ($reason      != "" ? $reason      : "None")  . "<br>" .
          "<b>Reference:   </b>" . ($reference   != "" ? $reference   : "None")  . "<br>" .
          "<b>Link:        </b>" . ($url         != "" ? $link        : "None")  . "<br>" .
          "<b>Phone:       </b>" . ($phone       != "" ? $phone       : "None")  . "<br>";
  return $user_profile_info;
}


function get_user_slice_info($user_id, $name){
  global $user;
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  $slices = get_slices_for_member($sa_url, $user, $user_id, true); 
  $slice_data = "<b style='text-decoration: underline;'>$name's slices</b><br>";
  $slice_ids = array();
  foreach ($slices as $slice) {
    $slice_ids [] = $slice[SA_SLICE_TABLE_FIELDNAME::SLICE_ID]; 
  }
  $slice_info = lookup_slice_details($sa_url, $user, $slice_ids);
  foreach ($slice_info as $slice_urn => $slice_details) {
    if($slice_details['expired'] != 1){
      $slice_data .= "<b>Slice name: </b>" . $slice_details[SA_SLICE_TABLE_FIELDNAME::SLICE_NAME] . "<br>";
      $slice_data .= "<b>Slice URN: </b>" . $slice_urn . "<br>";
      $owner = $slice_details[SA_SLICE_TABLE_FIELDNAME::OWNER_ID] == $user_id ? "yes" : "no";
      $slice_data .= "<b>Owner?: </b> " . $owner . "<hr style='height: 1px; background-color: #5F584E; margin: 3px'>";
    }
  }
  if(count($slice_info) == 0) {
    return "<i>user has no slices</i>";
  }
  return $slice_data;
}

function get_user_project_info($user_id, $name){
  global $user;
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  $projects = get_projects_for_member($sa_url, $user, $user_id, true);
  $project_info = lookup_project_details($sa_url, $user, $projects);
  $project_data = "<b style='text-decoration: underline;'>$name's projects</b><br>";
  foreach ($project_info as $project_urn => $project_details) {
    if($project_details['expired'] != 1){
      $project_data .= "<b>Project name: </b>" . $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME] . "<br>";
      $project_data .= "<b>Project URN: </b>" . $project_urn . "<br>";
      $lead = $project_details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID] == $user_id ? "yes" : "no";
      $project_data .= "<b>Lead?: </b> " . $lead . "<hr style='height: 1px; background-color: #5F584E; margin: 3px'>";
    }
  }
  if(count($project_info) == 0) {
    return "<i>user has no projects</i>";
  }
  return $project_data;
}


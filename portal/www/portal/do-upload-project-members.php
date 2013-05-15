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
require_once("header.php");
require_once('util.php');
require_once('pa_constants.php');
require_once('sa_constants.php');
require_once('pa_client.php');
require_once('sa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');


// Check the selections from the upload-project-members are valid
// If so, add the recognized members and send emails to the invited ones.


$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

if (! isset($pa_url)) {
  $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY);
}

if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}

// error_log("REQUEST = " . print_r($_REQUEST, true));


$project_id = $_REQUEST['project_id'];
unset($_REQUEST['project_id']);

$project_details = lookup_project($pa_url, $user, $project_id);
$project_name = $project_details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME];
$lead_id = $project_details[PA_PROJECT_TABLE_FIELDNAME::LEAD_ID];

$lead_name = lookup_member_names($ma_url, $user, array($lead_id));
$lead_name = $lead_name[$lead_id];

$selections = $_REQUEST;

$num_members_added = 0;
$num_members_invited = 0;

foreach($selections as $email_name => $attribs) {
  // Turn commas back to periods, and tabs back to spaces
  $email_name = str_replace(",", ".", $email_name);
  $email_name = str_replace("\t", " ", $email_name);
  $email_name_parts = explode(":", $email_name);
  $email = $email_name_parts[0];
  $user_name = $email_name_parts[1];
  $attrib_array = explode(",", $attribs);
  $role = intval(trim($attrib_array[0]));
  $member_id = trim($attrib_array[1]);

  if ($role <= 0) continue; // This is a 'do not add' stipulation

  //  error_log("Selection : $email => $role | $member_id |" . strlen($member_id));

  // If they're already a member, add to project and send confirmation email
  if(strlen($member_id) > 0) {
    add_project_member($pa_url, $user, $project_id, $member_id, $role);
    $num_members_added = $num_members_added + 1;
    
  } else {
    $invite_data = invite_member($pa_url, $user, $project_id, $role);
    $invite_id = $invite_data[PA_PROJECT_MEMBER_INVITATION_TABLE_FIELDNAME::INVITE_ID];
    // If not, send an inviation email
    $email_subject = "Invitation to project: " . $project_name;
    $hostname = $_SERVER['SERVER_NAME'];
    $confirmation_url = "https://$hostname/secure/accept-project-invite?invite_id=$invite_id&project_name=$project_name";
    $email_text = "Dear $user_name, \n" . 
      "You are invited to join GENI project $project_name whose lead is $lead_name. " . 
      "If you would like to join the project and have a GENI account, click on this URL " . $confirmation_url . ". " .
      "If you have a GENI account, once you authenticate you will directed to a page to confirm your choice to join the project. " .
      "If you do not have a GENI account, contact help@geni.net to sign up for a GENI account.";

    //    error_log("EMAIL_ADDRESS : $email");
    //    error_log("EMAIL_SUBJECT : $email_subject");
    //    error_log("EMAIL_TEXT : $email_text");

    mail($email, $email_subject, $email_text);
    $num_members_invited = $num_members_invited + 1;
  }
}

$_SESSION['lastmessage'] = "Added $num_members_added members; Invited $num_members_invited members";

relative_redirect("project.php?project_id=".$project_id);

?>


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
require_once("header.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("ma_client.php");
require_once('logging_client.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");
show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-breadcrumbs.php");

if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
}

//Invite to Join a Project
//Generic page to 1+ GENI members with a link to the join-this-project
//page, telling them to come join your project
//-- include optional text area for more reasoning
//- include project info

$invitees = null;
$error = null;
$message = '';
$skips = "";
$in_projects = '';
if (array_key_exists("to", $_REQUEST)) {
  $invitee_string = $_REQUEST["to"];
  // split on ,;
  $invitees = preg_split("/[\s,;]+/", $invitee_string);
  for ($i = 0; $i < count($invitees); $i++) {
    $invitees[$i] = trim($invitees[$i]);
    $invitees[$i] = filter_var($invitees[$i], FILTER_SANITIZE_EMAIL);
    if (! filter_var($invitees[$i], FILTER_VALIDATE_EMAIL)) {
      //error_log("invite-to-project Skipping invitee " . $invitees[$i] . " that seems invalid");
      if ($skips !== "")
	$skips = $skips . ", ";
      $skips = $skips . $invitees[$i];
      $invitees[$i] = null;
    }
    //Now check if invitee is already in project
    $members_by_email = lookup_members_by_email($ma_url, $user, $invitees);
    $members_by_email = array_change_key_case($members_by_email,CASE_LOWER);

    $project_members = get_project_members($sa_url, $user, $project_id);
    $project_member_ids = array();
    foreach($project_members as $project_member) {
      $project_member_id = $project_member[PA_PROJECT_MEMBER_TABLE_FIELDNAME::MEMBER_ID];
      $project_member_ids[] = $project_member_id;
    }
    foreach($members_by_email as $member_array) {
      $member_id = $member_array[0];
      $is_member = ($member_id != null && in_array($member_id, $project_member_ids));
      if ($is_member) {
	if ($in_projects !== "")
	  $in_projects = $in_projects . ", ";
	$in_projects = $in_projects . $invitees[$i];
	$invitees[$i] = null;
      }
      // FIXME: See http://www.linuxjournal.com/article/9585
    }
  }
  if (array_key_exists("message", $_REQUEST)) {
    $message = $_REQUEST["message"];
  }
}

if (isset($invitees) && ! is_null($invitees) && (!isset($error) || is_null($error))) {
  // Send the email
  $hostname = $_SERVER['SERVER_NAME'];
  $message .= "\nTo join my project, go here: 
      https://$hostname/secure/join-this-project.php?project_id=$project_id

Once you request to join, I'll get an email to come back to the GENI portal and approve you.
Then you can work with slices in my project.

If you are new to GENI:
GENI is an NSF funded virtual testbed supporting computer networking research and innovation. 
I use GENI, and you should too.
For more info on GENI, see: http://www.geni.net
To get started using GENI, go to the GENI Portal: https://$hostname
You log in with your home university or college username, or request a GENI-specific account.

Thank you,\n" . $user->prettyName() . "\n";

  $to = implode(", ", $invitees);
  if (preg_match("/ , /", $to)) {
    $to = preg_replace("/ , /", " ", $to);
  }
  if (preg_match("/^, /", $to)) {
    $to = preg_replace("/^, /", "", $to);
  }
  if (preg_match("/, $/", $to)) {
    $to = preg_replace("/, $/", "", $to);
  }
  if ($to === "") {
    if ($in_projects !== "") {
      print "<p class='warn'>Skipped email addresses of project members: $in_projects</p>\n";
    }
    if ($skips !== "") {
      print "<p class='warn'>Skipped invalid email addresses: $skips</p>\n";
    }
    exit();
  }
  $email = $user->email();
  $name = $user->prettyName();

  $headers = "Reply-To: $email" . "\r\n" . "From: \"$name (via the GENI Portal)\" <www-data@gpolab.bbn.com>\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";


  mail($to,
       "Join my GENI project $project_name!",
       $message,
       $headers,
       "-f $email"); // This tells sendmail directly to resend the envelope-sender, so the portal users gets bounces

  $attributes = get_attribute_for_context(CS_CONTEXT_TYPE::PROJECT, $project_id);
  $msg = "$name invited people to project $project_name: $to";
  $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
  log_event($log_url, $user, $msg, $attributes);

  // Put up a page saying we invited them.
  print "<h1>Invite Someone to Project $project_name</h1>\n";
  print "<p>\n";
  print "<b>Sent</b> Project $project_name invitation to:<br/>\n" . "$to.</p>\n";
  if ($skips !== "") {
    print "<p class='warn'>Skipped invalid email addresses: $skips</p>\n";
  }
  if ($in_projects !== "") {
    print "<p class='warn'>Skipped email addresses of project members: $in_projects</p>\n";
  }
  $lines = explode("\r\n", $message);
  print "<p><b>Message</b>: </p><pre style='margin-left:80px;'>\n";
  foreach ($lines as $line) {
    print "$line\n";
  }
  print "</pre>";
  include("footer.php");
  exit();
}

print "<h1>Invite Someone to Project <i>$project_name</i></h1>\n";
print "<p>Invite your co-workers and friends to use your GENI project <i>$project_name</i>!</p>\n";

print "<p>For your co-workers or students to collaborate on experiments in GENI (share GENI slices), ";
print "they must be in your project <i>$project_name</i>. This page lets you invite them to join your project.</p>\n";
$hostname = $_SERVER['SERVER_NAME'];
print "<p>This form will send them an email with a link to a page to join your project <i>$project_name</i>. \n";
print "They will then request to join the project. You will get an email when they have done this. \n";
print "Then, you must approve them to join the project, and specify what kind of role they should have on the project.</p>\n";

print "<p>You can include a custom message explaining how you want to collaborate with them in GENI.</p>\n";
if (isset($error) && ! is_null($error)) {
  print $error;
}
//mailto:larry,dan?cc=mike&bcc=sue&subject=test&body=type+your&body=message+here
print "<form action=\"invite-to-project.php\">\n";
print "<input type=\"hidden\" name=\"project_id\" value=\"$project_id\"/>\n";
print "<h3>Email addresses of people to invite:</h3>\n";
print "<p><textarea name='to' cols=\"60\" rows=\"4\"></textarea></p>\n"; // FIXME: Need to ensure this is valid - JS?
print "<p>Addresses should be space, comma, or newline separated.</p>\n";
print "<h3>Invitation message:</h3>\n";

// FIXME: ticket #66: Make this a template. Take from 'To join my' out of the editable bits.
print "<p><textarea name='message' cols='60' rows='5'>Please work with me on GENI project $project_name!

Since we work in the same lab, we should do our GENI research together. 
That means belonging to the same GENI project.\n</textarea></p>\n";
print "<h3>Message footer:</h3>\n";
print "<p><i>To join my project, go here: <br/>
      https://$hostname/secure/join-this-project.php?project_id=$project_id<br/>
<br/>
Once you request to join, I'll get an email to come back to the GENI portal and approve you.
Then you can work with slices in my project.
<br/>
If you are new to GENI:<br/>
GENI is an NSF funded virtual testbed supporting computer networking research and innovation. 
I use GENI, and you should too.<br/>
For more info on GENI, see: http://www.geni.net<br/>
To get started using GENI, go to the GENI Portal: https://$hostname<br/>
You log in with your home university or college username, or request a GENI-specific account.<br/>
<br/>
Thank you,<br/>\n";
print $user->prettyName();
print "</i></p>\n";

print "<p><button type=\"submit\" value=\"submit\"><b>Invite</b></button>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/></p>\n";
print "</form>\n";

include("footer.php");
?>

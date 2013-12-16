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
require_once('pa_constants.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// Check if the user has create project privilege and short circuit this
if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
  relative_redirect('edit-project.php');
}

show_header('GENI Portal: Projects', $TAB_PROJECTS);

include("tool-lookupids.php");
include("tool-breadcrumbs.php");

// Ask someone to create you a project, and join GENI if necessary
// Collect the desired name & purpose of the project
// Collect who you are and why they should be the one to create it for you
// Collect email address of person to request a project from
//- includes requestor info
//- includes instructions on
//--- join GENI
//--- create project
//--- Invite me to join (include link), which sends me mail
//--- You will get email about my request to join the project.
// Then you have to approve my request

// FIXME: Clean up email text

$requestee = null;
$error = null;
$message = null;
$project_name = null;
$project_purpose = null;
if (array_key_exists("to", $_REQUEST)) {
  $requestee_string = trim($_REQUEST["to"]);
  // FIXME: validate as an email
  $requestee = filter_var($requestee_string, FILTER_SANITIZE_EMAIL);
  if (! filter_var($requestee, FILTER_VALIDATE_EMAIL)) {
    error_log("ask-for-project Got invalid requestee " . $requestee);
    $error = "Invalid email address: " . $requestee;
    $requestee = null;
  }
  // FIXME: See http://www.linuxjournal.com/article/9585

  if (array_key_exists("message", $_REQUEST)) {
    $message = $_REQUEST["message"];
  }
  if (array_key_exists("name", $_REQUEST)) {
    $project_name = $_REQUEST["name"];
  }
  if (array_key_exists("purpose", $_REQUEST)) {
    $project_purpose = $_REQUEST["purpose"];
  }
  $hostname = $_SERVER['SERVER_NAME'];
  $message .= "\nCreate my project here: https://$hostname/secure/edit-project.php

In case you are new to GENI:
GENI is an NSF funded virtual testbed supporting computer networking research and innovation. 
For more info on GENI, see: http://www.geni.net

To create a project for me in GENI, you first go to the GENI Portal: https://$hostname
You log in with your home university or college username, or request a GENI-specific account.
Then you need to wait for your GENI account to be approved to create
projects, if you don't have that permission yet.
You'll get email when your GENI account is approved to create projects, and you can go back to the GENI portal to log in.

Once you are logged in to the GENI portal, click 'Create Project' to create a new project. 
(https://$hostname/secure/edit-project.php)

Please give it the project name I listed, so I can find it again (or something similar).

On the following project page, you should see an 'Invite Project Member' button. Click that, and enter my email address (" . $user->email() . "), to invite me to join the new project.

I'll get an email telling me I've been invited, with a link in it. That link will take me to the 'Request to Join' the new project page in the GENI portal. 
Once I request to join the project, you'll get another email, asking you to approve my membership in the new project. Click on the link 
in that email to add me to the project.

Thank you,\n" . $user->prettyName() . "\n";

  $message .= "\r\n----\r\nRequested Project Name: " . $project_name . "\r\n";
  $message .= "Requested Project Purpose: " . $project_purpose . "\r\n";
}
if (isset($requestee) && ! is_null($requestee) && (!isset($error) || is_null($error))) {
  // Send the email
  $email = $user->email();
  $name = $user->prettyName();
  $headers = "Auto-Submitted: auto-generated\r\n";
  $headers .= "Precedence: bulk\r\n";
  $headers .= "Reply-To: $email" . "\r\n" . "From: $name (via the GENI Portal) <www-data@gpolab.bbn.com>";  

  mail($requestee,
       "Please create me a GENI Project",
       $message,
       $headers,
       "-f $email");

  // FIXME: Ticket #65: Put this as a request. Include the request ID in the email?
  // Then when the request is handled, can auto add the member who requested the project.
  // And the request can store the project name and description

  // Put up a page saying we invited them.
  print "<h2>Ask Someone to Create you a Project</h2>\n";
  print "<br/>\n";
  print "<b>Sent</b> GENI project request to:<br/>\n" . "$requestee.<br/><br/>\n";
  $lines = explode("\r\n", $message);
  print "<b>Message</b>: <br/>\n";
  print "<pre>";
  foreach ($lines as $line) {
    print "$line\n";
  }
  print "</pre>";
  include("footer.php");
  exit();
}

print "<h2>Ask Someone to Create you a Project</h2>\n";
print "If you can't create your own project, you can ask someone to create one for you.<br/>\n";
print "This is typically your advisor, lab head, or similar. \n";
print "They will need a GENI account, so this may take time. <br/>\n";
print "This form will send them an email with links to the GENI portal and main web site.<br/>\n";
print "You should include a custom message explaining how you want to use GENI and why they should create a project for you.<br/>\n";
print "<br/>\n";
if (isset($error) && ! is_null($error)) {
  print "<p class='warn'>$error</p>\n";
}
//mailto:larry,dan?cc=mike&bcc=sue&subject=test&body=type+your&body=message+here
print "<form action=\"ask-for-project.php\">\n";
print "<b>Email address of person to request a project from</b>:<br/>\n";
print "<textarea name='to' cols=\"60\" rows=\"1\"></textarea><br/>\n"; // FIXME: Need to ensure this is valid - JS?
print "<b>Proposed project name</b>:<br/>\n";
print "<input type='text' name='name'/><br/>\n";
print "<b>Proposed project purpose</b>:<br/>\n";
print "<textarea name='purpose' cols='60' rows='2'></textarea><br/>\n";
print "<b>Project request message</b>:<br/>\n";
$hostname = $_SERVER['SERVER_NAME'];
// FIXME: Ticket #66: Split this up. Much of this is canned text. Maybe starting with 'Create my...'? More?
print "<textarea name='message' cols='60' rows='5'>Would you please create a GENI project for me?
I need to use GENI for my research, and I need someone to create a 'GENI project' to hold my research.
I don't have the right permissions to create a GENI project, but I think you do or could.

Would you be willing to create a GENI project for my research, and be the responsible Project Lead for my project? 
</textarea><br/>\n";
print "<b>Message footer</b>: <br/>\n";
print "Create my project here: https://$hostname/secure/edit-project.php<br/>
<br/>
In case you are new to GENI:<br/>
GENI is an NSF funded virtual testbed supporting computer networking research and innovation. <br/>
For more info on GENI, see: http://www.geni.net<br/>
<br/>
To create a project for me in GENI, you first go to the GENI Portal: https://$hostname<br/>
You log in with your home university or college username, or request a GENI-specific account.<br/>
Then you need to wait for your GENI account to be approved to create
projects, if you don't have that permission yet.<br/>
You'll get email when your GENI account is approved to create projects, and you can go back to the GENI portal to log in.<br/>
<br/>
Once you are logged in to the GENI portal, click 'Create Project' to create a new project. <br/>
(https://$hostname/secure/edit-project.php)<br/>
<br/>
Please give it the project name I listed, so I can find it again (or something similar).<br/>
<br/>
On the following project page, you should see an 'Invite Project Member' button. Click that, and enter my email address (";
print $user->email();
print "), to invite me to join the new project.<br/>
<br/>
I'll get an email telling me I've been invited, with a link in it. That link will take me to the 'Request to Join' the new project page in the GENI portal. <br/>
Once I request to join the project, you'll get another email, asking you to approve my membership in the new project. Click on the link <br/>
in that email to add me to the project.<br/>
<br/>
Thank you,<br/>\n";
print $user->prettyName();
print "<br/>\n<br/>\n";
print "<button type=\"submit\" value=\"submit\"><b>Send Request</b></button>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print "</form>\n";
include("footer.php");
?>

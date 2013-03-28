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
require_once("sr_client.php");
require_once("sr_constants.php");
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
include("tool-lookupids.php");

//Invite to Join GENI
//Generic page to email a bunch of folks with a link to the unsecured
//main page, telling them to come join GENI it's great
//-- include optional text area for more reasoning? Make my text editable?
//- include requestor info

$invitees = null;
$error = null;
$message = "";
if (array_key_exists("to", $_REQUEST)) {
  $invitee_string = $_REQUEST["to"];
  // split on ,
  $invitees = preg_split("/[\s,]+/", $invitee_string);
  for ($i = 0; $i < count($invitees); $i++) {
    $invitees[$i] = trim($invitees[$i]);
    // FIXME: validate each as an email
    $invitees[$i] = filter_var($invitees[$i], FILTER_SANITIZE_EMAIL);
    // FIXME: See http://www.linuxjournal.com/article/9585
  }
  if (array_key_exists("message", $_REQUEST)) {
    $message = $_REQUEST["message"];
  }
  $hostname = $_SERVER['SERVER_NAME'];
  $message .= "\nFor more information on GENI, see: http://www.geni.net
To get started using GENI, go to the GENI Portal: https://$hostname

You log in with your home university or college username, or request a GENI-specific account.

Thank you,\n" . $user->prettyName() . "\n";
}

show_header('GENI Portal: Home', $TAB_HOME);

include("tool-breadcrumbs.php");

if (isset($invitees) && ! is_null($invitees) && (!isset($error) || is_null($error))) {
  // Send the email
  $to = implode(", ", $invitees);
  $email = $user->email();
  $name = $user->prettyName();
  mail($to,
       "Join GENI!",
       $message,
       "Reply-To: $email" . "\r\n" . "From: $name <$email>");

  // Put up a page saying we invited them.
  print "<h2>Invite Someone to GENI</h2>\n";
  print "<br/>\n";
  print "<b>Sent</b> GENI invitation to:<br/>\n" . "$to.<br/><br/>\n";
  $lines = explode("\r\n", $message);
  print "<b>Message</b>: <br/><pre>\n";
  foreach ($lines as $line) {
    print "$line\n";
  }
  print "</pre>";
  include("footer.php");
  exit();
}

print "<h2>Invite Someone to GENI</h2>\n";
print "Invite your co-workers and friends to use GENI!<br/>\n";
print "This form will send them an email with links to the GENI portal and main web site.<br/>\n";
print "You can include a custom message explaining how you use GENI or might want to collaborate with them in GENI.<br/>\n";
print "<br/>\n";
if (isset($error) && ! is_null($error)) {
  print $error;
}
//mailto:larry,dan?cc=mike&bcc=sue&subject=test&body=type+your&body=message+here
print "<form action=\"invite-to-geni.php\">\n";
print "<b>Email address of people to invite</b>:<br/>\n";
print "<textarea name='to' cols=\"60\" rows=\"4\"></textarea><br/>\n"; // FIXME: Need to ensure this is valid - JS?
print "<b>Invitation message</b>:<br/>\n";
$hostname = $_SERVER['SERVER_NAME'];
// FIXME: Ticket #66: Make this only partially editable. Maybe starting with 'For more info...'
print "<textarea name='message' cols='60' rows='5'>Come use GENI! 
GENI is an NSF funded virtual testbed supporting computer networking research and innovation. 
I use GENI, and you should too.
</textarea><br/>\n";

print "<b>Message footer</b>: <br/>\n";
print "For more information on GENI, see: http://www.geni.net<br/>
To get started using GENI, go to the GENI Portal: https://$hostname<br/>
<br/>
You log in with your home university or college username, or request a GENI-specific account.<br/>
<br/>
Thank you,<br/>\n";
print $user->prettyName();
print "<br/><br/>\n";
print "<button type=\"submit\" value=\"submit\"><b>Invite</b></button>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print "</form>\n";
include("footer.php");
?>

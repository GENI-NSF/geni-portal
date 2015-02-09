<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2015 Raytheon BBN Technologies
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

require_once("settings.php");
require_once("util.php");
require_once("user.php");
require_once("pa_constants.php");
require_once("cs_client.php");
require_once("cs_constants.php");
require_once('logging_client.php');
require_once('logging_constants.php');
require_once('ma_client.php');
require_once('ma_constants.php');
include_once('/etc/geni-ch/settings.php');


$user=geni_loadUser();
if (! isset($user) || ! $user->isActive()) {
  relative_redirect("home.php");
}

$sr_url = get_sr_url();
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
$log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);

/*
 * Pull info from the $_REQUEST
 */
$form_name = 'name';
$form_telephone = 'telephone';
$form_reference = 'reference';
$form_url = 'url';
$form_reason = 'reason';
$form_projectlead = 'projectlead';

function from_request($key) {
  return empty($_REQUEST[$key]) ? null : trim($_REQUEST[$key]);
}

function update_ma($ma_url, $user, $name, $value, $old_value) {
  if ($value === $old_value) {
    // If no change, do nothing.
    return;
  } else if (empty($value)) {
    remove_member_attribute($ma_url, $user, $user->account_id, $name);
  } else {
    add_member_attribute($ma_url, $user, $user->account_id,
                         $name, $value, true);
  }
}

$req_name = from_request($form_name);
$req_telephone = from_request($form_telephone);
$req_reference = from_request($form_reference);
$req_url = from_request($form_url);
$req_reason = from_request($form_reason);
$req_projectlead = from_request($form_projectlead);

// Filter the name a bit so it makes some sense.

// This is an arbitrary string of "bad" characters we simply remove
// from the name.
$bad_chars = '~{()}@^$%?;:/*&|#!^\\"';
$req_name = trim(str_replace(str_split($bad_chars), '', $req_name));

// Update the attributes, except for project lead
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::DISPLAY_NAME, $req_name,
          $user->prettyName());
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::TELEPHONE_NUMBER, $req_telephone,
          $user->phone());
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::REFERENCE, $req_reference,
          $user->reference());
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::URL, $req_url, $user->url());
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::REASON, $req_reason,
          $user->reason());

// Now handle project lead...

// If we got 'projectlead' in the POST, it is a pi_request.
$pi_request = isset($req_projectlead);

// If the user is allowed to create a project, they are a PI (PL?)
$is_pi = $user->isAllowed(PA_ACTION::CREATE_PROJECT,
                          CS_CONTEXT_TYPE::RESOURCE, null);

$body = '';
$subject = "GENI Project Lead request";

if ($pi_request and ! $is_pi) {
  $body .= 'The following user has requested to be a Project Lead:';
  $log_msg = $user->prettyName() . " requested to be a Project Lead";
} else if (! $pi_request and $is_pi) {
  $body .= 'The following user has requested to no longer be a Project Lead:';
  $log_msg = $user->prettyName() . " requested to NOT be a Project Lead";
}

// If there's something to log, then send email about it too.
if (isset($log_msg)) {
  $log_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER,
                                              $user->account_id);
  log_event($log_url, $user, $log_msg, $log_attributes);

  $body .= PHP_EOL;
  $body .= PHP_EOL;
  $body .= "Account ID: " . $user->account_id . "\n";
  $body .= "EPPN: " . $user->eppn . "\n";
  $body .= "Username: " . $user->username . "\n";
  $body .= "Email: " . $user->email() . "\n";
  $body .= "Name: $req_name\n";
  $body .= "Telephone: $req_telephone\n";
  $body .= "Reference: $req_reference\n";
  $body .= "Url: $req_url\n";
  $body .= "Reason: $req_reason\n";

  mail($portal_admin_email, $subject, $body);
}

// If they submitted a new name, use it. Otherwise, use what we have.
$pretty_name = isset($req_name) ? $req_name : $user->prettyName();

if ($pi_request and ! $is_pi) {
  $body = "Dear $pretty_name,\n\n";
  $body .= "We have received your request to be a GENI Project Lead.";
  $body .= " We are processing your request and you should hear from us";
  $body .= " in 3-4 business days.\n\n";
  $body .= "If you have any questions about your request or about using GENI";
  $body .= " in general, please email help@geni.net.\n\n";
  $body .= "Thank you for your interest in GENI!\n\n";
  $body .= "Sincerely,\n";
  $body .= "GENI Experimenter Support\n";
  $body .= "help@geni.net\n";

  $headers = "Reply-To: help@geni.net\r\n";
  $headers .= "Bcc: $portal_admin_email\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $headers .= "Content-Transfer-Encoding: 8bit\r\n";

  $to = $user->prettyEmailAddress();
  $subject = "Your GENI Project Lead request has been received";
  mail($to, $subject, $body, $headers);
}

/*
 * Send an email listing values changed.
 */
function note_change($field, $new, $old) {
  if ($old === $new) {
    return '';
  } else {
    return "$field was '$old', now '$new'\n";
  }
}
$change_text = '';
$change_text .= note_change('Name', $req_name, $user->prettyName());
$change_text .= note_change('Telephone', $req_telephone, $user->phone());
$change_text .= note_change('Reference', $req_reference, $user->reference());
$change_text .= note_change('URL', $req_url, $user->url());
$change_text .= note_change('Reason', $req_reason, $user->reason());

if (! empty($change_text)) {
  $body = 'Account changes were posted by the following user:';
  $body .= PHP_EOL;
  $body .= PHP_EOL;
  $body .= "Account ID: " . $user->account_id . "\n";
  $body .= "EPPN: " . $user->eppn . "\n";
  $body .= "Username: " . $user->username . "\n";
  $body .= "Email: " . $user->email() . "\n";
  $body .= PHP_EOL;
  $body .= PHP_EOL;
  $body .= 'Changes posted:';
  $body .= PHP_EOL;
  $body .= PHP_EOL;
  $body .= $change_text;

  $subject = "GENI account changes posted";
  mail($portal_admin_email, $subject, $body);
}


/*
 * Now display the web page to the user.
 */
include("header.php");
show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");

print '<h1> Modify Your Account </h1>' . PHP_EOL;
print '<p><b>Your account information has been updated.</b></p>';

if ($pi_request and ! $is_pi) {
  print '<p>' . PHP_EOL;
  print "Your Project Lead request has been received.";
  print " Project Lead requests take several days to review.";
  print " You should hear from us within 3 - 4 business days.";
  print '</p>' . PHP_EOL;
  print '<p>' . PHP_EOL;
  print 'If you have any questions about your request or about';
  print ' using GENI in general, please email';
  print ' <a href="mailto:help@geni.net">GENI Help</a>.';
  print '</p>' . PHP_EOL;
} else if (! $pi_request and $is_pi) {
  print '<p>' . PHP_EOL;
  print "You have indicated that you no longer want to be a  Project Lead.";
  print " This is an administrative function that requires approval.";
  print " You should hear from us within 3 - 4 business days.";
  print '</p>' . PHP_EOL;
  print '<p>' . PHP_EOL;
  print 'If you have any questions about your request or about';
  print ' using GENI in general, please email';
  print ' <a href="mailto:help@geni.net">GENI Help</a>.';
  print '</p>' . PHP_EOL;
}

include("footer.php");
?>

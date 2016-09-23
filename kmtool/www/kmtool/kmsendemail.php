<?php
//----------------------------------------------------------------------
// Copyright (c) 2016 Raytheon BBN Technologies
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

// Step 2 in a user self asserting their email address
// This is the target of the form that puts the record in the database,
// and sends the user email
// Step 1 is kmnoemail, and Step 3 is kmconfirmeail

include("util.php");
include_once('/etc/geni-ch/settings.php');
require_once 'maintenance_mode.php';
require_once 'km_utils.php';
require_once 'response_format.php';
require_once 'db_utils.php';

// If no eppn, go directly to InCommon error page
if (! key_exists('eppn', $_SERVER)) {
  $feh_url = incommon_feh_url();
  header("Location: $feh_url");
  exit;
}

$eppn = strtolower($_SERVER['eppn']);

// Use the main path if we are getting email
if (key_exists('mail', $_SERVER)) {
  relative_redirect('dashboard.php');
}

// If the email has already been asserted, use the main route in
$asserted_attrs = get_asserted_attributes($eppn);
if (key_exists('mail', $asserted_attrs)) {
  relative_redirect('dashboard.php');
}

// So we have an EPPN but no email from Shib or from asserted attribute

// If we don't have an asserted email yet, go back
if (! key_exists('assertedemail', $_REQUEST)) {
  relative_redirect('kmnoemail.php');
}

$email = $_REQUEST['assertedemail'];

// If the email is invalid, go back
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  error_log("Invalid email address given");
  relative_redirect('kmnoemail.php?bademail=True');
}

include("kmheader.php");

// Bail if we're in maintenance
if ($in_maintenance_mode) {
  print "This GENI Clearinghouse is currently in maintenance mode and cannot register new users.";
  print "<br>";
  print "<button onClick=\"history.back(-1)\"><b>Back</b></button>";
  include("kmfooter.php");
  return;
}

// Here on we're setting up to store the email address
// and them email to confirm it

// Generate a random string (nums, uppercase, lowercase) of width $width
function random_id($width=6) {
    $result = '';
    for ($i=0; $i < $width; $i++) {
        $result .= base_convert(strval(rand(0, 35)), 10, 36);
    }
    return strtoupper($result);
}

// make a new /kmconfirmemail.php?id=XXX&n=YYY link for use in email confirmation email
function create_email_confirm_link($id1, $id2) {
  return relative_url("kmconfirmemail.php?id=$id1&n=$id2");
}

// Insert the email address assertion into the km_email_confirm table
// removing any previous entry
function insert_email_confirm($email, $nonce, $eppn) {
    $db_conn = db_conn();

    // First, delete any old entry for the same EPPN
    // - allowing user to fix a bad email address
    $sql = "delete from km_email_confirm"
      . " where eppn = " . $db_conn->quote($eppn, 'text');
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
      $rows = $result[RESPONSE_ARGUMENT::VALUE];
      if ($rows > 0) {
	error_log("Deleted (to replace) $rows record(s) for $eppn from km_email_confirm");
      }
    }

    // Instead of delete then insert, could add index on eppn and add
    // this to the insert statement, but this doesn't add much
    // on conflict (eppn) do update set email=$email, nonce=$nonce, created=NOW() at time zone 'utc'
    // Now insert a new row
    $sql = "insert into km_email_confirm (email, nonce, eppn) values (";
    $sql .= $db_conn->quote($email, 'text');
    $sql .= ', ';
    $sql .= $db_conn->quote($nonce, 'text');
    $sql .= ', ';
    $sql .= $db_conn->quote($eppn, 'text');
    $sql .= ") returning id, created";

    $db_result = db_fetch_row($sql, "insert km_email_confirm");
    $result = false;
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE];
    } else {
        error_log("Error inserting KM email confirm record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result;
}

function get_user_conf_email_body($confirm_url) {
  global $portal_help_email;
  $body = 'Thank you for self asserting your email address for use with GENI. ';
  $body .= "Please confirm your email address by clicking this link:\n\n $confirm_url \n\n";
  $body .= "You will then be able to complete your GENI account creation.\n\n";
  $body .= "Questions? Contact $portal_help_email.";
  $body .= "\n\n";
  $body .= "Thanks,\n";
  $body .= "GENI Operations\n";
  return $body;
}

// Email requester to confirm their email address
// Note that we BCC portal admins
function send_user_confirmation_email($user_email, $confirm_url) {
  global $portal_help_email;
  $subject = "GENI Account Email Confirmation";
  $body = get_user_conf_email_body($confirm_url);
  $headers = "Reply-To: $portal_help_email";
  $headers .= "\r\nContent-Type: text/plain; charset=UTF-8";
  $headers .= "\r\nContent-Transfer-Encoding: 8bit";
  return mail($user_email, $subject, $body, $headers);
}

// Main body
// Create a nonce for this email/eppn assertion
// Insert or update a row in the DB with the new email and eppn

$nonce = random_id(8);
$db_result = insert_email_confirm($email, $nonce, $eppn);
if ($db_result) {
  $db_id = $db_result['id'];
  $confirm_url = create_email_confirm_link($db_id, $nonce);
  if (send_user_confirmation_email($email, $confirm_url)) {
    print "<h2>Email Address Asserted</h2>\n";
    print "<p>\n";
    print "A confirmation email has been sent to $email.</p>";
    print "<p>Please confirm your email address by following the";
    print " link in that email. If you do not receive an email";
    print " within 24 hours, check your SPAM / junk mail folder. </p><p>Questions? Contact us at";
    print " <a href=\"mailto:$portal_help_email\">$portal_help_email</a>.\n";
    print "</p>\n";
  } else {
    // Failed to queue email

    // Notify admins  / put in log
    error_log("Failed to send self asserted email confirmation email to " . $email . " for eppn " . $eppn);

    // Produce the result page
    print "<h2>Address Assertion failed</h2>";
    print "<p> We are sorry, your email address assertion failed. ";
    print "Please <a href=\"" . relative_url('kmnoemail.php') . "\">try again</a>, or contact us at";
    print " <a href=\"mailto:$portal_help_email\">$portal_help_email</a>.\n";
    print "</p>\n";
  }
} else {
  // Notify admins  / put in log
  error_log("Failed to insert into DB self asserted email confirmation request for " . $email . " and eppn " . $eppn);

  // Produce the result page
  print "<h2>Address Assertion failed</h2>";
  print "<p> We are sorry, your email address assertion failed. ";
  print "Please <a href=\"" . relative_url('kmnoemail.php') . "\">try again</a>, or contact us at";
  print " <a href=\"mailto:$portal_help_email\">$portal_help_email</a>.\n";
  print "</p>\n";
}
include("kmfooter.php");
?>

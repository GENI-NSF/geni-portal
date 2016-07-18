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

// Step 3 of handling user who must self assert their email address
// This is the target page of the confirmation email we send.
// Assuming the link matches the current EPPN,
// the given email address is inserted in km_asserted_attribute,
// so that other Portal pages will use that and proceed.
// Step 1 is kmnoemail, Step 2 is kmsendemail

include("util.php");
include_once('/etc/geni-ch/settings.php');
require_once 'maintenance_mode.php';
require_once 'km_utils.php';
require_once 'response_format.php';
require_once 'db_utils.php';
require_once 'portal.php';

// If this is a valid nonce/id for this EPPN, then
// put this entry in the km_asserted_attributes table,
// and remove it from km_email_confirm
// Returns user's email if confirmation link was valid, null otherwise.
function confirm_email($nonce, $db_id, $eppn) {
    $db_conn = db_conn();
    $sql = "SELECT * from km_email_confirm "
      . "where id =" . $db_conn->quote($db_id, 'integer')
      . " and eppn = " . $db_conn->quote($eppn, 'text')
      . " and nonce =" . $db_conn->quote($nonce, 'text');
    $db_result = db_fetch_row($sql, "get km_email_confirm");
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE];
	if (is_null($result)) {
	  error_log("Found no email confirmation record for id $db_id, nonce $nonce, eppn $eppn");
          return null;
	}
        $email = $result['email'];
	$asserter = Portal::getUid();
	$sql2 = "insert into km_asserted_attribute (eppn, name, value, asserter_id) select "
	  . $db_conn->quote($eppn, 'text') . ", 'mail', " . $db_conn->quote($email, 'text')
	  . ", " . $db_conn->quote($asserter, 'text')
	  . " where not exists (select 1 from km_asserted_attribute where name='mail' and eppn= "
	  . $db_conn->quote($eppn, 'text') . ") returning value";
        $update_result = db_fetch_row($sql2, "insert into km_asserted_attribute confirmed email");
        if ($update_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
            delete_confirmation($db_id, $nonce);
	    $email = $update_result[RESPONSE_ARGUMENT::VALUE]['value'];
	    return $email;
        } else {
            error_log("Failed to insert self asserted email address for $eppn: " .
                      $update_result[RESPONSE_ARGUMENT::OUTPUT]);
            return null;
        }
    } else {
        error_log("Error getting KM email confirm record for eppn $eppn: "
                 . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        return null;
    }
}

// Once email is confirmed, delete the entry from km_email_confirm
function delete_confirmation($id, $nonce) {
    $db_conn = db_conn();
    $sql = "delete from km_email_confirm"
         . " where id = " . $db_conn->quote($id, 'text')
         . " and nonce = " . $db_conn->quote($nonce, 'text');
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
        error_log("Error deleting old records: "
                  . $result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result == RESPONSE_ERROR::NONE;
}

// If no eppn, go directly to InCommon error page
if (! key_exists('eppn', $_SERVER)) {
  $feh_url = incommon_feh_url();
  header("Location: $feh_url");
  exit;
}

$eppn = strtolower($_SERVER['eppn']);

// Use the mail path if we are getting email
if (key_exists('mail', $_SERVER)) {
  relative_redirect('dashboard.php');
}

// If the email has already been asserted, use the main route in
$asserted_attrs = get_asserted_attributes($eppn);
if (key_exists('mail', $asserted_attrs)) {
  relative_redirect('dashboard.php');
}

if (array_key_exists('n', $_REQUEST) && array_key_exists('id', $_REQUEST)) {
    $nonce = $_REQUEST['n'];
    $db_id = $_REQUEST['id'];
} else {
  error_log("Bad URL: missing args");
  relative_redirect('kmnoemail.php');
}

// So we have an EPPN but no email from Shib or from asserted attribute

// Bail if we're in maintenance
if ($in_maintenance_mode) {
  include("kmheader.php");
  print "This GENI Clearinghouse is currently in maintenance mode and cannot register new users.";
  print "<br>";
  print "<button onClick=\"history.back(-1)\"><b>Back</b></button>";
  include("kmfooter.php");
  return;
}

list($email, $acctreq_id) = confirm_email($nonce, $db_id, $eppn);

if($email != "") {
  $_SESSION['lastmessage'] = "Email address successfully confirmed";
  relative_redirect('dashboard.php');
} else {
  include("kmheader.php");
  print "<h2>Email Address Confirmation Error</h2>";
  print "<p>Could not confirm email. ";
  print "Please <a href=\"" . relative_url('kmnoemail.php') . "\">try again</a>, or contact us at";
  print " <a href=\"mailto:$portal_help_email\">$portal_help_email</a>.</p>\n";
  include("kmfooter.php");
}

?>

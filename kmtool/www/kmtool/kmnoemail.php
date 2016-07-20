<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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

// Handle a user where we get an EPPN from their Shibboleth IdP,
// but no email address; some schools don't send that for students.
// On this page, request they enter their email address.
// This page directs to
// Step 2: kmsendemail, which emails them a confirmation link to
// Step 3: kmconfirmemail, which enters their email address in the DB,
// for use by later pages.

include("util.php");
include_once('/etc/geni-ch/settings.php');
require_once 'maintenance_mode.php';
require_once 'km_utils.php';

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
  error_log("Already have asserted email");
  relative_redirect('dashboard.php');
}

// So we have an EPPN but no email from Shib or from asserted attribute

include("kmheader.php");

// Bail if we're in maintenance
if ($in_maintenance_mode) {
  print "This GENI Clearinghouse is currently in maintenance mode and cannot register new users.";
  print "<br>";
  print "<button onClick=\"history.back(-1)\"><b>Back</b></button>";
  include("kmfooter.php");
  return;
}

// Clear out requests older than $hours hours in km_email_confirm
function delete_expired_assertions($hours) {
    $sql = "delete from km_email_confirm";
    $sql .= " where created <";
    $sql .= "  (now()  at time zone 'utc') - interval '$hours hours';";
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $rows = $result[RESPONSE_ARGUMENT::VALUE];
        error_log("Deleted $rows old records");
    } else {
        error_log("Error deleting old records: "
                  . $result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result == RESPONSE_ERROR::NONE;
}

// Requests are good for a week - delete old ones now
delete_expired_assertions(7*24);

// kmsendemail sends us back here as needed with bademail
if (key_exists('bademail', $_REQUEST)) {
  // Set up the error display
  $_SESSION['lasterror'] = "Invalid email address given";
}

// Get any partially asserted email from the DB
// to pre-fill in the form
$email = "";
$db_conn = db_conn();
$sql = "SELECT email from km_email_confirm "
  . "where  eppn = " . $db_conn->quote($eppn, 'text');
$db_result = db_fetch_row($sql, "get km_email_confirm");
if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
  $result = $db_result[RESPONSE_ARGUMENT::VALUE];
  if (! is_null($result)) {
    $email = $result['email'];
  }
}

include("tool-showmessage.php");

// Here we show a form asking for their email
// This page sends to kmsendmail.php
?>

<h2>No Email Address</h2>
<p>Your identity provider is not sharing your email address with us.
<a href="http://www.geni.net">GENI</a> requires an email address
so that you can be contacted if necessary about your reserved
resources.</p>
<p>If you would like to register for a GENI account, please self-assert
  your email address:</p>
  <form action="kmsendemail.php" method="POST">
    <p><span class="required">*</span> = required</p>
    <p><label class="input">Email address:<span class="required">*</span>
        <input name="assertedemail" type="email" size="35" required value="<?php echo $email; ?>"></label>
      <br>(Your school or organization email address is preferred.)
    </p>
    <input type="submit"/>
  </form>
<p>
Questions? Contact 
<a href="<?php echo $portal_help_email;?>"><?php echo $portal_help_email;?></a>.
</p>
<p><a href="
<?php
// Link to InCommon federated error handling service.
print incommon_feh_url();
?>
">Technical Information</a></p>
<?php
include("kmfooter.php");
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2014-2016 Raytheon BBN Technologies
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
require_once('portal.php');
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("am_map.php");
require_once("sa_client.php");
require_once("logging_client.php");
require_once("header.php");
require_once("omni_invocation_constants.php");

/*
    send_bug_report.php

    Purpose: Sends bug report of omni invocation files to e-mail(s) specified

    Accepts:
        Required:
            invocation_id: unique ID for an omni invocation (e.g. qlj7KS)
            invocation_user: user ID (e.g. bujcich)
            slice_id: slice ID related to invocation
            to: receiver's e-mail address
        Optional:
            copy: copy the user on the bug report

*/

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

function no_invocation_id_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No omni invocation id and/or user ID specified.';
  exit();
}

// redirect if no attributes passed in
if (! count($_REQUEST)) {
  no_slice_error();
}

// set user ID and invocation
if(array_key_exists("invocation_id", $_REQUEST) &&
        array_key_exists("invocation_user", $_REQUEST)) {
    $invocation_user = $_REQUEST['invocation_user'];
    $invocation_id = $_REQUEST['invocation_id'];
}
else {
    no_invocation_id_error();
}

// set slice information
unset($slice);
include("tool-lookupids.php");
if (! isset($slice)) {
  no_slice_error();
}

// redirect if slice has expired
if (isset($slice_expired) && convert_boolean($slice_expired)) {
  if (! isset($slice_name)) {
    $slice_name = "";
  }
  $_SESSION['lasterror'] = "Slice " . $slice_name . " is expired.";
  relative_redirect('dashboard.php#slices');
}

// redirect if user isn't allowed to look up slice
if(!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

// put header and breadcrumb here
show_header('GENI Portal: Send Problem Report');
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
echo "<h1>Send Problem Report</h1>";

// get 'To:' field and sanitize input
// We support semi-colon or comma separated lists of addresses
if(array_key_exists("to", $_REQUEST)) {
    $tos = preg_split("/[;,]\s*/", $_REQUEST['to'], NULL, PREG_SPLIT_NO_EMPTY);
    $rtos = array();
    foreach($tos as $to) {
      $to = filter_var($to, FILTER_SANITIZE_EMAIL);
      if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $rtos[] = $to;
      }
    }
    if (count($rtos) == 0) {
        send_bug_report_error("E-mail address provided is not valid. Problem report not sent.");
    }
    $to = implode(", ", $rtos);
}
else {
    send_bug_report_error("No e-mail address provided. Problem report not sent.");
}

// set CC field to user's e-mail if exists
$cc = "";
if(array_key_exists("copy", $_REQUEST) && $_REQUEST['copy'] == 'true') {
    $cc = $user->email();
}

// get user's custom message
$custom_message = "";
if(array_key_exists("message", $_REQUEST)) {
    $custom_message = $_REQUEST['message'];
}

// good to go, so set up everything else
send_bug_report($user, $invocation_user, $invocation_id, $to, $cc, $custom_message);


/*
    Handle errors when sending bug report
    Exits early
*/
function send_bug_report_error($msg) {
    global $portal_version; // this lives outside of the function's scope
    global $invocation_id;
    global $invocation_user;
    global $slice_id;
    echo "<p class='error'>$msg</p>";
    echo "<button onClick=\"window.location='sliceresource.php?invocation_user=$invocation_user&invocation_id=$invocation_id&slice_id=$slice_id'\" title='Return to Status Page'>Return to Status Page</button>";
    include("footer.php");
    exit();
}

/*
    Handle success when sending bug report
    Exits at the end
*/
function send_bug_report_success($msg) {
    global $portal_version; // this lives outside of the function's scope
    global $invocation_id;
    global $invocation_user;
    global $slice_id;
    echo "<p class='instruction'>$msg</p>";
    echo "<button onClick=\"window.location='sliceresource.php?invocation_user=$invocation_user&invocation_id=$invocation_id&slice_id=$slice_id'\" title='Return to Status Page'>Return to Status Page</button>";
    include("footer.php");
    exit();
}

/*
    Handle zipping and mailing the bug report
*/
function send_bug_report($user, $invocation_user, $invocation_id, $to, $cc, $custom_message) {

    $omni_invocation_dir = get_invocation_dir_name($invocation_user, $invocation_id);

    // make sure directory actually exists
    if(!is_dir($omni_invocation_dir)) {
        send_bug_report_error("Problem report files could not be zipped in an archive. Problem report not sent.");
    }

    // don't include the user's cert, credentials, private key, and (maybe) SF cred
    $excluded_files_list = array(
        "$omni_invocation_dir/" . OMNI_INVOCATION_FILE::SLICE_CREDENTIAL_FILE,
        "$omni_invocation_dir/" . OMNI_INVOCATION_FILE::CERTIFICATE_FILE,
        "$omni_invocation_dir/" . OMNI_INVOCATION_FILE::PRIVATE_KEY_FILE,
        "$omni_invocation_dir/" . OMNI_INVOCATION_FILE::SPEAKSFOR_CREDENTIAL_FILE
    );

    $zip_name = OMNI_INVOCATION_FILE::ZIP_ARCHIVE_PREFIX . "-$invocation_user-$invocation_id.zip";
    $zip_path = "$omni_invocation_dir/$zip_name";

    // FIXME: See ticket #1117/1169: Try making this a .tar.gz?

    $retVal = zip_dir_files($zip_path, $omni_invocation_dir, $excluded_files_list);

    // if .zip file exists, grab its contents for attachment and delete file
    $attachment = "";
    if($retVal) {
        $attachment = chunk_split(base64_encode(file_get_contents($retVal)), 76, "\n");
        if(file_exists($retVal)) {
            unlink($retVal);
        }
    }
    else {
        send_bug_report_error("Problem report files could not be zipped in an archive. Problem report not sent.");
    }

    // prepare metadata
    $metadata = "";
    $metadata_file = "$omni_invocation_dir/" . OMNI_INVOCATION_FILE::METADATA_BUG_REPORT_EMAIL_FILE;
    if(is_file($metadata_file) && filesize($metadata_file)) {
        $metadata_json_string = file_get_contents($metadata_file);
        $metadata_array = json_decode($metadata_json_string, True);
        foreach($metadata_array as $key => $value) {
	  if (is_array($value)) {
	    $metadata .= "$key: " . implode(', ', $value) . "\n";
	  } else {
            $metadata .= "$key: $value\n";
	  }
        }
    }

    // set up e-mail
    global $portal_from_email;
    $boundary_string = md5(date('r', time()));
    $from = "\"" . $user->prettyName() . " (via the GENI Portal)\" <$portal_from_email>";
    $subject = "GENI Portal Reservation Problem Report";

    $headers   = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: multipart/mixed; boundary=\"PHP-mixed-" . $boundary_string . "\"";
    $headers[] = "From: $from";
    if($cc) {
        $headers[] = "Cc: $cc";
    }
    $headers[] = "Reply-To: " . $user->prettyEmailAddress();
    $headers[] = "X-Mailer: PHP/" . phpversion();

    // start output buffering
    // do not add any additional tabs here as they will indent
    // the PHP-mixed et al lines
    ob_start();
?>

--PHP-mixed-<?php echo "$boundary_string\n"; ?>
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 8bit

Attached is a problem report about reserving resources generated from the
GENI Portal (https://portal.geni.net/). This problem report contains
process-related information such as log files, resource specifications
(RSpecs) and metadata.

User message:
<?php echo $custom_message; ?>


Process metadata:
<?php echo $metadata; ?>

Thanks,
<?php echo $user->prettyName(); ?>

--PHP-mixed-<?php echo "$boundary_string\n"; ?>
Content-Type: application/zip; name="<?php echo $zip_name; ?>"
Content-Transfer-Encoding: base64
Content-Disposition: attachment

<?php echo $attachment; ?>

<?php
    $message = ob_get_clean();
    $message .= "\r\n";
    $message .= "--PHP-mixed-$boundary_string--\r\n";

    $retVal = mail($to, $subject, $message, implode("\r\n", $headers));

    if($retVal) {
        if($cc) {
            $msg = "Problem report sent to <b>$to</b> (and copied to <b>$cc</b>).";
        }
        else {
            $msg = "Problem report sent to <b>$to</b>.";
        }
        send_bug_report_success($msg);
    }
    else {
        error_log("Error sending problem report $invocation_user-$invocation_id: $retVal");
        send_bug_report_error("Could not send problem report. Try again later or " .
            "please <a href='contact-us.php'>contact us</a> for assistance.");
    }

}


// set message to display on next page load

// relative redirect back to results page




?>

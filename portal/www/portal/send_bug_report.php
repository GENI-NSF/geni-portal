<?php
//----------------------------------------------------------------------
// Copyright (c) 2014 Raytheon BBN Technologies
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
  relative_redirect('slices.php');
}

// redirect if user isn't allowed to look up slice
if(!$user->isAllowed(SA_ACTION::LOOKUP_SLICE, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

// FIXME: put header and breadcrumb here
show_header('GENI Portal: Send Bug Report',  $TAB_SLICES);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
echo "<h1>Send Bug Report</h1>";

// get 'To:' field and sanitize input
if(array_key_exists("to", $_REQUEST)) {
    $to = filter_var($_REQUEST['to'], FILTER_SANITIZE_EMAIL);
    // if e-mail isn't valid
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        send_bug_report_error("E-mail address provided is not valid. Bug report not sent.");
    }
}
else {
    send_bug_report_error("No e-mail address provided. Bug report not sent.");
}

// set CC field to user's e-mail if exists
$cc = "";
if(array_key_exists("copy", $_REQUEST) && $_REQUEST['copy'] == 'true') {
    $cc = $user->email();
}

// good to go, so set up everything else
send_bug_report($user, $invocation_user, $invocation_id, $to, $cc);


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
    echo "<p><button onClick=\"window.location='sliceresource.php?invocation_user=$invocation_user&invocation_id=$invocation_id&slice_id=$slice_id'\" title='Return to Status Page'>Return to Status Page</button></p>";
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
    echo "<p><button onClick=\"window.location='sliceresource.php?invocation_user=$invocation_user&invocation_id=$invocation_id&slice_id=$slice_id'\" title='Return to Status Page'>Return to Status Page</button></p>";
    include("footer.php");
    exit();
}

/*
    Handle zipping and mailing the bug report
*/
function send_bug_report($user, $invocation_user, $invocation_id, $to, $cc) {
    
    $omni_invocation_dir = get_invocation_dir_name($invocation_user, $invocation_id);

    // make sure directory actually exists
    if(!is_dir($omni_invocation_dir)) {
        send_bug_report_error("Bug report files could not be zipped in an archive. Bug report not sent.");
    }

    // don't include the user's cert, credentials, and private key
    $excluded_files_list = array(
        "$omni_invocation_dir/cert", 
        "$omni_invocation_dir/cred",
        "$omni_invocation_dir/key"
    );

    $zip_name = "omni-invocation-bug-report-$invocation_user-$invocation_id.zip";
    $zip_path = "$omni_invocation_dir/$zip_name";

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
        send_bug_report_error("Bug report files could not be zipped in an archive. Bug report not sent.");
    }

    // set up e-mail
    $boundary_string = md5(date('r', time()));
    $from = "\"" . $user->prettyName() . " (via the GENI Portal)\" <" . $user->email() . ">";
    $subject = "GENI Portal Omni Bug Report";
    
    // FIXME: Set up what body will say
    $body = "";
    
    $footer = "Attached is a bug report generated by " . $user->prettyName() .
        " from the GENI Portal (https://portal.geni.net/).";

    $headers   = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: multipart/mixed;boundary=\"PHP-mixed-" . $boundary_string . "\"";
    $headers[] = "From: $from";
    if($cc) {
        $headers[] = "Cc: $cc";
    }
    $headers[] = "Reply-To: " . $user->prettyEmailAddress();
    $headers[] = "Subject: {$subject}";
    $headers[] = "X-Mailer: PHP/" . phpversion();

    // start output buffering
    // do not add any additional tabs here
    ob_start();
    ?>

--PHP-mixed-<?php echo $boundary_string; ?> 
Content-Type: text/plain; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

<?php echo $footer; ?>

--PHP-mixed-<?php echo $boundary_string; ?> 
Content-Type: application/zip; name="<?php echo $zip_name; ?>" 
Content-Transfer-Encoding: base64 
Content-Disposition: attachment 

<?php echo $attachment; ?>

    <?php 
    $message = ob_get_clean(); 

    $retVal = mail($to, $subject, $message, implode("\r\n", $headers));

    if($retVal) {
        if($cc) {
            $msg = "Bug report sent to <b>$to</b> (and copied to <b>$cc</b>).";
        }
        else {
            $msg = "Bug report sent to <b>$to</b>.";
        }
        send_bug_report_success($msg);
    }
    else {
        send_bug_report_error("Could not send bug report. Try again later or " .
            "please contact <a href='mailto:portal-help@geni.net'>Portal Help</a>.");
    }

}


// set message to display on next page load

// relative redirect back to results page




?>

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
?>
<?php
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

/**
 * Find an attribute value either in the ENV or in the POST.
 *
 * @param unknown_type $attr
 * @param unknown_type $value
 * @param unknown_type $self_asserted
 * @return boolean
 */
function attrValue($attr, &$value, &$self_asserted) {
  $value = null;
  $self_asserted = null;
  $result = false;
  if (array_key_exists($attr, $_SERVER)) {
    $value = $_SERVER[$attr];
    $self_asserted = false;
    $result = true;
  } else if (array_key_exists($attr, $_POST)) {
      $value = $_POST[$attr];
      $self_asserted = true;
      $result = true;
  }
  return $result;
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
  return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
}

function update_ma($ma_url, $user, $name, $value) {
  if (isset($value)) {
    add_member_attribute($ma_url, $user, $user->account_id,
                         $name, $value, true);
  } else {
    remove_member_attribute($ma_url, $user, $user->account_id, $name);
  }
}


$req_name = from_request($form_name);
$req_telephone = from_request($form_telephone);
$req_reference = from_request($form_reference);
$req_url = from_request($form_url);
$req_reason = from_request($form_reason);
$req_projectlead = from_request($form_projectlead);

update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::DISPLAY_NAME, $req_name);
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::TELEPHONE_NUMBER, $req_telephone);
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::REFERENCE, $req_reference);
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::URL, $req_url);
update_ma($ma_url, $user, MA_ATTRIBUTE_NAME::REASON, $req_reason);

// If we got 'projectlead' in the POST, it is a pi_request.
$pi_request = isset($req_projectlead);

// If the user is allowed to create a project, they are a PI (PL?)
$is_pi = $user->isAllowed(PA_ACTION::CREATE_PROJECT,
                          CS_CONTEXT_TYPE::RESOURCE, null);


// FIXME: This is not right
// Instead: Create a request infrastructure in the MA
// register a request
// MA sends mail
// MA has a method to approve or deny such a request
// approve needs to update the appropriate identity/ma_attribute
// tables, and then email the user that it was approved
// activate_account script then needs to call this

$body = '';
$subject = "GENI Project Lead request";

if ($pi_request and ! $is_pi) {
  $body .= 'The following user has requested to be a Project Lead:';
  $log_msg = $user->prettyName() . " requested to be a Project Lead";
} else if (! $pi_request and $is_pi) {
  $body .= 'The following user has requested to no longer be a Project Lead:';
  $log_msg = $user->prettyName() . " requested to NOT be a Project Lead";
}

$body .= PHP_EOL;
$body .= PHP_EOL;
$body .= "Account ID: " . $user->account_id . "\n";
$body .= "EPPN: " . $user->eppn . "\n";
$body .= "Username: " . $user->username . "\n";
$body .= "Email: " . $user->email() . "\n";

$log_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER,
                                            $user->account_id);
log_event($log_url, $user, $msg, $log_attributes);

mail($portal_admin_email, $subject, $body);

// If they submitted a new name, use it. Otherwise, use what we have.
$pretty_name = isset($req_name) ? $req_name : $user->prettyName();

if ($pi_request and ! $is_pi) {
  $body = "Dear $pretty_name,\n\n";
  $body .= "We have received your request to be a GENI Project Lead.";
  $body .= "We are processing your request and you should hear from us";
  $body .= " in 3-4 business days.\n\n";
  $body .= "If you have any questions about your request or about using GENI";
  $body .= " in general, please email help@geni.net.\n\n";
  $body .= "Thank you for your interest in GENI!\n\n";
  $body .= "Sincerely,\n";
  $body .= "GENI Experimenter Support\n";
  $body .= "help@geni.net\n";

  $headers = "Reply-To: help@geni.net\r\n";
  $headers .= "Bcc: $portal_admin_email\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
  $headers .= $cc;

  $to = $user->prettyEmailAddress();
  $subject = "Your GENI Project Lead request has been received";
  mail($to, $subject, $body, $headers);
}



include("header.php");
show_header('GENI Portal Home', $TAB_HOME);
include("tool-breadcrumbs.php");
?>

<h1> Modify Your Account </h1>
<p><b>Your account modification request has been submitted.</b></p>
<ul>
<?php
print '<pre>' . PHP_EOL;
print_r($_POST);
print '</pre>' . PHP_EOL;

print "<li><b>Account ID:</b> " . $user->account_id . "</li>\n";
print "<li><b>Name:</b> " . $user->prettyName() . "</li>\n";
print "<li><b>Username:</b> " . $user->username . "</li>\n";
if ($pi_request and ! $is_pi) {
  print "<li><b>Requesting to be a Project Lead</b></li>\n";
} else if (! $pi_request and $is_pi) {
  print "<li><b>Requesting to NOT be a Project Lead</b></li>\n";
}
if ($changed_str !== '') {
  print "<li><b>Changes:</b> $changed_str</li>\n";
}
if ($added_str !== '') {
  print "<li><b>Additions:</b> $added_str</li>\n";
}
if ($removed_str !== '') {
  print "<li><b>Removals:</b> $removed_str</li>\n";
}
echo '</ul>';
echo '<p>Your change request is being processed by the Portal operators, and you will receive an email when your request has been handled.</p>';
if ($pi_request and ! $is_pi) {
  echo '<p>If and when you are made a Project Lead, your Home Page will show the "Create Project" button.</p>';
}
?>

<p>Go to the <a href=
<?php
$url = relative_url("home.php");
print $url
?>
>portal home page</a>.</p>

<?php
include("footer.php");
?>

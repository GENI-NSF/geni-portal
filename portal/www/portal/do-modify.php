<?php
//----------------------------------------------------------------------
// Copyright (c) 2011-2014 Raytheon BBN Technologies
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

attrValue('givenName', $first_name, $first_name_self_asserted);
attrValue('sn', $last_name, $larst_name_self_asserted);
attrValue('mail', $email_address, $email_address_self_asserted);
attrValue('telephoneNumber', $telephone_number, $telephone_number_self_asserted);
$attrs = array();
$sa_attrs = array();
$all_attrs = array('givenName' => MA_ATTRIBUTE_NAME::FIRST_NAME,
        'sn' => MA_ATTRIBUTE_NAME::LAST_NAME,
        'mail' => MA_ATTRIBUTE_NAME::EMAIL_ADDRESS,
        'telephoneNumber' => MA_ATTRIBUTE_NAME::TELEPHONE_NUMBER,
        'affiliation' => 'affiliation',
        'eppn' => 'eppn',
        'reference' => 'reference',
        'reason' => 'reason',
        'profile' => 'profile'
		   );
foreach (array_keys($all_attrs) as $attr_name) {
  if (attrValue($attr_name, $value, $self_asserted)) {
    if ($self_asserted) {
      $sa_attrs[$all_attrs[$attr_name]] = $value;
    } else {
      $attrs[$all_attrs[$attr_name]] = $value;
    }
  }
}

$added_attrs = array();
$removed_attrs = array();
$changed_attrs = array();
$changed_attrs_old = array();
$newEmail = null;

// Diff the fields
foreach (array_keys($sa_attrs) as $attr_name) {
  if (array_key_exists($attr_name, $user->attributes)) {
    if ($sa_attrs[$attr_name] != $user->attributes[$attr_name]) {
      $changed_attrs[$attr_name] = $sa_attrs[$attr_name];
      $changed_attrs_old[$attr_name] = $user->attributes[$attr_name];
      if ($attr_name === "mail" or $attr_name === "email_address") {
	$newEmail = $sa_attrs[$attr_name];
      }
    }
  } else if (isset($sa_attrs[$attr_name]) and
	     !is_null($sa_attrs[$attr_name]) and $sa_attrs[$attr_name] !== ''){
    $added_attrs[$attr_name] = $sa_attrs[$attr_name];
    if ($attr_name === "mail" or $attr_name === "email_address") {
      $newEmail = $sa_attrs[$attr_name];
    }
  }
}
foreach (array_keys($user->attributes) as $attr_name) {
  if (array_key_exists($attr_name, $sa_attrs)) {
    continue;
  }
  if (array_key_exists($attr_name, $attrs)) {
    if ($user->attributes[$attr_name] != $attrs[$attr_name]) {
      error_log("Account " . $user->account_id . " got changed shib attribute $attr_name. Was " 
		. $user->attributes[$attr_name] . " now " . $attrs[$attr_name]);
    }
    continue;
  }
  if (! isset($user->attributes[$attr_name]) or
      is_null($user->attributes[$attr_name]) or
      $user->attributes[$attr_name] === '') {
    continue;
  }
  if (array_key_exists($attr_name, $_SERVER)) {
    if ($_SERVER[$attr_name] !== $user->attributes[$attr_name]) {
      $changed_attrs[$attr_name] = $_SERVER[$attr_name];
      $changed_attrs_old[$attr_name] = $user->attributes[$attr_name];
    }
    continue;
  }
  $removed_attrs[$attr_name] = $user->attributes[$attr_name];
}

$pi_request = false;
if (array_key_exists("projectlead", $_REQUEST)) {
  $pi_request = true;
}

$is_pi = false;
if ($user->isAllowed(PA_ACTION::CREATE_PROJECT, CS_CONTEXT_TYPE::RESOURCE, null)) {
  $is_pi = true;
}

if (count($changed_attrs) == 0 and count($added_attrs) == 0 and
    count($removed_attrs) == 0 and $pi_request == $is_pi) {
  $_SESSION['lastmessage'] = "No account edits made.";
  relative_redirect('profile.php');
}

$changed_str = "";
if (count($changed_attrs) > 0) {
  foreach (array_keys($changed_attrs) as $attr_name) {
    $changed_str = $changed_str . "[" . $attr_name . "]: " . $changed_attrs_old[$attr_name] . " => " .
      $changed_attrs[$attr_name] . "; \n";
  }
    //  $changed_str = print_r($changed_attrs, true) . "(was "
    //    . print_r($changed_attrs_old, true) . ")";
}
$added_str = "";
if (count($added_attrs) > 0) {
  foreach (array_keys($added_attrs) as $attr_name) {
    $added_str = $added_str . "[" . $attr_name . "]: " . $added_attrs[$attr_name] . "; \n";
  }
  //  $added_str = print_r($added_attrs, true);
}
$removed_str = "";
if (count($removed_attrs) > 0) {
  foreach (array_keys($removed_attrs) as $attr_name) {
    $removed_str = $removed_str . "[" . $attr_name . "]: " . $removed_attrs[$attr_name] . "; \n";
  }
  //  $removed_str = print_r($removed_attrs, true);
}

// FIXME: This is not right
// Instead: Create a request infrastructure in the MA
// register a request
// MA sends mail
// MA has a method to approve or deny such a request
// approve needs to update the appropriate identity/ma_attribute
// tables, and then email the user that it was approved
// activate_account script then needs to call this
$body = "There is a new account change request on ". $_SERVER['SERVER_NAME'] . ":\n\n";
$body .= "Account ID: " . $user->account_id . "\n"; // same as member_id
$body .= "Identity ID: " . $user->identity_id . "\n";
$body .= "EPPN: " . $user->eppn . "\n"; // same as member->eppn
$body .= "Name: " . $user->prettyName() . "\n";
$body .= "Username: " . $user->username . "\n";
$body .= "Email: " . $user->email() . "\n";
$member_attributes = get_attribute_for_context(CS_CONTEXT_TYPE::MEMBER,
					       $user->account_id);
$subject = "New GENI CH account change requested";
if ($pi_request and ! $is_pi) {
  $body .= "\t\t**** Requesting to be a Project Lead. ****\n";
  $msg = $user->prettyName() . " requested to be a Project Lead";
  $subject = "New GENI CH Project Lead request";
  $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
  log_event($log_url, $user, $msg, $member_attributes, $user->account_id);
} else if (! $pi_request and $is_pi) {
  $body .= "\t\t**** Requesting to NOT be a Project Lead. ****\n";
  $msg = $user->prettyName() . " requested to NOT be a Project Lead";
  $log_url = get_first_service_of_type(SR_SERVICE_TYPE::LOGGING_SERVICE);
  log_event($log_url, $user, $msg, $member_attributes, $user->account_id);
}
if ($changed_str !== '') {
  $body .= "Changes: \n$changed_str\n";
}
if ($added_str !== '') {
  $body .= "Additions: \n$added_str\n";
}
if ($removed_str !== '') {
  $body .= "Removals: \n$removed_str\n";
}
include_once('/etc/geni-ch/settings.php');
global $portal_admin_email;
$headers = "Auto-Submitted: auto-generated\r\n";
$headers .= "Precedence: bulk\r\n";
mail($portal_admin_email, $subject,
     $body, $headers);

if ($pi_request and ! $is_pi) {
  // Email the experimenter that their request was received
  $cc = "";
  if (! is_null($newEmail) and $newEmail !== $user->email()) {
    $cc = "\r\nCc: " . $newEmail;
  }
  $to = $user->prettyEmailAddress();
  //  $from = "From: GENI Portal <www-data@portal.geni.net>"; // FIXME!!!
  $subject = "Your GENI Project Lead request has been received";
  $body = "\nDear " . $user->prettyName() . ",\n";
  $body = $body . "\nWe have received your request to be a GENI Project Lead.  We are processing your request and you should hear from us in 3-4 business days.\n";
  $body = $body . "\nIf you have any questions about your request or about using GENI in general, please email help@geni.net.\n";
  $body = $body . "\nThank you for your interest in GENI!\n";
  $body = $body . "\nSincerely,\n\nGENI Experimenter Support\nhelp@geni.net\n";
  $headers = "Reply-To: help@geni.net\r\n";
  $headers .= "Bcc: " . $portal_admin_email . "\r\n";
  $headers .= "Auto-Submitted: auto-generated\r\n";
  $headers .= "Precedence: bulk\r\n";
  $headers .= $cc;

  mail($to, $subject, $body, $headers);
}

//error_log("Request: " . print_r($_REQUEST, true));

// Validate the inputs
// Only allow editing certain fields
// Submit this as a Request to the MA Admin
// FIXME!

include("header.php");
show_header('GENI Portal Home', $TAB_HOME);
include("tool-breadcrumbs.php");
?>

<h1> Modify Your Account </h1>
<p><b>Your account modification request has been submitted.</b></p>
<ul>
<?php
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

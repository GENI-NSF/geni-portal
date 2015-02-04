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
require_once("db-util.php");
require_once("file_utils.php");
require_once("cert_utils.php");
require_once("util.php");
require_once("user.php");
require_once("sr_constants.php");
require_once("sr_client.php");
require_once("ma_constants.php");
require_once("ma_client.php");
require_once("portal.php");
require_once("km_utils.php");


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

/**
 * Find a multi-valued attribute in the ENV or POST.
 *
 * Sets parameter $value to an array of values. Sets $self_asserted
 * according to where the data was found. If in $_SERVER,
 * $self_asserted is false. If in $_POST, $self_asserted is true.
 *
 * Returns boolean indicating success (true) or failure
 * (i.e. attribute not found) (false).
 */
function multiAttrValue($attr, &$value, &$self_asserted) {
  $av_result = attrValue($attr, $raw_value, $self_asserted);
  if ($av_result) {
    // Parse raw_value into an array to return.  According to:
    // https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAddAttribute
    // "Multiple values are separated by a semicolon, and semicolons
    // in values are escaped with a backslash." The regular expression
    // below uses a lookbehind assertion to handle escaped
    // semicolons. I didn't write it, I found it on the internet. See
    // http://php.net/preg_split
    $value = preg_split('#(?<!\\\)\;#', $raw_value);
    return true;
  } else {
    // attrValue couldn't find the attribute, so return the failure to
    // the caller.
    $value = $raw_value;
    return $av_result;
  }
}

$sr_url = get_sr_url();
$ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);

// If not agree=agree, do nothing
if (! array_key_exists('agree', $_POST) or $_POST['agree'] !== 'agree') {
  $_SESSION['lasterror'] = "You must agree to GENI policies in order to use GENI.";
  relative_redirect('kmactivate.php');
}

$attrs = array(); // non-self-asserted attributes
$sa_attrs = array(); // Self-asserted attributes

$eppn = strtolower($_SERVER['eppn']);
$attrs['eppn'] = $eppn; // eppn is never self-asserted.

$first_name = null;
if (multiAttrValue('givenName', $first_name, $first_name_self_asserted)) {
  $first_name = $first_name[0];
  if ($first_name_self_asserted) {
    $sa_attrs[MA_ATTRIBUTE_NAME::FIRST_NAME] = $first_name;
  } else {
    $attrs[MA_ATTRIBUTE_NAME::FIRST_NAME] = $first_name;
  }
}

$last_name = null;
if (multiAttrValue('sn', $last_name, $last_name_self_asserted)) {
  $last_name = $last_name[0];
  if ($last_name_self_asserted) {
    $sa_attrs[MA_ATTRIBUTE_NAME::LAST_NAME] = $last_name;
  } else {
    $attrs[MA_ATTRIBUTE_NAME::LAST_NAME] = $last_name;
  }
}

$email_address = null;
if (multiAttrValue('mail', $email_address, $email_address_self_asserted)) {
  $email_address = $email_address[0];
}

if (! isset($email_address)) {
  $asserted_attrs = get_asserted_attributes($eppn);
  if (key_exists('mail', $asserted_attrs)) {
    $email_address = $asserted_attrs['mail'];
    $email_address_self_asserted = false;
  } else {
    error_log("No email, redirecting to kmnoemail.php");
    relative_redirect('kmnoemail.php');
  }
}

$email_address = filter_var($email_address, FILTER_SANITIZE_EMAIL);
if (! filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
  error_log("do-register got invalid email address! EPPN: " . $eppn . ", email: " . $email_address);
  // FIXME: Bail out?
}

if ($email_address_self_asserted) {
  $sa_attrs[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS] = $email_address;
} else {
  $attrs[MA_ATTRIBUTE_NAME::EMAIL_ADDRESS] = $email_address;
}

// Pick up remaining attributes. Affiliation is really multi-valued,
// but we treat it as one long string, so we pick it up as a simple
// attribute. DisplayName is a single-valued attribute per the
// documentation.
$all_attrs = array('affiliation' => 'affiliation',
                   'displayName' => 'displayName'
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

// Avoid double registration by checking if this is a valid
// user before creating a new account. If this user is already
// registered, redirect to the home page.
$member = ma_lookup_member_by_eppn($ma_url, Portal::getInstance(), $eppn);
  //$attrs = array('eppn' => $eppn);
  //$ma_members = ma_lookup_members($ma_url, Portal::getInstance(), $attrs);
  //$count = count($ma_members);
  //if ($count !== 0) {
if (!is_null($member)) {
  error_log("Attempted double registration by $eppn?");
  // Existing account, go to home page
  relative_redirect("home.php");
}

$result = ma_create_account($ma_url, $km_signer, $attrs, $sa_attrs);
if (is_array($result) && array_key_exists(RESPONSE_ARGUMENT::CODE, $result) && $result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
  error_log("Failed to create account for $attrs: $result");
  relative_redirect('error-text.php' . "?error=" . urlencode($result[RESPONSE_ARGUMENT::OUTPUT]));
}
$member_id = $result;

function derive_username() {
  // See http://www.linuxjournal.com/article/9585
  // try to figure out a reasonable username.
  $email_addr = NULL;
  if (array_key_exists('mail', $_SERVER)) {
    $email_addr = filter_input(INPUT_SERVER, 'mail', FILTER_SANITIZE_EMAIL);
  } else if (array_key_exists('mail', $_POST)) {
    $email_addr = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL);
  } else {
    // Use a fake one.
    $email_addr = 'unknown@example.com';
  }

  /* print "<br/>derive2: email_addr = $email_addr<br/>\n"; */

  // Now get the username portion.
  $atindex = strrpos($email_addr, "@");
  /* print "atindex = $atindex<br/>\n"; */
  $username = substr($email_addr, 0, $atindex);
  /* print "base username = $username<br/>\n"; */

  // FIXME: Follow the rules here: http://groups.geni.net/geni/wiki/GeniApiIdentifiers#Name
  // Max 8 characters
  // Case insensitive internally
  // Obey this regex: '^[a-zA-Z][\w]\{1,8\}$'

  // Sanitize the username so it can be used in ABAC
  $username = strtolower($username);
  $username = preg_replace("/[^a-z0-9_]/", "", $username);
  if (! db_fetch_user_by_username($username)) {
    /* print "no conflict with $username<br/>\n"; */
    return $username;
  } else {
    for ($i = 1; $i <= 99; $i++) {
      $tmpname = $username . $i;
      /* print "trying $tmpname<br/>\n"; */
      if (! db_fetch_user_by_username($tmpname)) {
        /* print "no conflict with $tmpname<br/>\n"; */
        return $tmpname;
      }
    }
  }
  die("Unable to find a username based on $username");
}

//--------------------------------------------------
// Create the database connection
//--------------------------------------------------
$conn = portal_conn();
if (PEAR::isError($conn)) {
  die("error connecting to db: " . $conn->getMessage());
}

//--------------------------------------------------
// Insert into account table
//--------------------------------------------------
$account_id = make_uuid();
$username = derive_username();
$sql = "INSERT INTO account (account_id, username, status) VALUES ("
  . $conn->quote($account_id, 'text')
  . ", ". $conn->quote($username, 'text')
  . ", 'requested');";
$result = $conn->exec($sql);
if (PEAR::isError($result)) {
  die("error on account insert: " . $result->getMessage());
}

//--------------------------------------------------
// Insert into identity table
//--------------------------------------------------

// TODO: Check for the existence of each, error if not available.
// TODO: Use filters to sanitize these
$eppn = strtolower($_SERVER['eppn']);
$shib_idp = $_SERVER['Shib-Identity-Provider'];
$affiliation = "";
if (array_key_exists('affiliation', $_SERVER)) {
  $affiliation = $_SERVER['affiliation'];
} else {
  //  error_log("IdP " . $shib_idp . " gave no affiliation for eppn " . $eppn);
}

$sql = "INSERT INTO identity (provider_url, eppn, affiliation, account_id)"
  . "VALUES ("
  . $conn->quote($shib_idp, 'text')
  . ", " . $conn->quote($eppn, 'text')
  . ", " . $conn->quote($affiliation, 'text')
  . ", " . $conn->quote($account_id, 'text')
  . ");";
// print $sql . "<br/>";

$result = $conn->exec($sql);
if (PEAR::isError($result)) {
  die("error on identity insert: " . $result->getMessage());
}

//--------------------------------------------------
// Now pull the id out of that newly inserted identity record and add
// the additional attributes.
//--------------------------------------------------
$sql = "SELECT identity_id from identity WHERE provider_url = "
  . $conn->quote($shib_idp, 'text')
  . " AND eppn = "
  . $conn->quote($eppn, 'text')
  . ";";
//print "Query = $sql<br/>";
$resultset = $conn->query($sql);
if (PEAR::isError($resultset)) {
  die("error on identity id select: " . $resultset->getMessage());
}
$rows = $resultset->fetchall(MDB2_FETCHMODE_ASSOC);
$rowcount = count($rows);
//print "rowcount = $rowcount<br/>";
$identity_id = $rows[0]['identity_id'];
//print "identity_id = $identity_id<br/>";

//--------------------------------------------------
// Add extra attributes
//--------------------------------------------------

function addIdentityAttribute($conn, $identity_id, $attr, $value,
                              $self_asserted) {
  $sql = "INSERT INTO identity_attribute "
    . "(identity_id, name, value, self_asserted) VALUES ("
    . $conn->quote($identity_id, 'integer')
    . ", " . $conn->quote($attr, 'text')
    . ", " . $conn->quote($value, 'text')
    . ", " . $conn->quote($self_asserted, 'boolean')
    . ");";
  /* print "attr insert: $sql<br/>"; */
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on attr $attr insert: " . $result->getMessage());
  }
}

if ($last_name) {
  addIdentityAttribute($conn, $identity_id, 'sn', $last_name,
                       $last_name_self_asserted);
}
if ($first_name) {
  addIdentityAttribute($conn, $identity_id, 'givenName', $first_name,
                       $first_name_self_asserted);
}
// We always have email address
addIdentityAttribute($conn, $identity_id, 'mail', $email_address,
                     $email_address_self_asserted);

if ($portal_enable_abac) {
  // ----------------------------------------------------------------------
  // Add an ABAC cert and key. This should probably be done when
  // and admin approves the request, but we don't have that page
  // yet, so... do it here for now, and migrate it later.
  // ----------------------------------------------------------------------
  $cmd_array = array("/usr/local/bin/creddy",
		     "--generate",
		     "--cn",
		     $username
		     );
  $command = implode(" ", $cmd_array);
  $orig_dir = getcwd();
  chdir(sys_get_temp_dir());
  $result = exec($command, $output, $status);
  $abac_id_file = $username . "_ID.pem";
  $abac_key_file = $username . "_private.pem";
  $abac_id = file_get_contents($abac_id_file);
  $abac_key = file_get_contents($abac_key_file);
  
  // Get the ABAC fingerprint for use in creating attributes later
  $cmd_array = array("/usr/local/bin/creddy",
		     "--keyid",
		     "--cert",
		     $abac_id_file
		     );
  $command = implode(" ", $cmd_array);
  // Clear the previous output
  unset($output);
  $result = exec($command, $output, $status);
  $abac_fingerprint = $output[0];
  unlink($abac_id_file);
  unlink($abac_key_file);
  chdir($orig_dir);
  
  // Put this stuff in the database
  $sql = "INSERT INTO abac"
    . "(account_id, abac_id, abac_key, abac_fingerprint) VALUES ("
    . $conn->quote($account_id, 'text')
    . ", ". $conn->quote($abac_id, 'text')
    . ", ". $conn->quote($abac_key, 'text')
    . ", ". $conn->quote($abac_fingerprint, 'text')
    . ");";
  $result = $conn->exec($sql);
  if (PEAR::isError($result)) {
    die("error on abac insert: " . $result->getMessage());
  }
} // end of block if portal_enable_abac

// if portal=portal, then authorize the portal.
// FIXME: Really this should be in a util in the km area for code
// cleanliness. Minor point though.

/* Where to send the user to authorize the portal. */
$authorize_portal_page = 'kmhome.php';
if ($speaks_for_enabled) {
  /* In speaks-for, go to the cert page */
  $authorize_portal_page = 'kmcert.php';
}

if (array_key_exists('portal', $_POST) and $_POST['portal'] === 'portal') {
  // get portal tool URN
  $portal_urn = ''; // FIXMEFIXME
  $candidate_tools = ma_list_clients($ma_url, $km_signer);
  foreach($candidate_tools as $toolname => $toolurn) {
    if ($toolname == 'portal') {
      $portal_urn = $toolurn;
      break;
    }
  }
  if ($portal_urn == '') {
    error_log("KM: Error authorizing portal for $username: Couldn't find portal in list of KM clients");
    $_SESSION['lastmessage'] = 'Your GENI account is active.';
    $_SESSION['lasterror'] = 'GENI Portal not authorized: Could not find portal in list of available clients';
    relative_redirect($authorize_portal_page);
  }
  $result = ma_authorize_client($ma_url, $km_signer, $member_id, $portal_urn, true);
  //  error_log("auth res = " . print_r($result, true));
  if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
    relative_redirect('home.php');
  } else {
    $auth_error = $result[RESPONSE_ARGUMENT::OUTPUT];
    error_log("KM: Error authorizing portal for $username: " . $auth_error);
    $_SESSION['lastmessage'] = 'Your GENI account is active.';
    $_SESSION['lasterror'] = 'GENI Portal not authorized: error authorizing: $auth_error';
    relative_redirect($authorize_portal_page);
  }
} else {
  // portal not authorized
  $_SESSION['lastmessage'] = 'Your GENI account is active.';
  if (! $speaks_for_enabled) {
    $_SESSION['lasterror'] = 'GENI Portal not authorized.';
  }
  relative_redirect($authorize_portal_page);
}

/* <?php */
/* include("header.php"); */
/* show_header('GENI Portal Home', $TAB_HOME); */
/* ?> */
/* <h2>Your account request has been submitted.</h2> */
/* Go to the <a href= */
/* <?php */
/* $url = relative_url("home.php"); */
/* if ($portal_auto_approve) { */
/*   $args['id'] = $account_id; */
/*   $query = http_build_query($args); */
/*   $url = relative_url("approve.php?"); */
/*   $url = $url . $query; */
/* } */
/* print $url */
/* ?> */
/* >portal home page</a> */

/* <?php */
/* //$array = $_POST; */
/* //foreach ($array as $var => $value) { */
/* //    print "POST[$var] = $value<br/>"; */
/* //    } */
/* ?> */

/* <hr/> */

/* <?php */
/* include("footer.php"); */
/* ?> */


?>

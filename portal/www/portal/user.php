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

require_once 'db-util.php';
require_once 'util.php';
require_once 'cs_client.php';
require_once 'sr_constants.php';
require_once 'sr_client.php';
require_once 'permission_manager.php';
require 'abac.php';
require_once 'ma_constants.php';
require_once 'ma_client.php';
require_once 'geni_syslog.php';
require_once 'portal.php';

const PERMISSION_MANAGER_TAG = 'permission_manager';
const PERMISSION_MANAGER_TIMESTAMP_TAG = 'permission_manager_timestamp';
const PERMISSION_MANAGER_ACCOUNT_ID_TAG = 'permission_manager_account_id';

const USER_CACHE_ACCOUNT_ID_TAG = 'user_cache_account_id';
const USER_CACHE_EPPN_TAG = 'user_cache_eppn';

//----------------------------------------------------------------------
// A class representing an experimenter who has logged in
// via an IdP.
//----------------------------------------------------------------------
class GeniUser
{
  public $identity_id;
  public $idp_url;
  public $eppn = NULL;
  public $account_id = NULL;
  public $affiliation;
  public $status = NULL;
  public $attributes;
  public $raw_attrs;


  function __construct() {
    $this->certificate = NULL;
    $this->private_key = NULL;
  }

  function init_from_member($member) {
    //  $this->identity_id = $row['identity_id'];
    //  $this->idp_url = $row['provider_url'];
    //  $this->affiliation = $row['affiliation'];
    $this->eppn = $member->eppn;
    $this->account_id = $member->member_id;
    $this->username = $member->username;
    if (isset($member->email_address)) {
      $this->attributes['mail'] = $member->email_address;
    }
    if (isset($member->displayName)) {
      $this->attributes['displayName'] = $member->displayName;
    }
    if (isset($member->first_name)) {
      $this->attributes['givenName'] = $member->first_name;
    }
    if (isset($member->last_name)) {
      $this->attributes['sn'] = $member->last_name;
    }
    if (isset($member->provider_url)) {
      $this->idp_url = $member->provider_url;
    }
    if (isset($member->affiliation)) {
      $this->affiliation = $member->affiliation;
    }
    if (isset($member->urn)) {
      $this->urn = $member->urn;
      geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "Found URN  " . $this->urn);
    }
    // FIXME: MA should maintain a member status
    $this->status = 'active';
    /* Store the MA member to read arbitrary properties
       via has_attribute */
    $this->ma_member = $member;
  }

  // Fill in attributes from this identity on this user. Lets us get affiliation and idp_url
  function init_from_identity($identity) {
    if (! isset($identity) or is_null($identity)) {
      return;
    }
    // loop over keys. set the values
    foreach (array_keys($identity) as $attr_name) {
      if ($attr_name == 'account_id') {
	continue;
      }
      if (! isset($this->{$attr_name})) {
	$this->{$attr_name} = $identity[$attr_name];
      } else if (isset($this->{$attr_name}) && $this->{$attr_name} != $identity[$attr_name]) {
	error_log("User " . $this->eppn . ": Changing member-set $attr_name to identity-set " . $identity[$attr_name]);
	$this->{$attr_name} = $identity[$attr_name];
      }
    }
    if (isset($this->provider_url) && ! isset($this->idp_url)) {
      $this->idp_url = $this->provider_url;
    }
  }

  // If we haven't re-read the permissions in this many seconds, re-read
  //  const STALE_PERMISSION_MANAGER_THRESHOLD_SEC = 30; 

  // MSB : Disable the permission manager cache: it is ALWAYS stale
  const STALE_PERMISSION_MANAGER_THRESHOLD_SEC = -1;

  function loadAccount() {
    /* print "in GeniUser->loadAccount<br/>"; */
    $dict = loadAccount($this->account_id);
    $this->status = $dict['status'];
    $this->username = $dict['username'];
    /*
     * It seems to be necessary to use a temporary
     * variable rather than assigning directly to
     * the instance variable. I don't know why.
     */
    $attrs = loadIdentityAttributes($this->identity_id);
    $this->raw_attrs = $attrs;
    foreach ($attrs as $attr) {
      $this->attributes[$attr['name']] = $attr['value'];
    }
    // identity table has this - but where else?
    if (isset($this->attributes['provider_url']) && ! isset($this->idp_url)) {
      $this->idp_url = $this->attributes['provider_url'];
    }
    if (isset($this->attributes['affiliation']) && ! isset($this->affiliation)) {
      $this->affiliation = $this->attributes['affiliation'];
    }
  }

  function isActive() {
    return $this->status == 'active';
  }
  function isRequested() {
    return $this->status == 'requested';
  }
  function isDisabled() {
    return $this->status == 'disabled';
  }

  function email() {
    /* return the value of the 'mail' attribute from the IdP. */
    return $this->attributes['mail'];
  }

  function urn() {
    return $this->urn;
  }

  function prettyName() {
    if (array_key_exists('displayName', $this->attributes)
        && $this->attributes['displayName']) {
      return $this->attributes['displayName'];
    } elseif (array_key_exists('givenName', $this->attributes)
        && $this->attributes['givenName']
        && array_key_exists('sn', $this->attributes)
        && $this->attributes['sn']) {
      return $this->attributes['givenName']
        . " " . $this->attributes['sn'];
    } else {
      return $this->email();
    }
  }

  // Is given permission (function/method/action) allowed in given 
  // context_type/context_id for given user?
  function isAllowed($permission, $context_type, $context_id)
  {

    global $cs_url;
    $now = time();
    $permission_manager = null;
    if (array_key_exists(PERMISSION_MANAGER_TAG, $_SESSION)) {
      $permission_manager = $_SESSION[PERMISSION_MANAGER_TAG];
    }
    $permission_manager_timestamp = null;
    if (array_key_exists(PERMISSION_MANAGER_TIMESTAMP_TAG, $_SESSION)) {
      $permission_manager_timestamp = $_SESSION[PERMISSION_MANAGER_TIMESTAMP_TAG];
    }
    $permission_manager_account_id = null;
    if (array_key_exists(PERMISSION_MANAGER_ACCOUNT_ID_TAG, $_SESSION)) {
      $permission_manager_account_id = $_SESSION[PERMISSION_MANAGER_ACCOUNT_ID_TAG];
    }

    //    error_log("SESSION = " . print_r($_SESSION, true));

    //    error_log("PMT = " . $permission_manager_timestamp  . " " . $now);

    if (
	($permission_manager == null) || 
	($permission_manager_account_id != $this->account_id) ||
	($now - $permission_manager_timestamp  > GeniUser::STALE_PERMISSION_MANAGER_THRESHOLD_SEC)
	) 
      {
	//	error_log("PM = " . $permission_manager . ", " . $this->account_id . ", " . $permission_manager_account_id);
	//	error_log("PMT = " . $permission_manager_timestamp  . " " . $now);
	if ($cs_url == null) {
	  $cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
	}
	$permission_manager = get_permissions($cs_url, $this, $this->account_id);
	$permission_manager_timestamp = $now;
	$permission_manager_account_id = $this->account_id;
	//	error_log("Refreshing permission manager " . $permission_manager_timestamp . " " 
	//		  . $permission_manager_account_id . " " 
	//		  . print_r($permission_manager, true));
	$_SESSION[PERMISSION_MANAGER_TAG] = $permission_manager;
	$_SESSION[PERMISSION_MANAGER_TIMESTAMP_TAG] = $now;
	$_SESSION[PERMISSION_MANAGER_ACCOUNT_ID_TAG] = $this->account_id;
      }
    //    error_log("PM = " . print_r($permission_manager, true));
    $result = $permission_manager->is_allowed($permission, $context_type, $context_id);
    return $result;
  }

  private function getInsideKeyPair() {

    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $row = lookup_keys_and_certs($ma_url, Portal::getInstance(),
            $this->account_id);
    $this->certificate = $row[MA_INSIDE_KEY_TABLE_FIELDNAME::CERTIFICATE];
    $this->private_key = $row[MA_INSIDE_KEY_TABLE_FIELDNAME::PRIVATE_KEY];
  }

  function certificate() {
    if (is_null($this->certificate)) {
      $this->getInsideKeyPair();
    }
    return $this->certificate;
  }

  function privateKey() {
    if (is_null($this->private_key)) {
      $this->getInsideKeyPair();
    }
    return $this->private_key;
  }

  /**
   * Fetch the user's ssh keys.
   */
  function sshKeys() {
    // NOTE: This is a candidate for caching on the HTTP session
    /* If I don't have an inside cert/key, I can't sign the call.
     * Return no keys. */
    if (! $this->certificate()) {
      return array();
    }
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $keys = lookup_ssh_keys($ma_url, $this, $this->account_id);
    return $keys;
  }

  function fetchMember($member_id)
  {
    if ($this->account_id == $member_id) return $this;
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $member = ma_lookup_member_by_id($ma_url, $this, $member_id);
    $user = new GeniUser();
    $user->init_from_member($member);
    // add in identity attributes
    //    error_log("In user " . $this->eppn . " looking up identity of eppn " . $user->eppn);
    $identity = geni_load_identity_by_eppn($user->eppn);
    $user->init_from_identity($identity);
    return $user;
  }

  function portalIsAuthorized()
  {
    // If an inside cert is present, the portal
    // is authorized.
    return ! is_null($this->certificate());
  }

  /**
   * Determine if the user has the specified attribute.
   * Returns true if the attribute exists, false otherwise.
   */
  function hasAttribute($a)
  {
    return property_exists($this->ma_member, $a);
  }

  // We could add getAttribute($a) which would return the value
  // of the attribute rather than the existence of the attribute.

} // End of class GeniUser

/* Insufficient attributes were released.
 * Funnel this back through the incommon
 * service to help the user understand.
 * See https://spaces.internet2.edu/display/InCCollaborate/Error+Handling+Service
 */
function incommon_attribute_redirect()
{
	$error_service_url = 'https://ds.incommon.org/FEH/sp-error.html?';
	$params['sp_entityID'] = "https://" . $_SERVER['SERVER_NAME'] . "/shibboleth";
	$params['idp_entityID'] = $_SERVER['Shib-Identity-Provider'];
	$query = http_build_query($params);
	$url = $error_service_url . $query;
	error_log("Insufficient attributes. Redirecting to $url");
	header("Location: $url");
	exit;
}

/**
 * Send email to operators declaring a failed attempt
 * to access the portal due to insufficient attributes.
 * Include the entire HTTP environment in case their is
 * useful info for debugging or contacting the institution
 * or experimenter.
 */
function send_attribute_fail_email()
{
  // From /etc/geni-ch/settings.php
  global $portal_admin_email;
  $server_host = $_SERVER['SERVER_NAME'];
  $body = "An access attempt on $server_host failed";
  $body .= " due to insufficient attributes.";
  $body .= "\n\nServer environment:\n";
  // Put the entire HTTP environement in the email
  // for debugging.
  $array = $_SERVER;
  foreach ($array as $var => $value) {
    $body .= "$var = $value\n";
  }
  mail($portal_admin_email,
          "Portal access failure on $server_host",
          $body);
}

// Loads an experimenter from the database.
function geni_loadUser_legacy($id='')
{
  $conn = portal_conn();
  $conn->setFetchMode(MDB2_FETCHMODE_ASSOC);
  $eppn = '';

  if ($id == '') {
    // Short circuit if no eppn. We require eppn as the persistent db key.
    if (! array_key_exists('eppn', $_SERVER)) {
      // No eppn was found - redirect to a gentle error page
      incommon_attribute_redirect();
    }

    $eppn = $_SERVER['eppn'];

    $query = 'SELECT * FROM identity WHERE eppn = '
      . $conn->quote($eppn, 'text');
  } else {
    // FIXME: There may be multiple identities with the same account
    // So which identity do you use
    $query = 'SELECT * FROM identity WHERE account_id = ' . $conn->quote($id, 'text');
  }

  // Try to return a value from cache
  if (strcmp($id, '') <> 0 || strcmp($eppn, '') <> 0) {
    $user_from_cache = geni_loadUser_cache($id, $eppn);
    if ($user_from_cache != null) {
      $dbStatus = loadAccountStatus($user_from_cache->account_id);
      if (is_null($dbStatus)) {
	$dbStatus = "noaccount";
      } elseif (is_array($dbStatus)) {
	$dbStatus = $dbStatus['status'];
      }
      if ($dbStatus == $user_from_cache->status) {
	return $user_from_cache;
      } else {
	error_log("Not using user from cache with different status: $dbStatus != " . $user_from_cache->status);
      }
    }
  }

  $res =& $conn->queryAll($query);

  // Always check that result is not an error
  if (PEAR::isError($res)) {
    die("error on query: " . $res->getMessage());
  }

  $row_count = count($res);
  /* print("Query was: $query<br/>"); */
  /* print("Found $row_count rows<br/>"); */

  $rownum = -1;
  if ($row_count == 0) {
    // New identity, go to activation page
    relative_redirect("kmactivate.php");
  } else if ($row_count > 1) {
    if ($id != '') {
      // An account ID was selected. Which identity do we use?
      // Pick the max by identity ID for now
      $rowcur = 0;
      $idmax = 0;
      foreach ($res as $row) {
	$rowcur = $rowcur+1;
	if ($row['identity_id'] > $idmax) {
	  $idmax = $row['identity_id'];
	  $rownum = $rowcur;
	}
      }
    } else {
      // More than one row! Something is wrong!
      die("Too many identity matches - " . $row_count . " identities for eppn " . $eppn . ".");
    }
  } else {
    $rownum = 0;
  }
 
  if ($rownum >= 0) {
    // There is exactly 1 such identity, or an account_id was
    // specified, and we picked the row we want

    // The identity already exists, find the account
    $row = $res[$rownum];
    /* foreach ($row as $var => $value) { */
    /*   print "geni_loadUser row $var = $value<br/>"; */
    /* } */

    $user = new GeniUser();
    $user->identity_id = $row['identity_id'];
    $user->idp_url = $row['provider_url'];
    $user->affiliation = $row['affiliation'];
    $user->eppn = $row['eppn'];
    $user->account_id = $row['account_id'];
    $user->loadAccount();

    // Cache the IDP attributes as ABAC assertions
    abac_store_idp_attrs($user);

    // Cache the user by account_id
    ensure_user_cache();
    $user_cache_account_id = $_SESSION[USER_CACHE_ACCOUNT_ID_TAG];
    $user_cache_eppn = $_SESSION[USER_CACHE_EPPN_TAG];
    $user_cache_account_id[$user->account_id] = $user;
    $user_cache_eppn[$user->eppn] = $user;
    $_SESSION[USER_CACHE_ACCOUNT_ID_TAG] = $user_cache_account_id;
    $_SESSION[USER_CACHE_EPPN_TAG] = $user_cache_eppn;
    //    error_log("Caching " . print_r($user, true) . " " . $user->account_id . " " . $user->eppn);

    return $user;
  }

}


function geni_load_user_by_eppn($eppn)
{
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $attrs = array('eppn' => $eppn);
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "Looking up EPPN " . $eppn);
  $ma_members = ma_lookup_members($ma_url, Portal::getInstance(), $attrs);
  $count = count($ma_members);
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "Found " . $count . " members.");
  if ($count == 0) {
    // New identity, go to activation page
    relative_redirect("kmactivate.php");
  } else if ($count > 1) {
    // ERROR: multiple users under unique key
  }
  $member = $ma_members[0];
  $user = new GeniUser();
  $user->init_from_member($member);
  return $user;
}

function geni_load_user_by_member_id($member_id)
{
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  $member = ma_lookup_member_by_id($ma_url, Portal::getInstance(), $member_id);
  $user = new GeniUser();
  $user->init_from_member($member);
  return $user;
}

// Load the identity attributes from the DB, looking up by eppn
function geni_load_identity_by_eppn($eppn)
{
  if (! isset($eppn) || is_null($eppn)) {
    return array();
  }
  $identity = array();
  $conn = db_conn();
  $sql = "select * from identity where eppn = " . $conn->quote($eppn, 'text');
  $row = db_fetch_row($sql);
  if ($row['code'] == RESPONSE_ERROR::NONE) {
    $identity = $row['value'];
  } else {
    error_log("Failed to get identity info for eppn $eppn: " . $row['output']);
    return $identity;
  }

  //  error_log("Got identity for $eppn: " . print_r($identity, true));

  // FIXME: If affiliation has changed, log it / update it
  $sql = "select * from identity_attribute where identity_id = " . $conn->quote($identity['identity_id'], 'text');
  $res = db_fetch_rows($sql);
  if ($res['code'] != RESPONSE_ERROR::NONE) {
    error_log("Failed to get identity attributes for eppn $eppn: " . $res['output']);
    return $identity;
  }
  $rows = $res['value'];
  $cureppn = null;
  if (array_key_exists('eppn', $_SERVER)) {
    $cureppn = $_SERVER['eppn'];
  }
  foreach($rows as $row) {
    $identity[$row['name']] = $row['value'];
    if (! is_null($cureppn) && $cureppn == $identity['eppn'] && array_key_exists($row['name'], $_SERVER)) {
      if ($row['value'] != $_SERVER[$row['name']]) {
	error_log("IdP changed value for eppn $eppn value " . $row['name'] . ": Old=" . $row['value'] . ", new = " . $_SERVER[$row['name']]);
	geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "IdP changed value for eppn $eppn value " . $row['name'] . ": Old=" . $row['value'] . ", new = " . $_SERVER[$row['name']]);
	// FIXME: Update the identity_attribute table!
      }
    }
  }

  //  error_log("Added attributes, now ID is " . print_r($identity, true));

  return $identity;
}

/**
 * Dispatch function to support migration to MA.
 * @param unknown_type $account_id
 */
function geni_loadUser()
{

  // TODO: Look up in cache here
  if (! array_key_exists('eppn', $_SERVER)) {
    // Requird attributes were not found - redirect to a gentle error page
    send_attribute_fail_email();
    incommon_attribute_redirect();
  }
  // Load current user based on Shibboleth environment
  $eppn = $_SERVER['eppn'];
  $user = geni_load_user_by_eppn($eppn);
  $identity = geni_load_identity_by_eppn($eppn);
  $user->init_from_identity($identity);
  // FIXME: Confirm that attributes we have in DB match attributes in the environment

  // TODO: Insert user in cache here
  return $user;
}


// Loads an exerimenter from the cache if there
function geni_loadUser_cache($account_id, $eppn)
{
  ensure_user_cache();
  $user = null;
  //  error_log("GLUC -" . $account_id . "- -" . $eppn . "- -" . strcmp($account_id, '') . "-");
  if (strcmp($account_id, '') <> 0) {
    $user_cache_account_id = $_SESSION[USER_CACHE_ACCOUNT_ID_TAG];
    //    error_log("CACHE_AI = " . print_r($user_cache_account_id, true));
    if (array_key_exists($account_id, $user_cache_account_id)) {
      $user = $user_cache_account_id[$account_id];
      //      error_log("pulled user from user_cache_account_id from session");
    }
  }
  if ($user == null && strcmp($eppn, '') <> 0) {
    $user_cache_eppn = $_SESSION[USER_CACHE_EPPN_TAG];
    //    error_log("CACHE_EPPN = " . print_r($user_cache_eppn, true));
    if (array_key_exists($eppn, $user_cache_eppn)) {
      $user = $user_cache_eppn[$eppn];
      //      error_log("pulled user from user_cache_eppn from session");
    }
  }

  /* error_log('CACHE ACCT=' . $account_id . ' EPPN=' . $eppn . " USER=" . print_r($user, true)); */
  /* reset($user); */
  return $user;
}

function ensure_user_cache()
{
  if (!array_key_exists(USER_CACHE_ACCOUNT_ID_TAG, $_SESSION)) {
    $_SESSION[USER_CACHE_ACCOUNT_ID_TAG] = array();
    error_log("cleared/set user cache acount id tag in session");
  }
  if (!array_key_exists(USER_CACHE_EPPN_TAG, $_SESSION)) {
    $_SESSION[USER_CACHE_EPPN_TAG] = array();
    error_log("cleared/set user cache eppn tag in session");
  }
}

if (!isset($_SESSION)) {
  session_start();
}

?>

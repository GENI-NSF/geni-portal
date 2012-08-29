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

  /* FIXME: This needs to be an MA function. */
  function urn() {
    exec('/bin/hostname -s', $site, $status);
    if ($status) {
      error_log("error running \"/bin/hostname -s\": $site");
      $site = 'unknown';
    } else {
      $site = $site[0];
    }
    $urn = "urn:publicid:IDN+$site+user+" . $this->username;
    return $urn;
  }

  function prettyName() {
    if (array_key_exists('givenName', $this->attributes)
        && array_key_exists('sn', $this->attributes)) {
      return $this->attributes['givenName']
        . " " . $this->attributes['sn'];
    } else {
      return $this->eppn;
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
    $row = db_fetch_inside_private_key_cert($this->account_id);
    $this->certificate = $row['certificate'];
    $this->private_key = $row['private_key'];
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

} // End of class GeniUser

/* Insufficient attributes were released.
 * Funnel this back through the incommon
 * service to help the user understand.
 * See https://spaces.internet2.edu/display/InCCollaborate/Error+Handling+Service
 */
function incommon_attribute_redirect()
{
	$error_service_url = 'https://ds.incommon.org/FEH/sp-error.html?';
	$params['sp_entityID'] = "https://" . $_SERVER[HTTP_HOST] . "/shibboleth";
	$params['idp_entityID'] = $_SERVER['Shib-Identity-Provider'];
	$query = http_build_query($params);
	$url = $error_service_url . $query;
	error_log("Insufficient attributes. Redirecting to $url");
	header("Location: $url");
	exit;
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
    // New identity, go to registration page
    relative_redirect("register.php");
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
  $ma_members = ma_lookup_members($ma_url, PORTAL::getInstance(), $attrs);
  $count = count($ma_members);
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "Found " . $count . " members.");
  if ($count == 0) {
    // No user by that eppn. Pass to registration page.
  } else if ($count > 1) {
    // ERROR: multiple users under unique key
  }
  $member = $ma_members[0];
  $user = new GeniUser();
//  $user->identity_id = $row['identity_id'];
//  $user->idp_url = $row['provider_url'];
//  $user->affiliation = $row['affiliation'];
  $user->eppn = $eppn;
  $user->account_id = $member->member_id;
  $user->attributes['mail'] = $member->email_address;
  $user->attributes['givenName'] = $member->first_name;
  $user->attributes['sn'] = $member->last_name;

  // FIXME: MA should maintain a member status
  $user->status = 'active';
  return $user;
}


/**
 * Dispatch function to support migration to MA.
 * @param unknown_type $account_id
 */
function geni_loadUser($account_id = NULL)
{
  $use_ma = FALSE;
  if (! $use_ma) {
    return geni_loadUser_legacy(is_null($account_id) ? '' : $account_id);
  }

  $user = NULL;
  // TODO: Look up in cache here
  if (is_null($account_id)) {
    if (! array_key_exists('eppn', $_SERVER)) {
      // No eppn was found - redirect to a gentle error page
      incommon_attribute_redirect();
    }
    // Load current user based on Shibboleth environment
    $eppn = $_SERVER['eppn'];
    $user = geni_load_user_by_eppn($eppn);
  } else {
    // Load user by account id
    $user = geni_load_user_by_account_id($account_id);
  }
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

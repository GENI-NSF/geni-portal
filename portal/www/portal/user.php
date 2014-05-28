<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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
require_once 'settings.php';
if ($portal_enable_abac)
  require 'abac.php';
require_once 'ma_constants.php';
require_once 'ma_client.php';
require_once 'geni_syslog.php';
require_once 'portal.php';
require_once('maintenance_mode.php');
require_once('cs_constants.php');

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
    $this->sf_cred = NULL;
    $this->sf_expires = NULL;
    $this->portal = NULL;
  }

  function init_from_member($member) {
    $this->attributes = array();
    //  $this->identity_id = $row['identity_id'];
    //  $this->idp_url = $row['provider_url'];
    //  $this->affiliation = $row['affiliation'];
    if (! isset($member) or is_null($member)) {
      error_log("Null member to init a user - the MA?");
      return;
    }
    $this->eppn = $member->eppn;
    $this->account_id = $member->member_id;
    $this->username = $member->username;
    $this->urn = $member->urn;
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
    if (isset($member->certificate)) {
      $this->certificate = $member->certificate;
    }
    if (isset($member->private_key)) {
      $this->private_key = $member->private_key;
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

  function phone() {
    /* return the value of the telephone number attribute from the IdP. */
    if (isset($this->ma_member->telephone_number))
      $phone = $this->ma_member->telephone_number;
    else
      $phone = "";
    return $phone;

  }
  
  // Produce a pretty email name/address for sending like '"My Name" <my email>'
  function prettyEmailAddress() {
    return sprintf('"%s" <%s>', $this->prettyName(),
		   $this->email());
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

    //    error_log("GIKP : " . print_r($this, true));
    //    error_log("GIKP : " . $this->certificate . " " . $this->private_key);

    // We only do this for the currently logged in user
    if(strtolower($_SERVER['eppn']) != $this->eppn) {
      throw new Exception("Can't call getInsideKeyPair other than for current suer");
    }

    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $row = lookup_keys_and_certs($ma_url, Portal::getInstance(),
            $this->account_id);
    if ($row != null) {
      if (in_array(MA_INSICE_KEY_TABLE_FIELDNAME::CERTIFICATE, $row))
	$this->certificate = $row[MA_INSIDE_KEY_TABLE_FIELDNAME::CERTIFICATE];
      if (in_array(MA_INSICE_KEY_TABLE_FIELDNAME::PRIVATE_KEY, $row))
	$this->private_key = $row[MA_INSIDE_KEY_TABLE_FIELDNAME::PRIVATE_KEY];
    }
  }

  /**
   * Get the inside certificate for this user. For use when the inside
   * key/cert is the only way to go.
   */
  function insideCertificate() {
    if (is_null($this->certificate)) {
      $this->getInsideKeyPair();
    }
    return $this->certificate;
  }

  /**
   * Get the inside private key for this user. For use when the inside
   * key/cert is the only way to go.
   */
  function insidePrivateKey() {
    if (is_null($this->private_key)) {
      $this->getInsideKeyPair();
    }
    return $this->private_key;
  }

  /*------------------------------------------------------------
   * Signer implementation
   *------------------------------------------------------------*/
  function certificate() {
    global $speaks_for_enabled;
    if ($this->sfcred || (isset($speaks_for_enabled) && $speaks_for_enabled)) {
      if (is_null($this->portal)) {
        $this->portal = Portal::getInstance();
      }
      return $this->portal->certificate();
    } else {
      /* Not using speaks for */
      if (is_null($this->certificate)) {
        $this->getInsideKeyPair();
      }
      return $this->certificate;
    }
  }

  function privateKey() {
    global $speaks_for_enabled;
    if ($this->sfcred || (isset($speaks_for_enabled) && $speaks_for_enabled)) {
      if (is_null($this->portal)) {
        $this->portal = Portal::getInstance();
      }
      return $this->portal->privateKey();
    } else {
      /* Not using speaks for */
      if (is_null($this->private_key)) {
        $this->getInsideKeyPair();
      }
      return $this->private_key;
    }
  }

  function speaksForCred() {
    return $this->sfcred;
  }

  /*------------------------------------------------------------
   * End Signer implementation
   *------------------------------------------------------------*/

  /**
   * Fetch the user's public ssh keys.
   */
  function sshKeys() {
    // NOTE: This is a candidate for caching on the HTTP session
    /* If I don't have an inside cert/key, I can't sign the call.
     * Return no keys. */
    if (! $this->certificate()) {
      return array();
    }
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $keys = lookup_private_ssh_keys($ma_url, $this, $this->account_id);
    return $keys;
  }

  // Retrieve a list of user objecs for a list member UIDs
  // Don't pull information from identity tables
  function fetchMembersNoIdentity($member_ids)
  {
    $members = array();
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $other_member_ids = array();
    foreach($member_ids as $member_id) {
      if ($member_id != $this->account_id) {
	$other_member_ids[] = $member_id;
      } else {
	$members[] = $this->ma_member;
      }
    }
    $other_members = ma_lookup_members_by_identifying($ma_url, $this, 'MEMBER_UID', $other_member_ids);
    $members = array_merge($members, $other_members);
    return $members;
  }

  function fetchMember($member_id)
  {
    if ($this->account_id == $member_id) return $this;
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    $member = ma_lookup_member_by_id($ma_url, $this,
				     $member_id);
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
  $params['sp_entityID'] = "https://panther.gpolab.bbn.com/shibboleth";
  $params['idp_entityID'] = $_SERVER['Shib-Identity-Provider'];
  $query = http_build_query($params);
  $url = $error_service_url . $query;
  error_log("Insufficient attributes from identity provider with entityID = \""
            . $params['idp_entityID']
            . "\". Redirecting to InCommon federated error handling service.");
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
  if (array_key_exists("Shib-Identity-Provider", $_SERVER)) {
    $body .= " from " . $_SERVER["Shib-Identity-Provider"];
  }
  $body .= " due to insufficient attributes.";
  $body .= "\n\nServer environment:\n";
  // Put the entire HTTP environement in the email
  // for debugging.
  $array = $_SERVER;
  foreach ($array as $var => $value) {
    $body .= "$var = $value\n";
  }
  $headers = "Auto-Submitted: auto-generated\r\n";
  $headers .= "Precedence: bulk\r\n";
  mail($portal_admin_email,
          "Portal access failure on $server_host",
       $body, $headers);
}

function geni_load_user_by_eppn($eppn, $sfcred)
{
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  //  $attrs = array('eppn' => $eppn);
  geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "Looking up EPPN " . $eppn);
  $signer = Portal::getInstance($sfcred);
  $member = ma_lookup_member_by_eppn($ma_url, $signer, $eppn);
  //error_log("MEMBER = " . print_r($member, True));
  if (is_null($member) || !isset($member->certificate)) {
    // New identity, go to activation page
    relative_redirect("kmactivate.php");
  } else {
    geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "Found member for EPPN " . $eppn);
  }
  $user = new GeniUser();
  $user->init_from_member($member);
  $user->sfcred = $sfcred;
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
  $eppn = strtolower($eppn);
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
    $cureppn = strtolower($_SERVER['eppn']);
  }
  foreach($rows as $row) {
    $identity[$row['name']] = $row['value'];
    if (! is_null($cureppn) && $cureppn == $identity['eppn'] && array_key_exists($row['name'], $_SERVER)) {
      if ($row['value'] != $_SERVER[$row['name']]) {
	error_log("IdP changed value for eppn $eppn value " . $row['name'] . ": Old=" . $row['value'] . ", new = " . $_SERVER[$row['name']]);
	geni_syslog(GENI_SYSLOG_PREFIX::PORTAL, "IdP changed value for eppn $eppn value " . $row['name'] . ": Old=" . $row['value'] . ", new = " . $_SERVER[$row['name']]);
	// FIXME: Update the identity_attribute table!
	// "update identity_attribute set value = '" . $conn->quote($_SERVER[$row['name']], 'text') . "' where identity_id = " . $conn->quote($identity['identity_id'], 'text') . " and name = '" . $conn->quote($row['name'], 'text')
	// What about ma_member_attribute? That one is harder to generalize
	// update ma_member_attribute set value = <new> where value = <old> and member_id = !!!! <and name = !!!
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
  global $in_maintenance_mode;

  // TODO: Look up in cache here
  if (! array_key_exists('eppn', $_SERVER)) {
    // Required attributes were not found - redirect to a gentle error page
    send_attribute_fail_email();
    incommon_attribute_redirect();
  }

  // Load current user based on Shibboleth environment
  $eppn = strtolower($_SERVER['eppn']);
  $sfcred = NULL;
  global $speaks_for_enabled;
  $sfcred = fetch_speaks_for($eppn, $expires);
  if ($sfcred === FALSE) {
      /* A DB error occurred. */
    if (isset($speaks_for_enabled) && $speaks_for_enabled) {
      return NULL;
    }
  } else if (is_null($sfcred)) {
    if (isset($speaks_for_enabled) && $speaks_for_enabled) {
      error_log("No speaks for cred on file for eppn '$eppn'");
      relative_redirect('speaks-for.php');
    }
  }
  $user = geni_load_user_by_eppn($eppn, $sfcred);
  $identity = geni_load_identity_by_eppn($eppn);
  $user->init_from_identity($identity);
  // FIXME: Confirm that attributes we have in DB match attributes in the environment

  // Non-operators can't use the portal while in maintenance: they go to the 'Maintenance" page
  if ($in_maintenance_mode && 
      !$user->isAllowed(CS_ACTION::ADMINISTER_MEMBERS, CS_CONTEXT_TYPE::MEMBER, 
			null)) 
    {
      error_log($user->prettyName() . " tried to access portal during maintenance");
      relative_redirect("maintenance_redirect_page.php");
    }

  // TODO: Insert user in cache here
  return $user;
}


// Loads an exerimenter from the cache if there
function geni_loadUser_cache($account_id, $eppn)
{
  $eppn = strtolower($eppn);
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

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
require 'abac.php';

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
  }

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
    $this->privileges = loadAccountPrivileges($this->account_id);
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

  function prettyName() {
    if (array_key_exists('givenName', $this->attributes)
        && array_key_exists('sn', $this->attributes)) {
      return $this->attributes['givenName']
        . " " . $this->attributes['sn'];
    } else {
      return $this->eppn;
    }
  }

  // For now, everyone can create slices
  function privSlice() {
    return in_array ("slice", $this->privileges);
  }

  // For now, everyone is an admin
  function privAdmin() {
    return in_array ("admin", $this->privileges);
  }
}

// Loads an experimenter from the database.
function geni_loadUser()
{
  $conn = portal_conn();
  $conn->setFetchMode(MDB2_FETCHMODE_ASSOC);

  // Short circuit if no eppn. We require eppn as the persistent db key.
  if (! array_key_exists('eppn', $_SERVER)) {
    // No eppn was found - redirect to a gentle error page
    relative_redirect("error-eppn.php");
  }

  $eppn = $_SERVER['eppn'];
  $query = 'SELECT * FROM identity WHERE eppn = '
    . $conn->quote($eppn, 'text');
  $res =& $conn->queryAll($query);

  // Always check that result is not an error
  if (PEAR::isError($res)) {
    die("error on query: " . $res->getMessage());
  }

  $row_count = count($res);
  /* print("Query was: $query<br/>"); */
  /* print("Found $row_count rows<br/>"); */

  if ($row_count == 0) {
    // New identity, go to registration page
    relative_redirect("register.php");
  } else if ($row_count == 1) {
    // The identity already exists, find the account
    $row = $res[0];
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

    return $user;
  } else {
    // More than one row! Something is wrong!
    die("Too many identity matches - " . $row_count . " identities for eppn " . $eppn . ".");
  }
}
?>

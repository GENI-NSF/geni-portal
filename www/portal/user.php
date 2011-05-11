<?php

require_once 'db-util.php';
require_once 'util.php';

//----------------------------------------------------------------------
// A class representing an experimenter who has logged in
// via an IdP.
//----------------------------------------------------------------------
class GeniUser
{
  public $eppn = NULL;
  public $account_id = NULL;
  public $status = NULL;

  function __construct() {
  }

  function loadAccount() {
    print "in GeniUser->loadAccount<br/>";
    $dict = loadAccount($this->account_id);
    $this->status = $dict['status'];
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
}

// Loads an experimenter from the database.
function geni_loadUser()
{
  $conn = portal_conn();
  $conn->setFetchMode(MDB2_FETCHMODE_ASSOC);

  $eppn = $_SERVER['eppn'];
  $query = 'SELECT * FROM identity WHERE eppn = '
    . $conn->quote($eppn, 'text');
  $res =& $conn->queryAll($query);

  // Always check that result is not an error
  if (PEAR::isError($res)) {
    die("error on query: " . $res->getMessage());
  }

  $row_count = count($res);
  print("Query was: $query<br/>");
  print("Found $row_count rows<br/>");

  if ($row_count == 0) {
    // New identity, go to registration page
    relative_redirect("register.php");
  } else if ($row_count == 1) {
    // The identity already exists, find the account
    $row = $res[0];
    foreach ($row as $var => $value) {
      print "geni_loadUser row $var = $value<br/>";
    }

    $user = new GeniUser();
    //    $user->$eppn = $res[0]['eppn'];
    $user->account_id = $res[0]['account_id'];
    $user->loadAccount();
    return $user;
  } else {
    // More than one row! Something is wrong!
    die("Too many identity matches.");
  }
}
?>

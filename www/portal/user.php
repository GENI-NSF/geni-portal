<?php

//----------------------------------------------------------------------
// Create the database connection.
//
// We should probably place this in a db-util file in the future.
//----------------------------------------------------------------------
require_once 'MDB2.php';

function portal_conn()
{
  $db_dsn = 'pgsql://portal:portal@localhost/portal';
  $db_options = array('debug' => 5,
                      'result_buffering' => false,
                      );
  $portal_db =& MDB2::singleton($db_dsn, $db_options);
  if (PEAR::isError($portal_db)) {
    die("Error connecting: " . $portal_db->getMessage());
  }
  return $portal_db;
}

//----------------------------------------------------------------------
// A class representing an experimenter who has logged in
// via an IdP.
//----------------------------------------------------------------------
class GeniUser
{
  public $eppn = NULL;

  function __construct($eppn) {
    $this->$eppn = $eppn;
  }

  public function isValid() {
    return True;
  }
}

// Loads an experimenter from the database.
function geni_loadUser($eppn)
{
  $conn = portal_conn();
  $conn->setFetchMode(MDB2_FETCHMODE_ASSOC);

  $query = 'SELECT * FROM identity WHERE eppn = '
    . $conn->quote($eppn, 'text');

  $res =& $conn->queryAll($query);

  // Always check that result is not an error
  if (PEAR::isError($res)) {
    die("error on query: " . $res->getMessage());
  }

  $row_count = count($res);
  print("Query was: " . $query);
  print("Found " . $row_count . " rows");

  if ($row_count == 0) {
    /* Redirect to a different page in the current
       directory that was requested */
    $protocol = "http";
    if (array_key_exists('HTTPS', $_SERVER)) {
      $protocol = "https";
    }
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $extra = 'register.php';
    header("Location: $protocol://$host$uri/$extra");
    exit;
  } else if ($row_count == 1) {
    // The identity already exists!
    die("This user already exists");
  } else {
    // More than one row! Something is wrong!
    die("Too many identity matches.");
  }
}
?>

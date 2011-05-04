<?php

// A class representing an experimenter who has logged in
// via an IdP.
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
  if ((strncasecmp($eppn, 'tmitchel', 8)) == 0) {
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
  } else {
    return new GeniUser($eppn);
  }
}
?>

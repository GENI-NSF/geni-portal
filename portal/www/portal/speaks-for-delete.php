<?php
//----------------------------------------------------------------------
// Copyright (c) 2014-2015 Raytheon BBN Technologies
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

/*
 * Delete the user's speaks-for credential, deauthorizing the portal.
 */
require_once 'user.php';
require_once 'db-util.php';

$key_token = NULL;
if (array_key_exists('AUTH_TYPE', $_SERVER)
    && strcmp($_SERVER['AUTH_TYPE'], 'shibboleth') == 0) {
  /* Shibboleth authentication is present. Look for EPPN. */
  if (array_key_exists('eppn', $_SERVER)) {
    /* Our key token is the EPPN with shibboleth authentication. */
    $key_token = $_SERVER['eppn'];
  }
}

/* Bail out because no key token was found. */
if (is_null($key_token)) {
  header('Unauthorized', true, 401);
  exit();
}

$db_result = delete_speaks_for($key_token);

if (! $db_result) {
  header('HTTP/1.1 500 Cannot delete credential');
  exit();
}

// All done. Signal success without passing any content.
$_SESSION['lastmessage'] = "The GENI portal is no longer authorized.";
header('HTTP/1.1 204 No Content');
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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

// This file facilitates the passing of a GENI certificate to SAVI
// for purposes of using an existing SAVI account or creating a new one.

// The workflow:
//   1. Ensure tha the user has an outside certificate. If not, direct them
//     to the page to set up an outside certificate.
//   2. Ask them if they want to pass their certificate to SAVI. 
//      If not, go back
//      If so, POST {'cert' : cert) to $savi_url

require_once('user.php');
require_once('portal.php');
require_once('header.php');

$user = geni_loadUser();
$savi_url = 'http://controller.mcgeer-qv4564.vikelab-pg0.apt.emulab.net:5001/geni';
$ssl_url = "profile.php#ssl";

error_log("USER = " . print_r($user, true));

$cert = $user->certificate();
if ($cert == NULL || strlen($cert) == 0) {
  // *** Put up error message and button to return
}

error_log("CERT = " . print_r($cert, true));

$post_data = array('cert' => $cert);

?>

<p>This form will send your GENI certificate to authenicate to SAVI
(or create a SAVI account if this is your first time connecting to SAVI).
</p>

<p>
Do you with to send your GENI certificate to SAVI?
</p>

<?php

// Does this user have an external certificate? If not, go to $ssl_url

// Start a form
echo '<form id="f1" action="' . $savi_url . '" method="post">';
echo '<input type="hidden" name="cert" value="' . $cert . '"/>';
echo '<button type="submit" >Proceed</button>';
echo '<button type="button" onClick="history.back(-1)"/>Cancel</button>';
echo '</form>';
?>

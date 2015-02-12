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
require_once("util.php");
require_once("user.php");

/*
 * Convert identity provider attributes into ABAC attributes and store
 * them in the database.
 */
function abac_store_idp_attrs($user) {
  /* For now, just add affiliation. The other attributes are identifiable. */
  $affiliations = $_SERVER['affiliation'];
  $affiliations = split(';', $affiliations);
  $subject_id = fetch_abac_fingerprint($user->account_id);
  $issuer_fingerprint = "";
  foreach ($affiliations as $affiliation) {
    $role = $affiliation;
    $role = str_replace('@', '_at_', $role);
    $role = str_replace('.', '_dot_', $role);
    // Make the assertion with creddy
    // Record the data
    // Can we get an assertion expiration time? Easily?
    $assertion = abac_assert($role, $subject_id);
    // Store this in the database
    storeAbacAssertion($assertion,
                       $issuer_fingerprint,
                       $role,
                       $subject_id,
                       new DateTime(null, new DateTimeZone('UTC')));
  }
}

function abac_assert($role, $subject_id) {
  $tmpfile = tempnam(sys_get_temp_dir(), "portal");
  // FIXME: Hard coded stuff
  // Run creddy to generate an owner credential
  $cmd_array = array("/usr/local/bin/creddy",
                     "--attribute",
                     "--issuer",
                     "/usr/share/geni-ch/portal/abac/GeniPortal_ID.pem",
                     "--key",
                     "/usr/share/geni-ch/portal/abac/GeniPortal_private.pem",
                     "--role",
                     $role,
                     "--subject-id",
                     $subject_id,
                     "--validity",
                     /* For now, 7 days. Why is granularity days and
                        not seconds? */
                     "7",
                     "--out",
                     $tmpfile
                     );
  $command = implode(" ", $cmd_array);
  /* print "$command<br/>\n"; */
  $result = exec($command, $output, $status);
  if ($status == 0) {
    $abac_attr = file_get_contents($tmpfile);
    unlink($tmpfile);
    return $abac_attr;
  } else {
    trigger_error("creddy exited with error status $status",
                  E_USER_ERROR);
    /* print "status = $status<br/>\n"; */
    /* print "<pre>\n"; */
    /* print_r($output); */
    /* print "</pre>\n"; */
  }
}

?>

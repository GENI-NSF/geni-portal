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
?>
<?php
require_once("settings.php");
require_once("user.php");
$user = geni_loadUser();
if (! $user->isActive()) {
  relative_redirect("home.php");
}
?>
<?php
function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}
$slice = null;
include("tool-lookupids.php");
if (is_null($slice) || $slice == '') {
  no_slice_error();
}

// *** Perhaps this should be GET_ABAC_CREDENTIAL eventuall
if (!$user->isAllowed(SA_ACTION::GET_SLICE_CREDENTIAL`, CS_CONTEXT_TYPE::SLICE, $slice_id)) {
  relative_redirect('home.php');
}

$abac_fingerprint = fetch_abac_fingerprint($user->account_id);
/* print "abac_fingerprint = $abac_fingerprint<br/>\n"; */
$tmpfile = tempnam(sys_get_temp_dir(), "portal");

// TODO: Does the $user have permissions on this $slice?
// TODO: Pass expiration to slicecred.py

$slice_hash = sha1($slice["urn"]);

// Run creddy to generate an owner credential
$cmd_array = array("/usr/local/bin/creddy",
                   "--attribute",
                   "--issuer",
                   "/usr/share/geni-ch/portal/abac/GeniPortal_ID.pem",
                   "--key",
                   "/usr/share/geni-ch/portal/abac/GeniPortal_private.pem",
                   "--role",
                   "Owner_" . $slice_hash,
                   "--subject-id",
                   $abac_fingerprint,
                   "--validity",
                   // For now, 7 days. Why is granularity days and not seconds?
                   "7",
                   "--out",
                   $tmpfile
                   );
$command = implode(" ", $cmd_array);
/* print "$command<br/>\n"; */
$result = exec($command, $output, $status);
/* print "status = $status<br/>\n"; */
/* print "<pre>\n"; */
/* print_r($output); */
/* print "</pre>\n"; */

$abac_attr = file_get_contents($tmpfile);
unlink($tmpfile);

$file = $slice_name . "-abac.der";
/* print "$file<br/>\n"; */
// Set headers for download
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$file");
header("Content-Type: application/der");
header("Content-Transfer-Encoding: binary");
print $abac_attr;
?>

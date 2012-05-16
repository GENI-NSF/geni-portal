<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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
if (! $user->privSlice() || ! $user->isActive()) {
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

// TODO: Does the $user have permissions on this $slice?
// TODO: Pass expiration to slicecred.py


$key = db_fetch_public_key($user->account_id);
if (! $key) {
  include("header.php");
  show_header('GENI Portal: Slices', $TAB_SLICES);
  include("tool-breadcrumbs.php");
  print "<h2>Cannot Download Slice Credential</h2>\n";
  print "This page allows you to download a slice credential file, for use in other tools (e.g. Omni).\n";
  print "This is advanced functionality, not required for typical GENI users.\n";
  print "Please"
    . " <button onClick=\"window.location='" . relative_url("uploadkey.php") . "'\">Upload a public key</button>"
    . " so that a credential can be retrieved.";
  include("footer.php");
  exit();
}

// Get the slice credential from the SA
$slice_credential = get_slice_credential($sa_url, $user, $slice_id);

// FIXME: slice name only unique within project. Need slice URN?
$cred_filename = $slice_name . "-cred.xml";

// Set headers for download
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$cred_filename");
header("Content-Type: text/xml");
header("Content-Transfer-Encoding: binary");
print $slice_credential;
?>

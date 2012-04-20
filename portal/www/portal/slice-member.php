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

require_once("user.php");
require_once("header.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("pa_client.php");
require_once("pa_constants.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Slices', $TAB_SLICES);

$slice = "None";
$slice_name = "None";
$member = "None";
$member_name = "None";
include("tool-lookupids.php");
include("tool-breadcrumbs.php");
if ($slice == "None") {
  print "<h2>Error: Couldn't find slice</h2>";
  include("footer.php");
  exit();
}
if ($member == "None") {
  print "<h2>Error: Couldn't find member</h2>";
  include("footer.php");
  exit();
}
print "<h1>GENI Slice: " . $slice_name . ", Member: " . $member_name . "</h1>\n";


$cs_url = get_first_service_of_type(SR_SERVICE_TYPE::CREDENTIAL_STORE);
$slice_attribs = get_attributes($cs_url, $member_id, CS_CONTEXT_TYPE::SLICE, $slice_id);
//error_log("SLICE ATTRIBS = " . print_r($attributes, true));

print("<b>Slice Roles</b>");
print("\n<table border=\"1\">\n");
print ("<tr><th>Slice</th><th>Role</th></tr>");
foreach($slice_attribs as $attrib) {
  $slice_id = $attrib[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT];
  $slice_link = "<a href=\"slice.php?slice_id=$slice_id\">" . $slice_name . "</a>";
  $role = $attrib[CS_ATTRIBUTE_TABLE_FIELDNAME::NAME];
  print("<tr><td>$slice_link</td><td>$role</td></tr>\n");
}
print("</table>\n\n");




// FIXME: Retrieve info from DB
print "<br/>\n";

/*
print "<form method=\"POST\" action=\"do-edit-slice-member.php\">\n";
print "<b>Slice Permissions</b><br/><br/>\n";
print "<b>Name</b>: " . $member_name . "<br/>\n";
print "<input type=\"hidden\" name=\"slice_id\" value=\"" . $slice_id . "\"/>\n";
print "<input type=\"hidden\" name=\"member_id\" value=\"" . $member_id . "\"/>\n";

// FIXME

print "<input type=\"submit\" value=\"Edit\"/>\n";
print "<input type=\"button\" value=\"Cancel\" onclick=\"history.back(-1)\"/>\n";
print "</form>\n";
*/

include("footer.php");
?>

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
show_header('GENI Portal: Slices', $TAB_SLICES);
$user = geni_loadUser();
$slice = "<None>";
if (array_key_exists("id", $_GET)) {
  $slice = $_GET['id'];
  $slice_item = fetch_slice($slice);
  $slice_name = $slice_item['name'];
  $slice_urn = $slice_item['urn'];
  $slice_owner_id = $slice_item['owner'];
  //  $owner = fetch_user(
  $slice_owner_name = $_item['owner'];
  $owner_email = "owner test";
  $slice_expiration = $slice_item['expiration'];

}

$edit_url = 'edit-slice.php?id='.$slice;
$add_url = 'slice-add-resources.php?id='.$slice;
$res_url = 'sliceresource.php?id='.$slice;
print "<h1>GENI Slice: " . $slice_name ." </h1>\n";
print "<ul>";
print "<li>Slice URN: " . $slice_urn . "</li>";
print "<li>Slice UUID: " . $slice . "</li>";
print "<li>Slice Owner: " . $slice_owner_name . " <a href='mailto:" . $owner_email . "'>e-mail</a>" . "</li>";
print "<li>Slice Expiration: " . $slice_expiration . "</li>";
print "</ul>";

print "Date to renew until: ";
print "<form method='POST' action='do-renew.php'>";
print "<input type='text' name='Renew'/>";
print "<input type='submit' value='Renew'/>";
print "</form>";
print "<br/>";

print "Members:";
print "<ul>";
print "<li>Member 1</li>";
print "</ul>";

print '<ul><li>';
print '<a href='.$edit_url.'>Edit</a>';
print '</li><li>';
print '<a href='.$add_url.'>Add Resources</a>';
print '</li><li>';
print '<a href='.$res_url.'>Resources</a>';
print '</li></ul>';

include("footer.php");
?>

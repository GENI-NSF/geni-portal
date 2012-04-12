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
require_once('util.php');
require_once('pa_constants.php');
require_once('pa_client.php');
require_once('sr_constants.php');
require_once('sr_client.php');
show_header('GENI Portal: Slices', $TAB_SLICES);
$user = geni_loadUser();
$slice = "<None>";
if (array_key_exists("id", $_GET)) {
  $slice = $_GET['id'];
  $slice_item = fetch_slice($slice);
  $name = $slice_item['name'];
  $slice_urn = $slice_item['urn'];
  $slice_owner_id = $slice_item['owner'];
  $owner = geni_loadUser($slice_owner_id);
  $slice_owner_name = $owner->prettyName();
  $owner_email = $owner->email();
  $slice_expiration = $slice_item['expiration'];
}

/* $sr_url = get_sr_url(); */
/* $pa_url = get_first_service_of_type(SR_SERVICE_TYPE::PROJECT_AUTHORITY); */
/* $name = $details[PA_PROJECT_TABLE_FIELDNAME::PROJECT_NAME]; */




$edit_url = 'edit-slice.php?id='.$slice;
$add_url = 'slice-add-resources.php?id='.$slice;
$res_url = 'sliceresource.php?id='.$slice;
print "<h1>GENI Slice: " . $name ." </h1>\n";
print "<b>Name</b>: $name<br/>\n";
print "<b>Warning: Slice name is public</b><br/>\n";
print "<b>Slice URN</b>: $slice_urn<br/>\n";
print "<b>Slice UUID</b>: $slice<br/>\n";
print "<b>Slice Owner</b>: $slice_owner_name <a href='mailto:$owner_email'>e-mail</a><br/>\n";
print "<b>Slice Expiration</b>: $slice_expiration<br/>\n";
print "<b>Other static info</b>: etc<br/>";
print "<a href=$edit_url>Edit Slice</a>";
?>

<br/>
<b>Date to renew until</b>:  <br/>
<form method='POST' action='do-renew.php'>
<input type='text' name='Renew'/>
<input type='submit' value='Renew'/>
</form>

<br/>
<?php
  print "<a href='$add_url'>Add Resources</a>";
?>
<br/>

<?php
if ($user->privAdmin()) {
  print "Approve new slice members<br/>\n";
  print "?Invite new slice member?<br/>\n";
}
?>




<h2>Slice members</h2>
<table border="1">
<tr><th>Slice Member</th><th>Roles</th></tr>
<?php
   // FIXME: See project-member.php. Replace all that with a table or 2 here?
   print "<tr><td><a href=\"project-member.php?id=" . $slice . "&member=joe\">Joe</a></td><td>Lead</td></tr>\n";
?>
</table>

<?php
  print "<br/><a href=\"mailto:$owner_email\">Contact the slice owner: $slice_owner_name</a><br/>\n";
?>

<h2>Recent Slice Actions</h2>
[stuff goes here...]<br/><br/>


<?php
if ($user->privAdmin()) {
  print "<a href=\"delete-slice.php?id=" . $slice . "\">Delete Slice " . $name. "</a><br/>\n";
}



include("footer.php");
?>

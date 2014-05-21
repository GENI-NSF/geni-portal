<?php
//----------------------------------------------------------------------
// Copyright (c) 2014 Raytheon BBN Technologies
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

require_once("settings.php");
require_once("user.php");
require_once("header.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$rspec_id = NULL;
if (array_key_exists('id', $_REQUEST)) {
  $rspec_id = $_REQUEST['id'];
}

if (is_null($rspec_id)) {
  relative_redirect('home.php');
}

/* $rspec is the XML */
$rspec = fetchRSpec($rspec_id);

if (is_null($rspec)) {
  relative_redirect('home.php');
}

/* If I'm not the owner of the rspec, bail. */
$owner = $rspec['owner'];
if (! $owner === $user->account_id) {
  relative_redirect('home.php');
}

show_header('GENI Portal: Edit RSpec', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
//print '<pre>'; print_r($rspec); print '</pre>';

$public_selected = "";
$private_selected = "";
if ($rspec['visibility'] === 'public') {
  $public_selected = "checked";
} else if ($rspec['visibility'] === 'private') {
  $private_selected = "checked";
}

?>
<h1>Edit Resource Specification</h1>
<p>
<form id="edit1" action="rspechandleedit.php">
<input type="hidden" name="id" value="<?php echo $rspec_id; ?>">
Name: <input type="text" name="name" width="120"
             value="<?php echo $rspec['name'];?>"><br>
Description: <input type="text" name="description" width="180"
                    value="<?php echo $rspec['description'];?>"><br>
Visibility:
  <input type="radio" name="visibility" value="public"
         <?php echo $public_selected;?>>Public
  <input type="radio" name="visibility" value="private"
         <?php echo $private_selected;?>>Private<br/>
</form>
<button onclick="$('#edit1').submit()">Submit</button>
<button onclick="window.location='profile.php#rspecs'">Cancel</button>
</p>

<?php
include("footer.php");

?>

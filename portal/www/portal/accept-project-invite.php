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

require_once('header.php');
require_once("user.php");
require_once('sr_constants.php');
require_once('sr_client.php');

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

// error_log("REQUEST = " . print_R($_REQUEST, true));


$invite_id = null;
if(array_key_exists('invite_id', $_REQUEST)) {
  $invite_id = $_REQUEST['invite_id'];
}

$project_name = null;
if(array_key_exists('project_name', $_REQUEST)) {
  $project_name = $_REQUEST['project_name'];
}

if($invite_id == null || $project_name == null) {
  $_SESSION['lasterror'] = "Ill-formed request to accept-project-invite.php";
  relative_redirect("home.php");
}

// FIXME: It would be nice to have the lead name and project description here to help confirm
// this is what you meant to join

show_header('GENI Portal: Projects', $TAB_PROJECTS);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");

print "<h2>Invitation to Join Project: $project_name</h2>";

// error_log("PID = $project_id invite = $invite_id");

print "Click the 'confirm' button to confirm that you wish to accept the invitation to join  project $project_name. Click the 'cancel' button if you do not.";
print "<br/>";

?>

<script type="text/javascript">
  function GoHome() { window.location="home.php"; }
</script>

<?php

print "<form method=\"POST\" action=\"do-accept-project-invite.php?invite_id=$invite_id&project_name=$project_name\"/>";
print "<br/>";
//print "<input type=\"submit\" value=\"Confirm\"/>\n";
print "<button onClick=\"window.location='do-accept-project-invite.php?invite_id=$invite_id&project_name=$project_name'\"><b>Confirm</b></button>";
print "<input type=\"button\" value=\"Cancel\" onclick=\"GoHome()\"/>\n";
print "</form>";

include("footer.php");
?>

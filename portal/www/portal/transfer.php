<?php
//----------------------------------------------------------------------
// Copyright (c) 2017 Raytheon BBN Technologies
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

// Get the authenticated user for logging
$user = geni_loadUser();
if (! isset($user)) {
        relative_redirect('home.php');
}

// Redirect GPO users to the home page. They are not eligible
// for the transfer.
if (preg_match('/@gpolab.bbn.com$/', $user->eppn) !== 0) {
        error_log("transfer rejecting GPO user " . $user->eppn);
        relative_redirect('home.php');
}


$sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
$projects = get_projects_for_member($sa_url, $user, $user->account_id, true);


show_header('GENI Portal: Transfer GPO Account');
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
?>
<script src="transfer.js"></script>
<h1>Transfer GENI Project Office Account</h1>

<?php
if (count($projects) > 0) {
  // -------------------------------------------------------------
  // The user is in at least one project. They'll need to manually
  // migrate their account.
  // -------------------------------------------------------------
?>
<p>
You cannnot transfer your GENI Project Office account to this account
because this account is a member of
<a href="dashboard.php#projects">at least one project</a>.
If your GENI Project Office account is a member of any additional projects
you will need to request to be added to those projects via the
<a href="join-project.php">Join Project</a> page.
</p>
<p>
If you need further assistance, please write to
<a href="mailto:help@geni.net">help@geni.net</a>
</p>
<?php
  exit;
}

// -------------------------------------------------------------------
// Below here is executed if the user is NOT a member of any projects.
// -------------------------------------------------------------------
?>
<p>
In order to transfer the projects and slices from your GENI Project
Office account to this account, please enter your GENI Project Office
username and password below, then click on the "Transfer Account" button.
</p>

<label for="gpo_user">User:</label>
<input type="text" name="gpo_user" id="gpo_user"><br/>
<label for="gpo_pass">Password:</label>
<input type="password" name="gpo_pass" id="gpo_pass"><br/>
<button id="gpo_verify">Transfer Account</button>

<div id="confirm_div" style="display:none;">
  <p>Confirmation text and an execute button go here.</p>
  <button id="confirm_transfer">Confirm</button>
</div>

<?php
include("footer.php");
?>

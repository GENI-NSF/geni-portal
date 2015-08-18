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

require_once("user.php");
require_once("header.php");
require_once('util.php');
$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}
skip_km_authorization();
show_header('GENI Portal: Contact Us');
include("tool-breadcrumbs.php");
$hostname = $_SERVER['SERVER_NAME'];
// Links to wiki, help, tutorials
?>

<h2>Contact Us</h2>

<p>For feedback, issues and questions about the GENI Portal please email <a href="mailto:portal-help@geni.net">portal-help@geni.net</a>.</p>

<p>For technical assistance or advice about your GENI experiment please email the community forum  <a href="mailto:geni-users@googlegroups.com"> geni-users@googlegroups.com</a>.
If you are not already a member please <a href="https://groups.google.com/forum/#!forum/geni-users" target="_blank">register</a> to be able to receive the responses.</p>

<p>To report an issue with an existing or a failed resource reservation made through the portal:</p>
<ol>
<li>Go to the <a href="dashboard.php#logs">Logs</a> tab in the Home page.</li>
<li>Find the log message about the reservation attempt and load the results.</li>
<li>Click on the “Send Problem Report” tab.</li>
</ol>

<p>For all other questions please email <a href="mailto:help@geni.net">help@geni.net</a>.</p>


<?php
include("footer.php");
?>
